<?php

namespace App\Services\TPIX;

use App\Models\TPIXToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Decentralized Exchange (DEX) Service
 * Token Swap with Automated Market Maker (AMM)
 * Constant Product Formula: x * y = k
 */
class DEXService
{
    /**
     * Swap Token A for Token B
     * Uses constant product AMM formula
     */
    public function swap(
        User $user,
        TPIXToken $tokenIn,
        TPIXToken $tokenOut,
        string $amountIn,
        string $minAmountOut,
        float $slippageTolerance = 0.5
    ): array {
        DB::beginTransaction();

        try {
            // Get liquidity pool
            $pool = $this->getOrCreatePool($tokenIn, $tokenOut);

            // Calculate output amount using AMM formula
            $amountOut = $this->calculateOutputAmount(
                $amountIn,
                $pool['reserve_in'],
                $pool['reserve_out']
            );

            // Check slippage
            if (bccomp($amountOut, $minAmountOut, 8) < 0) {
                throw new \Exception('Slippage tolerance exceeded');
            }

            // Calculate price impact
            $priceImpact = $this->calculatePriceImpact(
                $amountIn,
                $pool['reserve_in'],
                $pool['reserve_out']
            );

            // Platform fee (0.3%)
            $fee = bcmul($amountOut, '0.003', 8);
            $amountOutAfterFee = bcsub($amountOut, $fee, 8);

            // Check user balance
            $userBalance = \App\Models\TPIXTokenBalance::where('user_id', $user->id)
                ->where('token_id', $tokenIn->id)
                ->first();

            if (!$userBalance || bccomp($userBalance->available_balance, $amountIn, 8) < 0) {
                throw new \Exception('Insufficient balance');
            }

            // Execute swap
            // 1. Deduct tokenIn from user
            $userBalance->decrement('balance', $amountIn);
            $userBalance->decrement('available_balance', $amountIn);

            // 2. Add tokenOut to user
            $userBalanceOut = \App\Models\TPIXTokenBalance::firstOrCreate(
                ['user_id' => $user->id, 'token_id' => $tokenOut->id],
                ['balance' => 0, 'available_balance' => 0]
            );

            $userBalanceOut->increment('balance', $amountOutAfterFee);
            $userBalanceOut->increment('available_balance', $amountOutAfterFee);

            // 3. Update pool reserves
            $this->updatePoolReserves(
                $pool['id'],
                bcadd($pool['reserve_in'], $amountIn, 8),
                bcsub($pool['reserve_out'], $amountOut, 8)
            );

            // 4. Add fee to LP providers
            $this->distributeFeeToLPs($pool['id'], $fee);

            // 5. Record swap transaction
            $swap = \App\Models\TPIXSwap::create([
                'user_id' => $user->id,
                'pool_id' => $pool['id'],
                'token_in_id' => $tokenIn->id,
                'token_out_id' => $tokenOut->id,
                'amount_in' => $amountIn,
                'amount_out' => $amountOutAfterFee,
                'fee' => $fee,
                'price_impact' => $priceImpact,
                'tx_hash' => '0x' . bin2hex(random_bytes(32)),
            ]);

            DB::commit();

            Log::info("Swap executed", [
                'user_id' => $user->id,
                'token_in' => $tokenIn->symbol,
                'token_out' => $tokenOut->symbol,
                'amount_in' => $amountIn,
                'amount_out' => $amountOutAfterFee,
            ]);

            return [
                'success' => true,
                'swap_id' => $swap->id,
                'amount_in' => $amountIn,
                'amount_out' => $amountOutAfterFee,
                'fee' => $fee,
                'price_impact' => $priceImpact,
                'tx_hash' => $swap->tx_hash,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Swap failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate output amount using constant product formula
     * amountOut = (reserveOut * amountIn) / (reserveIn + amountIn)
     */
    protected function calculateOutputAmount(
        string $amountIn,
        string $reserveIn,
        string $reserveOut
    ): string {
        // Apply 0.3% fee
        $amountInWithFee = bcmul($amountIn, '997', 8); // 99.7%

        $numerator = bcmul($amountInWithFee, $reserveOut, 8);
        $denominator = bcadd(bcmul($reserveIn, '1000', 8), $amountInWithFee, 8);

        return bcdiv($numerator, $denominator, 8);
    }

    /**
     * Calculate price impact
     */
    protected function calculatePriceImpact(
        string $amountIn,
        string $reserveIn,
        string $reserveOut
    ): float {
        // Price before = reserveOut / reserveIn
        $priceBefore = bcdiv($reserveOut, $reserveIn, 8);

        // Price after
        $newReserveIn = bcadd($reserveIn, $amountIn, 8);
        $amountOut = $this->calculateOutputAmount($amountIn, $reserveIn, $reserveOut);
        $newReserveOut = bcsub($reserveOut, $amountOut, 8);
        $priceAfter = bcdiv($newReserveOut, $newReserveIn, 8);

        // Impact = (priceAfter - priceBefore) / priceBefore * 100
        $impact = bcdiv(
            bcmul(bcsub($priceAfter, $priceBefore, 8), '100', 8),
            $priceBefore,
            2
        );

        return (float) $impact;
    }

    /**
     * Add liquidity to pool
     */
    public function addLiquidity(
        User $user,
        TPIXToken $tokenA,
        TPIXToken $tokenB,
        string $amountA,
        string $amountB
    ): array {
        DB::beginTransaction();

        try {
            $pool = $this->getOrCreatePool($tokenA, $tokenB);

            // Calculate LP tokens to mint
            if ($pool['total_liquidity'] === '0') {
                // Initial liquidity
                $lpTokens = bcsqrt(bcmul($amountA, $amountB, 8), 8);
            } else {
                // Proportional liquidity
                $lpTokensA = bcdiv(
                    bcmul($amountA, $pool['total_liquidity'], 8),
                    $pool['reserve_a'],
                    8
                );
                $lpTokensB = bcdiv(
                    bcmul($amountB, $pool['total_liquidity'], 8),
                    $pool['reserve_b'],
                    8
                );
                $lpTokens = min($lpTokensA, $lpTokensB);
            }

            // Deduct tokens from user
            $this->deductUserBalance($user->id, $tokenA->id, $amountA);
            $this->deductUserBalance($user->id, $tokenB->id, $amountB);

            // Update pool reserves
            $this->updatePoolReserves(
                $pool['id'],
                bcadd($pool['reserve_a'], $amountA, 8),
                bcadd($pool['reserve_b'], $amountB, 8)
            );

            // Mint LP tokens to user
            $lpPosition = \App\Models\TPIXLiquidityPosition::create([
                'user_id' => $user->id,
                'pool_id' => $pool['id'],
                'token_a_amount' => $amountA,
                'token_b_amount' => $amountB,
                'lp_tokens' => $lpTokens,
            ]);

            DB::commit();

            return [
                'success' => true,
                'lp_tokens' => $lpTokens,
                'position_id' => $lpPosition->id,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove liquidity from pool
     */
    public function removeLiquidity(
        User $user,
        int $positionId,
        string $lpTokensToRemove
    ): array {
        DB::beginTransaction();

        try {
            $position = \App\Models\TPIXLiquidityPosition::where('id', $positionId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (bccomp($lpTokensToRemove, $position->lp_tokens, 8) > 0) {
                throw new \Exception('Insufficient LP tokens');
            }

            $pool = $this->getPool($position->pool_id);

            // Calculate tokens to return
            $percentage = bcdiv($lpTokensToRemove, $pool['total_liquidity'], 8);
            $amountA = bcmul($pool['reserve_a'], $percentage, 8);
            $amountB = bcmul($pool['reserve_b'], $percentage, 8);

            // Update pool reserves
            $this->updatePoolReserves(
                $pool['id'],
                bcsub($pool['reserve_a'], $amountA, 8),
                bcsub($pool['reserve_b'], $amountB, 8)
            );

            // Return tokens to user
            $this->addUserBalance($user->id, $pool['token_a_id'], $amountA);
            $this->addUserBalance($user->id, $pool['token_b_id'], $amountB);

            // Burn LP tokens
            $position->decrement('lp_tokens', $lpTokensToRemove);

            if (bccomp($position->fresh()->lp_tokens, '0', 8) === 0) {
                $position->delete();
            }

            DB::commit();

            return [
                'success' => true,
                'amount_a' => $amountA,
                'amount_b' => $amountB,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get or create liquidity pool
     */
    protected function getOrCreatePool(TPIXToken $tokenA, TPIXToken $tokenB): array
    {
        // Ensure consistent ordering
        if ($tokenA->id > $tokenB->id) {
            [$tokenA, $tokenB] = [$tokenB, $tokenA];
        }

        $pool = \App\Models\TPIXLiquidityPool::firstOrCreate(
            [
                'token_a_id' => $tokenA->id,
                'token_b_id' => $tokenB->id,
            ],
            [
                'reserve_a' => '0',
                'reserve_b' => '0',
                'total_liquidity' => '0',
                'fee_percentage' => '0.3',
            ]
        );

        return $pool->toArray();
    }

    /**
     * Helper methods
     */
    protected function getPool(int $poolId): array
    {
        return \App\Models\TPIXLiquidityPool::findOrFail($poolId)->toArray();
    }

    protected function updatePoolReserves(int $poolId, string $reserveA, string $reserveB): void
    {
        \App\Models\TPIXLiquidityPool::where('id', $poolId)->update([
            'reserve_a' => $reserveA,
            'reserve_b' => $reserveB,
            'updated_at' => now(),
        ]);
    }

    protected function deductUserBalance(int $userId, int $tokenId, string $amount): void
    {
        $balance = \App\Models\TPIXTokenBalance::where('user_id', $userId)
            ->where('token_id', $tokenId)
            ->lockForUpdate()
            ->firstOrFail();

        $balance->decrement('balance', $amount);
        $balance->decrement('available_balance', $amount);
    }

    protected function addUserBalance(int $userId, int $tokenId, string $amount): void
    {
        $balance = \App\Models\TPIXTokenBalance::firstOrCreate(
            ['user_id' => $userId, 'token_id' => $tokenId],
            ['balance' => '0', 'available_balance' => '0']
        );

        $balance->increment('balance', $amount);
        $balance->increment('available_balance', $amount);
    }

    protected function distributeFeeToLPs(int $poolId, string $fee): void
    {
        // Distribute fee proportionally to LP token holders
        // This is a simplified version - implement full distribution logic
        Log::info("Distributing fee to LPs", ['pool_id' => $poolId, 'fee' => $fee]);
    }

    /**
     * Get quote for swap
     */
    public function getQuote(TPIXToken $tokenIn, TPIXToken $tokenOut, string $amountIn): array
    {
        $pool = $this->getOrCreatePool($tokenIn, $tokenOut);

        if ($pool['reserve_in'] === '0' || $pool['reserve_out'] === '0') {
            return [
                'amount_out' => '0',
                'price_impact' => 0,
                'error' => 'No liquidity',
            ];
        }

        $amountOut = $this->calculateOutputAmount(
            $amountIn,
            $pool['reserve_in'],
            $pool['reserve_out']
        );

        $priceImpact = $this->calculatePriceImpact(
            $amountIn,
            $pool['reserve_in'],
            $pool['reserve_out']
        );

        $fee = bcmul($amountOut, '0.003', 8);

        return [
            'amount_out' => bcsub($amountOut, $fee, 8),
            'price_impact' => $priceImpact,
            'fee' => $fee,
            'price' => bcdiv($amountOut, $amountIn, 8),
        ];
    }

    /**
     * Square root function for BC Math
     */
    protected function bcsqrt(string $number, int $scale = 8): string
    {
        $guess = bcdiv($number, '2', $scale);

        for ($i = 0; $i < 10; $i++) {
            $guess = bcdiv(
                bcadd($guess, bcdiv($number, $guess, $scale), $scale),
                '2',
                $scale
            );
        }

        return $guess;
    }
}
