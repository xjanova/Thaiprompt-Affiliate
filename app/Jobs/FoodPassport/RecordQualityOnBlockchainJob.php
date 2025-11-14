<?php

namespace App\Jobs\FoodPassport;

use App\Models\QualityCheckpoint;
use App\Services\TpixBlockchainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Record Quality Checkpoint on Blockchain Job
 *
 * Records quality checkpoint data on TPIX blockchain for immutability
 */
class RecordQualityOnBlockchainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public QualityCheckpoint $checkpoint
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TpixBlockchainService $blockchain): void
    {
        // Skip if already recorded
        if ($this->checkpoint->blockchain_hash) {
            Log::info('Quality checkpoint already recorded on blockchain', [
                'checkpoint_id' => $this->checkpoint->id,
                'hash' => $this->checkpoint->blockchain_hash,
            ]);
            return;
        }

        Log::info('Recording quality checkpoint on blockchain', [
            'checkpoint_id' => $this->checkpoint->id,
            'product_id' => $this->checkpoint->food_product_id,
        ]);

        try {
            $product = $this->checkpoint->foodProduct;
            if (!$product) {
                throw new \Exception('Product not found for quality checkpoint');
            }

            // Prepare checkpoint data for blockchain
            $checkpointData = [
                'passport_id' => $product->food_passport_id,
                'checkpoint_id' => $this->checkpoint->id,
                'checkpoint_type' => $this->checkpoint->checkpoint_type,
                'stage' => $this->checkpoint->productJourney?->stage ?? 'unknown',
                'result' => $this->checkpoint->overall_result,
                'score' => $this->checkpoint->pass_score,
                'inspector' => $this->checkpoint->inspector_name ?? 'Unknown',
                'location' => $this->checkpoint->productJourney?->current_location ?? null,
                'coordinates' => $this->checkpoint->productJourney?->gps_coordinates ?? null,
                'test_parameters' => json_encode($this->checkpoint->test_parameters),
                'timestamp' => $this->checkpoint->checkpoint_date?->timestamp ?? now()->timestamp,
            ];

            // Upload metadata to IPFS
            $metadata = [
                'type' => 'quality_checkpoint',
                'checkpoint_data' => $checkpointData,
                'test_parameters' => $this->checkpoint->test_parameters,
                'inspector_notes' => $this->checkpoint->inspector_notes,
                'images' => $this->checkpoint->images ?? [],
                'recorded_at' => now()->toIso8601String(),
            ];

            $ipfsHash = $blockchain->uploadToIPFS($metadata);

            // Record on blockchain via smart contract
            $txHash = $blockchain->callContract(
                'quality_record',
                'recordQualityCheck',
                [
                    $product->food_passport_id,
                    $this->checkpoint->checkpoint_type,
                    $this->checkpoint->overall_result,
                    (int) $this->checkpoint->pass_score,
                    "ipfs://{$ipfsHash}",
                    $checkpointData['timestamp']
                ]
            );

            // Update checkpoint with blockchain info
            $this->checkpoint->update([
                'blockchain_hash' => $txHash,
                'ipfs_hash' => $ipfsHash,
            ]);

            Log::info('Quality checkpoint recorded on blockchain successfully', [
                'checkpoint_id' => $this->checkpoint->id,
                'tx_hash' => $txHash,
                'ipfs_hash' => $ipfsHash,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to record quality checkpoint on blockchain', [
                'checkpoint_id' => $this->checkpoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Quality checkpoint blockchain recording failed permanently', [
            'checkpoint_id' => $this->checkpoint->id,
            'error' => $exception->getMessage(),
        ]);

        // Mark checkpoint as having blockchain error
        $this->checkpoint->update([
            'blockchain_status' => 'failed',
        ]);
    }
}
