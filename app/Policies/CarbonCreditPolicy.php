<?php

namespace App\Policies;

use App\Models\CarbonCredit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CarbonCreditPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any carbon credits.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can browse credits
        return true;
    }

    /**
     * Determine if the user can view the carbon credit.
     */
    public function view(User $user, CarbonCredit $credit): bool
    {
        // Admins can view all credits
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        // Owners can view their own credits
        if ($credit->user_id === $user->id) {
            return true;
        }

        // Users can view tradeable credits on marketplace
        if ($credit->tradeable && $credit->status === 'active') {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can issue carbon credits.
     */
    public function issue(User $user): bool
    {
        // Only admins and carbon verification bodies can issue credits
        return in_array($user->role, [
            'carbon_verifier',
            'admin',
            'super_admin'
        ]);
    }

    /**
     * Determine if the user can trade the carbon credit.
     */
    public function trade(User $user, CarbonCredit $credit): bool
    {
        // Cannot trade if not tradeable
        if (!$credit->tradeable || !$credit->canTrade()) {
            return false;
        }

        // Admins can facilitate any trade
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        // Owners can trade their own credits
        if ($credit->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can purchase the carbon credit.
     */
    public function purchase(User $user, CarbonCredit $credit): bool
    {
        // Cannot purchase own credits
        if ($credit->user_id === $user->id) {
            return false;
        }

        // Cannot purchase if not tradeable or active
        if (!$credit->tradeable || $credit->status !== 'active' || !$credit->canTrade()) {
            return false;
        }

        // All authenticated users with verified accounts can purchase
        return $user->is_verified ?? false;
    }

    /**
     * Determine if the user can retire the carbon credit.
     */
    public function retire(User $user, CarbonCredit $credit): bool
    {
        // Admins can retire any credit
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        // Owners can retire their own active credits
        if ($credit->user_id === $user->id && $credit->status === 'active') {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can update the carbon credit.
     */
    public function update(User $user, CarbonCredit $credit): bool
    {
        // Only admins and carbon verifiers can update credits
        if (in_array($user->role, ['carbon_verifier', 'admin', 'super_admin'])) {
            return true;
        }

        // Cannot update blockchain-verified credits
        if ($credit->blockchain_hash) {
            return false;
        }

        return false;
    }

    /**
     * Determine if the user can delete the carbon credit.
     */
    public function delete(User $user, CarbonCredit $credit): bool
    {
        // Only super admins can delete credits
        if ($user->role === 'super_admin') {
            return true;
        }

        // Cannot delete blockchain-verified or traded credits
        if ($credit->blockchain_hash || $credit->status === 'traded') {
            return false;
        }

        return false;
    }

    /**
     * Determine if the user can verify the carbon credit.
     */
    public function verify(User $user, CarbonCredit $credit): bool
    {
        // Only carbon verifiers and admins can verify credits
        return in_array($user->role, [
            'carbon_verifier',
            'admin',
            'super_admin'
        ]);
    }

    /**
     * Determine if the user can view the marketplace.
     */
    public function viewMarketplace(User $user): bool
    {
        // All authenticated users can view marketplace
        return true;
    }

    /**
     * Determine if the user can create a listing.
     */
    public function createListing(User $user, CarbonCredit $credit): bool
    {
        // Admins can list any credit
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        // Owners can list their own credits if tradeable
        if ($credit->user_id === $user->id && $credit->canTrade()) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can view blockchain verification.
     */
    public function viewBlockchainData(?User $user, CarbonCredit $credit): bool
    {
        // Blockchain data is public for transparency
        return true;
    }

    /**
     * Determine if the user can view transaction history.
     */
    public function viewTransactionHistory(User $user, CarbonCredit $credit): bool
    {
        // Admins can view all transaction history
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        // Current and previous owners can view history
        if ($credit->user_id === $user->id || $credit->traded_to_user_id === $user->id) {
            return true;
        }

        return false;
    }
}
