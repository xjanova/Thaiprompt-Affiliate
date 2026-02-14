<?php

namespace App\Policies;

use App\Models\AccountingExpense;
use App\Models\User;

class AccountingExpensePolicy
{
    public function view(User $user, AccountingExpense $expense): bool
    {
        return $user->id === $expense->user_id || $user->isSuperAdmin();
    }

    public function update(User $user, AccountingExpense $expense): bool
    {
        return $user->id === $expense->user_id || $user->isSuperAdmin();
    }

    public function delete(User $user, AccountingExpense $expense): bool
    {
        return $user->id === $expense->user_id || $user->isSuperAdmin();
    }
}
