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
     * Priority (ปรับปรุงใหม่):
     * 1. proc_open() background process → รัน artisan command แยก process (เชื่อถือได้สุด, ไม่ต้องพึ่ง queue worker)
     * 2. Queue driver จริง (database/redis) → dispatch ไป queue worker (ต้องมี worker รัน)
     * 3. Artisan::call() sync + fastcgi_finish_request() → รันใน process เดิม (flush response ก่อน)
     *
     * หมายเหตุ: เปลี่ยนจาก queue-first เป็น proc_open-first เพราะ:
     * - Queue worker อาจไม่ได้รันหรือไม่ได้ listen ที่ fortune-deep queue
     * - proc_open() สร้าง process อิสระที่ไม่ติด web server timeout
     * - ไม่ต้องพึ่ง supervisor หรือ queue worker daemon
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

        // ✅ ลำดับแรก: proc_open() — เชื่อถือได้สูงสุด, ไม่ต้องพึ่ง queue worker
        // สร้าง background process แยกที่ไม่ติด web server timeout
        if (\function_exists('proc_open')) {
            Log::info('ProcessDeepFortuneReadingJob: ใช้ proc_open (primary strategy)', [
                'reading_id' => $readingId,
            ]);
            self::dispatchViaProcOpen($readingId, $notificationId, $platform, $userId);

            return;
        }

        // ✅ ลำดับสอง: Queue driver จริง → dispatch ไป queue worker
        // ใช้เมื่อ proc_open ไม่มี (disabled ใน php.ini)
        if ($driver !== 'sync') {
            Log::info('ProcessDeepFortuneReadingJob: fallback to queue dispatch (proc_open unavailable)', [
                'reading_id' => $readingId,
                'queue' => 'fortune-deep',
            ]);
            $job = new self($readingId, $notificationId, $platform, $userId);
            $job->onQueue('fortune-deep');
            dispatch($job);

            return;
        }

        // ✅ ลำดับสุดท้าย: Artisan::call() sync (ถ้าทั้ง proc_open + queue ใช้ไม่ได้)
        // พยายาม flush response ก่อนเพื่อให้ user ไม่ต้องรอ
        Log::info('ProcessDeepFortuneReadingJob: fallback to Artisan::call (sync — last resort)', [
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
        $notifArg = $notificationId ? ' --notification-id=' . \escapeshellarg((string) $notificationId) : '';

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

        // ✅ Log output ไปไฟล์แทน /dev/null เพื่อ debug กรณี process ล้มเหลว
        $logFile = \storage_path("logs/fortune-deep-{$readingId}.log");

        Log::info('ProcessDeepFortuneReadingJob: proc_open background process', [
            'reading_id' => $readingId,
            'command' => $cmd,
            'log_file' => $logFile,
        ]);

        // ใช้ proc_open() เพื่อรัน background process
        // รองรับทั้ง Unix (nohup + &) และ Windows (start /B)
        if (self::isWindows()) {
            // Windows: ใช้ start /B เพื่อรัน background
            $bgCmd = "start /B {$cmd} >> \"{$logFile}\" 2>&1";
            $descriptors = [
                0 => ['file', 'NUL', 'r'],   // stdin
                1 => ['file', 'NUL', 'w'],   // stdout (shell stdout)
                2 => ['file', 'NUL', 'w'],   // stderr (shell stderr)
            ];
        } else {
            // Unix/Linux: ใช้ nohup + & เพื่อให้ process ทำงานอิสระจาก parent
            $bgCmd = "nohup {$cmd} >> \"{$logFile}\" 2>&1 &";
            $descriptors = [
                0 => ['file', '/dev/null', 'r'],  // stdin
                1 => ['file', '/dev/null', 'w'],  // stdout (shell stdout)
                2 => ['file', '/dev/null', 'w'],  // stderr (shell stderr)
            ];
        }

        $process = \proc_open($bgCmd, $descriptors, $pipes);

        if (\is_resource($process)) {
            // proc_close() รอ shell command เสร็จ (แต่ shell จะ return ทันที
            // เพราะใช้ & หรือ start /B ให้ child process ทำงาน background)
            \proc_close($process);
        } else {
            Log::error('ProcessDeepFortuneReadingJob: proc_open ล้มเหลว!', [
                'reading_id' => $readingId,
                'command' => $bgCmd,
            ]);
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

            // ✅ V3: ไม่ push เนื้อหาคำทำนาย → บันทึก DB เท่านั้น
            // เนื้อหาจริงจะส่งผ่าน replyMessage เมื่อ user ส่งข้อความมา (ฟรี!)
            // ✅ ส่ง platform + userId เพื่อให้ affiliate auto-register ทำงาน
            // channelManager = null → ไม่ push เนื้อหาคำทำนาย (streaming = false)
            $result = $conversationService->processPaymentConfirmed(
                $reading,
                $notification,
                null, // channelManager = null → streaming ปิด
                $this->platform,
                $this->userId
            );

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // ✅ ตั้ง flag "คำทำนายพร้อม"
            $reading->refresh();
            if (! empty($reading->deep_response)) {
                $reading->setConversationState('reading_ready_for_reply', true);
                $reading->setConversationState('reading_ready_at', now()->toIso8601String());
            }

            // ✅ Push แจ้งเตือนทันที "คำทำนายพร้อมแล้ว อ่านเลยไหม"
            // ลูกค้าจ่ายเงินแล้ว → ต้องแจ้งให้ได้ทันที (bypass gatekeeper + priority push)
            if (! empty($reading->deep_response) && $this->userId) {
                $alreadyNotified = $reading->getConversationState('reading_notification_sent', false);
                $retryCount = (int) $reading->getConversationState('reading_notification_retry_count', 0);

                if (! $alreadyNotified && $retryCount < 3) {
                    try {
                        $name = $reading->facebook_user_name ?? 'คุณ';
                        $readyMessage = "🔮✨ คุณ{$name}คะ คำทำนายพร้อมแล้วค่ะ!\n\n"
                            . "อ่านเลยไหมคะ? 💎\n\n"
                            . "💡 กด 'อ่านคำทำนาย' ด้านล่างเลยค่ะ ✨";

                        Log::info('ProcessDeepFortuneReadingJob: กำลัง push แจ้ง "คำทำนายพร้อมแล้ว" ทันที', [
                            'reading_id' => $this->readingId,
                            'platform' => $this->platform,
                            'user_id' => $this->userId,
                            'retry_count' => $retryCount,
                        ]);

                        $reading->setConversationState('reading_notification_attempted', true);
                        $reading->setConversationState('reading_notification_retry_count', $retryCount + 1);

                        $notifySent = false;

                        // ✅ สำหรับ LINE → ส่ง priority push ตรงก่อนเลย (เร็วสุด, bypass gatekeeper)
                        if ($this->platform === 'line') {
                            try {
                                $lineService = new \App\Services\LineFortuneService($settings);
                                $notifySent = $lineService->sendMessagePriority($this->userId, $readyMessage, [
                                    'quick_replies' => [
                                        ['label' => '📖 อ่านคำทำนาย', 'text' => 'อ่านคำทำนาย'],
                                        ['label' => '⏰ ไว้ดูทีหลัง', 'text' => 'ไว้ดูทีหลัง'],
                                    ],
                                ]);
                            } catch (\Exception $directErr) {
                                Log::warning('ProcessDeepFortuneReadingJob: LINE direct push ล้มเหลว → ลอง channelManager', [
                                    'reading_id' => $this->readingId,
                                    'error' => $directErr->getMessage(),
                                ]);
                            }
                        }

                        // Fallback: ผ่าน channelManager (สำหรับ Facebook หรือ LINE push ล้มเหลว)
                        if (! $notifySent) {
                            $notifySent = $channelManager->sendResponse($this->platform, $this->userId, [
                                'action' => 'fortune_ready_notification',
                                'message' => $readyMessage,
                                'reading' => $reading,
                                'quick_replies' => ['อ่านคำทำนาย', 'ไว้ดูทีหลัง'],
                            ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
                        }

                        $reading->setConversationState('reading_notification_sent', $notifySent);
                        $reading->setConversationState('reading_notification_sent_at', now()->toIso8601String());

                        Log::info('ProcessDeepFortuneReadingJob: push แจ้ง "คำทำนายพร้อมแล้ว" ผลลัพธ์', [
                            'reading_id' => $this->readingId,
                            'sent' => $notifySent,
                            'platform' => $this->platform,
                        ]);
                    } catch (\Exception $notifyErr) {
                        Log::warning('ProcessDeepFortuneReadingJob: push แจ้งเตือนล้มเหลว (fallback replyMessage)', [
                            'reading_id' => $this->readingId,
                            'error' => $notifyErr->getMessage(),
                        ]);
                        $reading->setConversationState('reading_notification_attempted', true);
                    }
                }
            }

            Log::info('✅ ProcessDeepFortuneReadingJob: สำเร็จ', [
                'reading_id' => $this->readingId,
                'action' => $result['action'] ?? 'unknown',
                'duration_ms' => $duration,
                'attempt' => $this->attempts(),
                'deep_response_length' => mb_strlen($reading->deep_response ?? ''),
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
     *
     * เปลี่ยนสถานะบิลเป็น completed เพื่อไม่ให้ค้างที่ paid ตลอดไป
     * แอดมินยังสามารถกด retryFortune ได้ในภายหลัง
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

        // เปลี่ยนสถานะเป็น completed เพื่อไม่ให้บิลค้างที่ paid
        // แอดมินยัง retry ได้เพราะ retryFortune() เช็คแค่ is_paid + มีคำถาม
        try {
            $reading = FortuneReading::find($this->readingId);
            if ($reading && $reading->conversation_status !== FortuneReading::STATUS_COMPLETED) {
                $reading->update([
                    'conversation_status' => FortuneReading::STATUS_COMPLETED,
                ]);
                Log::info('ProcessDeepFortuneReadingJob: เปลี่ยนสถานะเป็น completed หลัง retry หมด', [
                    'reading_id' => $this->readingId,
                ]);
            }
        } catch (\Exception $statusErr) {
            Log::error('ProcessDeepFortuneReadingJob: เปลี่ยนสถานะเป็น completed ไม่สำเร็จ', [
                'reading_id' => $this->readingId,
                'error' => $statusErr->getMessage(),
            ]);
        }

        // ✅ V3: ไม่ push error message ให้ลูกค้า (ประหยัดโควต้า LINE push)
        // fortune:check-pending จะตั้ง flag reading_ready_for_reply → user ส่งข้อความมาจะได้รับผ่าน replyMessage
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
