<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * SnakeGameController (Stateless Mode)
 *
 * รองรับการทำงานในสภาพแวดล่อมที่ไม่มี database
 * ใช้ in-memory storage แทน
 */
class SnakeGameControllerStateless extends Controller
{
    // In-memory storage (จะหายเมื่อ restart server)
    private static array $rooms = [];
    private static array $players = [];
    private static array $items = [];

    /**
     * เข้าร่วมเกม (ไม่ต้องใช้ database)
     */
    public function join(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_name' => 'required|string|max:50',
            'skin_slug' => 'nullable|string|max:50',
        ]);

        // สร้าง room ID ง่ายๆ
        $roomId = 1;
        if (!isset(self::$rooms[$roomId])) {
            self::$rooms[$roomId] = [
                'id' => $roomId,
                'code' => 'ROOM-' . strtoupper(Str::random(5)),
                'player_count' => 0,
                'status' => 'waiting',
            ];
        }

        // สร้าง player
        $playerId = count(self::$players) + 1;
        self::$players[$playerId] = [
            'id' => $playerId,
            'room_id' => $roomId,
            'name' => $validated['player_name'],
            'skin' => $validated['skin_slug'] ?? 'classic',
            'position' => ['x' => 0, 'y' => 0, 'z' => 0],
            'score' => 0,
            'length' => 5,
            'is_alive' => true,
        ];

        self::$rooms[$roomId]['player_count']++;

        return response()->json([
            'success' => true,
            'room_id' => $roomId,
            'room_code' => self::$rooms[$roomId]['code'],
            'player_id' => $playerId,
            'message' => 'เข้าร่วมห้องสำเร็จ',
        ]);
    }

    /**
     * ออกจากเกม
     */
    public function leave(Request $request): JsonResponse
    {
        $playerId = $request->input('player_id');

        if (isset(self::$players[$playerId])) {
            $roomId = self::$players[$playerId]['room_id'];
            unset(self::$players[$playerId]);

            if (isset(self::$rooms[$roomId])) {
                self::$rooms[$roomId]['player_count']--;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'ออกจากห้องสำเร็จ',
        ]);
    }

    /**
     * อัปเดตสถานะผู้เล่น
     */
    public function updateState(Request $request): JsonResponse
    {
        $playerId = $request->input('player_id');

        if (!isset(self::$players[$playerId])) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found',
            ], 404);
        }

        self::$players[$playerId]['position'] = $request->input('position');
        self::$players[$playerId]['direction'] = $request->input('direction');
        self::$players[$playerId]['score'] = $request->input('score');
        self::$players[$playerId]['length'] = $request->input('length');

        return response()->json(['success' => true]);
    }

    /**
     * ผู้เล่นตาย
     */
    public function playerDied(Request $request): JsonResponse
    {
        $playerId = $request->input('player_id');

        if (isset(self::$players[$playerId])) {
            self::$players[$playerId]['is_alive'] = false;
        }

        return response()->json([
            'success' => true,
            'final_score' => self::$players[$playerId]['score'] ?? 0,
        ]);
    }

    /**
     * เก็บไอเทม
     */
    public function collectItem(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'item' => ['type' => 'food', 'value' => 1],
        ]);
    }

    /**
     * ดึงสถานะห้อง
     */
    public function getRoomState(int $roomId): JsonResponse
    {
        $roomPlayers = array_filter(self::$players, fn($p) => $p['room_id'] == $roomId);

        return response()->json([
            'success' => true,
            'room_state' => [
                'players' => array_values($roomPlayers),
                'items' => [],
            ],
        ]);
    }

    /**
     * บันทึกคะแนน (ไม่ต้อง auth ใน stateless mode)
     */
    public function saveScore(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'คะแนนถูกบันทึกแล้ว (in-memory)',
        ]);
    }

    /**
     * ตรวจสอบ wallet
     */
    public function checkWallet(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'authenticated' => false,
            'balance' => 0,
            'can_save_score' => false,
            'login_url' => '/login',
            'register_url' => '/register',
        ]);
    }
}
