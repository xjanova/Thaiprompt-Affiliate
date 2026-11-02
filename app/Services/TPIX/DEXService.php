<?php

namespace App\Services\TPIX;

use App\Models\TPIXLiquidityPool;
use App\Models\TPIXLiquidityPosition;
use App\Models\TPIXSwap;
use App\Models\TPIXToken;
use App\Models\TPIXTokenBalance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * บริการแลกเปลี่ยนเหรียญ (DEX) แบบ AMM สูตร x * y = k
 *
 * กติกาสำคัญ
 * - พูลเก็บเหรียญตามลำดับ token_a_id < token_b_id เสมอ → ทุกครั้งที่ใช้สำรอง/จำนวน ต้องจัดทิศให้ตรงกับพูลก่อน
 * - ขอราคา/แลก ใช้พูลแบบอ่านอย่างเดียว (ไม่สร้างพูลใหม่) · สร้างพูลได้เฉพาะตอนเพิ่มสภาพคล่อง
 * - ข้อผิดพลาดที่สมาชิกควรเห็นโยนเป็น \DomainException (ข้อความเป็นรหัสภาษาอังกฤษที่หน้าเว็บแปลไทยได้)
 *   ส่วนข้อผิดพลาดอื่นให้ตัวควบคุมบันทึก log แล้วตอบข้อความกลาง
 */
