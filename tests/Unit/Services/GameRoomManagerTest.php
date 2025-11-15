<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\GameRoomManager;
use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\GameRoomItem;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

/**
 * GameRoomManager Unit Tests
 *
 * ทดสอบการทำงานของ GameRoomManager Service
 */
class GameRoomManagerTest extends TestCase
{
    use RefreshDatabase;

    protected GameRoomManager $manager;
    protected Game $game;

    /**
     * ตั้งค่าเริ่มต้นก่อนแต่ละ test
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new GameRoomManager();

        // สร้างเกม Snake.io สำหรับทดสอบ
        $this->game = Game::factory()->create([
            'slug' => 'snake-io',
            'name' => 'Snake.io',
            'is_active' => true,
        ]);
    }

    /**
     * ทดสอบการสร้างห้องใหม่
     *
     * @test
     */
    public function it_can_create_new_room(): void
    {
        // Act: สร้างห้องใหม่
        $room = $this->manager->createNewRoom($this->game->id);

        // Assert: ตรวจสอบว่าห้องถูกสร้างสำเร็จ
        $this->assertInstanceOf(GameRoom::class, $room);
        $this->assertEquals($this->game->id, $room->game_id);
        $this->assertEquals('waiting', $room->status);
        $this->assertEquals(30, $room->max_players);
        $this->assertEquals(0, $room->current_players);
        $this->assertNotEmpty($room->room_code);
        $this->assertEquals(6, strlen($room->room_code));

        // ตรวจสอบ settings
        $this->assertEquals(200, $room->settings['world_size']);
        $this->assertEquals(100, $room->settings['food_count']);

        // ตรวจสอบว่ามีอาหารเริ่มต้น 100 ชิ้น
        $this->assertEquals(100, $room->items()->where('item_type', 'food')->count());
    }

    /**
     * ทดสอบการค้นหาห้องที่ว่าง
     *
     * @test
     */
    public function it_can_find_available_room(): void
    {
        // Arrange: สร้างห้องที่ยังไม่เต็ม
        $room = $this->manager->createNewRoom($this->game->id);
        $room->update(['current_players' => 10]);

        // Act: ค้นหาห้องที่ว่าง
        $foundRoom = $this->manager->findOrCreateAvailableRoom($this->game->id);

        // Assert: ควรได้ห้องเดียวกัน
        $this->assertEquals($room->id, $foundRoom->id);
    }

    /**
     * ทดสอบการสร้างห้องใหม่เมื่อห้องเต็ม
     *
     * @test
     */
    public function it_creates_new_room_when_full(): void
    {
        // Arrange: สร้างห้องที่เต็มแล้ว
        $fullRoom = $this->manager->createNewRoom($this->game->id);
        $fullRoom->update(['current_players' => 30]);

        // Act: ค้นหาห้องใหม่
        $newRoom = $this->manager->findOrCreateAvailableRoom($this->game->id);

        // Assert: ควรได้ห้องใหม่
        $this->assertNotEquals($fullRoom->id, $newRoom->id);
        $this->assertEquals(0, $newRoom->current_players);
    }

    /**
     * ทดสอบการเข้าร่วมห้อง
     *
     * @test
     */
    public function it_can_join_room(): void
    {
        // Arrange
        $room = $this->manager->createNewRoom($this->game->id);
        $user = User::factory()->create();

        // Act: ผู้เล่นเข้าร่วมห้อง
        $player = $this->manager->joinRoom($room, $user->id, 'TestPlayer', 'classic');

        // Assert: ตรวจสอบผู้เล่น
        $this->assertInstanceOf(GameRoomPlayer::class, $player);
        $this->assertEquals($room->id, $player->room_id);
        $this->assertEquals($user->id, $player->user_id);
        $this->assertEquals('TestPlayer', $player->player_name);
        $this->assertEquals('classic', $player->skin_slug);
        $this->assertTrue($player->is_alive);
        $this->assertEquals('active', $player->status);

        // ตรวจสอบว่าห้องเพิ่มจำนวนผู้เล่น
        $this->assertEquals(1, $room->fresh()->current_players);
    }

