<?php

namespace App\Observers;

use App\Models\CarbonCredit;
use App\Services\BlockchainRecordService;
use App\Jobs\RecordCarbonCreditOnBlockchainJob;
use App\Jobs\SendCarbonCreditNotificationJob;
use App\Jobs\ProcessCommissionJob;
use Illuminate\Support\Facades\Log;

class CarbonCreditObserver
{
    protected BlockchainRecordService $blockchain;

    public function __construct(BlockchainRecordService $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    /**
     * Handle the CarbonCredit "created" event.
     */
    public function created(CarbonCredit $credit): void
    {
        Log::info('Carbon credit issued', [
            'credit_id' => $credit->id,
            'user_id' => $credit->user_id,
            'amount' => $credit->credit_amount,
            'type' => $credit->credit_type,
        ]);

        // Record on blockchain
        if (config('food-passport.blockchain.auto_record', true)) {
            RecordCarbonCreditOnBlockchainJob::dispatch($credit);
        }

        // Send notification to credit owner
        SendCarbonCreditNotificationJob::dispatch(
            $credit,
            'credit_issued',
            [
                'amount' => $credit->credit_amount,
                'value' => $credit->total_value,
                'tier' => $this->determineTier($credit),
            ]
        );

        // Update user's total carbon credits
        $this->updateUserCarbonStats($credit);
    }

    /**
     * Handle the CarbonCredit "updated" event.
     */
    public function updated(CarbonCredit $credit): void
    {
        // Handle status changes
        if ($credit->isDirty('status')) {
            $this->handleStatusChange($credit);
        }

        // Handle trades
        if ($credit->isDirty('traded_to_user_id') && $credit->traded_to_user_id) {
            $this->handleCreditTrade($credit);
        }

        Log::info('Carbon credit updated', [
            'credit_id' => $credit->id,
            'changes' => $credit->getChanges()
        ]);
    }

    /**
     * Handle carbon credit status changes.
     */
    protected function handleStatusChange(CarbonCredit $credit): void
    {
        $oldStatus = $credit->getOriginal('status');
        $newStatus = $credit->status;

        Log::info('Carbon credit status changed', [
            'credit_id' => $credit->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        match($newStatus) {
            'traded' => $this->handleTradedStatus($credit),
            'retired' => $this->handleRetiredStatus($credit),
            'expired' => $this->handleExpiredStatus($credit),
            default => null,
        };

        // Update blockchain record for status change
        if (config('food-passport.blockchain.auto_record', true)) {
            RecordCarbonCreditOnBlockchainJob::dispatch($credit);
        }
    }

    /**
     * Handle credit trade.
     */
    protected function handleCreditTrade(CarbonCredit $credit): void
    {
        $seller = $credit->user;
        $buyer = $credit->tradedToUser;

        Log::info('Carbon credit traded', [
            'credit_id' => $credit->id,
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'price' => $credit->trade_price,
        ]);

        // Process platform commission (5%)
        $commission = $credit->trade_price * 0.05;
        ProcessCommissionJob::dispatch($credit, $commission);

        // Notify seller
        SendCarbonCreditNotificationJob::dispatch(
            $credit,
            'credit_sold',
            [
                'buyer_name' => $buyer->name,
                'price' => $credit->trade_price,
                'commission' => $commission,
                'net_amount' => $credit->trade_price - $commission,
            ]
        );

        // Notify buyer
        SendCarbonCreditNotificationJob::dispatch(
            $credit,
            'credit_purchased',
            [
                'seller_name' => $seller->name,
                'amount' => $credit->credit_amount,
                'price' => $credit->trade_price,
            ],
            $buyer->id
        );

        // Update both users' carbon stats
        $this->updateUserCarbonStats($credit, $seller->id);
        $this->updateUserCarbonStats($credit, $buyer->id);

        // Record trade on blockchain
        RecordCarbonCreditOnBlockchainJob::dispatch($credit);
    }

    /**
     * Handle traded status.
     */
    protected function handleTradedStatus(CarbonCredit $credit): void
    {
        Log::info('Carbon credit marked as traded', ['credit_id' => $credit->id]);

        // Credit is now owned by traded_to_user_id
        // Update user_id to reflect new owner
        if ($credit->traded_to_user_id) {
            $credit->user_id = $credit->traded_to_user_id;
            $credit->status = 'active'; // Back to active for new owner
            $credit->tradeable = true; // Can be traded again
            $credit->saveQuietly();
        }
    }

    /**
     * Handle retired status.
     */
    protected function handleRetiredStatus(CarbonCredit $credit): void
    {
        Log::info('Carbon credit retired', [
            'credit_id' => $credit->id,
            'amount' => $credit->credit_amount,
        ]);

        // Retired credits cannot be traded
        $credit->tradeable = false;
        $credit->saveQuietly();

        // Notify credit owner
        SendCarbonCreditNotificationJob::dispatch(
            $credit,
            'credit_retired',
            [
                'amount' => $credit->credit_amount,
                'environmental_impact' => $this->calculateEnvironmentalImpact($credit),
            ]
        );

        // Update global retired credits statistics
        $this->updateRetiredCreditsStats($credit);
    }

    /**
     * Handle expired status.
     */
    protected function handleExpiredStatus(CarbonCredit $credit): void
    {
        Log::warning('Carbon credit expired', [
            'credit_id' => $credit->id,
            'expiry_date' => $credit->expiry_date,
        ]);

        // Expired credits cannot be traded
        $credit->tradeable = false;
        $credit->saveQuietly();

        // Notify credit owner
        SendCarbonCreditNotificationJob::dispatch(
            $credit,
            'credit_expired',
            [
                'amount' => $credit->credit_amount,
                'expiry_date' => $credit->expiry_date,
            ]
        );
    }

    /**
     * Determine reward tier based on credit amount.
     */
    protected function determineTier(CarbonCredit $credit): string
    {
        $tiers = config('food-passport.carbon.tiers', []);

        foreach ($tiers as $tier => $config) {
            if ($credit->credit_amount >= $config['threshold']) {
                return $tier;
            }
        }

        return 'Bronze';
    }

    /**
     * Update user's carbon credit statistics.
     */
    protected function updateUserCarbonStats(CarbonCredit $credit, ?int $userId = null): void
    {
        $userId = $userId ?? $credit->user_id;

        $totalCredits = CarbonCredit::where('user_id', $userId)
            ->where('status', 'active')
            ->sum('credit_amount');

        $totalValue = CarbonCredit::where('user_id', $userId)
            ->where('status', 'active')
            ->sum('current_value');

        Log::info('User carbon stats updated', [
            'user_id' => $userId,
            'total_credits' => $totalCredits,
            'total_value' => $totalValue,
        ]);

        // This could be stored in a user_stats table or cache
    }

    /**
     * Update global retired credits statistics.
     */
    protected function updateRetiredCreditsStats(CarbonCredit $credit): void
    {
        $totalRetired = CarbonCredit::where('status', 'retired')
            ->sum('credit_amount');

        Log::info('Global retired credits updated', [
            'total_retired' => $totalRetired,
        ]);

        // Could be cached for dashboard display
    }

    /**
     * Calculate environmental impact of retired credit.
     */
    protected function calculateEnvironmentalImpact(CarbonCredit $credit): array
    {
        // 1 carbon credit = 1 ton of CO2 equivalent
        $co2Tonnes = $credit->credit_amount;

        return [
            'co2_offset_tonnes' => $co2Tonnes,
            'trees_equivalent' => round($co2Tonnes * 50), // ~50 trees per tonne CO2/year
            'cars_off_road_days' => round($co2Tonnes * 250), // ~250 days per tonne
            'homes_powered_days' => round($co2Tonnes * 100), // ~100 days per tonne
        ];
    }

    /**
     * Handle the CarbonCredit "deleting" event.
     */
    public function deleting(CarbonCredit $credit): void
    {
        // Prevent deletion if blockchain verified
        if ($credit->blockchain_hash) {
            throw new \Exception('Cannot delete blockchain-verified carbon credit.');
        }

        // Prevent deletion if traded
        if ($credit->status === 'traded' || $credit->traded_to_user_id) {
            throw new \Exception('Cannot delete traded carbon credit.');
        }

        Log::info('Carbon credit deleting', ['id' => $credit->id]);
    }
}
