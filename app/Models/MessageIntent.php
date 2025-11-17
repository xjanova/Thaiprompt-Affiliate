<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MessageIntent Model
 *
 * เก็บข้อมูล intents ที่ตรวจพบจากข้อความผู้ใช้
 * ใช้สำหรับ NLP intent recognition และการจัดส่งไปยังแผนกที่เหมาะสม
 *
 * @property int $id
 * @property int $message_sentiment_id
 * @property int|null $line_bot_keyword_id
 * @property string $primary_intent Intent หลัก
 * @property array|null $secondary_intents Intents เสริม
 * @property float $primary_confidence ความมั่นใจของ intent หลัก
 * @property array|null $intent_scores คะแนนแต่ละ intent
 * @property string $intent_source แหล่งที่มา
 * @property string|null $intent_explanation คำอธิบาย
 * @property array|null $trigger_keywords คำหลักที่ทริกเกอร์
 * @property array|null $suggested_actions แนะนำการดำเนินการ
 * @property string|null $suggested_department แผนกที่แนะนำ
 * @property string $priority_level ระดับความสำคัญ
 */
class MessageIntent extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'message_intents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'message_sentiment_id',
        'line_bot_keyword_id',
        'primary_intent',
        'secondary_intents',
        'primary_confidence',
        'intent_scores',
        'intent_source',
        'intent_explanation',
        'trigger_keywords',
        'suggested_actions',
        'suggested_department',
        'priority_level',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'secondary_intents' => 'json',
        'intent_scores' => 'json',
        'trigger_keywords' => 'json',
        'suggested_actions' => 'json',
        'primary_confidence' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ MessageSentiment
     *
     * @return BelongsTo
     */
    public function sentiment(): BelongsTo
    {
        return $this->belongsTo(MessageSentiment::class, 'message_sentiment_id');
    }

    /**
     * ความสัมพันธ์กับ LineBotKeyword
     *
     * @return BelongsTo
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(LineBotKeyword::class, 'line_bot_keyword_id');
    }

    /**
     * Scope: ดึง intents ตามประเภท
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $intent ชนิด intent
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $intent)
    {
        return $query->where('primary_intent', $intent);
    }

    /**
     * Scope: ดึง intents ตามแผนก
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $department ชื่อแผนก
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDepartment($query, string $department)
    {
        return $query->where('suggested_department', $department);
    }

    /**
     * Scope: ดึง intents ตามระดับความสำคัญ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $level ระดับความสำคัญ (LOW, NORMAL, HIGH, URGENT)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithPriority($query, string $level)
    {
        return $query->where('priority_level', $level);
    }

    /**
     * Scope: ดึง intents เร่งด่วน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority_level', 'URGENT');
    }

    /**
     * Scope: ดึง intents ที่มี confidence สูง
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minConfidence ค่า confidence ต่ำสุด (default: 0.85)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighConfidence($query, float $minConfidence = 0.85)
    {
        return $query->where('primary_confidence', '>=', $minConfidence);
    }

    /**
     * ความมั่นใจในรูปแบบเปอร์เซ็นต์
     *
     * @return int
     */
    public function getConfidencePercentageAttribute(): int
    {
        return (int) round($this->primary_confidence * 100);
    }

    /**
     * ตรวจสอบว่า intent เป็นการร้องเรียน
     *
     * @return bool
     */
    public function isComplaint(): bool
    {
        return $this->primary_intent === 'COMPLAINT';
    }

    /**
     * ตรวจสอบว่า intent เป็นการสอบถาม
     *
     * @return bool
     */
    public function isInquiry(): bool
    {
        return $this->primary_intent === 'INQUIRY';
    }

    /**
     * ตรวจสอบว่า intent เป็นการขอความช่วยเหลือ
     *
     * @return bool
     */
    public function isSupport(): bool
    {
        return $this->primary_intent === 'SUPPORT';
    }

    /**
     * ตรวจสอบว่า intent เป็นการซื้อสินค้า
     *
     * @return bool
     */
    public function isPurchase(): bool
    {
        return $this->primary_intent === 'PURCHASE';
    }

    /**
     * ตรวจสอบว่า intent เร่งด่วน
     *
     * @return bool
     */
    public function isUrgent(): bool
    {
        return $this->priority_level === 'URGENT';
    }

    /**
     * ตรวจสอบว่า intent มี secondary intents
     *
     * @return bool
     */
    public function hasSecondaryIntents(): bool
    {
        return !empty($this->secondary_intents) && is_array($this->secondary_intents);
    }

    /**
     * ดึงจำนวน secondary intents
     *
     * @return int
     */
    public function getSecondaryIntentCountAttribute(): int
    {
        return count($this->secondary_intents ?? []);
    }

    /**
     * ดึง all intents (primary + secondary)
     *
     * @return array
     */
    public function getAllIntents(): array
    {
        $intents = [$this->primary_intent];

        if ($this->hasSecondaryIntents()) {
            $intents = array_merge($intents, $this->secondary_intents);
        }

        return array_unique($intents);
    }

    /**
     * ได้รับ intent score สำหรับ intent เฉพาะ
     *
     * @param string $intent ชื่อ intent
     * @return float|null
     */
    public function getIntentScore(string $intent): ?float
    {
        if (empty($this->intent_scores) || !is_array($this->intent_scores)) {
            return null;
        }

        return $this->intent_scores[$intent] ?? null;
    }

    /**
     * ได้รับไอคอน priority level
     *
     * @return string
     */
    public function getPriorityIconAttribute(): string
    {
        return match($this->priority_level) {
            'LOW' => '🟢',
            'NORMAL' => '🟡',
            'HIGH' => '🟠',
            'URGENT' => '🔴',
            default => '⚪',
        };
    }

    /**
     * ได้รับป้ายกำกับ priority level
     *
     * @return string
     */
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority_level) {
            'LOW' => 'ต่ำ',
            'NORMAL' => 'ปกติ',
            'HIGH' => 'สูง',
            'URGENT' => 'เร่งด่วน',
            default => 'ไม่ระบุ',
        };
    }
}
