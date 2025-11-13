<?php

namespace App\Observers;

use App\Models\FoodProduct;
use App\Services\BlockchainRecordService;
use App\Jobs\GenerateProductQRCodeJob;
use App\Jobs\RecordProductOnBlockchainJob;
use App\Jobs\SendFarmerNotificationJob;
use Illuminate\Support\Facades\Log;

class FoodProductObserver
{
    protected BlockchainRecordService $blockchain;

    public function __construct(BlockchainRecordService $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    /**
     * Handle the FoodProduct "created" event.
     */
    public function created(FoodProduct $product): void
    {
        Log::info('Food Product created', ['passport_id' => $product->food_passport_id]);

        // Generate QR code asynchronously
        if (!$product->qr_code_url) {
            GenerateProductQRCodeJob::dispatch($product);
        }

        // Record on blockchain asynchronously (if enabled in config)
        if (config('food-passport.blockchain.auto_record', true)) {
            RecordProductOnBlockchainJob::dispatch($product);
        }

        // Notify farmer
        if ($product->farmer_id) {
            SendFarmerNotificationJob::dispatch(
                $product->farmer_id,
                'product_created',
                [
                    'product_id' => $product->id,
                    'passport_id' => $product->food_passport_id,
                    'product_type' => $product->product_type,
                ]
            );
        }
    }

    /**
     * Handle the FoodProduct "updated" event.
     */
    public function updated(FoodProduct $product): void
    {
        // Recalculate carbon score if carbon footprint changed
        if ($product->isDirty('total_carbon_footprint')) {
            $this->updateCarbonScore($product);
        }

        // Update blockchain record if critical fields changed
        if ($product->isDirty(['product_type', 'variety', 'certification_status'])) {
            if (config('food-passport.blockchain.auto_record', true)) {
                RecordProductOnBlockchainJob::dispatch($product);
            }
        }

        Log::info('Food Product updated', [
            'id' => $product->id,
            'changes' => $product->getChanges()
        ]);
    }

    /**
     * Handle the FoodProduct "deleting" event.
     */
    public function deleting(FoodProduct $product): void
    {
        // Prevent deletion if product has started its journey
        if ($product->journeyStages()->count() > 0) {
            Log::warning('Attempted to delete product with journey stages', [
                'product_id' => $product->id
            ]);

            throw new \Exception('Cannot delete product that has journey stages. Archive it instead.');
        }

        Log::info('Food Product deleting', ['id' => $product->id]);
    }

    /**
     * Handle the FoodProduct "deleted" event.
     */
    public function deleted(FoodProduct $product): void
    {
        Log::info('Food Product deleted', ['id' => $product->id]);

        // Clean up related data
        $product->carbonRecords()->delete();
        $product->consumerScans()->delete();
    }

    /**
     * Update carbon score based on total carbon footprint.
     */
    protected function updateCarbonScore(FoodProduct $product): void
    {
        if (!$product->total_carbon_footprint) {
            return;
        }

        // Get baseline for product type
        $baselines = config('food-passport.carbon.baselines', []);
        $productCategory = $this->getProductCategory($product->product_type);
        $baseline = $baselines[$productCategory] ?? 50.0; // Default baseline

        // Calculate percentage of baseline
        $percentage = ($product->total_carbon_footprint / $baseline) * 100;

        // Assign score (A+ to E)
        $score = match(true) {
            $percentage <= 30 => 'A+',
            $percentage <= 50 => 'A',
            $percentage <= 70 => 'B',
            $percentage <= 90 => 'C',
            $percentage <= 110 => 'D',
            default => 'E',
        };

        $product->carbon_score = $score;
        $product->saveQuietly(); // Prevent infinite loop
    }

    /**
     * Get product category for baseline lookup.
     */
    protected function getProductCategory(string $productType): string
    {
        $lowerType = strtolower($productType);

        return match(true) {
            str_contains($lowerType, 'rice') || str_contains($lowerType, 'ข้าว') => 'rice',
            str_contains($lowerType, 'vegetable') || str_contains($lowerType, 'ผัก') => 'vegetables',
            str_contains($lowerType, 'fruit') || str_contains($lowerType, 'ผลไม้') => 'fruits',
            str_contains($lowerType, 'meat') || str_contains($lowerType, 'เนื้อ') => 'meat',
            str_contains($lowerType, 'dairy') || str_contains($lowerType, 'นม') => 'dairy',
            str_contains($lowerType, 'seafood') || str_contains($lowerType, 'ทะเล') => 'seafood',
            default => 'other',
        };
    }
}
