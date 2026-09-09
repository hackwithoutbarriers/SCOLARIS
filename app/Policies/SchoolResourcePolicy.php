<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SchoolResourcePolicy
{
    public function before(User $user): ?bool { return $user->isSuperAdmin() ? true : null; }
    public function viewAny(User $user): bool { return $user->school_id !== null; }
    public function view(User $user, Model $model): bool { return $user->isDirector() && $model->school_id === $user->school_id; }
    public function create(User $user): bool { return $user->isDirector() && $user->school_id !== null; }
    public function update(User $user, Model $model): bool { return $user->isDirector() && $model->school_id === $user->school_id; }
    public function delete(User $user, Model $model): bool { return $user->isDirector() && $model->school_id === $user->school_id; }
}
