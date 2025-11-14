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
        // ตรวจสอบว่า user login หรือไม่
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => true,
                'authenticated' => false,
                'balance' => 0,
                'can_save_score' => false,
                'login_url' => '/login',
                'register_url' => '/register',
            ]);
        }

        // ดึงข้อมูล wallet
        $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
        $balance = $wallet ? $wallet->balance : 0;

        // ตรวจสอบว่ามียอดเงินพอบันทึกคะแนนหรือไม่ (ต้องการ 1 แต้ม)
        $requiredPoints = 1;
        $canSave = $balance >= $requiredPoints;

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'balance' => (float) $balance,
            'can_save_score' => $canSave,
            'required_points' => $requiredPoints,
            'topup_url' => '/user/wallet/topup',
        ]);
    }

    /**
     * บันทึก skin preference ของสมาชิก
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveSkinPreference(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'skin' => 'required|string|max:50',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาเข้าสู่ระบบก่อน',
                ], 401);
            }

            // ดึง game preferences ปัจจุบัน (JSON)
            $preferences = $user->game_preferences ?? [];
            if (is_string($preferences)) {
                $preferences = json_decode($preferences, true) ?? [];
            }

            // อัปเดตสี skin ของเกม Snake.io
            $preferences['snake_io'] = [
                'skin' => $validated['skin'],
                'updated_at' => now()->toIso8601String(),
            ];

            // บันทึกกลับเข้า database
            $user->game_preferences = $preferences;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'บันทึกสี skin สำเร็จ',
                'skin' => $validated['skin'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดึง skin preference ของสมาชิก
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSkinPreference(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาเข้าสู่ระบบก่อน',
                ], 401);
            }

            // ดึง game preferences
            $preferences = $user->game_preferences ?? [];
            if (is_string($preferences)) {
                $preferences = json_decode($preferences, true) ?? [];
            }

            // ดึงสี skin ของเกม Snake.io
            $snakeSkin = $preferences['snake_io']['skin'] ?? 'classic';

            return response()->json([
                'success' => true,
                'skin' => $snakeSkin,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                'skin' => 'classic', // default
            ], 500);
        }
    }
}
