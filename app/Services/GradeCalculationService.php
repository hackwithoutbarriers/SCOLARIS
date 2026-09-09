<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class GradeCalculationService
{
    public function saveGrade(Assessment $assessment, Student $student, float|int|string $score, array $attributes = []): Grade
    {
        if ($assessment->school_id !== $student->school_id) {
            throw ValidationException::withMessages(['student' => 'The student and assessment belong to different schools.']);
        }
        $score = Grade::validateScore($score, $assessment->max_score);
        $normalized = ($score / (float) $assessment->max_score) * 100;
        $band = $this->bandFor($assessment->subjectConfig?->gradeBands() ?? [], $normalized);

        return Grade::updateOrCreate(
            ['assessment_id' => $assessment->id, 'student_id' => $student->id],
            array_merge($attributes, [
                'school_id' => $assessment->school_id,
                'score' => $score,
                'normalized_score' => $normalized,
                'grade_letter' => $band['letter'] ?? null,
                'graded_by' => $attributes['graded_by'] ?? auth()->id(),
                'validated_at' => $attributes['validated_at'] ?? now(),
            ]),
        );
    }

    /** @return array{student:array,period:array,subjects:array,summary:array,appreciation:array} */
    public function calculate(Student|int $student, Term|int|null $term = null): array
    {
        $student = $student instanceof Student ? $student : Student::findOrFail($student);
        $term = is_int($term) ? Term::findOrFail($term) : $term;
        $grades = Grade::query()
            ->with(['assessment.subjectConfig.subject', 'assessment.term'])
            ->where('student_id', $student->id)
            ->when($term, fn ($query) => $query->whereHas('assessment', fn ($q) => $q->where('term_id', $term->id)))
            ->get();

        $subjects = [];
        foreach ($grades->groupBy(fn (Grade $grade) => $grade->assessment->subject_config_id) as $configGrades) {
            $config = $configGrades->first()->assessment->subjectConfig;
            $weightTotal = 0.0;
            $weightedTotal = 0.0;
            $assessments = [];
            foreach ($configGrades as $grade) {
                $assessmentWeight = (float) $grade->assessment->weight;
                $weightTotal += $assessmentWeight;
                $weightedTotal += (float) $grade->normalized_score * $assessmentWeight;
                $assessments[] = [
                    'id' => $grade->assessment_id,
                    'title' => $grade->assessment->title,
                    'score' => (float) $grade->score,
                    'max_score' => (float) $grade->assessment->max_score,
                    'normalized_score' => (float) $grade->normalized_score,
                    'grade_letter' => $grade->grade_letter,
                ];
            }
            $average = $config->grading_method === 'simple_average'
                ? (count($configGrades) ? array_sum(array_map(fn (Grade $grade) => (float) $grade->normalized_score, $configGrades->all())) / count($configGrades) : 0.0)
                : ($weightTotal > 0 ? $weightedTotal / $weightTotal : 0.0);
            $band = $this->bandFor($config->gradeBands(), $average);
            $subjects[] = [
                'subject_id' => $config->subject_id,
                'subject' => $config->subject?->name,
                'weight' => (float) $config->weight,
                'average' => $average,
                'passing_score' => (float) $config->passing_score,
                'passed' => $average >= (float) $config->passing_score,
                'grade_letter' => $band['letter'] ?? null,
                'label' => $band['label'] ?? null,
                'assessments' => $assessments,
            ];
        }

        $subjectWeight = array_sum(array_column($subjects, 'weight'));
        $average = $subjectWeight > 0
            ? array_sum(array_map(fn (array $subject) => $subject['average'] * $subject['weight'], $subjects)) / $subjectWeight
            : 0.0;
        $passed = count($subjects) ? count(array_filter($subjects, fn (array $subject) => $subject['passed'])) : false;
        return [
            'student' => ['id' => $student->id, 'name' => $student->full_name, 'student_number' => $student->student_number],
            'period' => ['term_id' => $term?->id, 'term' => $term?->name],
            'subjects' => $subjects,
            'summary' => ['average' => $average, 'subjects_count' => count($subjects), 'passed_count' => $passed, 'passed' => count($subjects) > 0 && $passed === count($subjects)],
            'appreciation' => ['label' => $this->appreciationFor($average)],
        ];
    }

    public function calculateStudent(Student|int $student, Term|int|null $term = null): array { return $this->calculate($student, $term); }

    public function rank(Student|int $student, Term|int|null $term = null): ?int
    {
        $student = $student instanceof Student ? $student : Student::findOrFail($student);
        $term = is_int($term) ? Term::findOrFail($term) : $term;
        $students = Student::query()->where('school_id', $student->school_id)->get();
        $averages = $students->mapWithKeys(fn (Student $item) => [$item->id => $this->calculate($item, $term)['summary']['average']])->sortDesc();
        $rank = 1;
        foreach ($averages as $id => $average) {
            if ($id === $student->id) return $rank;
            $rank++;
        }
        return null;
    }

    public function bandFor(array $bands, float $score): array
    {
        $bands = array_values(array_filter($bands, 'is_array'));
        usort($bands, fn (array $a, array $b) => (float) ($b['min'] ?? 0) <=> (float) ($a['min'] ?? 0));
        foreach ($bands as $band) {
            if ($score >= (float) ($band['min'] ?? 0)) return $band;
        }
        return [];
    }

    public function appreciationFor(float $average): string
    {
        return match (true) {
            $average >= 90 => 'Excellent',
            $average >= 80 => 'Very good',
            $average >= 70 => 'Good',
            $average >= 50 => 'Satisfactory',
            default => 'Needs improvement',
        };
    }
}
