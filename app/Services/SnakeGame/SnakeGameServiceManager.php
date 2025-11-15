<?php

namespace App\Services\SnakeGame;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Snake Game Service Manager
 *
 * จัดการสถานะของ multiplayer service, rooms, และ players
 * ใช้ Singleton pattern เพื่อให้มี instance เดียวในระบบ
 */
class SnakeGameServiceManager
{
    /**
     * Cache keys
     */
    private const CACHE_SERVICE_STATUS = 'snake_game:service:status';
    private const CACHE_ONLINE_PLAYERS = 'snake_game:online_players';
    private const CACHE_ROOMS = 'snake_game:rooms';
    private const CACHE_SUSPICIOUS_ACTIVITIES = 'snake_game:suspicious';

    /**
     * Service status
     */
    private const STATUS_ONLINE = 'online';
    private const STATUS_OFFLINE = 'offline';

    /**
     * ตรวจสอบว่า service เปิดอยู่หรือไม่
     *
     * @return bool
     */
    public function isOnline(): bool
    {
        return Cache::get(self::CACHE_SERVICE_STATUS, self::STATUS_OFFLINE) === self::STATUS_ONLINE;
    }

    /**
     * เปิด service (เข้าสู่ online mode)
     *
     * @return array
     */
    public function startService(): array
    {
        Cache::put(self::CACHE_SERVICE_STATUS, self::STATUS_ONLINE, now()->addDays(7));

        Log::info('Snake Game Service started', [
            'timestamp' => now()->toDateTimeString(),
        ]);

        return [
            'status' => 'online',
            'message' => 'Service started successfully',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * ปิด service (เข้าสู่ offline mode)
     *
     * ผู้เล่นจะเล่นกับ bots อย่างเดียว
     *
     * @return array
     */
    public function stopService(): array
    {
        Cache::put(self::CACHE_SERVICE_STATUS, self::STATUS_OFFLINE, now()->addDays(7));

        Log::info('Snake Game Service stopped', [
            'timestamp' => now()->toDateTimeString(),
        ]);

        return [
            'status' => 'offline',
            'message' => 'Service stopped - players will switch to offline mode',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * ดึงสถานะ service
     *
     * @return array
     */
    public function getServiceStatus(): array
    {
        $status = $this->isOnline() ? self::STATUS_ONLINE : self::STATUS_OFFLINE;
        $onlinePlayers = $this->getOnlinePlayers();
        $rooms = $this->getRooms();

        return [
            'status' => $status,
            'is_online' => $this->isOnline(),
            'total_players' => count($onlinePlayers),
            'total_rooms' => count($rooms),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * เพิ่มผู้เล่น online
     *
     * @param int $userId
     * @param string $username
     * @param string $ipAddress
     * @param array $metadata
     * @return void
     */
    public function addOnlinePlayer(int $userId, string $username, string $ipAddress, array $metadata = []): void
    {
        $players = $this->getOnlinePlayers();

        $players[$userId] = [
            'user_id' => $userId,
            'username' => $username,
            'ip_address' => $ipAddress,
            'joined_at' => now()->toIso8601String(),
            'last_activity' => now()->toIso8601String(),
            'room_id' => $metadata['room_id'] ?? null,
            'score' => $metadata['score'] ?? 0,
            'length' => $metadata['length'] ?? 5,
        ];

        Cache::put(self::CACHE_ONLINE_PLAYERS, $players, now()->addMinutes(30));
    }

    /**
     * ลบผู้เล่น online
     *
     * @param int $userId
     * @return void
     */
    public function removeOnlinePlayer(int $userId): void
    {
        $players = $this->getOnlinePlayers();
        unset($players[$userId]);
        Cache::put(self::CACHE_ONLINE_PLAYERS, $players, now()->addMinutes(30));
    }

    /**
     * อัพเดทกิจกรรมของผู้เล่น
     *
     * @param int $userId
     * @param array $data
     * @return void
     */
    public function updatePlayerActivity(int $userId, array $data = []): void
    {
        $players = $this->getOnlinePlayers();

        if (isset($players[$userId])) {
            $players[$userId]['last_activity'] = now()->toIso8601String();

            if (isset($data['score'])) {
                $players[$userId]['score'] = $data['score'];
            }

            if (isset($data['length'])) {
                $players[$userId]['length'] = $data['length'];
            }

            if (isset($data['room_id'])) {
                $players[$userId]['room_id'] = $data['room_id'];
            }

            Cache::put(self::CACHE_ONLINE_PLAYERS, $players, now()->addMinutes(30));
        }
    }

    /**
     * ดึงรายชื่อผู้เล่น online
     *
     * @return array
     */
    public function getOnlinePlayers(): array
    {
        $players = Cache::get(self::CACHE_ONLINE_PLAYERS, []);

        // ลบผู้เล่นที่ไม่มีกิจกรรมเกิน 5 นาที
        $cutoff = now()->subMinutes(5);
        $players = array_filter($players, function ($player) use ($cutoff) {
            return isset($player['last_activity']) &&
                   \Carbon\Carbon::parse($player['last_activity'])->gt($cutoff);
        });

        Cache::put(self::CACHE_ONLINE_PLAYERS, $players, now()->addMinutes(30));

        return $players;
    }

    /**
     * สร้างห้องใหม่
     *
     * @param string $roomName
     * @param int $maxPlayers
     * @return string Room ID
     */
    public function createRoom(string $roomName = 'Auto Room', int $maxPlayers = 10): string
    {
        $rooms = $this->getRooms();
        $roomId = 'room_' . uniqid();

        $rooms[$roomId] = [
            'room_id' => $roomId,
            'name' => $roomName,
            'max_players' => $maxPlayers,
            'current_players' => 0,
            'created_at' => now()->toIso8601String(),
            'players' => [],
        ];

        Cache::put(self::CACHE_ROOMS, $rooms, now()->addHours(2));

        return $roomId;
    }

    /**
     * เพิ่มผู้เล่นเข้าห้อง
     *
     * @param string $roomId
     * @param int $userId
     * @return bool
     */
    public function addPlayerToRoom(string $roomId, int $userId): bool
    {
        $rooms = $this->getRooms();

        if (!isset($rooms[$roomId])) {
            return false;
        }

        if ($rooms[$roomId]['current_players'] >= $rooms[$roomId]['max_players']) {
            return false;
        }

        $rooms[$roomId]['players'][] = $userId;
        $rooms[$roomId]['current_players'] = count($rooms[$roomId]['players']);

        Cache::put(self::CACHE_ROOMS, $rooms, now()->addHours(2));

        return true;
    }

    /**
     * ลบผู้เล่นออกจากห้อง
     *
     * @param string $roomId
     * @param int $userId
     * @return void
     */
    public function removePlayerFromRoom(string $roomId, int $userId): void
    {
        $rooms = $this->getRooms();

        if (isset($rooms[$roomId])) {
            $rooms[$roomId]['players'] = array_values(
                array_filter($rooms[$roomId]['players'], fn($id) => $id !== $userId)
            );
            $rooms[$roomId]['current_players'] = count($rooms[$roomId]['players']);

            // ลบห้องถ้าว่าง
            if ($rooms[$roomId]['current_players'] === 0) {
                unset($rooms[$roomId]);
            }

            Cache::put(self::CACHE_ROOMS, $rooms, now()->addHours(2));
        }
    }

    /**
     * ดึงรายการห้อง
     *
     * @return array
     */
    public function getRooms(): array
    {
        return Cache::get(self::CACHE_ROOMS, []);
    }

    /**
     * หาห้องที่เหมาะสม (matchmaking)
     *
     * @return string|null Room ID
     */
    public function findAvailableRoom(): ?string
    {
        $rooms = $this->getRooms();

        foreach ($rooms as $room) {
            if ($room['current_players'] < $room['max_players']) {
                return $room['room_id'];
            }
        }

        // ไม่มีห้องว่าง สร้างใหม่
        return $this->createRoom();
    }

    /**
     * บันทึกกิจกรรมน่าสงสัย
     *
     * @param int $userId
     * @param string $type
     * @param array $data
     * @return void
     */
    public function logSuspiciousActivity(int $userId, string $type, array $data = []): void
    {
        $activities = Cache::get(self::CACHE_SUSPICIOUS_ACTIVITIES, []);

        $activities[] = [
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];

        // เก็บแค่ 100 รายการล่าสุด
        $activities = array_slice($activities, -100);

        Cache::put(self::CACHE_SUSPICIOUS_ACTIVITIES, $activities, now()->addDays(7));
    }

    /**
     * ดึงกิจกรรมน่าสงสัย
     *
     * @return array
     */
    public function getSuspiciousActivities(): array
    {
        return Cache::get(self::CACHE_SUSPICIOUS_ACTIVITIES, []);
    }

    /**
     * ล้างข้อมูลทั้งหมด (สำหรับ admin)
     *
     * @return void
     */
    public function clearAllData(): void
    {
        Cache::forget(self::CACHE_ONLINE_PLAYERS);
        Cache::forget(self::CACHE_ROOMS);
        Cache::forget(self::CACHE_SUSPICIOUS_ACTIVITIES);

        Log::info('Snake Game Service data cleared');
    }
}
