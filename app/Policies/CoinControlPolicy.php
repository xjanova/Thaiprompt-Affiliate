<?php

namespace App\Policies;

use App\Models\TPIXToken;
use App\Models\User;

class CoinControlPolicy
{
    /**
     * Determine if the user can perform any coin control actions.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Admins can do everything
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine if the user can mint tokens.
     */
    public function mint(User $user, TPIXToken $token): bool
    {
        // Only if token is mintable
        if (!$token->is_mintable) {
            return false;
        }

        // Token creator can mint if allowed
        return $token->creator_id === $user->id && $token->allow_creator_mint;
    }

    /**
     * Determine if the user can burn tokens.
     */
    public function burn(User $user, TPIXToken $token): bool
    {
        // Only if token is burnable
        if (!$token->is_burnable) {
            return false;
        }

        // Token creator can burn if allowed
        return $token->creator_id === $user->id && $token->allow_creator_burn;
    }

    /**
     * Determine if the user can freeze addresses.
     */
    public function freezeAddress(User $user, TPIXToken $token): bool
    {
        // Only if token is freezable
        if (!$token->is_freezable) {
            return false;
        }

        // Only token creator or admin
        return $token->creator_id === $user->id;
    }

    /**
     * Determine if the user can unfreeze addresses.
     */
    public function unfreezeAddress(User $user, TPIXToken $token): bool
    {
        // Only if token is freezable
        if (!$token->is_freezable) {
            return false;
        }

        // Only token creator or admin
        return $token->creator_id === $user->id;
    }

    /**
     * Determine if the user can pause the contract.
     */
    public function pause(User $user, TPIXToken $token): bool
    {
        // Only if token is pausable
        if (!$token->is_pausable) {
            return false;
        }

        // Only token creator or admin
        return $token->creator_id === $user->id;
    }

    /**
     * Determine if the user can unpause the contract.
     */
    public function unpause(User $user, TPIXToken $token): bool
    {
        // Only if token is pausable
        if (!$token->is_pausable) {
            return false;
        }

        // Only token creator or admin
        return $token->creator_id === $user->id;
    }

    /**
     * Determine if the user can approve the token.
     */
    public function approve(User $user, TPIXToken $token): bool
    {
        // Admin only
        return false; // Handled by before() method
    }

    /**
     * Determine if the user can reject the token.
     */
    public function reject(User $user, TPIXToken $token): bool
    {
        // Admin only
        return false; // Handled by before() method
    }

    /**
     * Determine if the user can verify the token.
     */
    public function verify(User $user, TPIXToken $token): bool
    {
        // Admin only
        return false; // Handled by before() method
    }
}
