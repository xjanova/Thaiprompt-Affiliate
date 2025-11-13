<?php

namespace App\Policies;

use App\Models\QualityCheckpoint;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QualityCheckpointPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any quality checkpoints.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view checkpoints
        return true;
    }

    /**
     * Determine if the user can view the quality checkpoint.
     */
    public function view(?User $user, QualityCheckpoint $checkpoint): bool
    {
        // Public access - transparency for consumers
        return true;
    }

    /**
     * Determine if the user can create quality checkpoints.
     */
    public function create(User $user): bool
    {
        // Quality inspectors, certification bodies, and admins can create checkpoints
        return in_array($user->role, [
            'quality_inspector',
            'certification_body',
            'farmer',
            'processor',
            'admin',
            'super_admin'
        ]);
    }

    /**
     * Determine if the user can update the quality checkpoint.
     */
    public function update(User $user, QualityCheckpoint $checkpoint): bool
    {
        // Admins can update any checkpoint
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        // Inspectors can update their own checkpoints within 24 hours
        if ($user->role === 'quality_inspector' && $checkpoint->inspector_id === $user->id) {
            return $checkpoint->created_at->diffInHours(now()) < 24;
        }

        // Cannot update checkpoints that have been blockchain-verified
        if ($checkpoint->blockchain_hash) {
            return false;
        }

        return false;
    }

    /**
     * Determine if the user can delete the quality checkpoint.
     */
    public function delete(User $user, QualityCheckpoint $checkpoint): bool
    {
        // Only super admins can delete checkpoints
        if ($user->role === 'super_admin') {
            return true;
        }

        // Inspectors can delete their own checkpoints within 1 hour if not blockchain-verified
        if ($user->role === 'quality_inspector'
            && $checkpoint->inspector_id === $user->id
            && !$checkpoint->blockchain_hash
            && $checkpoint->created_at->diffInHours(now()) < 1) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can issue certifications based on checkpoint.
     */
    public function issueCertification(User $user, QualityCheckpoint $checkpoint): bool
    {
        // Only certification bodies and admins can issue certifications
        if (!in_array($user->role, ['certification_body', 'admin', 'super_admin'])) {
            return false;
        }

        // Checkpoint must have passed
        if ($checkpoint->overall_result !== 'pass') {
            return false;
        }

        // Checkpoint must not already have a certification
        return !$checkpoint->certification_issued;
    }

    /**
     * Determine if the user can require a retest.
     */
    public function requireRetest(User $user, QualityCheckpoint $checkpoint): bool
    {
        // Quality inspectors, certification bodies, and admins can require retests
        return in_array($user->role, [
            'quality_inspector',
            'certification_body',
            'admin',
            'super_admin'
        ]);
    }

    /**
     * Determine if the user can view detailed test parameters.
     */
    public function viewDetailedParameters(?User $user, QualityCheckpoint $checkpoint): bool
    {
        // Public can see basic results
        if (!$user) {
            return false;
        }

        // Authenticated users in supply chain can view details
        return in_array($user->role, [
            'farmer',
            'processor',
            'distributor',
            'retailer',
            'quality_inspector',
            'certification_body',
            'admin',
            'super_admin'
        ]);
    }

    /**
     * Determine if the user can add corrective actions.
     */
    public function addCorrectiveActions(User $user, QualityCheckpoint $checkpoint): bool
    {
        // Admins can always add corrective actions
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        // Original inspector can add corrective actions
        if ($checkpoint->inspector_id === $user->id) {
            return true;
        }

        // Product owner can add corrective actions
        $product = $checkpoint->foodProduct;
        if ($product && $product->farmer_id === $user->id) {
            return true;
        }

        return false;
    }
}
