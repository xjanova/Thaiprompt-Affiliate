<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameLeaderboard;
use App\Models\Game;
use App\Services\SnakeGame\SnakeGameServiceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     *
     * @var SnakeGameServiceManager
     */
    private SnakeGameServiceManager $serviceManager;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
        $this->serviceManager = app(SnakeGameServiceManager::class);
    }

    /**
     * แสดง Admin Dashboard
     *
     * GET /admin/games/snake-io/monitor
     *
     * @return View
     */
    public function dashboard(): View
    {
        $status = $this->serviceManager->getServiceStatus();
        $onlinePlayers = $this->serviceManager->getOnlinePlayers();
        $rooms = $this->serviceManager->getRooms();
        $suspicious = $this->serviceManager->getSuspiciousActivities();

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
            'rooms' => $rooms,
            'suspiciousActivities' => $suspicious,
            'topScores' => $topScores,
        ]);
    }

    /**
     * ดึงสถานะ service (API)
     *
     * GET /api/admin/games/snake-io/status
     *
     * @return JsonResponse
     */
    public function getStatus(): JsonResponse
    {
        $status = $this->serviceManager->getServiceStatus();
        $onlinePlayers = $this->serviceManager->getOnlinePlayers();
        $rooms = $this->serviceManager->getRooms();

        return response()->json([
            'success' => true,
            'status' => $status,
            'players' => array_values($onlinePlayers),
            'rooms' => array_values($rooms),
        ]);
    }

    /**
     * เปิด service
     *
     * POST /api/admin/games/snake-io/start
     *
     * @return JsonResponse
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
     *
     * @return JsonResponse
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
     *
     * @return JsonResponse
     */
    public function getOnlinePlayers(): JsonResponse
    {
        $players = $this->serviceManager->getOnlinePlayers();

        return response()->json([
            'success' => true,
            'players' => array_values($players),
            'total' => count($players),
        ]);
    }

    /**
     * ดึงรายการห้อง
     *
     * GET /api/admin/games/snake-io/rooms
     *
     * @return JsonResponse
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
     *
     * @return JsonResponse
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
     *
     * @param int $userId
     * @return JsonResponse
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
     *
     * @return JsonResponse
     */
    public function clearData(): JsonResponse
    {
        $this->serviceManager->clearAllData();

        return response()->json([
            'success' => true,
            'message' => 'All service data cleared',
        ]);
    }
}
