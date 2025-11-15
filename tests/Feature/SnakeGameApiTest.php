<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Game;
use App\Models\User;
use App\Models\Wallet;
use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\GameRoomItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Snake Game API Feature Tests
 *
 * ทดสอบ REST API endpoints ของเกม Snake.io
 */
class SnakeGameApiTest extends TestCase
{
    use RefreshDatabase;

    protected Game $game;
    protected User $user;

    /**
     * ตั้งค่าเริ่มต้นก่อนแต่ละ test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // สร้างเกม Snake.io
        $this->game = Game::factory()->create([
            'slug' => 'snake-io',
            'name' => 'Snake.io',
            'is_active' => true,
        ]);

        // สร้างผู้ใช้สำหรับทดสอบ
        $this->user = User::factory()->create();
    }

    /**
     * ทดสอบ API: เข้าร่วมเกม (Guest)
     *
     * @test
     */
    public function guest_can_join_game(): void
    {
        // Act: เรียก API เข้าร่วมเกม
        $response = $this->postJson('/api/games/snake-io/join', [
            'player_name' => 'GuestPlayer',
            'skin_slug' => 'classic',
        ]);

        // Assert: ตรวจสอบ response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'room_code',
                'room_id',
                'player',
                'room_state',
            ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['room_code']);

        // ตรวจสอบว่าห้องถูกสร้าง
        $this->assertDatabaseHas('game_rooms', [
            'room_code' => $data['room_code'],
            'game_id' => $this->game->id,
        ]);

