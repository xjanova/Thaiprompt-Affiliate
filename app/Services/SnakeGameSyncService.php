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
 * - ใช้ Player Registry แบ่งตาม room_id สำหรับติดตามผู้เล่นที่ active
 * - TTL 60 วินาที (เพิ่มจาก 30 เพื่อลดปัญหาเน็ตหลุด)
 * - Lightweight - ไม่ทำให้เกมค้าง
 */
class SnakeGameSyncService
{
    /**
     * TTL สำหรับข้อมูลผู้เล่น (วินาที)
     * เพิ่มจาก 30 เป็น 60 เพื่อลดปัญหาเน็ตหลุดชั่วคราว
     */
    const PLAYER_TTL = 60;

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
     * ✅ เปลี่ยนเป็นแบ่งตาม room เพื่อให้ผู้เล่นเห็นเฉพาะคนในห้องเดียวกัน
     */
    const PLAYERS_REGISTRY_PREFIX = 'snake_game:room_registry:';

    /**
     * Key เดิมสำหรับ global registry (backward compatible)
     */
    const GLOBAL_REGISTRY_KEY = 'snake_game:players_registry';

    /**
     * Default room สำหรับผู้เล่นที่ไม่ระบุห้อง
     */
    const DEFAULT_ROOM = 'default';

