<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function before(User $user, string $ability, mixed ...$targets): ?bool
    {
        if (in_array($ability, ['update', 'delete'], true)) {
            return false;
        }

        return $user->isSuperAdmin() ? $user->canUseSchoolIntervention(...$targets) : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isFinanceOperator();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->isFinanceOperator() && $user->canAccessSchool((int) $payment->school_id);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isFinanceOperator() && $user->school_id !== null;
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }

    /** Reversals are the audited correction mechanism and remain director-only. */
    public function reverse(User $user, Payment $payment): bool
    {
        return $user->is_active
            && ($user->isDirector() || ($user->isSuperAdmin() && $user->hasActiveSchoolIntervention((int) $payment->school_id)))
            && $user->canAccessSchool((int) $payment->school_id);
    }
}
