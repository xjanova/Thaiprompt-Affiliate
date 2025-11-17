<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Keyword A/B Test Variant Model
 *
 * @property int $id
 * @property int $ab_test_id
 * @property string $variant_type variant_a หรือ variant_b
 * @property string $response_text ข้อความตอบกลับ
 * @property array|null $trigger_words คำที่ทริกเกอร์
 * @property string $response_type ประเภท (text, quick_reply, flex_message)
 * @property string|null $description คำอธิบาย
 * @property array|null $additional_data ข้อมูลเพิ่มเติม
 * @property int $impressions จำนวนครั้งที่แสดง
 * @property int $interactions จำนวนครั้งที่โต้ตอบ
 * @property float $conversion_rate อัตราการแปลง
 * @property float $avg_response_time เวลาตอบกลับเฉลี่ย
 * @property float $satisfaction_score คะแนนความพึงพอใจ
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class KeywordABTestVariant extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'keyword_ab_test_variants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'ab_test_id',
        'variant_type',
        'response_text',
        'trigger_words',
        'response_type',
        'description',
        'additional_data',
        'impressions',
        'interactions',
        'conversion_rate',
        'avg_response_time',
        'satisfaction_score',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trigger_words' => 'json',
        'additional_data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'conversion_rate' => 'float',
        'avg_response_time' => 'float',
        'satisfaction_score' => 'float',
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
     * ความสัมพันธ์กับ KeywordABTestResult
     *
     * @return HasMany
     */
    public function results(): HasMany
    {
        return $this->hasMany(KeywordABTestResult::class, 'variant_id');
    }

    /**
     * คำนวณ conversion rate
     *
     * @return float
     */
    public function calculateConversionRate(): float
    {
        if ($this->impressions == 0) {
            return 0;
        }

        return ($this->interactions / $this->impressions) * 100;
    }

    /**
     * คำนวณ average response time
     *
     * @return float
     */
    public function calculateAverageResponseTime(): float
    {
        $totalTime = $this->results()
            ->sum('response_time');

        if ($this->interactions == 0) {
            return 0;
        }

        return $totalTime / $this->interactions;
    }

    /**
     * คำนวณ satisfaction score เฉลี่ย
     *
     * @return float
     */
    public function calculateAverageSatisfaction(): float
    {
        $avgSatisfaction = $this->results()
            ->whereNotNull('satisfaction')
            ->avg('satisfaction');

        return $avgSatisfaction ? round($avgSatisfaction * 20, 2) : 0; // Convert 1-5 to 0-100
    }

    /**
     * Refresh metrics จากผลลัพธ์
     *
     * @return void
     */
    public function refreshMetrics(): void
    {
        $this->impressions = $this->results()->count();
        $this->interactions = $this->results()
            ->where('interacted', true)
            ->count();
        $this->conversion_rate = $this->calculateConversionRate();
        $this->avg_response_time = $this->calculateAverageResponseTime();
        $this->satisfaction_score = $this->calculateAverageSatisfaction();
        $this->save();
    }

    /**
     * ได้ชื่อปลายทาง (Variant A / Variant B)
     *
     * @return string
     */
    public function getVariantLabel(): string
    {
        return $this->variant_type === 'variant_a' ? 'Variant A' : 'Variant B';
    }

    /**
     * ได้สถิติโดยละเอียด
     *
     * @return array
     */
    public function getDetailedStats(): array
    {
        return [
            'variant_type' => $this->variant_type,
            'impressions' => $this->impressions,
            'interactions' => $this->interactions,
            'conversion_rate' => $this->conversion_rate,
            'avg_response_time' => $this->avg_response_time,
            'satisfaction_score' => $this->satisfaction_score,
            'engagement_rate' => $this->impressions > 0
                ? round(($this->interactions / $this->impressions) * 100, 2)
                : 0,
            'response_time_seconds' => round($this->avg_response_time / 1000, 2),
        ];
    }
}
