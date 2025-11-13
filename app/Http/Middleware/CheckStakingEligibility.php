<?php

namespace App\Http\Middleware;

use App\Models\TPIXStakingPool;
use App\Models\TPIXTokenBalance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStakingEligibility
{
    /**
     * Handle an incoming request.
     *
     * Verifies that the user is eligible to stake in the pool
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $poolId = $request->route('pool_id') ?? $request->input('pool_id');

        if (!$poolId) {
            return response()->json(['error' => 'Pool ID not found'], 400);
        }

        $pool = TPIXStakingPool::find($poolId);

        if (!$pool) {
            return response()->json(['error' => 'Staking pool not found'], 404);
        }

        $user = $request->user();

        // Check if pool is active
        if (!$pool->isActiveNow()) {
            return response()->json([
                'error' => 'Pool not active',
                'message' => 'This staking pool is not currently active',
                'pool_status' => $pool->status,
            ], 422);
        }

        // Check if pool is full (max stakers)
        if ($pool->max_stakers && $pool->current_stakers >= $pool->max_stakers) {
            return response()->json([
                'error' => 'Pool full',
                'message' => 'This staking pool has reached maximum capacity',
                'current_stakers' => $pool->current_stakers,
                'max_stakers' => $pool->max_stakers,
            ], 422);
        }

        // Check if pool cap is reached
        if ($pool->pool_cap && bccomp($pool->total_staked, $pool->pool_cap, 8) >= 0) {
            return response()->json([
                'error' => 'Pool cap reached',
                'message' => 'This staking pool has reached its maximum stake amount',
                'total_staked' => $pool->total_staked,
                'pool_cap' => $pool->pool_cap,
            ], 422);
        }

        // Check if user has sufficient balance
        $amount = $request->input('amount');

        if ($amount) {
            $balance = TPIXTokenBalance::where('token_id', $pool->token_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$balance || bccomp($balance->available_balance, $amount, 8) < 0) {
                return response()->json([
                    'error' => 'Insufficient balance',
                    'message' => 'You do not have enough tokens to stake',
                    'available_balance' => $balance->available_balance ?? '0',
                    'requested_amount' => $amount,
                ], 422);
            }

            // Check min/max stake amount
            if (bccomp($amount, $pool->min_stake_amount, 8) < 0) {
                return response()->json([
                    'error' => 'Amount too low',
                    'message' => "Minimum stake amount is {$pool->min_stake_amount}",
                    'min_amount' => $pool->min_stake_amount,
                ], 422);
            }

            if ($pool->max_stake_amount && bccomp($amount, $pool->max_stake_amount, 8) > 0) {
                return response()->json([
                    'error' => 'Amount too high',
                    'message' => "Maximum stake amount is {$pool->max_stake_amount}",
                    'max_amount' => $pool->max_stake_amount,
                ], 422);
            }
        }

        // Add pool to request
        $request->merge(['pool' => $pool]);

        return $next($request);
    }
}