class DEXService
{
    /**
     * แลกเหรียญ tokenIn → tokenOut ผ่านพูลที่มีอยู่แล้ว
     */
    public function swap(
        User $user,
        TPIXToken $tokenIn,
        TPIXToken $tokenOut,
        string $amountIn,
        string $minAmountOut,
        float $slippageTolerance = 0.5
    ): array {
        $amountIn = $this->normalizeAmount($amountIn);
        $minAmountOut = $this->normalizeAmount($minAmountOut, true);

        if ($tokenIn->id === $tokenOut->id) {
            throw new \DomainException('Invalid pair');
        }

        DB::beginTransaction();

        try {
            // ล็อกแถวพูลกันการแลกพร้อมกันอ่านสำรองค่าเดียวกัน
            $pool = $this->findPool($tokenIn, $tokenOut, true);
            if (! $pool) {
                throw new \DomainException('Pool not found');
            }

            [$reserveIn, $reserveOut, $inIsA] = $this->orientReserves($pool, $tokenIn->id);
            if (bccomp($reserveIn, '0', 8) <= 0 || bccomp($reserveOut, '0', 8) <= 0) {
                throw new \DomainException('No liquidity');
            }

            $amountOut = $this->calculateOutputAmount($amountIn, $reserveIn, $reserveOut);
            if (bccomp($amountOut, '0', 8) <= 0 || bccomp($amountOut, $reserveOut, 8) >= 0) {
                throw new \DomainException('No liquidity');
            }

            if (bccomp($amountOut, $minAmountOut, 8) < 0) {
                throw new \DomainException('Slippage tolerance exceeded');
            }

            $priceImpact = $this->calculatePriceImpact($amountIn, $reserveIn, $reserveOut);

            // ค่าธรรมเนียมแพลตฟอร์ม 0.3%
            $fee = bcmul($amountOut, '0.003', 8);
            $amountOutAfterFee = bcsub($amountOut, $fee, 8);

            // ตรวจและหักยอดฝั่งจ่าย (ล็อกแถว)
            $this->deductUserBalance($user->id, $tokenIn->id, $amountIn);

            // เพิ่มยอดฝั่งรับ (สร้างแถวพร้อม wallet_address ถ้ายังไม่เคยถือ)
            $this->addUserBalance($user->id, $tokenOut->id, $amountOutAfterFee);

            // อัปเดตสำรองตามทิศของพูล
            $newIn = bcadd($reserveIn, $amountIn, 8);
            $newOut = bcsub($reserveOut, $amountOut, 8);
            $pool->forceFill([
                'reserve_a' => $inIsA ? $newIn : $newOut,
                'reserve_b' => $inIsA ? $newOut : $newIn,
                'fees_collected' => bcadd((string) $pool->fees_collected, $fee, 8),
                'tx_count' => (int) $pool->tx_count + 1,
                'last_trade_at' => now(),
            ])->save();

            $this->distributeFeeToLPs($pool->id, $fee);

            $swap = TPIXSwap::create([
                'user_id' => $user->id,
                'pool_id' => $pool->id,
                'token_in_id' => $tokenIn->id,
                'token_out_id' => $tokenOut->id,
                'amount_in' => $amountIn,
                'amount_out' => $amountOutAfterFee,
                'fee' => $fee,
                'price_impact' => $priceImpact,
                'price_before' => bcdiv($reserveOut, $reserveIn, 8),
                'price_after' => bcdiv($newOut, $newIn, 8),
                'min_amount_out' => $minAmountOut,
                'slippage_tolerance' => $slippageTolerance,
                'tx_hash' => '0x'.bin2hex(random_bytes(32)),
                'status' => 'completed',
            ]);

            DB::commit();

            Log::info('Swap executed', [
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
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * จำนวนที่ได้ตามสูตร constant product (หัก 0.3% ฝั่งขาเข้า)
     * amountOut = (reserveOut * amountIn * 997) / (reserveIn * 1000 + amountIn * 997)
     */
    protected function calculateOutputAmount(string $amountIn, string $reserveIn, string $reserveOut): string
    {
        $amountInWithFee = bcmul($amountIn, '997', 8);
        $numerator = bcmul($amountInWithFee, $reserveOut, 8);
        $denominator = bcadd(bcmul($reserveIn, '1000', 8), $amountInWithFee, 8);

        return bcdiv($numerator, $denominator, 8);
    }

    /**
     * ผลกระทบต่อราคา (%) — ค่าติดลบ = ราคาฝั่งรับแย่ลง
     */
    protected function calculatePriceImpact(string $amountIn, string $reserveIn, string $reserveOut): float
    {
        $priceBefore = bcdiv($reserveOut, $reserveIn, 8);
        if (bccomp($priceBefore, '0', 8) === 0) {
            return 0.0;
        }

        $newReserveIn = bcadd($reserveIn, $amountIn, 8);
        $amountOut = $this->calculateOutputAmount($amountIn, $reserveIn, $reserveOut);
        $newReserveOut = bcsub($reserveOut, $amountOut, 8);
        $priceAfter = bcdiv($newReserveOut, $newReserveIn, 8);

        return (float) bcdiv(bcmul(bcsub($priceAfter, $priceBefore, 8), '100', 8), $priceBefore, 2);
    }

    /**
     * เพิ่มสภาพคล่อง (สร้างพูลใหม่ได้ที่นี่ที่เดียว)
     *
     * $amountA เป็นจำนวนของ $tokenA ตามที่สมาชิกเลือก — ถ้าทิศกลับกับพูลจะสลับจำนวนให้ตรงฝั่งก่อนคำนวณ
     */
    public function addLiquidity(
        User $user,
        TPIXToken $tokenA,
        TPIXToken $tokenB,
        string $amountA,
        string $amountB
    ): array {
        $amountA = $this->normalizeAmount($amountA);
        $amountB = $this->normalizeAmount($amountB);

        if ($tokenA->id === $tokenB->id) {
            throw new \DomainException('Invalid pair');
        }

        DB::beginTransaction();

        try {
            $pool = $this->getOrCreatePool($tokenA, $tokenB);
            if (! $pool->is_active) {
                throw new \DomainException('Pool is not active');
            }

            // จัดจำนวนให้ตรงฝั่งของพูล (token_a_id < token_b_id)
            if ($tokenA->id !== (int) $pool->token_a_id) {
                [$tokenA, $tokenB] = [$tokenB, $tokenA];
                [$amountA, $amountB] = [$amountB, $amountA];
            }

            $reserveA = (string) $pool->reserve_a;
            $reserveB = (string) $pool->reserve_b;
            $totalLiquidity = $this->effectiveTotalLiquidity($pool);
            $hasReserves = bccomp($reserveA, '0', 8) > 0 && bccomp($reserveB, '0', 8) > 0;

            if (! $hasReserves || bccomp($totalLiquidity, '0', 8) <= 0) {
                // สภาพคล่องแรกของพูล
                $lpTokens = bcsqrt(bcmul($amountA, $amountB, 16), 8);
            } else {
                // ตามสัดส่วนสำรอง — ใช้ฝั่งที่ได้น้อยกว่า
                $lpTokensA = bcdiv(bcmul($amountA, $totalLiquidity, 16), $reserveA, 8);
                $lpTokensB = bcdiv(bcmul($amountB, $totalLiquidity, 16), $reserveB, 8);
                $lpTokens = bccomp($lpTokensA, $lpTokensB, 8) <= 0 ? $lpTokensA : $lpTokensB;
            }

            if (bccomp($lpTokens, '0', 8) <= 0) {
                throw new \DomainException('Amount too small');
            }

            // หักยอดสมาชิก (ตรวจยอดพอก่อนหักทุกครั้ง)
            $this->deductUserBalance($user->id, $tokenA->id, $amountA);
            $this->deductUserBalance($user->id, $tokenB->id, $amountB);

            $pool->forceFill([
                'reserve_a' => bcadd($reserveA, $amountA, 8),
                'reserve_b' => bcadd($reserveB, $amountB, 8),
                'total_liquidity' => bcadd($totalLiquidity, $lpTokens, 8),
            ])->save();

            $lpPosition = TPIXLiquidityPosition::create([
                'user_id' => $user->id,
                'pool_id' => $pool->id,
                'token_a_amount' => $amountA,
                'token_b_amount' => $amountB,
                'lp_tokens' => $lpTokens,
                'status' => 'active',
                'add_tx_hash' => '0x'.bin2hex(random_bytes(32)),
            ]);

            DB::commit();

            return [
                'success' => true,
                'pool_id' => $pool->id,
                'lp_tokens' => $lpTokens,
                'position_id' => $lpPosition->id,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * ถอนสภาพคล่องตามจำนวน LP
     */
    public function removeLiquidity(User $user, int $positionId, string $lpTokensToRemove): array
    {
        $lpTokensToRemove = $this->normalizeAmount($lpTokensToRemove);

        DB::beginTransaction();

        try {
            $position = TPIXLiquidityPosition::where('id', $positionId)
                ->where('user_id', $user->id)
                ->where('status', '!=', 'removed')
                ->lockForUpdate()
                ->first();

            if (! $position) {
                throw new \DomainException('Position not found');
            }

            if (bccomp($lpTokensToRemove, (string) $position->lp_tokens, 8) > 0) {
                throw new \DomainException('Insufficient LP tokens');
            }

            $pool = TPIXLiquidityPool::whereKey($position->pool_id)->lockForUpdate()->firstOrFail();
            $totalLiquidity = $this->effectiveTotalLiquidity($pool);

            if (bccomp($totalLiquidity, '0', 8) <= 0 || bccomp($lpTokensToRemove, $totalLiquidity, 8) > 0) {
                throw new \DomainException('Insufficient LP tokens');
            }

            $reserveA = (string) $pool->reserve_a;
            $reserveB = (string) $pool->reserve_b;
            $amountA = bcdiv(bcmul($reserveA, $lpTokensToRemove, 16), $totalLiquidity, 8);
            $amountB = bcdiv(bcmul($reserveB, $lpTokensToRemove, 16), $totalLiquidity, 8);

            $pool->forceFill([
                'reserve_a' => bcsub($reserveA, $amountA, 8),
                'reserve_b' => bcsub($reserveB, $amountB, 8),
                'total_liquidity' => bcsub($totalLiquidity, $lpTokensToRemove, 8),
            ])->save();

            $this->addUserBalance($user->id, (int) $pool->token_a_id, $amountA);
            $this->addUserBalance($user->id, (int) $pool->token_b_id, $amountB);

            // เผา LP · ถอนหมดแล้วเก็บแถวไว้เป็นประวัติ (status removed) แทนการลบทิ้ง
            $remaining = bcsub((string) $position->lp_tokens, $lpTokensToRemove, 8);
            $isEmpty = bccomp($remaining, '0', 8) <= 0;
            $position->forceFill([
                'lp_tokens' => $isEmpty ? '0' : $remaining,
                'status' => $isEmpty ? 'removed' : 'active',
                'removed_at' => $isEmpty ? now() : null,
                'remove_tx_hash' => '0x'.bin2hex(random_bytes(32)),
            ])->save();

            DB::commit();

            return [
                'success' => true,
                'amount_a' => $amountA,
                'amount_b' => $amountB,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * สภาพคล่องรวมที่ใช้คำนวณ — พูลเก่าที่ total_liquidity ค้างเป็น 0 ใช้ผลรวม LP ของตำแหน่งที่ยังเปิดอยู่แทน
     * (เดิม addLiquidity ไม่เคยบวก total_liquidity จึงมีพูลที่มีสำรองแต่ total = 0)
     */
    public function effectiveTotalLiquidity(TPIXLiquidityPool $pool): string
    {
        $total = (string) ($pool->total_liquidity ?? '0');
        if (bccomp($total, '0', 8) > 0) {
            return $total;
        }

        $sum = TPIXLiquidityPosition::where('pool_id', $pool->id)
            ->where('status', '!=', 'removed')
            ->sum('lp_tokens');

        return $this->normalizeAmount((string) $sum, true);
    }

    /**
     * หาพูลของคู่เหรียญแบบอ่านอย่างเดียว (เฉพาะพูลที่เปิดใช้งาน) — ไม่สร้างพูลใหม่
     */
    public function findPool(TPIXToken $tokenA, TPIXToken $tokenB, bool $lock = false): ?TPIXLiquidityPool
    {
        [$a, $b] = $tokenA->id < $tokenB->id ? [$tokenA->id, $tokenB->id] : [$tokenB->id, $tokenA->id];

        $query = TPIXLiquidityPool::where('token_a_id', $a)
            ->where('token_b_id', $b)
            ->where('is_active', true);

        return $lock ? $query->lockForUpdate()->first() : $query->first();
    }

    /**
     * หาหรือสร้างพูล (ใช้เฉพาะตอนเพิ่มสภาพคล่อง) — ล็อกแถวพูลไว้จนจบ transaction
     */
    protected function getOrCreatePool(TPIXToken $tokenA, TPIXToken $tokenB): TPIXLiquidityPool
    {
        [$a, $b] = $tokenA->id < $tokenB->id ? [$tokenA->id, $tokenB->id] : [$tokenB->id, $tokenA->id];

        $pool = TPIXLiquidityPool::where('token_a_id', $a)->where('token_b_id', $b)->lockForUpdate()->first();
        if ($pool) {
            return $pool;
        }

        TPIXLiquidityPool::create([
            'token_a_id' => $a,
            'token_b_id' => $b,
            'reserve_a' => '0',
            'reserve_b' => '0',
            'total_liquidity' => '0',
            'fee_percentage' => '0.3',
            'is_active' => true,
        ]);

        return TPIXLiquidityPool::where('token_a_id', $a)->where('token_b_id', $b)->lockForUpdate()->firstOrFail();
    }

    /**
     * สำรองตามทิศการแลก: [สำรองฝั่งจ่าย, สำรองฝั่งรับ, ฝั่งจ่ายคือ token_a หรือไม่]
     */
    protected function orientReserves(TPIXLiquidityPool $pool, int $tokenInId): array
    {
        $reserveA = $this->normalizeAmount((string) $pool->reserve_a, true);
        $reserveB = $this->normalizeAmount((string) $pool->reserve_b, true);
        $inIsA = $tokenInId === (int) $pool->token_a_id;

        return $inIsA ? [$reserveA, $reserveB, true] : [$reserveB, $reserveA, false];
    }

    /**
     * หักยอดสมาชิก — ปฏิเสธถ้ายอดใช้ได้ไม่พอ (เดิมหักตรงๆ ทำให้ยอดติดลบได้)
     */
    protected function deductUserBalance(int $userId, int $tokenId, string $amount): void
    {
        $balance = TPIXTokenBalance::where('user_id', $userId)
            ->where('token_id', $tokenId)
            ->lockForUpdate()
            ->first();

        if (! $balance
            || $balance->is_frozen
            || bccomp((string) $balance->available_balance, $amount, 8) < 0
            || bccomp((string) $balance->balance, $amount, 8) < 0) {
            throw new \DomainException('Insufficient balance');
        }

        $balance->forceFill([
            'balance' => bcsub((string) $balance->balance, $amount, 8),
            'available_balance' => bcsub((string) $balance->available_balance, $amount, 8),
            'last_activity_at' => now(),
        ])->save();
    }

    /**
     * เพิ่มยอดสมาชิก (สร้างแถวพร้อม wallet_address ถ้ายังไม่มี)
     */
    protected function addUserBalance(int $userId, int $tokenId, string $amount): void
    {
        $balance = TPIXTokenBalance::lockOrCreateFor($userId, $tokenId);

        $balance->forceFill([
            'balance' => bcadd((string) $balance->balance, $amount, 8),
            'available_balance' => bcadd((string) $balance->available_balance, $amount, 8),
            'last_activity_at' => now(),
        ])->save();
    }

    protected function distributeFeeToLPs(int $poolId, string $fee): void
    {
        // แบบย่อ: ค่าธรรมเนียมบันทึกใน fees_collected ของพูล ยังไม่ได้แจกรายตำแหน่ง
        Log::info('Distributing fee to LPs', ['pool_id' => $poolId, 'fee' => $fee]);
    }

    /**
     * ขอราคาแลก — อ่านอย่างเดียว ไม่สร้างพูล
     * ไม่มีพูล/ไม่มีสภาพคล่อง ตอบ amount_out = 0 พร้อม error 'No liquidity'
     */
    public function getQuote(TPIXToken $tokenIn, TPIXToken $tokenOut, string $amountIn): array
    {
        $amountIn = $this->normalizeAmount($amountIn);
        $empty = ['amount_out' => '0', 'price_impact' => 0, 'fee' => '0', 'price' => '0', 'error' => 'No liquidity'];

        $pool = $tokenIn->id === $tokenOut->id ? null : $this->findPool($tokenIn, $tokenOut);
        if (! $pool) {
            return $empty;
        }

        [$reserveIn, $reserveOut] = $this->orientReserves($pool, $tokenIn->id);
        if (bccomp($reserveIn, '0', 8) <= 0 || bccomp($reserveOut, '0', 8) <= 0) {
            return $empty;
        }

        $amountOut = $this->calculateOutputAmount($amountIn, $reserveIn, $reserveOut);
        if (bccomp($amountOut, '0', 8) <= 0) {
            return $empty;
        }

        $fee = bcmul($amountOut, '0.003', 8);

        return [
            'pool_id' => $pool->id,
            'amount_out' => bcsub($amountOut, $fee, 8),
            'price_impact' => $this->calculatePriceImpact($amountIn, $reserveIn, $reserveOut),
            'fee' => $fee,
            'price' => bcdiv($amountOut, $amountIn, 8),
        ];
    }

    /**
     * แปลงจำนวนเป็นสตริงทศนิยมที่ bcmath ใช้ได้ (กันค่าแบบ 1e3 / ค่าติดลบ ที่ทำให้ bcmath พัง)
     */
    protected function normalizeAmount(string $amount, bool $allowZero = false): string
    {
        $amount = trim($amount);

        if (! preg_match('/^\d+(\.\d+)?$/', $amount)) {
            if (is_numeric($amount) && (float) $amount >= 0) {
                $amount = number_format((float) $amount, 8, '.', '');
            } else {
                throw new \DomainException('Invalid amount');
            }
        }

        $amount = bcadd($amount, '0', 8);

        if (! $allowZero && bccomp($amount, '0', 8) <= 0) {
            throw new \DomainException('Invalid amount');
        }

        return $amount;
    }
}
