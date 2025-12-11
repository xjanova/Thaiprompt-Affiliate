<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * POS Terminal Model
 *
 * จัดการข้อมูลเครื่อง POS ที่ลงทะเบียนในระบบ
 *
 * @property int $id
 * @property int $api_key_id
 * @property int $shop_id
 * @property string $product_key Product Key จากเครื่อง
 * @property string $device_id Device ID (hardware fingerprint)
 * @property string $device_name ชื่อเครื่อง
 * @property string|null $device_model รุ่นเครื่อง
 * @property string|null $platform Platform: windows, android, ios
 * @property string|null $app_version เวอร์ชันแอป
 * @property bool $is_active สถานะใช้งาน
 * @property \Carbon\Carbon $registered_at วันที่ลงทะเบียน
 * @property \Carbon\Carbon|null $last_seen_at เข้าใช้งานล่าสุด
 * @property \Carbon\Carbon|null $last_sync_at Sync ข้อมูลล่าสุด
 * @property array|null $settings ตั้งค่าเครื่อง
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class PosTerminal extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pos_terminals';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'api_key_id',
        'shop_id',
        'product_key',
        'device_id',
        'device_name',
        'device_model',
        'platform',
        'app_version',
        'is_active',
        'registered_at',
        'last_seen_at',
        'last_sync_at',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'registered_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ API Key
     *
     * @return BelongsTo
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(PosApiKey::class, 'api_key_id');
    }

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
     * ความสัมพันธ์กับรายงานรายวัน
     *
     * @return HasMany
     */
    public function dailyReports(): HasMany
    {
        return $this->hasMany(PosDailyReport::class, 'pos_terminal_id');
    }

    /**
     * ความสัมพันธ์กับ Orders ที่สร้างจากเครื่องนี้
     *
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'pos_terminal_id');
    }

    /**
     * Scope: Terminals ที่ใช้งานอยู่
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Terminals ที่ออนไลน์ (เห็นใน 5 นาทีที่แล้ว)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnline($query)
    {
        return $query->where('last_seen_at', '>=', now()->subMinutes(5));
    }

    /**
     * ตรวจสอบว่าเครื่องออนไลน์หรือไม่
     *
     * @return bool
     */
    public function isOnline(): bool
    {
        if (!$this->last_seen_at) {
            return false;
        }

        return $this->last_seen_at->diffInMinutes(now()) < 5;
    }

    /**
     * อัพเดท last seen
     *
     * @return bool
     */
    public function updateLastSeen(): bool
    {
        $this->last_seen_at = now();
        return $this->save();
    }

    /**
     * อัพเดท last sync
     *
     * @return bool
     */
    public function updateLastSync(): bool
    {
        $this->last_sync_at = now();
        return $this->save();
    }

    /**
     * ดึงยอดขายวันนี้
     *
     * @return float
     */
    public function getTodaySalesAttribute(): float
    {
        $report = $this->dailyReports()
            ->where('report_date', today())
            ->first();

        return $report?->total_sales ?? 0;
    }

    /**
     * ดึงจำนวน orders วันนี้
     *
     * @return int
     */
    public function getTodayOrdersCountAttribute(): int
    {
        $report = $this->dailyReports()
            ->where('report_date', today())
            ->first();

        return $report?->total_orders ?? 0;
    }
}