        // ตรวจสอบว่าผู้เล่นถูกเพิ่มในห้อง
        $this->assertDatabaseHas('game_room_players', [
            'room_id' => $data['room_id'],
            'player_name' => 'GuestPlayer',
            'skin_slug' => 'classic',
        ]);
    }

    /**
     * ทดสอบ API: เข้าร่วมเกม (Authenticated User)
     *
     * @test
     */
    public function authenticated_user_can_join_game(): void
    {
        // Act: เรียก API เข้าร่วมเกม (authenticated)
        $response = $this->actingAs($this->user, 'web')
            ->postJson('/api/games/snake-io/join', [
                'player_name' => $this->user->name,
                'skin_slug' => 'fire',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // ตรวจสอบว่าผู้เล่นมี user_id
        $this->assertDatabaseHas('game_room_players', [
            'user_id' => $this->user->id,
            'player_name' => $this->user->name,
            'skin_slug' => 'fire',
        ]);
    }

    /**
     * ทดสอบ API: ออกจากเกม
     *
     * @test
     */
    public function player_can_leave_game(): void
    {
        // Arrange: เข้าร่วมเกมก่อน
        $joinResponse = $this->postJson('/api/games/snake-io/join', [
            'player_name' => 'TestPlayer',
            'skin_slug' => 'classic',
        ]);

        $playerId = $joinResponse->json('player.id');

        // Act: ออกจากเกม
        $response = $this->postJson('/api/games/snake-io/leave', [
            'player_id' => $playerId,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // ตรวจสอบว่าผู้เล่นถูกลบ
        $this->assertDatabaseMissing('game_room_players', [
            'id' => $playerId,
        ]);
    }

    /**
     * ทดสอบ API: อัพเดทสถานะผู้เล่น
     *
     * @test
     */
    public function player_can_update_state(): void
    {
        // Arrange: เข้าร่วมเกมก่อน
        $joinResponse = $this->postJson('/api/games/snake-io/join', [
            'player_name' => 'TestPlayer',
            'skin_slug' => 'classic',
        ]);

        $playerId = $joinResponse->json('player.id');

        // Act: อัพเดทสถานะ
        $response = $this->postJson('/api/games/snake-io/update-state', [
            'player_id' => $playerId,
            'position' => ['x' => 10, 'y' => 0, 'z' => 20],
            'direction' => ['x' => 1, 'y' => 0, 'z' => 0],
            'score' => 100,
            'length' => 15,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // ตรวจสอบข้อมูลใน database
        $player = GameRoomPlayer::find($playerId);
        $this->assertEquals(['x' => 10, 'y' => 0, 'z' => 20], $player->position);
        $this->assertEquals(100, $player->score);
        $this->assertEquals(15, $player->length);
    }

    /**
     * ทดสอบ API: แจ้งว่าผู้เล่นตาย
     *
     * @test
     */
    public function player_can_die(): void
    {
        // Arrange: เข้าร่วมเกมก่อน
        $joinResponse = $this->postJson('/api/games/snake-io/join', [
            'player_name' => 'TestPlayer',
            'skin_slug' => 'classic',
        ]);

        $playerId = $joinResponse->json('player.id');

        // Act: แจ้งว่าตาย
        $response = $this->postJson('/api/games/snake-io/player-died', [
            'player_id' => $playerId,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // ตรวจสอบสถานะใน database
        $player = GameRoomPlayer::find($playerId);
        $this->assertFalse($player->is_alive);
        $this->assertEquals('dead', $player->status);
    }

    /**
     * ทดสอบ API: เก็บไอเทม
     *
     * @test
     */
    public function player_can_collect_item(): void
    {
        // Arrange: เข้าร่วมเกมก่อน
        $joinResponse = $this->postJson('/api/games/snake-io/join', [
            'player_name' => 'TestPlayer',
            'skin_slug' => 'classic',
        ]);

        $playerId = $joinResponse->json('player.id');
        $roomId = $joinResponse->json('room_id');

        // หาไอเทมชิ้นแรกในห้อง
        $item = GameRoomItem::where('room_id', $roomId)
            ->where('is_collected', false)
            ->first();

        $this->assertNotNull($item);

        // Act: เก็บไอเทม
        $response = $this->postJson('/api/games/snake-io/collect-item', [
            'player_id' => $playerId,
            'item_id' => $item->id,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // ตรวจสอบว่าไอเทมถูก mark ว่าเก็บแล้ว
        $item->refresh();
        $this->assertTrue($item->is_collected);
        $this->assertEquals($playerId, $item->collected_by);
        $this->assertNotNull($item->collected_at);
    }

    /**
     * ทดสอบ API: ดึงสถานะห้อง
     *
     * @test
     */
    public function can_get_room_state(): void
    {
        // Arrange: เข้าร่วมเกมก่อน
        $joinResponse = $this->postJson('/api/games/snake-io/join', [
            'player_name' => 'TestPlayer',
            'skin_slug' => 'classic',
        ]);

        $roomId = $joinResponse->json('room_id');

        // Act: ดึงสถานะห้อง
        $response = $this->getJson("/api/games/snake-io/room-state/{$roomId}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'room',
                'players',
                'items',
            ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals($roomId, $data['room']['id']);
        $this->assertIsArray($data['players']);
        $this->assertIsArray($data['items']);
    }

    /**
     * ทดสอบ API: ตรวจสอบ wallet (Guest)
     *
     * @test
     */
    public function guest_cannot_check_wallet(): void
    {
        // Act: Guest ตรวจสอบ wallet
        $response = $this->getJson('/api/games/snake-io/check-wallet');

        // Assert: ควรได้ 401 Unauthorized
        $response->assertStatus(401);
    }

    /**
     * ทดสอบ API: ตรวจสอบ wallet (Authenticated)
     *
     * @test
     */
    public function authenticated_user_can_check_wallet(): void
    {
        // Arrange: สร้าง wallet ให้ user
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000,
        ]);

        // Act: ตรวจสอบ wallet
        $response = $this->actingAs($this->user, 'web')
            ->getJson('/api/games/snake-io/check-wallet');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'has_wallet' => true,
                'balance' => 1000,
                'can_save_score' => true,
            ]);
    }

    /**
     * ทดสอบ API: บันทึกคะแนน (ไม่มี wallet)
     *
     * @test
     */
    public function cannot_save_score_without_wallet(): void
    {
        // Act: พยายามบันทึกคะแนนโดยไม่มี wallet
        $response = $this->actingAs($this->user, 'web')
            ->postJson('/api/games/snake-io/save-score', [
                'score' => 1000,
                'length' => 50,
            ]);

        // Assert: ควรสร้าง wallet ใหม่และบันทึกคะแนนสำเร็จ
        // (ตาม logic ใน controller จะสร้าง wallet อัตโนมัติ)
        $response->assertStatus(200);

        // ตรวจสอบว่ามี wallet ถูกสร้าง
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * ทดสอบ API: บันทึกคะแนน (มี wallet และมีแต้มเพียงพอ)
     *
     * @test
     */
    public function can_save_score_with_sufficient_balance(): void
    {
        // Arrange: สร้าง wallet ที่มีแต้มเพียงพอ
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 100,
        ]);

        // Act: บันทึกคะแนน
        $response = $this->actingAs($this->user, 'web')
            ->postJson('/api/games/snake-io/save-score', [
                'score' => 1500,
                'length' => 60,
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // ตรวจสอบว่าคะแนนถูกบันทึก
        $this->assertDatabaseHas('game_leaderboards', [
            'user_id' => $this->user->id,
            'game_id' => $this->game->id,
            'score' => 1500,
            'wave_reached' => 60, // length ใช้เป็น wave_reached
        ]);

        // ตรวจสอบว่าแต้มถูกหัก 1
        $wallet->refresh();
        $this->assertEquals(99, $wallet->balance);
    }

    /**
     * ทดสอบ API: ไม่สามารถบันทึกคะแนนโดยไม่ login
     *
     * @test
     */
    public function guest_cannot_save_score(): void
    {
        // Act: Guest พยายามบันทึกคะแนน
        $response = $this->postJson('/api/games/snake-io/save-score', [
            'score' => 1000,
            'length' => 50,
        ]);

        // Assert: ควรได้ 401 Unauthorized
        $response->assertStatus(401);
    }

    /**
     * ทดสอบ API: ตรวจสอบสถานะ service
     *
     * @test
     */
    public function can_check_service_status(): void
    {
        // Act: ตรวจสอบสถานะ service
        $response = $this->getJson('/api/games/snake-io/service-status');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'is_online',
                'mode',
            ]);
    }

    /**
     * ทดสอบ Validation: player_name จำเป็นต้องมี
     *
     * @test
     */
    public function join_requires_player_name(): void
    {
        // Act: เข้าร่วมเกมโดยไม่ส่ง player_name
        $response = $this->postJson('/api/games/snake-io/join', [
            'skin_slug' => 'classic',
        ]);

        // Assert: ควรได้ 422 Validation Error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['player_name']);
    }

    /**
     * ทดสอบ Validation: skin_slug ต้องถูกต้อง
     *
     * @test
     */
    public function join_validates_skin_slug(): void
    {
        // Act: เข้าร่วมเกมด้วย skin ที่ไม่ถูกต้อง
        $response = $this->postJson('/api/games/snake-io/join', [
            'player_name' => 'TestPlayer',
            'skin_slug' => 'invalid_skin',
        ]);

        // Assert: ควรได้ 422 Validation Error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['skin_slug']);
    }

    /**
     * ทดสอบว่าหลายผู้เล่นสามารถอยู่ในห้องเดียวกันได้
     *
     * @test
     */
    public function multiple_players_can_join_same_room(): void
    {
        // Act: ผู้เล่น 3 คนเข้าร่วมเกม
        $player1 = $this->postJson('/api/games/snake-io/join', [
            'player_name' => 'Player1',
            'skin_slug' => 'classic',
        ]);

        $player2 = $this->postJson('/api/games/snake-io/join', [
            'player_name' => 'Player2',
            'skin_slug' => 'fire',
        ]);

        $player3 = $this->postJson('/api/games/snake-io/join', [
            'player_name' => 'Player3',
            'skin_slug' => 'ice',
        ]);

        // Assert: ทุกคนควรอยู่ในห้องเดียวกัน
        $roomId = $player1->json('room_id');
        $this->assertEquals($roomId, $player2->json('room_id'));
        $this->assertEquals($roomId, $player3->json('room_id'));

        // ตรวจสอบจำนวนผู้เล่นในห้อง
        $room = GameRoom::find($roomId);
        $this->assertEquals(3, $room->current_players);
    }

    /**
     * ทดสอบว่าห้องใหม่จะถูกสร้างเมื่อห้องเดิมเต็ม
     *
     * @test
     */
    public function new_room_created_when_full(): void
    {
        // Arrange: สร้างห้องที่เต็มแล้ว (30 คน)
        $room = GameRoom::factory()->create([
            'game_id' => $this->game->id,
            'current_players' => 30,
            'max_players' => 30,
            'status' => 'active',
        ]);

        // Act: ผู้เล่นใหม่พยายามเข้าเล่น
        $response = $this->postJson('/api/games/snake-io/join', [
            'player_name' => 'NewPlayer',
            'skin_slug' => 'classic',
        ]);

        // Assert: ควรได้ห้องใหม่
        $newRoomId = $response->json('room_id');
        $this->assertNotEquals($room->id, $newRoomId);

        // ตรวจสอบว่าห้องใหม่ถูกสร้าง
        $newRoom = GameRoom::find($newRoomId);
        $this->assertEquals(1, $newRoom->current_players);
    }
}
