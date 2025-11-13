<?php

namespace App\Listeners\FoodPassport;

use App\Events\FoodPassport\ProductJourneyCompletedEvent;
use App\Services\CarbonCreditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AssessCarbonCreditEligibilityListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        protected CarbonCreditService $carbonService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(ProductJourneyCompletedEvent $event): void
    {
        $product = $event->product;

        try {
            // Get baseline for this product type
            $baseline = $this->carbonService->getBaseline($product);

            Log::info('Assessing carbon credit eligibility', [
                'product_id' => $product->id,
                'total_carbon' => $product->total_carbon_footprint,
                'baseline' => $baseline,
            ]);

            // Check if product qualifies for carbon credits
            if ($product->total_carbon_footprint < $baseline) {
                $reduction = $baseline - $product->total_carbon_footprint;
                $reductionPercentage = ($reduction / $baseline) * 100;

                Log::info('Product qualifies for carbon credits', [
                    'product_id' => $product->id,
                    'reduction' => $reduction,
                    'reduction_percentage' => $reductionPercentage,
                ]);

                // Create pending carbon credit request
                // This would typically require admin or verifier approval
                \DB::table('carbon_credit_requests')->insert([
                    'food_product_id' => $product->id,
                    'user_id' => $product->farmer_id,
                    'baseline_emission' => $baseline,
                    'actual_emission' => $product->total_carbon_footprint,
                    'reduction_amount' => $reduction,
                    'reduction_percentage' => $reductionPercentage,
                    'status' => 'pending_verification',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Notify farmer of potential credit
                \App\Jobs\FoodPassport\SendFoodPassportNotificationJob::dispatch(
                    $product->farmer_id,
                    'carbon_credit_eligible',
                    [
                        'product_id' => $product->id,
                        'reduction' => $reduction,
                        'reduction_percentage' => round($reductionPercentage, 2),
                    ]
                );
            } else {
                Log::info('Product does not qualify for carbon credits', [
                    'product_id' => $product->id,
                    'exceeded_baseline_by' => $product->total_carbon_footprint - $baseline,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to assess carbon credit eligibility', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
