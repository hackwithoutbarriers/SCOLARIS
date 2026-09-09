<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AcademicPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->school_id !== null && $user->is_active;
    }

    public function view(User $user, Model $model): bool
    {
        return $model->school_id === $user->school_id
            && $user->is_active
            && ($user->isDirector() || ($model instanceof Student && $this->viewGrades($user, $model)));
    }

    public function create(User $user): bool
    {
        return $user->isDirector() && $user->school_id !== null && $user->is_active;
    }

    public function update(User $user, Model $model): bool
    {
        return $user->isDirector() && $model->school_id === $user->school_id && $user->is_active;
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->update($user, $student);
    }

    public function viewGrades(User $user, Student $student): bool
    {
        if ($student->school_id !== $user->school_id || !$user->is_active) {
            return false;
        }

        if ($user->isDirector()) {
            return true;
        }

        return $user->role === 'teacher'
            && $student->enrollments()
                ->whereHas('classRoom.teacherAssignments', fn ($query) => $query->where('teacher_id', $user->id))
                ->exists();
    }

    public function manageGrades(User $user, Assessment $assessment, Student $student): bool
    {
        if ($assessment->school_id !== $user->school_id || $student->school_id !== $user->school_id) {
            return false;
        }

        if ($user->isDirector()) {
            return true;
        }

        return $user->role === 'teacher'
            && $assessment->teacher_id === $user->id
            && $this->viewGrades($user, $student);
    }

    public function viewReportCard(User $user, ReportCard $reportCard): bool
    {
        if ($reportCard->school_id !== $user->school_id || !$user->is_active) {
            return false;
        }

        if ($user->isDirector()) {
            return true;
        }

        return $user->role === 'teacher'
            && $this->viewGrades($user, $reportCard->student);
    }
}
