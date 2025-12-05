<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RiderLocation Model
 *
 * เก็บข้อมูลตำแหน่ง GPS ของไรเดอร์
 * เก็บเฉพาะตอนที่ไรเดอร์รับงานเท่านั้น
 *
 * @property int $id
 * @property int $rider_id
 * @property int|null $job_id
 * @property decimal $latitude
 * @property decimal $longitude
 * @property decimal|null $speed
 */
class RiderLocation extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'rider_locations';

    /**
     * Fields ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'rider_id',
        'job_id',
        'latitude',
        'longitude',
        'altitude',
        'accuracy',
        'speed',
        'heading',
        'address',
        'activity_type',
        'battery_level',
        'is_charging',
        'device_model',
        'os_version',
        'recorded_at',
    ];

    /**
     * Casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'altitude' => 'decimal:2',
        'accuracy' => 'decimal:2',
        'speed' => 'decimal:2',
        'heading' => 'decimal:2',
        'is_charging' => 'boolean',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =====================================================
    // Relationships
    // =====================================================

    /**
     * ความสัมพันธ์กับ Rider
     *
     * @return BelongsTo
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    /**
     * ความสัมพันธ์กับ Job
     *
     * @return BelongsTo
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(RiderJob::class, 'job_id');
    }

    // =====================================================
    // Scopes
    // =====================================================

    /**
     * Scope สำหรับตำแหน่งล่าสุด
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('recorded_at', 'desc');
    }

    /**
     * Scope สำหรับตำแหน่งในช่วงเวลา
     */
    public function scopeInTimeRange($query, $from, $to)
    {
        return $query->whereBetween('recorded_at', [$from, $to]);
    }

    /**
     * Scope สำหรับตำแหน่งของงาน
     */
    public function scopeForJob($query, $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    // =====================================================
    // Accessors
    // =====================================================

    /**
     * ความเร็วเป็น กม./ชม.
     *
     * @return float|null
     */
    public function getSpeedKphAttribute(): ?float
    {
        return $this->speed;
    }

    /**
     * ชื่อกิจกรรมภาษาไทย
     *
     * @return string
     */
    public function getActivityTypeTextAttribute(): string
    {
        return match($this->activity_type) {
            'still' => 'หยุดนิ่ง',
            'walking' => 'เดิน',
            'running' => 'วิ่ง',
            'cycling' => 'ปั่นจักรยาน',
            'driving' => 'ขับรถ',
            'unknown' => 'ไม่ทราบ',
            default => 'ไม่ระบุ',
        };
    }

    // =====================================================
    // Static Methods
    // =====================================================

    /**
     * บันทึกตำแหน่งใหม่
     *
     * @param int $riderId
     * @param array $locationData
     * @param int|null $jobId
     * @return self
     */
    public static function recordLocation(int $riderId, array $locationData, ?int $jobId = null): self
    {
        return self::create([
            'rider_id' => $riderId,
            'job_id' => $jobId,
            'latitude' => $locationData['latitude'],
            'longitude' => $locationData['longitude'],
            'altitude' => $locationData['altitude'] ?? null,
            'accuracy' => $locationData['accuracy'] ?? null,
            'speed' => $locationData['speed'] ?? null,
            'heading' => $locationData['heading'] ?? null,
            'address' => $locationData['address'] ?? null,
            'activity_type' => $locationData['activity_type'] ?? null,
            'battery_level' => $locationData['battery_level'] ?? null,
            'is_charging' => $locationData['is_charging'] ?? null,
            'device_model' => $locationData['device_model'] ?? null,
            'os_version' => $locationData['os_version'] ?? null,
            'recorded_at' => $locationData['recorded_at'] ?? now(),
        ]);
    }

    /**
     * คำนวณระยะทางระหว่างสองจุด (Haversine formula)
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float ระยะทางเป็น กม.
     */
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // กม.

        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
