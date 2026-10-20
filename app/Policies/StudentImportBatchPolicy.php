<?php

namespace App\Policies;

use App\Models\StudentImportBatch;
use App\Models\User;

class StudentImportBatchPolicy
{
    public function before(User $user, string $ability, mixed ...$targets): ?bool
    {
        return $user->isSuperAdmin() ? $user->canUseSchoolIntervention(...$targets) : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->school_id !== null
            && ($user->isDirector() || $user->isSecretary());
    }

    public function view(User $user, StudentImportBatch $batch): bool
    {
        return $this->viewAny($user) && (int) $batch->school_id === (int) $user->school_id;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, StudentImportBatch $batch): bool
    {
        return $this->view($user, $batch);
    }

    public function delete(User $user, StudentImportBatch $batch): bool
    {
        return false;
    }
}
