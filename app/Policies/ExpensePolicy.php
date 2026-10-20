<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function before(User $user, string $ability, mixed ...$targets): ?bool
    {
        return $user->isSuperAdmin() ? $user->canUseSchoolIntervention(...$targets) : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isFinanceOperator();
    }

    public function view(User $user, Expense $expense): bool
    {
        return $this->viewAny($user) && $expense->school_id === $user->school_id;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->school_id !== null;
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->is_active && $user->isDirector() && $expense->school_id === $user->school_id;
    }

    public function reverse(User $user, Expense $expense): bool
    {
        return $this->delete($user, $expense);
    }

    public function approve(User $user, Expense $expense): bool
    {
        return $user->isDirector() && $expense->school_id === $user->school_id && $expense->approval_status === 'pending';
    }

    public function reject(User $user, Expense $expense): bool
    {
        return $this->approve($user, $expense);
    }

    public function resubmit(User $user, Expense $expense): bool
    {
        return $user->is_active && $user->hasRole('accountant') && $expense->school_id === $user->school_id && $expense->approval_status === 'rejected';
    }
}
