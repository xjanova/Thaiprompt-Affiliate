<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameLeaderboard;
use App\Models\VideoCoin;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PoolGameController
 *
 * จัดการ API endpoints สำหรับเกม 8 Ball Pool
 * - check wallet, place bet, settle match, save result
 */
class PoolGameController extends Controller
{
    /**
     * ตรวจสอบ wallet + coin balance
     *
     * GET /api/games/8ball-pool/check-wallet
     */
    public function checkWallet(): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'authenticated' => false,
                'login_url' => route('login'),
                'register_url' => route('register'),
            ]);
        }

        $user = Auth::user();
        $wallet = Wallet::where('user_id', $user->id)->first();
        $videoCoin = VideoCoin::where('user_id', $user->id)->first();

        $walletBalance = $wallet ? (float) $wallet->balance : 0;
        $coinBalance = $videoCoin ? (float) $videoCoin->balance : 0;

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'balance' => $walletBalance,
            'coin_balance' => $coinBalance,
            'can_bet' => $walletBalance >= 10 || $coinBalance >= 10,
            'topup_url' => route('user.wallet.index'),
        ]);
    }

    /**
     * วางเดิมพัน (หักจาก wallet)
     *
     * POST /api/games/8ball-pool/place-bet
     */
    public function placeBet(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเข้าสู่ระบบก่อนวางเดิมพัน',
            ], 401);
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:0|max:10000',
        ]);

        $amount = $validated['amount'];

        if ($amount === 0) {
            return response()->json([
                'success' => true,
                'match_id' => Str::uuid()->toString(),
                'remaining_balance' => 0,
                'message' => 'เล่นฟรี',
            ]);
        }

        $user = Auth::user();

        try {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'total_earned' => 0, 'total_spent' => 0, 'currency' => 'points']
            );

            if ($wallet->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => '💰 แต้มไม่เพียงพอ! ต้องการ '.$amount.' แต้ม คุณมี '.number_format($wallet->balance).' แต้ม',
                    'current_balance' => $wallet->balance,
                    'topup_url' => route('user.wallet.index'),
                ], 400);
            }

            DB::beginTransaction();

            try {
                $balanceBefore = $wallet->balance;
                $wallet->decrement('balance', $amount);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $user->id,
                    'type' => 'fee',
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceBefore - $amount,
                    'description' => 'วางเดิมพัน 8 Ball Pool - '.number_format($amount).' แต้ม',
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                $matchId = Str::uuid()->toString();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'match_id' => $matchId,
                    'remaining_balance' => $wallet->fresh()->balance,
                    'message' => 'วางเดิมพัน '.number_format($amount).' แต้ม สำเร็จ!',
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * จ่ายรางวัลหลังจบเกม
     *
     * POST /api/games/8ball-pool/settle-match
     */
    public function settleMatch(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'match_id' => 'required|string',
            'won' => 'required|boolean',
            'amount' => 'required|integer|min:0',
        ]);

        if (! $validated['won'] || $validated['amount'] === 0) {
            return response()->json(['success' => true, 'message' => 'No payout']);
        }

        $user = Auth::user();
        $winnings = $validated['amount'] * 2; // ได้คืน 2 เท่า

        try {
            DB::beginTransaction();

            $wallet = Wallet::where('user_id', $user->id)->first();
            if (! $wallet) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Wallet not found'], 400);
            }

            $balanceBefore = $wallet->balance;
            $wallet->increment('balance', $winnings);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'reward',
                'amount' => $winnings,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $winnings,
                'description' => 'ชนะ 8 Ball Pool - รับ '.number_format($winnings).' แต้ม',
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'winnings' => $winnings,
                'remaining_balance' => $wallet->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * บันทึกผลลงกระดานคะแนน (หักค่าบันทึก 1 แต้ม)
     *
     * POST /api/games/8ball-pool/save-result
     */
    public function saveResult(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเข้าสู่ระบบก่อนบันทึกผล',
            ], 401);
        }

        $validated = $request->validate([
            'won' => 'required|boolean',
            'bet_amount' => 'nullable|integer|min:0',
            'payment_method' => 'nullable|string|in:wallet,coin',
            'player_name' => 'nullable|string|max:50',
        ]);

        $paymentMethod = $validated['payment_method'] ?? 'wallet';
        $user = Auth::user();

        try {
            if ($paymentMethod === 'coin') {
                $videoCoin = VideoCoin::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0, 'lifetime_earned' => 0, 'lifetime_spent' => 0, 'lifetime_exchanged' => 0]
                );

                if ($videoCoin->balance < 1) {
                    return response()->json([
                        'success' => false,
                        'message' => '🪙 เหรียญไม่เพียงพอ!',
                        'current_balance' => $videoCoin->balance,
                    ], 400);
                }
            } else {
                $wallet = Wallet::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0, 'total_earned' => 0, 'total_spent' => 0, 'currency' => 'points']
                );

                if ($wallet->balance < 1) {
                    return response()->json([
                        'success' => false,
                        'message' => '💰 แต้มไม่เพียงพอ!',
                        'current_balance' => $wallet->balance,
                    ], 400);
                }
            }

            DB::beginTransaction();

            try {
                $remainingBalance = 0;

                if ($paymentMethod === 'coin') {
                    $videoCoin = VideoCoin::where('user_id', $user->id)->first();
                    $videoCoin->deductCoins(
                        1,
                        'spent_shop',
                        'Game',
                        null,
                        'บันทึกผล 8 Ball Pool'
                    );
                    $remainingBalance = $videoCoin->balance;
                } else {
                    $wallet = Wallet::where('user_id', $user->id)->first();
                    $balanceBefore = $wallet->balance;
                    $wallet->decrement('balance', 1);

                    WalletTransaction::create([
                        'wallet_id' => $wallet->id,
                        'user_id' => $user->id,
                        'type' => 'fee',
                        'amount' => 1,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceBefore - 1,
                        'description' => 'บันทึกผล 8 Ball Pool',
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                    $remainingBalance = $wallet->balance;
                }

                // Save to leaderboard
                $game = Game::where('slug', '8ball-pool')->first();

                if ($game) {
                    $playerName = ! empty($validated['player_name'])
                        ? $validated['player_name']
                        : ($user->name ?? 'Player');

                    GameLeaderboard::create([
                        'user_id' => $user->id,
                        'game_id' => $game->id,
                        'player_name' => $playerName,
                        'score' => $validated['won'] ? ($validated['bet_amount'] ?? 0) * 2 : 0,
                        'wave_reached' => $validated['won'] ? 1 : 0,
                        'ship_used' => 'pool',
                        'weapon_used' => 'default',
                        'playtime_seconds' => 0,
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'บันทึกผลสำเร็จ!',
                    'remaining_balance' => $remainingBalance,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }
}
