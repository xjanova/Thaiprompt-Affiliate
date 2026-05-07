<?php

namespace App\Services;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Tts\GoogleCloudTtsProvider;
use App\Services\Tts\GttsProvider;
use App\Services\Tts\MiniMaxTtsProvider;
use App\Services\Tts\OpenAITtsProvider;
use App\Services\Tts\TtsProviderInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 🎙️ (2026-05-08) Fortune Voice Summary Service
 *
 * จัดการสร้างเสียงสรุปคำทำนาย Celtic 99฿ ให้ลูกค้าฟัง
 *
 * Flow:
 *   1. AI สรุปคำทำนายเป็น speech-friendly text (~1-2 นาทีเมื่ออ่าน)
 *   2. เลือก TTS provider ตาม chain (admin ตั้ง):
 *        primary  = MiniMax (paid, premium)
 *        fallback = Google Cloud TTS / gTTS (free)
 *        last     = OpenAI TTS (paid, alternative)
 *   3. สังเคราะห์ mp3 → save to storage/app/public/fortune-voice/{token}.mp3
 *      (token = random 32 chars — กัน enumeration ผ่าน reading_id)
 *   4. คืน public URL ให้ caller ใช้ส่งผ่าน LINE/FB
 *
 * Concurrency:
 *   - Cache lock 60s ต่อ reading_id — กันสร้างซ้ำซ้อน
 *
 * Cache:
 *   - mp3 file persist บน disk → caller สามารถ replay ส่งใหม่ได้ฟรี
 *   - DB column voice_audio_token = single source of truth
 */
class FortuneVoiceSummaryService
{
    public function __construct(
        protected FortuneTellingSetting $settings,
        protected ?FortuneAIService $ai = null,
        protected ?AiApiKeyPoolService $pool = null,
    ) {
        $this->ai ??= new FortuneAIService($settings);
        $this->pool ??= app(AiApiKeyPoolService::class);
    }

