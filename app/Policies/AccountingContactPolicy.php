<?php

namespace App\Policies;

use App\Models\AccountingContact;
use App\Models\User;

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
