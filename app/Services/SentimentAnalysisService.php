<?php

namespace App\Services;

use App\Models\MessageSentiment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sentiment Analysis Service
 *
 * วิเคราะห์ความรู้สึกและอารมณ์จากข้อความผู้ใช้
 */
class SentimentAnalysisService
{
    /**
     * Sentiment lexicon - Thai positive words
     */
    private const POSITIVE_WORDS_TH = [
        'ขอบคุณ', 'ยินดี', 'ดี', 'สุดยอด', 'ยอดเยี่ยม', 'ไม่เสียใจ', 'สบายใจ',
        'พอใจ', 'ชอบ', 'ราคา', 'ถูก', 'ประเทศ', 'รวดเร็ว', 'เพียบ', 'เฟฟ',
        'ภูมิใจ', 'เก่ง', 'เจ๋ง', 'ฟิต', 'ใจดี', 'เมตตา', 'หนึ่ง', 'เพิ่ม', 'ทำดี',
    ];

    /**
     * Sentiment lexicon - Thai negative words
     */
    private const NEGATIVE_WORDS_TH = [
        'ไม่', 'ไม่พอใจ', 'ผิด', 'ผิดพลาด', 'เสียใจ', 'สั่งใจ', 'เสื่อม', 'เข็ญ',
        'ลำบาก', 'ปัญหา', 'เป็น', 'ห่วย', 'ห่อย', 'หัดใจ', 'กำลังใจ', 'เสียบ',
        'โกรธ', 'เกียจ', 'ชิด', 'ผิดหวัง', 'แย่', 'ร้ายแรง', 'ร้องไห้', 'เคราะห์',
    ];

    /**
     * Sentiment lexicon - English positive words
     */
    private const POSITIVE_WORDS_EN = [
        'thanks', 'thank', 'good', 'great', 'excellent', 'awesome', 'amazing',
        'love', 'like', 'happy', 'pleased', 'satisfied', 'perfect', 'wonderful',
        'fantastic', 'nice', 'kind', 'helpful', 'support', 'best', 'brilliant',
    ];

    /**
     * Sentiment lexicon - English negative words
     */
    private const NEGATIVE_WORDS_EN = [
        'no', 'not', 'bad', 'terrible', 'awful', 'hate', 'problem', 'issue',
        'angry', 'upset', 'sad', 'disappointed', 'frustrated', 'annoyed', 'wrong',
        'broken', 'fail', 'error', 'complaint', 'complain', 'unhappy', 'terrible',
    ];

    /**
     * Pain points keywords
     */
    private const PAIN_POINTS = [
        'refund' => 'refund, คืนเงิน, ขอเงินคืน, return money',
        'shipping' => 'shipping, delivery, ส่ง, delivery, ส่งหายไป',
        'payment' => 'payment, charge, ชำระเงิน, payment error',
        'quality' => 'quality, defect, หลวม, ไม่ได้มาตรฐาน',
        'delay' => 'delay, late, ช้า, ล่าช้า',
        'support' => 'help, support, support team, ติดต่อ',
        'account' => 'account, password, login, login problem',
        'stock' => 'out of stock, sold out, หมด, ขาดสินค้า',
    ];

