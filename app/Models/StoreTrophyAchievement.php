<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StoreTrophyAchievement Model
 *
 * บันทึก Trophy ที่ร้านค้าได้รับ
 *
 * @property int $id
 * @property int $store_id
 * @property int $trophy_id
 * @property \Carbon\Carbon $achieved_at
 * @property array|null $snapshot
 * @property bool $is_displayed
 *
 * @property-read VendorStore $store
 * @property-read StoreTrophy $trophy
 */
class StoreTrophyAchievement extends Model
{
    use HasFactory;

    protected $table = 'store_trophy_achievements';

    protected $fillable = [
        'store_id',
        'trophy_id',
        'achieved_at',
        'snapshot',
        'is_displayed',
    ];

    protected $casts = [
        'achieved_at' => 'datetime',
        'snapshot' => 'array',
        'is_displayed' => 'boolean',
    ];

    /**
     * ความสัมพันธ์กับ VendorStore
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * ความสัมพันธ์กับ StoreTrophy
     */
    public function trophy(): BelongsTo
    {
        return $this->belongsTo(StoreTrophy::class, 'trophy_id');
    }

    /**
     * Scope สำหรับ Trophy ที่แสดงบนหน้าร้าน
     */
    public function scopeDisplayed($query)
    {
        return $query->where('is_displayed', true);
    }

    /**
     * Scope สำหรับเรียงตามวันที่ได้รับ
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('achieved_at');
    }

    /**
     * Toggle การแสดงผล
     */
    public function toggleDisplay(): bool
    {
        $this->is_displayed = !$this->is_displayed;
        $this->save();
        return $this->is_displayed;
    }
}
