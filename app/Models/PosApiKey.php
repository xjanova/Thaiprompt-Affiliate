<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * POS API Key Model
 *
 * จัดการ API Keys สำหรับเครื่อง POS ของร้านค้า
 * ใช้สำหรับ authentication ในระบบสองกุญแจ (Product Key + API Key)
 *
 * @property int $id
 * @property int $shop_id
 * @property string $name ชื่อ API Key
 * @property string $key API Key (hashed)
 * @property string $key_prefix ตัวอักษรต้น key
 * @property string|null $description รายละเอียด
 * @property int $max_terminals จำนวนเครื่องที่อนุญาต
 * @property bool $is_active สถานะใช้งาน
 * @property \Carbon\Carbon|null $expires_at วันหมดอายุ
 * @property \Carbon\Carbon|null $last_used_at ใช้งานล่าสุด
 * @property int|null $created_by ผู้สร้าง
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class PosApiKey extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pos_api_keys';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'shop_id',
        'name',
        'key',
        'key_prefix',
        'description',
        'max_terminals',
        'is_active',
        'expires_at',
        'last_used_at',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'max_terminals' => 'integer',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'key',
    ];

    /**
     * ความสัมพันธ์กับร้านค้า
     *
     * @return BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * ความสัมพันธ์กับผู้สร้าง
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * ความสัมพันธ์กับ POS Terminals
     *
     * @return HasMany
     */
    public function terminals(): HasMany
    {
        return $this->hasMany(PosTerminal::class, 'api_key_id');
    }

    /**
     * สร้าง API Key ใหม่
     *
     * @return array{key: string, prefix: string} คืน plain key และ prefix
     */
    public static function generateKey(): array
    {
        // สร้าง key แบบสุ่ม 32 characters
        $plainKey = 'pk_' . Str::random(32);
        $prefix = substr($plainKey, 0, 8);

        return [
            'key' => $plainKey,
            'prefix' => $prefix,
        ];
    }

    /**
     * ตรวจสอบว่า API Key หมดอายุหรือไม่
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * ตรวจสอบว่าสามารถลงทะเบียนเครื่องเพิ่มได้หรือไม่
     *
     * @return bool
     */
    public function canRegisterMoreTerminals(): bool
    {
        if ($this->max_terminals === 0) {
            return true; // ไม่จำกัด
        }

        return $this->terminals()->count() < $this->max_terminals;
    }

    /**
     * จำนวนเครื่องที่ลงทะเบียนแล้ว
     *
     * @return int
     */
    public function getRegisteredTerminalsCountAttribute(): int
    {
        return $this->terminals()->count();
    }

    /**
     * Scope: API Keys ที่ใช้งานได้
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * อัพเดทเวลาใช้งานล่าสุด
     *
     * @return bool
     */
    public function touch(): bool
    {
        $this->last_used_at = now();
        return $this->save();
    }
}
