<?php

namespace Tests\Unit\Services;

use App\Services\FortuneConversationService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 🚨 (2026-09-13 จับผี) คำตอบ "ยอดโอนที่ไม่ตรงเป๊ะนี้ ใช่ของคุณไหม" — true = ตัดบิล
 *
 * ตัวเดิมอนุมัติบิลผิดได้ (เปิดเมื่อ enable_fuzzy_payment_match):
 *   - ปุ่ม "❌ ไม่ใช่" ส่งรหัสดิบ FUZZY_CONFIRM_NO → มีตัว y + คำ confirm → นับเป็น "ใช่"
 *   - "ยังไม่ได้โอนเลย" มีคำว่า "โอน" → นับเป็น "ใช่"
 * ฝั่งนี้คือเงินลูกค้า — ไม่ชัด = ไม่ตัด
 */
class FortuneFuzzyConfirmationDecisionTest extends TestCase
{
    private function decide(string $normalized): ?bool
    {
        $ref = new \ReflectionMethod(FortuneConversationService::class, 'fuzzyConfirmationDecision');
        $ref->setAccessible(true);

        return $ref->invoke(null, $normalized);
    }

    public static function noCases(): array
    {
        return [
            'ปุ่มไม่ใช่ (รหัสดิบ)' => ['fuzzy_confirm_no'],
            'ไม่ใช่' => ['ไม่ใช่'],
            'ไม่ใช่ของหนูค่ะ' => ['ไม่ใช่ของหนูค่ะ'],
            'ของหนูไม่ใช่ค่ะ' => ['ของหนูไม่ใช่ค่ะ'],
            'ยังไม่ได้โอนเลย' => ['ยังไม่ได้โอนเลย'],
            'ยังค่ะ' => ['ยังค่ะ'],
            'ผิดค่ะ' => ['ผิดค่ะ'],
            'no' => ['no'],
            'not mine' => ['not mine'],
        ];
    }

    #[DataProvider('noCases')]
    public function test_negative_or_button_no_never_approves(string $text): void
    {
        $this->assertFalse($this->decide($text));
    }

    public static function yesCases(): array
    {
        return [
            'ปุ่มใช่ (รหัสดิบ)' => ['fuzzy_confirm_yes'],
            'ใช่' => ['ใช่'],
            'ใช่ค่ะ' => ['ใช่ค่ะ'],
            'ค่ะ' => ['ค่ะ'],
            'ยืนยัน' => ['ยืนยัน'],
            'ของหนูเองค่ะ' => ['ของหนูเองค่ะ'],
            'โอนแล้วค่ะ' => ['โอนแล้วค่ะ'],
            'yes' => ['yes'],
        ];
    }

    #[DataProvider('yesCases')]
    public function test_clear_affirmative_approves(string $text): void
    {
        $this->assertTrue($this->decide($text));
    }

    public function test_unrelated_text_is_undecided(): void
    {
        // เดิม: มีตัว y ที่ไหนก็ได้ = ใช่ ("sorry", "why") · มี "ค่ะ" ในประโยค = ใช่
        $this->assertNull($this->decide('why'));
        $this->assertNull($this->decide('sorry'));
        $this->assertNull($this->decide('ขอดูดวงความรักค่ะ'));
    }
}
