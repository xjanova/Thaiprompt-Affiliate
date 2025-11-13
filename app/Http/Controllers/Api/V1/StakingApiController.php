<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TPIXStakingPool;
use App\Models\TPIXStake;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * TPIX Staking API Controller
 * Handles all staking-related API endpoints
 */
class StakingApiController extends Controller
{
    /**
     * Get all active staking pools
     */
    public function getPools(Request $request): JsonResponse
    {
        try {
            $pools = TPIXStakingPool::with('token')
                ->where('status', 'active')
                ->orderBy('apy', 'desc')
                ->get()
                ->map(function ($pool) {
                    return [
                        'id' => $pool->id,
                        'token' => [
                            'id' => $pool->token->id,
                            'name' => $pool->token->name,
                            'symbol' => $pool->token->symbol,
                            'logo_url' => $pool->token->logo_url,
                        ],
                        'apy' => (float) $pool->apy,
                        'total_staked' => (float) $pool->total_staked,
                        'staker_count' => (int) $pool->staker_count,
                        'min_stake_amount' => (float) $pool->min_stake_amount,
                        'max_stake_amount' => $pool->max_stake_amount ? (float) $pool->max_stake_amount : null,
                        'early_withdrawal_penalty_percent' => (float) $pool->early_withdrawal_penalty_percent,
                        'status' => $pool->status,
                        'created_at' => $pool->created_at->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $pools,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch staking pools',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get staking pool details
     */
    public function getPool(Request $request, int $poolId): JsonResponse
    {
        try {
            $pool = TPIXStakingPool::with('token')->findOrFail($poolId);

            // Calculate additional statistics
            $totalRewardsPaid = TPIXStake::where('pool_id', $poolId)
                ->sum('rewards_earned');

            $avgStakePerUser = $pool->staker_count > 0
                ? $pool->total_staked / $pool->staker_count
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $pool->id,
                    'token' => [
                        'id' => $pool->token->id,
                        'name' => $pool->token->name,
                        'symbol' => $pool->token->symbol,
                        'logo_url' => $pool->token->logo_url,
                        'price_thb' => (float) $pool->token->price_thb ?? 0,
                    ],
                    'apy' => (float) $pool->apy,
                    'total_staked' => (float) $pool->total_staked,
                    'staker_count' => (int) $pool->staker_count,
                    'min_stake_amount' => (float) $pool->min_stake_amount,
                    'max_stake_amount' => $pool->max_stake_amount ? (float) $pool->max_stake_amount : null,
                    'early_withdrawal_penalty_percent' => (float) $pool->early_withdrawal_penalty_percent,
                    'lock_periods' => $this->getLockPeriods($pool),
                    'total_rewards_paid' => (float) $totalRewardsPaid,
                    'avg_stake_per_user' => (float) $avgStakePerUser,
                    'status' => $pool->status,
                    'description' => $pool->description,
                    'created_at' => $pool->created_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pool details',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get user's stake in a specific pool
     */
    public function getMyStake(Request $request, int $poolId): JsonResponse
    {
        try {
            $stake = auth()->user()
                ->stakes()
                ->with('pool.token')
                ->where('pool_id', $poolId)
                ->where('status', 'active')
                ->first();

            if (!$stake) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                ]);
            }

            // Calculate pending rewards
            $pendingRewards = $this->calculatePendingRewards($stake);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $stake->id,
                    'staked_amount' => (float) $stake->staked_amount,
                    'staked_value_thb' => (float) ($stake->staked_amount * ($stake->pool->token->price_thb ?? 0)),
                    'lock_period_days' => (int) $stake->lock_period_days,
                    'apy' => (float) $stake->apy,
                    'unlock_date' => $stake->unlock_date ? $stake->unlock_date->toISOString() : null,
                    'rewards_earned' => (float) $stake->rewards_earned,
                    'pending_rewards' => (float) $pendingRewards,
                    'status' => $stake->status,
                    'created_at' => $stake->created_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stake details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stake tokens
     */
    public function stake(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pool_id' => 'required|exists:tpix_staking_pools,id',
            'amount' => 'required|numeric|min:0.00000001',
            'lock_period_days' => 'required|integer|in:0,7,30,90,180,365',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $pool = TPIXStakingPool::with('token')->findOrFail($request->pool_id);
            $user = auth()->user();

            // Validate amount
            if ($request->amount < $pool->min_stake_amount) {
                throw new \Exception("Minimum stake amount is {$pool->min_stake_amount} {$pool->token->symbol}");
            }

            if ($pool->max_stake_amount && $request->amount > $pool->max_stake_amount) {
                throw new \Exception("Maximum stake amount is {$pool->max_stake_amount} {$pool->token->symbol}");
            }

            // Check user balance
            $balance = $user->tokenBalances()
                ->where('token_id', $pool->token_id)
                ->first();

            if (!$balance || $balance->amount < $request->amount) {
                throw new \Exception('Insufficient balance');
            }

            // Calculate APY based on lock period
            $apy = $this->calculateAPY($pool->apy, $request->lock_period_days);

            // Calculate unlock date
            $unlockDate = $request->lock_period_days > 0
                ? now()->addDays($request->lock_period_days)
                : null;

            // Create stake
            $stake = TPIXStake::create([
                'user_id' => $user->id,
                'pool_id' => $pool->id,
                'staked_amount' => $request->amount,
                'lock_period_days' => $request->lock_period_days,
                'apy' => $apy,
                'unlock_date' => $unlockDate,
                'status' => 'active',
                'rewards_earned' => 0,
            ]);

            // Deduct from user balance
            $balance->decrement('amount', $request->amount);

            // Update pool statistics
            $pool->increment('total_staked', $request->amount);
            $pool->increment('staker_count');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Staking successful',
                'data' => [
                    'stake_id' => $stake->id,
                    'amount' => (float) $stake->staked_amount,
                    'apy' => (float) $stake->apy,
                    'unlock_date' => $stake->unlock_date ? $stake->unlock_date->toISOString() : null,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Staking failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Unstake tokens
     */
    public function unstake(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pool_id' => 'required|exists:tpix_staking_pools,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $stake = auth()->user()
                ->stakes()
                ->with('pool.token')
                ->where('pool_id', $request->pool_id)
                ->where('status', 'active')
                ->firstOrFail();

            // Calculate penalty if unstaking early
            $penalty = 0;
            $amountToReturn = $stake->staked_amount;

            if ($stake->unlock_date && now()->lt($stake->unlock_date)) {
                $penalty = bcmul(
                    $stake->staked_amount,
                    bcdiv($stake->pool->early_withdrawal_penalty_percent, '100', 8),
                    8
                );
                $amountToReturn = bcsub($stake->staked_amount, $penalty, 8);
            }

            // Calculate and add pending rewards
            $pendingRewards = $this->calculatePendingRewards($stake);
            $stake->rewards_earned = bcadd($stake->rewards_earned, $pendingRewards, 8);

            // Return tokens to user
            $balance = auth()->user()->tokenBalances()
                ->where('token_id', $stake->pool->token_id)
                ->first();

            if ($balance) {
                $balance->increment('amount', bcadd($amountToReturn, $stake->rewards_earned, 8));
            } else {
                auth()->user()->tokenBalances()->create([
                    'token_id' => $stake->pool->token_id,
                    'amount' => bcadd($amountToReturn, $stake->rewards_earned, 8),
                ]);
            }

            // Update pool statistics
            $stake->pool->decrement('total_staked', $stake->staked_amount);
            $stake->pool->decrement('staker_count');

            // Update stake status
            $stake->update([
                'status' => 'withdrawn',
                'unstaked_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Unstaking successful',
                'data' => [
                    'amount_returned' => (float) $amountToReturn,
                    'rewards' => (float) $stake->rewards_earned,
                    'penalty' => (float) $penalty,
                    'total_received' => (float) bcadd($amountToReturn, $stake->rewards_earned, 8),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Unstaking failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Claim rewards
     */
    public function claim(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pool_id' => 'required|exists:tpix_staking_pools,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $stake = auth()->user()
                ->stakes()
                ->with('pool.token')
                ->where('pool_id', $request->pool_id)
                ->where('status', 'active')
                ->firstOrFail();

            // Calculate pending rewards
            $pendingRewards = $this->calculatePendingRewards($stake);

            if (bccomp($pendingRewards, '0', 8) <= 0) {
                throw new \Exception('No rewards to claim');
            }

            // Add rewards to user balance
            $balance = auth()->user()->tokenBalances()
                ->where('token_id', $stake->pool->token_id)
                ->first();

            if ($balance) {
                $balance->increment('amount', $pendingRewards);
            } else {
                auth()->user()->tokenBalances()->create([
                    'token_id' => $stake->pool->token_id,
                    'amount' => $pendingRewards,
                ]);
            }

            // Update stake
            $stake->rewards_earned = bcadd($stake->rewards_earned, $pendingRewards, 8);
            $stake->last_reward_claim_at = now();
            $stake->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rewards claimed successfully',
                'data' => [
                    'claimed_amount' => (float) $pendingRewards,
                    'total_rewards_earned' => (float) $stake->rewards_earned,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Claiming failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get staking history
     */
    public function getHistory(Request $request): JsonResponse
    {
        try {
            $stakes = auth()->user()
                ->stakes()
                ->with('pool.token')
                ->latest()
                ->get()
                ->map(function ($stake) {
                    return [
                        'id' => $stake->id,
                        'type' => $stake->status === 'active' ? 'stake' : 'unstake',
                        'pool' => [
                            'id' => $stake->pool->id,
                            'token' => [
                                'id' => $stake->pool->token->id,
                                'name' => $stake->pool->token->name,
                                'symbol' => $stake->pool->token->symbol,
                                'logo_url' => $stake->pool->token->logo_url,
                            ],
                        ],
                        'amount' => (float) $stake->staked_amount,
                        'amount_thb' => (float) ($stake->staked_amount * ($stake->pool->token->price_thb ?? 0)),
                        'lock_period_days' => (int) $stake->lock_period_days,
                        'apy' => (float) $stake->apy,
                        'rewards_earned' => (float) $stake->rewards_earned,
                        'status' => $stake->status,
                        'created_at' => $stake->created_at->toISOString(),
                        'unlock_date' => $stake->unlock_date ? $stake->unlock_date->toISOString() : null,
                        'unstaked_at' => $stake->unstaked_at ? $stake->unstaked_at->toISOString() : null,
                    ];
                });

            // Calculate summary
            $summary = [
                'total_staked_thb' => $stakes->where('status', 'active')->sum('amount_thb'),
                'total_staked_count' => $stakes->where('status', 'active')->count(),
                'total_rewards_thb' => $stakes->sum(fn($s) => $s['rewards_earned'] * ($s['pool']['token']['price_thb'] ?? 0)),
                'total_rewards_tokens' => $stakes->sum('rewards_earned'),
                'active_stakes' => $stakes->where('status', 'active')->count(),
                'active_pools' => $stakes->where('status', 'active')->pluck('pool.id')->unique()->count(),
                'avg_apy' => $stakes->where('status', 'active')->avg('apy') ?? 0,
                'avg_duration_days' => $stakes->where('status', 'active')->avg('lock_period_days') ?? 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $stakes,
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent stakes in a pool
     */
    public function getRecentStakes(Request $request, int $poolId): JsonResponse
    {
        try {
            $stakes = TPIXStake::with('user')
                ->where('pool_id', $poolId)
                ->latest()
                ->limit(20)
                ->get()
                ->map(function ($stake) {
                    return [
                        'user' => [
                            'id' => $stake->user->id,
                            'username' => $stake->user->username ?? 'User #' . $stake->user->id,
                        ],
                        'type' => $stake->status === 'active' ? 'stake' : 'unstake',
                        'amount' => (float) $stake->staked_amount,
                        'created_at' => $stake->created_at->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $stakes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent stakes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Get lock periods with APY
     */
    private function getLockPeriods($pool): array
    {
        return [
            ['days' => 0, 'apy' => (float) ($pool->apy * 0.5), 'label' => 'Flexible'],
            ['days' => 7, 'apy' => (float) ($pool->apy * 0.7), 'label' => '7 Days'],
            ['days' => 30, 'apy' => (float) ($pool->apy * 0.9), 'label' => '30 Days'],
            ['days' => 90, 'apy' => (float) $pool->apy, 'label' => '90 Days'],
            ['days' => 180, 'apy' => (float) ($pool->apy * 1.2), 'label' => '180 Days'],
            ['days' => 365, 'apy' => (float) ($pool->apy * 1.5), 'label' => '365 Days'],
        ];
    }

    /**
     * Helper: Calculate APY based on lock period
     */
    private function calculateAPY(float $baseAPY, int $lockDays): float
    {
        return match($lockDays) {
            0 => $baseAPY * 0.5,
            7 => $baseAPY * 0.7,
            30 => $baseAPY * 0.9,
            90 => $baseAPY,
            180 => $baseAPY * 1.2,
            365 => $baseAPY * 1.5,
            default => $baseAPY,
        };
    }

    /**
     * Helper: Calculate pending rewards
     */
    private function calculatePendingRewards($stake): string
    {
        $lastClaim = $stake->last_reward_claim_at ?? $stake->created_at;
        $now = now();

        // Calculate time elapsed in years
        $secondsElapsed = $now->diffInSeconds($lastClaim);
        $yearsElapsed = bcdiv((string)$secondsElapsed, '31536000', 18); // 365 * 24 * 60 * 60

        // Calculate rewards: staked_amount * APY * time_elapsed
        $apyDecimal = bcdiv($stake->apy, '100', 18);
        $rewards = bcmul(
            bcmul($stake->staked_amount, $apyDecimal, 18),
            $yearsElapsed,
            8
        );

        return $rewards;
    }
}
