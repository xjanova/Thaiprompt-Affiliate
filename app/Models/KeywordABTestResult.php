<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Keyword A/B Test Result Model
 *
 * @property int $id
 * @property int $ab_test_id
 * @property int $variant_id
 * @property string $line_user_id ผู้ใช้ LINE
 * @property string $user_message ข้อความของผู้ใช้
 * @property string $variant_served variant_a หรือ variant_b
 * @property float $response_time เวลาตอบกลับ (ms)
 * @property bool $matched ตรงกับคำหลักหรือไม่
 * @property bool $interacted ผู้ใช้โต้ตอบหรือไม่
 * @property int|null $satisfaction ความพึงพอใจ (1-5 stars)
 * @property string|null $user_feedback ข้อเสนอแนะ
 * @property array|null $context ข้อมูลเพิ่มเติม
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class KeywordABTestResult extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'keyword_ab_test_results';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'ab_test_id',
        'variant_id',
        'line_user_id',
        'user_message',
        'variant_served',
        'response_time',
        'matched',
        'interacted',
        'satisfaction',
        'user_feedback',
        'context',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'response_time' => 'float',
        'matched' => 'boolean',
        'interacted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'context' => 'json',
    ];

    /**
     * ความสัมพันธ์กับ KeywordABTest
     *
     * @return BelongsTo
     */
    public function abTest(): BelongsTo
    {
        return $this->belongsTo(KeywordABTest::class, 'ab_test_id');
    }

    /**
     * ความสัมพันธ์กับ KeywordABTestVariant
     *
     * @return BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(KeywordABTestVariant::class, 'variant_id');
    }

    /**
     * Scope: ได้แค่ variant A results
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVariantA($query)
    {
        return $query->where('variant_served', 'variant_a');
    }

    /**
     * Scope: ได้แค่ variant B results
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVariantB($query)
    {
        return $query->where('variant_served', 'variant_b');
    }

    /**
     * Scope: ได้แค่ที่มี interactions
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithInteraction($query)
    {
        return $query->where('interacted', true);
    }

    /**
     * Scope: ได้แค่ที่มี satisfaction ratings
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithSatisfaction($query)
    {
        return $query->whereNotNull('satisfaction');
    }

    /**
     * ได้ satisfaction คะแนน 0-100
     *
     * @return int
     */
    public function getSatisfactionScore(): int
    {
        if (!$this->satisfaction) {
            return 0;
        }

        // Convert 1-5 stars to 0-100
        return ($this->satisfaction - 1) * 25;
    }

    /**
     * ได้ response time เป็น seconds
     *
     * @return float
     */
    public function getResponseTimeInSeconds(): float
    {
        return round($this->response_time / 1000, 3);
    }

    /**
     * ได้ satisfaction เป็นข้อความ
     *
     * @return string
     */
    public function getSatisfactionLabel(): string
    {
        return match ($this->satisfaction) {
            1 => 'ไม่พอใจ',
            2 => 'ปานกลาง',
            3 => 'พอใจ',
            4 => 'พอใจมาก',
            5 => 'พอใจมากที่สุด',
            default => 'ไม่ได้ระบุ',
        };
    }
}
