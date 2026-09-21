<?php

namespace App\Http\Controllers\Api\Juntra\Server;

use App\Http\Controllers\Controller;
use App\Services\Fortune\JuntraChatService;
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
 */
class ChatController extends Controller
{
    public function __construct(private JuntraChatService $chat) {}

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_ref' => 'required|string|max:64|regex:/^[A-Za-z0-9_-]+$/',
            // 💬 (2026-09-21) ห้องที่คุยต่อจากคำพยากรณ์ที่ลูกค้าจ่ายแล้ว — ไพ่ทุกใบ + คำพยากรณ์ฉบับเต็ม
            //    ไม่ส่ง = ห้องคุยทั่วไปแบบเดิม (เว็บรุ่นก่อนหน้าไม่รู้จักฟิลด์นี้)
            //    รับเฉพาะทางเซิร์ฟเวอร์ของเว็บ — ทาง token ของลูกค้าไม่รับ เพราะบริบทนี้ไปอยู่ใน system message
            'context' => 'sometimes|nullable|string|max:'.JuntraChatService::MAX_CONTEXT_CHARS,
        ]);

        return response()->json(['data' => $this->chat->start($this->owner($data['user_ref']), $data['context'] ?? null)], 201);
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
        ]);

        try {
            $out = $this->chat->send(
                $this->owner($data['user_ref']),
                $data['session_id'],
                trim($data['text']),
                // ป้ายใน log การใช้ AI — ห้ามใส่ user_id: คอลัมน์เป็นตัวเลขของ users ฝั่ง Thaiprompt
                // ลูกค้าเว็บไม่มีแถวในนั้น (ใส่ id ฝั่งเว็บไปจะชี้ผิดคน หรือ insert พังบน MySQL strict)
                ['customer_name' => 'เว็บจันทรา #'.$data['user_ref'].(! empty($data['name']) ? ' '.$data['name'] : '')],
                webOffers: ! $request->boolean('grounded'),
                fillerOnEmpty: false,
                context: $data['context'] ?? null,
            );
        } catch (Throwable $e) {
            Log::warning('Juntra server chat send failed', [
                'user_ref' => $data['user_ref'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'แม่หมอกำลังพักสายตา · ลองใหม่อีกครู่นะคะลูก',
                'reason_code' => 'ai_unavailable',
            ], 503);
        }

        return response()->json(['data' => [
            'session_id' => $data['session_id'],
            'reply' => $out['reply'],
            'ai_provider' => $out['provider'],
            'kind' => $out['kind'],
            'offer_topic' => $out['offer_topic'],
        ]]);
    }

    /** ห้องของลูกค้าเว็บ — คนละ namespace กับ users.id ของ Thaiprompt (key ไม่มีทางชนกัน) */
    private function owner(string $userRef): string
    {
        return 'jw'.$userRef;
    }
}
