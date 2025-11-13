<?php

namespace App\Policies;

use App\Models\TPIXToken;
use App\Models\User;

class TokenPolicy
{
    /**
     * Determine if the user can view any tokens.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the token.
     */
    public function view(User $user, TPIXToken $token): bool
    {
        // Can view if: token is active/deployed, or user is creator, or user is admin
        return $token->status === 'active'
            || $token->status === 'deployed'
            || $token->creator_id === $user->id
            || $user->hasRole('admin');
    }

    /**
     * Determine if the user can create tokens.
     */
    public function create(User $user): bool
    {
        // Check if user has verified email
        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        // Check if user has any restrictions
        // if ($user->is_banned || $user->is_suspended) {
        //     return false;
        // }

        return true;
    }

    /**
     * Determine if the user can update the token.
     */
    public function update(User $user, TPIXToken $token): bool
    {
        // Can update if: user is creator and token is not deployed yet, or user is admin
        if ($user->hasRole('admin')) {
            return true;
        }

        return $token->creator_id === $user->id && in_array($token->status, ['draft', 'pending']);
    }

    /**
     * Determine if the user can delete the token.
     */
    public function delete(User $user, TPIXToken $token): bool
    {
        // Can delete if: user is creator and token is draft, or user is admin
        if ($user->hasRole('admin')) {
            return true;
        }

        return $token->creator_id === $user->id && $token->status === 'draft';
    }

    /**
     * Determine if the user can deploy the token.
     */
    public function deploy(User $user, TPIXToken $token): bool
    {
        // Can deploy if: user is creator and token is draft or pending
        return $token->creator_id === $user->id && in_array($token->status, ['draft', 'pending']);
    }

    /**
     * Determine if the user can transfer the token.
     */
    public function transfer(User $user, TPIXToken $token): bool
    {
        // Can transfer if: token is deployed/active and user has balance
        if (!in_array($token->status, ['deployed', 'active'])) {
            return false;
        }

        // Check if user has balance
        $balance = $token->balances()->where('user_id', $user->id)->first();
        return $balance && $balance->available_balance > 0;
    }

    /**
     * Determine if the user can buy the token.
     */
    public function buy(User $user, TPIXToken $token): bool
    {
        // Can buy if: token is active and listed
        return $token->status === 'active' && $token->is_listed;
    }

    /**
     * Determine if the user can sell the token.
     */
    public function sell(User $user, TPIXToken $token): bool
    {
        // Can sell if: token is active and user has balance
        if ($token->status !== 'active') {
            return false;
        }

        $balance = $token->balances()->where('user_id', $user->id)->first();
        return $balance && $balance->available_balance > 0;
    }
}
