<?php

namespace Tests\Unit\Services;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneConversationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use ReflectionClass;
use Tests\TestCase;

/**
 * 🕊️ ทดสอบกฎ "ถามต่อหลังช่วงคุยต่อ Deep 39 ปิด" — ห้ามเงียบใส่คนที่เพิ่งจ่ายเงิน
 *
 * เคสต้นเรื่อง (prod 2026-11-07 บิล #14643 FTU-261105-Y7178, FB):
 *   บิลจ่าย 5 พ.ย. → ทำนายใหม่ + เปิด Pro Session อีกรอบ 7 พ.ย. 16:35
 *   - started_at ของรอบ 5 พ.ย. ค้าง ⇒ session ใหม่ "หมดเวลาตั้งแต่เกิด" → sweep ล้างธง 16:40
 *   - 16:48 ลูกค้าถามเรื่องคดีความ → ไหลลงแชทฟรี → buffer บน cache → deploy ล้าง → ไม่มีใครตอบ
 *
 * ไม่ใช้ฐานข้อมูล — service แบบข้าม constructor + โมเดลปลอมที่เก็บ state ในหน่วยความจำ
 * ส่วนที่แตะ DB (ค้นหาบิล / handleProSession) ถูกแทนด้วยตัวบันทึกใน PostSessionServiceDouble
 */
class ProSessionGraceTest extends TestCase
{
    protected PostSessionServiceDouble $service;

