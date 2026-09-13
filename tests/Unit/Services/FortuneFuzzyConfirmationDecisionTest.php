<?php

namespace Tests\Unit\Services;

use App\Services\FortuneConversationService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 🚨 (2026-09-13 จับผี) คำตอบ "ยอดโอนที่ไม่ตรงเป๊ะนี้ ใช่ของคุณไหม" — true = ตัดบิล (เปิดเมื่อ enable_fuzzy_payment_match)
 *
 * ตัวเดิมอนุมัติบิลผิดได้:
 *   - ปุ่ม "❌ ไม่ใช่" ส่งรหัสดิบ FUZZY_CONFIRM_NO → มีตัว y + คำ confirm → นับเป็น "ใช่"
 *   - "ยังไม่ได้โอนเลย" มีคำว่า "โอน" → นับเป็น "ใช่"
 * แต่ห้ามเข้มจนปฏิเสธคนที่ตอบใช่จริง — ถามข้อนี้เพราะยอดไม่ตรง คนจึงตอบ "ใช่ค่ะ โอนผิดยอด" บ่อยที่สุด
 *
 * ทุกเคสผ่าน normalizeUserInput() ตัวจริงก่อน (ตัดคำลงท้าย "ค่ะ/คะ" ออก) — เทียบกับของที่วิ่งจริง
 */
class FortuneFuzzyConfirmationDecisionTest extends TestCase
{
    private function decide(string $customerText): ?bool
    {
        $service = (new \ReflectionClass(FortuneConversationService::class))->newInstanceWithoutConstructor();

        $normalize = new \ReflectionMethod(FortuneConversationService::class, 'normalizeUserInput');
        $normalize->setAccessible(true);

        $decide = new \ReflectionMethod(FortuneConversationService::class, 'fuzzyConfirmationDecision');
        $decide->setAccessible(true);

        return $decide->invoke(null, $normalize->invoke($service, $customerText));
    }

    public static function noCases(): array
    {
        return [
            'ปุ่มไม่ใช่ (รหัสดิบ)' => ['FUZZY_CONFIRM_NO'],
            'ไม่ใช่' => ['ไม่ใช่'],
            'ไม่ค่ะ' => ['ไม่ค่ะ'],
            'ไม่ใช่ของหนูค่ะ' => ['ไม่ใช่ของหนูค่ะ'],
            'ของหนูไม่ใช่ค่ะ' => ['ของหนูไม่ใช่ค่ะ'],
            'ยังไม่ได้โอนเลย' => ['ยังไม่ได้โอนเลย'],
            'หนูยังไม่ได้โอนค่ะ' => ['หนูยังไม่ได้โอนค่ะ'],
            'ยังค่ะ' => ['ยังค่ะ'],
            'ผิดค่ะ' => ['ผิดค่ะ'],
            'ผิดคนแล้ว' => ['ผิดคนแล้ว'],
            'no' => ['no'],
            'not mine' => ['not mine'],
        ];
    }

    #[DataProvider('noCases')]
    public function test_denial_or_no_button_never_approves(string $text): void
    {
        $this->assertFalse($this->decide($text));
    }

    public static function yesCases(): array
    {
        return [
            'ปุ่มใช่ (รหัสดิบ)' => ['FUZZY_CONFIRM_YES'],
            'ใช่' => ['ใช่'],
            'ใช่ค่ะ' => ['ใช่ค่ะ'],
            'ยืนยัน' => ['ยืนยัน'],
            'ถูกต้องค่ะ' => ['ถูกต้องค่ะ'],
            'ของหนูเองค่ะ' => ['ของหนูเองค่ะ'],
            'โอนแล้วค่ะ' => ['โอนแล้วค่ะ'],
            'yes' => ['yes'],
            'ok' => ['ok'],
            // ถามเพราะยอดไม่ตรง → คำตอบใช่ที่เจอบ่อยที่สุดมีคำว่า "ไม่/ผิด/ยัง" ตามหลัง — ห้ามปฏิเสธ
            'ใช่ค่ะ โอนผิดยอด' => ['ใช่ค่ะ โอนผิดยอด'],
            'ใช่ค่ะ ไม่ได้ใส่เศษสตางค์' => ['ใช่ค่ะ ไม่ได้ใส่เศษสตางค์'],
            'ใช่ค่ะ โอนไม่ครบ' => ['ใช่ค่ะ โอนไม่ครบ'],
            'ใช่ค่ะ ต้องทำยังไงต่อ' => ['ใช่ค่ะ ต้องทำยังไงต่อ'],
            'ใช่ค่ะ ทำไมยังไม่ได้คำทำนาย' => ['ใช่ค่ะ ทำไมยังไม่ได้คำทำนาย'],
        ];
    }

    #[DataProvider('yesCases')]
    public function test_clear_affirmative_approves(string $text): void
    {
        $this->assertTrue($this->decide($text));
    }

    public static function undecidedCases(): array
    {
        return [
            // เดิม: มีตัว y ที่ไหนก็ได้ = ใช่
            'why' => ['why'],
            'sorry' => ['sorry'],
            'ขอดูดวงความรักค่ะ' => ['ขอดูดวงความรักค่ะ'],
            // ประโยคคำถาม = ลูกค้ายังไม่ได้ยืนยัน
            'ใช่ไหมคะ' => ['ใช่ไหมคะ'],
            'ใช่มั้ย' => ['ใช่มั้ย'],
            'ใช่เหรอ' => ['ใช่เหรอ'],
            'ของหนูหรือเปล่าคะ' => ['ของหนูหรือเปล่าคะ'],
            // "ยกเลิก" ต้องไหลไปเส้นยกเลิกบิล ไม่ใช่ถูกกลืนเป็นคำปฏิเสธ fuzzy
            'ยกเลิก' => ['ยกเลิก'],
            // "ใช้" ≠ "ใช่"
            'ใช้บัญชีแฟนโอน' => ['ใช้บัญชีแฟนโอนค่ะ'],
            // ขัดกันเอง → ถามใหม่ดีกว่าเดา
            'ใช่ค่ะ ไม่ใช่ของหนู' => ['ใช่ค่ะ ไม่ใช่ของหนู'],
            // ขึ้นต้น "ไม่…" แต่ความหมายคือโอนจริง → ห้ามปฏิเสธ (ปล่อยไม่ชัด)
            'ไม่ได้ใส่เศษสตางค์ค่ะ' => ['ไม่ได้ใส่เศษสตางค์ค่ะ'],
        ];
    }

    #[DataProvider('undecidedCases')]
    public function test_unclear_or_question_is_undecided(string $text): void
    {
        $this->assertNull($this->decide($text));
    }
}