    /**
     * ทดสอบการเข้าร่วมแบบ Guest (ไม่มี user_id)
     *
     * @test
     */
    public function it_can_join_room_as_guest(): void
    {
        // Arrange
        $room = $this->manager->createNewRoom($this->game->id);

        // Act: Guest เข้าร่วมห้อง
        $player = $this->manager->joinRoom($room, null, 'Guest123', 'fire');

        // Assert
        $this->assertNull($player->user_id);
        $this->assertEquals('Guest123', $player->player_name);
        $this->assertEquals('fire', $player->skin_slug);
    }

    /**
     * ทดสอบการออกจากห้อง
     *
     * @test
     */
    public function it_can_leave_room(): void
    {
        // Arrange: เข้าร่วมห้อง
        $room = $this->manager->createNewRoom($this->game->id);
        $player = $this->manager->joinRoom($room, null, 'TestPlayer', 'classic');

        $this->assertEquals(1, $room->fresh()->current_players);

        // Act: ออกจากห้อง
        $this->manager->leaveRoom($player);

        // Assert: ตรวจสอบว่าจำนวนผู้เล่นลดลง
        $this->assertEquals(0, $room->fresh()->current_players);

        // ตรวจสอบว่าผู้เล่นถูกลบ
        $this->assertNull(GameRoomPlayer::find($player->id));
    }

    /**
     * ทดสอบการอัพเดทสถานะผู้เล่น
     *
     * @test
     */
    public function it_can_update_player_state(): void
    {
        // Arrange
        $room = $this->manager->createNewRoom($this->game->id);
        $player = $this->manager->joinRoom($room, null, 'TestPlayer', 'classic');

        // Act: อัพเดทสถานะ
        $this->manager->updatePlayerState(
            $player,
            ['x' => 10, 'y' => 0, 'z' => 20],
            ['x' => 1, 'y' => 0, 'z' => 0],
            100,
            15
        );

        // Assert: ตรวจสอบข้อมูลอัพเดท
        $player->refresh();
        $this->assertEquals(['x' => 10, 'y' => 0, 'z' => 20], $player->position);
        $this->assertEquals(['x' => 1, 'y' => 0, 'z' => 0], $player->direction);
        $this->assertEquals(100, $player->score);
        $this->assertEquals(15, $player->length);
        $this->assertNotNull($player->last_update);
    }

    /**
     * ทดสอบการตายของผู้เล่น
     *
     * @test
     */
    public function it_can_handle_player_death(): void
    {
        // Arrange
        $room = $this->manager->createNewRoom($this->game->id);
        $player = $this->manager->joinRoom($room, null, 'TestPlayer', 'classic');

        // อัพเดทให้มีคะแนนและความยาว
        $this->manager->updatePlayerState($player, ['x' => 0, 'y' => 0, 'z' => 0], ['x' => 1, 'y' => 0, 'z' => 0], 100, 15);

        $foodCountBefore = $room->items()->where('item_type', 'food')->count();

        // Act: ผู้เล่นตาย
        $this->manager->playerDied($player);

        // Assert: ตรวจสอบสถานะ
        $player->refresh();
        $this->assertFalse($player->is_alive);
        $this->assertEquals('dead', $player->status);

        // ตรวจสอบว่ามีอาหารจากการตายเพิ่มขึ้น (สูงสุด 10 ชิ้น)
        $foodCountAfter = $room->items()->where('item_type', 'food')->count();
        $this->assertGreaterThan($foodCountBefore, $foodCountAfter);
        $this->assertLessThanOrEqual($foodCountBefore + 10, $foodCountAfter);
    }

    /**
     * ทดสอบการเก็บไอเทม
     *
     * @test
     */
    public function it_can_collect_item(): void
    {
        // Arrange
        $room = $this->manager->createNewRoom($this->game->id);
        $player = $this->manager->joinRoom($room, null, 'TestPlayer', 'classic');

        // หาอาหารชิ้นแรก
        $food = $room->items()->where('item_type', 'food')->first();
        $this->assertNotNull($food);

        // Act: เก็บอาหาร
        $collectedItem = $this->manager->collectItem($player, $food->id);

        // Assert: ตรวจสอบว่าเก็บสำเร็จ
        $this->assertNotNull($collectedItem);
        $this->assertEquals($food->id, $collectedItem->id);

        // ตรวจสอบว่าไอเทมถูก mark ว่าเก็บแล้ว
        $food->refresh();
        $this->assertTrue($food->is_collected);
        $this->assertEquals($player->id, $food->collected_by);
        $this->assertNotNull($food->collected_at);
    }

