<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use Filament\Widgets\Widget;

class AttendanceOverview extends Widget
{
    protected static string $view = 'filament.widgets.attendance-overview';

    protected static ?int $sort = 1;

    public ?string $date = null;

    public ?int $classRoomId = null;

    public string $period = 'today';

    public static function canView(): bool
    {
        return auth()->user()?->isAttendanceManager() === true;
    }

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function getClassOptions(): array
    {
        return ClassRoom::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function getStats(): array
    {
        $records = AttendanceRecord::query()->whereHas('session', function ($query): void {
            $query->whereDate('session_date', $this->date ?: now()->toDateString());
            if ($this->classRoomId) {
                $query->where('class_room_id', $this->classRoomId);
            }
        });
        $total = (clone $records)->count();
        $present = (clone $records)->where('status', 'PRESENT')->count();

        return [
            'rate' => $total ? round(($present / $total) * 100, 1).'%' : '0%',
            'absent' => (clone $records)->where('status', 'ABSENT')->count(),
            'late' => (clone $records)->where('status', 'LATE')->count(),
            'unvalidated' => ClassRoom::query()
                ->when($this->classRoomId, fn ($query) => $query->whereKey($this->classRoomId))
                ->whereDoesntHave('attendanceSessions', function ($query): void {
                    $query->whereDate('session_date', $this->date ?: now()->toDateString())
                        ->where('status', 'VALIDATED');
                })->count(),
        ];
    }

    public function getSupervision(): array
    {
        $end = now()->endOfDay();
        $start = match ($this->period) {
            'seven_days' => now()->subDays(6)->startOfDay(),
            'thirty_days' => now()->subDays(29)->startOfDay(),
            'academic_year' => now()->subYear()->startOfDay(),
            default => now()->startOfDay(),
        };

        $records = AttendanceRecord::query()
            ->whereIn('status', ['ABSENT', 'LATE'])
            ->whereHas('session', function ($query) use ($start, $end): void {
                $query->whereBetween('session_date', [$start->toDateString(), $end->toDateString()]);
                if ($this->classRoomId) {
                    $query->where('class_room_id', $this->classRoomId);
                }
            });

        $recurrent = (clone $records)
            ->select('student_id')
            ->selectRaw('COUNT(*) as absence_count')
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) >= 3')
            ->with('student.currentEnrollment.classRoom')
            ->orderByDesc('absence_count')
            ->limit(10)
            ->get();

        return [
            'to_contact' => (clone $records)->whereHas('followUp', fn ($query) => $query->where('status', 'TO_CONTACT'))->count(),
            'unjustified' => (clone $records)->whereHas('followUp', fn ($query) => $query->where('status', 'UNJUSTIFIED'))->count(),
            'to_monitor' => (clone $records)->whereHas('followUp', fn ($query) => $query->where('status', 'TO_MONITOR'))->count(),
            'recurrent' => $recurrent,
            'classes' => (clone $records)
                ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
                ->join('class_rooms', 'class_rooms.id', '=', 'attendance_sessions.class_room_id')
                ->selectRaw('class_rooms.name, COUNT(*) as absence_count')
                ->groupBy('class_rooms.id', 'class_rooms.name')
                ->orderByDesc('absence_count')
                ->limit(5)
                ->get(),
        ];
    }

    public function getPeriodOptions(): array
    {
        return [
            'today' => 'Aujourd’hui',
            'seven_days' => '7 derniers jours',
            'thirty_days' => '30 derniers jours',
            'academic_year' => 'Année scolaire',
        ];
    }
}
