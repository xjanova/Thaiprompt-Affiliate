<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * SMS Checker Device Model
 *
 * แทนอุปกรณ์ Android ที่ลงทะเบียนเพื่อส่ง SMS notifications
 * แต่ละอุปกรณ์มี api_key และ secret_key เฉพาะตัว
 *
 * @property int $id
 * @property string $device_id รหัสอุปกรณ์ (SMSCHK-XXXXXXXX)
 * @property string|null $device_name ชื่ออุปกรณ์
 * @property string $api_key API Key สำหรับ authentication (64 chars hex)
 * @property string $secret_key Secret Key สำหรับ encryption/signing (64 chars hex)
 * @property string $platform แพลตฟอร์ม (android)
 * @property string|null $app_version เวอร์ชั่นแอพ
 * @property string $status สถานะ (active/inactive/blocked)
 * @property \Carbon\Carbon|null $last_active_at เวลากิจกรรมล่าสุด
 * @property int|null $user_id เจ้าของอุปกรณ์
 * @property string|null $ip_address IP address ล่าสุด
 */
class SmsCheckerDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'device_name',
        'api_key',
        'secret_key',
        'platform',
        'app_version',
        'status',
        'last_active_at',
        'user_id',
        'ip_address',
    ];

    protected $hidden = [
        'api_key',
        'secret_key',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
    ];

    /**
     * ตรวจสอบว่าอุปกรณ์ active หรือไม่
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * ความสัมพันธ์กับ User (เจ้าของอุปกรณ์)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ SMS notifications ที่ส่งจากอุปกรณ์นี้
     */
    public function notifications()
    {
        return $this->hasMany(SmsPaymentNotification::class, 'device_id', 'device_id');
    }

    /**
     * ค้นหาอุปกรณ์จาก API Key
     *
     * @param string $apiKey
     * @return self|null
     */
    public static function findByApiKey(string $apiKey): ?self
    {
        return static::where('api_key', $apiKey)->first();
    }

    /**
     * สร้าง API Key ใหม่ (64-char hex string)
     *
     * @return string
     */
    public static function generateApiKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * สร้าง Secret Key ใหม่ (64-char hex string)
     *
     * @return string
     */
    public static function generateSecretKey(): string
    {
        return bin2hex(random_bytes(32));
    }
}
