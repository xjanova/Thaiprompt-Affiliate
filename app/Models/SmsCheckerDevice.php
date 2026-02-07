<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 * @property int|null $store_id ร้านค้าที่อุปกรณ์นี้ผูกไว้ (null = admin/platform device)
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
        'store_id',
        'fcm_token',
        'fcm_token_updated_at',
    ];

    protected $hidden = [
        'api_key',
        'secret_key',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
        'fcm_token_updated_at' => 'datetime',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับร้านค้าที่อุปกรณ์นี้ผูกไว้
     * store_id = null หมายถึงอุปกรณ์ของ admin/platform
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * ความสัมพันธ์กับ SMS notifications ที่ส่งจากอุปกรณ์นี้
     */
    public function notifications()
    {
        return $this->hasMany(SmsPaymentNotification::class, 'device_id', 'device_id');
    }

    /**
     * ดึงบัญชีธนาคารที่เปิดใช้ SMS Checker
     * ใช้แสดงว่าอุปกรณ์นี้กำลังตรวจสอบบัญชีไหนบ้าง
     */
    public function monitoredBankAccounts()
    {
        return PaymentBankAccount::active()->smsCheckerEnabled()->get();
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

    // ========================================
    // SCOPES (Multi-Store)
    // ========================================

    /**
     * Filter อุปกรณ์ตามร้านค้า
     * storeId = null → ดึงเฉพาะอุปกรณ์ admin/platform
     */
    public function scopeForStore($query, ?int $storeId)
    {
        if ($storeId === null) {
            return $query->whereNull('store_id');
        }
        return $query->where('store_id', $storeId);
    }

    /**
     * ดึงเฉพาะอุปกรณ์ admin (store_id IS NULL)
     */
    public function scopeAdminDevices($query)
    {
        return $query->whereNull('store_id');
    }

    /**
     * ดึงเฉพาะอุปกรณ์ที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * ดึงเฉพาะอุปกรณ์ที่มี FCM token
     */
    public function scopeWithFcmToken($query)
    {
        return $query->whereNotNull('fcm_token')->where('fcm_token', '!=', '');
    }

    // ========================================
    // HELPERS (Multi-Store)
    // ========================================

    /**
     * ตรวจสอบว่าเป็นอุปกรณ์ของ admin/official shop หรือไม่
     *
     * Admin device = store_id เป็น null (legacy) หรือ platformStoreId
     */
    public function isAdminDevice(): bool
    {
        if ($this->store_id === null) {
            return true; // Legacy device ที่ยังไม่ได้ set store_id
        }

        return (int) $this->store_id === VendorStore::getPlatformStoreId();
    }

    /**
     * ตรวจสอบว่าเป็นอุปกรณ์ของร้านค้าที่ระบุหรือไม่
     */
    public function belongsToStore(int $storeId): bool
    {
        return $this->store_id === $storeId;
    }
}
