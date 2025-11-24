<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ServicePricingRule Model - กฎคำนวณราคาตามระยะทาง
 *
 * กำหนดกฎการคิดค่าเดินทางตามระยะทาง เช่น:
 * - 0-5 km: ค่าคงที่ 50฿
 * - 5-10 km: 50฿ + 10฿/km
 * - 10+ km: 100฿ + 15฿/km
 *
 * @property int $id
 * @property int|null $service_id บริการเฉพาะ (null = ทุกบริการ)
 * @property int $owner_id
 * @property string $name ชื่อกฎ
 * @property float $min_distance_km ระยะทางขั้นต่ำ
 * @property float|null $max_distance_km ระยะทางสูงสุด (null = ไม่จำกัด)
 * @property float|null $price_per_km ราคาต่อ km
 * @property float $flat_fee ค่าบริการคงที่
 * @property bool $is_active เปิดใช้งาน
 * @property int $priority ลำดับความสำคัญ
 * @property string|null $description คำอธิบาย
 */
class ServicePricingRule extends Model
{
    use HasFactory;

    protected $table = 'service_pricing_rules';

    protected $fillable = [
        'service_id',
        'owner_id',
        'name',
        'min_distance_km',
        'max_distance_km',
        'price_per_km',
        'flat_fee',
        'is_active',
        'priority',
        'description',
    ];

    protected $casts = [
        'min_distance_km' => 'decimal:2',
        'max_distance_km' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'flat_fee' => 'decimal:2',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    //===========================================
    // Relationships
    //===========================================

    /**
     * บริการที่ใช้กฎนี้ (null = ทุกบริการ)
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * เจ้าของระบบ
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    //===========================================
    // Scopes
    //===========================================

    /**
     * Scope: เฉพาะกฎที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตาม priority (เลขน้อย = ตรวจสอบก่อน)
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority')->orderBy('min_distance_km');
    }

    /**
     * Scope: กฎสำหรับบริการเฉพาะ
     */
    public function scopeForService($query, int $serviceId)
    {
        return $query->where(function ($q) use ($serviceId) {
            $q->where('service_id', $serviceId)
                ->orWhereNull('service_id');
        });
    }

    /**
     * Scope: กฎที่ครอบคลุมระยะทางที่กำหนด
     */
    public function scopeCoveringDistance($query, float $distanceKm)
    {
        return $query->where('min_distance_km', '<=', $distanceKm)
            ->where(function ($q) use ($distanceKm) {
                $q->whereNull('max_distance_km')
                    ->orWhere('max_distance_km', '>=', $distanceKm);
            });
    }

    //===========================================
    // Methods - Rule Matching
    //===========================================

    /**
     * ตรวจสอบว่ากฎนี้ใช้กับระยะทางที่กำหนดหรือไม่
     */
    public function matchesDistance(float $distanceKm): bool
    {
        // ต้องมากกว่าหรือเท่ากับ min_distance
        if ($distanceKm < $this->min_distance_km) {
            return false;
        }

        // ถ้ามี max_distance ต้องน้อยกว่าหรือเท่ากับ
        if ($this->max_distance_km !== null && $distanceKm > $this->max_distance_km) {
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบว่ากฎนี้ใช้กับบริการที่กำหนดหรือไม่
     */
    public function appliesTo(?int $serviceId): bool
    {
        // null = ใช้กับทุกบริการ
        if ($this->service_id === null) {
            return true;
        }

        return $this->service_id === $serviceId;
    }

    //===========================================
    // Methods - Price Calculation
    //===========================================

    /**
     * คำนวณราคาตามระยะทาง
     *
     * ตัวอย่าง:
     * - กฎ: min=0, max=5, flat_fee=50, price_per_km=0
     *   ระยะ 3 km → 50฿
     *
     * - กฎ: min=5, max=10, flat_fee=50, price_per_km=10
     *   ระยะ 7 km → 50 + (7 × 10) = 120฿
     *
     * @param float $distanceKm
     * @return float
     */
    public function calculatePrice(float $distanceKm): float
    {
        if (!$this->matchesDistance($distanceKm)) {
            return 0;
        }

        $price = $this->flat_fee;

        // เพิ่มค่าระยะทาง
        if ($this->price_per_km > 0) {
            $price += ($distanceKm * $this->price_per_km);
        }

        return round($price, 2);
    }

    /**
     * หากฎที่เหมาะสมสำหรับการคำนวณ
     *
     * @param float $distanceKm
     * @param int|null $serviceId
     * @return static|null
     */
    public static function findMatchingRule(float $distanceKm, ?int $serviceId = null): ?self
    {
        return static::active()
            ->when($serviceId, function ($query) use ($serviceId) {
                $query->forService($serviceId);
            })
            ->coveringDistance($distanceKm)
            ->ordered()
            ->first();
    }

    /**
     * คำนวณราคาโดยใช้กฎที่เหมาะสม
     *
     * @param float $distanceKm
     * @param int|null $serviceId
     * @return float
     */
    public static function calculateDistancePrice(float $distanceKm, ?int $serviceId = null): float
    {
        $rule = static::findMatchingRule($distanceKm, $serviceId);

        if (!$rule) {
            // ถ้าไม่มีกฎที่เหมาะสม ใช้ default rate
            return $distanceKm * config('services.default_price_per_km', 10);
        }

        return $rule->calculatePrice($distanceKm);
    }

    //===========================================
    // Methods - Display
    //===========================================

    /**
     * แสดงช่วงระยะทางเป็นข้อความ
     */
    public function getDistanceRangeText(): string
    {
        if ($this->max_distance_km === null) {
            return "{$this->min_distance_km}+ km";
        }

        if ($this->min_distance_km == 0) {
            return "0-{$this->max_distance_km} km";
        }

        return "{$this->min_distance_km}-{$this->max_distance_km} km";
    }

    /**
     * แสดงสูตรการคำนวณ
     */
    public function getFormulaText(): string
    {
        $parts = [];

        if ($this->flat_fee > 0) {
            $parts[] = number_format($this->flat_fee, 2) . '฿';
        }

        if ($this->price_per_km > 0) {
            $perKm = number_format($this->price_per_km, 2);
            $parts[] = "{$perKm}฿/km";
        }

        if (empty($parts)) {
            return 'ฟรี';
        }

        return implode(' + ', $parts);
    }

    /**
     * ตัวอย่างการคำนวณ
     */
    public function getExampleCalculation(): string
    {
        $sampleDistance = ($this->min_distance_km + ($this->max_distance_km ?? $this->min_distance_km + 5)) / 2;
        $price = $this->calculatePrice($sampleDistance);

        return "ระยะ " . number_format($sampleDistance, 1) . " km = " . number_format($price, 2) . "฿";
    }
}
