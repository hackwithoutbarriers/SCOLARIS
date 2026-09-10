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
        abort_unless($this->canContact($request, $guardian, $template, $request->array('context')), 403);
        $variables = $request->array('variables');
        $result = $manager->whatsapp()->send($guardian, $template, $variables);
        abort_unless($result->url !== null, 422, $result->message ?: 'Lien WhatsApp indisponible.');

        return redirect()->away($result->url);
    }

    public function email(Request $request, Guardian $guardian, string $template, NotificationManager $manager)
    {
        abort_unless($this->canContact($request, $guardian, $template, $request->array('context')), 403);
        $result = $manager->email()->send($guardian, $template, $request->array('variables'));
        abort_unless($result->status !== 'failed', 422, $result->message ?: 'Email indisponible.');

        return back()->with('notification_status', $result->message);
    }

    public static function whatsappUrl(Guardian $guardian, string $template, array $variables, array $context = []): string
    {
        return URL::signedRoute('notifications.whatsapp', [
            'guardian' => $guardian,
            'template' => $template,
            'variables' => $variables,
            'context' => $context,
        ]);
    }

    public static function emailUrl(Guardian $guardian, string $template, array $variables, array $context = []): string
    {
        return URL::signedRoute('notifications.email', [
            'guardian' => $guardian,
            'template' => $template,
            'variables' => $variables,
            'context' => $context,
        ]);
    }

    private function canContact(Request $request, Guardian $guardian, string $template, array $context): bool
    {
        $user = $request->user();
        if (! $user || (! $user->isSuperAdmin() && $guardian->school_id !== $user->school_id)) {
            return false;
        }
        $studentId = (int) ($context['student_id'] ?? $request->input('variables.student_id'));
        if (! $studentId || ! $guardian->students()->whereKey($studentId)->exists()) {
            return false;
        }
        if (in_array($template, ['absence_notification', 'late_notification'], true)) {
            if ($user->isDirector() || $user->isSuperAdmin()) {
                return true;
            }
            $record = AttendanceRecord::query()->with('session')->where('student_id', $studentId)->find($context['attendance_record_id'] ?? null);
            return $user->role === 'teacher' && $record && (
                $record->session->teacher_id === $user->id
                || $user->teacherAssignments()->where('class_room_id', $record->session->class_room_id)->exists()
            );
        }
        if ($template === 'payment_reminder') {
            $invoice = Invoice::query()->whereKey($context['invoice_id'] ?? null)->where('student_id', $studentId)->first();
            return $user->isFinanceOperator() && $invoice?->balance() > 0;
        }
        if ($template === 'report_card_ready') {
            return $user->isAdmin() && ReportCard::query()->whereKey($context['report_card_id'] ?? null)->where('student_id', $studentId)->exists();
        }

        return false;
    }
}