    /**
     * สร้างเสียงสรุปสำหรับ reading
     *
     * @param  string|null  $sourceText  ถ้าไม่ส่ง — ใช้ deep_response ของ reading
     * @return array{
     *   success: bool,
     *   audio_url: ?string,
     *   audio_path: ?string,
     *   audio_duration_ms: ?int,
     *   provider_used: ?string,
     *   error: ?string,
     * }
     */
    public function generate(FortuneReading $reading, ?string $sourceText = null): array
    {
        // 0. Tier scope guard
        if (! $this->settings->shouldGenerateVoiceSummary($reading)) {
            return $this->failResult('voice_summary ไม่เปิด สำหรับ reading นี้ (tier scope mismatch)');
        }

        // 1. Cache lock — กันสร้างซ้อน (admin retry / concurrent dispatch)
        $lock = Cache::lock("voice_synth_{$reading->id}", 60);
        if (! $lock->get()) {
            Log::info('FortuneVoiceSummary: lock busy — กำลังถูก generate อยู่', [
                'reading_id' => $reading->id,
            ]);

            return $this->failResult('voice กำลังถูก generate อยู่ ลองใหม่อีก 1 นาที');
        }

        try {
            // 2. ถ้ามี audio cached แล้ว (token + path มีใน DB) → return เดิม
            if (! empty($reading->voice_audio_token) && ! empty($reading->voice_audio_path)) {
                $absPath = Storage::disk('public')->path($reading->voice_audio_path);
                if (file_exists($absPath) && filesize($absPath) > 1000) {
                    return [
                        'success' => true,
                        'audio_url' => Storage::disk('public')->url($reading->voice_audio_path),
                        'audio_path' => $absPath,
                        'audio_duration_ms' => $reading->voice_audio_duration_ms ?? 60000,
                        'provider_used' => $reading->voice_audio_provider.'+cache',
                        'error' => null,
                    ];
                }
                // ถ้าไฟล์หาย → regenerate (เคลียร์ DB ก่อน)
                Log::info('FortuneVoiceSummary: cache file missing — regenerate', [
                    'reading_id' => $reading->id,
                    'old_path' => $reading->voice_audio_path,
                ]);
            }

            // 3. ดึง source text
            $source = $sourceText ?: ($reading->deep_response ?? '');
            if (mb_strlen(trim($source)) < 50) {
                return $this->failResult('source text สั้นเกินไป (< 50 chars)');
            }

            // 4. AI summarize → speech-friendly text
            $maxChars = (int) ($this->settings->voice_summary_max_chars ?? 2000);
            $summary = $this->ai->generateVoiceSummary($reading, $source, $maxChars);

            if (empty($summary) || mb_strlen(trim($summary)) < 30) {
                Log::info('FortuneVoiceSummary: AI summary empty, fallback to truncated source', [
                    'reading_id' => $reading->id,
                ]);
                $summary = $this->stripFormattingForSpeech(mb_substr($source, 0, $maxChars));
            }

            // 5. Generate random token + relative path
            //    token = 40 chars (URL-safe random) → ~240 bits entropy
            $token = $reading->voice_audio_token ?: Str::random(40);
            $relativePath = 'fortune-voice/'.$token.'.mp3';
            $absolutePath = Storage::disk('public')->path($relativePath);

            // ตรวจ storage:link — log ครั้งเดียวพอ
            $this->ensureStorageLinkOrWarn();

            // 6. ลอง provider chain
            $chain = $this->settings->getVoiceProviderChain();
            $lastError = null;
            $providerUsed = null;
            $audioLengthMs = null;
            $usageChars = mb_strlen($summary);

            foreach ($chain as $providerName) {
                $provider = $this->resolveProvider($providerName);
                if (! $provider) {
                    Log::warning('FortuneVoiceSummary: unknown provider', ['provider' => $providerName]);

                    continue;
                }

                if (! $provider->isAvailable()) {
                    Log::info('FortuneVoiceSummary: provider ไม่พร้อม — ข้าม', [
                        'provider' => $providerName,
                        'reading_id' => $reading->id,
                    ]);

                    continue;
                }

                $result = $provider->synthesize($summary, [
                    'output_path' => $absolutePath,
                    'language' => 'th',
                ]);

                if ($result['success'] && file_exists($absolutePath) && filesize($absolutePath) > 1000) {
                    $providerUsed = $providerName;
                    $audioLengthMs = $result['audio_length_ms'] ?? $this->estimateDurationMs($absolutePath, $summary);
                    $usageChars = $result['usage_chars'] ?? mb_strlen($summary);

                    Log::info('🎙️ FortuneVoiceSummary: ✅ สำเร็จ', [
                        'reading_id' => $reading->id,
                        'provider' => $providerName,
                        'voice' => $result['voice_used'] ?? null,
                        'duration_ms' => $result['duration_ms'] ?? null,
                        'audio_length_ms' => $audioLengthMs,
                        'file_size_kb' => round(filesize($absolutePath) / 1024, 1),
                    ]);
                    break;
                }

                $lastError = $result['error'] ?? 'unknown';
                Log::warning('FortuneVoiceSummary: provider fail — fallback ถัดไป', [
                    'reading_id' => $reading->id,
                    'provider' => $providerName,
                    'error' => $lastError,
                ]);
            }

            if (! $providerUsed) {
                return $this->failResult('ทุก TTS provider ล้มเหลว — last: '.($lastError ?? 'unknown'));
            }

            // 7. บันทึก metadata ลง DB
            $reading->update([
                'voice_audio_token' => $token,
                'voice_audio_path' => $relativePath,
                'voice_audio_duration_ms' => $audioLengthMs,
                'voice_audio_provider' => $providerUsed,
                'voice_audio_chars' => $usageChars,
                'voice_audio_generated_at' => now(),
            ]);

            return [
                'success' => true,
                'audio_url' => Storage::disk('public')->url($relativePath),
                'audio_path' => $absolutePath,
                'audio_duration_ms' => $audioLengthMs,
                'provider_used' => $providerUsed,
                'error' => null,
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * Resolve provider instance ตามชื่อ
     */
    protected function resolveProvider(string $name): ?TtsProviderInterface
    {
        return match ($name) {
            'minimax' => new MiniMaxTtsProvider($this->settings),
            'openai_tts' => new OpenAITtsProvider($this->settings, $this->pool),
            'google_tts' => new GoogleCloudTtsProvider($this->settings),
            'gtts' => new GttsProvider,
            default => null,
        };
    }

    /**
     * ลอกฟอร์แมต Markdown/emoji ออกจาก text เพื่อให้อ่านลื่น
     */
    protected function stripFormattingForSpeech(string $text): string
    {
        $clean = preg_replace('/\*\*(.+?)\*\*/u', '$1', $text);
        $clean = preg_replace('/\*(.+?)\*/u', '$1', $clean);
        $clean = preg_replace('/__(.+?)__/u', '$1', $clean);
        $clean = preg_replace('/`(.+?)`/u', '$1', $clean);
        $clean = preg_replace('/[─━═┃│┄┅┆┇┈┉┊┋]+/u', '', $clean);
        $clean = preg_replace('/^\s*[-*•]+\s*/mu', '', $clean);
        $clean = preg_replace('/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{2300}-\x{23FF}]/u', '', $clean);
        $clean = preg_replace("/\n{2,}/", '. ', $clean);
        $clean = preg_replace("/\n/", ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);

        return trim($clean);
    }

    /**
     * ประมาณ duration ms จาก file size (fallback ถ้า provider ไม่บอก)
     * MP3 128kbps mono → bytes_per_second ≈ 16000 → seconds = filesize / 16000
     */
    protected function estimateDurationMs(string $path, string $text): int
    {
        if (file_exists($path)) {
            $size = filesize($path);
            if ($size > 0) {
                // 128 kbps = 16000 bytes/sec → ms = size * 1000 / 16000 = size / 16
                return max(1000, (int) round($size / 16));
            }
        }

        // last-resort: estimate จาก text length (ภาษาไทย ~4 chars/sec ในเสียงพูด)
        return max(5000, (int) round(mb_strlen($text) / 4 * 1000));
    }

    /**
     * Warn admin ถ้า public/storage symlink ไม่มี (URL จะ 404)
     */
    protected function ensureStorageLinkOrWarn(): void
    {
        $symlinkPath = public_path('storage');
        if (file_exists($symlinkPath) || is_link($symlinkPath)) {
            return;
        }

        // Cache log warning เพื่อไม่ spam — log วันละครั้งพอ
        $cacheKey = 'voice_summary_storage_link_warning_'.now()->format('Y-m-d');
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, true, 86400);

        Log::warning('🚨 FortuneVoiceSummary: public/storage symlink ไม่มี — voice URL จะ 404! รัน: php artisan storage:link');
    }

    /**
     * ลบ audio cache ของ reading (admin regenerate)
     */
    public function clearCache(FortuneReading $reading): bool
    {
        if ($reading->voice_audio_path) {
            Storage::disk('public')->delete($reading->voice_audio_path);
        }
        $reading->update([
            'voice_audio_token' => null,
            'voice_audio_path' => null,
            'voice_audio_duration_ms' => null,
            'voice_audio_provider' => null,
            'voice_audio_chars' => null,
            'voice_audio_generated_at' => null,
        ]);

        return true;
    }

    /**
     * Build standard fail-result array
     */
    protected function failResult(string $error): array
    {
        return [
            'success' => false,
            'audio_url' => null,
            'audio_path' => null,
            'audio_duration_ms' => null,
            'provider_used' => null,
            'error' => $error,
        ];
    }
}
