<?php

namespace App\Filament\Widgets;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SchoolStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Students', Student::count())->icon('heroicon-o-academic-cap'),
            Stat::make('Guardians', Guardian::count())->icon('heroicon-o-users'),
            Stat::make('Classrooms', ClassRoom::count())->icon('heroicon-o-building-office-2'),
            Stat::make('Current academic years', AcademicYear::where('is_current', true)->count())->icon('heroicon-o-calendar-days'),
            Stat::make('Enseignants', User::where('role', 'teacher')->count())->icon('heroicon-o-user-group'),
        ];
    }
}
