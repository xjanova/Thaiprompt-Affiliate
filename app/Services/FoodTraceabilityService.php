<?php

namespace App\Services;

use App\Models\FoodProduct;
use App\Models\ProductJourney;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class FoodTraceabilityService
{
    public function __construct(
        protected BlockchainRecordService $blockchain,
        protected CarbonCreditService $carbonService
    ) {}

    /**
     * Create a new food product with passport
     */
    public function createFoodProduct(array $data): FoodProduct
    {
        return DB::transaction(function () use ($data) {
            // Generate unique passport ID
            $passportId = $this->generatePassportId();

            // Generate batch number if not provided
            $batchNumber = $data['batch_number'] ?? $this->generateBatchNumber();

            $product = FoodProduct::create([
                'food_passport_id' => $passportId,
                'product_id' => $data['product_id'] ?? null,
                'farmer_id' => $data['farmer_id'] ?? auth()->id(),
                'farm_name' => $data['farm_name'] ?? null,
                'farm_location' => $data['farm_location'] ?? null,
                'product_type' => $data['product_type'],
                'variety' => $data['variety'],
                'harvest_date' => $data['harvest_date'],
                'batch_number' => $batchNumber,
                'estimated_quantity' => $data['quantity'],
                'unit' => $data['unit'],
                'certifications' => $data['certifications'] ?? [],
                'current_stage' => 'farm',
            ]);

            // Generate QR code
            $this->generateQRCode($product);

            // Create initial journey stage (farm origin)
            $this->addJourneyStage($product->id, [
                'stage' => 'farm',
                'stage_name' => 'Origin Farm',
                'location_name' => $data['farm_name'] ?? 'Farm',
                'location_coordinates' => $data['farm_location'] ?? null,
                'handler_id' => $product->farmer_id,
                'handler_name' => $product->farmer->name ?? 'Farmer',
                'handler_type' => 'farmer',
            ]);

            // Record on blockchain (async)
            try {
                $hash = $this->blockchain->recordProductOnChain($product);
                $product->update(['blockchain_hash' => $hash]);
            } catch (\Exception $e) {
                \Log::error('Blockchain recording failed', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage()
                ]);
                // Continue without blockchain hash - will retry later
            }

            return $product->fresh();
        });
    }

    /**
     * Add a journey stage
     */
    public function addJourneyStage(int $productId, array $stageData): ProductJourney
    {
        return DB::transaction(function () use ($productId, $stageData) {
            $product = FoodProduct::findOrFail($productId);

            $sequence = ProductJourney::where('food_product_id', $productId)
                ->max('sequence_order') + 1;

            $journey = ProductJourney::create([
                'food_product_id' => $productId,
                'stage' => $stageData['stage'],
                'stage_name' => $stageData['stage_name'] ?? ucfirst($stageData['stage']),
                'sequence_order' => $sequence,
                'location_name' => $stageData['location_name'],
                'location_coordinates' => $stageData['location_coordinates'] ?? null,
                'arrived_at' => $stageData['arrived_at'] ?? now(),
                'handler_id' => $stageData['handler_id'] ?? auth()->id(),
                'handler_name' => $stageData['handler_name'] ?? auth()->user()->name,
                'handler_type' => $stageData['handler_type'] ?? 'transporter',
                'organization' => $stageData['organization'] ?? null,
                'temperature_range' => $stageData['temperature_range'] ?? null,
                'humidity_range' => $stageData['humidity_range'] ?? null,
                'storage_conditions' => $stageData['storage_conditions'] ?? null,
                'transport_method' => $stageData['transport_method'] ?? null,
                'distance_km' => $stageData['distance_km'] ?? null,
                'fuel_type' => $stageData['fuel_type'] ?? null,
                'notes' => $stageData['notes'] ?? null,
                'photos' => $stageData['photos'] ?? [],
                'documents' => $stageData['documents'] ?? [],
            ]);

            // Calculate carbon emission for this stage if transportation
            if ($journey->transport_method && $journey->distance_km) {
                $emission = $this->carbonService->calculateTransportEmission($journey);
                $journey->update(['stage_carbon_emission' => $emission]);
            }

            // Update product current stage
            $product->update(['current_stage' => $stageData['stage']]);

            // Complete previous stage if exists
            $previousStage = ProductJourney::where('food_product_id', $productId)
                ->where('sequence_order', $sequence - 1)
                ->first();

            if ($previousStage && !$previousStage->departed_at) {
                $previousStage->update(['departed_at' => now()]);
            }

            // Record on blockchain
            try {
                $hash = $this->blockchain->recordJourneyStageOnChain($journey);
                $journey->update(['blockchain_hash' => $hash]);
            } catch (\Exception $e) {
                \Log::error('Blockchain journey recording failed', [
                    'journey_id' => $journey->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Send notifications
            $this->notifyStakeholders($product, $journey);

            return $journey;
        });
    }

    /**
     * Complete a journey stage (mark as departed)
     */
    public function completeJourneyStage(int $stageId): ProductJourney
    {
        $stage = ProductJourney::findOrFail($stageId);

        $stage->update(['departed_at' => now()]);

        return $stage;
    }

    /**
     * Get full journey with all details
     */
    public function getFullJourney(int $productId): Collection
    {
        return ProductJourney::where('food_product_id', $productId)
            ->with(['handler', 'qualityCheckpoints', 'carbonRecords'])
            ->orderBy('sequence_order')
            ->get();
    }

    /**
     * Get current location of product
     */
    public function getCurrentLocation(int $productId): ?ProductJourney
    {
        return ProductJourney::where('food_product_id', $productId)
            ->latest('sequence_order')
            ->first();
    }

    /**
     * Calculate total journey duration
     */
    public function calculateJourneyDuration(int $productId): float
    {
        return ProductJourney::where('food_product_id', $productId)
            ->whereNotNull('duration_hours')
            ->sum('duration_hours');
    }

    /**
     * Generate timeline data for visualization
     */
    public function generateTimeline(int $productId): array
    {
        $stages = $this->getFullJourney($productId);

        return $stages->map(function ($stage) {
            return [
                'id' => $stage->id,
                'stage' => $stage->stage,
                'name' => $stage->stage_name,
                'location' => $stage->location_name,
                'arrived_at' => $stage->arrived_at->toIso8601String(),
                'departed_at' => $stage->departed_at?->toIso8601String(),
                'handler' => $stage->handler_name,
                'organization' => $stage->organization,
                'status' => $stage->isCompleted() ? 'completed' : 'in_progress',
                'quality_status' => $stage->quality_status,
                'carbon_emission' => $stage->stage_carbon_emission,
                'distance_km' => $stage->distance_km,
                'quality_checks' => $stage->qualityCheckpoints->count(),
            ];
        })->toArray();
    }

    /**
     * Generate unique passport ID
     */
    protected function generatePassportId(): string
    {
        $prefix = 'FP';
        $year = date('Y');
        $sequence = FoodProduct::whereYear('created_at', $year)->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }

    /**
     * Generate batch number
     */
    protected function generateBatchNumber(): string
    {
        return 'BATCH-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate QR code for product
     */
    protected function generateQRCode(FoodProduct $product): void
    {
        try {
            $url = route('food-passport.scan', $product->food_passport_id);

            // Generate QR code image
            $qrCode = QrCode::format('png')
                ->size(512)
                ->margin(2)
                ->errorCorrection('H')
                ->generate($url);

            // Save to storage
            $filename = "qr-codes/{$product->food_passport_id}.png";
            Storage::disk('public')->put($filename, $qrCode);

            // Update product with QR code URL
            $product->update([
                'qr_code_url' => Storage::url($filename)
            ]);
        } catch (\Exception $e) {
            \Log::error('QR code generation failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify stakeholders about journey update
     */
    protected function notifyStakeholders(FoodProduct $product, ProductJourney $journey): void
    {
        // Notify farmer
        if ($product->farmer && $product->farmer->line_user_id) {
            try {
                app(LineService::class)->sendFlexMessage(
                    $product->farmer->line_user_id,
                    $this->buildJourneyUpdateMessage($product, $journey)
                );
            } catch (\Exception $e) {
                \Log::error('LINE notification failed', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // TODO: Email notification
        // TODO: Push notification for mobile app
    }

    /**
     * Build LINE flex message for journey update
     */
    protected function buildJourneyUpdateMessage(FoodProduct $product, ProductJourney $journey): array
    {
        return [
            'type' => 'flex',
            'altText' => 'อัพเดทเส้นทางผลิตภัณฑ์',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '🚚 อัพเดทเส้นทาง',
                            'weight' => 'bold',
                            'size' => 'xl',
                        ],
                        [
                            'type' => 'text',
                            'text' => $product->variety,
                            'margin' => 'md',
                            'color' => '#666666',
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'lg',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'spacing' => 'sm',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => 'ขั้นตอน: ' . $journey->stage_name,
                                    'size' => 'sm',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'สถานที่: ' . $journey->location_name,
                                    'size' => 'sm',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'ผู้รับผิดชอบ: ' . $journey->handler_name,
                                    'size' => 'sm',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get product statistics
     */
    public function getProductStatistics(int $productId): array
    {
        $product = FoodProduct::with([
            'journeyStages',
            'qualityCheckpoints',
            'carbonRecords',
            'consumerScans'
        ])->findOrFail($productId);

        return [
            'total_stages' => $product->journeyStages->count(),
            'completed_stages' => $product->journeyStages()->completed()->count(),
            'total_distance_km' => $product->journeyStages->sum('distance_km'),
            'total_quality_checks' => $product->qualityCheckpoints->count(),
            'passed_quality_checks' => $product->qualityCheckpoints()->passed()->count(),
            'average_quality_score' => $product->average_quality_score,
            'total_carbon_footprint' => $product->total_carbon_footprint,
            'carbon_score' => $product->carbon_score,
            'total_scans' => $product->consumerScans->count(),
            'total_duration_hours' => $this->calculateJourneyDuration($productId),
        ];
    }
}
