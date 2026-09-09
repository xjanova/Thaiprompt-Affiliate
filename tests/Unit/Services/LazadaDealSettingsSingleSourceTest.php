<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * ⚡ ล็อกว่า "ค่าตั้ง Flash Deals มีแหล่งเดียว" — ทุกฝั่งต้องอ่านผ่าน LazadaDealSettings
 *
 * 🚨 ทำไมต้องล็อกด้วยเทสต์
 *   ตัวเลขชุดนี้ถูกใช้ 3 ที่ที่ต้องตรงกันเสมอ:
 *     1. `lazada:scan-deals`                     — ตอนคัดของเข้า
 *     2. `StorefrontController::getFlashDeals()`  — ตอนเลือกของขึ้นแถบหน้าแรก
 *     3. ตัวกรอง `?deals=1`                       — หน้า "ดู Flash Deals ทั้งหมด"
 *
 *   ถ้าใครเผลอเขียน `config('lazada-deals.fresh_hours')` ตรง ๆ ที่ใดที่หนึ่ง
 *   แอดมินแก้ค่าในหลังบ้านแล้วจะมีผลแค่บางหน้า ⇒ ของบนแถบหน้าแรกกับของในหน้ารวม
 *   กลายเป็นคนละชุด **โดยไม่มี error ที่ไหนเลย** ลูกค้าเห็นคนเดียว
 *
 * ⚠️ อ่าน source ตรง ๆ — คลาสจริงต้องมี app container + DB ถึงจะเรียกได้
 *    ([[rule_verification_script_can_lie]] — ตรวจไฟล์จริง ไม่ใช่เดาจากชื่อคลาส)
 */
class LazadaDealSettingsSingleSourceTest extends TestCase
{
    private function root(): string
    {
        return __DIR__.'/../../..';
    }

    /** ไฟล์ที่อนุญาตให้แตะ config('lazada-deals.*') ได้ */
    private function allowedFiles(): array
    {
        return [
            'app/Support/LazadaDealSettings.php', // ตัวอ่านค่าเอง — ใช้ config เป็นค่าปริยาย
        ];
    }

    /** ไฟล์ที่ต้องอ่านผ่าน LazadaDealSettings เท่านั้น */
    private function consumerFiles(): array
    {
        return [
            'app/Console/Commands/LazadaScanDeals.php',
            'app/Http/Controllers/StorefrontController.php',
            'app/Http/Controllers/Admin/LazadaHub/FlashDealsController.php',
        ];
    }

    /**
     * ตัดคอมเมนต์ออกก่อนตรวจ
     *
     * ⚠️ จำเป็น — docblock ของไฟล์พวกนี้ "เขียนเตือนห้ามใช้ config('lazada-deals.*')"
     *    ถ้าตรวจทั้งไฟล์ดิบ ประโยคเตือนจะทำให้เทสต์แดงเอง (เคยแดงมาแล้วรอบแรก)
     */
    private function codeOnly(string $path): string
    {
        $out = '';
        foreach (token_get_all(file_get_contents($path)) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $out .= is_array($token) ? $token[1] : $token;
        }

        return $out;
    }

    /** ผู้ใช้ค่าตั้งทุกตัวห้ามอ่าน config('lazada-deals.*') ตรง ๆ */
    public function test_consumers_never_read_config_directly(): void
    {
        foreach ($this->consumerFiles() as $relative) {
            $path = $this->root().'/'.$relative;
            $this->assertFileExists($path, "ไม่เจอ {$relative}");

            $this->assertStringNotContainsString(
                "config('lazada-deals",
                $this->codeOnly($path),
                "{$relative} อ่าน config('lazada-deals.*') ตรง ๆ — ต้องผ่าน LazadaDealSettings ".
                'ไม่งั้นค่าที่แอดมินตั้งในหลังบ้านจะมีผลไม่ครบทุกหน้า'
            );
        }
    }

