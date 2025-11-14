<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameSkin;
use App\Models\GameTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GameShopController extends Controller
{
    public function __construct()
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

        // Get user's wallet balance
        $walletBalance = Auth::user()->wallet_balance ?? 0;

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
     * Purchase skin
     */
    public function purchaseSkin(Request $request, $slug, $skinId)
    {
        $game = Game::where('slug', $slug)->firstOrFail();
        $skin = GameSkin::findOrFail($skinId);
        $user = Auth::user();

        // Validate
        if ($skin->game_id !== $game->id) {
            return response()->json(['error' => 'Invalid skin for this game'], 400);
        }

        if (!$skin->isAvailable()) {
            return response()->json(['error' => 'This skin is not available'], 400);
        }

        // Check if already owned
        if ($user->gameSkins()->where('skin_id', $skin->id)->exists()) {
            return response()->json(['error' => 'You already own this skin'], 400);
        }

        // Check wallet balance
        $walletBalance = $user->wallet_balance ?? 0;

        if ($walletBalance < $skin->price) {
            return response()->json([
                'error' => 'Insufficient balance',
                'required' => $skin->price,
                'balance' => $walletBalance,
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Deduct from wallet
            $balanceBefore = $walletBalance;
            $balanceAfter = $walletBalance - $skin->price;

            $user->wallet_balance = $balanceAfter;
            $user->save();

            // Add skin to user
            $user->gameSkins()->attach($skin->id, [
                'purchase_price' => $skin->price,
                'purchased_at' => now(),
            ]);

            // Create transaction record
            GameTransaction::create([
                'user_id' => $user->id,
                'game_id' => $game->id,
                'skin_id' => $skin->id,
                'type' => 'skin_purchase',
                'amount' => -$skin->price,
                'currency' => $skin->currency,
                'status' => 'completed',
                'description' => "Purchased {$skin->name}",
                'wallet_balance_before' => $balanceBefore,
                'wallet_balance_after' => $balanceAfter,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Skin purchased successfully!',
                'skin' => $skin,
                'new_balance' => $balanceAfter,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Purchase failed: ' . $e->getMessage()], 500);
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
