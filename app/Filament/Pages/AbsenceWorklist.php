<?php

namespace App\Filament\Pages;

use App\Models\AttendanceRecord;
use App\Http\Controllers\NotificationController;
use App\Services\PhoneNumberFormatter;
use App\Services\Notifications\SchoolMailerService;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class AbsenceWorklist extends Page
{
    protected static ?string $navigationGroup = 'Présence';

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Absences à traiter';

    protected static ?string $title = 'Absences à traiter';

    protected static string $view = 'filament.pages.absence-worklist';

    public string $date;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

    public static function shouldRegisterNavigation(): bool { return static::canAccess(); }

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function getAbsences(): Collection
    {
        return AttendanceRecord::query()
            ->with(['student', 'session.classRoom', 'session.teacher'])
            ->whereIn('status', ['ABSENT', 'LATE'])
            ->whereHas('session', fn ($query) => $query->whereDate('session_date', $this->date))
            ->latest('marked_at')
            ->limit(100)
            ->get();
    }

    public function whatsappUrl(AttendanceRecord $record): ?string
    {
        $guardian = $record->student?->primaryGuardian()->first() ?: $record->student?->guardians()->first();
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
        $guardian = $record->student?->primaryGuardian()->first() ?: $record->student?->guardians()->first();
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
}
