<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MobileAuthToken Model
 *
 * เก็บ tokens สำหรับ Web-Based Mobile Authentication
 *
 * @property int $id
 * @property string $login_token
 * @property \Carbon\Carbon $login_token_expires_at
 * @property string $code_challenge
 * @property string|null $auth_code
 * @property \Carbon\Carbon|null $auth_code_expires_at
 * @property string $state
 * @property int|null $user_id
 * @property string $device_id
 * @property string|null $device_name
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon|null $used_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MobileAuthToken extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mobile_auth_tokens';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'login_token',
        'login_token_expires_at',
        'code_challenge',
        'auth_code',
        'auth_code_expires_at',
        'state',
        'user_id',
        'device_id',
        'device_name',
        'ip_address',
        'user_agent',
        'used_at',
        // ── Admin QR Pairing fields (เพิ่ม 2026-05-01) ──
        'is_admin',
        'pair_code',
        'pair_code_expires_at',
        'generator_user_id',
        'requires_2fa',
        'two_factor_passed',
        'claimed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'login_token_expires_at' => 'datetime',
        'auth_code_expires_at' => 'datetime',
        'used_at' => 'datetime',
        // ── Admin pairing casts ──
        'is_admin' => 'boolean',
        'pair_code_expires_at' => 'datetime',
        'requires_2fa' => 'boolean',
        'two_factor_passed' => 'boolean',
        'claimed_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'login_token',
        'code_challenge',
        'auth_code',
        'pair_code',
    ];

    /**
     * ความสัมพันธ์กับ User (ผู้ที่ login สำเร็จ)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ User ผู้สร้าง pair_code (admin web)
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generator_user_id');
    }

    /**
     * ตรวจสอบว่า pair_code หมดอายุหรือยัง
     */
    public function isPairCodeExpired(): bool
    {
        if (! $this->pair_code_expires_at) {
            return true;
        }

        return $this->pair_code_expires_at < now();
    }

    /**
     * ตรวจสอบว่า login_token หมดอายุหรือยัง
     */
    public function isLoginTokenExpired(): bool
    {
        return $this->login_token_expires_at < now();
    }

    /**
     * ตรวจสอบว่า auth_code หมดอายุหรือยัง
     */
    public function isAuthCodeExpired(): bool
    {
        if (! $this->auth_code_expires_at) {
            return true;
        }

        return $this->auth_code_expires_at < now();
    }

    /**
     * ตรวจสอบว่าถูกใช้แล้วหรือยัง
     */
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * Scope: ยังไม่หมดอายุ
     */
    public function scopeValid($query)
    {
        return $query->where('login_token_expires_at', '>', now())
            ->whereNull('used_at');
    }

    /**
     * Scope: พร้อม exchange
     */
    public function scopeReadyToExchange($query)
    {
        return $query->whereNotNull('auth_code')
            ->whereNotNull('user_id')
            ->where('auth_code_expires_at', '>', now())
            ->whereNull('used_at');
    }

    /**
     * ลบ tokens ที่หมดอายุ
     *
     * @return int จำนวนที่ลบ
     */
    public static function cleanExpired(): int
    {
        return self::where('login_token_expires_at', '<', now()->subHour())
            ->orWhere(function ($query) {
                $query->whereNotNull('used_at')
                    ->where('used_at', '<', now()->subDay());
            })
            ->delete();
    }
}
