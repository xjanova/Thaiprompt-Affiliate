<?php

namespace Tests\Feature;

use App\Models\MessageSentiment;
use App\Models\User;
use App\Services\SentimentAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sentiment Analysis Tests
 *
 * ทดสอบระบบวิเคราะห์ความรู้สึกจากข้อความผู้ใช้
 */
class SentimentAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private SentimentAnalysisService $sentimentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->sentimentService = app(SentimentAnalysisService::class);
    }

    /**
     * ทดสอบการแสดง sentiment dashboard
     */
    public function test_can_view_sentiment_dashboard(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.sentiment-analysis.index'));

        $response->assertStatus(200)
            ->assertViewHas('sentiments')
            ->assertViewHas('statistics');
    }

    /**
     * ทดสอบการวิเคราะห์ positive sentiment
     */
    public function test_can_analyze_positive_sentiment(): void
    {
        $message = "ขอบคุณค่ะ! ยินดีมากครับ สำนักงานของคุณเยี่ยมมาก!";

        $sentiment = $this->sentimentService->analyzeSentiment($message, 'U123');

        $this->assertNotNull($sentiment->id);
        $this->assertEquals('positive', $sentiment->sentiment);
        $this->assertGreaterThan(0, $sentiment->sentiment_score);
    }

    /**
     * ทดสอบการวิเคราะห์ negative sentiment
     */
    public function test_can_analyze_negative_sentiment(): void
    {
        $message = "โกรธมากครับ! ไม่พอใจเลย ปัญหามากมาย";

        $sentiment = $this->sentimentService->analyzeSentiment($message, 'U124');

        $this->assertNotNull($sentiment->id);
        $this->assertEquals('negative', $sentiment->sentiment);
        $this->assertLessThan(0, $sentiment->sentiment_score);
    }

    /**
     * ทดสอบการวิเคราะห์ neutral sentiment
     */
    public function test_can_analyze_neutral_sentiment(): void
    {
        $message = "ฉันอยากดูเอกสาร";

        $sentiment = $this->sentimentService->analyzeSentiment($message, 'U125');

        $this->assertNotNull($sentiment->id);
        $this->assertEquals('neutral', $sentiment->sentiment);
    }

    /**
     * ทดสอบการตรวจสอบ complaints
     */
    public function test_can_detect_complaints(): void
    {
        $message = "ร้องเรียน ปัญหา refund ไม่ได้คืนเงิน!";

        $sentiment = $this->sentimentService->analyzeSentiment($message, 'U126');

        $this->assertTrue($sentiment->is_complaint);
    }

    /**
     * ทดสอบการตรวจสอบ urgent issues
     */
    public function test_can_detect_urgent_issues(): void
    {
        $message = "ด่วน!!! ปัญหา refund ดำเนินการตอนนี้ชีวิต!!!";

        $sentiment = $this->sentimentService->analyzeSentiment($message, 'U127');

        $this->assertTrue($sentiment->is_urgent);
    }

    /**
     * ทดสอบการตรวจสอบ pain points
     */
    public function test_can_detect_pain_points(): void
    {
        $message = "ปัญหา refund และ shipping ล่าช้า";

        $sentiment = $this->sentimentService->analyzeSentiment($message, 'U128');

        $this->assertNotEmpty($sentiment->detected_issues);
        $this->assertContains('refund', $sentiment->detected_issues);
        $this->assertContains('shipping', $sentiment->detected_issues);
    }

    /**
     * ทดสอบการคำนวณ confidence
     */
    public function test_sentiment_confidence_is_calculated(): void
    {
        $message = "ขอบคุณ ยินดี ดี สุดยอด";

        $sentiment = $this->sentimentService->analyzeSentiment($message, 'U129');

        $this->assertGreaterThan(0, $sentiment->confidence);
        $this->assertLessThanOrEqual(100, $sentiment->confidence);
    }

    /**
     * ทดสอบการตรวจสอบ emotion scores
     */
    public function test_emotion_scores_are_calculated(): void
    {
        $message = "ยินดี โกรธ เศร้า กลัว ประหลาดใจ";

        $sentiment = $this->sentimentService->analyzeSentiment($message, 'U130');

        $this->assertGreaterThanOrEqual(0, $sentiment->joy_score);
        $this->assertGreaterThanOrEqual(0, $sentiment->anger_score);
        $this->assertGreaterThanOrEqual(0, $sentiment->sadness_score);
        $this->assertGreaterThanOrEqual(0, $sentiment->fear_score);
        $this->assertGreaterThanOrEqual(0, $sentiment->surprise_score);
    }

    /**
     * ทดสอบการตรวจสอบ duplicates
     */
    public function test_duplicate_messages_are_not_reanalyzed(): void
    {
        $message = "เดียวกัน";

        $sentiment1 = $this->sentimentService->analyzeSentiment($message, 'U131');
        $sentiment2 = $this->sentimentService->analyzeSentiment($message, 'U132');

        // Second call should return same record
        $this->assertEquals($sentiment1->id, $sentiment2->id);
    }

    /**
     * ทดสอบการดึง sentiment statistics
     */
    public function test_can_get_sentiment_statistics(): void
    {
        $this->createTestSentiments();

        $stats = $this->sentimentService->getSentimentStatistics(30);

        $this->assertGreaterThan(0, $stats['total_messages']);
        $this->assertIsNumeric($stats['positive_percentage']);
        $this->assertIsNumeric($stats['negative_percentage']);
    }

    /**
     * ทดสอบการดึง pain points distribution
     */
    public function test_can_get_pain_points_distribution(): void
    {
        $this->createTestSentiments();

        $painPoints = $this->sentimentService->getPainPointsDistribution(30);

        $this->assertGreaterThan(0, $painPoints->count());
    }

    /**
     * ทดสอบการดึง sentiment trends
     */
    public function test_can_get_sentiment_trends(): void
    {
        $this->createTestSentiments();

        $trend = $this->sentimentService->getSentimentsTrend(30);

        $this->assertGreaterThan(0, $trend->count());
    }

    /**
     * ทดสอบการดึง top complaints
     */
    public function test_can_get_top_complaints(): void
    {
        $this->createTestSentiments();

        $complaints = $this->sentimentService->getTopComplaints(5, 30);

        // May be 0 if no complaints in test data
        $this->assertIsCollection($complaints);
    }

    /**
     * ทดสอบการดึง urgent issues
     */
    public function test_can_get_urgent_issues(): void
    {
        MessageSentiment::create([
            'line_user_id' => 'U999',
            'user_message' => 'ด่วน!!! ปัญหาทันที!!!',
            'message_hash' => MessageSentiment::hashMessage('ด่วน!!! ปัญหาทันที!!!'),
            'sentiment' => 'negative',
            'sentiment_score' => -0.8,
            'confidence' => 85,
            'is_urgent' => true,
        ]);

        $urgent = $this->sentimentService->getUrgentIssues(10);

        $this->assertGreaterThan(0, $urgent->count());
    }

    /**
     * ทดสอบการดึง recommendations
     */
    public function test_can_get_recommendations(): void
    {
        $this->createTestSentiments();

        $recommendations = $this->sentimentService->getRecommendations(30);

        $this->assertIsArray($recommendations);
    }

    /**
     * ทดสอบ positive sentiment scope
     */
    public function test_positive_scope_filters_correctly(): void
    {
        MessageSentiment::create([
            'line_user_id' => 'U200',
            'user_message' => 'ขอบคุณมาก',
            'message_hash' => MessageSentiment::hashMessage('ขอบคุณมาก'),
            'sentiment' => 'positive',
            'sentiment_score' => 0.8,
        ]);

        MessageSentiment::create([
            'line_user_id' => 'U201',
            'user_message' => 'ไม่พอใจ',
            'message_hash' => MessageSentiment::hashMessage('ไม่พอใจ'),
            'sentiment' => 'negative',
            'sentiment_score' => -0.8,
        ]);

        $positiveCount = MessageSentiment::positive()->count();

        $this->assertEquals(1, $positiveCount);
    }

    /**
     * ทดสอบ negative sentiment scope
     */
    public function test_negative_scope_filters_correctly(): void
    {
        MessageSentiment::create([
            'line_user_id' => 'U202',
            'user_message' => 'ไม่ดี',
            'message_hash' => MessageSentiment::hashMessage('ไม่ดี'),
            'sentiment' => 'negative',
            'sentiment_score' => -0.7,
        ]);

        MessageSentiment::create([
            'line_user_id' => 'U203',
            'user_message' => 'ดี',
            'message_hash' => MessageSentiment::hashMessage('ดี'),
            'sentiment' => 'positive',
            'sentiment_score' => 0.7,
        ]);

        $negativeCount = MessageSentiment::negative()->count();

        $this->assertEquals(1, $negativeCount);
    }

    /**
     * ทดสอบการดึง JSON list sentiments
     */
    public function test_can_get_sentiments_json(): void
    {
        $this->createTestSentiments();

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.sentiment-analysis.list-json'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'count',
                'data',
            ]);
    }

    /**
     * ทดสอบการดึง statistics JSON
     */
    public function test_can_get_statistics_json(): void
    {
        $this->createTestSentiments();

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.sentiment-analysis.statistics'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'statistics',
            ]);
    }

    /**
     * ทดสอบการดึง trend data
     */
    public function test_can_get_trend_data(): void
    {
        $this->createTestSentiments();

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.sentiment-analysis.trend-data'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'labels',
                'datasets',
            ]);
    }

    /**
     * ทดสอบการลบ sentiment
     */
    public function test_can_delete_sentiment(): void
    {
        $sentiment = MessageSentiment::create([
            'line_user_id' => 'U300',
            'user_message' => 'test',
            'message_hash' => MessageSentiment::hashMessage('test unique'),
            'sentiment' => 'neutral',
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.line-bot.keywords.sentiment-analysis.destroy', $sentiment));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('message_sentiments', ['id' => $sentiment->id]);
    }

    /**
     * ทดสอบ English sentiment analysis
     */
    public function test_can_analyze_english_sentiment(): void
    {
        $message = "Thank you! Great service!";

        $sentiment = $this->sentimentService->analyzeSentiment($message, 'U400');

        $this->assertEquals('en', $sentiment->language);
        $this->assertEquals('positive', $sentiment->sentiment);
    }

    // ===================== Helper Methods =====================

    /**
     * สร้าง test sentiments
     */
    private function createTestSentiments(): void
    {
        $messages = [
            ['message' => 'ขอบคุณ ยินดี', 'sentiment' => 'positive', 'score' => 0.7],
            ['message' => 'โกรธ ไม่พอใจ', 'sentiment' => 'negative', 'score' => -0.7],
            ['message' => 'ธรรมดา', 'sentiment' => 'neutral', 'score' => 0.0],
        ];

        foreach ($messages as $data) {
            MessageSentiment::create([
                'line_user_id' => 'U' . uniqid(),
                'user_message' => $data['message'],
                'message_hash' => MessageSentiment::hashMessage($data['message'] . uniqid()),
                'sentiment' => $data['sentiment'],
                'sentiment_score' => $data['score'],
                'confidence' => 80,
            ]);
        }
    }
}
