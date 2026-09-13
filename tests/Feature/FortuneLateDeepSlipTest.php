<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\SlipOkService;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 🧾 (2026-09-13) สลิปที่มาหลังบิลดูดวง 39 หมดเวลา — processLateDeepSlip
 *
 * เคสจริง Pantaree Donpar (FB 26273302092329161, FTU-260912-Q1090):
 *   บิล 39.16 หมดเวลา 3 ชม. → ไปโอนที่ร้านตอนเช้า → ส่งรูปสลิป → เดิมถูกเก็บเงียบ ไม่มีใครตรวจ
 *
 * สิ่งที่ห้ามหลุด (จากรีวิวก่อนพุชร่างแรก):
 *   1. ยอดขนาด 39 ต้อง "ตัดบิลเดิม" ไม่เปิดแถวใหม่ — ไม่งั้น SMS ที่มาทีหลังปลุกบิลเดิมได้ = จ่ายซ้ำ 2 ใบ
 *   2. SMS ตัดบิลเดิมไปแล้ว/กำลังตัด → ไม่ตัดซ้ำ + ไม่ส่งข้อความว่าง ("ระบบกำลังดำเนินการ")
 *   3. รูปที่ส่งมาเฉย ๆ: ไม่ใช่สลิปชัด ๆ = เงียบ · อ่านไม่ได้ = เงียบ · ไม่แตะด่าน flood (ไม่นับ strike)
 *   4. ยอดไม่ใช่ขนาด 39 (99 / ขาด) → ส่งเข้าเส้นแยกแพคเกจเดิม ด้วยผลตรวจที่มีแล้ว (ไม่ยิงซ้ำ)
 *   5. ทางเข้าทั้งสาม (รูปอย่างเดียว / มีธงรอสลิป / พิมพ์โอนแล้ว + รูปที่เก็บไว้) ต่อสายเข้าตัวนี้จริง
 *
 * SlipOK / classifier / finalize ถูกแทนด้วย subclass — เทสต์ตรรกะการตัดสินและการต่อสาย
 * (ตัวตัดบิลจริง finalizeSlipOkApproved มีเทสต์และใช้งานบน prod อยู่แล้ว)
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
    }

    /**
     * @param  array{slip?: bool, verify?: array, eval?: array, spy_process?: bool}  $opts
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

            protected function imageIsConfidentlySlip(?string $url, ?string $base64): bool
            {
                $this->calls[] = 'classify_strict';

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
                $this->calls[] = 'finalize:'.$reading->id;
                $reading->confirmPayment(null);

                return ['action' => 'test_finalized', 'message' => 'ok', 'reading' => $reading];
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
            'unique_payment_amount_id' => 5000 + $seq,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'bill_reference' => 'FTU-TEST-L10'.$seq,
        ], $overrides));
    }

    // ── 1. ยอดขนาด 39 → ตัดบิลเดิม ─────────────────────────────────────

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

    // ── 3. รูปอย่างเดียว: เงียบเมื่อไม่ชัด · ไม่นับ strike ──────────────────

    public function test_image_only_non_slip_is_silent_and_never_hits_slipok(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service(['slip' => false]);

        $resp = $svc->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/selfie.jpg', null, false);

        $this->assertNull($resp, 'รูปที่ไม่ใช่สลิป จากคนที่ไม่ได้บอกว่าโอน = เงียบ (webhook เก็บรูปตามเดิม)');
        $this->assertNotContains('slipok', $svc->calls);
        $this->assertNotContains('flood', $svc->calls, 'รูปอย่างเดียวห้ามแตะด่าน flood (strike → แบนได้)');
        $this->assertFalse((bool) $bill->fresh()->is_paid);
    }

    public function test_unreadable_slip_is_silent_for_image_only_but_asks_when_customer_claimed(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $noQr = ['decision' => SlipOkService::DECISION_NO_QR, 'reason' => 'test'];

        $imageOnly = $this->service(['verify' => ['ok' => false, 'error_code' => 1007], 'eval' => $noQr]);
        $this->assertNull($imageOnly->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false));

        $claimed = $this->service(['verify' => ['ok' => false, 'error_code' => 1007], 'eval' => $noQr]);
        $resp = $claimed->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, true);
        $this->assertSame('slipok_ask_slip', $resp['action'] ?? null, 'ลูกค้าบอกว่าโอนแล้ว + อ่านสลิปไม่ได้ → ขอสลิปตามเดิม');
        $this->assertContains('flood', $claimed->calls, 'คนที่บอกว่าโอนแล้ว ใช้ด่าน flood ตามเส้นเดิม');
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

        $this->assertContains('reroute', $svc->calls, 'โอนขาด → เครดิต + ขอเติม ตามเส้นโอนก่อนบิลเดิม');
        $this->assertFalse((bool) $bill->fresh()->is_paid);
    }

    public function test_duplicate_slip_is_told_to_the_customer(): void
    {
        $bill = $this->makeExpiredDeepBill();
        $svc = $this->service(['eval' => ['decision' => SlipOkService::DECISION_DUPLICATE, 'reason' => 'test']]);

        $resp = $svc->processLateDeepSlip($bill, 'facebook', self::PSID, 'https://cdn.test/a.jpg', null, false);

        $this->assertSame('slipok_duplicate', $resp['action'] ?? null);
        $this->assertFalse((bool) $bill->fresh()->is_paid);
    }

    // ── 5. ต่อสายจริงทั้งสามทางเข้า ─────────────────────────────────────

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

    /** กำลังตรวจรูปอยู่ + ลูกค้าพิมพ์โอนแล้วตามมา → บอกว่ากำลังตรวจ ไม่ขอสลิปซ้อน */
    public function test_claim_while_slip_is_being_checked_says_checking(): void
    {
        $this->makeExpiredDeepBill();
        $svc = $this->service();
        Cache::put('fortune:late_slip_inflight:'.self::PSID, 1, 60);

        $resp = $svc->tryReturningPaidSlipCheck('facebook', self::PSID, 'หนูโอนให้แล้วนะคะ');

        $this->assertSame('slipok_checking', $resp['action'] ?? null);
    }

    /** ไม่มีบิล 39 ค้าง → ไม่แตะเส้นใหม่ (กลับไปพฤติกรรมเดิม) */
    public function test_no_expired_deep_bill_means_old_behaviour(): void
    {
        $svc = $this->service(['spy_process' => true]);

        $this->assertNull($svc->handleReturningSlipImage('facebook', self::PSID, 'https://cdn.test/a.jpg', null));
        $this->assertSame([], $svc->processArgs);
    }
}
