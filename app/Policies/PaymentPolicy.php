<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function before(User $user): ?bool { return $user->isSuperAdmin() ? true : null; }
    public function viewAny(User $user): bool { return $user->isFinanceOperator(); }
    public function view(User $user, Payment $payment): bool { return $user->isFinanceOperator() && $payment->school_id === $user->school_id; }
    public function create(User $user): bool { return $user->isFinanceOperator(); }
    public function update(User $user, Payment $payment): bool { return $user->isDirector() && $payment->school_id === $user->school_id; }
    public function delete(): bool { return false; }
}
