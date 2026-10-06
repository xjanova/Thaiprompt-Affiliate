<?php

namespace Tests\Feature;

use App\Services\Fortune\FortuneFarewellJudge;
use App\Services\FortuneAIService;
use App\Services\FortuneConversationService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * 🌙 ตัดสิน "ลูกค้าลา" vs "ลูกค้าคุยต่อ" (2026-10-06)
 *
 * ต้นเรื่อง: Noi Fuchs ก๊อปคำชวน 'พิมพ์ "ดูดวง" เพื่อเปิดไพ่…' ส่งกลับ → บอทอวยพรแล้วเงียบทั้งวัน
 * ข้อความทุกตัวในเทสต์นี้มาจาก log prod 2026-09-29 → 2026-10-06
 *
 * ล็อกไว้ 4 เรื่อง:
 *   1. ขอบคุณ/สาธุ ล้วน = ปิดทันที ไม่เรียก AI (เคสส่วนใหญ่ ต้องฟรี)
 *   2. มีสัญญาณคุยต่อ (ดูดวง / คำถาม / ดูแบบ99 / ปีเกิด) = ห้ามปิด แม้มีคำขอบคุณปน
 *   3. ก้ำกึ่ง → AI อ่านพร้อมข้อความล่าสุดของแม่หมอ · AI ล่ม = คุยต่อ
 *   4. ขาปลุก (หลังอวยพรแล้ว) ถาม AI ได้จำกัดต่อวัน
 *
 * ไม่แตะ DB — AI ถูก mock · ประวัติแชทถูกแทนด้วยค่าคงที่
 */
class FortuneFarewellJudgeTest extends TestCase
{
    private const UID = '27000000000000099';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** service จริงที่ไม่เรียก constructor (constructor อ่าน DB) — เปิด judgeFarewell ให้เทสต์เรียกได้ */
    private function service(FortuneAIService $ai, ?string $lastBot = null): object
    {
        return new class($ai, $lastBot) extends FortuneConversationService
        {
            public function __construct(FortuneAIService $ai, private ?string $lastBot)
            {
                $this->aiService = $ai;
            }

            protected function lastBotMessageFor(string $userId): ?string
            {
                return $this->lastBot;
            }

            public function judge(string $text, bool $alreadyClosed): bool
            {
                return $this->judgeFarewell(FortuneFarewellJudgeTest::uid(), $text, $alreadyClosed);
            }
        };
    }

    public static function uid(): string
    {
        return self::UID;
    }

    /** AI ที่ห้ามถูกเรียก */
    private function aiNeverCalled(): FortuneAIService
    {
        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldNotReceive('chatWithCustomSystemPrompt');

        return $ai;
    }

    /** AI ที่ตอบคำเดียว และเก็บ prompt ที่ได้รับไว้ตรวจ */
    private function aiAnswering(string $answer, ?array &$captured = null, int $times = 1): FortuneAIService
    {
        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('chatWithCustomSystemPrompt')
            ->times($times)
            ->andReturnUsing(function (string $system, string $input, array $config) use ($answer, &$captured) {
                $captured = ['system' => $system, 'input' => $input, 'config' => $config];

                return ['response' => $answer];
            });

        return $ai;
    }

    // ───────────────────────── 1. กติกาตัวอักษร ─────────────────────────

    public function test_pure_thanks_and_blessing_close_without_ai(): void
    {
        foreach ([
            'สาธุ', 'ขอบคุณค่ะ', 'สาธุๆๆ', 'สาธุ สาธุ สาธุ', 'ขอบคุณค่ะแม่หมอ', 'ครับขอบคุณครับ',
            'น้อมรับสาธุ', 'สาธุ🙏🙏🙏🎉🎉🎉ครับ', 'สาธุสาธุค่ะแม่หมอขอบพระคุณมากค', 'ສາທຸ',
        ] as $text) {
            $this->assertSame(FortuneFarewellJudge::CLOSE, FortuneFarewellJudge::quickVerdict($text), $text);
        }
    }