    /**
     * วิเคราะห์ sentiment ของข้อความ
     *
     * @param string $message ข้อความที่ต้องการวิเคราะห์
     * @param string|null $lineUserId LINE user ID (optional)
     * @param int|null $keywordId Keyword ID (optional)
     * @return MessageSentiment
     */
    public function analyzeSentiment(string $message, ?string $lineUserId = null, ?int $keywordId = null): MessageSentiment
    {
        // Check duplicate
        if (MessageSentiment::isDuplicate($message)) {
            return MessageSentiment::where('message_hash', MessageSentiment::hashMessage($message))->first();
        }

        // Detect language
        $language = $this->detectLanguage($message);

        // Extract words
        $words = $this->extractWords($message, $language);

        // Calculate sentiment scores
        $sentimentData = $this->calculateSentimentScores($words, $language);

        // Detect emotions
        $emotions = $this->detectEmotions($words, $message);

        // Detect keywords
        $keywords = $this->detectKeywords($words, $language);

        // Detect pain points
        $painPoints = $this->detectPainPoints($message, $words);

        // Determine if complaint or urgent
        $isComplaint = $this->isComplaint($sentimentData, $painPoints, $message);
        $isUrgent = $this->isUrgent($sentimentData, $painPoints, $message);

        // Create sentiment record
        $sentiment = MessageSentiment::create([
            'keyword_id' => $keywordId,
            'line_user_id' => $lineUserId ?? 'unknown',
            'user_message' => $message,
            'message_hash' => MessageSentiment::hashMessage($message),
            'sentiment' => $sentimentData['sentiment'],
            'sentiment_score' => $sentimentData['score'],
            'confidence' => $sentimentData['confidence'],
            'joy_score' => $emotions['joy'],
            'anger_score' => $emotions['anger'],
            'sadness_score' => $emotions['sadness'],
            'fear_score' => $emotions['fear'],
            'surprise_score' => $emotions['surprise'],
            'positive_keywords' => $keywords['positive'],
            'negative_keywords' => $keywords['negative'],
            'neutral_keywords' => $keywords['neutral'],
            'language' => $language,
            'is_complaint' => $isComplaint,
            'is_urgent' => $isUrgent,
            'detected_issues' => $painPoints['issues'],
            'primary_issue' => $painPoints['primary'] ?? null,
        ]);

        // Log activity
        activity()
            ->performedOn($sentiment)
            ->log('วิเคราะห์ sentiment สำเร็จ: ' . $sentimentData['sentiment']);

        return $sentiment;
    }

    /**
     * ตรวจสอบภาษา
     *
     * @param string $message
     * @return string
     */
    private function detectLanguage(string $message): string
    {
        // Simple heuristic: if contains Thai characters, it's Thai
        if (preg_match('/[\x{0E00}-\x{0E7F}]/u', $message)) {
            return 'th';
        }

        return 'en';
    }

