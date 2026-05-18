<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneVoiceStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * 🌥️ (2026-05-18) ย้ายไฟล์ voice mp3 จาก driver หนึ่ง → driver อื่น
 *
 *   php artisan fortune:migrate-voice-storage --from=local --to=r2
 *   php artisan fortune:migrate-voice-storage --from=local --to=r2 --dry-run
 *   php artisan fortune:migrate-voice-storage --from=local --to=r2 --limit=50
 *
 * Flow:
 *   1. หา FortuneReading ที่ voice_audio_disk = $from (หรือ null สำหรับ local)
 *   2. โหลดไฟล์จาก source disk
 *   3. อัปโหลดไป target disk (ผ่าน FortuneVoiceStorageService)
 *   4. อัปเดต voice_audio_disk = $to
 *   5. ลบไฟล์ source (ยกเว้น --keep-source)
 *
 * ปลอดภัย:
 *   - ใช้ --dry-run ลองก่อน ดูว่าจะย้ายกี่ไฟล์
 *   - ใช้ --keep-source ถ้าอยากเก็บไฟล์ source ไว้สำรอง
 *   - ใช้ --limit จำกัด batch แรกๆ ก่อนรัน full
 */
class FortuneMigrateVoiceStorage extends Command
{
    protected $signature = 'fortune:migrate-voice-storage
        {--from=local : Source driver (local/r2/s3/gcs/firebase)}
        {--to=r2 : Target driver (local/r2/s3/gcs/firebase)}
        {--limit=0 : จำกัดจำนวน readings (0 = ไม่จำกัด)}
        {--dry-run : แค่แสดงรายการ ไม่ย้ายจริง}
        {--keep-source : ไม่ลบไฟล์ source หลังย้าย}';

    protected $description = '🌥️ ย้ายไฟล์ voice mp3 จาก driver หนึ่ง → อีก driver (local/r2/s3/gcs/firebase)';

    public function handle(): int
    {
        $from = $this->option('from');
        $to = $this->option('to');
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');
        $keepSource = (bool) $this->option('keep-source');

        if ($from === $to) {
            $this->error('--from กับ --to ต้องไม่เหมือนกัน');

            return self::FAILURE;
        }

        $validDrivers = ['local', 'r2', 's3', 'gcs', 'firebase'];
        if (! in_array($from, $validDrivers) || ! in_array($to, $validDrivers)) {
            $this->error('driver ต้องเป็น: '.implode(', ', $validDrivers));

            return self::FAILURE;
        }

        if (! Schema::hasColumn('fortune_readings', 'voice_audio_disk')) {
            $this->error('คอลัมน์ voice_audio_disk ยังไม่ migrate — รัน: php artisan migrate');

            return self::FAILURE;
        }

        $settings = FortuneTellingSetting::getSettings();
        $storage = new FortuneVoiceStorageService($settings);

        // ทดสอบ target driver ก่อน
        $this->info("🔍 ทดสอบ target driver: {$to}...");
        $testResult = $storage->testConnection($to);
        if (! $testResult['success']) {
            $this->error('Target driver ใช้ไม่ได้: '.$testResult['message']);

            return self::FAILURE;
        }
        $this->info($testResult['message']);

        // หา readings ที่ต้องย้าย
        $query = FortuneReading::whereNotNull('voice_audio_path');
        if ($from === 'local') {
            // local: รวมทั้ง null (backward compat) + 'local'
            $query->where(function ($q) {
                $q->whereNull('voice_audio_disk')->orWhere('voice_audio_disk', 'local');
            });
        } else {
            $query->where('voice_audio_disk', $from);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $total = $query->count();
        $this->info("📊 พบ readings ที่ต้องย้าย: {$total} อัน ({$from} → {$to})");

        if ($total === 0) {
            $this->info('ไม่มีอะไรต้องทำ');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('🧪 DRY RUN — ไม่ย้ายจริง');
            $sample = $query->limit(10)->get(['id', 'voice_audio_path', 'voice_audio_disk']);
            $this->table(
                ['ID', 'Path', 'Current Disk'],
                $sample->map(fn ($r) => [$r->id, $r->voice_audio_path, $r->voice_audio_disk ?? 'null (=local)'])
            );

            return self::SUCCESS;
        }

        if (! $this->confirm("ยืนยันย้าย {$total} readings จาก {$from} → {$to}?")) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $migrated = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];

        $readings = $query->cursor();

        foreach ($readings as $reading) {
            $relativePath = $reading->voice_audio_path;

            try {
                // 1. โหลดไฟล์จาก source
                $sourceContent = $this->fetchSource($from, $relativePath, $reading, $storage);

                if ($sourceContent === null) {
                    $skipped++;
                    $errors[] = "#{$reading->id}: source file ไม่เจอ ({$from}: {$relativePath})";
                    $bar->advance();

                    continue;
                }

                // 2. เขียน temp file
                $tempDir = storage_path('app/tmp-voice-migrate');
                if (! is_dir($tempDir)) {
                    @mkdir($tempDir, 0775, true);
                }
                $tempPath = $tempDir.'/migrate-'.$reading->id.'-'.uniqid().'.mp3';
                file_put_contents($tempPath, $sourceContent);

                // 3. Upload to target (สลับ driver ชั่วคราว)
                $origDriver = $settings->voice_storage_driver;
                $settings->voice_storage_driver = $to;
                $put = (new FortuneVoiceStorageService($settings))->putAudio($tempPath, $relativePath);
                $settings->voice_storage_driver = $origDriver;

                @unlink($tempPath);

                if (! $put['success']) {
                    $failed++;
                    $errors[] = "#{$reading->id}: upload fail — {$put['error']}";
                    $bar->advance();

                    continue;
                }

                // 4. ลบ source (ถ้าไม่ keep)
                if (! $keepSource) {
                    $storage->deleteAudio($relativePath, $from);
                }

                // 5. อัปเดต DB
                $reading->update(['voice_audio_disk' => $to]);

                $migrated++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "#{$reading->id}: exception — ".$e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ ย้ายสำเร็จ: {$migrated}");
        if ($skipped > 0) {
            $this->warn("⏭️  ข้าม (source ไม่เจอ): {$skipped}");
        }
        if ($failed > 0) {
            $this->error("❌ ล้มเหลว: {$failed}");
        }

        if (! empty($errors)) {
            $this->newLine();
            $this->warn('Errors ('.count($errors).'):');
            foreach (array_slice($errors, 0, 10) as $err) {
                $this->line('  - '.$err);
            }
            if (count($errors) > 10) {
                $this->line('  ... และอีก '.(count($errors) - 10).' รายการ');
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * อ่านไฟล์จาก source driver (return binary content หรือ null ถ้าไม่เจอ)
     */
    protected function fetchSource(string $driver, string $path, FortuneReading $reading, FortuneVoiceStorageService $storage): ?string
    {
        try {
            if ($driver === 'local') {
                $absPath = Storage::disk('public')->path($path);
                if (! file_exists($absPath)) {
                    return null;
                }

                return file_get_contents($absPath) ?: null;
            }

            // cloud: ดึงผ่าน URL (HTTP GET)
            $url = $storage->audioUrl($path, $driver);
            if (empty($url)) {
                return null;
            }

            $content = @file_get_contents($url);

            return $content !== false ? $content : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
