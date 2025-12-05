<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * MobileBanner Model
 *
 * จัดการ Banner โฆษณาในแอพมือถือ
 * Admin สามารถเพิ่ม/แก้ไข/ลบ banner ได้
 *
 * @property int $id
 * @property string $title หัวข้อ banner
 * @property string $image URL รูปภาพ
 * @property string|null $link ลิงก์เมื่อคลิก
 * @property string $link_type ประเภทลิงก์ (internal, external, product, category)
 * @property string|null $link_target เป้าหมาย (product_id, category_id, etc.)
 * @property string $position ตำแหน่ง (top, middle, bottom)
 * @property int $sort_order ลำดับการแสดง
 * @property bool $is_active สถานะเปิด/ปิด
 * @property \Carbon\Carbon|null $start_date วันเริ่มแสดง
 * @property \Carbon\Carbon|null $end_date วันสิ้นสุดการแสดง
 * @property int $view_count จำนวนการแสดง
 * @property int $click_count จำนวนการคลิก
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MobileBanner extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mobile_banners';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'image',
        'link',
        'link_type',
        'link_target',
        'position',
        'sort_order',
        'is_active',
        'start_date',
        'end_date',
        'view_count',
        'click_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'view_count' => 'integer',
        'click_count' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values.
     *
     * @var array
     */
    protected $attributes = [
        'position' => 'top',
        'link_type' => 'internal',
        'is_active' => true,
        'view_count' => 0,
        'click_count' => 0,
        'sort_order' => 0,
    ];

    /**
     * Scope: เฉพาะ Active banners
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope: ตามตำแหน่ง
     */
    public function scopePosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope: เรียงตามลำดับ
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * เพิ่ม view count
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    /**
     * เพิ่ม click count
     */
    public function incrementClicks(): void
    {
        $this->increment('click_count');
    }

    /**
     * คำนวณ CTR (Click-Through Rate)
     */
    public function getCtrAttribute(): float
    {
        if ($this->view_count === 0) return 0;
        return round(($this->click_count / $this->view_count) * 100, 2);
    }

    /**
     * ตรวจสอบว่า banner กำลังแสดงอยู่หรือไม่
     */
    public function getIsShowingAttribute(): bool
    {
        if (!$this->is_active) return false;

        $now = now();

        if ($this->start_date && $this->start_date > $now) return false;
        if ($this->end_date && $this->end_date < $now) return false;

        return true;
    }
}
