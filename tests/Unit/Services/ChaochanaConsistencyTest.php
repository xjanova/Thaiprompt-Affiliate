<?php

namespace Tests\Unit\Services;

use App\Services\FortuneChartService;
use PHPUnit\Framework\TestCase;

/**
 * 🔯 ล็อกความสอดคล้องของตารางดาวมิตร/ศัตรู (ตำราเจ้าชนะ)
 *
 * 🚨 ทำไมต้องมีเทสต์นี้ (2026-09-03):
 *   ตาราง `FortuneChartService::CHAOCHANA` เก็บ "รายวันเกิด" 7 แถว ไม่ใช่ "รายคู่ดาว"
 *   ⇒ ความสัมพันธ์ของดาว 1 คู่ถูกเขียน 2 ที่ ไม่มีอะไรบังคับให้ตรงกัน
 *   เจอของจริง: แถววันพฤหัสบดีบอก "เสาร์ = ศัตรู" แต่แถววันเสาร์บอก
 *   "พฤหัสบดี = มิตร" — โหรที่อ่านผังลูกค้า 2 ใบเห็นทันทีว่าขัดกัน
 *
 *   คอนเทนต์นี้โพสสาธารณะ + ขายจริง ⇒ ปล่อยให้ regress ไม่ได้
 *
 * ⚠️ ใช้ PHPUnit\Framework\TestCase ตรง ๆ (ไม่แตะ DB) เครื่อง dev ที่ไม่มี MySQL รันได้
 */
class ChaochanaConsistencyTest extends TestCase
{
    /**
     * ห้ามมีคู่ดาวที่ฝั่งหนึ่งว่า "มิตร" อีกฝั่งว่า "ศัตรู"
     *
     * (ไม่สมมาตรได้ ถ้าอีกฝั่งเงียบ = เป็นกลาง ซึ่งตรงกับตำราที่มิตรภาพ
     *  ไม่จำเป็นต้องตอบแทนกันเสมอ)
     */
    public function test_ไม่มีคู่ดาวไหนที่ฝั่งหนึ่งว่ามิตรอีกฝั่งว่าศัตรู(): void
    {
        $byPlanet = [];
        foreach (FortuneChartService::CHAOCHANA as $row) {
            $byPlanet[$row['planet']] = $row;
        }

        $conflicts = [];
        foreach ($byPlanet as $planet => $row) {
            foreach (($row['friends'] ?? []) as $other) {
                // อีกฝั่งไม่มีแถวของตัวเอง (ราหูไม่มีวันประจำในสัปดาห์) → ข้าม
                if (! isset($byPlanet[$other])) {
                    continue;
                }
                if (in_array($planet, $byPlanet[$other]['enemies'] ?? [], true)) {
                    $conflicts[] = "{$planet} ถือ {$other} เป็นมิตร แต่ {$other} ถือ {$planet} เป็นศัตรู";
                }
            }
        }

        $this->assertSame([], $conflicts, "ตารางเจ้าชนะขัดกันเอง:\n".implode("\n", $conflicts));
    }

    /** ทุกวันต้องมีดาวเจ้าเรือน + มิตร + ศัตรู ครบ ไม่มีแถวว่าง */
    public function test_ครบทั้ง7วันและไม่มีช่องว่าง(): void
    {
        $this->assertCount(7, FortuneChartService::CHAOCHANA);

        foreach (FortuneChartService::CHAOCHANA as $dow => $row) {
            $this->assertArrayHasKey('planet', $row, "วันที่ {$dow} ไม่มีดาวเจ้าเรือน");
            $this->assertNotEmpty($row['friends'] ?? [], "วันที่ {$dow} ไม่มีดาวมิตร");
            $this->assertNotEmpty($row['enemies'] ?? [], "วันที่ {$dow} ไม่มีดาวศัตรู");
        }
    }

