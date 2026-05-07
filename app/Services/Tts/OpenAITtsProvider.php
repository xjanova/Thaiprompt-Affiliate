<?php

namespace App\Services\Tts;

use App\Models\AiApiKey;
use App\Models\FortuneTellingSetting;
use App\Services\AiApiKeyPoolService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🎙️ OpenAI TTS — paid fallback ผ่าน ai_api_keys pool
 *
 * Doc: https://platform.openai.com/docs/guides/text-to-speech
 * Endpoint: POST {base}/audio/speech
 * Models: gpt-4o-mini-tts (ใหม่ + ถูก) / tts-1 / tts-1-hd
 * Voices: alloy / echo / fable / onyx / nova / shimmer
 *
 * Cost: $15/1M chars (tts-1) / $30/1M chars (tts-1-hd)
 */
class OpenAITtsProvider implements TtsProviderInterface
{
    public function __construct(
        protected FortuneTellingSetting $settings,
        protected AiApiKeyPoolService $pool,
    ) {}

    public function name(): string
    {
        return 'openai_tts';
    }

    public function isAvailable(): bool
    {
        // ลองหา key ผ่าน pool (provider=openai, purpose=tts หรือ any)
        $key = $this->pool->getKey('openai', 'tts');
        if ($key) {
            return true;
        }

        // fallback หา openai key ทั่วไป (purpose=any/null)
        $key = $this->pool->getKey('openai');

        return $key !== null;
    }

    public function synthesize(string $text, array $options = []): array
    {
        $startTime = microtime(true);

        // ลอง purpose=tts ก่อน → fallback any
        $key = $this->pool->getKey('openai', 'tts') ?? $this->pool->getKey('openai');

        if (! $key) {
            return [
                'success' => false,
                'audio_path' => null,
                'error' => 'ไม่มี OpenAI key ใน pool',
                'duration_ms' => null,
                'voice_used' => null,
            ];
        }

        $apiKey = $key->api_key;
        $baseUrl = $key->resolveBaseUrl() ?? AiApiKey::DEFAULT_BASE_URLS['openai'];
        $url = rtrim($baseUrl, '/').'/audio/speech';

        $voice = $options['voice'] ?? $this->settings->openai_tts_voice ?? 'shimmer';
        $model = $this->settings->openai_tts_model ?? 'gpt-4o-mini-tts';
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

        $payload = [
            'model' => $model,
            'input' => $text,
            'voice' => $voice,
            'response_format' => 'mp3',
            'speed' => 0.95,  // ช้านิดให้แม่หมอ feel
        ];

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post($url, $payload);

            if (! $response->successful()) {
                $errMsg = 'OpenAI TTS HTTP '.$response->status();
                $isRateLimit = $response->status() === 429;

                // record error ที่ pool
                $key->recordSmartError(
                    $errMsg.': '.mb_substr($response->body(), 0, 200),
                    $model,
                    $isRateLimit
                );

                return [
                    'success' => false,
                    'audio_path' => null,
                    'error' => $errMsg.': '.mb_substr($response->body(), 0, 200),
                    'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                    'voice_used' => $voice,
                ];
            }

            $audioBinary = $response->body();
            if (empty($audioBinary)) {
                return [
                    'success' => false,
                    'audio_path' => null,
                    'error' => 'OpenAI TTS empty response',
                    'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                    'voice_used' => $voice,
                ];
            }

            $dir = dirname($outputPath);
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            file_put_contents($outputPath, $audioBinary);

            // record usage (ประมาณ tokens จาก char count)
            $approxTokens = (int) ceil(mb_strlen($text) / 4);
            $key->recordUsage(0, $approxTokens, $model, (int) round((microtime(true) - $startTime) * 1000), 'tts');

            return [
                'success' => true,
                'audio_path' => $outputPath,
                'error' => null,
                'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                'voice_used' => $voice,
            ];
        } catch (\Throwable $e) {
            Log::warning('OpenAI TTS exception', ['error' => $e->getMessage()]);
            $key?->recordSmartError($e->getMessage(), $model);

            return [
                'success' => false,
                'audio_path' => null,
                'error' => 'OpenAI TTS exception: '.$e->getMessage(),
                'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                'voice_used' => $voice,
            ];
        }
    }
}
