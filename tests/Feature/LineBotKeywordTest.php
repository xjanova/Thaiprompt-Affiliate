<?php

namespace Tests\Feature;

use App\Models\LineBotKeyword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * LINE Bot Keyword Management Tests
 *
 * ทดสอบระบบจัดการ Keywords สำหรับ Hybrid Bot
 */
class LineBotKeywordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin user for testing
     */
    protected $admin;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    /**
     * ทดสอบแสดงรายการ keywords
     */
    public function test_can_view_keywords_list()
    {
        // Create sample keywords
        LineBotKeyword::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.index'));

        $response->assertStatus(200)
            ->assertViewHas('keywords')
            ->assertViewHas('stats');
    }

    /**
     * ทดสอบการสร้าง keyword
     */
    public function test_can_create_keyword()
    {
        $data = [
            'keyword' => 'refund',
            'description' => 'Refund policy',
            'trigger_words' => 'refund, คืนเงิน, return',
            'response_type' => 'text',
            'response_text' => '💰 Refund policy...',
            'category' => 'faq',
            'priority' => 50,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.line-bot.keywords.store'), $data);

        $response->assertRedirect(route('admin.line-bot.keywords.index'));
        $this->assertDatabaseHas('line_bot_keywords', ['keyword' => 'refund']);
    }

    /**
     * ทดสอบการแก้ไข keyword
     */
    public function test_can_update_keyword()
    {
        $keyword = LineBotKeyword::factory()->create();

        $data = [
            'keyword' => 'updated_keyword',
            'description' => 'Updated description',
            'trigger_words' => 'test, ทดสอบ',
            'response_type' => 'text',
            'response_text' => 'Updated response',
            'category' => 'support',
            'priority' => 75,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.line-bot.keywords.update', $keyword), $data);

        $response->assertRedirect(route('admin.line-bot.keywords.index'));
        $this->assertDatabaseHas('line_bot_keywords', ['keyword' => 'updated_keyword']);
    }

    /**
     * ทดสอบการลบ keyword
     */
    public function test_can_delete_keyword()
    {
        $keyword = LineBotKeyword::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.line-bot.keywords.destroy', $keyword));

        $response->assertRedirect(route('admin.line-bot.keywords.index'));
        $this->assertDatabaseMissing('line_bot_keywords', ['id' => $keyword->id]);
    }

    /**
     * ทดสอบการทดสอบ keyword
     */
    public function test_can_test_keyword()
    {
        LineBotKeyword::factory()->create([
            'keyword' => 'refund',
            'trigger_words' => ['refund', 'คืนเงิน'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.line-bot.keywords.test'), [
                'message' => 'Can I get refund?',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'matched' => true,
                'keyword' => 'refund',
            ]);
    }

    /**
     * ทดสอบการทดสอบข้อความที่ไม่ตรง
     */
    public function test_keyword_test_not_matched()
    {
        LineBotKeyword::factory()->create([
            'keyword' => 'refund',
            'trigger_words' => ['refund', 'คืนเงิน'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.line-bot.keywords.test'), [
                'message' => 'Hello world',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'matched' => false,
            ]);
    }

    /**
     * ทดสอบ keyword matching logic
     */
    public function test_keyword_matches_correctly()
    {
        $keyword = LineBotKeyword::factory()->create([
            'trigger_words' => ['refund', 'คืนเงิน', 'return'],
        ]);

        // Should match
        $this->assertTrue($keyword->matches('Can I refund?'));
        $this->assertTrue($keyword->matches('REFUND'));
        $this->assertTrue($keyword->matches('Can I get a refund'));
        $this->assertTrue($keyword->matches('คืนเงินได้ไหม'));

        // Should not match
        $this->assertFalse($keyword->matches('Hello world'));
        $this->assertFalse($keyword->matches('Payment'));
    }

    /**
     * ทดสอบ keyword priority sorting
     */
    public function test_keywords_sorted_by_priority()
    {
        LineBotKeyword::factory()->create(['priority' => 30]);
        LineBotKeyword::factory()->create(['priority' => 90]);
        LineBotKeyword::factory()->create(['priority' => 60]);

        $keywords = LineBotKeyword::orderBy('priority', 'desc')->get();

        $this->assertEquals(90, $keywords[0]->priority);
        $this->assertEquals(60, $keywords[1]->priority);
        $this->assertEquals(30, $keywords[2]->priority);
    }

    /**
     * ทดสอบการจัดการ active/inactive status
     */
    public function test_can_toggle_keyword_status()
    {
        $keyword = LineBotKeyword::factory()->create(['is_active' => true]);

        // Check active keywords
        $this->assertDatabaseHas('line_bot_keywords', [
            'id' => $keyword->id,
            'is_active' => true,
        ]);

        // Update to inactive
        $keyword->update(['is_active' => false]);

        $this->assertDatabaseHas('line_bot_keywords', [
            'id' => $keyword->id,
            'is_active' => false,
        ]);
    }

    /**
     * ทดสอบการ clone keyword
     */
    public function test_can_clone_keyword()
    {
        $original = LineBotKeyword::factory()->create([
            'keyword' => 'original',
            'trigger_words' => ['test', 'ทดสอบ'],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.line-bot.keywords.clone', $original));

        // Should have new keyword with _copy suffix
        $cloned = LineBotKeyword::where('keyword', 'like', 'original_copy_%')->first();
        $this->assertNotNull($cloned);
        $this->assertEquals($original->trigger_words, $cloned->trigger_words);
        $this->assertFalse($cloned->is_active);
    }

    /**
     * ทดสอบ API: ดึงรายการ keywords
     */
    public function test_api_can_list_keywords()
    {
        $user = User::factory()->create();
        LineBotKeyword::factory()->count(3)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/line-bot/keywords/');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'keyword',
                        'trigger_words',
                        'response_type',
                    ],
                ],
                'pagination',
            ]);
    }

    /**
     * ทดสอบ API: สร้าง keyword
     */
    public function test_api_can_create_keyword()
    {
        $user = User::factory()->create();

        $data = [
            'keyword' => 'api_test',
            'trigger_words' => ['test', 'ทดสอบ'],
            'response_type' => 'text',
            'response_text' => 'Test response',
            'category' => 'custom',
            'priority' => 50,
            'is_active' => true,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/line-bot/keywords/', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('line_bot_keywords', ['keyword' => 'api_test']);
    }

    /**
     * ทดสอบ API: ทดสอบ keyword
     */
    public function test_api_can_test_keyword()
    {
        $user = User::factory()->create();
        LineBotKeyword::factory()->create([
            'keyword' => 'refund',
            'trigger_words' => ['refund', 'คืนเงิน'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/line-bot/keywords/test', [
                'message' => 'Can I get a refund?',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'matched' => true,
            ]);
    }

    /**
     * ทดสอบ API: ดึง statistics
     */
    public function test_api_can_get_statistics()
    {
        $user = User::factory()->create();
        LineBotKeyword::factory()->count(5)->create(['category' => 'faq']);
        LineBotKeyword::factory()->count(3)->create(['category' => 'support']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/line-bot/keywords/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 8,
                ],
            ]);
    }

    /**
     * ทดสอบ validation errors
     */
    public function test_keyword_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.line-bot.keywords.store'), []);

        $response->assertSessionHasErrors(['keyword', 'trigger_words', 'response_type', 'category', 'priority']);
    }

    /**
     * ทดสอบ keyword uniqueness
     */
    public function test_keyword_must_be_unique()
    {
        LineBotKeyword::factory()->create(['keyword' => 'unique_test']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.line-bot.keywords.store'), [
                'keyword' => 'unique_test',
                'trigger_words' => 'test',
                'response_type' => 'text',
                'response_text' => 'test',
                'category' => 'custom',
                'priority' => 50,
            ]);

        $response->assertSessionHasErrors('keyword');
    }
}
