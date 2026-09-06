<?php

namespace Tests\Feature;

use App\Models\FortuneHoroscopeCampaign;
use App\Models\FortuneHoroscopeContent;
use App\Services\FortuneHoroscopeService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * 🔮 ดวงรายวันต้องไม่ตายเงียบทั้งวัน
 *
 * เหตุจริง 2026-09-06 — ดวงรายวันไม่โพสเลยทั้งวัน (0/7 ใบ) เงียบ ไม่มีใครรู้ 13 ชั่วโมง
 *
 * ต้นเหตุซ้อนกัน 2 ชั้น:
 *   1. `FortuneHoroscopeService` อยู่ namespace `App\Services` แต่เรียก `FacebookContentPolicy::`
 *      โดย **ลืม `use`** → PHP resolve เป็น `App\Services\FacebookContentPolicy` (ไม่มีจริง)
 *      → `Error: Class not found` ตอน runtime — ไฟล์ parse ผ่าน lint ผ่าน CI ผ่าน
 *   2. ด่านกันรายใบเป็น `catch (Exception)` แต่ `Error` **ไม่ใช่ลูกของ Exception**
 *      → หลุดด่านไปฆ่าทั้งลูป ⇒ พัง 1 ใบ กลายเป็นพังหมด 7 ใบ
 *      และแถวแรกค้างสถานะ `generating` ถาวร (ไม่เคย markFailed) = แอดมินเห็นเหมือนกำลังทำอยู่
 *
 * บทเรียน: **ด่านกันรายใบต้องกันของที่ "ไม่คาดคิด" ได้จริง ไม่ใช่แค่ของที่เราตั้งใจโยน**
 *   ชั้นที่ 2 คือตัวแปลง "บั๊กเล็ก" เป็น "เงียบทั้งวัน" — อันตรายกว่าชั้นที่ 1
 *
 * ⚠️ ไม่ใช้ RefreshDatabase — ทดสอบด้วย model ที่ยังไม่ save + subclass ที่โยน Error
 */
class HoroscopeGenerationResilienceTest extends TestCase
{
    /**
     * 🎯 ชั้นที่ 1 — คลาสที่ FortuneHoroscopeService เรียก ต้องมีอยู่จริง
     *
     * ถ้าเทสต์นี้แดง = ลืม use อีกแล้ว ดวงรายวันจะตายทั้งวัน
     */
    public function test_คลาสที่บริการดวงรายวันเรียก_ต้องมีอยู่จริง(): void
    {
        $this->assertTrue(
            class_exists(\App\Services\Fortune\FacebookContentPolicy::class),
            'FacebookContentPolicy ต้องอยู่ที่ App\Services\Fortune'
        );

        $this->assertFalse(
            class_exists('App\Services\FacebookContentPolicy'),
            'ไม่มีคลาสนี้ที่ App\Services — การเรียกแบบไม่ใส่ use ใน namespace นั้นจึง fatal เสมอ'
        );
    }

    /**
     * 🔍 ทุกไฟล์ที่เรียก FacebookContentPolicy:: ต้อง resolve ได้จริง
     *
     * ครอบคลุมกว่าการเช็คไฟล์เดียว — ไฟล์ถัดไปที่ลืม use จะถูกจับที่นี่
     * (คลาสนี้ถูกเรียกจาก 7 ไฟล์ ข้าม 2 namespace ⇒ พลาดง่ายมาก)
     */
    public function test_ทุกไฟล์ที่เรียก_policy_ต้อง_resolve_ได้(): void
    {
        $ผิด = [];

        foreach ($this->phpFilesUnder(app_path()) as $path) {
            $code = (string) file_get_contents($path);

            // เอาเฉพาะการเรียกจริง — ตัดบรรทัดคอมเมนต์ทิ้ง
            $เรียกจริง = collect(preg_split('/\R/', $code))
                ->reject(fn ($line) => preg_match('/^\s*(\/\/|\*|#)/', $line))
                ->contains(fn ($line) => str_contains($line, 'FacebookContentPolicy::'));

            if (! $เรียกจริง) {
                continue;
            }

            preg_match('/^namespace\s+([^;]+);/m', $code, $ns);
            $namespace = trim($ns[1] ?? '');

            $resolveได้ = $namespace === 'App\Services\Fortune'
                || str_contains($code, 'use App\Services\Fortune\FacebookContentPolicy;');

            if (! $resolveได้) {
                $ผิด[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path)." [{$namespace}]";
            }
        }

        $this->assertSame(
            [],
            $ผิด,
            "ไฟล์เหล่านี้เรียก FacebookContentPolicy:: แต่ resolve ไม่ได้ (ลืม use) = fatal ตอน runtime:\n"
                .implode("\n", $ผิด)
        );
    }

    /**
     * 🛟 ชั้นที่ 2 (ตัวที่อันตรายกว่า) — ใบเดียวพังต้องไม่ลากทั้ง 7 ใบไปด้วย
     *
     * โยน `Error` (ไม่ใช่ Exception) แบบเดียวกับ class-not-found ของจริง
     * ถ้าด่านยังเป็น `catch (Exception)` เทสต์นี้จะระเบิดออกมาแทนที่จะได้ผลนับ
     */
    public function test_ใบที่พังต้องไม่ลากใบอื่นตายด้วย(): void
    {
        $service = new class extends FortuneHoroscopeService
        {
            public array $attempted = [];

            // ข้าม constructor ตัวจริง — มันสร้าง FortuneAIService/FortuneChartService ซึ่งอ่าน
            // settings จาก DB ทั้งชุด. ลูปที่กำลังทดสอบไม่ได้ใช้ของพวกนั้นเลย
            public function __construct() {}

            public function generateForBirthDay(
                FortuneHoroscopeCampaign $campaign,
                Carbon $targetDate,
                int $birthDay
            ): FortuneHoroscopeContent {
                $this->attempted[] = $birthDay;

                // ใบวันอาทิตย์ (0) พังแบบ Error เหมือนของจริง — ที่เหลือต้องเดินต่อ
                if ($birthDay === 0) {
                    throw new \Error('Class "App\Services\FacebookContentPolicy" not found');
                }

                return new FortuneHoroscopeContent;
            }
        };

        $campaign = new FortuneHoroscopeCampaign;
        $campaign->id = 1;
        $campaign->name = 'ดวงรายวัน 7 วันเกิด';
        $campaign->target_birth_days = [0, 1, 2, 3, 4, 5, 6];

        $result = $service->generateDailyContent($campaign, Carbon::parse('2026-09-06'));

        // 🌙 8 ใบ ไม่ใช่ 7 — getTargetBirthDays() เติม "วันเกิดที่ 8" (พุธกลางคืน = 7) ให้เอง
        //    กับแถวเก่าที่บันทึกไว้ตอนยังมีแค่ 0-6 (ตรงกับ log prod: birth_days [0..7])
        $this->assertSame([0, 1, 2, 3, 4, 5, 6, 7], $service->attempted, 'ต้องลองครบทุกวันเกิด');
        $this->assertSame(1, $result['failed'], 'ใบที่โยน Error ต้องถูกนับว่าพัง');
        $this->assertSame(7, $result['success'], 'ที่เหลือต้องสำเร็จครบ ไม่ใช่ 0');
    }

    /** ไล่ไฟล์ .php ทั้งหมดใต้ path */
    private function phpFilesUnder(string $dir): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($it as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
