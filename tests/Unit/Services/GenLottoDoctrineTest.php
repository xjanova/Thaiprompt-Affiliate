<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\AstroAspects;
use App\Services\Fortune\ThaiAstrologyService;
use App\Services\Fortune\ThaiNumerology;
use App\Services\HoroscopeNumerologyService;
use Tests\TestCase;

/**
 * 🇹🇭 ตำราโหราของแม่หมอ = ตำรา GenLotto (เจ้าของสั่ง 2026-09-21 "ยึดตำราโหราจาก genlotto เป็นหลักทั้งหมด")
 *
 * ล็อกค่าที่เคยต่างจาก GenLotto ไว้ทุกจุด — ถ้าเทสต์นี้ตก = มีคนเปลี่ยนกลับไปใช้ตำราอื่น
 *   1. มาตรฐานดาว: ราหูเกษตรกุมภ์ อุจพิจิก นิจพฤษภ · เสาร์เกษตรมังกรราศีเดียว · มี "ประ"
 *   2. มุมดาว: นับตามราศี · 60° = โยค · กุมดี/ร้ายตามศุภ-บาปเคราะห์
 *   3. ธาตุดาว: พฤหัสบดี = ลม · เสาร์ = ดิน (ทุกตารางในระบบต้องตรงกัน)
 *   4. เลขศาสตร์: ตารางอักษร GenLotto (นับสระ/วรรณยุกต์) · เลขดาวไทย 1 อาทิตย์ … 8 ราหู 9 เกตุ
 */
class GenLottoDoctrineTest extends TestCase
{
    // ───────────── 1. มาตรฐานดาว ─────────────

    public function test_rahu_rules_aquarius_and_is_exalted_in_scorpio(): void
    {
        $a = new ThaiAstrologyService;
        $this->assertStringContainsString('เกษตร', $a->dignityOf('ราหู', 'กุมภ์'));
        $this->assertStringContainsString('อุจ', $a->dignityOf('ราหู', 'พิจิก'));
        $this->assertStringContainsString('นิจ', $a->dignityOf('ราหู', 'พฤษภ'));
        $this->assertStringContainsString('ประ', $a->dignityOf('ราหู', 'สิงห์'));
    }

    public function test_saturn_rules_capricorn_only(): void
    {
        $a = new ThaiAstrologyService;
        $this->assertStringContainsString('เกษตร', $a->dignityOf('เสาร์', 'มังกร'));
        $this->assertStringNotContainsString('เกษตร', $a->dignityOf('เสาร์', 'กุมภ์'), 'กุมภ์เป็นเรือนราหูตามตำราไทย');
        $this->assertStringContainsString('ประ', $a->dignityOf('เสาร์', 'กรกฎ'));
    }

    public function test_detriment_is_opposite_of_own_sign_and_debilitation_wins_ties(): void
    {
        $a = new ThaiAstrologyService;
        $this->assertStringContainsString('ประ', $a->dignityOf('อาทิตย์', 'กุมภ์'));
        $this->assertStringContainsString('ประ', $a->dignityOf('ศุกร์', 'เมษ'));
        // พุธในมีน = ทั้งนิจและประ ⇒ ลำดับ GenLotto: นิจก่อน
        $this->assertStringContainsString('นิจ', $a->dignityOf('พุธ', 'มีน'));
    }

    public function test_every_dignity_row_matches_genlotto(): void
    {
        // GenLotto ThaiTables::DIGNITY แปลงดัชนีราศีเป็นชื่อ (0 = เมษ)
        $signs = ['เมษ', 'พฤษภ', 'เมถุน', 'กรกฎ', 'สิงห์', 'กันย์', 'ตุลย์', 'พิจิก', 'ธนู', 'มังกร', 'กุมภ์', 'มีน'];
        $genlotto = [
            'อาทิตย์' => [[4], 0, 6, [10]], 'จันทร์' => [[3], 1, 7, [9]], 'อังคาร' => [[0, 7], 9, 3, [6, 1]],
            'พุธ' => [[2, 5], 5, 11, [8, 11]], 'พฤหัสบดี' => [[8, 11], 3, 9, [2, 5]], 'ศุกร์' => [[1, 6], 11, 5, [7, 0]],
            'เสาร์' => [[9], 6, 0, [3]], 'ราหู' => [[10], 7, 1, [4]],
        ];
        $cfg = config('thai_astrology_knowledge.planet_dignity');
        foreach ($genlotto as $planet => [$rules, $ex, $deb, $det]) {
            $this->assertSame(array_map(fn ($i) => $signs[$i], $rules), $cfg[$planet]['rules'], "{$planet} เกษตร");
            $this->assertSame($signs[$ex], $cfg[$planet]['exalted'], "{$planet} อุจ");
            $this->assertSame($signs[$deb], $cfg[$planet]['debilitated'], "{$planet} นิจ");
            $this->assertSame(array_map(fn ($i) => $signs[$i], $det), $cfg[$planet]['detriment'], "{$planet} ประ");
        }
    }

