<?php

namespace App\Filament\Widgets;

use App\Models\Grade;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\SubjectConfig;
use App\Support\DashboardPeriod;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AcademicOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $period = DashboardPeriod::resolve($this->filters['period'] ?? null, $this->filters['from'] ?? null, $this->filters['to'] ?? null);
        $evaluated = Grade::query()->whereBetween('created_at', [$period['from'], $period['to']])->distinct('student_id')->count('student_id');
        $average = (float) Grade::query()->whereBetween('created_at', [$period['from'], $period['to']])->avg('normalized_score');
        $missing = SubjectConfig::query()->where('active', true)->whereDoesntHave('assessments.grades')->count();

        return [
            Stat::make('Élèves évalués', $evaluated ?: Student::query()->whereBetween('created_at', [$period['from'], $period['to']])->count())->description($period['label'])->color('primary'),
            Stat::make('Moyenne générale', number_format($average, 2).' %')->color($average >= 50 ? 'success' : 'warning'),
            Stat::make('Matières sans notes', $missing)->color($missing ? 'warning' : 'success'),
            Stat::make('Bulletins brouillon', ReportCard::query()->where('status', 'draft')->count())->color('warning'),
            Stat::make('Bulletins à valider', ReportCard::query()->whereIn('status', ['review', 'approved'])->count())->color('primary'),
            Stat::make('Bulletins publiés', ReportCard::query()->where('status', 'published')->count())->color('success'),
        ];
    }
}
