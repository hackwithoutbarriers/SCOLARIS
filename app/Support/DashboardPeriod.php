<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Term;
use Carbon\Carbon;

final class DashboardPeriod
{
    /** @return array{from:Carbon,to:Carbon,label:string} */
    public static function resolve(?string $mode = null, ?string $from = null, ?string $to = null): array
    {
        $mode ??= request()->query('period', 'today');
        if ($mode === 'custom' && $from && $to) {
            return ['from' => Carbon::parse($from)->startOfDay(), 'to' => Carbon::parse($to)->endOfDay(), 'label' => 'Période personnalisée'];
        }
        if ($mode === 'active_term') {
            $term = Term::query()->whereDate('starts_at', '<=', today())->whereDate('ends_at', '>=', today())->first();
            if ($term) {
                return ['from' => Carbon::parse($term->starts_at)->startOfDay(), 'to' => Carbon::parse($term->ends_at)->endOfDay(), 'label' => $term->name];
            }
        }
        if ($mode === 'academic_year') {
            $year = AcademicYear::query()->where('is_current', true)->orWhere('status', 'active')->first();
            if ($year) {
                return ['from' => Carbon::parse($year->start_date ?? $year->starts_at)->startOfDay(), 'to' => Carbon::parse($year->end_date ?? $year->ends_at)->endOfDay(), 'label' => $year->name];
            }
        }

        return ['from' => now()->startOfDay(), 'to' => now()->endOfDay(), 'label' => 'Aujourd’hui'];
    }
}
