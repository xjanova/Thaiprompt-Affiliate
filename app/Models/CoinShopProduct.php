<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CoinShopProduct Model
 *
 * สินค้าในร้านค้า Video Coins
 *
 * @property int $id
 * @property string $name ชื่อสินค้า
 * @property string|null $name_th ชื่อภาษาไทย
 * @property string|null $description รายละเอียด
 * @property string|null $description_th รายละเอียดภาษาไทย
 * @property string|null $thumbnail รูปสินค้า
 * @property string|null $icon ไอคอน
 * @property string $type ประเภทสินค้า
 * @property string|null $category หมวดหมู่
 * @property array|null $tags แท็ก
 * @property float $price_coins ราคา (Coins)
 * @property float|null $original_price_coins ราคาเดิม
 * @property float|null $price_money มูลค่าเทียบเท่าเงิน
 * @property float|null $value_amount มูลค่า
 * @property string|null $value_unit หน่วย
 * @property array|null $value_data ข้อมูลเพิ่มเติม
 * @property int|null $stock_quantity จำนวนคงเหลือ
 * @property int $total_sold จำนวนที่ขายไปแล้ว
 * @property int|null $max_per_user จำกัดต่อคน
 * @property int|null $max_per_day จำกัดต่อวัน
 * @property int $min_level Level ขั้นต่ำ
 * @property int|null $min_rank_id Rank ขั้นต่ำ
 * @property bool $require_kyc ต้อง KYC
 * @property Carbon|null $sale_start_at เริ่มขาย
 * @property Carbon|null $sale_end_at สิ้นสุดการขาย
 * @property Carbon|null $use_expire_at หมดอายุการใช้งาน
 * @property int|null $use_expire_days หมดอายุหลังจากซื้อกี่วัน
 * @property bool $is_active เปิดขาย
 * @property bool $is_featured แนะนำ
 * @property bool $is_limited Limited Edition
 * @property bool $is_new สินค้าใหม่
 * @property int $priority ลำดับความสำคัญ
 * @property int $sort_order ลำดับการแสดง
 * @property int $view_count จำนวนคนดู
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class CoinShopProduct extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'coin_shop_products';

    /**
     * ประเภทสินค้า
     */
    public const TYPES = [
        'voucher' => [
            'name' => 'บัตรกำนัล/ส่วนลด',
            'icon' => '🎫',
            'color' => 'purple',
        ],
        'coupon' => [
            'name' => 'คูปอง',
            'icon' => '🏷️',
            'color' => 'pink',
        ],
        'physical' => [
            'name' => 'สินค้าจริง',
            'icon' => '📦',
            'color' => 'amber',
        ],
        'digital' => [
            'name' => 'สินค้าดิจิทัล',
            'icon' => '💾',
            'color' => 'blue',
        ],
        'privilege' => [
            'name' => 'สิทธิพิเศษ',
            'icon' => '⭐',
            'color' => 'yellow',
        ],
        'upgrade' => [
            'name' => 'อัพเกรด',
            'icon' => '⬆️',
            'color' => 'green',
        ],
        'money' => [
            'name' => 'แลกเป็นเงิน',
            'icon' => '💵',
            'color' => 'emerald',
        ],
        'tpix' => [
            'name' => 'แลกเป็น TPIX',
            'icon' => '🪙',
            'color' => 'orange',
        ],
        'exp_boost' => [
            'name' => 'EXP Boost',
            'icon' => '🚀',
            'color' => 'red',
        ],
        'other' => [
            'name' => 'อื่นๆ',
            'icon' => '🎁',
            'color' => 'gray',
        ],
    ];

    /**
     * ฟิลด์ที่อนุญาตให้ mass assign
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'name_th',
        'description',
        'description_th',
        'thumbnail',
        'icon',
        'type',
        'category',
        'tags',
        'price_coins',
        'original_price_coins',
        'price_money',
        'value_amount',
        'value_unit',
        'value_data',
        'stock_quantity',
        'total_sold',
        'max_per_user',
        'max_per_day',
        'min_level',
        'min_rank_id',
        'require_kyc',
        'sale_start_at',
        'sale_end_at',
        'use_expire_at',
        'use_expire_days',
        'is_active',
        'is_featured',
        'is_limited',
        'is_new',
        'priority',
        'sort_order',
        'view_count',
        'created_by',
        'updated_by',
    ];

    /**
     * ฟิลด์ที่ต้อง cast
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tags' => 'array',
        'value_data' => 'array',
        'price_coins' => 'decimal:2',
        'original_price_coins' => 'decimal:2',
        'price_money' => 'decimal:2',
        'value_amount' => 'decimal:2',
        'require_kyc' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_limited' => 'boolean',
        'is_new' => 'boolean',
        'sale_start_at' => 'datetime',
        'sale_end_at' => 'datetime',
        'use_expire_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'voucher',
        'min_level' => 1,
        'is_active' => true,
        'is_featured' => false,
        'is_limited' => false,
        'is_new' => false,
        'require_kyc' => false,
        'total_sold' => 0,
        'view_count' => 0,
        'priority' => 0,
        'sort_order' => 0,
    ];

    // ==========================================
    // ความสัมพันธ์ (Relationships)
    // ==========================================

    /**
     * ผู้สร้าง
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * ผู้แก้ไขล่าสุด
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Rank ขั้นต่ำ
     */
    public function minRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'min_rank_id');
    }

    /**
     * ประวัติการซื้อ
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(CoinPurchase::class, 'product_id');
    }

    // ==========================================
    // Accessors
    // ==========================================

    /**
     * ชื่อที่แสดง (ภาษาไทยก่อน)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name_th ?? $this->name;
    }

    /**
     * รายละเอียดที่แสดง
     */
    public function getDisplayDescriptionAttribute(): ?string
    {
        return $this->description_th ?? $this->description;
    }

    /**
     * ข้อมูลประเภทสินค้า
     */
    public function getTypeInfoAttribute(): array
    {
        return self::TYPES[$this->type] ?? self::TYPES['other'];
    }

    /**
     * ชื่อประเภท
     */
    public function getTypeNameAttribute(): string
    {
        return $this->type_info['name'];
    }

    /**
     * ไอคอนประเภท
     */
    public function getTypeIconAttribute(): string
    {
        return $this->icon ?? $this->type_info['icon'];
    }

    /**
     * สีประเภท
     */
    public function getTypeColorAttribute(): string
    {
        return $this->type_info['color'];
    }

    /**
     * เปอร์เซ็นต์ส่วนลด
     */
    public function getDiscountPercentAttribute(): ?float
    {
        if (! $this->original_price_coins || $this->original_price_coins <= 0) {
            return null;
        }

        return round((1 - ($this->price_coins / $this->original_price_coins)) * 100, 1);
    }

    /**
     * มีส่วนลดหรือไม่
     */
    public function getHasDiscountAttribute(): bool
    {
        return $this->discount_percent !== null && $this->discount_percent > 0;
    }

    /**
     * สินค้าหมดหรือไม่
     */
    public function getIsOutOfStockAttribute(): bool
    {
        if ($this->stock_quantity === null) {
            return false; // ไม่จำกัดสต็อก
        }

        return $this->stock_quantity <= 0;
    }

    /**
     * จำนวนคงเหลือ (สำหรับแสดง)
     */
    public function getStockDisplayAttribute(): string
    {
        if ($this->stock_quantity === null) {
            return 'ไม่จำกัด';
        }

        return number_format($this->stock_quantity);
    }

    /**
     * อยู่ในช่วงขายหรือไม่
     */
    public function getIsInSalePeriodAttribute(): bool
    {
        $now = Carbon::now();

        if ($this->sale_start_at && $now->lt($this->sale_start_at)) {
            return false;
        }

        if ($this->sale_end_at && $now->gt($this->sale_end_at)) {
            return false;
        }

        return true;
    }

    /**
     * สามารถซื้อได้หรือไม่
     */
    public function getIsPurchasableAttribute(): bool
    {
        return $this->is_active
            && ! $this->is_out_of_stock
            && $this->is_in_sale_period;
    }

    /**
     * URL รูปภาพ
     */
    public function getThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail) {
            return asset('storage/'.$this->thumbnail);
        }

        return asset('images/placeholder-product.png');
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * เฉพาะสินค้าที่เปิดขาย
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * เฉพาะสินค้าแนะนำ
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * เฉพาะสินค้าที่ซื้อได้
     */
    public function scopePurchasable($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('stock_quantity')
                    ->orWhere('stock_quantity', '>', 0);
            })
            ->where(function ($q) {
                $q->whereNull('sale_start_at')
                    ->orWhere('sale_start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('sale_end_at')
                    ->orWhere('sale_end_at', '>=', now());
            });
    }

    /**
     * กรองตามประเภท
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * กรองตาม Level
     */
    public function scopeForLevel($query, int $level)
    {
        return $query->where('min_level', '<=', $level);
    }

    /**
     * กรองตามหมวดหมู่
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * เรียงตาม priority
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc')
            ->orderBy('sort_order', 'asc');
    }

    // ==========================================
    // Methods
    // ==========================================

    /**
     * ตรวจสอบว่า user ซื้อได้หรือไม่
     *
     * @param  int  $quantity  จำนวนที่ต้องการซื้อ
     * @return array ['can_purchase' => bool, 'reason' => string|null]
     */
    public function canPurchase(User $user, int $quantity = 1): array
    {
        // ตรวจสอบสถานะสินค้า
        if (! $this->is_active) {
            return ['can_purchase' => false, 'reason' => 'สินค้านี้ไม่ได้เปิดขายในขณะนี้'];
        }

        // ตรวจสอบช่วงเวลาขาย
        if (! $this->is_in_sale_period) {
            if ($this->sale_start_at && Carbon::now()->lt($this->sale_start_at)) {
                return [
                    'can_purchase' => false,
                    'reason' => 'สินค้านี้ยังไม่เปิดขาย จะเริ่มขาย '.$this->sale_start_at->thaidate('j M y H:i'),
                ];
            }

            return ['can_purchase' => false, 'reason' => 'สินค้านี้หมดระยะเวลาขายแล้ว'];
        }

        // ตรวจสอบสต็อก
        if ($this->stock_quantity !== null) {
            if ($this->stock_quantity < $quantity) {
                return ['can_purchase' => false, 'reason' => 'สินค้าคงเหลือไม่เพียงพอ'];
            }
        }

        // ตรวจสอบ Level
        $userLevel = $user->videoLevel?->currentLevel?->level ?? 1;
        if ($userLevel < $this->min_level) {
            return [
                'can_purchase' => false,
                'reason' => "ต้องมี Level อย่างน้อย {$this->min_level} จึงจะซื้อได้ (คุณอยู่ Level {$userLevel})",
            ];
        }

        // ตรวจสอบ Rank
        if ($this->min_rank_id) {
            $userRankId = $user->rank_id ?? 0;
            if ($userRankId < $this->min_rank_id) {
                $rankName = $this->minRank?->name ?? "Rank {$this->min_rank_id}";

                return [
                    'can_purchase' => false,
                    'reason' => "ต้องมี Rank อย่างน้อย {$rankName} จึงจะซื้อได้",
                ];
            }
        }

        // ตรวจสอบ KYC
        if ($this->require_kyc && ! $user->is_kyc_verified) {
            return ['can_purchase' => false, 'reason' => 'สินค้านี้ต้องยืนยันตัวตน (KYC) ก่อนจึงจะซื้อได้'];
        }

        // ตรวจสอบจำกัดต่อคน
        if ($this->max_per_user) {
            $userPurchaseCount = $this->purchases()
                ->where('user_id', $user->id)
                ->whereNotIn('status', ['cancelled', 'refunded'])
                ->sum('quantity');

            if (($userPurchaseCount + $quantity) > $this->max_per_user) {
                $remaining = $this->max_per_user - $userPurchaseCount;

                return [
                    'can_purchase' => false,
                    'reason' => "สินค้านี้จำกัดการซื้อต่อคน {$this->max_per_user} ชิ้น (คุณซื้อไปแล้ว {$userPurchaseCount} ชิ้น, เหลือ {$remaining} ชิ้น)",
                ];
            }
        }

        // ตรวจสอบจำกัดต่อวัน
        if ($this->max_per_day) {
            $todayPurchaseCount = $this->purchases()
                ->where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->whereNotIn('status', ['cancelled', 'refunded'])
                ->sum('quantity');

            if (($todayPurchaseCount + $quantity) > $this->max_per_day) {
                $remaining = $this->max_per_day - $todayPurchaseCount;

                return [
                    'can_purchase' => false,
                    'reason' => "สินค้านี้จำกัดการซื้อ {$this->max_per_day} ชิ้นต่อวัน (คุณซื้อไปแล้ว {$todayPurchaseCount} ชิ้นวันนี้)",
                ];
            }
        }

        // ตรวจสอบ Coins
        $totalPrice = $this->price_coins * $quantity;
        $userBalance = $user->videoCoin?->balance ?? 0;
        if ($userBalance < $totalPrice) {
            return [
                'can_purchase' => false,
                'reason' => 'Coins ไม่เพียงพอ ต้องการ '.number_format($totalPrice, 2).' Coins แต่คุณมี '.number_format($userBalance, 2).' Coins',
            ];
        }

        return ['can_purchase' => true, 'reason' => null];
    }

    /**
     * ลดสต็อก
     */
    public function decreaseStock(int $quantity = 1): bool
    {
        if ($this->stock_quantity === null) {
            // ไม่จำกัดสต็อก
            $this->increment('total_sold', $quantity);

            return true;
        }

        if ($this->stock_quantity < $quantity) {
            return false;
        }

        $this->decrement('stock_quantity', $quantity);
        $this->increment('total_sold', $quantity);

        return true;
    }

    /**
     * เพิ่มสต็อก (คืนสินค้า)
     */
    public function increaseStock(int $quantity = 1): void
    {
        if ($this->stock_quantity !== null) {
            $this->increment('stock_quantity', $quantity);
        }

        $this->decrement('total_sold', $quantity);
    }

    /**
     * เพิ่มยอดเข้าชม
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * คำนวณวันหมดอายุหลังซื้อ
     */
    public function calculateExpiryDate(): ?Carbon
    {
        if ($this->use_expire_at) {
            return $this->use_expire_at;
        }

        if ($this->use_expire_days) {
            return now()->addDays($this->use_expire_days);
        }

        return null;
    }
}
