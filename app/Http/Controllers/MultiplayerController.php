<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MultiplayerController extends Controller
{
    /**
     * Create a new game room
     */
    public function createRoom(Request $request, $slug)
    {
        $game = Game::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'max_players' => 'nullable|integer|min:2|max:50',
        ]);

        $room = GameRoom::create([
            'game_id' => $game->id,
            'room_code' => GameRoom::generateRoomCode(),
            'name' => $validated['name'] ?? 'Room ' . GameRoom::generateRoomCode(),
            'max_players' => $validated['max_players'] ?? 10,
        ]);

        return response()->json([
            'success' => true,
            'room' => $room,
        ]);
    }

    /**
     * List available rooms
     */
    public function listRooms($slug)
    {
        $game = Game::where('slug', $slug)->firstOrFail();

        $rooms = GameRoom::where('game_id', $game->id)
            ->where('status', 'waiting')
            ->where('current_players', '<', DB::raw('max_players'))
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json($rooms);
    }

    /**
     * Join a room
     */
    public function joinRoom(Request $request, $slug, $roomCode)
    {
        $game = Game::where('slug', $slug)->firstOrFail();
        $room = GameRoom::where('room_code', $roomCode)->firstOrFail();

        if (!$room->canJoin()) {
            return response()->json(['error' => 'Room is full or not available'], 400);
        }

        $validated = $request->validate([
            'player_name' => 'required|string|max:50',
            'skin_slug' => 'nullable|string',
        ]);

        // Check if user already in room
        $existingPlayer = GameRoomPlayer::where('room_id', $room->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingPlayer) {
            return response()->json([
                'success' => true,
                'player' => $existingPlayer,
                'room' => $room,
            ]);
        }

        $player = GameRoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => Auth::id(),
            'player_name' => $validated['player_name'],
            'skin_slug' => $validated['skin_slug'] ?? 'classic',
            'position' => ['x' => rand(-90, 90), 'y' => 0, 'z' => rand(-90, 90)],
            'direction' => ['x' => 1, 'y' => 0, 'z' => 0],
        ]);

        $room->updatePlayerCount();

        // Auto-start if enough players (optional)
        if ($room->current_players >= 2 && $room->status === 'waiting') {
            $room->start();
        }

        return response()->json([
            'success' => true,
            'player' => $player,
            'room' => $room->fresh(),
        ]);
    }

    /**
     * Leave room
     */
    public function leaveRoom($slug, $roomCode)
    {
        $room = GameRoom::where('room_code', $roomCode)->firstOrFail();

        $player = GameRoomPlayer::where('room_id', $room->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($player) {
            $player->disconnect();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Update player position
     */
    public function updatePosition(Request $request, $slug, $roomCode)
    {
        $room = GameRoom::where('room_code', $roomCode)->firstOrFail();

        $validated = $request->validate([
            'position' => 'required|array',
            'position.x' => 'required|numeric',
            'position.y' => 'required|numeric',
            'position.z' => 'required|numeric',
            'direction' => 'required|array',
            'direction.x' => 'required|numeric',
            'direction.y' => 'required|numeric',
            'direction.z' => 'required|numeric',
            'score' => 'nullable|integer',
            'length' => 'nullable|integer',
        ]);

        $player = GameRoomPlayer::where('room_id', $room->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $player->updatePosition($validated['position'], $validated['direction']);

        if (isset($validated['score'])) {
            $player->updateScore($validated['score'], $validated['length'] ?? 3);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get room state (all players)
     */
    public function getRoomState($slug, $roomCode)
    {
        $room = GameRoom::where('room_code', $roomCode)->firstOrFail();

        $players = GameRoomPlayer::where('room_id', $room->id)
            ->where('status', 'active')
            ->where('last_update', '>=', now()->subSeconds(10))
            ->get();

        return response()->json([
            'room' => $room,
            'players' => $players,
        ]);
    }

    /**
     * Report player death
     */
    public function reportDeath(Request $request, $slug, $roomCode)
    {
        $room = GameRoom::where('room_code', $roomCode)->firstOrFail();

        $player = GameRoomPlayer::where('room_id', $room->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $player->die();

        return response()->json(['success' => true]);
    }
}
