<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AccountingProduct;

class AccountingProductPolicy
{
    public function view(User $user, AccountingProduct $product): bool
    {
        return $user->id === $product->user_id || $user->isSuperAdmin();
    }

    public function update(User $user, AccountingProduct $product): bool
    {
        return $user->id === $product->user_id || $user->isSuperAdmin();
    }

    public function delete(User $user, AccountingProduct $product): bool
    {
        return $user->id === $product->user_id || $user->isSuperAdmin();
    }
}
