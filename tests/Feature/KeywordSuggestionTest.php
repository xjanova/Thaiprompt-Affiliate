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
     * 🔧 (2026-08-10) เรียกเมธอด private ของ service ผ่าน Reflection
     *
     * เดิมเทสต์เรียก analyzePatterns()/getNoMatchMessages() ตรง ๆ ทั้งที่เป็น private
     * → Error ทันที ไม่เคยได้ทดสอบอะไรเลย
     *
     * เลือก Reflection แทนการเปลี่ยนเมธอดเป็น public โดยตั้งใจ —
     * การเปิด API ของ service ให้กว้างขึ้นเพียงเพื่อให้เทสต์ผ่าน คือการแก้ที่ผิดฝั่ง
     * (แพทเทิร์นเดียวกับ tests/Unit/Services/FortuneDailySoftInviteTest)
     */
    private function callPrivate(string $method, ...$args)
    {
        $m = new \ReflectionMethod($this->suggestionService, $method);
        $m->setAccessible(true);

        return $m->invoke($this->suggestionService, ...$args);
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
            // 🔧 (2026-08-10) คีย์จริงของ controller คือ 'suggestions' ไม่ใช่ 'data'
            //    ยึดตาม controller เพราะเป็น API ที่หน้าแอดมินใช้อยู่จริง —
            //    เปลี่ยนชื่อคีย์ฝั่ง response เพื่อให้เทสต์ผ่าน = ทำ UI ที่ใช้งานอยู่พัง
            ->assertJsonStructure([
                'success',
                'suggestions' => [
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
            // 🔧 (2026-08-10) controller ห่อไว้ใต้คีย์ 'data' (ยึดตาม API จริง)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_no_matches',
                    'unique_no_matches',
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
            // 🔧 (2026-08-10) controller ห่อไว้ใต้คีย์ 'data' (ยึดตาม API จริง)
            ->assertJsonStructure([
                'success',
                // 🔧 (2026-08-10) รูปจริงคือ type/message/action — ไม่มี priority/suggestion เลย
                //    ยืนยันจาก blade ทุกหน้าในโซนนี้ที่อ่าน $rec['type'|'message'|'action']
                'data' => [
                    '*' => [
                        'type',
                        'message',
                        'action',
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
            // 🔧 (2026-08-10) controller validate 'trigger_words' => 'required|json'
            //    = ต้องเป็น **สตริง JSON** ไม่ใช่ array · และ 'priority' เป็น required ด้วย
            //    ของเดิมส่ง array + ไม่ส่ง priority → 422 ทุกครั้ง
            ->postJson(route('admin.line-bot.keywords.suggestions.approve'), [
                'keyword' => $suggestion['keyword'],
                'trigger_words' => json_encode($suggestion['trigger_words']),
                'category' => 'support',
                'response_type' => 'text',
                'response_text' => 'ตอบกลับแบบอัตโนมัติ',
                'priority' => 50,
            ]);

        // Assert
        // 🔧 (2026-08-10) approve() เป็น action ของฟอร์มแอดมิน → คืน **redirect**
        //    ไปหน้า keywords.index พร้อม flash success ไม่ใช่ JSON envelope
        //    (ต่างจาก approveBatch ที่คืน JSON จริง) → ยึดพฤติกรรมจริงของ controller
        $response->assertRedirect(route('admin.line-bot.keywords.index'));
        $response->assertSessionHas('success');

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
            // 🔧 (2026-08-10) เหมือนกับ approve เดี่ยว — trigger_words ต้องเป็นสตริง JSON
            //    และ priority เป็น required (suggestions.*.priority)
            return [
                'keyword' => $s['keyword'],
                'trigger_words' => json_encode($s['trigger_words']),
                'category' => 'faq',
                'response_type' => 'text',
                'response_text' => 'ตอบกลับอัตโนมัติ',
                'priority' => 50,
            ];
        })->toArray();

        // Act
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.line-bot.keywords.suggestions.approve-batch'), [
                'suggestions' => $suggestionsToApprove,
            ]);

        // Assert
        // 🔧 (2026-08-10) เดิมยึดเลข 3 ตายตัว แต่ตัววิเคราะห์อาจได้ suggestion ไม่ถึง 3
        //    จากข้อความชุดทดสอบ (take(3) ได้เท่าที่มี) → เทสต์เลยแดงทั้งที่ batch ทำงานถูก
        //    วัดจาก "ที่ส่งไปจริง" แทน แล้วยืนยันว่าถูกสร้างครบทุกตัว = ตรงกับสิ่งที่ทดสอบ
        $response->assertStatus(200);

        $this->assertNotEmpty($suggestionsToApprove, 'ต้องมี suggestion อย่างน้อย 1 ตัวไปให้ batch ทำงาน');

        foreach ($suggestionsToApprove as $submitted) {
            $this->assertDatabaseHas('line_bot_keywords', [
                'keyword' => $submitted['keyword'],
                'category' => 'faq',
            ]);
        }
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
            // 🔧 (2026-08-10) พารามิเตอร์ที่ 2 ของ getJson() คือ **headers** ไม่ใช่ query string
            //    'keyword' เลยถูกส่งไปเป็น HTTP header → controller มองไม่เห็น → 422
            //    ต้องผูกเข้ากับ URL ผ่าน route() แทน
            ->getJson(route('admin.line-bot.keywords.suggestions.detail', [
                'keyword' => $suggestion['keyword'],
            ]));

        // Assert
        // 🔧 (2026-08-10) getDetail คืนคีย์ 'data' ไม่ใช่ 'detail'
        //    และ suggestion ที่ service สร้างไม่มีฟิลด์ 'trends' (มี keyword/trigger_words/
        //    frequency/confidence/sample_messages) → ยึดรูปจริงของ API
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'keyword',
                    'trigger_words',
                    'frequency',
                    'confidence',
                    'sample_messages',
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
            // 🔧 (2026-08-10) controller คืน 'suggestions' (ตัวรายการ) ไม่ใช่ 'suggestions_count'
            ->assertJsonStructure([
                'success',
                'suggestions',
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
        // 🔧 (2026-08-10) endpoint นี้เป็น **ไฟล์ดาวน์โหลด** (streamDownload) ไม่ใช่ JSON envelope
        //    ของจริงคือ array ดิบของ suggestions ไม่มีคีย์ success/data/exported_at เลย
        //    เทสต์เดิมเช็คสัญญาที่ไม่เคยมีอยู่ → ต้องยึดพฤติกรรมจริงของ controller
        $response->assertStatus(200);
        $this->assertStringStartsWith('application/json', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));

        $payload = json_decode($response->streamedContent(), true);
        $this->assertIsArray($payload);

        foreach ($payload as $row) {
            $this->assertArrayHasKey('keyword', $row);
            $this->assertArrayHasKey('trigger_words', $row);
            $this->assertArrayHasKey('frequency', $row);
            $this->assertArrayHasKey('confidence', $row);
        }
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
        $suggestions = $this->callPrivate('analyzePatterns', $messages);

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
        // 🔧 (2026-08-10) คีย์จริงคือ unique_no_matches — ยืนยันจาก
        //    resources/views/admin/line-bot/keywords/suggestions.blade.php:72 ที่ใช้คีย์นี้อยู่
        //    (ถ้าเปลี่ยนฝั่ง service ให้ตรงเทสต์ หน้าแอดมินจะพังทันที)
        $this->assertGreaterThan(0, $statistics['unique_no_matches']);
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
        // 🔧 (2026-08-10) ไม่มีคีย์ priority — service คืน type/message/action
        $this->assertArrayHasKey('message', $recommendation);
        $this->assertArrayHasKey('action', $recommendation);
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
        // 🔧 (2026-08-10) createKeywordDraft คืนโมเดลที่ **ยังไม่ได้ save** โดยตั้งใจ
        //    (ชื่อก็บอกว่า draft — controller::preview เอาไปโชว์ตัวอย่างโดยไม่บันทึก)
        //    id จึงเป็น null เสมอ · สิ่งที่ควรยืนยันคือ "เป็นดราฟต์" + ค่าที่ประกอบมาถูก
        $this->assertFalse($keyword->exists, 'draft ต้องยังไม่ถูกบันทึกลงฐานข้อมูล');
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
        $messages = $this->callPrivate('getNoMatchMessages', 30);

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
            'shipping ไป', 'การจัดส่ง ช้า',
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
                'line_user_id' => 'U'.(($i % 5) + 1),
                'action_type' => 'no_match',
                'created_at' => now()->subMinutes($i),
                'timestamp' => now()->subMinutes($i),
            ]);
        }
    }
}
