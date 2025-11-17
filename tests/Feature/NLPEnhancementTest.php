<?php

namespace Tests\Feature;

use App\Models\MessageSentiment;
use App\Models\MessageEntity;
use App\Models\MessageIntent;
use App\Models\KeywordCluster;
use App\Models\KeywordClusterItem;
use App\Models\LineBotKeyword;
use App\Services\NLPEnhancementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NLPEnhancementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var NLPEnhancementService
     */
    protected NLPEnhancementService $nlpService;

    /**
     * Setup test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->nlpService = app(NLPEnhancementService::class);
    }

    /**
     * Test entity extraction for Thai text
     */
    public function test_extract_entities_from_thai_message(): void
    {
        $sentiment = MessageSentiment::factory()->create(['language' => 'th']);

        $result = $this->nlpService->analyzeMessage(
            $sentiment,
            'ฉันต้องการซื้อเสื้อสีแดงจากกรุงเทพ',
            null
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('entities', $result);
        $this->assertGreaterThan(0, count($result['entities']));
    }

    /**
     * Test entity extraction for English text
     */
    public function test_extract_entities_from_english_message(): void
    {
        $sentiment = MessageSentiment::factory()->create(['language' => 'en']);

        $result = $this->nlpService->analyzeMessage(
            $sentiment,
            'I want to buy a red shirt from Bangkok',
            null
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('entities', $result);
    }

    /**
     * Test intent recognition for complaint
     */
    public function test_recognize_complaint_intent(): void
    {
        $sentiment = MessageSentiment::factory()->create();

        $result = $this->nlpService->analyzeMessage(
            $sentiment,
            'ไม่พอใจสินค้า หาย เสีย ต้องการคืนเงิน',
            null
        );

        $this->assertArrayHasKey('intent', $result);
        $intent = $result['intent'];
        $this->assertIn($intent['primary_intent'], ['COMPLAINT', 'RETURN', 'SUPPORT']);
    }

    /**
     * Test intent recognition for inquiry
     */
    public function test_recognize_inquiry_intent(): void
    {
        $sentiment = MessageSentiment::factory()->create();

        $result = $this->nlpService->analyzeMessage(
            $sentiment,
            'สินค้านี้มีไหม ราคาเท่าไร',
            null
        );

        $this->assertArrayHasKey('intent', $result);
        $intent = $result['intent'];
        $this->assertEquals('INQUIRY', $intent['primary_intent']);
    }

    /**
     * Test intent recognition for purchase
     */
    public function test_recognize_purchase_intent(): void
    {
        $sentiment = MessageSentiment::factory()->create();

        $result = $this->nlpService->analyzeMessage(
            $sentiment,
            'ฉันต้องการสั่งซื้อ 5 ชิ้น',
            null
        );

        $this->assertArrayHasKey('intent', $result);
        $intent = $result['intent'];
        $this->assertEquals('PURCHASE', $intent['primary_intent']);
    }

    /**
     * Test priority level determination for urgent complaint
     */
    public function test_priority_level_urgent_for_complaint(): void
    {
        $sentiment = MessageSentiment::factory()->create([
            'is_urgent' => true,
            'sentiment_score' => -0.8,
        ]);

        $result = $this->nlpService->analyzeMessage(
            $sentiment,
            'ร้องเรียน เสีย ไม่ทำงาน เร่งด่วน',
            null
        );

        $intent = $result['intent'];
        $this->assertEquals('URGENT', $intent['priority_level']);
    }

    /**
     * Test MessageEntity model relationships
     */
    public function test_message_entity_relationships(): void
    {
        $sentiment = MessageSentiment::factory()->create();
        $entity = MessageEntity::factory()->create(['message_sentiment_id' => $sentiment->id]);

        $this->assertInstanceOf(MessageSentiment::class, $entity->sentiment);
        $this->assertEquals($sentiment->id, $entity->sentiment->id);
    }

    /**
     * Test MessageEntity primary scope
     */
    public function test_message_entity_primary_scope(): void
    {
        MessageEntity::factory()->count(3)->create(['is_primary' => false]);
        MessageEntity::factory()->count(2)->create(['is_primary' => true]);

        $primaryEntities = MessageEntity::primary()->count();
        $this->assertEquals(2, $primaryEntities);
    }

    /**
     * Test MessageEntity type scope
     */
    public function test_message_entity_type_scope(): void
    {
        MessageEntity::factory()->count(5)->create(['entity_type' => 'PRODUCT']);
        MessageEntity::factory()->count(3)->create(['entity_type' => 'ISSUE']);

        $productEntities = MessageEntity::ofType('PRODUCT')->count();
        $this->assertEquals(5, $productEntities);
    }

    /**
     * Test MessageEntity high confidence scope
     */
    public function test_message_entity_high_confidence_scope(): void
    {
        MessageEntity::factory()->count(2)->create(['confidence' => 0.95]);
        MessageEntity::factory()->count(3)->create(['confidence' => 0.60]);

        $highConfidenceEntities = MessageEntity::highConfidence(0.8)->count();
        $this->assertEquals(2, $highConfidenceEntities);
    }

    /**
     * Test MessageIntent model relationships
     */
    public function test_message_intent_relationships(): void
    {
        $sentiment = MessageSentiment::factory()->create();
        $intent = MessageIntent::factory()->create(['message_sentiment_id' => $sentiment->id]);

        $this->assertInstanceOf(MessageSentiment::class, $intent->sentiment);
    }

    /**
     * Test MessageIntent type scope
     */
    public function test_message_intent_type_scope(): void
    {
        MessageIntent::factory()->count(5)->create(['primary_intent' => 'COMPLAINT']);
        MessageIntent::factory()->count(3)->create(['primary_intent' => 'INQUIRY']);

        $complaints = MessageIntent::ofType('COMPLAINT')->count();
        $this->assertEquals(5, $complaints);
    }

    /**
     * Test MessageIntent priority scope
     */
    public function test_message_intent_priority_scope(): void
    {
        MessageIntent::factory()->count(2)->create(['priority_level' => 'URGENT']);
        MessageIntent::factory()->count(3)->create(['priority_level' => 'NORMAL']);

        $urgent = MessageIntent::withPriority('URGENT')->count();
        $this->assertEquals(2, $urgent);
    }

    /**
     * Test MessageIntent urgent scope
     */
    public function test_message_intent_urgent_scope(): void
    {
        MessageIntent::factory()->count(2)->create(['priority_level' => 'URGENT']);
        MessageIntent::factory()->count(3)->create(['priority_level' => 'NORMAL']);

        $urgent = MessageIntent::urgent()->count();
        $this->assertEquals(2, $urgent);
    }

    /**
     * Test MessageIntent department scope
     */
    public function test_message_intent_department_scope(): void
    {
        MessageIntent::factory()->count(5)->create(['suggested_department' => 'SUPPORT']);
        MessageIntent::factory()->count(3)->create(['suggested_department' => 'SALES']);

        $support = MessageIntent::forDepartment('SUPPORT')->count();
        $this->assertEquals(5, $support);
    }

    /**
     * Test KeywordCluster model
     */
    public function test_keyword_cluster_creation(): void
    {
        $cluster = KeywordCluster::factory()->create();

        $this->assertIsNotNull($cluster->id);
        $this->assertIsString($cluster->cluster_name);
        $this->assertIsString($cluster->display_name);
    }

    /**
     * Test KeywordCluster active scope
     */
    public function test_keyword_cluster_active_scope(): void
    {
        KeywordCluster::factory()->count(3)->create(['is_active' => true]);
        KeywordCluster::factory()->count(2)->create(['is_active' => false]);

        $activeClusters = KeywordCluster::active()->count();
        $this->assertEquals(3, $activeClusters);
    }

    /**
     * Test KeywordCluster category scope
     */
    public function test_keyword_cluster_category_scope(): void
    {
        KeywordCluster::factory()->count(5)->create(['cluster_category' => 'ISSUE']);
        KeywordCluster::factory()->count(3)->create(['cluster_category' => 'PRODUCT']);

        $issueClusters = KeywordCluster::byCategory('ISSUE')->count();
        $this->assertEquals(5, $issueClusters);
    }

    /**
     * Test KeywordCluster frequent scope
     */
    public function test_keyword_cluster_frequent_scope(): void
    {
        KeywordCluster::factory()->count(2)->create(['usage_frequency' => 0.8]);
        KeywordCluster::factory()->count(3)->create(['usage_frequency' => 0.2]);

        $frequentClusters = KeywordCluster::frequent(0.5)->count();
        $this->assertEquals(2, $frequentClusters);
    }

    /**
     * Test KeywordCluster system scope
     */
    public function test_keyword_cluster_system_scope(): void
    {
        KeywordCluster::factory()->count(3)->create(['is_system' => true]);
        KeywordCluster::factory()->count(2)->create(['is_system' => false]);

        $systemClusters = KeywordCluster::system()->count();
        $this->assertEquals(3, $systemClusters);
    }

    /**
     * Test KeywordCluster user-defined scope
     */
    public function test_keyword_cluster_user_defined_scope(): void
    {
        KeywordCluster::factory()->count(3)->create(['is_system' => true]);
        KeywordCluster::factory()->count(2)->create(['is_system' => false]);

        $userClusters = KeywordCluster::userDefined()->count();
        $this->assertEquals(2, $userClusters);
    }

    /**
     * Test KeywordCluster add keyword
     */
    public function test_keyword_cluster_add_keyword(): void
    {
        $cluster = KeywordCluster::factory()->create();
        $keyword = LineBotKeyword::factory()->create();

        $cluster->addKeyword($keyword, 'RELATED', 0.9);

        $this->assertTrue($cluster->keywords()->where('id', $keyword->id)->exists());
        $this->assertEquals(1, $cluster->keyword_count);
    }

    /**
     * Test KeywordCluster remove keyword
     */
    public function test_keyword_cluster_remove_keyword(): void
    {
        $cluster = KeywordCluster::factory()->create();
        $keyword = LineBotKeyword::factory()->create();

        $cluster->addKeyword($keyword);
        $cluster->removeKeyword($keyword);

        $this->assertFalse($cluster->keywords()->where('id', $keyword->id)->exists());
        $this->assertEquals(0, $cluster->keyword_count);
    }

    /**
     * Test KeywordCluster record match
     */
    public function test_keyword_cluster_record_match(): void
    {
        $cluster = KeywordCluster::factory()->create(['total_matches' => 10]);

        $cluster->recordMatch(5);

        $this->assertEquals(15, $cluster->fresh()->total_matches);
        $this->assertEquals(0, $cluster->fresh()->days_since_last_match);
    }

    /**
     * Test KeywordClusterItem relationships
     */
    public function test_keyword_cluster_item_relationships(): void
    {
        $cluster = KeywordCluster::factory()->create();
        $keyword = LineBotKeyword::factory()->create();

        $item = KeywordClusterItem::factory()->create([
            'keyword_cluster_id' => $cluster->id,
            'line_bot_keyword_id' => $keyword->id,
        ]);

        $this->assertInstanceOf(KeywordCluster::class, $item->cluster);
        $this->assertInstanceOf(LineBotKeyword::class, $item->keyword);
    }

    /**
     * Test KeywordClusterItem primary scope
     */
    public function test_keyword_cluster_item_primary_scope(): void
    {
        KeywordClusterItem::factory()->count(3)->create(['relationship_type' => 'PRIMARY']);
        KeywordClusterItem::factory()->count(2)->create(['relationship_type' => 'SYNONYM']);

        $primaryItems = KeywordClusterItem::primary()->count();
        $this->assertEquals(3, $primaryItems);
    }

    /**
     * Test KeywordClusterItem high relevance scope
     */
    public function test_keyword_cluster_item_high_relevance_scope(): void
    {
        KeywordClusterItem::factory()->count(2)->create(['relevance_score' => 0.9]);
        KeywordClusterItem::factory()->count(3)->create(['relevance_score' => 0.5]);

        $highRelevance = KeywordClusterItem::highRelevance(0.7)->count();
        $this->assertEquals(2, $highRelevance);
    }

    /**
     * Test NLP dashboard access
     */
    public function test_nlp_dashboard_access(): void
    {
        $this->actingAsAdmin()
            ->get(route('admin.line-bot.keywords.nlp-analysis.index'))
            ->assertStatus(200)
            ->assertViewHas('pageTitle', 'การวิเคราะห์ NLP');
    }

    /**
     * Test entities list view
     */
    public function test_entities_list_view(): void
    {
        MessageEntity::factory()->count(5)->create();

        $this->actingAsAdmin()
            ->get(route('admin.line-bot.keywords.nlp-analysis.entities'))
            ->assertStatus(200)
            ->assertViewHas('entities');
    }

    /**
     * Test intents list view
     */
    public function test_intents_list_view(): void
    {
        MessageIntent::factory()->count(5)->create();

        $this->actingAsAdmin()
            ->get(route('admin.line-bot.keywords.nlp-analysis.intents'))
            ->assertStatus(200)
            ->assertViewHas('intents');
    }

    /**
     * Test clusters list view
     */
    public function test_clusters_list_view(): void
    {
        KeywordCluster::factory()->count(5)->create(['is_active' => true]);

        $this->actingAsAdmin()
            ->get(route('admin.line-bot.keywords.nlp-analysis.clusters'))
            ->assertStatus(200)
            ->assertViewHas('clusters');
    }

    /**
     * Test entity statistics API
     */
    public function test_entity_statistics_api(): void
    {
        MessageEntity::factory()->count(5)->create(['entity_type' => 'PRODUCT']);
        MessageEntity::factory()->count(3)->create(['entity_type' => 'ISSUE']);

        $this->actingAsAdmin()
            ->getJson(route('admin.line-bot.keywords.nlp-analysis.entity-statistics'))
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    /**
     * Test intent statistics API
     */
    public function test_intent_statistics_api(): void
    {
        MessageIntent::factory()->count(5)->create(['primary_intent' => 'COMPLAINT']);
        MessageIntent::factory()->count(3)->create(['primary_intent' => 'INQUIRY']);

        $this->actingAsAdmin()
            ->getJson(route('admin.line-bot.keywords.nlp-analysis.intent-statistics'))
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    /**
     * Test cluster usage API
     */
    public function test_cluster_usage_api(): void
    {
        KeywordCluster::factory()->count(3)->create(['is_active' => true]);

        $this->actingAsAdmin()
            ->getJson(route('admin.line-bot.keywords.nlp-analysis.cluster-usage'))
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    /**
     * Test export report API
     */
    public function test_export_report_api(): void
    {
        MessageEntity::factory()->count(10)->create();
        MessageIntent::factory()->count(10)->create();
        KeywordCluster::factory()->count(3)->create(['is_active' => true]);

        $this->actingAsAdmin()
            ->getJson(route('admin.line-bot.keywords.nlp-analysis.export-report'))
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'generated_at',
                    'period',
                    'entities',
                    'intents',
                    'clusters',
                ]
            ]);
    }

    /**
     * Test create cluster
     */
    public function test_create_cluster(): void
    {
        $keywords = LineBotKeyword::factory()->count(3)->create();

        $data = [
            'cluster_name' => 'shipping_issues',
            'display_name' => 'ปัญหาการจัดส่ง',
            'cluster_category' => 'ISSUE',
            'keywords' => $keywords->pluck('id')->toArray(),
        ];

        $cluster = $this->nlpService->createCluster($data);

        $this->assertIsNotNull($cluster->id);
        $this->assertEquals('shipping_issues', $cluster->cluster_name);
        $this->assertEquals(3, $cluster->keyword_count);
    }

    /**
     * Helper: Act as admin
     */
    protected function actingAsAdmin()
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        return $this->actingAs($admin);
    }
}
