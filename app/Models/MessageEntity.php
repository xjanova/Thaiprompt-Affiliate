<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MessageEntity Model
 *
 * เก็บข้อมูล entities ที่ดึงออกมาจากข้อความผู้ใช้
 * ใช้สำหรับ NLP processing และการสกัดข้อมูลเชิงโครงสร้าง
 *
 * @property int $id
 * @property int $message_sentiment_id
 * @property int|null $line_bot_keyword_id
 * @property string $entity_type ประเภท entity
 * @property string $entity_value ค่าของ entity
 * @property string|null $display_name ชื่อที่แสดง
 * @property string|null $entity_text ข้อความต้นฉบับ
 * @property int|null $start_position ตำแหน่งเริ่มต้น
 * @property int|null $end_position ตำแหน่งสิ้นสุด
 * @property float $confidence ความมั่นใจ (0-1)
 * @property string $entity_source แหล่งที่มา
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property bool $is_primary เป็น entity หลักหรือไม่
 */
class MessageEntity extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'message_entities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'message_sentiment_id',
        'line_bot_keyword_id',
        'entity_type',
        'entity_value',
        'display_name',
        'entity_text',
        'start_position',
        'end_position',
        'confidence',
        'entity_source',
        'metadata',
        'is_primary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'json',
        'is_primary' => 'boolean',
        'confidence' => 'float',
        'start_position' => 'integer',
        'end_position' => 'integer',
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
     * Scope: ดึงเฉพาะ primary entities
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope: ดึง entities ตามประเภท
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type ประเภท entity
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('entity_type', $type);
    }

    /**
     * Scope: ดึง entities ที่มี confidence สูง
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minConfidence ค่า confidence ต่ำสุด (default: 0.8)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighConfidence($query, float $minConfidence = 0.8)
    {
        return $query->where('confidence', '>=', $minConfidence);
    }

    /**
     * Scope: ดึง entities ตามแหล่งที่มา
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $source แหล่งที่มา
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('entity_source', $source);
    }

    /**
     * หาชื่อที่แสดง หากไม่มีให้ใช้ entity_value
     *
     * @return string
     */
    public function getDisplayValueAttribute(): string
    {
        return $this->display_name ?? $this->entity_value;
    }

    /**
     * ความเชื่อมั่นในรูปแบบเปอร์เซ็นต์
     *
     * @return int
     */
    public function getConfidencePercentageAttribute(): int
    {
        return (int) round($this->confidence * 100);
    }

    /**
     * ความยาวของ entity text
     *
     * @return int
     */
    public function getEntityLengthAttribute(): int
    {
        if ($this->start_position !== null && $this->end_position !== null) {
            return $this->end_position - $this->start_position;
        }

        return strlen($this->entity_text ?? '');
    }

    /**
     * ตรวจสอบว่า entity นี้เป็นชนิด PRODUCT
     *
     * @return bool
     */
    public function isProduct(): bool
    {
        return $this->entity_type === 'PRODUCT';
    }

    /**
     * ตรวจสอบว่า entity นี้เป็นชนิด ISSUE
     *
     * @return bool
     */
    public function isIssue(): bool
    {
        return $this->entity_type === 'ISSUE';
    }

    /**
     * ตรวจสอบว่า entity นี้เป็นชนิด LOCATION
     *
     * @return bool
     */
    public function isLocation(): bool
    {
        return $this->entity_type === 'LOCATION';
    }

    /**
     * ตรวจสอบว่า entity นี้เป็นชนิด QUANTITY
     *
     * @return bool
     */
    public function isQuantity(): bool
    {
        return $this->entity_type === 'QUANTITY';
    }

    /**
     * ตรวจสอบว่า entity นี้มี confidence สูง
     *
     * @return bool
     */
    public function hasHighConfidence(): bool
    {
        return $this->confidence >= 0.8;
    }
}
