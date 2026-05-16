<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\CelticCrossService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🤖 (2026-05-16) Admin Ask AI background runner สำหรับ Celtic 99฿
 *
 * เลียนแบบ pattern จาก FortuneProcessDeepReading — รันใน process แยก
 * จาก web request ผ่าน Artisan::call() หลัง fastcgi_finish_request()
 *
 * ทำไมต้องเป็น Artisan command (ไม่ใช่เรียก service ตรงๆ ใน shutdown):
 *   - Laravel bootstrap fresh → DB connection สด, service container clean
 *   - หลีกเลี่ยง teardown issue ที่ Eloquent อาจ fail ใน register_shutdown_function
 *   - extend timeout ได้ปลอดภัย (300s) — ไม่กระทบ FPM main request
 *
 * Usage:
 *   php artisan fortune:celtic-admin-ask {readingId} {question}
 */
class FortuneCelticAdminAskAi extends Command
{
    protected $signature = 'fortune:celtic-admin-ask
                            {readingId : FortuneReading ID}
                            {question : คำถามที่แอดมินส่งให้ AI ทำนาย}';

    protected $description = '🤖 รัน AI ทำนาย Celtic 99฿ ตามคำถามของแอดมิน + push ลูกค้าอัตโนมัติ (background)';

    public function handle(): int
    {
        $readingId = (int) $this->argument('readingId');
        $question = (string) $this->argument('question');

        $startTime = microtime(true);

        Log::info('🤖 fortune:celtic-admin-ask เริ่มประมวลผล', [
            'reading_id' => $readingId,
            'question_len' => mb_strlen($question),
        ]);

        $reading = FortuneReading::find($readingId);
        if (! $reading) {
            Log::error('🤖 fortune:celtic-admin-ask: ไม่พบ reading', ['reading_id' => $readingId]);
            $this->error("Reading #{$readingId} ไม่พบ");

            return self::FAILURE;
        }

        if ($reading->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS) {
            Log::error('🤖 fortune:celtic-admin-ask: reading ไม่ใช่ Celtic', [
                'reading_id' => $readingId,
                'reading_type' => $reading->reading_type,
            ]);

            return self::FAILURE;
        }

        try {
            $settings = FortuneTellingSetting::getSettings();
            $service = new CelticCrossService($settings);

            $result = $service->askQuestionAsAdmin($reading, $question);

            $elapsed = (int) ((microtime(true) - $startTime) * 1000);

            Log::info('🤖 fortune:celtic-admin-ask เสร็จ', [
                'reading_id' => $readingId,
                'success' => $result['success'] ?? false,
                'sequence' => $result['sequence'] ?? null,
                'pushed' => $result['pushed'] ?? null,
                'response_len' => mb_strlen($result['response'] ?? ''),
                'elapsed_ms' => $elapsed,
                'service_message' => $result['message'] ?? null,
            ]);

            if (! ($result['success'] ?? false)) {
                $this->error('AI fail: '.($result['message'] ?? 'unknown'));

                return self::FAILURE;
            }

            $this->info('✅ AI ทำนายสำเร็จ + push '.(($result['pushed'] ?? false) ? 'OK' : 'FAIL'));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('🤖 fortune:celtic-admin-ask exception', [
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 800),
            ]);

            $this->error('Exception: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