    /** ข้อความจริงของลูกค้าเคสต้นเรื่อง (ย่อ) */
    protected const REAL_QUESTION = 'อยากถามค่ะว่าเรื่องที่เกิดขึ้นจะเป็นไปในแนวทางไหนคะถึงกับต้องขึ้นศาลตัดสินรึป่าวคะกังวลมากค่ะ ขอให้แม่หมอชี้แนะด้วยค่ะ';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 11, 7, 16, 48, 48, 'Asia/Bangkok'));

        $this->service = (new ReflectionClass(PostSessionServiceDouble::class))->newInstanceWithoutConstructor();

        $settings = (new FortuneTellingSetting)->forceFill([
            'deep_reading_qa_window_minutes' => 7,
            'pro_session_standby_minutes' => 30,
        ]);
        $prop = new \ReflectionProperty(FortuneConversationService::class, 'settings');
        $prop->setAccessible(true);
        $prop->setValue($this->service, $settings);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function invoke(string $method, ...$args)
    {
        $ref = new \ReflectionMethod(FortuneConversationService::class, $method);
        $ref->setAccessible(true);

        return $ref->invoke($this->service, ...$args);
    }

    /**
     * บิล Deep 39 ที่ session ปิดไปแล้ว $closedMinutesAgo นาที
     *
     * @param  string  $platform  facebook | line
     */
    protected function closedDeepReading(int $closedMinutesAgo, string $platform = 'facebook'): FortuneReading
    {
        $reading = new class extends FortuneReading
        {
            /** @var array<string, mixed> */
            public array $memoryState = [];

            public function setConversationState(string $key, $value): void
            {
                $this->memoryState[$key] = $value;
            }

            public function getConversationState(string $key, $default = null)
            {
                return array_key_exists($key, $this->memoryState) && $this->memoryState[$key] !== null
                    ? $this->memoryState[$key]
                    : $default;
            }

            public function refresh(): static
            {
                return $this;
            }
        };

        $reading->forceFill([
            'id' => 14643,
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'is_paid' => true,
            // จ่ายวันนี้ → closingInviteLine ใช้บรรทัด "ไม่ขายซ้ำ" (ไม่แตะ RepurchaseCta/settings)
            'paid_at' => now()->subMinutes(60),
            'facebook_user_name' => 'อิสรีย์',
            'platform' => $platform,
            $platform === 'line' ? 'platform_user_id' : 'facebook_user_id' => $platform === 'line' ? 'U1234567890abcdef' : '37035672059412704',
        ]);

        $reading->memoryState = [
            'pro_session_active' => false,
            'pro_session_type' => 'deep',
            'pro_session_window_minutes' => 7,
            'pro_session_opened_at' => now()->subMinutes($closedMinutesAgo + 13)->toIso8601String(),
            'pro_session_ready_at' => now()->subMinutes($closedMinutesAgo + 13)->toIso8601String(),
            'pro_session_closed_at' => now()->subMinutes($closedMinutesAgo)->toIso8601String(),
        ];

        return $reading;
    }

    /** ✅ คำถามจริงภายในช่วงผ่อนผัน → เปิด session ต่อสั้น ๆ แล้วส่งเข้าเส้นตอบ Pro Session */
    public function test_question_inside_grace_is_answered_through_pro_session(): void
    {
        $reading = $this->closedDeepReading(8);
        $this->service->candidate = $reading;

        $result = $this->invoke('maybeHandlePostProSessionFollowup', '37035672059412704', self::REAL_QUESTION, null);

        // ส่งเข้า handleProSession ด้วยข้อความเต็ม (เส้นนี้จด DB ก่อน buffer = deploy ล้างไม่หาย)
        $this->assertCount(1, $this->service->handledQuestions);
        $this->assertSame(self::REAL_QUESTION, $this->service->handledQuestions[0]);
        $this->assertSame('silent_skip', $result['action']);

        // session ถูกเปิดต่อจริง และ isInProSession มองเห็น (job ที่จะตอบต้องผ่านด่านนี้)
        $this->assertTrue($reading->getConversationState('pro_session_active'));
        $this->assertSame(FortuneConversationService::PRO_SESSION_GRACE_REOPEN_MINUTES, $reading->getConversationState('pro_session_window_minutes'));
        $this->assertTrue($reading->getConversationState('pro_session_grace_used'));
        $this->assertTrue($this->invoke('isInProSession', $reading));
    }

    /** ✅ ใช้สิทธิ์เปิดต่อได้ครั้งเดียวต่อบิล — ครั้งถัดไปได้คำตอบสุภาพ ไม่เงียบ */
    public function test_grace_reopen_is_once_per_reading(): void
    {
        $reading = $this->closedDeepReading(3);
        $this->service->candidate = $reading;

        $this->invoke('maybeHandlePostProSessionFollowup', '37035672059412704', self::REAL_QUESTION, null);

        // ช่วงต่อเวลาหมด (ปิดอีกรอบ) แล้วลูกค้าถามอีก — ยังอยู่ใน 15 นาทีหลังปิด
        Carbon::setTestNow(now()->addMinutes(6));
        $this->invoke('clearProSessionFlags', $reading);
        Carbon::setTestNow(now()->addMinutes(2));

        $result = $this->invoke('maybeHandlePostProSessionFollowup', '37035672059412704', 'แล้วต้องทำยังไงต่อดีคะ', null);

        $this->assertCount(1, $this->service->handledQuestions, 'ห้ามเปิด session ต่อซ้ำเป็นรอบที่สอง');
        $this->assertSame('pro_session_after_window', $result['action']);
    }

    /** ✅ เลยช่วงผ่อนผัน → ตอบสุภาพในบทแม่หมอทันที ไม่เงียบ และไม่วนตอบกล่องเดิมซ้ำ */
    public function test_after_grace_gets_polite_in_character_reply_not_silence(): void
    {
        $reading = $this->closedDeepReading(40);
        $this->service->candidate = $reading;

        $result = $this->invoke('maybeHandlePostProSessionFollowup', '37035672059412704', self::REAL_QUESTION, null);

        $this->assertNotNull($result, 'ลูกค้าที่จ่ายเงินแล้วถามจริง ห้ามได้ความเงียบ');
        $this->assertSame('pro_session_after_window', $result['action']);
        $this->assertNotSame('', trim((string) $result['message']));
        $this->assertStringContainsString('คุณอิสรีย์', $result['message']);
        $this->assertStringContainsString('ช่วงถามต่อของดวงรอบนี้ปิดไปแล้ว', $result['message']);
        $this->assertStringContainsString('อ่านคำทำนายล่าสุด', $result['message']);
        // อยู่ในบทแม่หมอ — ห้ามหลุดคำระบบ
        foreach (['AI', 'ระบบ', 'คิว', 'error'] as $word) {
            $this->assertStringNotContainsString($word, $result['message']);
        }
        // ไม่เปิด session ต่อ ไม่เรียก AI
        $this->assertSame([], $this->service->handledQuestions);
        $this->assertFalse($reading->getConversationState('pro_session_active'));

        // ข้อความถัดไปในช่วงพัก → ปล่อยไหลตาม flow เดิม (ไม่ส่งกล่องเดิมซ้ำทุกข้อความ)
        $again = $this->invoke('maybeHandlePostProSessionFollowup', '37035672059412704', 'แล้วจะชนะคดีไหมคะ', null);
        $this->assertNull($again);
    }

    /** ✅ ขอบคุณ / ลา / ฟิลเลอร์ → ยังเงียบเหมือนเดิม (ด่านนี้ไม่แตะเลย) */
    public function test_farewell_thanks_and_filler_stay_silent(): void
    {
        foreach ([8, 40] as $closedAgo) {
            $reading = $this->closedDeepReading($closedAgo);
            $before = $reading->memoryState;
            $this->service->candidate = $reading;

            foreach (['ขอบคุณค่ะ', 'ขอบคุณมากนะคะแม่หมอ 🙏', 'ค่ะ', 'คะ', '👍', 'บายค่ะ', 'ฝันดีนะคะ', 'โอเคค่ะ', 'แม่นมากค่ะ ตรงทุกข้อเลย'] as $text) {
                $result = $this->invoke('maybeHandlePostProSessionFollowup', '37035672059412704', $text, null);
                $this->assertNull($result, "'{$text}' ต้องไม่ได้ข้อความตอบจากด่านนี้");
            }

            $this->assertSame([], $this->service->handledQuestions);
            $this->assertSame($before, $reading->memoryState, 'ข้อความลา/ขอบคุณต้องไม่แตะ state ของบิล');
        }
    }

    /** ✅ ข้อความที่มีเส้นของตัวเอง (ดูดวงใหม่ / ถามราคา) → ไม่แย่ง */
    public function test_new_reading_and_pricing_requests_are_not_intercepted(): void
    {
        $this->service->candidate = $this->closedDeepReading(8);

        foreach (['ดูดวง', 'ดูดวงความรักค่ะ', 'ราคาเท่าไหร่คะ'] as $text) {
            $this->assertNull($this->invoke('maybeHandlePostProSessionFollowup', '37035672059412704', $text, null), $text);
        }
        $this->assertSame([], $this->service->handledQuestions);
    }

    /**
     * ✅ LINE ใช้ replyToken เท่านั้น
     *
     * - ช่วงผ่อนผัน: คืน silent_skip (จาก settle-buffer) = action ที่ ChannelManager ฝาก replyToken
     *   ลง ReplyTokenVault ให้ job ยืมไปตอบฟรี และต้องไม่ติดอาวุธ cron "หมดเวลา" ที่ push
     * - เลยช่วงผ่อนผัน: คำตอบคืนในเทิร์นนี้เลย (ไม่มี job/คิว) = webhook ตอบด้วย replyToken ของตัวเอง
     */
    public function test_line_uses_reply_token_only(): void
    {
        Queue::fake();

        // — เลยช่วงผ่อนผัน: synchronous reply —
        $polite = $this->closedDeepReading(40, 'line');
        $this->service->candidate = $polite;

        $result = $this->invoke('maybeHandlePostProSessionFollowup', 'U1234567890abcdef', self::REAL_QUESTION, null);

        $this->assertSame('pro_session_after_window', $result['action']);
        $this->assertNotSame('', trim((string) $result['message']));
        $this->assertArrayNotHasKey('force_push', $result);
        $this->assertArrayNotHasKey('push', $result);
        Queue::assertNothingPushed();

        // — ช่วงผ่อนผัน: เทิร์นนี้เงียบ (ฝาก token) + ไม่มีทาง push ข้อความปิดท้าย —
        $grace = $this->closedDeepReading(5, 'line');
        $this->service->candidate = $grace;

        $result = $this->invoke('maybeHandlePostProSessionFollowup', 'U1234567890abcdef', self::REAL_QUESTION, null);
        $this->assertSame('silent_skip', $result['action']);

        // ช่วงต่อเวลาหมดไปนานแล้ว — cron fortune:deep-auto-finalize ต้องไม่หยิบบิลนี้ไป push "หมดเวลา" ซ้ำ
        Carbon::setTestNow(now()->addMinutes(30));
        $this->assertFalse($this->invoke('isDeepProSessionTimedOutUnnotified', $grace));
    }

    /** ✅ บิลเกิน 24 ชม. หลังเปิด session → ไม่ใช่เคสนี้ (flow เดิม) */
    public function test_old_sessions_are_left_to_normal_flow(): void
    {
        $reading = $this->closedDeepReading(60 * 25);

        $this->assertSame('none', $this->invoke('postProSessionPhase', $reading));
    }

    /** ✅ บิลก่อน deploy นี้ไม่มี pro_session_closed_at → คำนวณจาก started_at + window ได้ */
    public function test_closed_at_falls_back_to_started_plus_window_for_legacy_readings(): void
    {
        $reading = $this->closedDeepReading(0);
        unset($reading->memoryState['pro_session_closed_at']);
        $reading->memoryState['pro_session_started_at'] = now()->subMinutes(12)->toIso8601String();

        // ปิดจริงตอน started+7 = 5 นาทีที่แล้ว → ยังอยู่ช่วงผ่อนผัน
        $this->assertSame('grace', $this->invoke('postProSessionPhase', $reading));

        $reading->memoryState['pro_session_started_at'] = now()->subMinutes(60)->toIso8601String();
        $this->assertSame('polite', $this->invoke('postProSessionPhase', $reading));
    }

    /**
     * ✅ ต้นตอจริง: เปิด session รอบใหม่บนบิลเดิม ต้องล้างนาฬิกาของรอบก่อน
     *
     * #14643: started_at = 5 พ.ย. 23:44 ค้าง → session 7 พ.ย. หมดเวลาตั้งแต่เปิด
     */
    public function test_enter_pro_session_clears_stale_timer_from_previous_round(): void
    {
        $reading = $this->closedDeepReading(0);
        $reading->memoryState = [
            'pro_session_active' => false,
            'pro_session_type' => 'deep',
            'pro_session_started_at' => '2026-11-05T23:44:12+07:00',
            'pro_session_timeout_notified' => true,
            'pro_session_timeout_notified_at' => '2026-11-05T23:54:03+07:00',
            'pro_session_grace_used' => true,
            'time_notice_sent' => true,
        ];

        $this->invoke('enterProSession', $reading, 'deep');

        $this->assertNull($reading->getConversationState('pro_session_started_at'));
        $this->assertFalse($reading->getConversationState('pro_session_timeout_notified'));
        $this->assertFalse($reading->getConversationState('pro_session_grace_used'));
        $this->assertTrue($this->invoke('isInProSession', $reading), 'session ใหม่ต้องยังเปิดอยู่ (รอคำถามแรก)');

        // 8 นาทีหลังส่งคำทำนาย (เวลาที่ลูกค้าจริงถาม 16:48 vs ส่ง 16:35 ≈ 13 นาที) — ยังอยู่ในสแตนบาย
        Carbon::setTestNow(now()->addMinutes(13));
        $this->assertTrue($this->invoke('isInProSession', $reading));
        $this->assertFalse($this->invoke('proSessionWindowExpired', $reading), 'sweep cron ต้องไม่กวาดทิ้ง');
    }
}

/**
 * ตัวแทน service — ตัดส่วนที่แตะ DB ออก แล้วจดสิ่งที่ถูกเรียกไว้ตรวจ
 */
class PostSessionServiceDouble extends FortuneConversationService
{
    public ?FortuneReading $candidate = null;

    /** @var array<int, string> */
    public array $handledQuestions = [];

    protected function findRecentClosedDeepProSessionReading(string $userId): ?FortuneReading
    {
        if ($this->candidate !== null && $this->postProSessionPhase($this->candidate) !== 'none') {
            return $this->candidate;
        }

        return null;
    }

    /** แทนเส้นจริง: settle-buffer คืน silent_skip แล้ว job ตอบทีหลัง */
    protected function handleProSession(FortuneReading $reading, string $messageText, ?array $userProfile = null, bool $skipSettle = false): array
    {
        $this->handledQuestions[] = $messageText;

        return ['action' => 'silent_skip', 'message' => null, 'reading' => $reading];
    }

    protected function resolveBirthdateCorrectionTarget(string $userId, string $messageText): ?FortuneReading
    {
        return null;
    }
}
