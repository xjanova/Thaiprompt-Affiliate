<?php

namespace App\Services\Tts;

use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🎙️ Google Cloud Text-to-Speech — free tier (1M chars/month for Standard, 1M for WaveNet)
 *
 * Doc: https://cloud.google.com/text-to-speech/docs
 * Endpoint: POST https://texttospeech.googleapis.com/v1/text:synthesize (คีย์ส่งทาง header x-goog-api-key ไม่ใส่ ?key= ใน URL)
 *
 * 2 modes:
 *   1. API key auth — เร็ว + ไม่ต้อง OAuth (เก็บใน config('services.google.tts_api_key'))
 *   2. Service Account — ผ่าน google/cloud-text-to-speech composer package
 *
 * Implementation นี้ใช้ API key (mode 1) เพราะง่าย + ระบบมี config แค่ key เดียว
 *
 * Voices สำหรับไทย:
 *   th-TH-Neural2-C  (female, premium quality)
 *   th-TH-Neural2-A  (female)
 *   th-TH-Standard-A (female, free tier)
 *   th-TH-Wavenet-C  (female, WaveNet)
 *
 * Free tier:
 *   - Standard: 4M chars/month
 *   - WaveNet/Neural2: 1M chars/month
 */
class GoogleCloudTtsProvider implements TtsProviderInterface
{
    /** ไบต์สูงสุดต่อคำขอของ Google คือ 5,000 — ไทย 3 ไบต์/ตัว ≈ 1,600 ตัวอักษร */
    public const MAX_INPUT_BYTES = 4800;

    /**
     * @param  string|null  $apiKey  🎬 (2026-11-04) กุญแจที่จะใช้แทนของที่ตั้งไว้ — ปุ่ม "ทดสอบกุญแจ" ก่อนบันทึก
     */
    public function __construct(protected FortuneTellingSetting $settings, protected ?string $apiKey = null) {}

