<?php

namespace App\Policies;

use App\Models\FoodProduct;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FoodProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any food products.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view products
        return true;
    }

    /**
     * Determine if the user can view the food product.
     */
    public function view(?User $user, FoodProduct $product): bool
    {
        // Public access - anyone can view (including unauthenticated)
        return true;
    }

    /**
     * Determine if the user can create food products.
     */
    public function create(User $user): bool
    {
        // Only farmers and admins can create products
        return in_array($user->role, ['farmer', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can update the food product.
     */
    public function update(User $user, FoodProduct $product): bool
    {
        // Admins can update any product
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        // Farmers can only update their own products
        if ($user->role === 'farmer') {
            return $product->farmer_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can delete the food product.
     */
    public function delete(User $user, FoodProduct $product): bool
    {
        // Only super admins can delete products
        if ($user->role === 'super_admin') {
            return true;
        }

        // Farmers can delete their own products if not yet distributed
        if ($user->role === 'farmer' && $product->farmer_id === $user->id) {
            // Check if product hasn't started its journey
            return $product->journeyStages()->count() === 0;
        }

        return false;
    }

    /**
     * Determine if the user can add journey stages to the product.
     */
    public function addJourneyStage(User $user, FoodProduct $product): bool
    {
        // Admins can always add stages
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        // Farmers can add stages to their own products
        if ($user->role === 'farmer' && $product->farmer_id === $user->id) {
            return true;
        }

        // Supply chain stakeholders can add stages
        if (in_array($user->role, ['processor', 'distributor', 'retailer', 'logistics'])) {
            // Check if they have a stakeholder role record for this product
            return $product->stakeholders()
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine if the user can add quality checkpoints.
     */
    public function addQualityCheckpoint(User $user, FoodProduct $product): bool
    {
        // Quality inspectors and admins can add checkpoints
        if (in_array($user->role, ['quality_inspector', 'admin', 'super_admin'])) {
            return true;
        }

        // Farmers can add self-inspections
        if ($user->role === 'farmer' && $product->farmer_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can view carbon data.
     */
    public function viewCarbonData(?User $user, FoodProduct $product): bool
    {
        // Carbon data is public - transparency for consumers
        return true;
    }

    /**
     * Determine if the user can manage carbon credits for this product.
     */
    public function manageCarbonCredits(User $user, FoodProduct $product): bool
    {
        // Admins can manage any credits
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        // Farmers can manage credits for their products
        if ($user->role === 'farmer' && $product->farmer_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can issue certifications.
     */
    public function issueCertification(User $user, FoodProduct $product): bool
    {
        // Only certification bodies and admins can issue certifications
        return in_array($user->role, ['certification_body', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can bulk import products.
     */
    public function bulkImport(User $user): bool
    {
        // Only admins and approved farmers can bulk import
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        // Farmers with verified status can bulk import
        if ($user->role === 'farmer') {
            return $user->is_verified ?? false;
        }

        return false;
    }
}
