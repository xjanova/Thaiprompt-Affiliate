<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FortuneSavedQuestion — คำถามที่ AI ตอบไม่ได้ รอแอดมินตอบกลับ
 *
 * เมื่อแชทบอท "จันทรา" เจอคำถามที่ไม่สามารถตอบได้
 * จะบันทึกคำถามไว้ในตารางนี้ เพื่อให้แอดมินมาตอบกลับทีหลัง
 *
 * @property int $id
 * @property string $platform_user_id LINE/Facebook user ID
 * @property string $platform line หรือ facebook
 * @property string|null $user_name ชื่อผู้ใช้
 * @property string $question คำถามที่ AI ตอบไม่ได้
 * @property string|null $ai_response คำตอบของ AI (ถ้ามี)
 * @property string $reason สาเหตุ: ai_cannot_answer, ai_failed, fallback
 * @property string|null $admin_reply คำตอบจากแอดมิน
 * @property int|null $replied_by แอดมินที่ตอบ
 * @property \Carbon\Carbon|null $replied_at เวลาที่แอดมินตอบ
 * @property bool $is_replied ตอบแล้วหรือยัง
 * @property bool $is_sent_to_user ส่งคำตอบกลับไปหาผู้ใช้แล้วหรือยัง
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FortuneSavedQuestion extends Model
{
    /**
     * @var string
     */
    protected $table = 'fortune_saved_questions';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'platform_user_id',
        'platform',
        'user_name',
        'question',
        'ai_response',
        'reason',
        'admin_reply',
        'replied_by',
        'replied_at',
        'is_replied',
        'is_sent_to_user',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_replied' => 'boolean',
        'is_sent_to_user' => 'boolean',
        'replied_at' => 'datetime',
    ];

    // ============================================================
    // Relationships
    // ============================================================

    /**
     * แอดมินที่ตอบคำถาม
     */
    public function repliedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * คำถามที่ยังไม่ได้ตอบ
     */
    public function scopePending($query)
    {
        return $query->where('is_replied', false);
    }

    /**
     * คำถามที่ตอบแล้ว
     */
    public function scopeReplied($query)
    {
        return $query->where('is_replied', true);
    }

    /**
     * คำถามที่ตอบแล้วแต่ยังไม่ได้ส่งกลับไปหาผู้ใช้
     */
    public function scopeNotSentToUser($query)
    {
        return $query->where('is_replied', true)->where('is_sent_to_user', false);
    }

    // ============================================================
    // Helper Methods
    // ============================================================

    /**
     * บันทึกคำถามใหม่ที่ AI ตอบไม่ได้
     */
    public static function saveQuestion(
        string $platformUserId,
        string $question,
        string $reason = 'ai_cannot_answer',
        ?string $aiResponse = null,
        ?string $userName = null,
        string $platform = 'line'
    ): self {
        return self::create([
            'platform_user_id' => $platformUserId,
            'platform' => $platform,
            'user_name' => $userName,
            'question' => $question,
            'ai_response' => $aiResponse,
            'reason' => $reason,
        ]);
    }

    /**
     * แอดมินตอบคำถาม
     */
    public function markAsReplied(string $reply, int $adminId): self
    {
        $this->update([
            'admin_reply' => $reply,
            'replied_by' => $adminId,
            'replied_at' => now(),
            'is_replied' => true,
        ]);

        return $this;
    }

    /**
     * Label สำหรับ reason
     */
    public function getReasonLabelAttribute(): string
    {
        return match ($this->reason) {
            'ai_cannot_answer' => 'AI ตอบไม่ได้',
            'ai_failed' => 'AI ล้มเหลว',
            'fallback' => 'ไม่ match คำใดเลย',
            default => $this->reason,
        };
    }
}
