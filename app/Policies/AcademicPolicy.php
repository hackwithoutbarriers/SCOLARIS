<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AcademicPolicy
{
    public function before(User $user, string $ability, mixed ...$targets): ?bool
    {
        return $user->isSuperAdmin() ? $user->canUseSchoolIntervention(...$targets) : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->school_id !== null && $user->is_active;
    }

    public function view(User $user, Model $model): bool
    {
        return $user->canAccessSchool((int) $model->school_id)
            && $user->is_active
            && ($user->isDirector()
                || ($model instanceof Student && ($user->isSecretary() || $this->viewGrades($user, $model))));
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->school_id !== null && $user->isDirector();
    }

    public function update(User $user, Model $model): bool
    {
        return $model instanceof Student
            ? $user->is_active
                && $model->school_id === $user->school_id
                && ($user->isDirector() || $user->isSecretary())
            : $user->isDirector() && $model->school_id === $user->school_id && $user->is_active;
    }

    public function delete(User $user, Model $model): bool
    {
        if ($model instanceof Student) {
            return false;
        }

        return $this->update($user, $model);
    }

    public function viewGrades(User $user, Student $student, ?Term $term = null): bool
    {
        if (! $user->canAccessSchool((int) $student->school_id)
            || ($term && $term->school_id !== $student->school_id)) {
            return false;
        }

        if ($user->isDirector()) {
            return true;
        }

        if ($user->isCenseur()) {
            return $student->enrollments()
                ->when($term, fn ($query) => $query->where('academic_year_id', $term->academic_year_id))
                ->exists();
        }

        return $user->hasRole('teacher')
            && $student->enrollments()
                ->whereHas(
                    'classRoom.teacherAssignments',
                    fn ($assignment) => $assignment
                        ->where('teacher_id', $user->id)
                        ->whereColumn('teacher_assignments.academic_year_id', 'enrollments.academic_year_id')
                )
                ->when($term, fn ($query) => $query->where('academic_year_id', $term->academic_year_id))
                ->exists();
    }

    public function manageGrades(User $user, Assessment $assessment, Student $student): bool
    {
        if (! $user->canAccessSchool((int) $assessment->school_id)
            || ! $user->canAccessSchool((int) $student->school_id)) {
            return false;
        }

        if ($user->isDirector()) {
            return true;
        }

        if (! $user->hasRole('teacher') || $assessment->teacher_id !== $user->id || ! $this->viewGrades($user, $student)) {
            return false;
        }

        $assessment->loadMissing('subjectConfig');

        return TeacherAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('academic_year_id', $assessment->subjectConfig?->academic_year_id)
            ->where('subject_id', $assessment->subjectConfig?->subject_id)
            ->whereIn(
                'class_room_id',
                $student->enrollments()
                    ->where('academic_year_id', $assessment->subjectConfig?->academic_year_id)
                    ->select('class_room_id')
            )
            ->exists();
    }

    public function viewReportCard(User $user, ReportCard $reportCard): bool
    {
        if (! $user->canAccessSchool((int) $reportCard->school_id)) {
            return false;
        }

        if ($user->isDirector()) {
            return true;
        }

        if ($user->isCenseur()) {
            return $reportCard->student->enrollments()
                ->where('academic_year_id', $reportCard->academic_year_id)
                ->exists();
        }

        return $user->hasRole('teacher')
            && $reportCard->student->enrollments()
                ->where('academic_year_id', $reportCard->academic_year_id)
                ->whereHas(
                    'classRoom.teacherAssignments',
                    fn ($assignment) => $assignment
                        ->where('teacher_id', $user->id)
                        ->whereColumn('teacher_assignments.academic_year_id', 'enrollments.academic_year_id')
                )
                ->exists();
    }
}
