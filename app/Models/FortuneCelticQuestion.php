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
    ];

    protected $casts = [
        'sequence' => 'integer',
        'ai_tokens_used' => 'integer',
        'ai_response_time_ms' => 'integer',
        'answered_at' => 'datetime',
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
}
