<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Province Model
 *
 * จัดการข้อมูลจังหวัดของประเทศไทย พร้อมพิกัด GPS
 *
 * @property int $id
 * @property string $name_th ชื่อจังหวัดภาษาไทย
 * @property string $name_en ชื่อจังหวัดภาษาอังกฤษ
 * @property string $code รหัสจังหวัด (2 หลัก)
 * @property string $region ภูมิภาค
 * @property decimal $latitude ละติจูด
 * @property decimal $longitude ลองจิจูด
 * @property bool $is_active สถานะการใช้งาน
 * @property int $sort_order ลำดับการแสดงผล
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Province extends Model
{
    use HasFactory;

    /**
     * ตารางที่เชื่อมโยงกับโมเดล
     *
     * @var string
     */
    protected $table = 'provinces';

    /**
     * ฟิลด์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'name_th',
        'name_en',
        'code',
        'region',
        'latitude',
        'longitude',
        'is_active',
        'sort_order',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * ค่าเริ่มต้นสำหรับฟิลด์
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    /**
     * ภูมิภาคที่รองรับ
     */
    public const REGIONS = [
        'central' => 'ภาคกลาง',
        'north' => 'ภาคเหนือ',
        'northeast' => 'ภาคตะวันออกเฉียงเหนือ',
        'east' => 'ภาคตะวันออก',
        'west' => 'ภาคตะวันตก',
        'south' => 'ภาคใต้',
    ];

    // ===============================
    // Relationships
    // ===============================

    /**
     * โรงแรมในจังหวัดนี้
     */
    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class, 'province_id');
    }

    // ===============================
    // Scopes
    // ===============================

    /**
     * กรองเฉพาะจังหวัดที่ใช้งานได้
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * กรองตามภูมิภาค
     */
    public function scopeByRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    /**
     * เรียงตามลำดับ
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')
            ->orderBy('name_th', 'asc');
    }

    /**
     * ค้นหาตามชื่อ (ไทย/อังกฤษ)
     */
    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('name_th', 'LIKE', "%{$keyword}%")
                ->orWhere('name_en', 'LIKE', "%{$keyword}%");
        });
    }

    // ===============================
    // Accessors
    // ===============================

    /**
     * ชื่อจังหวัดแบบเต็ม (ไทย + อังกฤษ)
     */
    public function getFullNameAttribute(): string
    {
        return $this->name_th.' ('.$this->name_en.')';
    }

    /**
     * ชื่อภูมิภาคภาษาไทย
     */
    public function getRegionNameAttribute(): string
    {
        return self::REGIONS[$this->region] ?? $this->region;
    }

    /**
     * จำนวนโรงแรมในจังหวัด
     */
    public function getHotelCountAttribute(): int
    {
        return $this->hotels()->active()->count();
    }

    // ===============================
    // Methods
    // ===============================

    /**
     * คำนวณระยะทางจากพิกัดที่ระบุ (กิโลเมตร)
     *
     * @param  float  $latitude  ละติจูด
     * @param  float  $longitude  ลองจิจูด
     * @return float ระยะทางเป็นกิโลเมตร
     */
    public function distanceFrom(float $latitude, float $longitude): float
    {
        $earthRadius = 6371; // รัศมีโลกเป็นกิโลเมตร

        $latFrom = deg2rad($latitude);
        $lonFrom = deg2rad($longitude);
        $latTo = deg2rad($this->latitude);
        $lonTo = deg2rad($this->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2 +
             cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;
        $c = 2 * asin(sqrt($a));

        return $earthRadius * $c;
    }

    /**
     * ดึงจังหวัดใกล้เคียงจากพิกัด GPS
     *
     * @param  float  $latitude  ละติจูด
     * @param  float  $longitude  ลองจิจูด
     * @param  int  $limit  จำนวนที่ต้องการ
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getNearbyProvinces(float $latitude, float $longitude, int $limit = 5)
    {
        return self::active()
            ->selectRaw('*,
                (6371 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )) AS distance', [$latitude, $longitude, $latitude])
            ->orderBy('distance', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * ดึงจังหวัดที่ใกล้ที่สุด
     *
     * @param  float  $latitude  ละติจูด
     * @param  float  $longitude  ลองจิจูด
     */
    public static function getNearestProvince(float $latitude, float $longitude): ?Province
    {
        return self::getNearbyProvinces($latitude, $longitude, 1)->first();
    }
}
