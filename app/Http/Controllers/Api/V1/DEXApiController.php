<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TPIXToken;
use App\Models\TPIXLiquidityPool;
use App\Services\TPIX\DEXService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class DEXApiController extends Controller
{
    public function __construct(
        protected DEXService $dexService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all liquidity pools
     */
    public function pools(Request $request): JsonResponse
    {
        $cacheKey = 'dex_pools_' . md5(json_encode($request->all()));

        $result = Cache::remember($cacheKey, 60, function () use ($request) {
            $query = TPIXLiquidityPool::with(['tokenA', 'tokenB'])
                ->where('is_active', true);

            // Filter by token
            if ($request->token_id) {
                $query->where(function($q) use ($request) {
                    $q->where('token_a_id', $request->token_id)
                      ->orWhere('token_b_id', $request->token_id);
                });
            }

            // Sort
            $sortField = $request->get('sort', 'volume_24h');
            $sortOrder = $request->get('order', 'desc');
            $query->orderBy($sortField, $sortOrder);

            $pools = $query->paginate($request->get('per_page', 20));

            return [
                'data' => $pools->map(function($pool) {
                    return [
                        'id' => $pool->id,
                        'pair' => $pool->pair_name,
                        'token_a' => [
                            'id' => $pool->tokenA->id,
                            'symbol' => $pool->tokenA->symbol,
                            'logo' => $pool->tokenA->logo ? asset('storage/' . $pool->tokenA->logo) : null,
                        ],
                        'token_b' => [
                            'id' => $pool->tokenB->id,
                            'symbol' => $pool->tokenB->symbol,
                            'logo' => $pool->tokenB->logo ? asset('storage/' . $pool->tokenB->logo) : null,
                        ],
                        'reserves' => [
                            'token_a' => $pool->reserve_a,
                            'token_b' => $pool->reserve_b,
                        ],
                        'total_liquidity' => $pool->total_liquidity,
                        'tvl' => $pool->getTVL(),
                        'apy' => $pool->getAPY(),
                        'volume_24h' => $pool->volume_24h,
                        'fee_percentage' => $pool->fee_percentage,
                        'price' => $pool->getCurrentPrice(),
                    ];
                }),
                'meta' => [
                    'current_page' => $pools->currentPage(),
                    'last_page' => $pools->lastPage(),
                    'per_page' => $pools->perPage(),
                    'total' => $pools->total(),
                ],
            ];
        });

        return response()->json($result);
    }

    /**
     * Get pool details
     */
    public function poolDetails(string $id): JsonResponse
    {
        $pool = TPIXLiquidityPool::with(['tokenA', 'tokenB'])->findOrFail($id);

        // Get recent swaps
        $recentSwaps = $pool->swaps()
            ->with(['user', 'tokenIn', 'tokenOut'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($swap) {
                return [
                    'tx_hash' => $swap->tx_hash,
                    'direction' => $swap->swap_direction,
                    'amount_in' => $swap->amount_in,
                    'amount_out' => $swap->amount_out,
                    'price_impact' => $swap->price_impact,
                    'timestamp' => $swap->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => [
                'id' => $pool->id,
                'pair' => $pool->pair_name,
                'token_a' => [
                    'id' => $pool->tokenA->id,
                    'name' => $pool->tokenA->name,
                    'symbol' => $pool->tokenA->symbol,
                    'logo' => $pool->tokenA->logo ? asset('storage/' . $pool->tokenA->logo) : null,
                ],
                'token_b' => [
                    'id' => $pool->tokenB->id,
                    'name' => $pool->tokenB->name,
                    'symbol' => $pool->tokenB->symbol,
                    'logo' => $pool->tokenB->logo ? asset('storage/' . $pool->tokenB->logo) : null,
                ],
                'reserves' => [
                    'token_a' => $pool->reserve_a,
                    'token_b' => $pool->reserve_b,
                ],
                'total_liquidity' => $pool->total_liquidity,
                'tvl' => $pool->getTVL(),
                'apy' => $pool->getAPY(),
                'volume_24h' => $pool->volume_24h,
                'volume_7d' => $pool->volume_7d,
                'fees_collected' => $pool->fees_collected,
                'tx_count' => $pool->tx_count,
                'fee_percentage' => $pool->fee_percentage,
                'price' => $pool->getCurrentPrice(),
                'recent_swaps' => $recentSwaps,
            ],
        ]);
    }

    /**
     * Get swap quote
     */
    public function quote(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token_in_id' => 'required|exists:tpix_tokens,id',
            'token_out_id' => 'required|exists:tpix_tokens,id|different:token_in_id',
            'amount_in' => 'required|numeric|min:0.00000001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tokenIn = TPIXToken::findOrFail($request->token_in_id);
            $tokenOut = TPIXToken::findOrFail($request->token_out_id);

            $quote = $this->dexService->getQuote(
                $tokenIn,
                $tokenOut,
                $request->amount_in
            );

            return response()->json([
                'data' => [
                    'token_in' => [
                        'id' => $tokenIn->id,
                        'symbol' => $tokenIn->symbol,
                    ],
                    'token_out' => [
                        'id' => $tokenOut->id,
                        'symbol' => $tokenOut->symbol,
                    ],
                    'amount_in' => $request->amount_in,
                    'amount_out' => $quote['amount_out'],
                    'price_impact' => $quote['price_impact'],
                    'fee' => $quote['fee'],
                    'price' => $quote['price'],
                    'minimum_received' => bcmul($quote['amount_out'], '0.995', 8), // 0.5% slippage
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get quote',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute token swap
     */
    public function swap(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token_in_id' => 'required|exists:tpix_tokens,id',
            'token_out_id' => 'required|exists:tpix_tokens,id|different:token_in_id',
            'amount_in' => 'required|numeric|min:0.00000001',
            'min_amount_out' => 'required|numeric|min:0',
            'slippage_tolerance' => 'nullable|numeric|min:0.1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tokenIn = TPIXToken::findOrFail($request->token_in_id);
            $tokenOut = TPIXToken::findOrFail($request->token_out_id);

            $result = $this->dexService->swap(
                $request->user(),
                $tokenIn,
                $tokenOut,
                $request->amount_in,
                $request->min_amount_out,
                $request->slippage_tolerance ?? 0.5
            );

            return response()->json([
                'message' => 'Swap executed successfully',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Swap failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Add liquidity to pool
     */
    public function addLiquidity(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token_a_id' => 'required|exists:tpix_tokens,id',
            'token_b_id' => 'required|exists:tpix_tokens,id|different:token_a_id',
            'amount_a' => 'required|numeric|min:0.00000001',
            'amount_b' => 'required|numeric|min:0.00000001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tokenA = TPIXToken::findOrFail($request->token_a_id);
            $tokenB = TPIXToken::findOrFail($request->token_b_id);

            $result = $this->dexService->addLiquidity(
                $request->user(),
                $tokenA,
                $tokenB,
                $request->amount_a,
                $request->amount_b
            );

            return response()->json([
                'message' => 'Liquidity added successfully',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add liquidity',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove liquidity from pool
     */
    public function removeLiquidity(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'position_id' => 'required|exists:tpix_liquidity_positions,id',
            'lp_tokens' => 'required|numeric|min:0.00000001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->dexService->removeLiquidity(
                $request->user(),
                $request->position_id,
                $request->lp_tokens
            );

            return response()->json([
                'message' => 'Liquidity removed successfully',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove liquidity',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user's liquidity positions
     */
    public function myPositions(Request $request): JsonResponse
    {
        $positions = $request->user()
            ->liquidityPositions()
            ->with(['pool.tokenA', 'pool.tokenB'])
            ->where('status', 'active')
            ->get()
            ->map(function($position) {
                return [
                    'id' => $position->id,
                    'pool' => [
                        'id' => $position->pool->id,
                        'pair' => $position->pool->pair_name,
                    ],
                    'token_a' => [
                        'symbol' => $position->pool->tokenA->symbol,
                        'amount' => $position->token_a_amount,
                    ],
                    'token_b' => [
                        'symbol' => $position->pool->tokenB->symbol,
                        'amount' => $position->token_b_amount,
                    ],
                    'lp_tokens' => $position->lp_tokens,
                    'share_percentage' => $position->share_percentage,
                    'current_value_tpix' => $position->getCurrentValueTPIX(),
                    'fees_earned' => $position->fees_earned,
                    'unclaimed_fees' => $position->unclaimed_fees,
                    'roi' => $position->roi,
                    'duration_days' => $position->duration_days,
                    'created_at' => $position->created_at->toIso8601String(),
                ];
            });

        return response()->json(['data' => $positions]);
    }

    /**
     * Get user's swap history
     */
    public function mySwaps(Request $request): JsonResponse
    {
        $swaps = $request->user()
            ->swaps()
            ->with(['tokenIn', 'tokenOut', 'pool'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $swaps->map(function($swap) {
                return [
                    'id' => $swap->id,
                    'tx_hash' => $swap->tx_hash,
                    'direction' => $swap->swap_direction,
                    'token_in' => [
                        'symbol' => $swap->tokenIn->symbol,
                        'amount' => $swap->amount_in,
                    ],
                    'token_out' => [
                        'symbol' => $swap->tokenOut->symbol,
                        'amount' => $swap->amount_out,
                    ],
                    'fee' => $swap->fee,
                    'price_impact' => $swap->price_impact,
                    'status' => $swap->status,
                    'explorer_url' => $swap->explorer_url,
                    'created_at' => $swap->created_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $swaps->currentPage(),
                'last_page' => $swaps->lastPage(),
                'total' => $swaps->total(),
            ],
        ]);
    }

    /**
     * Get DEX statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = Cache::remember('dex_statistics', 300, function () {
            $pools = TPIXLiquidityPool::where('is_active', true)->get();

            $totalTVL = $pools->sum(function($pool) {
                return $pool->getTVL();
            });

            $totalVolume24h = $pools->sum('volume_24h');
            $totalVolume7d = $pools->sum('volume_7d');
            $totalFeesCollected = $pools->sum('fees_collected');

            return [
                'total_pools' => $pools->count(),
                'total_tvl' => $totalTVL,
                'total_volume_24h' => $totalVolume24h,
                'total_volume_7d' => $totalVolume7d,
                'total_fees_collected' => $totalFeesCollected,
                'active_pairs' => $pools->count(),
            ];
        });

        return response()->json(['data' => $stats]);
    }
}
