<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * การส่งมอบของจากไรเดอร์ถึงผู้ซื้อ (ไรเดอร์รอบ 2, 2026-10-04)
 *
 * ปิดงานได้ 4 ทาง (คอลัมน์ method):
 *   qr / code      — ไรเดอร์สแกน QR ผู้ซื้อ (หรือกรอกรหัส 6 หลักของผู้ซื้อ) + ผู้ซื้อสแกน QR ไรเดอร์
 *                    (หรือกรอกรหัส 6 หลักของไรเดอร์) ครบสองฝ่าย — ฝั่งใดใช้รหัส = code
 *   fallback       — ผู้ซื้อไม่สแกน: รูปรอบ 1 ที่จุดส่ง → รอ 3 นาที → รูปรอบ 2 → ปลดเงินอัตโนมัติใน 24 ชม. ถ้าไม่ร้องเรียน
 *   buyer_confirm  — ระหว่างทางสำรอง ผู้ซื้อกด "ได้รับของแล้ว" เอง
 *   admin          — แอดมินตัดสิน (ปล่อยเงิน/คืนเงิน)
 *
 * @property int $id
 * @property int $rider_job_id
 * @property int|null $buyer_user_id
 * @property string $status
 * @property string|null $method
 * @property string $secret กุญแจลับต่องาน (เข้ารหัสใน DB)
 * @property string|null $code_hash
 */
class DeliveryHandover extends Model
{
    public const STATUS_WAITING = 'waiting';

    public const STATUS_RIDER_CONFIRMED = 'rider_confirmed';

    public const STATUS_BUYER_CONFIRMED = 'buyer_confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FALLBACK_WAITING = 'fallback_waiting';

    public const STATUS_FALLBACK_PENDING_RELEASE = 'fallback_pending_release';

    public const STATUS_DISPUTED = 'disputed';

    public const STATUS_RELEASED = 'released';

    public const STATUS_REFUNDED = 'refunded';

    /** สถานะที่เงินถูกปลด/คืนแล้ว แก้ต่อไม่ได้ */
    public const FINAL_STATUSES = [self::STATUS_COMPLETED, self::STATUS_RELEASED, self::STATUS_REFUNDED];

    protected $fillable = [
        'rider_job_id',
        'buyer_user_id',
        'status',
        'method',
        'secret',
        'code_hash',
        'code_attempts',
        'code_locked_until',
        'rider_code_attempts',  // รอบแก้หลังรีวิว: ผู้ซื้อกรอกรหัสของไรเดอร์ผิด
        'rider_code_locked_until',
        'rider_confirmed_at',
        'rider_confirm_latitude',
        'rider_confirm_longitude',
        'rider_confirm_distance_m',
        'buyer_confirmed_at',
        'arrival_photo_path',
        'arrival_photo_at',
        'arrival_latitude',
        'arrival_longitude',
        'arrival_distance_m',
        'wait_until',
        'waited_photo_path',
        'waited_photo_at',
        'waited_latitude',
        'waited_longitude',
        'auto_release_at',
        'disputed_at',
        'dispute_reason',
        'dispute_note',
        'resolved_at',
        'resolved_by',
        'resolution',
        'resolution_note',
        'completed_at',
    ];

    /** ห้ามหลุดออกไปกับ JSON ใดๆ */
    protected $hidden = ['secret', 'code_hash'];

    protected $casts = [
        'secret' => 'encrypted',
        'code_attempts' => 'integer',
        'code_locked_until' => 'datetime',
        'rider_code_attempts' => 'integer',
        'rider_code_locked_until' => 'datetime',
        'rider_confirmed_at' => 'datetime',
        'rider_confirm_latitude' => 'decimal:7',
        'rider_confirm_longitude' => 'decimal:7',
        'rider_confirm_distance_m' => 'integer',
        'buyer_confirmed_at' => 'datetime',
        'arrival_photo_at' => 'datetime',
        'arrival_latitude' => 'decimal:7',
        'arrival_longitude' => 'decimal:7',
        'arrival_distance_m' => 'integer',
        'wait_until' => 'datetime',
        'waited_photo_at' => 'datetime',
        'waited_latitude' => 'decimal:7',
        'waited_longitude' => 'decimal:7',
        'auto_release_at' => 'datetime',
        'disputed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function riderJob(): BelongsTo
    {
        return $this->belongsTo(RiderJob::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true);
    }
}
