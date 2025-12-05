<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MobileDevice Model
 *
 * เก็บข้อมูลเครื่องที่ลงทะเบียนแอพมือถือ
 * สำหรับ Admin Analytics Dashboard
 *
 * @property int $id
 * @property string $device_id Unique device identifier
 * @property int|null $user_id เชื่อมกับ user (nullable สำหรับ guest)
 * @property string $platform ios หรือ android
 * @property string $device_model รุ่นเครื่อง
 * @property string $device_brand ยี่ห้อเครื่อง
 * @property string $os_version เวอร์ชัน OS
 * @property string $app_version เวอร์ชันแอพ
 * @property string|null $push_token Push notification token
 * @property string $locale ภาษาของเครื่อง
 * @property string $timezone Timezone ของเครื่อง
 * @property bool $is_active สถานะ active
 * @property \Carbon\Carbon|null $last_active_at ใช้งานล่าสุดเมื่อ
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MobileDevice extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mobile_devices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'device_id',
        'user_id',
        'platform',
        'device_model',
        'device_brand',
        'os_version',
        'app_version',
        'push_token',
        'locale',
        'timezone',
        'is_active',
        'last_active_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_active_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: เฉพาะ Active devices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เฉพาะ iOS
     */
    public function scopeIos($query)
    {
        return $query->where('platform', 'ios');
    }

    /**
     * Scope: เฉพาะ Android
     */
    public function scopeAndroid($query)
    {
        return $query->where('platform', 'android');
    }

    /**
     * Scope: Active ในช่วง N วันที่ผ่านมา
     */
    public function scopeActiveWithinDays($query, int $days)
    {
        return $query->where('last_active_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: มี Push Token
     */
    public function scopeWithPushToken($query)
    {
        return $query->whereNotNull('push_token');
    }

    /**
     * อัปเดท last_active_at
     */
    public function touchLastActive(): bool
    {
        $this->last_active_at = now();
        return $this->save();
    }

    /**
     * อัปเดท Push Token
     */
    public function updatePushToken(string $token): bool
    {
        $this->push_token = $token;
        return $this->save();
    }

    /**
     * เชื่อมกับ User
     */
    public function linkToUser(int $userId): bool
    {
        $this->user_id = $userId;
        return $this->save();
    }
}
