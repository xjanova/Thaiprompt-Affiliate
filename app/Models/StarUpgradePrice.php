<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * StarUpgradePrice Model
 *
 * ราคาอัพเกรดดาวแต่ละระดับ
 *
 * @property int $id
 * @property int $stars จำนวนดาว (1-5)
 * @property string $name ชื่อ
 * @property string|null $name_th ชื่อภาษาไทย
 * @property string|null $description คำอธิบาย
 * @property string|null $description_th คำอธิบายภาษาไทย
 * @property string $star_color สีดาว
 * @property float $price_coins ราคา Coins
 * @property float|null $price_coins_from_previous ราคาอัพจากดาวก่อนหน้า
 * @property int $min_level Level ขั้นต่ำ
 * @property bool $require_previous_star ต้องมีดาวก่อนหน้า
 * @property array|null $benefits สิทธิประโยชน์
 * @property float $exp_multiplier ตัวคูณ EXP
 * @property float $coin_multiplier ตัวคูณ Coins
 * @property bool $is_active
 * @property int $sort_order
 */
class StarUpgradePrice extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'star_upgrade_prices';

    /**
     * สีดาว
     */
    public const STAR_COLORS = [
        'bronze' => [
            'name' => 'ทองแดง',
            'hex' => '#CD7F32',
            'gradient' => 'from-amber-600 to-amber-800',
            'bg' => 'bg-amber-600',
        ],
        'silver' => [
            'name' => 'เงิน',
            'hex' => '#C0C0C0',
            'gradient' => 'from-gray-300 to-gray-500',
            'bg' => 'bg-gray-400',
        ],
        'gold' => [
            'name' => 'ทอง',
            'hex' => '#FFD700',
            'gradient' => 'from-yellow-400 to-yellow-600',
            'bg' => 'bg-yellow-500',
        ],
        'platinum' => [
            'name' => 'แพลตินัม',
            'hex' => '#E5E4E2',
            'gradient' => 'from-slate-200 to-slate-400',
            'bg' => 'bg-slate-300',
        ],
        'diamond' => [
            'name' => 'เพชร',
            'hex' => '#B9F2FF',
            'gradient' => 'from-cyan-300 to-blue-500',
            'bg' => 'bg-cyan-400',
        ],
    ];

    /**
     * ฟิลด์ที่อนุญาตให้ mass assign
     *
     * @var array<string>
     */
    protected $fillable = [
        'stars',
        'name',
        'name_th',
        'description',
        'description_th',
        'star_color',
        'price_coins',
        'price_coins_from_previous',
        'min_level',
        'require_previous_star',
        'benefits',
        'exp_multiplier',
        'coin_multiplier',
        'is_active',
        'sort_order',
    ];

    /**
     * ฟิลด์ที่ต้อง cast
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_coins' => 'decimal:2',
        'price_coins_from_previous' => 'decimal:2',
        'benefits' => 'array',
        'exp_multiplier' => 'decimal:2',
        'coin_multiplier' => 'decimal:2',
        'require_previous_star' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'star_color' => 'bronze',
        'min_level' => 1,
        'require_previous_star' => true,
        'exp_multiplier' => 1.00,
        'coin_multiplier' => 1.00,
        'is_active' => true,
        'sort_order' => 0,
    ];

    // ==========================================
    // ความสัมพันธ์ (Relationships)
    // ==========================================

    /**
     * ประวัติการอัพเกรดมาดาวนี้
     */
    public function upgradeHistory(): HasMany
    {
        return $this->hasMany(StarUpgradeHistory::class, 'to_stars', 'stars');
    }

    // ==========================================
    // Accessors
    // ==========================================

    /**
     * ชื่อที่แสดง
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name_th ?? $this->name;
    }

    /**
     * คำอธิบายที่แสดง
     */
    public function getDisplayDescriptionAttribute(): ?string
    {
        return $this->description_th ?? $this->description;
    }

    /**
     * ข้อมูลสีดาว
     */
    public function getStarColorInfoAttribute(): array
    {
        return self::STAR_COLORS[$this->star_color] ?? self::STAR_COLORS['bronze'];
    }

    /**
     * ชื่อสีดาว
     */
    public function getStarColorNameAttribute(): string
    {
        return $this->star_color_info['name'];
    }

    /**
     * Hex สีดาว
     */
    public function getStarColorHexAttribute(): string
    {
        return $this->star_color_info['hex'];
    }

    /**
     * Gradient class
     */
    public function getGradientClassAttribute(): string
    {
        return $this->star_color_info['gradient'];
    }

    /**
     * Background class
     */
    public function getBgClassAttribute(): string
    {
        return $this->star_color_info['bg'];
    }

    /**
     * แสดงดาว (HTML)
     */
    public function getStarsHtmlAttribute(): string
    {
        $starSvg = '<svg class="w-5 h-5" style="color: '.$this->star_color_hex.'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';

        return str_repeat($starSvg, $this->stars);
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * เฉพาะที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * เรียงตามดาว
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('stars', 'asc');
    }

    /**
     * กรองตาม Level
     */
    public function scopeForLevel($query, int $level)
    {
        return $query->where('min_level', '<=', $level);
    }

    // ==========================================
    // Static Methods
    // ==========================================

    /**
     * ดึงราคาตามจำนวนดาว
     */
    public static function getByStars(int $stars): ?self
    {
        return static::where('stars', $stars)->first();
    }

    /**
     * ดึงราคาทั้งหมดที่ active
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllActive()
    {
        return static::active()->ordered()->get();
    }

    /**
     * คำนวณราคาอัพเกรดจากดาวปัจจุบัน
     *
     * @param  int  $currentStars  ดาวปัจจุบัน
     * @param  int  $targetStars  ดาวที่ต้องการ
     */
    public static function calculateUpgradePrice(int $currentStars, int $targetStars): ?float
    {
        if ($targetStars <= $currentStars) {
            return null;
        }

        $targetPrice = static::getByStars($targetStars);
        if (! $targetPrice || ! $targetPrice->is_active) {
            return null;
        }

        // ถ้ามีดาวอยู่แล้ว ใช้ราคาจากดาวก่อนหน้า (ถ้ากำหนดไว้)
        if ($currentStars > 0 && $targetPrice->price_coins_from_previous !== null) {
            return $targetPrice->price_coins_from_previous;
        }

        return $targetPrice->price_coins;
    }
}
