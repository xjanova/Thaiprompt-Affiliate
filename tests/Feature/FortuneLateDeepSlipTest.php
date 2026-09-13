<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\SlipOkService;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 🧾 (2026-09-13) สลิปที่มาหลังบิลดูดวง 39 หมดเวลา — processLateDeepSlip
 *
 * เคสจริง Pantaree Donpar (FB 26273302092329161, FTU-260912-Q1090):
 *   บิล 39.16 หมดเวลา 3 ชม. → ไปโอนที่ร้านตอนเช้า → ส่งรูปสลิป → เดิมถูกเก็บเงียบ ไม่มีใครตรวจ
 *
 * สิ่งที่ห้ามหลุด (จากรีวิวก่อนพุช 2 รอบ):
 *   1. ต้อง "ตัดบิลเดิม" ไม่เปิดแถวใหม่ — และถ้ามีหลายใบ ต้องเป็นใบที่ยอดทศนิยมตรงสลิป
 *      (ใบเดียวกับที่ SMS จะปลุก) ไม่งั้นโอนครั้งเดียวได้ 2 ใบ
 *   2. SMS ตัดบิลเดิมไปแล้ว/กำลังตัด → ไม่ตัดซ้ำ + ไม่ส่งข้อความว่าง ("ระบบกำลังดำเนินการ")
 *   3. รูปที่ส่งมาเฉย ๆ: ต้องถอด QR สลิปได้เอง (ไม่ใช้ vision) · ไม่ชัด/ซ้ำ/อ่านไม่ได้ = เงียบ · ไม่แตะด่าน flood
 *      แต่สลิปจริงที่ธนาคารยังยืนยันไม่ได้ ห้ามเงียบ
 *   4. ยอดไม่ใช่ขนาด 39 (99 / ขาด) → เส้นแยกแพคเกจเดิม ด้วยผลตรวจที่มีแล้ว (ไม่ยิงซ้ำ)
 *   5. พิมพ์ "โอนแล้ว" ระหว่างตรวจ → "กำลังตรวจ" แล้วถ้ารูปจบแบบเงียบ ต้องกลายเป็นขอสลิป
 *   6. ต่อสายจริงทุกทางเข้า + บิล 39 ที่ใหม่กว่า Celtic ได้สิทธิ์ก่อน
 *
 * SlipOK / ตัวถอด QR / finalize / กู้ Celtic ถูกแทนด้วย subclass — เทสต์ตรรกะการตัดสินและการต่อสาย
 *
 * @group fortune-slip
 */
class FortuneLateDeepSlipTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '26273302092329161';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // ⚠️ getSettings() มี static memo ข้ามเทสต์ใน process เดียวกัน — เทสต์ก่อนหน้า rollback แถวทิ้งไปแล้ว
        //   memo ยังชี้แถวเดิม → save() อัปเดต 0 แถว → fresh() คืน null (CI แดง 13/14 รอบแรก)
        FortuneTellingSetting::clearSettingsCache();
        // เส้น Celtic เดิมดาวน์โหลดรูปจาก URL เอง — ห้ามเทสต์ยิงเน็ตจริง
        Http::fake();
    }

    /**
     * @param  array{slip?: bool, verify?: array, eval?: array, spy_process?: bool, throw?: bool}  $opts
     */
    private function service(array $opts = []): FortuneConversationService
    {
        $settings = FortuneTellingSetting::getSettings();
        $settings->enable_slipok_verify = true;
        $settings->slipok_branch_id = 'test-branch';
        $settings->slipok_api_key = 'test-key';
        $settings->slipok_auto_provision = true;
        // ⚠️ schema default = 99 (prod ตั้ง 39) — ต้องตั้งเอง ไม่งั้นเกณฑ์ "ยอดขนาด 39" เพี้ยน
        $settings->deep_reading_price = 39;
        $settings->save();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();

        return new class(FortuneTellingSetting::getSettings(), $opts) extends FortuneConversationService
        {
            /** @var array<int, string> */
            public array $calls = [];

            public array $processArgs = [];

            public function __construct(FortuneTellingSetting $settings, public array $opts)
            {
                parent::__construct($settings);
            }

            protected function imageHasSlipQr(string $bytes): bool
            {
                $this->calls[] = 'qr';

                return $this->opts['slip'] ?? true;
            }

            protected function returningImageLooksLikeSlip(?string $url, ?string $base64): bool
            {
                $this->calls[] = 'classify_loose';

                return $this->opts['slip'] ?? true;
            }

            protected function readSlipBytes(?string $url, ?string $base64): ?string
            {
                return 'fake-jpeg-bytes';
            }

            protected function verifyLateSlipFile(SlipOkService $svc, string $absPath, string $platform, string $userId): array
            {
                $this->calls[] = 'slipok';

                return $this->opts['verify'] ?? ['ok' => true, 'transRef' => 'T-TEST-1', 'amount' => 39.16];
            }

            protected function evaluateLateSlip(SlipOkService $svc, FortuneReading $bill, array $verify): array
            {
                return $this->opts['eval'] ?? ['decision' => SlipOkService::DECISION_APPROVE, 'reason' => 'test', 'verify' => $verify];
            }

            protected function archiveSlipForLog(string $absPath, ?int $readingId): ?string
            {
                return null;
            }

            protected function finalizeSlipOkApproved(FortuneReading $reading, array $verify, string $platform, string $userId): array
            {
                if ($this->opts['throw'] ?? false) {
                    throw new \RuntimeException('finalize พังกลางทาง (จำลอง)');
                }
                $this->calls[] = 'finalize:'.$reading->id;
                $reading->confirmPayment(null);
                if ($this->opts['throw_after_pay'] ?? false) {
                    throw new \RuntimeException('พังหลังตัดบิลแล้ว (จำลอง)');
                }

                return ['action' => 'test_finalized', 'message' => 'ok', 'reading' => $reading];
            }

            protected function handlePartialPayment(FortuneReading $reading, array $verify, ?string $platform, ?string $userId, string $context): array
            {
                $this->calls[] = 'partial:'.$reading->id.':'.$context;

                return ['action' => 'test_partial', 'message' => 'ok', 'reading' => $reading];
            }

            public function respondEval(FortuneReading $reading, array $verify, array $eval): array
            {
                return $this->respondWithEvaluatedSlip($reading, $verify, $eval, 'facebook', (string) $reading->facebook_user_id);
            }

            public function exactFor(string $userId, float $amount, ?string $transTimestamp = null): ?FortuneReading
            {
                return $this->findUnpaidBillByExactSlipAmount($userId, $amount, $transTimestamp);
            }

            protected function recoverCelticFromVerifiedSlip(FortuneReading $reading, array $verify, string $platform, string $userId): array
            {
                $this->calls[] = 'recover_celtic:'.$reading->id;

                return ['action' => 'test_recovered_celtic', 'message' => 'ok', 'reading' => $reading];
            }

            protected function routeVerifiedSlipViaProvisional(array $verify, string $platform, string $userId): ?array
            {
                $this->calls[] = 'reroute';

                return ['action' => 'test_rerouted', 'message' => 'ok', 'reading' => null];
            }

            public function slipFloodGate(string $platform, string $userId, ?FortuneReading $reading = null, string $context = 'flood'): ?array
            {
                $this->calls[] = 'flood';

                return null;
            }

            public function processLateDeepSlip(FortuneReading $bill, string $platform, string $userId, ?string $url, ?string $base64, bool $explicitClaim): ?array
            {
                if ($this->opts['spy_process'] ?? false) {
                    $this->processArgs = ['bill' => $bill->id, 'url' => $url, 'base64' => $base64, 'explicit' => $explicitClaim];

                    return ['action' => 'test_spy', 'message' => 'spy', 'reading' => $bill];
                }

                return parent::processLateDeepSlip($bill, $platform, $userId, $url, $base64, $explicitClaim);
            }

            public function decide(FortuneReading $bill, array $verify, array $eval, bool $explicitClaim): ?array
            {
                return $this->decideLateDeepSlip($bill, $verify, $eval, 'facebook', (string) $bill->facebook_user_id, $explicitClaim);
            }
        };
    }

    /** ยอดจองของบิล (UPA) ที่หมดอายุแล้ว — ใช้ทดสอบการจับบิลด้วยยอดทศนิยมตรงเป๊ะ */
    private function makeExpiredUpa(float $uniqueAmount, float $base, int $createdHoursAgo = 4): int
    {
        return (int) DB::table('unique_payment_amounts')->insertGetId([
            'base_amount' => $base,
            'unique_amount' => $uniqueAmount,
            'decimal_suffix' => (int) round(($uniqueAmount - floor($uniqueAmount)) * 100),
            'transaction_type' => 'fortune_reading',
            'status' => 'cancelled',
            'expires_at' => now()->subHour(),
            'created_at' => now()->subHours($createdHoursAgo),
            'updated_at' => now()->subHour(),
        ]);
    }

    /** "ตรวจแล้ว จบเงียบ" — ห้ามเป็น null (null = webhook เก็บรูป แล้วเส้นอื่นตรวจซ้ำได้ "ซ้ำ") */
    private function assertVerifiedQuiet(?array $resp, string $why): void
    {
        $this->assertSame('silent_skip', $resp['action'] ?? null, $why);
        $this->assertTrue((bool) ($resp['late_quiet'] ?? false), $why);
    }

    /** บิลดูดวง 39 ทรงเดียวกับ FTU-260912-Q1090 หลังถูกปิดเพราะหมดเวลา */
    private function makeExpiredDeepBill(array $overrides = []): FortuneReading
    {
        static $seq = 0;
        $seq++;

        return FortuneReading::create(array_merge([
            'facebook_user_id' => self::PSID,
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            // ⚠️ คอลัมน์ NOT NULL ไม่มี default — ไม่ใส่ = insert ตายที่ MySQL (error 1364)
            'questions' => ['ขอดูพื้นดวงโดยรวมของเจ้าชะตา'],
            'is_paid' => false,
            'amount_paid' => 39.16,
            'unique_payment_amount_id' => 900000 + $seq,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'bill_reference' => 'FTU-TEST-L10'.$seq,
        ], $overrides));
    }

    // ── 1. ตัดบิลเดิม ────────────────────────────────────────────────────

    public function test_deep_sized_slip_pays_the_original_bill(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service(['verify' => ['ok' => true, 'transRef' => 'T1', 'amount' => 39.16]]);

        $resp = $svc->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/slip.jpg', null, false);

        $this->assertSame('test_finalized', $resp['action'] ?? null);
        $this->assertContains('finalize:'.$bill->id, $svc->calls, 'ต้องตัดบิลเดิม ไม่เปิดแถวใหม่ (กันจ่ายซ้ำกับ SMS)');
        $this->assertNotContains('reroute', $svc->calls);
        $this->assertTrue((bool) $bill->fresh()->is_paid);
        $this->assertTrue(
            (bool) $bill->fresh()->getConversationState('sms_match_processed', false),
            'ตัดแล้วต้องตั้งธงเดียวกับเส้น SMS — SMS ที่หลุดมาทีหลังจะข้าม side effects'
        );
    }

    /** โอนเลขกลม 39.00 ที่ร้าน (ไม่ใช่ยอดทศนิยมของบิล) ก็ยังเป็นบิลเดิม */
    public function test_round_39_from_a_shop_also_pays_the_original_bill(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service(['verify' => ['ok' => true, 'transRef' => 'T2', 'amount' => 39.00]]);

        $svc->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/slip.jpg', null, false);

        $this->assertContains('finalize:'.$bill->id, $svc->calls);
    }

    /** ขอบเกณฑ์เดียวกับเส้นโอนก่อนบิล: ≤40.00 = 39 · 40.01 ขึ้นไป = ต้องถามแพคเกจ */
    public function test_forty_is_still_deep_but_forty_point_zero_one_is_not(): void
    {
        $bill = $this->makeExpiredDeepBill();

        $at40 = $this->service();
        $at40->decide($bill, ['ok' => true, 'transRef' => 'T40', 'amount' => 40.00],
            ['decision' => SlipOkService::DECISION_APPROVE, 'reason' => 'test'], false);
        $this->assertContains('finalize:'.$bill->id, $at40->calls);

        $bill2 = $this->makeExpiredDeepBill();
        $over = $this->service();
        $over->decide($bill2, ['ok' => true, 'transRef' => 'T4001', 'amount' => 40.01],
            ['decision' => SlipOkService::DECISION_APPROVE, 'reason' => 'test'], false);
        $this->assertContains('reroute', $over->calls);
        $this->assertNotContains('finalize:'.$bill2->id, $over->calls);
    }

    /** บิล 39 สองใบ: สลิปยอด 39.16 ต้องตัด "ใบที่ยอด 39.16" ไม่ใช่ใบล่าสุด — ใบเดียวกับที่ SMS จะปลุก */
    public function test_two_deep_bills_pay_the_one_whose_amount_matches_the_slip(): void
    {
        $older = $this->makeExpiredDeepBill(['unique_payment_amount_id' => $this->makeExpiredUpa(39.16, 39)]);
        $newer = $this->makeExpiredDeepBill([
            'amount_paid' => 39.42,
            'unique_payment_amount_id' => $this->makeExpiredUpa(39.42, 39),
        ]);
        $svc = $this->service(['verify' => ['ok' => true, 'transRef' => 'T6', 'amount' => 39.16]]);

        $svc->processLateDeepSlip($newer, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false);

        $this->assertContains('finalize:'.$older->id, $svc->calls, 'ต้องตัดใบที่ยอดตรงสลิป');
        $this->assertNotContains('finalize:'.$newer->id, $svc->calls);
    }

    /** สลับ Celtic → 39 แล้วโอนยอดของบิล Celtic เดิมเป๊ะ → กู้ Celtic ใบนั้น ไม่ใช่เปิดแถวใหม่ */
    public function test_slip_matching_an_older_celtic_bill_recovers_that_celtic_bill(): void
    {
        $celtic = $this->makeExpiredDeepBill([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'amount_paid' => 99.37,
            'unique_payment_amount_id' => $this->makeExpiredUpa(99.37, 99),
        ]);
        $deep = $this->makeExpiredDeepBill(['unique_payment_amount_id' => $this->makeExpiredUpa(39.16, 39)]);
        $svc = $this->service(['verify' => ['ok' => true, 'transRef' => 'T7', 'amount' => 99.37]]);

        $svc->processLateDeepSlip($deep, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false);

        $this->assertContains('recover_celtic:'.$celtic->id, $svc->calls);
        $this->assertNotContains('reroute', $svc->calls, 'ยอดตรงบิล Celtic เดิม = ห้ามเปิดบิลชั่วคราวใหม่ (SMS จะปลุกใบเดิม = จ่ายซ้ำ)');
    }

    /** สลับ 39 → Celtic แล้วโอนตาม QR บิล 39 เดิม: เส้น Celtic ต้องตัดบิล 39 ใบนั้น ไม่เครดิตเข้า Celtic เป็นโอนขาด */
    public function test_celtic_path_pays_the_older_deep_bill_whose_amount_matches(): void
    {
        $deep = $this->makeExpiredDeepBill(['unique_payment_amount_id' => $this->makeExpiredUpa(39.16, 39)]);
        $celtic = $this->makeExpiredDeepBill([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'amount_paid' => 99.37,
            'unique_payment_amount_id' => $this->makeExpiredUpa(99.37, 99),
        ]);
        $svc = $this->service();

        $svc->respondEval($celtic, ['ok' => true, 'transRef' => 'T8', 'amount' => 39.16],
            ['decision' => SlipOkService::DECISION_REJECT_AMOUNT, 'reason' => 'test']);

        $this->assertContains('finalize:'.$deep->id, $svc->calls, 'SMS จะปลุกบิล 39 ใบนั้น — ต้องตัดใบนั้น ไม่ใช่เครดิตเข้า Celtic');
        $this->assertNotContains('partial:'.$celtic->id.':returning', $svc->calls);
    }

    /** ยอดทศนิยมเวียนใช้ซ้ำได้ — UPA ที่สร้าง "หลัง" เวลาโอนในสลิป ไม่ใช่ใบที่ลูกค้าจ่าย (ด่านเดียวกับ SMS) */
    public function test_exact_match_ignores_price_codes_created_after_the_transfer(): void
    {
        $this->makeExpiredDeepBill(['unique_payment_amount_id' => $this->makeExpiredUpa(39.16, 39, 1)]);
        $svc = $this->service();

        $this->assertNull($svc->exactFor(self::PSID, 39.16, now()->subHours(5)->toIso8601ZuluString()));
        $this->assertNotNull($svc->exactFor(self::PSID, 39.16, now()->toIso8601ZuluString()), 'ตัวเทียบ: โอนหลังออกยอด = ใบนี้');
    }

    /** บิลที่พัก HOLD (โอนขาด 3 รอบ) = รอแม่หมอ/แอดมินตัดสิน — ระบบห้ามตัดเอง */
    public function test_exact_match_skips_bills_on_partial_hold(): void
    {
        $this->makeExpiredDeepBill([
            'unique_payment_amount_id' => $this->makeExpiredUpa(39.16, 39),
            'partial_hold_at' => now(),
        ]);

        $this->assertNull($this->service()->exactFor(self::PSID, 39.16));
    }

    /** บิลที่มียอดสะสมโอนขาดอยู่ → ต้องผ่านเส้นโอนขาดเดิม (บวกยอดสะสม) ไม่ตัดข้าม partial_paid_total */
    public function test_exact_bill_with_running_partial_total_goes_through_partial_flow(): void
    {
        $bill = $this->makeExpiredDeepBill([
            'unique_payment_amount_id' => $this->makeExpiredUpa(19.42, 19),
            'partial_paid_total' => 20.00,
        ]);
        $svc = $this->service([
            'verify' => ['ok' => true, 'transRef' => 'T9', 'amount' => 19.42],
            'eval' => ['decision' => SlipOkService::DECISION_APPROVE, 'reason' => 'test'],
        ]);

        $svc->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false);

        $this->assertContains('partial:'.$bill->id.':late_slip', $svc->calls);
        $this->assertNotContains('finalize:'.$bill->id, $svc->calls);
    }

    // ── 2. SMS ตัดไปแล้ว → ไม่ซ้ำ ไม่ส่งข้อความว่าง ──────────────────────

    public function test_bill_already_paid_by_sms_is_not_paid_again(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service();
        // SMS ตัดบิลเดียวกันไปก่อน (ระหว่างที่เรากำลังตรวจสลิป)
        DB::table('fortune_readings')->where('id', $bill->id)->update(['is_paid' => true, 'paid_at' => now()]);

        $resp = $svc->decide($bill, ['ok' => true, 'transRef' => 'T3', 'amount' => 39.16],
            ['decision' => SlipOkService::DECISION_APPROVE, 'reason' => 'test'], false);

        $this->assertSame('silent_skip', $resp['action'] ?? null, 'ห้ามคืน message ว่าง — ChannelManager จะส่ง "ระบบกำลังดำเนินการ"');
        $this->assertNotContains('finalize:'.$bill->id, $svc->calls);
    }

    // ── 3. รูปอย่างเดียว ────────────────────────────────────────────────

    public function test_image_only_without_slip_qr_is_silent_and_never_hits_slipok(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service(['slip' => false]);

        $resp = $svc->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/selfie.jpg', null, false);

        $this->assertNull($resp, 'รูปที่ไม่มี QR สลิป จากคนที่ไม่ได้บอกว่าโอน = เงียบ (webhook เก็บรูปตามเดิม)');
        $this->assertContains('qr', $svc->calls);
        $this->assertNotContains('classify_loose', $svc->calls, 'รูปอย่างเดียวห้ามใช้ vision (owner ปิด enable_image_vision)');
        $this->assertNotContains('slipok', $svc->calls);
        $this->assertNotContains('flood', $svc->calls, 'รูปอย่างเดียวห้ามแตะด่าน flood (strike → แบนได้)');
        $this->assertFalse((bool) $bill->fresh()->is_paid);
    }

    public function test_unreadable_slip_is_silent_for_image_only_but_asks_when_customer_claimed(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $noQr = ['decision' => SlipOkService::DECISION_NO_QR, 'reason' => 'test'];

        $imageOnly = $this->service(['verify' => ['ok' => false, 'error_code' => 1007], 'eval' => $noQr]);
        $this->assertVerifiedQuiet(
            $imageOnly->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false),
            'ส่งไปตรวจแล้ว อ่านไม่ได้ + ลูกค้ายังไม่ได้บอกว่าโอน = เงียบ แต่ห้ามให้เส้นอื่นตรวจซ้ำ'
        );

        $claimed = $this->service(['verify' => ['ok' => false, 'error_code' => 1007], 'eval' => $noQr]);
        $resp = $claimed->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, true);
        $this->assertSame('slipok_ask_slip', $resp['action'] ?? null, 'ลูกค้าบอกว่าโอนแล้ว + อ่านสลิปไม่ได้ → ขอสลิปตามเดิม');
        $this->assertContains('flood', $claimed->calls, 'คนที่บอกว่าโอนแล้ว ใช้ด่าน flood ตามเส้นเดิม');
    }

    /** สลิปจริง (มี QR) แต่ธนาคารยังยืนยันไม่ได้ → ห้ามเงียบกับเงินจริง + ตั้งธงให้สลิปใบถัดไปถูกตรวจ */
    public function test_real_slip_that_bank_cannot_confirm_yet_is_acknowledged(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service([
            'verify' => ['ok' => false, 'error_code' => 1010],
            'eval' => ['decision' => SlipOkService::DECISION_BANK_DELAY, 'reason' => 'test'],
        ]);

        $resp = $svc->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false);

        $this->assertSame('slipok_received_pending', $resp['action'] ?? null);
        $this->assertStringContainsString('ส่งสลิปใบเดิม', $resp['message'] ?? '', 'ธนาคารช้า = ส่งใหม่ช่วยได้');
        $this->assertTrue(Cache::has('fortune:returning_slip_ask:'.self::PSID));
    }

    /** โควตา SlipOK ของเราหมด → ได้รับแล้ว แต่ห้ามขอส่งใหม่ (ส่งใหม่ก็วนกลับมาที่เดิม) */
    public function test_quota_out_acknowledges_without_asking_to_resend(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service([
            'verify' => ['ok' => false, 'error_code' => 1004],
            'eval' => ['decision' => SlipOkService::DECISION_QUOTA, 'reason' => 'test'],
        ]);

        $resp = $svc->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false);

        $this->assertSame('slipok_received_pending', $resp['action'] ?? null);
        $this->assertStringNotContainsString('ส่งสลิปใบเดิม', $resp['message'] ?? '');
    }

    public function test_image_only_respects_slipok_quota_without_strikes(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service();
        // ใช้โควตาต่อคนจนเต็ม — คีย์เดียวกับที่ SlipOkService::checksUsed() อ่าน
        Cache::put('fortune:slipok:spend:facebook:'.self::PSID, 99, 3600);
        $this->assertFalse(
            (new SlipOkService(FortuneTellingSetting::getSettings()))->canSpendForUser('facebook', self::PSID),
            'ตัวเทียบ: โควตาต้องเต็มจริง ไม่งั้นเทสต์นี้ไม่ได้ทดสอบอะไร'
        );

        $this->assertNull($svc->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false));
        $this->assertNotContains('slipok', $svc->calls);
        $this->assertNotContains('flood', $svc->calls);
    }

    public function test_duplicate_slip_is_silent_for_image_only_but_told_when_claimed(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $dup = ['decision' => SlipOkService::DECISION_DUPLICATE, 'reason' => 'test'];

        $this->assertVerifiedQuiet(
            $this->service(['eval' => $dup])->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false),
            'สลิปซ้ำจากรูปที่ส่งมาเฉย ๆ = เงียบ'
        );

        $resp = $this->service(['eval' => $dup])
            ->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, true);
        $this->assertSame('slipok_duplicate', $resp['action'] ?? null);
        $this->assertFalse((bool) $bill->fresh()->is_paid);
    }

    // ── 4. ยอดไม่ใช่ขนาด 39 → เส้นแยกแพคเกจเดิม ───────────────────────────

    public function test_celtic_sized_slip_goes_to_package_routing_not_the_deep_bill(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service(['verify' => ['ok' => true, 'transRef' => 'T4', 'amount' => 99.00]]);

        $resp = $svc->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false);

        $this->assertSame('test_rerouted', $resp['action'] ?? null);
        $this->assertNotContains('finalize:'.$bill->id, $svc->calls, 'โอน 99 = ตั้งใจดู Celtic ห้ามตัดเป็นบิล 39');
        $this->assertFalse((bool) $bill->fresh()->is_paid);
    }

    public function test_underpaid_slip_goes_to_package_routing(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service([
            'verify' => ['ok' => true, 'transRef' => 'T5', 'amount' => 20.00],
            'eval' => ['decision' => SlipOkService::DECISION_REJECT_AMOUNT, 'reason' => 'test'],
        ]);

        $svc->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false);

        $this->assertContains('reroute', $svc->calls, 'โอนขาด → เครดิต + เสนอเติม ตามเส้นโอนก่อนบิลเดิม');
        $this->assertFalse((bool) $bill->fresh()->is_paid);
    }

    /** ตรวจผ่านแล้ว (SlipOK จดสลิปไว้แล้ว) แต่ตัดบิลพังกลางทาง → ห้ามเงียบ ส่งใหม่จะกลายเป็น "ซ้ำ" */
    public function test_crash_after_successful_verify_is_not_silent(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service(['throw' => true]);

        $resp = $svc->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false);

        $this->assertSame('slipok_received_pending', $resp['action'] ?? null);
        $this->assertStringNotContainsString('ส่งสลิปใบเดิม', $resp['message'] ?? '', 'SlipOK จดสลิปไว้แล้ว ส่งใหม่จะได้ "ซ้ำ" — ห้ามขอส่งใหม่');
    }

    /** พังหลังตัดบิลสำเร็จแล้ว → ต้องบอกว่า "ได้รับยอดแล้ว" ไม่ใช่ "ยังยืนยันไม่ได้" */
    public function test_crash_after_the_bill_was_paid_says_paid(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service(['throw_after_pay' => true]);

        $resp = $svc->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false);

        $this->assertSame('slipok_received_paid', $resp['action'] ?? null);
        $this->assertTrue((bool) $bill->fresh()->is_paid);
    }

    // ── 5. พิมพ์ "โอนแล้ว" ระหว่างตรวจ ─────────────────────────────────────

    public function test_claim_while_slip_is_being_checked_says_checking_and_leaves_a_trace(): void
    {
        $this->makeExpiredDeepBill();
        $svc = $this->service();
        Cache::put('fortune:late_slip_inflight:'.self::PSID, 1, 60);

        $resp = $svc->tryReturningPaidSlipCheck('facebook', self::PSID, 'หนูโอนให้แล้วนะคะ');

        $this->assertSame('slipok_checking', $resp['action'] ?? null);
        $this->assertTrue(Cache::has('fortune:late_slip_claim_waiting:'.self::PSID));
        $this->assertTrue(Cache::has('fortune:returning_slip_ask:'.self::PSID), 'สลิปใบถัดไปต้องถูกตรวจแน่');
    }

    /** ได้ "กำลังตรวจ" ไปแล้ว + รูปนั้นจบแบบเงียบ → ต้องกลายเป็นขอสลิป (คำว่ากำลังตรวจห้ามหายเงียบ) */
    public function test_image_that_ends_silently_after_a_claim_asks_for_the_slip(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service(['slip' => false]);
        Cache::put('fortune:late_slip_claim_waiting:'.self::PSID, 1, 120);

        $resp = $svc->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false);

        $this->assertSame('slipok_ask_slip', $resp['action'] ?? null);
        $this->assertFalse(Cache::has('fortune:late_slip_claim_waiting:'.self::PSID), 'ใช้ธงแล้วต้องล้าง');
    }

    /**
     * อีกรูปมาชนระหว่างตรวจ (ล็อกไม่ว่าง) → ห้ามดึงธง "พิมพ์โอนแล้วระหว่างตรวจ" ไปใช้ + ห้ามทิ้งรูป
     * (สายที่ถือล็อกต้องเป็นคนตัดสินว่ารูปแรกจบเงียบแล้วต้องขอสลิปไหม)
     */
    public function test_image_hitting_a_busy_check_keeps_the_marker_and_stashes_itself(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service();
        Cache::add('fortune:celtic_create_lock:'.self::PSID, 1, 90);
        Cache::put('fortune:late_slip_claim_waiting:'.self::PSID, 1, 120);

        $resp = $svc->processLateDeepSlip($bill, 'facebook', self::PSID, null, base64_encode('second-slip-bytes'), false);

        $this->assertVerifiedQuiet($resp, 'ชนล็อก = ไม่ตอบ (สายแรกกำลังจะตอบ)');
        $this->assertTrue(Cache::has('fortune:late_slip_claim_waiting:'.self::PSID), 'ธงเป็นของสายที่ถือล็อก');
        $this->assertTrue(Cache::has('fortune:pending_slip:facebook:'.self::PSID), 'รูปที่สองต้องถูกเก็บไว้ ไม่ทิ้ง');
        $this->assertNotContains('slipok', $svc->calls);

        Storage::disk('local')->delete((string) Cache::get('fortune:pending_slip:facebook:'.self::PSID));
    }

    // ── 6. ต่อสายจริงทุกทางเข้า ───────────────────────────────────────────

    /** รูปอย่างเดียว (ไม่มีธง) → เข้า processLateDeepSlip แบบไม่ได้บอกว่าโอน + ไม่ผ่านด่าน flood ของเส้นเดิม */
    public function test_returning_image_without_claim_is_wired_as_image_only(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service(['spy_process' => true]);

        $resp = $svc->handleReturningSlipImage('facebook', self::PSID, 'https://cdn.test/a.jpg', null);

        $this->assertSame('test_spy', $resp['action'] ?? null);
        $this->assertSame($bill->id, $svc->processArgs['bill'] ?? null);
        $this->assertFalse($svc->processArgs['explicit'] ?? true);
        $this->assertNotContains('flood', $svc->calls, 'ด่าน flood ของ handleReturningSlipImage ต้องไม่ทำงานก่อนเส้นนี้');
    }

    /** มีธงรอสลิป (ลูกค้าเพิ่งพิมพ์โอนแล้ว) → เข้าแบบบอกว่าโอนแล้ว */
    public function test_returning_image_after_claim_is_wired_as_explicit(): void
    {
        $this->makeExpiredDeepBill();
        $svc = $this->service(['spy_process' => true]);
        Cache::put('fortune:returning_slip_ask:'.self::PSID, true, now()->addHour());

        $svc->handleReturningSlipImage('facebook', self::PSID, 'https://cdn.test/a.jpg', null);

        $this->assertTrue($svc->processArgs['explicit'] ?? false);
    }

    /** สลับ Celtic → 39: บิล 39 ใหม่กว่า = ความตั้งใจล่าสุด ต้องได้เส้นบิล 39 (เดิม Celtic ชนะเสมอ) */
    public function test_newer_deep_bill_wins_over_an_older_recoverable_celtic(): void
    {
        $this->makeExpiredDeepBill([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'amount_paid' => 99.37,
        ]);
        $deep = $this->makeExpiredDeepBill();
        $svc = $this->service(['spy_process' => true]);

        $svc->handleReturningSlipImage('facebook', self::PSID, 'https://cdn.test/a.jpg', null);

        $this->assertSame($deep->id, $svc->processArgs['bill'] ?? null);
    }

    /** กลับกัน: Celtic ใหม่กว่า → เส้น Celtic เดิม ไม่แตะเส้นใหม่ */
    public function test_newer_celtic_keeps_its_own_path(): void
    {
        $this->makeExpiredDeepBill();
        $this->makeExpiredDeepBill([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'amount_paid' => 99.37,
        ]);
        $svc = $this->service(['spy_process' => true]);

        $svc->handleReturningSlipImage('facebook', self::PSID, 'https://cdn.test/a.jpg', null);

        $this->assertSame([], $svc->processArgs);
        $this->assertContains('classify_loose', $svc->calls, 'ต้องวิ่งเส้น Celtic เดิมจริง (ไม่ใช่แค่ไม่เข้าเส้นใหม่)');
    }

    /** Celtic ที่จ่ายแล้วแต่ยังไม่ได้ดู (เคส entony) ต้องได้เปิดบิลนั้น — บิล 39 ที่ใหม่กว่าห้ามแย่ง */
    public function test_paid_but_unread_celtic_keeps_its_path_over_a_newer_deep_bill(): void
    {
        $this->makeExpiredDeepBill([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'amount_paid' => 99.37,
            'is_paid' => true,
            'paid_at' => now()->subDays(2),
            'celtic_questions_used' => 0,
        ]);
        $this->makeExpiredDeepBill();
        $svc = $this->service(['spy_process' => true]);

        $svc->handleReturningSlipImage('facebook', self::PSID, 'https://cdn.test/a.jpg', null);

        $this->assertSame([], $svc->processArgs);
    }

    /** เส้นใหม่ยังไม่ได้ส่งไปตรวจ (ถอด QR ไม่ได้) + มี Celtic เก่าให้กู้ → ไหลต่อเส้น Celtic เดิม ไม่เงียบ */
    public function test_undecodable_image_falls_back_to_the_older_celtic_path(): void
    {
        $this->makeExpiredDeepBill([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'amount_paid' => 99.37,
        ]);
        $this->makeExpiredDeepBill();
        $svc = $this->service(['slip' => false]);

        $svc->handleReturningSlipImage('facebook', self::PSID, 'https://cdn.test/a.jpg', null);

        $this->assertContains('qr', $svc->calls, 'ลองเส้นบิล 39 ก่อน');
        $this->assertContains('classify_loose', $svc->calls, 'แล้วต้องไหลต่อเส้น Celtic เดิม (พฤติกรรมก่อนหน้า)');
    }

    /** ลำดับเดียวกับเคสจริง: รูปก่อน (เก็บไว้) → "หนูโอนให้แล้วนะคะ" → รูปที่เก็บไว้เข้าเส้นตัดบิลเดิม */
    public function test_typed_claim_with_stashed_image_is_wired_to_the_original_bill(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service(['spy_process' => true]);

        $rel = 'fortune/slips/pend_test_'.md5(self::PSID).'.jpg';
        Storage::disk('local')->put($rel, 'fake-jpeg-bytes');
        Cache::put('fortune:pending_slip:facebook:'.self::PSID, $rel, now()->addMinutes(30));

        try {
            $resp = $svc->tryReturningPaidSlipCheck('facebook', self::PSID, 'หนูโอนให้แล้วนะคะ');
        } finally {
            Storage::disk('local')->delete($rel);
        }

        $this->assertSame('test_spy', $resp['action'] ?? null, 'ต้องไม่เปิดแถวใหม่ (autoProvision) เมื่อมีบิล 39 เดิมค้างจ่าย');
        $this->assertSame($bill->id, $svc->processArgs['bill'] ?? null);
        $this->assertTrue($svc->processArgs['explicit'] ?? false);
        $this->assertNotEmpty($svc->processArgs['base64'] ?? null);
    }

    /** ไม่มีบิล 39 ค้าง → ไม่แตะเส้นใหม่ (กลับไปพฤติกรรมเดิม) */
    public function test_no_expired_deep_bill_means_old_behaviour(): void
    {
        $svc = $this->service(['spy_process' => true]);

        $this->assertNull($svc->handleReturningSlipImage('facebook', self::PSID, 'https://cdn.test/a.jpg', null));
        $this->assertSame([], $svc->processArgs);
    }
}
