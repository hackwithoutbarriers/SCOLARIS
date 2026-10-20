<?php

namespace App\Policies;

use App\Models\StudentImportRow;
use App\Models\User;

class StudentImportRowPolicy
{
    public function before(User $user, string $ability, mixed ...$targets): ?bool
    {
        return $user->isSuperAdmin() ? $user->canUseSchoolIntervention(...$targets) : null;
    }

    public function view(User $user, StudentImportRow $row): bool
    {
        $batch = $row->batch;

        return $batch !== null
            && (int) $batch->school_id === (int) $row->school_id
            && $user->can('view', $batch);
    }

    public function update(User $user, StudentImportRow $row): bool
    {
        return $this->view($user, $row);
    }

    public function delete(User $user, StudentImportRow $row): bool
    {
        return false;
    }
}
