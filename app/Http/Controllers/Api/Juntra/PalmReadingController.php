<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Services\Fortune\PersonaBreakDetector;
use App\Services\FortuneAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ✋ (2026-11-07) ดูลายมือให้เว็บจันทรา — POST fortune/palm (ลูกค้าจ่ายที่ juntraweb แล้ว)
 *
 * เดิม juntraweb อ่านลายมือด้วย Gemini ของตัวเอง ซึ่งบน prod ไม่มีคีย์ ⇒ บริการเปิดใช้ไม่ได้เลย
 * ที่นี่ใช้ด่าน vision ตัวเดียวกับบอท (FortuneAIService::chatWithImage — OpenAI sensitive key, เคารพสวิตช์
 * enable_image_vision ของแอดมิน)
 *
 * รหัสตอบกลับ (juntraweb ต้องคืนเงินทุกกรณีที่ไม่ใช่ 200):
 *   503 vision_unavailable = ปิด vision / ไม่มีคีย์ / โมเดลล่ม · 503 model_refused = คำตอบหลุดบท
 *   422 not_palm = รูปไม่ใช่ฝ่ามือ (ลูกค้าถ่ายใหม่ได้)
 */
class PalmReadingController extends Controller
{
    /** base64 ของรูป ~4.5 MB — juntraweb ย่อรูปก่อนส่ง เพดานนี้กันเผื่อ */
    private const MAX_DATA_URL = 6_500_000;

    private const NOT_PALM = '[NOT_PALM]';

    public function __invoke(Request $request, FortuneAIService $ai): JsonResponse
    {
        $data = $request->validate([
            'image' => ['required', 'string', 'max:'.self::MAX_DATA_URL],
            'question' => 'nullable|string|max:500',
            'customer_name' => 'nullable|string|max:80',
        ]);
        // ตรวจเองแทน regex ทั้งก้อน — สตริงหลาย MB ชนเพดาน backtrack ของ PCRE ได้
        if (preg_match('/^data:image\/(jpeg|png|webp);base64,/', $data['image'], $m) !== 1
            || base64_decode(substr($data['image'], strlen($m[0])), true) === false) {
            return response()->json(['message' => 'รูปต้องเป็น JPEG/PNG/WebP แบบ base64', 'reason_code' => 'bad_image'], 422);
        }

        $question = trim((string) ($data['question'] ?? ''));
        try {
            $result = $ai->chatWithImage(
                $data['image'],
                $this->systemPrompt(),
                $question !== '' ? 'คำถามของลูกค้า: '.$question : 'ลูกค้าขอให้อ่านลายมือภาพรวม',
                ['temperature' => 0.7, 'max_tokens' => 1500],
            );
        } catch (\Throwable $e) {
            Log::warning('Juntra palm: vision ล้ม', ['err' => mb_substr($e->getMessage(), 0, 200)]);
            $result = null;
        }

        $text = trim((string) ($result['response'] ?? ''));
        if ($text === '') {
            return response()->json(['message' => 'ระบบอ่านรูปไม่พร้อมชั่วคราว', 'reason_code' => 'vision_unavailable'], 503);
        }
        if (str_contains($text, self::NOT_PALM)) {
            return response()->json(['message' => 'รูปนี้ยังไม่เห็นฝ่ามือชัดพอ ลองถ่ายฝ่ามือให้เต็มกรอบในที่สว่างนะคะ', 'reason_code' => 'not_palm'], 422);
        }
        if (($reason = PersonaBreakDetector::reason($text, strict: true)) !== null) {
            Log::warning('Juntra palm: คำตอบหลุดบท', ['reason' => $reason]);

            return response()->json(['message' => 'ระบบอ่านรูปไม่พร้อมชั่วคราว', 'reason_code' => 'model_refused'], 503);
        }

        return response()->json(['data' => [
            'reading' => $text,
            'ai_provider' => $result['provider'] ?? 'openai',
            'ai_model' => $result['model'] ?? null,
        ]]);
    }

    private function systemPrompt(): string
    {
        return 'คุณคือ "แม่หมอ" ผู้อ่านลายมือของเว็บจันทรา อ่านจากสิ่งที่เห็นในรูปจริงเท่านั้น'
            ."\n1. ถ้ารูปไม่ใช่ฝ่ามือคน หรือมองไม่เห็นเส้นบนฝ่ามือ ให้ตอบคำเดียวว่า ".self::NOT_PALM.' แล้วหยุด'
            ."\n2. บอกก่อนว่าเห็นมือข้างไหน (ถ้าดูไม่ออกให้บอกตรง ๆ) แล้วอ่านเส้นชีวิต เส้นใจ เส้นปัญญา เส้นวาสนา (ถ้าเห็น) และเนินที่เด่น"
            ."\n   ทุกข้อต้องบอกลักษณะที่เห็นจริง (ยาว/สั้น ลึก/จาง โค้ง/ตรง มีเส้นแตกหรือเกาะ) ก่อนแปลความ — เส้นที่มองไม่ชัดให้บอกว่าไม่ชัด ห้ามแต่ง"
            ."\n3. ถ้าลูกค้าถามเรื่องใด ตอบเรื่องนั้นจากเส้นที่เกี่ยวข้อง 1 ย่อหน้า"
            ."\n4. 4-6 ย่อหน้า ภาษาไทย พูดแทนตัวเองว่าแม่หมอ ลงท้ายค่ะ/นะคะ อบอุ่น ไม่ขู่ ห้ามทำนายความตาย/โรคร้าย"
            ."\n5. ห้ามพูดว่าเป็น AI/โมเดล ห้ามขายของ ห้ามให้เบอร์โทรหรือบัญชีโอนเงิน";
    }
}
