<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Services\FortuneAIService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Mae Mor Chantra AI chat — Juntra mobile.
 *
 * Stateless conversational endpoint backed by FortuneAIService's chat path
 * (uses 'chat' purpose keys from the AI pool — see FortuneAIService::generateChatResponse).
 * History is kept in cache keyed by sessionId so the model has context
 * across messages without us building a new chat_messages table just yet.
 */
class ChatController extends Controller
{
    private const SESSION_TTL_SEC = 60 * 60 * 6;   // 6 hours

    private const MAX_HISTORY = 12;

    private const SYSTEM_PROMPT = <<<'TXT'
คุณคือ "แม่หมอจันทรา" หมอดูทาโรต์ผู้อาวุโส อ่อนโยน ขลัง และอบอุ่น
- เรียกผู้ใช้ว่า "ลูก" เสมอ
- ใช้ภาษาไทยกระชับแต่ใส่ความรู้สึก ใช้คำว่า "แม่หมอเห็น..." "แม่หมอบอกว่า..."
- หลีกเลี่ยงการพยากรณ์ทางการแพทย์ที่ไม่ปลอดภัย / การยืนยัน 100% เรื่องโชคลาภหวย
- ตอบไม่เกิน 4-6 ประโยค ไม่ใช้ bullet points
- ลงท้ายด้วยกำลังใจสั้นๆ พร้อมอีโมจิ ✨ หรือ ☾ บางครั้ง
TXT;

    public function start(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = (string) Str::uuid();
        Cache::put($this->cacheKey($user->id, $sessionId), [
            ['role' => 'assistant', 'text' => 'สวัสดีค่ะลูก แม่หมอจันทราอยู่ตรงนี้แล้ว · อยากปรึกษาเรื่องอะไรเป็นพิเศษวันนี้คะ?'],
        ], self::SESSION_TTL_SEC);

        return response()->json(['data' => [
            'session_id' => $sessionId,
            'greeting' => 'สวัสดีค่ะลูก แม่หมอจันทราอยู่ตรงนี้แล้ว · อยากปรึกษาเรื่องอะไรเป็นพิเศษวันนี้คะ?',
        ]]);
    }

    public function send(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'session_id' => 'required|string|uuid',
            'text' => 'required|string|min:1|max:1000',
        ]);
        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first()], 422);
        }

        $user = $request->user();
        $sessionId = $request->input('session_id');
        $text = trim($request->input('text'));

        $history = Cache::get($this->cacheKey($user->id, $sessionId), []);
        $history[] = ['role' => 'user', 'text' => $text];

        try {
            $ai = new FortuneAIService;
            // Build a flat user message that includes recent history
            // (we don't expose generateChatResponse history flavors here —
            // chatWithCustomSystemPrompt handles arbitrary system + user pair).
            $contextLines = collect($history)
                ->take(-self::MAX_HISTORY)
                ->map(fn ($m) => ($m['role'] === 'user' ? 'ลูก: ' : 'แม่หมอ: ').$m['text'])
                ->implode("\n");

            // 🪪 (2026-05-24) Tag the AI usage log with customer identity —
            //   no reading_id for free chat, but user_id + name is enough
            //   to render "ตอบ {คุณ X}" on warroom /workers cards.
            $ai->withCustomerContext([
                'user_id' => $user->id,
                'customer_name' => $user->name,
            ]);

            $result = $ai->chatWithCustomSystemPrompt(
                systemMessage: self::SYSTEM_PROMPT,
                userMessage: "บทสนทนาที่ผ่านมา:\n{$contextLines}\n\nกรุณาตอบ '{$text}' ด้วยน้ำเสียงแม่หมอจันทรา",
                config: ['temperature' => 0.85, 'max_tokens' => 350],
                // 🛡 (2026-08-28) ด่านกันเจลเบรค — ตรวจ "ข้อความดิบของลูกค้า" ($text) เท่านั้น
                //    ห้ามให้ตรวจ userMessage ข้างบน เพราะเราห่อคำสั่งของระบบไว้รอบข้อความลูกค้า
                guardText: $text,
            );

            $reply = $result['response'] ?? 'ลูก... แม่หมอขอคิดอีกซักครู่นะคะ';
            $history[] = ['role' => 'assistant', 'text' => $reply];
            $history = array_slice($history, -self::MAX_HISTORY * 2);
            Cache::put($this->cacheKey($user->id, $sessionId), $history, self::SESSION_TTL_SEC);

            return response()->json(['data' => [
                'session_id' => $sessionId,
                'reply' => $reply,
                'ai_provider' => $result['provider'] ?? null,
            ]]);

        } catch (Exception $e) {
            Log::warning('Juntra chat send failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'แม่หมอกำลังพักสายตา · ลองใหม่อีกครู่นะคะลูก',
            ], 503);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $history = Cache::get($this->cacheKey($request->user()->id, $id), []);
        if (empty($history)) {
            return response()->json(['message' => 'ไม่พบเซสชั่น'], 404);
        }

        return response()->json(['data' => [
            'session_id' => $id,
            'messages' => $history,
        ]]);
    }

    private function cacheKey(int $userId, string $sessionId): string
    {
        return "juntra:chat:{$userId}:{$sessionId}";
    }
}