    /**
     * สร้าง game session ใหม่
     *
     * @param  string  $playerId  ไอดีผู้เล่น (unique)
     * @param  string  $playerName  ชื่อผู้เล่น
     * @param  string  $skin  สกินที่ใช้
     * @param  string  $roomId  ไอดีห้อง (ถ้าไม่ระบุจะใช้ default)
     * @return array session data
     */
    public function createSession(string $playerId, string $playerName, string $skin, string $roomId = ''): array
    {
        $roomId = $roomId ?: self::DEFAULT_ROOM;

        $sessionData = [
            'player_id' => $playerId,
            'player_name' => $playerName,
            'skin' => $skin,
            'room_id' => $roomId,
            'created_at' => now()->timestamp,
            'last_ping' => now()->timestamp,
        ];

        // เก็บ session ใน cache
        $key = $this->getSessionKey($playerId);
        Cache::put($key, $sessionData, self::SESSION_TTL);

        // สร้าง initial state พร้อมชื่อ, สกิน, และห้อง
        $initialState = [
            'player_id' => $playerId,
            'player_name' => $playerName,
            'skin' => $skin,
            'room_id' => $roomId,
            'position' => ['x' => 0, 'y' => 0, 'z' => 0],
            'direction' => ['x' => 1, 'y' => 0, 'z' => 0],
            'score' => 0,
            'length' => 5,
            'is_alive' => true,
            'updated_at' => now()->timestamp,
        ];

        // เก็บ state เริ่มต้น
        Cache::put($this->getPlayerStateKey($playerId), $initialState, self::PLAYER_TTL);

        // ลงทะเบียนใน Room Registry
        $this->addToRegistry($playerId, $roomId);

        Log::info("[SnakeSync] สร้าง session: {$playerId} ({$playerName}) ห้อง: {$roomId}");

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
            // ดึงข้อมูลจาก session เพื่อรวมชื่อ, สกิน, และห้อง
            $session = Cache::get($this->getSessionKey($playerId));
            $playerName = $session['player_name'] ?? 'Player';
            $skin = $session['skin'] ?? 'classic';
            $roomId = $session['room_id'] ?? self::DEFAULT_ROOM;

            $playerData = [
                'player_id' => $playerId,
                'player_name' => $playerName,
                'skin' => $skin,
                'room_id' => $roomId,
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
            $this->addToRegistry($playerId, $roomId);

            return true;
        } catch (\Exception $e) {
            Log::warning("[SnakeSync] อัปเดตสถานะล้มเหลว: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * ดึงผู้เล่นที่ active ทั้งหมดในห้องเดียวกัน (ไม่รวมตัวเอง)
     * ✅ FIX: กรองเฉพาะผู้เล่นในห้องเดียวกัน
     *
     * @param  string  $excludePlayerId  ไอดีที่ไม่ต้องการ (ตัวเอง)
     * @param  int  $limit  จำนวนสูงสุด
     * @param  string  $roomId  ห้องที่ต้องการ (ถ้าไม่ระบุจะดึงจาก session)
     */
    public function getActivePlayers(string $excludePlayerId, int $limit = 10, string $roomId = ''): array
    {
        try {
            // ถ้าไม่ระบุห้อง ดึงจาก session
            if (empty($roomId)) {
                $session = Cache::get($this->getSessionKey($excludePlayerId));
                $roomId = $session['room_id'] ?? self::DEFAULT_ROOM;
            }

            // ดึง registry ของห้องนี้
            $registryKey = self::PLAYERS_REGISTRY_PREFIX . $roomId;
            $registry = Cache::get($registryKey, []);

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
                Cache::put($registryKey, $registry, self::SESSION_TTL);
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
            // ดึง room_id จาก session ก่อนลบ
            $session = Cache::get($this->getSessionKey($playerId));
            $roomId = $session['room_id'] ?? self::DEFAULT_ROOM;

            // ลบสถานะผู้เล่น
            $key = $this->getPlayerStateKey($playerId);
            Cache::forget($key);

            // ลบจาก room registry
            $this->removeFromRegistry($playerId, $roomId);

            Log::info("[SnakeSync] ผู้เล่นตาย: {$playerId} ห้อง: {$roomId}");

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
            // ดึง room_id จาก session ก่อนลบ
            $session = Cache::get($this->getSessionKey($playerId));
            $roomId = $session['room_id'] ?? self::DEFAULT_ROOM;

            // ลบทั้ง session, state, และ registry
            Cache::forget($this->getSessionKey($playerId));
            Cache::forget($this->getPlayerStateKey($playerId));
            $this->removeFromRegistry($playerId, $roomId);

            Log::info("[SnakeSync] ผู้เล่นออก: {$playerId} ห้อง: {$roomId}");

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
            // ทำความสะอาดทั้ง global registry (backward compat) และ room registries
            $globalRegistry = Cache::get(self::GLOBAL_REGISTRY_KEY, []);
            $deletedCount = 0;
            $now = now()->timestamp;

            foreach ($globalRegistry as $playerId => $joinTime) {
                $session = Cache::get($this->getSessionKey($playerId));
                $state = Cache::get($this->getPlayerStateKey($playerId));

                $shouldRemove = false;

                if (! $session && ! $state) {
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
                    unset($globalRegistry[$playerId]);
                    $deletedCount++;
                }
            }

            // อัปเดต global registry
            Cache::put(self::GLOBAL_REGISTRY_KEY, $globalRegistry, self::SESSION_TTL);

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
     * ดึงจำนวนผู้เล่น active ทั้งหมด (ทุกห้อง)
     */
    public function getActivePlayerCount(): int
    {
        try {
            $globalRegistry = Cache::get(self::GLOBAL_REGISTRY_KEY, []);
            $count = 0;
            $now = now()->timestamp;

            foreach ($globalRegistry as $playerId => $joinTime) {
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
     * ดึงจำนวนผู้เล่น active ในห้องที่ระบุ
     */
    public function getActivePlayerCountInRoom(string $roomId): int
    {
        try {
            $registryKey = self::PLAYERS_REGISTRY_PREFIX . $roomId;
            $registry = Cache::get($registryKey, []);
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
     * ดึงรายการห้องที่มีผู้เล่น active (รวม player list ต่อห้อง)
     *
     * @return array รายการห้อง [{room_id, player_count, players: [{player_id, player_name, score, length, is_alive}]}]
     */
    public function getActiveRooms(): array
    {
        try {
            $globalRegistry = Cache::get(self::GLOBAL_REGISTRY_KEY, []);
            $rooms = [];
            $now = now()->timestamp;

            foreach ($globalRegistry as $playerId => $joinTime) {
                $playerData = Cache::get($this->getPlayerStateKey($playerId));
                if ($playerData) {
                    $timeSinceUpdate = $now - ($playerData['updated_at'] ?? 0);
                    if ($timeSinceUpdate <= self::PLAYER_TTL) {
                        $roomId = $playerData['room_id'] ?? self::DEFAULT_ROOM;
                        if (! isset($rooms[$roomId])) {
                            $rooms[$roomId] = [
                                'room_id' => $roomId,
                                'player_count' => 0,
                                'players' => [],
                            ];
                        }
                        $rooms[$roomId]['player_count']++;
                        $rooms[$roomId]['players'][] = [
                            'player_id' => $playerData['player_id'] ?? $playerId,
                            'player_name' => $playerData['player_name'] ?? 'Player',
                            'skin' => $playerData['skin'] ?? 'classic',
                            'score' => $playerData['score'] ?? 0,
                            'length' => $playerData['length'] ?? 5,
                            'is_alive' => $playerData['is_alive'] ?? true,
                        ];
                    }
                }
            }

            return array_values($rooms);
        } catch (\Exception $e) {
            Log::warning("[SnakeSync] ดึงรายการห้องล้มเหลว: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * ดึงจำนวนห้องที่มีผู้เล่น active
     */
    public function getActiveRoomsCount(): int
    {
        return count($this->getActiveRooms());
    }

    /**
     * ดึงผู้เล่น active ทั้งหมดจาก global registry (สำหรับ admin monitor)
     *
     * @param  int  $limit  จำนวนสูงสุด
     * @return array รายการผู้เล่นทุกห้อง
     */
    public function getAllActivePlayers(int $limit = 100): array
    {
        try {
            $globalRegistry = Cache::get(self::GLOBAL_REGISTRY_KEY, []);
            $players = [];
            $now = now()->timestamp;
            $count = 0;

            foreach ($globalRegistry as $playerId => $joinTime) {
                if ($count >= $limit) {
                    break;
                }

                $playerData = Cache::get($this->getPlayerStateKey($playerId));
                if ($playerData) {
                    $timeSinceUpdate = $now - ($playerData['updated_at'] ?? 0);
                    if ($timeSinceUpdate <= self::PLAYER_TTL) {
                        $players[] = $playerData;
                        $count++;
                    }
                }
            }

            return $players;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * เพิ่มผู้เล่นเข้า Room Registry + Global Registry (ใช้ lock ป้องกัน race condition)
     */
    protected function addToRegistry(string $playerId, string $roomId = ''): void
    {
        $roomId = $roomId ?: self::DEFAULT_ROOM;
        $registryKey = self::PLAYERS_REGISTRY_PREFIX . $roomId;

        $lock = Cache::lock('snake_registry_lock_' . $roomId, 3);

        if ($lock->get()) {
            try {
                // เพิ่มเข้า room registry
                $registry = Cache::get($registryKey, []);
                $registry[$playerId] = now()->timestamp;
                Cache::put($registryKey, $registry, self::SESSION_TTL);

                // เพิ่มเข้า global registry ด้วย (สำหรับ stats/cleanup)
                $globalRegistry = Cache::get(self::GLOBAL_REGISTRY_KEY, []);
                $globalRegistry[$playerId] = now()->timestamp;
                Cache::put(self::GLOBAL_REGISTRY_KEY, $globalRegistry, self::SESSION_TTL);
            } finally {
                $lock->release();
            }
        } else {
            // fallback สำหรับ file cache ที่ไม่รองรับ lock
            $registry = Cache::get($registryKey, []);
            $registry[$playerId] = now()->timestamp;
            Cache::put($registryKey, $registry, self::SESSION_TTL);

            $globalRegistry = Cache::get(self::GLOBAL_REGISTRY_KEY, []);
            $globalRegistry[$playerId] = now()->timestamp;
            Cache::put(self::GLOBAL_REGISTRY_KEY, $globalRegistry, self::SESSION_TTL);
        }
    }

    /**
     * ลบผู้เล่นออกจาก Room Registry + Global Registry (ใช้ lock ป้องกัน race condition)
     */
    protected function removeFromRegistry(string $playerId, string $roomId = ''): void
    {
        $roomId = $roomId ?: self::DEFAULT_ROOM;
        $registryKey = self::PLAYERS_REGISTRY_PREFIX . $roomId;

        $lock = Cache::lock('snake_registry_lock_' . $roomId, 3);

        if ($lock->get()) {
            try {
                // ลบจาก room registry
                $registry = Cache::get($registryKey, []);
                unset($registry[$playerId]);
                Cache::put($registryKey, $registry, self::SESSION_TTL);

                // ลบจาก global registry
                $globalRegistry = Cache::get(self::GLOBAL_REGISTRY_KEY, []);
                unset($globalRegistry[$playerId]);
                Cache::put(self::GLOBAL_REGISTRY_KEY, $globalRegistry, self::SESSION_TTL);
            } finally {
                $lock->release();
            }
        } else {
            // fallback
            $registry = Cache::get($registryKey, []);
            unset($registry[$playerId]);
            Cache::put($registryKey, $registry, self::SESSION_TTL);

            $globalRegistry = Cache::get(self::GLOBAL_REGISTRY_KEY, []);
            unset($globalRegistry[$playerId]);
            Cache::put(self::GLOBAL_REGISTRY_KEY, $globalRegistry, self::SESSION_TTL);
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
