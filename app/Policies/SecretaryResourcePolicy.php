<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SecretaryResourcePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_active && ($user->isDirector() || $user->role === 'secretary') && $user->school_id !== null;
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user) && $model->school_id === $user->school_id;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->view($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->view($user, $model);
    }
}
