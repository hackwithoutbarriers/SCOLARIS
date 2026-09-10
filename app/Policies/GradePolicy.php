<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;

class GradePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_active && ($user->isDirector() || $user->role === 'teacher') && $user->school_id !== null;
    }

    public function view(User $user, Grade $grade): bool
    {
        return $this->viewAny($user)
            && $grade->school_id === $user->school_id
            && $user->can('manageGrades', [$grade->assessment, $grade->student]);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Grade $grade): bool
    {
        return ! $grade->validated_at
            && $grade->assessment?->term?->status !== 'closed'
            && $this->view($user, $grade);
    }

    public function delete(User $user, Grade $grade): bool
    {
        return $this->update($user, $grade);
    }
}
