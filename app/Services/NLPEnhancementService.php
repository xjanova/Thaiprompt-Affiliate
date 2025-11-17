<?php

namespace App\Services;

use App\Models\MessageSentiment;
use App\Models\MessageEntity;
use App\Models\MessageIntent;
use App\Models\KeywordCluster;
use App\Models\LineBotKeyword;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * NLP Enhancement Service
 *
 * บริหารจัดการ entity extraction, intent recognition, และ keyword clustering
 * สำหรับ NLP processing ของข้อความผู้ใช้
 *
 * Features:
 * - Entity extraction (products, issues, locations, etc.)
 * - Intent recognition (complaints, inquiries, purchases, etc.)
 * - Keyword clustering and relationship analysis
 * - Context-aware suggestions
 * - Related keyword recommendations
 */
class NLPEnhancementService
{
    /**
     * Entity lexicon for Thai language
     */
    private const ENTITY_LEXICON_TH = [
        'PRODUCT' => [
            'สินค้า', 'ผลิตภัณฑ์', 'สิ่งของ', 'เสื้อ', 'กระเป๋า', 'รองเท้า',
            'หนังสือ', 'โทรศัพท์', 'แล็ปท็อป', 'คอมพิวเตอร์', 'เฟอร์นิเจอร์',
            'ของเล่น', 'อาหาร', 'เครื่องสำอาง', 'ยา', 'วิตามิน',
        ],
        'ISSUE' => [
            'ปัญหา', 'ข้อร้องเรียน', 'เสีย', 'หาย', 'ผิด', 'ไม่ได้', 'เสียหาย',
            'แตกหัก', 'ขาด', 'ชำรุด', 'ไม่ทำงาน', 'ไม่ตรงกัน', 'ผิดสี',
            'ตกชั้น', 'หาย', 'สูญหาย', 'สกปรก', 'เหม่น',
        ],
        'LOCATION' => [
            'กรุงเทพ', 'ชลบุรี', 'ระยอง', 'เชียงใหม่', 'เชียงราย', 'สมุทรปราการ',
            'นนทบุรี', 'สตูล', 'ภูเก็ต', 'กระบี่', 'หาดใหญ่', 'นครสวรรค์',
            'บ้าน', 'บ้านเลขที่', 'ซอย', 'ถนน', 'เขต', 'อำเภอ', 'จังหวัด',
        ],
        'QUANTITY' => [
            'ชิ้น', 'อัน', 'ห่อ', 'แพ็ค', 'กล่อง', 'หลัก', 'มัด', 'คู่',
        ],
        'TIME' => [
            'วันนี้', 'เมื่อวาน', 'พรุ่งนี้', 'สัปดาห์', 'เดือน', 'ปี',
            'ชั่วโมง', 'นาที', 'วินาที', 'อาทิตย์ที่แล้ว', 'เดือนที่แล้ว',
        ],
        'PAYMENT_METHOD' => [
            'โอนเงิน', 'บัตรเครดิต', 'บัตรเดบิต', 'อีวอลเลต', 'จ่ายปลายทาง',
            'เงินสด', 'เช็ค', 'โปรมาณ์โปะ', 'ลิปค์', 'ไลน์เพย์',
        ],
    ];

    /**
     * Entity lexicon for English language
     */
    private const ENTITY_LEXICON_EN = [
        'PRODUCT' => [
            'product', 'item', 'thing', 'shirt', 'bag', 'shoe', 'shoes',
            'book', 'phone', 'laptop', 'computer', 'furniture', 'toy',
            'food', 'cosmetic', 'medicine', 'vitamin', 'device', 'gadget',
        ],
        'ISSUE' => [
            'problem', 'issue', 'complaint', 'broken', 'damaged', 'lost',
            'missing', 'wrong', 'not working', 'defective', 'cracked',
            'dirty', 'expired', 'missing piece', 'color mismatch',
        ],
        'LOCATION' => [
            'bangkok', 'chiang mai', 'phuket', 'pattaya', 'krabi',
            'home', 'address', 'street', 'zone', 'district', 'province',
        ],
        'QUANTITY' => [
            'piece', 'pieces', 'pack', 'box', 'unit', 'set', 'pair',
        ],
        'TIME' => [
            'today', 'yesterday', 'tomorrow', 'week', 'month', 'year',
            'hour', 'minute', 'second', 'last week', 'last month',
        ],
        'PAYMENT_METHOD' => [
            'transfer', 'credit card', 'debit card', 'wallet', 'cash on delivery',
            'cash', 'check', 'paypal', 'bitcoin', 'line pay',
        ],
    ];

