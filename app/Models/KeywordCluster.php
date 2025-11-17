<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * KeywordCluster Model
 *
 * จัดกลุ่มคำสำคัญที่เกี่ยวข้องกัน
 * ใช้สำหรับขยายการตรวจจับคำสำคัญ และสร้างข้อเสนอแนะที่เกี่ยวข้อง
 *
 * @property int $id
 * @property string $cluster_name ชื่อตัวระบุ (unique)
 * @property string $display_name ชื่อแสดง
 * @property string|null $description คำอธิบาย
 * @property string|null $icon ไอคอน
 * @property string $cluster_category หมวดหมู่กลุ่ม
 * @property int $keyword_count จำนวนคำสำคัญ
 * @property array|null $primary_keywords คำสำคัญหลัก
 * @property array|null $related_intents Intents ที่เกี่ยวข้อง
 * @property array|null $related_entities Entities ที่เกี่ยวข้อง
 * @property array|null $suggested_actions แนะนำการดำเนินการ
 * @property int $total_matches จำนวนครั้งที่พบ
 * @property float $usage_frequency ความถี่การใช้
 * @property int|null $days_since_last_match วันตั้งแต่พบครั้งล่าสุด
 * @property bool $is_active ใช้งานอยู่หรือไม่
 * @property bool $is_system เป็นระบบหรือผู้ใช้สร้าง
 */
class KeywordCluster extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'keyword_clusters';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'cluster_name',
        'display_name',
        'description',
        'icon',
        'cluster_category',
        'keyword_count',
        'primary_keywords',
        'related_intents',
        'related_entities',
        'suggested_actions',
        'total_matches',
        'usage_frequency',
        'days_since_last_match',
        'is_active',
        'is_system',
        'created_by_user_id',
        'last_modified_by_user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'primary_keywords' => 'json',
        'related_intents' => 'json',
        'related_entities' => 'json',
        'suggested_actions' => 'json',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'usage_frequency' => 'float',
        'total_matches' => 'integer',
        'days_since_last_match' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์: many-to-many กับ LineBotKeyword
     * ผ่าน keyword_cluster_items
     *
     * @return BelongsToMany
     */
    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(
            LineBotKeyword::class,
            'keyword_cluster_items',
            'keyword_cluster_id',
            'line_bot_keyword_id'
        )
            ->withPivot('relationship_type', 'relevance_score', 'co_occurrence_count', 'context_data', 'notes')
            ->withTimestamps();
    }

    /**
     * ความสัมพันธ์: hasMany กับ KeywordClusterItem
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(KeywordClusterItem::class, 'keyword_cluster_id');
    }

    /**
     * Scope: ดึงเฉพาะ active clusters
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ดึง clusters ตามหมวดหมู่
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category หมวดหมู่
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('cluster_category', $category);
    }

    /**
     * Scope: ดึง clusters ที่ใช้บ่อย (usage frequency สูง)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minFrequency ความถี่ต่ำสุด (default: 0.5)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFrequent($query, float $minFrequency = 0.5)
    {
        return $query->where('usage_frequency', '>=', $minFrequency)
            ->orderBy('total_matches', 'desc');
    }

    /**
     * Scope: ดึง system clusters
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope: ดึง user-defined clusters
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUserDefined($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope: เรียงตามการใช้งาน (popularity)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByPopularity($query)
    {
        return $query->orderBy('total_matches', 'desc');
    }

    /**
     * ดึง primary keywords เป็น array
     *
     * @return array
     */
    public function getPrimaryKeywordsArray(): array
    {
        return $this->primary_keywords ?? [];
    }

    /**
     * ดึง primary keywords เป็น string (comma-separated)
     *
     * @return string
     */
    public function getPrimaryKeywordsString(): string
    {
        return implode(', ', $this->getPrimaryKeywordsArray());
    }

    /**
     * ดึง related intents
     *
     * @return array
     */
    public function getRelatedIntents(): array
    {
        return $this->related_intents ?? [];
    }

    /**
     * ดึง related entities
     *
     * @return array
     */
    public function getRelatedEntities(): array
    {
        return $this->related_entities ?? [];
    }

    /**
     * ตรวจสอบว่า cluster นี้มีคำสำคัญ
     *
     * @return bool
     */
    public function hasKeywords(): bool
    {
        return $this->keyword_count > 0;
    }

    /**
     * ตรวจสอบว่า cluster นี้ถูกใช้งาน (matches หรือ frequency สูง)
     *
     * @return bool
     */
    public function isInUse(): bool
    {
        return $this->total_matches > 0 || $this->usage_frequency > 0.1;
    }

    /**
     * ตรวจสอบว่า cluster มี suggested actions
     *
     * @return bool
     */
    public function hasSuggestedActions(): bool
    {
        return !empty($this->suggested_actions) && is_array($this->suggested_actions);
    }

    /**
     * ดึงสถานะ activity ของ cluster
     *
     * @return string
     */
    public function getActivityStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'ปิดใช้งาน';
        }

        if ($this->days_since_last_match === null) {
            return 'ยังไม่ถูกใช้';
        }

        if ($this->days_since_last_match === 0) {
            return 'ใช้งานวันนี้';
        }

        if ($this->days_since_last_match <= 7) {
            return 'ใช้งานล่าสุด';
        }

        return 'ไม่ได้ใช้งาน';
    }

    /**
     * ดึง usage frequency เป็นเปอร์เซ็นต์
     *
     * @return int
     */
    public function getUsageFrequencyPercentageAttribute(): int
    {
        return (int) round($this->usage_frequency * 100);
    }

    /**
     * ดึง keyword count จาก relation
     *
     * @return int
     */
    public function getActualKeywordCountAttribute(): int
    {
        return $this->keywords()->count();
    }

    /**
     * เพิ่ม keyword ลงใน cluster
     *
     * @param LineBotKeyword $keyword
     * @param string $relationshipType PRIMARY|SYNONYM|RELATED|VARIANT|SIMILAR
     * @param float $relevanceScore ความเกี่ยวข้อง (0-1)
     * @return void
     */
    public function addKeyword(LineBotKeyword $keyword, string $relationshipType = 'RELATED', float $relevanceScore = 1.0): void
    {
        $this->keywords()->attach($keyword->id, [
            'relationship_type' => $relationshipType,
            'relevance_score' => $relevanceScore,
        ]);

        // Update keyword count
        $this->update(['keyword_count' => $this->keywords()->count()]);
    }

    /**
     * ลบ keyword ออกจาก cluster
     *
     * @param LineBotKeyword $keyword
     * @return void
     */
    public function removeKeyword(LineBotKeyword $keyword): void
    {
        $this->keywords()->detach($keyword->id);

        // Update keyword count
        $this->update(['keyword_count' => $this->keywords()->count()]);
    }

    /**
     * อัพเดท usage statistics
     *
     * @param int $matchCount จำนวนครั้งที่พบ
     * @return void
     */
    public function recordMatch(int $matchCount = 1): void
    {
        $this->update([
            'total_matches' => $this->total_matches + $matchCount,
            'days_since_last_match' => 0,
            'usage_frequency' => min(($this->total_matches + $matchCount) / max(100, $this->total_matches + $matchCount), 1.0),
        ]);
    }
}
