<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SlipVerificationLog — ประวัติการตรวจสลิป "ทุกครั้ง" (audit)
 *
 * บันทึกทุกการตรวจสลิป (สำเร็จ/ล้มเหลว/ซ้ำ/ไม่มีบิล/pre-check ตัด) ให้แอดมินเห็น:
 * ส่งไป SlipOK จริงไหม / สลิปซ้ำไหม / บิลไหน
 *
 * @property int $id
 * @property int|null $fortune_reading_id บิลที่ตรวจ
 * @property string|null $platform facebook/line
 * @property string|null $chat_user_id chat user id
 * @property string|null $context บริบท (returning/no_bill/active_fallback)
 * @property bool $sent_to_slipok ยิง SlipOK จริงไหม
 * @property string|null $decision ผลการตัดสิน
 * @property bool $is_duplicate สลิปซ้ำ
 * @property bool|null $to_our_account เข้าบัญชีร้านไหม
 * @property string|null $trans_ref เลขอ้างอิง
 * @property float|null $amount ยอด
 * @property string|null $sender_name ชื่อผู้โอน
 * @property string|null $receiver_account บัญชีปลายทาง
 * @property string|null $note หมายเหตุ
 */
class SlipVerificationLog extends Model
{
    protected $table = 'slip_verification_logs';

    protected $fillable = [
        'fortune_reading_id',
        'platform',
        'chat_user_id',
        'context',
        'sent_to_slipok',
        'decision',
        'is_duplicate',
        'to_our_account',
        'trans_ref',
        'amount',
        'sender_name',
        'receiver_account',
        'note',
    ];

    protected $casts = [
        'sent_to_slipok' => 'boolean',
        'is_duplicate' => 'boolean',
        'to_our_account' => 'boolean',
        'amount' => 'decimal:2',
    ];

    /**
     * บิลดูดวงที่จับคู่กับการตรวจนี้
     */
    public function reading(): BelongsTo
    {
        return $this->belongsTo(FortuneReading::class, 'fortune_reading_id');
    }

    /**
     * ป้ายสีตามผล (ใช้ใน admin UI)
     */
    public function decisionBadge(): string
    {
        return match ($this->decision) {
            'approve' => 'green',
            'duplicate' => 'red',
            'reject_receiver', 'reject_amount', 'stale', 'error' => 'orange',
            'no_qr', 'not_slip' => 'gray',
            default => str_starts_with((string) $this->decision, 'no_bill') ? 'blue' : 'gray',
        };
    }
}
