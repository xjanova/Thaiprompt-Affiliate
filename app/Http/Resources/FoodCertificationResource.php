<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodCertificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Certification details
            'certification_type' => $this->certification_type,
            'certification_name' => $this->certification_name,
            'certifying_body' => $this->certifying_body,

            // Certificate
            'certificate_number' => $this->certificate_number,
            'issued_date' => $this->issued_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'days_until_expiry' => $this->days_until_expiry,
            'status' => $this->status,

            // Validity
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->isExpiringSoon(),

            // Scope
            'scope' => $this->scope,
            'standards_version' => $this->standards_version,

            // Documentation
            'certificate_url' => $this->certificate_url,
            'audit_report_url' => $this->audit_report_url,
            'qr_code_url' => $this->qr_code_url,

            // Verification
            'verification' => [
                'code' => $this->verification_code,
                'url' => $this->verification_url,
            ],

            // Blockchain
            'blockchain' => [
                'hash' => $this->blockchain_hash,
                'ipfs_hash' => $this->ipfs_hash,
            ],

            // Product
            'food_product' => $this->whenLoaded('foodProduct', fn() => [
                'id' => $this->foodProduct->id,
                'passport_id' => $this->foodProduct->food_passport_id,
                'variety' => $this->foodProduct->variety,
            ]),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
