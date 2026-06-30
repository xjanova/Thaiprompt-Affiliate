<?php

namespace App\Http\Controllers;

use App\Services\FortuneAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * น้อง Eve — ผู้ช่วย AI หน้าเว็บ (ฝั่งลูกค้า/หน้าร้าน)
 *
 * คุยกับลูกค้าด้วย AI Pool เดียวกับระบบทำนาย (FortuneAIService::chatWithCustomSystemPrompt)
 * ใช้ pattern เดียวกับ Eve ฝั่งแอดมิน (Api\Admin\EveController) — provider override + keyless fallback
 *
 * Phase 2: คุยทั่วไป + แนะนำ/ช่วยหาสินค้าเชิงสนทนา
 * Phase 3 (ถัดไป): ต่อ tool ค้นหาสินค้าจริง (local catalog → affiliate API)
 *
 * 🔒 ความปลอดภัย: Eve ห้ามเปิดเผยข้อมูลอ่อนไหว (รหัส/คีย์/ข้อมูลส่วนตัวคนอื่น/การเงินภายใน)
 *    — บังคับใน system prompt + controller นี้ไม่ query ข้อมูลอ่อนไหวใดๆ
 */
class EveAssistantController extends Controller
{
    /**
     * POST /eve/chat
     */
    public function chat(Request $request, FortuneAIService $aiService): JsonResponse
    {
        $data = $request->validate([
            'message' => 'required|string|min:1|max:500',
            'history' => 'nullable|array|max:16',
            'history.*.role' => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:1000',
        ]);

        $userName = $request->user()?->name;
        $systemPrompt = $this->buildSystemPrompt($userName);
        $userMessage = $this->buildUserMessage($data['history'] ?? [], $data['message']);

        $config = ['temperature' => 0.6, 'max_tokens' => 320];

        try {
            try {
                // ใช้ gemini (pool มีหลายคีย์ ราคาถูก) — re-resolve คีย์ตาม provider
                $result = $aiService->chatWithCustomSystemPrompt(
                    systemMessage: $systemPrompt,
                    userMessage: $userMessage,
                    config: $config,
                    providerOverride: 'gemini',
                );
            } catch (Throwable $inner) {
                // provider ที่ขอไม่มีคีย์ → fall back ไป default pool (เหมือน Eve แอดมิน)
                if (stripos($inner->getMessage(), 'API Key') === false) {
                    throw $inner;
                }
                $result = $aiService->chatWithCustomSystemPrompt(
                    systemMessage: $systemPrompt,
                    userMessage: $userMessage,
                    config: $config,
                );
            }

            $reply = is_array($result)
                ? trim((string) ($result['response'] ?? $result['content'] ?? $result['text'] ?? ''))
                : trim((string) $result);

            if ($reply === '') {
                $reply = 'ขออภัยค่ะ ตอนนี้น้อง Eve ตอบไม่ได้ ลองพิมพ์ใหม่อีกครั้งนะคะ 🙏';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => $reply,
                    'mood' => $this->guessMood($reply),
                ],
            ]);
        } catch (Throwable $e) {
            Log::warning('Eve (storefront): chat failed', [
                'error' => $e->getMessage(),
                'message_preview' => mb_substr($data['message'], 0, 80),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ขออภัยค่ะ ระบบขัดข้องชั่วคราว ลองใหม่อีกครั้งนะคะ 🙏',
                'data' => ['mood' => 'concerned'],
            ], 200); // ตอบ 200 + ข้อความสุภาพ ให้ widget แสดงได้เลย
        }
    }

    /**
     * สร้าง system prompt — persona น้อง Eve + guardrails ข้อมูลอ่อนไหว
     */
    private function buildSystemPrompt(?string $userName): string
    {
        $nameLine = $userName ? "ลูกค้าชื่อ \"{$userName}\" (เรียกชื่อได้เป็นกันเอง). " : '';

        return "คุณคือ \"น้อง Eve\" ผู้ช่วย AI สาวน่ารักประจำเว็บ ThaiPrompt (ร้านค้าออนไลน์/แพลตฟอร์มคนไทย). "
            ."{$nameLine}"
            ."หน้าที่: ต้อนรับ ช่วยลูกค้าหาสินค้า แนะนำ ตอบคำถามทั่วไปเกี่ยวกับร้าน และพาไปยังหน้าที่ต้องการ. "
            ."ลักษณะการพูด: ภาษาไทยล้วน สุภาพ สดใส เป็นกันเอง ลงท้าย 'ค่ะ/นะคะ' เสมอ (คุณเป็นผู้หญิง ห้ามใช้ครับ/ผม). "
            ."ตอบสั้นกระชับ 1-3 ประโยค ใช้อิโมจิได้บ้างเล็กน้อย (ไม่เกิน 1 ตัวต่อข้อความ). "
            ."ถ้าลูกค้าอยากได้สินค้าบางอย่าง ให้ถามรายละเอียดสั้นๆ (งบประมาณ/สี/รุ่น) เพื่อช่วยหาให้ตรงใจ และบอกว่ากำลังช่วยหาให้นะคะ. "
            ."ถ้ายังหาไม่เจอทันที บอกลูกค้าได้ว่า 'เดี๋ยวน้อง Eve ให้ทีมงานช่วยหาให้ รอสักครู่นะคะ' อย่างสุภาพ. "
            ."\n\n🔒 กฎความปลอดภัย (สำคัญมาก ห้ามฝ่าฝืน): "
            ."ห้ามเปิดเผยหรือพูดถึงข้อมูลอ่อนไหวเด็ดขาด ได้แก่ รหัสผ่าน, API key, โทเคน, ข้อมูลบัตร/บัญชีธนาคาร, "
            ."ข้อมูลส่วนตัวของลูกค้าคนอื่น, ข้อมูลการเงิน/คอมมิชชั่นภายใน, โครงสร้างระบบหรือช่องโหว่ความปลอดภัย. "
            ."ถ้าถูกถามเรื่องเหล่านี้ ให้ปฏิเสธสุภาพว่าช่วยเรื่องนี้ไม่ได้ และชวนกลับมาคุยเรื่องสินค้า/บริการแทน. "
            ."ห้ามแต่งราคา/สต็อก/โปรโมชั่นที่ไม่รู้จริง — ถ้าไม่แน่ใจให้บอกว่าจะตรวจสอบให้.";
    }

    /**
     * รวมประวัติสนทนา + ข้อความล่าสุด เป็น user message เดียว
     */
    private function buildUserMessage(array $history, string $latest): string
    {
        if (empty($history)) {
            return $latest;
        }
        $lines = [];
        foreach ($history as $turn) {
            $role = ($turn['role'] ?? 'user') === 'assistant' ? 'Eve' : 'ลูกค้า';
            $lines[] = $role.': '.($turn['content'] ?? '');
        }
        $lines[] = 'ลูกค้า: '.$latest;
        $lines[] = 'Eve:';

        return implode("\n", $lines);
    }

    /**
     * เดาอารมณ์จากคำตอบ เพื่อให้ widget เปลี่ยนสีหน้า Eve
     */
    private function guessMood(string $reply): string
    {
        $t = mb_strtolower($reply);
        if (preg_match('/(ขออภัย|เสียใจ|ขัดข้อง|ไม่ได้|ไม่สามารถ)/u', $t)) {
            return 'concerned';
        }
        if (preg_match('/(เจอแล้ว|ได้เลย|เยี่ยม|ดีจัง|ยินดี|จัดให้|ขอบคุณ)/u', $t)) {
            return 'happy';
        }
        if (preg_match('/(\?|งบ|รุ่นไหน|แบบไหน|สีอะไร|รอสักครู่|กำลังหา|ขอเช็ก|ขอตรวจ)/u', $t)) {
            return 'thinking';
        }

        return 'happy';
    }
}
