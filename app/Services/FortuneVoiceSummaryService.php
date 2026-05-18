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
 *   3. Provider เขียน mp3 ลง temp file ที่ storage/app/tmp-voice/
 *   4. FortuneVoiceStorageService อัปโหลด temp → cloud/local (admin เลือก driver)
 *      → ลด disk usage บนเซิร์ฟเวอร์ (ย้ายไป R2/S3/GCS/Firebase)
 *   5. คืน public URL ให้ caller ใช้ส่งผ่าน LINE/FB
 *
 * Concurrency:
 *   - Cache lock 60s ต่อ reading_id — กันสร้างซ้ำซ้อน
 *
 * Cache:
 *   - mp3 file persist บน storage driver → caller สามารถ replay ส่งใหม่ได้ฟรี
 *   - DB column voice_audio_token + voice_audio_disk = single source of truth
 *
 * 🌥️ (2026-05-18) Multi-cloud storage:
 *   - admin เลือก driver ผ่าน /admin/fortune/settings → Voice → Storage
 *   - รองรับ: local / r2 / s3 / gcs / firebase
 *   - เก่า: voice_audio_disk=null → assume local (backward compat)
 */
class FortuneVoiceSummaryService
{
    protected FortuneVoiceStorageService $storage;

    public function __construct(
        protected FortuneTellingSetting $settings,
        protected ?FortuneAIService $ai = null,
        protected ?AiApiKeyPoolService $pool = null,
        ?FortuneVoiceStorageService $storage = null,
    ) {
        $this->ai ??= new FortuneAIService($settings);
        $this->pool ??= app(AiApiKeyPoolService::class);
        $this->storage = $storage ?? new FortuneVoiceStorageService($settings);
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
            // 2. ถ้ามี audio cached แล้ว (token + path มีใน DB) → return URL เดิม
            if (! empty($reading->voice_audio_token) && ! empty($reading->voice_audio_path)) {
                $existingDisk = $reading->voice_audio_disk ?: 'local';

                // 🌥️ ใช้ voice_audio_url ก่อน (Firebase ต้องมี token ใน URL)
                $cachedUrl = $reading->voice_audio_url ?: $this->storage->audioUrl($reading->voice_audio_path, $existingDisk);

                if (! empty($cachedUrl)) {
                    return [
                        'success' => true,
                        'audio_url' => $cachedUrl,
                        'audio_path' => $reading->voice_audio_path,
                        'audio_duration_ms' => $reading->voice_audio_duration_ms ?? 60000,
                        'provider_used' => ($reading->voice_audio_provider ?: 'unknown').'+cache',
                        'error' => null,
                    ];
                }
                // ถ้าหา URL ไม่ได้ → regenerate
                Log::info('FortuneVoiceSummary: cached URL หาย — regenerate', [
                    'reading_id' => $reading->id,
                    'old_disk' => $existingDisk,
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

            // 5. สร้าง temp file path + final relative path
            //    token = 40 chars (URL-safe random) → ~240 bits entropy
            $token = $reading->voice_audio_token ?: Str::random(40);
            $relativePath = 'fortune-voice/'.$token.'.mp3';

            // Temp path สำหรับ provider เขียน — ใน storage/app/ (ไม่ต้องผ่าน public symlink)
            $tempDir = storage_path('app/tmp-voice');
            if (! is_dir($tempDir)) {
                @mkdir($tempDir, 0775, true);
            }
            $tempPath = $tempDir.'/'.$token.'.mp3';

            // 6. ลอง provider chain
            $chain = $this->settings->getVoiceProviderChain();
            $lastError = null;
            $providerUsed = null;
            $audioLengthMs = null;
            $usageChars = mb_strlen($summary);
            $providerAttempts = [];

            foreach ($chain as $providerName) {
                $provider = $this->resolveProvider($providerName);
                if (! $provider) {
                    Log::warning('FortuneVoiceSummary: unknown provider', ['provider' => $providerName]);
                    $providerAttempts[$providerName] = 'unknown_provider';

                    continue;
                }

                if (! $provider->isAvailable()) {
                    Log::info('FortuneVoiceSummary: provider ไม่พร้อม — ข้าม', [
                        'provider' => $providerName,
                        'reading_id' => $reading->id,
                    ]);
                    $providerAttempts[$providerName] = 'not_available';

                    continue;
                }

                $result = $provider->synthesize($summary, [
                    'output_path' => $tempPath,
                    'language' => 'th',
                ]);

                if ($result['success'] && file_exists($tempPath) && filesize($tempPath) > 1000) {
                    $providerUsed = $providerName;
                    $audioLengthMs = $result['audio_length_ms'] ?? $this->estimateDurationMs($tempPath, $summary);
                    $usageChars = $result['usage_chars'] ?? mb_strlen($summary);
                    $providerAttempts[$providerName] = 'success';

                    Log::info('🎙️ FortuneVoiceSummary: ✅ TTS สำเร็จ', [
                        'reading_id' => $reading->id,
                        'provider' => $providerName,
                        'voice' => $result['voice_used'] ?? null,
                        'audio_length_ms' => $audioLengthMs,
                        'file_size_kb' => round(filesize($tempPath) / 1024, 1),
                    ]);
                    break;
                }

                $lastError = $result['error'] ?? 'unknown';
                $providerAttempts[$providerName] = $lastError;
                Log::warning('FortuneVoiceSummary: provider fail — fallback ถัดไป', [
                    'reading_id' => $reading->id,
                    'provider' => $providerName,
                    'error' => $lastError,
                ]);

                // ลบ temp ที่ provider อาจสร้างไว้แต่ไม่สมบูรณ์
                if (file_exists($tempPath) && filesize($tempPath) < 1000) {
                    @unlink($tempPath);
                }
            }

            if (! $providerUsed) {
                @unlink($tempPath);
                $errSummary = 'TTS providers ทั้งหมดล้มเหลว — '.json_encode($providerAttempts, JSON_UNESCAPED_UNICODE);

                return $this->failResult($errSummary);
            }

            // 7. อัปโหลด temp → storage driver (local / R2 / S3 / GCS / Firebase)
            $putResult = $this->storage->putAudio($tempPath, $relativePath);

            if (! $putResult['success']) {
                @unlink($tempPath);

                return $this->failResult(
                    'TTS สำเร็จ ('.$providerUsed.') แต่ upload ไป '.$putResult['disk'].' ล้มเหลว: '.$putResult['error']
                );
            }

            // local driver: temp = ปลายทาง อยู่แล้ว ไม่ต้องลบ
            // cloud driver: storage service ลบ temp ให้แล้ว

            // 8. บันทึก metadata ลง DB
            $updateData = [
                'voice_audio_token' => $token,
                'voice_audio_path' => $relativePath,
                'voice_audio_duration_ms' => $audioLengthMs,
                'voice_audio_provider' => $providerUsed,
                'voice_audio_chars' => $usageChars,
                'voice_audio_generated_at' => now(),
            ];

            // 🌥️ voice_audio_disk + voice_audio_url อาจยังไม่ migrate → ใส่ใน try
            try {
                if (\Illuminate\Support\Facades\Schema::hasColumn('fortune_readings', 'voice_audio_disk')) {
                    $updateData['voice_audio_disk'] = $putResult['disk'];
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn('fortune_readings', 'voice_audio_url')) {
                    $updateData['voice_audio_url'] = $putResult['url'];
                }
            } catch (\Throwable $e) {
                // ข้าม — backward compat
            }

            $reading->update($updateData);

            return [
                'success' => true,
                'audio_url' => $putResult['url'],
                'audio_path' => $relativePath,
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
     * ลบ audio cache ของ reading (admin regenerate)
     */
    public function clearCache(FortuneReading $reading): bool
    {
        if ($reading->voice_audio_path) {
            $disk = $reading->voice_audio_disk ?: 'local';
            $this->storage->deleteAudio($reading->voice_audio_path, $disk);
        }

        $clearData = [
            'voice_audio_token' => null,
            'voice_audio_path' => null,
            'voice_audio_duration_ms' => null,
            'voice_audio_provider' => null,
            'voice_audio_chars' => null,
            'voice_audio_generated_at' => null,
        ];
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('fortune_readings', 'voice_audio_disk')) {
                $clearData['voice_audio_disk'] = null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('fortune_readings', 'voice_audio_url')) {
                $clearData['voice_audio_url'] = null;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $reading->update($clearData);

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
