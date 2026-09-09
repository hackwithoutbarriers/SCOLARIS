<?php

namespace App\Filament\Widgets;

use App\Models\Grade;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\SubjectConfig;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AcademicOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $evaluated = Grade::query()->distinct('student_id')->count('student_id');
        $average = (float) Grade::query()->avg('normalized_score');
        $missing = SubjectConfig::query()->where('active', true)->whereDoesntHave('assessments.grades')->count();

        return [
            Stat::make('Élèves évalués', $evaluated ?: Student::query()->count())->color('primary'),
            Stat::make('Moyenne générale', number_format($average, 2).' %')->color($average >= 50 ? 'success' : 'warning'),
            Stat::make('Matières sans notes', $missing)->color($missing ? 'warning' : 'success'),
            Stat::make('Bulletins brouillon', ReportCard::query()->where('status', 'draft')->count())->color('warning'),
            Stat::make('Bulletins à valider', ReportCard::query()->whereIn('status', ['review', 'approved'])->count())->color('primary'),
            Stat::make('Bulletins publiés', ReportCard::query()->where('status', 'published')->count())->color('success'),
        ];
    }
}