    public function test_fortune_intent_or_question_never_closes_even_with_thanks(): void
    {
        foreach ([
            // Noi Fuchs — ก๊อปคำชวนของบอทส่งกลับ
            "\"ดูดวง\"\n\nเพื่อเปิดไพ่Celtic10ใบได้เลยนะคะ ขอบคุณค่ะ",
            'สาธุดูดวง',
            'สาธุๆค่ะดูดวงค่ะ🙏',
            'อยากรูัครับแม่หมอสาธุ🙏✌️🥰♥️🌹',
            // ขาปลุก — เคยโดนเงียบใส่หลังอวยพร
            'แล้วเมียผม จะกลับมาหาผมไหมครับ',
            'ดูแบบ99จบเลยคะ',
            'เมื่อไหร่คะอยากรู้เมื่อไหร่จะมีเงิน',
            'ปีเกิด1สิงหาคม2509ปีมะเมีย',
            'เปีดไพ',
            'ดีไหมค่ะแม่หมอจันทรา',
            'ยังอีกกี่วันค่ะพี่หมอ',
            'ขอบคุณค่ะ แล้วเรื่องงานล่ะ?',
        ] as $text) {
            $this->assertSame(FortuneFarewellJudge::CONTINUE, FortuneFarewellJudge::quickVerdict($text), $text);
        }
    }

    public function test_mixed_or_bare_ack_is_unsure(): void
    {
        foreach ([
            'ผมต้องขอบคุณแม่หมอมากนะครับถ้าเรื่องนี้สำเร็จจริง',
            'สาธุขอให้เป็นจริง',
            'รับเงินล้านรัวๆๆ สาธุ',
            'สาธุ 99',       // ตัวเลข = เนื้อหา ไม่ใช่คำขอบคุณล้วน
            'โอเคค่ะ',       // ลาหรือตกลง ขึ้นกับว่าบอทเพิ่งถามอะไร
            'ขอบคุณค่ะ ทำไม่เป็นรีวิวค่ะแม่นมาก', // "ทำไม่" ไม่ใช่คำถาม "ทำไม"
            'ไม่ดูดวงแล้วค่ะ ขอบคุณค่ะ',         // ปฏิเสธ — "ดูดวง" ในประโยคนี้ไม่ใช่คำขอ
        ] as $text) {
            $this->assertSame(FortuneFarewellJudge::UNSURE, FortuneFarewellJudge::quickVerdict($text), $text);
        }

        $this->assertTrue(FortuneFarewellJudge::isBareAck('โอเคค่ะ'));
        $this->assertTrue(FortuneFarewellJudge::isBareAck('ครับๆ'));
        $this->assertFalse(FortuneFarewellJudge::isBareAck('ค่ะ'), '"ค่ะ" ตอบรับคำถามบอท ห้ามนับเป็นคำรับทราบ');
        $this->assertFalse(FortuneFarewellJudge::hasCloserWord('สบายดี'), '"บาย" ใน "สบาย" ไม่ใช่คำลา');
    }

    // ───────────────────────── 2. ขาเข้า (จะอวยพรไหม) ─────────────────────────

    public function test_noi_fuchs_case_continues_without_ai(): void
    {
        $svc = $this->service($this->aiNeverCalled());

        $this->assertFalse($svc->judge("\"ดูดวง\"\n\nเพื่อเปิดไพ่Celtic10ใบได้เลยนะคะ", false));
    }

    public function test_pure_thanks_still_gets_blessing_without_ai(): void
    {
        $svc = $this->service($this->aiNeverCalled());

        $this->assertTrue($svc->judge('ขอบคุณค่ะแม่หมอ 🙏', false));
    }

    public function test_plain_message_without_closer_word_skips_ai(): void
    {
        $svc = $this->service($this->aiNeverCalled());

        $this->assertFalse($svc->judge('ช่วงนี้เหนื่อยมากเลยค่ะ', false));
    }

    public function test_payment_message_with_thanks_continues(): void
    {
        $svc = $this->service($this->aiNeverCalled());

        $this->assertFalse($svc->judge('โอนแบบธรรมดานะคะ สแกนฉันไม่เป็น ขอบคุณค่ะ', false));
    }

