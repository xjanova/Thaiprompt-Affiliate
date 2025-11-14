<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Snake Game Sync Service
 *
 * บริการจัดการ sync สำหรับเกม Snake.io โดยเฉพาะ
 * - ใช้ Redis/Cache แทน database (เร็วกว่า)
 * - TTL 30 วินาที (ลบข้อมูลเก่าอัตโนมัติ)
 * - Lightweight - ไม่ทำให้เกมค้าง
 */
class SnakeGameSyncService
{
    /**
     * TTL สำหรับข้อมูลผู้เล่น (วินาที)
     */
    const PLAYER_TTL = 30;

    /**
     * TTL สำหรับ session (วินาที)
     */
    const SESSION_TTL = 300; // 5 นาที

    /**
     * Prefix สำหรับ cache key
     */
    const CACHE_PREFIX = 'snake_game';

    /**
     * สร้าง game session ใหม่
     *
     * @param string $playerId ไอดีผู้เล่น (unique)
     * @param string $playerName ชื่อผู้เล่น
     * @param string $skin สกินที่ใช้
     * @return array session data
     */
    public function createSession(string $playerId, string $playerName, string $skin): array
    {
        $sessionData = [
            'player_id' => $playerId,
            'player_name' => $playerName,
            'skin' => $skin,
            'created_at' => now()->timestamp,
            'last_ping' => now()->timestamp,
        ];

        // เก็บใน cache ด้วย TTL
        $key = $this->getSessionKey($playerId);
        Cache::put($key, $sessionData, self::SESSION_TTL);

        Log::info("[SnakeSync] สร้าง session: {$playerId}");

        return $sessionData;
    }