    public function name(): string
    {
        return 'google_tts';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->resolveApiKey());
    }

    /**
     * ดึง API key จาก config — Google Cloud project ใช้ key/credentials เดียวกันได้
     * (ขอเปิด "Cloud Text-to-Speech API" ใน project เดียวกับ Translate/Vision)
     *
     * Priority:
     *   0. 🎬 (2026-11-04) กุญแจที่ส่งเข้ามาตรง ๆ → กุญแจที่แอดมินกรอกในหน้าเสียงตัวละคร (ตาราง settings เข้ารหัส)
     *   1. GOOGLE_TTS_API_KEY              (เฉพาะ TTS)
     *   2. GOOGLE_TRANSLATE_API_KEY        (มีใน .env อยู่แล้ว — share)
     *   3. GOOGLE_CLOUD_TRANSLATE_API_KEY  (alternative same project)
     *   4. GOOGLE_API_KEY                  (general-purpose)
     */
    protected function resolveApiKey(): ?string
    {
        // อ่านผ่าน config() เท่านั้น — prod รัน config:cache แล้ว env() นอกไฟล์ config คืน null
        return $this->apiKey
            ?: $this->settings->getGoogleTtsApiKey()
            ?: config('services.google.tts_api_key')
            ?: config('services.google.translate.api_key')
            ?: config('services.google_cloud.translate_api_key')
            ?: config('services.google.api_key')
            ?: null;
    }

    /**
     * รายงานสถานะ credentials ทั้งหมดที่ตรวจเจอ — สำหรับ admin UI ดู
     *
     * @return array{
     *   resolved_key_source: string|null,
     *   keys_found: array<string, bool>,
     *   service_account_path: string|null,
     *   project_id: string|null,
     * }
     */
    public static function diagnoseCredentials(): array
    {
        $checks = [
            'ADMIN_PAGE_KEY' => (new FortuneTellingSetting)->getGoogleTtsApiKey() !== null,
            'GOOGLE_TTS_API_KEY' => ! empty(config('services.google.tts_api_key')),
            'GOOGLE_TRANSLATE_API_KEY' => ! empty(config('services.google.translate.api_key')),
            'GOOGLE_CLOUD_TRANSLATE_API_KEY' => ! empty(config('services.google_cloud.translate_api_key')),
            'GOOGLE_API_KEY' => ! empty(config('services.google.api_key')),
        ];

        $resolved = null;
        foreach (array_keys($checks) as $name) {
            if ($checks[$name]) {
                $resolved = $name;
                break;
            }
        }

        // Service account JSON (Speech-to-Text ใช้อยู่)
        $saPath = config('services.google.credentials_path');
        $saExists = $saPath && file_exists($saPath);

        return [
            'resolved_key_source' => $resolved,
            'keys_found' => $checks,
            'service_account_path' => $saExists ? $saPath : null,
            'project_id' => config('services.google.translate.project_id') ?: config('services.google.cloud_project_id') ?: null,
        ];
    }

    /**
     * 🎬 (2026-11-04) รายชื่อเสียงไทยของ Google — ใช้ทดสอบกุญแจ (ไม่กินโควตาตัวอักษร) + ให้แอดมินเลือกเสียงต่อห้อง
     * error เป็นภาษาไทยพร้อมแสดงในหน้าแอดมิน · ไม่มีกุญแจในข้อความ
     *
     * @return array{success:bool, voices:list<array{name:string,gender:string,type:string}>, status:?int, error:?string}
     */
    public function listVoices(string $languageCode = 'th-TH'): array
    {
        $apiKey = $this->resolveApiKey();
        if (! $apiKey) {
            return ['success' => false, 'voices' => [], 'status' => null, 'error' => 'ยังไม่ได้ใส่กุญแจ Google Cloud'];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->get('https://texttospeech.googleapis.com/v1/voices', ['languageCode' => $languageCode]);
        } catch (\Throwable $e) {
            return ['success' => false, 'voices' => [], 'status' => null, 'error' => 'ต่อ Google ไม่ได้ ลองใหม่อีกครั้ง'];
        }

        if (! $response->successful()) {
            $reason = (string) $response->json('error.status', '');
            $message = (string) $response->json('error.message', '');

            return ['success' => false, 'voices' => [], 'status' => $response->status(), 'error' => match (true) {
                $response->status() === 403 && (str_contains($message, 'has not been used') || str_contains($message, 'disabled') || $reason === 'PERMISSION_DENIED' && str_contains($message, 'API')) => 'กุญแจถูกต้อง แต่โปรเจกต์ยังไม่ได้เปิด Cloud Text-to-Speech API',
                in_array($response->status(), [400, 401, 403], true) => 'กุญแจไม่ถูกต้อง หรือถูกจำกัดไม่ให้ใช้ Text-to-Speech',
                $response->status() === 429 => 'Google จำกัดจำนวนคำขอชั่วคราว ลองใหม่ภายหลัง',
                default => 'Google ตอบผิดปกติ (HTTP '.$response->status().')',
            }];
        }

        $voices = [];
        foreach ((array) $response->json('voices', []) as $v) {
            $name = (string) ($v['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $voices[] = [
                'name' => $name,
                'gender' => strtolower((string) ($v['ssmlGender'] ?? '')),
                // th-TH-Neural2-C → Neural2 · th-TH-Chirp3-HD-Achernar → Chirp3
                'type' => explode('-', $name)[2] ?? '',
            ];
        }
        usort($voices, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return ['success' => true, 'voices' => $voices, 'status' => 200, 'error' => null];
    }

    public function synthesize(string $text, array $options = []): array
    {
        $startTime = microtime(true);

        $apiKey = $this->resolveApiKey();
        if (! $apiKey) {
            return [
                'success' => false,
                'audio_path' => null,
                'error' => 'Google TTS API key ไม่ได้ตั้งค่า (GOOGLE_TTS_API_KEY ใน .env)',
                'duration_ms' => null,
                'voice_used' => null,
            ];
        }

        $voice = $options['voice'] ?? $this->settings->google_tts_voice ?? 'th-TH-Neural2-C';
        $rate = (float) ($options['speaking_rate'] ?? $this->settings->google_tts_speaking_rate ?? 0.95);
        $outputPath = $options['output_path'] ?? null;

        // ยาวเกิน = Google ตอบ 400 อยู่ดี — ตัดสินเองก่อน ไม่เสียคำขอ (ผู้เรียกถอยไป gTTS ที่ตัดเป็นท่อน)
        if (strlen($text) > self::MAX_INPUT_BYTES) {
            return [
                'success' => false,
                'audio_path' => null,
                'error' => 'Google TTS ข้อความยาวเกิน '.self::MAX_INPUT_BYTES.' ไบต์',
                'duration_ms' => null,
                'voice_used' => $voice,
            ];
        }

        if (! $outputPath) {
            return [
                'success' => false,
                'audio_path' => null,
                'error' => 'output_path required',
                'duration_ms' => null,
                'voice_used' => $voice,
            ];
        }

        // Voice gender mapping (Google ต้องระบุ ssmlGender)
        $gender = str_contains($voice, '-A') || str_contains($voice, '-C') ? 'FEMALE' : 'MALE';

        $payload = [
            'input' => ['text' => $text],
            'voice' => [
                'languageCode' => 'th-TH',
                'name' => $voice,
                'ssmlGender' => $gender,
            ],
            'audioConfig' => [
                'audioEncoding' => 'MP3',
                'speakingRate' => $rate,
                'pitch' => 0.0,
                'volumeGainDb' => 0.0,
                'sampleRateHertz' => 24000,
            ],
        ];

        // 🔐 คีย์ส่งทาง header x-goog-api-key ไม่ใส่ ?key= ใน URL (error ของ Guzzle พิมพ์ URL เต็ม = คีย์หลุดไปกับข้อความ)
        $url = 'https://texttospeech.googleapis.com/v1/text:synthesize';

        try {
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json', 'x-goog-api-key' => $apiKey])
                ->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('Google TTS HTTP fail', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                return [
                    'success' => false,
                    'audio_path' => null,
                    'error' => 'Google TTS HTTP '.$response->status().': '.mb_substr($response->body(), 0, 200),
                    'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                    'voice_used' => $voice,
                ];
            }

            $data = $response->json();
            $audioContent = $data['audioContent'] ?? null;

            if (empty($audioContent)) {
                return [
                    'success' => false,
                    'audio_path' => null,
                    'error' => 'Google TTS response ไม่มี audioContent',
                    'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                    'voice_used' => $voice,
                ];
            }

            // base64 decode → mp3 binary
            $audioBinary = base64_decode($audioContent, true);
            if ($audioBinary === false) {
                return [
                    'success' => false,
                    'audio_path' => null,
                    'error' => 'Google TTS base64 decode fail',
                    'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                    'voice_used' => $voice,
                ];
            }

            $dir = dirname($outputPath);
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            file_put_contents($outputPath, $audioBinary);

            return [
                'success' => true,
                'audio_path' => $outputPath,
                'error' => null,
                'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                'voice_used' => $voice,
            ];
        } catch (\Throwable $e) {
            Log::warning('Google TTS exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'audio_path' => null,
                'error' => 'Google TTS exception: '.\App\Support\SafeLog::exceptionMessage($e),
                'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                'voice_used' => $voice,
            ];
        }
    }
}
