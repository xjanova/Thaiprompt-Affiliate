<?php

namespace Tests\Unit\Services;

use App\Support\FontFile;
use PHPUnit\Framework\TestCase;

/**
 * 🔤 ไฟล์ใน resources/fonts ต้องเป็นฟอนต์จริง (2026-09-12)
 *
 * ที่มา: resources/fonts/DejaVuSans.ttf (commit 4286cf86a, 2026-02-16) เป็นหน้า "Page not found · GitHub"
 *   file_exists() ผ่าน ⇒ FortuneChartService::getSymbolFont() คืน path นี้ ⇒ @imagettftext ล้มเงียบ
 *   ⇒ สัญลักษณ์ดาว ☉☽♂ ในรูป PNG ไม่เคยขึ้นบน prod 7 เดือน โดยไม่มีเทสต์ไหนแดงเลย
 *
 * เทสต์ชุดนี้ล็อก:
 *   1. ทุกไฟล์ .ttf/.otf ใน resources/fonts ขึ้นต้นด้วย signature ของฟอนต์ (ไม่ใช่ HTML/ไฟล์ว่าง)
 *   2. ฟอนต์ที่โค้ดอ้างชื่อตรงๆ ต้องมีอยู่จริง
 *   3. GD อ่านฟอนต์ได้จริง (imagettfbbox ไม่คืน false) — ข้ามถ้าเครื่องไม่มี GD+FreeType
 *   4. FontFile::isReal() ปัดตกไฟล์ HTML ที่ตั้งชื่อ .ttf · firstReal() ข้ามไปตัวถัดไปได้
 *
 * ไม่แตะ DB และไม่บูตแอป
 */
class ResourceFontFilesTest extends TestCase
{
    /**
     * 4 ไบต์แรกของไฟล์ฟอนต์ — เขียนแยกจาก FontFile::SIGNATURES โดยตั้งใจ
     * (ถ้าค่าคงที่ในคลาสเพี้ยน เทสต์ต้องจับได้ ไม่ใช่ผ่านตามไปด้วย)
     */
    private const FONT_SIGNATURES = [
        "\x00\x01\x00\x00", // TrueType
        'OTTO',             // OpenType (CFF)
        'true',             // TrueType ของ Apple
        'ttcf',             // TrueType Collection
    ];

    /** ฟอนต์ที่โค้ดอ้างชื่อตรงๆ — หายไป = รูปพังเงียบ */
    private const REQUIRED_FONTS = [
        'NotoSansThai-Bold.ttf', // FortuneChartService::getThaiFont · Celtic · PaymentBanner · RichMenu
        'DejaVuSans.ttf',        // FortuneChartService::getSymbolFont — สัญลักษณ์ดาว ☉☽♂☿♃♀♄☊☋
    ];

    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'font-file-test-'.bin2hex(random_bytes(4));
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    private function fontsDir(): string
    {
        return dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'fonts';
    }

    /**
     * ไฟล์ฟอนต์ทั้งหมดใน resources/fonts (ไม่ใช้ GLOB_BRACE — บางระบบไม่รองรับ)
     *
     * @return array<string, string> ชื่อไฟล์ => path เต็ม
     */
    private function fontFiles(): array
    {
        $files = [];
        foreach (scandir($this->fontsDir()) ?: [] as $name) {
            if (in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), ['ttf', 'otf'], true)) {
                $files[$name] = $this->fontsDir().DIRECTORY_SEPARATOR.$name;
            }
        }

        return $files;
    }

    public function test_ทุกไฟล์ฟอนต์ใน_resources_fonts_ขึ้นต้นด้วย_signature_ของฟอนต์(): void
    {
        $files = $this->fontFiles();
        $this->assertNotEmpty($files, 'ไม่เจอไฟล์ฟอนต์ใน resources/fonts เลย');

        foreach ($files as $name => $path) {
            $head = (string) file_get_contents($path, false, null, 0, 4);

            $this->assertContains(
                $head,
                self::FONT_SIGNATURES,
                "resources/fonts/{$name} ไม่ใช่ไฟล์ฟอนต์ — 4 ไบต์แรก = ".bin2hex($head)
                .' (ถ้าข้างในเป็น <!DOCTYPE html> = โหลดหน้าเว็บมาแทนไฟล์ ต้องโหลดใหม่จาก release ทางการ)'
            );
            $this->assertTrue(FontFile::isReal($path), "FontFile::isReal() ปัดตก resources/fonts/{$name} ทั้งที่ signature ถูก");
        }
    }

    public function test_ฟอนต์ที่โค้ดอ้างชื่อตรงๆ_ต้องมีอยู่จริง(): void
    {
        $files = $this->fontFiles();

        foreach (self::REQUIRED_FONTS as $name) {
            $this->assertArrayHasKey($name, $files, "resources/fonts/{$name} หายไป — โค้ดอ้างชื่อไฟล์นี้ตรงๆ");
        }
    }

    public function test_gd_อ่านฟอนต์ทุกไฟล์ได้จริง(): void
    {
        if (! function_exists('imagettfbbox')) {
            $this->markTestSkipped('เครื่องนี้ไม่มี GD + FreeType');
        }

        foreach ($this->fontFiles() as $name => $path) {
            $this->assertIsArray(@imagettfbbox(14, 0, $path, 'Ag 123'), "GD อ่าน resources/fonts/{$name} ไม่ได้ (imagettfbbox คืน false)");
        }
    }

    public function test_is_real_ปัดตกหน้า_html_ที่ตั้งชื่อเป็น_ttf(): void
    {
        // หน้าตาไฟล์ที่เคยอยู่ในรีโปจริง — ขึ้นบรรทัดว่าง แล้วตามด้วย <!DOCTYPE html>
        $fake = $this->tmpDir.DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        file_put_contents($fake, "\n\n\n\n\n\n\n\n<!DOCTYPE html>\n<html lang=\"en\"><title>Page not found · GitHub</title></html>");
        $this->assertFalse(FontFile::isReal($fake));

        $empty = $this->tmpDir.DIRECTORY_SEPARATOR.'empty.ttf';
        file_put_contents($empty, '');
        $this->assertFalse(FontFile::isReal($empty));

        $this->assertFalse(FontFile::isReal($this->tmpDir.DIRECTORY_SEPARATOR.'missing.ttf'));
        $this->assertFalse(FontFile::isReal($this->tmpDir), 'โฟลเดอร์ไม่ใช่ฟอนต์');
        $this->assertFalse(FontFile::isReal(''));
    }

    public function test_first_real_ข้ามไฟล์ปลอมกับไฟล์ที่ไม่มี_ไปเจอฟอนต์จริงตัวถัดไป(): void
    {
        $fake = $this->tmpDir.DIRECTORY_SEPARATOR.'fake.ttf';
        file_put_contents($fake, '<!DOCTYPE html>');
        $missing = $this->tmpDir.DIRECTORY_SEPARATOR.'missing.ttf';
        $real = $this->fontsDir().DIRECTORY_SEPARATOR.'NotoSansThai-Bold.ttf';

        $this->assertSame($real, FontFile::firstReal([$missing, $fake, $real]));
        $this->assertNull(FontFile::firstReal([$missing, $fake]), 'ไม่มีฟอนต์จริงสักตัว ต้องคืน null ไม่ใช่คืนไฟล์ปลอม');
    }
}
