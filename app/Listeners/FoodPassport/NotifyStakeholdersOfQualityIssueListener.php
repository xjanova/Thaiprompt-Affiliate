<?php

namespace App\Listeners\FoodPassport;

use App\Events\FoodPassport\QualityCheckFailedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyStakeholdersOfQualityIssueListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(QualityCheckFailedEvent $event): void
    {
        try {
            $checkpoint = $event->checkpoint;
            $product = $checkpoint->foodProduct;

            // Get all stakeholders in the supply chain
            $stakeholders = $this->getProductStakeholders($product);

            Log::info('Notifying stakeholders of quality issue', [
                'checkpoint_id' => $checkpoint->id,
                'product_id' => $product->id,
                'stakeholder_count' => count($stakeholders),
            ]);

            // Notify each stakeholder
            foreach ($stakeholders as $stakeholder) {
                \App\Jobs\FoodPassport\SendFoodPassportNotificationJob::dispatch(
                    $stakeholder['user_id'],
                    'quality_failed',
                    [
                        'product_id' => $product->id,
                        'passport_id' => $product->food_passport_id,
                        'checkpoint_type' => $checkpoint->checkpoint_type,
                        'result' => $checkpoint->overall_result,
                        'score' => $checkpoint->pass_score,
                        'corrective_actions' => $checkpoint->corrective_actions,
                        'retest_required' => $checkpoint->retest_required,
                    ]
                );
            }

            // Create quality alert record
            \DB::table('quality_alerts')->insert([
                'quality_checkpoint_id' => $checkpoint->id,
                'food_product_id' => $product->id,
                'severity' => $this->determineSeverity($checkpoint),
                'alert_type' => 'quality_failed',
                'notified_count' => count($stakeholders),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to notify stakeholders of quality issue', [
                'checkpoint_id' => $event->checkpoint->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all stakeholders for a product.
     */
    protected function getProductStakeholders($product): array
    {
        $stakeholders = [];

        // Add farmer
        $stakeholders[] = [
            'user_id' => $product->farmer_id,
            'role' => 'farmer',
        ];

        // Add stakeholders from journey stages
        $journeyStakeholders = \DB::table('product_journey')
            ->where('food_product_id', $product->id)
            ->whereNotNull('handler_id')
            ->select('handler_id as user_id', 'handler_type as role')
            ->distinct()
            ->get()
            ->toArray();

        return array_merge($stakeholders, $journeyStakeholders);
    }

    /**
     * Determine alert severity based on checkpoint.
     */
    protected function determineSeverity($checkpoint): string
    {
        if ($checkpoint->pass_score < 50) {
            return 'critical';
        } elseif ($checkpoint->pass_score < 70) {
            return 'high';
        } else {
            return 'medium';
        }
    }
}
