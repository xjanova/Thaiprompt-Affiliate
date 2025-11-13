<?php

namespace App\Policies;

use App\Models\FoodCertification;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FoodCertificationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any food certifications.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view certifications
        return true;
    }

    /**
     * Determine if the user can view the food certification.
     */
    public function view(?User $user, FoodCertification $certification): bool
    {
        // Public access - certifications are public for consumer trust
        return true;
    }

    /**
     * Determine if the user can create food certifications.
     */
    public function create(User $user): bool
    {
        // Only certification bodies and admins can issue certifications
        return in_array($user->role, [
            'certification_body',
            'admin',
            'super_admin'
        ]);
    }

    /**
     * Determine if the user can update the food certification.
     */
    public function update(User $user, FoodCertification $certification): bool
    {
        // Admins can update any certification
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        // Cannot update blockchain-verified certifications
        if ($certification->blockchain_hash) {
            return false;
        }

        // Certifying body can update their own certifications within 48 hours
        if ($user->role === 'certification_body'
            && $certification->created_at->diffInHours(now()) < 48) {
            // Check if this user is from the same certifying body
            // This would need additional logic to match certifying_body to user's organization
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete the food certification.
     */
    public function delete(User $user, FoodCertification $certification): bool
    {
        // Only super admins can delete certifications
        if ($user->role === 'super_admin') {
            return true;
        }

        // Cannot delete blockchain-verified certifications
        if ($certification->blockchain_hash) {
            return false;
        }

        // Cannot delete active certifications
        if ($certification->isActive()) {
            return false;
        }

        return false;
    }

    /**
     * Determine if the user can renew the certification.
     */
    public function renew(User $user, FoodCertification $certification): bool
    {
        // Only certification bodies and admins can renew
        if (!in_array($user->role, ['certification_body', 'admin', 'super_admin'])) {
            return false;
        }

        // Can only renew expired or expiring soon certifications
        return $certification->isExpired() || $certification->isExpiringSoon();
    }

    /**
     * Determine if the user can revoke the certification.
     */
    public function revoke(User $user, FoodCertification $certification): bool
    {
        // Only certification bodies and admins can revoke
        return in_array($user->role, [
            'certification_body',
            'admin',
            'super_admin'
        ]);
    }

    /**
     * Determine if the user can verify the certification.
     */
    public function verify(?User $user, FoodCertification $certification): bool
    {
        // Public verification - anyone can verify using verification code
        return true;
    }

    /**
     * Determine if the user can upload certificate documents.
     */
    public function uploadDocuments(User $user, FoodCertification $certification): bool
    {
        // Admins can upload documents
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        // Certification bodies can upload documents
        if ($user->role === 'certification_body') {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can generate QR code for certification.
     */
    public function generateQRCode(User $user, FoodCertification $certification): bool
    {
        // Certification bodies and admins can generate QR codes
        return in_array($user->role, [
            'certification_body',
            'admin',
            'super_admin'
        ]);
    }

    /**
     * Determine if the user can view audit reports.
     */
    public function viewAuditReport(User $user, FoodCertification $certification): bool
    {
        // Admins can view all audit reports
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        // Certification bodies can view audit reports
        if ($user->role === 'certification_body') {
            return true;
        }

        // Product owner can view their own product's audit reports
        $product = $certification->foodProduct;
        if ($product && $product->farmer_id === $user->id) {
            return true;
        }

        // Quality inspectors can view audit reports
        if ($user->role === 'quality_inspector') {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can record on blockchain.
     */
    public function recordOnBlockchain(User $user, FoodCertification $certification): bool
    {
        // Only admins and certification bodies can record on blockchain
        if (!in_array($user->role, ['certification_body', 'admin', 'super_admin'])) {
            return false;
        }

        // Cannot re-record if already on blockchain
        if ($certification->blockchain_hash) {
            return false;
        }

        // Must be active to record
        return $certification->isActive();
    }

    /**
     * Determine if the user can view blockchain data.
     */
    public function viewBlockchainData(?User $user, FoodCertification $certification): bool
    {
        // Blockchain data is public for transparency
        return true;
    }

    /**
     * Determine if the user can request certification for a product.
     */
    public function requestCertification(User $user): bool
    {
        // Farmers and processors can request certifications
        return in_array($user->role, [
            'farmer',
            'processor',
            'admin',
            'super_admin'
        ]);
    }
}