    // ───────────── 2. มุมดาว ─────────────

    public function test_aspects_are_counted_by_sign_not_by_degree(): void
    {
        // 29° มีน กับ 1° เมษ ห่าง 2° แต่คนละราศี ⇒ ตำราไทยไม่นับเป็นกุม
        $this->assertNull(AstroAspects::between(359.0, 1.0));
        // ห่าง 58° แต่ราศีห่าง 2 ⇒ โยค (ชื่อไทยของมุม 60°)
        $hit = AstroAspects::between(28.0, 86.0);
        $this->assertSame('โยค', $hit['name']);
        $this->assertTrue($hit['good']);
        // ห่าง 45° แต่ราศีห่าง 2 ⇒ ยังเป็นโยค (แต่ไม่แน่น)
        $wide = AstroAspects::between(29.0, 74.0);
        $this->assertSame('โยค', $wide['name']);
        $this->assertFalse($wide['tight']);
    }

    public function test_conjunction_tone_follows_benefic_and_malefic_planets(): void
    {
        // ศุภเคราะห์ = จันทร์ 2 · พุธ 4 · พฤหัส 5 · ศุกร์ 6
        $this->assertSame('ดี', AstroAspects::between(10.0, 12.0, 5, 6)['tone']);   // พฤหัสกุมศุกร์
        $this->assertSame('ร้าย', AstroAspects::between(10.0, 12.0, 3, 7)['tone']); // อังคารกุมเสาร์
        $this->assertSame('ผสม', AstroAspects::between(10.0, 12.0, 1, 2)['tone']);  // อาทิตย์กุมจันทร์
        $this->assertSame('ร้าย', AstroAspects::between(10.0, 100.0)['tone']);      // จตุโกณร้ายเสมอ
    }

    public function test_no_aspect_is_named_the_old_western_label(): void
    {
        foreach (AstroAspects::ASPECTS as $a) {
            $this->assertNotSame('สัมพันธ์', $a['name'], 'มุม 60° ตำราไทยเรียก "โยค"');
        }
    }

    // ───────────── 3. ธาตุดาว ─────────────

    public function test_planet_elements_agree_everywhere(): void
    {
        $meta = config('thai_astrology_knowledge.planet_meta');
        $this->assertSame('ลม', $meta['พฤหัสบดี']['element']);
        $this->assertSame('ดิน', $meta['เสาร์']['element']);

        $a = new ThaiAstrologyService;
        $this->assertSame('ธาตุลม', $a->getPlanetByDayOfWeek(4)['element']);
        $this->assertSame('ธาตุดิน', $a->getPlanetByDayOfWeek(6)['element']);

        // FortuneChartService::CHAOCHANA (ต้นทางตารางของ GenLotto) ต้องตรงกับ config
        $map = [0 => 'อาทิตย์', 1 => 'จันทร์', 2 => 'อังคาร', 3 => 'พุธ', 4 => 'พฤหัสบดี', 5 => 'ศุกร์', 6 => 'เสาร์'];
        foreach ($map as $dow => $th) {
            $this->assertSame(\App\Services\FortuneChartService::CHAOCHANA[$dow]['element'], $meta[$th]['element'], "ธาตุดาว{$th}");
            $this->assertSame('ธาตุ'.$meta[$th]['element'], $a->getPlanetByDayOfWeek($dow)['element'], "ธาตุดาว{$th} (getPlanetByDayOfWeek)");
        }
    }

    // ───────────── 4. เลขศาสตร์ ─────────────

    public function test_name_numerology_counts_vowels_and_tone_marks(): void
    {
        // "สมชาย" = ส 7 + ม 5 + ช 2 + า 1 + ย 8 = 23 (ดีมาก ตามตาราง)
        $r = ThaiNumerology::name('สมชาย');
        $this->assertSame([7, 5, 2, 1, 8], $r['digits']);
        $this->assertSame(23, $r['sum']);
        $this->assertSame('ดีมาก', $r['tone']);
        // วรรณยุกต์นับด้วย: "น้ำ" = น 5 + ้ 2 + ำ 1
        $this->assertSame([5, 2, 1], ThaiNumerology::name('น้ำ')['digits']);
        $this->assertSame(9, ThaiNumerology::reduce(0));
    }

    public function test_web_numerology_uses_thai_planet_numbers(): void
    {
        $meanings = HoroscopeNumerologyService::getAllNumberMeanings();
        $this->assertSame(['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'ราหู', 'เกตุ'], array_values(array_column($meanings, 'name')));
        $this->assertSame('', $meanings[9]['color'], 'เกตุไม่มีสีประจำตามตำรา — ห้ามเติมเอง');
    }
}
