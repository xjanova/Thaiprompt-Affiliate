<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * ServicePackage Model - แพคเกจบริการ
 *
 * แพคเกจที่รวมบริการ + สินค้า เช่น นวด 1 ชม. + โลชั่น + น้ำมันหอมระเหย
 *
 * @property int $id
 * @property int $owner_id
 * @property string $name ชื่อแพคเกจ
 * @property string $slug URL slug
 * @property string|null $description คำอธิบาย
 * @property array|null $images รูปภาพ
 * @property float $total_price ราคารวมก่อนลด
 * @property float $discount_percentage ส่วนลด (%)
 * @property float $final_price ราคาสุทธิ
 * @property bool $is_active เปิดใช้งาน
 * @property bool $is_featured แนะนำ
 * @property \Carbon\Carbon|null $valid_from เริ่มใช้งาน
 * @property \Carbon\Carbon|null $valid_until สิ้นสุด
 * @property int $purchase_count จำนวนครั้งที่ซื้อ
 * @property float $average_rating คะแนนเฉลี่ย
 */
class ServicePackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_packages';

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'images',
        'total_price',
        'discount_percentage',
        'final_price',
        'is_active',
        'is_featured',
        'valid_from',
        'valid_until',
        'purchase_count',
        'average_rating',
    ];

    protected $casts = [
        'images' => 'array',
        'total_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'final_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'purchase_count' => 'integer',
        'average_rating' => 'decimal:2',
    ];

    //===========================================
    // Lifecycle Hooks
    //===========================================

    protected static function boot()
    {
        parent::boot();

        // สร้าง slug อัตโนมัติจาก name
        static::creating(function ($package) {
            if (empty($package->slug)) {
                $package->slug = Str::slug($package->name);
            }
        });

        // คำนวณ final_price อัตโนมัติ
        static::saving(function ($package) {
            if ($package->isDirty(['total_price', 'discount_percentage'])) {
                $discount = ($package->total_price * $package->discount_percentage) / 100;
                $package->final_price = $package->total_price - $discount;
            }
        });
    }

    //===========================================
    // Relationships
    //===========================================

    /**
     * เจ้าของแพคเกจ
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * รายการในแพคเกจ
     */
    public function items(): HasMany
    {
        return $this->hasMany(ServicePackageItem::class, 'package_id');
    }

    /**
     * การจองที่ใช้แพคเกจนี้
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class, 'package_id');
    }

    //===========================================
    // Scopes
    //===========================================

    /**
     * Scope: เฉพาะแพคเกจที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เฉพาะแพคเกจแนะนำ
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: เฉพาะแพคเกจที่ใช้งานได้ (ยังไม่หมดอายุ)
     */
    public function scopeValid($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('valid_from')
                ->orWhere('valid_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('valid_until')
                ->orWhere('valid_until', '>=', $now);
        });
    }

    /**
     * Scope: ค้นหาแพคเกจ
     */
    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%");
        });
    }

    //===========================================
    // Accessors & Mutators
    //===========================================

    /**
     * รูปภาพหลัก
     */
    public function getMainImageAttribute(): ?string
    {
        return $this->images[0] ?? null;
    }

    /**
     * ยอดส่วนลด (บาท)
     */
    public function getDiscountAmountAttribute(): float
    {
        return $this->total_price - $this->final_price;
    }

    /**
     * % ส่วนลดที่แท้จริง
     */
    public function getActualDiscountPercentageAttribute(): float
    {
        if ($this->total_price <= 0) {
            return 0;
        }
        return (($this->total_price - $this->final_price) / $this->total_price) * 100;
    }

    //===========================================
    // Methods - Package Status
    //===========================================

    /**
     * ตรวจสอบว่าแพคเกจพร้อมใช้งานหรือไม่
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        // ตรวจสอบวันเริ่มต้น
        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        // ตรวจสอบวันสิ้นสุด
        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบว่าแพคเกจหมดอายุแล้วหรือยัง
     */
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * ตรวจสอบว่ามีรายการในแพคเกจหรือไม่
     */
    public function hasItems(): bool
    {
        return $this->items()->count() > 0;
    }

    //===========================================
    // Methods - Price Calculation
    //===========================================

    /**
     * คำนวณราคารวมจากรายการในแพคเกจ
     *
     * @return float
     */
    public function calculateTotalFromItems(): float
    {
        return $this->items()->sum('price');
    }

    /**
     * คำนวณ final_price ใหม่
     */
    public function recalculateFinalPrice(): void
    {
        $discount = ($this->total_price * $this->discount_percentage) / 100;
        $this->final_price = $this->total_price - $discount;
        $this->save();
    }

    //===========================================
    // Methods - Statistics
    //===========================================

    /**
     * เพิ่มจำนวนการซื้อ
     */
    public function incrementPurchaseCount(): void
    {
        $this->increment('purchase_count');
    }

    /**
     * อัพเดทคะแนนเฉลี่ยจากรีวิว
     */
    public function updateAverageRating(): void
    {
        // อัพเดทจาก reviews ของ services ในแพคเกจ
        $serviceIds = $this->items()
            ->where('item_type', 'service')
            ->pluck('item_id');

        if ($serviceIds->isEmpty()) {
            return;
        }

        $avgRating = ServiceReview::whereHas('booking', function ($query) use ($serviceIds) {
            $query->whereIn('service_id', $serviceIds);
        })
            ->where('is_visible', true)
            ->avg('rating');

        $this->average_rating = $avgRating ?? 0;
        $this->save();
    }

    //===========================================
    // Methods - URL & Display
    //===========================================

    /**
     * URL ของแพคเกจ
     */
    public function getUrl(): string
    {
        return route('services.packages.show', $this->slug);
    }

    /**
     * แสดงสถานะโปรโมชั่น
     */
    public function getPromotionStatusText(): string
    {
        if (!$this->valid_from && !$this->valid_until) {
            return 'โปรโมชั่นถาวร';
        }

        $now = now();

        if ($this->valid_from && $this->valid_from->isFuture()) {
            return 'เริ่ม ' . $this->valid_from->format('d/m/Y');
        }

        if ($this->valid_until) {
            if ($this->valid_until->isPast()) {
                return 'หมดอายุแล้ว';
            }
            $daysLeft = $now->diffInDays($this->valid_until);
            if ($daysLeft <= 7) {
                return "เหลืออีก {$daysLeft} วัน";
            }
            return 'ถึง ' . $this->valid_until->format('d/m/Y');
        }

        return 'พร้อมใช้งาน';
    }
}
