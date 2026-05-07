<?php

namespace App\Services\Tts;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🎙️ gTTS — Google Translate Text-to-Speech (unofficial, ไม่ต้องมี key)
 *
 * ⚠️ Disclaimer:
 *   - ใช้ undocumented endpoint ของ translate.google.com
 *   - Rate-limit อาจขึ้นได้ — ใช้เป็น last-resort fallback เท่านั้น
 *   - Quality สู้ Google Cloud TTS / MiniMax / OpenAI ไม่ได้ — แต่ดีกว่าเงียบ
 *
 * Endpoint: https://translate.google.com/translate_tts
 * Limit: ~200 chars/request → ต้องแบ่ง chunk + concat
 * Voice: ใช้ Thai female default (เลือกไม่ได้)
 */
class GttsProvider implements TtsProviderInterface
{
    /**
     * gTTS จำกัด ~200 chars/request
     */
    private const CHUNK_SIZE = 180;

    public function name(): string
    {
        return 'gtts';
    }

    public function isAvailable(): bool
    {
        // gTTS ไม่ต้องตั้งค่าอะไร — return true เสมอ
        // (caller ตัดสินใจปิดได้ผ่าน fallback list)
        return true;
    }

    public function synthesize(string $text, array $options = []): array
    {
        $startTime = microtime(true);

        $outputPath = $options['output_path'] ?? null;
        if (! $outputPath) {
            return [
                'success' => false,
                'audio_path' => null,
                'error' => 'output_path required',
                'duration_ms' => null,
                'voice_used' => 'gtts-th',
            ];
        }

        $language = $options['language'] ?? 'th';

        // แบ่ง text เป็น chunk ไม่เกิน 180 chars (gTTS ไม่รับยาว)
        $chunks = $this->chunkText($text, self::CHUNK_SIZE);
        if (empty($chunks)) {
            return [
                'success' => false,
                'audio_path' => null,
                'error' => 'gTTS empty text',
                'duration_ms' => null,
                'voice_used' => 'gtts-'.$language,
            ];
        }

        // Concat MP3 chunks (raw concat ใช้ได้กับ MP3 frame-by-frame)
        $combined = '';
        foreach ($chunks as $idx => $chunk) {
            $url = 'https://translate.google.com/translate_tts'
                .'?ie=UTF-8'
                .'&q='.urlencode($chunk)
                .'&tl='.urlencode($language)
                .'&total='.count($chunks)
                .'&idx='.$idx
                .'&textlen='.mb_strlen($chunk)
                .'&client=tw-ob';

            try {
                $response = Http::withHeaders([
                    // ⚠️ User-Agent ต้องเหมือน browser — ไม่งั้น Google block
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Referer' => 'https://translate.google.com/',
                ])
                    ->timeout(30)
                    ->get($url);

                if (! $response->successful()) {
                    Log::warning('gTTS chunk fail', [
                        'idx' => $idx,
                        'status' => $response->status(),
                    ]);

                    return [
                        'success' => false,
                        'audio_path' => null,
                        'error' => 'gTTS HTTP '.$response->status().' ใน chunk #'.$idx,
                        'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                        'voice_used' => 'gtts-'.$language,
                    ];
                }

                $combined .= $response->body();

                // กัน rate-limit — หน่วงนิดระหว่าง chunks
                if (count($chunks) > 1 && $idx < count($chunks) - 1) {
                    usleep(200000); // 200ms
                }
            } catch (\Throwable $e) {
                Log::warning('gTTS chunk exception', [
                    'idx' => $idx,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'audio_path' => null,
                    'error' => 'gTTS exception ใน chunk #'.$idx.': '.$e->getMessage(),
                    'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                    'voice_used' => 'gtts-'.$language,
                ];
            }
        }

        if (empty($combined)) {
            return [
                'success' => false,
                'audio_path' => null,
                'error' => 'gTTS empty combined output',
                'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                'voice_used' => 'gtts-'.$language,
            ];
        }

        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($outputPath, $combined);

        return [
            'success' => true,
            'audio_path' => $outputPath,
            'error' => null,
            'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
            'voice_used' => 'gtts-'.$language,
        ];
    }

    /**
     * แบ่ง text เป็น chunk ตามขอบประโยค (ไม่ตัดกลางคำ)
     *
     * @return array<int, string>
     */
    protected function chunkText(string $text, int $maxLen): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        if (mb_strlen($text) <= $maxLen) {
            return [$text];
        }

        $chunks = [];
        $remaining = $text;

        while (mb_strlen($remaining) > $maxLen) {
            $slice = mb_substr($remaining, 0, $maxLen);

            // หา breakpoint ตามลำดับ: ., ?, !, , space
            $breakpoints = ['. ', '? ', '! ', ', ', ' ', "\n"];
            $cutAt = false;
            foreach ($breakpoints as $bp) {
                $pos = mb_strrpos($slice, $bp);
                if ($pos !== false && $pos > $maxLen / 2) {
                    $cutAt = $pos + mb_strlen($bp);
                    break;
                }
            }

            if ($cutAt === false) {
                $cutAt = $maxLen;  // ตัดดิบๆ ถ้าหา bp ไม่เจอ
            }

            $chunks[] = trim(mb_substr($remaining, 0, $cutAt));
            $remaining = trim(mb_substr($remaining, $cutAt));
        }

        if ($remaining !== '') {
            $chunks[] = $remaining;
        }

        return array_values(array_filter($chunks, fn ($c) => trim($c) !== ''));
    }
}
