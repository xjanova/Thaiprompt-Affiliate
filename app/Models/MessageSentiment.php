<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Message Sentiment Model
 *
 * @property int $id
 * @property int|null $keyword_id
 * @property string $line_user_id
 * @property string $user_message ข้อความผู้ใช้
 * @property string $message_hash
 * @property string $sentiment ประเภทความรู้สึก (positive, neutral, negative)
 * @property float $sentiment_score คะแนนความรู้สึก (-1 to 1)
 * @property float $confidence ความมั่นใจ (%)
 * @property float $joy_score คะแนนความสุข
 * @property float $anger_score คะแนนความโกรธ
 * @property float $sadness_score คะแนนความเศร้า
 * @property float $fear_score คะแนนความกลัว
 * @property float $surprise_score คะแนนความประหลาดใจ
 * @property array|null $positive_keywords
 * @property array|null $negative_keywords
 * @property array|null $neutral_keywords
 * @property string $language ภาษาที่ตรวจพบ
 * @property string|null $context บริบท
 * @property bool $is_complaint ข้อร้องเรียน?
 * @property bool $is_urgent ด่วน?
 * @property array|null $detected_issues ปัญหาที่ตรวจพบ
 * @property string|null $primary_issue ปัญหาหลัก
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MessageSentiment extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'message_sentiments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'keyword_id',
        'line_user_id',
        'user_message',
        'message_hash',
        'sentiment',
        'sentiment_score',
        'confidence',
        'joy_score',
        'anger_score',
        'sadness_score',
        'fear_score',
        'surprise_score',
        'positive_keywords',
        'negative_keywords',
        'neutral_keywords',
        'language',
        'context',
        'is_complaint',
        'is_urgent',
        'detected_issues',
        'primary_issue',
        'nlp_data',
        'extra_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'positive_keywords' => 'json',
        'negative_keywords' => 'json',
        'neutral_keywords' => 'json',
        'detected_issues' => 'json',
        'nlp_data' => 'json',
        'extra_data' => 'json',
        'is_complaint' => 'boolean',
        'is_urgent' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'sentiment_score' => 'float',
        'confidence' => 'float',
        'joy_score' => 'float',
        'anger_score' => 'float',
        'sadness_score' => 'float',
        'fear_score' => 'float',
        'surprise_score' => 'float',
    ];

    /**
     * ความสัมพันธ์กับ LineBotKeyword
     *
     * @return BelongsTo
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(LineBotKeyword::class);
    }

    /**
     * Scope: ได้เฉพาะ positive sentiments
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePositive($query)
    {
        return $query->where('sentiment', 'positive');
    }

    /**
     * Scope: ได้เฉพาะ negative sentiments
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNegative($query)
    {
        return $query->where('sentiment', 'negative');
    }

    /**
     * Scope: ได้เฉพาะ neutral sentiments
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeutral($query)
    {
        return $query->where('sentiment', 'neutral');
    }

    /**
     * Scope: ได้เฉพาะข้อร้องเรียน
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeComplaints($query)
    {
        return $query->where('is_complaint', true);
    }

    /**
     * Scope: ได้เฉพาะเรื่องด่วน
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true);
    }

    /**
     * Scope: ได้เฉพาะ high confidence
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighConfidence($query)
    {
        return $query->where('confidence', '>=', 80);
    }

    /**
     * ได้ emotion หลัก
     *
     * @return string
     */
    public function getPrimaryEmotion(): string
    {
        $emotions = [
            'joy' => $this->joy_score,
            'anger' => $this->anger_score,
            'sadness' => $this->sadness_score,
            'fear' => $this->fear_score,
            'surprise' => $this->surprise_score,
        ];

        $primary = array_key_first($emotions);
        foreach ($emotions as $emotion => $score) {
            if ($score > $emotions[$primary]) {
                $primary = $emotion;
            }
        }

        return $primary;
    }

    /**
     * ได้ sentiment label
     *
     * @return string
     */
    public function getSentimentLabel(): string
    {
        return match ($this->sentiment) {
            'positive' => '😊 Positive',
            'negative' => '😠 Negative',
            'neutral' => '😐 Neutral',
            default => 'Unknown',
        };
    }

    /**
     * ได้ urgency icon
     *
     * @return string
     */
    public function getUrgencyIcon(): string
    {
        if ($this->is_urgent) {
            return '🔴 Urgent';
        }

        if ($this->is_complaint) {
            return '🟠 Complaint';
        }

        return '🟢 Normal';
    }

    /**
     * Normalize sentiment score to -1 to 1 range
     *
     * @param float $score
     * @return float
     */
    public static function normalizeSentimentScore(float $score): float
    {
        return max(-1, min(1, $score));
    }

    /**
     * Convert sentiment score to percentage (0-100)
     *
     * @return float
     */
    public function getSentimentPercentage(): float
    {
        // -1 = 0%, 0 = 50%, 1 = 100%
        return round((($this->sentiment_score + 1) / 2) * 100, 2);
    }

    /**
     * ตรวจสอบว่า message นี้ซ้ำกับที่มีอยู่แล้วหรือไม่
     *
     * @param string $message
     * @return bool
     */
    public static function isDuplicate(string $message): bool
    {
        $hash = self::hashMessage($message);
        return self::where('message_hash', $hash)->exists();
    }

    /**
     * สร้าง hash สำหรับ message
     *
     * @param string $message
     * @return string
     */
    public static function hashMessage(string $message): string
    {
        return hash('sha256', trim(mb_strtolower($message)));
    }
}
