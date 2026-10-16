<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ใบเสนอยอดชำระด้วยเหรียญ TPIX (1 ใบ = จ่าย 1 ครั้ง)
 *
 * ยอดที่ต้องโอน = amount_wei ตรงทุกหลัก (มีเลขท้ายเฉพาะใบ) → จับคู่ธุรกรรมกับใบได้แน่นอน
 * สถานะ:
 *  - pending          รอโอน
 *  - confirming       เจอธุรกรรมแล้ว รอ confirmation ครบ
 *  - paid             ได้รับเงิน + ส่งมอบแล้ว (ออเดอร์จ่ายแล้ว / เติมวอลเลตแล้ว)
 *  - credited_wallet  ได้รับเงินแต่ออเดอร์ปิดไปแล้ว → เติมมูลค่าเป็นบาทเข้ากระเป๋าแทน (เงินไม่หาย)
 *  - late             โอนหลังใบหมดอายุ → รอแอดมินตัดสิน (ไม่ส่งมอบอัตโนมัติ)
 *  - expired          หมดเวลา ไม่มีการโอน
 *  - cancelled        ยกเลิก (เช่น ออเดอร์จ่ายด้วยช่องทางอื่นแล้ว)
 *  - failed           ธุรกรรมที่ส่งมาไม่ผ่าน (รายละเอียดใน failure_reason)
 *
 * @property int $id
 * @property string $reference
 * @property int|null $user_id
 * @property int|null $payment_transaction_id
 * @property string $purpose
 * @property string $amount_thb
 * @property string $rate_thb
 * @property string $amount_wei
 * @property string $amount_tpix
 * @property string $receive_address
 * @property string $status
 * @property string|null $tx_hash
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $paid_at
 */
class TpixPayment extends Model
{
    public const PURPOSE_ORDER = 'order';

    public const PURPOSE_WALLET_TOPUP = 'wallet_topup';

    /**
     * จ่ายตะกร้าตลาดสดล่วงหน้า: ได้เหรียญ → เติมบาทเข้ากระเป๋า → สั่งซื้อผ่านเส้นกระเป๋าเงินเดิม (escrow ฯลฯ)
     * meta.checkout = ข้อมูลสั่งซื้อที่ตรวจแล้ว · meta.fulfilment = pending|placed|failed + ผลลัพธ์
     */
    public const PURPOSE_FRESH_MARKET = 'fresh_market';

    /** จ่ายการจองบริการ: ได้เหรียญ → เติมบาทเข้ากระเป๋า → ชำระการจองจากกระเป๋า (payable_id = service_bookings.id) */
    public const PURPOSE_SERVICE_BOOKING = 'service_booking';

    /** ใบที่ส่งมอบด้วย "เติมกระเป๋าแล้วสั่ง/จ่ายต่อ" (ผลการสั่งเก็บใน meta.fulfilment) */
    public const CHECKOUT_PURPOSES = [self::PURPOSE_FRESH_MARKET, self::PURPOSE_SERVICE_BOOKING];

    public const FULFIL_PENDING = 'pending';

    public const FULFIL_PLACED = 'placed';

    public const FULFIL_FAILED = 'failed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMING = 'confirming';

    public const STATUS_PAID = 'paid';

    public const STATUS_CREDITED_WALLET = 'credited_wallet';

    public const STATUS_LATE = 'late';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    /** สถานะที่ยังรับเงินได้ (ระบบสแกนหา/ตรวจธุรกรรม) */
    public const OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_CONFIRMING, self::STATUS_FAILED];

    /** สถานะที่ได้รับเงินแล้ว */
    public const SETTLED_STATUSES = [self::STATUS_PAID, self::STATUS_CREDITED_WALLET];

    protected $fillable = [
        'reference',
        'user_id',
        'payment_transaction_id',
        'purpose',
        'payable_type',
        'payable_id',
        'amount_thb',
        'rate_thb',
        'price_usd',
        'usd_thb',
        'amount_wei',
        'amount_tpix',
        'receive_address',
        'status',
        'open_key',
        'tx_hash',
        'from_address',
        'block_number',
        'confirmations',
        'submitted_at',
        'expires_at',
        'paid_at',
        'failure_reason',
        'meta',
    ];

    protected $casts = [
        'amount_thb' => 'decimal:2',
        'meta' => 'array',
        'block_number' => 'integer',
        'confirmations' => 'integer',
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    /**
     * ออเดอร์ร้านค้าที่ใบนี้จ่าย (เฉพาะ purpose=order)
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'payable_id');
    }

    public function isSettled(): bool
    {
        return in_array($this->status, self::SETTLED_STATUSES, true);
    }

    /** ใบจ่ายตลาดสด/จองบริการ (เติมกระเป๋าแล้วสั่งต่อ) */
    public function isCheckoutPurpose(): bool
    {
        return in_array($this->purpose, self::CHECKOUT_PURPOSES, true);
    }

    /** สถานะการสั่งซื้อ/ชำระต่อหลังได้เหรียญ (null = ไม่ใช่ใบแบบนี้ หรือยังไม่ได้เงิน) */
    public function fulfilmentStatus(): ?string
    {
        $status = $this->meta['fulfilment']['status'] ?? null;

        return is_string($status) ? $status : null;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    /** หมดเวลาโอนแล้วหรือยัง (ยังรับเงินที่โอน "ก่อน" หมดเวลาได้ ถึงจะตรวจเจอทีหลัง) */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * open_key ของยอดนี้ (กระเป๋า + ยอด wei) — ซ้ำกับใบที่ยังเปิดไม่ได้
     */
    public static function openKeyFor(string $address, string $amountWei): string
    {
        return strtolower($address).':'.$amountWei;
    }

    /**
     * ยอดเหรียญสำหรับแสดงผล (ตัดศูนย์ท้าย คงทศนิยมอย่างน้อย 2 ตำแหน่ง)
     */
    public function amountTpixDisplay(): string
    {
        return \App\Services\TpixPay\TpixAmount::display((string) $this->amount_tpix, 8);
    }

    /**
     * ลิงก์ EIP-681 สำหรับ QR (กระเป๋ามือถือเปิดแล้วกรอกยอดให้เอง)
     */
    public function eip681Uri(): string
    {
        return 'ethereum:'.$this->receive_address.'@'.config('tpix_pay.chain_id').'?value='.$this->amount_wei;
    }

    public function explorerTxUrl(): ?string
    {
        return $this->tx_hash ? rtrim((string) config('tpix_pay.explorer_url'), '/').'/tx/'.$this->tx_hash : null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอโอนเหรียญ',
            self::STATUS_CONFIRMING => 'กำลังยืนยันบนเชน',
            self::STATUS_PAID => 'ชำระแล้ว',
            self::STATUS_CREDITED_WALLET => 'เติมเข้ากระเป๋าเงินแล้ว',
            self::STATUS_LATE => 'โอนหลังหมดเวลา — รอแอดมินตรวจ',
            self::STATUS_EXPIRED => 'หมดเวลา',
            self::STATUS_CANCELLED => 'ยกเลิก',
            self::STATUS_FAILED => 'ตรวจธุรกรรมไม่ผ่าน',
            default => $this->status,
        };
    }
}
