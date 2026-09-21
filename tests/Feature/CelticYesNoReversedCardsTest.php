<?php

namespace Tests\Feature;

use App\Services\FortuneKnowledgeService;
use Tests\TestCase;

/**
 * 🔮 (2026-09-21) ตาชั่ง Yes/No ของบอท Celtic 99 (FortuneKnowledgeService::yesNoVerdict)
 *
 * บั๊ก: พลิกเครื่องหมายไพ่กลับหัวทุกใบ → หอคอยกลับหัวที่ ต.10 (−3 × 2.5) กลายเป็น +7.5 ดันผลเป็น "ใช่ชัด"
 * ตำรา yes/no ให้ไพ่กลับหัวไม่ตรงกัน (ไม่ใช่ / ล่าช้า / ก้ำกึ่ง) → ไม่นับคะแนน ให้แม่หมออ่านความหมายกลับหัวเอง
 * (กฎเดียวกับไพ่เว็บ yesNoVerdictFor — commit 01b36a79d) · สำรับตั้งตรงครบ 10 ใบต้องได้ผลเหมือนเดิมทุกอย่าง
 */
class CelticYesNoReversedCardsTest extends TestCase
{
    private function kb(): FortuneKnowledgeService
    {
        return app(FortuneKnowledgeService::class);
    }

    /** @param  array<int,array{0:string,1?:bool}>  $spec  ตำแหน่ง => [name_en, กลับหัวไหม] */
    private function spread(array $spec): array
    {
        $cards = [];
        foreach ($spec as $pos => [$en, $rev]) {
            $cards[$pos] = ['card_name_en' => $en, 'card_name_th' => $en, 'is_reversed' => $rev ?? false];
        }

        return $cards;
    }

    /** ตาชั่งเดิมก่อนแก้ (เฉพาะกรณีตั้งตรงครบ 10 ใบ) — ใช้เทียบว่าผลไม่เปลี่ยน */
    private function oldVerdictForUpright(array $cards): array
    {
        $cfg = config('fortune_yes_no_weights');
        $total = 0.0;
        foreach (range(1, 10) as $pos) {
            $total += (int) $cfg['card_weights'][$cards[$pos]['card_name_en']] * (float) $cfg['position_multiplier'][$pos];
        }
        $key = 'strong_no';
        foreach (['strong_yes', 'lean_yes', 'unclear', 'lean_no'] as $k) {
            if ($total >= (float) $cfg['verdicts'][$k]['threshold']) {
                $key = $k;
                break;
            }
        }

        return [number_format($total, 1), $cfg['verdicts'][$key]['icon']];
    }

    public function test_a_reversed_tower_in_the_outcome_no_longer_turns_the_answer_into_a_clear_yes(): void
    {
        // ต.1 สี่ถ้วย (−1) · ต.2-9 ไพ่กลาง (0) · ต.10 หอคอยกลับหัว — ตาชั่งเดิม: −1 + 7.5 = +6.5 "✅ ใช่ชัด"
        $cards = $this->spread([
            1 => ['Four of Cups', false], 2 => ['The High Priestess', false], 3 => ['Nine of Wands', false],
            4 => ['Two of Swords', false], 5 => ['Page of Swords', false], 6 => ['Knight of Swords', false],
            7 => ['Four of Pentacles', false], 8 => ['Seven of Pentacles', false], 9 => ['Two of Swords', false],
            10 => ['The Tower', true],
        ]);

        $out = $this->kb()->yesNoVerdict($cards);

        $this->assertStringContainsString('ต.10 The Tower(กลับหัว): ไม่นับคะแนน', $out);
        $this->assertStringContainsString('📊 คะแนนรวม: -1.0', $out);
        $this->assertStringNotContainsString('✅', $out);
        $this->assertStringNotContainsString('🟢', $out);
        $this->assertStringContainsString('🔶', $out, 'one mild "no" card left on the scale leans no');
    }

    public function test_reversed_hard_cards_never_add_a_positive_score(): void
    {
        $spec = [];
        foreach (range(1, 9) as $pos) {
            $spec[$pos] = ['Four of Cups', false];
        }
        $spec[10] = ['Ten of Swords', true];

        $out = $this->kb()->yesNoVerdict($this->spread($spec));

        // ตาชั่งเดิม: −9.5 + 7.5 = −2.0 · ตอนนี้ดาบสิบกลับหัวไม่บวกให้อีก
        $this->assertStringContainsString('📊 คะแนนรวม: -9.5', $out);
        $this->assertStringContainsString('ต.10 Ten of Swords(กลับหัว): ไม่นับคะแนน', $out);
        $this->assertStringContainsString('🔴', $out);
    }

    public function test_an_all_upright_spread_gets_exactly_the_old_verdict(): void
    {
        $names = array_keys(config('fortune_yes_no_weights.card_weights'));
        $this->assertGreaterThanOrEqual(78, count($names));

        // สามชุด: ต้นสำรับ (Major ส่วนใหญ่บวก) · ท้ายสำรับ · ชุดดาบกลางสำรับ — ครอบทั้งผลบวก กลาง และลบ
        foreach ([array_slice($names, 0, 10), array_slice($names, -10), array_slice($names, 50, 10)] as $set) {
            $cards = $this->spread(array_combine(range(1, 10), array_map(fn ($n) => [$n, false], $set)));
            [$total, $icon] = $this->oldVerdictForUpright($cards);

            $out = $this->kb()->yesNoVerdict($cards);

            $this->assertStringContainsString("📊 คะแนนรวม: {$total} (จาก 10 ใบตั้งตรง)", $out, implode(', ', $set));
            $this->assertStringContainsString("{$icon} ผลฟันธง:", $out, implode(', ', $set));
            $this->assertStringNotContainsString('ไม่นับคะแนน', $out);
        }
    }

    public function test_all_reversed_leaves_no_scale_and_extra_positions_are_ignored(): void
    {
        $spec = [];
        foreach (range(1, 10) as $pos) {
            $spec[$pos] = ['The Sun', true];
        }
        $this->assertSame('', $this->kb()->yesNoVerdict($this->spread($spec)), 'nothing upright to weigh — read the reversed meanings');

        // บอทส่งมาแค่ 1..10 — ตำแหน่งอื่นไม่ถูกนับ (ไม่ขยายตาชั่งของบอทโดยไม่ตั้งใจ)
        $spec[11] = ['The Sun', false];
        $this->assertSame('', $this->kb()->yesNoVerdict($this->spread($spec)));
    }
}
