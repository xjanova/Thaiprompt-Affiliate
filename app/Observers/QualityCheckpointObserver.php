<?php

namespace App\Observers;

use App\Models\QualityCheckpoint;
use App\Services\BlockchainRecordService;
use App\Jobs\FoodPassport\SendQualityAlertJob;
use App\Jobs\FoodPassport\IssueCertificationJob;
use App\Jobs\FoodPassport\RecordQualityOnBlockchainJob;
use Illuminate\Support\Facades\Log;

class QualityCheckpointObserver
{
    protected BlockchainRecordService $blockchain;

    public function __construct(BlockchainRecordService $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    /**
     * Handle the QualityCheckpoint "created" event.
     */
    public function created(QualityCheckpoint $checkpoint): void
    {
        Log::info('Quality checkpoint created', [
            'product_id' => $checkpoint->food_product_id,
            'type' => $checkpoint->checkpoint_type,
            'result' => $checkpoint->overall_result,
        ]);

        // Handle based on result
        match($checkpoint->overall_result) {
            'fail' => $this->handleFailedCheckpoint($checkpoint),
            'conditional_pass' => $this->handleConditionalPass($checkpoint),
            'pass' => $this->handlePassedCheckpoint($checkpoint),
            default => null,
        };

        // Record on blockchain
        if (config('food-passport.blockchain.auto_record', true)) {
            RecordQualityOnBlockchainJob::dispatch($checkpoint);
        }
    }

    /**
     * Handle the QualityCheckpoint "updated" event.
     */
    public function updated(QualityCheckpoint $checkpoint): void
    {
        // If result changed from fail to pass
        if ($checkpoint->isDirty('overall_result')) {
            $oldResult = $checkpoint->getOriginal('overall_result');
            $newResult = $checkpoint->overall_result;

            Log::info('Quality checkpoint result changed', [
                'checkpoint_id' => $checkpoint->id,
                'old_result' => $oldResult,
                'new_result' => $newResult,
            ]);

            // Handle based on new result
            match($newResult) {
                'fail' => $this->handleFailedCheckpoint($checkpoint),
                'conditional_pass' => $this->handleConditionalPass($checkpoint),
                'pass' => $this->handlePassedCheckpoint($checkpoint),
                default => null,
            };

            // Update blockchain record
            if (config('food-passport.blockchain.auto_record', true)) {
                RecordQualityOnBlockchainJob::dispatch($checkpoint);
            }
        }
    }

    /**
     * Handle failed quality checkpoint.
     */
    protected function handleFailedCheckpoint(QualityCheckpoint $checkpoint): void
    {
        Log::warning('Quality checkpoint failed', [
            'checkpoint_id' => $checkpoint->id,
            'product_id' => $checkpoint->food_product_id,
            'type' => $checkpoint->checkpoint_type,
        ]);

        // Update product quality status
        $product = $checkpoint->foodProduct;
        if ($product) {
            $product->quality_status = 'failed';
            $product->saveQuietly();
        }

        // Send alerts to all stakeholders
        SendQualityAlertJob::dispatch(
            $checkpoint,
            'quality_failed',
            [
                'checkpoint_type' => $checkpoint->checkpoint_type,
                'score' => $checkpoint->pass_score,
                'test_parameters' => $checkpoint->test_parameters,
            ]
        );

        // Mark product journey stage as requiring attention
        if ($checkpoint->productJourney) {
            $checkpoint->productJourney->quality_status = 'failed';
            $checkpoint->productJourney->saveQuietly();
        }
    }

    /**
     * Handle conditional pass quality checkpoint.
     */
    protected function handleConditionalPass(QualityCheckpoint $checkpoint): void
    {
        Log::info('Quality checkpoint conditional pass', [
            'checkpoint_id' => $checkpoint->id,
            'product_id' => $checkpoint->food_product_id,
        ]);

        // Send notification about conditional pass
        SendQualityAlertJob::dispatch(
            $checkpoint,
            'quality_conditional',
            [
                'corrective_actions' => $checkpoint->corrective_actions,
                'retest_required' => $checkpoint->retest_required,
            ]
        );

        // Update product quality status
        $product = $checkpoint->foodProduct;
        if ($product) {
            $product->quality_status = 'conditional';
            $product->saveQuietly();
        }
    }

    /**
     * Handle passed quality checkpoint.
     */
    protected function handlePassedCheckpoint(QualityCheckpoint $checkpoint): void
    {
        Log::info('Quality checkpoint passed', [
            'checkpoint_id' => $checkpoint->id,
            'product_id' => $checkpoint->food_product_id,
            'score' => $checkpoint->pass_score,
        ]);

        // Update product quality status
        $product = $checkpoint->foodProduct;
        if ($product) {
            // Only update if not already failed elsewhere
            if ($product->quality_status !== 'failed') {
                $product->quality_status = 'passed';
                $product->saveQuietly();
            }
        }

        // Update journey stage quality status
        if ($checkpoint->productJourney) {
            $checkpoint->productJourney->quality_status = 'passed';
            $checkpoint->productJourney->saveQuietly();
        }

        // Auto-issue certification if score is high enough
        if ($this->qualifiesForCertification($checkpoint)) {
            IssueCertificationJob::dispatch($checkpoint);
        }

        // Send success notification
        if ($checkpoint->pass_score >= 90) {
            SendQualityAlertJob::dispatch(
                $checkpoint,
                'quality_excellent',
                ['score' => $checkpoint->pass_score]
            );
        }
    }

    /**
     * Check if checkpoint qualifies for automatic certification.
     */
    protected function qualifiesForCertification(QualityCheckpoint $checkpoint): bool
    {
        // Criteria for auto-certification
        $criteria = [
            'pass_score' => 95,
            'types' => ['laboratory', 'certification'],
        ];

        return $checkpoint->pass_score >= $criteria['pass_score']
            && in_array($checkpoint->checkpoint_type, $criteria['types'])
            && !$checkpoint->certification_issued;
    }

    /**
     * Handle the QualityCheckpoint "deleting" event.
     */
    public function deleting(QualityCheckpoint $checkpoint): void
    {
        // Prevent deletion if blockchain verified
        if ($checkpoint->blockchain_hash) {
            throw new \Exception('Cannot delete blockchain-verified quality checkpoint.');
        }

        // Prevent deletion if certification was issued
        if ($checkpoint->certification_issued) {
            throw new \Exception('Cannot delete checkpoint that issued a certification.');
        }

        Log::info('Quality checkpoint deleting', ['id' => $checkpoint->id]);
    }
}
