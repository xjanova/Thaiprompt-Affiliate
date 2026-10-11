<?php

namespace Tests\Unit\Services;

use App\Services\CelticSpreadImageGenerator;
use App\Support\ThaiFontText;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * 🃏 ภาพเรียงไพ่ 10 ใบตอนสรุปจบบิล 99 (CelticSpreadImageGenerator) — ออกแบบใหม่ 2026-10-11
 *
 * เทสต์ชุดนี้ล็อก:
 *   1. ThaiFontText::liftStackedToneMarks — วรรณยุกต์ที่ตามสระบน (เรื่อง ที่ ชื่อ) และก่อนสระอำ (น้ำ) ถูกแยกไปวาดยกลอย
 *      — GD ไม่ทำ Thai shaping วาดทับสระจนหาย ("หัวใจของเรื่อง" เคยขึ้นเป็น "เรือง")
 *      ใช้ร่วมกับรูปผังดวง (FortuneChartService::drawCenteredText) — ชื่อลูกค้า "ชื่อ" เคยขึ้นเป็น "ชือ"
 *   2. ภาพทั้งใบ (ไพ่ไม่มีรูป 10 ใบ + ชื่อยาวสุดในสำรับ) ขนาด 1440×2200 และ JPEG ≤ MAX_BYTES
 *      — LINE ใช้ URL เดียวกันเป็น previewImageUrl ซึ่งรับไม่เกิน 1MB
 *
 * ไม่แตะ DB / ไม่โหลดรูปไพ่ (image_url = null → การ์ดหลังไพ่) · ต้องมี GD + FreeType (CI ติดตั้ง gd ไว้แล้ว)
 */
class CelticSpreadImageGeneratorTest extends TestCase
{
    public function test_tone_marks_after_upper_vowels_are_lifted(): void
    {
        $this->assertSame(['เรือง', [['เรื', '่']]], ThaiFontText::liftStackedToneMarks('เรื่อง'));
        $this->assertSame(['ดูดวงวันที', [['ดูดวงวันที', '่']]], ThaiFontText::liftStackedToneMarks('ดูดวงวันที่'));
        $this->assertSame(['ชือ', [['ชื', '่']]], ThaiFontText::liftStackedToneMarks('ชื่อ'));
        $this->assertSame(['นำ', [['น', '้']]], ThaiFontText::liftStackedToneMarks('น้ำ'));

        // วรรณยุกต์ที่ไม่มีสระบนรองรับ ต้องวาดปกติ (ไม่ยก)
        $this->assertSame(['สองไม้เท้า', []], ThaiFontText::liftStackedToneMarks('สองไม้เท้า'));
        $this->assertSame(['ข้าราชบริพารไม้เท้า', []], ThaiFontText::liftStackedToneMarks('ข้าราชบริพารไม้เท้า'));
    }

    public function test_full_spread_renders_within_line_preview_size_limit(): void
    {
        if (! function_exists('imagettftext')) {
            $this->markTestSkipped('ต้องมี GD + FreeType');
        }

        // ชื่อยาวสุดในสำรับ 78 ใบ (prod 2026-10-11) + ไพ่กลับหัวสลับกัน
        $names = ['ข้าราชบริพารไม้เท้า', 'ชายผู้ถูกแขวนคอ', 'ข้าราชบริพารเหรียญ', 'กษัตริย์เหรียญ', 'ข้าราชบริพารถ้วย',
            'มหาปุโรหิตหญิง', 'กษัตริย์ไม้เท้า', 'ความพอประมาณ', 'กงล้อแห่งโชค', 'ข้าราชบริพารดาบ'];
        $cards = [];
        foreach ($names as $i => $name) {
            $cards[$i + 1] = ['card_name_th' => $name, 'image_url' => null, 'is_reversed' => $i % 2 === 0];
        }

        $canvas = (new CelticSpreadImageGenerator)->render($cards, Carbon::parse('2026-10-11 14:20', 'Asia/Bangkok'));
        $this->assertSame(1440, imagesx($canvas));
        $this->assertSame(2200, imagesy($canvas));

        ob_start();
        imagejpeg($canvas, null, 90);
        $bytes = strlen((string) ob_get_clean());
        imagedestroy($canvas);

        $this->assertGreaterThan(50_000, $bytes, 'ภาพว่างผิดปกติ');
        $this->assertLessThanOrEqual(CelticSpreadImageGenerator::MAX_BYTES, $bytes, 'ภาพใหญ่เกินขีด LINE preview');
    }
}