    /** ดาวดวงหนึ่งจะเป็นทั้งมิตรและศัตรูของวันเดียวกันไม่ได้ */
    public function test_ดาวเดียวเป็นทั้งมิตรและศัตรูของวันเดียวกันไม่ได้(): void
    {
        foreach (FortuneChartService::CHAOCHANA as $dow => $row) {
            $both = array_intersect($row['friends'] ?? [], $row['enemies'] ?? []);
            $this->assertSame([], array_values($both), "วันที่ {$dow} มีดาวซ้ำทั้งฝั่งมิตรและศัตรู");
        }
    }

    /** ดาวเจ้าเรือนของวันตัวเอง ห้ามโผล่ในลิสต์มิตร/ศัตรูของตัวเอง */
    public function test_ดาวเจ้าเรือนห้ามเป็นมิตรหรือศัตรูของตัวเอง(): void
    {
        foreach (FortuneChartService::CHAOCHANA as $dow => $row) {
            $self = $row['planet'];
            $this->assertNotContains($self, $row['friends'] ?? [], "วันที่ {$dow} มีดาวตัวเองในลิสต์มิตร");
            $this->assertNotContains($self, $row['enemies'] ?? [], "วันที่ {$dow} มีดาวตัวเองในลิสต์ศัตรู");
        }
    }

    /**
     * 🚨 ตารางดาวมิตร/ศัตรูมี **3 ชุด** ในระบบ ต้องพูดตรงกันเสมอ
     *
     *   1. `FortuneChartService::CHAOCHANA`        → รูปผังดวง + ดวงรายวันบนเพจ + DailyAstroBrief
     *   2. `ThaiAstrologyService::getPlanetByDayOfWeek()` → ผังดวงในบิลจ่ายเงิน 39/99
     *   3. `FortuneAIService::getPlanetByDayOfWeek()`     → พรอมต์สายเก่า
     *
     * เคสจริง 2026-09-03: แก้ข้อขัดแย้งพฤหัสบดี-เสาร์ที่ชุดที่ 1 ชุดเดียว
     * ⇒ ลูกค้าที่อ่านโพสบนเพจกับลูกค้าที่จ่าย 99 ได้ "ดาวมิตร" คนละคำตอบ
     * เทสต์นี้จับได้ทันที (ก่อนหน้านี้ไม่มีอะไรจับ — ต้องไล่อ่านเอง 3 ไฟล์)
     */
    public function test_ตารางทั้ง3ชุดในระบบต้องตรงกัน(): void
    {
        $strip = fn (string $s): array => array_values(array_filter(array_map(
            fn ($x) => trim(str_replace('ดาว', '', $x)),
            explode(',', $s)
        )));

        $tas = new \App\Services\Fortune\ThaiAstrologyService;

        $ref = new \ReflectionClass(\App\Services\FortuneAIService::class);
        $aiMethod = $ref->getMethod('getPlanetByDayOfWeek');
        $aiMethod->setAccessible(true);
        $ai = $ref->newInstanceWithoutConstructor();

        foreach (FortuneChartService::CHAOCHANA as $dow => $row) {
            $fromKeys = fn (array $keys): array => array_map(
                fn ($k) => trim(str_replace('ดาว', '', FortuneChartService::PLANETS[$k]['name'] ?? $k)),
                $keys
            );

            $chart = ['f' => $fromKeys($row['friends']), 'e' => $fromKeys($row['enemies'])];
            $thai = $tas->getPlanetByDayOfWeek($dow);
            $aiRow = $aiMethod->invoke($ai, $dow);

            $this->assertSame($chart['f'], $strip($thai['friends']), "ดาวมิตรวันที่ {$dow}: CHAOCHANA ไม่ตรงกับ ThaiAstrologyService");
            $this->assertSame($chart['e'], $strip($thai['enemies']), "ดาวศัตรูวันที่ {$dow}: CHAOCHANA ไม่ตรงกับ ThaiAstrologyService");
            $this->assertSame($strip($thai['friends']), $strip($aiRow['friends']), "ดาวมิตรวันที่ {$dow}: ThaiAstrologyService ไม่ตรงกับ FortuneAIService");
            $this->assertSame($strip($thai['enemies']), $strip($aiRow['enemies']), "ดาวศัตรูวันที่ {$dow}: ThaiAstrologyService ไม่ตรงกับ FortuneAIService");
        }
    }
}
