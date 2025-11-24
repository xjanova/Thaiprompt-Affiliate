<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ServiceBookingLocation Model - ตำแหน่งที่อยู่การจอง
 *
 * เก็บตำแหน่ง GPS และที่อยู่สำหรับให้บริการ
 * และตำแหน่งปัจจุบันของ provider
 *
 * @property int $id
 * @property int $booking_id
 * @property string $type ประเภท: service_location, provider_location
 * @property float $latitude ละติจูด
 * @property float $longitude ลองจิจูด
 * @property string $address ที่อยู่เต็ม
 * @property string|null $google_place_id Google Place ID
 * @property string|null $building_name ชื่ออาคาร
 * @property string|null $floor_number ชั้น
 * @property string|null $room_number เลขที่ห้อง
 * @property string|null $landmark จุดสังเกต
 * @property string|null $contact_name ชื่อผู้ติดต่อ
 * @property string|null $contact_phone เบอร์โทร
 */
class ServiceBookingLocation extends Model
{
    use HasFactory;

    protected $table = 'service_booking_locations';

    protected $fillable = [
        'booking_id',
        'type',
        'latitude',
        'longitude',
        'address',
        'google_place_id',
        'building_name',
        'floor_number',
        'room_number',
        'landmark',
        'contact_name',
        'contact_phone',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

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

    //===========================================
    // Scopes
    //===========================================

    /**
     * Scope: ตำแหน่งให้บริการ (ที่ลูกค้า)
     */
    public function scopeServiceLocation($query)
    {
        return $query->where('type', 'service_location');
    }

    /**
     * Scope: ตำแหน่ง provider
     */
    public function scopeProviderLocation($query)
    {
        return $query->where('type', 'provider_location');
    }

    //===========================================
    // Methods - Location
    //===========================================

    /**
     * คำนวณระยะทางไปยังตำแหน่งอื่น (km)
     *
     * @param float $destinationLat
     * @param float $destinationLng
     * @return float
     */
    public function calculateDistanceTo(float $destinationLat, float $destinationLng): float
    {
        // ใช้ Haversine formula
        $earthRadius = 6371; // km

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($destinationLat);
        $lonTo = deg2rad($destinationLng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 2);
    }

    /**
     * แสดงพิกัดเป็น string
     */
    public function getCoordinatesTextAttribute(): string
    {
        return "{$this->latitude}, {$this->longitude}";
    }

    /**
     * แสดงที่อยู่แบบเต็ม
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->room_number ? "ห้อง {$this->room_number}" : null,
            $this->floor_number ? "ชั้น {$this->floor_number}" : null,
            $this->building_name,
            $this->address,
        ]);

        $fullAddress = implode(', ', $parts);

        if ($this->landmark) {
            $fullAddress .= " (ใกล้ {$this->landmark})";
        }

        return $fullAddress;
    }

    /**
     * Google Maps URL
     */
    public function getGoogleMapsUrlAttribute(): string
    {
        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    /**
     * ประเภทเป็นภาษาไทย
     */
    public function getTypeTextAttribute(): string
    {
        return match ($this->type) {
            'service_location' => 'ตำแหน่งให้บริการ',
            'provider_location' => 'ตำแหน่งผู้ให้บริการ',
            default => 'ไม่ระบุ',
        };
    }
}
