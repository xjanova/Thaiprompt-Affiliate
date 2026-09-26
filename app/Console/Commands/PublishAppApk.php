<?php

namespace App\Console\Commands;

use App\Services\AppDownloadService;
use Illuminate\Console\Command;

/**
 * นำไฟล์ APK ของแอป Thai Prompt APP ขึ้นให้ดาวน์โหลดจากเว็บ (ปุ่มโหลดแอปหน้าแรก + /app/download)
 *
 * @example
 * php artisan app:publish-apk /home/admin/tmp/ThaiPrompt-APP-3.384.0.apk --app-version=3.384.0 --version-code=41
 */
class PublishAppApk extends Command
{
    protected $signature = 'app:publish-apk
        {path : ไฟล์ APK บนเครื่องนี้}
        {--app-version= : เวอร์ชันแอป เช่น 3.384.0 (ตรงกับ app.json)}
        {--version-code= : versionCode ของ Android (ตรงกับ app.json)}';

    protected $description = 'นำไฟล์ APK แอป Thai Prompt APP ขึ้นให้ดาวน์โหลดผ่านเว็บ';

    public function handle(AppDownloadService $downloads): int
    {
        $version = trim((string) $this->option('app-version'));
        if ($version === '') {
            $this->error('ต้องระบุ --app-version (เช่น 3.384.0)');

            return self::FAILURE;
        }

        $code = $this->option('version-code');
        if ($code !== null && ! ctype_digit((string) $code)) {
            $this->error('--version-code ต้องเป็นตัวเลข');

            return self::FAILURE;
        }

        try {
            $apk = $downloads->publish((string) $this->argument('path'), $version, $code !== null ? (int) $code : null);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('เผยแพร่แล้ว: '.$apk['file'].' ('.number_format($apk['size'] / 1048576, 1).' MB)');
        $this->line('sha256: '.$apk['sha256']);
        $this->line('ไฟล์: '.$downloads->fileUrl($apk));
        $this->line('ปุ่มบนเว็บ: '.route('app.download'));

        return self::SUCCESS;
    }
}