    /**
     * ทดสอบว่าไม่สามารถเก็บไอเทมซ้ำได้
     *
     * @test
     */
    public function it_cannot_collect_already_collected_item(): void
    {
        // Arrange
        $room = $this->manager->createNewRoom($this->game->id);
        $player1 = $this->manager->joinRoom($room, null, 'Player1', 'classic');
        $player2 = $this->manager->joinRoom($room, null, 'Player2', 'classic');

        $food = $room->items()->where('item_type', 'food')->first();

        // Player1 เก็บก่อน
        $this->manager->collectItem($player1, $food->id);

        // Act: Player2 พยายามเก็บไอเทมเดียวกัน
        $result = $this->manager->collectItem($player2, $food->id);

        // Assert: ควรได้ null (เก็บไม่ได้)
        $this->assertNull($result);
    }

    /**
     * ทดสอบการ spawn powerup
     *
     * @test
     */
    public function it_can_spawn_powerup(): void
    {
        // Arrange
        $room = $this->manager->createNewRoom($this->game->id);

        $powerupCountBefore = $room->items()
            ->whereIn('item_type', ['powerup_magnet', 'powerup_speed', 'powerup_multiplier'])
            ->count();

        // Act: Spawn powerup
        $this->manager->spawnRandomPowerup($room->id);

        // Assert: ตรวจสอบว่ามี powerup เพิ่มขึ้น
        $powerupCountAfter = $room->items()
            ->whereIn('item_type', ['powerup_magnet', 'powerup_speed', 'powerup_multiplier'])
            ->count();

        $this->assertEquals($powerupCountBefore + 1, $powerupCountAfter);
    }

    /**
     * ทดสอบการดึงสถานะห้อง
     *
     * @test
     */
    public function it_can_get_room_state(): void
    {
        // Arrange
        $room = $this->manager->createNewRoom($this->game->id);
        $player = $this->manager->joinRoom($room, null, 'TestPlayer', 'classic');

        // Act: ดึงสถานะห้อง
        $state = $this->manager->getRoomState($room->id);

        // Assert: ตรวจสอบโครงสร้างข้อมูล
        $this->assertIsArray($state);
        $this->assertArrayHasKey('room', $state);
        $this->assertArrayHasKey('players', $state);
        $this->assertArrayHasKey('items', $state);

        // ตรวจสอบข้อมูลห้อง
        $this->assertEquals($room->id, $state['room']['id']);
        $this->assertEquals($room->room_code, $state['room']['room_code']);

        // ตรวจสอบผู้เล่น
        $this->assertCount(1, $state['players']);
        $this->assertEquals('TestPlayer', $state['players'][0]['player_name']);

        // ตรวจสอบไอเทม
        $this->assertGreaterThanOrEqual(100, count($state['items']));
    }

    /**
     * ทดสอบ room code ไม่ซ้ำกัน
     *
     * @test
     */
    public function it_generates_unique_room_codes(): void
    {
        // Act: สร้างหลายห้อง
        $room1 = $this->manager->createNewRoom($this->game->id);
        $room2 = $this->manager->createNewRoom($this->game->id);
        $room3 = $this->manager->createNewRoom($this->game->id);

        // Assert: ตรวจสอบว่า room code ไม่ซ้ำกัน
        $this->assertNotEquals($room1->room_code, $room2->room_code);
        $this->assertNotEquals($room1->room_code, $room3->room_code);
        $this->assertNotEquals($room2->room_code, $room3->room_code);
    }

    /**
     * ทดสอบการจำกัดผู้เล่นสูงสุดต่อห้อง
     *
     * @test
     */
    public function it_respects_max_players_limit(): void
    {
        // Arrange: สร้างห้องและเพิ่มผู้เล่น 30 คน
        $room = $this->manager->createNewRoom($this->game->id);

        for ($i = 0; $i < 30; $i++) {
            $this->manager->joinRoom($room, null, "Player{$i}", 'classic');
        }

        $room->refresh();
        $this->assertEquals(30, $room->current_players);

        // Act: หาห้องใหม่เมื่อห้องเดิมเต็ม
        $newRoom = $this->manager->findOrCreateAvailableRoom($this->game->id);

        // Assert: ควรได้ห้องใหม่
        $this->assertNotEquals($room->id, $newRoom->id);
        $this->assertEquals(0, $newRoom->current_players);
    }
}
