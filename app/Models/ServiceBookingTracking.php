<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ServiceBookingTracking Model - ติดตามสถานะ Real-time
 *
 * เก็บประวัติการอัพเดทตำแหน่งและสถานะของ provider
 * ใช้สำหรับแสดง real-time tracking บนแผนที่
 *
 * @property int $id
 * @property int $booking_id
 * @property int $provider_id
 * @property string $status สถานะปัจจุบัน
 * @property float|null $latitude ละติจูด
 * @property float|null $longitude ลองจิจูด
 * @property float|null $distance_to_customer_km ระยะทางถึงลูกค้า
 * @property int|null $estimated_arrival_minutes เวลาถึงโดยประมาณ
 * @property string|null $notes หมายเหตุ
 * @property float|null $speed_kmh ความเร็ว
 * @property string|null $battery_level แบตเตอรี่
 * @property \Carbon\Carbon $created_at
 */
class ServiceBookingTracking extends Model
{
    use HasFactory;

    protected $table = 'service_booking_tracking';

    public $timestamps = false; // ใช้แค่ created_at

    protected $fillable = [
        'booking_id',
        'provider_id',
        'status',
        'latitude',
        'longitude',
        'distance_to_customer_km',
        'estimated_arrival_minutes',
        'notes',
        'speed_kmh',
        'battery_level',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'distance_to_customer_km' => 'decimal:2',
        'estimated_arrival_minutes' => 'integer',
        'speed_kmh' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    //===========================================
    // Lifecycle Hooks
    //===========================================

    protected static function boot()
    {
        parent::boot();

        // ตั้งค่า created_at อัตโนมัติ
        static::creating(function ($tracking) {
            $tracking->created_at = now();
        });
    }

    //===========================================
    // Relationships
    //===========================================

    /**
     * การจอง
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class, 'booking_id');
    }

    /**
     * ผู้ให้บริการ
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    //===========================================
    // Scopes
    //===========================================

    /**
     * Scope: เรียงตามเวลาล่าสุด
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope: เฉพาะที่มีตำแหน่ง GPS
     */
    public function scopeWithLocation($query)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }

    //===========================================
    // Methods
    //===========================================

    /**
     * สร้าง tracking log ใหม่
     *
     * @param ServiceBooking $booking
     * @param ServiceProvider $provider
     * @param array $data
     * @return static
     */
    public static function log(ServiceBooking $booking, ServiceProvider $provider, array $data): self
    {
        return static::create([
            'booking_id' => $booking->id,
            'provider_id' => $provider->id,
            'status' => $data['status'] ?? $booking->status,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'distance_to_customer_km' => $data['distance_to_customer_km'] ?? null,
            'estimated_arrival_minutes' => $data['estimated_arrival_minutes'] ?? null,
            'notes' => $data['notes'] ?? null,
            'speed_kmh' => $data['speed_kmh'] ?? null,
            'battery_level' => $data['battery_level'] ?? null,
        ]);
    }

    /**
     * แสดงพิกัดเป็น string
     */
    public function getCoordinatesTextAttribute(): string
    {
        if (!$this->latitude || !$this->longitude) {
            return 'ไม่มีข้อมูลตำแหน่ง';
        }

        return "{$this->latitude}, {$this->longitude}";
    }

    /**
     * แสดงเวลาที่ผ่านมา
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * สถานะเป็นภาษาไทย
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'provider_on_way' => 'กำลังเดินทาง',
            'in_progress' => 'กำลังให้บริการ',
            'completed' => 'เสร็จสิ้น',
            default => $this->status,
        };
    }

    /**
     * ข้อความสถานะแบบเต็ม
     */
    public function getFullStatusMessageAttribute(): string
    {
        $message = $this->status_text;

        if ($this->distance_to_customer_km) {
            $message .= " | ห่างจากลูกค้า " . number_format($this->distance_to_customer_km, 1) . " km";
        }

        if ($this->estimated_arrival_minutes) {
            $message .= " | ถึงใน {$this->estimated_arrival_minutes} นาที";
        }

        if ($this->speed_kmh) {
            $message .= " | ความเร็ว " . number_format($this->speed_kmh, 0) . " km/h";
        }

        return $message;
    }
}
