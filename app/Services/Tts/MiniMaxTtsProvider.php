<?php

namespace App\Services\Tts;

use App\Models\AiApiKey;
use App\Models\FortuneTellingSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🎙️ MiniMax T2A v2 (Text-to-Audio) — premium primary provider
 *
 * 📚 https://platform.minimax.io/docs/api-reference/speech-t2a-http
 *
 * Endpoint: POST https://api.minimax.io/v1/t2a_v2
 * Auth:     Bearer {api_key}
 * GroupId:  v2 ไม่ต้องการใน URL — เก็บไว้รองรับ legacy endpoint (v1) เผื่อ admin ใช้ key เก่า
 *
 * Models (verified 2026-06-21 จาก docs):
 *   speech-2.8-hd / 2.8-turbo (ใหม่สุด — default, hd คุณภาพสูงสุด)
 *   speech-2.6-hd / 2.6-turbo
 *   speech-02-hd / 02-turbo
 *   speech-01-hd / 01-turbo (legacy)
 *
 * 🆕 (speech-2.8) voice_setting รองรับ text_normalization (อ่านเลข/วันที่/เงินเป็นธรรมชาติ)
 *    + emotion: happy/sad/angry/fearful/disgusted/surprised/calm/fluent/whisper
 * Alt low-latency endpoint: https://api-uw.minimax.io/v1/t2a_v2
 *
 * Cost: $0.10-0.30 per 1k chars (depending on model)
 * Max text: 10,000 chars per request (เกิน 3,000 แนะนำ stream)
 *
 * Voice IDs สำหรับไทย — admin ต้องไปดูที่ MiniMax Voice ID List page
 * (https://platform.minimax.io/docs/faq/system-voice-id) — ตัวอย่าง:
 *   Thai_warmFemaleHost / Thai_femaleSerene / female-tianmei
 *
 * Response: data.audio = HEX-encoded mp3 (default), bin = ใช้ hex2bin
 * (หาก output_format='base64' — fallback ผ่าน base64_decode)
 */
class MiniMaxTtsProvider implements TtsProviderInterface
{
    public function __construct(protected FortuneTellingSetting $settings) {}

    public function name(): string
    {
        return 'minimax';
    }

    public function isAvailable(): bool
    {
        // v2 ใช้แค่ API key — group_id optional
        return ! empty($this->settings->getMinimaxApiKey());
    }

    public function synthesize(string $text, array $options = []): array
    {
        $startTime = microtime(true);

        $apiKey = $this->settings->getMinimaxApiKey();

        if (empty($apiKey)) {
            return $this->failResult($startTime, 'MiniMax API key missing', null);
        }

        // ⚠️ (2026-06-21) default voice 'Thai_warmFemaleHost' ไม่มีจริงในบัญชี (status 2054)
        //   → ใช้ Thai_female_1_sample1 (verified มีจริงผ่าน get_voice). admin override ได้
        $voice = $options['voice'] ?? $this->settings->minimax_voice_id ?? 'Thai_female_1_sample1';
        $model = $this->settings->minimax_model ?: 'speech-2.8-hd';
        $outputPath = $options['output_path'] ?? null;
        $language = $options['language'] ?? 'th';

        if (! $outputPath) {
            return $this->failResult($startTime, 'output_path required', $voice);
        }

        // ⚠️ Text length guard — MiniMax v2 = 10,000 chars max
        $textLen = mb_strlen($text);
        if ($textLen > 9500) {
            $text = mb_substr($text, 0, 9500);
            Log::info('MiniMax TTS: ตัด text ที่ 9500 chars (max 10,000)', [
                'original_len' => $textLen,
            ]);
        }

        // Resolve base URL — admin override ผ่าน ai_api_keys[base_url] ได้
        $baseUrl = AiApiKey::DEFAULT_BASE_URLS['minimax'] ?? 'https://api.minimax.io/v1';

        // GroupId optional — รองรับ legacy v1 endpoint ถ้า admin ใช้ key เก่า
        $groupId = $this->settings->getMinimaxGroupId();
        $url = rtrim($baseUrl, '/').'/t2a_v2';
        if (! empty($groupId)) {
            $url .= '?GroupId='.urlencode($groupId);
        }

        // language_boost: ช่วยให้ออกเสียงตรงภาษา — Thai/Chinese,Yue/English,etc.
        $languageBoost = $this->resolveLanguageBoost($language);

        $payload = [
            'model' => $model,
            'text' => $text,
            'stream' => false,
            'output_format' => 'hex',  // explicit — กัน server default เปลี่ยน
            'language_boost' => $languageBoost,
            'voice_setting' => [
                'voice_id' => $voice,
                'speed' => 1.0,
                'vol' => 1.0,
                'pitch' => 0,
                'emotion' => 'calm',  // อบอุ่น เหมาะกับแม่หมอ
                // 🆕 (2026-06-21) speech-2.8 — อ่านตัวเลข/วันที่/จำนวนเงินให้เป็นธรรมชาติ
                //   (เช่น "12/12/2500" → วันที่, "฿99" → เก้าสิบเก้าบาท) เหมาะกับเสียงดูดวง
                'text_normalization' => true,
            ],
            'audio_setting' => [
                'sample_rate' => 32000,
                'bitrate' => 128000,
                'format' => 'mp3',
                'channel' => 1,
            ],
        ];

        try {
            // 🔁 (2026-07-09) Retry เฉพาะ connection/SSL timeout (cURL 28) ก่อน fallback
            //   เคสจริง reading 8888 (FTU-260709-T5259): minimax SSL connection timeout ครั้งเดียว
            //   → ตกไป gtts ทันที ทั้งที่เครดิตยังมี (endpoint ต่างประเทศ เน็ตสะดุดชั่วคราว).
            //   retry 2 ครั้ง (รวม 3 attempts) เว้น 2 วิ — retry เฉพาะ ConnectionException
            //   (ไม่ retry error API เช่น quota/auth ที่ตอบ HTTP 200 + base_resp.status_code)
            //   + connectTimeout(12) กัน hang ยาว (default อาจรอนานเกิน)
            $response = Http::withToken($apiKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->connectTimeout(12)
                ->timeout(60)
                ->retry(3, 2000, function ($exception) {
                    $retryable = $exception instanceof ConnectionException;
                    if ($retryable) {
                        Log::warning('MiniMax TTS: connection fail → retry', [
                            'error' => mb_substr($exception->getMessage(), 0, 160),
                        ]);
                    }

                    return $retryable;
                }, throw: true)
                ->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('MiniMax TTS HTTP fail', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                return $this->failResult(
                    $startTime,
                    'MiniMax HTTP '.$response->status().': '.mb_substr($response->body(), 0, 200),
                    $voice
                );
            }

            $data = $response->json();

            // base_resp.status_code: 0 = success
            $statusCode = $data['base_resp']['status_code'] ?? null;
            if ($statusCode !== 0) {
                $errMsg = $data['base_resp']['status_msg'] ?? 'unknown';
                Log::warning('MiniMax TTS API error', [
                    'status_code' => $statusCode,
                    'status_msg' => $errMsg,
                    'trace_id' => $data['trace_id'] ?? null,
                ]);

                return $this->failResult(
                    $startTime,
                    'MiniMax status '.$statusCode.': '.$errMsg,
                    $voice
                );
            }

            $audioRaw = $data['data']['audio'] ?? null;
            if (empty($audioRaw)) {
                return $this->failResult($startTime, 'MiniMax response ไม่มี audio data', $voice);
            }

            // 🛡️ Defensive decode — try hex first (default), fallback base64
            //    เผื่อ MiniMax change default output_format ในอนาคต
            $audioBinary = $this->decodeAudio($audioRaw);
            if ($audioBinary === false) {
                return $this->failResult(
                    $startTime,
                    'MiniMax audio decode fail (hex+base64 ทั้งคู่)',
                    $voice
                );
            }

            // เขียน mp3
            $dir = dirname($outputPath);
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            file_put_contents($outputPath, $audioBinary);

            // Log usage chars (จาก response)
            $usageChars = $data['extra_info']['usage_characters'] ?? mb_strlen($text);
            $audioLengthMs = $data['extra_info']['audio_length'] ?? null;

            Log::info('🎙️ MiniMax TTS สำเร็จ', [
                'model' => $model,
                'voice' => $voice,
                'usage_chars' => $usageChars,
                'audio_length_ms' => $audioLengthMs,
                'audio_size_kb' => round(strlen($audioBinary) / 1024, 1),
                'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
            ]);

            return [
                'success' => true,
                'audio_path' => $outputPath,
                'error' => null,
                'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                'voice_used' => $voice,
                'audio_length_ms' => $audioLengthMs,  // 🆕 จริงจาก MiniMax — ใช้ส่ง LINE duration
                'usage_chars' => $usageChars,
            ];
        } catch (\Throwable $e) {
            Log::warning('MiniMax TTS exception', ['error' => $e->getMessage()]);

            return $this->failResult($startTime, 'MiniMax exception: '.$e->getMessage(), $voice);
        }
    }

    /**
     * Decode audio data — ลอง hex ก่อน fallback base64
     */
    protected function decodeAudio(string $raw)
    {
        // hex check — ทั้งหมดเป็น 0-9a-f
        if (preg_match('/^[0-9a-fA-F]+$/', $raw)) {
            $binary = @hex2bin($raw);
            if ($binary !== false && strlen($binary) > 100) {
                return $binary;
            }
        }

        // base64 fallback
        $binary = @base64_decode($raw, true);
        if ($binary !== false && strlen($binary) > 100) {
            return $binary;
        }

        return false;
    }

    /**
     * Resolve language_boost ตามภาษา input
     * MiniMax รองรับ: auto, Chinese, Chinese,Yue, English, Arabic, Russian,
     *                 Spanish, French, Portuguese, German, Turkish, Dutch,
     *                 Ukrainian, Vietnamese, Indonesian, Japanese, Italian,
     *                 Korean, Thai, Polish, Romanian, Greek, Czech, Finnish, Hindi
     */
    protected function resolveLanguageBoost(string $language): string
    {
        return match (strtolower($language)) {
            'th', 'tha', 'thai' => 'Thai',
            'en', 'eng', 'english' => 'English',
            'zh', 'chi', 'chinese' => 'Chinese',
            'ja', 'jpn', 'japanese' => 'Japanese',
            default => 'auto',
        };
    }

    protected function failResult(float $startTime, string $error, ?string $voice): array
    {
        return [
            'success' => false,
            'audio_path' => null,
            'error' => $error,
            'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
            'voice_used' => $voice,
            'audio_length_ms' => null,
            'usage_chars' => 0,
        ];
    }
}
