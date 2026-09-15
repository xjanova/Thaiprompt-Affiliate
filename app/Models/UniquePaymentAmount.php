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
 * @property string|null $external_ref (juntraweb_topup) reference_code ฝั่งจันทรา.online เช่น TUP-AB12CD34
 * @property int|null $external_id (juntraweb_topup) wallet_transactions.id ฝั่งจันทรา.online
 */
class UniquePaymentAmount extends Model
{
    /**
     * ⏳ (2026-08-07) ห้ามสุ่มทศนิยมซ้ำกับบิลที่ยังไม่ได้จ่าย ภายในกี่นาที
     *
     * ตั้ง 24 ชม. เพราะลูกค้าโอนช้าข้ามวันมีจริง — ถ้า suffix ถูกเวียนก่อนหน้านั้น
     * เงินของคนแรกจะวิ่งเข้าบิลของคนที่สอง
     */
    public const SUFFIX_COOLDOWN_MINUTES = 1440;

    /**
     * 🌙 (2026-09-15) ยอดที่จองให้เว็บ จันทรา.online (juntraweb) — Thaiprompt เป็นผู้จองยอดให้ทั้งสองเว็บ
     *    เพราะบัญชีรับเงินเดียวกัน + มือถือ SMS เครื่องเดียวกัน → ยอดทศนิยมห้ามชนกันข้ามเว็บ
     *
     * ⚠️ แถวประเภทนี้ "ไม่ใช่บิลของ Thaiprompt" — ห้ามทุกเส้นจับคู่ SMS ของเราหยิบไปตัดบิล
     *    (transaction_id = null เสมอ; id ฝั่ง juntraweb อยู่ที่ external_id / external_ref)
     */
    public const TYPE_JUNTRAWEB_TOPUP = 'juntraweb_topup';

    /** ชื่อเว็บที่ส่งกลับให้แอพ SmsChecker ใช้ระบุว่าเงินก้อนนี้เป็นของเว็บไหน */
    public const EXTERNAL_SITE_JUNTRAWEB = 'จันทรา.online';

    /**
     * ⏳ ยอดของจันทรายัง "เป็นของจันทรา" ต่ออีกกี่นาทีหลังหมดอายุ/ปิด
     *   ช่วงนี้ SMS ยอดนี้ = เงินของจันทรา (ไม่จับคู่บิลเรา ไม่เตือนเงินกำพร้า)
     *   และตัวจองยอดของเราก็ห้ามแจกยอดนี้ให้บิลเราในช่วงเดียวกัน → ไม่มีวันชนกัน
     */
    public const EXTERNAL_CLAIM_WINDOW_MINUTES = 1440;

    /** เผื่อนาฬิกามือถือช้ากว่าเซิร์ฟเวอร์ (SMS ต้องมาหลังจองยอด แต่ยอมคลาดได้เท่านี้) */
    public const EXTERNAL_CLOCK_SKEW_MINUTES = 5;

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
        'external_ref',
        'external_id',
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
     * 🌙 ยอดของจันทรา.online ที่ยัง "อ้างสิทธิ์" ได้ — ยังจองอยู่ หรือเพิ่งหมดอายุ/ปิดไม่เกิน 24 ชม.
     *
     * ใช้ร่วมกันสองที่ (ต้องเป็นเงื่อนไขเดียวกันเสมอ):
     *   1. generate() — ห้ามแจกยอดนี้ให้บิลของ Thaiprompt
     *   2. findJuntrawebClaim() — SMS ยอดนี้เป็นเงินของจันทรา ไม่ใช่ของเรา
     *   ⇒ เมื่อ (2) บอกว่าเป็นของจันทรา จะไม่มีบิล Thaiprompt ที่จองยอดเดียวกันไว้หลังจากนั้นเลย
     */
    public function scopeJuntrawebClaimWindow($query)
    {
        $since = now()->subMinutes(self::EXTERNAL_CLAIM_WINDOW_MINUTES);

        return $query->where('transaction_type', self::TYPE_JUNTRAWEB_TOPUP)
            ->where(function ($q) use ($since) {
                $q->where('expires_at', '>', $since)
                    ->orWhere('updated_at', '>', $since);
            });
    }

