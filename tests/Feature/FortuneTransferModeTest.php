<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use App\Services\Fortune\FortuneBotMode;
use App\Services\Fortune\TransferModeTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 🔀 โหมด TRANSFER — ดักหน้าแชท FB พาลูกค้าไปดูดวงฟรีที่เว็บ/LINE
 *
 * ล็อกกติกาที่พลาดแล้วเสียลูกค้า/เสียเงิน:
 *
 *  1. โหมด classic = ไม่มีอะไรเปลี่ยนเลย (ตัวดักต้องคืน null ทุกกรณี)
 *  2. LINE ไม่ถูกดัก — เป็นปลายทางที่เราอยากให้ลูกค้าไปใช้
 *  3. ไม่มีปลายทางให้ไป (ปุ่มเว็บปิด + ไม่ได้ตั้ง LINE OA) = ห้ามดัก
 *     ไม่งั้นลูกค้าเจอกล่องที่กดแล้วไม่มีอะไรเกิดขึ้น
 *  4. ลูกค้าที่ยืนยันว่าทำเว็บ/ไลน์ไม่เป็น ต้องไม่ถูกดักซ้ำอีก (จำ 30 วัน)
 *  5. พยายามพาไปครบตามที่ตั้ง → ต้องถามความสมัครใจ แล้วยอมเปิดบิลให้
 *  6. rollout % ต้องให้ผลเดิมกับคนเดิมทุกครั้ง (ไม่สลับบุคลิกไปมา)
 *  7. รอบแจกสิทธิ์ฟรีใหม่ (free_card_regrant_at) = คนที่เคยใช้ได้สิทธิ์ใหม่
 */
class FortuneTransferModeTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '61550000000001';

    private function settings(array $override = []): FortuneTellingSetting
    {
        // ⚠️ ต้องล้าง static memo **ก่อน** อ่านครั้งแรก
        //    getSettings() จำ instance ไว้ใน static property ซึ่ง **ค้างข้ามเทสต์**
        //    (RefreshDatabase ย้อน DB แต่ไม่ล้าง static) → ได้ instance ของแถวที่
        //    ถูกย้อนไปแล้ว → forceFill()->save() ยิง UPDATE แถวที่ไม่มีอยู่
        //    = 0 rows affected โดยไม่ error → ค่าที่ตั้งหายเงียบ ๆ กลับไปใช้ default
        //    (เจอจริงบน CI: เทสต์ที่คาด array ล้มหมด เพราะโหมดกลับเป็น classic)
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->forceFill(array_merge([
            'fortune_bot_mode' => FortuneBotMode::MODE_TRANSFER,
            'transfer_rollout_percent' => 100,
            'transfer_fallback_attempts' => 3,
            'transfer_fallback_days' => 30,
            'transfer_box_cooldown_hours' => 24,
            // ต้องมีปลายทางอย่างน้อยหนึ่งทาง ไม่งั้นตัวดักจะปล่อยผ่าน (ตามดีไซน์)
            'line_bot_basic_id' => '@maemor',
        ], $override))->save();

        FortuneTellingSetting::clearSettingsCache();

        $fresh = FortuneTellingSetting::getSettings();

        // fail fast ถ้าค่าไม่ลง DB จริง — กันเทสต์ที่ "ผ่านเพราะอ่านค่า default"
        // (เช็คเฉพาะค่า scalar ที่ไม่ใช่ null — null/Carbon เทียบตรง ๆ ไม่ได้)
        foreach ($override as $key => $expected) {
            if (is_scalar($expected)) {
                $this->assertSame(
                    (string) $expected,
                    (string) $fresh->{$key},
                    "ตั้งค่า {$key} ไม่ลง DB — เทสต์จะอ่านค่า default แล้วผลลวง"
                );
            }
        }

        return $fresh;
    }

    /**
     * ตัวทดสอบ trait — จำลอง FortuneConversationService เท่าที่ trait ต้องใช้
     */
    private function interceptor(FortuneTellingSetting $settings, string $platform = 'facebook'): object
    {
        return new class($settings, $platform)
        {
            use TransferModeTrait;

            public function __construct(public $settings, public string $currentPlatform) {}

            /** เปิด public ให้เทสต์เรียกได้ */
            public function run(string $psid, string $text): ?array
            {
                return $this->maybeTransferIntercept($psid, $text);
            }

            /** ฟรีออโต้บน LINE */
            public function runLineAutoFree(string $uid, string $text): ?array
            {
                return $this->maybeAutoFreeCardOnLine($uid, null, $text);
            }

            /** directive ที่ฉีดให้ AI */
            public function runDirective(string $platform, string $uid): string
            {
                return $this->buildTransferChatDirective($platform, $uid);
            }

            /** stub — ของจริงอยู่ใน FreeCardConversationTrait (เทสต์แค่ว่า "ถูกเรียกไหม") */
            protected function startFreeCardFlow(string $platformUserId, ?array $userProfile = null, ?string $customerMessage = null, bool $skipQuestionGate = false): array
            {
                return [
                    'action' => 'free_card_drawn',
                    'skip_question_gate' => $skipQuestionGate,
                    'reading' => null,
                ];
            }

            // ตัวตรวจ intent ของคลาสจริง — จำลองแบบง่ายให้ครอบเคสที่เทสต์ใช้
            protected function isGenericFortuneRequest(string $text): bool
            {
                return str_contains($text, 'ดูดวง') || str_contains($text, 'ทำนาย');
            }

            protected function isExplicitlyAsking39(string $text): bool
            {
                return str_contains($text, '39');
            }
        };
    }

    public function test_โหมด_classic_ไม่ดักอะไรเลย(): void
    {
        $settings = $this->settings(['fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC]);

        $this->assertNull($this->interceptor($settings)->run(self::PSID, 'ดูดวง'));
    }

    public function test_โหมด_transfer_ดักคำขอดูดวงแล้วส่งกล่องพาไป(): void
    {
        $settings = $this->settings();

        $result = $this->interceptor($settings)->run(self::PSID, 'ดูดวง');

        $this->assertIsArray($result);
        $this->assertSame('transfer_box', $result['action']);
        $this->assertNotEmpty($result['fb_template']);

        // ต้องนับความพยายาม เพื่อให้ ladder เดินไปถึงทางถอยได้
        $this->assertSame(1, (new FortuneBotMode($settings))->attempts('facebook', self::PSID));
    }

    public function test_ไม่ดักข้อความที่ไม่ได้ขอดูดวง(): void
    {
        $settings = $this->settings();

        $this->assertNull($this->interceptor($settings)->run(self::PSID, 'สวัสดีค่ะแม่หมอ'));
    }

    public function test_lin_e_ไม่ถูกดัก(): void
    {
        $settings = $this->settings();

        $this->assertNull($this->interceptor($settings, 'line')->run('U'.str_repeat('a', 32), 'ดูดวง'));
    }

    public function test_ไม่มีปลายทางให้ไป_ต้องไม่ดัก(): void
    {
        // ปุ่มเว็บปิด (default) + ไม่ได้ตั้ง LINE OA = ไม่มีที่ให้ไป
        $settings = $this->settings(['line_bot_basic_id' => null, 'enable_web_fortune_button' => false]);

        $this->assertNull($this->interceptor($settings)->run(self::PSID, 'ดูดวง'));
    }

    public function test_ลูกค้าบอกว่าทำไม่เป็น_ต้องถามความสมัครใจ(): void
    {
        $settings = $this->settings();

        foreach (['ไม่มีไลน์', 'กดไม่ได้', 'ทำไม่เป็นค่ะ', 'ขอดูที่นี่ได้ไหม'] as $text) {
            $result = $this->interceptor($settings)->run(self::PSID, $text);

            $this->assertIsArray($result, "ควรดัก: {$text}");
            $this->assertSame('transfer_stay_confirm', $result['action'], "ควรถามยืนยัน: {$text}");
        }
    }

    public function test_พยายามครบแล้วต้องถามความสมัครใจ(): void
    {
        $settings = $this->settings(['transfer_fallback_attempts' => 2]);

        FortuneUserCredit::getOrCreate(self::PSID, 'facebook')
            ->forceFill(['transfer_attempts' => 2])->save();

        $result = $this->interceptor($settings)->run(self::PSID, 'ดูดวง');

        $this->assertSame('transfer_stay_confirm', $result['action']);
    }

    public function test_ยืนยันขอดูในแชทแล้ว_ต้องไม่ถูกดักอีก(): void
    {
        $settings = $this->settings();
        $mode = new FortuneBotMode($settings);

        $mode->grantFbFallback('facebook', self::PSID);

        $this->assertTrue($mode->hasFbFallback('facebook', self::PSID));
        $this->assertNull($this->interceptor($settings)->run(self::PSID, 'ดูดวง'));
    }

    public function test_สิทธิ์ที่ยืนยันไว้หมดอายุตามจำนวนวัน(): void
    {
        $settings = $this->settings(['transfer_fallback_days' => 7]);

        FortuneUserCredit::getOrCreate(self::PSID, 'facebook')
            ->forceFill(['fb_fallback_granted_at' => now()->subDays(8)])->save();

        $this->assertFalse((new FortuneBotMode($settings))->hasFbFallback('facebook', self::PSID));
    }

    public function test_rollout_ให้ผลเดิมกับคนเดิมทุกครั้ง(): void
    {
        $mode = new FortuneBotMode($this->settings(['transfer_rollout_percent' => 50]));

        $first = $mode->inRollout(self::PSID);
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame($first, $mode->inRollout(self::PSID), 'ผลต้องคงที่ ไม่สลับไปมา');
        }

        // 0% = ไม่มีใครเข้า · 100% = ทุกคนเข้า
        $this->assertFalse((new FortuneBotMode($this->settings(['transfer_rollout_percent' => 0])))->inRollout(self::PSID));
        $this->assertTrue((new FortuneBotMode($this->settings(['transfer_rollout_percent' => 100])))->inRollout(self::PSID));
    }

    public function test_รอบแจกสิทธิ์ฟรีใหม่_คนที่เคยใช้ได้สิทธิ์ใหม่(): void
    {
        $settings = $this->settings();

        FortuneReading::create([
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'facebook_user_id' => self::PSID,
            'reading_type' => FortuneReading::READING_TYPE_FREE_CARD,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'questions' => [],
            'ai_response' => 'คำทำนายฟรีของรอบก่อน',
            'responded_at' => now()->subDays(3),
        ]);

        // ยังไม่ตั้งรอบแจกใหม่ → ถือว่าใช้สิทธิ์ไปแล้ว
        $this->assertFalse((new FortuneBotMode($settings))->freeCardAvailable('facebook', self::PSID));

        // ตั้งรอบแจกใหม่หลังจากนั้น → ได้สิทธิ์ใหม่ทันที
        $settings = $this->settings(['free_card_regrant_at' => now()->subDay()]);
        $this->assertTrue((new FortuneBotMode($settings))->freeCardAvailable('facebook', self::PSID));
    }

    public function test_ความยาวคำทำนายฟรีตั้งค่าได้(): void
    {
        $this->assertSame(0, (new FortuneBotMode($this->settings(['free_card_max_chars' => 0])))->freeCardMaxChars());
        $this->assertSame(500, (new FortuneBotMode($this->settings(['free_card_max_chars' => 500])))->freeCardMaxChars());
        // clamp ขอบเขต
        $this->assertSame(200, (new FortuneBotMode($this->settings(['free_card_max_chars' => 10])))->freeCardMaxChars());
        $this->assertSame(2000, (new FortuneBotMode($this->settings(['free_card_max_chars' => 9999])))->freeCardMaxChars());
    }

    // ═══════════════════════════════════════════════════════════════
    // 🩹 (2026-07-28) ชุดที่ปิดรูรั่ว "เปิดโหมดแล้วใช้ไม่ได้จริง"
    // ═══════════════════════════════════════════════════════════════

    /**
     * 🔴 บั๊กที่ทำให้ขา LINE ตายเงียบ: prod ปิด enable_free_card_reading อยู่
     *    แต่กล่องบน FB โฆษณา "ดูดวงฟรี" พร้อมปุ่มไป LINE
     *    → โหมด transfer ต้องเปิดฟรีบน LINE ให้เอง
     */
    public function test_ฟรีบน_line_เปิดเองในโหมด_transfer_แม้สวิตช์หลักปิด(): void
    {
        $settings = $this->settings(['enable_free_card_reading' => false]);
        $mode = new FortuneBotMode($settings);

        $this->assertTrue($mode->freeCardEnabledFor('line'), 'โหมด transfer ต้องเปิดฟรีบน LINE ให้เอง');

        // FB ไม่ได้ — ในโหมดนี้ฟรีต้องเกิดที่ปลายทาง ไม่ใช่ในแชท FB
        $this->assertFalse($mode->freeCardEnabledFor('facebook'));

        // โหมด classic = เคารพสวิตช์หลัก 100% เหมือนเดิม
        $classic = new FortuneBotMode($this->settings([
            'fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC,
            'enable_free_card_reading' => false,
        ]));
        $this->assertFalse($classic->freeCardEnabledFor('line'));
        $this->assertTrue(
            (new FortuneBotMode($this->settings([
                'fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC,
                'enable_free_card_reading' => true,
            ])))->freeCardEnabledFor('line')
        );
    }

    public function test_ฟรีออโต้บน_line_เปิดไพ่ให้เลยไม่ย้อนถาม(): void
    {
        $settings = $this->settings(['enable_free_card_reading' => false]);
        $uid = 'U'.str_repeat('b', 32);

        $result = $this->interceptor($settings, 'line')->runLineAutoFree($uid, 'สวัสดีค่ะ');

        $this->assertIsArray($result, 'ลูกค้า LINE ต้องได้ไพ่ฟรีทันทีโดยไม่ต้องพิมพ์ "ดูดวง"');
        $this->assertTrue($result['skip_question_gate'], 'ห้ามย้อนถาม "อยากดูเรื่องอะไร" — ลูกค้าไม่ได้ขอดูดวงด้วยซ้ำ');
    }

    /**
     * 🚫 ฟรีออโต้ยิงที่ข้อความแรกทุกข้อความ — ต้องไม่ทับเรื่องเงิน/แอดมิน/ยกเลิก
     *    (ลูกค้าถามเลขบัญชีแล้วได้ไพ่ตอบกลับ = พังกว่าไม่มีฟรี)
     */
    public function test_ฟรีออโต้บน_line_ต้องไม่ทับเรื่องเงินหรือแอดมิน(): void
    {
        $settings = $this->settings();
        $uid = 'U'.str_repeat('c', 32);
        $interceptor = $this->interceptor($settings, 'line');

        foreach ([
            'ขอเลขบัญชีหน่อยค่ะ',
            'โอนแล้วนะคะ',
            'ขอคุยกับแอดมิน',
            'ขอยกเลิกค่ะ',
            'ส่งสลิปให้แล้ว',
        ] as $text) {
            $this->assertNull($interceptor->runLineAutoFree($uid, $text), "ห้ามแจกไพ่ทับ: {$text}");
        }

        // โทเคนระบบก็ห้ามตีความเป็นคำขอดูดวง
        $this->assertNull($interceptor->runLineAutoFree($uid, 'ref_'.str_repeat('a', 32)));
    }

    /**
     * 🗣️ AI ต้องไม่พูดสวนกล่อง ("พิมพ์ ดูดวง 99") หลังบอทเพิ่งชวนไปเว็บ
     */
    public function test_directive_ฉีดเฉพาะโหมด_transfer_และหยุดเมื่อลูกค้าเลือกอยู่แชท(): void
    {
        $settings = $this->settings();
        $interceptor = $this->interceptor($settings);

        $directive = $interceptor->runDirective('facebook', self::PSID);
        $this->assertNotSame('', $directive);
        $this->assertStringContainsString('ไม่ทำนายในแชทนี้', $directive);

        // LINE ไม่ต้องฉีด (ไม่ได้ถูกดัก)
        $this->assertSame('', $interceptor->runDirective('line', 'U'.str_repeat('d', 32)));

        // ลูกค้ายืนยันขอดูในแชทแล้ว → กลับไปพูดแบบเดิมได้ (เขาจะซื้อที่นี่)
        (new FortuneBotMode($settings))->grantFbFallback('facebook', self::PSID);
        $this->assertSame('', $interceptor->runDirective('facebook', self::PSID));

        // โหมด classic ต้องไม่ฉีดเด็ดขาด
        $classic = $this->settings(['fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC]);
        $this->assertSame('', $this->interceptor($classic)->runDirective('facebook', '61550000000009'));
    }

    /**
     * 🔘 ปุ่มท้ายข้อความ AI — เคยสร้างไว้แล้วไม่มีใครเรียก (dead code)
     *    เทสต์นี้ล็อกว่า "ต้องมีคนเรียก" ไม่ใช่แค่มีเมธอด
     */
    public function test_ปุ่มท้ายข้อความ_ai_ถูกแนบเฉพาะโหมด_transfer(): void
    {
        $settings = $this->settings();
        $manager = new \App\Services\FortuneChannelManager($settings);
        $rich = new \App\Services\FacebookRichMessageService($settings);

        $call = function (string $action, string $psid) use ($manager, $rich) {
            $m = new \ReflectionMethod($manager, 'buildTransferQuickRepliesFor');
            $m->setAccessible(true);

            return $m->invoke($manager, $action, $psid, $rich);
        };

        $qr = $call('ai_chat_response', self::PSID);
        $this->assertIsArray($qr, 'ข้อความคุยเล่นบน FB ต้องมีปุ่มพาไปเว็บ/LINE ท้ายกล่อง');
        $this->assertNotEmpty($qr);

        // action ที่เป็นเงิน/โฟลที่ทำอยู่ ห้ามแตะ
        $this->assertNull($call('payment_check_pending', self::PSID));

        // ลูกค้ายืนยันขอดูในแชทแล้ว → หยุดตื๊อ (rule_listen_dont_pitch_when_declining)
        (new FortuneBotMode($settings))->grantFbFallback('facebook', self::PSID);
        $this->assertNull($call('ai_chat_response', self::PSID));

        // โหมด classic ต้องไม่แตะปุ่มเดิมเลย
        $classicManager = new \App\Services\FortuneChannelManager(
            $this->settings(['fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC])
        );
        $m = new \ReflectionMethod($classicManager, 'buildTransferQuickRepliesFor');
        $m->setAccessible(true);
        $this->assertNull($m->invoke($classicManager, 'ai_chat_response', '61550000000008', $rich));
    }

    /**
     * 🚨 ลูกค้าที่จ่ายเงินแล้ว/มีบิลค้าง ห้ามโดนปุ่มหรือ directive แทรกเด็ดขาด
     *    (คนจ่าย 99 แล้วถามต่อใน Pro Session ต้องไม่โดนตอบว่า "ไปดูที่เว็บนะคะ")
     */
    public function test_ลูกค้าที่จ่ายเงินแล้วต้องไม่โดนปุ่มหรือ_directive_แทรก(): void
    {
        $settings = $this->settings();

        FortuneReading::create([
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'facebook_user_id' => self::PSID,
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_PAID,
            'is_paid' => true,
            'questions' => [],
        ]);

        $mode = new FortuneBotMode($settings);
        $this->assertFalse(
            $mode->shouldNudgeToTransfer('facebook', self::PSID),
            'ลูกค้าที่จ่ายแล้ว/มีโฟลค้าง ห้ามถูกชวนย้ายช่องทางกลางคัน'
        );

        // directive ต้องไม่ถูกฉีด
        $this->assertSame('', $this->interceptor($settings)->runDirective('facebook', self::PSID));

        // ปุ่มท้ายข้อความต้องไม่ถูกแทน (ใช้ปุ่มเดิมของโฟลที่ทำอยู่)
        $manager = new \App\Services\FortuneChannelManager($settings);
        $m = new \ReflectionMethod($manager, 'buildTransferQuickRepliesFor');
        $m->setAccessible(true);
        $this->assertNull(
            $m->invoke($manager, 'ai_chat_response', self::PSID, new \App\Services\FacebookRichMessageService($settings))
        );
    }

    /**
     * 🚨 Pro Session = reading เป็น completed แล้ว (ไม่ติดกำแพง activeReading)
     *    แต่ลูกค้าจ่ายเงินแล้วและกำลังถามต่อ — ห้ามโดนชวนย้ายช่องทางกลางคัน
     */
    public function test_ลูกค้าใน_pro_session_ต้องไม่โดนชวนย้ายช่องทาง(): void
    {
        $settings = $this->settings();
        $psid = '61550000000123';

        $reading = FortuneReading::create([
            'platform' => 'facebook',
            'platform_user_id' => $psid,
            'facebook_user_id' => $psid,
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'is_paid' => true,
            'questions' => [],
        ]);

        // จบคำทำนายไปนานแล้ว (พ้นหน้าต่าง 30 นาที) แต่ธง pro session ยังเปิด
        $reading->forceFill([
            'conversation_state' => ['pro_session_active' => true],
            'updated_at' => now()->subHours(2),
        ])->save();

        $this->assertFalse(
            (new FortuneBotMode($settings))->shouldNudgeToTransfer('facebook', $psid),
            'คนที่จ่ายเงินแล้วกำลังถามต่อใน Pro Session ห้ามโดนแทรก'
        );

        // ปิด session แล้ว + พ้นหน้าต่าง → ชวนย้ายช่องทางได้ตามปกติ
        $reading->forceFill(['conversation_state' => ['pro_session_active' => false]])->save();
        FortuneReading::where('id', $reading->id)->update(['updated_at' => now()->subHours(2)]);

        $this->assertTrue((new FortuneBotMode($settings))->shouldNudgeToTransfer('facebook', $psid));
    }

    /**
     * 🙏 กลุ่ม "ทำเว็บ/ไลน์ไม่เป็น" ต้องไม่เจอเกตที่ 3-4 ซ้อน
     *    (เคสจริงที่เสียลูกค้าไปแล้ว: ผู้สูงอายุวนกล่องกติกาจนเลิก)
     */
    public function test_ผ่อนเกตกติกาให้กลุ่มที่ยืนยันขอดูในแชท(): void
    {
        $settings = $this->settings([
            'fortune_consent_enabled' => true,
            'enable_consent_audio_code' => true,
            'enable_consent_quiz' => true,
            'consent_audio_code_min_unpaid_bills' => 0,
            'consent_quiz_min_unpaid_bills' => 0,
        ]);

        $service = new \App\Services\FortuneConversationService($settings);
        $service->setPlatform('facebook');

        $audio = new \ReflectionMethod($service, 'shouldUseAudioCode');
        $audio->setAccessible(true);
        $quiz = new \ReflectionMethod($service, 'shouldUseConsentQuiz');
        $quiz->setAccessible(true);

        // ลูกค้าทั่วไป → เกตยังทำงานเหมือนเดิม
        $this->assertTrue($audio->invoke($service, self::PSID), 'ลูกค้าทั่วไปต้องยังเจอเกตเสียง');
        $this->assertTrue($quiz->invoke($service, self::PSID), 'ลูกค้าทั่วไปต้องยังเจอแบบสอบถาม');

        // ยืนยันว่าทำไม่เป็น → ผ่อนทั้งสองเกต (เหลือกล่องกติกาเดิมด่านเดียว)
        (new FortuneBotMode($settings))->grantFbFallback('facebook', self::PSID);

        $this->assertFalse($audio->invoke($service, self::PSID));
        $this->assertFalse($quiz->invoke($service, self::PSID));
    }
}
