<?php

namespace App\Filament\Pages;

use App\Http\Controllers\NotificationController;
use App\Models\AbsenceFollowUp;
use App\Models\AttendanceRecord;
use App\Services\AbsenceFollowUpService;
use App\Services\Notifications\SchoolMailerService;
use App\Services\PhoneNumberFormatter;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class AbsenceWorklist extends Page
{
    protected static ?string $navigationGroup = 'Suivi scolaire';

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Suivi des absences et parents';

    protected static ?string $title = 'Suivi des absences et information des parents';

    protected static string $view = 'filament.pages.absence-worklist';

    public string $date;

    public array $followUpStatus = [];

    public array $followUpChannel = [];

    public array $followUpNotes = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAttendanceManager() === true
            || auth()->user()?->isSecretary() === true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function getAbsences(): Collection
    {
        return AttendanceRecord::query()
            ->with([
                'student.guardians',
                'student.school',
                'session.classRoom',
                'session.teacher',
                'followUp',
            ])
            ->whereIn('status', ['ABSENT', 'LATE'])
            ->whereHas('session', fn ($query) => $query->whereDate('session_date', $this->date))
            ->latest('marked_at')
            ->limit(100)
            ->get();
    }

    public function whatsappUrl(AttendanceRecord $record): ?string
    {
        $guardian = $record->student?->guardians
            ?->first(fn ($guardian): bool => (bool) $guardian->pivot?->is_primary)
            ?: $record->student?->guardians?->first();
        if (! $guardian || ! PhoneNumberFormatter::toE164((string) $guardian->phone)) {
            return null;
        }

        return NotificationController::whatsappUrl($guardian, $record->status === 'LATE' ? 'late_notification' : 'absence_notification', [
            'guardian_name' => $guardian->full_name,
            'student_name' => $record->student->full_name,
            'class_name' => $record->session?->classRoom?->name ?: '—',
            'date' => $record->session?->session_date?->format('d/m/Y') ?: '—',
            'school_name' => $record->student->school?->name ?: '',
            'student_id' => $record->student_id,
        ], ['student_id' => $record->student_id, 'attendance_record_id' => $record->id]);
    }

    public function emailUrl(AttendanceRecord $record): ?string
    {
        $guardian = $record->student?->guardians
            ?->first(fn ($guardian): bool => (bool) $guardian->pivot?->is_primary)
            ?: $record->student?->guardians?->first();
        if (! $guardian?->email || ! app(SchoolMailerService::class)->isConfigured($guardian->school)) {
            return null;
        }

        return NotificationController::emailUrl($guardian, $record->status === 'LATE' ? 'late_notification' : 'absence_notification', [
            'guardian_name' => $guardian->full_name, 'student_name' => $record->student->full_name,
            'class_name' => $record->session?->classRoom?->name ?: '—',
            'date' => $record->session?->session_date?->format('d/m/Y') ?: '—',
            'school_name' => $record->student->school?->name ?: '', 'student_id' => $record->student_id,
        ], ['student_id' => $record->student_id, 'attendance_record_id' => $record->id]);
    }

    public function saveFollowUp(int $recordId): void
    {
        $record = AttendanceRecord::query()->whereKey($recordId)->firstOrFail();
        app(AbsenceFollowUpService::class)->update(
            $record,
            auth()->user(),
            $this->followUpStatus[$recordId] ?? 'TO_CONTACT',
            $this->followUpChannel[$recordId] ?? null,
            $this->followUpNotes[$recordId] ?? null,
        );
        $this->dispatch('absence-follow-up-updated');
    }

    public function followUpStatuses(): array
    {
        return AbsenceFollowUp::STATUSES;
    }

    public function summary(): array
    {
        $absences = $this->getAbsences();

        return [
            'total' => $absences->count(),
            'to_contact' => $absences->filter(fn (AttendanceRecord $record): bool => ($record->followUp?->status ?? 'TO_CONTACT') === 'TO_CONTACT')->count(),
            'justified' => $absences->filter(fn (AttendanceRecord $record): bool => $record->followUp?->status === 'JUSTIFIED')->count(),
            'unjustified' => $absences->filter(fn (AttendanceRecord $record): bool => $record->followUp?->status === 'UNJUSTIFIED')->count(),
        ];
    }
}
