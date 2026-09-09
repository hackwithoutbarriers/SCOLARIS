<?php

namespace App\Policies;

use App\Models\User;

class SchoolPolicy
{
    public function view(User $user, $school): bool { return $user->school_id === $school->id; }
    public function update(User $user, $school): bool { return $user->school_id === $school->id && $user->isAdmin(); }
}
