<?php

namespace App\Services;

use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

final class AcademicYearService
{
    public function activate(AcademicYear $year): AcademicYear
    {
        return DB::transaction(function () use ($year): AcademicYear {
            AcademicYear::withoutGlobalScopes()
                ->where('school_id', $year->school_id)
                ->whereKeyNot($year->getKey())
                ->where(function ($query): void {
                    $query->where('status', 'active')->orWhere('is_current', true);
                })
                ->update(['status' => 'closed', 'is_current' => false]);

            $year->forceFill(['status' => 'active', 'is_current' => true])->save();
            return $year->refresh();
        });
    }

    public function close(AcademicYear $year): AcademicYear
    {
        $year->forceFill(['status' => 'closed', 'is_current' => false])->save();
        return $year->refresh();
    }
}
