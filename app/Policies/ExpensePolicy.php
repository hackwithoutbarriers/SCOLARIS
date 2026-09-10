<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function before(User $user): ?bool { return $user->isSuperAdmin() ? true : null; }
    public function viewAny(User $user): bool { return $user->is_active && $user->isFinanceOperator(); }
    public function view(User $user, Expense $expense): bool { return $this->viewAny($user) && $expense->school_id === $user->school_id; }
    public function create(User $user): bool { return $this->viewAny($user) && $user->school_id !== null; }
    public function delete(User $user, Expense $expense): bool { return $user->is_active && $user->isDirector() && $expense->school_id === $user->school_id; }
    public function reverse(User $user, Expense $expense): bool { return $this->delete($user, $expense); }
}
