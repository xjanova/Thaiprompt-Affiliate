<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Rider Model
 *
 * จัดการข้อมูลไรเดอร์ในระบบ
 *
 * @property int $id
 * @property int $user_id
 * @property string $full_name
 * @property string $phone
 * @property string|null $id_card_number
 * @property string|null $vehicle_type
 * @property string $status
 * @property string $availability
 * @property bool $gps_permission_granted
 * @property bool $camera_permission_granted
 * @property bool $microphone_permission_granted
 * @property decimal $last_latitude
 * @property decimal $last_longitude
 */
class Rider extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'riders';

    /**
     * Fields ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'id_card_number',
        'birth_date',
        'address',
        'province',
        'district',
        'vehicle_type',
        'vehicle_plate',
        'vehicle_brand',
        'vehicle_color',
        'id_card_image',
        'driver_license_image',
        'vehicle_registration_image',
        'profile_image',
        'status',
        'availability',
        'rejection_reason',
        'gps_permission_granted',
        'camera_permission_granted',
        'microphone_permission_granted',
        'notification_permission_granted',
        'permissions_granted_at',
        'total_jobs',
        'completed_jobs',
        'cancelled_jobs',
        'rating',
        'rating_count',
        'total_earnings',
        'last_latitude',
        'last_longitude',
        'last_location_update',
        'approved_at',
        'approved_by',
    ];

    /**
     * Casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'gps_permission_granted' => 'boolean',
        'camera_permission_granted' => 'boolean',
        'microphone_permission_granted' => 'boolean',
        'notification_permission_granted' => 'boolean',
        'permissions_granted_at' => 'datetime',
        'last_latitude' => 'decimal:8',
        'last_longitude' => 'decimal:8',
        'last_location_update' => 'datetime',
        'approved_at' => 'datetime',
        'rating' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'availability' => 'offline',
        'vehicle_type' => 'motorcycle',
        'gps_permission_granted' => false,
        'camera_permission_granted' => false,
        'microphone_permission_granted' => false,
        'notification_permission_granted' => false,
        'total_jobs' => 0,
        'completed_jobs' => 0,
        'cancelled_jobs' => 0,
        'rating' => 0,
        'rating_count' => 0,
        'total_earnings' => 0,
    ];

    // =====================================================
    // Relationships
    // =====================================================

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ User ที่อนุมัติ
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * งานทั้งหมดของไรเดอร์
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(RiderJob::class);
    }

    /**
     * ประวัติตำแหน่ง GPS
     */
    public function locations(): HasMany
    {
        return $this->hasMany(RiderLocation::class);
    }

    // =====================================================
    // Scopes
    // =====================================================

    /**
     * Scope สำหรับไรเดอร์ที่อนุมัติแล้ว
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope สำหรับไรเดอร์ที่ออนไลน์
     */
    public function scopeOnline($query)
    {
        return $query->where('availability', 'online');
    }

    /**
     * Scope สำหรับไรเดอร์ที่พร้อมรับงาน
     */
    public function scopeAvailable($query)
    {
        return $query->approved()
            ->where('availability', 'online');
    }

    /**
     * Scope สำหรับไรเดอร์ที่อยู่ใกล้พิกัดที่กำหนด
     */
    public function scopeNearby($query, $latitude, $longitude, $radiusKm = 5)
    {
        // Haversine formula สำหรับคำนวณระยะทาง
        return $query->selectRaw('*,
            (6371 * acos(cos(radians(?)) * cos(radians(last_latitude)) *
            cos(radians(last_longitude) - radians(?)) +
            sin(radians(?)) * sin(radians(last_latitude)))) AS distance_km',
            [$latitude, $longitude, $latitude])
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km');
    }

    // =====================================================
    // Accessors
    // =====================================================

    /**
     * ตรวจสอบว่าได้รับสิทธิ์ทั้งหมดหรือยัง
     */
    public function getHasAllPermissionsAttribute(): bool
    {
        return $this->gps_permission_granted
            && $this->camera_permission_granted
            && $this->microphone_permission_granted
            && $this->notification_permission_granted;
    }

    /**
     * ชื่อสถานะภาษาไทย
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอตรวจสอบ',
            'approved' => 'อนุมัติแล้ว',
            'rejected' => 'ถูกปฏิเสธ',
            'suspended' => 'ถูกระงับ',
            'inactive' => 'ไม่ใช้งาน',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * ชื่อสถานะพร้อมรับงานภาษาไทย
     */
    public function getAvailabilityTextAttribute(): string
    {
        return match ($this->availability) {
            'online' => 'พร้อมรับงาน',
            'offline' => 'ออฟไลน์',
            'busy' => 'กำลังส่งงาน',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * ชื่อยานพาหนะภาษาไทย
     */
    public function getVehicleTypeTextAttribute(): string
    {
        return match ($this->vehicle_type) {
            'motorcycle' => 'มอเตอร์ไซค์',
            'car' => 'รถยนต์',
            'bicycle' => 'จักรยาน',
            'walk' => 'เดินเท้า',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * อัตราการทำงานสำเร็จ
     */
    public function getCompletionRateAttribute(): float
    {
        if ($this->total_jobs == 0) {
            return 0;
        }

        return round(($this->completed_jobs / $this->total_jobs) * 100, 2);
    }

    // =====================================================
    // Methods
    // =====================================================

    /**
     * อัปเดตตำแหน่ง GPS
     */
    public function updateLocation(float $latitude, float $longitude): void
    {
        $this->update([
            'last_latitude' => $latitude,
            'last_longitude' => $longitude,
            'last_location_update' => now(),
        ]);
    }

    /**
     * ตั้งค่าสถานะออนไลน์
     */
    public function goOnline(): void
    {
        if ($this->status !== 'approved') {
            throw new \Exception('ไรเดอร์ยังไม่ได้รับการอนุมัติ');
        }

        $this->update(['availability' => 'online']);
    }

    /**
     * ตั้งค่าสถานะออฟไลน์
     */
    public function goOffline(): void
    {
        $this->update(['availability' => 'offline']);
    }

    /**
     * ตั้งค่าสถานะกำลังส่งงาน
     */
    public function setBusy(): void
    {
        $this->update(['availability' => 'busy']);
    }

    /**
     * บันทึกสิทธิ์ที่ได้รับ
     */
    public function grantPermissions(array $permissions): void
    {
        $updateData = [];

        if (isset($permissions['gps'])) {
            $updateData['gps_permission_granted'] = $permissions['gps'];
        }
        if (isset($permissions['camera'])) {
            $updateData['camera_permission_granted'] = $permissions['camera'];
        }
        if (isset($permissions['microphone'])) {
            $updateData['microphone_permission_granted'] = $permissions['microphone'];
        }
        if (isset($permissions['notification'])) {
            $updateData['notification_permission_granted'] = $permissions['notification'];
        }

        if (! empty($updateData)) {
            $updateData['permissions_granted_at'] = now();
            $this->update($updateData);
        }
    }
}