    /**
     * Intent patterns for recognition
     */
    private const INTENT_PATTERNS = [
        'COMPLAINT' => [
            'ไม่พอใจ', 'ร้องเรียน', 'โกรธ', 'เสีย', 'ปัญหา', 'ไม่ได้',
            'disappointed', 'angry', 'complaint', 'problem', 'issue', 'broken',
            'damaged', 'wrong', 'refund', 'return', 'exchange',
        ],
        'INQUIRY' => [
            'ถาม', 'สอบถาม', 'อยากรู้', 'มีไหม', 'ได้ไหม', 'ราคา',
            'ask', 'question', 'how', 'where', 'what', 'when', 'why',
            'price', 'cost', 'available', 'in stock',
        ],
        'PURCHASE' => [
            'ต้องการซื้อ', 'ซื้อ', 'สั่งซื้อ', 'อยากได้', 'เอา',
            'buy', 'purchase', 'order', 'want', 'need', 'buy me',
            'how much', 'how many',
        ],
        'SUPPORT' => [
            'ขอความช่วยเหลือ', 'ช่วยด้วย', 'ติดขัด', 'ไม่เข้าใจ',
            'help', 'support', 'assist', 'stuck', 'confused', 'guide',
        ],
        'PAYMENT' => [
            'จ่ายเงิน', 'ชำระเงิน', 'โอนเงิน', 'บัตรเครดิต',
            'payment', 'pay', 'transfer', 'credit card', 'invoice',
        ],
        'DELIVERY' => [
            'จัดส่ง', 'ส่ง', 'ไปหน่อย', 'มาถึง', 'ยังไม่มา',
            'delivery', 'shipping', 'shipped', 'arrived', 'send',
        ],
        'FEEDBACK' => [
            'ข้อเสนอ', 'คิดเห็น', 'ดี', 'ชอบ', 'ขอบคุณ',
            'feedback', 'suggestion', 'idea', 'opinion', 'thanks',
        ],
    ];

    /**
     * Analyze message for entities, intents, and clusters
     *
     * @param MessageSentiment $sentiment ข้อมูล sentiment
     * @param string $message ข้อความต้นฉบับ
     * @param LineBotKeyword|null $keyword คำสำคัญที่ตรงกัน
     * @return array ผลการวิเคราะห์ entities, intents, clusters
     */
    public function analyzeMessage(MessageSentiment $sentiment, string $message, ?LineBotKeyword $keyword = null): array
    {
        return DB::transaction(function () use ($sentiment, $message, $keyword) {
            // ตรวจพบภาษา
            $language = $sentiment->language ?? 'en';

            // 1. สกัด entities
            $entities = $this->extractEntities($sentiment, $message, $language);

            // 2. จดจำ intents
            $intent = $this->recognizeIntent($sentiment, $message, $language, $entities, $keyword);

            // 3. ค้นหา keyword clusters ที่เกี่ยวข้อง
            $clusters = $this->findRelatedClusters($keyword, $entities, $intent);

            // 4. บันทึกข้อมูล
            $this->recordEntities($sentiment, $entities);
            $this->recordIntent($sentiment, $intent, $keyword);
            $this->recordClusterMatches($sentiment, $clusters);

            return [
                'entities' => $entities,
                'intent' => $intent,
                'clusters' => $clusters,
            ];
        });
    }

    /**
     * Extract entities from message
     *
     * @param MessageSentiment $sentiment
     * @param string $message ข้อความ
     * @param string $language ภาษา (th|en)
     * @return array entities ที่พบ
     */
    private function extractEntities(MessageSentiment $sentiment, string $message, string $language): array
    {
        $entities = [];
        $lexicon = $language === 'th' ? self::ENTITY_LEXICON_TH : self::ENTITY_LEXICON_EN;

        // ทำเล็กหมด
        $messageLower = strtolower($message);

        // ค้นหา entities ตามประเภท
        foreach ($lexicon as $entityType => $keywords) {
            foreach ($keywords as $keyword) {
                $pos = strpos($messageLower, strtolower($keyword));
                if ($pos !== false) {
                    // หาตำแหน่งของ entity
                    $startPos = $pos;
                    $endPos = $pos + strlen($keyword);

                    // เพิ่มเข้า entities
                    $entities[] = [
                        'entity_type' => $entityType,
                        'entity_value' => $keyword,
                        'entity_text' => substr($message, $startPos, strlen($keyword)),
                        'start_position' => $startPos,
                        'end_position' => $endPos,
                        'confidence' => 0.95,
                        'entity_source' => 'LEXICON',
                        'is_primary' => true,
                        'metadata' => ['language' => $language],
                    ];
                }
            }
        }

        // จดจำ entities ตามรูปแบบ (patterns)
        $entities = array_merge($entities, $this->extractPatternBasedEntities($message, $language));

        // ลบ duplicates
        $entities = array_unique($entities, SORT_REGULAR);

        return $entities;
    }

