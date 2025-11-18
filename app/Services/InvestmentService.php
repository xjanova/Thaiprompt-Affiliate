<?php

namespace App\Services;

use App\Models\InvestmentPlan;
use App\Models\StakingPosition;
use App\Models\RoiDistribution;
use App\Models\User;
use App\Models\Wallet;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvestmentService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Create a new investment/staking position
     */
    public function createInvestment(
        User $user,
        InvestmentPlan $plan,
        float $amount,
        bool $autoCompound = false
    ): array {
        try {
            DB::beginTransaction();

            // Validate investment
            $validation = $this->validateInvestment($user, $plan, $amount);
            if (!$validation['success']) {
                return $validation;
            }

            // Get or create wallet
            $wallet = $this->walletService->getOrCreateWallet($user);

            // Check wallet balance
            if ($wallet->balance < $amount) {
                return [
                    'success' => false,
                    'message' => 'ยอดเงินในกระเป๋าไม่เพียงพอ',
                    'error' => 'insufficient_balance',
                ];
            }

            // Get referrer (from MLM system)
            $referrerId = null;
            $mlmMember = $user->mlmMembers()->first();
            if ($mlmMember && $mlmMember->unilevel_sponsor_id) {
                $sponsorMember = $mlmMember->sponsor;
                $referrerId = $sponsorMember->user_id ?? null;
            }

            // Calculate expected ROI
            $expectedRoi = $plan->calculateExpectedROI($amount);

            // Get rank bonus multiplier
            $rankMultiplier = $this->getRankBonusMultiplier($user);

            // Create staking position
            $position = StakingPosition::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'investment_plan_id' => $plan->id,
                'amount' => $amount,
                'roi_rate' => $plan->roi_rate,
                'roi_frequency' => $plan->roi_frequency,
                'term_days' => $plan->term_days,
                'lock_days' => $plan->lock_days,
                'expected_roi' => $expectedRoi * $rankMultiplier,
                'auto_compound' => $autoCompound,
                'referrer_id' => $referrerId,
                'rank_bonus_multiplier' => $rankMultiplier,
                'status' => 'pending', // Requires admin approval
            ]);

            // Deduct from wallet
            $transaction = $this->walletService->withdrawal(
                $wallet,
                $amount,
                'การลงทุนในแผน ' . $plan->display_name,
                StakingPosition::class,
                $position->id,
                [
                    'investment_plan_id' => $plan->id,
                    'position_number' => $position->position_number,
                ]
            );

            // Calculate and pay referral bonus if applicable
            if ($referrerId && $plan->referral_bonus_rate > 0) {
                $this->payReferralBonus($position, $plan->referral_bonus_rate);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'สร้างการลงทุนสำเร็จ รอการอนุมัติจากแอดมิน',
                'position' => $position,
                'transaction' => $transaction,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Investment creation failed', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างการลงทุน',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate investment
     */
    protected function validateInvestment(User $user, InvestmentPlan $plan, float $amount): array
    {
        // Check if plan is active
        if (!$plan->is_active) {
            return [
                'success' => false,
                'message' => 'แผนการลงทุนนี้ไม่พร้อมใช้งาน',
                'error' => 'plan_inactive',
            ];
        }

        // Check if user can invest in this plan
        if (!$plan->canUserInvest($user)) {
            $reasons = [];

            if ($plan->requires_kyc && $user->kyc_status !== 'verified') {
                $reasons[] = 'ต้องยืนยันตัวตนก่อน (KYC)';
            }

            if ($plan->required_rank_id) {
                $reasons[] = 'ต้องมีแรงค์ขั้นต่ำ: ' . $plan->requiredRank->name;
            }

            if ($plan->max_investors && $plan->current_investors >= $plan->max_investors) {
                $reasons[] = 'แผนนี้เต็มแล้ว';
            }

            return [
                'success' => false,
                'message' => 'คุณไม่สามารถลงทุนในแผนนี้ได้: ' . implode(', ', $reasons),
                'error' => 'cannot_invest',
            ];
        }

        // Check amount range
        if ($amount < $plan->min_amount) {
            return [
                'success' => false,
                'message' => 'จำนวนเงินต่ำกว่าขั้นต่ำ (' . number_format($plan->min_amount, 2) . ' บาท)',
                'error' => 'amount_too_low',
            ];
        }

        if ($plan->max_amount && $amount > $plan->max_amount) {
            return [
                'success' => false,
                'message' => 'จำนวนเงินสูงกว่าสูงสุด (' . number_format($plan->max_amount, 2) . ' บาท)',
                'error' => 'amount_too_high',
            ];
        }

        return ['success' => true];
    }

    /**
     * Get rank bonus multiplier for user
     */
    protected function getRankBonusMultiplier(User $user): float
    {
        if (!$user->current_rank_id || !$user->currentRank) {
            return 1.00;
        }

        // Base multiplier on rank level
        // You can customize this based on your rank system
        $rank = $user->currentRank;
        $bonusMultiplier = $rank->bonus_multiplier ?? 1.00;

        return max(1.00, $bonusMultiplier);
    }

    /**
     * Pay referral bonus
     */
    protected function payReferralBonus(StakingPosition $position, float $bonusRate): void
    {
        if (!$position->referrer_id) {
            return;
        }

        try {
            $referrer = User::find($position->referrer_id);
            if (!$referrer) {
                return;
            }

            $bonusAmount = $position->amount * ($bonusRate / 100);
            $referrerWallet = $this->walletService->getOrCreateWallet($referrer);

            // Deposit bonus to referrer's wallet
            $this->walletService->deposit(
                $referrerWallet,
                $bonusAmount,
                'โบนัสแนะนำเพื่อนจากการลงทุน #' . $position->position_number,
                StakingPosition::class,
                $position->id,
                [
                    'type' => 'referral_bonus',
                    'position_number' => $position->position_number,
                ]
            );

            // Update position
            $position->update(['referral_bonus' => $bonusAmount]);

            Log::info('Referral bonus paid', [
                'position_id' => $position->id,
                'referrer_id' => $referrer->id,
                'amount' => $bonusAmount,
            ]);
        } catch (Exception $e) {
            Log::error('Referral bonus payment failed', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Approve investment position
     */
    public function approveInvestment(StakingPosition $position, User $admin): array
    {
        try {
            DB::beginTransaction();

            if ($position->status !== 'pending') {
                return [
                    'success' => false,
                    'message' => 'การลงทุนนี้ไม่สามารถอนุมัติได้',
                ];
            }

            // Approve the position
            $position->approve($admin);

            // Increment investor count
            $position->investmentPlan->incrementInvestors();

            DB::commit();

            return [
                'success' => true,
                'message' => 'อนุมัติการลงทุนสำเร็จ',
                'position' => $position,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Investment approval failed', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอนุมัติ',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Withdraw from investment position
     */
    public function withdrawInvestment(StakingPosition $position, ?string $reason = null): array
    {
        try {
            DB::beginTransaction();

            // Check if can withdraw
            if (!$position->canWithdraw()) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถถอนเงินได้ในขณะนี้',
                ];
            }

            $isEarlyWithdrawal = $position->isLocked();
            $withdrawalData = $isEarlyWithdrawal
                ? $position->calculateEarlyWithdrawalAmount()
                : [
                    'principal' => $position->amount,
                    'earned_roi' => $position->earned_roi,
                    'total' => $position->amount + $position->earned_roi,
                    'penalty' => 0,
                    'net_amount' => $position->amount + $position->earned_roi,
                ];

            // Deposit to wallet
            $wallet = $position->wallet;
            $transaction = $this->walletService->deposit(
                $wallet,
                $withdrawalData['net_amount'],
                'ถอนเงินจากการลงทุน #' . $position->position_number,
                StakingPosition::class,
                $position->id,
                [
                    'withdrawal_type' => $isEarlyWithdrawal ? 'early' : 'normal',
                    'penalty' => $withdrawalData['penalty'],
                ]
            );

            // Update position
            $position->update([
                'total_withdrawn' => $withdrawalData['net_amount'],
                'early_withdrawal_penalty' => $withdrawalData['penalty'],
                'withdrawal_reason' => $reason,
            ]);

            $position->markAsWithdrawn($isEarlyWithdrawal);

            // Decrement investor count
            $position->investmentPlan->decrementInvestors();

            DB::commit();

            return [
                'success' => true,
                'message' => 'ถอนเงินสำเร็จ',
                'position' => $position,
                'transaction' => $transaction,
                'withdrawal_data' => $withdrawalData,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Investment withdrawal failed', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการถอนเงิน',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get user's investment summary
     */
    public function getUserInvestmentSummary(User $user): array
    {
        $positions = StakingPosition::where('user_id', $user->id)->get();

        return [
            'total_invested' => $positions->whereIn('status', ['active', 'locked', 'matured'])->sum('amount'),
            'total_earned_roi' => $positions->sum('earned_roi'),
            'total_expected_roi' => $positions->whereIn('status', ['active', 'locked'])->sum('expected_roi'),
            'active_positions' => $positions->where('status', 'active')->count(),
            'total_positions' => $positions->count(),
            'total_withdrawn' => $positions->sum('total_withdrawn'),
            'pending_roi' => $positions->whereIn('status', ['active', 'locked'])->sum('pending_roi'),
        ];
    }
}
