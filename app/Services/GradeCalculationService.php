<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Enrollment;
use App\Models\MentionThreshold;
use App\Models\Student;
use App\Models\SubjectConfig;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class GradeCalculationService
{
    public function saveGrade(Assessment $assessment, Student $student, float|int|string $score, array $attributes = []): Grade
    {
        if ($assessment->school_id !== $student->school_id) {
            throw ValidationException::withMessages(['student' => 'L’élève et l’évaluation appartiennent à des écoles différentes.']);
        }
        $score = Grade::validateScore($score, $assessment->max_score);
        $normalized = ($score / (float) $assessment->max_score) * 100;
        $band = $this->bandFor($assessment->subjectConfig?->gradeBands() ?? [], $normalized);
        if (! empty($attributes['client_operation_id'])) {
            $existing = Grade::query()->where('school_id', $assessment->school_id)
                ->where('client_operation_id', $attributes['client_operation_id'])->first();
            if ($existing) {
                return $existing;
            }
        }

        $grade = Grade::updateOrCreate(
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
        if ($assessment->term && $assessment->term->grade_validation_status === 'validated') {
            $assessment->term->update(['grade_validation_status' => 'pending', 'grades_validated_at' => null, 'grades_validated_by' => null]);
        }
        if ($assessment->term) {
            app(ReportCardService::class)->refreshDraftRankings($assessment->term);
        }

        return $grade;
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

        $year = $term?->academicYear;
        $classRoom = $year
            ? $student->enrollments()->where('academic_year_id', $year->id)->latest('id')->first()?->classRoom
            : null;
        $subjects = [];
        foreach ($grades->groupBy(fn (Grade $grade) => $grade->assessment->subject_config_id) as $configGrades) {
            $assessmentConfig = $configGrades->first()->assessment->subjectConfig;
            $config = $year && $assessmentConfig?->subject
                ? SubjectConfig::resolveFor($assessmentConfig->subject, $year, $classRoom) ?? $assessmentConfig
                : $assessmentConfig;
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

        $academicMention = $year ? $this->mentionFor($student, $year, $average) : [];

        return [
            'student' => ['id' => $student->id, 'name' => $student->full_name, 'student_number' => $student->student_number],
            'period' => ['term_id' => $term?->id, 'term' => $term?->name],
            'subjects' => $subjects,
            'summary' => ['average' => $average, 'subjects_count' => count($subjects), 'passed_count' => $passed, 'passed' => count($subjects) > 0 && $passed === count($subjects), 'academic_mention' => $academicMention],
            'appreciation' => ['label' => $academicMention['label'] ?? null],
            'academic_mention' => $academicMention,
        ];
    }

    public function calculateStudent(Student|int $student, Term|int|null $term = null): array
    {
        return $this->calculate($student, $term);
    }

    public function rank(Student|int $student, Term|int|null $term = null): ?int
    {
        return $this->rankings($student, $term)['class_rank'];
    }

    /**
     * Competitive ranking: equal scores share a rank and the next rank skips
     * the tied positions (1, 2, 2, 4).
     */
    public function rankings(Student|int $student, Term|int|null $term = null): array
    {
        $student = $student instanceof Student ? $student : Student::findOrFail($student);
        $term = is_int($term) ? Term::findOrFail($term) : $term;
        $year = $term?->academicYear ?? $student->enrollments()->latest('id')->first()?->academicYear;
        $enrollment = $year ? $student->enrollments()->where('academic_year_id', $year->id)->latest('id')->first() : null;
        $classStudents = $enrollment?->class_room_id
            ? Student::query()->whereHas('enrollments', fn ($query) => $query->where('academic_year_id', $year->id)->where('class_room_id', $enrollment->class_room_id)->where('status', 'active'))->get()
            : collect([$student]);
        $generalStudents = $year
            ? Student::query()->where('school_id', $student->school_id)->whereHas('enrollments', fn ($query) => $query->where('academic_year_id', $year->id)->where('status', 'active'))->get()
            : collect([$student]);

        $classRanks = $this->rankCollection($classStudents, $term);
        $generalRanks = $this->rankCollection($generalStudents, $term);
        $subjectRanks = [];
        foreach ($this->calculate($student, $term)['subjects'] as $subject) {
            $subjectId = (int) $subject['subject_id'];
            $subjectRanks[(string) $subjectId] = $this->rankCollection(
                $classStudents,
                $term,
                $subjectId,
            )[$student->id] ?? null;
        }

        $provisional = $this->rankingIsProvisional($term);

        return [
            'class_rank' => $classRanks[$student->id] ?? null,
            'general_rank' => $generalRanks[$student->id] ?? null,
            'subject_ranks' => $subjectRanks,
            'rank' => $classRanks[$student->id] ?? $generalRanks[$student->id] ?? null,
            'provisional' => $provisional,
            'is_provisional' => $provisional,
            'status' => $provisional ? 'provisional' : 'definitive',
        ];
    }

    public function mentionFor(Student $student, AcademicYear $year, float $average): array
    {
        $threshold = MentionThreshold::query()
            ->where('school_id', $student->school_id)
            ->where('academic_year_id', $year->id)
            ->where('active', true)
            ->orderByDesc('minimum_score')
            ->get()
            ->first(fn (MentionThreshold $item): bool => $item->appliesTo($average));

        return $threshold ? [
            'label' => $threshold->label,
            'minimum_score' => (float) $threshold->minimum_score,
            'score' => $average,
            'configured' => true,
        ] : [];
    }

    public function validationState(Term $term): array
    {
        $students = Student::query()->where('school_id', $term->school_id)
            ->whereHas('enrollments', fn ($query) => $query->where('academic_year_id', $term->academic_year_id)->where('status', 'active'))
            ->get();
        $assessments = Assessment::query()->where('term_id', $term->id)->with('grades')->get();
        $missing = [];
        foreach ($assessments as $assessment) {
            foreach ($students as $student) {
                if (! $assessment->grades->contains('student_id', $student->id)) {
                    $missing[] = ['assessment_id' => $assessment->id, 'student_id' => $student->id];
                }
            }
        }
        $anomalies = Grade::query()->whereHas('assessment', fn ($query) => $query->where('term_id', $term->id))
            ->get()->filter(fn (Grade $grade): bool => (float) $grade->normalized_score < 0 || (float) $grade->normalized_score > 100)->values();

        return ['missing' => $missing, 'anomalies' => $anomalies->pluck('id')->all(), 'valid' => $missing === [] && $anomalies->isEmpty()];
    }

    public function validateTerm(Term $term): Term
    {
        if ($term->isClosed()) {
            throw ValidationException::withMessages(['term' => 'Cette période est clôturée.']);
        }
        $state = $this->validationState($term);
        if (! $state['valid']) {
            throw ValidationException::withMessages(['grades' => 'Des notes sont manquantes ou incohérentes.']);
        }
        $term->update(['grade_validation_status' => 'validated', 'grades_validated_at' => now(), 'grades_validated_by' => auth()->id()]);

        return $term->refresh();
    }

    private function rankingIsProvisional(?Term $term): bool
    {
        if (! $term) {
            return false;
        }
        $state = $this->validationState($term);
        $hasAssessments = Assessment::query()->where('term_id', $term->id)->exists();
        return $hasAssessments && (! $state['valid'] || ! $term->gradesAreValidated());
    }

    private function rankCollection(Collection $students, ?Term $term, ?int $subjectId = null): array
    {
        $averages = $students->mapWithKeys(function (Student $item) use ($term, $subjectId): array {
            $subjects = $this->calculate($item, $term)['subjects'];
            if ($subjectId !== null) {
                $subject = collect($subjects)->firstWhere('subject_id', $subjectId);
                return [$item->id => (float) ($subject['average'] ?? 0)];
            }
            return [$item->id => (float) $this->calculate($item, $term)['summary']['average']];
        })->sortDesc();
        $result = [];
        $position = 0;
        $previous = null;
        $rank = 0;
        foreach ($averages as $id => $average) {
            $position++;
            if ($previous === null || abs($average - $previous) > 0.000001) {
                $rank = $position;
            }
            $result[$id] = $rank;
            $previous = $average;
        }

        return $result;
    }

    public function bandFor(array $bands, float $score): array
    {
        $bands = array_values(array_filter($bands, 'is_array'));
        usort($bands, fn (array $a, array $b) => (float) ($b['min'] ?? 0) <=> (float) ($a['min'] ?? 0));
        foreach ($bands as $band) {
            if ($score >= (float) ($band['min'] ?? 0)) {
                return $band;
            }
        }

        return [];
    }

}
