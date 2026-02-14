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

        // ใช้ database queue เพื่อรัน background จริง (ไม่ติด web timeout)
        // ต้องมี jobs table + queue:work worker
        $this->onConnection('database');
        $this->onQueue('fortune-deep');
    }

    /**
     * Dispatch job อัจฉริยะ — ลอง database queue ก่อน, fallback เป็น default
     *
     * ถ้า jobs table มีอยู่ → dispatch ไป database queue (background จริง)
     * ถ้าไม่มี → dispatch ตาม default driver (อาจเป็น sync)
     *
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public static function dispatchSmart(int $readingId, ?int $notificationId, string $platform, string $userId)
    {
        $job = new self($readingId, $notificationId, $platform, $userId);

        // ตรวจว่ามี jobs table หรือไม่
        try {
            if (Schema::hasTable('jobs')) {
                // มี jobs table → ใช้ database queue (background จริง)
                Log::info('ProcessDeepFortuneReadingJob: dispatch ไป database queue', [
                    'reading_id' => $readingId,
                ]);

                return dispatch($job);
            }
        } catch (\Exception $e) {
            Log::warning('ProcessDeepFortuneReadingJob: ไม่สามารถเช็ค jobs table', [
                'error' => $e->getMessage(),
            ]);
        }

        // ไม่มี jobs table → fallback เป็น default driver
        // ขยาย PHP timeout เพื่อป้องกัน PHP timeout (แม้ web server อาจยังตัด)
        Log::info('ProcessDeepFortuneReadingJob: fallback ไป default queue driver', [
            'reading_id' => $readingId,
        ]);

        $job->onConnection(config('queue.default', 'sync'));
        $job->onQueue('default');

        return dispatch($job);
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
