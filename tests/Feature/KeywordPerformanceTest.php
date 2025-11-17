<?php

namespace Tests\Feature;

use App\Models\LineBotKeyword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Keyword Performance Dashboard Tests
 *
 * ทดสอบ performance dashboard สำหรับ keywords
 */
class KeywordPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /**
     * ทดสอบการแสดง performance dashboard
     */
    public function test_can_view_performance_dashboard(): void
    {
        // Arrange
        LineBotKeyword::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.performance.index'));

        // Assert
        $response->assertStatus(200)
            ->assertViewHas('metrics')
            ->assertViewHas('categoryPerformance')
            ->assertViewHas('keywords');
    }

    /**
     * ทดสอบการคำนวณ performance metrics
     */
    public function test_metrics_calculation_is_correct(): void
    {
        // Arrange
        $keywords = LineBotKeyword::factory()->count(3)->create();

        // บันทึก activity logs
        foreach ($keywords as $keyword) {
            for ($i = 0; $i < 5; $i++) {
                DB::table('keyword_activity_logs')->insert([
                    'keyword_id' => $keyword->id,
                    'keyword_name' => $keyword->keyword,
                    'user_message' => "Test message $i",
                    'line_user_id' => "U$i",
                    'action_type' => 'matched',
                    'created_at' => now(),
                    'timestamp' => now(),
                ]);
            }
        }

        // Act
        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.performance.index'));

        // Assert
        $response->assertStatus(200);
        $metrics = $response->viewData('metrics');

        $this->assertEquals(3, $metrics['totalKeywords']);
        $this->assertEquals(3, $metrics['usedKeywords']);
        $this->assertEquals(0, $metrics['unusedKeywords']);
        $this->assertEquals(100, $metrics['coveragePercentage']);
    }

    /**
     * ทดสอบการดึง chart data
     */
    public function test_can_get_chart_data(): void
    {
        // Arrange
        LineBotKeyword::factory()->count(2)->create();

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.performance.chart-data'));

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'topKeywords',
                'effectiveness',
                'coverage',
            ]);
    }

    /**
     * ทดสอบการดึง comparison data
     */
    public function test_can_get_comparison_data(): void
    {
        // Arrange
        DB::table('keyword_activity_logs')->insert([
            [
                'action_type' => 'matched',
                'created_at' => now(),
                'timestamp' => now(),
            ],
            [
                'action_type' => 'no_match',
                'created_at' => now(),
                'timestamp' => now(),
            ],
        ]);

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.performance.comparison-data'));

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'comparison' => [
                    'keyword_matches',
                    'ai_fallback',
                    'total',
                    'keyword_percentage',
                    'ai_percentage',
                ],
            ]);

        $this->assertEquals(50, $response['comparison']['keyword_percentage']);
        $this->assertEquals(50, $response['comparison']['ai_percentage']);
    }

    /**
     * ทดสอบการดึง trend data
     */
    public function test_can_get_trend_data(): void
    {
        // Arrange
        for ($i = 0; $i < 5; $i++) {
            DB::table('keyword_activity_logs')->insert([
                'action_type' => 'matched',
                'created_at' => now()->subDays($i),
                'timestamp' => now()->subDays($i),
            ]);
        }

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.performance.trend-data'));

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data',
            ]);

        // Verify data structure
        $data = $response['data'];
        $this->assertIsArray($data);
    }

    /**
     * ทดสอบ export report
     */
    public function test_can_export_performance_report(): void
    {
        // Arrange
        LineBotKeyword::factory()->count(2)->create();

        // Act
        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.performance.export'));

        // Assert
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv');
    }

    /**
     * ทดสอบ category performance calculation
     */
    public function test_category_performance_calculation(): void
    {
        // Arrange
        $faqKeywords = LineBotKeyword::factory()->count(2)->create(['category' => 'faq']);
        $supportKeywords = LineBotKeyword::factory()->count(3)->create(['category' => 'support']);

        // Add logs for FAQ
        foreach ($faqKeywords as $kw) {
            DB::table('keyword_activity_logs')->insert([
                'keyword_id' => $kw->id,
                'keyword_name' => $kw->keyword,
                'category' => 'faq',
                'action_type' => 'matched',
                'created_at' => now(),
                'timestamp' => now(),
            ]);
        }

        // Add logs for Support
        foreach ($supportKeywords as $kw) {
            for ($i = 0; $i < 2; $i++) {
                DB::table('keyword_activity_logs')->insert([
                    'keyword_id' => $kw->id,
                    'keyword_name' => $kw->keyword,
                    'category' => 'support',
                    'action_type' => 'matched',
                    'created_at' => now(),
                    'timestamp' => now(),
                ]);
            }
        }

        // Act
        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.performance.index'));

        // Assert
        $response->assertStatus(200);
        $categoryPerf = $response->viewData('categoryPerformance');

        $this->assertEquals(2, $categoryPerf['faq']['keywords']);
        $this->assertEquals(6, $categoryPerf['support']['keywords']);
    }

    /**
     * ทดสอบ effectiveness score calculation
     */
    public function test_effectiveness_scores_are_calculated(): void
    {
        // Arrange
        $keyword = LineBotKeyword::factory()->create([
            'times_matched' => 50,
            'priority' => 75,
            'response_type' => 'quick_reply',
            'category' => 'faq',
        ]);

        // Act
        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.performance.index'));

        // Assert
        $response->assertStatus(200);
        $metrics = $response->viewData('metrics');
        $effectiveness = $metrics['effectivenessScores'];

        $this->assertIsArray($effectiveness);
        $this->assertGreater(0, count($effectiveness));
    }

    /**
     * ทดสอบ keyword popularity ranking
     */
    public function test_keyword_popularity_ranking(): void
    {
        // Arrange
        $kw1 = LineBotKeyword::factory()->create(['times_matched' => 100]);
        $kw2 = LineBotKeyword::factory()->create(['times_matched' => 50]);
        $kw3 = LineBotKeyword::factory()->create(['times_matched' => 25]);

        // Act
        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.performance.index'));

        // Assert
        $response->assertStatus(200);
        $metrics = $response->viewData('metrics');
        $popularity = $metrics['popularity'];

        // Most popular should be first
        $this->assertEquals(100, $popularity[0]['matches']);
    }

    /**
     * ทดสอบ coverage percentage calculation
     */
    public function test_coverage_percentage_is_correct(): void
    {
        // Arrange
        $total = LineBotKeyword::factory()->count(10)->create();
        $used = LineBotKeyword::factory()->count(5)->create();

        // Add logs only for used keywords
        foreach ($used as $keyword) {
            DB::table('keyword_activity_logs')->insert([
                'keyword_id' => $keyword->id,
                'keyword_name' => $keyword->keyword,
                'action_type' => 'matched',
                'created_at' => now(),
                'timestamp' => now(),
            ]);
        }

        // Act
        $response = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.performance.index'));

        // Assert
        $metrics = $response->viewData('metrics');
        // Coverage should be around 33% (5 out of 15 keywords used)
        $this->assertGreater(0, $metrics['coveragePercentage']);
    }

    /**
     * ทดสอบ response time data
     */
    public function test_can_get_response_time_data(): void
    {
        // Arrange
        LineBotKeyword::factory()->count(3)->create();

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.performance.response-time-data'));

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'keywords',
                'keyword_times',
                'ai_times',
                'time_savings',
            ]);

        // Verify that AI is slower than keyword
        $this->assertGreater(
            collect($response['ai_times'])->average(),
            collect($response['keyword_times'])->average()
        );
    }

    /**
     * ทดสอบ keyword details
     */
    public function test_can_get_keyword_details(): void
    {
        // Arrange
        $keyword = LineBotKeyword::factory()->create();

        DB::table('keyword_activity_logs')->insert([
            'keyword_id' => $keyword->id,
            'keyword_name' => $keyword->keyword,
            'user_message' => 'test',
            'line_user_id' => 'U123',
            'action_type' => 'matched',
            'created_at' => now(),
            'timestamp' => now(),
        ]);

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.line-bot.keywords.performance.details', $keyword));

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'keyword',
                'statistics',
            ]);
    }

    /**
     * ทดสอบ performance data consistency
     */
    public function test_performance_data_is_consistent(): void
    {
        // Arrange
        $keywords = LineBotKeyword::factory()->count(5)->create();

        // บันทึก activity
        foreach ($keywords as $keyword) {
            for ($i = 0; $i < rand(1, 10); $i++) {
                DB::table('keyword_activity_logs')->insert([
                    'keyword_id' => $keyword->id,
                    'keyword_name' => $keyword->keyword,
                    'action_type' => 'matched',
                    'created_at' => now(),
                    'timestamp' => now(),
                ]);
            }
        }

        // Act
        $response1 = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.performance.index'));

        $response2 = $this->actingAs($this->admin)
            ->get(route('admin.line-bot.keywords.performance.index'));

        // Assert
        $metrics1 = $response1->viewData('metrics');
        $metrics2 = $response2->viewData('metrics');

        $this->assertEquals($metrics1['totalKeywords'], $metrics2['totalKeywords']);
        $this->assertEquals($metrics1['usedKeywords'], $metrics2['usedKeywords']);
        $this->assertEquals($metrics1['coveragePercentage'], $metrics2['coveragePercentage']);
    }
}
