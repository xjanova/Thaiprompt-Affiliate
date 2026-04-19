<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

/**
 * Proxies text-to-speech requests to Gemini 3.1 Native Audio.
 *
 * Why proxy rather than let the mobile client call Gemini directly:
 *   • keeps our API key server-side (never shipped in APK)
 *   • single rate-limit bucket (20 req/min per user via throttle middleware)
 *   • lets us swap providers (Gemini ↔ Piper server ↔ Azure) without
 *     shipping a new client
 *
 * POST /api/v1/ai/tts
 *   {
 *     "text":   "สวัสดีค่ะ",       // 1..2000 chars
 *     "voice":  "th-premwadee",   // optional; defaults to female Thai
 *     "format": "mp3"             // mp3 | wav | ogg
 *   }
 * → 200 Content-Type: audio/mpeg (binary bytes)
 *
 * Environment:
 *   GEMINI_API_KEY=...              (shared with AiChatApiController)
 *   AI_TTS_MODEL=gemini-2.5-flash-preview-tts  (override per deploy)
 */
class AiTtsApiController extends Controller
{
    private const ALLOWED_FORMATS = ['mp3', 'wav', 'ogg'];

    /**
     * Female voices ONLY — "น้องหญิง" is a female persona. Do not add male
     * entries; product rule, enforced here server-side so even a compromised
     * client cannot request a male voice.
     */
    private const THAI_VOICES = [
        'th-premwadee' => 'Aoede',        // warm female · default
        'th-achara'    => 'Callirrhoe',   // gentler female alt
    ];

    public function speak(Request $request)
    {
        $data = $request->validate([
            'text'   => 'required|string|min:1|max:2000',
            'voice'  => 'nullable|string|max:32',
            'format' => 'nullable|in:mp3,wav,ogg',
        ]);

        $voiceKey = $data['voice'] ?? 'th-premwadee';
        $geminiVoice = self::THAI_VOICES[$voiceKey] ?? self::THAI_VOICES['th-premwadee'];
        $format = $data['format'] ?? 'mp3';

        $apiKey = env('GEMINI_API_KEY');
        if (! $apiKey) {
            return response()->json([
                'error'   => 'tts_unavailable',
                'message' => 'ยังไม่ได้ตั้งค่า TTS ค่ะ',
            ], 503);
        }

        $model = env('AI_TTS_MODEL', 'gemini-2.5-flash-preview-tts');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        try {
            $resp = Http::timeout(20)->post($url, [
                'contents' => [[
                    'parts' => [['text' => $data['text']]],
                ]],
                'generationConfig' => [
                    'responseModalities' => ['AUDIO'],
                    'speechConfig' => [
                        'voiceConfig' => [
                            'prebuiltVoiceConfig' => [
                                'voiceName' => $geminiVoice,
                            ],
                        ],
                    ],
                ],
            ]);
            $resp->throw();

            $audioB64 = data_get($resp->json(), 'candidates.0.content.parts.0.inlineData.data');
            if (! $audioB64) {
                return response()->json([
                    'error'   => 'tts_empty',
                    'message' => 'น้องพูดไม่ออกค่ะ 🥺',
                ], 502);
            }

            $bytes = base64_decode($audioB64);
            $mime = match ($format) {
                'wav' => 'audio/wav',
                'ogg' => 'audio/ogg',
                default => 'audio/mpeg',
            };

            return response($bytes, 200, [
                'Content-Type'   => $mime,
                'Cache-Control'  => 'private, max-age=300',
                'X-Voice'        => $voiceKey,
            ]);
        } catch (ConnectionException $e) {
            return response()->json([
                'error'   => 'network',
                'message' => 'เน็ตไม่ดีค่ะ ลองใหม่นะคะ',
            ], 503);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'error'   => 'internal',
                'message' => 'น้องเจอปัญหานิดนึงค่ะ',
            ], 500);
        }
    }
}
