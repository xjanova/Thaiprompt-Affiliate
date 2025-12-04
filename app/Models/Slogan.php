<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Slogan Model
 *
 * จัดการคำขวัญ/คำคมสำหรับแสดงใน Dashboard และหน้าต่างๆ
 *
 * @property int $id
 * @property string $content เนื้อหาคำขวัญ
 * @property string|null $author ผู้แต่ง/แหล่งที่มา
 * @property string $category หมวดหมู่
 * @property string $icon ไอคอน Emoji
 * @property bool $is_active สถานะการใช้งาน
 * @property int $priority ลำดับความสำคัญ
 * @property int $display_count จำนวนครั้งที่แสดง
 * @property array|null $roles roles ที่แสดง
 * @property \Carbon\Carbon|null $start_date วันเริ่มแสดง
 * @property \Carbon\Carbon|null $end_date วันสิ้นสุดแสดง
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Slogan extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'slogans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'content',
        'author',
        'category',
        'icon',
        'is_active',
        'priority',
        'display_count',
        'roles',
        'start_date',
        'end_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'display_count' => 'integer',
        'roles' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * หมวดหมู่ที่กำหนดไว้
     */
    const CATEGORIES = [
        'general' => 'ทั่วไป',
        'motivation' => 'แรงบันดาลใจ',
        'business' => 'ธุรกิจ',
        'success' => 'ความสำเร็จ',
        'teamwork' => 'การทำงานร่วมกัน',
        'thai_spirit' => 'จิตวิญญาณไทย',
        'affiliate' => 'Affiliate',
        'ecommerce' => 'อีคอมเมิร์ซ',
        'life' => 'ชีวิต',
        'wisdom' => 'ปรัชญา',
        'seasonal' => 'ตามฤดูกาล',
        'promotion' => 'โปรโมชั่น',
    ];

    /**
     * ไอคอนที่แนะนำ
     */
    const ICONS = [
        '💡', '🌟', '✨', '🔥', '💪', '🎯', '🚀', '💰', '🏆', '❤️',
        '🇹🇭', '🙏', '💝', '🤝', '📈', '💎', '🌈', '⭐', '🎉', '👑',
    ];

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope สำหรับคำขวัญที่ active
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope สำหรับคำขวัญที่อยู่ในช่วงเวลาที่กำหนด
     */
    public function scopeInDateRange(Builder $query): Builder
    {
        $now = now();

        return $query->where(function ($q) use ($now) {
            $q->whereNull('start_date')
                ->orWhere('start_date', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', $now);
        });
    }

    /**
     * Scope สำหรับคำขวัญตาม role
     */
    public function scopeForRole(Builder $query, ?string $role = null): Builder
    {
        if (!$role) {
            return $query;
        }

        return $query->where(function ($q) use ($role) {
            $q->whereNull('roles')
                ->orWhereJsonContains('roles', $role);
        });
    }

    /**
     * Scope สำหรับหมวดหมู่
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope เรียงตาม priority
     */
    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderByDesc('priority');
    }

    // ========================================
    // STATIC METHODS
    // ========================================

    /**
     * สุ่มคำขวัญสำหรับแสดง
     *
     * @param string|null $role Role ของผู้ใช้
     * @param string|null $category หมวดหมู่ที่ต้องการ
     * @param int $count จำนวนที่ต้องการ
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRandom(?string $role = null, ?string $category = null, int $count = 1)
    {
        $query = static::active()
            ->inDateRange()
            ->forRole($role);

        if ($category) {
            $query->category($category);
        }

        // สุ่มโดยให้น้ำหนักตาม priority
        $slogans = $query->get();

        if ($slogans->isEmpty()) {
            return collect();
        }

        // สร้าง weighted random selection
        $weighted = collect();
        foreach ($slogans as $slogan) {
            // เพิ่มคำขวัญตามจำนวน priority
            for ($i = 0; $i < $slogan->priority; $i++) {
                $weighted->push($slogan);
            }
        }

        // สุ่มและเลือกไม่ซ้ำ
        $selected = $weighted->shuffle()->unique('id')->take($count);

        // เพิ่ม display_count
        static::whereIn('id', $selected->pluck('id'))->increment('display_count');

        return $selected->values();
    }

    /**
     * ดึงคำขวัญสำหรับ Seller Dashboard
     *
     * @param int $count จำนวนที่ต้องการ
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getForSellerDashboard(int $count = 5)
    {
        return static::getRandom('seller', null, $count);
    }

    /**
     * ดึงคำขวัญสำหรับ Admin Dashboard
     *
     * @param int $count จำนวนที่ต้องการ
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getForAdminDashboard(int $count = 3)
    {
        return static::getRandom('admin', null, $count);
    }

    /**
     * ดึงคำขวัญทั้งหมดที่ active สำหรับ API
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllActive()
    {
        return static::active()
            ->inDateRange()
            ->byPriority()
            ->get();
    }

    /**
     * ดึงรายการหมวดหมู่
     *
     * @return array
     */
    public static function getCategories(): array
    {
        return static::CATEGORIES;
    }

    /**
     * ดึงรายการไอคอน
     *
     * @return array
     */
    public static function getIcons(): array
    {
        return static::ICONS;
    }

    /**
     * ดึงสถิติ
     *
     * @return array
     */
    public static function getStats(): array
    {
        return [
            'total' => static::count(),
            'active' => static::active()->count(),
            'inactive' => static::where('is_active', false)->count(),
            'by_category' => static::selectRaw('category, count(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
            'total_displays' => static::sum('display_count'),
        ];
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * รับคำขวัญพร้อมไอคอน
     */
    public function getFormattedContentAttribute(): string
    {
        return "{$this->icon} {$this->content}";
    }

    /**
     * รับคำขวัญพร้อมผู้แต่ง
     */
    public function getContentWithAuthorAttribute(): string
    {
        if ($this->author) {
            return "\"{$this->content}\" — {$this->author}";
        }
        return $this->content;
    }

    /**
     * รับชื่อหมวดหมู่ภาษาไทย
     */
    public function getCategoryNameAttribute(): string
    {
        return static::CATEGORIES[$this->category] ?? $this->category;
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * เพิ่ม display count
     */
    public function incrementDisplayCount(): void
    {
        $this->increment('display_count');
    }

    /**
     * Toggle active status
     */
    public function toggleActive(): bool
    {
        $this->is_active = !$this->is_active;
        return $this->save();
    }
}
