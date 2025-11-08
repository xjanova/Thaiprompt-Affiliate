<?php

namespace App\Policies;

use App\Models\StakingPosition;
use App\Models\User;

class StakingPositionPolicy
{
    /**
     * Determine if the user can view the staking position.
     */
    public function view(User $user, StakingPosition $position): bool
    {
        return $user->id === $position->user_id || $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determine if the user can update the staking position.
     */
    public function update(User $user, StakingPosition $position): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determine if the user can delete the staking position.
     */
    public function delete(User $user, StakingPosition $position): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can withdraw from the staking position.
     */
    public function withdraw(User $user, StakingPosition $position): bool
    {
        // Only the owner can withdraw
        if ($user->id !== $position->user_id) {
            return false;
        }

        // Check if position allows withdrawal
        return $position->canWithdraw();
    }

    /**
     * Determine if the user can approve the staking position.
     */
    public function approve(User $user, StakingPosition $position): bool
    {
        return ($user->isAdmin() || $user->isSuperAdmin()) && $position->status === 'pending';
    }
}
