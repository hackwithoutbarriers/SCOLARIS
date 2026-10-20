<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\ReportCard;
use App\Services\Notifications\NotificationManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class NotificationController extends Controller
{
    public function whatsapp(Request $request, Guardian $guardian, string $template, NotificationManager $manager)
    {
        $context = $request->array('context');
        abort_unless($this->canContact($request, $guardian, $template, $context), 403);
        $variables = $this->variablesForContact($guardian, $template, $context);
        $result = $manager->whatsapp()->send($guardian, $template, $variables);
        abort_unless($result->url !== null, 422, $result->message ?: 'Lien WhatsApp indisponible.');

        return redirect()->away($result->url);
    }

    public function email(Request $request, Guardian $guardian, string $template, NotificationManager $manager)
    {
        $context = $request->array('context');
        abort_unless($this->canContact($request, $guardian, $template, $context), 403);
        $result = $manager->email()->send($guardian, $template, $this->variablesForContact($guardian, $template, $context));
        abort_unless($result->status !== 'failed', 422, $result->message ?: 'Email indisponible.');

        return back()->with('notification_status', $result->message);
    }

    public static function whatsappUrl(Guardian $guardian, string $template, array $variables, array $context = []): string
    {
        unset($context['student_id']);

        return URL::signedRoute('notifications.whatsapp', [
            'guardian' => $guardian,
            'template' => $template,
            'context' => $context,
        ]);
    }

    public static function emailUrl(Guardian $guardian, string $template, array $variables, array $context = []): string
    {
        unset($context['student_id']);

        return URL::signedRoute('notifications.email', [
            'guardian' => $guardian,
            'template' => $template,
            'context' => $context,
        ]);
    }

    private function canContact(Request $request, Guardian $guardian, string $template, array $context): bool
    {
        $user = $request->user();
        if (! $user || ! $user->canAccessSchool((int) $guardian->school_id)) {
            return false;
        }
        if (in_array($template, ['absence_notification', 'late_notification'], true)) {
            $record = AttendanceRecord::query()->with(['session', 'student'])
                ->find($context['attendance_record_id'] ?? null);
            if (! $record || $record->school_id !== $guardian->school_id
                || (int) $record->session?->school_id !== (int) $guardian->school_id
                || (int) $record->student?->school_id !== (int) $guardian->school_id
                || ! $guardian->students()->whereKey($record->student_id)->exists()
                || ($template === 'absence_notification' && $record->status !== 'ABSENT')
                || ($template === 'late_notification' && $record->status !== 'LATE')) {
                return false;
            }

            return $user->isAttendanceManager()
                || $user->isSecretary()
                || ($user->hasRole('teacher') && (
                    $record->session->teacher_id === $user->id
                    || $user->teacherAssignments()
                        ->where('class_room_id', $record->session->class_room_id)
                        ->where('academic_year_id', $record->session->academic_year_id)
                        ->exists()
                ));
        }
        if ($template === 'payment_reminder') {
            $invoice = Invoice::query()->with('student')->whereKey($context['invoice_id'] ?? null)
                ->where('school_id', $guardian->school_id)
                ->first();

            return $user->isFinanceOperator()
                && $invoice
                && (int) $invoice->student?->school_id === (int) $guardian->school_id
                && $guardian->students()->whereKey($invoice->student_id)->exists()
                && $invoice->balance() > 0;
        }
        if ($template === 'report_card_ready') {
            $reportCard = ReportCard::query()->with('student')
                ->whereKey($context['report_card_id'] ?? null)
                ->where('school_id', $guardian->school_id)
                ->where('status', 'published')
                ->first();

            return ($user->isAdmin() || $user->isCenseur())
                && $reportCard
                && (int) $reportCard->student?->school_id === (int) $guardian->school_id
                && $guardian->students()->whereKey($reportCard->student_id)->exists();
        }

        return false;
    }

    private function variablesForContact(Guardian $guardian, string $template, array $context): array
    {
        $target = match ($template) {
            'absence_notification', 'late_notification' => AttendanceRecord::query()
                ->with(['session.classRoom', 'student.school'])
                ->findOrFail($context['attendance_record_id']),
            'payment_reminder' => Invoice::query()
                ->with('student.school')
                ->where('school_id', $guardian->school_id)
                ->findOrFail($context['invoice_id']),
            'report_card_ready' => ReportCard::query()
                ->with('student.school')
                ->where('school_id', $guardian->school_id)
                ->where('status', 'published')
                ->findOrFail($context['report_card_id']),
            default => abort(403),
        };
        $student = $target->student;
        abort_unless(
            $student
                && (int) $student->school_id === (int) $guardian->school_id
                && $guardian->students()->whereKey($student->id)->exists(),
            403
        );
        $variables = [
            'guardian_name' => $guardian->full_name,
            'student_name' => $student->full_name,
            'school_name' => $student->school?->name ?: '',
            'student_id' => $student->id,
        ];

        if (in_array($template, ['absence_notification', 'late_notification'], true)) {
            /** @var AttendanceRecord $record */
            $record = $target;

            return array_merge($variables, [
                'class_name' => $record->session?->classRoom?->name ?: '—',
                'date' => $record->session?->session_date?->format('d/m/Y') ?: '—',
            ]);
        }

        if ($template === 'payment_reminder') {
            /** @var Invoice $invoice */
            $invoice = $target;

            return array_merge($variables, [
                'amount' => number_format($invoice->balance(), 0, ',', ' ').' '.$invoice->currency,
            ]);
        }

        if ($template === 'report_card_ready') {
            /** @var ReportCard $reportCard */
            $reportCard = $target;

            return array_merge($variables, [
                'report_card_url' => route('report-cards.pdf', $reportCard),
            ]);
        }

        abort(403);
    }
}
