<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\UserGameProgress;
use App\Models\GameLeaderboard;
use App\Models\GameAchievement;
use App\Models\LeaderboardSeason;
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

        // Route to appropriate game view
        if ($slug === 'snake-io') {
            return $this->showSnakeIo($game);
        }

        // Space Shooter or other games
        return $this->showSpaceShooter($game);
    }

    private function showSnakeIo($game)
    {
        // Get leaderboard
        $leaderboard = GameLeaderboard::where('game_id', $game->id)
            ->with('user:id,name,profile_picture')
            ->orderBy('score', 'desc')
            ->limit(10)
            ->get();

        return view('games.snake-io', compact('game', 'leaderboard'));
    }

    private function showSpaceShooter($game)
    {
        $progress = null;
        $unlockedShips = ['basic'];
        $unlockedWeapons = ['basic'];

        if (Auth::check()) {
            $progress = UserGameProgress::firstOrCreate(
                ['user_id' => Auth::id(), 'game_id' => $game->id],
                ['unlocked_ships' => ['basic'], 'unlocked_weapons' => ['basic']]
            );

            $unlockedShips = $progress->unlocked_ships ?? ['basic'];
            $unlockedWeapons = $progress->unlocked_weapons ?? ['basic'];
        }

        $leaderboard = GameLeaderboard::where('game_id', $game->id)
            ->with('user:id,name,profile_picture')
            ->orderBy('score', 'desc')
            ->limit(10)
            ->get();

        return view('games.space-shooter', compact('game', 'progress', 'unlockedShips', 'unlockedWeapons', 'leaderboard'));
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

        // Get active season
        $activeSeason = LeaderboardSeason::where('game_id', $game->id)
            ->where('status', 'active')
            ->first();

        // Save to leaderboard if score is good
        GameLeaderboard::create([
            'user_id' => Auth::id(),
            'game_id' => $game->id,
            'season_id' => $activeSeason?->id,
            'score' => $validated['score'],
            'wave_reached' => $validated['wave'],
            'ship_used' => $validated['ship_used'] ?? 'basic',
            'weapon_used' => $validated['weapon_used'] ?? 'basic',
            'playtime_seconds' => $validated['playtime'] ?? 0,
        ]);

        // Check for unlocks
        $unlocks = $this->checkUnlocks($progress, $validated);

        // Check for achievement unlocks
        $achievementUnlocks = $this->checkAchievements($game, Auth::user(), $validated);

        return response()->json([
            'success' => true,
            'progress' => $progress->fresh(),
            'unlocks' => $unlocks,
            'achievements' => $achievementUnlocks,
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
     * Show achievements page
     */
    public function achievements($slug)
    {
        $game = Game::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // Get all achievements for this game
        $achievements = GameAchievement::where('game_id', $game->id)
            ->orderBy('requirement')
            ->get();

        // Get user's unlocked achievements
        $unlockedAchievementIds = [];
        if (Auth::check()) {
            $unlockedAchievementIds = Auth::user()->achievements()
                ->whereIn('achievement_id', $achievements->pluck('id'))
                ->pluck('achievement_id')
                ->toArray();
        }

        return view('games.achievements', compact('game', 'achievements', 'unlockedAchievementIds'));
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

    /**
     * Check and unlock achievements based on stats
     */
    private function checkAchievements($game, $user, $stats)
    {
        $achievementUnlocks = [];

        // Get all achievements for this game
        $achievements = GameAchievement::where('game_id', $game->id)->get();

        foreach ($achievements as $achievement) {
            // Check if user already has this achievement
            if ($user->achievements()->where('achievement_id', $achievement->id)->exists()) {
                continue;
            }

            $shouldUnlock = false;
            $value = 0;

            // Check based on achievement type
            switch ($achievement->type) {
                case 'score':
                    $value = $stats['score'] ?? 0;
                    $shouldUnlock = $value >= $achievement->requirement;
                    break;

                case 'wave':
                    $value = $stats['wave'] ?? 0;
                    $shouldUnlock = $value >= $achievement->requirement;
                    break;

                case 'kills':
                    $value = $stats['kills'] ?? 0;
                    $shouldUnlock = $value >= $achievement->requirement;
                    break;

                case 'boss':
                    $value = $stats['bosses'] ?? 0;
                    $shouldUnlock = $value >= $achievement->requirement;
                    break;

                case 'time':
                    $value = $stats['playtime'] ?? 0;
                    $shouldUnlock = $value >= $achievement->requirement;
                    break;
            }

            if ($shouldUnlock) {
                // Unlock achievement
                $user->achievements()->attach($achievement->id, [
                    'unlocked_at' => now()
                ]);

                // Award points if configured
                if ($achievement->reward_points > 0) {
                    // You can add a points system here if needed
                    // $user->increment('points', $achievement->reward_points);
                }

                $achievementUnlocks[] = [
                    'id' => $achievement->id,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'reward_points' => $achievement->reward_points,
                ];
            }
        }

        return $achievementUnlocks;
    }
}
