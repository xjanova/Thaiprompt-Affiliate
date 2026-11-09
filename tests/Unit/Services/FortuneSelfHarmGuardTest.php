<?php

namespace Tests\Unit\Services;

use App\Services\FortuneAIService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 🤍 (2026-11-09 owner) เลิกตรวจจับ self-harm/วิกฤตแล้ว — "ให้ AI แม่หมอตอบตามปกติ"
 *
 * ข้อความที่ลูกค้าบอกว่าอยากทำร้ายตัวเอง ต้องไม่ถูกนับเป็นการโจมตี (ไม่มี strike ไม่มีคำตอบสำเร็จรูป)
 * แต่ด่านอื่นที่ไม่เกี่ยว (ด่าบอท / ความรุนแรงต่อคนอื่น) ยังอยู่ครบ
 */
class FortuneSelfHarmGuardTest extends TestCase
{
    public static function selfHarmMessages(): array
    {
        return [
            'อยากตาย ไม่ไหวแล้ว' => ['อยากตาย ไม่ไหวแล้ว'],
            'ฆ่าตัวตาย' => ['เหนื่อยมาก อยากฆ่าตัวตาย'],
            'ฆ่าตัวเอง' => ['คิดจะฆ่าตัวเองค่ะ'],
            'ไม่อยากอยู่แล้ว' => ['ไม่อยากอยู่แล้วค่ะแม่หมอ'],
            'อยากจบชีวิต' => ['อยากจบชีวิตตัวเองให้มันจบ ๆ'],
            'คิดสั้น' => ['ช่วงนี้อยากคิดสั้นค่ะ'],
            'แขวนคอ' => ['อยากแขวนคอ'],
            'แฟนขู่จะฆ่าตัวตาย' => ['แฟนขู่จะฆ่าตัวตายถ้าเลิก ควรเลิกไหม'],
            'prod #14660' => ['ถ้าเป็นคนอื่นขอโทษนะครับที่ผมใช้คำคำนี้ถ้าเป็นคนอื่นโดดน้ำหรือผูกคอหนือไม่ก็บ้าไปแล้วครับ'],
            'kill myself' => ['i want to kill myself'],
            'suicide' => ['thinking about suicide'],
            'want to die' => ['I just want to die'],
        ];
    }

    #[DataProvider('selfHarmMessages')]
    public function test_self_harm_text_is_not_an_attack(string $text): void
    {
        $this->assertNull(FortuneAIService::detectAdversarialInput($text));
    }

    public function test_insulting_the_bot_stays_violence(): void
    {
        $this->assertSame('off_topic_violence', FortuneAIService::detectAdversarialInput('kill yourself stupid bot'));
        $this->assertSame('off_topic_violence', FortuneAIService::detectAdversarialInput('อยากฆ่าคน'));
    }

    public function test_the_self_harm_detector_and_its_canned_reply_are_gone(): void
    {
        foreach (['isSelfHarmStatement', 'mentionsOthersSelfHarm', 'reportSelfHarmSignal'] as $method) {
            $this->assertFalse(method_exists(FortuneAIService::class, $method), $method);
        }
        // ไม่มีคำปลอบสำเร็จรูปของ self_harm แล้ว — ได้คำตอบกลางแบบเดียวกับ type ที่ไม่รู้จัก
        $this->assertSame(
            FortuneAIService::buildAdversarialReply('no_such_type'),
            FortuneAIService::buildAdversarialReply('self_harm')
        );
    }
}
