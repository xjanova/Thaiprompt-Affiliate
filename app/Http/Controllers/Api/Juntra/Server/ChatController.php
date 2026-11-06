<?php

namespace App\Http\Controllers\Api\Juntra\Server;

use App\Http\Controllers\Controller;
use App\Services\Fortune\FortuneLiveActivity;
use App\Services\Fortune\JuntraChatService;
use App\Services\Fortune\JuntraRegularGreetingService;
use App\Services\Fortune\WebChatContinuityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 🌙 (2026-09-15) แชทแม่หมอให้เว็บ จันทรา.online ด้วยตัวตนของเว็บเอง (juntra.server)
 *
 * ทำไม: ทางเดิม (/api/v1/juntra/chat/mae-mor) ต้องใช้ token ของลูกค้าที่ล็อกอินผ่าน Thaiprompt —
 * ลูกค้าที่สมัครด้วยเบอร์/อีเมลจึงคุยไม่ได้เลย (บน production: สมาชิก 4 คน มี token 1 คน,
 * 60 วันไม่มีข้อความจากลูกค้าสักข้อความ) เจ้าของสั่ง: ทุกคนที่ล็อกอินคุยได้ และคุยฟรีเหมือนบอท
 * ใน FB/LINE จนกว่าจะเริ่มการทำนาย
 *
 * ต่างจากทางเดิม:
 *   - เจ้าของห้อง = ลูกค้าฝั่งเว็บ (user_ref) — ประวัติแยกต่อคน ไม่ปนกัน
 *   - แม่หมอไม่ทำนายเองในแชท ชวนเลือกแพ็กเกจเปิดไพ่แทน (kind=offer + offer_topic)
 *   - AI เงียบ = 503 ai_unavailable (ไม่ส่งข้อความรองรับที่หน้าตาเหมือนคำตอบจริง)
 *   - 💬 (2026-09-21) `context` (ไม่บังคับ) = คำพยากรณ์ที่ห้องนี้คุยต่อ — อยู่ใน system message ทุกรอบ
 *   - 🌙 (2026-11-03 FB web-only) `thaiprompt_user_id` + `handoff` (ไม่บังคับ) = ลูกค้าที่ผูกกับแชทเพจ FB
 *     แม่หมอได้ "ความจำ" (WebChatContinuityService) วางใน system message เท่านั้น
 *     ความจำไม่ถูกส่งกลับไปที่เว็บ — ตอบแค่ continued_from / customer.display_name / returning
 *     💬 (2026-11-04 เจ้าของสั่ง) ความจำ = RPG/อุปนิสัย + ประวัติเปิดไพ่ เท่านั้น (ไม่มีแชทเพจ/เรื่องที่ทักมา)
 *        ใช้ได้ทุกห้องที่ลูกค้าเปิด ไม่ต้องมาจากลิงก์ handoff · ธง facebook_web_only ปิด = ห้องแบบเดิมทุกอย่าง
 *   - 🎭 (2026-11-03) `persona` = maemor | busaba | kunsai (ไม่ส่ง = maemor · ไม่รู้จัก = 422) ล็อกตั้งแต่ start
 *   - 🔈 (2026-11-04) `voice_mode` + `input_mode` + `voice_clips` (ไม่บังคับ) = โหมดคุยด้วยเสียง → ตอบ `voice_plan`
 *     {source, pre, say, say_parts, post} ให้เว็บเล่นคลิปคลัง → (เสียงใหม่ผ่าน /voice/speak) → คลิปคลัง
 *   - 🎬 (2026-11-04) `POST /chat/greeting` คำทักทายเฉพาะตัวของลูกค้าคุ้นเคย (เว็บนับบิลเอง ≥ 6)
 */
