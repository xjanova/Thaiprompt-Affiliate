<?php

namespace App\Services;

use App\Models\FortuneSystemVoiceClip;
use App\Models\FortuneTellingSetting;
use App\Services\Tts\GoogleCloudTtsProvider;
use App\Services\Tts\GttsProvider;
use App\Services\Tts\MiniMaxTtsProvider;
use App\Services\Tts\OpenAITtsProvider;
use App\Services\Tts\TtsProviderInterface;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 🎧 (2026-06-21) Fortune System Voice Service
 *
 * จัดการ "เสียงระบบ" — ข้อความกลางตายตัว (กล่องกระตุ้น/กติกา/วิธีกรอกวันเกิด/เตือนจ่าย ฯลฯ)
 * ที่สร้างไฟล์เสียงเก็บไว้ล่วงหน้า แล้ว reuse ส่งให้ลูกค้าทุกคน — ไม่ต้องสร้าง TTS ใหม่ทุกครั้ง
 *
 * ต่างจาก FortuneVoiceSummaryService (เสียงทำนายเฉพาะคน สร้างสด เก็บ cloud ได้):
 *   - เสียงระบบ = generic เก็บที่ public disk (local server) เท่านั้น — ไม่ส่ง R2/cloud
 *   - 1 key = 1 ไฟล์ reuse ตลอด จนกว่า admin แก้ข้อความ/กดสร้างใหม่
 */
class FortuneSystemVoiceService
{
    /**
     * โฟลเดอร์เก็บเสียงระบบบน public disk (local server)
     */
    protected const STORAGE_DIR = 'fortune-voice/system';

    /**
     * cache การหา ffmpeg (ต่อ instance)
     */
    protected bool $ffmpegChecked = false;

    protected ?string $ffmpegResolved = null;

    public function __construct(
        protected FortuneTellingSetting $settings,
        protected ?AiApiKeyPoolService $pool = null,
    ) {
        $this->pool ??= app(AiApiKeyPoolService::class);
    }

    /**
     * คืน public URL ของเสียงระบบ key นี้ — ถ้าพร้อมส่งจริงเท่านั้น
     *
     * เงื่อนไข: master toggle เปิด + คลิปเปิด + มีไฟล์เสียง + ไฟล์ยังอยู่จริง
     * ไม่เข้าเงื่อนไข = คืน null (จุดส่งจะข้ามเสียง ส่งแต่ข้อความเดิม)
     */
    public function urlFor(string $key): ?string
    {
        // master toggle ปิด → ไม่ส่งเสียงระบบเลย
        if (! (bool) ($this->settings->system_voice_enabled ?? false)) {
            return null;
        }

        try {
            $clip = FortuneSystemVoiceClip::where('clip_key', $key)->first();
            if (! $clip || ! $clip->isDeliverable()) {
                return null;
            }

            // เช็คไฟล์ยังอยู่จริงบน public disk (กันไฟล์ถูกลบ/ย้าย)
            if (! Storage::disk('public')->exists($clip->audio_path)) {
                return null;
            }

            return $clip->audio_url;
        } catch (\Throwable $e) {
            // fail-open ฝั่งเสียง: เงียบ ไม่ทำให้ flow ส่งข้อความพัง
            return null;
        }
    }

    /**
     * สร้าง (หรือสร้างใหม่) ไฟล์เสียงสำหรับคลิป — เก็บ metadata ลง DB
     *
     * @return array{success: bool, audio_url: ?string, provider_used: ?string, duration_ms: ?int, error: ?string}
     */
    public function generateClip(FortuneSystemVoiceClip $clip): array
    {
        $text = trim((string) $clip->script_text);
        if (mb_strlen($text) < 5) {
            return $this->fail('ข้อความสั้นเกินไป (< 5 ตัวอักษร)');
        }

        // กันสร้างซ้อนต่อ key
        $lock = Cache::lock('system_voice_gen_'.$clip->clip_key, 60);
        if (! $lock->get()) {
            return $this->fail('กำลังสร้างเสียงคลิปนี้อยู่ ลองใหม่อีกครั้ง');
        }

        try {
            // path ปลายทาง (stable ตาม key — สร้างใหม่ทับไฟล์เดิมได้)
            $relativePath = self::STORAGE_DIR.'/'.$clip->clip_key.'.mp3';

            // ถ้าเดิมเป็นไฟล์อัปโหลด (นามสกุลอื่น) → ลบไฟล์เก่าก่อน กันไฟล์ค้าง
            $this->forgetOldFileIfDifferent($clip, $relativePath);

            $synth = $this->synthesizeToPublic($text, $clip->clip_key, $clip->voice_config ?? null, $relativePath);
            if (! $synth['success']) {
                return $synth;
            }

            $clip->update([
                'audio_path' => $relativePath,
                'audio_url' => $synth['audio_url'],
                'audio_duration_ms' => $synth['duration_ms'],
                'audio_provider' => $synth['provider_used'],
                'audio_source' => 'tts',
                'audio_original_name' => null,
                'audio_voice_id' => $synth['voice_used'],
                'audio_chars' => mb_strlen($text),
                'generated_at' => now(),
            ]);

            return $synth;
        } finally {
            $lock->release();
        }
    }

