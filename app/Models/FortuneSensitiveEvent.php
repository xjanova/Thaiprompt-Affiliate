<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FortuneSensitiveEvent Model
 *
 * บันทึกทุก trigger ของ Sensitive AI Mode
 *
 * ใช้สำหรับ:
 *   - admin วิเคราะห์ pattern (ลูกค้าคนไหน trigger บ่อย)
 *   - debug false positive (Pro ถูก trigger โดยไม่จำเป็น)
 *   - ติดตามต้นทุน (รวม cost_thb ต่อวัน)
 *   - feed ลง ObsidianX (Phase 3) — เรียนรู้จาก history
 *
 * @property int $id
 * @property string $platform
 * @property string $platform_user_id
 * @property int|null $user_id
 * @property string $context
 * @property bool $is_sensitive
 * @property bool $is_offtopic
 * @property int|null $mood_level
 * @property int|null $complexity
 * @property array|null $reasons
 * @property string|null $detection_used
 * @property bool $used_pro_model
 * @property string|null $pro_provider
 * @property string|null $pro_model
 * @property bool $budget_blocked
 * @property bool $offtopic_blocked
 * @property string|null $message_hash
 * @property string|null $message_preview
 * @property int|null $message_length
 * @property int|null $tokens_used
 * @property float|null $cost_thb
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FortuneSensitiveEvent extends Model
{
    protected $table = 'fortune_sensitive_events';

    /** @var array<string> */
    protected $fillable = [
        'platform',
        'platform_user_id',
        'user_id',
        'context',
        'is_sensitive',
        'is_offtopic',
        'mood_level',
        'complexity',
        'reasons',
        'detection_used',
        'used_pro_model',
        'pro_provider',
        'pro_model',
        'budget_blocked',
        'offtopic_blocked',
        'message_hash',
        'message_preview',
        'message_length',
        'tokens_used',
        'cost_thb',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'user_id' => 'integer',
        'is_sensitive' => 'boolean',
        'is_offtopic' => 'boolean',
        'mood_level' => 'integer',
        'complexity' => 'integer',
        'reasons' => 'array',
        'used_pro_model' => 'boolean',
        'budget_blocked' => 'boolean',
        'offtopic_blocked' => 'boolean',
        'message_length' => 'integer',
        'tokens_used' => 'integer',
        'cost_thb' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ User (ถ้า map ได้)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: เฉพาะ events ของวันนี้
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    /**
     * Scope: เฉพาะที่ใช้ Pro model จริง
     */
    public function scopeUsedPro($query)
    {
        return $query->where('used_pro_model', true);
    }

    /**
     * Scope: ของ user เฉพาะ
     */
    public function scopeForPlatformUser($query, string $platform, string $platformUserId)
    {
        return $query->where('platform', $platform)
            ->where('platform_user_id', $platformUserId);
    }
}
