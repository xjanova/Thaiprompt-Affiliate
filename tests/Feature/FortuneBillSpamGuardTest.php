<?php

namespace Tests\Feature;

use App\Contracts\MessagingPlatformInterface;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserBan;
use App\Services\FcmNotificationService;
use App\Services\Fortune\BillTrollGuardService;
use App\Services\FortuneBanService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * 🧾 (2026-09-27) ด่านกันสร้างบิลรัว ๆ — รันเส้นจริงกับฐานข้อมูลจริง ไม่ใช่แค่อ่านลำดับโค้ด
 *
 * owner: "ถ้ามีคนสร้างบิลรัวๆ ทำงานไหมระบบป้องกัน"
 *   prod 30 วัน: 3 คนสร้าง ≥3 บิลไม่จ่าย ไม่มีใครโดนแบน (เอิร์ท 28 ส.ค. 5 บิล · YaVanh 3 ก.ย. 3 บิลใน 47 วิ)
 *   ทั้งคู่เกิดก่อนแก้ 2026-09-08 · หลังแก้ยังไม่มีเคสจริงมาพิสูจน์ · BillTrollGuardOrderTest ตรวจแค่ลำดับโค้ด
 *   + ช่องโหว่: สลับแพคเกจ 39↔99 ปิดบิลด้วย package_switch = ไม่ถูกนับ สร้างบิลได้ไม่จำกัด
 *
 * ตั้งค่าตรง prod วันนี้: troll ban เปิด · แบบสอบถาม 5 ข้อเปิด (เกณฑ์ 2 บิล · แบน 7 วัน) · consent_gate_bypass เปิด
 * ส่งข้อความ/FCM ถูกแทนด้วยตัวปลอม — ที่เหลือ (ตัวนับ ด่าน ตัวกวาดบิลหมดเวลา การแบน) เป็นของจริงทั้งหมด
 */
#[Group('fortune-billing')]
class FortuneBillSpamGuardTest extends TestCase
{
    use RefreshDatabase;

    /** PSID สมมติ (ไม่ใช่ลูกค้าจริง) */
    private const PSID = '29990000000000001';

    /** @var array<int, array{to: string, text: string}> ข้อความที่บอทส่งออกไปจริง */
    private array $sent = [];

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();
        Http::fake();

        $settings = FortuneTellingSetting::getSettings();
        $settings->enable_bill_troll_ban = true;
        $settings->enable_consent_quiz = true;
        $settings->fortune_consent_enabled = true;
        $settings->consent_quiz_min_unpaid_bills = 2;
        $settings->consent_quiz_ban_days = 7;
        $settings->consent_gate_bypass = true;
        $settings->enable_celtic_cross = true;
        $settings->save();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();

        $test = $this;
        $platform = new class($test) implements MessagingPlatformInterface
        {
            public function __construct(private FortuneBillSpamGuardTest $test) {}

            public function sendMessage(string $recipientId, string $message, array $options = []): bool
            {
                $this->test->recordSent($recipientId, $message);

                return true;
            }

            public function sendRichMessage(string $recipientId, array $richContent): bool
            {
                return true;
            }

            public function sendImage(string $recipientId, string $imageUrl, ?string $previewUrl = null): bool
            {
                return true;
            }

            public function sendQuickReplies(string $recipientId, string $message, array $quickReplies): bool
            {
                $this->test->recordSent($recipientId, $message);

                return true;
            }

            public function getUserProfile(string $userId): ?array
            {
                return null;
            }

            public function isMessageEvent(array $event): bool
            {
                return false;
            }

            public function getMessageText(array $event): ?string
            {
                return null;
            }

            public function getUserIdFromEvent(array $event): ?string
            {
                return null;
            }

            public function getPlatformName(): string
            {
                return 'facebook';
            }

            public function supportsRichMessage(): bool
            {
                return false;
            }
        };

