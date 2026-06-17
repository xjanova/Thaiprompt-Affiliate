<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\SmsPaymentNotification;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Services\FortuneLocaleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
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
     */
    public static function dispatchSmart(int $readingId, ?int $notificationId, string $platform, string $userId): void
    {
        $driver = config('queue.default', 'sync');

        Log::info('ProcessDeepFortuneReadingJob: dispatch', [
            'reading_id' => $readingId,
            'queue_driver' => $driver,
            'platform' => $platform,
        ]);

        // ✅ ลำดับแรก: fastcgi_finish_request() + Artisan::call() sync
        // เชื่อถือได้สูงสุด — รันใน process เดียวกัน (ไม่มีทาง crash เงียบ)
        // fastcgi_finish_request() ส่ง response กลับ client ทันที → PHP process ทำงานต่อ background
        if (\function_exists('fastcgi_finish_request')) {
            Log::info('ProcessDeepFortuneReadingJob: ใช้ fastcgi_finish_request + sync (primary strategy)', [
                'reading_id' => $readingId,
            ]);

            \set_time_limit(300); // 5 นาที
            \fastcgi_finish_request(); // ส่ง response กลับ client ทันที

            $args = [
                'readingId' => $readingId,
                'platform' => $platform,
                'userId' => $userId,
            ];
            if ($notificationId) {
                $args['--notification-id'] = $notificationId;
            }

            try {
                Artisan::call('fortune:process-deep', $args);
                Log::info('ProcessDeepFortuneReadingJob: Artisan::call สำเร็จ (fastcgi sync)', [
                    'reading_id' => $readingId,
                ]);
            } catch (\Exception $e) {
                Log::error('ProcessDeepFortuneReadingJob: Artisan::call ล้มเหลว (fastcgi sync)', [
                    'reading_id' => $readingId,
                    'error' => $e->getMessage(),
                ]);
            }

            return;
        }

        // ✅ ลำดับสอง: proc_open() — ใช้เมื่อไม่มี fastcgi (เช่น Apache mod_php)
        // ⚠️ proc_open อาจ crash เงียบ → fortune:check-pending จะ retry ให้
        if (\function_exists('proc_open')) {
            Log::info('ProcessDeepFortuneReadingJob: ใช้ proc_open (fallback — no fastcgi)', [
                'reading_id' => $readingId,
            ]);
            self::dispatchViaProcOpen($readingId, $notificationId, $platform, $userId);

            return;
        }

        // ✅ ลำดับสาม: Queue driver จริง → dispatch ไป queue worker
        if ($driver !== 'sync') {
            Log::info('ProcessDeepFortuneReadingJob: fallback to queue dispatch', [
                'reading_id' => $readingId,
                'queue' => 'fortune-deep',
            ]);
            $job = new self($readingId, $notificationId, $platform, $userId);
            $job->onQueue('fortune-deep');
            dispatch($job);

            return;
        }

        // ✅ ลำดับสุดท้าย: Artisan::call() sync ตรง (ไม่มี fastcgi, ไม่มี proc_open, ไม่มี queue)
        Log::info('ProcessDeepFortuneReadingJob: fallback to Artisan::call (sync — last resort)', [
            'reading_id' => $readingId,
        ]);

        \set_time_limit(300);

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
        $notifArg = $notificationId ? ' --notification-id='.\escapeshellarg((string) $notificationId) : '';

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
        // 🩹 (2026-05-05) เพิ่มเงื่อนไข reading_sent_directly — ป้องกัน Pay-Later retry block
        //    เคสจริง: Pay-Later AI gen เสร็จ + status COMPLETED แล้ว แต่ Job's sendResponse fail
        //              (FB 24hr expired / network error) → flag reading_sent_directly ไม่ set
        //    เดิม: early-return → admin retry ก็ block (ลูกค้าไม่ได้คำทำนาย/QR ตลอดไป)
        //    ใหม่: เช็ค reading_sent_directly ด้วย — ถ้ายังไม่ส่ง → ผ่าน → Pay-Later block deliver ใหม่
        $alreadyDelivered = (bool) $reading->getConversationState('reading_sent_directly', false);
        if (! empty($reading->deep_response)
            && $reading->conversation_status === FortuneReading::STATUS_COMPLETED
            && $alreadyDelivered) {
            Log::info('ProcessDeepFortuneReadingJob: คำทำนายเสร็จและส่งแล้ว — ข้าม', [
                'reading_id' => $this->readingId,
            ]);

            return;
        }

        // 🛑 (2026-05-06) Pay-Later removed — ไม่มี skipAiRegenerate (always regenerate ถ้ายังไม่จ่าย)
        $skipAiRegenerate = false;

        // 🌐 (2026-05-03) Restore locale จาก stored value — queue worker ไม่มี request context
        //    หาก Lao ลูกค้าจ่าย — ทุก push (ทั้ง processPaymentConfirmed streaming + FB push branch)
        //    ต้องใช้ภาษาที่ resolve ไว้ก่อนหน้า ไม่งั้น lo() fallback กลับเป็น TH
        try {
            $storedLocale = FortuneLocaleService::getStored($this->platform, $this->userId)
                ?? FortuneLocaleService::LOCALE_TH;
            FortuneLocaleService::setCurrent($storedLocale);
        } catch (\Throwable $e) {
            // safe fallback — ไม่ทำให้ job fail เพราะ locale
            FortuneLocaleService::setCurrent(FortuneLocaleService::LOCALE_TH);
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
            // 🩹 (2026-05-05) skipAiRegenerate guard — Pay-Later partial recovery
            //    ถ้า AI gen เสร็จแล้ว (deep_response มี) แต่ยัง deliver ไม่สำเร็จ → ข้าม regenerate
            //    ป้องกัน double AI cost + potential prompt drift จากการ generate ซ้ำ
            if (! $skipAiRegenerate) {
                // 🔒 (2026-06-10) Generation lock — กัน AI generate ซ้อนหลาย process
                //   key เดียวกับ fortune:process-deep command (เคสจริง R5604: รันขนาน 3 รอบ
                //   → คำทำนายเขียนทับ + ส่งซ้ำ + AI cost ×3)
                $genLockKey = "fortune:deep_gen:{$this->readingId}";
                // (2026-06-17) TTL 300→600s — completeness-gate retry อาจทำให้ gen นานขึ้น;
                //   กัน lock หมดอายุระหว่าง generate แล้ว check-pending (ทุกนาที) dispatch ซ้ำ → gen ซ้อน
                if (! \Illuminate\Support\Facades\Cache::add($genLockKey, 1, 600)) {
                    Log::info('ProcessDeepFortuneReadingJob: generation lock ถูกถือโดย process อื่น — skip duplicate generation', [
                        'reading_id' => $this->readingId,
                    ]);

                    return;
                }

                try {
                    // 🔔 (2026-05-14) AI Ping — Loading update 10s/30s/60s + admin alert > 1 min
                    //   เปิด AI session ก่อน call AI — pings วิ่ง async ใน queue worker
                    //   ถ้า AI เสร็จก่อน 10s → ping ทั้งหมด skip (cache cleared)
                    \App\Services\Fortune\FortuneAiPingDispatcher::start(
                        $this->readingId,
                        $this->platform,
                        $this->userId,
                        'deep'
                    );

                    try {
                        $result = $conversationService->processPaymentConfirmed(
                            $reading,
                            $notification,
                            null, // channelManager = null → streaming ปิด
                            $this->platform,
                            $this->userId
                        );
                    } finally {
                        // ปิด AI session — pings ที่ยังไม่ run จะ skip
                        \App\Services\Fortune\FortuneAiPingDispatcher::complete($this->readingId);
                    }
                } finally {
                    // 🔓 ปล่อย gen lock เสมอ — gen เสร็จแล้ว deep_response มี (check-pending ไม่จับซ้ำ)
                    //    ถ้า gen ล้ม → retry รอบหน้า generate ได้
                    \Illuminate\Support\Facades\Cache::forget($genLockKey);
                }
            } else {
                Log::info('💎 Pay-Later recovery: skip AI regenerate — AI gen done previously, delivering only', [
                    'reading_id' => $this->readingId,
                ]);
                $result = ['action' => 'recovery_skip_ai'];
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // ✅ ตั้ง flag "คำทำนายพร้อม"
            $reading->refresh();
            if (! empty($reading->deep_response)) {
                $reading->setConversationState('reading_ready_for_reply', true);
                $reading->setConversationState('reading_ready_at', now()->toIso8601String());
            }

            // 🛑 (2026-05-06) Pay-Later flow ลบทิ้ง — Job รันเฉพาะ pay-first
            //   เดิม 130+ บรรทัด: createPaymentBill + send reading+QR ในข้อความเดียว
            //   ใหม่: ทุก paid reading รัน processPaymentConfirmed ตามปกติ → push reading
            //   (ลูกค้าจ่ายก่อน → SMS match → Job dispatch → AI gen → push)

            // ✅ Push เมื่อคำทำนายพร้อม (2026-04-27 — แตกต่างกันตาม platform)
            //
            // 📱 Facebook → push **คำทำนายเต็มทันที** (ไม่ถาม "พร้อมไหม?")
            //   ใช้ POST_PURCHASE_UPDATE message_tag — ฟรีตาม FB policy
            //   action: view_reading_deep → channelManager ส่งข้อความ + chart image
            //
            // 💎 LINE → push **แจ้งเตือนสั้นๆ Flex Message** (ยอมเสีย quota 1 ครั้งเพื่อ UX)
            //   - ใช้ buildFortuneReadyFlexMessage (ปุ่ม "อ่านคำทำนาย" สวยงาม)
            //   - คำทำนายเต็มยังส่งฟรีผ่าน replyMessage ตอน user กดอ่าน/ทักกลับมา
            //   - ตั้ง reading_notification_sent=true → FCS:766 จะส่งคำทำนายเต็มตอน user ตอบกลับ
            if (! empty($reading->deep_response) && $this->userId) {
                $alreadySent = $reading->getConversationState('reading_sent_directly', false);
                $alreadyNotified = $reading->getConversationState('reading_notification_sent', false);
                $retryCount = (int) $reading->getConversationState('reading_notification_retry_count', 0);

                // 🔒 (2026-06-09) Atomic delivery lock — กัน 2 job (primary sync + auto-retry) push ซ้ำ
                //   Bug (FTU-260609-B8104): guard read-then-write มีช่องว่าง ~45s (gen 14s + push ช้า)
                //   → auto-retry re-dispatch ระหว่าง job แรกยัง deliver ไม่เสร็จ → 2 job ผ่าน flag-guard
                //   พร้อมกัน → push view_reading_deep (chart + ไพ่) 2 ครั้ง.
                //   Cache::add() = atomic → มีแค่ job เดียวที่ได้ lock → deliver ครั้งเดียว
                $deliverLockKey = "fortune:deep_deliver:{$this->readingId}";
                $passFlagGuard = ! $alreadySent && ! $alreadyNotified && $retryCount < 3;
                $gotDeliverLock = $passFlagGuard
                    && \Illuminate\Support\Facades\Cache::add($deliverLockKey, 1, 600);

                if ($passFlagGuard && ! $gotDeliverLock) {
                    Log::info('ProcessDeepFortuneReadingJob: delivery lock ถูกถือโดย job อื่น — skip duplicate push', [
                        'reading_id' => $this->readingId,
                    ]);
                }

                if ($gotDeliverLock) {
                    $name = $reading->facebook_user_name ?? 'คุณ';
                    $reading->setConversationState('reading_notification_attempted', true);
                    $reading->setConversationState('reading_notification_retry_count', $retryCount + 1);

                    if ($this->platform === 'line') {
                        // 💎 LINE: push Flex แจ้งเตือนสั้นๆ (1 quota), คำทำนายเต็มส่งฟรีตอนตอบกลับ
                        try {
                            $lineService = new \App\Services\LineFortuneService($settings);
                            $readyMessage = "🔮✨ คุณ{$name}คะ คำทำนายพร้อมแล้วค่ะ!\n\n"
                                ."อ่านเลยไหมคะ? 💎\n\n"
                                ."💡 กด 'อ่านคำทำนาย' ด้านล่างเลยค่ะ ✨";

                            Log::info('ProcessDeepFortuneReadingJob: LINE — push Flex แจ้งเตือนสั้นๆ (1 quota)', [
                                'reading_id' => $this->readingId,
                                'user_id' => $this->userId,
                            ]);

                            // ลอง Flex สวยๆ ก่อน
                            $flex = $lineService->buildFortuneReadyFlexMessage(
                                $name,
                                $reading->bill_reference
                            );
                            $notifySent = $lineService->sendRichMessagePriority($this->userId, [
                                'alt_text' => '🔮 คำทำนายเชิงลึกพร้อมแล้ว! กดอ่านได้เลยค่ะ',
                                'contents' => $flex,
                            ]);

                            // Fallback: text + quick replies ถ้า Flex ล้มเหลว
                            if (! $notifySent) {
                                Log::warning('ProcessDeepFortuneReadingJob: LINE Flex push ล้มเหลว → fallback text', [
                                    'reading_id' => $this->readingId,
                                ]);
                                $notifySent = $lineService->sendMessagePriority($this->userId, $readyMessage, [
                                    'quick_replies' => [
                                        ['label' => '📖 อ่านคำทำนาย', 'text' => 'อ่านคำทำนาย'],
                                        ['label' => '⏰ ไว้ดูทีหลัง', 'text' => 'ไว้ดูทีหลัง'],
                                    ],
                                ]);
                            }

                            // 🛡️ (2026-05-03) เขียน flag เฉพาะ true — กัน transient lock
                            //    เคสเดิม: $notifySent=false → flag เขียน false → retry counter ขึ้น → 3 fail = lock
                            //    ตอนนี้: false → ไม่เขียน → reply path ตอน user ทักกลับยังทำงาน
                            if ($notifySent) {
                                $reading->setConversationState('reading_notification_sent', true);
                                $reading->setConversationState('reading_notification_sent_at', now()->toIso8601String());
                            } else {
                                // 🔓 push ล้ม → ปล่อย delivery lock เพื่อให้ retry/ทักกลับ deliver ได้
                                \Illuminate\Support\Facades\Cache::forget($deliverLockKey);
                                Log::warning('ProcessDeepFortuneReadingJob: LINE push fail (transient) — ไม่ lock, fallback ตอน user ทักกลับ', [
                                    'reading_id' => $this->readingId,
                                    'retry_count' => $retryCount,
                                ]);
                            }

                            Log::info('ProcessDeepFortuneReadingJob: LINE push แจ้งเตือนสั้น ผลลัพธ์', [
                                'reading_id' => $this->readingId,
                                'sent' => $notifySent,
                            ]);
                        } catch (\Exception $notifyErr) {
                            Log::warning('ProcessDeepFortuneReadingJob: LINE push แจ้งเตือนล้มเหลว', [
                                'reading_id' => $this->readingId,
                                'error' => $notifyErr->getMessage(),
                            ]);
                            // notification_attempted=true แล้ว → FCS:794 จะส่งคำทำนายเต็มทันทีตอนทักกลับ
                        }
                    } else {
                        // 📱 (2026-05-22) Facebook: push **คำทำนายเต็มทันที** (ไม่ถาม "พร้อมไหม?")
                        //    User spec 2026-05-22: "กล่องข้อความให้อ่านคำทำนายพร้อมแล้ว ใน fb ไม่ต้องมี
                        //                          เมื่อคำทำนายเสร็จแล้ว ส่งให้ลูกค้าทันทีเลย"
                        //
                        //    เดิม (2026-05-19 Batch 7): push fortune_ready_notification (button 2 ปุ่ม)
                        //    ใหม่: push view_reading_deep — ส่งภาพไพ่ + chart + คำทำนายเต็ม ด้วย POST_PURCHASE_UPDATE tag (ฟรี)
                        try {
                            $headerMessage = "🌟 *คำทำนายเชิงลึกของคุณ{$name}*\n";
                            $headerMessage .= '📋 เลขที่บิล: '.($reading->bill_reference ?? '-')."\n";
                            $headerMessage .= '📅 วันที่: '.$reading->created_at->format('d/m/Y H:i')."\n";
                            $headerMessage .= "═══════════════════════\n\n";
                            $headerMessage .= $reading->deep_response;

                            Log::info('ProcessDeepFortuneReadingJob: Facebook — push คำทำนายเต็มทันที (view_reading_deep)', [
                                'reading_id' => $this->readingId,
                                'user_id' => $this->userId,
                                'retry_count' => $retryCount,
                                'response_length' => mb_strlen($reading->deep_response ?? ''),
                            ]);

                            $sent = $channelManager->sendResponse($this->platform, $this->userId, [
                                'action' => 'view_reading_deep',
                                'message' => $headerMessage,
                                'reading' => $reading,
                                'chart_image_url' => $reading->reading_image_url,
                                // 🃏 ส่งรูปไพ่ยิปซีที่ลูกค้าจับได้ด้วย
                                'tarot_image_urls' => collect($reading->getCollectedTarotCards())
                                    ->pluck('image_url')->filter()->values()->all(),
                                // 🌙 (2026-05-22) ส่งกล่อง follow-up "หมออยู่ตอบเพิ่ม 10 นาที" หลังคำทำนาย
                                'send_pro_session_followup' => true,
                            ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);

                            // 🛡️ ตั้ง reading_sent_directly=true เมื่อส่งสำเร็จ (กัน duplicate delivery จาก reply path)
                            //    flag reading_notification_sent ตั้งด้วยเพื่อ skip Phase 2 ของ check-pending command
                            if ($sent) {
                                $reading->setConversationState('reading_sent_directly', true);
                                $reading->setConversationState('reading_ready_sent', true);
                                $reading->setConversationState('reading_ready_sent_at', now()->toIso8601String());
                                $reading->setConversationState('reading_notification_sent', true);
                                $reading->setConversationState('delivered_by_push', true);
                            } else {
                                // 🔓 push ล้ม → ปล่อย delivery lock เพื่อให้ retry/ทักกลับ deliver ได้
                                \Illuminate\Support\Facades\Cache::forget($deliverLockKey);
                                Log::warning('ProcessDeepFortuneReadingJob: Facebook push คำทำนายเต็ม fail (transient) — fallback ตอน user ทักกลับ', [
                                    'reading_id' => $this->readingId,
                                    'retry_count' => $retryCount,
                                ]);
                            }

                            Log::info('ProcessDeepFortuneReadingJob: Facebook push คำทำนายเต็ม ผลลัพธ์', [
                                'reading_id' => $this->readingId,
                                'sent' => $sent,
                            ]);
                        } catch (\Exception $notifyErr) {
                            // 🔓 push exception → ปล่อย delivery lock เพื่อให้ retry/ทักกลับ deliver ได้
                            \Illuminate\Support\Facades\Cache::forget($deliverLockKey);
                            Log::warning('ProcessDeepFortuneReadingJob: Facebook push คำทำนายเต็ม ล้มเหลว (จะ fallback ตอน user ทักกลับมา)', [
                                'reading_id' => $this->readingId,
                                'error' => $notifyErr->getMessage(),
                            ]);
                            // notification_attempted=true แล้ว → FCS:1287 จะส่งคำทำนายเต็มตอนทักกลับ
                        }
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

        } catch (\Throwable $e) {
            // 🩹 (2026-05-09) catch \Throwable แทน \Exception — PHP 8 TypeError/Error
            //                 ไม่ extends \Exception → จะ leak ผ่าน gen_processing clear ใน failed()
            //                 ก่อนหน้า: TypeError = silent skip 5 นาที, customer งง
            // 🔓 (2026-06-09) ปล่อย delivery lock เผื่อ crash (\Error) ระหว่าง push ก่อนตั้ง flag
            //   → queue retry deliver ได้ (ถ้า deliver สำเร็จไปแล้ว flag-guard กันซ้ำอยู่แล้ว)
            \Illuminate\Support\Facades\Cache::forget("fortune:deep_deliver:{$this->readingId}");

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

        // 🌙 (2026-05-08 v3 audit fix) Clear Quiet Period flag — ลูกค้าจะได้ตอบบอทได้
        //   ไม่งั้น flag ค้าง 5 นาที + ลูกค้าพิมพ์อะไรก็โดน silent skip / "หมอกำลังร่ายมนตร์"
        //   หลัง failure notification ส่งให้แล้ว ลูกค้าควรพิมพ์ติดต่อแอดมินได้
        try {
            if (! empty($this->userId)) {
                \Illuminate\Support\Facades\Cache::forget("fortune:gen_processing:{$this->userId}");
                \Illuminate\Support\Facades\Cache::forget("fortune:gen_announce:{$this->userId}");
            }
        } catch (\Throwable $cacheErr) {
            // ignore — non-blocking
        }

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

        // 🚨 (2026-05-03) Alert admin — ลูกค้าจ่ายเงินแล้ว AI ล้ม → admin ต้องรู้เพื่อ recover
        //    ก่อนหน้านี้: แค่ Log::critical (admin ต้องเข้าดู log เอง = ไม่ทันการ)
        $this->alertAdminAfterAiFailure($exception);

        // 🚨 V3.2: Push error notification ให้ลูกค้า (สำคัญ — ลูกค้าจ่ายเงินแล้ว ต้องรู้ว่าเกิดอะไรขึ้น)
        // ยอมเสีย LINE push 1 ครั้ง เพื่อรักษา trust + ลดกรณีลูกค้าคิดว่าโดนโกง
        try {
            $this->pushFailureNotification();
        } catch (\Exception $pushErr) {
            Log::error('ProcessDeepFortuneReadingJob: push failure notification ล้มเหลว', [
                'reading_id' => $this->readingId,
                'error' => $pushErr->getMessage(),
            ]);
        }
    }

    /**
     * แจ้งแอดมินว่า AI ล้มเหลวถาวร — ลูกค้าจ่ายเงินแล้ว ต้องการ human recovery
     *
     * ทำ 3 อย่าง (best-effort, แยก try/catch อย่ายอม block อะไร):
     * 1. ส่ง LINE alert ผ่าน LineAlertService (admin LINE OA)
     * 2. บันทึกใน FortuneTakeoverLog เพื่อ trace ใน admin UI
     * 3. ตั้ง flag ai_failed_alert ใน reading conversation_state — UI filter หาบิลที่ stuck ได้
     */
    protected function alertAdminAfterAiFailure(Throwable $exception): void
    {
        // 1. LINE alert (best-effort)
        try {
            $alertService = app(\App\Services\LineAlertService::class);
            $alertService->alertSystemError(
                'AI ดูดวงล้มเหลวถาวร — ลูกค้าจ่ายเงินแล้ว ต้อง recover ด่วน',
                [
                    'reading_id' => $this->readingId,
                    'platform' => $this->platform,
                    'user_id' => $this->userId,
                    'error' => mb_substr($exception->getMessage(), 0, 200),
                    'attempts' => $this->attempts(),
                    'admin_action' => 'ไปที่ /admin/fortune/billing แล้วกด retry หรือทำคำทำนายเอง — ลูกค้าได้ failure notification แล้ว',
                ]
            );
        } catch (\Throwable $alertErr) {
            Log::warning('ProcessDeepFortuneReadingJob: LINE alert admin ล้มเหลว (non-blocking)', [
                'reading_id' => $this->readingId,
                'error' => $alertErr->getMessage(),
            ]);
        }

        // 2. บันทึก takeover log + 3. ตั้ง flag ใน reading
        try {
            $reading = FortuneReading::find($this->readingId);
            if (! $reading) {
                return;
            }

            \App\Models\FortuneTakeoverLog::create([
                'fortune_reading_id' => $reading->id,
                'user_id' => null,
                'action' => \App\Models\FortuneTakeoverLog::ACTION_MESSAGE,
                'reason' => 'ai_failed_after_retries',
                'message' => 'AI ล้มเหลวถาวร — ต้อง admin recover (ลูกค้าจ่ายเงินแล้ว)',
                'platform' => $this->platform,
                'metadata' => [
                    'alert_type' => 'ai_failed_after_retries',
                    'error' => mb_substr($exception->getMessage(), 0, 500),
                    'attempts' => $this->attempts(),
                    'requires_admin_action' => true,
                ],
            ]);

            $reading->setConversationState('ai_failed_alert', true);
            $reading->setConversationState('ai_failed_alert_at', now()->toIso8601String());
            $reading->setConversationState('ai_failed_alert_error', mb_substr($exception->getMessage(), 0, 200));
        } catch (\Throwable $logErr) {
            Log::warning('ProcessDeepFortuneReadingJob: บันทึก ai_failed alert ล้มเหลว (non-blocking)', [
                'reading_id' => $this->readingId,
                'error' => $logErr->getMessage(),
            ]);
        }
    }

    /**
     * ส่งข้อความแจ้งลูกค้าเมื่อ AI ล้มเหลวถาวร
     *
     * แจ้งลูกค้าด้วยโทนนุ่ม (ไม่บอกว่า AI/ระบบขัดข้อง) พร้อมเสนอทางเลือก:
     * - รอทีมงานช่วยโดยตรง (ปุ่ม "คุยกับแม่หมอ")
     * - ทีมงานจะทำคำทำนายให้เอง (ไม่มีระบบคืนเงิน — ต้องส่งคำทำนายให้ครบ)
     */
    protected function pushFailureNotification(): void
    {
        $reading = FortuneReading::find($this->readingId);
        if (! $reading) {
            return;
        }

        // 🌙 (2026-06-06) user spec: "อย่าแจ้งลูกค้าว่าเอไอขัดข้องเด็ดขาด" — โทนนุ่ม คงคำมั่น+ปุ่มไว้
        $message = "🌙 ขออภัยค่ะ คำทำนายของคุณใช้เวลามากกว่าปกติเล็กน้อย\n\n"
            ."ทีมงานแม่หมอกำลังดูแลให้อยู่ และจะส่งคำทำนายให้ครบแน่นอนค่ะ\n\n"
            ."💬 กดปุ่ม 'คุยกับแม่หมอ' เพื่อให้ทีมงานช่วยโดยตรง\n"
            .'🔮 ขอเวลาสักครู่นะคะ — คำทำนายของคุณไม่หายไปไหนค่ะ';

        // ตรวจ platform ที่รู้จัก (กันกรณี queue payload พัง)
        if (! in_array($this->platform, ['line', 'facebook'], true)) {
            Log::warning('ProcessDeepFortuneReadingJob: platform ไม่รู้จัก ข้าม failure notification', [
                'reading_id' => $this->readingId,
                'platform' => $this->platform,
            ]);

            return;
        }

        try {
            if ($this->platform === 'line') {
                $settings = FortuneTellingSetting::getSettings();
                $lineService = new \App\Services\LineFortuneService($settings);

                // ส่ง Flex พร้อมปุ่ม "คุยกับแม่หมอ"
                $richContent = [
                    'alt_text' => '🌙 คำทำนายกำลังมา — กดคุยกับแม่หมอได้ค่ะ',
                    'contents' => [
                        'type' => 'bubble',
                        'body' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'contents' => [
                                ['type' => 'text', 'text' => '🌙 คำทำนายของคุณกำลังมาค่ะ', 'weight' => 'bold', 'size' => 'lg', 'color' => '#D97706'],
                                ['type' => 'text', 'text' => $message, 'wrap' => true, 'size' => 'sm', 'margin' => 'md'],
                            ],
                        ],
                        'footer' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'spacing' => 'sm',
                            'contents' => [
                                [
                                    'type' => 'button',
                                    'style' => 'primary',
                                    'color' => '#7C3AED',
                                    'action' => ['type' => 'message', 'label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
                                ],
                                [
                                    'type' => 'button',
                                    'style' => 'secondary',
                                    'action' => ['type' => 'message', 'label' => '🔍 เช็คสถานะ', 'text' => 'เช็คสถานะ'],
                                ],
                            ],
                        ],
                    ],
                ];

                $lineService->sendRichMessage($this->userId, $richContent);
            } else {
                // Facebook — ส่งข้อความธรรมดา
                $settings = FortuneTellingSetting::getSettings();
                $fbService = new \App\Services\FacebookWebhookService($settings);
                $fbService->sendMessage($this->userId, $message);
            }

            Log::info('ProcessDeepFortuneReadingJob: ส่ง failure notification สำเร็จ', [
                'reading_id' => $this->readingId,
                'platform' => $this->platform,
            ]);
        } catch (\Exception $e) {
            Log::warning('ProcessDeepFortuneReadingJob: push failure notification ล้มเหลว', [
                'reading_id' => $this->readingId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
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

    /**
     * 🆕 (2026-04-28) ส่ง follow-up DM หลังคำทำนายแล้ว
     *
     * แจ้งลูกค้าว่าคุยต่อเรื่องเดิมได้ฟรีใน 48 ชม. (post-reading discussion mode)
     * หน่วงเวลา 1.5 วินาทีเพื่อให้คำทำนายขึ้นก่อน + Messenger group ข้อความรวมกัน
     */
    protected function sendPostReadingFollowUp(FortuneChannelManager $channelManager, FortuneReading $reading, string $name): void
    {
        try {
            // หน่วงให้คำทำนายแสดงก่อน — กัน DM ซ้อน
            usleep(1_500_000);

            $hours = FortuneConversationService::POST_READING_DISCUSSION_HOURS;
            $maxTurns = FortuneConversationService::POST_READING_MAX_TURNS;

            $followUpMessage = "💬 *อ่านแล้วมีอะไรอยากถามเพิ่ม?*\n\n"
                ."เจ้าชะตา{$name} สามารถ **คุยต่อเรื่องนี้กับแม่หมอจันทราได้ฟรี** ภายใน {$hours} ชั่วโมง\n\n"
                ."🃏 ใช้ไพ่+ดวงดาวชุดเดิมที่เปิดไว้แล้ว\n"
                ."💭 ถามขยายความ หรือเรื่องที่สงสัยจากคำทำนาย ได้สูงสุด {$maxTurns} ครั้ง\n\n"
                ."_(ถ้าเป็นเรื่องใหม่/หมวดอื่น แม่หมอจะแจ้งให้เปิดไพ่ใหม่ค่ะ)_\n\n"
                .'พิมพ์คำถามมาได้เลย ✨';

            $channelManager->sendResponse($this->platform, $this->userId, [
                'action' => 'post_reading_invite',
                'message' => $followUpMessage,
                'reading' => $reading,
            ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);

            $reading->setConversationState('post_reading_invite_sent', true);
            $reading->setConversationState('post_reading_invite_at', now()->toIso8601String());

            Log::info('ProcessDeepFortuneReadingJob: ส่ง post-reading follow-up DM', [
                'reading_id' => $this->readingId,
                'platform' => $this->platform,
            ]);
        } catch (\Throwable $e) {
            Log::warning('ProcessDeepFortuneReadingJob: post-reading follow-up DM ล้ม (non-blocking)', [
                'reading_id' => $this->readingId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
