<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Keyword A/B Test Model
 *
 * @property int $id
 * @property int $keyword_id
 * @property string $test_name ชื่อการทดสอบ
 * @property string|null $description คำอธิบาย
 * @property string $status สถานะ (planning, active, paused, completed, cancelled)
 * @property int $variant_a_percentage เปอร์เซ็นต์แบบ A
 * @property int $variant_b_percentage เปอร์เซ็นต์แบบ B
 * @property string $winning_criterion เกณฑ์ชนะ
 * @property \Carbon\Carbon|null $started_at เวลาเริ่มทดสอบ
 * @property \Carbon\Carbon|null $ended_at เวลาสิ้นสุดทดสอบ
 * @property int $minimum_samples จำนวนตัวอย่างต่ำสุด
 * @property string|null $winner ผู้ชนะ (variant_a or variant_b)
 * @property float|null $winner_confidence ความมั่นใจทางสถิติ
 * @property array|null $results ผลลัพธ์
 * @property array|null $config การตั้งค่า
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class KeywordABTest extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'keyword_ab_tests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'keyword_id',
        'test_name',
        'description',
        'status',
        'variant_a_percentage',
        'variant_b_percentage',
        'winning_criterion',
        'started_at',
        'ended_at',
        'minimum_samples',
        'winner',
        'winner_confidence',
        'results',
        'config',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'results' => 'json',
        'config' => 'json',
        'winner_confidence' => 'float',
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
     * ความสัมพันธ์กับ KeywordABTestVariant (variants)
     *
     * @return HasMany
     */
    public function variants(): HasMany
    {
        return $this->hasMany(KeywordABTestVariant::class, 'ab_test_id');
    }

    /**
     * ความสัมพันธ์กับ KeywordABTestResult (results)
     *
     * @return HasMany
     */
    public function results_records(): HasMany
    {
        return $this->hasMany(KeywordABTestResult::class, 'ab_test_id');
    }

    /**
     * Scope: ได้แค่ active tests
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->whereNotNull('started_at')
            ->where('started_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ended_at')
                    ->orWhere('ended_at', '>', now());
            });
    }

    /**
     * Scope: ได้แค่ completed tests
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: ได้แค่ tests ในช่วงเวลา
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days จำนวนวัน
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentlyStarted($query, $days = 30)
    {
        return $query->whereNotNull('started_at')
            ->where('started_at', '>=', now()->subDays($days));
    }

    /**
     * ตรวจสอบว่าทดสอบสถิติถูกต้องหรือไม่
     *
     * @return bool
     */
    public function hasStatisticalSignificance(): bool
    {
        if (!$this->winner_confidence) {
            return false;
        }

        // ความมั่นใจต้อง >= 95% (เกณฑ์มาตรฐาน)
        return $this->winner_confidence >= 95;
    }

    /**
     * ตรวจสอบว่าตัวอย่างเพียงพอหรือไม่
     *
     * @return bool
     */
    public function hasSufficientSamples(): bool
    {
        $total_interactions = $this->results_records()
            ->count();

        return $total_interactions >= $this->minimum_samples;
    }

    /**
     * ได้ variant A
     *
     * @return KeywordABTestVariant|null
     */
    public function variantA()
    {
        return $this->variants()->where('variant_type', 'variant_a')->first();
    }

    /**
     * ได้ variant B
     *
     * @return KeywordABTestVariant|null
     */
    public function variantB()
    {
        return $this->variants()->where('variant_type', 'variant_b')->first();
    }

    /**
     * ได้สรุปผลการทดสอบ
     *
     * @return array
     */
    public function getSummary(): array
    {
        $variantA = $this->variantA();
        $variantB = $this->variantB();

        return [
            'test_name' => $this->test_name,
            'status' => $this->status,
            'duration' => $this->started_at && $this->ended_at
                ? $this->ended_at->diffInDays($this->started_at)
                : null,
            'total_interactions' => $this->results_records()->count(),
            'variant_a' => [
                'impressions' => $variantA?->impressions ?? 0,
                'interactions' => $variantA?->interactions ?? 0,
                'conversion_rate' => $variantA?->conversion_rate ?? 0,
                'avg_response_time' => $variantA?->avg_response_time ?? 0,
            ],
            'variant_b' => [
                'impressions' => $variantB?->impressions ?? 0,
                'interactions' => $variantB?->interactions ?? 0,
                'conversion_rate' => $variantB?->conversion_rate ?? 0,
                'avg_response_time' => $variantB?->avg_response_time ?? 0,
            ],
            'winner' => $this->winner,
            'winner_confidence' => $this->winner_confidence,
            'statistical_significance' => $this->hasStatisticalSignificance(),
            'sufficient_samples' => $this->hasSufficientSamples(),
        ];
    }
}
