<?php

namespace App\Services;

use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\GameRoomItem;
use App\Models\Game;
use App\Events\SnakeGame\PlayerJoined;
use App\Events\SnakeGame\PlayerLeft;
use App\Events\SnakeGame\PlayerMoved;
use App\Events\SnakeGame\PlayerDied as PlayerDiedEvent;
use App\Events\SnakeGame\ItemSpawned;
use App\Events\SnakeGame\ItemCollected as ItemCollectedEvent;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * GameRoomManager Service
 *
 * จัดการห้องเกม multiplayer สำหรับ Snake.io
 * - จำกัดห้องละ 30 คน
 * - สร้างห้องใหม่อัตโนมัติเมื่อเต็ม
 * - จัดการไอเทมและการเก็บ
 */
class GameRoomManager
{
    /**
     * จำนวนผู้เล่นสูงสุดต่อห้อง
     */
    const MAX_PLAYERS_PER_ROOM = 30;

    /**
     * จำนวนอาหารเริ่มต้นในห้อง
     */
    const INITIAL_FOOD_COUNT = 100;

    /**
     * ระยะเวลา spawn powerup (20 วินาที)
     */
    const POWERUP_SPAWN_INTERVAL = 20;

    /**
     * ค้นหาหรือสร้างห้องที่ว่างสำหรับเข้าเล่น
     *
     * @param int $gameId
     * @return GameRoom
     */
    public function findOrCreateAvailableRoom(int $gameId): GameRoom
    {
        // ค้นหาห้องที่รอผู้เล่นและยังไม่เต็ม
        $room = GameRoom::where('game_id', $gameId)
            ->where('status', 'waiting')
            ->where('current_players', '<', self::MAX_PLAYERS_PER_ROOM)
            ->first();

        // ถ้าไม่มีห้องว่าง สร้างห้องใหม่
        if (!$room) {
            $room = $this->createNewRoom($gameId);
        }

        return $room;
    }

    /**
     * สร้างห้องเกมใหม่
     *
     * @param int $gameId
     * @return GameRoom
     */
    public function createNewRoom(int $gameId): GameRoom
    {
        $roomCode = $this->generateUniqueRoomCode();

        $room = GameRoom::create([
            'game_id' => $gameId,
            'room_code' => $roomCode,
            'name' => 'Room ' . $roomCode,
            'max_players' => self::MAX_PLAYERS_PER_ROOM,
            'current_players' => 0,
            'status' => 'waiting',
            'settings' => [
                'world_size' => 200,
                'food_count' => self::INITIAL_FOOD_COUNT,
                'powerup_spawn_interval' => self::POWERUP_SPAWN_INTERVAL,
            ],
        ]);

        // สร้างอาหารเริ่มต้น
        $this->spawnInitialFood($room->id);

        return $room;
    }

    /**
     * ผู้เล่นเข้าร่วมห้อง
     *
     * @param GameRoom $room
     * @param int|null $userId
     * @param string $playerName
     * @param string $skinSlug
     * @return GameRoomPlayer
     */
    public function joinRoom(GameRoom $room, ?int $userId, string $playerName, string $skinSlug = 'classic'): GameRoomPlayer
    {
        // ตรวจสอบว่าห้องเต็มหรือไม่
        if ($room->isFull()) {
            throw new \Exception('ห้องเต็ม กรุณาเลือกห้องอื่น');
        }

        // สร้างตำแหน่งเริ่มต้นแบบสุ่ม
        $position = $this->generateRandomPosition($room);

        // สร้างผู้เล่นในห้อง
        $player = GameRoomPlayer::create([
            'room_id' => $room->id,
            'user_id' => $userId,
            'player_name' => $playerName,
            'skin_slug' => $skinSlug,
            'position' => $position,
            'direction' => ['x' => 1, 'y' => 0, 'z' => 0], // เดินไปทางขวา
            'score' => 0,
            'length' => 5, // ความยาวเริ่มต้น
            'is_alive' => true,
            'status' => 'active',
        ]);

        // อัปเดตจำนวนผู้เล่นในห้อง
        $room->increment('current_players');

        // เริ่มเกมถ้ามีผู้เล่นอย่างน้อย 2 คน
        if ($room->current_players >= 2 && $room->status === 'waiting') {
            $room->start();
        }

        // Broadcast event: ผู้เล่นเข้าร่วมห้อง
        broadcast(new PlayerJoined(
            $room->id,
            $player->id,
            $playerName,
            $skinSlug,
            $position
        ))->toOthers();

        return $player;
    }

