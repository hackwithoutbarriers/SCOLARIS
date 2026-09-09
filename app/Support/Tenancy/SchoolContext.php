<?php

namespace App\Support\Tenancy;

use App\Models\School;
use App\Models\User;
use Illuminate\Auth\SessionGuard;

final class SchoolContext
{
    private ?int $schoolId = null;

    public function set(?int $schoolId): void
    {
        $this->schoolId = $schoolId;
    }

    public function setSchool(?School $school): void
    {
        $this->set($school?->getKey());
    }

    public function id(): ?int
    {
        if ($this->schoolId !== null) {
            return $this->schoolId;
        }

        $guard = auth()->guard();
        if ($guard->hasUser()) {
            return $guard->user()?->school_id;
        }

        if ($guard instanceof SessionGuard) {
            $userId = $guard->getSession()->get($guard->getName());
            if ($userId) {
                return User::withoutGlobalScopes()->whereKey($userId)->value('school_id');
            }
        }

        return null;
    }

    public function clear(): void
    {
        $this->schoolId = null;
    }
}
