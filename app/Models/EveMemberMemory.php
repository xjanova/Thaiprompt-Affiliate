<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 🌸 ความจำบทสนทนาของน้อง Eve รายสมาชิก (เก็บ 7 วัน)
 *
 * แถวละ 1 สมาชิก เก็บเทิร์นล่าสุดไว้เป็น JSON + บทสรุปของเทิร์นเก่าที่ย่อไปแล้ว
 *
 * ⚠️ อย่าสับสนกับ [[EveMemory]] — ตัวนั้นคือ "บทเรียนรวม" ที่แอดมิน/LLM เขียนไว้ใช้ทั้งระบบ
 *    ไม่ผูกกับผู้ใช้คนไหน และถูกอ่านทุกเทิร์นของ Eve ฝั่งแอดมิน
 *
 * ⚠️ ผู้ที่ไม่ได้ล็อกอิน (guest) ต้องไม่มีแถวในตารางนี้เด็ดขาด
 *    เพราะไม่มีทางยืนยันตัวตนเพื่อลบตามคำขอ PDPA ได้
 *
 * @property int $id
 * @property int $user_id
 * @property array|null $turns เทิร์นสนทนา [{role, content, ts}]
 * @property string|null $summary บทสรุปเทิร์นเก่า
 * @property int $turn_count จำนวนเทิร์นสะสม
 * @property \Carbon\Carbon|null $last_message_at เวลาคุยล่าสุด
 */
class EveMemberMemory extends Model
{
    /** จำนวนวันที่เก็บความจำ — แหล่งความจริงเดียวของ "7 วัน" */
    public const RETENTION_DAYS = 7;

    protected $table = 'eve_member_memories';

    protected $fillable = [
        'user_id',
        'turns',
        'summary',
        'turn_count',
        'last_message_at',
    ];

    protected $casts = [
        'turns' => 'array',
        'turn_count' => 'integer',
        'last_message_at' => 'datetime',
    ];

    /**
     * เจ้าของความจำ
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความจำที่หมดอายุแล้ว (ไว้ให้คำสั่ง prune กวาดทิ้ง)
     */
    public function scopeStale(Builder $query, ?int $days = null): Builder
    {
        $days = $days ?? self::RETENTION_DAYS;

        return $query->where(function ($q) use ($days) {
            $q->where('last_message_at', '<', now()->subDays($days))
                ->orWhereNull('last_message_at');
        });
    }

    /**
     * แถวนี้เกินอายุที่กำหนดหรือยัง
     *
     * ใช้ตอนอ่านด้วย เพื่อ self-heal: ถึงคำสั่ง prune ไม่ได้รัน
     * ความจำที่หมดอายุก็จะไม่ถูกส่งเข้า prompt อยู่ดี
     */
    public function isExpired(?int $days = null): bool
    {
        $days = $days ?? self::RETENTION_DAYS;

        if ($this->last_message_at === null) {
            return true;
        }

        return $this->last_message_at->lt(now()->subDays($days));
    }
}
