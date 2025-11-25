<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserBlock Model
 *
 * จัดการการ Block ระหว่างผู้ใช้/ผู้ให้บริการ
 *
 * @property int $id
 * @property int|null $blocker_user_id
 * @property int|null $blocker_provider_id
 * @property string $blocker_type
 * @property int|null $blocked_user_id
 * @property int|null $blocked_provider_id
 * @property string $blocked_type
 * @property string $block_type
 * @property string|null $reason
 * @property \Carbon\Carbon|null $expires_at
 */
class UserBlock extends Model
{
    protected $fillable = [
        'blocker_user_id',
        'blocker_provider_id',
        'blocker_type',
        'blocked_user_id',
        'blocked_provider_id',
        'blocked_type',
        'block_type',
        'reason',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * ประเภทการ block
     */
    public const BLOCK_TYPES = [
        'personal' => 'Block ส่วนตัว',
        'safety' => 'ปัญหาความปลอดภัย',
        'system_auto' => 'ระบบ block อัตโนมัติ',
        'admin' => 'Admin block',
    ];

    /**
     * ผู้ block (User)
     */
    public function blockerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocker_user_id');
    }

    /**
     * ผู้ block (Provider)
     */
    public function blockerProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'blocker_provider_id');
    }

    /**
     * ผู้ถูก block (User)
     */
    public function blockedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_user_id');
    }

    /**
     * ผู้ถูก block (Provider)
     */
    public function blockedProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'blocked_provider_id');
    }

    /**
     * Scope: กรองเฉพาะที่ยังมีผล
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * ตรวจสอบว่ายังมีผลอยู่หรือไม่
     */
    public function isActive(): bool
    {
        return is_null($this->expires_at) || $this->expires_at->isFuture();
    }

    /**
     * ยกเลิก block
     */
    public function unblock(): void
    {
        $this->delete();
    }

    /**
     * ตรวจสอบว่า User ถูก block โดย Provider หรือไม่
     */
    public static function isUserBlockedByProvider(int $userId, int $providerId): bool
    {
        return static::where('blocked_user_id', $userId)
            ->where('blocker_provider_id', $providerId)
            ->active()
            ->exists();
    }

    /**
     * ตรวจสอบว่า Provider ถูก block โดย User หรือไม่
     */
    public static function isProviderBlockedByUser(int $providerId, int $userId): bool
    {
        return static::where('blocked_provider_id', $providerId)
            ->where('blocker_user_id', $userId)
            ->active()
            ->exists();
    }
}
