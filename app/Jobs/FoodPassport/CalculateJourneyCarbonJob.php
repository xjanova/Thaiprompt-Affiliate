<?php

namespace App\Jobs\FoodPassport;

use App\Models\ProductJourney;
use App\Services\CarbonCreditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateJourneyCarbonJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ProductJourney $journey
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CarbonCreditService $carbonService): void
    {
        try {
            // Skip if no transport details
            if (!$this->journey->transport_method || !$this->journey->distance_km) {
                Log::info('Journey stage has no transport data, skipping carbon calculation', [
                    'journey_id' => $this->journey->id,
                ]);
                return;
            }

            // Calculate transport emissions
            $emission = $carbonService->calculateTransportEmission($this->journey);

            // Update journey stage carbon
            $this->journey->stage_carbon_emission = $emission;
            $this->journey->saveQuietly();

            Log::info('Journey carbon calculated successfully', [
                'journey_id' => $this->journey->id,
                'emission' => $emission,
                'transport_method' => $this->journey->transport_method,
                'distance_km' => $this->journey->distance_km,
            ]);

            // Update product's total carbon footprint
            $this->updateProductCarbonTotal();
        } catch (\Exception $e) {
            Log::error('Failed to calculate journey carbon', [
                'journey_id' => $this->journey->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update the product's total carbon footprint.
     */
    protected function updateProductCarbonTotal(): void
    {
        $product = $this->journey->foodProduct;

        if (!$product) {
            return;
        }

        // Sum all journey stages
        $totalJourneyCarbon = $product->journeyStages()
            ->whereNotNull('stage_carbon_emission')
            ->sum('stage_carbon_emission');

        // Add farming emissions
        $farmingCarbon = $product->carbonRecords()
            ->where('emission_category', 'farming')
            ->sum('total_co2_equivalent');

        // Update product
        $product->total_carbon_footprint = $totalJourneyCarbon + $farmingCarbon;
        $product->saveQuietly();

        Log::info('Product total carbon updated', [
            'product_id' => $product->id,
            'total_carbon' => $product->total_carbon_footprint,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateJourneyCarbonJob failed', [
            'journey_id' => $this->journey->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
