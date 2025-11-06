<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AccountingExpense;

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
