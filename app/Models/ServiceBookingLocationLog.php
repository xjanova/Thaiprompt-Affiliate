<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ServiceBookingLocationLog Model
 *
 * บันทึกประวัติตำแหน่ง GPS ระหว่างการให้บริการ
 * ใช้สำหรับ:
 * - ติดตามตำแหน่งแบบ real-time
 * - หลักฐานในกรณีเกิดข้อพิพาท
 * - ตรวจจับพฤติกรรมผิดปกติ (GPS spoofing)
 *
 * @property int $id
 * @property int $service_booking_id
 * @property int|null $user_id
 * @property int|null $provider_id
 * @property string $actor_type user|provider
 * @property float $latitude
 * @property float $longitude
 * @property float|null $accuracy ความแม่นยำเป็นเมตร
 * @property float|null $speed ความเร็ว km/h
 * @property float|null $heading ทิศทาง 0-360
 * @property float|null $altitude ความสูงจากระดับน้ำทะเล
 * @property string $source gps|network|passive|fused|manual
 * @property int|null $battery_level
 * @property bool|null $is_charging
 * @property string $app_state foreground|background|terminated
 * @property \Carbon\Carbon $recorded_at
 */
class ServiceBookingLocationLog extends Model
{
    protected $fillable = [
        'service_booking_id',
        'user_id',
        'provider_id',
        'actor_type',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'heading',
        'altitude',
        'source',
        'battery_level',
        'is_charging',
        'app_state',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'speed' => 'float',
        'heading' => 'float',
        'altitude' => 'float',
        'battery_level' => 'integer',
        'is_charging' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ ServiceBooking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class, 'service_booking_id');
    }

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Provider
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    /**
     * Scope: กรองตาม actor type
     */
    public function scopeByActor($query, string $type)
    {
        return $query->where('actor_type', $type);
    }

    /**
     * Scope: กรองตามช่วงเวลา
     */
    public function scopeBetween($query, $start, $end)
    {
        return $query->whereBetween('recorded_at', [$start, $end]);
    }

    /**
     * คำนวณระยะทางจากจุดก่อนหน้า
     */
    public function getDistanceFromPrevious(): ?float
    {
        $previous = static::where('service_booking_id', $this->service_booking_id)
            ->where('actor_type', $this->actor_type)
            ->where('recorded_at', '<', $this->recorded_at)
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (! $previous) {
            return null;
        }

        return $this->calculateDistance(
            $previous->latitude,
            $previous->longitude,
            $this->latitude,
            $this->longitude
        );
    }

    /**
     * คำนวณระยะทางระหว่าง 2 จุด (Haversine formula)
     */
    protected function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // km

        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * ตรวจจับ GPS Spoofing
     * - ความเร็วเกินจริง (> 200 km/h สำหรับ on-ground service)
     * - กระโดดตำแหน่ง
     */
    public function detectSpoofing(): array
    {
        $flags = [];

        // ตรวจสอบความเร็วเกินจริง
        if ($this->speed && $this->speed > 200) {
            $flags[] = 'impossible_speed';
        }

        // ตรวจสอบการกระโดดตำแหน่ง
        $distance = $this->getDistanceFromPrevious();
        if ($distance !== null) {
            $previous = static::where('service_booking_id', $this->service_booking_id)
                ->where('actor_type', $this->actor_type)
                ->where('recorded_at', '<', $this->recorded_at)
                ->orderBy('recorded_at', 'desc')
                ->first();

            if ($previous) {
                $timeDiff = $this->recorded_at->diffInSeconds($previous->recorded_at);
                if ($timeDiff > 0) {
                    $impliedSpeed = ($distance / $timeDiff) * 3600; // km/h
                    if ($impliedSpeed > 300) {
                        $flags[] = 'location_jump';
                    }
                }
            }
        }

        // ตรวจสอบ accuracy ต่ำเกินไป (อาจปลอม)
        if ($this->accuracy && $this->accuracy < 1) {
            $flags[] = 'suspicious_accuracy';
        }

        return $flags;
    }
}
