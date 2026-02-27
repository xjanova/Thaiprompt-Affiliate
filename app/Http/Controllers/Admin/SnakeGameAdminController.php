<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameLeaderboard;
use App\Models\GameSetting;
use App\Services\SnakeGame\SnakeGameServiceManager;
use App\Services\SnakeGameSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Snake Game Admin Controller
 *
 * จัดการ admin dashboard สำหรับ Snake.io multiplayer service
 */
class SnakeGameAdminController extends Controller
{
    /**
     * Service Manager instance
     */
    private SnakeGameServiceManager $serviceManager;

    /**
     * Sync Service instance (สำหรับดึงข้อมูลผู้เล่นจริงจาก cache-based sync)
     */
    private SnakeGameSyncService $syncService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
        $this->serviceManager = app(SnakeGameServiceManager::class);
        $this->syncService = app(SnakeGameSyncService::class);
    }

    /**
     * แสดง Admin Dashboard
     *
     * GET /admin/games/snake-io/monitor
     */
    public function dashboard(): View
    {
        $status = $this->serviceManager->getServiceStatus();
        $suspicious = $this->serviceManager->getSuspiciousActivities();

        // ✅ ดึงข้อมูลจาก SyncService (ข้อมูลจริง)
        $syncPlayers = $this->syncService->getAllActivePlayers(100);
        $syncPlayerCount = $this->syncService->getActivePlayerCount();
        $syncRooms = $this->syncService->getActiveRooms();

        // อัปเดตจำนวนใน status ให้ถูกต้อง
        $status['total_sync_players'] = $syncPlayerCount;
        $status['total_players'] = $syncPlayerCount;
        $status['total_rooms'] = count($syncRooms);

        // ✅ ดึง server limits จาก GameSettings
        $limits = [
            'max_rooms' => (int) GameSetting::get('snake_io_max_rooms', 20),
            'max_total_players' => (int) GameSetting::get('snake_io_max_total_players', 200),
            'max_players_per_room' => (int) GameSetting::get('snake_io_max_players_per_room', 30),
        ];

        // ดึง top scores
        $game = Game::where('slug', 'snake-io')->first();
        $topScores = [];
        if ($game) {
            $topScores = GameLeaderboard::where('game_id', $game->id)
                ->with('user')
                ->orderBy('score', 'desc')
                ->take(20)
                ->get();
        }

        return view('admin.games.snake-io.monitor', [
            'pageTitle' => 'Snake.io Monitor',
            'status' => $status,
            'syncPlayers' => $syncPlayers,
            'syncRooms' => $syncRooms,
            'limits' => $limits,
            'suspiciousActivities' => $suspicious,
            'topScores' => $topScores,
        ]);
    }

    /**
     * ดึงสถานะ service (API)
     *
     * GET /api/admin/games/snake-io/status
     */
    public function getStatus(): JsonResponse
    {
        $status = $this->serviceManager->getServiceStatus();

        // ✅ ดึงข้อมูลจาก SyncService (ข้อมูลจริง)
        $syncPlayers = $this->syncService->getAllActivePlayers(100);
        $syncPlayerCount = $this->syncService->getActivePlayerCount();
        $syncRooms = $this->syncService->getActiveRooms();

        $status['total_sync_players'] = $syncPlayerCount;
        $status['total_players'] = $syncPlayerCount;
        $status['total_rooms'] = count($syncRooms);

        // ✅ ดึง server limits
        $limits = [
            'max_rooms' => (int) GameSetting::get('snake_io_max_rooms', 20),
            'max_total_players' => (int) GameSetting::get('snake_io_max_total_players', 200),
            'max_players_per_room' => (int) GameSetting::get('snake_io_max_players_per_room', 30),
        ];

        return response()->json([
            'success' => true,
            'status' => $status,
            'sync_players' => $syncPlayers,
            'rooms' => $syncRooms,
            'limits' => $limits,
        ]);
    }

    /**
     * เปิด service
     *
     * POST /api/admin/games/snake-io/start
     */
    public function startService(): JsonResponse
    {
        $result = $this->serviceManager->startService();

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => '✅ Service started - ผู้เล่นจะเข้าสู่ Online Mode',
        ]);
    }

    /**
     * ปิด service
     *
     * POST /api/admin/games/snake-io/stop
     */
    public function stopService(): JsonResponse
    {
        $result = $this->serviceManager->stopService();

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => '⏸️ Service stopped - ผู้เล่นจะเล่น Offline Mode (กับ bots)',
        ]);
    }

    /**
     * ดึงรายการผู้เล่น online
     *
     * GET /api/admin/games/snake-io/players
     */
    public function getOnlinePlayers(): JsonResponse
    {
        // ✅ ดึงจาก SyncService (ข้อมูลจริง)
        $syncPlayers = $this->syncService->getAllActivePlayers(100);

        return response()->json([
            'success' => true,
            'sync_players' => $syncPlayers,
            'total' => count($syncPlayers),
        ]);
    }

    /**
     * ดึงรายการห้อง
     *
     * GET /api/admin/games/snake-io/rooms
     */
    public function getRooms(): JsonResponse
    {
        // ✅ ดึงจาก SyncService แทน ServiceManager
        $rooms = $this->syncService->getActiveRooms();

        return response()->json([
            'success' => true,
            'rooms' => $rooms,
            'total' => count($rooms),
        ]);
    }

    /**
     * ดึงกิจกรรมน่าสงสัย
     *
     * GET /api/admin/games/snake-io/suspicious
     */
    public function getSuspiciousActivities(): JsonResponse
    {
        $activities = $this->serviceManager->getSuspiciousActivities();

        return response()->json([
            'success' => true,
            'activities' => $activities,
            'total' => count($activities),
        ]);
    }

    /**
     * Kick ผู้เล่นออกจากระบบ
     *
     * POST /api/admin/games/snake-io/kick/{userId}
     */
    public function kickPlayer(int $userId): JsonResponse
    {
        $this->serviceManager->removeOnlinePlayer($userId);

        return response()->json([
            'success' => true,
            'message' => "Kicked player {$userId} from the game",
        ]);
    }

    /**
     * ล้างข้อมูลทั้งหมด
     *
     * POST /api/admin/games/snake-io/clear-data
     */
    public function clearData(): JsonResponse
    {
        $this->serviceManager->clearAllData();

        // ✅ ล้างข้อมูล Sync Service ด้วย
        $this->syncService->cleanup();

        return response()->json([
            'success' => true,
            'message' => 'All service data cleared (including sync data)',
        ]);
    }
}
