<?php

namespace App\Services;

use App\Models\StakingPosition;
use App\Models\RoiDistribution;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StakingService
{
    protected WalletService $walletService;
    protected InvestmentService $investmentService;

    public function __construct(WalletService $walletService, InvestmentService $investmentService)
    {
        $this->walletService = $walletService;
        $this->investmentService = $investmentService;
    }

    /**
     * Distribute ROI to all eligible positions
     */
    public function distributeROI(): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'total_amount' => 0,
            'errors' => [],
        ];

        try {
            // Get all positions ready for distribution
            $positions = StakingPosition::readyForDistribution()->get();

            Log::info('ROI Distribution started', [
                'positions_count' => $positions->count(),
                'date' => now()->toDateString(),
            ]);

            foreach ($positions as $position) {
                $result = $this->distributePositionROI($position);

                if ($result['success']) {
                    $results['success']++;
                    $results['total_amount'] += $result['amount'];
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'position_id' => $position->id,
                        'error' => $result['error'] ?? 'Unknown error',
                    ];
                }
            }

            Log::info('ROI Distribution completed', $results);

            return $results;
        } catch (Exception $e) {
            Log::error('ROI Distribution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Distribute ROI for a single position
     */
    public function distributePositionROI(StakingPosition $position): array
    {
        try {
            DB::beginTransaction();

            // Calculate ROI amount
            $roiData = $this->calculatePositionROI($position);

            if ($roiData['amount'] <= 0) {
                DB::rollBack();
                return [
                    'success' => false,
                    'error' => 'ROI amount is zero or negative',
                ];
            }

            // Create distribution record
            $distribution = RoiDistribution::create([
                'staking_position_id' => $position->id,
                'user_id' => $position->user_id,
                'wallet_id' => $position->wallet_id,
                'roi_amount' => $roiData['amount'],
                'base_amount' => $roiData['base_amount'],
                'bonus_amount' => $roiData['bonus_amount'],
                'rank_bonus' => $roiData['rank_bonus'],
                'roi_rate' => $position->roi_rate,
                'distribution_type' => $position->roi_frequency,
                'distribution_date' => now()->toDateString(),
                'day_number' => $position->days_elapsed + 1,
                'total_days' => $position->term_days,
                'accumulated_roi' => $position->earned_roi + $roiData['amount'],
                'status' => 'pending',
            ]);

            // Process distribution
            $processResult = $this->processDistribution($distribution);

            if (!$processResult['success']) {
                DB::rollBack();
                return $processResult;
            }

            // Update position
            $position->addEarnedROI($roiData['amount']);

            // Check if position should mature
            if ($position->isMatured()) {
                $position->markAsMatured();
            }

            DB::commit();

            return [
                'success' => true,
                'amount' => $roiData['amount'],
                'distribution' => $distribution,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Position ROI distribution failed', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate ROI for a position
     */
    protected function calculatePositionROI(StakingPosition $position): array
    {
        $plan = $position->investmentPlan;

        // Calculate base ROI
        $baseAmount = $plan->calculateDailyROI($position->amount);

        // Apply rank bonus multiplier
        $rankBonus = $baseAmount * ($position->rank_bonus_multiplier - 1);

        // Calculate any additional bonuses
        $bonusAmount = 0; // Can be extended for special bonuses

        $totalAmount = $baseAmount + $rankBonus + $bonusAmount;

        return [
            'amount' => $totalAmount,
            'base_amount' => $baseAmount,
            'rank_bonus' => $rankBonus,
            'bonus_amount' => $bonusAmount,
        ];
    }

    /**
     * Process a distribution (deposit to wallet)
     */
    protected function processDistribution(RoiDistribution $distribution): array
    {
        try {
            $distribution->markAsProcessing();

            $position = $distribution->stakingPosition;
            $wallet = $distribution->wallet;

            // Check if should compound
            if ($position->auto_compound) {
                return $this->compoundROI($distribution);
            }

            // Deposit ROI to wallet
            $transaction = $this->walletService->deposit(
                $wallet,
                $distribution->roi_amount,
                'ROI จากการลงทุน #' . $position->position_number,
                RoiDistribution::class,
                $distribution->id,
                [
                    'distribution_type' => $distribution->distribution_type,
                    'position_number' => $position->position_number,
                    'day_number' => $distribution->day_number,
                ]
            );

            $distribution->markAsCompleted($transaction->id);

            return [
                'success' => true,
                'transaction' => $transaction,
            ];
        } catch (Exception $e) {
            $distribution->markAsFailed($e->getMessage());
            Log::error('Distribution processing failed', [
                'distribution_id' => $distribution->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Compound ROI (reinvest automatically)
     */
    protected function compoundROI(RoiDistribution $distribution): array
    {
        try {
            $position = $distribution->stakingPosition;
            $plan = $position->investmentPlan;

            // Check if plan allows compound
            if (!$plan->allow_compound) {
                return $this->processDistribution($distribution);
            }

            // Check if compound amount meets minimum
            $compoundAmount = $position->compound_amount + $distribution->roi_amount;

            if ($compoundAmount < $plan->min_amount) {
                // Accumulate for future compound
                $position->increment('compound_amount', $distribution->roi_amount);
                $distribution->update([
                    'is_compounded' => true,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                return [
                    'success' => true,
                    'compounded' => false,
                    'accumulated' => $compoundAmount,
                ];
            }

            // Create new position with compound amount
            $wallet = $this->walletService->getOrCreateWallet($position->user);

            // Deposit compound amount to wallet first
            $this->walletService->deposit(
                $wallet,
                $compoundAmount,
                'ROI สะสมสำหรับรีลงทุน',
                RoiDistribution::class,
                $distribution->id
            );

            // Create new investment
            $newInvestmentResult = $this->investmentService->createInvestment(
                $position->user,
                $plan,
                $compoundAmount,
                true // auto compound
            );

            if ($newInvestmentResult['success']) {
                $distribution->update([
                    'is_compounded' => true,
                    'new_position_id' => $newInvestmentResult['position']->id,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // Reset compound amount
                $position->update([
                    'compound_amount' => 0,
                    'compound_count' => $position->compound_count + 1,
                ]);

                return [
                    'success' => true,
                    'compounded' => true,
                    'new_position' => $newInvestmentResult['position'],
                ];
            }

            return $newInvestmentResult;
        } catch (Exception $e) {
            Log::error('Compound ROI failed', [
                'distribution_id' => $distribution->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to normal distribution
            return $this->processDistribution($distribution);
        }
    }

    /**
     * Process matured positions
     */
    public function processMaturePositions(): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            $positions = StakingPosition::shouldBeMature()->get();

            Log::info('Processing mature positions', [
                'count' => $positions->count(),
            ]);

            foreach ($positions as $position) {
                try {
                    $position->markAsMatured();
                    $results['success']++;
                } catch (Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'position_id' => $position->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return $results;
        } catch (Exception $e) {
            Log::error('Process mature positions failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retry failed distributions
     */
    public function retryFailedDistributions(int $maxRetries = 3): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            $failedDistributions = RoiDistribution::failed()
                ->where('retry_count', '<', $maxRetries)
                ->get();

            foreach ($failedDistributions as $distribution) {
                try {
                    DB::beginTransaction();

                    $distribution->incrementRetry();
                    $distribution->update(['status' => 'pending']);

                    $result = $this->processDistribution($distribution);

                    if ($result['success']) {
                        $results['success']++;
                        DB::commit();
                    } else {
                        $results['failed']++;
                        $results['errors'][] = [
                            'distribution_id' => $distribution->id,
                            'error' => $result['error'] ?? 'Unknown error',
                        ];
                        DB::rollBack();
                    }
                } catch (Exception $e) {
                    DB::rollBack();
                    $results['failed']++;
                    $results['errors'][] = [
                        'distribution_id' => $distribution->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return $results;
        } catch (Exception $e) {
            Log::error('Retry failed distributions error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get staking statistics
     */
    public function getStakingStatistics(): array
    {
        return [
            'total_active_positions' => StakingPosition::active()->count(),
            'total_staked_amount' => StakingPosition::whereIn('status', ['active', 'locked'])->sum('amount'),
            'total_roi_distributed' => RoiDistribution::completed()->sum('roi_amount'),
            'total_roi_pending' => RoiDistribution::pending()->sum('roi_amount'),
            'positions_matured_today' => StakingPosition::matured()
                ->whereDate('maturity_date', now()->toDateString())
                ->count(),
            'distributions_today' => RoiDistribution::today()->count(),
            'total_investors' => StakingPosition::distinct('user_id')->count('user_id'),
        ];
    }
}
