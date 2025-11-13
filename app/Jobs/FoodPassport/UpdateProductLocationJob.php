<?php

namespace App\Jobs\FoodPassport;

use App\Models\FoodProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateProductLocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public FoodProduct $product
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get the most recent journey stage
            $currentStage = $this->product->journeyStages()
                ->orderBy('sequence_order', 'desc')
                ->first();

            if (!$currentStage) {
                return;
            }

            // Update product's current location
            $locationData = [
                'stage' => $currentStage->stage,
                'location_name' => $currentStage->location_name,
                'coordinates' => $currentStage->location_coordinates,
                'updated_at' => now()->toIso8601String(),
            ];

            $this->product->current_location = $locationData;
            $this->product->saveQuietly();

            Log::info('Product location updated', [
                'product_id' => $this->product->id,
                'location' => $currentStage->location_name,
                'stage' => $currentStage->stage,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update product location', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