    /**
     * ผู้เล่นออกจากห้อง
     *
     * @param GameRoomPlayer $player
     * @return void
     */
    public function leaveRoom(GameRoomPlayer $player): void
    {
        $room = $player->room;
        $roomId = $room->id;
        $playerId = $player->id;

        // ลบผู้เล่น
        $player->disconnect();

        // Broadcast event: ผู้เล่นออกจากห้อง
        broadcast(new PlayerLeft($roomId, $playerId))->toOthers();

        // อัปเดตจำนวนผู้เล่น
        $room->updatePlayerCount();

        // ถ้าไม่มีผู้เล่นเหลือ ปิดห้อง
        if ($room->current_players === 0) {
            $room->end();
        }
    }

    /**
     * อัปเดตตำแหน่งผู้เล่น
     *
     * @param GameRoomPlayer $player
     * @param array $position
     * @param array $direction
     * @param int $score
     * @param int $length
     * @return void
     */
    public function updatePlayerState(GameRoomPlayer $player, array $position, array $direction, int $score, int $length): void
    {
        $player->update([
            'position' => $position,
            'direction' => $direction,
            'score' => $score,
            'length' => $length,
            'last_update' => now(),
        ]);

        // Broadcast event: ผู้เล่นเคลื่อนที่
        broadcast(new PlayerMoved(
            $player->room_id,
            $player->id,
            $position,
            $direction,
            $score,
            $length
        ))->toOthers();
    }

    /**
     * ผู้เล่นตาย
     *
     * @param GameRoomPlayer $player
     * @return void
     */
    public function playerDied(GameRoomPlayer $player): void
    {
        $finalScore = $player->score;
        $finalLength = $player->length;

        $player->die();

        // Broadcast event: ผู้เล่นตาย
        broadcast(new PlayerDiedEvent(
            $player->room_id,
            $player->id,
            $finalScore,
            $finalLength
        ))->toOthers();

        // สร้างอาหารจากร่างของงู (แปลง segments เป็นอาหาร)
        $this->spawnFoodFromSnake($player);
    }

    /**
     * เก็บไอเทม
     *
     * @param GameRoomPlayer $player
     * @param int $itemId
     * @return GameRoomItem|null
     */
    public function collectItem(GameRoomPlayer $player, int $itemId): ?GameRoomItem
    {
        $item = GameRoomItem::where('room_id', $player->room_id)
            ->where('id', $itemId)
            ->where('is_collected', false)
            ->first();

        if (!$item || $item->isExpired()) {
            return null;
        }

        // เก็บไอเทม
        $item->collect($player->id);

        // Broadcast event: เก็บไอเทม
        broadcast(new ItemCollectedEvent(
            $player->room_id,
            $item->id,
            $player->id
        ))->toOthers();

        // อัปเดตคะแนนผู้เล่นถ้าเป็นอาหาร
        if ($item->item_type === GameRoomItem::TYPE_FOOD) {
            $player->increment('score', $item->value);
            $player->increment('length', $item->value);
        }

        return $item;
    }

    /**
     * สร้างอาหารเริ่มต้นในห้อง
     *
     * @param int $roomId
     * @return void
     */
    public function spawnInitialFood(int $roomId): void
    {
        $room = GameRoom::findOrFail($roomId);
        $worldSize = $room->settings['world_size'] ?? 200;
        $foodCount = $room->settings['food_count'] ?? self::INITIAL_FOOD_COUNT;

        for ($i = 0; $i < $foodCount; $i++) {
            $position = $this->generateRandomFoodPosition($worldSize);
            GameRoomItem::spawnFood($roomId, $position, 1);
        }
    }

    /**
     * Spawn powerup แบบสุ่ม
     *
     * @param int $roomId
     * @return void
     */
    public function spawnRandomPowerup(int $roomId): void
    {
        $room = GameRoom::findOrFail($roomId);
        $worldSize = $room->settings['world_size'] ?? 200;

        // สุ่มประเภท powerup
        $types = [
            GameRoomItem::TYPE_POWERUP_MAGNET,
            GameRoomItem::TYPE_POWERUP_SPEED,
            GameRoomItem::TYPE_POWERUP_MULTIPLIER,
        ];
        $type = $types[array_rand($types)];

        $position = $this->generateRandomFoodPosition($worldSize);
        $item = GameRoomItem::spawnPowerup($roomId, $type, $position);

        // Broadcast event: เกิดไอเทมใหม่
        if ($item) {
            broadcast(new ItemSpawned(
                $roomId,
                $item->id,
                $item->item_type,
                $position,
                $item->value
            ));
        }
    }

