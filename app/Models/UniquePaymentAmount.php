<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Unique Payment Amount Model
 *
 * จัดการจำนวนเงินเฉพาะสำหรับจับคู่ SMS payment
 * เพิ่มทศนิยม (0.01-0.99) เข้าไปในราคาเดิมเพื่อแยกแยะ transactions
 *
 * @property int $id
 * @property float $base_amount ราคาเดิม (จำนวนเต็ม)
 * @property float $unique_amount ราคาเฉพาะ (base + suffix/100)
 * @property int $decimal_suffix ส่วนทศนิยม (01-99)
 * @property int|null $transaction_id ID ของ transaction ที่เกี่ยวข้อง
 * @property string|null $transaction_type ประเภท transaction (order, topup, ...)
 * @property string $status สถานะ (reserved/used/expired/cancelled)
 * @property \Carbon\Carbon $expires_at เวลาหมดอายุ
 * @property \Carbon\Carbon|null $matched_at เวลาที่จับคู่สำเร็จ
 */
class UniquePaymentAmount extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_amount',
        'unique_amount',
        'decimal_suffix',
        'transaction_id',
        'transaction_type',
        'status',
        'expires_at',
        'matched_at',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'unique_amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'matched_at' => 'datetime',
    ];

    // ความสัมพันธ์

    /**
     * Payment Transaction ที่เชื่อมโยง
     */
    public function transaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'transaction_id');
    }

    // Scopes

    /**
     * กรองเฉพาะที่ reserved อยู่
     */
    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved');
    }

    /**
     * กรองเฉพาะที่ reserved และยังไม่หมดอายุ
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'reserved')
            ->where('expires_at', '>', now());
    }

    /**
     * กรองเฉพาะที่หมดอายุแล้ว
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'reserved')
            ->where('expires_at', '<=', now());
    }

    /**
     * สร้าง unique amount สำหรับราคาสินค้า
     *
     * เพิ่มส่วนทศนิยม (0.01 - 0.99) เพื่อแยกแยะ transactions ที่มีราคาเดียวกัน
     * ⚠️ base_amount จะถูกปัดเศษเป็นจำนวนเต็มก่อนเพิ่ม suffix
     * เช่น base_amount = 500 + suffix 37 = 500.37
     *
     * @param  float  $baseAmount  ราคาสินค้าเดิม (จำนวนเต็ม)
     * @param  int|null  $transactionId  ID ของ transaction ที่เกี่ยวข้อง
     * @param  string  $transactionType  ประเภท transaction
     * @param  int  $expiryMinutes  เวลาหมดอายุของ reservation (นาที)
     */
    public static function generate(
        float $baseAmount,
        ?int $transactionId = null,
        string $transactionType = 'order',
        ?int $expiryMinutes = null
    ): ?self {
        $expiryMinutes = $expiryMinutes ?? config('smschecker.unique_amount_expiry', 30);
        $maxPending = config('smschecker.max_pending_per_amount', 99);

        // ใช้ DB transaction + pessimistic lock ป้องกัน race condition
        return \Illuminate\Support\Facades\DB::transaction(function () use (
            $baseAmount, $transactionId, $transactionType, $expiryMinutes, $maxPending
        ) {
            // 🔧 (2026-05-21) Mark expired แทน DELETE — กัน orphan FK ใน fortune_readings
            //   เคสบั๊ก (Bill FTU-260521-F3826):
            //     1. ลูกค้าสร้างบิล 39.80 → UPA #777 reserved
            //     2. ลูกค้าโอนช้ากว่า 30 นาที (UPA expired)
            //     3. คนอื่นสร้างบิลใหม่ → generate() ลบ UPA #777
            //     4. SMS ของลูกค้ามาถึง (ใน grace 60 นาที) → findMatch() Path 2
            //        หา UPA reserved/expired ไม่เจอ (#777 ถูกลบไปแล้ว)
            //     5. บิลค้าง pending_payment ตลอดกาล — admin force fix ผ่าน tinker
            //   Migration 2026_02_05_180500 ลบ unique constraint บน (base_amount,
            //   decimal_suffix, status) ไปแล้ว → update status='expired' ปลอดภัย
            //   findMatch() Path 2 (whereIn['reserved','expired']) จะหา UPA เจอ
            static::where('status', 'reserved')
                ->where('expires_at', '<=', now())
                ->update(['status' => 'expired']);

            // ลบ expired records เก่าๆ เฉพาะที่ไม่ถูกอ้างอิงใน fortune_readings
            // (เก่ากว่า 7 วัน + ไม่มี orphan FK risk)
            //   ⚠️ ก่อนหน้านี้ใช้ 1 วัน — ผ่อนเป็น 7 วัน เพื่อให้ grace recovery + admin review ทัน
            $candidateIds = static::where('status', 'expired')
                ->where('updated_at', '<', now()->subDays(7))
                ->pluck('id')
                ->toArray();
            if (! empty($candidateIds)) {
                // กรองออก ids ที่ยังถูกอ้างอิงใน fortune_readings (กัน orphan FK)
                $stillReferenced = \App\Models\FortuneReading::whereIn('unique_payment_amount_id', $candidateIds)
                    ->pluck('unique_payment_amount_id')
                    ->unique()
                    ->toArray();
                $safeToDelete = array_diff($candidateIds, $stillReferenced);
                if (! empty($safeToDelete)) {
                    static::whereIn('id', $safeToDelete)->delete();
                }
            }

            // ปัดเศษ base_amount เป็นจำนวนเต็มเพื่อให้ทศนิยมใช้เป็น suffix เท่านั้น
            $intBaseAmount = intval($baseAmount);

            // ค้นหา suffix ที่ยังว่างอยู่ (01-99) พร้อม lockForUpdate ป้องกัน race condition
            $usedSuffixes = static::where('base_amount', $intBaseAmount)
                ->where('status', 'reserved')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->pluck('decimal_suffix')
                ->toArray();

            // สร้างรายการ suffix ที่ยังไม่ถูกใช้
            $availableSuffixes = array_diff(range(1, min($maxPending, 99)), $usedSuffixes);

            if (empty($availableSuffixes)) {
                // suffix เต็มหมดแล้วสำหรับราคานี้
                return null;
            }

            // สุ่มเลือก suffix ที่ยังว่าง (ใช้ cryptographic random)
            $availableValues = array_values($availableSuffixes);
            $suffix = $availableValues[random_int(0, count($availableValues) - 1)];
            $uniqueAmount = $intBaseAmount + ($suffix / 100);

            return static::create([
                'base_amount' => $intBaseAmount,
                'unique_amount' => $uniqueAmount,
                'decimal_suffix' => $suffix,
                'transaction_id' => $transactionId,
                'transaction_type' => $transactionType,
                'status' => 'reserved',
                'expires_at' => now()->addMinutes($expiryMinutes),
            ]);
        });
    }

    /**
     * ค้นหา unique amount ที่ตรงกับจำนวนเงินที่ได้รับ
     *
     * สามารถกรองตาม transaction_type เพื่อแยกบิลระหว่างระบบ:
     * - 'fortune_reading' = บิลดูดวง
     * - 'tarot_reading' = บิลไพ่ทาโร่
     * - 'order' / 'order_payment' = บิลอีคอมเมิร์ซ
     *
     * 🔒 SECURITY (2026-04-28): Temporal binding + FIFO
     *   ป้องกันบั๊กที่ SMS เก่ามา match กับบิลใหม่ที่ลูกค้ายังไม่ได้โอน
     *   - $smsTimestamp ทำให้ match เฉพาะ UPA ที่สร้าง *ก่อน* SMS arrived
     *   - FIFO order ป้องกัน race เมื่อมี UPA หลายตัวยอดเดียวกัน (pick เก่าสุด)
     *
     * @param  float  $amount  จำนวนเงินที่ได้รับ
     * @param  string|array|null  $transactionType  กรองตามประเภท (null = ไม่กรอง, ใช้แบบเดิม)
     * @param  \Carbon\Carbon|null  $smsTimestamp  เวลาที่ SMS เข้า (เพื่อกัน SMS ก่อน bill)
     */
    public static function findMatch(
        float $amount,
        string|array|null $transactionType = null,
        ?\Carbon\Carbon $smsTimestamp = null
    ): ?self {
        $query = static::where('unique_amount', $amount)
            ->where('status', 'reserved')
            ->where('expires_at', '>', now());

        // กรองตาม transaction_type เพื่อแยกบิลไม่ปะปนกัน
        if ($transactionType !== null) {
            if (is_array($transactionType)) {
                $query->whereIn('transaction_type', $transactionType);
            } else {
                $query->where('transaction_type', $transactionType);
            }
        }

        // 🔒 SMS ต้องมาหลัง bill ถูกสร้าง — ไม่งั้นเป็นบั๊ก/ฉ้อโกง
        // เคสที่กัน: Bill #A 39.47 paid → Bill #B 39.47 รอ → SMS เก่าของ A reprocess
        // ผลก่อนแก้: B ได้ตัดผิด, ผลหลังแก้: skip B (เพราะ A.created_at < SMS < B.created_at ไม่จริง)
        if ($smsTimestamp !== null) {
            $query->where('created_at', '<=', $smsTimestamp);
        }

        // FIFO — บิลที่สร้างเก่าสุด (แต่ยัง reserved) match ก่อน
        // กัน race condition เมื่อ generate() reuse suffix หลัง bill เก่า used แล้ว
        return $query->orderBy('created_at', 'asc')->lockForUpdate()->first();
    }

    /**
     * ยกเลิก unique amount (ปลดปล่อย suffix ให้ใช้ซ้ำได้)
     *
     * ใช้เมื่อ admin/Android app ปฏิเสธบิล หรือผู้ใช้ยกเลิก
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
