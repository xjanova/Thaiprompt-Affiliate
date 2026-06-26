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

            // ใช้ไฟล์ของ "สล็อตที่เลือก" (tts หรือ upload)
            $path = $clip->activeAudioPath();
            $url = $clip->activeAudioUrl();
            if (empty($path) || empty($url)) {
                return null;
            }

            // เช็คไฟล์ยังอยู่จริงบน public disk (กันไฟล์ถูกลบ/ย้าย)
            if (! Storage::disk('public')->exists($path)) {
                return null;
            }

            // 🔊 คืน URL พร้อม cache-bust (?v=version) — กัน FB ส่งเสียงเก่าที่ cache ไว้
            //   (path ไฟล์คงที่ทุกครั้งที่เจนใหม่ → ถ้าไม่ติดเวอร์ชัน FB จะ reuse เสียงเดิม)
            return $clip->deliveryAudioUrl();
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
            // path ปลายทางสล็อต TTS (stable ตาม key — สร้างใหม่ทับไฟล์ TTS เดิมได้)
            $relativePath = self::STORAGE_DIR.'/'.$clip->clip_key.'.mp3';

            // 🛡️ กันชนสล็อต (defense-in-depth): ถ้าสล็อตอัปโหลดบังเอิญอ้างไฟล์ path เดียวกับ TTS
            //    (เช่น legacy row จาก migration เก่าที่ย้ายไฟล์ไม่สำเร็จ) — ห้ามเขียน TTS ทับไฟล์อัปโหลด
            if (! empty($clip->upload_audio_path) && $clip->upload_audio_path === $relativePath) {
                return $this->fail('สล็อตอัปโหลดชี้ไฟล์เดียวกับเสียง AI — ลบไฟล์อัปโหลด หรืออัปโหลดใหม่ก่อนกดสร้างเสียง');
            }

            // ลบไฟล์ TTS เดิมถ้า path ต่าง (ไม่แตะไฟล์อัปโหลด ซึ่งอยู่คนละ path)
            $this->forgetOldFile($clip->audio_path, $relativePath);

            $synth = $this->synthesizeToPublic($text, $clip->clip_key, $clip->voice_config ?? null, $relativePath);
            if (! $synth['success']) {
                return $synth;
            }

            $update = [
                'audio_path' => $relativePath,
                'audio_url' => $synth['audio_url'],
                'audio_duration_ms' => $synth['duration_ms'],
                'audio_provider' => $synth['provider_used'],
                'audio_source' => 'tts',
                'audio_voice_id' => $synth['voice_used'],
                'audio_chars' => mb_strlen($text),
                'generated_at' => now(),
            ];

            // ตั้ง active = tts เฉพาะเมื่อยังไม่มีไฟล์อัปโหลดให้เลือก (ไม่แย่ง choice ของแอดมิน)
            if (! $clip->hasUploadAudio()) {
                $update['active_audio_source'] = 'tts';
            }

            $clip->update($update);

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

            // 📂 สล็อตอัปโหลดใช้ path แยกจาก TTS (`-upload`) → เก็บได้ทั้ง 2 สล็อตพร้อมกัน
            $oldUploadPath = $clip->upload_audio_path;

            if ($origExt === 'mp3') {
                // mp3 อยู่แล้ว — stream เก็บตรง (ไม่โหลดเข้า memory)
                $relativePath = self::STORAGE_DIR.'/'.$clip->clip_key.'-upload.mp3';
                $this->forgetOldFile($oldUploadPath, $relativePath);
                $file->storeAs(self::STORAGE_DIR, $clip->clip_key.'-upload.mp3', 'public');
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

                $relativePath = self::STORAGE_DIR.'/'.$clip->clip_key.'-upload.mp3';
                $this->forgetOldFile($oldUploadPath, $relativePath);
                // stream temp → public (ไม่โหลดทั้งไฟล์เข้า memory)
                Storage::disk('public')->putFileAs(self::STORAGE_DIR, new File($tmpMp3), $clip->clip_key.'-upload.mp3');
                $converted = true;
            } else {
                // ไม่มี ffmpeg → stream เก็บตามฟอร์แมตเดิม (FB อาจไม่รองรับ)
                $relativePath = self::STORAGE_DIR.'/'.$clip->clip_key.'-upload.'.$origExt;
                $this->forgetOldFile($oldUploadPath, $relativePath);
                $file->storeAs(self::STORAGE_DIR, $clip->clip_key.'-upload.'.$origExt, 'public');
            }

            // ต้องมีไฟล์จริง + ไม่ใช่ไฟล์ว่าง
            if (! Storage::disk('public')->exists($relativePath) || Storage::disk('public')->size($relativePath) < 100) {
                return $this->fail('บันทึกไฟล์อัปโหลดไม่สำเร็จ (ไฟล์ว่าง)');
            }

            $uploadUrl = Storage::disk('public')->url($relativePath);

            $update = [
                'upload_audio_path' => $relativePath,
                'upload_audio_url' => $uploadUrl,
                'upload_audio_duration_ms' => $this->estimateDurationMs($relativePath, (string) $clip->script_text),
                'upload_original_name' => $origName,
                'upload_audio_at' => now(),
            ];

            // ตั้ง active = upload เฉพาะเมื่อยังไม่มีเสียง TTS ให้เลือก (ไม่แย่ง choice ของแอดมิน)
            if (! $clip->hasTtsAudio()) {
                $update['active_audio_source'] = 'upload';
            }

            $clip->update($update);

            Log::info('🎧 SystemVoice: ⬆️ อัปโหลดไฟล์เสียงสำเร็จ', [
                'clip_key' => $clip->clip_key,
                'orig_ext' => $origExt,
                'converted_to_mp3' => $converted,
                'path' => $relativePath,
                'active_source' => $clip->fresh()->activeSource(),
            ]);

            return [
                'success' => true,
                'audio_url' => $uploadUrl,
                'provider_used' => $converted ? 'upload+mp3' : 'upload',
                'duration_ms' => $update['upload_audio_duration_ms'],
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
     * ลบไฟล์เก่าถ้า path ใหม่ต่างจากเดิม (เช่น เปลี่ยนนามสกุล .mp3 → .wav ในสล็อตเดียวกัน)
     */
    protected function forgetOldFile(?string $old, string $newPath): void
    {
        try {
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
     * 🔊 (2026-06-26) สร้างไฟล์เสียง "กติกา (เดิม) + คลิปรหัสยืนยัน (สุ่ม) ต่อท้าย" เป็นไฟล์เดียว
     *
     * ใช้กับฟีเจอร์ "บังคับฟังเสียงกติกาก่อนสร้างบิล": ลูกค้าต้องฟังให้จบเพื่อได้รหัสท้ายคลิป
     * แล้วพิมพ์รหัสยืนยัน → ระบบจึงออก QR. รหัสอยู่ท้ายคลิป = บังคับฟังโดยปริยาย.
     *
     * - reuse consent_rules.mp3 (เสียงกติกาที่เจนไว้แล้ว — 32kHz mono)
     * - เจน "คลิปรหัส" ด้วย provider ที่แอดมินเลือก (default minimax 32kHz = format ตรงกับกติกา)
     * - รวมไฟล์ด้วย ffmpeg (re-encode uniform = เนียน) ; ไม่มี ffmpeg → raw mp3 concat (strip ID3)
     * - 1 ไฟล์ต่อ user (overwrite ทุกครั้ง) + cache-bust ?v= กัน FB/LINE cache เสียงเก่า
     *
     * @return string|null public URL ของไฟล์รวม หรือ null ถ้าสร้างไม่สำเร็จ (caller → degrade เป็นกล่องกติกาปกติ)
     */
    public function buildConsentAudioWithCode(string $code, string $userKey, ?string $provider = null): ?string
    {
        try {
            $code = preg_replace('/\D/', '', $code);
            if ($code === '') {
                return null;
            }

            // 1) เสียงกติกาเดิม (active slot ของ clip 'consent_rules' หรือไฟล์ static)
            $rulesPath = $this->resolveConsentRulesPath();
            if (! $rulesPath) {
                Log::warning('🔊 ConsentCode: ไม่พบไฟล์เสียงกติกา (consent_rules) → ข้ามเสียง');

                return null;
            }

            // 2) เจนคลิปรหัส (provider เลือกได้) — temp path บน public disk
            $provider = $provider ?: ($this->settings->consent_audio_code_voice_provider ?? 'minimax');
            $codeRel = self::STORAGE_DIR.'/consent_code/_code_'.md5($userKey).'.mp3';
            $synth = $this->synthesizeToPublic(
                $this->buildCodeSpokenText($code),
                'consent-code',
                ['provider' => $provider],
                $codeRel,
            );
            if (! ($synth['success'] ?? false)) {
                Log::warning('🔊 ConsentCode: เจนคลิปรหัสไม่สำเร็จ', ['provider' => $provider, 'error' => $synth['error'] ?? null]);

                return null;
            }

            // 3) รวมไฟล์ (กติกา + รหัส) → 1 ไฟล์ต่อ user
            $outRel = self::STORAGE_DIR.'/consent_code/combined_'.md5($userKey).'.mp3';
            $rulesAbs = Storage::disk('public')->path($rulesPath);
            $codeAbs = Storage::disk('public')->path($codeRel);
            $outAbs = Storage::disk('public')->path($outRel);
            @mkdir(dirname($outAbs), 0775, true);

            $merged = $this->concatMp3($rulesAbs, $codeAbs, $outAbs);

            // เก็บกวาดคลิปรหัส (ไม่ต้องเก็บแยก)
            $this->deleteFile($codeRel);

            if (! $merged || ! Storage::disk('public')->exists($outRel)) {
                Log::warning('🔊 ConsentCode: รวมไฟล์เสียงไม่สำเร็จ');

                return null;
            }

            // เก็บความยาวเสียง (ประมาณจากขนาดไฟล์) ให้ LINE แสดง metadata — key ต้องตรงกับ trait CONSENT_AUDIO_DUR_PREFIX
            try {
                Cache::put('fortune:consent_audio_dur:'.$userKey, $this->estimateDurationMs($outRel, ''), 600);
            } catch (\Throwable $e) {
                // non-blocking
            }

            return Storage::disk('public')->url($outRel).'?v='.now()->timestamp;
        } catch (\Throwable $e) {
            Log::warning('🔊 ConsentCode: buildConsentAudioWithCode exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * หา path (public disk) ของไฟล์เสียงกติกา consent_rules — slot ที่ active หรือไฟล์ static
     */
    protected function resolveConsentRulesPath(): ?string
    {
        try {
            $clip = FortuneSystemVoiceClip::where('clip_key', 'consent_rules')->first();
            if ($clip) {
                $p = $clip->activeAudioPath();
                if (! empty($p) && Storage::disk('public')->exists($p)) {
                    return $p;
                }
            }
        } catch (\Throwable $e) {
            // ignore → ลอง static
        }

        $static = self::STORAGE_DIR.'/consent_rules.mp3';

        return Storage::disk('public')->exists($static) ? $static : null;
    }

    /**
     * ข้อความพูด "รหัสยืนยัน" ต่อท้ายเสียงกติกา — เว้นวรรคเลขให้ TTS อ่านชัด + ย้ำ 2 รอบ
     */
    protected function buildCodeSpokenText(string $code): string
    {
        $spaced = trim(implode('   ', mb_str_split($code)));

        return 'และนี่คือรหัสยืนยันสำหรับเจ้าชะตานะคะ ... '
            ."รหัสคือ {$spaced} ... "
            ."ขอย้ำอีกครั้ง รหัสคือ {$spaced} ... "
            ."กรุณาพิมพ์รหัสนี้ในแชท เพื่อยืนยันว่าฟังกติกาแล้ว แล้วแม่หมอจะส่งคิวอาร์โค้ดให้นะคะ";
    }

    /**
     * รวมไฟล์ mp3 สองไฟล์ → ไฟล์เดียว : ffmpeg (re-encode uniform 32kHz mono) ก่อน, ไม่มี → raw concat
     */
    protected function concatMp3(string $a, string $b, string $out): bool
    {
        $ffmpeg = $this->ffmpegPath();
        if ($ffmpeg) {
            try {
                $cmd = escapeshellarg($ffmpeg).' -y -hide_banner -loglevel error'
                    .' -i '.escapeshellarg($a)
                    .' -i '.escapeshellarg($b)
                    .' -filter_complex '.escapeshellarg('[0:a][1:a]concat=n=2:v=0:a=1[out]')
                    .' -map '.escapeshellarg('[out]')
                    .' -ar 32000 -ac 1 -b:a 128k '.escapeshellarg($out).' 2>&1';
                @exec($cmd, $o, $code);
                if ($code === 0 && file_exists($out) && filesize($out) > 1000) {
                    return true;
                }
            } catch (\Throwable $e) {
                // fall through → raw concat
            }
        }

        return $this->rawConcatMp3($a, $b, $out);
    }

    /**
     * Raw MP3 byte concat (fallback ไม่มี ffmpeg) — strip ID3v2 ของไฟล์ที่ 2 กันหัว tag โผล่กลางสตรีม
     */
    protected function rawConcatMp3(string $a, string $b, string $out): bool
    {
        try {
            $ba = @file_get_contents($a);
            $bb = @file_get_contents($b);
            if ($ba === false || $bb === false) {
                return false;
            }
            $bb = $this->stripId3v2($bb);
            $r = @file_put_contents($out, $ba.$bb);

            return $r !== false && file_exists($out) && filesize($out) > 1000;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * ตัด ID3v2 header (ต้นไฟล์) ออก — ใช้ตอน raw concat ไฟล์ที่ 2
     */
    protected function stripId3v2(string $bytes): string
    {
        if (strlen($bytes) > 10 && strncmp($bytes, 'ID3', 3) === 0) {
            // ขนาด tag = syncsafe int (byte 6-9, ใช้ 7 บิตล่างของแต่ละ byte)
            $size = ((ord($bytes[6]) & 0x7f) << 21)
                | ((ord($bytes[7]) & 0x7f) << 14)
                | ((ord($bytes[8]) & 0x7f) << 7)
                | (ord($bytes[9]) & 0x7f);
            $headerSize = 10 + $size;
            if ($headerSize > 0 && $headerSize < strlen($bytes)) {
                return substr($bytes, $headerSize);
            }
        }

        return $bytes;
    }

    /**
     * ลบไฟล์เสียงของคลิป — เลือกสล็อตได้ (tts | upload | all)
     *
     * ลบสล็อต active → ระบบจะสลับ active ไปสล็อตที่เหลือให้อัตโนมัติ (ถ้ามี)
     *
     * @param  string  $which  สล็อตที่จะลบ: 'tts' | 'upload' | 'all'
     */
    public function deleteClipAudio(FortuneSystemVoiceClip $clip, string $which = 'all'): void
    {
        $which = in_array($which, ['tts', 'upload', 'all'], true) ? $which : 'all';
        $update = [];

        // ── ลบสล็อต TTS ──
        if ($which === 'tts' || $which === 'all') {
            $this->deleteFile($clip->audio_path);
            $update += [
                'audio_path' => null,
                'audio_url' => null,
                'audio_duration_ms' => null,
                'audio_provider' => null,
                'audio_source' => 'tts',
                'audio_original_name' => null,
                'audio_voice_id' => null,
                'audio_chars' => null,
                'generated_at' => null,
            ];
        }

        // ── ลบสล็อตอัปโหลด ──
        if ($which === 'upload' || $which === 'all') {
            $this->deleteFile($clip->upload_audio_path);
            $update += [
                'upload_audio_path' => null,
                'upload_audio_url' => null,
                'upload_audio_duration_ms' => null,
                'upload_original_name' => null,
                'upload_audio_at' => null,
            ];
        }

        // ── สลับ active ไปสล็อตที่ยังเหลือไฟล์ (กัน active ชี้สล็อตว่าง) ──
        $ttsLeft = ($which === 'tts' || $which === 'all') ? false : $clip->hasTtsAudio();
        $uploadLeft = ($which === 'upload' || $which === 'all') ? false : $clip->hasUploadAudio();

        if ($clip->activeSource() === 'tts' && ! $ttsLeft && $uploadLeft) {
            $update['active_audio_source'] = 'upload';
        } elseif ($clip->activeSource() === 'upload' && ! $uploadLeft && $ttsLeft) {
            $update['active_audio_source'] = 'tts';
        } elseif (! $ttsLeft && ! $uploadLeft) {
            // ไม่เหลือไฟล์เลย → reset เป็น tts (กัน isUploaded() ค้าง true → CLI ข้ามถาวร)
            $update['active_audio_source'] = 'tts';
        }

        $clip->update($update);
    }

    /**
     * เลือกว่าจะใช้เสียงสล็อตไหนส่งให้ลูกค้า (tts | upload)
     *
     * @return array{success: bool, active_source: ?string, error: ?string}
     */
    public function setActiveSource(FortuneSystemVoiceClip $clip, string $source): array
    {
        if (! in_array($source, ['tts', 'upload'], true)) {
            return ['success' => false, 'active_source' => $clip->activeSource(), 'error' => 'สล็อตไม่ถูกต้อง'];
        }

        // เลือกสล็อตที่ "มีไฟล์" เท่านั้น
        if ($source === 'tts' && ! $clip->hasTtsAudio()) {
            return ['success' => false, 'active_source' => $clip->activeSource(), 'error' => 'ยังไม่มีเสียง AI ในคลิปนี้ — กดสร้างเสียงก่อน'];
        }
        if ($source === 'upload' && ! $clip->hasUploadAudio()) {
            return ['success' => false, 'active_source' => $clip->activeSource(), 'error' => 'ยังไม่มีไฟล์อัปโหลดในคลิปนี้ — อัปโหลดก่อน'];
        }

        $clip->update(['active_audio_source' => $source]);

        return ['success' => true, 'active_source' => $source, 'error' => null];
    }

    /**
     * ลบไฟล์บน public disk แบบเงียบ (ไม่ throw)
     */
    protected function deleteFile(?string $path): void
    {
        try {
            if (! empty($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Throwable $e) {
            // ignore
        }
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
