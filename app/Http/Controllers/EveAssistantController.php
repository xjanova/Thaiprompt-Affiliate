<?php

namespace App\Http\Controllers;

use App\Models\EveProductWish;
use App\Models\Product;
use App\Services\FortuneAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
    /** จำนวนเทิร์นสูงสุดของประวัติสนทนาที่เก็บฝั่งเซิร์ฟเวอร์ (นับรวมทั้งลูกค้าและ Eve) */
    private const TRANSCRIPT_MAX_TURNS = 12;

    /** อายุประวัติสนทนาในแคช (วินาที) — ประมาณ 2 ชั่วโมง */
    private const TRANSCRIPT_TTL = 7200;

    /** ความยาวสูงสุดต่อ 1 เทิร์นที่เก็บลงแคช (กันแคชบวม) */
    private const TRANSCRIPT_MAX_CHARS = 600;

    /** เพดานจำนวนข้อความต่อวัน — ผู้ที่ไม่ได้ล็อกอิน (นับตาม IP) */
    private const DAILY_CAP_GUEST = 60;

    /** เพดานจำนวนข้อความต่อวัน — สมาชิกที่ล็อกอินแล้ว (นับตาม user id) */
    private const DAILY_CAP_MEMBER = 200;

    /**
     * 🚫 บัญชีดำ "ป้ายความเสี่ยงภายใน" ที่ห้ามหลุดถึงลูกค้าเด็ดขาด
     *
     * ป้ายเหล่านี้เป็นผลการประเมินลูกค้าของระบบ persona (ใช้ภายในเท่านั้น)
     * ถ้าโมเดลเผลอพ่นออกมาแม้แต่คำเดียว = ลูกค้าเห็นว่าเราแปะป้ายเขาไว้ = เหตุละเมิดความเป็นส่วนตัว
     */
    private const INTERNAL_LABELS = [
        'MENTAL_FRAGILE',
        'SCAM_VICTIM',
        'HOSTILE_SUPERIOR',
        'DISRUPTIVE_TROLL',
        'ABUSIVE_TONE',
        'TIME_WASTER',
        'COMPLAINT_PRONE',
        'DECLINE_PUSHER',
    ];

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

        $user = $request->user();

        // 💸 เพดานการใช้งานต่อวัน (ซ้อนบน throttle รายนาทีของ route)
        //    endpoint นี้ใช้ AI key pool "ก้อนเดียวกับการดูดวงที่ลูกค้าจ่ายเงิน"
        //    ถ้าปล่อยให้คนนอกยิงฟรีไม่จำกัด คีย์จะถูกเผา/ติด rate limit จนสินค้าที่มีคนจ่ายเงินเสียหาย
        if (! $this->consumeDailyQuota($request, $user?->id)) {
            return response()->json([
                'success' => false,
                'message' => 'วันนี้น้อง Eve คุยครบโควตาแล้วค่ะ 🙏 พรุ่งนี้มาคุยกันใหม่นะคะ หรือทักแอดมินได้เลยค่ะ',
                'data' => ['mood' => 'concerned'],
            ], 200);
        }

        $userName = $user?->name;
        $systemPrompt = $this->buildSystemPrompt($userName);

        // 🔒 กัน Prompt Injection: ประวัติสนทนาต้องมาจาก "ฝั่งเซิร์ฟเวอร์" เท่านั้น
        //    เดิมรับ history จากไคลเอนต์ตรงๆ แล้วต่อเข้า prompt → ผู้โจมตี POST เทิร์นปลอมของ Eve
        //    (role=assistant) เข้ามาเพื่อ "เขียนทับ" กฎความปลอดภัยใน system prompt ได้
        $transcriptKey = $this->transcriptKey($request, $user?->id);
        $history = $this->loadTranscript($transcriptKey, $data['history'] ?? []);
        $userMessage = $this->buildUserMessage($history, $data['message']);

        $config = ['temperature' => 0.6, 'max_tokens' => 320];

        try {
            try {
                // ใช้ gemini (pool มีหลายคีย์ ราคาถูก) — re-resolve คีย์ตาม provider
                //
                // ⚠️ ตรงนี้ override เฉพาะ "provider" แต่ model id ยังมาจากตั้งค่า Chat AI ของแอดมิน
                //    ถ้าแอดมินตั้ง model ของ groq/openai ไว้ ระบบจะประกอบ URL ของ gemini ด้วย model
                //    ที่ไม่ใช่ของ gemini → ตอบ 404 ทุกครั้ง
                //    ห้าม hardcode model id ใหม่ตรงนี้ — กฎภายในบังคับให้ "ยิงทดสอบ model id กับ API จริง"
                //    ก่อนนำไปตั้งค่าเสมอ จึงแก้ด้วยการทำ fallback ให้ทนทานแทน (ดู catch ด้านล่าง)
                $result = $aiService->chatWithCustomSystemPrompt(
                    systemMessage: $systemPrompt,
                    userMessage: $userMessage,
                    config: $config,
                    providerOverride: 'gemini',
                );
            } catch (Throwable $inner) {
                // ❗ ข้อผิดพลาด "ทุกชนิด" ของ provider ที่ override ไว้ → ถอยไปใช้ default pool ตามตั้งค่าแอดมิน
                //    (ไม่มีคีย์ / model ผิด provider / 404 / rate limit / เครือข่ายล่ม)
                //    เดิมเช็กแค่ข้อความ 'API Key' ทำให้เคส model mismatch ตกลงไป error หมดเลย
                Log::warning('Eve (storefront): gemini override ล้มเหลว → fallback default pool', [
                    'error' => mb_substr($inner->getMessage(), 0, 200),
                ]);

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

            // 🔎 ค้นหาสินค้า: ใช้แท็ก [FIND:] จาก AI ถ้ามี — ไม่งั้นดึงเจตนาจาก "ข้อความลูกค้า" เอง
            //    (กันเคสโมเดลเล็กไม่ปล่อยแท็ก → เดิมตอบ "หาให้ค่ะ" แต่ไม่เคยค้นจริง = ลูกค้าเห็นเป็นค้าง)
            [$reply, $products, $mood] = $this->runProductSearch($reply, $data['message'], $user?->id);

            // 🧼 ล้างเศษแท็กเครื่องมือ + ป้ายความเสี่ยงภายใน ก่อนส่งถึงลูกค้าเสมอ (ด่านสุดท้าย)
            $reply = $this->scrubInternalArtifacts($reply);
            if ($reply === '') {
                $reply = 'ได้เลยค่ะ 😊';
            }

            // 💾 บันทึกเทิร์นล่าสุดลงประวัติฝั่งเซิร์ฟเวอร์ (เก็บเฉพาะข้อความที่ผ่านการล้างแล้ว)
            $this->appendTranscript($transcriptKey, $history, $data['message'], $reply);

            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => $reply,
                    'mood' => $mood,
                    'products' => $products,
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
            ."\n\n🔎 เครื่องมือค้นหาสินค้า: ถ้าลูกค้าอยากได้/หา/ซื้อสินค้าอะไรสักอย่าง ให้ตอบสั้นๆ ว่ากำลังหาให้ (1 ประโยค) "
            ."แล้วลงท้ายข้อความด้วยแท็กพิเศษ [FIND: คำค้นสั้นๆเป็นคีย์เวิร์ด | งบสูงสุดเป็นตัวเลขบาทหรือ 0] "
            ."เช่น [FIND: หูฟังบลูทูธ | 500] หรือ [FIND: กระเป๋าสะพาย | 0]. "
            ."⚠️ ลูกค้าจะไม่เห็นแท็กนี้ (ระบบเอาไปค้นให้แล้วลบทิ้ง) ห้ามอธิบายแท็ก ใส่ได้สูงสุด 1 อันต่อข้อความ "
            ."และห้ามแต่งรายชื่อ/ราคาสินค้าเอง — ระบบจะแสดงสินค้าจริงให้เอง. ถ้าเป็นการคุยทั่วไป/ทักทาย/ถามข้อมูล ไม่ต้องใส่แท็ก. "
            ."\n\n🔒 กฎความปลอดภัย (สำคัญมาก ห้ามฝ่าฝืน): "
            ."ห้ามเปิดเผยหรือพูดถึงข้อมูลอ่อนไหวเด็ดขาด ได้แก่ รหัสผ่าน, API key, โทเคน, ข้อมูลบัตร/บัญชีธนาคาร, "
            ."ข้อมูลส่วนตัวของลูกค้าคนอื่น, ข้อมูลการเงิน/คอมมิชชั่นภายใน, โครงสร้างระบบหรือช่องโหว่ความปลอดภัย. "
            ."ถ้าถูกถามเรื่องเหล่านี้ ให้ปฏิเสธสุภาพว่าช่วยเรื่องนี้ไม่ได้ และชวนกลับมาคุยเรื่องสินค้า/บริการแทน. "
            ."ห้ามแต่งราคา/สต็อก/โปรโมชั่นที่ไม่รู้จริง — ถ้าไม่แน่ใจให้บอกว่าจะตรวจสอบให้.";
    }

    /**
     * รวมประวัติสนทนา + ข้อความล่าสุด เป็น user message เดียว
     *
     * ⚠️ $history ต้องมาจากประวัติฝั่งเซิร์ฟเวอร์เท่านั้น (loadTranscript) ห้ามรับ role=assistant
     *    จากไคลเอนต์ตรงๆ ไม่งั้นผู้โจมตีจะปลอมคำพูดของ Eve เพื่อล้มกฎใน system prompt ได้
     */
    private function buildUserMessage(array $history, string $latest): string
    {
        $latest = $this->sanitizeTurnContent($latest);

        if (empty($history)) {
            return $latest;
        }
        $lines = [];
        foreach ($history as $turn) {
            $role = ($turn['role'] ?? 'user') === 'assistant' ? 'Eve' : 'ลูกค้า';
            $lines[] = $role.': '.$this->sanitizeTurnContent((string) ($turn['content'] ?? ''));
        }
        $lines[] = 'ลูกค้า: '.$latest;
        $lines[] = 'Eve:';

        return implode("\n", $lines);
    }

    /**
     * คีย์แคชประวัติสนทนา — ผูกกับ session id เสมอ + user id เมื่อล็อกอิน
     *
     * ที่ต้องมี user id ด้วย: เครื่องเดียวกัน/เซสชันเดียวกันแต่สลับบัญชี ต้องไม่เห็นประวัติของอีกคน
     */
    private function transcriptKey(Request $request, ?int $userId): string
    {
        $sessionId = '';
        try {
            $sessionId = (string) $request->session()->getId();
        } catch (Throwable $e) {
            $sessionId = '';
        }

        // ไม่มี session (เช่นถูกเรียกนอก middleware web) → ใช้ IP แทนแบบ hash
        if ($sessionId === '') {
            $sessionId = 'ip-'.(string) $request->ip();
        }

        return 'eve:chat:transcript:'.($userId ? 'u'.$userId.':' : 'g:').sha1($sessionId);
    }

    /**
     * โหลดประวัติสนทนาฝั่งเซิร์ฟเวอร์
     *
     * ถ้าแคชว่าง (หมดอายุ/เพิ่งเปิดแชท) จะยอมรับประวัติจากไคลเอนต์ได้ "เฉพาะ role=user" เท่านั้น
     * เพราะข้อความของลูกค้าเองไม่มีอำนาจสั่งงานอยู่แล้ว (เท่ากับพิมพ์เข้ามาใหม่) —
     * แต่เทิร์นของ Eve (assistant) ห้ามรับจากไคลเอนต์เด็ดขาด
     *
     * @param  array<int,array>  $clientHistory
     * @return array<int,array{role:string,content:string}>
     */
    private function loadTranscript(string $key, array $clientHistory): array
    {
        try {
            $stored = Cache::get($key);
            if (is_array($stored) && ! empty($stored)) {
                return $this->boundTranscript($stored);
            }
        } catch (Throwable $e) {
            // แคชล่ม → ถือว่าไม่มีประวัติ (ไม่บล็อกการคุย)
        }

        $seed = [];
        foreach ($clientHistory as $turn) {
            if (! is_array($turn) || ($turn['role'] ?? '') !== 'user') {
                continue;   // 🔒 ทิ้งเทิร์น assistant ที่ไคลเอนต์ส่งมาทั้งหมด
            }
            $content = $this->sanitizeTurnContent((string) ($turn['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $seed[] = ['role' => 'user', 'content' => $content];
        }

        return $this->boundTranscript($seed);
    }

    /**
     * บันทึกเทิร์นล่าสุด (ลูกค้า + Eve) ลงประวัติฝั่งเซิร์ฟเวอร์ แบบจำกัดขนาด
     */
    private function appendTranscript(string $key, array $history, string $userMessage, string $reply): void
    {
        try {
            $history[] = ['role' => 'user', 'content' => $this->sanitizeTurnContent($userMessage)];
            $history[] = ['role' => 'assistant', 'content' => $this->sanitizeTurnContent($reply)];

            Cache::put($key, $this->boundTranscript($history), self::TRANSCRIPT_TTL);
        } catch (Throwable $e) {
            // best-effort — ประวัติหายได้ แต่ห้ามทำให้การตอบลูกค้าล้ม
        }
    }

    /**
     * ตัดประวัติให้เหลือไม่เกิน TRANSCRIPT_MAX_TURNS เทิร์นล่าสุด + ล้างรูปแบบให้ปลอดภัย
     *
     * @return array<int,array{role:string,content:string}>
     */
    private function boundTranscript(array $history): array
    {
        $clean = [];
        foreach ($history as $turn) {
            if (! is_array($turn)) {
                continue;
            }
            $content = $this->sanitizeTurnContent((string) ($turn['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $clean[] = [
                'role' => ($turn['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user',
                'content' => $content,
            ];
        }

        return array_slice($clean, -self::TRANSCRIPT_MAX_TURNS);
    }

    /**
     * ล้างเนื้อความ 1 เทิร์นก่อนนำไปต่อ prompt/เก็บแคช
     *
     * 🔒 ยุบขึ้นบรรทัดใหม่ให้เป็นช่องว่าง: กันลูกค้าพิมพ์ข้อความหลายบรรทัดในรูปแบบ
     *    "\nEve: (คำพูดปลอม)\nลูกค้า: ..." เพื่อปลอมบทสนทนาซ้อนเข้าไปใน prompt
     */
    private function sanitizeTurnContent(string $content): string
    {
        // ⚠️ ใช้หลักเดียวกับ scrubInternalArtifacts(): preg_replace ที่มี /u
        //    จะคืน null ถ้าเจอ UTF-8 เพี้ยน (เช่นลูกค้าวางข้อความจากที่อื่นมา)
        //    ถ้า cast เป็น (string) ตรงๆ จะกลายเป็น '' = ข้อความลูกค้าหายทั้งเทิร์น
        //    → เก็บของเดิมไว้ดีกว่าทำหาย
        $collapsed = preg_replace('/\s+/u', ' ', $content);
        $content = is_string($collapsed) ? $collapsed : $content;
        $content = trim($content);

        return mb_substr($content, 0, self::TRANSCRIPT_MAX_CHARS);
    }

    /**
     * 🧼 ล้างข้อความก่อนส่งถึงลูกค้า — เศษแท็กเครื่องมือ + ป้ายความเสี่ยงภายใน
     *
     * ทำไมต้องมี: ป้ายอย่าง MENTAL_FRAGILE / SCAM_VICTIM / DISRUPTIVE_TROLL ฯลฯ
     * เป็น "ป้ายประเมินความเสี่ยงภายใน" ของระบบ persona ไม่ใช่ข้อความสำหรับลูกค้า
     * ถ้าหลุดออกไปแม้แต่คำเดียว ลูกค้าจะเห็นว่าเราแปะป้ายตัดสินเขาไว้ = เหตุละเมิดความเป็นส่วนตัวทันที
     * เช่นเดียวกับแท็ก [FIND: ...] / [REMEMBER: ...] ที่เป็นกลไกภายใน ลูกค้าไม่ควรเห็น
     */
    private function scrubInternalArtifacts(string $reply): string
    {
        // ถ้า regex ล้ม (เช่นข้อความจากโมเดลมี UTF-8 เพี้ยน) preg_replace คืน null →
        // ต้องคงข้อความเดิมไว้ ห้าม cast เป็น '' ไม่งั้นคำตอบของลูกค้าหายทั้งก้อน
        $apply = static function (string $pattern, string $replacement, string $subject): string {
            $out = preg_replace($pattern, $replacement, $subject);

            return is_string($out) ? $out : $subject;
        };

        // 1) แท็กเครื่องมือแบบปิดวงเล็บครบ
        $reply = $apply('/\[\s*(?:FIND|REMEMBER)\s*:[^\]]*\]/iu', ' ', $reply);
        // 2) เศษแท็กที่ปิดวงเล็บไม่ครบ — ลบเฉพาะ "หัวแท็ก" พอ (ไม่กินเนื้อความที่เหลือ)
        $reply = $apply('/\[\s*(?:FIND|REMEMBER)\s*:?\s*/iu', ' ', $reply);

        // 3) ป้ายความเสี่ยงภายใน (ห้ามหลุดถึงลูกค้า)
        foreach (self::INTERNAL_LABELS as $label) {
            $reply = $apply('/\b'.preg_quote($label, '/').'\b/i', ' ', $reply);
        }

        // 4) เก็บกวาดวงเล็บ/ช่องว่างที่เหลือค้าง
        $reply = $apply('/\[\s*\]/u', ' ', $reply);
        $reply = $apply('/[ \t]{2,}/u', ' ', $reply);
        $reply = $apply('/\n{3,}/u', "\n\n", $reply);

        return trim($reply);
    }

    /**
     * นับ/ตรวจโควตาการใช้งานต่อวัน — คืน false เมื่อใช้ครบแล้ว
     *
     * นับตาม user id เมื่อล็อกอิน ไม่งั้นนับตาม IP (hash) · หมดอายุอัตโนมัติสิ้นวัน
     */
    private function consumeDailyQuota(Request $request, ?int $userId): bool
    {
        $limit = $userId ? self::DAILY_CAP_MEMBER : self::DAILY_CAP_GUEST;
        $who = $userId ? 'u'.$userId : 'ip-'.sha1((string) $request->ip());
        $key = 'eve:chat:daily:'.$who.':'.now()->format('Ymd');

        try {
            Cache::add($key, 0, now()->endOfDay());
            $used = (int) Cache::increment($key);

            return $used <= $limit;
        } catch (Throwable $e) {
            // แคชล่ม = ไม่บล็อกลูกค้า (throttle รายนาทีของ route ยังทำงานอยู่)
            return true;
        }
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

    /**
     * ตรวจแท็ก [FIND: คำค้น | งบ] จากคำตอบ AI → ค้นสินค้าจริง → คืน [reply, products, mood]
     */
    private function runProductSearch(string $reply, string $userMessage, ?int $userId): array
    {
        // ดึงคำค้น + งบจากแท็ก (ทนทานต่อ noise: "500 บาท", "1,500", "~500", case-insensitive, เว้นวรรครอบ :|)
        $query = null;
        $budget = null;
        if (preg_match('/\[\s*FIND\s*:\s*([^\]|]+?)\s*(?:\|\s*([^\]]*?))?\s*\]/iu', $reply, $m)) {
            $query = trim($m[1]);
            if (isset($m[2])) {
                $num = preg_replace('/[^0-9.]/', '', $m[2]);   // '500 บาท'/'1,500' → 500/1500 · 'ไม่จำกัด' → ''
                $budget = ($num !== '' && (float) $num > 0) ? (float) $num : null;
            }
        }

        // ⚠️ ลบแท็ก [FIND...] "เสมอ" ก่อน return — ไม่ว่าจะ parse งบได้หรือไม่ กันแท็กดิบหลุดให้ลูกค้าเห็น
        $reply = trim((string) preg_replace('/\[\s*FIND\s*:[^\]]*\]/iu', '', $reply));

        // ⭐ Fallback กันค้าง: ถ้า AI ไม่ปล่อยแท็ก แต่ลูกค้าสื่อว่าอยากได้/หาสินค้า
        //    → ดึงคำค้นจาก "ข้อความลูกค้า" เองแล้วค้นให้เสมอ (โมเดลเล็กมักไม่ใส่แท็ก จึงต้องไม่พึ่งมัน)
        if ($query === null || $query === '') {
            [$intentQuery, $intentBudget] = $this->extractSearchIntent($userMessage);
            if ($intentQuery !== null) {
                $query = $intentQuery;
                if ($budget === null) {
                    $budget = $intentBudget;
                }
            }
        }

        $products = [];
        $mood = $this->guessMood($reply);

        if ($query !== null && $query !== '') {
            {
                $products = $this->searchCatalog($query, $budget);

                if (empty($products)) {
                    $this->recordWish($query, $budget, $userId);
                    // 🙏 จริงใจ + ชวนปรับคำค้น (เลี่ยงสัญญา "รอ 10 นาที" ที่ยังไม่มีระบบส่งผลกลับจริง = ค้าง)
                    $reply = trim($reply."\n\nตอนนี้ยังไม่เจอสินค้านี้ในร้านค่ะ 🙏 ลองบอกยี่ห้อ/รุ่น หรือพิมพ์คำค้นอื่นดูได้นะคะ เดี๋ยว Eve หาให้ใหม่ค่ะ");
                    $mood = 'thinking';
                } else {
                    $reply = trim($reply."\n\nเจอ ".count($products)." รายการที่น่าจะใช่ค่ะ 😊 ลองดูเลยนะคะ — ถ้าใช่กด \"ดูสินค้า\" ได้เลย หรืออยากให้หาแบบอื่นก็บอกได้ค่ะ");
                    $mood = 'happy';
                }
            }
        }

        if ($reply === '') {
            $reply = 'ได้เลยค่ะ 😊';
        }

        return [$reply, $products, $mood];
    }

    /**
     * ดึง "เจตนาหาสินค้า + คำค้น + งบ" จากข้อความลูกค้าเอง (ไม่พึ่งแท็ก [FIND:] ของ AI)
     *
     * ⚠️ เหตุผล: โมเดล chat เล็ก (เช่น gemini-flash-lite) มักไม่ปล่อยแท็ก →
     *    เดิม Eve ตอบ "กำลังหาให้ค่ะ" แต่ไม่เคยค้นจริง → ลูกค้าเห็นเป็น "ค้างไปเลย"
     *    เมธอดนี้การันตีว่าถ้าลูกค้าสื่อว่าอยากได้ของ ระบบจะค้น catalog ให้เสมอ
     *
     * 🇹🇭 ความปลอดภัยภาษาไทย: ตัดคำสั่ง/คำเติม "เฉพาะหัว/ท้ายประโยค" (anchored ^ $) เท่านั้น
     *    ห้าม str_replace ทั้งสตริง — "หา" เป็น substring ของ "อาหาร", "มี" ของ "มีด", "ขอ" ของ "ของ"
     *    จึงไม่ใส่ มี/ขอ/ดู ในรายการตัดหัว (อันตราย) — ใช้คำประสมที่ชัดแทน เช่น "ขอดู"
     *
     * @return array{0: ?string, 1: ?float} [คำค้น (null = ไม่ใช่การหาสินค้า), งบ]
     */
    private function extractSearchIntent(string $message): array
    {
        $q = trim((string) preg_replace('/\s+/u', ' ', $message));
        if ($q === '') {
            return [null, null];
        }

        // ต้องมีสัญญาณว่ากำลังหา/อยากได้สินค้า ไม่งั้นถือเป็นคุยเล่น/ทักทาย (ไม่ค้น)
        if (! preg_match('/(หา|อยากได้|อยากซื้อ|หาซื้อ|ซื้อ|ขาย|มีขาย|มี.{0,8}(ไหม|มั้ย|ป่าว|รึเปล่า)|แนะนำ|อยากหา|ช่วยหา|ขอดู|เอา|สั่ง|รุ่นไหน|ยี่ห้อ|ราคา|งบ)/u', $q)) {
            return [null, null];
        }

        // งบประมาณ: "งบ 500", "ไม่เกิน 500", "ราคา 500", "500 บาท"
        $budget = null;
        if (preg_match('/(?:งบ|ไม่เกิน|ภายใน|ไม่ถึง|ราคา)\s*([0-9][0-9,.]*)/u', $q, $b)
            || preg_match('/([0-9][0-9,.]*)\s*บาท/u', $q, $b)) {
            $num = (float) preg_replace('/[^0-9.]/', '', $b[1]);
            $budget = $num > 0 ? $num : null;
        }

        // ตัดวลีงบ/ราคา (มีตัวเลข = ปลอดภัย ไม่ทับชื่อสินค้า)
        $q = preg_replace('/(?:งบ|ไม่เกิน|ภายใน|ไม่ถึง|ราคาไม่เกิน|ราคา)\s*[0-9][0-9,.]*\s*(?:บาท)?/u', ' ', $q);
        $q = preg_replace('/[0-9][0-9,.]*\s*บาท/u', ' ', $q);

        // ตัดคำสั่ง "หัวประโยค" (anchored ^) ทีละชั้น — คำประสมที่ยาว/ชัดก่อน
        $q = preg_replace('/^\s*(ช่วย|รบกวน)\s*/u', '', (string) $q);
        $q = preg_replace('/^\s*(ขอดู|อยากได้|อยากซื้อ|อยากหา|หาซื้อ|หาให้|ช่วยหา|แนะนำ|สั่งซื้อ|หา|ซื้อ|สั่ง|เอา)\s*/u', '', (string) $q);

        // ตัดคำลงท้าย/คำถาม "ท้ายประโยค" (anchored $)
        $q = preg_replace('/\s*(ราคาถูกๆ|ราคาถูก|ราคาประหยัด|เท่าไหร่|เท่าไร|ถูกๆ|ถูก|ดีๆ|สวยๆ|หน่อยค่ะ|หน่อยครับ|หน่อย|ด้วยค่ะ|ด้วยครับ|ด้วย|ให้หน่อย|ให้ที|ทีนะ|นะคะ|นะครับ|จ้า|ค่ะ|คะ|ครับ|มีไหม|มีมั้ย|ไหม|มั้ย|ป่าว|รึเปล่า|บ้าง|อะ|อ่ะ)+\s*$/u', '', (string) $q);

        $q = trim((string) preg_replace('/\s+/u', ' ', (string) $q));

        // เหลือสั้นไป หรือเหลือแต่คำถามล้วน → ไม่ค้น (กันค้นมั่ว)
        if (mb_strlen($q) < 2 || preg_match('/^(อะไร|เท่าไหร่|เท่าไร|ยังไง|ที่ไหน|อันไหน|แบบไหน|ไง|ดี|มี|ของ|ราคา)$/u', $q)) {
            return [null, null];
        }

        return [$q, $budget];
    }

    /**
     * ค้นสินค้าใน catalog ของเรา (ตาราง products ที่เผยแพร่ขายได้จริง) — ข้อมูลสาธารณะเท่านั้น
     *
     * @return array<int,array>
     */
    private function searchCatalog(string $query, ?float $budget): array
    {
        $tokens = collect(preg_split('/\s+/u', trim($query)) ?: [])
            ->map(fn ($t) => trim($t))
            ->filter(fn ($t) => mb_strlen($t) >= 2)
            ->take(6)
            ->values();

        if ($tokens->isEmpty()) {
            return [];
        }

        try {
            // ใช้ scope กลาง publicVisible() (active + visible + notBlocked) ให้ตรงกับหน้าร้านอื่นๆ
            // ⚠️ คง is_public_approved ไว้ต่างหาก — เป็นด่านอนุมัติสินค้าของผู้ขาย (VendorPublicProduct)
            //    ซึ่งไม่ได้อยู่ใน publicVisible() ถ้าตัดทิ้งสินค้าที่ยังไม่อนุมัติจะโผล่ให้ลูกค้าเห็น
            $q = Product::query()
                ->publicVisible()
                ->where('is_public_approved', true);

            if ($budget && $budget > 0) {
                $q->where('price', '<=', $budget);
            }

            $q->where(function ($w) use ($tokens) {
                foreach ($tokens as $t) {
                    $w->orWhere('name', 'like', "%{$t}%")
                        ->orWhere('brand', 'like', "%{$t}%")
                        ->orWhere('short_description', 'like', "%{$t}%");
                }
            });

            return $q->orderByDesc('is_featured')
                ->orderByDesc('sales_count')
                ->limit(6)
                ->get(['id', 'name', 'slug', 'price', 'main_image_url', 'is_affiliate', 'affiliate_url', 'external_platform'])
                ->map(function (Product $p) {
                    // 💰 สินค้าแอฟฟิลิเอต (เช่น Lazada) เราไม่ได้ส่งของเอง —
                    //    ถ้าลิงก์เข้าหน้าตะกร้าภายในจะ "ไม่ได้ค่าคอมเลย" และลูกค้าสั่งซื้อไม่ได้จริง
                    //    จึงต้องส่งลิงก์แอฟฟิลิเอตออกไปข้างนอกแทน
                    $affiliateUrl = trim((string) $p->affiliate_url);

                    // รับเฉพาะ http/https (กันค่าแปลกปลอมในคอลัมน์กลายเป็นลิงก์อันตราย เช่น javascript:)
                    $isExternal = (bool) $p->is_affiliate
                        && $affiliateUrl !== ''
                        && preg_match('#^https?://#i', $affiliateUrl) === 1;

                    return [
                        'name' => $p->name,
                        'price' => (float) $p->price,
                        'image' => $p->main_image_url,
                        'url' => $isExternal
                            ? $affiliateUrl
                            : ($p->slug ? route('shop.show', $p->slug) : url('/storefront')),
                        'external' => $isExternal,
                        'platform' => $isExternal ? ($p->external_platform ?: 'affiliate') : null,
                    ];
                })
                ->all();
        } catch (Throwable $e) {
            Log::warning('Eve: searchCatalog failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * บันทึก "ของที่ลูกค้าอยากได้แต่ยังไม่มี" — best-effort (ตารางอาจยังไม่ migrate)
     */
    private function recordWish(string $query, ?float $budget, ?int $userId): void
    {
        try {
            if (Schema::hasTable('eve_product_wishes')) {
                EveProductWish::create([
                    'user_id' => $userId,
                    'query' => mb_substr($query, 0, 255),
                    'budget' => $budget,
                    'results_found' => 0,
                    'status' => 'pending',
                    'source' => 'eve_chat',
                ]);
            }
        } catch (Throwable $e) {
            // best-effort — ไม่บล็อกการตอบ
        }
    }
}
