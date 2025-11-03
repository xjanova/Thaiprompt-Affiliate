<?php

namespace App\Services;

use App\Models\AiBotProfile;
use App\Models\BotRental;
use App\Models\RentalTransaction;
use App\Models\OwnerEarning;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RentalService
{
    /**
     * Rent a bot (monthly subscription)
     *
     * @param AiBotProfile $bot
     * @param User $renter
     * @param string $paymentMethod
     * @return array ['success' => bool, 'rental' => BotRental|null, 'transaction' => RentalTransaction|null]
     */
    public function rentMonthly(AiBotProfile $bot, User $renter, string $paymentMethod = 'wallet'): array
    {
        if (!$bot->is_rentable || !$bot->rental_price_per_month) {
            return [
                'success' => false,
                'error' => 'Bot is not available for rental',
            ];
        }

        // Check if already rented
        $existingRental = BotRental::where('bot_profile_id', $bot->id)
            ->where('renter_id', $renter->id)
            ->where('rental_type', 'monthly')
            ->active()
            ->first();

        if ($existingRental) {
            return [
                'success' => false,
                'error' => 'You already have an active rental for this bot',
            ];
        }

        try {
            DB::beginTransaction();

            // Create rental
            $rental = BotRental::create([
                'bot_profile_id' => $bot->id,
                'renter_id' => $renter->id,
                'rental_type' => 'monthly',
                'price' => $bot->rental_price_per_month,
                'commission_rate' => $bot->commission_rate ?? 20, // Default 20%
                'start_date' => now(),
                'end_date' => now()->addMonth(),
                'auto_renew' => true,
                'status' => 'active',
                'last_billing_date' => now(),
                'next_billing_date' => now()->addMonth(),
            ]);

            // Calculate amounts
            $amount = $rental->price;
            $commission = $rental->calculateCommission($amount);
            $ownerEarning = $rental->calculateOwnerEarning($amount);

            // Create transaction
            $transaction = RentalTransaction::create([
                'rental_id' => $rental->id,
                'user_id' => $renter->id,
                'type' => 'subscription',
                'amount' => $amount,
                'platform_commission' => $commission,
                'owner_earning' => $ownerEarning,
                'period_start' => $rental->start_date,
                'period_end' => $rental->end_date,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
                'description' => 'Monthly subscription for ' . $bot->name,
            ]);

            // Record owner earning
            OwnerEarning::create([
                'owner_id' => $bot->owner_id,
                'bot_profile_id' => $bot->id,
                'rental_id' => $rental->id,
                'transaction_id' => $transaction->id,
                'gross_amount' => $amount,
                'commission' => $commission,
                'net_amount' => $ownerEarning,
                'earning_date' => now()->toDateString(),
                'earning_month' => now()->format('Y-m'),
                'payout_status' => 'pending',
                'description' => 'Monthly rental earning',
            ]);

            DB::commit();

            Log::info('Bot rented successfully', [
                'rental_id' => $rental->id,
                'bot_id' => $bot->id,
                'renter_id' => $renter->id,
                'amount' => $amount,
            ]);

            return [
                'success' => true,
                'rental' => $rental,
                'transaction' => $transaction,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bot rental failed', [
                'bot_id' => $bot->id,
                'renter_id' => $renter->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process rental: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Rent a bot (per-message)
     *
     * @param AiBotProfile $bot
     * @param User $renter
     * @return array
     */
    public function rentPerMessage(AiBotProfile $bot, User $renter): array
    {
        if (!$bot->is_rentable || !$bot->rental_price_per_message) {
            return [
                'success' => false,
                'error' => 'Bot is not available for per-message rental',
            ];
        }

        // Check if already has per-message rental
        $existingRental = BotRental::where('bot_profile_id', $bot->id)
            ->where('renter_id', $renter->id)
            ->where('rental_type', 'per_message')
            ->active()
            ->first();

        if ($existingRental) {
            return [
                'success' => true,
                'rental' => $existingRental,
                'message' => 'Using existing per-message rental',
            ];
        }

        try {
            // Create rental (no upfront payment for per-message)
            $rental = BotRental::create([
                'bot_profile_id' => $bot->id,
                'renter_id' => $renter->id,
                'rental_type' => 'per_message',
                'price' => $bot->rental_price_per_message,
                'commission_rate' => $bot->commission_rate ?? 20,
                'start_date' => now(),
                'status' => 'active',
            ]);

            Log::info('Per-message rental created', [
                'rental_id' => $rental->id,
                'bot_id' => $bot->id,
                'renter_id' => $renter->id,
            ]);

            return [
                'success' => true,
                'rental' => $rental,
            ];
        } catch (\Exception $e) {
            Log::error('Per-message rental failed', [
                'bot_id' => $bot->id,
                'renter_id' => $renter->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create rental: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Charge for message usage (per-message rental)
     *
     * @param BotRental $rental
     * @param int $messageCount
     * @return array
     */
    public function chargeMessageUsage(BotRental $rental, int $messageCount = 1): array
    {
        if ($rental->rental_type !== 'per_message') {
            return [
                'success' => false,
                'error' => 'Rental is not per-message type',
            ];
        }

        try {
            DB::beginTransaction();

            $amount = $rental->price * $messageCount;
            $commission = $rental->calculateCommission($amount);
            $ownerEarning = $rental->calculateOwnerEarning($amount);

            // Update rental totals
            $rental->increment('total_messages', $messageCount);
            $rental->increment('total_amount', $amount);

            // Create transaction
            $transaction = RentalTransaction::create([
                'rental_id' => $rental->id,
                'user_id' => $rental->renter_id,
                'type' => 'usage',
                'amount' => $amount,
                'platform_commission' => $commission,
                'owner_earning' => $ownerEarning,
                'message_count' => $messageCount,
                'rate_per_message' => $rental->price,
                'payment_method' => 'wallet',
                'status' => 'completed',
                'description' => "{$messageCount} messages @ {$rental->price} each",
            ]);

            // Record owner earning
            OwnerEarning::create([
                'owner_id' => $rental->botProfile->owner_id,
                'bot_profile_id' => $rental->bot_profile_id,
                'rental_id' => $rental->id,
                'transaction_id' => $transaction->id,
                'gross_amount' => $amount,
                'commission' => $commission,
                'net_amount' => $ownerEarning,
                'earning_date' => now()->toDateString(),
                'earning_month' => now()->format('Y-m'),
                'payout_status' => 'pending',
                'description' => "Usage earning: {$messageCount} messages",
            ]);

            DB::commit();

            return [
                'success' => true,
                'transaction' => $transaction,
                'charged_amount' => $amount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Message usage charge failed', [
                'rental_id' => $rental->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to charge: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel rental
     *
     * @param BotRental $rental
     * @param string $reason
     * @return array
     */
    public function cancelRental(BotRental $rental, string $reason = ''): array
    {
        try {
            $rental->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'end_date' => now(),
            ]);

            Log::info('Rental cancelled', [
                'rental_id' => $rental->id,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'message' => 'Rental cancelled successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Rental cancellation failed', [
                'rental_id' => $rental->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to cancel rental: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get owner earnings summary
     *
     * @param User $owner
     * @param string|null $month Format: YYYY-MM
     * @return array
     */
    public function getOwnerEarningsSummary(User $owner, ?string $month = null): array
    {
        $query = OwnerEarning::forOwner($owner->id);

        if ($month) {
            $query->forMonth($month);
        }

        $totalGross = $query->sum('gross_amount');
        $totalCommission = $query->sum('commission');
        $totalNet = $query->sum('net_amount');

        $pendingPayout = $query->where('payout_status', 'pending')->sum('net_amount');
        $paidOut = $query->where('payout_status', 'paid')->sum('net_amount');

        return [
            'total_gross' => $totalGross,
            'total_commission' => $totalCommission,
            'total_net' => $totalNet,
            'pending_payout' => $pendingPayout,
            'paid_out' => $paidOut,
            'commission_rate' => $totalGross > 0 ? ($totalCommission / $totalGross) * 100 : 0,
        ];
    }

    /**
     * Check if user can rent a bot
     *
     * @param AiBotProfile $bot
     * @param User $user
     * @return bool
     */
    public function canUserRentBot(AiBotProfile $bot, User $user): bool
    {
        // Can't rent own bot
        if ($bot->owner_id === $user->id) {
            return false;
        }

        // Bot must be rentable
        if (!$bot->is_rentable) {
            return false;
        }

        // Bot must be active
        if (!$bot->is_active) {
            return false;
        }

        return true;
    }
}
