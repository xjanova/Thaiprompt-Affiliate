<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserNotificationToken Model
 *
 * เก็บ Push Token ของผู้ใช้
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string $provider
 * @property string $platform
 */
class UserNotificationToken extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'user_notification_tokens';

    /**
     * Fields ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'token',
        'provider',
        'device_id',
        'device_name',
        'platform',
        'app_version',
        'is_active',
        'last_used_at',
    ];

    /**
     * Casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'provider' => 'expo',
        'platform' => 'android',
        'is_active' => true,
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

    // =====================================================
    // Scopes
    // =====================================================

    /**
     * Scope สำหรับ token ที่ใช้งานอยู่
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope สำหรับ platform
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope สำหรับ provider
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    // =====================================================
    // Methods
    // =====================================================

    /**
     * อัพเดทเวลาใช้งานล่าสุด
     */
    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * ปิดใช้งาน token
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * เปิดใช้งาน token
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * บันทึกหรืออัพเดท token
     */
    public static function registerToken(int $userId, string $token, array $deviceInfo = []): self
    {
        return self::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $userId,
                'provider' => $deviceInfo['provider'] ?? 'expo',
                'device_id' => $deviceInfo['device_id'] ?? null,
                'device_name' => $deviceInfo['device_name'] ?? null,
                'platform' => $deviceInfo['platform'] ?? 'android',
                'app_version' => $deviceInfo['app_version'] ?? null,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }
}
