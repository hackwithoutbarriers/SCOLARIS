<?php

namespace App\Filament\Pages;

use App\Models\AttendanceRecord;
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

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function getAbsences(): Collection
    {
        return AttendanceRecord::query()
            ->with(['student', 'session.classRoom', 'session.teacher'])
            ->where('status', 'ABSENT')
            ->whereHas('session', fn ($query) => $query->whereDate('session_date', $this->date))
            ->latest('marked_at')
            ->limit(100)
            ->get();
    }
}
