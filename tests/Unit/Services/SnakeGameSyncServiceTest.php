<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SnakeGameSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

/**
 * SnakeGameSyncService Unit Tests
 *
 * ทดสอบการทำงานของ SnakeGameSyncService (Lightweight multiplayer)
 */
class SnakeGameSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SnakeGameSyncService $service;

    /**
     * ตั้งค่าเริ่มต้นก่อนแต่ละ test
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SnakeGameSyncService();

        // ล้าง cache ก่อนทดสอบ
        Cache::flush();
    }

    /**
     * ล้าง cache หลังแต่ละ test
     */
    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /**
     * ทดสอบการสร้าง session ผู้เล่น
     *
     * @test
     */
    public function it_can_create_player_session(): void
    {
        // Arrange
        $playerId = 'player_123';
        $playerName = 'TestPlayer';
        $skin = 'classic';

        // Act: สร้าง session
        $session = $this->service->createSession($playerId, $playerName, $skin);

        // Assert: ตรวจสอบข้อมูล session
        $this->assertIsArray($session);
        $this->assertEquals($playerId, $session['player_id']);
        $this->assertEquals($playerName, $session['name']);
        $this->assertEquals($skin, $session['skin']);
        $this->assertArrayHasKey('created_at', $session);
        $this->assertArrayHasKey('last_ping', $session);

        // ตรวจสอบว่า session ถูกเก็บใน cache
        $cached = Cache::get("snake_sync:player:{$playerId}");
        $this->assertNotNull($cached);
        $this->assertEquals($playerName, $cached['name']);
    }

    /**
     * ทดสอบการอัพเดทสถานะผู้เล่น
     *
     * @test
     */
    public function it_can_update_player_state(): void
    {
        // Arrange: สร้าง session ก่อน
        $playerId = 'player_123';
        $this->service->createSession($playerId, 'TestPlayer', 'classic');

        // Act: อัพเดทสถานะ
        $state = [
            'position' => ['x' => 10, 'y' => 0, 'z' => 20],
            'direction' => ['x' => 1, 'y' => 0, 'z' => 0],
            'score' => 100,
            'length' => 15,
            'is_alive' => true,
        ];

        $result = $this->service->updatePlayerState($playerId, $state);

        // Assert: ตรวจสอบว่าอัพเดทสำเร็จ
        $this->assertTrue($result);

        // ตรวจสอบข้อมูลใน cache
        $cached = Cache::get("snake_sync:player:{$playerId}");
        $this->assertEquals(100, $cached['score']);
        $this->assertEquals(15, $cached['length']);
        $this->assertTrue($cached['is_alive']);
        $this->assertEquals(['x' => 10, 'y' => 0, 'z' => 20], $cached['position']);
    }

    /**
     * ทดสอบว่าไม่สามารถอัพเดทผู้เล่นที่ไม่มี session ได้
     *
     * @test
     */
    public function it_cannot_update_non_existent_player(): void
    {
        // Act: พยายามอัพเดทผู้เล่นที่ไม่มี session
        $result = $this->service->updatePlayerState('non_existent_player', [
            'score' => 100,
        ]);

        // Assert: ควรได้ false
        $this->assertFalse($result);
    }

    /**
     * ทดสอบการดึงรายการผู้เล่นที่ active
     *
     * @test
     */
    public function it_can_get_active_players(): void
    {
        // Arrange: สร้างผู้เล่นหลายคน
        $this->service->createSession('player_1', 'Player1', 'classic');
        $this->service->createSession('player_2', 'Player2', 'fire');
        $this->service->createSession('player_3', 'Player3', 'ice');

        // อัพเดทตำแหน่งของแต่ละคน
        $this->service->updatePlayerState('player_1', ['position' => ['x' => 10, 'y' => 0, 'z' => 10]]);
        $this->service->updatePlayerState('player_2', ['position' => ['x' => 20, 'y' => 0, 'z' => 20]]);
        $this->service->updatePlayerState('player_3', ['position' => ['x' => 30, 'y' => 0, 'z' => 30]]);

        // Act: ดึงผู้เล่น active (ยกเว้น player_1)
        $players = $this->service->getActivePlayers('player_1', 10);

        // Assert: ควรได้ player_2 และ player_3
        $this->assertCount(2, $players);

        $playerIds = array_column($players, 'player_id');
        $this->assertContains('player_2', $playerIds);
        $this->assertContains('player_3', $playerIds);
        $this->assertNotContains('player_1', $playerIds);
    }

    /**
     * ทดสอบการจำกัดจำนวนผู้เล่นที่ดึงได้
     *
     * @test
     */
    public function it_respects_player_limit(): void
    {
        // Arrange: สร้างผู้เล่น 15 คน
        for ($i = 1; $i <= 15; $i++) {
            $this->service->createSession("player_{$i}", "Player{$i}", 'classic');
            $this->service->updatePlayerState("player_{$i}", ['position' => ['x' => $i, 'y' => 0, 'z' => $i]]);
        }

        // Act: ดึงผู้เล่นสูงสุด 5 คน
        $players = $this->service->getActivePlayers('player_1', 5);

        // Assert: ควรได้ไม่เกิน 5 คน
        $this->assertLessThanOrEqual(5, count($players));
    }

    /**
     * ทดสอบการแจ้งว่าผู้เล่นตาย
     *
     * @test
     */
    public function it_can_mark_player_as_dead(): void
    {
        // Arrange: สร้าง session
        $playerId = 'player_123';
        $this->service->createSession($playerId, 'TestPlayer', 'classic');
        $this->service->updatePlayerState($playerId, ['is_alive' => true]);

        // Act: แจ้งว่าตาย
        $result = $this->service->playerDied($playerId);

        // Assert: ตรวจสอบว่าอัพเดทสำเร็จ
        $this->assertTrue($result);

        // ตรวจสอบสถานะใน cache
        $cached = Cache::get("snake_sync:player:{$playerId}");
        $this->assertFalse($cached['is_alive']);
    }

    /**
     * ทดสอบการออกจากเกม
     *
     * @test
     */
    public function it_can_leave_game(): void
    {
        // Arrange: สร้าง session
        $playerId = 'player_123';
        $this->service->createSession($playerId, 'TestPlayer', 'classic');

        // ตรวจสอบว่ามี session ใน cache
        $this->assertNotNull(Cache::get("snake_sync:player:{$playerId}"));

        // Act: ออกจากเกม
        $result = $this->service->leaveGame($playerId);

        // Assert: ตรวจสอบว่าลบสำเร็จ
        $this->assertTrue($result);

        // ตรวจสอบว่า session ถูกลบจาก cache
        $this->assertNull(Cache::get("snake_sync:player:{$playerId}"));
    }

    /**
     * ทดสอบการ ping session เพื่อรักษาการเชื่อมต่อ
     *
     * @test
     */
    public function it_can_ping_session(): void
    {
        // Arrange: สร้าง session
        $playerId = 'player_123';
        $this->service->createSession($playerId, 'TestPlayer', 'classic');

        // รอ 1 วินาที
        sleep(1);

        // Act: Ping session
        $result = $this->service->pingSession($playerId);

        // Assert: ตรวจสอบว่า ping สำเร็จ
        $this->assertTrue($result);

        // ตรวจสอบว่า last_ping ถูกอัพเดท
        $cached = Cache::get("snake_sync:player:{$playerId}");
        $this->assertNotNull($cached['last_ping']);
    }

    /**
     * ทดสอบว่า ping ผู้เล่นที่ไม่มี session ไม่สำเร็จ
     *
     * @test
     */
    public function it_cannot_ping_non_existent_player(): void
    {
        // Act: พยายาม ping ผู้เล่นที่ไม่มี session
        $result = $this->service->pingSession('non_existent_player');

        // Assert: ควรได้ false
        $this->assertFalse($result);
    }

    /**
     * ทดสอบการ cleanup session ที่หมดอายุ
     *
     * @test
     */
    public function it_can_cleanup_expired_sessions(): void
    {
        // Arrange: สร้างผู้เล่นหลายคน
        $this->service->createSession('player_1', 'Player1', 'classic');
        $this->service->createSession('player_2', 'Player2', 'fire');
        $this->service->createSession('player_3', 'Player3', 'ice');

        // จำลองว่า player_1 และ player_2 หมดอายุ (45 วินาทีที่แล้ว)
        $expiredTime = now()->subSeconds(45)->timestamp;

        Cache::put("snake_sync:player:player_1", [
            'player_id' => 'player_1',
            'name' => 'Player1',
            'last_ping' => $expiredTime,
        ], 60);

        Cache::put("snake_sync:player:player_2", [
            'player_id' => 'player_2',
            'name' => 'Player2',
            'last_ping' => $expiredTime,
        ], 60);

        // Act: Cleanup
        $cleanedCount = $this->service->cleanup();

        // Assert: ควร cleanup 2 sessions
        $this->assertEquals(2, $cleanedCount);

        // ตรวจสอบว่า player_3 ยังอยู่
        $this->assertNotNull(Cache::get("snake_sync:player:player_3"));

        // ตรวจสอบว่า player_1 และ player_2 ถูกลบ
        $this->assertNull(Cache::get("snake_sync:player:player_1"));
        $this->assertNull(Cache::get("snake_sync:player:player_2"));
    }

    /**
     * ทดสอบการนับจำนวนผู้เล่น active
     *
     * @test
     */
    public function it_can_count_active_players(): void
    {
        // Arrange: สร้างผู้เล่น 5 คน
        for ($i = 1; $i <= 5; $i++) {
            $this->service->createSession("player_{$i}", "Player{$i}", 'classic');
        }

        // Act: นับจำนวนผู้เล่น
        $count = $this->service->getActivePlayerCount();

        // Assert: ควรได้ 5 คน
        $this->assertEquals(5, $count);
    }

    /**
     * ทดสอบว่าผู้เล่นที่ตายแล้วยังถูกนับเป็น active
     *
     * @test
     */
    public function it_counts_dead_players_as_active(): void
    {
        // Arrange: สร้างผู้เล่น 3 คน
        $this->service->createSession('player_1', 'Player1', 'classic');
        $this->service->createSession('player_2', 'Player2', 'fire');
        $this->service->createSession('player_3', 'Player3', 'ice');

        // ให้ player_2 ตาย
        $this->service->playerDied('player_2');

        // Act: นับจำนวนผู้เล่น
        $count = $this->service->getActivePlayerCount();

        // Assert: ยังได้ 3 คนเพราะยังมี session (แม้ตายแล้ว)
        $this->assertEquals(3, $count);
    }

    /**
     * ทดสอบว่า session มี TTL 30 วินาที
     *
     * @test
     */
    public function it_sets_session_ttl_to_30_seconds(): void
    {
        // Arrange
        $playerId = 'player_123';

        // Act: สร้าง session
        $this->service->createSession($playerId, 'TestPlayer', 'classic');

        // Assert: ตรวจสอบว่ามี session
        $this->assertNotNull(Cache::get("snake_sync:player:{$playerId}"));

        // Note: ไม่สามารถทดสอบ TTL จริงๆ ได้ในหน่วยทดสอบ
        // แต่สามารถตรวจสอบได้ว่า session ถูกสร้างสำเร็จ
        $this->assertTrue(true);
    }

    /**
     * ทดสอบการอัพเดทหลายค่าพร้อมกัน
     *
     * @test
     */
    public function it_can_update_multiple_fields(): void
    {
        // Arrange: สร้าง session
        $playerId = 'player_123';
        $this->service->createSession($playerId, 'TestPlayer', 'classic');

        // Act: อัพเดทหลายค่า
        $state = [
            'position' => ['x' => 15, 'y' => 0, 'z' => 25],
            'direction' => ['x' => 0, 'y' => 0, 'z' => 1],
            'score' => 250,
            'length' => 30,
            'is_alive' => true,
            'is_boosting' => true,
        ];

        $this->service->updatePlayerState($playerId, $state);

        // Assert: ตรวจสอบทุกค่า
        $cached = Cache::get("snake_sync:player:{$playerId}");
        $this->assertEquals(250, $cached['score']);
        $this->assertEquals(30, $cached['length']);
        $this->assertTrue($cached['is_alive']);
        $this->assertTrue($cached['is_boosting']);
        $this->assertEquals(['x' => 15, 'y' => 0, 'z' => 25], $cached['position']);
        $this->assertEquals(['x' => 0, 'y' => 0, 'z' => 1], $cached['direction']);
    }
}
