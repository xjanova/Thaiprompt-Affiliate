<?php

namespace Tests\Unit\Services;

use App\Services\FortuneAIService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 🤍 (2026-11-03) ด่าน guard ต้องแยก "คนที่อยากทำร้ายตัวเอง" ออกจาก "ความรุนแรง"
 *
 * เดิม "ฆ่าตัวเอง / kill myself" ถูกจัดเป็น off_topic_violence → ได้คำตอบปัดตก
 * และ "ฆ่าตัวตาย" ไม่ถูกจับเลย
 */
class FortuneSelfHarmGuardTest extends TestCase
{
    public static function selfHarmMessages(): array
    {
        return [
            'ฆ่าตัวตาย' => ['เหนื่อยมาก อยากฆ่าตัวตาย'],
            'ฆ่าตัวเอง' => ['คิดจะฆ่าตัวเองค่ะ'],
            'อยากตาย' => ['ไม่มีใครรักเลย อยากตาย'],
            'ไม่อยากอยู่แล้ว' => ['ไม่อยากอยู่แล้วค่ะแม่หมอ'],
            'ไม่อยากอยู่ ท้ายประโยค' => ['ชีวิตพังหมดแล้ว ไม่อยากอยู่'],
            'ไม่อยากมีชีวิตอยู่' => ['ไม่อยากมีชีวิตอยู่ต่อไปแล้ว'],
            'อยากจบชีวิต' => ['อยากจบชีวิตตัวเองให้มันจบ ๆ'],
            'ทำร้ายตัวเอง' => ['เมื่อคืนทำร้ายตัวเองอีกแล้ว'],
            'กินยาตาย' => ['จะกินยาตายคืนนี้'],
            'kill myself' => ['i want to kill myself'],
            'kill my self' => ['Gonna kill my self tonight'],
            'suicide' => ['thinking about suicide'],
            'suicidal' => ['I feel suicidal'],
            'want to die' => ['I just want to die'],
            'end my life' => ['I want to end my life'],
            'just kill me' => ['just kill me already'],
            'มีแท็กระบบนำหน้า' => ['[TURN 4] [OFFER_COOLDOWN] [RETURNING_CUSTOMER past_topics="การงาน"] อยากตายค่ะ'],
            'ระบายยาวเกิน 2000 ตัว' => [str_repeat('เรื่องงานหนักมาก ', 200).'อยากฆ่าตัวตาย'],
            // 🤍 (2026-11-04) ขยาย regex
            'ฆ่าตัวตาย เดี่ยว ๆ' => ['ฆ่าตัวตาย'],
            'ฆ่า ตัว ตาย เว้นวรรค' => ['คิดเรื่อง ฆ่า ตัว ตาย ทุกคืน'],
            'คิดสั้น' => ['ช่วงนี้อยากคิดสั้นค่ะ'],
            'เกือบคิดสั้น' => ['เมื่อคืนเกือบคิดสั้นไปแล้ว'],
            'ทำร้ายร่างกายตัวเอง' => ['ทำร้ายร่างกายตัวเองบ่อยมาก'],
            'กินยานอนหลับเกินขนาด' => ['คิดจะกินยานอนหลับเกินขนาด'],
            'กินยาหมดแผง' => ['จะกินยาหมดแผงเลย'],
            'แขวนคอ' => ['อยากแขวนคอ'],
            'กระโดดสะพาน' => ['จะไปกระโดดสะพานตาย'],
            'ไม่อยากตื่นอีก' => ['หลับไปแล้วไม่อยากตื่นขึ้นมาอีก'],
            'เบื่อที่จะมีชีวิต' => ['เบื่อที่จะมีชีวิตอยู่แล้ว'],
        ];
    }

    #[DataProvider('selfHarmMessages')]
    public function test_self_harm_is_its_own_group_not_violence(string $text): void
    {
        $this->assertSame('self_harm', FortuneAIService::detectAdversarialInput($text));
    }

