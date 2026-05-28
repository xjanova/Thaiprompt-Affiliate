<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * คำถาม-คำตอบใน Celtic Cross reading
 *
 * 1 row = 1 คำถาม (sequence 1, 2, หรือ 3)
 * - Q1 (sequence=1): full storytelling — AI ผูก 10 ไพ่ × 10 ตำแหน่ง × คำถาม
 * - Q2-Q3 (sequence=2,3): follow-up — AI ตอบคำถามใหม่ ไม่อธิบายไพ่ใหม่
 *
 * @property int $id
 * @property int $fortune_reading_id
 * @property int $sequence
 * @property string $question
 * @property string|null $response
 * @property string|null $ai_provider
 * @property string|null $ai_model
 * @property int $ai_tokens_used
 * @property int $ai_response_time_ms
 * @property \Carbon\Carbon|null $answered_at
 * @property \Carbon\Carbon|null $delivered_at เวลาที่ push ถึงลูกค้าสำเร็จจริง (null = ยังไม่ถึง)
 * @property int $delivery_attempts จำนวนครั้งที่พยายามส่งคำตอบให้ลูกค้า
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FortuneCelticQuestion extends Model
{
    protected $table = 'fortune_celtic_questions';

    protected $fillable = [
        'fortune_reading_id',
        'sequence',
        'question',
        'response',
        'ai_provider',
        'ai_model',
        'ai_tokens_used',
        'ai_response_time_ms',
        'answered_at',
        'delivered_at',
        'delivery_attempts',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'ai_tokens_used' => 'integer',
        'ai_response_time_ms' => 'integer',
        'answered_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_attempts' => 'integer',
    ];

    /**
     * ความสัมพันธ์กับ FortuneReading
     */
    public function reading(): BelongsTo
    {
        return $this->belongsTo(FortuneReading::class, 'fortune_reading_id');
    }

    /**
     * เป็นคำถามแรก (full storytelling) ไหม
     */
    public function isMainQuestion(): bool
    {
        return $this->sequence === 1;
    }

    /**
     * เป็น follow-up (no card explain) ไหม
     */
    public function isFollowup(): bool
    {
        return $this->sequence > 1;
    }

    /**
     * 🐛 (2026-05-28) ทำเครื่องหมายว่า push คำตอบถึงลูกค้าสำเร็จแล้ว
     *
     * เรียกจาก FortuneChannelManager หลัง send สำเร็จ + จาก fortune:celtic-redeliver cron
     * idempotent — set แค่ครั้งแรก (กัน overwrite เวลาส่งจริง)
     */
    public function markDelivered(): void
    {
        if ($this->delivered_at !== null) {
            return;
        }

        $this->forceFill(['delivered_at' => now()])->save();
    }

    /**
     * scope: คำถามที่ตอบแล้วแต่ยังส่งไม่ถึงลูกค้า (สำหรับ cron re-deliver)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUndelivered($query)
    {
        return $query->whereNotNull('answered_at')
            ->whereNull('delivered_at')
            ->whereNotNull('response')
            ->where('response', '!=', '');
    }
}
