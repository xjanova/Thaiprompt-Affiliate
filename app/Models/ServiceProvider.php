<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ServiceProvider Model - ผู้ให้บริการ/พนักงาน
 *
 * เก็บข้อมูลผู้ให้บริการพร้อมระบบแจ้งเตือนผ่าน LINE OA
 *
 * @property int $id
 * @property int $user_id
 * @property int $owner_id
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property string|null $avatar
 * @property string|null $bio
 * @property float $rating คะแนนเฉลี่ย 0-5
 * @property int $total_reviews
 * @property int $total_bookings
 * @property int $total_accepted จำนวนงานที่รับ
 * @property int $total_rejected จำนวนงานที่ปฏิเสธ
 * @property float $acceptance_rate อัตราการรับงาน %
 * @property int $average_response_minutes เวลาเฉลี่ยในการตอบกลับ
 * @property string $status available|busy|offline
 * @property bool $is_verified
 * @property bool $is_active
 * @property string|null $line_user_id LINE User ID
 * @property string|null $line_notify_token
 * @property \Carbon\Carbon|null $line_oa_linked_at
 * @property array|null $notification_settings
 * @property bool $auto_accept_bookings
 * @property float|null $auto_accept_max_distance_km
 * @property int|null $auto_accept_max_daily_bookings
 * @property float|null $current_latitude
 * @property float|null $current_longitude
 * @property \Carbon\Carbon|null $last_location_update
 */
