<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * ServiceArea Model - พื้นที่ให้บริการ
 *
 * กำหนดพื้นที่ที่สามารถให้บริการได้ เช่น กรุงเทพมหานคร, นนทบุรี
 *
 * @property int $id
 * @property int $owner_id
 * @property string $name ชื่อพื้นที่
 * @property string $province จังหวัด
 * @property string|null $district อำเภอ/เขต
 * @property string|null $subdistrict ตำบล/แขวง
 * @property array|null $coordinates พิกัดขอบเขต (polygon)
 * @property float|null $center_latitude ละติจูดจุดกึ่งกลาง
 * @property float|null $center_longitude ลองจิจูดจุดกึ่งกลาง
 * @property bool $is_active เปิดใช้งาน
 * @property int $sort_order ลำดับการแสดงผล
 * @property int $provider_count จำนวน provider
 * @property int $booking_count จำนวนการจอง
 */
class ServiceArea extends Model
{
    use HasFactory;

    protected $table = 'service_areas';

    protected $fillable = [
        'owner_id',
        'name',
        'province',
        'district',
        'subdistrict',
        'coordinates',
        'center_latitude',
        'center_longitude',
        'is_active',
        'sort_order',
        'provider_count',
        'booking_count',
    ];

    protected $casts = [
        'coordinates' => 'array',
        'center_latitude' => 'decimal:8',
        'center_longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'provider_count' => 'integer',
        'booking_count' => 'integer',
    ];

    //===========================================
    // Relationships
    //===========================================

    /**
     * เจ้าของระบบ
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * ผู้ให้บริการในพื้นที่นี้
     */
    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(
            ServiceProvider::class,
            'service_provider_areas',
            'area_id',
            'provider_id'
        )->withTimestamps();
    }

    //===========================================
    // Scopes
    //===========================================

    /**
     * Scope: เฉพาะพื้นที่ที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตามลำดับ
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope: ค้นหาพื้นที่
     */
    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('province', 'like', "%{$keyword}%")
                ->orWhere('district', 'like', "%{$keyword}%")
                ->orWhere('subdistrict', 'like', "%{$keyword}%");
        });
    }

    /**
     * Scope: กรองตามจังหวัด
     */
    public function scopeProvince($query, string $province)
    {
        return $query->where('province', $province);
    }

    //===========================================
    // Methods - Location
    //===========================================

    /**
     * ตรวจสอบว่ามีศูนย์กลางพื้นที่หรือไม่
     */
    public function hasCenter(): bool
    {
        return !is_null($this->center_latitude) && !is_null($this->center_longitude);
    }

    /**
     * ตรวจสอบว่ามีขอบเขตพื้นที่หรือไม่
     */
    public function hasCoordinates(): bool
    {
        return !empty($this->coordinates) && is_array($this->coordinates);
    }

    /**
     * คำนวณระยะทางจากจุดกึ่งกลางไปยังตำแหน่งที่กำหนด (km)
     *
     * @param float $latitude
     * @param float $longitude
     * @return float
     */
    public function calculateDistanceToCenter(float $latitude, float $longitude): float
    {
        if (!$this->hasCenter()) {
            return 0;
        }

        // ใช้ Haversine formula
        $earthRadius = 6371; // km

        $latFrom = deg2rad($this->center_latitude);
        $lonFrom = deg2rad($this->center_longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 2);
    }

    /**
     * ตรวจสอบว่าจุดที่กำหนดอยู่ในพื้นที่นี้หรือไม่
     *
     * ใช้ Point in Polygon algorithm
     *
     * @param float $latitude
     * @param float $longitude
     * @return bool
     */
    public function containsPoint(float $latitude, float $longitude): bool
    {
        if (!$this->hasCoordinates()) {
            return false;
        }

        $polygon = $this->coordinates;
        $vertices = count($polygon);
        $inside = false;

        for ($i = 0, $j = $vertices - 1; $i < $vertices; $j = $i++) {
            $xi = $polygon[$i]['lat'];
            $yi = $polygon[$i]['lng'];
            $xj = $polygon[$j]['lat'];
            $yj = $polygon[$j]['lng'];

            $intersect = (($yi > $longitude) != ($yj > $longitude))
                && ($latitude < ($xj - $xi) * ($longitude - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    //===========================================
    // Methods - Statistics
    //===========================================

    /**
     * อัพเดทจำนวน provider ในพื้นที่
     */
    public function updateProviderCount(): void
    {
        $this->provider_count = $this->providers()->count();
        $this->save();
    }

    /**
     * เพิ่มจำนวนการจอง
     */
    public function incrementBookingCount(): void
    {
        $this->increment('booking_count');
    }

    //===========================================
    // Methods - Display
    //===========================================

    /**
     * ชื่อเต็มของพื้นที่
     */
    public function getFullName(): string
    {
        $parts = array_filter([
            $this->subdistrict,
            $this->district,
            $this->province,
        ]);

        return implode(', ', $parts);
    }

    /**
     * แสดงข้อมูลศูนย์กลางเป็น string
     */
    public function getCenterCoordinatesText(): string
    {
        if (!$this->hasCenter()) {
            return 'ไม่ระบุ';
        }

        return "{$this->center_latitude}, {$this->center_longitude}";
    }
}
