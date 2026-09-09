<?php
namespace App\Services;
use App\Models\Student;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
class NotificationPolicy {
    public function shouldNotify(string $event, Student $student, ?int $lateMinutes = null): bool {
        if ($event === 'ABSENT') return (bool) config('attendance.notifications.absent_after_validation', true);
        if ($event === 'LATE') return $lateMinutes !== null && $lateMinutes > (int) config('attendance.notifications.late_threshold_minutes', 30);
        return false;
    }

    public function eventFor(AttendanceRecord $record): ?string
    {
        return match ($record->status) {
            'ABSENT' => 'ABSENT',
            'LATE' => 'LATE',
            default => null,
        };
    }
}
