<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiApiKey;
use App\Services\Eve\EveConfig;
use App\Services\FortuneAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * ⚙️ ตั้งค่าน้อง Eve — หน้าหลังบ้านให้แอดมินปรับทุกอย่างเองได้
 *
 * บุคลิก · ชื่อ · ข้อความทักทาย · เปิด/ปิดรายพื้นที่ · โควตา · AI (provider/model/คีย์)
 * + ปุ่ม "ทดสอบคุย" ที่ยิง AI จริงก่อนบันทึก (กฎภายใน: ห้ามตั้ง model id โดยไม่ยิงทดสอบจริง)
 */
class EveSettingsController extends Controller
{
    /**
     * แสดงหน้าตั้งค่า
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // รายชื่อคีย์ในพูลตาม provider — โชว์ให้แอดมินเห็นว่า "โหมดอัตโนมัติ" มีคีย์อะไรให้ใช้บ้าง
        $poolSummary = [];
        try {
            if (Schema::hasTable('ai_api_keys')) {
                $poolSummary = AiApiKey::where('is_active', true)
                    ->whereNull('disabled_until')
                    ->selectRaw('provider, COUNT(*) AS total')
                    ->groupBy('provider')
                    ->pluck('total', 'provider')
                    ->all();
            }
        } catch (Throwable $e) {
            $poolSummary = [];
        }

        return view('admin.eve.settings.index', [
            'pageTitle' => 'ตั้งค่าน้อง Eve',
            'config' => EveConfig::all(),
            'personalities' => EveConfig::PERSONALITIES,
            'poolSummary' => $poolSummary,
        ]);
    }

    /**
     * บันทึกการตั้งค่า
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'assistant_name' => 'required|string|max:40',
            'personality' => 'required|in:'.implode(',', array_keys(EveConfig::PERSONALITIES)),
            'greeting' => 'nullable|string|max:300',
            'extra_prompt' => 'nullable|string|max:1500',
            'ai_provider' => 'required|in:gemini,openai,groq,anthropic',
            'ai_model' => 'nullable|string|max:80',
            'ai_api_key' => 'nullable|string|max:200',
            'quota_guest' => 'required|integer|min:1|max:10000',
            'quota_customer' => 'required|integer|min:1|max:10000',
            'quota_seller' => 'required|integer|min:1|max:10000',
            'quota_admin' => 'required|integer|min:1|max:10000',
            'enabled_storefront' => 'nullable|boolean',
            'enabled_user' => 'nullable|boolean',
            'enabled_seller' => 'nullable|boolean',
            'enabled_admin' => 'nullable|boolean',
        ]);

        // checkbox ที่ไม่ติ๊ก browser ไม่ส่งมา → ต้องตีเป็น false ชัดๆ
        foreach (['enabled_storefront', 'enabled_user', 'enabled_seller', 'enabled_admin'] as $flag) {
            $validated[$flag] = $request->boolean($flag);
        }

        // 🔑 คีย์: ช่องว่าง = "คงคีย์เดิมไว้" (ฟอร์มโชว์แบบ masked ไม่ส่งคีย์จริงกลับมา)
        //    พิมพ์ค่าใหม่ = เปลี่ยน · พิมพ์ "-" = ล้างคีย์ทิ้งกลับไปใช้พูลอัตโนมัติ
        $key = trim((string) ($validated['ai_api_key'] ?? ''));
        if ($key === '') {
            unset($validated['ai_api_key']);
        } elseif ($key === '-') {
            $validated['ai_api_key'] = '';
        }

        $validated['greeting'] = trim((string) ($validated['greeting'] ?? ''));
        $validated['extra_prompt'] = trim((string) ($validated['extra_prompt'] ?? ''));
        $validated['ai_model'] = trim((string) ($validated['ai_model'] ?? ''));

        EveConfig::save($validated);

        return redirect()
            ->route('admin.eve.settings.index')
            ->with('success', 'บันทึกการตั้งค่าน้อง Eve เรียบร้อยค่ะ');
    }

    /**
     * 🧪 ทดสอบคุยด้วยค่าที่กรอกในฟอร์ม "ก่อนบันทึก" — ยิง AI จริง เห็นคำตอบ+โมเดลที่ใช้จริง
     *
     * บังคับตามกฎภายใน: ห้ามตั้ง model id ใหม่โดยไม่เคยยิงทดสอบกับ API จริง
     * (model ผิด provider = Eve ตาย 404 ทุกข้อความ ลูกค้าเจอ "ระบบขัดข้อง" ทั้งเว็บ)
     */
    public function test(Request $request, FortuneAIService $aiService): JsonResponse
    {
        $data = $request->validate([
            'message' => 'required|string|min:1|max:300',
            'personality' => 'required|in:'.implode(',', array_keys(EveConfig::PERSONALITIES)),
            'assistant_name' => 'required|string|max:40',
            'ai_provider' => 'required|in:gemini,openai,groq,anthropic',
            'ai_model' => 'nullable|string|max:80',
            'ai_api_key' => 'nullable|string|max:200',
            'extra_prompt' => 'nullable|string|max:1500',
        ]);

        // ประกอบ prompt แบบเดียวกับของจริง แต่ใช้ค่าจากฟอร์ม (ยังไม่บันทึก)
        $name = $data['assistant_name'];
        $persona = match ($data['personality']) {
            'sassy' => "คุณคือ \"{$name}\" สาวน้อยแสนซน สดใสมั่นใจ พูดมีลูกเล่น หยอดมุกน่ารักๆ ได้ อิโมจิ ≤2 ตัว ลงท้าย ค่ะ/น้า/ค่า",
            'sweet' => "คุณคือ \"{$name}\" ผู้ช่วยสาวสุภาพ อ่อนหวาน เรียบร้อย ลงท้าย ค่ะ/นะคะ",
            default => "คุณคือ \"{$name}\" ผู้ช่วยสาวร่าเริง เป็นกันเอง ขี้เล่นนิดๆ ลงท้าย ค่ะ/นะคะ",
        };
        $system = $persona.' ตอบสั้นกระชับ 1-3 ประโยค ภาษาไทยล้วน.';
        if (trim((string) ($data['extra_prompt'] ?? '')) !== '') {
            $system .= "\n\n[นโยบายเพิ่มเติม] ".mb_substr(trim($data['extra_prompt']), 0, 1500);
        }

        // คีย์: ว่าง = ใช้ค่าที่บันทึกไว้ / ไม่มีก็พูลอัตโนมัติ
        $apiKey = trim((string) ($data['ai_api_key'] ?? ''));
        if ($apiKey === '' || $apiKey === '-') {
            $saved = (string) EveConfig::get('ai_api_key');
            $apiKey = $saved !== '' ? $saved : null;
        }

        $model = trim((string) ($data['ai_model'] ?? ''));

        $t0 = microtime(true);
        try {
            $result = $aiService->chatWithCustomSystemPrompt(
                systemMessage: $system,
                userMessage: mb_substr($data['message'], 0, 300),
                config: ['temperature' => 0.7, 'max_tokens' => 320],
                providerOverride: $data['ai_provider'],
                modelOverride: $model !== '' ? $model : null,
                apiKeyOverride: $apiKey,
            );

            $reply = is_array($result)
                ? trim((string) ($result['response'] ?? $result['content'] ?? $result['text'] ?? ''))
                : trim((string) $result);

            return response()->json([
                'success' => $reply !== '',
                'reply' => $reply !== '' ? $reply : '(ได้คำตอบว่างกลับมา — ลองเปลี่ยนโมเดลดูค่ะ)',
                'provider' => $data['ai_provider'],
                'model' => $model !== '' ? $model : '(พูลเลือกอัตโนมัติ)',
                'ms' => (int) round((microtime(true) - $t0) * 1000),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                // โชว์ error ให้แอดมินเห็นตรงๆ (หน้านี้แอดมินเท่านั้น) — ตัดสั้นกัน stack trace ยาว
                'reply' => '❌ ทดสอบไม่ผ่าน: '.mb_substr($e->getMessage(), 0, 300),
                'provider' => $data['ai_provider'],
                'model' => $model !== '' ? $model : '(พูลเลือกอัตโนมัติ)',
                'ms' => (int) round((microtime(true) - $t0) * 1000),
            ]);
        }
    }
}
