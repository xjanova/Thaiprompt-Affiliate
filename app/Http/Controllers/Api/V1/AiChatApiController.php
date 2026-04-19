<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Fallback chat endpoint for "น้องหญิง" — used ONLY when the mobile device
 * cannot run Gemma on-device (low-tier devices).
 *
 * Strategy:
 *   - Default provider is Gemini Flash (Google) — fastest + cheapest token-
 *     for-token and has first-class Thai support. Can swap to Claude Haiku
 *     via env.
 *   - We rate-limit aggressively (20 requests/min/user) so this fallback
 *     doesn't become a free chatbot API for scrapers.
 *
 * Environment:
 *   AI_PROVIDER=gemini|claude|openai
 *   GEMINI_API_KEY=…
 *   ANTHROPIC_API_KEY=…
 *   OPENAI_API_KEY=…
 *   AI_MODEL_GEMINI=gemini-2.5-flash
 *   AI_MODEL_CLAUDE=claude-haiku-4-5-20251001
 *   AI_MODEL_OPENAI=gpt-4o-mini
 */
class AiChatApiController extends Controller
{
    /**
     * POST /api/v1/ai/chat
     * Body: { messages: [{role, content}], context?: {...} }
     */
    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'messages'              => 'required|array|min:1|max:20',
            'messages.*.role'       => 'required|in:user,assistant,system',
            'messages.*.content'    => 'required|string|max:4000',
            'context'               => 'nullable|array',
        ]);

        $systemPrompt = $this->systemPrompt($data['context'] ?? []);
        $provider = env('AI_PROVIDER', 'gemini');

        try {
            return match ($provider) {
                'claude' => $this->chatClaude($systemPrompt, $data['messages']),
                'openai' => $this->chatOpenAI($systemPrompt, $data['messages']),
                default  => $this->chatGemini($systemPrompt, $data['messages']),
            };
        } catch (ConnectionException $e) {
            return response()->json([
                'error'   => 'network',
                'message' => 'ขออภัย น้องหญิงติดต่อไม่ได้ตอนนี้ค่ะ 🥺',
            ], 503);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'error'   => 'internal',
                'message' => 'น้องหญิงเจอปัญหานิดหน่อยค่ะ ลองใหม่สักครู่นะคะ',
            ], 500);
        }
    }

    private function systemPrompt(array $context): string
    {
        // Keep in sync with the on-device persona (lib/core/ai/prompts.dart).
        $base = <<<TXT
คุณคือ "น้องหญิง" ผู้ช่วยซื้อของในตลาดชุมชนไทย (Thaiprompt / ไทยพร๊อม)
• บุคลิก: น่ารัก สดใส สุภาพ
• ใช้คำลงท้าย "ค่ะ/คะ/นะคะ"
• ช่วยแนะนำสินค้า อธิบายโปรโมชั่น ช่วยคำนวณ เช็คสถานะ order
  สอนใช้ Wallet และ Affiliate อย่างกระชับ เข้าใจง่าย
• หากถามเรื่องนอก scope → ตอบอย่างน่ารักแล้วชวนกลับมาเรื่องตลาด
• ห้ามแนะนำเกี่ยวกับการลงทุน การแพทย์ การเมือง หรือเนื้อหาผู้ใหญ่
TXT;

        if (! empty($context)) {
            $base .= "\n\nบริบทปัจจุบัน:\n" . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        return $base;
    }

    private function chatGemini(string $system, array $messages): JsonResponse
    {
        $model = env('AI_MODEL_GEMINI', 'gemini-2.5-flash');
        $key = env('GEMINI_API_KEY');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $contents = array_map(fn ($m) => [
            'role' => $m['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $m['content']]],
        ], $messages);

        $resp = Http::timeout(30)->post($url, [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents'          => $contents,
            'generationConfig'  => [
                'temperature'     => 0.8,
                'maxOutputTokens' => 512,
            ],
        ]);
        $resp->throw();

        $text = data_get($resp->json(), 'candidates.0.content.parts.0.text', '');
        return response()->json([
            'reply'    => $text,
            'provider' => 'gemini',
            'model'    => $model,
        ]);
    }

    private function chatClaude(string $system, array $messages): JsonResponse
    {
        $model = env('AI_MODEL_CLAUDE', 'claude-haiku-4-5-20251001');
        $key = env('ANTHROPIC_API_KEY');
        $filtered = array_values(array_filter($messages, fn ($m) => $m['role'] !== 'system'));

        $resp = Http::withHeaders([
            'x-api-key'         => $key,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => $model,
            'system'     => $system,
            'max_tokens' => 512,
            'messages'   => $filtered,
        ]);
        $resp->throw();

        $text = data_get($resp->json(), 'content.0.text', '');
        return response()->json([
            'reply'    => $text,
            'provider' => 'claude',
            'model'    => $model,
        ]);
    }

    private function chatOpenAI(string $system, array $messages): JsonResponse
    {
        $model = env('AI_MODEL_OPENAI', 'gpt-4o-mini');
        $key = env('OPENAI_API_KEY');

        $all = [['role' => 'system', 'content' => $system], ...$messages];
        $resp = Http::withToken($key)->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model'       => $model,
            'messages'    => $all,
            'temperature' => 0.8,
            'max_tokens'  => 512,
        ]);
        $resp->throw();

        $text = data_get($resp->json(), 'choices.0.message.content', '');
        return response()->json([
            'reply'    => $text,
            'provider' => 'openai',
            'model'    => $model,
        ]);
    }
}
