<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * KeywordRelationship Model
 *
 * เก็บข้อมูล relationships ระหว่าง keywords
 * ใช้สำหรับ auto-discovery ของ related keywords
 *
 * @property int $id
 * @property int $source_keyword_id
 * @property int $related_keyword_id
 * @property float $strength_score
 * @property int $co_occurrence_frequency
 * @property int $shared_user_count
 * @property string $relationship_type
 * @property string $discovery_method
 * @property array|null $supporting_messages
 * @property array|null $contextual_info
 * @property int $days_tracked
 * @property float $confidence
 * @property bool $is_verified
 * @property bool $is_active
 * @property int $usage_count
 */
class KeywordRelationship extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'keyword_relationships';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'source_keyword_id',
        'related_keyword_id',
        'strength_score',
        'co_occurrence_frequency',
        'shared_user_count',
        'relationship_type',
        'discovery_method',
        'supporting_messages',
        'contextual_info',
        'days_tracked',
        'confidence',
        'is_verified',
        'is_active',
        'usage_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'supporting_messages' => 'json',
        'contextual_info' => 'json',
        'strength_score' => 'float',
        'confidence' => 'float',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ source keyword
     *
     * @return BelongsTo
     */
    public function sourceKeyword(): BelongsTo
    {
        return $this->belongsTo(LineBotKeyword::class, 'source_keyword_id');
    }

    /**
     * ความสัมพันธ์กับ related keyword
     *
     * @return BelongsTo
     */
    public function relatedKeyword(): BelongsTo
    {
        return $this->belongsTo(LineBotKeyword::class, 'related_keyword_id');
    }

    /**
     * Scope: ดึง active relationships
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ดึง verified relationships
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: ดึง strong relationships
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minStrength ค่าต่ำสุด (default: 0.7)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStrong($query, float $minStrength = 0.7)
    {
        return $query->where('strength_score', '>=', $minStrength);
    }

    /**
     * Scope: ดึง synonyms
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSynonyms($query)
    {
        return $query->where('relationship_type', 'SYNONYM');
    }

    /**
     * Scope: ดึง by relationship type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('relationship_type', $type);
    }

    /**
     * Scope: ดึง high confidence relationships
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minConfidence ค่าต่ำสุด (default: 0.75)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighConfidence($query, float $minConfidence = 0.75)
    {
        return $query->where('confidence', '>=', $minConfidence);
    }

    /**
     * ตรวจสอบว่า relationship เป็น synonym
     *
     * @return bool
     */
    public function isSynonym(): bool
    {
        return $this->relationship_type === 'SYNONYM';
    }

    /**
     * ตรวจสอบว่า relationship ได้รับการยืนยัน
     *
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    /**
     * ตรวจสอบว่า relationship แข็งแรง
     *
     * @param float $threshold default: 0.7
     * @return bool
     */
    public function isStrong(float $threshold = 0.7): bool
    {
        return $this->strength_score >= $threshold;
    }

    /**
     * ดึง strength percentage
     *
     * @return int
     */
    public function getStrengthPercentageAttribute(): int
    {
        return (int) round($this->strength_score * 100);
    }

    /**
     * ดึง confidence percentage
     *
     * @return int
     */
    public function getConfidencePercentageAttribute(): int
    {
        return (int) round($this->confidence * 100);
    }

    /**
     * ดึง supporting message count
     *
     * @return int
     */
    public function getSupportingMessageCountAttribute(): int
    {
        return count($this->supporting_messages ?? []);
    }

    /**
     * Increment usage count
     *
     * @return void
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Update strength based on new data
     *
     * @param float $newScore
     * @return void
     */
    public function updateStrength(float $newScore): void
    {
        // Average with existing score
        $avgScore = ($this->strength_score + $newScore) / 2;
        $this->update(['strength_score' => $avgScore]);
    }
}