    public function test_long_mixed_message_asks_ai_with_last_bot_line(): void
    {
        $captured = null;
        $svc = $this->service(
            $this->aiAnswering('CONTINUE', $captured),
            'อยากให้แม่หมอเจาะลึก พิมพ์ "ดูดวง" ได้เลยนะคะ'
        );

        $this->assertFalse($svc->judge('ผมต้องขอบคุณแม่หมอมากนะครับถ้าเรื่องนี้สำเร็จจริง', false));
        $this->assertStringContainsString('พิมพ์ "ดูดวง"', $captured['input'], 'AI ต้องเห็นข้อความล่าสุดของแม่หมอ');
        $this->assertStringContainsString('ผมต้องขอบคุณแม่หมอ', $captured['input']);
        $this->assertSame(8, $captured['config']['timeout'], 'ต้องตั้งเพดานรอ AI สั้น');
    }

    public function test_ai_close_verdict_gives_blessing(): void
    {
        $svc = $this->service($this->aiAnswering('CLOSE'));

        $this->assertTrue($svc->judge('สาธุขอให้เป็นจริง', false));
    }

    public function test_bare_ack_after_bot_question_goes_to_ai(): void
    {
        $captured = null;
        $svc = $this->service($this->aiAnswering('CONTINUE', $captured), 'พร้อมให้แม่หมอเปิดไพ่เลยไหมคะ');

        $this->assertFalse($svc->judge('โอเคค่ะ', false));
        $this->assertStringContainsString('พร้อมให้แม่หมอเปิดไพ่', $captured['input']);
    }

    public function test_ai_failure_means_continue(): void
    {
        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('chatWithCustomSystemPrompt')->once()
            ->andThrow(new \Exception('Gemini Chat API Error: This model is currently experiencing high demand.'));
        $svc = $this->service($ai);

        $this->assertFalse($svc->judge('สาธุขอให้เป็นจริง', false));
    }

    public function test_ai_garbage_answer_means_continue(): void
    {
        $svc = $this->service($this->aiAnswering('ขอบคุณที่ทักมาค่ะ'));

        $this->assertFalse($svc->judge('สาธุขอให้เป็นจริง', false));
    }

    public function test_same_message_is_judged_once(): void
    {
        $svc = $this->service($this->aiAnswering('CLOSE', $captured, 1));

        $this->assertTrue($svc->judge('สาธุขอให้เป็นจริง', false));
        $this->assertTrue($svc->judge('สาธุขอให้เป็นจริง', false));
    }

    // ───────────────────────── 3. ขาปลุก (หลังอวยพรแล้ว) ─────────────────────────

    public function test_real_question_after_blessing_wakes_without_ai(): void
    {
        $svc = $this->service($this->aiNeverCalled());

        $this->assertFalse($svc->judge('แล้วเมียผม จะกลับมาหาผมไหมครับ', true));
        $this->assertFalse($svc->judge('ดูแบบ99จบเลยคะ', true));
    }

    public function test_more_thanks_after_blessing_stays_silent_without_ai(): void
    {
        $svc = $this->service($this->aiNeverCalled());

        $this->assertTrue($svc->judge('สาธุครับ', true));
    }

    public function test_unclear_message_after_blessing_asks_ai_with_closed_flag(): void
    {
        $captured = null;
        $svc = $this->service($this->aiAnswering('CLOSE', $captured));

        $this->assertTrue($svc->judge('ทำเพจ', true));
        $this->assertStringContainsString('อวยพรปิดบทสนทนาไปแล้ว', $captured['input']);
    }

    public function test_ai_quota_after_blessing_is_capped_per_day(): void
    {
        $svc = $this->service($this->aiAnswering('CLOSE', $captured, 6));

        // ข้อความไม่ซ้ำกัน 7 ข้อความ (cache ไม่ช่วย) — ครั้งที่ 7 ต้องเงียบต่อโดยไม่ถาม AI
        foreach (['รับ', 'ทำเพจ', 'ใช้มอไซค์', 'เป็นแบรนด์', 'ฉันเก่ง', 'รับเงินล้าน', 'ทำแบนเนอร์'] as $text) {
            $this->assertTrue($svc->judge($text, true), $text);
        }
    }
}