    /**
     * 🌙 ยอดนี้เป็นเงินของเว็บ จันทรา.online หรือเปล่า
     *
     * @param  \Carbon\Carbon|null  $smsTimestamp  เวลาใน SMS — ต้องมาหลังจองยอด (เผื่อนาฬิกาคลาด)
     *                                             null = ไม่รู้เวลา (เช่น /orders/match) → ไม่เช็คเวลา
     */
    public static function findJuntrawebClaim(float $amount, ?\Carbon\Carbon $smsTimestamp = null): ?self
    {
        if ($amount <= 0) {
            return null;
        }

        try {
            $query = static::query()
                ->juntrawebClaimWindow()
                ->whereBetween('unique_amount', [$amount - 0.001, $amount + 0.001]);

            // SMS ที่มาก่อนจองยอด = ไม่ใช่เงินของรายการนี้ (เช่น SMS เก่าถูกส่งซ้ำ)
            if ($smsTimestamp !== null) {
                $query->where('created_at', '<=', $smsTimestamp->copy()->addMinutes(self::EXTERNAL_CLOCK_SKEW_MINUTES));
            }

            return $query->orderByDesc('created_at')->first();
        } catch (\Throwable $e) {
            // ตารางยังไม่ migrate คอลัมน์ใหม่ ฯลฯ — ห้ามทำให้การรับ SMS ของเราพัง
            \Illuminate\Support\Facades\Log::warning('UPA: เช็คยอดของจันทรา.online ไม่สำเร็จ (non-blocking)', [
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
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
     * @param  int|null  $minSuffix  ทศนิยมขั้นต่ำ (1-99) — บิล top-up "โอนขาด" ต้องการยอดที่
     *                               *ไม่ต่ำกว่าส่วนที่ขาดจริง* เช่น ขาด ฿38.10 → base 38 + suffix ≥ 10
     *                               (null = พูลเต็ม 01-99 เหมือนเดิม — ทุก caller เดิมไม่เปลี่ยนพฤติกรรม)
     * @param  array  $extraAttributes  (2026-09-15) ฟิลด์เพิ่มตอนสร้างแถว เช่น external_ref/external_id
     *                                  ของยอดที่จองให้จันทรา.online (ว่าง = เหมือนเดิมทุกประการ)
     */
    public static function generate(
        float $baseAmount,
        ?int $transactionId = null,
        string $transactionType = 'order',
        ?int $expiryMinutes = null,
        ?int $minSuffix = null,
        array $extraAttributes = []
    ): ?self {
        $expiryMinutes = $expiryMinutes ?? config('smschecker.unique_amount_expiry', 30);
        $maxPending = config('smschecker.max_pending_per_amount', 99);

        // ใช้ DB transaction + pessimistic lock ป้องกัน race condition
        return \Illuminate\Support\Facades\DB::transaction(function () use (
            $baseAmount, $transactionId, $transactionType, $expiryMinutes, $maxPending, $minSuffix, $extraAttributes
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
            $activeSuffixes = static::where('base_amount', $intBaseAmount)
                ->where('status', 'reserved')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->pluck('decimal_suffix')
                ->toArray();

            // 🛡️ (2026-08-07, เจ้าของเสนอ) ห้ามสุ่มทศนิยมซ้ำกับบิลที่ "ยังไม่ได้จ่าย" ภายใน 24 ชม.
            //
            //   กันปัญหาที่ต้นทาง ดีกว่าไปเดาปลายทาง: เดิมกันเฉพาะ suffix ที่ยัง reserved และ
            //   ไม่หมดอายุ → ยอด 99.36 ของบิลที่เพิ่งยกเลิก ถูกจ่ายให้บิลใหม่ได้ทันที
            //   ถ้าลูกค้าคนแรกโอนช้ามาทีหลัง เงินจะวิ่งเข้าบิลของ "คนที่สอง" = ตัดเงินผิดคน
            //
            //   status != 'used' = ทุกสถานะที่ยังมีสิทธิ์รับเงินโอนตามหลัง
            //   (reserved / expired / cancelled) — ตรงกับคำว่า "บิลที่ยังไม่ได้จ่าย"
            //
            //   ⚖️ ปลอดภัยเรื่องพูล: prod สร้างจริงสูงสุด ~11 ใบ/วัน/ราคา จากพูล 99
            //      กันไว้ 24 ชม. ใช้ราว 11% เท่านั้น
            $recentUnpaidSuffixes = static::where('base_amount', $intBaseAmount)
                ->where('created_at', '>=', now()->subMinutes(self::SUFFIX_COOLDOWN_MINUTES))
                ->where('status', '!=', 'used')
                ->pluck('decimal_suffix')
                ->toArray();

            // 🌙 (2026-09-15) ยอดของจันทรา.online ที่ยังอ้างสิทธิ์ได้ = ห้ามแจกซ้ำ "เด็ดขาด"
            //   อยู่ในชุดเดียวกับ active (ด่านถอยกลับข้างล่างก็ยังกัน) — ถ้าหลุดไปแจกให้บิลเรา
            //   SMS ของลูกค้าเราจะถูกมองเป็นเงินของจันทรา (findJuntrawebClaim) แล้วไม่ตัดบิล
            //   ไม่มีแถวประเภทนี้ = อาร์เรย์ว่าง = พฤติกรรมเดิมทุกประการ
            $externalSuffixes = static::where('base_amount', $intBaseAmount)
                ->juntrawebClaimWindow()
                ->pluck('decimal_suffix')
                ->toArray();
            $hardBlockedSuffixes = array_unique(array_merge($activeSuffixes, $externalSuffixes));

            // 🌙 จองให้จันทรา → เอาเฉพาะทศนิยมที่ "ไม่มีใครแตะเลย 24 ชม." (ทุกสถานะ รวมที่จ่ายแล้ว)
            //   กันเงินของบิลเราที่ SMS มาช้า/ส่งซ้ำ ถูกมองเป็นของจันทรา — จันทราไม่มีด่านถอยกลับ
            //   (พูลหมดก็ตอบ pool_exhausted ให้เว็บจันทราจัดการเอง ดีกว่าแจกยอดที่อาจชนบิลเรา)
            $isJuntraweb = $transactionType === self::TYPE_JUNTRAWEB_TOPUP;
            if ($isJuntraweb) {
                $since = now()->subMinutes(self::SUFFIX_COOLDOWN_MINUTES);
                $recentAnySuffixes = static::where('base_amount', $intBaseAmount)
                    ->where(function ($q) use ($since) {
                        $q->where('created_at', '>=', $since)
                            ->orWhere('updated_at', '>=', $since)
                            ->orWhere('expires_at', '>=', $since);
                    })
                    ->pluck('decimal_suffix')
                    ->toArray();
                $recentUnpaidSuffixes = array_merge($recentUnpaidSuffixes, $recentAnySuffixes);
            }

            // 💰 (2026-08-29) พื้นทศนิยม — บิล top-up "โอนขาด" ส่งค่ามา เพื่อให้ยอดที่ขอ
            //    *ไม่ต่ำกว่าส่วนที่ขาดจริง* (ขาด ฿38.10 → base 38 ต้องได้ suffix ≥ 10 = ฿38.10-38.99)
            //    ถ้าไม่มีพื้น ระบบจะออกยอด ฿38.03 = น้อยกว่าที่ขาด → ลูกค้าโอนตามแล้วยังไม่ครบ วนอีกรอบ
            $suffixFloor = max(1, min(99, (int) ($minSuffix ?? 1)));
            $suffixTop = min($maxPending, 99);

            // 🛡️ พื้นสูงกว่าเพดาน (config max_pending ถูกหรี่ลง) → ไม่มี suffix ที่ใช้ได้เลย
            //    ⚠️ ห้ามปล่อยให้ range(80, 50) ทำงาน — PHP คืนอาเรย์ "ย้อนกลับ" [80..50]
            //    = หลุดไปใช้ทศนิยมต่ำกว่าพื้น (ออกยอดน้อยกว่าที่ขาดจริง) โดยไม่มีใครรู้
            if ($suffixFloor > $suffixTop) {
                return null;
            }

            $pool = range($suffixFloor, $suffixTop);
            $availableSuffixes = array_diff($pool, array_unique(array_merge($hardBlockedSuffixes, $recentUnpaidSuffixes)));

            // 🚨 Safety valve — ถ้ากฎ 24 ชม. กันจนไม่เหลือ suffix เลย ห้ามทำให้ "สร้างบิลไม่ได้"
            //    ยอมถอยกลับไปใช้กฎเดิม (กันเฉพาะที่ยัง active) ดีกว่าปิดการขายทั้งราคา
            //    ถ้าเห็น log นี้บ่อย = ยอดขายโตจนพูล 99 ไม่พอ ต้องขยายช่วงราคา/ทศนิยม
            //    🌙 ยอดของจันทรายังกันอยู่ในด่านนี้ ($hardBlockedSuffixes) · การจองให้จันทราไม่มีด่านถอยกลับ
            if (empty($availableSuffixes) && ! $isJuntraweb) {
                $availableSuffixes = array_diff($pool, $hardBlockedSuffixes);

                \Illuminate\Support\Facades\Log::warning('⚠️ UPA: กฎกันซ้ำ 24 ชม. ทำให้ suffix หมด — ถอยไปใช้กฎเดิม', [
                    'base_amount' => $intBaseAmount,
                    'suffix_floor' => $suffixFloor,
                    'active' => count($activeSuffixes),
                    'recent_unpaid' => count($recentUnpaidSuffixes),
                ]);
            }

            if (empty($availableSuffixes)) {
                // suffix เต็มหมดแล้วสำหรับราคานี้
                //   มีพื้นทศนิยม → คืน null ให้ caller ถอยเอง (ห้ามหลุดไปใช้ suffix ต่ำกว่าพื้น
                //   = ออกยอดน้อยกว่าที่ขาดจริง แล้วไปทวงลูกค้าซ้ำรอบถัดไป)
                return null;
            }

            $availableValues = array_values($availableSuffixes);

            // 🎯 (2026-09-02, บิล FTU-260902-E0187) บิล top-up "โอนขาด" → เอาทศนิยมที่ "ขาดจริง" ก่อนเสมอ
            //
            //   เคสจริง: ขาด ฿32.38 → พื้น suffix=38 แต่ random_int สุ่มได้ 52 → ระบบรอ ฿32.52
            //   ขณะที่กล่องข้อความบอกลูกค้าเองว่า "ขาดอีก ฿32.38" → ลูกค้าโอน 32.38 ตามที่อ่าน
            //   → findMatch หา 32.52 ไม่เจอ → SMS matched:false → เงินตกร่องเงียบ ลูกค้าถูกทวงซ้ำ
            //   (66.62 + 32.38 = 99.00 พอดีเป๊ะ — ลูกค้าพูดถูก "มันเกิน99นะคะ" แต่บอทไม่รู้)
            //
            //   สุ่มมีประโยชน์แค่ตอน "กระจายบิลราคาเดียวกัน" — บิลโอนขาดไม่ต้องการความสุ่ม
            //   ต้องการ "เลขเดียวกับที่พูด" ([[rule_partial_payment_ask_must_equal_stated_shortfall]])
            //   ⚠️ ใช้เฉพาะเมื่อ caller ส่ง $minSuffix มา — บิลปกติ ($minSuffix = null → floor 1)
            //      ต้องสุ่มเหมือนเดิม ไม่งั้นทุกบิลจะได้ .01 เหมือนกันหมด
            if ($minSuffix !== null && in_array($suffixFloor, $availableValues, true)) {
                $suffix = $suffixFloor;
            } else {
                // สุ่มเลือก suffix ที่ยังว่าง (ใช้ cryptographic random)
                $suffix = $availableValues[random_int(0, count($availableValues) - 1)];
            }
            $uniqueAmount = $intBaseAmount + ($suffix / 100);

            return static::create(array_merge($extraAttributes, [
                'base_amount' => $intBaseAmount,
                'unique_amount' => $uniqueAmount,
                'decimal_suffix' => $suffix,
                'transaction_id' => $transactionId,
                'transaction_type' => $transactionType,
                'status' => 'reserved',
                'expires_at' => now()->addMinutes($expiryMinutes),
            ]));
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
        } else {
            // 🌙 (2026-09-15) ไม่ระบุประเภท = บิลของ Thaiprompt ทุกประเภท — แต่ไม่ใช่ยอดของจันทรา.online
            $query->where(function ($q) {
                $q->whereNull('transaction_type')
                    ->orWhere('transaction_type', '!=', self::TYPE_JUNTRAWEB_TOPUP);
            });
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
