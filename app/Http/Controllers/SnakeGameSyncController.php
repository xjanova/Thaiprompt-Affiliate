<?php

namespace App\Http\Controllers;

use App\Services\SnakeGameSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Snake Game Sync Controller
 *
 * จัดการ API endpoints สำหรับ sync เกม Snake.io
 * - Lightweight และรวดเร็ว
 * - ใช้ Cache แทน database
 * - มี rate limiting
 */
class SnakeGameSyncController extends Controller
{
    protected SnakeGameSyncService $syncService;

    public function __construct(SnakeGameSyncService $syncService)
    {
        $this->syncService = $syncService;

        // Rate limiting: 120 requests/minute ต่อ IP
        $this->middleware('throttle:120,1');
    }

    /**
     * เข้าร่วมเกม (สร้าง session)
     *
     * POST /api/snake-sync/join
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function join(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_name' => 'required|string|max:20',
                'skin' => 'nullable|string|max:100',
            ]);

            // สร้าง unique player ID
            $playerId = 'player_' . Str::uuid();

            // สร้าง session
            $session = $this->syncService->createSession(
                $playerId,
                $validated['player_name'],
                $validated['skin'] ?? 'classic'
            );

            return response()->json([
                'success' => true,
                'player_id' => $playerId,
                'session' => $session,
                'message' => 'เข้าร่วมเกมสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เข้าร่วมเกมล้มเหลว: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัปเดตสถานะผู้เล่น
     *
     * POST /api/snake-sync/update
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateState(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|string',
                'position' => 'required|array',
                'position.x' => 'required|numeric',
                'position.y' => 'required|numeric',
                'position.z' => 'required|numeric',
                'direction' => 'nullable|array',
                'score' => 'required|integer|min:0',
                'length' => 'required|integer|min:1',
                'is_alive' => 'nullable|boolean',
            ]);

            $success = $this->syncService->updatePlayerState(
                $validated['player_id'],
                [
                    'position' => $validated['position'],
                    'direction' => $validated['direction'] ?? ['x' => 1, 'y' => 0, 'z' => 0],
                    'score' => $validated['score'],
                    'length' => $validated['length'],
                    'is_alive' => $validated['is_alive'] ?? true,
                ]
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'อัปเดตสถานะล้มเหลว',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'อัปเดตสถานะล้มเหลว: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดึงผู้เล่น active ทั้งหมด (ไม่รวมตัวเอง)
     *
     * GET /api/snake-sync/players/{playerId}
     *
     * @param string $playerId
     * @return JsonResponse
     */
    public function getActivePlayers(string $playerId): JsonResponse
    {
        try {
            $players = $this->syncService->getActivePlayers($playerId, 10);

            return response()->json([
                'success' => true,
                'players' => $players,
                'count' => count($players),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ดึงข้อมูลผู้เล่นล้มเหลว',
                'players' => [],
                'count' => 0,
            ], 500);
        }
    }

    /**
     * แจ้งว่าผู้เล่นตาย
     *
     * POST /api/snake-sync/died
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function playerDied(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|string',
            ]);

            $success = $this->syncService->playerDied($validated['player_id']);

            return response()->json([
                'success' => $success,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'แจ้งการตายล้มเหลว',
            ], 500);
        }
    }

    /**
     * ออกจากเกม
     *
     * POST /api/snake-sync/leave
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function leave(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|string',
            ]);

            $success = $this->syncService->leaveGame($validated['player_id']);

            return response()->json([
                'success' => $success,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ออกจากเกมล้มเหลว',
            ], 500);
        }
    }

    /**
     * Ping เพื่อรักษา session
     *
     * POST /api/snake-sync/ping
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ping(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|string',
            ]);

            $success = $this->syncService->pingSession($validated['player_id']);

            return response()->json([
                'success' => $success,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
            ], 500);
        }
    }

    /**
     * ดึงสถิติ (จำนวนผู้เล่น active)
     *
     * GET /api/snake-sync/stats
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $activeCount = $this->syncService->getActivePlayerCount();

            return response()->json([
                'success' => true,
                'active_players' => $activeCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'active_players' => 0,
            ]);
        }
    }
}