    /**
     * แยกคำจากข้อความ
     *
     * @param string $message
     * @param string $language
     * @return array
     */
    private function extractWords(string $message, string $language): array
    {
        // Convert to lowercase
        $message = mb_strtolower($message);

        // Remove special characters but keep Thai characters
        if ($language === 'th') {
            $words = preg_split('/[\s\p{P}]+/u', $message, -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $words = preg_split('/[\s\p{P}]+/u', $message, -1, PREG_SPLIT_NO_EMPTY);
        }

        // Remove empty strings and short words
        return array_filter($words, fn($w) => strlen($w) > 1);
    }

    /**
     * คำนวณ sentiment scores
     *
     * @param array $words
     * @param string $language
     * @return array
     */
    private function calculateSentimentScores(array $words, string $language): array
    {
        $positiveCount = 0;
        $negativeCount = 0;
        $totalCount = count($words);

        $lexicon = $language === 'th'
            ? ['positive' => self::POSITIVE_WORDS_TH, 'negative' => self::NEGATIVE_WORDS_TH]
            : ['positive' => self::POSITIVE_WORDS_EN, 'negative' => self::NEGATIVE_WORDS_EN];

        foreach ($words as $word) {
            foreach ($lexicon['positive'] as $positive) {
                if (strpos($word, $positive) !== false || strpos($positive, $word) !== false) {
                    $positiveCount++;
                }
            }

            foreach ($lexicon['negative'] as $negative) {
                if (strpos($word, $negative) !== false || strpos($negative, $word) !== false) {
                    $negativeCount++;
                }
            }
        }

        // Calculate net sentiment score (-1 to 1)
        $score = $totalCount > 0
            ? ($positiveCount - $negativeCount) / $totalCount
            : 0;

        $score = MessageSentiment::normalizeSentimentScore($score);

        // Determine sentiment
        if ($score > 0.1) {
            $sentiment = 'positive';
        } elseif ($score < -0.1) {
            $sentiment = 'negative';
        } else {
            $sentiment = 'neutral';
        }

        // Calculate confidence
        $confidence = (abs($score) * 100);

        return [
            'sentiment' => $sentiment,
            'score' => $score,
            'confidence' => $confidence,
            'positive_count' => $positiveCount,
            'negative_count' => $negativeCount,
        ];
    }

    /**
     * ตรวจสอบอารมณ์
     *
     * @param array $words
     * @param string $message
     * @return array
     */
    private function detectEmotions(array $words, string $message): array
    {
        $emotions = [
            'joy' => 0,
            'anger' => 0,
            'sadness' => 0,
            'fear' => 0,
            'surprise' => 0,
        ];

        // Joy indicators
        $joyWords = ['ยินดี', 'สุข', 'happy', 'smile', 'laugh', 'fun', 'thanks', 'ขอบคุณ'];
        foreach ($joyWords as $word) {
            foreach ($words as $w) {
                if (strpos($w, $word) !== false) {
                    $emotions['joy'] += 20;
                }
            }
        }

        // Anger indicators
        $angerWords = ['โกรธ', 'ไม่พอใจ', 'angry', 'hate', 'furious', 'damn', 'ห่วย'];
        foreach ($angerWords as $word) {
            foreach ($words as $w) {
                if (strpos($w, $word) !== false) {
                    $emotions['anger'] += 25;
                }
            }
        }

        // Sadness indicators
        $sadnessWords = ['เศร้า', 'ผิดหวัง', 'sad', 'upset', 'cry', 'depressed', 'เสียใจ'];
        foreach ($sadnessWords as $word) {
            foreach ($words as $w) {
                if (strpos($w, $word) !== false) {
                    $emotions['sadness'] += 20;
                }
            }
        }

        // Fear indicators
        $fearWords = ['กลัว', 'ห่วง', 'scared', 'afraid', 'worry', 'concerned'];
        foreach ($fearWords as $word) {
            foreach ($words as $w) {
                if (strpos($w, $word) !== false) {
                    $emotions['fear'] += 15;
                }
            }
        }

        // Surprise indicators
        $surpriseWords = ['ประหลาดใจ', 'เซอร์ไพรส์', 'surprise', 'shocked', 'wow', 'amazing'];
        foreach ($surpriseWords as $word) {
            foreach ($words as $w) {
                if (strpos($w, $word) !== false) {
                    $emotions['surprise'] += 15;
                }
            }
        }

        // Normalize to 0-100
        foreach ($emotions as &$score) {
            $score = min(100, $score);
        }

        return $emotions;
    }

    /**
     * ตรวจสอบคีย์เวิร์ด
     *
     * @param array $words
     * @param string $language
     * @return array
     */
    private function detectKeywords(array $words, string $language): array
    {
        $lexicon = $language === 'th'
            ? ['positive' => self::POSITIVE_WORDS_TH, 'negative' => self::NEGATIVE_WORDS_TH]
            : ['positive' => self::POSITIVE_WORDS_EN, 'negative' => self::NEGATIVE_WORDS_EN];

        $keywords = [
            'positive' => [],
            'negative' => [],
            'neutral' => [],
        ];

        foreach ($words as $word) {
            $found = false;

            foreach ($lexicon['positive'] as $positive) {
                if ($word === $positive || strpos($positive, $word) !== false) {
                    $keywords['positive'][] = $word;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                foreach ($lexicon['negative'] as $negative) {
                    if ($word === $negative || strpos($negative, $word) !== false) {
                        $keywords['negative'][] = $word;
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found && strlen($word) > 2) {
                $keywords['neutral'][] = $word;
            }
        }

        return [
            'positive' => array_unique($keywords['positive']),
            'negative' => array_unique($keywords['negative']),
            'neutral' => array_unique($keywords['neutral']),
        ];
    }

    /**
     * ตรวจสอบปัญหาและ pain points
     *
     * @param string $message
     * @param array $words
     * @return array
     */
    private function detectPainPoints(string $message, array $words): array
    {
        $detected = [];
        $primary = null;

        $messageLower = mb_strtolower($message);

        foreach (self::PAIN_POINTS as $issue => $keywords) {
            $keywordList = array_map('trim', explode(',', $keywords));

            foreach ($keywordList as $keyword) {
                if (strpos($messageLower, mb_strtolower($keyword)) !== false) {
                    if (!in_array($issue, $detected)) {
                        $detected[] = $issue;
                        if (!$primary) {
                            $primary = $issue;
                        }
                    }
                }
            }
        }

        return [
            'issues' => $detected,
            'primary' => $primary,
        ];
    }

    /**
     * ตรวจสอบว่าเป็น complaint หรือไม่
     *
     * @param array $sentimentData
     * @param array $painPoints
     * @param string $message
     * @return bool
     */
    private function isComplaint(array $sentimentData, array $painPoints, string $message): bool
    {
        // Negative sentiment + pain points = complaint
        if ($sentimentData['sentiment'] === 'negative' && count($painPoints['issues']) > 0) {
            return true;
        }

        // Message contains complaint words
        $complaintWords = ['complaint', 'complain', 'problem', 'issue', 'ร้องเรียน', 'ปัญหา', 'แจ้งเรื่อง'];
        foreach ($complaintWords as $word) {
            if (strpos(mb_strtolower($message), mb_strtolower($word)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่า urgent หรือไม่
     *
     * @param array $sentimentData
     * @param array $painPoints
     * @param string $message
     * @return bool
     */
    private function isUrgent(array $sentimentData, array $painPoints, string $message): bool
    {
        // Very negative + multiple pain points = urgent
        if ($sentimentData['sentiment'] === 'negative' && count($painPoints['issues']) > 1) {
            return true;
        }

        // Message contains urgent words
        $urgentWords = ['urgent', 'ด่วน', 'ตอนนี้', 'now', 'asap', 'emergency', 'immediately'];
        foreach ($urgentWords as $word) {
            if (strpos(mb_strtolower($message), mb_strtolower($word)) !== false) {
                return true;
            }
        }

        // Exclamation marks indicate urgency
        if (substr_count($message, '!') > 2 || substr_count($message, '!!!') > 0) {
            return true;
        }

        return false;
    }

    /**
     * ได้ sentiments ตามช่วงเวลา
     *
     * @param int $days
     * @return Collection
     */
    public function getSentimentsTrend(int $days = 30): Collection
    {
        return MessageSentiment::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, sentiment, COUNT(*) as count')
            ->groupBy('date', 'sentiment')
            ->orderBy('date')
            ->get();
    }

    /**
     * ได้สถิติ sentiments
     *
     * @param int $days
     * @return array
     */
    public function getSentimentStatistics(int $days = 30): array
    {
        $query = MessageSentiment::where('created_at', '>=', now()->subDays($days));

        $totalMessages = $query->count();
        $positiveCount = $query->clone()->positive()->count();
        $negativeCount = $query->clone()->negative()->count();
        $neutralCount = $query->clone()->neutral()->count();

        $complaintCount = $query->clone()->complaints()->count();
        $urgentCount = $query->clone()->urgent()->count();

        $avgSentimentScore = $query->clone()->avg('sentiment_score');
        $avgConfidence = $query->clone()->avg('confidence');

        return [
            'total_messages' => $totalMessages,
            'positive_count' => $positiveCount,
            'negative_count' => $negativeCount,
            'neutral_count' => $neutralCount,
            'positive_percentage' => $totalMessages > 0 ? round(($positiveCount / $totalMessages) * 100, 2) : 0,
            'negative_percentage' => $totalMessages > 0 ? round(($negativeCount / $totalMessages) * 100, 2) : 0,
            'neutral_percentage' => $totalMessages > 0 ? round(($neutralCount / $totalMessages) * 100, 2) : 0,
            'complaint_count' => $complaintCount,
            'urgent_count' => $urgentCount,
            'avg_sentiment_score' => round($avgSentimentScore ?? 0, 3),
            'avg_confidence' => round($avgConfidence ?? 0, 2),
        ];
    }

    /**
     * ได้ pain points distribution
     *
     * @param int $days
     * @return Collection
     */
    public function getPainPointsDistribution(int $days = 30): Collection
    {
        $sentiments = MessageSentiment::where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('detected_issues')
            ->get();

        $distribution = [];
        foreach ($sentiments as $sentiment) {
            foreach ($sentiment->detected_issues as $issue) {
                $distribution[$issue] = ($distribution[$issue] ?? 0) + 1;
            }
        }

        arsort($distribution);

        return collect($distribution)->map(function ($count, $issue) {
            return [
                'issue' => $issue,
                'count' => $count,
            ];
        });
    }

    /**
     * ได้ top complaints
     *
     * @param int $limit
     * @param int $days
     * @return Collection
     */
    public function getTopComplaints(int $limit = 10, int $days = 30): Collection
    {
        return MessageSentiment::where('created_at', '>=', now()->subDays($days))
            ->complaints()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * ได้ urgent issues
     *
     * @param int $limit
     * @return Collection
     */
    public function getUrgentIssues(int $limit = 10): Collection
    {
        return MessageSentiment::urgent()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * ได้ recommendations จาก sentiment analysis
     *
     * @param int $days
     * @return array
     */
    public function getRecommendations(int $days = 30): array
    {
        $stats = $this->getSentimentStatistics($days);
        $painPoints = $this->getPainPointsDistribution($days);
        $recommendations = [];

        // High negative percentage
        if ($stats['negative_percentage'] > 30) {
            $recommendations[] = [
                'type' => 'warning',
                'priority' => 'high',
                'message' => $stats['negative_percentage'] . '% ของข้อความเป็น negative',
                'action' => 'ตรวจสอบ pain points และปรับปรุงการบริการ',
            ];
        }

        // Many complaints
        if ($stats['complaint_count'] > ($stats['total_messages'] * 0.2)) {
            $recommendations[] = [
                'type' => 'warning',
                'priority' => 'high',
                'message' => $stats['complaint_count'] . ' ข้อร้องเรียนในช่วง ' . $days . ' วัน',
                'action' => 'ตอบสนองอย่างเร็วต่อข้อร้องเรียน',
            ];
        }

        // Urgent issues
        if ($stats['urgent_count'] > 0) {
            $recommendations[] = [
                'type' => 'danger',
                'priority' => 'urgent',
                'message' => $stats['urgent_count'] . ' เรื่องด่วนที่ต้องการความสนใจทันที',
                'action' => 'ดำเนินการทันทีสำหรับเรื่องด่วน',
            ];
        }

        // Top pain point
        if ($painPoints->count() > 0) {
            $topPain = $painPoints->first();
            $recommendations[] = [
                'type' => 'info',
                'priority' => 'medium',
                'message' => 'ปัญหาหลัก: ' . $topPain['issue'] . ' (' . $topPain['count'] . ' ครั้ง)',
                'action' => 'พิจารณาปรับปรุงประเภท: ' . $topPain['issue'],
            ];
        }

        // High satisfaction
        if ($stats['positive_percentage'] > 70) {
            $recommendations[] = [
                'type' => 'success',
                'priority' => 'low',
                'message' => $stats['positive_percentage'] . '% ของข้อความเป็น positive',
                'action' => 'ยังคงรักษาคุณภาพการบริการ',
            ];
        }

        return $recommendations;
    }
}
