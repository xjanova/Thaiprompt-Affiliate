<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Services\CelticSpreadImageGenerator;
use Illuminate\Console\Command;

/**
 * วินิจฉัยระบบสร้างภาพ composite Celtic Cross spread
 *
 * รัน:
 *   php artisan fortune:celtic-spread-diagnose
 *   php artisan fortune:celtic-spread-diagnose --reading=12345 (ลอง generate จริง)
 *
 * ตรวจ:
 * 1. GD library installed
 * 2. Storage symlink (public/storage)
 * 3. Directory celtic-spreads/ writable
 * 4. Reading มีไพ่ครบ 10 ใบหรือไม่
 * 5. ลอง generate + verify ไฟล์จริง (ถ้าระบุ --reading)
 */
class FortuneCelticSpreadDiagnose extends Command
{
    protected $signature = 'fortune:celtic-spread-diagnose
                            {--reading= : Reading ID ที่จะลอง generate ภาพจริง}';

    protected $description = 'วินิจฉัยระบบสร้างภาพ composite Celtic Cross';

    public function handle(): int
    {
        $this->info('🔍 Celtic Cross Spread Image Diagnose');
        $this->newLine();

        // ── 1. GD library ──────────────────────────────────────
        $this->line('🖼️  GD library:');
        if (! extension_loaded('gd')) {
            $this->error('  ❌ GD ไม่ได้ติดตั้ง — ติดตั้งด้วย:');
            $this->line('     sudo apt install php8.3-gd');
            $this->line('     sudo systemctl restart php8.3-fpm');
            return self::FAILURE;
        }

        $gdInfo = gd_info();
        $this->line('  ✅ GD version: ' . ($gdInfo['GD Version'] ?? '-'));
        $this->line('  JPEG support: ' . (($gdInfo['JPEG Support'] ?? false) ? '✅' : '❌'));
        $this->line('  PNG support : ' . (($gdInfo['PNG Support'] ?? false) ? '✅' : '❌'));
        $this->line('  FreeType    : ' . (($gdInfo['FreeType Support'] ?? false) ? '✅ (font rendering พร้อม)' : '❌ (จะใช้ default font แทน)'));
        $this->newLine();

        // ── 2. Storage symlink ─────────────────────────────────
        $this->line('🔗 Storage symlink:');
        $publicStorage = public_path('storage');
        if (! is_link($publicStorage) && ! is_dir($publicStorage)) {
            $this->error('  ❌ public/storage ไม่มี symlink — รัน:');
            $this->line('     php artisan storage:link');
            return self::FAILURE;
        }
        $this->line('  ✅ public/storage มี (link หรือ dir)');
        if (is_link($publicStorage)) {
            $this->line('     → ชี้ไปที่: ' . readlink($publicStorage));
        }
        $this->newLine();

        // ── 3. Directory writable ──────────────────────────────
        $this->line('📁 Directory celtic-spreads/:');
        $absoluteDir = storage_path('app/public/celtic-spreads');
        if (! is_dir($absoluteDir)) {
            $created = @mkdir($absoluteDir, 0755, true);
            if (! $created) {
                $this->error('  ❌ สร้าง directory ไม่ได้: ' . $absoluteDir);
                return self::FAILURE;
            }
            $this->line('  📂 สร้างใหม่: ' . $absoluteDir);
        }
        if (! is_writable($absoluteDir)) {
            $this->error('  ❌ directory ไม่สามารถเขียนได้: ' . $absoluteDir);
            $this->line('     แก้: sudo chown -R www-data:www-data storage/app/public/celtic-spreads');
            $this->line('         sudo chmod -R 775 storage/app/public/celtic-spreads');
            return self::FAILURE;
        }
        $existingFiles = glob($absoluteDir . '/*.jpg');
        $this->line('  ✅ writable | ไฟล์ที่มีอยู่: ' . count($existingFiles) . ' ภาพ');
        if (count($existingFiles) > 0) {
            $latest = collect($existingFiles)->sortByDesc(fn ($f) => filemtime($f))->take(3);
            $this->line('  📄 3 ไฟล์ล่าสุด:');
            foreach ($latest as $f) {
                $size = filesize($f);
                $mtime = date('Y-m-d H:i:s', filemtime($f));
                $this->line(sprintf('     %s  %s  %d bytes', basename($f), $mtime, $size));
            }
        }
        $this->newLine();

        // ── 4. Thai font ──────────────────────────────────────
        $this->line('🔤 Thai font (สำหรับ imagettftext):');
        $fontCandidates = [
            resource_path('fonts/Sarabun-Regular.ttf'),
            resource_path('fonts/THSarabun.ttf'),
            resource_path('fonts/THSarabunNew.ttf'),
            base_path('public/fonts/Sarabun-Regular.ttf'),
        ];
        $foundFont = null;
        foreach ($fontCandidates as $f) {
            if (is_file($f)) {
                $foundFont = $f;
                break;
            }
        }
        if ($foundFont) {
            $this->line('  ✅ ' . $foundFont);
        } else {
            $this->warn('  ⚠️  ไม่พบ Thai font — จะใช้ default font (อาจแสดงข้อความเป็นกล่อง)');
            $this->line('     ลองดาวน์โหลด: https://fonts.google.com/specimen/Sarabun');
            $this->line('     วางที่: resources/fonts/Sarabun-Regular.ttf');
        }
        $this->newLine();

        // ── 5. Try generate (optional) ─────────────────────────
        $readingId = $this->option('reading');
        if ($readingId) {
            $this->line('🧪 ลอง generate spread image ของ reading #' . $readingId . ':');
            $reading = FortuneReading::find($readingId);
            if (! $reading) {
                $this->error('  ❌ ไม่พบ reading #' . $readingId);
                return self::FAILURE;
            }

            $cards = $reading->getCelticCards();
            $this->line('  ไพ่ที่เลือกไว้: ' . count($cards) . '/10 ใบ');
            if (count($cards) < 10) {
                $this->warn('  ⚠️  ไพ่ไม่ครบ 10 ใบ — generator จะ return null');
                return self::SUCCESS;
            }

            $generator = app(CelticSpreadImageGenerator::class);
            $url = $generator->generate($reading);

            if ($url) {
                $this->info('  ✅ generate สำเร็จ!');
                $this->line('     URL: ' . $url);
                $absolute = storage_path("app/public/celtic-spreads/{$reading->id}.jpg");
                if (is_file($absolute)) {
                    $this->line(sprintf('     ขนาดไฟล์: %d bytes (%.1f KB)', filesize($absolute), filesize($absolute) / 1024));
                }
            } else {
                $this->error('  ❌ generate ล้มเหลว — ตรวจ laravel.log: grep "CelticSpreadImage"');
            }
        } else {
            $this->line('💡 ลอง generate จริง: php artisan fortune:celtic-spread-diagnose --reading=<reading_id>');
        }
        $this->newLine();

        // ── 6. สรุป ─────────────────────────────────────────────
        $this->line('🩺 สรุป:');
        $this->info('  ✅ ทุกชั้น OK — ถ้ายังส่งภาพไม่สำเร็จใน production:');
        $this->line('     1. ตรวจ laravel.log: grep "CelticSpreadImage" | tail -50');
        $this->line('     2. ตรวจ FB ส่ง: grep "ส่งรูปภาพ" laravel.log | tail');
        $this->line('     3. ตรวจ APP_URL ถูก https + accessible: curl -I <url>');
        $this->line('     4. FB cache อาจค้าง — generator มี cache buster ?v={mtime} แล้ว');

        return self::SUCCESS;
    }
}
