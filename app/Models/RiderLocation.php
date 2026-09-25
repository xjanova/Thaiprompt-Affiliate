<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RiderLocation Model
 *
 * เก็บข้อมูลตำแหน่ง GPS ของไรเดอร์
 * เก็บเฉพาะตอนที่ไรเดอร์รับงานเท่านั้น และลบอัตโนมัติเมื่อเก่ากว่า rider.location_retention_days
 * (คำสั่ง rider:purge-locations รันทุกวัน)
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
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    /**
     * ความสัมพันธ์กับ Job
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
     */
    public function getSpeedKphAttribute(): ?float
    {
        return $this->speed;
    }

    /**
     * ชื่อกิจกรรมภาษาไทย
     */
    public function getActivityTypeTextAttribute(): string
    {
        return match ($this->activity_type) {
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
     * บันทึกตำแหน่งใหม่ (ค่าที่ผิดรูปจากอุปกรณ์ถูกล้างเป็น null ก่อนบันทึก)
     */
    public static function recordLocation(int $riderId, array $locationData, ?int $jobId = null): self
    {
        $clean = self::sanitize($locationData);

        return self::create([
            'rider_id' => $riderId,
            'job_id' => $jobId,
            'latitude' => $clean['latitude'],
            'longitude' => $clean['longitude'],
            'altitude' => $clean['altitude'],
            'accuracy' => $clean['accuracy'],
            'speed' => $clean['speed'],
            'heading' => $clean['heading'],
            'address' => $locationData['address'] ?? null,
            'activity_type' => $clean['activity_type'],
            'battery_level' => $clean['battery_level'],
            'is_charging' => $clean['is_charging'],
            'device_model' => isset($locationData['device_model']) ? mb_substr((string) $locationData['device_model'], 0, 255) : null,
            'os_version' => isset($locationData['os_version']) ? mb_substr((string) $locationData['os_version'], 0, 255) : null,
            'recorded_at' => $locationData['recorded_at'] ?? now(),
        ]);
    }

    /**
     * ล้างค่าที่อุปกรณ์ส่งมาผิดรูป
     *
     * - heading ติดลบ (iOS ส่ง -1 เมื่อไม่รู้ทิศ) → null, เกิน 360 → วนกลับ
     * - speed / accuracy ติดลบ → null
     * - battery_level นอกช่วง 0-100 → null
     * - activity_type ที่ไม่อยู่ใน enum → null
     *
     * @return array{latitude: float, longitude: float, altitude: ?float, accuracy: ?float, speed: ?float, heading: ?float, battery_level: ?int, is_charging: ?bool, activity_type: ?string}
     */
    public static function sanitize(array $data): array
    {
        $num = fn ($v) => is_numeric($v) ? (float) $v : null;

        $heading = $num($data['heading'] ?? null);
        if ($heading !== null) {
            $heading = $heading < 0 ? null : fmod($heading, 360.0);
        }

        $speed = $num($data['speed'] ?? null);
        $accuracy = $num($data['accuracy'] ?? null);
        $battery = is_numeric($data['battery_level'] ?? null) ? (int) $data['battery_level'] : null;
        $activity = $data['activity_type'] ?? null;

        return [
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'altitude' => $num($data['altitude'] ?? null),
            'accuracy' => ($accuracy !== null && $accuracy >= 0) ? $accuracy : null,
            'speed' => ($speed !== null && $speed >= 0) ? $speed : null,
            'heading' => $heading,
            'battery_level' => ($battery !== null && $battery >= 0 && $battery <= 100) ? $battery : null,
            'is_charging' => isset($data['is_charging']) ? (bool) $data['is_charging'] : null,
            'activity_type' => in_array($activity, ['still', 'walking', 'running', 'cycling', 'driving', 'unknown'], true) ? $activity : null,
        ];
    }

    /**
     * ลบประวัติตำแหน่งเก่ากว่า N วัน (ทีละก้อน กันล็อกตารางนาน) — คืนจำนวนแถวที่ลบ
     *
     * นโยบายเก็บข้อมูล: rider.location_retention_days (ค่าเริ่มต้น 30 วัน) — PDPA / Data safety
     */
    public static function purgeOlderThan(int $days, int $chunk = 5000): int
    {
        $cutoff = now()->subDays(max(1, $days));
        $deleted = 0;

        do {
            $ids = self::where('recorded_at', '<', $cutoff)->limit($chunk)->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }

            $deleted += self::whereIn('id', $ids)->delete();
        } while ($ids->count() === $chunk);

        return $deleted;
    }

    /**
     * คำนวณระยะทางระหว่างสองจุด (Haversine formula)
     *
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