    public static function notSelfHarmMessages(): array
    {
        return [
            'ปฏิเสธ' => ['ป่วยหนัก แต่ไม่อยากตายค่ะ ดวงสุขภาพเป็นยังไง'],
            'ปฏิเสธ อังกฤษ' => ["I don't want to die young, how is my health?"],
            'ทำนายฝัน' => ['ฝันว่าตัวเองฆ่าตัวตาย หมายความว่าอะไรคะ'],
            'จบชีวิตคู่' => ['อยากจบชีวิตคู่ ควรหย่าไหมคะ'],
            'จบชีวิตโสด' => ['ปีนี้จะได้จบชีวิตโสดไหม'],
            'ไม่อยากอยู่บ้าน' => ['ไม่อยากอยู่บ้านนี้แล้ว ควรย้ายไหม'],
            'ไม่อยากอยู่กับแฟน' => ['ไม่อยากอยู่กับแฟนต่อ'],
            'สำนวนร้อนจะตาย' => ['ร้อนจะตาย ขอดูดวงเรื่องงาน'],
            'ถามดวงเรื่องความตาย' => ['ปีนี้จะตายไหมคะ'],
            'going to die' => ['am i going to die this year?'],
            'ดูดวงปกติ' => ['อยากถามเรื่องความรักค่ะ'],
            // 🔮 (2026-11-04) คำดูดวงที่มีคำว่า "ตาย/Death" ต้องไม่ติด
            'ดวงตาย' => ['ดวงตายไหมปีนี้คะ'],
            'ไพ่ Death' => ['เปิดได้ไพ่ Death หมายถึงอะไรคะ'],
            'ไพ่ความตาย' => ['ได้ไพ่ความตาย น่ากลัวไหม'],
            'death card' => ['I got the Death card, what does it mean?'],
            'ปฏิเสธคิดสั้น' => ['ไม่อยากคิดสั้นหรอก แค่เหนื่อย'],
            'ผูกคอเสื้อ' => ['ใส่เสื้อผูกคอสีอะไรดีวันสัมภาษณ์'],
        ];
    }

    #[DataProvider('notSelfHarmMessages')]
    public function test_ordinary_or_negated_messages_are_not_self_harm(string $text): void
    {
        $this->assertNotSame('self_harm', FortuneAIService::detectAdversarialInput($text));
        $this->assertFalse(FortuneAIService::isSelfHarmStatement($text));
    }

    public function test_insulting_the_bot_stays_violence(): void
    {
        $this->assertSame('off_topic_violence', FortuneAIService::detectAdversarialInput('kill yourself stupid bot'));
        $this->assertSame('off_topic_violence', FortuneAIService::detectAdversarialInput('อยากฆ่าคน'));
    }

    public function test_self_harm_reply_is_caring_with_hotline_and_keeps_the_door_open(): void
    {
        $reply = FortuneAIService::buildAdversarialReply('self_harm');

        $this->assertStringContainsString('สายด่วนสุขภาพจิต 1323', $reply);
        $this->assertStringContainsString('ศูนย์ช่วยเหลือสังคม 1300', $reply);
        $this->assertStringContainsString('1669', $reply, 'อยู่ในอันตรายตอนนี้ต้องรู้เบอร์ฉุกเฉิน');
        $this->assertStringContainsString('24 ชม.', $reply);
        $this->assertStringContainsString('พิมพ์มาได้เลย', $reply, 'ต้องชวนคุยต่อ ไม่ตัดบท');
        $this->assertStringNotContainsString('ไม่ตอบเรื่องแบบนั้น', $reply);
        foreach (['ดูดวง', 'เปิดไพ่', 'ไพ่', 'ราคา', 'บาท', '[OFFER', '[[OFFER'] as $sales) {
            $this->assertStringNotContainsString($sales, $reply, "ห้ามชวนดูดวง/ขายในเทิร์นนี้ ({$sales})");
        }
        // 🎭 ใช้ข้อความเดียวทุกตัวละคร — ห้ามเรียกชื่อตัวเองหรือเรียกลูกค้าแบบของตัวละครใดตัวหนึ่ง
        foreach (['แม่หมอ', 'บุษบา', 'ลูก', 'ครับ'] as $personaWord) {
            $this->assertStringNotContainsString($personaWord, $reply, "ต้องเป็นกลางทุกตัวละคร ({$personaWord})");
        }
    }

    public function test_violence_never_swallows_self_harm_any_more(): void
    {
        foreach (['อยากฆ่าตัวเอง', 'ฆ่าตัวตาย', 'kill myself'] as $text) {
            $this->assertNotSame('off_topic_violence', FortuneAIService::detectAdversarialInput($text), $text);
        }
    }
}