class ServiceProvider extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_providers';

    protected $fillable = [
        'user_id',
        'owner_id',
        'name',
        'phone',
        'email',
        'avatar',
        'bio',
        'rating',
        'total_reviews',
        'total_bookings',
        'total_accepted',
        'total_rejected',
        'acceptance_rate',
        'average_response_minutes',
        'status',
        'is_verified',
        'is_active',
        'line_user_id',
        'line_notify_token',
        'line_oa_linked_at',
        'notification_settings',
        'auto_accept_bookings',
        'auto_accept_max_distance_km',
        'auto_accept_max_daily_bookings',
        'current_latitude',
        'current_longitude',
        'last_location_update',
        'id_card_number',
        'id_card_image',
        'license_image',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'acceptance_rate' => 'decimal:2',
        'auto_accept_max_distance_km' => 'decimal:2',
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
        'total_reviews' => 'integer',
        'total_bookings' => 'integer',
        'total_accepted' => 'integer',
        'total_rejected' => 'integer',
        'average_response_minutes' => 'integer',
        'auto_accept_max_daily_bookings' => 'integer',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'auto_accept_bookings' => 'boolean',
        'notification_settings' => 'array',
        'line_oa_linked_at' => 'datetime',
        'last_location_update' => 'datetime',
    ];

    //===========================================
    // Relationships
    //===========================================

    /**
     * บัญชี User ของ provider
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * เจ้าของระบบ
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * การจองทั้งหมด
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class, 'provider_id');
    }

    /**
     * รีวิวทั้งหมด
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ServiceReview::class, 'provider_id');
    }

    /**
     * พื้นที่ที่รับงาน
     */
    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(
            ServiceArea::class,
            'service_provider_areas',
            'provider_id',
            'area_id'
        )->withTimestamps();
    }

    /**
     * การแจ้งเตือนทั้งหมด
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(ServiceBookingNotification::class, 'provider_id');
    }

    //===========================================
    // Scopes
    //===========================================

    /**
     * Scope: เฉพาะ provider ที่พร้อมรับงาน
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
            ->where('is_active', true);
    }

    /**
     * Scope: เฉพาะ provider ที่ยืนยันตัวตนแล้ว
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: เชื่อม LINE OA แล้ว
     */
    public function scopeLinkedLine($query)
    {
        return $query->whereNotNull('line_user_id');
    }

    //===========================================
    // Methods - Status Management
    //===========================================

    /**
     * ตั้งสถานะว่าง (Available)
     */
    public function setAvailable(): void
    {
        $this->update(['status' => 'available']);
    }

    /**
     * ตั้งสถานะไม่ว่าง (Busy)
     */
    public function setBusy(): void
    {
        $this->update(['status' => 'busy']);
    }

    /**
     * ตั้งสถานะออฟไลน์
     */
    public function setOffline(): void
    {
        $this->update(['status' => 'offline']);
    }

    /**
     * ตรวจสอบว่า provider พร้อมรับงานหรือไม่
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->is_active;
    }

    //===========================================
    // Methods - LINE Integration
    //===========================================

    /**
     * ตรวจสอบว่าเชื่อม LINE OA แล้วหรือยัง
     */
    public function hasLineConnected(): bool
    {
        return !empty($this->line_user_id);
    }

    /**
     * ตั้งค่า LINE User ID
     */
    public function linkLineAccount(string $lineUserId): void
    {
        $this->update([
            'line_user_id' => $lineUserId,
            'line_oa_linked_at' => now(),
        ]);
    }

    /**
     * ยกเลิกการเชื่อม LINE
     */
    public function unlinkLineAccount(): void
    {
        $this->update([
            'line_user_id' => null,
            'line_notify_token' => null,
            'line_oa_linked_at' => null,
        ]);
    }

    /**
     * ตรวจสอบว่าเปิดการแจ้งเตือนผ่าน LINE หรือไม่
     */
    public function isLineNotificationEnabled(): bool
    {
        if (!$this->hasLineConnected()) {
            return false;
        }

        $settings = $this->notification_settings ?? [];
        return $settings['line'] ?? false;
    }

    //===========================================
    // Methods - Statistics
    //===========================================

    /**
     * เพิ่มจำนวนการรับงาน
     */
    public function incrementAccepted(): void
    {
        $this->increment('total_accepted');
        $this->increment('total_bookings');
        $this->recalculateAcceptanceRate();
    }

    /**
     * เพิ่มจำนวนการปฏิเสธงาน
     */
    public function incrementRejected(): void
    {
        $this->increment('total_rejected');
        $this->recalculateAcceptanceRate();
    }

    /**
     * คำนวณอัตราการรับงานใหม่
     */
    protected function recalculateAcceptanceRate(): void
    {
        $total = $this->total_accepted + $this->total_rejected;

        if ($total > 0) {
            $this->acceptance_rate = ($this->total_accepted / $total) * 100;
            $this->save();
        }
    }

    /**
     * อัพเดทคะแนนเฉลี่ย
     */
    public function updateAverageRating(): void
    {
        $avgRating = $this->reviews()
            ->where('is_visible', true)
            ->avg('rating');

        $this->rating = $avgRating ?? 0;
        $this->total_reviews = $this->reviews()->where('is_visible', true)->count();
        $this->save();
    }

    /**
     * อัพเดทเวลาเฉลี่ยในการตอบกลับ
     */
    public function updateAverageResponseTime(int $responseMinutes): void
    {
        // คำนวณค่าเฉลี่ยแบบ moving average
        $currentAvg = $this->average_response_minutes;
        $newAvg = ($currentAvg + $responseMinutes) / 2;

        $this->average_response_minutes = round($newAvg);
        $this->save();
    }

    //===========================================
    // Methods - Location
    //===========================================

    /**
     * อัพเดทตำแหน่งปัจจุบัน
     */
    public function updateLocation(float $latitude, float $longitude): void
    {
        $this->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'last_location_update' => now(),
        ]);
    }

    /**
     * ตรวจสอบว่ามีตำแหน่งหรือไม่
     */
    public function hasLocation(): bool
    {
        return !is_null($this->current_latitude) && !is_null($this->current_longitude);
    }

    /**
     * คำนวณระยะทางจากตำแหน่งปัจจุบันไปยังจุดหมาย (km)
     */
    public function calculateDistanceTo(float $destinationLat, float $destinationLng): float
    {
        if (!$this->hasLocation()) {
            return 0;
        }

        // ใช้ Haversine formula
        $earthRadius = 6371; // km

        $latFrom = deg2rad($this->current_latitude);
        $lonFrom = deg2rad($this->current_longitude);
        $latTo = deg2rad($destinationLat);
        $lonTo = deg2rad($destinationLng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 2);
    }

    //===========================================
    // Methods - Auto Accept
    //===========================================

    /**
     * ตรวจสอบว่าควรรับงานอัตโนมัติหรือไม่
     */
    public function shouldAutoAccept(float $distanceKm, int $todayBookingsCount): bool
    {
        if (!$this->auto_accept_bookings) {
            return false;
        }

        // ตรวจสอบระยะทาง
        if ($this->auto_accept_max_distance_km && $distanceKm > $this->auto_accept_max_distance_km) {
            return false;
        }

        // ตรวจสอบจำนวนงานต่อวัน
        if ($this->auto_accept_max_daily_bookings && $todayBookingsCount >= $this->auto_accept_max_daily_bookings) {
            return false;
        }

        return true;
    }
}