    /**
     * อัปเดตสถานะผู้เล่น (ตำแหน่ง, คะแนน, ความยาว)
     *
     * @param string $playerId
     * @param array $state [position, score, length, direction]
     * @return bool
     */
    public function updatePlayerState(string $playerId, array $state): bool
    {
        try {
            $playerData = [
                'player_id' => $playerId,
                'position' => $state['position'] ?? ['x' => 0, 'y' => 0, 'z' => 0],
                'direction' => $state['direction'] ?? ['x' => 1, 'y' => 0, 'z' => 0],
                'score' => $state['score'] ?? 0,
                'length' => $state['length'] ?? 5,
                'is_alive' => $state['is_alive'] ?? true,
                'updated_at' => now()->timestamp,
            ];

            // เก็บใน cache
            $key = $this->getPlayerStateKey($playerId);
            Cache::put($key, $playerData, self::PLAYER_TTL);

            // อัปเดต last_ping ใน session
            $this->pingSession($playerId);

            return true;
        } catch (\Exception $e) {
            Log::warning("[SnakeSync] อัปเดตสถานะล้มเหลว: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * ดึงผู้เล่นที่ active ทั้งหมด (ไม่รวมตัวเอง)
     *
     * @param string $excludePlayerId ไอดีที่ไม่ต้องการ
     * @param int $limit จำนวนสูงสุด
     * @return array
     */
    public function getActivePlayers(string $excludePlayerId, int $limit = 10): array
    {
        try {
            // ดึง keys ทั้งหมดที่เกี่ยวข้อง
            $pattern = self::CACHE_PREFIX . ':player:*';
            $keys = $this->getCacheKeys($pattern);

            $players = [];
            $count = 0;

            foreach ($keys as $key) {
                if ($count >= $limit) {
                    break;
                }

                $playerData = Cache::get($key);
                if ($playerData && $playerData['player_id'] !== $excludePlayerId) {
                    // ตรวจสอบว่ายัง active อยู่ (อัปเดตภายใน 30 วินาที)
                    $timeSinceUpdate = now()->timestamp - ($playerData['updated_at'] ?? 0);
                    if ($timeSinceUpdate <= self::PLAYER_TTL) {
                        $players[] = $playerData;
                        $count++;
                    }
                }
            }

            return $players;
        } catch (\Exception $e) {
            Log::warning("[SnakeSync] ดึงผู้เล่นล้มเหลว: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * ผู้เล่นตาย - ลบสถานะ
     *
     * @param string $playerId
     * @return bool
     */
    public function playerDied(string $playerId): bool
    {
        try {
            // ลบสถานะผู้เล่น
            $key = $this->getPlayerStateKey($playerId);
            Cache::forget($key);

            Log::info("[SnakeSync] ผู้เล่นตาย: {$playerId}");

            return true;
        } catch (\Exception $e) {
            Log::warning("[SnakeSync] ลบสถานะล้มเหลว: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * ออกจากเกม - ลบทั้ง session และสถานะ
     *
     * @param string $playerId
     * @return bool
     */
    public function leaveGame(string $playerId): bool
    {
        try {
            // ลบทั้ง session และ state
            Cache::forget($this->getSessionKey($playerId));
            Cache::forget($this->getPlayerStateKey($playerId));

            Log::info("[SnakeSync] ผู้เล่นออก: {$playerId}");

            return true;
        } catch (\Exception $e) {
            Log::warning("[SnakeSync] ออกจากเกมล้มเหลว: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Ping session เพื่อรักษา session ให้ active
     *
     * @param string $playerId
     * @return bool
     */
    public function pingSession(string $playerId): bool
    {
        try {
            $key = $this->getSessionKey($playerId);
            $session = Cache::get($key);

            if ($session) {
                $session['last_ping'] = now()->timestamp;
                Cache::put($key, $session, self::SESSION_TTL);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * ทำความสะอาดข้อมูลเก่า (เรียกจาก scheduler)
     *
     * @return int จำนวนที่ลบ
     */
    public function cleanup(): int
    {
        try {
            $deletedCount = 0;
            $pattern = self::CACHE_PREFIX . ':*';
            $keys = $this->getCacheKeys($pattern);

            $now = now()->timestamp;

            foreach ($keys as $key) {
                $data = Cache::get($key);
                if ($data) {
                    $lastUpdate = $data['updated_at'] ?? $data['last_ping'] ?? 0;
                    $age = $now - $lastUpdate;

                    // ลบข้อมูลที่เก่ากว่า TTL
                    if ($age > self::SESSION_TTL) {
                        Cache::forget($key);
                        $deletedCount++;
                    }
                }
            }

            if ($deletedCount > 0) {
                Log::info("[SnakeSync] ทำความสะอาด: ลบ {$deletedCount} รายการ");
            }

            return $deletedCount;
        } catch (\Exception $e) {
            Log::error("[SnakeSync] Cleanup error: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * ดึงจำนวนผู้เล่น active
     *
     * @return int
     */
    public function getActivePlayerCount(): int
    {
        try {
            $pattern = self::CACHE_PREFIX . ':player:*';
            $keys = $this->getCacheKeys($pattern);

            $count = 0;
            $now = now()->timestamp;

            foreach ($keys as $key) {
                $data = Cache::get($key);
                if ($data) {
                    $timeSinceUpdate = $now - ($data['updated_at'] ?? 0);
                    if ($timeSinceUpdate <= self::PLAYER_TTL) {
                        $count++;
                    }
                }
            }

            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * สร้าง cache key สำหรับ session
     *
     * @param string $playerId
     * @return string
     */
    protected function getSessionKey(string $playerId): string
    {
        return self::CACHE_PREFIX . ':session:' . $playerId;
    }

    /**
     * สร้าง cache key สำหรับสถานะผู้เล่น
     *
     * @param string $playerId
     * @return string
     */
    protected function getPlayerStateKey(string $playerId): string
    {
        return self::CACHE_PREFIX . ':player:' . $playerId;
    }

    /**
     * ดึง cache keys ตาม pattern
     *
     * @param string $pattern
     * @return array
     */
    protected function getCacheKeys(string $pattern): array
    {
        try {
            // ถ้าใช้ Redis
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getStore()->connection();
                return $redis->keys($pattern);
            }

            // ถ้าใช้ cache อื่นๆ - คืนค่า empty array (ไม่รองรับ pattern search)
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
