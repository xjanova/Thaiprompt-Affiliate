<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FortuneTakeoverLog Model
 *
 * เก็บประวัติการเทคโอเวอร์ทั้งหมดในระบบดูดวง
 * (แอดมิน/แม่หมอเข้ามาคุยแทน AI)
 *
 * @property int $id
 * @property int $fortune_reading_id
 * @property int|null $user_id
 * @property string $action  takeover|resume|extend|message|auto_expire
 * @property string|null $reason  manual|auto_reply|customer_request
 * @property int|null $duration_minutes
 * @property string|null $message
 * @property string|null $platform  line|facebook
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FortuneTakeoverLog extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'fortune_takeover_logs';

    /**
     * ประเภทการกระทำที่เป็นไปได้
     */
    public const ACTION_TAKEOVER = 'takeover';

    public const ACTION_RESUME = 'resume';

    public const ACTION_EXTEND = 'extend';

    public const ACTION_MESSAGE = 'message';

    public const ACTION_AUTO_EXPIRE = 'auto_expire';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'fortune_reading_id',
        'user_id',
        'action',
        'reason',
        'duration_minutes',
        'message',
        'platform',
        'metadata',
    ];

    /**
     * การ cast ประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'duration_minutes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ FortuneReading
     *
     * @return BelongsTo
     */
    public function reading(): BelongsTo
    {
        return $this->belongsTo(FortuneReading::class, 'fortune_reading_id');
    }

    /**
     * ความสัมพันธ์กับ User (แอดมินที่กระทำ)
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Label ของ action สำหรับแสดงผล
     */
    public function getActionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_TAKEOVER => '🎯 เทคโอเวอร์',
            self::ACTION_RESUME => '✨ ให้ AI กลับมา',
            self::ACTION_EXTEND => '⏱ ต่อเวลา',
            self::ACTION_MESSAGE => '💬 ส่งข้อความ',
            self::ACTION_AUTO_EXPIRE => '⌛ หมดเวลาอัตโนมัติ',
            default => $this->action,
        };
    }

    /**
     * Label ของ reason สำหรับแสดงผล
     */
    public function getReasonLabel(): string
    {
        return match ($this->reason) {
            FortuneReading::TAKEOVER_REASON_MANUAL => 'กดเทคโอเวอร์เอง',
            FortuneReading::TAKEOVER_REASON_AUTO_REPLY => 'แอดมินพิมพ์ในช่องสนทนา',
            FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST => 'ลูกค้าขอคุยกับคน',
            default => $this->reason ?? '-',
        };
    }
}
