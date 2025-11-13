<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'food_passport_id' => $this->food_passport_id,

            // Product details
            'product_type' => $this->product_type,
            'variety' => $this->variety,
            'harvest_date' => $this->harvest_date?->format('Y-m-d'),
            'batch_number' => $this->batch_number,
            'quantity' => $this->estimated_quantity,
            'unit' => $this->unit,

            // Farm information
            'farm' => [
                'name' => $this->farm_name,
                'location' => $this->farm_location,
            ],

            // Farmer
            'farmer' => $this->whenLoaded('farmer', fn() => [
                'id' => $this->farmer->id,
                'name' => $this->farmer->name,
                'email' => $this->farmer->email,
            ]),

            // Status
            'current_stage' => $this->current_stage,

            // Carbon
            'carbon' => [
                'score' => $this->carbon_score,
                'total_footprint' => $this->total_carbon_footprint,
                'breakdown' => $this->when(
                    $this->relationLoaded('carbonRecords'),
                    fn() => $this->getCarbonBreakdown()
                ),
            ],

            // Quality
            'quality' => [
                'average_score' => $this->average_quality_score,
                'checkpoints_count' => $this->whenCounted('qualityCheckpoints'),
                'latest_check' => $this->when(
                    $this->latestQualityCheck(),
                    fn() => new QualityCheckpointResource($this->latestQualityCheck())
                ),
            ],

            // Journey
            'journey' => [
                'stages_count' => $this->whenCounted('journeyStages'),
                'current_location' => $this->when(
                    $this->currentStage(),
                    fn() => new ProductJourneyResource($this->currentStage())
                ),
                'stages' => $this->whenLoaded('journeyStages', fn() =>
                    ProductJourneyResource::collection($this->journeyStages)
                ),
            ],

            // Certifications
            'certifications' => $this->whenLoaded('certifications', fn() =>
                FoodCertificationResource::collection($this->certifications)
            ),

            // Consumer engagement
            'engagement' => [
                'total_scans' => $this->total_scans,
                'scans' => $this->whenCounted('consumerScans'),
            ],

            // Blockchain
            'blockchain' => [
                'hash' => $this->blockchain_hash,
                'ipfs_hash' => $this->ipfs_hash,
                'explorer_url' => $this->blockchain_hash ?
                    app(\App\Services\BlockchainRecordService::class)->getExplorerUrl($this->blockchain_hash) : null,
            ],

            // URLs
            'qr_code_url' => $this->qr_code_url,
            'passport_url' => $this->passport_url,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