    /**
     * Extract pattern-based entities (e.g., emails, phone numbers, prices)
     *
     * @param string $message
     * @param string $language
     * @return array
     */
    private function extractPatternBasedEntities(string $message, string $language): array
    {
        $entities = [];

        // ค้นหา email
        if (preg_match_all('/[\w\.-]+@[\w\.-]+\.\w+/', $message, $matches)) {
            foreach ($matches[0] as $email) {
                $entities[] = [
                    'entity_type' => 'EMAIL',
                    'entity_value' => $email,
                    'entity_text' => $email,
                    'confidence' => 0.99,
                    'entity_source' => 'PATTERN',
                    'is_primary' => false,
                ];
            }
        }

        // ค้นหา เบอร์โทรศัพท์
        if (preg_match_all('/\d{9,11}/', $message, $matches)) {
            foreach ($matches[0] as $phone) {
                if (strlen($phone) >= 9) {
                    $entities[] = [
                        'entity_type' => 'PHONE',
                        'entity_value' => $phone,
                        'entity_text' => $phone,
                        'confidence' => 0.90,
                        'entity_source' => 'PATTERN',
                        'is_primary' => false,
                    ];
                }
            }
        }

        // ค้นหา ราคา
        if (preg_match_all('/\d+[\s,]*(?:บาท|บ|รูปี|บาทต่ำที่สุด|\$|USD|THB)/', $message, $matches)) {
            foreach ($matches[0] as $price) {
                $entities[] = [
                    'entity_type' => 'PRICE',
                    'entity_value' => $price,
                    'entity_text' => $price,
                    'confidence' => 0.85,
                    'entity_source' => 'PATTERN',
                    'is_primary' => false,
                ];
            }
        }

        return $entities;
    }

    /**
     * Recognize intent from message
     *
     * @param MessageSentiment $sentiment
     * @param string $message
     * @param string $language
     * @param array $entities
     * @param LineBotKeyword|null $keyword
     * @return array
     */
    private function recognizeIntent(MessageSentiment $sentiment, string $message, string $language, array $entities, ?LineBotKeyword $keyword = null): array
    {
        $messageLower = strtolower($message);
        $intentScores = [];
        $triggerKeywords = [];

        // คำนวณคะแนน intent แต่ละประเภท
        foreach (self::INTENT_PATTERNS as $intentType => $patterns) {
            $score = 0;
            $triggers = [];

            foreach ($patterns as $pattern) {
                if (stripos($messageLower, $pattern) !== false) {
                    $score += 0.2;
                    $triggers[] = $pattern;
                }
            }

            if ($score > 0) {
                $intentScores[$intentType] = min($score, 1.0);
                $triggerKeywords = array_merge($triggerKeywords, $triggers);
            }
        }

        // หา primary intent (ค่าสูงสุด)
        $primaryIntent = 'OTHER';
        $primaryConfidence = 0;

        if (!empty($intentScores)) {
            arsort($intentScores);
            $primaryIntent = key($intentScores);
            $primaryConfidence = current($intentScores);
        }

        // หา secondary intents
        $secondaryIntents = [];
        $sortedIntents = array_slice(array_keys($intentScores), 1, 2);
        if (!empty($sortedIntents)) {
            $secondaryIntents = $sortedIntents;
        }

        // จดจำ priority level โดยวิเคราะห์ sentiment
        $priorityLevel = $this->determinePriorityLevel($sentiment, $primaryIntent, $primaryConfidence);

        // แนะนำแผนก
        $suggestedDepartment = $this->suggestDepartment($primaryIntent);

        return [
            'primary_intent' => $primaryIntent,
            'secondary_intents' => $secondaryIntents,
            'primary_confidence' => $primaryConfidence,
            'intent_scores' => $intentScores,
            'intent_source' => 'KEYWORDS',
            'trigger_keywords' => array_unique($triggerKeywords),
            'suggested_department' => $suggestedDepartment,
            'priority_level' => $priorityLevel,
            'intent_explanation' => $this->getIntentExplanation($primaryIntent),
            'suggested_actions' => $this->getSuggestedActions($primaryIntent, $entities),
        ];
    }

