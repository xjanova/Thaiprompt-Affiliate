<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TPIX\CreateTokenRequest;
use App\Http\Requests\TPIX\TransferTokenRequest;
use App\Http\Requests\TPIX\BuyTokenRequest;
use App\Http\Requests\TPIX\SellTokenRequest;
use App\Models\TPIXToken;
use App\Services\TPIX\TokenFactoryService;
use App\Services\TPIX\TokenWalletIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TokenApiController extends Controller
{
    public function __construct(
        protected TokenFactoryService $tokenFactory,
        protected TokenWalletIntegrationService $walletIntegration
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get token list with filters
     */
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'api_tokens_' . md5(json_encode($request->all()));

        $result = Cache::remember($cacheKey, 60, function () use ($request) {
            $query = TPIXToken::where('status', 'active')
                ->where('is_listed', true);

            // Search
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('symbol', 'like', "%{$request->search}%");
                });
            }

            // Category filter
            if ($request->category) {
                $query->where('category', $request->category);
            }

            // Sort
            $sortField = $request->get('sort', 'created_at');
            $sortOrder = $request->get('order', 'desc');
            $query->orderBy($sortField, $sortOrder);

            $tokens = $query->paginate($request->get('per_page', 20));

            return [
                'data' => $tokens->map(function($token) {
                    return [
                        'id' => $token->id,
                        'name' => $token->name,
                        'symbol' => $token->symbol,
                        'logo' => $token->logo ? asset('storage/' . $token->logo) : null,
                        'current_price' => $token->current_price_tpix,
                        'price_change_24h' => $token->price_change_24h,
                        'market_cap' => $token->market_cap,
                        'volume_24h' => $token->volume_24h,
                        'holders_count' => $token->holders_count,
                        'is_verified' => $token->is_verified,
                        'is_featured' => $token->is_featured,
                        'category' => $token->category,
                    ];
                }),
                'meta' => [
                    'current_page' => $tokens->currentPage(),
                    'last_page' => $tokens->lastPage(),
                    'per_page' => $tokens->perPage(),
                    'total' => $tokens->total(),
                ],
            ];
        });

        return response()->json($result);
    }

    /**
     * Get token details
     */
    public function show(string $id): JsonResponse
    {
        $token = TPIXToken::with(['creator'])->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $token->id,
                'name' => $token->name,
                'symbol' => $token->symbol,
                'description' => $token->description,
                'logo' => $token->logo ? asset('storage/' . $token->logo) : null,
                'contract_address' => $token->contract_address,
                'total_supply' => $token->total_supply,
                'decimals' => $token->decimals,
                'current_price' => $token->current_price_tpix,
                'price_change_24h' => $token->price_change_24h,
                'price_change_7d' => $token->price_change_7d,
                'market_cap' => $token->market_cap,
                'volume_24h' => $token->volume_24h,
                'holders_count' => $token->holders_count,
                'tx_count_24h' => $token->tx_count_24h,
                'is_verified' => $token->is_verified,
                'is_featured' => $token->is_featured,
                'category' => $token->category,
                'features' => [
                    'mintable' => $token->is_mintable,
                    'burnable' => $token->is_burnable,
                    'pausable' => $token->is_pausable,
                    'freezable' => $token->is_freezable,
                ],
                'social' => [
                    'website' => $token->website,
                    'twitter' => $token->twitter_handle,
                    'telegram' => $token->telegram_group,
                    'discord' => $token->discord_invite,
                ],
                'creator' => [
                    'id' => $token->creator->id,
                    'name' => $token->creator->name,
                ],
                'created_at' => $token->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create new token
     */
    public function store(CreateTokenRequest $request): JsonResponse
    {
        try {
            $token = $this->tokenFactory->createToken(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'message' => 'Token created successfully',
                'data' => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'symbol' => $token->symbol,
                    'status' => $token->status,
                    'referral_code' => $token->referral_code,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deploy token to blockchain
     */
    public function deploy(string $id): JsonResponse
    {
        try {
            $token = TPIXToken::findOrFail($id);

            $this->authorize('deploy', $token);

            \App\Jobs\TPIX\DeployTokenJob::dispatch($token->id);

            return response()->json([
                'message' => 'Token deployment initiated',
                'data' => [
                    'id' => $token->id,
                    'status' => 'deploying',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to deploy token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transfer tokens
     */
    public function transfer(TransferTokenRequest $request, string $id): JsonResponse
    {
        try {
            $token = TPIXToken::findOrFail($id);

            $result = $this->walletIntegration->transferToken(
                $token,
                $request->user(),
                $request->to_address_or_email,
                $request->amount
            );

            return response()->json([
                'message' => 'Transfer successful',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Transfer failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buy tokens
     */
    public function buy(BuyTokenRequest $request, string $id): JsonResponse
    {
        try {
            $token = TPIXToken::findOrFail($id);

            $result = $this->walletIntegration->buyToken(
                $request->user(),
                $token,
                $request->tpix_amount
            );

            return response()->json([
                'message' => 'Purchase successful',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Purchase failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sell tokens
     */
    public function sell(SellTokenRequest $request, string $id): JsonResponse
    {
        try {
            $token = TPIXToken::findOrFail($id);

            $result = $this->walletIntegration->sellToken(
                $request->user(),
                $token,
                $request->token_amount
            );

            return response()->json([
                'message' => 'Sale successful',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sale failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's token balances
     */
    public function balances(Request $request): JsonResponse
    {
        $balances = $request->user()->tokenBalances()
            ->with('token')
            ->where('balance', '>', 0)
            ->get()
            ->map(function($balance) {
                return [
                    'token' => [
                        'id' => $balance->token->id,
                        'name' => $balance->token->name,
                        'symbol' => $balance->token->symbol,
                        'logo' => $balance->token->logo ? asset('storage/' . $balance->token->logo) : null,
                    ],
                    'balance' => $balance->balance,
                    'available_balance' => $balance->available_balance,
                    'locked_balance' => $balance->locked_balance,
                    'is_frozen' => $balance->is_frozen,
                ];
            });

        return response()->json(['data' => $balances]);
    }

    /**
     * Get user's portfolio value
     */
    public function portfolio(Request $request): JsonResponse
    {
        $portfolio = $this->walletIntegration->getUserPortfolio($request->user());

        return response()->json(['data' => $portfolio]);
    }

    /**
     * Get token transactions
     */
    public function transactions(Request $request, string $id): JsonResponse
    {
        $token = TPIXToken::findOrFail($id);

        $transactions = $token->transfers()
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $transactions->map(function($tx) {
                return [
                    'tx_hash' => $tx->tx_hash,
                    'from' => $tx->from_address,
                    'to' => $tx->to_address,
                    'amount' => $tx->amount,
                    'status' => $tx->status,
                    'timestamp' => $tx->created_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }
}
