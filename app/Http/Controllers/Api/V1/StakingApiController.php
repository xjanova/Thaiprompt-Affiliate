<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TPIXStake;
use App\Models\TPIXStakingPool;
use App\Models\TPIXTokenBalance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

            if (! $stake) {
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
     * Stake เหรียญเข้าพูล
     *
     * ใช้ชื่อคอลัมน์จริงของตาราง (tpix_token_balances.available_balance, tpix_stakes.amount/unlock_at/token_id,
     * tpix_staking_pools.current_stakers) — เดิมอ้าง amount/staked_amount/apy/lock_period_days ที่ไม่มีอยู่จริง
     * จึงล้มเหลวทุกครั้ง
     * - APY และระยะล็อกยึดตามพูล (ตาราง tpix_stakes ไม่มีคอลัมน์เก็บ APY/ระยะล็อกรายรายการ)
     * - รับเฉพาะพูลสถานะ active ที่อยู่ในช่วงเวลา และยังไม่เต็มเพดาน/จำนวนผู้ Stake
     * - มี Stake ที่ยังเปิดอยู่ในพูลเดิม = Stake เพิ่ม (สะสมรางวัลถึงตอนนี้ก่อน แล้วเริ่มนับระยะล็อกใหม่)
     */
    public function stake(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pool_id' => 'required|integer|exists:tpix_staking_pools,id',
            'amount' => 'required|numeric|min:0.00000001',
            'lock_period_days' => 'nullable|integer|min:0',
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
            $user = $request->user();
            $amount = $this->normalizeAmount((string) $request->amount);

            // ล็อกแถวพูลกันการ Stake พร้อมกันจนเกินเพดาน
            $pool = TPIXStakingPool::with('token')->whereKey($request->pool_id)->lockForUpdate()->firstOrFail();
            $symbol = $pool->token->symbol ?? '';

            $this->assertPoolOpen($pool);

            $lockDays = (int) $pool->lock_period_days;
            if ($request->filled('lock_period_days') && (int) $request->lock_period_days !== $lockDays) {
                throw new \DomainException("พูลนี้กำหนดระยะล็อก {$lockDays} วัน");
            }

            if ($pool->min_stake_amount !== null && bccomp($amount, (string) $pool->min_stake_amount, 8) < 0) {
                throw new \DomainException('ต่ำกว่าจำนวน Stake ขั้นต่ำ '.$this->plain($pool->min_stake_amount).' '.$symbol);
            }

            $existing = TPIXStake::where('user_id', $user->id)
                ->where('pool_id', $pool->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            $newTotal = $existing ? bcadd((string) $existing->amount, $amount, 8) : $amount;
            if ($pool->max_stake_amount !== null && bccomp($newTotal, (string) $pool->max_stake_amount, 8) > 0) {
                throw new \DomainException('เกินจำนวน Stake สูงสุดต่อคน '.$this->plain($pool->max_stake_amount).' '.$symbol);
            }

            if ($pool->pool_cap !== null && bccomp(bcadd((string) $pool->total_staked, $amount, 8), (string) $pool->pool_cap, 8) > 0) {
                throw new \DomainException('พูลนี้ใกล้เต็มเพดานแล้ว กรุณาลดจำนวนลง');
            }

            if (! $existing && $pool->max_stakers && (int) $pool->current_stakers >= (int) $pool->max_stakers) {
                throw new \DomainException('พูลนี้มีผู้ Stake ครบจำนวนแล้ว');
            }

            // ตรวจและย้ายยอด: available → staked (balance รวมยังเท่าเดิม)
            $balance = TPIXTokenBalance::where('user_id', $user->id)
                ->where('token_id', $pool->token_id)
                ->lockForUpdate()
                ->first();

            if (! $balance || $balance->is_frozen || bccomp((string) $balance->available_balance, $amount, 8) < 0) {
                throw new \DomainException('ยอดคงเหลือไม่พอ');
            }

            $balance->forceFill([
                'available_balance' => bcsub((string) $balance->available_balance, $amount, 8),
                'staked_balance' => bcadd((string) $balance->staked_balance, $amount, 8),
                'last_activity_at' => now(),
            ])->save();

            $unlockAt = $lockDays > 0 ? now()->addDays($lockDays) : null;

            if ($existing) {
                $this->accrueRewards($existing, $pool);
                $existing->forceFill([
                    'amount' => $newTotal,
                    'unlock_at' => $unlockAt,
                ])->save();
                $stake = $existing;
            } else {
                $stake = new TPIXStake;
                $stake->forceFill([
                    'user_id' => $user->id,
                    'pool_id' => $pool->id,
                    'token_id' => $pool->token_id,
                    'amount' => $amount,
                    'rewards_earned' => '0',
                    'rewards_claimed' => '0',
                    'staked_at' => now(),
                    'unlock_at' => $unlockAt,
                    'last_reward_claim_at' => now(),
                    'status' => 'active',
                    'stake_tx_hash' => '0x'.bin2hex(random_bytes(32)),
                ])->save();
            }

            $pool->forceFill([
                'total_staked' => bcadd((string) $pool->total_staked, $amount, 8),
                'current_stakers' => (int) $pool->current_stakers + ($existing ? 0 : 1),
            ])->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Staking successful',
                'data' => [
                    'stake_id' => $stake->id,
                    'amount' => (string) $stake->amount,
                    'apy' => (float) $pool->apy,
                    'lock_period_days' => $lockDays,
                    'unlock_date' => $unlockAt?->toISOString(),
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->failure($e, 'Staking failed');
        }
    }

    /**
     * ถอน Stake พร้อมรางวัลที่ยังไม่ได้รับ
     *
     * พูลไม่มีการตั้งค่าค่าปรับถอนก่อนกำหนด จึงถอนได้เมื่อพ้นช่วงล็อกแล้วเท่านั้น
     */
    public function unstake(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pool_id' => 'required|integer|exists:tpix_staking_pools,id',
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
            $user = $request->user();
            $pool = TPIXStakingPool::whereKey($request->pool_id)->lockForUpdate()->firstOrFail();
            $stake = $this->activeStake($user->id, $pool->id);

            if ($stake->unlock_at && $stake->unlock_at->isFuture()) {
                throw new \DomainException('ยังอยู่ในช่วงล็อก ถอนได้หลังวันปลดล็อก');
            }

            $this->accrueRewards($stake, $pool);
            $amount = (string) $stake->amount;
            $rewards = bcsub((string) $stake->rewards_earned, (string) $stake->rewards_claimed, 8);
            if (bccomp($rewards, '0', 8) < 0) {
                $rewards = '0';
            }

            // คืนเหรียญ: staked → available และบวกรางวัลเข้ายอด
            $balance = TPIXTokenBalance::lockOrCreateFor($user->id, (int) $pool->token_id);
            $staked = bcsub((string) $balance->staked_balance, $amount, 8);
            $balance->forceFill([
                'balance' => bcadd((string) $balance->balance, $rewards, 8),
                'available_balance' => bcadd(bcadd((string) $balance->available_balance, $amount, 8), $rewards, 8),
                'staked_balance' => bccomp($staked, '0', 8) < 0 ? '0' : $staked,
                'last_activity_at' => now(),
            ])->save();

            $stake->forceFill([
                'rewards_claimed' => bcadd((string) $stake->rewards_claimed, $rewards, 8),
                'status' => 'unstaked',
                'unstaked_at' => now(),
                'unstake_tx_hash' => '0x'.bin2hex(random_bytes(32)),
            ])->save();

            $totalStaked = bcsub((string) $pool->total_staked, $amount, 8);
            $pool->forceFill([
                'total_staked' => bccomp($totalStaked, '0', 8) < 0 ? '0' : $totalStaked,
                'current_stakers' => max(0, (int) $pool->current_stakers - 1),
                'total_rewards_distributed' => bcadd((string) $pool->total_rewards_distributed, $rewards, 8),
            ])->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Unstaking successful',
                'data' => [
                    'amount_returned' => $amount,
                    'rewards' => $rewards,
                    'penalty' => '0',
                    'total_received' => bcadd($amount, $rewards, 8),
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->failure($e, 'Unstaking failed');
        }
    }

    /**
     * รับรางวัล Stake ที่สะสมถึงตอนนี้
     */
    public function claim(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pool_id' => 'required|integer|exists:tpix_staking_pools,id',
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
            $user = $request->user();
            $pool = TPIXStakingPool::whereKey($request->pool_id)->lockForUpdate()->firstOrFail();
            $stake = $this->activeStake($user->id, $pool->id);

            $this->accrueRewards($stake, $pool);
            $claimable = bcsub((string) $stake->rewards_earned, (string) $stake->rewards_claimed, 8);

            if (bccomp($claimable, '0', 8) <= 0) {
                throw new \DomainException('ยังไม่มีรางวัลให้รับ');
            }

            $balance = TPIXTokenBalance::lockOrCreateFor($user->id, (int) $pool->token_id);
            $balance->forceFill([
                'balance' => bcadd((string) $balance->balance, $claimable, 8),
                'available_balance' => bcadd((string) $balance->available_balance, $claimable, 8),
                'last_activity_at' => now(),
            ])->save();

            $stake->forceFill([
                'rewards_claimed' => bcadd((string) $stake->rewards_claimed, $claimable, 8),
            ])->save();

            $pool->forceFill([
                'total_rewards_distributed' => bcadd((string) $pool->total_rewards_distributed, $claimable, 8),
            ])->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rewards claimed successfully',
                'data' => [
                    'claimed_amount' => $claimable,
                    'total_rewards_earned' => (string) $stake->rewards_earned,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->failure($e, 'Claiming failed');
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
                'total_rewards_thb' => $stakes->sum(fn ($s) => $s['rewards_earned'] * ($s['pool']['token']['price_thb'] ?? 0)),
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
                            'username' => $stake->user->username ?? 'User #'.$stake->user->id,
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
     * ระยะล็อก/APY ของพูล — พูลกำหนดระยะล็อกเดียว (ไม่มีตัวคูณตามระยะล็อกแล้ว)
     */
    private function getLockPeriods($pool): array
    {
        $days = (int) $pool->lock_period_days;

        return [
            ['days' => $days, 'apy' => (float) $pool->apy, 'label' => $days > 0 ? $days.' Days' : 'Flexible'],
        ];
    }

    /**
     * รางวัลที่ยังไม่ได้สะสมเข้า rewards_earned
     * = amount × APY ของพูล × เวลาที่ผ่านไปตั้งแต่จุดสะสมล่าสุด (last_reward_claim_at) จนถึงตอนนี้หรือวันสิ้นสุดพูล
     */
    private function calculatePendingRewards(TPIXStake $stake, ?TPIXStakingPool $pool = null): string
    {
        $pool ??= $stake->pool;
        if (! $pool) {
            return '0';
        }

        $from = $stake->last_reward_claim_at ?? $stake->staked_at ?? $stake->created_at;
        $until = now();
        if ($pool->ends_at && $pool->ends_at->lt($until)) {
            $until = $pool->ends_at;
        }
        if (! $from || $from->gte($until)) {
            return '0';
        }

        $seconds = (string) abs((int) $from->diffInSeconds($until));
        $years = bcdiv($seconds, '31536000', 18); // 365 × 24 × 60 × 60
        $apy = bcdiv((string) $pool->apy, '100', 18);

        return bcmul(bcmul((string) $stake->amount, $apy, 18), $years, 8);
    }

    /**
     * สะสมรางวัลถึงตอนนี้เข้า rewards_earned แล้วเลื่อนจุดสะสม (last_reward_claim_at) มาเป็นตอนนี้
     */
    private function accrueRewards(TPIXStake $stake, TPIXStakingPool $pool): void
    {
        $pending = $this->calculatePendingRewards($stake, $pool);

        $stake->forceFill([
            'rewards_earned' => bcadd((string) $stake->rewards_earned, $pending, 8),
            'last_reward_claim_at' => now(),
        ])->save();
    }

    /**
     * Stake ที่ยังเปิดอยู่ของสมาชิกในพูล (ล็อกแถว)
     */
    private function activeStake(int $userId, int $poolId): TPIXStake
    {
        $stake = TPIXStake::where('user_id', $userId)
            ->where('pool_id', $poolId)
            ->where('status', 'active')
            ->lockForUpdate()
            ->first();

        if (! $stake) {
            throw new \DomainException('ไม่พบ Stake ที่ยังเปิดอยู่ในพูลนี้');
        }

        return $stake;
    }

    /**
     * พูลต้องเปิดรับ Stake อยู่: สถานะ active และอยู่ในช่วง starts_at – ends_at
     * (เดิมรับ Stake พูล draft/paused/ended ได้ เพราะตรวจแค่ว่ามี id อยู่จริง)
     */
    private function assertPoolOpen(TPIXStakingPool $pool): void
    {
        if ($pool->status !== 'active' || ! $pool->token) {
            throw new \DomainException('พูลนี้ไม่ได้เปิดรับ Stake ในตอนนี้');
        }
        if ($pool->starts_at && $pool->starts_at->isFuture()) {
            throw new \DomainException('พูลนี้ยังไม่เปิดรับ Stake');
        }
        if ($pool->ends_at && $pool->ends_at->isPast()) {
            throw new \DomainException('พูลนี้ปิดรับ Stake แล้ว');
        }
    }

    /**
     * แปลงจำนวนเป็นสตริงทศนิยมสำหรับ bcmath (กัน 1e3 / ค่าติดลบ)
     */
    private function normalizeAmount(string $amount): string
    {
        $amount = trim($amount);
        if (! preg_match('/^\d+(\.\d+)?$/', $amount)) {
            if (! is_numeric($amount) || (float) $amount <= 0) {
                throw new \DomainException('จำนวนไม่ถูกต้อง');
            }
            $amount = number_format((float) $amount, 8, '.', '');
        }

        $amount = bcadd($amount, '0', 8);
        if (bccomp($amount, '0', 8) <= 0) {
            throw new \DomainException('จำนวนไม่ถูกต้อง');
        }

        return $amount;
    }

    /**
     * ตัวเลขสำหรับข้อความ (ตัดศูนย์ท้าย)
     */
    private function plain($value): string
    {
        $s = bcadd((string) $value, '0', 8);

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    }

    /**
     * ตอบข้อผิดพลาดของการทำรายการ
     * - \DomainException = เงื่อนไขทางธุรกิจ ข้อความภาษาไทยส่งให้สมาชิกเห็นได้
     * - อื่นๆ บันทึก log แล้วตอบข้อความกลาง ไม่ส่งข้อความ exception ดิบ (SQL / ชื่อคอลัมน์) ออกไป
     */
    private function failure(\Throwable $e, string $fallback): JsonResponse
    {
        if ($e instanceof \DomainException) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 400);
        }

        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบพูล Stake',
                'error' => 'Not found',
            ], 404);
        }

        Log::error('Staking action failed: '.$fallback, [
            'user_id' => auth()->id(),
            'exception' => $e,
        ]);

        return response()->json([
            'success' => false,
            'message' => $fallback,
            'error' => 'Request failed',
        ], 500);
    }
}
