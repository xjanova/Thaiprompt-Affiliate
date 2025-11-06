<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AccountingInvoice;

class AccountingInvoicePolicy
{
    public function view(User $user, AccountingInvoice $invoice): bool
    {
        return $user->id === $invoice->user_id || $user->isSuperAdmin();
    }

    public function update(User $user, AccountingInvoice $invoice): bool
    {
        return $user->id === $invoice->user_id || $user->isSuperAdmin();
    }

    public function delete(User $user, AccountingInvoice $invoice): bool
    {
        return $user->id === $invoice->user_id || $user->isSuperAdmin();
    }
}
