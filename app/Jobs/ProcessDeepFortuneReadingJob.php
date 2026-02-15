<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\SmsPaymentNotification;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * ประมวลผลคำทำนายเชิงลึกแบบ background (Queue Job)
 *
 * แก้ปัญหา: web server timeout (~60 วินาที) ฆ่า PHP process ก่อนที่
 * AI จะสร้างคำทำนายครบ 2 ข้อ + บันทึกลง DB ได้
 *
 * Job นี้ทำงาน background → ไม่ติด nginx/Apache timeout
 * - สร้าง birth chart + ส่งรูป
 * - สร้างคำทำนายทีละข้อ + ส่งผ่าน Messenger ทันที
 * - บันทึก deep_response ลง DB
 * - ส่งข้อความขอบคุณ
 *
 * Queue Strategy:
 * - ลอง database queue ก่อน (ต้องมี queue:work worker)
 * - ถ้า jobs table ไม่มี → fallback เป็น default driver (sync)
 *
 * Retry: 2 ครั้ง, backoff: 15s, 60s
 * Timeout: 300 วินาที (5 นาที — เผื่อ AI ช้า)
 */
class ProcessDeepFortuneReadingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * จำนวนครั้งที่ retry สูงสุด
     */
    public int $tries = 2;

    /**
     * Job timeout (วินาที) — ต้องมากพอสำหรับ 3 AI calls + message sending
     */
    public int $timeout = 300;

    /**
     * Exponential backoff (วินาที)
     */
    public array $backoff = [15, 60];

    /**
     * ข้อมูลที่ใช้ในการสร้างคำทำนาย
     */
    public int $readingId;

    public ?int $notificationId;

    public string $platform;

    public string $userId;

    /**
     * สร้าง job instance
     *
     * @param  int  $readingId  FortuneReading ID
     * @param  int|null  $notificationId  SmsPaymentNotification ID (ถ้ามาจาก SMS)
     * @param  string  $platform  'facebook' หรือ 'line'
     * @param  string  $userId  Platform user ID (Facebook PSID / LINE user ID)
     */
    public function __construct(int $readingId, ?int $notificationId, string $platform, string $userId)
    {
        $this->readingId = $readingId;
        $this->notificationId = $notificationId;
        $this->platform = $platform;
        $this->userId = $userId;

        // ไม่ force connection ใน constructor — ให้ dispatchSmart() ตัดสินใจ
    }

    /**
     * Dispatch อัจฉริยะ — รัน fortune processing ใน background เสมอ
     *
     * Priority:
     * 1. Queue driver จริง (database/redis) → dispatch ไป queue worker
     * 2. proc_open() background process → รัน artisan command แยก process (ไม่ติด web timeout)
     * 3. Artisan::call() sync + fastcgi_finish_request() → รันใน process เดิม (flush response ก่อน)
     *
     * @return void
     */
    public static function dispatchSmart(int $readingId, ?int $notificationId, string $platform, string $userId): void
    {
        $driver = config('queue.default', 'sync');

        Log::info('ProcessDeepFortuneReadingJob: dispatch', [
            'reading_id' => $readingId,
            'queue_driver' => $driver,
            'platform' => $platform,
        ]);

        // ถ้ามี queue driver จริง → dispatch ไป queue worker (background)
        if ($driver !== 'sync') {
            $job = new self($readingId, $notificationId, $platform, $userId);
            $job->onQueue('fortune-deep');
            dispatch($job);

            return;
        }

        // Sync driver → ลองรัน background process หรือ fallback เป็น Artisan::call()
        // ลอง proc_open() ก่อน (exec() อาจถูก disable ใน php.ini)
        if (\function_exists('proc_open')) {
            self::dispatchViaProcOpen($readingId, $notificationId, $platform, $userId);

            return;
        }

        // Fallback: ใช้ Artisan::call() ตรงๆ (sync — อาจติด timeout)
        // พยายาม flush response ก่อนเพื่อให้ user ไม่ต้องรอ
        Log::info('ProcessDeepFortuneReadingJob: fallback to Artisan::call (sync)', [
            'reading_id' => $readingId,
        ]);

        // ขยาย execution time เพื่อป้องกัน PHP timeout
        \set_time_limit(300);

        // Flush response กลับ user ก่อน (ถ้าเป็น FPM)
        if (\function_exists('fastcgi_finish_request')) {
            \fastcgi_finish_request();
        }

        $args = [
            'readingId' => $readingId,
            'platform' => $platform,
            'userId' => $userId,
        ];

        if ($notificationId) {
            $args['--notification-id'] = $notificationId;
        }

        Artisan::call('fortune:process-deep', $args);
    }

    /**
     * รัน artisan command ใน background ผ่าน proc_open()
     *
     * ใช้แทน exec() เมื่อ exec() ถูก disable ใน php.ini
     * proc_open() สร้าง process แยกที่ไม่ติด web server timeout
     */
    private static function dispatchViaProcOpen(int $readingId, ?int $notificationId, string $platform, string $userId): void
    {
        $artisan = \base_path('artisan');
        $php = self::findPhpBinary();
        $notifArg = $notificationId ? " --notification-id={$notificationId}" : '';

        // สร้าง command
        $cmd = \sprintf(
            '%s %s fortune:process-deep %d %s %s%s',
            \escapeshellarg($php),
            \escapeshellarg($artisan),
            $readingId,
            \escapeshellarg($platform),
            \escapeshellarg($userId),
            $notifArg
        );

        Log::info('ProcessDeepFortuneReadingJob: proc_open background process', [
            'reading_id' => $readingId,
            'command' => $cmd,
        ]);

        // ใช้ proc_open() เพื่อรัน background process
        // รองรับทั้ง Unix (nohup + &) และ Windows (start /B)
        if (self::isWindows()) {
            // Windows: ใช้ start /B เพื่อรัน background + NUL แทน /dev/null
            $bgCmd = "start /B {$cmd} > NUL 2>&1";
            $descriptors = [
                0 => ['file', 'NUL', 'r'],   // stdin
                1 => ['file', 'NUL', 'w'],   // stdout
                2 => ['file', 'NUL', 'w'],   // stderr
            ];
        } else {
            // Unix/Linux: ใช้ nohup + & เพื่อให้ process ทำงานอิสระจาก parent
            $bgCmd = "nohup {$cmd} > /dev/null 2>&1 &";
            $descriptors = [
                0 => ['file', '/dev/null', 'r'],  // stdin
                1 => ['file', '/dev/null', 'w'],  // stdout
                2 => ['file', '/dev/null', 'w'],  // stderr
            ];
        }

        $process = \proc_open($bgCmd, $descriptors, $pipes);

        if (\is_resource($process)) {
            // proc_close() รอ shell command เสร็จ (แต่ shell จะ return ทันที
            // เพราะใช้ & หรือ start /B ให้ child process ทำงาน background)
            \proc_close($process);
        }
    }

    /**
     * หา PHP binary path
     */
    private static function findPhpBinary(): string
    {
        // ลองใช้ PHP_BINARY ก่อน
        if (defined('PHP_BINARY') && PHP_BINARY !== '') {
            return PHP_BINARY;
        }

        // Fallback: ใช้ 'php' ใน PATH
        return 'php';
    }

    /**
     * ตรวจสอบว่าเป็น Windows หรือไม่
     */
    private static function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * ประมวลผล Job
     *
     * เรียก processPaymentConfirmed() ใน background — ไม่ติด web server timeout
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        // ขยาย PHP execution time (สำคัญเมื่อรัน sync)
        set_time_limit(300);

        Log::info('🔮 ProcessDeepFortuneReadingJob: เริ่มประมวลผล', [
            'reading_id' => $this->readingId,
            'platform' => $this->platform,
            'user_id' => $this->userId,
            'attempt' => $this->attempts(),
            'connection' => $this->connection ?? 'unknown',
        ]);

        // ดึง FortuneReading จาก DB
        $reading = FortuneReading::find($this->readingId);

        if (! $reading) {
            Log::error('ProcessDeepFortuneReadingJob: ไม่พบ FortuneReading', [
                'reading_id' => $this->readingId,
            ]);

            return;
        }

        // ถ้า reading เสร็จแล้ว (deep_response มีอยู่) → ข้าม
        if (! empty($reading->deep_response) && $reading->conversation_status === FortuneReading::STATUS_COMPLETED) {
            Log::info('ProcessDeepFortuneReadingJob: คำทำนายเสร็จแล้ว — ข้าม', [
                'reading_id' => $this->readingId,
            ]);

            return;
        }

        // ดึง SMS notification (ถ้ามี)
        $notification = $this->notificationId
            ? SmsPaymentNotification::find($this->notificationId)
            : null;

        try {
            // สร้าง services
            $settings = FortuneTellingSetting::getSettings();
            $conversationService = new FortuneConversationService($settings);
            $channelManager = new FortuneChannelManager($settings);

            // เรียก processPaymentConfirmed() — streaming mode ส่งทีละข้อ
            $result = $conversationService->processPaymentConfirmed(
                $reading,
                $notification,
                $channelManager,
                $this->platform,
                $this->userId
            );

            // ถ้าไม่ได้ streaming (fallback) → ส่งข้อความรวม
            if (empty($result['streaming']) && ! empty($result['message'])) {
                $channelManager->sendResponse($this->platform, $this->userId, $result);
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('✅ ProcessDeepFortuneReadingJob: สำเร็จ', [
                'reading_id' => $this->readingId,
                'action' => $result['action'] ?? 'unknown',
                'duration_ms' => $duration,
                'attempt' => $this->attempts(),
            ]);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('❌ ProcessDeepFortuneReadingJob: ล้มเหลว', [
                'reading_id' => $this->readingId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'attempt' => $this->attempts(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            // Throw เพื่อให้ queue retry
            throw $e;
        }
    }

    /**
     * จัดการเมื่อ job ล้มเหลวถาวร (หลัง retry หมด)
     */
    public function failed(Throwable $exception): void
    {
        Log::critical('🚨 ProcessDeepFortuneReadingJob: ล้มเหลวถาวร', [
            'reading_id' => $this->readingId,
            'platform' => $this->platform,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // ส่งข้อความให้ user ว่าระบบมีปัญหา
        try {
            $settings = FortuneTellingSetting::getSettings();
            $channelManager = new FortuneChannelManager($settings);

            $errorMessage = "🔮 ขออภัยค่ะ ระบบสร้างคำทำนายเชิงลึกขัดข้อง\n\n"
                ."กรุณาทักแชทเพื่อแจ้งแอดมินได้เลยค่ะ\n"
                .'แอดมินจะจัดการให้เร็วที่สุดนะคะ 🙏';

            $channelManager->sendResponse($this->platform, $this->userId, [
                'action' => 'error',
                'message' => $errorMessage,
            ], ['from_admin' => true]);
        } catch (\Exception $e) {
            Log::error('ProcessDeepFortuneReadingJob: ส่งข้อความ error ไม่สำเร็จ', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ชื่อ Job สำหรับ monitoring
     */
    public function displayName(): string
    {
        return "ProcessDeepFortune[#{$this->readingId}:{$this->platform}]";
    }

    /**
     * Tags สำหรับ monitoring (Horizon)
     */
    public function tags(): array
    {
        return [
            'fortune-deep',
            "reading:{$this->readingId}",
            "platform:{$this->platform}",
        ];
    }
}
