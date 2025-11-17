<?php

namespace Tests\Feature;

use App\Models\LineBotKeyword;
use App\Models\User;
use App\Services\KeywordActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Keyword Activity Logging Tests
 *
 * ทดสอบระบบบันทึกกิจกรรมของ keywords
 */
class KeywordActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private KeywordActivityLogService $service;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(KeywordActivityLogService::class);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /**
     * ทดสอบการบันทึก keyword match
     */
    public function test_can_log_keyword_match(): void
    {
        // Arrange
        $keyword = LineBotKeyword::factory()->create([
            'keyword' => 'refund',
            'times_matched' => 0,
        ]);

        // Act
        $this->service->logKeywordMatch(
            $keyword,
            'Can I get a refund?',
            'U1234567890abcdef1234567890abcdef'
        );

        // Assert
        $this->assertDatabaseHas('keyword_activity_logs', [
            'keyword_id' => $keyword->id,
            'keyword_name' => 'refund',
            'action_type' => 'matched',
            'user_message' => 'Can I get a refund?',
        ]);

        // Verify counter was incremented
        $this->assertDatabaseHas('line_bot_keywords', [
            'id' => $keyword->id,
            'times_matched' => 1,
        ]);
    }

    /**
     * ทดสอบการบันทึก no match (ไป AI)
     */
    public function test_can_log_no_match(): void
    {
        // Act
        $this->service->logNoMatch(
            'Hello, how are you?',
            'U1234567890abcdef1234567890abcdef'
        );

        // Assert
        $this->assertDatabaseHas('keyword_activity_logs', [
            'keyword_id' => null,
            'keyword_name' => null,
            'action_type' => 'no_match',
            'user_message' => 'Hello, how are you?',
            'response_type' => 'ai_bot',
        ]);
    }

    /**
     * ทดสอบการดึง statistics
     */
    public function test_can_get_keyword_stats(): void
    {
        // Arrange
        $keyword = LineBotKeyword::factory()->create();

        // บันทึก matches
        $this->service->logKeywordMatch($keyword, 'test', 'user1');
        $this->service->logKeywordMatch($keyword, 'test', 'user2');
        $this->service->logNoMatch('no match', 'user1');

        // Act
        $stats = $this->service->getKeywordStats();

        // Assert
        $this->assertEquals(3, $stats['total_matches']);
        $this->assertArrayHasKey('unique_users', $stats);
        $this->assertArrayHasKey('by_category', $stats);
        $this->assertArrayHasKey('by_response_type', $stats);
    }

    /**
     * ทดสอบการ export CSV
     */
    public function test_can_export_to_csv(): void
    {
        // Arrange
        $keyword = LineBotKeyword::factory()->create(['keyword' => 'test']);
        $this->service->logKeywordMatch($keyword, 'Test message', 'U123456789');
        $this->service->logNoMatch('No match message', 'U987654321');

        // Act
        $csv = $this->service->exportToCSV(30);

        // Assert
        $this->assertStringContainsString('Date,Time,Keyword,Category', $csv);
        $this->assertStringContainsString('test', $csv);
        $this->assertStringContainsString('Test message', $csv);
    }

    /**
     * ทดสอบการดึง user conversation history
     */
    public function test_can_get_user_history(): void
    {
        // Arrange
        $keyword = LineBotKeyword::factory()->create();
        $lineUserId = 'U1234567890abcdef1234567890abcdef';

        // บันทึก user activity
        for ($i = 0; $i < 5; $i++) {
            $this->service->logKeywordMatch($keyword, "Message $i", $lineUserId);
        }

        // Act
        $history = $this->service->getUserHistory($lineUserId, 10);

        // Assert
        $this->assertCount(5, $history);
        $this->assertEquals($lineUserId, $history[0]['line_user_id']);
    }

    /**
     * ทดสอบการ clear old logs
     */
    public function test_can_clear_old_logs(): void
    {
        // Arrange
        $keyword = LineBotKeyword::factory()->create();

        // สร้าง old log (มากกว่า 90 วัน)
        DB::table('keyword_activity_logs')->insert([
            'keyword_id' => $keyword->id,
            'keyword_name' => 'old',
            'user_message' => 'old message',
            'line_user_id' => 'U123',
            'action_type' => 'matched',
            'created_at' => now()->subDays(100),
            'timestamp' => now()->subDays(100),
        ]);

        // สร้าง new log (เมื่อ 10 วันที่แล้ว)
        DB::table('keyword_activity_logs')->insert([
            'keyword_id' => $keyword->id,
            'keyword_name' => 'new',
            'user_message' => 'new message',
            'line_user_id' => 'U456',
            'action_type' => 'matched',
            'created_at' => now()->subDays(10),
            'timestamp' => now()->subDays(10),
        ]);

        // Act
        $deleted = $this->service->clearOldLogs(90);

        // Assert
        $this->assertEquals(1, $deleted);
        $this->assertDatabaseHas('keyword_activity_logs', ['keyword_name' => 'new']);
        $this->assertDatabaseMissing('keyword_activity_logs', ['keyword_name' => 'old']);
    }

    /**
     * ทดสอบ admin panel activity logs
     */
    public function test_admin_can_view_activity_logs(): void
    {
        // Arrange
        $keyword = LineBotKeyword::factory()->create();
        $this->service->logKeywordMatch($keyword, 'test', 'U123');
        $this->service->logNoMatch('no match', 'U456');

        // Act
        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.activity.index'));

        // Assert
        $response->assertStatus(200)
            ->assertViewHas('logs')
            ->assertViewHas('stats');
    }

    /**
     * ทดสอบ filter activity logs
     */
    public function test_can_filter_activity_logs(): void
    {
        // Arrange
        $keyword1 = LineBotKeyword::factory()->create(['keyword' => 'refund']);
        $keyword2 = LineBotKeyword::factory()->create(['keyword' => 'shipping']);

        $this->service->logKeywordMatch($keyword1, 'refund query', 'U123');
        $this->service->logKeywordMatch($keyword2, 'shipping query', 'U456');
        $this->service->logNoMatch('other query', 'U789');

        // Act - Filter by keyword
        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.activity.index', ['keyword' => 'refund']));

        // Assert
        $response->assertStatus(200)
            ->assertViewHas('logs');

        // The view should contain filtered logs
        $this->assertDatabaseHas('keyword_activity_logs', [
            'keyword_name' => 'refund',
        ]);
    }

    /**
     * ทดสอบ export CSV from admin
     */
    public function test_admin_can_export_activity_logs_csv(): void
    {
        // Arrange
        $keyword = LineBotKeyword::factory()->create();
        $this->service->logKeywordMatch($keyword, 'test', 'U123');

        // Act
        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.activity.export'));

        // Assert
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv');
    }

    /**
     * ทดสอบ get keyword stats API
     */
    public function test_can_get_keyword_stats_api(): void
    {
        // Arrange
        LineBotKeyword::factory()->count(3)->create(['times_matched' => 5]);

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.activity.keyword-stats'));

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ]);
    }

    /**
     * ทดสอบ get user history API
     */
    public function test_can_get_user_history_api(): void
    {
        // Arrange
        $keyword = LineBotKeyword::factory()->create();
        $lineUserId = 'U1234567890abcdef1234567890abcdef';

        $this->service->logKeywordMatch($keyword, 'test 1', $lineUserId);
        $this->service->logKeywordMatch($keyword, 'test 2', $lineUserId);

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.activity.user-history', [
                'line_user_id' => $lineUserId,
            ]));

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.0.line_user_id', $lineUserId);
    }

    /**
     * ทดสอบ daily activity chart data
     */
    public function test_can_get_daily_activity_chart_data(): void
    {
        // Arrange
        $keyword = LineBotKeyword::factory()->create();

        // สร้าง activity สำหรับวันนี้
        $this->service->logKeywordMatch($keyword, 'match 1', 'U123');
        $this->service->logNoMatch('no match 1', 'U456');

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.activity.daily-chart'));

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'labels',
                'datasets',
            ]);
    }

    /**
     * ทดสอบ multiple logs จากผู้ใช้คนเดียวกัน
     */
    public function test_multiple_logs_from_same_user(): void
    {
        // Arrange
        $keyword1 = LineBotKeyword::factory()->create();
        $keyword2 = LineBotKeyword::factory()->create();
        $lineUserId = 'U1234567890abcdef1234567890abcdef';

        // Act - User sends multiple messages
        $this->service->logKeywordMatch($keyword1, 'message 1', $lineUserId);
        $this->service->logKeywordMatch($keyword2, 'message 2', $lineUserId);
        $this->service->logNoMatch('message 3', $lineUserId);

        // Assert
        $logs = DB::table('keyword_activity_logs')
            ->where('line_user_id', $lineUserId)
            ->get();

        $this->assertCount(3, $logs);

        // Verify both keywords were matched
        $this->assertDatabaseHas('line_bot_keywords', [
            'id' => $keyword1->id,
            'times_matched' => 1,
        ]);

        $this->assertDatabaseHas('line_bot_keywords', [
            'id' => $keyword2->id,
            'times_matched' => 1,
        ]);
    }

    /**
     * ทดสอบ times_matched counter increment
     */
    public function test_times_matched_counter_increments(): void
    {
        // Arrange
        $keyword = LineBotKeyword::factory()->create(['times_matched' => 5]);

        // Act - Log multiple matches
        for ($i = 0; $i < 3; $i++) {
            $this->service->logKeywordMatch($keyword, 'test', "user$i");
        }

        // Assert
        $this->assertDatabaseHas('line_bot_keywords', [
            'id' => $keyword->id,
            'times_matched' => 8, // 5 + 3
        ]);
    }
}
