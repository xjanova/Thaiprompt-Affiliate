<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\GameRoomItem;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\GameRoomManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * SnakeGameController
 *
 * จัดการ API endpoints สำหรับเกม Snake.io Multiplayer
 */
class SnakeGameController extends Controller
{
    protected GameRoomManager $roomManager;

    public function __construct(GameRoomManager $roomManager)
    {
        $this->roomManager = $roomManager;
    }

    /**
     * เข้าร่วมเกม - ค้นหาหรือสร้างห้อง
     *
     * POST /api/games/snake-io/join
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function join(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_name' => 'required|string|max:20',
            'skin_slug' => 'nullable|string|in:classic,fire,ice,gold,rainbow',
        ]);

        try {
            // ดึงเกม Snake.io
            $game = Game::where('slug', 'snake-io')->firstOrFail();

            // ค้นหาหรือสร้างห้องที่ว่าง
            $room = $this->roomManager->findOrCreateAvailableRoom($game->id);

            // ผู้เล่นเข้าร่วมห้อง
            $player = $this->roomManager->joinRoom(
                $room,
                Auth::id(),
                $validated['player_name'],
                $validated['skin_slug'] ?? 'classic'
            );

            // ดึงสถานะห้อง
            $roomState = $this->roomManager->getRoomState($room->id);

            return response()->json([
                'success' => true,
                'room_id' => $room->id,
                'room_code' => $room->room_code,
                'player_id' => $player->id,
                'room_state' => $roomState,
                'message' => 'เข้าร่วมห้องสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ออกจากห้อง
     *
     * POST /api/games/snake-io/leave
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function leave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|integer|exists:game_room_players,id',
        ]);

        try {
            $player = GameRoomPlayer::findOrFail($validated['player_id']);

            // ตรวจสอบว่าเป็นผู้เล่นคนเดียวกัน
            if (Auth::check() && $player->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่มีสิทธิ์',
                ], 403);
            }

            $this->roomManager->leaveRoom($player);

            return response()->json([
                'success' => true,
                'message' => 'ออกจากห้องสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * อัปเดตสถานะผู้เล่น (ตำแหน่ง, ทิศทาง, คะแนน)
     *
     * POST /api/games/snake-io/update-state
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateState(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|integer|exists:game_room_players,id',
            'position' => 'required|array',
            'position.x' => 'required|numeric',
            'position.y' => 'required|numeric',
            'position.z' => 'required|numeric',
            'direction' => 'required|array',
            'direction.x' => 'required|numeric',
            'direction.y' => 'required|numeric',
            'direction.z' => 'required|numeric',
            'score' => 'required|integer|min:0',
            'length' => 'required|integer|min:1',
        ]);

        try {
            $player = GameRoomPlayer::findOrFail($validated['player_id']);

            $this->roomManager->updatePlayerState(
                $player,
                $validated['position'],
                $validated['direction'],
                $validated['score'],
                $validated['length']
            );

            return response()->json([
                'success' => true,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ผู้เล่นตาย
     *
     * POST /api/games/snake-io/player-died
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function playerDied(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|integer|exists:game_room_players,id',
        ]);

        try {
            $player = GameRoomPlayer::findOrFail($validated['player_id']);

            $this->roomManager->playerDied($player);

            return response()->json([
                'success' => true,
                'final_score' => $player->score,
                'final_length' => $player->length,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * เก็บไอเทม
     *
     * POST /api/games/snake-io/collect-item
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function collectItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|integer|exists:game_room_players,id',
            'item_id' => 'required|integer|exists:game_room_items,id',
        ]);

        try {
            $player = GameRoomPlayer::findOrFail($validated['player_id']);
            $item = $this->roomManager->collectItem($player, $validated['item_id']);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไอเทมไม่พร้อมเก็บหรือหมดอายุแล้ว',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'item' => [
                    'type' => $item->item_type,
                    'value' => $item->value,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ดึงสถานะห้อง (สำหรับ sync)
     *
     * GET /api/games/snake-io/room-state/{roomId}
     *
     * @param int $roomId
     * @return JsonResponse
     */
    public function getRoomState(int $roomId): JsonResponse
    {
        try {
            $roomState = $this->roomManager->getRoomState($roomId);

            return response()->json([
                'success' => true,
                'room_state' => $roomState,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * บันทึกคะแนนและหักแต้ม wallet
     *
     * POST /api/games/snake-io/save-score
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveScore(Request $request): JsonResponse
    {
        // ต้อง login ก่อน
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเข้าสู่ระบบก่อนบันทึกคะแนน',
                'login_url' => route('login'),
                'register_url' => route('register'),
            ], 401);
        }

        $validated = $request->validate([
            'player_id' => 'required|integer|exists:game_room_players,id',
            'score' => 'required|integer|min:0',
            'length' => 'required|integer|min:1',
        ]);

        try {
            $user = Auth::user();

            // ตรวจสอบ wallet
            $wallet = Wallet::where('user_id', $user->id)->first();

            if (!$wallet || $wallet->balance < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'แต้มไม่เพียงพอ ต้องการ 1 แต้มในการบันทึกคะแนน',
                    'current_balance' => $wallet ? $wallet->balance : 0,
                    'topup_url' => route('user.wallet.index'),
                    'need_points' => 1,
                ], 400);
            }

            DB::beginTransaction();

            try {
                // หักแต้มจาก wallet
                $wallet->decrement('balance', 1);

                // บันทึก transaction
                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'expense',
                    'amount' => 1,
                    'description' => 'บันทึกคะแนนเกม Snake.io - Score: ' . $validated['score'],
                    'status' => 'completed',
                ]);

                // บันทึกคะแนนลง leaderboard (ใช้ GameController logic)
                $game = Game::where('slug', 'snake-io')->firstOrFail();

                \App\Models\GameLeaderboard::create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                    'score' => $validated['score'],
                    'wave_reached' => 1,
                    'ship_used' => 'snake',
                    'weapon_used' => 'default',
                    'playtime_seconds' => 0,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'บันทึกคะแนนสำเร็จ!',
                    'remaining_balance' => $wallet->balance,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ตรวจสอบ wallet balance
     *
     * GET /api/games/snake-io/check-wallet
     *
     * @return JsonResponse
     */
    public function checkWallet(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'authenticated' => false,
                'login_url' => route('login'),
                'register_url' => route('register'),
            ]);
        }

        $user = Auth::user();
        $wallet = Wallet::where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'balance' => $wallet ? $wallet->balance : 0,
            'can_save_score' => $wallet && $wallet->balance >= 1,
            'topup_url' => route('user.wallet.index'),
        ]);
    }
}
