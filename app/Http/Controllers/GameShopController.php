<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameSkin;
use App\Models\GameTransaction;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameShopController extends Controller
{
    public function __construct(protected WalletService $walletService)
    {
        $this->middleware('auth');
    }

    /**
     * Show game shop
     */
    public function index($slug)
    {
        $game = Game::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $skins = GameSkin::where('game_id', $game->id)
            ->available()
            ->orderBy('price')
            ->get();

        // Get user's owned skins
        $ownedSkinIds = Auth::user()->gameSkins()
            ->where('game_id', $game->id)
            ->pluck('game_skins.id')
            ->toArray();

        // ยอดเงินจริงอยู่ที่ตาราง wallets (users ไม่มีคอลัมน์ wallet_balance)
        $walletBalance = (float) (Auth::user()->wallet?->balance ?? 0);

        // Get recent transactions
        $transactions = GameTransaction::where('user_id', Auth::id())
            ->where('game_id', $game->id)
            ->with('skin')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('games.shop', compact('game', 'skins', 'ownedSkinIds', 'walletBalance', 'transactions'));
    }

    /**
     * ซื้อสกินด้วยเงินใน wallet
     *
     * หักเงินผ่าน WalletService (บันทึก WalletTransaction) ภายใน transaction เดียวกับการมอบสกิน
     * กดซื้อซ้ำ: คำขอที่สองรอล็อก wallet แล้วเจอว่ามีสกินแล้ว → ไม่หักเงินซ้ำ
     */
    public function purchaseSkin(Request $request, $slug, $skinId)
    {
        $game = Game::where('slug', $slug)->firstOrFail();
        $skin = GameSkin::findOrFail($skinId);
        $user = Auth::user();

        if ($skin->game_id !== $game->id) {
            return response()->json(['error' => 'สกินนี้ไม่ได้อยู่ในเกมนี้'], 400);
        }

        if (! $skin->isAvailable()) {
            return response()->json(['error' => 'สกินนี้ไม่เปิดขายในขณะนี้'], 400);
        }

        $price = round((float) $skin->price, 2);

        try {
            return DB::transaction(function () use ($user, $game, $skin, $price) {
                // ล็อกแถว wallet ก่อน — คำขอซื้อพร้อมกันของผู้ใช้คนเดียวกันจะต่อคิวกันตรงนี้
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

                // เช็คความเป็นเจ้าของหลังได้ล็อก จึงเห็นสกินที่คำขอก่อนหน้าเพิ่งซื้อไป
                if ($user->gameSkins()->where('skin_id', $skin->id)->exists()) {
                    return response()->json(['error' => 'คุณมีสกินนี้อยู่แล้ว'], 400);
                }

                $balanceBefore = round((float) ($wallet?->balance ?? 0), 2);
                $balanceAfter = $balanceBefore;

                if ($price > 0) {
                    if ($wallet && ! $wallet->isActive()) {
                        return response()->json(['error' => 'กระเป๋าเงินของคุณใช้งานไม่ได้ในขณะนี้ กรุณาติดต่อเจ้าหน้าที่'], 403);
                    }

                    if ($balanceBefore < $price) {
                        return response()->json([
                            'error' => 'ยอดเงินในกระเป๋าไม่เพียงพอ',
                            'required' => $price,
                            'balance' => $balanceBefore,
                        ], 400);
                    }

                    $walletTransaction = $this->walletService->deductForService(
                        $wallet,
                        $price,
                        "ซื้อสกิน {$skin->name} ({$game->name})",
                        'game_skin',
                        $skin->id,
                        ['game_id' => $game->id, 'skin_id' => $skin->id]
                    );
                    $balanceAfter = round((float) $walletTransaction->balance_after, 2);
                }

                $user->gameSkins()->attach($skin->id, [
                    'purchase_price' => $price,
                    'purchased_at' => now(),
                ]);

                GameTransaction::create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                    'skin_id' => $skin->id,
                    'type' => 'skin_purchase',
                    'amount' => -$price,
                    'currency' => $skin->currency,
                    'status' => 'completed',
                    'description' => "ซื้อสกิน {$skin->name}",
                    'wallet_balance_before' => $balanceBefore,
                    'wallet_balance_after' => $balanceAfter,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'ซื้อสกินสำเร็จ!',
                    'skin' => $skin,
                    'new_balance' => $balanceAfter,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // สกินฟรี/ไม่มี wallet ให้ล็อก: คำขอซ้ำชน unique (user_id, skin_id) → transaction ถูก rollback ทั้งก้อน
            return response()->json(['error' => 'คุณมีสกินนี้อยู่แล้ว'], 400);
        } catch (\Throwable $e) {
            Log::error('Game skin purchase failed', [
                'user_id' => $user->id,
                'skin_id' => $skin->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'ซื้อสกินไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'], 500);
        }
    }

    /**
     * Get user's owned skins
     */
    public function getOwnedSkins($slug)
    {
        $game = Game::where('slug', $slug)->firstOrFail();

        $skins = Auth::user()->gameSkins()
            ->where('game_id', $game->id)
            ->withPivot(['purchase_price', 'purchased_at'])
            ->get();

        return response()->json($skins);
    }
}
