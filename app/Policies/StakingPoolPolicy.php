<?php

namespace App\Policies;

use App\Models\TPIXStakingPool;
use App\Models\User;

class StakingPoolPolicy
{
    /**
     * Determine if the user can view any staking pools.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the staking pool.
     */
    public function view(User $user, TPIXStakingPool $pool): bool
    {
        // Can view if: pool is active, or user is creator, or user is admin
        return $pool->status === 'active'
            || $pool->creator_id === $user->id
            || $user->hasRole('admin');
    }

    /**
     * Determine if the user can create staking pools.
     */
    public function create(User $user): bool
    {
        // User must own at least one deployed token
        $hasToken = \App\Models\TPIXToken::where('creator_id', $user->id)
            ->whereIn('status', ['deployed', 'active'])
            ->exists();

        return $hasToken || $user->hasRole('admin');
    }

    /**
     * Determine if the user can update the staking pool.
     */
    public function update(User $user, TPIXStakingPool $pool): bool
    {
        // Can update if: user is creator and pool is draft, or user is admin
        if ($user->hasRole('admin')) {
            return true;
        }

        return $pool->creator_id === $user->id && $pool->status === 'draft';
    }

    /**
     * Determine if the user can delete the staking pool.
     */
    public function delete(User $user, TPIXStakingPool $pool): bool
    {
        // Can delete if: user is creator and pool is draft with no stakes, or user is admin
        if ($user->hasRole('admin')) {
            return true;
        }

        return $pool->creator_id === $user->id
            && $pool->status === 'draft'
            && $pool->current_stakers == 0;
    }

    /**
     * Determine if the user can stake in the pool.
     */
    public function stake(User $user, TPIXStakingPool $pool): bool
    {
        // Can stake if: pool is active and not full
        if ($pool->status !== 'active') {
            return false;
        }

        // Check if pool is full
        if ($pool->max_stakers && $pool->current_stakers >= $pool->max_stakers) {
            return false;
        }

        // Check if pool cap reached
        if ($pool->pool_cap && bccomp($pool->total_staked, $pool->pool_cap, 8) >= 0) {
            return false;
        }

        // Check if pool has started
        if ($pool->starts_at && $pool->starts_at > now()) {
            return false;
        }

        // Check if pool has ended
        if ($pool->ends_at && $pool->ends_at < now()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can unstake from the pool.
     */
    public function unstake(User $user, TPIXStakingPool $pool): bool
    {
        // User must have an unlocked stake in the pool
        $hasUnlockedStake = $pool->stakes()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('unlock_at', '<=', now())
            ->exists();

        return $hasUnlockedStake;
    }

    /**
     * Determine if the user can activate the pool.
     */
    public function activate(User $user, TPIXStakingPool $pool): bool
    {
        // Only creator or admin can activate
        return $pool->creator_id === $user->id || $user->hasRole('admin');
    }

    /**
     * Determine if the user can pause the pool.
     */
    public function pause(User $user, TPIXStakingPool $pool): bool
    {
        // Only creator or admin can pause
        return $pool->creator_id === $user->id || $user->hasRole('admin');
    }
}
