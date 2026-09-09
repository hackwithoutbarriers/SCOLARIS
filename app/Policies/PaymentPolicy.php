<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isFinanceOperator();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->is_active && $user->isFinanceOperator() && $payment->school_id === $user->school_id;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isFinanceOperator() && $user->school_id !== null;
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->is_active && $user->isDirector() && $payment->school_id === $user->school_id;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->is_active && $user->isDirector() && $payment->school_id === $user->school_id;
    }
}
