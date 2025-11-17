<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KeywordClusterItem Model
 *
 * Junction model สำหรับความสัมพันธ์ระหว่าง KeywordCluster และ LineBotKeyword
 *
 * @property int $id
 * @property int $keyword_cluster_id
 * @property int $line_bot_keyword_id
 * @property string $relationship_type ประเภท: PRIMARY, SYNONYM, RELATED, VARIANT, SIMILAR
 * @property float $relevance_score ความเกี่ยวข้อง (0-1)
 * @property int $co_occurrence_count จำนวนครั้งที่ปรากฏพร้อมกัน
 * @property array|null $context_data ข้อมูลบริบท
 * @property string|null $notes หมายเหตุ
 */
class KeywordClusterItem extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'keyword_cluster_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'keyword_cluster_id',
        'line_bot_keyword_id',
        'relationship_type',
        'relevance_score',
        'co_occurrence_count',
        'context_data',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'relevance_score' => 'float',
        'co_occurrence_count' => 'integer',
        'context_data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ KeywordCluster
     *
     * @return BelongsTo
     */
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(KeywordCluster::class, 'keyword_cluster_id');
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
     * Scope: ดึง items ตามประเภท relationship
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type ประเภท relationship
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('relationship_type', $type);
    }

    /**
     * Scope: ดึง primary items
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrimary($query)
    {
        return $query->where('relationship_type', 'PRIMARY');
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
     * Scope: ดึง items ที่มี relevance score สูง
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minScore ค่า score ต่ำสุด (default: 0.7)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighRelevance($query, float $minScore = 0.7)
    {
        return $query->where('relevance_score', '>=', $minScore);
    }

    /**
     * Scope: เรียงตามความเกี่ยวข้อง
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByRelevance($query)
    {
        return $query->orderBy('relevance_score', 'desc')
            ->orderBy('co_occurrence_count', 'desc');
    }

    /**
     * ความเกี่ยวข้องในรูปแบบเปอร์เซ็นต์
     *
     * @return int
     */
    public function getRelevancePercentageAttribute(): int
    {
        return (int) round($this->relevance_score * 100);
    }

    /**
     * ตรวจสอบว่า item เป็น primary
     *
     * @return bool
     */
    public function isPrimary(): bool
    {
        return $this->relationship_type === 'PRIMARY';
    }

    /**
     * ตรวจสอบว่า item เป็น synonym
     *
     * @return bool
     */
    public function isSynonym(): bool
    {
        return $this->relationship_type === 'SYNONYM';
    }

    /**
     * ตรวจสอบว่า item มี co-occurrence แรง
     *
     * @param int $threshold เกณฑ์ต่ำสุด (default: 5)
     * @return bool
     */
    public function hasStrongCoOccurrence(int $threshold = 5): bool
    {
        return $this->co_occurrence_count >= $threshold;
    }

    /**
     * ดึง relationship type label
     *
     * @return string
     */
    public function getRelationshipTypeLabel(): string
    {
        return match($this->relationship_type) {
            'PRIMARY' => 'หลัก',
            'SYNONYM' => 'คำพ้องความหมาย',
            'RELATED' => 'เกี่ยวข้อง',
            'VARIANT' => 'รูปแบบอื่น',
            'SIMILAR' => 'คล้ายกัน',
            default => $this->relationship_type,
        };
    }

    /**
     * เพิ่มจำนวน co-occurrence
     *
     * @param int $count จำนวนครั้งที่เพิ่ม (default: 1)
     * @return void
     */
    public function incrementCoOccurrence(int $count = 1): void
    {
        $this->update([
            'co_occurrence_count' => $this->co_occurrence_count + $count,
        ]);
    }

    /**
     * อัพเดท relevance score
     *
     * @param float $newScore ค่า score ใหม่
     * @return void
     */
    public function updateRelevanceScore(float $newScore): void
    {
        $this->update(['relevance_score' => min(max($newScore, 0), 1)]);
    }
}
