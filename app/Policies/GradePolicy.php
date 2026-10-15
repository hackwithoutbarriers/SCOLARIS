<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;

class GradePolicy
{
    public function before(User $user, string $ability, mixed ...$targets): ?bool
    {
        return $user->isSuperAdmin() ? $user->canUseSchoolIntervention(...$targets) : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_active
            && ($user->isDirector() || $user->isCenseur() || $user->hasRole('teacher'))
            && $user->school_id !== null;
    }

    public function view(User $user, Grade $grade): bool
    {
        if (! $this->viewAny($user) || ! $user->canAccessSchool((int) $grade->school_id)) {
            return false;
        }

        return $user->isCenseur()
            || $user->can('manageGrades', [$grade->assessment, $grade->student]);
    }

    public function create(User $user): bool
    {
        return $user->is_active
            && $user->school_id !== null
            && ($user->isDirector() || $user->hasRole('teacher'));
    }

    public function update(User $user, Grade $grade): bool
    {
        return ! $user->isCenseur()
            && ! $grade->validated_at
            && $grade->assessment?->term?->status !== 'closed'
            && $this->view($user, $grade);
    }

    public function delete(User $user, Grade $grade): bool
    {
        return $this->update($user, $grade);
    }
}