        $this->mock(FortuneChannelManager::class, fn ($m) => $m->shouldReceive('getPlatform')->andReturn($platform));
        $this->mock(FcmNotificationService::class, fn ($m) => $m->shouldIgnoreMissing());
    }

    public function recordSent(string $to, string $text): void
    {
        $this->sent[] = ['to' => $to, 'text' => $text];
    }

    private function service(): FortuneConversationService
    {
        return new class(FortuneTellingSetting::getSettings()) extends FortuneConversationService
        {
            public function gate(string $uid, string $tier): ?array
            {
                return $this->consentGateOrNull($uid, $tier, null);
            }

            public function switchTier(FortuneReading $reading, string $target): array
            {
                return $this->switchPendingBillTier($reading, $target);
            }

            // เส้นเปิดบิลใหม่ของจริงยาวมาก (กติกา/QR/UPA/ส่งข้อความ) — แทนด้วยบิลเปล่า
            protected function routePayFirstDeep(FortuneReading $reading, bool $skipPaymentGate = false): array
            {
                return ['action' => 'test_new_bill', 'message' => 'บิลใหม่', 'reading' => $reading];
            }

            protected function startCelticCrossFlow(FortuneReading $reading, bool $skipStripeGate = false): array
            {
                return ['action' => 'test_new_bill', 'message' => 'บิลใหม่', 'reading' => $reading];
            }
        };
    }

    /** บิลจริงทรงเดียวกับที่ระบบออก (มี UPA) — สถานะ/ธงตามที่ส่งมา */
    private function bill(string $status, array $state = [], array $overrides = []): FortuneReading
    {
        $this->seq++;

        $reading = FortuneReading::create(array_merge([
            'facebook_user_id' => self::PSID,
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            // ⚠️ คอลัมน์ NOT NULL ไม่มี default
            'questions' => ['ขอดูภาพรวมชีวิต'],
            'is_paid' => false,
            'amount_paid' => 99 + $this->seq / 100,
            'unique_payment_amount_id' => 920000 + $this->seq,
            'conversation_status' => $status,
            'bill_reference' => 'FTU-TEST-S'.$this->seq,
        ], $overrides));

        foreach ($state as $key => $value) {
            $reading->setConversationState($key, $value);
        }

        return $reading->fresh();
    }

    /** บิลที่ลูกค้ากดยกเลิกเอง (ทรงเดียวกับ closeAllActiveConversations) */
    private function cancelledBill(string $reason = 'user_cancelled', array $state = []): FortuneReading
    {
        return $this->bill(FortuneReading::STATUS_COMPLETED, array_merge(['cancellation_reason' => $reason], $state));
    }

    private function activeBan(): ?FortuneUserBan
    {
        return FortuneUserBan::where('platform', 'facebook')->where('platform_user_id', self::PSID)->first();
    }

    // ── 1. บิลที่ 3 ต้องเจอแบบสอบถามก่อน ─────────────────────────────────

    public function test_บิลที่_3_ต้องเจอแบบสอบถาม_5_ข้อก่อน_แม้สวิตช์_bypass_เปิด(): void
    {
        $svc = $this->service();

        $this->cancelledBill();
        $this->assertNull(
            $svc->gate(self::PSID, 'celtic'),
            'ค้างแค่ใบเดียว + bypass เปิด → ต้องผ่านเลย (ลูกค้าปกติไม่ควรเจอด่าน)'
        );

        $this->cancelledBill();
        $gate = $svc->gate(self::PSID, 'celtic');

        $this->assertSame('consent_gate', $gate['action'] ?? null, 'ค้าง 2 ใบแล้วจะเปิดใบที่ 3 → ต้องโดนด่านก่อนออกบิล');
        $this->assertTrue((bool) ($gate['suppress_consent_voice'] ?? false), 'ต้องเป็นโหมดแบบสอบถาม 5 ข้อ ไม่ใช่กล่องกติกาธรรมดา');
        $this->assertNotNull(Cache::get('fortune:consent_quiz_step:'.self::PSID), 'ต้องเริ่มนับข้อของแบบสอบถาม');
    }

    // ── 2. ยอมรับแบบสอบถามแล้วปล่อยบิลที่ 3 หมดเวลา → แบน 7 วัน ─────────────

    public function test_ยอมรับแบบสอบถามแล้วปล่อยบิลที่_3_หมดเวลา_โดนแบน_7_วัน(): void
    {
        $this->cancelledBill();
        $this->cancelledBill();

        $upaId = (int) DB::table('unique_payment_amounts')->insertGetId([
            'base_amount' => 99,
            'unique_amount' => 99.37,
            'decimal_suffix' => 37,
            'transaction_type' => 'fortune_reading',
            'status' => 'reserved',
            'expires_at' => now()->subMinute(),
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);
        $third = $this->bill(FortuneReading::STATUS_CELTIC_PENDING_PAYMENT, [
            'quiz_gate_accepted' => true,
            'quiz_gate_ban_days' => 7,
        ], ['unique_payment_amount_id' => $upaId]);

        // ตัวกวาดบิลหมดเวลาตัวจริง (cron ทุก 5 นาที)
        $this->assertGreaterThanOrEqual(1, FortuneReading::cancelExpiredPendingBills());

        $third->refresh();
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $third->conversation_status);
        $this->assertSame('auto_expired', $third->getConversationState('cancellation_reason'));

        $ban = $this->activeBan();
        $this->assertNotNull($ban, 'ยอมรับกติกาแล้วไม่จ่าย ต้องโดนแบน');
        $this->assertNotNull($ban->banned_until, 'ต้องเป็นแบนชั่วคราว ห้ามถาวร (กฎ quiz เปิด)');
        $this->assertEqualsWithDelta(now()->addDays(7)->getTimestamp(), $ban->banned_until->getTimestamp(), 120);
        $this->assertTrue(app(FortuneBanService::class)->isBanned('facebook', self::PSID));
        $this->assertTrue(
            collect($this->sent)->contains(fn ($m) => str_contains($m['text'], 'งดให้บริการชั่วคราว 7 วัน')),
            'ต้องแจ้งลูกค้าก่อนแบน'
        );
    }

    // ── 3. ไม่ได้ทำแบบสอบถาม แต่เห็นคำเตือนบนบิลที่ 3 แล้วยังไม่จ่าย → แบน 7 วัน ──

    public function test_เห็นคำเตือนบนบิลที่_3_แล้วยกเลิกอีก_โดนแบน_7_วัน(): void
    {
        $this->cancelledBill();
        $this->cancelledBill();
        $third = $this->cancelledBill('user_cancelled', ['troll_warning_shown' => true]);

        app(BillTrollGuardService::class)->maybeBanAfterUnpaidCancel($third);

        $ban = $this->activeBan();
        $this->assertNotNull($ban, 'ไม่จ่ายครบ 3 ใบใน 3 วันหลังโดนเตือน ต้องโดนแบน');
        $this->assertNotNull($ban->banned_until, 'quiz เปิดอยู่ = ห้ามแบนถาวร');
        $this->assertStringContainsString('bill_troll', (string) $ban->reason);
    }

    public function test_ยังไม่เคยเห็นคำเตือน_ไม่แบน(): void
    {
        $this->cancelledBill();
        $this->cancelledBill();
        $third = $this->cancelledBill();

        app(BillTrollGuardService::class)->maybeBanAfterUnpaidCancel($third);

        $this->assertNull($this->activeBan(), 'ยุติธรรม: ไม่แบนคนที่ไม่เคยถูกเตือน');
    }

    public function test_ลูกค้าที่เคยจ่ายใน_30_วัน_ไม่โดนแบนอัตโนมัติ(): void
    {
        $paid = $this->bill(FortuneReading::STATUS_COMPLETED, [], ['is_paid' => true]);
        // ตั้งเวลาย้อนหลังตรง ๆ — ไม่พึ่งว่า created_at/paid_at อยู่ใน fillable ไหม
        DB::table('fortune_readings')->where('id', $paid->id)->update([
            'created_at' => now()->subDays(10),
            'paid_at' => now()->subDays(10),
        ]);
        $this->cancelledBill();
        $this->cancelledBill();
        $third = $this->cancelledBill('user_cancelled', ['troll_warning_shown' => true]);

        app(BillTrollGuardService::class)->maybeBanAfterUnpaidCancel($third);

        $this->assertNull($this->activeBan(), 'ลูกค้าจริงที่เคยจ่าย ห้ามโดนแบนอัตโนมัติ');
    }

    // ── 4. สลับแพคเกจ 39↔99 ─────────────────────────────────────────────

    public function test_สลับแพคเกจครั้งแรก_ทำได้_และถูกนับ(): void
    {
        $pending = $this->bill(FortuneReading::STATUS_CELTIC_PENDING_PAYMENT);

        $resp = $this->service()->switchTier($pending, 'deep');

        $this->assertSame('test_new_bill', $resp['action'] ?? null);
        $this->assertStringContainsString('ยกเลิกบิลเดิมเรียบร้อย', (string) ($resp['message'] ?? ''));
        $this->assertSame('package_switch', $pending->fresh()->getConversationState('cancellation_reason'));
        $this->assertSame(1, app(BillTrollGuardService::class)->packageSwitchCount(self::PSID));
        $this->assertSame(0, app(BillTrollGuardService::class)->strikeCount(self::PSID), 'สลับแพคเกจไม่ใช่ strike');
    }

    public function test_สลับแพคเกจครบ_2_ครั้งแล้ว_ครั้งที่_3_คงบิลเดิม(): void
    {
        $this->cancelledBill('package_switch');
        $this->cancelledBill('package_switch');
        $pending = $this->bill(FortuneReading::STATUS_CELTIC_PENDING_PAYMENT);
        $before = FortuneReading::where('facebook_user_id', self::PSID)->count();

        $resp = $this->service()->switchTier($pending, 'deep');

        $this->assertSame('package_switch_limit', $resp['action'] ?? null);
        $this->assertStringContainsString($pending->bill_reference, (string) ($resp['message'] ?? ''));
        $this->assertStringContainsString('ยกเลิก', (string) ($resp['message'] ?? ''), 'ต้องบอกทางออก');
        $this->assertSame(
            FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
            $pending->fresh()->conversation_status,
            'บิลเดิมต้องยังรอจ่ายอยู่ — คนที่ตั้งใจจ่ายต้องโอนได้ทันที'
        );
        $this->assertNull($pending->fresh()->getConversationState('cancellation_reason'));
        $this->assertSame($before, FortuneReading::where('facebook_user_id', self::PSID)->count(), 'ห้ามเปิดบิลใหม่');
    }

    public function test_ปิดสวิตช์ระบบแบน_สลับแพคเกจได้ไม่จำกัดเหมือนเดิม(): void
    {
        $settings = FortuneTellingSetting::getSettings();
        $settings->enable_bill_troll_ban = false;
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        foreach (range(1, 5) as $i) {
            $this->cancelledBill('package_switch');
        }

        $this->assertTrue(app(BillTrollGuardService::class)->packageSwitchAllowed(self::PSID));
    }
}
