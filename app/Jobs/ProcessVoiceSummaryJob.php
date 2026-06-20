<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\FortuneVoiceSummaryService;
use App\Services\LineFortuneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 🎙️ (2026-05-08) ProcessVoiceSummaryJob
 *
 * สร้างเสียงสรุปคำทำนาย Celtic 99฿ ใน background + push audio ให้ลูกค้า
 *
 * ทำไมต้อง async:
 *   - AI summary call: 5-15s
 *   - MiniMax synth: 3-10s
 *   - Fallback chain เพิ่มอีก: 5-15s
 *   - รวม worst case: 30s+ → ลูกค้าจ่าย 99฿ ไม่ควรรอนาน
 *
 * Flow:
 *   1. endCelticSession ส่ง closing message + image (ทันที)
 *   2. dispatch Job นี้ใน background
 *   3. Job: AI summary → TTS synth → push audio + intro text
 *
 * Dispatch strategy: ใช้ pattern เดียวกับ ProcessDeepFortuneReadingJob
 *   - fastcgi_finish_request + Artisan::call (primary)
 *   - proc_open background (fallback)
 *   - queue worker (fallback)
 *   - sync (last resort)
 */
class ProcessVoiceSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;  // 3 นาที — TTS chain อาจช้า

    public array $backoff = [10, 30];

    public int $readingId;

    public string $platform;

    public string $userId;

    public function __construct(int $readingId, string $platform, string $userId)
    {
        $this->readingId = $readingId;
        $this->platform = $platform;
        $this->userId = $userId;
    }

    /**
     * Smart dispatch — ส่งงานไป background โดยไม่ block caller
     *
     * ลำดับ:
     *   1. fastcgi_finish_request + Artisan::call (sync ใน worker process)
     *   2. proc_open background process (ถ้าไม่มี fastcgi)
     *   3. Queue worker (ถ้ามี driver จริง)
     *   4. Artisan::call sync (last resort — เสี่ยง block แต่ดีกว่าไม่ทำ)
     */
    public static function dispatchSmart(int $readingId, string $platform, string $userId): void
    {
        Log::info('🎙️ ProcessVoiceSummaryJob: dispatch', [
            'reading_id' => $readingId,
            'platform' => $platform,
        ]);

        // 1. fastcgi finish + Artisan::call
        if (\function_exists('fastcgi_finish_request')) {
            \set_time_limit(180);
            \fastcgi_finish_request();

            try {
                Artisan::call('fortune:process-voice-summary', [
                    'readingId' => $readingId,
                    'platform' => $platform,
                    'userId' => $userId,
                ]);
            } catch (\Throwable $e) {
                Log::error('ProcessVoiceSummaryJob: Artisan::call ล้มเหลว (fastcgi)', [
                    'reading_id' => $readingId,
                    'error' => $e->getMessage(),
                ]);
            }

            return;
        }

        // 2. proc_open background
        if (\function_exists('proc_open')) {
            self::dispatchViaProcOpen($readingId, $platform, $userId);

            return;
        }

        // 3. Queue
        $driver = config('queue.default', 'sync');
        if ($driver !== 'sync') {
            $job = new self($readingId, $platform, $userId);
            $job->onQueue('fortune-voice');
            dispatch($job);

            return;
        }

        // 4. last resort — sync
        \set_time_limit(180);
        Artisan::call('fortune:process-voice-summary', [
            'readingId' => $readingId,
            'platform' => $platform,
            'userId' => $userId,
        ]);
    }

    /**
     * รัน artisan command ใน background ผ่าน proc_open
     */
    private static function dispatchViaProcOpen(int $readingId, string $platform, string $userId): void
    {
        $artisan = \base_path('artisan');
        $php = self::findPhpBinary();

        $cmd = \sprintf(
            '%s %s fortune:process-voice-summary %d %s %s',
            \escapeshellarg($php),
            \escapeshellarg($artisan),
            $readingId,
            \escapeshellarg($platform),
            \escapeshellarg($userId)
        );

        $logFile = \storage_path("logs/fortune-voice-{$readingId}.log");

        if (self::isWindows()) {
            $bgCmd = "start /B {$cmd} >> \"{$logFile}\" 2>&1";
            $descriptors = [
                0 => ['file', 'NUL', 'r'],
                1 => ['file', 'NUL', 'w'],
                2 => ['file', 'NUL', 'w'],
            ];
        } else {
            $bgCmd = "nohup {$cmd} >> \"{$logFile}\" 2>&1 &";
            $descriptors = [
                0 => ['file', '/dev/null', 'r'],
                1 => ['file', '/dev/null', 'w'],
                2 => ['file', '/dev/null', 'w'],
            ];
        }

        $process = \proc_open($bgCmd, $descriptors, $pipes);
        if (\is_resource($process)) {
            \proc_close($process);
        }
    }

    private static function findPhpBinary(): string
    {
        return defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
    }

    private static function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * รัน Job — สร้าง voice + push ให้ลูกค้า
     */
    public function handle(): void
    {
        \set_time_limit(180);

        $reading = FortuneReading::find($this->readingId);
        if (! $reading) {
            Log::error('ProcessVoiceSummaryJob: ไม่พบ FortuneReading', [
                'reading_id' => $this->readingId,
            ]);

            return;
        }

        // ถ้า voice generated แล้ว + push สำเร็จ → skip (idempotent)
        $alreadyPushed = (bool) $reading->getConversationState('voice_summary_pushed', false);
        if ($alreadyPushed && ! empty($reading->voice_audio_path)) {
            Log::info('ProcessVoiceSummaryJob: ทำเสร็จแล้ว — ข้าม', [
                'reading_id' => $this->readingId,
            ]);

            return;
        }

        $reading->setConversationState('voice_summary_status', 'processing');
        $reading->setConversationState('voice_summary_started_at', now()->toIso8601String());

        try {
            $settings = FortuneTellingSetting::getSettings();

            // 1. Generate voice
            //    🐛 (2026-06-20) Celtic ไม่เก็บคำทำนายใน deep_response (ว่างเสมอ) → generate()
            //    เดิม fallback deep_response → "source สั้นเกินไป" เสมอ. ส่ง source ที่ถูกต้องเข้าไป:
            //    voice_summary_source_text (state ตอนจบ) → celtic_grand_finale_summary → ai_response → deep_response
            $voiceService = new FortuneVoiceSummaryService($settings);
            $sourceText = (string) $reading->getConversationState('voice_summary_source_text', '');
            if (mb_strlen(trim($sourceText)) < 50) {
                $sourceText = (string) $reading->getConversationState('celtic_grand_finale_summary', '');
            }
            if (mb_strlen(trim($sourceText)) < 50) {
                $sourceText = (string) ($reading->ai_response ?? '');
            }
            if (mb_strlen(trim($sourceText)) < 50) {
                $sourceText = (string) ($reading->deep_response ?? '');
            }
            $result = $voiceService->generate($reading, $sourceText !== '' ? $sourceText : null);

            if (! $result['success']) {
                $reading->setConversationState('voice_summary_status', 'failed');
                $reading->setConversationState('voice_summary_error', $result['error']);
                Log::warning('ProcessVoiceSummaryJob: voice gen ล้มเหลว', [
                    'reading_id' => $this->readingId,
                    'error' => $result['error'],
                ]);

                return;
            }

            // 2. Push audio ตาม platform
            // 🎧 (2026-06-20) เสียง = "ผู้ช่วย AI" อ่านให้ ไม่ใช่เสียงแม่หมอ — สื่อให้ชัด
            $intro = $settings->voice_summary_intro_message
                ?: '🎧 ผู้ช่วย AI อ่านบทสรุปคำทำนายให้ฟังค่ะ (เป็นเสียงระบบผู้ช่วย AI ไม่ใช่เสียงแม่หมอ) ลองฟังดูนะคะ ✨';

            $pushed = $this->platform === 'line'
                ? $this->pushToLine($reading, $intro, $result, $settings)
                : $this->pushToFacebook($reading, $intro, $result, $settings);

            if ($pushed) {
                $reading->setConversationState('voice_summary_pushed', true);
                $reading->setConversationState('voice_summary_status', 'sent');
                $reading->setConversationState('voice_summary_sent_at', now()->toIso8601String());
                Log::info('🎙️ ProcessVoiceSummaryJob: ✅ push voice สำเร็จ', [
                    'reading_id' => $this->readingId,
                    'platform' => $this->platform,
                    'provider' => $result['provider_used'],
                ]);
            } else {
                $reading->setConversationState('voice_summary_status', 'push_failed');
                Log::warning('ProcessVoiceSummaryJob: push voice ล้มเหลว (ไฟล์ generate แล้ว)', [
                    'reading_id' => $this->readingId,
                ]);
            }
        } catch (\Throwable $e) {
            $reading->setConversationState('voice_summary_status', 'exception');
            $reading->setConversationState('voice_summary_error', $e->getMessage());
            Log::error('ProcessVoiceSummaryJob: exception', [
                'reading_id' => $this->readingId,
                'error' => $e->getMessage(),
            ]);
            throw $e;  // ให้ queue retry
        }
    }

    protected function pushToLine(FortuneReading $reading, string $intro, array $result, FortuneTellingSetting $settings): bool
    {
        try {
            $lineService = new LineFortuneService($settings);
            $duration = (int) ($result['audio_duration_ms'] ?? 60000);

            $sent = $lineService->sendMessage($this->userId, $intro);
            if (! empty($result['audio_url'])) {
                $sent = $lineService->sendAudio($this->userId, $result['audio_url'], $duration) || $sent;
            }

            return $sent;
        } catch (\Throwable $e) {
            Log::warning('ProcessVoiceSummaryJob: LINE push exception', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function pushToFacebook(FortuneReading $reading, string $intro, array $result, FortuneTellingSetting $settings): bool
    {
        try {
            $fbService = new FacebookWebhookService($settings);

            // intro + audio: sendMessage/sendAudio ลอง RESPONSE ก่อน (อยู่ใน 24 ชม.) → ผ่านชัวร์
            //   ⚠️ ห้าม force MESSAGE_TAG=POST_PURCHASE_UPDATE (FB เลิกแท็กแล้ว subcode 1893061 → audio ตก)
            $extra = ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE'];
            $introSent = $fbService->sendMessage($this->userId, $intro, $extra);

            // 🎧 (2026-06-20) success ต้องวัดที่ "audio ถึงลูกค้าจริง" ไม่ใช่แค่ intro
            //   เดิม `sendAudio() || $sent` → intro ผ่านก็ return true ทั้งที่เสียงตก → log "✅ สำเร็จ" หลอก
            $audioSent = true;
            if (! empty($result['audio_url'])) {
                $audioSent = $fbService->sendAudio($this->userId, $result['audio_url'], $extra);
                if (! $audioSent) {
                    Log::warning('🎙️ ProcessVoiceSummaryJob: FB ส่งไฟล์เสียงไม่สำเร็จ (intro ส่งแล้ว)', [
                        'reading_id' => $reading->id,
                        'user_id' => $this->userId,
                    ]);
                }

                return $audioSent; // มีเสียง → วัดความสำเร็จที่เสียงถึงลูกค้า
            }

            return $introSent;
        } catch (\Throwable $e) {
            Log::warning('ProcessVoiceSummaryJob: FB push exception', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('🚨 ProcessVoiceSummaryJob: ล้มเหลวถาวร', [
            'reading_id' => $this->readingId,
            'error' => $exception->getMessage(),
        ]);

        try {
            $reading = FortuneReading::find($this->readingId);
            if ($reading) {
                $reading->setConversationState('voice_summary_status', 'failed_permanently');
                $reading->setConversationState('voice_summary_error', $exception->getMessage());
            }
        } catch (\Throwable $e) {
            // best-effort
        }
    }

    public function displayName(): string
    {
        return "ProcessVoiceSummary[#{$this->readingId}:{$this->platform}]";
    }

    public function tags(): array
    {
        return [
            'fortune-voice',
            "reading:{$this->readingId}",
            "platform:{$this->platform}",
        ];
    }
}