    /** ตัวอ่านค่ากลางต้องยังใช้ config เป็นค่าปริยายอยู่ (ติดตั้งใหม่ที่ยังไม่มีแถวใน DB ต้องใช้ได้) */
    public function test_resolver_still_falls_back_to_config(): void
    {
        $source = file_get_contents($this->root().'/'.$this->allowedFiles()[0]);

        $this->assertStringContainsString("config('lazada-deals.filters'", $source);
        $this->assertStringContainsString("config('lazada-deals.seeds'", $source);
        $this->assertStringContainsString('marketplace_settings', $source, 'ต้องอ่านค่าที่แอดมินตั้งจากตาราง marketplace_settings');
    }

    /** หน้าแรกกับหน้า "ดูทั้งหมด" ต้องใช้เกณฑ์ความสดตัวเดียวกัน */
    public function test_storefront_uses_resolver_for_both_lanes(): void
    {
        $source = file_get_contents($this->root().'/app/Http/Controllers/StorefrontController.php');

        $this->assertSame(
            2,
            substr_count($source, 'LazadaDealSettings::freshHours()'),
            'ต้องเรียก freshHours() ทั้งใน getFlashDeals() และตัวกรอง ?deals=1 — ขาดที่ใดที่หนึ่ง = สองหน้าเห็นของคนละชุด'
        );
    }

    /** cron ต้องเคารพสวิตช์เปิด/ปิด แต่การรันมือต้องทำงานได้เสมอ */
    public function test_scheduled_run_respects_master_switch(): void
    {
        $command = file_get_contents($this->root().'/app/Console/Commands/LazadaScanDeals.php');
        $this->assertStringContainsString(
            "\$this->option('scheduled') && ! LazadaDealSettings::enabled()",
            $command,
            'รอบ cron ต้องเช็คสวิตช์ใหญ่ก่อนกวาด'
        );

        $console = file_get_contents($this->root().'/routes/console.php');
        $this->assertStringContainsString(
            "Schedule::command('lazada:scan-deals --scheduled')",
            $console,
            'ตาราง cron ต้องส่ง --scheduled ไม่งั้นสวิตช์ปิดในหลังบ้านจะไม่มีผล'
        );
    }

    /** ปุ่ม "กวาดเดี๋ยวนี้" ต้องผ่านคิว และ job ต้องประกาศ timeout ของตัวเอง */
    public function test_scan_job_declares_its_own_timeout(): void
    {
        $job = file_get_contents($this->root().'/app/Jobs/ScanLazadaDealsJob.php');

        $this->assertMatchesRegularExpression(
            '/public \$timeout = (\d+);/',
            $job,
            'ต้องประกาศ $timeout เอง — worker บนพร็อดรัน --timeout=120 ซึ่งสั้นกว่าเวลาที่การกวาดใช้จริง'
        );

        preg_match('/public \$timeout = (\d+);/', $job, $m);
        $this->assertGreaterThanOrEqual(600, (int) $m[1], '$timeout ต้องยาวพอสำหรับรอบกวาดที่นานได้ถึง ~5 นาที');

        $this->assertStringContainsString('Cache::forget(self::LOCK_KEY)', $job, 'ต้องปลดล็อกกันกดซ้ำเสมอ ไม่งั้นแอดมินกดใหม่ไม่ได้ 15 นาที');
    }

    /** ลบคำค้นออกหมดต้องไม่กลายเป็น "ใช้ชุดปริยาย" (กลับหัวกับที่แอดมินตั้งใจ) */
    public function test_empty_seed_list_turns_the_switch_off_instead_of_falling_back(): void
    {
        $controller = file_get_contents($this->root().'/app/Http/Controllers/Admin/LazadaHub/FlashDealsController.php');

        $this->assertStringContainsString(
            'if (empty($seeds)) {',
            $controller,
            'ต้องดักกรณีคำค้นว่าง'
        );
        $this->assertStringContainsString(
            '$enabled = false;',
            $controller,
            'คำค้นว่าง = ปิดสวิตช์ใหญ่ ไม่ใช่ปล่อยให้ตกไปใช้ชุดปริยาย 21 คำ'
        );
    }
}
