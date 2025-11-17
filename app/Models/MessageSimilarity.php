<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MessageSimilarity Model
 *
 * เก็บข้อมูลความคล้ายกันระหว่างข้อความ
 * ใช้สำหรับ semantic analysis และการเรียนรู้
 *
 * @property int $id
 * @property int $message_sentiment_id_1
 * @property int $message_sentiment_id_2
 * @property float $cosine_similarity
 * @property float $entity_overlap
 * @property float $intent_similarity
 * @property float $overall_similarity
 * @property string $similarity_type
 * @property array|null $matching_entities
 * @property array|null $matching_intents
 * @property array|null $matching_keywords
 * @property string|null $explanation
 * @property int $common_entity_count
 * @property int $common_keyword_count
 * @property int $message_gap_days
 */
class MessageSimilarity extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'message_similarities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'message_sentiment_id_1',
        'message_sentiment_id_2',
        'cosine_similarity',
        'entity_overlap',
        'intent_similarity',
        'overall_similarity',
        'similarity_type',
        'matching_entities',
        'matching_intents',
        'matching_keywords',
        'explanation',
        'common_entity_count',
        'common_keyword_count',
        'message_gap_days',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'matching_entities' => 'json',
        'matching_intents' => 'json',
        'matching_keywords' => 'json',
        'cosine_similarity' => 'float',
        'entity_overlap' => 'float',
        'intent_similarity' => 'float',
        'overall_similarity' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ MessageSentiment (first message)
     *
     * @return BelongsTo
     */
    public function message1(): BelongsTo
    {
        return $this->belongsTo(MessageSentiment::class, 'message_sentiment_id_1');
    }

    /**
     * ความสัมพันธ์กับ MessageSentiment (second message)
     *
     * @return BelongsTo
     */
    public function message2(): BelongsTo
    {
        return $this->belongsTo(MessageSentiment::class, 'message_sentiment_id_2');
    }

    /**
     * Scope: ดึง exact matches
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExact($query)
    {
        return $query->where('similarity_type', 'EXACT');
    }

    /**
     * Scope: ดึง semantic similarities
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSemantic($query)
    {
        return $query->where('similarity_type', 'SEMANTIC');
    }

    /**
     * Scope: ดึง high similarity matches
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $threshold ค่าต่ำสุด (default: 0.7)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighSimilarity($query, float $threshold = 0.7)
    {
        return $query->where('overall_similarity', '>=', $threshold);
    }

    /**
     * Scope: เรียงตาม overall similarity
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderBySimilarity($query)
    {
        return $query->orderBy('overall_similarity', 'desc');
    }

    /**
     * ตรวจสอบว่า messages ชนิด exact match
     *
     * @return bool
     */
    public function isExactMatch(): bool
    {
        return $this->similarity_type === 'EXACT';
    }

    /**
     * ตรวจสอบว่า similarity สูง
     *
     * @param float $threshold default: 0.7
     * @return bool
     */
    public function isHighSimilarity(float $threshold = 0.7): bool
    {
        return $this->overall_similarity >= $threshold;
    }

    /**
     * ดึง matching entities count
     *
     * @return int
     */
    public function getMatchingEntityCountAttribute(): int
    {
        return count($this->matching_entities ?? []);
    }

    /**
     * ดึง matching intents count
     *
     * @return int
     */
    public function getMatchingIntentCountAttribute(): int
    {
        return count($this->matching_intents ?? []);
    }

    /**
     * ดึง similarity percentage
     *
     * @return int
     */
    public function getSimilarityPercentageAttribute(): int
    {
        return (int) round($this->overall_similarity * 100);
    }
}