class ChatController extends Controller
{
    public function __construct(
        private JuntraChatService $chat,
        private WebChatContinuityService $continuity,
    ) {}

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_ref' => 'required|string|max:64|regex:/^[A-Za-z0-9_-]+$/',
            // 💬 (2026-09-21) ห้องที่คุยต่อจากคำพยากรณ์ที่ลูกค้าจ่ายแล้ว — ไพ่ทุกใบ + คำพยากรณ์ฉบับเต็ม
            //    ไม่ส่ง = ห้องคุยทั่วไปแบบเดิม (เว็บรุ่นก่อนหน้าไม่รู้จักฟิลด์นี้)
            //    รับเฉพาะทางเซิร์ฟเวอร์ของเว็บ — ทาง token ของลูกค้าไม่รับ เพราะบริบทนี้ไปอยู่ใน system message
            'context' => 'sometimes|nullable|string|max:'.JuntraChatService::MAX_CONTEXT_CHARS,
            // 🌙 (2026-11-03) users.id ฝั่ง Thaiprompt ที่เว็บรู้ (จาก SSO) — เชื่อแบบมีเงื่อนไข (JuntraAccount ชนะเสมอ)
            'thaiprompt_user_id' => 'sometimes|nullable|integer|min:1',
            // 🌙 (2026-11-03) nonce ของลิงก์ /chat/continue/{handoff}
            'handoff' => 'sometimes|nullable|uuid',
            'persona' => 'sometimes|nullable|string|in:'.implode(',', JuntraChatService::PERSONAS),
        ]);

        // 🔁 ธงปิด = ห้องแบบเดิม (continuity = null → ไม่มีความจำ คำทักทายเดิม)
        //    💬 (2026-11-04) ธงเปิด = ทุกห้องได้ความจำของลูกค้า (RPG/อุปนิสัย) — handoff แค่ consume ลิงก์
        $continuity = $this->continuity->enabled()
            ? $this->continuity->begin(
                $data['user_ref'],
                isset($data['thaiprompt_user_id']) ? (int) $data['thaiprompt_user_id'] : null,
                $data['handoff'] ?? null,
            )
            : null;

        $out = $this->chat->start(
            $this->owner($data['user_ref']),
            $data['context'] ?? null,
            $data['persona'] ?? null,
            $continuity,
        );

        return response()->json(['data' => [
            'session_id' => $out['session_id'],
            'greeting' => $out['greeting'],
            'persona' => $out['persona'],
            'display_name' => $out['display_name'],
            'offer_topic' => $out['offer_topic'],
            'continued_from' => $out['continued_from'],
            'handoff_id' => $out['handoff_id'],
            // 🔐 (รีวิว) true = ครั้งนี้ consume handoff ได้จริง (เว็บเปิดห้อง "คุยต่อจากเพจ" ได้)
            //    false = ลิงก์ถูกใช้ไปแล้ว/ใช้ไม่ได้ → เว็บพากลับห้องเดิม ไม่เปิดห้องซ้ำ ไม่ทักซ้ำ
            'handoff_consumed' => (bool) ($out['handoff_consumed'] ?? false),
            'customer' => $out['customer'],
        ]], 201);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_ref' => 'required|string|max:64|regex:/^[A-Za-z0-9_-]+$/',
            'name' => 'nullable|string|max:80',
            'session_id' => 'required|string|uuid',
            'text' => 'required|string|min:1|max:1000',
            // ห้องที่ลูกค้าเพิ่งจ่ายเปิดไพ่ไปแล้ว — คุยต่อจากไพ่ชุดนั้นได้เต็มที่ ไม่ต้องชวนเปิดไพ่ซ้ำ
            'grounded' => 'sometimes|boolean',
            // 💬 (2026-09-21) ส่งซ้ำได้ทุกรอบ — ห้องหมดอายุ (6 ชม.) / cache ถูกล้าง แม่หมอก็ยังเห็นคำพยากรณ์ครบ
            'context' => 'sometimes|nullable|string|max:'.JuntraChatService::MAX_CONTEXT_CHARS,
            // 🌙 (2026-11-03) ใช้สร้างความจำใหม่เมื่อห้องหมดอายุ (ไม่ consume handoff)
            'thaiprompt_user_id' => 'sometimes|nullable|integer|min:1',
            // 🎭 ใช้เฉพาะห้องที่ยังไม่ล็อกตัวละคร — ห้องที่ล็อกแล้วไม่เปลี่ยน
            'persona' => 'sometimes|nullable|string|in:'.implode(',', JuntraChatService::PERSONAS),
            // 🔈 (2026-11-04) โหมดคุยด้วยเสียง (ลูกค้าซื้อ pass แล้ว — เว็บเป็นคนตัดสิน) · ไม่ส่ง = ทางเดิมทุกตัวอักษร
            'voice_mode' => 'sometimes|boolean',
            'input_mode' => 'sometimes|nullable|string|in:'.implode(',', JuntraChatService::INPUT_MODES),
            // id คลิปคลังที่เว็บมีไฟล์จริง — AI เห็นเฉพาะคลิปที่เล่นได้
            'voice_clips' => 'sometimes|nullable|array|max:120',
            'voice_clips.*' => 'string|max:40|regex:/^[a-z_]+_\d+$/',
        ]);

        $userRef = $data['user_ref'];
        $voiceMode = $request->boolean('voice_mode');
        $claimed = isset($data['thaiprompt_user_id']) ? (int) $data['thaiprompt_user_id'] : null;

        // 🔴 (2026-11-04) ลูกค้าเว็บพิมพ์หาแม่หมอ = นับใน "กำลังสนทนาตอนนี้" แบบเดียวกับ DM ของเพจ/LINE
        app(FortuneLiveActivity::class)->touch('web', $userRef);

        try {
            $out = $this->chat->send(
                $this->owner($userRef),
                $data['session_id'],
                trim($data['text']),
                // ป้ายใน log การใช้ AI — ห้ามใส่ user_id: คอลัมน์เป็นตัวเลขของ users ฝั่ง Thaiprompt
                // ลูกค้าเว็บไม่มีแถวในนั้น (ใส่ id ฝั่งเว็บไปจะชี้ผิดคน หรือ insert พังบน MySQL strict)
                ['customer_name' => 'เว็บจันทรา #'.$userRef.(! empty($data['name']) ? ' '.$data['name'] : '')],
                webOffers: ! $request->boolean('grounded'),
                fillerOnEmpty: false,
                context: $data['context'] ?? null,
                persona: $data['persona'] ?? null,
                // 🔁 ธงปิด = ไม่อ่านความจำเลย · ห้องหมดอายุสร้างใหม่จาก RPG/ประวัติไพ่ของลูกค้า
                allowMemory: $this->continuity->enabled(),
                memoryLoader: fn () => $this->continuity->rebuildMemory($userRef, $claimed),
                voiceMode: $voiceMode,
                inputMode: $data['input_mode'] ?? 'text',
                voiceClips: isset($data['voice_clips']) ? array_values($data['voice_clips']) : null,
            );
        } catch (Throwable $e) {
            Log::warning('Juntra server chat send failed', [
                'user_ref' => $userRef,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'แม่หมอกำลังพักสายตา · ลองใหม่อีกครู่นะคะลูก',
                'reason_code' => 'ai_unavailable',
            ], 503);
        }

        $payload = [
            'session_id' => $data['session_id'],
            'reply' => $out['reply'],
            'ai_provider' => $out['provider'],
            'kind' => $out['kind'],
            'offer_topic' => $out['offer_topic'],
            'persona' => $out['persona'],
            // 🤍 (2026-11-06) crisis = true → เทิร์นนี้คือคำปลอบลูกค้าที่บอกว่าอยากทำร้ายตัวเอง (kind ยังเป็น guard เหมือนเดิม)
            //    เว็บต้องไม่คิดค่าแชทเทิร์นนี้ · crisis_room = ห้องอยู่ในโหมดวิกฤต (ไม่มีการ์ดขายจนห้องหมดอายุ)
            'crisis' => (bool) ($out['crisis'] ?? false),
            'crisis_room' => (bool) ($out['crisis_room'] ?? false),
        ];
        // 🔈 มีเฉพาะเมื่อเปิดโหมดเสียง — เว็บรุ่นเดิมได้ JSON หน้าตาเดิม
        if ($voiceMode) {
            $payload['voice_plan'] = $out['voice_plan'] ?? null;
        }

        return response()->json(['data' => $payload]);
    }

    /** ห้องของลูกค้าเว็บ — คนละ namespace กับ users.id ของ Thaiprompt (key ไม่มีทางชนกัน) */
    private function owner(string $userRef): string
    {
        return 'jw'.$userRef;
    }

    /**
     * 🎬 (2026-11-04) คำทักทายเฉพาะตัวของลูกค้าคุ้นเคย — ข้อ 17.7
     *
     * facts รับเฉพาะคีย์ที่พูดออกลำโพงได้ (คีย์อื่นถูกทิ้งก่อนถึง AI) · ชื่อเรียกห้ามมีตัวเลข/@ (กันเบอร์/อีเมล)
     * 200 {greeting, say, cached} · 429 ครบเพดานทั้งเว็บวันนี้ · 503 AI ไม่ตอบ/คำทักทายไม่ผ่าน → เว็บใช้คลิปลูกค้าเก่า
     */
    public function greeting(Request $request, JuntraRegularGreetingService $greetings): JsonResponse
    {
        $data = $request->validate([
            'user_ref' => 'required|string|max:64|regex:/^[A-Za-z0-9_-]+$/',
            'persona' => 'required|string|in:'.implode(',', JuntraChatService::PERSONAS),
            'facts' => 'sometimes|array',
            'facts.name' => ['sometimes', 'nullable', 'string', 'max:20', "regex:/^[\p{Thai}A-Za-z .'-]+$/u"],
            'facts.zodiac' => 'sometimes|nullable|in:'.implode(',', JuntraRegularGreetingService::ZODIACS),
            'facts.birth_weekday' => 'sometimes|nullable|in:'.implode(',', JuntraRegularGreetingService::WEEKDAYS),
            'facts.days_to_birthday' => 'sometimes|nullable|integer|min:0|max:7',
            'facts.visits' => 'sometimes|nullable|integer|min:1|max:9999',
            'facts.days_away' => 'sometimes|nullable|integer|min:0|max:3650',
            'facts.last_topic' => 'sometimes|nullable|in:'.implode(',', array_keys(JuntraRegularGreetingService::TOPICS)),
        ]);

        $facts = array_intersect_key($data['facts'] ?? [], array_flip(['name', 'zodiac', 'birth_weekday', 'days_to_birthday', 'visits', 'days_away', 'last_topic']));
        $result = $greetings->greet($data['persona'], $data['user_ref'], array_filter($facts, fn ($v) => $v !== null && $v !== ''));

        return match ($result['code']) {
            200 => response()->json(['success' => true, 'data' => $result['data']]),
            429 => response()->json(['success' => false, 'error' => 'greeting_cap', 'message' => 'วันนี้ครบโควต้าคำทักทายแล้ว'], 429),
            default => response()->json(['success' => false, 'error' => 'greeting_unavailable', 'message' => 'ยังทักทายด้วยเสียงไม่ได้ตอนนี้'], 503),
        };
    }
}
