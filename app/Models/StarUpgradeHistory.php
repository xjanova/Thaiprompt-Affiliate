<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StarUpgradeHistory Model
 *
 * ประวัติการอัพเกรดดาว
 *
 * @property int $id
 * @property int $user_id
 * @property int $from_stars ดาวก่อนอัพเกรด
 * @property int $to_stars ดาวหลังอัพเกรด
 * @property float $coins_paid Coins ที่จ่าย
 * @property string $status
 * @property string|null $note
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class StarUpgradeHistory extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'star_upgrade_history';

    /**
     * สถานะ
     */
    public const STATUSES = [
        'completed' => ['name' => 'สำเร็จ', 'color' => 'green', 'icon' => '✅'],
        'refunded' => ['name' => 'คืนเงิน', 'color' => 'orange', 'icon' => '↩️'],
    ];

    /**
     * ฟิลด์ที่อนุญาตให้ mass assign
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'from_stars',
        'to_stars',
        'coins_paid',
        'status',
        'note',
        'ip_address',
        'user_agent',
    ];

    /**
     * ฟิลด์ที่ต้อง cast
     *
     * @var array<string, string>
     */
    protected $casts = [
        'coins_paid' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'from_stars' => 0,
        'status' => 'completed',
    ];

    // ==========================================
    // ความสัมพันธ์ (Relationships)
    // ==========================================

    /**
     * ผู้ใช้
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ราคาดาวที่อัพเกรดไป
     */
    public function targetStarPrice(): BelongsTo
    {
        return $this->belongsTo(StarUpgradePrice::class, 'to_stars', 'stars');
    }

    // ==========================================
    // Accessors
    // ==========================================

    /**
     * ข้อมูลสถานะ
     */
    public function getStatusInfoAttribute(): array
    {
        return self::STATUSES[$this->status] ?? self::STATUSES['completed'];
    }

    /**
     * ชื่อสถานะ
     */
    public function getStatusNameAttribute(): string
    {
        return $this->status_info['name'];
    }

    /**
     * สีสถานะ
     */
    public function getStatusColorAttribute(): string
    {
        return $this->status_info['color'];
    }

    /**
     * จำนวนดาวที่อัพ
     */
    public function getStarsGainedAttribute(): int
    {
        return $this->to_stars - $this->from_stars;
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * กรองตามผู้ใช้
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * เฉพาะที่สำเร็จ
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * เรียงตามล่าสุด
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
