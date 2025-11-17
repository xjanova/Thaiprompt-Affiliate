<?php

namespace Tests\Feature;

use App\Models\KeywordABTest;
use App\Models\KeywordABTestResult;
use App\Models\KeywordABTestVariant;
use App\Models\LineBotKeyword;
use App\Models\User;
use App\Services\KeywordABTestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Keyword A/B Testing System Tests
 *
 * ทดสอบระบบ A/B test สำหรับการทดสอบตัวแปรคีย์เวิร์ด
 */
class KeywordABTestingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private KeywordABTestService $abTestService;
    private LineBotKeyword $keyword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->abTestService = app(KeywordABTestService::class);
        $this->keyword = LineBotKeyword::factory()->create();
    }

    /**
     * ทดสอบการแสดง A/B tests dashboard
     */
    public function test_can_view_ab_tests_dashboard(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.ab-tests.index'));

        $response->assertStatus(200)
            ->assertViewHas('tests')
            ->assertViewHas('stats')
            ->assertViewHas('recommendations');
    }

    /**
     * ทดสอบการแสดงหน้าสร้าง A/B test
     */
    public function test_can_view_create_ab_test_form(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.ab-tests.create'));

        $response->assertStatus(200)
            ->assertViewHas('keywords');
    }

    /**
     * ทดสอบการสร้าง A/B test ใหม่
     */
    public function test_can_create_ab_test(): void
    {
        $data = [
            'keyword_id' => $this->keyword->id,
            'test_name' => 'Test greeting variants',
            'description' => 'Testing different greeting messages',
            'variant_a_percentage' => 50,
            'variant_b_percentage' => 50,
            'winning_criterion' => 'conversion_rate',
            'minimum_samples' => 100,
            'variant_a' => [
                'response_text' => 'สวัสดีค่ะ! ยินดีต้อนรับ',
                'response_type' => 'text',
                'description' => 'Friendly greeting A',
            ],
            'variant_b' => [
                'response_text' => 'สวัสดี! ต้องการช่วยเหลือไหม?',
                'response_type' => 'text',
                'description' => 'Friendly greeting B with question',
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.line-bot.keywords.ab-tests.store'), $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('keyword_ab_tests', [
            'keyword_id' => $this->keyword->id,
            'test_name' => 'Test greeting variants',
            'status' => 'planning',
        ]);

        // Verify variants were created
        $this->assertEquals(2, KeywordABTestVariant::count());
    }

    /**
     * ทดสอบการตรวจสอบ percentages (ต้องรวมกัน 100%)
     */
    public function test_variant_percentages_must_equal_100(): void
    {
        $data = [
            'keyword_id' => $this->keyword->id,
            'test_name' => 'Test',
            'variant_a_percentage' => 60,
            'variant_b_percentage' => 50, // Sum = 110, invalid!
            'winning_criterion' => 'conversion_rate',
            'variant_a' => [
                'response_text' => 'A',
                'response_type' => 'text',
            ],
            'variant_b' => [
                'response_text' => 'B',
                'response_type' => 'text',
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.line-bot.keywords.ab-tests.store'), $data);

        $response->assertSessionHasErrors('variant_percentage');
    }

    /**
     * ทดสอบการดูรายละเอียด A/B test
     */
    public function test_can_view_ab_test_details(): void
    {
        $test = $this->createABTest();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.ab-tests.show', $test));

        $response->assertStatus(200)
            ->assertViewHas('test')
            ->assertViewHas('summary');
    }

    /**
     * ทดสอบการเริ่มทดสอบ
     */
    public function test_can_start_ab_test(): void
    {
        $test = $this->createABTest();

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.line-bot.keywords.ab-tests.start', $test));

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'test']);

        $this->assertTrue($test->fresh()->status === 'active');
        $this->assertNotNull($test->fresh()->started_at);
    }

    /**
     * ทดสอบการหยุดทดสอบชั่วคราว
     */
    public function test_can_pause_ab_test(): void
    {
        $test = $this->createABTest();
        $this->abTestService->startTest($test);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.line-bot.keywords.ab-tests.pause', $test), [
                'reason' => 'Need to check results',
            ]);

        $response->assertStatus(200);
        $this->assertTrue($test->fresh()->status === 'paused');
    }

    /**
     * ทดสอบการจบการทดสอบ
     */
    public function test_can_complete_ab_test(): void
    {
        $test = $this->createABTest();
        $this->abTestService->startTest($test);

        // Add some results
        $this->addTestResults($test, 'variant_a', 50);
        $this->addTestResults($test, 'variant_b', 40);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.line-bot.keywords.ab-tests.complete', $test));

        $response->assertStatus(200);
        $test->refresh();
        $this->assertTrue($test->status === 'completed');
        $this->assertNotNull($test->winner);
    }

    /**
     * ทดสอบการดึง JSON list tests
     */
    public function test_can_get_tests_json_list(): void
    {
        $this->createABTest('active');
        $this->createABTest('completed');

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.ab-tests.list-json'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'count',
                'data',
            ]);
    }

    /**
     * ทดสอบการดึง statistics
     */
    public function test_can_get_ab_test_statistics(): void
    {
        $this->createABTest('active');
        $this->createABTest('completed');

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.ab-tests.statistics'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'statistics' => [
                    'total_tests',
                    'active_tests',
                    'completed_tests',
                    'total_interactions',
                ],
            ]);
    }

    /**
     * ทดสอบการดึง recommendations
     */
    public function test_can_get_recommendations(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.ab-tests.recommendations'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'recommendations',
            ]);
    }

    /**
     * ทดสอบการเลือก variant (50/50 split)
     */
    public function test_variant_selection_respects_percentages(): void
    {
        $test = $this->createABTest();
        $test->update([
            'variant_a_percentage' => 70,
            'variant_b_percentage' => 30,
        ]);

        $selections = [];
        for ($i = 0; $i < 1000; $i++) {
            $variant = $this->abTestService->selectVariant($test);
            $selections[$variant] = ($selections[$variant] ?? 0) + 1;
        }

        // Rough check (allow 10% variance)
        $aPercentage = ($selections['variant_a'] / 1000) * 100;
        $this->assertGreaterThan(60, $aPercentage);
        $this->assertLessThan(80, $aPercentage);
    }

    /**
     * ทดสอบการบันทึกผลการทดสอบ
     */
    public function test_can_record_test_result(): void
    {
        $test = $this->createABTest();
        $variant = $test->variantA();

        $result = $this->abTestService->recordResult($test, [
            'variant_id' => $variant->id,
            'line_user_id' => 'U123',
            'user_message' => 'สวัสดี',
            'variant_served' => 'variant_a',
            'response_time' => 150,
            'matched' => true,
            'interacted' => true,
            'satisfaction' => 5,
        ]);

        $this->assertNotNull($result->id);
        $this->assertEquals('variant_a', $result->variant_served);
        $this->assertEquals(5, $result->satisfaction);
    }

    /**
     * ทดสอบการคำนวณ metrics ของ variant
     */
    public function test_variant_metrics_calculation(): void
    {
        $test = $this->createABTest();
        $variant = $test->variantA();

        // Add 100 impressions, 30 interactions
        for ($i = 0; $i < 100; $i++) {
            KeywordABTestResult::create([
                'ab_test_id' => $test->id,
                'variant_id' => $variant->id,
                'line_user_id' => 'U' . $i,
                'user_message' => 'test message',
                'variant_served' => 'variant_a',
                'response_time' => 200,
                'matched' => true,
                'interacted' => $i < 30, // First 30 are interactions
            ]);
        }

        $variant->refreshMetrics();

        $this->assertEquals(100, $variant->impressions);
        $this->assertEquals(30, $variant->interactions);
        $this->assertEquals(30.0, $variant->conversion_rate);
    }

    /**
     * ทดสอบการวิเคราะห์ผลและกำหนด winner
     */
    public function test_can_analyze_results_and_determine_winner(): void
    {
        $test = $this->createABTest();

        // Variant A: 50 interactions from 100
        // Variant B: 30 interactions from 100
        $this->addTestResults($test, 'variant_a', 50);
        $this->addTestResults($test, 'variant_b', 30);

        $results = $this->abTestService->analyzeResults($test);

        $this->assertNotNull($results['winner']);
        $this->assertEquals('variant_a', $results['winner']);
        $this->assertGreaterThan(0, $results['winner_confidence']);
    }

    /**
     * ทดสอบการนำไปใช้ variant ผู้ชนะ
     */
    public function test_can_apply_winner_to_keyword(): void
    {
        $test = $this->createABTest();
        $this->abTestService->startTest($test);
        $this->addTestResults($test, 'variant_a', 50);
        $this->addTestResults($test, 'variant_b', 30);
        $test = $this->abTestService->completeTest($test);

        $originalText = $this->keyword->response_text;

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.line-bot.keywords.ab-tests.apply-winner', $test));

        $response->assertStatus(200);

        // Verify keyword was updated with variant A's response
        $updatedKeyword = $this->keyword->fresh();
        $this->assertNotEquals($originalText, $updatedKeyword->response_text);
    }

    /**
     * ทดสอบการลบ A/B test
     */
    public function test_can_delete_ab_test(): void
    {
        $test = $this->createABTest();

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.line-bot.keywords.ab-tests.destroy', $test));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('keyword_ab_tests', ['id' => $test->id]);
    }

    /**
     * ทดสอบ A/B test scope: active tests
     */
    public function test_active_scope_returns_only_active_tests(): void
    {
        $this->createABTest('active');
        $this->createABTest('planning');
        $this->createABTest('completed');

        $activeTests = KeywordABTest::active()->count();

        $this->assertEquals(1, $activeTests);
    }

    /**
     * ทดสอบ A/B test scope: completed tests
     */
    public function test_completed_scope_returns_only_completed_tests(): void
    {
        $this->createABTest('active');
        $this->createABTest('completed');
        $this->createABTest('completed');

        $completedTests = KeywordABTest::completed()->count();

        $this->assertEquals(2, $completedTests);
    }

    /**
     * ทดสอบการตรวจสอบความสำคัญทางสถิติ
     */
    public function test_statistical_significance_check(): void
    {
        $test = $this->createABTest();

        // Case 1: Low confidence
        $test->update(['winner_confidence' => 50]);
        $this->assertFalse($test->hasStatisticalSignificance());

        // Case 2: High confidence
        $test->update(['winner_confidence' => 96]);
        $this->assertTrue($test->hasStatisticalSignificance());
    }

    /**
     * ทดสอบการตรวจสอบตัวอย่างเพียงพอ
     */
    public function test_sufficient_samples_check(): void
    {
        $test = $this->createABTest();
        $test->update(['minimum_samples' => 50]);

        // No results yet
        $this->assertFalse($test->hasSufficientSamples());

        // Add 100 results
        $this->addTestResults($test, 'variant_a', 50);
        $this->addTestResults($test, 'variant_b', 50);

        $this->assertTrue($test->hasSufficientSamples());
    }

    /**
     * ทดสอบการดึง variant A
     */
    public function test_can_get_variant_a(): void
    {
        $test = $this->createABTest();
        $variantA = $test->variantA();

        $this->assertNotNull($variantA);
        $this->assertEquals('variant_a', $variantA->variant_type);
    }

    /**
     * ทดสอบการดึง variant B
     */
    public function test_can_get_variant_b(): void
    {
        $test = $this->createABTest();
        $variantB = $test->variantB();

        $this->assertNotNull($variantB);
        $this->assertEquals('variant_b', $variantB->variant_type);
    }

    /**
     * ทดสอบการดึง test summary
     */
    public function test_can_get_test_summary(): void
    {
        $test = $this->createABTest();
        $summary = $this->abTestService->getTestSummary($test);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('test_name', $summary);
        $this->assertArrayHasKey('status', $summary);
        $this->assertArrayHasKey('variant_a', $summary);
        $this->assertArrayHasKey('variant_b', $summary);
    }

    /**
     * ทดสอบการแสดงผลแยกตามสถานะ (filter)
     */
    public function test_can_filter_tests_by_status(): void
    {
        $this->createABTest('active');
        $this->createABTest('active');
        $this->createABTest('completed');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.ab-tests.index', ['filter' => 'active']));

        $this->assertEquals(2, $response->viewData('tests')->total());
    }

    // ===================== Helper Methods =====================

    /**
     * สร้าง A/B test สำหรับทดสอบ
     */
    private function createABTest(string $status = 'planning'): KeywordABTest
    {
        $test = $this->abTestService->createTest([
            'keyword_id' => $this->keyword->id,
            'test_name' => 'Test variant ' . uniqid(),
            'variant_a_percentage' => 50,
            'variant_b_percentage' => 50,
            'winning_criterion' => 'conversion_rate',
            'minimum_samples' => 50,
            'variant_a' => [
                'response_text' => 'Variant A response',
                'response_type' => 'text',
            ],
            'variant_b' => [
                'response_text' => 'Variant B response',
                'response_type' => 'text',
            ],
        ]);

        if ($status !== 'planning') {
            if ($status === 'active') {
                $test = $this->abTestService->startTest($test);
            } elseif ($status === 'completed') {
                $test = $this->abTestService->startTest($test);
                $this->addTestResults($test, 'variant_a', 50);
                $this->addTestResults($test, 'variant_b', 40);
                $test = $this->abTestService->completeTest($test);
            }
        }

        return $test;
    }

    /**
     * เพิ่ม test results สำหรับ variant
     */
    private function addTestResults(KeywordABTest $test, string $variantType, int $count): void
    {
        $variant = $variantType === 'variant_a'
            ? $test->variantA()
            : $test->variantB();

        for ($i = 0; $i < $count; $i++) {
            KeywordABTestResult::create([
                'ab_test_id' => $test->id,
                'variant_id' => $variant->id,
                'line_user_id' => 'U' . $test->id . '-' . $i,
                'user_message' => 'test message ' . $i,
                'variant_served' => $variantType,
                'response_time' => rand(100, 500),
                'matched' => true,
                'interacted' => true,
                'satisfaction' => rand(3, 5),
            ]);
        }
    }
}