    /**
     * ลบไอเทมที่หมดอายุและ spawn ใหม่
     *
     * @param int $roomId
     * @return void
     */
    public function maintainItems(int $roomId): void
    {
        // ลบไอเทมที่หมดอายุ
        GameRoomItem::removeExpired($roomId);

        // นับจำนวนอาหารที่เหลือ
        $foodCount = GameRoomItem::where('room_id', $roomId)
            ->where('item_type', GameRoomItem::TYPE_FOOD)
            ->where('is_collected', false)
            ->count();

        // ถ้าอาหารน้อยเกินไป spawn เพิ่ม
        $room = GameRoom::findOrFail($roomId);
        $targetFoodCount = $room->settings['food_count'] ?? self::INITIAL_FOOD_COUNT;
        $worldSize = $room->settings['world_size'] ?? 200;

        if ($foodCount < $targetFoodCount * 0.8) { // เติมเมื่ออาหารเหลือน้อยกว่า 80%
            $spawnCount = $targetFoodCount - $foodCount;
            for ($i = 0; $i < $spawnCount; $i++) {
                $position = $this->generateRandomFoodPosition($worldSize);
                $item = GameRoomItem::spawnFood($roomId, $position, 1);

                // Broadcast event: เกิดอาหารใหม่
                if ($item) {
                    broadcast(new ItemSpawned(
                        $roomId,
                        $item->id,
                        $item->item_type,
                        $position,
                        $item->value
                    ));
                }
            }
        }
    }

    /**
     * สร้างอาหารจากงูที่ตาย
     *
     * @param GameRoomPlayer $player
     * @return void
     */
    protected function spawnFoodFromSnake(GameRoomPlayer $player): void
    {
        $position = $player->position;

        // สร้างอาหารจำนวนหนึ่งตามความยาวของงู
        $foodCount = min($player->length, 10); // สูงสุด 10 ชิ้น

        for ($i = 0; $i < $foodCount; $i++) {
            // สร้างตำแหน่งใกล้ๆ ตำแหน่งที่งูตาย
            $randomPos = [
                'x' => $position['x'] + (rand(-5, 5)),
                'y' => $position['y'] ?? 0.5,
                'z' => $position['z'] + (rand(-5, 5)),
            ];

            GameRoomItem::spawnFood($player->room_id, $randomPos, 2); // อาหารจากงูมีค่า 2 เท่า
        }
    }

    /**
     * สร้าง room code ที่ unique
     *
     * @return string
     */
    protected function generateUniqueRoomCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (GameRoom::where('room_code', $code)->exists());

        return $code;
    }

    /**
     * สร้างตำแหน่งเริ่มต้นแบบสุ่มสำหรับผู้เล่น
     *
     * @param GameRoom $room
     * @return array
     */
    protected function generateRandomPosition(GameRoom $room): array
    {
        $worldSize = $room->settings['world_size'] ?? 200;
        $halfWorld = $worldSize / 2 - 20;

        return [
            'x' => (rand(-100, 100) / 100) * $halfWorld,
            'y' => 0.5,
            'z' => (rand(-100, 100) / 100) * $halfWorld,
        ];
    }

    /**
     * สร้างตำแหน่งแบบสุ่มสำหรับอาหาร
     *
     * @param int $worldSize
     * @return array
     */
    protected function generateRandomFoodPosition(int $worldSize): array
    {
        $halfWorld = $worldSize / 2 - 10;

        return [
            'x' => (rand(-100, 100) / 100) * $halfWorld,
            'y' => 0.3,
            'z' => (rand(-100, 100) / 100) * $halfWorld,
        ];
    }

    /**
     * ดึงสถานะห้องทั้งหมด (สำหรับ sync กับ client)
     *
     * @param int $roomId
     * @return array
     */
    public function getRoomState(int $roomId): array
    {
        $room = GameRoom::with(['players' => function ($query) {
            $query->where('status', 'active');
        }])->findOrFail($roomId);

        $items = GameRoomItem::getActiveItems($roomId);

        return [
            'room' => [
                'id' => $room->id,
                'code' => $room->room_code,
                'status' => $room->status,
                'current_players' => $room->current_players,
                'max_players' => $room->max_players,
            ],
            'players' => $room->players->map(function ($player) {
                return [
                    'id' => $player->id,
                    'name' => $player->player_name,
                    'skin' => $player->skin_slug,
                    'position' => $player->position,
                    'direction' => $player->direction,
                    'score' => $player->score,
                    'length' => $player->length,
                    'is_alive' => $player->is_alive,
                ];
            }),
            'items' => $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => $item->item_type,
                    'position' => $item->position,
                    'value' => $item->value,
                    'expires_at' => $item->expires_at?->toIso8601String(),
                ];
            }),
        ];
    }
}
