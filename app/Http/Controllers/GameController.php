<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\UserGameProgress;
use App\Models\GameLeaderboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    /**
     * Display games lobby
     */
    public function index()
    {
        $games = Game::where('is_active', true)->get();

        $userProgress = [];
        if (Auth::check()) {
            $userProgress = UserGameProgress::where('user_id', Auth::id())
                ->get()
                ->keyBy('game_id');
        }

        return view('games.index', compact('games', 'userProgress'));
    }

    /**
     * Show specific game
     */
    public function show($slug)
    {
        $game = Game::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // Check if auth required
        if ($game->requires_auth && !Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to play this game.');
        }

        // Get user progress
        $progress = null;
        $unlockedShips = ['basic'];
        $unlockedWeapons = ['basic'];

        if (Auth::check()) {
            $progress = UserGameProgress::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'game_id' => $game->id,
                ],
                [
                    'unlocked_ships' => ['basic'],
                    'unlocked_weapons' => ['basic'],
                    'current_ship' => 'basic',
                    'current_weapon' => 'basic',
                ]
            );

            $unlockedShips = $progress->unlocked_ships ?? ['basic'];
            $unlockedWeapons = $progress->unlocked_weapons ?? ['basic'];
        }

        // Get leaderboard
        $leaderboard = GameLeaderboard::where('game_id', $game->id)
            ->with('user:id,name,profile_picture')
            ->orderBy('score', 'desc')
            ->limit(10)
            ->get();

        return view('games.space-shooter', compact(
            'game',
            'progress',
            'unlockedShips',
            'unlockedWeapons',
            'leaderboard'
        ));
    }

    /**
     * Save game progress (API)
     */
    public function saveProgress(Request $request, $slug)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $game = Game::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'score' => 'required|integer',
            'wave' => 'required|integer',
            'kills' => 'integer',
            'bosses' => 'integer',
            'powerups' => 'integer',
            'playtime' => 'integer',
            'ship_used' => 'string',
            'weapon_used' => 'string',
        ]);

        $progress = UserGameProgress::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'game_id' => $game->id,
            ]
        );

        // Update stats
        $progress->updateStats($validated);

        // Save to leaderboard if score is good
        GameLeaderboard::create([
            'user_id' => Auth::id(),
            'game_id' => $game->id,
            'score' => $validated['score'],
            'wave_reached' => $validated['wave'],
            'ship_used' => $validated['ship_used'] ?? 'basic',
            'weapon_used' => $validated['weapon_used'] ?? 'basic',
            'playtime_seconds' => $validated['playtime'] ?? 0,
        ]);

        // Check for unlocks
        $unlocks = $this->checkUnlocks($progress, $validated);

        return response()->json([
            'success' => true,
            'progress' => $progress->fresh(),
            'unlocks' => $unlocks,
        ]);
    }

    /**
     * Change ship/weapon
     */
    public function changeLoadout(Request $request, $slug)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $game = Game::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'ship' => 'nullable|string',
            'weapon' => 'nullable|string',
        ]);

        $progress = UserGameProgress::where('user_id', Auth::id())
            ->where('game_id', $game->id)
            ->firstOrFail();

        if (isset($validated['ship']) && $progress->hasUnlockedShip($validated['ship'])) {
            $progress->current_ship = $validated['ship'];
        }

        if (isset($validated['weapon']) && $progress->hasUnlockedWeapon($validated['weapon'])) {
            $progress->current_weapon = $validated['weapon'];
        }

        $progress->save();

        return response()->json([
            'success' => true,
            'current_ship' => $progress->current_ship,
            'current_weapon' => $progress->current_weapon,
        ]);
    }

    /**
     * Get leaderboard (API)
     */
    public function leaderboard($slug)
    {
        $game = Game::where('slug', $slug)->firstOrFail();

        $leaderboard = GameLeaderboard::where('game_id', $game->id)
            ->with('user:id,name,profile_picture')
            ->orderBy('score', 'desc')
            ->limit(100)
            ->get();

        return response()->json($leaderboard);
    }

    /**
     * Check and unlock ships/weapons based on progress
     */
    private function checkUnlocks($progress, $stats)
    {
        $unlocks = [];

        // Ship unlocks based on wave
        if ($stats['wave'] >= 5 && !$progress->hasUnlockedShip('interceptor')) {
            $progress->unlockShip('interceptor');
            $unlocks[] = ['type' => 'ship', 'item' => 'interceptor'];
        }

        if ($stats['wave'] >= 10 && !$progress->hasUnlockedShip('destroyer')) {
            $progress->unlockShip('destroyer');
            $unlocks[] = ['type' => 'ship', 'item' => 'destroyer'];
        }

        if ($stats['wave'] >= 15 && !$progress->hasUnlockedShip('titan')) {
            $progress->unlockShip('titan');
            $unlocks[] = ['type' => 'ship', 'item' => 'titan'];
        }

        // Weapon unlocks based on kills
        if ($progress->total_kills >= 50 && !$progress->hasUnlockedWeapon('laser')) {
            $progress->unlockWeapon('laser');
            $unlocks[] = ['type' => 'weapon', 'item' => 'laser'];
        }

        if ($progress->total_kills >= 100 && !$progress->hasUnlockedWeapon('spread')) {
            $progress->unlockWeapon('spread');
            $unlocks[] = ['type' => 'weapon', 'item' => 'spread'];
        }

        if ($progress->total_kills >= 200 && !$progress->hasUnlockedWeapon('missile')) {
            $progress->unlockWeapon('missile');
            $unlocks[] = ['type' => 'weapon', 'item' => 'missile'];
        }

        if ($progress->bosses_defeated >= 3 && !$progress->hasUnlockedWeapon('plasma')) {
            $progress->unlockWeapon('plasma');
            $unlocks[] = ['type' => 'weapon', 'item' => 'plasma'];
        }

        return $unlocks;
    }
}
