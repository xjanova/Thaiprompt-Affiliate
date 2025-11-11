<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoinExchangeRate;
use App\Models\CoinExchangeRequest;
use App\Models\VideoCoin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CoinExchangeController extends Controller
{
    /**
     * Get available exchange rates
     */
    public function rates(Request $request)
    {
        $user = $request->user();

        $rates = CoinExchangeRate::active()
            ->orderBy('min_coins')
            ->get()
            ->map(function ($rate) use ($user) {
                $eligibility = $rate->canUserExchange($user, $rate->min_coins);

                return [
                    'rate' => $rate,
                    'eligible' => $eligibility['can'],
                    'reason' => $eligibility['reason'] ?? null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $rates,
        ]);
    }

    /**
     * Calculate exchange amount
     */
    public function calculate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coins_amount' => 'required|numeric|min:1',
            'exchange_rate_id' => 'required|exists:coin_exchange_rates,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $coinsAmount = $request->input('coins_amount');
        $exchangeRate = CoinExchangeRate::findOrFail($request->input('exchange_rate_id'));

        $moneyAmount = $exchangeRate->calculateMoney($coinsAmount);

        return response()->json([
            'success' => true,
            'data' => [
                'coins_amount' => $coinsAmount,
                'money_amount' => $moneyAmount,
                'exchange_rate' => $exchangeRate->rate,
            ],
        ]);
    }

    /**
     * Create exchange request
     */
    public function exchange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coins_amount' => 'required|numeric|min:1',
            'exchange_rate_id' => 'required|exists:coin_exchange_rates,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $coinsAmount = $request->input('coins_amount');
        $exchangeRate = CoinExchangeRate::findOrFail($request->input('exchange_rate_id'));

        // Check if user can exchange
        $eligibility = $exchangeRate->canUserExchange($user, $coinsAmount);
        if (!$eligibility['can']) {
            return response()->json([
                'success' => false,
                'message' => $eligibility['reason'],
            ], 400);
        }

        // Check coin balance
        $videoCoin = VideoCoin::where('user_id', $user->id)->first();
        if (!$videoCoin || $videoCoin->balance < $coinsAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient coins balance',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Deduct coins
            $videoCoin->exchangeToMoney($coinsAmount, $exchangeRate->id);

            // Create exchange request
            $moneyAmount = $exchangeRate->calculateMoney($coinsAmount);

            $exchangeRequest = CoinExchangeRequest::create([
                'user_id' => $user->id,
                'exchange_rate_id' => $exchangeRate->id,
                'coins_amount' => $coinsAmount,
                'money_amount' => $moneyAmount,
                'exchange_rate' => $exchangeRate->rate,
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'exchange_request' => $exchangeRequest,
                    'message' => 'Exchange request created. Waiting for admin approval.',
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create exchange request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's exchange history
     */
    public function history(Request $request)
    {
        $user = $request->user();

        $history = CoinExchangeRequest::with('exchangeRate')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get exchange request details
     */
    public function show(Request $request, $requestId)
    {
        $user = $request->user();

        $exchangeRequest = CoinExchangeRequest::with(['exchangeRate', 'approver', 'walletTransaction'])
            ->where('user_id', $user->id)
            ->findOrFail($requestId);

        return response()->json([
            'success' => true,
            'data' => $exchangeRequest,
        ]);
    }
}
