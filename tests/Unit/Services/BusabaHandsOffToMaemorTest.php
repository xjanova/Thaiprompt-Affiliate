<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\JuntraChatService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 🌸 (2026-11-12, owner) บุษบาส่งต่อแม่หมอจันทรา เฉพาะเรื่องที่เกินไพ่ของบุษบา
 *
 * owner ชมว่า "บุษบา ฉลาดมากที่จะแนะนำให้ผู้ใช้ ไปดูดวงต่อกับ พี่จันทรา ในเรื่องที่เกินความสามารถเธอ"
 * แล้วแก้ว่า "เรื่องกฎส่งต่อแม่หมอจันทราต้องไพ่เกิน 9 ใบไม่ใช่ 3"
 * (juntraweb PR 58: ไพ่ทุกแบบที่ไม่ถึง 10 ใบ — 1 · 3 · 5 ใบ — เป็นของบุษบา · Celtic 10 ใบ + 12 เดือนเป็นของแม่หมอ)
 *
 * ล็อกไว้:
 *   1. แชท ยื่นการ์ด และห้องถามต่อ ของบุษบา ยังมีประโยคส่งต่อแม่หมอจันทราอยู่ (ห้ามหายจากการแก้พรอมต์รอบหน้า)
 *   2. เส้นส่งต่อคือ "ไพ่ตั้งแต่ 10 ใบ / ดวงทั้งปี" — ไม่ใช่ "เกินไพ่ 3 ใบ" แบบเดิม
 *   3. พรอมต์ของบุษบาไม่บอกว่าห้องนี้มีแค่ไพ่ 3 ใบอีก
 *
 * ไม่แตะ DB ไม่บูต Laravel — อ่านค่าคงที่ตรง ๆ
 */
class BusabaHandsOffToMaemorTest extends TestCase
{
    private const HANDOFF_LINE = 'ถ้าอยากให้ดูเรื่องนี้แบบลึกครบทุกมุม ตั้งแต่ต้นเหตุถึงผลลัพธ์ แม่หมอจันทราดูให้ได้นะคะ';

    /** @return array<string, string> */
    private static function busaba(): array
    {
        $profiles = (new \ReflectionClassConstant(JuntraChatService::class, 'PERSONA_PROFILES'))->getValue();

        return array_filter($profiles['busaba'], 'is_string');
    }

    #[Test]
    public function every_busaba_prompt_still_hands_deep_questions_to_mae_mor(): void
    {
        $p = self::busaba();

        foreach (['system', 'offer_rules', 'reading_rules'] as $key) {
            $this->assertStringContainsString(self::HANDOFF_LINE, $p[$key], "busaba.$key lost the hand-off to แม่หมอจันทรา");
        }
    }

    #[Test]
    public function the_hand_off_line_is_ten_cards_or_a_whole_year_not_three_cards(): void
    {
        $p = self::busaba();

        foreach (['system', 'offer_rules', 'reading_rules'] as $key) {
            $this->assertStringContainsString('ไพ่ตั้งแต่ 10 ใบขึ้นไป', $p[$key], "busaba.$key must hand off at 10+ cards");
            $this->assertStringContainsString('ดวงทั้งปี', $p[$key], "busaba.$key must hand off a whole-year reading");
        }
        // Busaba reads up to 9 cards herself — never send a single question away
        $this->assertStringContainsString('ไพ่ของบุษบามีถึง 9 ใบ', $p['system']);
        $this->assertStringContainsString('ห้ามส่งต่อเรื่องเดียวที่ไพ่ของบุษบาตอบได้', $p['system']);
    }

    #[Test]
    public function busaba_no_longer_says_her_room_only_has_three_cards(): void
    {
        foreach (self::busaba() as $key => $text) {
            $this->assertStringNotContainsString('เกินไพ่ 3 ใบ', $text, "busaba.$key");
            $this->assertStringNotContainsString('ไพ่ 3 ใบของบุษบา', $text, "busaba.$key");
            $this->assertStringNotContainsString('เปิดไพ่ทาโรต์ 3 ใบ', $text, "busaba.$key");
            $this->assertStringNotContainsString('จะเปิดไพ่ 3 ใบให้', $text, "busaba.$key");
        }
        $this->assertStringContainsString('ไม่ถึง 10 ใบ (1 ใบ · 3 ใบ · 5 ใบ)', self::busaba()['system']);
    }
}
