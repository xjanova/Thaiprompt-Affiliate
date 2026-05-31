<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SlipVerification Model
 *
 * เก็บผลการตรวจสลิปจาก SlipOK — ใช้ dedup (trans_ref unique) + audit trail
 *
 * @property int $id
 * @property string $trans_ref เลขอ้างอิงรายการ (unique)
 * @property int|null $fortune_reading_id บิลที่จับคู่
 * @property float|null $amount ยอดเงินจริงจากสลิป
 * @property string|null $sending_bank ธนาคารต้นทาง (3-digit code)
 * @property string|null $receiving_bank ธนาคารปลายทาง
 * @property string|null $receiver_account บัญชีปลายทาง (masked)
 * @property string|null $sender_name ชื่อผู้โอน
 * @property string $status verified/rejected/duplicate
 * @property array|null $raw response ดิบจาก SlipOK
 * @property \Carbon\Carbon|null $verified_at
 */
class SlipVerification extends Model
{
    protected $table = 'slip_verifications';

    protected $fillable = [
        'trans_ref',
        'fortune_reading_id',
        'amount',
        'sending_bank',
        'receiving_bank',
        'receiver_account',
        'sender_name',
        'status',
        'raw',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'raw' => 'array',
        'verified_at' => 'datetime',
    ];

    /**
     * บิลดูดวงที่จับคู่กับสลิปนี้
     */
    public function reading(): BelongsTo
    {
        return $this->belongsTo(FortuneReading::class, 'fortune_reading_id');
    }
}