    /**
     * เก็บไฟล์เสียงที่แอดมิน "อัปโหลดเอง" → ใช้แทน TTS (รองรับทุกฟอร์แมต)
     *
     * 🎯 ถ้ามี ffmpeg → แปลงทุกฟอร์แมตเป็น mp3 เสมอ (ส่ง FB ได้ชัวร์ทุกครั้ง)
     *    ถ้าไม่มี ffmpeg → fallback เก็บตามฟอร์แมตเดิม (FB อาจไม่รองรับฟอร์แมตอื่น)
     *
     * @return array{success: bool, audio_url: ?string, provider_used: ?string, duration_ms: ?int, voice_used: ?string, error: ?string}
     */
    public function storeUploadedAudio(FortuneSystemVoiceClip $clip, UploadedFile $file): array
    {
        // นามสกุลจริง (sanitize) — fallback mp3
        $origExt = strtolower($file->getClientOriginalExtension() ?: ($file->extension() ?: 'mp3'));
        $origExt = preg_replace('/[^a-z0-9]/', '', $origExt) ?: 'mp3';
        $origName = mb_substr($file->getClientOriginalName(), 0, 200);

        $tmpMp3 = null;

        try {
            $ffmpeg = $this->ffmpegPath();
            $converted = false;

            if ($origExt === 'mp3') {
                // mp3 อยู่แล้ว — stream เก็บตรง (ไม่โหลดเข้า memory)
                $relativePath = self::STORAGE_DIR.'/'.$clip->clip_key.'.mp3';
                $this->forgetOldFileIfDifferent($clip, $relativePath);
                $file->storeAs(self::STORAGE_DIR, $clip->clip_key.'.mp3', 'public');
            } elseif ($ffmpeg) {
                // 🎯 แปลงทุกฟอร์แมต → mp3 (FB-safe)
                $tempDir = storage_path('app/tmp-voice');
                if (! is_dir($tempDir)) {
                    @mkdir($tempDir, 0775, true);
                }
                $tmpMp3 = $tempDir.'/sys-up-'.Str::random(10).'.mp3';

                if (! $this->transcodeToMp3($ffmpeg, (string) $file->getRealPath(), $tmpMp3)) {
                    return $this->fail('แปลงไฟล์ .'.$origExt.' เป็น mp3 ไม่สำเร็จ (ffmpeg error)');
                }

                $relativePath = self::STORAGE_DIR.'/'.$clip->clip_key.'.mp3';
                $this->forgetOldFileIfDifferent($clip, $relativePath);
                // stream temp → public (ไม่โหลดทั้งไฟล์เข้า memory)
                Storage::disk('public')->putFileAs(self::STORAGE_DIR, new File($tmpMp3), $clip->clip_key.'.mp3');
                $converted = true;
            } else {
                // ไม่มี ffmpeg → stream เก็บตามฟอร์แมตเดิม (FB อาจไม่รองรับ)
                $relativePath = self::STORAGE_DIR.'/'.$clip->clip_key.'.'.$origExt;
                $this->forgetOldFileIfDifferent($clip, $relativePath);
                $file->storeAs(self::STORAGE_DIR, $clip->clip_key.'.'.$origExt, 'public');
            }

            // ต้องมีไฟล์จริง + ไม่ใช่ไฟล์ว่าง
            if (! Storage::disk('public')->exists($relativePath) || Storage::disk('public')->size($relativePath) < 100) {
                return $this->fail('บันทึกไฟล์อัปโหลดไม่สำเร็จ (ไฟล์ว่าง)');
            }

            $clip->update([
                'audio_path' => $relativePath,
                'audio_url' => Storage::disk('public')->url($relativePath),
                'audio_duration_ms' => $this->estimateDurationMs($relativePath, (string) $clip->script_text),
                'audio_provider' => $converted ? 'upload+mp3' : 'upload',
                'audio_source' => 'upload',
                'audio_original_name' => $origName,
                'audio_voice_id' => null,
                'audio_chars' => null,
                'generated_at' => now(),
            ]);

            Log::info('🎧 SystemVoice: ⬆️ อัปโหลดไฟล์เสียงสำเร็จ', [
                'clip_key' => $clip->clip_key,
                'orig_ext' => $origExt,
                'converted_to_mp3' => $converted,
                'path' => $relativePath,
            ]);

            return [
                'success' => true,
                'audio_url' => $clip->audio_url,
                'provider_used' => $converted ? 'upload+mp3' : 'upload',
                'duration_ms' => $clip->audio_duration_ms,
                'voice_used' => null,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return $this->fail('อัปโหลดไม่สำเร็จ: '.$e->getMessage());
        } finally {
            // เก็บกวาด temp mp3 เสมอ (กัน orphan ถ้า throw ระหว่างอ่าน)
            if ($tmpMp3 && file_exists($tmpMp3)) {
                @unlink($tmpMp3);
            }
        }
    }

    /**
     * หา path ของ ffmpeg (cache ผลลัพธ์) — null ถ้าไม่มี
     */
    protected function ffmpegPath(): ?string
    {
        if ($this->ffmpegChecked) {
            return $this->ffmpegResolved;
        }
        $this->ffmpegChecked = true;

        $candidates = [
            (string) config('app.ffmpeg_path'),
            '/home/admin/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/usr/bin/ffmpeg',
        ];
        foreach ($candidates as $c) {
            if (! empty($c) && @is_executable($c)) {
                return $this->ffmpegResolved = $c;
            }
        }

        // หาใน PATH
        try {
            $which = trim((string) @shell_exec('command -v ffmpeg 2>/dev/null'));
            if (! empty($which) && @is_executable($which)) {
                return $this->ffmpegResolved = $which;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return $this->ffmpegResolved = null;
    }

    /**
     * แปลงไฟล์เสียงใดๆ → mp3 (44.1kHz stereo 128kbps) ด้วย ffmpeg
     */
    protected function transcodeToMp3(string $ffmpeg, string $src, string $dest): bool
    {
        try {
            $cmd = escapeshellarg($ffmpeg).' -y -hide_banner -loglevel error'
                .' -i '.escapeshellarg($src)
                .' -vn -ar 44100 -ac 2 -b:a 128k '.escapeshellarg($dest).' 2>&1';
            @exec($cmd, $out, $code);

            return $code === 0 && file_exists($dest) && filesize($dest) > 1000;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * ลบไฟล์เสียงเดิมถ้า path ใหม่ต่างจากเดิม (เช่น เปลี่ยนนามสกุล tts.mp3 → upload.wav)
     */
    protected function forgetOldFileIfDifferent(FortuneSystemVoiceClip $clip, string $newPath): void
    {
        try {
            $old = $clip->audio_path;
            if (! empty($old) && $old !== $newPath && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * พรีวิวเสียงจากข้อความใดๆ (admin กด "ฟัง" ก่อนบันทึก) — เก็บไฟล์ชั่วคราว
     *
     * @param  array|null  $voiceConfig  override provider/voice เฉพาะครั้งนี้
     * @return array{success: bool, audio_url: ?string, provider_used: ?string, duration_ms: ?int, error: ?string}
     */
    public function previewText(string $text, ?array $voiceConfig = null): array
    {
        $text = trim($text);
        if (mb_strlen($text) < 5) {
            return $this->fail('ข้อความสั้นเกินไป (< 5 ตัวอักษร)');
        }

        // ตัดความยาวพรีวิวกัน abuse (เสียงระบบสั้นอยู่แล้ว)
        $text = mb_substr($text, 0, 1500);
        $token = Str::random(12);
        $relativePath = self::STORAGE_DIR.'/preview/'.$token.'.mp3';

        return $this->synthesizeToPublic($text, 'preview-'.$token, $voiceConfig, $relativePath);
    }

    /**
     * สังเคราะห์ข้อความ → เก็บที่ public disk (local) → คืน URL
     *
     * @param  string  $label  สำหรับ log/temp filename
     * @param  array|null  $voiceConfig  ['provider'=>?, 'voice'=>?]
     * @param  string  $relativePath  path ปลายทางบน public disk
     * @return array{success: bool, audio_url: ?string, provider_used: ?string, duration_ms: ?int, voice_used: ?string, error: ?string}
     */
    protected function synthesizeToPublic(string $text, string $label, ?array $voiceConfig, string $relativePath): array
    {
        // temp path ให้ provider เขียน
        $tempDir = storage_path('app/tmp-voice');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }
        $tempPath = $tempDir.'/sys-'.Str::slug($label).'-'.Str::random(8).'.mp3';

        // provider chain: ถ้า clip override provider ให้ใช้ก่อน แล้วต่อด้วย chain ปกติ
        $chain = $this->settings->getVoiceProviderChain();
        if (! empty($voiceConfig['provider'])) {
            $chain = array_values(array_unique(array_merge([$voiceConfig['provider']], $chain)));
        }

        $voiceOverride = $voiceConfig['voice'] ?? null;
        $lastError = null;
        $attempts = [];

        foreach ($chain as $providerName) {
            $provider = $this->resolveProvider($providerName);
            if (! $provider) {
                $attempts[$providerName] = 'unknown_provider';

                continue;
            }
            if (! $provider->isAvailable()) {
                $attempts[$providerName] = 'not_available';

                continue;
            }

            $opts = ['output_path' => $tempPath, 'language' => 'th'];
            if (! empty($voiceOverride)) {
                $opts['voice'] = $voiceOverride;
            }

            try {
                $result = $provider->synthesize($text, $opts);
            } catch (\Throwable $e) {
                $attempts[$providerName] = 'exception: '.$e->getMessage();

                continue;
            }

            if (($result['success'] ?? false) && file_exists($tempPath) && filesize($tempPath) > 1000) {
                // ✅ สำเร็จ — ย้ายเข้า public disk (local)
                try {
                    Storage::disk('public')->put($relativePath, file_get_contents($tempPath));
                } catch (\Throwable $e) {
                    @unlink($tempPath);

                    return $this->fail('สร้างเสียงสำเร็จ ('.$providerName.') แต่บันทึกไฟล์ไม่สำเร็จ: '.$e->getMessage());
                }
                @unlink($tempPath);

                // ⚠️ ใช้ audio_length_ms (ความยาวเสียงจริง) เท่านั้น — provider ส่วนใหญ่ (gtts/google/openai)
                //   คืน duration_ms = "เวลา synth" (wall-clock) ไม่ใช่ความยาวเสียง → ห้าม trust
                //   ไม่มี → ประมาณจาก file size (ตรงกับ FortuneVoiceSummaryService)
                $durationMs = $result['audio_length_ms'] ?? $this->estimateDurationMs($relativePath, $text);

                Log::info('🎧 SystemVoice: ✅ สร้างเสียงสำเร็จ', [
                    'label' => $label,
                    'provider' => $providerName,
                    'path' => $relativePath,
                ]);

                return [
                    'success' => true,
                    'audio_url' => Storage::disk('public')->url($relativePath),
                    'provider_used' => $providerName,
                    'duration_ms' => $durationMs,
                    'voice_used' => $result['voice_used'] ?? $voiceOverride,
                    'error' => null,
                ];
            }

            $lastError = $result['error'] ?? 'unknown';
            $attempts[$providerName] = $lastError;
            if (file_exists($tempPath) && filesize($tempPath) < 1000) {
                @unlink($tempPath);
            }
        }

        @unlink($tempPath);

        return $this->fail('TTS providers ทั้งหมดล้มเหลว — '.json_encode($attempts, JSON_UNESCAPED_UNICODE));
    }

    /**
     * ลบไฟล์เสียงของคลิป (สร้างใหม่/ปิดใช้)
     */
    public function deleteClipAudio(FortuneSystemVoiceClip $clip): void
    {
        try {
            if (! empty($clip->audio_path) && Storage::disk('public')->exists($clip->audio_path)) {
                Storage::disk('public')->delete($clip->audio_path);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $clip->update([
            'audio_path' => null,
            'audio_url' => null,
            'audio_duration_ms' => null,
            'audio_provider' => null,
            // 🔁 reset กลับเป็น tts — กัน isUploaded() ค้าง true → CLI ข้ามคลิปถาวร
            'audio_source' => 'tts',
            'audio_original_name' => null,
            'audio_voice_id' => null,
            'audio_chars' => null,
            'generated_at' => null,
        ]);
    }

    /**
     * Resolve provider instance ตามชื่อ (เหมือน FortuneVoiceSummaryService)
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
     * ประมาณ duration ms จาก file size (fallback)
     *
     * @param  string  $relativePath  path บน public disk
     */
    protected function estimateDurationMs(string $relativePath, string $text): int
    {
        try {
            $size = Storage::disk('public')->size($relativePath);
            if ($size > 0) {
                return max(1000, (int) round($size / 16));
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return max(3000, (int) round(mb_strlen($text) / 4 * 1000));
    }

    /**
     * Build standard fail-result
     *
     * @return array{success: bool, audio_url: null, provider_used: null, duration_ms: null, voice_used: null, error: string}
     */
    protected function fail(string $error): array
    {
        return [
            'success' => false,
            'audio_url' => null,
            'provider_used' => null,
            'duration_ms' => null,
            'voice_used' => null,
            'error' => $error,
        ];
    }
}
