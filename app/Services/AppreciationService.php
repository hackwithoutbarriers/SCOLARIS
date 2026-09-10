<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Appreciation;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Support\Facades\DB;

final class AppreciationService
{
    public function __construct(private readonly GradeCalculationService $calculator) {}

    /**
     * Rebuilds deterministic rankings for a school period. Ties share a rank.
     *
     * @return array<int, Appreciation>
     */
    public function generate(int $schoolId, AcademicYear $year, ?Term $term = null): array
    {
        $students = Student::withoutGlobalScopes()->where('school_id', $schoolId)->get();
        $rows = $students->map(fn (Student $student) => [
            'student' => $student,
            'average' => (float) $this->calculator->calculate($student, $term)['summary']['average'],
        ])->sortByDesc('average')->values()->all();
        $result = [];
        $rank = 0;
        $lastAverage = null;
        foreach ($rows as $index => $row) {
            if ($lastAverage === null || $row['average'] < $lastAverage) $rank = $index + 1;
            $lastAverage = $row['average'];
            $result[] = DB::transaction(fn () => Appreciation::withoutGlobalScopes()->updateOrCreate(
                ['school_id' => $schoolId, 'student_id' => $row['student']->id, 'academic_year_id' => $year->id, 'term_id' => $term?->id],
                [
                    'average_score' => $row['average'],
                    'rank' => $rank,
                    'label' => $this->calculator->mentionFor($row['student'], $year, $row['average'])['label'] ?? null,
                ],
            ));
        }
        return $result;
    }
}