    /**
     * Determine priority level based on sentiment and intent
     *
     * @param MessageSentiment $sentiment
     * @param string $intent
     * @param float $confidence
     * @return string LOW|NORMAL|HIGH|URGENT
     */
    private function determinePriorityLevel(MessageSentiment $sentiment, string $intent, float $confidence): string
    {
        // urgent intent types
        $urgentIntents = ['COMPLAINT', 'SUPPORT'];
        if (in_array($intent, $urgentIntents)) {
            if ($sentiment->is_urgent || $sentiment->sentiment_score < -0.5) {
                return 'URGENT';
            }
            return 'HIGH';
        }

        // complaint/negative is high priority
        if ($sentiment->is_complaint || $sentiment->sentiment_score < -0.2) {
            return 'HIGH';
        }

        // normal for others
        return 'NORMAL';
    }

    /**
     * Suggest department based on intent
     *
     * @param string $intent
     * @return string|null
     */
    private function suggestDepartment(string $intent): ?string
    {
        return match($intent) {
            'COMPLAINT', 'SUPPORT' => 'SUPPORT',
            'PURCHASE', 'INQUIRY' => 'SALES',
            'PAYMENT' => 'BILLING',
            'DELIVERY' => 'DELIVERY',
            'RETURN' => 'QUALITY',
            default => null,
        };
    }

    /**
     * Get intent explanation
     *
     * @param string $intent
     * @return string
     */
    private function getIntentExplanation(string $intent): string
    {
        return match($intent) {
            'COMPLAINT' => 'ผู้ใช้มีความไม่พอใจหรือร้องเรียน',
            'INQUIRY' => 'ผู้ใช้กำลังสอบถามข้อมูล',
            'PURCHASE' => 'ผู้ใช้ต้องการซื้อสินค้า',
            'SUPPORT' => 'ผู้ใช้ขอความช่วยเหลือ',
            'FEEDBACK' => 'ผู้ใช้มีข้อเสนอแนะ',
            'PAYMENT' => 'ผู้ใช้มีเรื่องเกี่ยวกับการชำระเงิน',
            'DELIVERY' => 'ผู้ใช้มีเรื่องเกี่ยวกับการจัดส่ง',
            'RETURN' => 'ผู้ใช้ต้องการคืนสินค้า',
            default => 'เรื่องอื่นๆ',
        };
    }

    /**
     * Get suggested actions for intent
     *
     * @param string $intent
     * @param array $entities
     * @return array
     */
    private function getSuggestedActions(string $intent, array $entities): array
    {
        $actions = [];

        switch ($intent) {
            case 'COMPLAINT':
                $actions = ['verify_issue', 'offer_solution', 'process_refund', 'arrange_pickup'];
                break;
            case 'PURCHASE':
                $actions = ['check_availability', 'provide_quote', 'process_order', 'arrange_delivery'];
                break;
            case 'SUPPORT':
                $actions = ['provide_assistance', 'troubleshoot', 'escalate_if_needed'];
                break;
            case 'PAYMENT':
                $actions = ['send_invoice', 'provide_payment_options', 'confirm_payment'];
                break;
            case 'DELIVERY':
                $actions = ['track_shipment', 'provide_eta', 'reschedule_if_needed'];
                break;
        }

        return $actions;
    }

    /**
     * Find related keyword clusters
     *
     * @param LineBotKeyword|null $keyword
     * @param array $entities
     * @param array $intent
     * @return Collection
     */
    private function findRelatedClusters(?LineBotKeyword $keyword, array $entities, array $intent): Collection
    {
        $clusters = collect();

        if (!$keyword) {
            return $clusters;
        }

        // หา clusters ที่มีคำสำคัญนี้
        $clusters = $keyword->clusters()
            ->with('keywords')
            ->limit(5)
            ->get();

        return $clusters;
    }

    /**
     * Record entities to database
     *
     * @param MessageSentiment $sentiment
     * @param array $entities
     * @return void
     */
    private function recordEntities(MessageSentiment $sentiment, array $entities): void
    {
        foreach ($entities as $entity) {
            MessageEntity::create([
                'message_sentiment_id' => $sentiment->id,
                ...$entity,
            ]);
        }
    }

