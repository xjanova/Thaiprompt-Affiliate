<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameLeaderboard;
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
        $onlinePlayers = $this->serviceManager->getOnlinePlayers();
        $rooms = $this->serviceManager->getRooms();
        $suspicious = $this->serviceManager->getSuspiciousActivities();

        // ✅ รวมผู้เล่นจาก Sync Service (cache-based real-time sync)
        $syncPlayers = $this->syncService->getActivePlayers('', 50);
        $syncPlayerCount = $this->syncService->getActivePlayerCount();

        // อัปเดตจำนวนผู้เล่นใน status
        $status['total_sync_players'] = $syncPlayerCount;
        $status['total_players'] = max($status['total_players'], $syncPlayerCount);

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
            'onlinePlayers' => $onlinePlayers,
            'syncPlayers' => $syncPlayers,
            'rooms' => $rooms,
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
        $onlinePlayers = $this->serviceManager->getOnlinePlayers();
        $rooms = $this->serviceManager->getRooms();

        // ✅ รวมผู้เล่นจาก Sync Service (cache-based)
        $syncPlayers = $this->syncService->getActivePlayers('', 50);
        $syncPlayerCount = $this->syncService->getActivePlayerCount();
        $status['total_sync_players'] = $syncPlayerCount;
        $status['total_players'] = max($status['total_players'], $syncPlayerCount);

        return response()->json([
            'success' => true,
            'status' => $status,
            'players' => array_values($onlinePlayers),
            'sync_players' => $syncPlayers,
            'rooms' => array_values($rooms),
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
        $players = $this->serviceManager->getOnlinePlayers();

        // ✅ รวมผู้เล่นจาก Sync Service
        $syncPlayers = $this->syncService->getActivePlayers('', 50);

        return response()->json([
            'success' => true,
            'players' => array_values($players),
            'sync_players' => $syncPlayers,
            'total' => count($players) + count($syncPlayers),
        ]);
    }

    /**
     * ดึงรายการห้อง
     *
     * GET /api/admin/games/snake-io/rooms
     */
    public function getRooms(): JsonResponse
    {
        $rooms = $this->serviceManager->getRooms();

        return response()->json([
            'success' => true,
            'rooms' => array_values($rooms),
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
