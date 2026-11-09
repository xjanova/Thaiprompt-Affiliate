<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 🧾 สลิปจากแชทเพจที่พักไว้รอเติมเงินเว็บจันทรา (2026-11-10)
 *
 * @property int $id
 * @property string $trans_ref เลขอ้างอิงธนาคาร (unique)
 * @property string $platform facebook / line
 * @property string $platform_user_id PSID / LINE user id ของลูกค้า
 * @property string $amount ยอดในสลิป
 * @property string $status parked | credited | review | duplicate | no_user
 * @property string|null $juntra_user_ref id ผู้ใช้ฝั่งเว็บจันทรา
 * @property string|null $topup_ref เลขบิลเติมเงินฝั่งเว็บจันทรา
 * @property string|null $credited_amount ยอดที่เว็บเติมให้จริง
 */
class JuntraChatSlip extends Model
{
    public const PARKED = 'parked';

    public const CREDITED = 'credited';

    public const REVIEW = 'review';

    public const DUPLICATE = 'duplicate';

    public const NO_USER = 'no_user';

    /** ผลที่เว็บจันทรารายงานกลับได้ */
    public const OUTCOMES = [self::CREDITED, self::REVIEW, self::DUPLICATE, self::NO_USER];

    protected $table = 'juntra_chat_slips';

    protected $fillable = [
        'trans_ref',
        'platform',
        'platform_user_id',
        'amount',
        'sender_name',
        'receiver_account',
        'sending_bank',
        'receiving_bank',
        'trans_timestamp',
        'slip_path',
        'status',
        'juntra_user_ref',
        'topup_ref',
        'credited_amount',
        'outcome_note',
        'ping_attempts',
        'resolved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'credited_amount' => 'decimal:2',
        'ping_attempts' => 'integer',
        'resolved_at' => 'datetime',
    ];

    /**
     * ตัวตนของลูกค้าในทะเบียนสลิป (slip_verifications.consumed_by_user_id) ตอนที่ยังพักอยู่
     */
    public static function ownerKey(string $platform, string $platformUserId): string
    {
        return 'chat:'.$platform.':'.$platformUserId;
    }

    public function owner(): string
    {
        return self::ownerKey($this->platform, $this->platform_user_id);
    }

    /**
     * ข้อมูลที่ส่งให้เว็บจันทรา (รูปแบบเดียวกับผล /slips/verify ที่เว็บใช้อยู่)
     */
    public function toJuntraPayload(): array
    {
        return [
            'trans_ref' => $this->trans_ref,
            'platform' => $this->platform,
            'platform_user_id' => $this->platform_user_id,
            'chat_owner' => $this->owner(),
            'status' => $this->status,
            'ok' => true,
            'error_code' => null,
            'amount' => (float) $this->amount,
            'sender_name' => $this->sender_name,
            'receiver_account' => $this->receiver_account,
            'sending_bank' => $this->sending_bank,
            'receiving_bank' => $this->receiving_bank,
            'trans_timestamp' => $this->trans_timestamp,
            // บอทตรวจสองด่านนี้ผ่านแล้วก่อนพัก (ไม่ผ่าน = ไม่พัก)
            'receiver_matches' => true,
            'slip_age_ok' => true,
            'used' => false,
            'used_source' => null,
            'used_by' => null,
            'topup_ref' => $this->topup_ref,
            'credited_amount' => $this->credited_amount !== null ? (float) $this->credited_amount : null,
            'parked_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