    /**
     * Record intent to database
     *
     * @param MessageSentiment $sentiment
     * @param array $intentData
     * @param LineBotKeyword|null $keyword
     * @return void
     */
    private function recordIntent(MessageSentiment $sentiment, array $intentData, ?LineBotKeyword $keyword = null): void
    {
        MessageIntent::create([
            'message_sentiment_id' => $sentiment->id,
            'line_bot_keyword_id' => $keyword?->id,
            'primary_intent' => $intentData['primary_intent'],
            'secondary_intents' => $intentData['secondary_intents'],
            'primary_confidence' => $intentData['primary_confidence'],
            'intent_scores' => $intentData['intent_scores'],
            'intent_source' => $intentData['intent_source'],
            'intent_explanation' => $intentData['intent_explanation'],
            'trigger_keywords' => $intentData['trigger_keywords'],
            'suggested_actions' => $intentData['suggested_actions'],
            'suggested_department' => $intentData['suggested_department'],
            'priority_level' => $intentData['priority_level'],
        ]);
    }

    /**
     * Record cluster matches
     *
     * @param MessageSentiment $sentiment
     * @param Collection $clusters
     * @return void
     */
    private function recordClusterMatches(MessageSentiment $sentiment, Collection $clusters): void
    {
        foreach ($clusters as $cluster) {
            $cluster->recordMatch(1);
        }
    }

    /**
     * Create or merge keyword clusters
     *
     * @param array $data ข้อมูล cluster
     * @return KeywordCluster
     */
    public function createCluster(array $data): KeywordCluster
    {
        return DB::transaction(function () use ($data) {
            $cluster = KeywordCluster::create([
                'cluster_name' => $data['cluster_name'],
                'display_name' => $data['display_name'] ?? $data['cluster_name'],
                'description' => $data['description'] ?? null,
                'icon' => $data['icon'] ?? null,
                'cluster_category' => $data['cluster_category'] ?? 'OTHER',
                'primary_keywords' => $data['primary_keywords'] ?? [],
                'related_intents' => $data['related_intents'] ?? [],
                'related_entities' => $data['related_entities'] ?? [],
                'is_system' => $data['is_system'] ?? false,
            ]);

            // เพิ่ม keywords ไปยัง cluster
            if (!empty($data['keywords'])) {
                foreach ($data['keywords'] as $keywordData) {
                    if (is_array($keywordData)) {
                        $cluster->keywords()->attach($keywordData['id'], [
                            'relationship_type' => $keywordData['relationship_type'] ?? 'RELATED',
                            'relevance_score' => $keywordData['relevance_score'] ?? 1.0,
                        ]);
                    } else {
                        $cluster->keywords()->attach($keywordData);
                    }
                }
            }

            return $cluster->fresh();
        });
    }

    /**
     * Get cluster recommendations
     *
     * @param int $days ดูข้อมูลของ N วันที่ผ่านมา
     * @return array
     */
    public function getClusterRecommendations(int $days = 30): array
    {
        // clusters ที่ใช้บ่อย
        $frequentClusters = KeywordCluster::active()
            ->frequent(0.5)
            ->limit(5)
            ->pluck('display_name', 'cluster_name')
            ->toArray();

        // clusters ที่ไม่ได้ใช้
        $unusedClusters = KeywordCluster::active()
            ->where('total_matches', 0)
            ->limit(5)
            ->pluck('display_name', 'cluster_name')
            ->toArray();

        // keywords ที่อาจจะต้องจัดกลุ่ม (keywords ที่ยังไม่อยู่ใน cluster)
        $ungroupedKeywords = LineBotKeyword::active()
            ->doesntHave('clusters')
            ->limit(5)
            ->pluck('keyword_text', 'id')
            ->toArray();

        return [
            'frequent_clusters' => $frequentClusters,
            'unused_clusters' => $unusedClusters,
            'ungrouped_keywords' => $ungroupedKeywords,
        ];
    }

    /**
     * Analyze cluster usage patterns
     *
     * @return Collection
     */
    public function analyzeClusterUsage(): Collection
    {
        return KeywordCluster::active()
            ->with('keywords')
            ->get()
            ->map(function ($cluster) {
                return [
                    'cluster_name' => $cluster->cluster_name,
                    'display_name' => $cluster->display_name,
                    'keyword_count' => $cluster->keyword_count,
                    'total_matches' => $cluster->total_matches,
                    'usage_frequency' => round($cluster->usage_frequency * 100, 2),
                    'status' => $cluster->activity_status,
                ];
            });
    }
}
