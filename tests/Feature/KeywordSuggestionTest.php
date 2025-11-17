<?php

namespace Tests\Feature;

use App\Models\LineBotKeyword;
use App\Models\User;
use App\Services\KeywordSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Keyword Suggestion Engine Tests
 *
 * ทดสอบระบบแนะนำคีย์เวิร์ดจากข้อความที่ไม่เข้าใจ
 */
class KeywordSuggestionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private KeywordSuggestionService $suggestionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->suggestionService = app(KeywordSuggestionService::class);
    }

    /**
     * ทดสอบการแสดง suggestions dashboard
     */
    public function test_can_view_suggestions_dashboard(): void
    {
        // Arrange
        $this->createNoMatchMessages();

        // Act
        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.suggestions.index'));

        // Assert
        $response->assertStatus(200)
            ->assertViewHas('suggestions')
            ->assertViewHas('statistics')
            ->assertViewHas('recommendations');
    }

    /**
     * ทดสอบการดึง suggestions เป็น JSON
     */
    public function test_can_get_suggestions_json(): void
    {
        // Arrange
        $this->createNoMatchMessages();

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.suggestions.json'));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'keyword',
                        'trigger_words',
                        'frequency',
                        'confidence',
                        'sample_messages',
                    ],
                ],
                'count',
            ]);
    }

    /**
     * ทดสอบการดึง statistics
     */
    public function test_can_get_statistics(): void
    {
        // Arrange
        $this->createNoMatchMessages(10);

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.suggestions.stats'));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'statistics' => [
                    'total_no_matches',
                    'unique_messages',
                    'suggestions_count',
                    'existing_keywords',
                    'potential_coverage_increase',
                ],
            ]);
    }

    /**
     * ทดสอบการดึง recommendations
     */
    public function test_can_get_recommendations(): void
    {
        // Arrange
        $this->createNoMatchMessages();

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.suggestions.recommendations'));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'recommendations' => [
                    '*' => [
                        'type',
                        'priority',
                        'message',
                        'suggestion',
                    ],
                ],
            ]);
    }

    /**
     * ทดสอบการ preview keyword จากแนะนำ
     */
    public function test_can_preview_keyword_from_suggestion(): void
    {
        // Arrange
        $this->createNoMatchMessages();
        $suggestions = $this->suggestionService->getSuggestions();
        $suggestion = collect($suggestions)->first();

        // Act
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.line-bot.keywords.suggestions.preview'), [
                'keyword' => $suggestion['keyword'],
                'trigger_words' => $suggestion['trigger_words'],
                'category' => 'support',
                'response_type' => 'text',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'preview' => [
                    'keyword',
                    'trigger_words',
                    'category',
                    'response_type',
                ],
            ]);
    }

    /**
     * ทดสอบการ approve suggestion และสร้าง keyword
     */
    public function test_can_approve_suggestion_and_create_keyword(): void
    {
        // Arrange
        $this->createNoMatchMessages();
        $suggestions = $this->suggestionService->getSuggestions();
        $suggestion = collect($suggestions)->first();

        // Act
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.line-bot.keywords.suggestions.approve'), [
                'keyword' => $suggestion['keyword'],
                'trigger_words' => $suggestion['trigger_words'],
                'category' => 'support',
                'response_type' => 'text',
                'response_text' => 'ตอบกลับแบบอัตโนมัติ',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message']);

        // Verify keyword was created
        $this->assertDatabaseHas('line_bot_keywords', [
            'keyword' => $suggestion['keyword'],
            'category' => 'support',
        ]);
    }

    /**
     * ทดสอบการ approve batch suggestions
     */
    public function test_can_approve_batch_suggestions(): void
    {
        // Arrange
        $this->createNoMatchMessages(20);
        $suggestions = $this->suggestionService->getSuggestions();
        $suggestionsToApprove = collect($suggestions)->take(3)->map(function ($s) {
            return [
                'keyword' => $s['keyword'],
                'trigger_words' => $s['trigger_words'],
                'category' => 'faq',
                'response_type' => 'text',
                'response_text' => 'ตอบกลับอัตโนมัติ',
            ];
        })->toArray();

        // Act
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.line-bot.keywords.suggestions.approve-batch'), [
                'suggestions' => $suggestionsToApprove,
            ]);

        // Assert
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(3, LineBotKeyword::count());
    }

    /**
     * ทดสอบการ reject suggestion
     */
    public function test_can_reject_suggestion(): void
    {
        // Arrange
        $this->createNoMatchMessages();
        $suggestions = $this->suggestionService->getSuggestions();
        $suggestion = collect($suggestions)->first();

        // Act
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.line-bot.keywords.suggestions.reject'), [
                'keyword' => $suggestion['keyword'],
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message']);
    }

    /**
     * ทดสอบการดึง detail ของ suggestion
     */
    public function test_can_get_suggestion_detail(): void
    {
        // Arrange
        $this->createNoMatchMessages();
        $suggestions = $this->suggestionService->getSuggestions();
        $suggestion = collect($suggestions)->first();

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.suggestions.detail'), [
                'keyword' => $suggestion['keyword'],
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'detail' => [
                    'keyword',
                    'trigger_words',
                    'frequency',
                    'confidence',
                    'sample_messages',
                    'trends',
                ],
            ]);
    }

    /**
     * ทดสอบการ refresh suggestions (re-analyze messages)
     */
    public function test_can_refresh_suggestions(): void
    {
        // Arrange
        $this->createNoMatchMessages();

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.suggestions.refresh'));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'suggestions_count',
                'statistics',
            ]);
    }

    /**
     * ทดสอบการ export suggestions
     */
    public function test_can_export_suggestions(): void
    {
        // Arrange
        $this->createNoMatchMessages();

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.suggestions.export'));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'keyword',
                        'trigger_words',
                        'frequency',
                        'confidence',
                    ],
                ],
                'exported_at',
            ]);
    }

    /**
     * ทดสอบ pattern extraction จากข้อความ
     */
    public function test_pattern_extraction_finds_keywords(): void
    {
        // Arrange
        $messages = collect([
            'refund ขอคืนเงินหน่อย',
            'refund ไม่พอใจการซื้อ',
            'เรียนด้วยเรื่อง refund',
            'shipping มาช้า',
            'shipping ไม่ได้รับ',
        ]);

        // Act
        $suggestions = $this->suggestionService->analyzePatterns($messages);

        // Assert
        $this->assertGreaterThan(0, $suggestions->count());
        $keywordNames = $suggestions->pluck('keyword')->toArray();
        $this->assertTrue(in_array('refund', $keywordNames) || in_array('shipping', $keywordNames));
    }

    /**
     * ทดสอบการ filter suggestions โดยความถี่ต่ำสุด
     */
    public function test_suggestions_respect_minimum_frequency(): void
    {
        // Arrange
        $messages = collect([
            'refund ขอคืนเงิน',
            'refund ไม่พอใจ',
            'refund ลองใหม่',
            'rare_word ที่ปรากฏครั้งเดียว',
        ]);

        // Insert no-match messages
        foreach ($messages as $message) {
            DB::table('keyword_activity_logs')->insert([
                'user_message' => $message,
                'line_user_id' => 'U123',
                'action_type' => 'no_match',
                'created_at' => now(),
                'timestamp' => now(),
            ]);
        }

        // Act
        $suggestions = $this->suggestionService->getSuggestions(30, 3);

        // Assert - refund should be included (3+ times), rare_word should not be
        $suggestionKeywords = collect($suggestions)->pluck('keyword')->toArray();
        $this->assertTrue(in_array('refund', $suggestionKeywords));
        $this->assertFalse(in_array('rare_word', $suggestionKeywords));
    }

    /**
     * ทดสอบการคำนวณ confidence score
     */
    public function test_confidence_score_calculation(): void
    {
        // Arrange
        $this->createNoMatchMessages(100);
        $suggestions = $this->suggestionService->getSuggestions();

        // Act
        $suggestion = collect($suggestions)->first();

        // Assert
        $this->assertIsNumeric($suggestion['confidence']);
        $this->assertGreaterThanOrEqual(0, $suggestion['confidence']);
        $this->assertLessThanOrEqual(100, $suggestion['confidence']);
    }

    /**
     * ทดสอบการแยกเพศระหว่าง existing keywords และ new suggestions
     */
    public function test_suggestions_exclude_existing_keywords(): void
    {
        // Arrange - สร้าง existing keyword
        LineBotKeyword::factory()->create(['keyword' => 'refund']);

        // สร้าง no-match messages พูดเกี่ยวกับ refund
        $this->createNoMatchMessages();

        // Act
        $suggestions = $this->suggestionService->getUniqueNewSuggestions();

        // Assert
        $suggestionKeywords = collect($suggestions)->pluck('keyword')->toArray();
        $this->assertFalse(in_array('refund', $suggestionKeywords));
    }

    /**
     * ทดสอบการ extract sample messages สำหรับ suggestion
     */
    public function test_sample_messages_are_extracted(): void
    {
        // Arrange
        $this->createNoMatchMessages();
        $suggestions = $this->suggestionService->getSuggestions();
        $suggestion = collect($suggestions)->first();

        // Assert
        $this->assertIsArray($suggestion['sample_messages']);
        $this->assertGreaterThan(0, count($suggestion['sample_messages']));
        $this->assertLessThanOrEqual(3, count($suggestion['sample_messages']));
    }

    /**
     * ทดสอบการคำนวณ statistics
     */
    public function test_statistics_calculation(): void
    {
        // Arrange
        $this->createNoMatchMessages(50);

        // Act
        $statistics = $this->suggestionService->getStatistics();

        // Assert
        $this->assertIsArray($statistics);
        $this->assertGreaterThan(0, $statistics['total_no_matches']);
        $this->assertGreaterThan(0, $statistics['unique_messages']);
        $this->assertIsNumeric($statistics['potential_coverage_increase']);
    }

    /**
     * ทดสอบการดึง recommendations
     */
    public function test_recommendations_are_generated(): void
    {
        // Arrange
        $this->createNoMatchMessages(30);

        // Act
        $recommendations = $this->suggestionService->getRecommendations();

        // Assert
        $this->assertIsArray($recommendations);
        $this->assertGreaterThan(0, count($recommendations));
        $recommendation = $recommendations[0];
        $this->assertArrayHasKey('type', $recommendation);
        $this->assertArrayHasKey('priority', $recommendation);
    }

    /**
     * ทดสอบการสร้าง keyword draft จากแนะนำ
     */
    public function test_can_create_keyword_draft_from_suggestion(): void
    {
        // Arrange
        $suggestion = [
            'keyword' => 'test_keyword',
            'trigger_words' => ['test', 'ทดสอบ'],
            'category' => 'faq',
            'response_type' => 'text',
        ];

        // Act
        $keyword = $this->suggestionService->createKeywordDraft($suggestion);

        // Assert
        $this->assertIsNotNull($keyword->id);
        $this->assertEquals('test_keyword', $keyword->keyword);
    }

    /**
     * ทดสอบการดึง no-match messages สำหรับช่วงเวลา
     */
    public function test_no_match_messages_filtered_by_date_range(): void
    {
        // Arrange
        $oldMessage = DB::table('keyword_activity_logs')->insert([
            'user_message' => 'old message 60 days ago',
            'line_user_id' => 'U123',
            'action_type' => 'no_match',
            'created_at' => now()->subDays(60),
            'timestamp' => now()->subDays(60),
        ]);

        $recentMessage = DB::table('keyword_activity_logs')->insert([
            'user_message' => 'recent message',
            'line_user_id' => 'U123',
            'action_type' => 'no_match',
            'created_at' => now(),
            'timestamp' => now(),
        ]);

        // Act
        $messages = $this->suggestionService->getNoMatchMessages(30);

        // Assert
        $this->assertEquals(1, $messages->count());
    }

    /**
     * ทดสอบ idempotency ของ suggestions (ไม่เปลี่ยนแปลง)
     */
    public function test_suggestions_are_consistent(): void
    {
        // Arrange
        $this->createNoMatchMessages();

        // Act
        $suggestions1 = $this->suggestionService->getSuggestions();
        $suggestions2 = $this->suggestionService->getSuggestions();

        // Assert
        $this->assertEquals(
            json_encode(collect($suggestions1)->pluck('keyword')->toArray()),
            json_encode(collect($suggestions2)->pluck('keyword')->toArray())
        );
    }

    /**
     * Helper: สร้าง no-match messages สำหรับทดสอบ
     */
    private function createNoMatchMessages(int $count = 15): void
    {
        $messages = [
            'ขอคืนเงิน',
            'refund',
            'เรียนเรื่องคืนเงิน',
            'ไม่พอใจการซื้อ',
            'ขอ refund หน่อย',
            'ขอ refund ตอนนี้',
            'shipping ไป','การจัดส่ง ช้า',
            'shipping มาไหม',
            'delivery ยังไม่ได้รับ',
            'ยังไม่ได้ shipping',
            'กรุณาส่ง shipping',
            'ปัญหาการชำระเงิน',
            'payment error',
            'ตรวจสอบการชำระเงิน',
            'ปัญหา payment',
        ];

        for ($i = 0; $i < $count; $i++) {
            DB::table('keyword_activity_logs')->insert([
                'user_message' => $messages[$i % count($messages)],
                'line_user_id' => 'U' . (($i % 5) + 1),
                'action_type' => 'no_match',
                'created_at' => now()->subMinutes($i),
                'timestamp' => now()->subMinutes($i),
            ]);
        }
    }
}
