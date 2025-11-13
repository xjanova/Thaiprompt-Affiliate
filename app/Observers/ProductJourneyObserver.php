<?php

namespace App\Observers;

use App\Models\ProductJourney;
use App\Services\CarbonCreditService;
use App\Services\BlockchainRecordService;
use App\Jobs\CalculateJourneyCarbonJob;
use App\Jobs\UpdateProductLocationJob;
use App\Jobs\SendStageCompletionNotificationJob;
use Illuminate\Support\Facades\Log;

class ProductJourneyObserver
{
    protected CarbonCreditService $carbonService;
    protected BlockchainRecordService $blockchain;

    public function __construct(
        CarbonCreditService $carbonService,
        BlockchainRecordService $blockchain
    ) {
        $this->carbonService = $carbonService;
        $this->blockchain = $blockchain;
    }

    /**
     * Handle the ProductJourney "created" event.
     */
    public function created(ProductJourney $journey): void
    {
        Log::info('Journey stage created', [
            'product_id' => $journey->food_product_id,
            'stage' => $journey->stage,
        ]);

        // Update product's current location
        UpdateProductLocationJob::dispatch($journey->foodProduct);

        // Record on blockchain if enabled
        if (config('food-passport.blockchain.auto_record', true)) {
            $this->recordJourneyOnBlockchain($journey);
        }
    }

    /**
     * Handle the ProductJourney "updated" event.
     */
    public function updated(ProductJourney $journey): void
    {
        // If stage was just completed (departed_at was set)
        if ($journey->isDirty('departed_at') && $journey->departed_at) {
            $this->handleStageCompletion($journey);
        }

        // Recalculate carbon if distance or transport method changed
        if ($journey->isDirty(['distance_km', 'transport_method', 'fuel_type'])) {
            CalculateJourneyCarbonJob::dispatch($journey);
        }

        Log::info('Journey stage updated', [
            'id' => $journey->id,
            'changes' => $journey->getChanges()
        ]);
    }

    /**
     * Handle the ProductJourney "deleting" event.
     */
    public function deleting(ProductJourney $journey): void
    {
        // Prevent deletion if blockchain verified
        if ($journey->blockchain_hash) {
            throw new \Exception('Cannot delete blockchain-verified journey stage.');
        }

        Log::info('Journey stage deleting', ['id' => $journey->id]);
    }

    /**
     * Handle stage completion actions.
     */
    protected function handleStageCompletion(ProductJourney $journey): void
    {
        Log::info('Journey stage completed', [
            'journey_id' => $journey->id,
            'stage' => $journey->stage,
            'duration_hours' => $journey->duration_hours,
        ]);

        // Calculate carbon emissions for this stage
        if ($journey->transport_method && $journey->distance_km) {
            CalculateJourneyCarbonJob::dispatch($journey);
        }

        // Update total product carbon footprint
        $this->updateProductCarbonFootprint($journey);

        // Send notifications to stakeholders
        SendStageCompletionNotificationJob::dispatch($journey);

        // Record completion on blockchain
        if (config('food-passport.blockchain.auto_record', true)) {
            $this->recordJourneyOnBlockchain($journey);
        }

        // Check if quality checkpoints are required
        $this->checkQualityRequirements($journey);

        // Check if all stages completed - issue carbon credits
        if ($this->isJourneyComplete($journey)) {
            $this->handleJourneyCompletion($journey);
        }
    }

    /**
     * Update product's total carbon footprint.
     */
    protected function updateProductCarbonFootprint(ProductJourney $journey): void
    {
        $product = $journey->foodProduct;

        // Sum all journey stages carbon emissions
        $totalJourneyCarbon = $product->journeyStages()
            ->whereNotNull('stage_carbon_emission')
            ->sum('stage_carbon_emission');

        // Add farming emissions from carbon records
        $farmingCarbon = $product->carbonRecords()
            ->where('emission_category', 'farming')
            ->sum('total_co2_equivalent');

        // Update product total
        $product->total_carbon_footprint = $totalJourneyCarbon + $farmingCarbon;
        $product->saveQuietly();

        Log::info('Product carbon footprint updated', [
            'product_id' => $product->id,
            'total_carbon' => $product->total_carbon_footprint,
        ]);
    }

    /**
     * Check if quality checkpoints are required for this stage.
     */
    protected function checkQualityRequirements(ProductJourney $journey): void
    {
        // Stages that require quality checks
        $requiresQuality = ['processing', 'packaging', 'retail'];

        if (in_array($journey->stage, $requiresQuality)) {
            // Check if quality checkpoint exists
            $hasCheckpoint = $journey->qualityCheckpoints()->exists();

            if (!$hasCheckpoint) {
                Log::warning('Quality checkpoint missing for stage', [
                    'journey_id' => $journey->id,
                    'stage' => $journey->stage,
                ]);

                // Could dispatch a notification job here
            }
        }
    }

    /**
     * Check if the entire product journey is complete.
     */
    protected function isJourneyComplete(ProductJourney $journey): bool
    {
        $product = $journey->foodProduct;

        // Check if product has reached retail/consumer stage
        $hasRetailStage = $product->journeyStages()
            ->where('stage', 'retail')
            ->whereNotNull('departed_at')
            ->exists();

        return $hasRetailStage;
    }

    /**
     * Handle actions when entire journey is complete.
     */
    protected function handleJourneyCompletion(ProductJourney $journey): void
    {
        $product = $journey->foodProduct;

        Log::info('Product journey completed', [
            'product_id' => $product->id,
            'passport_id' => $product->food_passport_id,
        ]);

        // Calculate eligibility for carbon credits
        $baseline = $this->carbonService->getBaseline($product);

        if ($product->total_carbon_footprint < $baseline) {
            // Product qualifies for carbon credits
            $reduction = $baseline - $product->total_carbon_footprint;

            Log::info('Product qualifies for carbon credits', [
                'product_id' => $product->id,
                'reduction' => $reduction,
                'baseline' => $baseline,
            ]);

            // Carbon credit issuance would be handled by CarbonCreditService
            // through a manual or automated approval process
        }
    }

    /**
     * Record journey stage on blockchain.
     */
    protected function recordJourneyOnBlockchain(ProductJourney $journey): void
    {
        try {
            if (!$journey->blockchain_hash) {
                $hash = $this->blockchain->recordJourneyStageOnChain($journey);
                $journey->blockchain_hash = $hash;
                $journey->saveQuietly();
            }
        } catch (\Exception $e) {
            Log::error('Failed to record journey on blockchain', [
                'journey_id' => $journey->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
