<?php

namespace App\Services\TPIX;

use App\Models\User;
use App\Models\TPIXToken;
use App\Models\TPIXReferralCode;
use App\Models\TPIXReferralUse;
use App\Models\TPIXReferralReward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * Referral Service with Verification System
 *
 * Advanced referral system for TPIX tokens with email/SMS verification
 */
class ReferralService
{
    /**
     * Create referral code
     */
    public function createReferralCode(User $user, array $data): TPIXReferralCode
    {
        DB::beginTransaction();
        try {
            $code = $this->generateUniqueCode($data['code_prefix'] ?? 'REF');

            $referralCode = TPIXReferralCode::create([
                'user_id' => $user->id,
                'token_id' => $data['token_id'] ?? null,
                'code' => $code,
                'name' => $data['name'] ?? null,
                'type' => $data['type'] ?? 'user',
                'reward_percentage' => $data['reward_percentage'] ?? 5.00,
                'referrer_percentage' => $data['referrer_percentage'] ?? 2.00,
                'reward_type' => $data['reward_type'] ?? 'tpix',
                'max_uses' => $data['max_uses'] ?? null,
                'min_transaction_amount' => $data['min_transaction_amount'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'is_active' => true,
            ]);

            DB::commit();

            Log::info('Referral code created', [
                'code' => $code,
                'user_id' => $user->id
            ]);

            return $referralCode;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create referral code', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Use referral code (with verification)
     */
    public function useReferralCode(
        string $code,
        User $referee,
        ?string $relatedTxHash = null,
        ?string $transactionAmount = null
    ): TPIXReferralUse {
        DB::beginTransaction();
        try {
            // Find referral code
            $referralCode = TPIXReferralCode::where('code', $code)
                ->where('is_active', true)
                ->first();

            if (!$referralCode) {
                throw new Exception('Invalid referral code');
            }

            // Check if expired
            if ($referralCode->expires_at && $referralCode->expires_at->isPast()) {
                throw new Exception('Referral code has expired');
            }

            // Check max uses
            if ($referralCode->max_uses && $referralCode->total_uses >= $referralCode->max_uses) {
                throw new Exception('Referral code has reached maximum uses');
            }

            // Check minimum transaction amount
            if ($referralCode->min_transaction_amount && $transactionAmount) {
                if (bccomp($transactionAmount, $referralCode->min_transaction_amount, 8) < 0) {
                    throw new Exception('Transaction amount is below minimum');
                }
            }

            // Check if user already used this code
            $alreadyUsed = TPIXReferralUse::where('referral_code_id', $referralCode->id)
                ->where('referee_id', $referee->id)
                ->exists();

            if ($alreadyUsed) {
                throw new Exception('You have already used this referral code');
            }

            // Calculate rewards
            $refereeReward = $transactionAmount
                ? bcmul($transactionAmount, bcdiv($referralCode->reward_percentage, '100', 8), 8)
                : '0';

            $referrerReward = $transactionAmount
                ? bcmul($transactionAmount, bcdiv($referralCode->referrer_percentage, '100', 8), 8)
                : '0';

            // Generate verification code
            $verificationCode = $this->generateVerificationCode();

            // Create referral use
            $referralUse = TPIXReferralUse::create([
                'referral_code_id' => $referralCode->id,
                'referrer_id' => $referralCode->user_id,
                'referee_id' => $referee->id,
                'token_id' => $referralCode->token_id,
                'related_tx_hash' => $relatedTxHash,
                'transaction_amount' => $transactionAmount,
                'referrer_reward' => $referrerReward,
                'referee_reward' => $refereeReward,
                'reward_status' => 'pending',
                'is_verified' => false,
                'verification_code' => $verificationCode,
            ]);

            // Update referral code stats
            $referralCode->increment('total_uses');
            $referralCode->update(['last_used_at' => now()]);

            // Send verification email/SMS
            $this->sendVerificationCode($referee, $verificationCode, $referralCode);

            DB::commit();

            Log::info('Referral code used', [
                'code' => $code,
                'referee_id' => $referee->id,
                'referrer_id' => $referralCode->user_id
            ]);

            return $referralUse;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to use referral code', [
                'code' => $code,
                'referee_id' => $referee->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Verify referral use
     */
    public function verifyReferralUse(TPIXReferralUse $referralUse, string $verificationCode): bool
    {
        if ($referralUse->is_verified) {
            throw new Exception('Referral already verified');
        }

        // Check verification attempts
        if ($referralUse->verification_attempts >= 5) {
            throw new Exception('Maximum verification attempts exceeded');
        }

        // Increment attempts
        $referralUse->increment('verification_attempts');

        // Check code
        if ($referralUse->verification_code !== $verificationCode) {
            throw new Exception('Invalid verification code');
        }

        DB::beginTransaction();
        try {
            // Mark as verified
            $referralUse->update([
                'is_verified' => true,
                'verified_at' => now(),
            ]);

            // Create reward records
            if ($referralUse->referrer_reward > 0) {
                TPIXReferralReward::create([
                    'referral_use_id' => $referralUse->id,
                    'user_id' => $referralUse->referrer_id,
                    'token_id' => $referralUse->token_id,
                    'reward_type' => 'referrer',
                    'amount' => $referralUse->referrer_reward,
                    'currency_type' => $this->getCurrencyType($referralUse),
                    'status' => 'pending',
                ]);
            }

            if ($referralUse->referee_reward > 0) {
                TPIXReferralReward::create([
                    'referral_use_id' => $referralUse->id,
                    'user_id' => $referralUse->referee_id,
                    'token_id' => $referralUse->token_id,
                    'reward_type' => 'referee',
                    'amount' => $referralUse->referee_reward,
                    'currency_type' => $this->getCurrencyType($referralUse),
                    'status' => 'pending',
                ]);
            }

            // Update referral code stats
            $referralUse->referralCode->increment('successful_referrals');

            DB::commit();

            Log::info('Referral verified', [
                'referral_use_id' => $referralUse->id
            ]);

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to verify referral', [
                'referral_use_id' => $referralUse->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Process reward payment
     */
    public function processReward(TPIXReferralReward $reward): bool
    {
        if ($reward->status !== 'pending') {
            throw new Exception('Reward is not pending');
        }

        DB::beginTransaction();
        try {
            $reward->update(['status' => 'processing']);

            // In production: Actually send TPIX or tokens
            // For now: Simulate payment
            $txHash = $this->simulatePayment($reward);

            $reward->update([
                'payment_tx_hash' => $txHash,
                'paid_at' => now(),
                'status' => 'paid',
            ]);

            // Update referral use
            $referralUse = $reward->referralUse;
            $allRewardsPaid = $referralUse->rewards()
                ->where('status', '!=', 'paid')
                ->count() === 0;

            if ($allRewardsPaid) {
                $referralUse->update(['reward_status' => 'paid']);
            }

            // Update referral code total rewards
            $referralCode = $referralUse->referralCode;
            $referralCode->increment('total_rewards_paid', $reward->amount);

            DB::commit();

            Log::info('Referral reward paid', [
                'reward_id' => $reward->id,
                'amount' => $reward->amount,
                'user_id' => $reward->user_id
            ]);

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            $reward->update([
                'status' => 'failed',
                'notes' => $e->getMessage()
            ]);

            Log::error('Failed to process reward', [
                'reward_id' => $reward->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Generate unique referral code
     */
    protected function generateUniqueCode(string $prefix = 'REF'): string
    {
        do {
            $code = strtoupper($prefix . '-' . Str::random(8));
        } while (TPIXReferralCode::where('code', $code)->exists());

        return $code;
    }

    /**
     * Generate verification code
     */
    protected function generateVerificationCode(): string
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send verification code
     */
    protected function sendVerificationCode(User $user, string $code, TPIXReferralCode $referralCode)
    {
        // In production: Send email or SMS
        // For now: Just log
        Log::info('Verification code sent', [
            'user_id' => $user->id,
            'code' => $code,
            'email' => $user->email
        ]);

        // Example email sending:
        // Mail::to($user->email)->send(new ReferralVerificationMail($code, $referralCode));
    }

    /**
     * Get currency type for reward
     */
    protected function getCurrencyType(TPIXReferralUse $referralUse): string
    {
        if ($referralUse->token_id) {
            $token = TPIXToken::find($referralUse->token_id);
            return $token ? $token->symbol : 'TPIX';
        }

        return 'TPIX';
    }

    /**
     * Simulate payment (replace with real payment in production)
     */
    protected function simulatePayment(TPIXReferralReward $reward): string
    {
        return '0x' . bin2hex(random_bytes(32));
    }

    /**
     * Get user referral statistics
     */
    public function getUserStats(User $user): array
    {
        return [
            'total_referrals' => TPIXReferralUse::where('referrer_id', $user->id)
                ->where('is_verified', true)
                ->count(),

            'pending_referrals' => TPIXReferralUse::where('referrer_id', $user->id)
                ->where('is_verified', false)
                ->count(),

            'total_rewards_earned' => TPIXReferralReward::where('user_id', $user->id)
                ->where('status', 'paid')
                ->sum('amount'),

            'pending_rewards' => TPIXReferralReward::where('user_id', $user->id)
                ->where('status', 'pending')
                ->sum('amount'),

            'active_codes' => TPIXReferralCode::where('user_id', $user->id)
                ->where('is_active', true)
                ->count(),
        ];
    }
}
