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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * ประมวลผลคำทำนายเชิงลึกแบบ background (Queue Job)
 *
 * แก้ปัญหา: web server timeout (~60 วินาที) ฆ่า PHP process ก่อนที่
 * AI จะสร้างคำทำนายครบ 3 ข้อ + บันทึกลง DB ได้
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
     * 2. exec() background process → รัน artisan command แยก process (ไม่ติด web timeout)
     * 3. Sync fallback → รันใน request เดิม (อาจติด web timeout)
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

        // Sync driver → ใช้ exec() รัน artisan command ใน background process แยก
        // ไม่ติด web server (nginx) timeout เพราะเป็น process อิสระ
        $artisan = base_path('artisan');
        $php = self::findPhpBinary();
        $notifArg = $notificationId ? " --notification-id={$notificationId}" : '';

        // สร้าง command สำหรับ background process
        $cmd = sprintf(
            '%s %s fortune:process-deep %d %s %s%s',
            escapeshellarg($php),
            escapeshellarg($artisan),
            $readingId,
            escapeshellarg($platform),
            escapeshellarg($userId),
            $notifArg
        );

        // ตรวจสอบ OS เพื่อรัน background ให้ถูกต้อง
        if (self::isWindows()) {
            // Windows: ใช้ start /B
            $bgCmd = "start /B {$cmd} > NUL 2>&1";
        } else {
            // Linux/Mac: ใช้ & + nohup
            $bgCmd = "nohup {$cmd} > /dev/null 2>&1 &";
        }

        Log::info('ProcessDeepFortuneReadingJob: exec background process', [
            'reading_id' => $readingId,
            'command' => $cmd,
        ]);

        \exec($bgCmd);
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
