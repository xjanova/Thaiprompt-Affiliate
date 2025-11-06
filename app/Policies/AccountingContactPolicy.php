<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AccountingContact;

class AccountingContactPolicy
{
    public function view(User $user, AccountingContact $contact): bool
    {
        return $user->id === $contact->user_id || $user->isSuperAdmin();
    }

    public function update(User $user, AccountingContact $contact): bool
    {
        return $user->id === $contact->user_id || $user->isSuperAdmin();
    }

    public function delete(User $user, AccountingContact $contact): bool
    {
        return $user->id === $contact->user_id || $user->isSuperAdmin();
    }
}
