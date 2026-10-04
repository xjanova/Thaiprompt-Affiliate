<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * หัวใจที่ผู้ซื้อให้ไรเดอร์ (ไรเดอร์รอบ 2, 2026-10-04)
 *
 * 1 ดวงต่อ 1 งานที่ส่งสำเร็จ (rider_job_id unique) ให้ได้เฉพาะผู้ซื้อของงานนั้น
 * ผู้ซื้อคนหนึ่งให้ไรเดอร์คนหนึ่งครบ rider.lock_min_hearts ดวง → ล็อกเรียกไรเดอร์คนนั้นได้
 *
 * @property int $id
 * @property int $rider_id
 * @property int $user_id ผู้ซื้อที่ให้หัวใจ
 * @property int $rider_job_id
 */
class RiderHeart extends Model
{
    protected $fillable = ['rider_id', 'user_id', 'rider_job_id'];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function riderJob(): BelongsTo
    {
        return $this->belongsTo(RiderJob::class);
    }
}
