<?php

namespace Tests\Unit\Services;

use App\Support\ThaiProvinces;
use Tests\TestCase;

/**
 * ThaiProvinces — ฐานพิกัดจังหวัดเกิดสำหรับผูกลัคนา
 *
 * โฟกัสของเทสต์ชุดนี้คือ "กับดักการจับคำไทย" เป็นหลัก
 * เพราะชื่อจังหวัดหลายตัวเป็นคำไทยธรรมดา (เลย/ตาก/แพร่/น่าน)
 * ถ้าจับพลาด = ผูกดวงลูกค้าด้วยพิกัดผิดจังหวัดโดยไม่มีใครรู้
 */
class ThaiProvincesTest extends TestCase
{
    /** @test */
    public function it_has_all_77_provinces_with_coordinates(): void
    {
        $this->assertCount(77, ThaiProvinces::PROVINCES, 'ต้องครบ 77 จังหวัด');

        foreach (ThaiProvinces::PROVINCES as $name => $coords) {
            $this->assertCount(2, $coords, "จังหวัด {$name} ต้องมีทั้งละติจูดและลองจิจูด");

            // กรอบประเทศไทย: ละติจูด 5.6–20.5 · ลองจิจูด 97.3–105.7
            $this->assertGreaterThan(5.5, $coords[0], "ละติจูด {$name} หลุดกรอบประเทศไทย");
            $this->assertLessThan(20.6, $coords[0], "ละติจูด {$name} หลุดกรอบประเทศไทย");
            $this->assertGreaterThan(97.2, $coords[1], "ลองจิจูด {$name} หลุดกรอบประเทศไทย");
            $this->assertLessThan(105.8, $coords[1], "ลองจิจูด {$name} หลุดกรอบประเทศไทย");
        }
    }

    /** @test */
    public function every_alias_points_to_a_real_province(): void
    {
        foreach (ThaiProvinces::ALIASES as $alias => $official) {
            $this->assertArrayHasKey(
                $official,
                ThaiProvinces::PROVINCES,
                "ชื่อเรียก \"{$alias}\" ชี้ไปจังหวัดที่ไม่มีในฐาน: {$official}"
            );
        }
    }

    /** @test */
    public function it_resolves_plain_province_names(): void
    {
        $this->assertSame('เชียงใหม่', ThaiProvinces::resolve('เชียงใหม่ค่ะ'));
        $this->assertSame('ภูเก็ต', ThaiProvinces::resolve('06:30 ที่ภูเก็ต'));
        $this->assertSame('ขอนแก่น', ThaiProvinces::resolve('ตี 5 ที่ขอนแก่น'));
    }

    /** @test */
    public function it_resolves_nicknames_and_big_towns(): void
    {
        $this->assertSame('กรุงเทพมหานคร', ThaiProvinces::resolve('กทม'));
        $this->assertSame('นครราชสีมา', ThaiProvinces::resolve('โคราชครับ'));
        $this->assertSame('อุบลราชธานี', ThaiProvinces::resolve('อุบล'));
        $this->assertSame('สงขลา', ThaiProvinces::resolve('หาดใหญ่'));
        $this->assertSame('พระนครศรีอยุธยา', ThaiProvinces::resolve('อยุธยา'));
    }

    /**
     * @test
     *
     * ชื่อยาวต้องชนะชื่อสั้นที่เป็น prefix ร่วมกัน
     * ("นครศรีธรรมราช" ห้ามถูก "นครปฐม/นครพนม/นครสวรรค์" ชิงตัดหน้า)
     */
    public function it_prefers_the_longest_matching_name(): void
    {
        $this->assertSame('นครศรีธรรมราช', ThaiProvinces::resolve('เกิดที่นครศรีธรรมราช'));
        $this->assertSame('นครพนม', ThaiProvinces::resolve('เกิดที่นครพนม'));
        $this->assertSame('นครสวรรค์', ThaiProvinces::resolve('เกิดที่นครสวรรค์'));
        $this->assertSame('นครราชสีมา', ThaiProvinces::resolve('เกิดที่นครราชสีมา'));
    }

    /**
     * @test
     *
     * 🚨 กับดักหลัก — ชื่อจังหวัดที่เป็นคำไทยธรรมดา ต้องไม่ถูกจับจากประโยคทั่วไป
     */
    public function it_does_not_match_ambiguous_names_without_a_place_cue(): void
    {
        $this->assertNull(ThaiProvinces::resolve('ไปเลยค่ะ'));
        $this->assertNull(ThaiProvinces::resolve('ตากแดดทั้งวัน'));
        $this->assertNull(ThaiProvinces::resolve('เรื่องนี้แพร่หลายมาก'));
        $this->assertNull(ThaiProvinces::resolve('ไม่ทราบ'));
    }

    /** @test */
    public function it_matches_ambiguous_names_when_a_cue_is_present(): void
    {
        $this->assertSame('เลย', ThaiProvinces::resolve('เกิดที่ จ.เลย'));
        $this->assertSame('ตาก', ThaiProvinces::resolve('เกิดที่ตาก'));
        $this->assertSame('แพร่', ThaiProvinces::resolve('จังหวัดแพร่'));
    }

    /** @test */
    public function a_bare_answer_counts_as_a_cue(): void
    {
        // แม่หมอเพิ่งถามว่าเกิดจังหวัดอะไร ลูกค้าตอบคำเดียว = ชัดเจนอยู่แล้ว
        $this->assertSame('เลย', ThaiProvinces::resolve('เลย'));
        $this->assertSame('เลย', ThaiProvinces::resolve('เลยค่ะ'));
        $this->assertSame('ตาก', ThaiProvinces::resolve('จังหวัดตาก'));
    }

    /** @test */
    public function it_returns_coordinates_for_names_and_aliases(): void
    {
        $bkk = ThaiProvinces::coords('กรุงเทพมหานคร');
        $this->assertNotNull($bkk);
        $this->assertEqualsWithDelta(13.7563, $bkk['lat'], 0.001);
        $this->assertEqualsWithDelta(100.5018, $bkk['lon'], 0.001);

        // ชื่อเรียกอื่นต้องได้พิกัดเดียวกับชื่อทางการ
        $this->assertSame($bkk, ThaiProvinces::coords('กทม'));
        $this->assertNull(ThaiProvinces::coords('ไม่มีจังหวัดนี้'));
    }

    /**
     * @test
     *
     * "เวลาอัตโนมัติ" — ส่วนต่างเวลานาฬิกากับเวลาสุริยคติท้องถิ่น (อิงเส้น 105°E)
     */
    public function it_computes_local_mean_time_offset(): void
    {
        // แม่ฮ่องสอน 97.9654°E → (97.9654 - 105) * 4 ≈ -28.1 นาที (ช้ากว่านาฬิกา)
        $this->assertEqualsWithDelta(-28.1, ThaiProvinces::localMeanTimeOffsetMinutes('แม่ฮ่องสอน'), 0.2);

        // อุบลราชธานี 104.8473°E → เกือบตรงเส้นมาตรฐาน
        $this->assertEqualsWithDelta(-0.6, ThaiProvinces::localMeanTimeOffsetMinutes('อุบลราชธานี'), 0.2);

        $this->assertNull(ThaiProvinces::localMeanTimeOffsetMinutes('ไม่มีจังหวัดนี้'));
    }
}
