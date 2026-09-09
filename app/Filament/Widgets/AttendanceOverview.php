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

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() === true;
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
}
