<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Snake Game Sync Service
 *
 * บริการจัดการ sync สำหรับเกม Snake.io โดยเฉพาะ
 * - ใช้ Cache แทน database (เร็วกว่า)
 * - รองรับทั้ง Redis และ File cache driver
 * - ใช้ Player Registry สำหรับติดตามผู้เล่นที่ active
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
     * Key สำหรับเก็บรายชื่อผู้เล่นทั้งหมด (Player Registry)
     * ใช้แทน Redis pattern search เพื่อรองรับทุก cache driver
     */
    const PLAYERS_REGISTRY_KEY = 'snake_game:players_registry';

    /**
     * สร้าง game session ใหม่
     *
     * @param  string  $playerId  ไอดีผู้เล่น (unique)
     * @param  string  $playerName  ชื่อผู้เล่น
     * @param  string  $skin  สกินที่ใช้
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

        // เก็บ session ใน cache
        $key = $this->getSessionKey($playerId);
        Cache::put($key, $sessionData, self::SESSION_TTL);

        // สร้าง initial state พร้อมชื่อและสกิน
        $initialState = [
            'player_id' => $playerId,
            'player_name' => $playerName,
            'skin' => $skin,
            'position' => ['x' => 0, 'y' => 0, 'z' => 0],
            'direction' => ['x' => 1, 'y' => 0, 'z' => 0],
            'score' => 0,
            'length' => 5,
            'is_alive' => true,
            'updated_at' => now()->timestamp,
        ];

        // เก็บ state เริ่มต้น
        Cache::put($this->getPlayerStateKey($playerId), $initialState, self::PLAYER_TTL);

        // ลงทะเบียนใน Player Registry
        $this->addToRegistry($playerId);

        Log::info("[SnakeSync] สร้าง session: {$playerId} ({$playerName})");

        return $sessionData;
    }

    /**
     * อัปเดตสถานะผู้เล่น (ตำแหน่ง, คะแนน, ความยาว)
     * รวมชื่อและสกินจาก session ด้วยเพื่อให้ผู้เล่นคนอื่นเห็นข้อมูลครบ
     *
     * @param  array  $state  [position, score, length, direction, is_alive]
     */
    public function updatePlayerState(string $playerId, array $state): bool
    {
        try {
            // ดึงข้อมูลจาก session เพื่อรวมชื่อและสกิน
            $session = Cache::get($this->getSessionKey($playerId));
            $playerName = $session['player_name'] ?? 'Player';
            $skin = $session['skin'] ?? 'classic';

            $playerData = [
                'player_id' => $playerId,
                'player_name' => $playerName,
                'skin' => $skin,
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

            // อัปเดต timestamp ใน registry
            $this->addToRegistry($playerId);

            return true;
        } catch (\Exception $e) {
            Log::warning("[SnakeSync] อัปเดตสถานะล้มเหลว: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * ดึงผู้เล่นที่ active ทั้งหมด (ไม่รวมตัวเอง)
     * ใช้ Player Registry แทน Redis pattern search เพื่อรองรับทุก cache driver
     *
     * @param  string  $excludePlayerId  ไอดีที่ไม่ต้องการ (ตัวเอง)
     * @param  int  $limit  จำนวนสูงสุด
     */
    public function getActivePlayers(string $excludePlayerId, int $limit = 10): array
    {
        try {
            $registry = Cache::get(self::PLAYERS_REGISTRY_KEY, []);
            $players = [];
            $count = 0;
            $staleIds = [];
            $now = now()->timestamp;

            foreach ($registry as $playerId => $joinTime) {
                // ข้ามตัวเอง
                if ($playerId === $excludePlayerId) {
                    continue;
                }

                // จำกัดจำนวน
                if ($count >= $limit) {
                    break;
                }

                // ดึงข้อมูลสถานะจาก cache
                $playerData = Cache::get($this->getPlayerStateKey($playerId));

                if ($playerData) {
                    // ตรวจสอบว่ายัง active อยู่ (อัปเดตภายใน PLAYER_TTL วินาที)
                    $timeSinceUpdate = $now - ($playerData['updated_at'] ?? 0);
                    if ($timeSinceUpdate <= self::PLAYER_TTL) {
                        $players[] = $playerData;
                        $count++;
                    } else {
                        // ข้อมูลเก่าเกินไป - ลบออกจาก registry
                        $staleIds[] = $playerId;
                    }
                } else {
                    // ไม่มีข้อมูลใน cache - ลบออกจาก registry
                    $staleIds[] = $playerId;
                }
            }

            // ลบผู้เล่นที่ไม่ active ออกจาก registry
            if (! empty($staleIds)) {
                foreach ($staleIds as $staleId) {
                    unset($registry[$staleId]);
                }
                Cache::put(self::PLAYERS_REGISTRY_KEY, $registry, self::SESSION_TTL);
            }

            return $players;
        } catch (\Exception $e) {
            Log::warning("[SnakeSync] ดึงผู้เล่นล้มเหลว: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * ผู้เล่นตาย - ลบสถานะและ registry
     */
    public function playerDied(string $playerId): bool
    {
        try {
            // ลบสถานะผู้เล่น
            $key = $this->getPlayerStateKey($playerId);
            Cache::forget($key);

            // ลบจาก registry
            $this->removeFromRegistry($playerId);

            Log::info("[SnakeSync] ผู้เล่นตาย: {$playerId}");

            return true;
        } catch (\Exception $e) {
            Log::warning("[SnakeSync] ลบสถานะล้มเหลว: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * ออกจากเกม - ลบทั้ง session, สถานะ, และ registry
     */
    public function leaveGame(string $playerId): bool
    {
        try {
            // ลบทั้ง session, state, และ registry
            Cache::forget($this->getSessionKey($playerId));
            Cache::forget($this->getPlayerStateKey($playerId));
            $this->removeFromRegistry($playerId);

            Log::info("[SnakeSync] ผู้เล่นออก: {$playerId}");

            return true;
        } catch (\Exception $e) {
            Log::warning("[SnakeSync] ออกจากเกมล้มเหลว: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Ping session เพื่อรักษา session ให้ active
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
            $registry = Cache::get(self::PLAYERS_REGISTRY_KEY, []);
            $deletedCount = 0;
            $now = now()->timestamp;

            foreach ($registry as $playerId => $joinTime) {
                // ตรวจสอบว่า session ยังอยู่หรือไม่
                $session = Cache::get($this->getSessionKey($playerId));
                $state = Cache::get($this->getPlayerStateKey($playerId));

                $shouldRemove = false;

                if (! $session && ! $state) {
                    // ทั้ง session และ state หมดอายุแล้ว
                    $shouldRemove = true;
                } elseif ($session) {
                    $lastPing = $session['last_ping'] ?? 0;
                    if ($now - $lastPing > self::SESSION_TTL) {
                        $shouldRemove = true;
                    }
                }

                if ($shouldRemove) {
                    Cache::forget($this->getSessionKey($playerId));
                    Cache::forget($this->getPlayerStateKey($playerId));
                    unset($registry[$playerId]);
                    $deletedCount++;
                }
            }

            // อัปเดต registry
            Cache::put(self::PLAYERS_REGISTRY_KEY, $registry, self::SESSION_TTL);

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
     */
    public function getActivePlayerCount(): int
    {
        try {
            $registry = Cache::get(self::PLAYERS_REGISTRY_KEY, []);
            $count = 0;
            $now = now()->timestamp;

            foreach ($registry as $playerId => $joinTime) {
                $playerData = Cache::get($this->getPlayerStateKey($playerId));
                if ($playerData) {
                    $timeSinceUpdate = $now - ($playerData['updated_at'] ?? 0);
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
     * เพิ่มผู้เล่นเข้า Player Registry (ใช้ lock ป้องกัน race condition)
     */
    protected function addToRegistry(string $playerId): void
    {
        $lock = Cache::lock('snake_registry_lock', 3);

        if ($lock->get()) {
            try {
                $registry = Cache::get(self::PLAYERS_REGISTRY_KEY, []);
                $registry[$playerId] = now()->timestamp;
                Cache::put(self::PLAYERS_REGISTRY_KEY, $registry, self::SESSION_TTL);
            } finally {
                $lock->release();
            }
        } else {
            // ถ้าไม่ได้ lock ให้ทำแบบเดิม (fallback สำหรับ file cache ที่ไม่รองรับ lock)
            $registry = Cache::get(self::PLAYERS_REGISTRY_KEY, []);
            $registry[$playerId] = now()->timestamp;
            Cache::put(self::PLAYERS_REGISTRY_KEY, $registry, self::SESSION_TTL);
        }
    }

    /**
     * ลบผู้เล่นออกจาก Player Registry (ใช้ lock ป้องกัน race condition)
     */
    protected function removeFromRegistry(string $playerId): void
    {
        $lock = Cache::lock('snake_registry_lock', 3);

        if ($lock->get()) {
            try {
                $registry = Cache::get(self::PLAYERS_REGISTRY_KEY, []);
                unset($registry[$playerId]);
                Cache::put(self::PLAYERS_REGISTRY_KEY, $registry, self::SESSION_TTL);
            } finally {
                $lock->release();
            }
        } else {
            // fallback
            $registry = Cache::get(self::PLAYERS_REGISTRY_KEY, []);
            unset($registry[$playerId]);
            Cache::put(self::PLAYERS_REGISTRY_KEY, $registry, self::SESSION_TTL);
        }
    }

    /**
     * สร้าง cache key สำหรับ session
     */
    protected function getSessionKey(string $playerId): string
    {
        return self::CACHE_PREFIX.':session:'.$playerId;
    }

    /**
     * สร้าง cache key สำหรับสถานะผู้เล่น
     */
    protected function getPlayerStateKey(string $playerId): string
    {
        return self::CACHE_PREFIX.':player:'.$playerId;
    }
}
