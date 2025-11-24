<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Service Model - บริการแต่ละประเภท
 *
 * เก็บรายละเอียดบริการ เช่น นวดไทย 1 ชม., นวดน้ำมัน 2 ชม.
 *
 * @property int $id
 * @property int $category_id
 * @property int $owner_id
 * @property string $name ชื่อบริการ
 * @property string $slug
 * @property string|null $description
 * @property string|null $short_description
 * @property array|null $images
 * @property float $base_price
 * @property int $duration_minutes
 * @property array|null $options
 * @property bool $requires_location
 * @property bool $is_active
 * @property bool $is_featured
 * @property int $min_booking_hours
 * @property int|null $max_bookings_per_day
 * @property int $cancellation_hours
 * @property float $commission_rate
 * @property int $view_count
 * @property int $booking_count
 * @property float $average_rating
 */
class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'services';

    protected $fillable = [
        'category_id',
        'owner_id',
        'name',
        'slug',
        'description',
        'short_description',
        'images',
        'base_price',
        'duration_minutes',
        'options',
        'requires_location',
        'is_active',
        'is_featured',
        'min_booking_hours',
        'max_bookings_per_day',
        'cancellation_hours',
        'commission_rate',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'view_count',
        'booking_count',
        'average_rating',
    ];

    protected $casts = [
        'images' => 'array',
        'options' => 'array',
        'base_price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'requires_location' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'duration_minutes' => 'integer',
        'min_booking_hours' => 'integer',
        'max_bookings_per_day' => 'integer',
        'cancellation_hours' => 'integer',
        'view_count' => 'integer',
        'booking_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    //===========================================
    // Relationships
    //===========================================

    /**
     * หมวดหมู่ของบริการ
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    /**
     * เจ้าของบริการ
     *
     * @return BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * การจองทั้งหมด
     *
     * @return HasMany
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class, 'service_id');
    }

    /**
     * รีวิวทั้งหมด
     *
     * @return HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ServiceReview::class, 'service_id');
    }

    /**
     * Pricing rules สำหรับบริการนี้
     *
     * @return HasMany
     */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(ServicePricingRule::class, 'service_id')
            ->orderBy('priority');
    }

    //===========================================
    // Scopes
    //===========================================

    /**
     * Scope: เฉพาะบริการที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เฉพาะบริการแนะนำ
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: เรียงตามความนิยม
     */
    public function scopePopular($query)
    {
        return $query->orderByDesc('booking_count');
    }

    /**
     * Scope: ค้นหาบริการ
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%")
                ->orWhere('short_description', 'like', "%{$keyword}%");
        });
    }

    //===========================================
    // Accessors & Mutators
    //===========================================

    /**
     * ได้รูปภาพหลัก
     *
     * @return string|null
     */
    public function getMainImageAttribute(): ?string
    {
        if (empty($this->images)) {
            return null;
        }

        return $this->images[0] ?? null;
    }

    /**
     * ได้ระยะเวลาในรูปแบบที่อ่านง่าย
     *
     * @return string
     */
    public function getDurationTextAttribute(): string
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours} ชั่วโมง {$minutes} นาที";
        } elseif ($hours > 0) {
            return "{$hours} ชั่วโมง";
        } else {
            return "{$minutes} นาที";
        }
    }

    //===========================================
    // Methods
    //===========================================

    /**
     * เพิ่มจำนวนการดู
     *
     * @return void
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * เพิ่มจำนวนการจอง
     *
     * @return void
     */
    public function incrementBookingCount(): void
    {
        $this->increment('booking_count');
    }

    /**
     * อัพเดทคะแนนเฉลี่ย
     *
     * @return void
     */
    public function updateAverageRating(): void
    {
        $this->average_rating = $this->reviews()
            ->where('is_visible', true)
            ->avg('rating') ?? 0;
        $this->save();
    }

    /**
     * คำนวณราคารวมค่าเดินทาง
     *
     * @param float $distanceKm ระยะทาง (กิโลเมตร)
     * @return float
     */
    public function calculateTotalPrice(float $distanceKm): float
    {
        $distancePrice = $this->calculateDistancePrice($distanceKm);
        return $this->base_price + $distancePrice;
    }

    /**
     * คำนวณค่าเดินทางตามระยะทาง
     *
     * @param float $distanceKm
     * @return float
     */
    public function calculateDistancePrice(float $distanceKm): float
    {
        // หา pricing rule ที่เหมาะสม
        $rule = $this->pricingRules()
            ->where('is_active', true)
            ->where('min_distance_km', '<=', $distanceKm)
            ->where(function ($q) use ($distanceKm) {
                $q->whereNull('max_distance_km')
                    ->orWhere('max_distance_km', '>=', $distanceKm);
            })
            ->orderBy('priority')
            ->first();

        if (!$rule) {
            // ถ้าไม่มี rule ใช้ค่า default
            return 0;
        }

        $price = $rule->flat_fee;

        if ($rule->price_per_km) {
            $price += $distanceKm * $rule->price_per_km;
        }

        return $price;
    }

    /**
     * ได้ URL บริการ
     *
     * @return string
     */
    public function getUrl(): string
    {
        return route('services.show', $this->slug);
    }

    /**
     * ตรวจสอบว่าสามารถจองได้หรือไม่
     *
     * @return bool
     */
    public function isBookable(): bool
    {
        return $this->is_active;
    }
}
