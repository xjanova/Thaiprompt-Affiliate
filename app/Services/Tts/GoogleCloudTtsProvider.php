<?php

namespace App\Services\Tts;

use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🎙️ Google Cloud Text-to-Speech — free tier (1M chars/month for Standard, 1M for WaveNet)
 *
 * Doc: https://cloud.google.com/text-to-speech/docs
 * Endpoint: POST https://texttospeech.googleapis.com/v1/text:synthesize?key={api_key}
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
    public function __construct(protected FortuneTellingSetting $settings) {}

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
     *   1. GOOGLE_TTS_API_KEY              (เฉพาะ TTS)
     *   2. GOOGLE_TRANSLATE_API_KEY        (มีใน .env อยู่แล้ว — share)
     *   3. GOOGLE_CLOUD_TRANSLATE_API_KEY  (alternative same project)
     *   4. GOOGLE_API_KEY                  (general-purpose)
     */
    protected function resolveApiKey(): ?string
    {
        return config('services.google.tts_api_key')
            ?: env('GOOGLE_TTS_API_KEY')
            ?: config('services.google.translate.api_key')
            ?: env('GOOGLE_TRANSLATE_API_KEY')
            ?: config('services.google_cloud.translate_api_key')
            ?: env('GOOGLE_CLOUD_TRANSLATE_API_KEY')
            ?: config('services.google.api_key')
            ?: env('GOOGLE_API_KEY')
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
            'GOOGLE_TTS_API_KEY' => ! empty(env('GOOGLE_TTS_API_KEY')) || ! empty(config('services.google.tts_api_key')),
            'GOOGLE_TRANSLATE_API_KEY' => ! empty(env('GOOGLE_TRANSLATE_API_KEY')) || ! empty(config('services.google.translate.api_key')),
            'GOOGLE_CLOUD_TRANSLATE_API_KEY' => ! empty(env('GOOGLE_CLOUD_TRANSLATE_API_KEY')) || ! empty(config('services.google_cloud.translate_api_key')),
            'GOOGLE_API_KEY' => ! empty(env('GOOGLE_API_KEY')) || ! empty(config('services.google.api_key')),
        ];

        $resolved = null;
        foreach (array_keys($checks) as $name) {
            if ($checks[$name]) {
                $resolved = $name;
                break;
            }
        }

        // Service account JSON (Speech-to-Text ใช้อยู่)
        $saPath = env('GOOGLE_APPLICATION_CREDENTIALS') ?: config('services.google.credentials_path');
        $saExists = $saPath && file_exists($saPath);

        return [
            'resolved_key_source' => $resolved,
            'keys_found' => $checks,
            'service_account_path' => $saExists ? $saPath : null,
            'project_id' => env('GOOGLE_TRANSLATE_PROJECT_ID') ?: env('GOOGLE_CLOUD_PROJECT_ID') ?: null,
        ];
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
        $rate = (float) ($this->settings->google_tts_speaking_rate ?? 0.95);
        $outputPath = $options['output_path'] ?? null;

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

        $url = 'https://texttospeech.googleapis.com/v1/text:synthesize?key='.urlencode($apiKey);

        try {
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
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
                'error' => 'Google TTS exception: '.$e->getMessage(),
                'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                'voice_used' => $voice,
            ];
        }
    }
}
