<?php

namespace App\Services\Verification;

use App\Models\ShopVerification;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ShopVerificationService
{
    /**
     * Submit shop verification request
     */
    public function submitVerification(Vendor $vendor, array $data): array
    {
        try {
            DB::beginTransaction();

            // Check if there's already a pending verification
            $existingVerification = ShopVerification::where('vendor_id', $vendor->id)
                ->whereIn('status', ['pending', 'resubmit'])
                ->first();

            if ($existingVerification) {
                throw new \Exception('You already have a pending verification request');
            }

            // Handle file uploads
            $documents = $this->handleDocumentUploads($data);

            // Create verification request
            $verification = ShopVerification::create([
                'vendor_id' => $vendor->id,
                'user_id' => $vendor->user_id,
                'status' => 'pending',
                'business_registration_number' => $data['business_registration_number'] ?? null,
                'business_registration_document' => $documents['business_registration_document'] ?? null,
                'tax_certificate' => $documents['tax_certificate'] ?? null,
                'business_license' => $documents['business_license'] ?? null,
                'id_card_number' => $data['id_card_number'] ?? null,
                'id_card_front' => $documents['id_card_front'] ?? null,
                'id_card_back' => $documents['id_card_back'] ?? null,
                'selfie_with_id' => $documents['selfie_with_id'] ?? null,
                'business_type' => $data['business_type'] ?? null,
                'business_address' => $data['business_address'] ?? null,
                'business_phone' => $data['business_phone'] ?? null,
                'business_email' => $data['business_email'] ?? null,
                'website' => $data['website'] ?? null,
                'bank_statement' => $documents['bank_statement'] ?? null,
                'bank_book_photo' => $documents['bank_book_photo'] ?? null,
                'additional_documents' => $documents['additional_documents'] ?? null,
                'notes' => $data['notes'] ?? null,
                'submitted_at' => now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'verification' => $verification,
                'message' => 'Verification request submitted successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Shop Verification Submit Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Approve verification request
     */
    public function approveVerification(int $verificationId, User $admin, string $badge = 'bronze'): array
    {
        try {
            DB::beginTransaction();

            $verification = ShopVerification::with('vendor')->findOrFail($verificationId);

            if (!$verification->isPending()) {
                throw new \Exception('Verification request is not pending');
            }

            $verification->approve($admin, $badge);

            DB::commit();

            return [
                'success' => true,
                'verification' => $verification->fresh(),
                'message' => 'Verification approved successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Shop Verification Approve Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reject verification request
     */
    public function rejectVerification(int $verificationId, User $admin, string $reason): array
    {
        try {
            DB::beginTransaction();

            $verification = ShopVerification::findOrFail($verificationId);

            if (!$verification->isPending()) {
                throw new \Exception('Verification request is not pending');
            }

            $verification->reject($admin, $reason);

            DB::commit();

            return [
                'success' => true,
                'verification' => $verification->fresh(),
                'message' => 'Verification rejected',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Shop Verification Reject Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get verification status
     */
    public function getVerificationStatus(Vendor $vendor): array
    {
        $verification = ShopVerification::where('vendor_id', $vendor->id)
            ->latest()
            ->first();

        if (!$verification) {
            return [
                'verified' => false,
                'status' => 'not_submitted',
                'message' => 'No verification request found',
            ];
        }

        return [
            'verified' => $vendor->is_verified,
            'status' => $verification->status,
            'badge' => $vendor->verification_badge,
            'badge_info' => $verification->getBadgeInfo(),
            'submitted_at' => $verification->submitted_at,
            'verified_at' => $verification->verified_at,
            'rejection_reason' => $verification->rejection_reason,
        ];
    }

    /**
     * Get pending verifications for admin
     */
    public function getPendingVerifications(int $perPage = 20)
    {
        return ShopVerification::with(['vendor', 'user'])
            ->pending()
            ->orderBy('submitted_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get all verifications for admin
     */
    public function getAllVerifications(int $perPage = 20, ?string $status = null)
    {
        $query = ShopVerification::with(['vendor', 'user']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('submitted_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Handle document uploads
     */
    private function handleDocumentUploads(array $data): array
    {
        $documents = [];

        $fileFields = [
            'business_registration_document',
            'tax_certificate',
            'business_license',
            'id_card_front',
            'id_card_back',
            'selfie_with_id',
            'bank_statement',
            'bank_book_photo',
        ];

        foreach ($fileFields as $field) {
            if (isset($data[$field]) && $data[$field] instanceof UploadedFile) {
                $documents[$field] = $this->uploadDocument($data[$field], $field);
            }
        }

        // Handle additional documents
        if (isset($data['additional_documents']) && is_array($data['additional_documents'])) {
            $additionalDocs = [];
            foreach ($data['additional_documents'] as $key => $file) {
                if ($file instanceof UploadedFile) {
                    $additionalDocs[] = $this->uploadDocument($file, 'additional_' . $key);
                }
            }
            $documents['additional_documents'] = $additionalDocs;
        }

        return $documents;
    }

    /**
     * Upload document to storage
     */
    private function uploadDocument(UploadedFile $file, string $fieldName): string
    {
        $path = $file->store('verification-documents', 'public');

        return $path;
    }

    /**
     * Calculate verification badge based on documents
     */
    public function calculateVerificationBadge(ShopVerification $verification): string
    {
        $score = 0;

        // Basic documents (Bronze)
        if ($verification->id_card_front && $verification->id_card_back) {
            $score += 1;
        }

        // Business registration (Silver)
        if ($verification->business_registration_document && $verification->tax_certificate) {
            $score += 2;
        }

        // Bank verification (Gold)
        if ($verification->bank_statement || $verification->bank_book_photo) {
            $score += 1;
        }

        // Business license (Platinum)
        if ($verification->business_license) {
            $score += 1;
        }

        // Determine badge
        if ($score >= 5) {
            return 'platinum';
        } elseif ($score >= 4) {
            return 'gold';
        } elseif ($score >= 3) {
            return 'silver';
        } else {
            return 'bronze';
        }
    }

    /**
     * Get verification statistics
     */
    public function getVerificationStatistics(): array
    {
        return [
            'total' => ShopVerification::count(),
            'pending' => ShopVerification::pending()->count(),
            'approved' => ShopVerification::approved()->count(),
            'rejected' => ShopVerification::rejected()->count(),
            'verified_vendors' => Vendor::where('is_verified', true)->count(),
            'by_badge' => [
                'bronze' => Vendor::where('verification_badge', 'bronze')->count(),
                'silver' => Vendor::where('verification_badge', 'silver')->count(),
                'gold' => Vendor::where('verification_badge', 'gold')->count(),
                'platinum' => Vendor::where('verification_badge', 'platinum')->count(),
            ],
        ];
    }
}
