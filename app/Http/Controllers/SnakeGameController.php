<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\VideoCoin;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * SnakeGameController
 *
 * จัดการ API endpoints สำหรับเกม Snake.io
 * เฉพาะ save score, wallet check, skin preference
 * (multiplayer ย้ายไป Dedicated Game Server แล้ว)
 */
class SnakeGameController extends Controller
{
    /**
     * บันทึกคะแนนและหักแต้ม wallet
     *
     * POST /api/games/snake-io/save-score
     */
    public function saveScore(Request $request): JsonResponse
    {
        // ต้อง login ก่อน
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเข้าสู่ระบบก่อนบันทึกคะแนน',
                'login_url' => route('login'),
                'register_url' => route('register'),
            ], 401);
        }

        $validated = $request->validate([
            'score' => 'required|integer|min:0',
            'length' => 'required|integer|min:1',
            'rank' => 'nullable|integer|min:0',
            'payment_method' => 'nullable|string|in:wallet,coin',
            'player_name' => 'nullable|string|max:50',
        ]);

        // default เป็น wallet ถ้าไม่ระบุ
        $paymentMethod = $validated['payment_method'] ?? 'wallet';

        try {
            $user = Auth::user();

            // ตรวจสอบ balance ตามวิธีการจ่าย
            if ($paymentMethod === 'coin') {
                $videoCoin = VideoCoin::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0, 'lifetime_earned' => 0, 'lifetime_spent' => 0, 'lifetime_exchanged' => 0]
                );

                if ($videoCoin->balance < 1) {
                    return response()->json([
                        'success' => false,
                        'message' => '🪙 เหรียญไม่เพียงพอ!\n\nต้องการ 1 เหรียญในการบันทึกคะแนน\nคุณมี '.number_format($videoCoin->balance, 2).' เหรียญ\n\nทำภารกิจหรือดูวิดีโอเพื่อรับเหรียญ',
                        'current_balance' => $videoCoin->balance,
                        'need_points' => 1,
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
                        'message' => '💰 แต้มไม่เพียงพอ!\n\nต้องการ 1 แต้มในการบันทึกคะแนน\nคุณมี '.$wallet->balance.' แต้ม\n\nกรุณาเติมแต้มหรือทำภารกิจเพื่อรับแต้มฟรี',
                        'current_balance' => $wallet->balance,
                        'topup_url' => route('user.wallet.index'),
                        'need_points' => 1,
                    ], 400);
                }
            }

            DB::beginTransaction();

            try {
                $remainingBalance = 0;
                $paymentLabel = '';

                if ($paymentMethod === 'coin') {
                    $videoCoin->deductCoins(
                        1,
                        'spent_shop',
                        'Game',
                        null,
                        'บันทึกคะแนนเกม Snake.io - Score: '.$validated['score']
                    );
                    $remainingBalance = $videoCoin->balance;
                    $paymentLabel = 'เหรียญ';
                } else {
                    $balanceBefore = $wallet->balance;
                    $wallet->decrement('balance', 1);

                    WalletTransaction::create([
                        'wallet_id' => $wallet->id,
                        'user_id' => $user->id,
                        'type' => 'fee',
                        'amount' => 1,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceBefore - 1,
                        'description' => 'บันทึกคะแนนเกม Snake.io - Score: '.$validated['score'],
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                    $remainingBalance = $wallet->balance;
                    $paymentLabel = 'แต้ม';
                }

                // บันทึกคะแนนลง leaderboard
                $game = Game::where('slug', 'snake-io')->firstOrFail();

                $playerName = !empty($validated['player_name'])
                    ? $validated['player_name']
                    : ($user->name ?? 'Player');

                \App\Models\GameLeaderboard::create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                    'player_name' => $playerName,
                    'score' => $validated['score'],
                    'wave_reached' => $validated['length'] ?? 1,
                    'ship_used' => 'snake',
                    'weapon_used' => 'default',
                    'playtime_seconds' => 0,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'บันทึกคะแนนสำเร็จ!',
                    'remaining_balance' => $remainingBalance,
                    'payment_method' => $paymentMethod,
                    'payment_label' => $paymentLabel,
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
     * ตรวจสอบ wallet + coin balance
     *
     * GET /api/games/snake-io/check-wallet
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
            'can_save_score' => $walletBalance >= 1 || $coinBalance >= 1,
            'topup_url' => route('user.wallet.index'),
            'coin_balance' => $coinBalance,
            'can_pay_wallet' => $walletBalance >= 1,
            'can_pay_coin' => $coinBalance >= 1,
        ]);
    }

    /**
     * บันทึก skin preference ของสมาชิก
     *
     * POST /api/games/snake-io/save-skin-preference
     */
    public function saveSkinPreference(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเข้าสู่ระบบก่อน',
            ], 401);
        }

        $validated = $request->validate([
            'skin' => 'required|string|max:50',
        ]);

        try {
            $user = Auth::user();

            $preferences = $user->game_preferences ?? [];
            if (is_string($preferences)) {
                $preferences = json_decode($preferences, true) ?? [];
            }

            $preferences['snake_io'] = [
                'skin' => $validated['skin'],
                'updated_at' => now()->toIso8601String(),
            ];

            \App\Models\User::where('id', $user->id)->update([
                'game_preferences' => json_encode($preferences),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'บันทึกสี skin สำเร็จ',
                'skin' => $validated['skin'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดึง skin preference ของสมาชิก
     *
     * GET /api/games/snake-io/get-skin-preference
     */
    public function getSkinPreference(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเข้าสู่ระบบก่อน',
                'skin' => 'classic',
            ], 401);
        }

        try {
            $user = Auth::user();

            $preferences = $user->game_preferences ?? [];
            if (is_string($preferences)) {
                $preferences = json_decode($preferences, true) ?? [];
            }

            $snakeSkin = $preferences['snake_io']['skin'] ?? 'classic';

            return response()->json([
                'success' => true,
                'skin' => $snakeSkin,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
                'skin' => 'classic',
            ], 500);
        }
    }
}
