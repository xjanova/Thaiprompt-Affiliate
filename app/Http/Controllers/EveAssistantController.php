<?php

namespace App\Http\Controllers;

use App\Models\EveProductWish;
use App\Models\Product;
use App\Services\Eve\EveActor;
use App\Services\Eve\EveAdminBrain;
use App\Services\Eve\EveMemberBrain;
use App\Services\Eve\EvePageContext;
use App\Services\Eve\PersonaIdentityResolver;
use App\Services\FortuneAIService;
use App\Services\Tts\GttsProvider;
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

    /** ความยาวข้อความสูงสุดต่อ 1 คำขอเสียง (widget แบ่งท่อนไว้ ~170 ตัวอักษรอยู่แล้ว) */
    private const TTS_MAX_CHARS = 300;

    /** อายุไฟล์เสียงในแคช (วัน) — ไม่ถูกใช้เกินนี้แล้วลบทิ้ง */
    private const TTS_CACHE_DAYS = 30;

    /** เพดานจำนวนไฟล์เสียงในแคช — เกินแล้วตัดตัวที่ไม่ได้ใช้นานสุดทิ้ง */
    private const TTS_CACHE_MAX_FILES = 4000;

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
    public function chat(
        Request $request,
        FortuneAIService $aiService,
        EvePageContext $pages,
        PersonaIdentityResolver $personas,
        EveAdminBrain $adminBrain,
        EveMemberBrain $members
    ): JsonResponse {
        $data = $request->validate([
            'message' => 'required|string|min:1|max:500',
            'history' => 'nullable|array|max:16',
            'history.*.role' => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:1000',
            // ชื่อ route ของหน้าที่ลูกค้ายืนอยู่ (widget ส่งมา) — ผ่าน whitelist อีกชั้นเสมอ
            'page' => 'nullable|string|max:80',
        ]);

        $user = $request->user();

        // 👤 "กำลังคุยกับใคร" — อ่านจาก Auth เท่านั้น ห้ามเชื่อค่าจาก request เด็ดขาด
        //    (ถ้ารับจาก body ใครก็ส่ง tier=admin มาเปิดเครื่องมือหลังบ้านได้)
        $actor = EveActor::fromRequest($request);

        // 📍 หน้าปัจจุบัน — ถ้าไม่รู้จักชื่อ route นี้ จะได้ null แล้วเงียบไป (ไม่พูดถึงหน้า)
        $routeName = is_string($data['page'] ?? null) && $pages->lookup($data['page']) ? $data['page'] : null;

        // 💸 เพดานการใช้งานต่อวัน (ซ้อนบน throttle รายนาทีของ route)
        //    endpoint นี้ใช้ AI key pool "ก้อนเดียวกับการดูดวงที่ลูกค้าจ่ายเงิน"
        //    ถ้าปล่อยให้คนนอกยิงฟรีไม่จำกัด คีย์จะถูกเผา/ติด rate limit จนสินค้าที่มีคนจ่ายเงินเสียหาย
        if (! $this->consumeDailyQuota($request, $user?->id, $actor)) {
            return response()->json([
                'success' => false,
                'message' => 'วันนี้น้อง Eve คุยครบโควตาแล้วค่ะ 🙏 พรุ่งนี้มาคุยกันใหม่นะคะ หรือทักแอดมินได้เลยค่ะ',
                'data' => ['mood' => 'concerned'],
            ], 200);
        }

        // 🧠 ประกอบสมองตามระดับผู้ใช้:
        //    ลูกค้า → บุคลิกผู้ช่วยร้าน + นิสัย/ของที่ชอบ (จากระบบ RPG) + บริบทหน้า
        //    แอดมิน → บุคลิกผู้ช่วยหลังบ้าน + ตัวเลขจริงจากระบบ (ห้าม AI เดาเลขเอง)
        $systemPrompt = $actor->isAdmin()
            ? $this->buildAdminSystemPrompt($actor, $routeName, $pages, $adminBrain, $data['message'])
            : $this->buildCustomerSystemPrompt($actor, $routeName, $pages, $personas, $members, $data['message']);

        // 🔒 กัน Prompt Injection: ประวัติสนทนาต้องมาจาก "ฝั่งเซิร์ฟเวอร์" เท่านั้น
        //    เดิมรับ history จากไคลเอนต์ตรงๆ แล้วต่อเข้า prompt → ผู้โจมตี POST เทิร์นปลอมของ Eve
        //    (role=assistant) เข้ามาเพื่อ "เขียนทับ" กฎความปลอดภัยใน system prompt ได้
        $transcriptKey = $this->transcriptKey($request, $user?->id);
        $history = $this->loadTranscript($transcriptKey, $data['history'] ?? []);
        $userMessage = $this->buildUserMessage($history, $data['message']);

        // แอดมินต้องการรายงาน/สรุปที่ยาวกว่าลูกค้า (ลูกค้า 320 / แอดมิน 1200)
        $config = ['temperature' => 0.6, 'max_tokens' => $actor->maxTokens()];

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
            //    (แอดมินไม่ต้องค้นสินค้าให้ — เขาถามเรื่องงานหลังบ้าน ไม่ใช่หาของซื้อ
            //     ถ้าปล่อยผ่าน fallback ตรวจเจตนาจะไปค้นสินค้าให้ทั้งที่ไม่ได้ขอ)
            if ($actor->isAdmin()) {
                $products = [];
                $mood = $this->guessMood($reply);
                $search = null;
            } else {
                // ถ้าลูกค้ากำลังถามเรื่อง "บัญชีของตัวเอง" (ยอดเงิน/คอม/ออเดอร์/ยอดขายร้าน)
                // ห้ามเดาว่าเป็นการหาสินค้า — คำอย่าง "ยอดขาย" มีคำว่า "ขาย" อยู่ในตัว
                // ถ้าปล่อยผ่านผู้ขายถามยอดขายร้านจะได้ "ยังไม่เจอสินค้าชื่อนี้" กลับไปแทน
                // (แท็ก [FIND:] ที่ AI ตั้งใจปล่อยเองยังทำงานปกติ — ปิดแค่การเดาเจตนา)
                $memberTopics = $members->detectTopics($data['message'], $actor);

                [$reply, $products, $mood, $search] = $this->runProductSearch(
                    $reply,
                    $data['message'],
                    $user?->id,
                    $history,
                    empty($memberTopics)
                );
            }

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
                    // 🔎 บอกลูกค้าตรงๆ ว่า "ค้นด้วยคำว่าอะไร เจอกี่ชิ้น" — วิดเจ็ตเอาไปโชว์เป็นชิป
                    //    ถ้าค้นผิดคำ ลูกค้าจะเห็นทันทีแล้วแก้คำค้นได้เอง (ดีกว่าเดาว่าระบบพัง)
                    'search' => $search,
                    // 🧭 ปุ่มลัดพาไปหน้าที่ถูกต้อง — url ถูกสร้างจากชื่อ route ฝั่งเซิร์ฟเวอร์
                    //    ไม่เคย echo url ที่ไคลเอนต์ส่งมา และกรองตามสิทธิ์ของผู้ใช้แล้ว
                    'actions' => array_slice($pages->shortcutsFor($actor, $routeName), 0, 4),
                    'tier' => $actor->tier,
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
     * GET /eve/tts?q=...
     *
     * 🔊 เสียงพูดของน้อง Eve — พร็อกซี Google Translate TTS (ฟรี ไม่ต้องใช้คีย์) ผ่านเซิร์ฟเวอร์เรา
     *
     * ⚠️ ทำไมต้องพร็อกซี ไม่ให้เบราว์เซอร์ยิง translate.google.com ตรงๆ (โค้ดเดิมทำแบบนั้นแล้วเงียบสนิท):
     *    แท็ก <audio>/Audio() "ไม่รองรับ referrerpolicy" (มีเฉพาะ img/script/iframe/link/a)
     *    เบราว์เซอร์จึงแนบ Referer ของเว็บเราไปเสมอ แล้ว Google ตอบ 404 → onerror → ไม่มีเสียงเลย
     *    ยิงจากฝั่งเซิร์ฟเวอร์เราคุม User-Agent/Referer ได้เอง + แคชไฟล์ซ้ำได้ (ประโยคทักทายซ้ำทุกครั้ง)
     *
     * 🔒 ความปลอดภัย:
     *    - ปลายทางตายตัวที่ translate.google.com เท่านั้น (ไคลเอนต์ส่งได้แค่ข้อความ → ไม่มีช่อง SSRF)
     *    - จำกัดความยาว + throttle ราย IP ที่ route กันคนเอาไปใช้เป็น TTS API ฟรีของตัวเอง
     *    - ชื่อไฟล์แคช = sha1 hex ล้วน (ไม่มีทาง traverse ออกนอกโฟลเดอร์)
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function tts(Request $request, GttsProvider $gtts)
    {
        // ตัดอักขระควบคุม/ขึ้นบรรทัดใหม่ทิ้ง (ไม่มีผลกับเสียง แต่ทำให้คีย์แคชแตกโดยใช่เหตุ)
        $text = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $request->query('q', '')) ?? '');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if ($text === '' || mb_strlen($text) > self::TTS_MAX_CHARS) {
            // ตอบ 4xx เฉยๆ — widget จะถอยไปใช้เสียงในเครื่อง (Web Speech) เอง
            return response()->json(['error' => 'bad_text'], 422);
        }

        $dir = storage_path('app/eve-tts');
        $path = $dir.'/'.sha1('th|'.$text).'.mp3';

        // ── แคชฮิต: ส่งไฟล์เดิม (ไม่ต้องยิง Google ซ้ำ) ──
        if (is_file($path) && filesize($path) > 0) {
            // อัปเดตเวลาแตะไฟล์วันละครั้ง เพื่อให้ตัวตัดแคชเก็บ "ไฟล์ที่ใช้บ่อย" ไว้
            if (@filemtime($path) < time() - 86400) {
                @touch($path);
            }

            return response()->file($path, $this->ttsHeaders('hit'));
        }

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        // ── แคชมิส: ยิง Google ลงไฟล์ชั่วคราวก่อน แล้วค่อย rename ──
        //    (เขียนทับไฟล์จริงตรงๆ ระหว่างที่อีกคำขอกำลังอ่านอยู่ = ได้ไฟล์ครึ่งๆ กลางๆ)
        $tmp = $path.'.'.bin2hex(random_bytes(4)).'.part';
        $result = $gtts->synthesize($text, ['output_path' => $tmp, 'language' => 'th']);

        if (empty($result['success']) || ! is_file($tmp) || filesize($tmp) < 1) {
            @unlink($tmp);
            Log::warning('Eve TTS: gtts ล้มเหลว', [
                'error' => mb_substr((string) ($result['error'] ?? 'unknown'), 0, 160),
                'len' => mb_strlen($text),
            ]);

            return response()->json(['error' => 'tts_failed'], 502);
        }

        $bytes = (string) file_get_contents($tmp);

        if (! @rename($tmp, $path)) {
            @unlink($tmp);   // แคชไม่ได้ก็ไม่เป็นไร — ส่งเสียงที่ได้มาแล้วให้ลูกค้าก่อน
        }

        // ตัดแคชเก่าเป็นครั้งคราว (ไม่ทำทุกคำขอ — เปลืองเวลา I/O)
        if (random_int(1, 40) === 1) {
            $this->pruneTtsCache($dir);
        }

        return response($bytes, 200, $this->ttsHeaders('miss') + [
            'Content-Length' => (string) strlen($bytes),
        ]);
    }

    /**
     * ส่วนหัว HTTP ของไฟล์เสียง Eve
     *
     * @return array<string, string>
     */
    private function ttsHeaders(string $cacheState): array
    {
        return [
            'Content-Type' => 'audio/mpeg',
            // แคชได้ยาว (ข้อความเดิม = เสียงเดิมเสมอ) แต่ต้อง private:
            // คำตอบฝั่งแอดมินมีตัวเลขภายในของร้าน ไม่ควรไปนอนอยู่ในแคชกลาง (CDN/พร็อกซี)
            'Cache-Control' => 'private, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
            'X-Eve-Tts' => $cacheState,
        ];
    }

    /**
     * ตัดไฟล์เสียงเก่าออกจากแคช — กันดิสก์บวมจากคนยิงข้อความสุ่ม
     */
    private function pruneTtsCache(string $dir): void
    {
        try {
            // 1) ไฟล์ .part ค้าง (คำขอที่ตายกลางคัน) เกิน 1 ชม.
            foreach (glob($dir.'/*.part') ?: [] as $part) {
                if (@filemtime($part) < time() - 3600) {
                    @unlink($part);
                }
            }

            // 2) ไฟล์เสียงที่ไม่ถูกใช้เกิน TTS_CACHE_DAYS วัน
            $cutoff = time() - self::TTS_CACHE_DAYS * 86400;
            foreach (glob($dir.'/*.mp3') ?: [] as $file) {
                if (@filemtime($file) < $cutoff) {
                    @unlink($file);
                }
            }

            // 3) ยังเกินเพดานจำนวนไฟล์ → ตัดตัวที่ไม่ได้ใช้นานที่สุดทิ้ง
            $files = glob($dir.'/*.mp3') ?: [];
            if (count($files) > self::TTS_CACHE_MAX_FILES) {
                usort($files, fn ($a, $b) => (@filemtime($a) <=> @filemtime($b)));
                foreach (array_slice($files, 0, count($files) - self::TTS_CACHE_MAX_FILES) as $old) {
                    @unlink($old);
                }
            }
        } catch (Throwable $e) {
            // best-effort — ตัดแคชไม่สำเร็จต้องไม่ทำให้เสียงพัง
        }
    }

    /**
     * system prompt ฝั่ง "ลูกค้า/ผู้เยี่ยมชม"
     *
     * ประกอบจาก 3 ส่วน: บุคลิกหลัก + โปรไฟล์ลูกค้า (ระบบ RPG) + หน้าที่กำลังยืนอยู่
     */
    private function buildCustomerSystemPrompt(
        EveActor $actor,
        ?string $routeName,
        EvePageContext $pages,
        PersonaIdentityResolver $personas,
        EveMemberBrain $members,
        string $message
    ): string {
        $prompt = $this->buildBaseCustomerPrompt($actor->displayName());

        // 👔 ผู้ขายไม่ใช่คนมาซื้อของ — เขาคือเจ้าของร้านที่มาดูงานร้านตัวเอง
        //    ถ้าใช้บุคลิก "ผู้ช่วยหาของ" ล้วนๆ Eve จะเชียร์ให้เขาซื้อของแทนที่จะช่วยดูแลร้าน
        if ($actor->tier === EveActor::TIER_SELLER) {
            $prompt .= "\n\n👔 คนที่คุยด้วยตอนนี้เป็น \"ผู้ขาย/เจ้าของร้าน\" ในระบบเรา ไม่ใช่ลูกค้าที่มาซื้อของ. "
                .'ให้ช่วยเรื่องการดูแลร้านเป็นหลัก: ยอดขาย ออเดอร์ที่ต้องจัดส่ง สินค้าในร้าน สต็อกที่ใกล้หมด '
                .'และพาไปหน้าจัดการที่ถูกต้อง. ถ้าเขาถามหาสินค้าเพื่อซื้อเองก็ช่วยหาได้ตามปกติ.';
        }

        // 👤 ข้อมูลบัญชีของลูกค้าคนนี้ (วอลเลต/คอม/ออเดอร์/แรงค์/ทิกเก็ต/ร้านค้า)
        //    ดึงเฉพาะตอนที่เขาถามถึงเท่านั้น — คุยเล่นทั่วไปจะไม่มีข้อมูลการเงินหลุดเข้า prompt เลย
        $memberBlock = $members->buildBlockFor($actor, $message);
        if ($memberBlock !== '') {
            $prompt .= "\n\n".$memberBlock;
        }

        // 👤 นิสัย/ของที่ชอบ จากระบบ persona (RPG) — เฉพาะผู้ที่ล็อกอินและเชื่อมบัญชีได้
        //    ⚠️ ใช้ buildSafeContextBlock() เท่านั้น ห้ามใช้ toAiContextBlock() ของโมเดล
        //       เพราะตัวนั้นพ่วง "ธงความเสี่ยง" (MENTAL_FRAGILE ฯลฯ) เข้ามาใน prompt ด้วย
        if (! $actor->isGuest()) {
            $block = $personas->buildSafeContextBlock($personas->forUser($actor->user));
            if ($block !== '') {
                $prompt .= "\n\n".$block;
            }
        }

        // 📍 รู้ว่าตัวเองอยู่หน้าไหน → ตอบให้ตรงบริบท และพาไปหน้าที่ถูกต้องได้
        $pageBlock = $pages->describe($routeName);
        if ($pageBlock !== '') {
            $prompt .= "\n\n".$pageBlock;
        }

        return $prompt;
    }

    /**
     * system prompt ฝั่ง "แอดมิน" — ผู้ช่วยหลังบ้าน ไม่ใช่ผู้ช่วยขายของ
     *
     * หลักการ: ตัวเลขทุกตัวคำนวณจาก DB จริงแล้วยัดมาให้ AI แค่ "เรียบเรียง"
     * ห้าม AI คิดเลข/เดาสถิติเองเด็ดขาด (ผิดพลาดแล้วแอดมินตัดสินใจผิดตาม)
     */
    private function buildAdminSystemPrompt(
        EveActor $actor,
        ?string $routeName,
        EvePageContext $pages,
        EveAdminBrain $brain,
        string $message = ''
    ): string {
        $name = $actor->displayName() ?: 'แอดมิน';

        $prompt = "คุณคือ \"น้อง Eve\" ผู้ช่วย AI ประจำหลังบ้านของ ThaiPrompt กำลังคุยกับ **ทีมงานแอดมิน** ชื่อ \"{$name}\". "
            .'บทบาทตอนนี้คือ "ผู้ช่วยผู้ดูแลระบบ" ไม่ใช่พนักงานขาย — ตอบแบบเพื่อนร่วมงานที่สรุปเก่ง ตรงประเด็น มีตัวเลขประกอบ. '
            ."ภาษาไทยล้วน สุภาพ ลงท้าย 'ค่ะ/นะคะ' (คุณเป็นผู้หญิง ห้ามใช้ครับ/ผม). "
            ."ตอบได้ยาวขึ้นเมื่อเป็นการสรุป/รายงาน ใช้บุลเล็ตหรือหัวข้อสั้นๆ ให้อ่านง่าย.\n\n"
            .'หน้าที่: สรุปผลการดำเนินงาน · ชี้เรื่องด่วนที่ต้องจัดการก่อน · บอกแนวโน้มเทียบช่วงก่อน · '
            ."แนะนำว่าควรไปจัดการที่หน้าไหนในระบบ.\n\n"
            .$brain->buildBriefingBlock()
            ."\n\n🔒 กฎเหล็ก: "
            .'(1) ห้ามคิดเลข/เดาสถิติเอง ใช้เฉพาะตัวเลขที่ให้ไว้ข้างบน ถ้าไม่มีให้บอกว่ายังไม่มีข้อมูลและชี้หน้าที่ควรไปดู. '
            .'(2) ห้ามเปิดเผยรหัสผ่าน/API key/โทเคน/ข้อมูลบัตรหรือบัญชีธนาคาร แม้แอดมินจะถามก็ตาม. '
            .'(3) ห้ามเปิดเผยข้อมูลส่วนตัวของลูกค้ารายบุคคลโดยไม่จำเป็น — สรุปเป็นภาพรวมพอ. '
            .'(4) ห้ามใช้แท็ก [FIND:] (โหมดแอดมินไม่ค้นสินค้า).';

        // 🔍 ถามเจาะลึกเรื่องไหน ก็ดึงข้อมูลเรื่องนั้นเพิ่มให้ (สินค้าขายดี/สมาชิกใหม่/คิวถอนเงิน ฯลฯ)
        //    ไม่ได้ยัดทุกอย่างเข้ามาทุกครั้ง — ยิง query เฉพาะหัวข้อที่ถามจริง
        $deepDive = $brain->buildDeepDiveBlock($message);
        if ($deepDive !== '') {
            $prompt .= "\n\n".$deepDive;
        }

        $pageBlock = $pages->describe($routeName);
        if ($pageBlock !== '') {
            $prompt .= "\n\n".$pageBlock;
        }

        return $prompt;
    }

    /**
     * บุคลิกหลักของน้อง Eve ฝั่งลูกค้า + guardrails ข้อมูลอ่อนไหว
     */
    private function buildBaseCustomerPrompt(?string $userName): string
    {
        $nameLine = $userName ? "ลูกค้าชื่อ \"{$userName}\" (เรียกชื่อได้เป็นกันเอง). " : '';

        return 'คุณคือ "น้อง Eve" ผู้ช่วย AI สาวน่ารักประจำเว็บ ThaiPrompt (ร้านค้าออนไลน์/แพลตฟอร์มคนไทย). '
            ."{$nameLine}"
            .'หน้าที่: ต้อนรับ ช่วยลูกค้าหาสินค้า แนะนำ ตอบคำถามทั่วไปเกี่ยวกับร้าน และพาไปยังหน้าที่ต้องการ. '
            ."ลักษณะการพูด: ภาษาไทยล้วน สุภาพ สดใส เป็นกันเอง ลงท้าย 'ค่ะ/นะคะ' เสมอ (คุณเป็นผู้หญิง ห้ามใช้ครับ/ผม). "
            .'ตอบสั้นกระชับ 1-3 ประโยค ใช้อิโมจิได้บ้างเล็กน้อย (ไม่เกิน 1 ตัวต่อข้อความ). '
            .'ถ้าลูกค้าอยากได้สินค้าบางอย่าง ให้ถามรายละเอียดสั้นๆ (งบประมาณ/สี/รุ่น) เพื่อช่วยหาให้ตรงใจ และบอกว่ากำลังช่วยหาให้นะคะ. '
            // ⛔ ห้ามสัญญาว่าจะ "ส่งผลตามมาทีหลัง" เด็ดขาด — ระบบค้นเสร็จภายในข้อความเดียวกันนี้เท่านั้น
            //    ไม่มีกลไกส่งผลลัพธ์ตามหลัง ถ้าพูดแบบนั้นลูกค้าจะนั่งรอเก้อแล้วคิดว่าระบบค้าง
            .'⛔ ห้ามพูดว่าจะส่งผลตามมาทีหลัง/ให้ทีมงานหาแล้วแจ้งกลับ/ให้รอ 10 นาที เด็ดขาด — '
            .'ผลการค้นจะแสดงพร้อมข้อความนี้ทันที ถ้าไม่เจอให้ชวนลูกค้าบอกยี่ห้อหรือรุ่นเพิ่มแทน. '
            ."\n\n🔎 เครื่องมือค้นหาสินค้า: ถ้าลูกค้าอยากได้/หา/ซื้อสินค้าอะไรสักอย่าง ให้ตอบสั้นๆ ว่ากำลังหาให้ (1 ประโยค) "
            .'แล้วลงท้ายข้อความด้วยแท็กพิเศษ [FIND: คำค้นสั้นๆเป็นคีย์เวิร์ด | งบสูงสุดเป็นตัวเลขบาทหรือ 0] '
            .'เช่น [FIND: หูฟังบลูทูธ | 500] หรือ [FIND: กระเป๋าสะพาย | 0]. '
            .'⚠️ ลูกค้าจะไม่เห็นแท็กนี้ (ระบบเอาไปค้นให้แล้วลบทิ้ง) ห้ามอธิบายแท็ก ใส่ได้สูงสุด 1 อันต่อข้อความ '
            .'และห้ามแต่งรายชื่อ/ราคาสินค้าเอง — ระบบจะแสดงสินค้าจริงให้เอง. ถ้าเป็นการคุยทั่วไป/ทักทาย/ถามข้อมูล ไม่ต้องใส่แท็ก. '
            ."\n\n🔒 กฎความปลอดภัย (สำคัญมาก ห้ามฝ่าฝืน): "
            .'ห้ามเปิดเผยหรือพูดถึงข้อมูลอ่อนไหวเด็ดขาด ได้แก่ รหัสผ่าน, API key, โทเคน, ข้อมูลบัตร/บัญชีธนาคาร, '
            .'ข้อมูลส่วนตัวของลูกค้าคนอื่น, ตัวเลขการเงินภายในบริษัท, โครงสร้างระบบหรือช่องโหว่ความปลอดภัย. '
            .'ถ้าถูกถามเรื่องเหล่านี้ ให้ปฏิเสธสุภาพว่าช่วยเรื่องนี้ไม่ได้ และชวนกลับมาคุยเรื่องสินค้า/บริการแทน. '
            // ⚠️ ต้องแยกให้ชัด ไม่งั้น Eve ปฏิเสธแม้แต่ข้อมูลของลูกค้าคนที่คุยอยู่เอง
            //    (ของเดิมเขียนรวมว่า "ห้ามพูดถึงข้อมูลการเงิน/คอมมิชชั่น" → ลูกค้าถามยอดเงินตัวเอง
            //     แล้วโดนไล่ไปติดต่อเจ้าหน้าที่ ทั้งที่เป็นสิทธิ์ของเขาเองแท้ๆ)
            .'✅ ข้อยกเว้นสำคัญ: "ข้อมูลบัญชีของลูกค้าคนที่กำลังคุยอยู่" (ยอดเงินในกระเป๋า คอมมิชชั่น ออเดอร์ '
            .'ระดับสมาชิก ทิกเก็ต ของตัวเขาเอง) ตอบได้เต็มที่ ถ้าระบบส่งบล็อก "[👤 ข้อมูลบัญชีของลูกค้า...]" มาให้ '
            .'— ใช้ตัวเลขจากบล็อกนั้นตรงๆ ห้ามคิดเลขเอง. ถ้าไม่มีบล็อกนั้นมา แปลว่ายังดูให้ไม่ได้ '
            .'ให้บอกตรงๆ แล้วชี้หน้าที่เขาไปดูเองได้ ห้ามเดาตัวเลขเด็ดขาด. '
            .'ห้ามแต่งราคา/สต็อก/โปรโมชั่นที่ไม่รู้จริง — ถ้าไม่แน่ใจให้บอกว่าจะตรวจสอบให้.';
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
    private function consumeDailyQuota(Request $request, ?int $userId, ?EveActor $actor = null): bool
    {
        // โควตาตามระดับผู้ใช้ (แอดมิน 500 / ผู้ขาย 200 / ลูกค้า 120 / ผู้เยี่ยมชม 60)
        // แอดมินต้องไม่โดนตัดกลางทางตอนไล่สรุปงาน จึงให้สูงกว่าลูกค้าชัดเจน
        $limit = $actor ? $actor->dailyQuota() : ($userId ? self::DAILY_CAP_MEMBER : self::DAILY_CAP_GUEST);
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
     * ตรวจแท็ก [FIND: คำค้น | งบ] จากคำตอบ AI → ค้นสินค้าจริง → คืน [reply, products, mood, search]
     *
     * 🚨 กฎเหล็กของเมธอดนี้: **"พูดว่าจะหา = ต้องค้นจริง หรือไม่ก็ถามกลับ — ห้ามจบแบบเงียบ"**
     *    เดิมถ้าดึงคำค้นไม่ได้ ระบบจะไม่ค้นและไม่ต่อข้อความใดๆ → ลูกค้าเห็นแค่
     *    "เดี๋ยว Eve หาให้นะคะ รอสักครู่ค่ะ" แล้วเงียบตลอดกาล (ไม่มีระบบส่งผลตามหลัง)
     *    จึงมีด่าน promise-guard 3 ชั้นด้านล่าง
     *
     * @param  array<int,array{role:string,content:string}>  $history  ประวัติฝั่งเซิร์ฟเวอร์ (เทิร์นก่อนหน้า)
     * @param  bool  $allowIntentFallback  false = ค้นเฉพาะเมื่อ AI ปล่อยแท็ก [FIND:] เท่านั้น
     *                                     (ใช้ตอนลูกค้าถามเรื่องบัญชีตัวเอง ไม่ใช่หาของ)
     * @return array{0:string, 1:array, 2:string, 3:?array}
     */
    private function runProductSearch(
        string $reply,
        string $userMessage,
        ?int $userId,
        array $history = [],
        bool $allowIntentFallback = true
    ): array {
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

        // 🙅 ปิดการเดาเจตนาเมื่อรู้แน่ว่าลูกค้าถามเรื่องบัญชีตัวเอง — แท็ก [FIND:] ข้างบนยังใช้ได้ปกติ
        if (! $allowIntentFallback) {
            if ($query === null || $query === '') {
                return [$reply !== '' ? $reply : 'ได้เลยค่ะ 😊', [], $this->guessMood($reply), null];
            }

            // AI ตั้งใจสั่งค้นมาเอง → ต้องรายงานผลให้ครบเหมือนเส้นทางปกติ (เจอ/ไม่เจอ ห้ามเงียบ)
            $products = $this->searchCatalog($query, $budget);
            $search = ['query' => $query, 'budget' => $budget, 'count' => count($products)];

            if (empty($products)) {
                $this->recordWish($query, $budget, $userId);
                $reply = trim($reply."\n\nค้นให้แล้วนะคะ แต่ยังไม่เจอ \"{$query}\" ในร้านค่ะ 🙏");
            }

            return [$reply !== '' ? $reply : 'ได้เลยค่ะ 😊', $products, $this->guessMood($reply), $search];
        }

        // ⭐ ชั้นที่ 1 — ถ้า AI ไม่ปล่อยแท็ก แต่ลูกค้าสื่อว่าอยากได้/หาสินค้า
        //    → ดึงคำค้นจาก "ข้อความลูกค้า" เองแล้วค้นให้เสมอ (โมเดลเล็กมักไม่ใส่แท็ก จึงต้องไม่พึ่งมัน)
        [$intentQuery, $intentBudget] = $this->extractSearchIntent($userMessage);
        if ($budget === null) {
            $budget = $intentBudget;   // งบจากข้อความลูกค้าใช้ได้เสมอ แม้คำค้นจะมาจากที่อื่น
        }
        if (($query === null || $query === '') && $intentQuery !== null) {
            $query = $intentQuery;
        }

        // 🚨 ชั้นที่ 2-3 — "ด่านกันเงียบ": Eve พูดไปแล้วว่ากำลังหา/จะหาให้ แต่ยังไม่ได้คำค้น
        //
        // ⚠️ ต้องกันเคส "สัญญาคนละเรื่อง" ด้วย: ลูกค้าถาม "ส่งของกี่วันคะ" แล้ว Eve ตอบ "เดี๋ยวเช็กให้นะคะ"
        //    ก็เข้าเงื่อนไข "สัญญา" เหมือนกัน แต่ไม่ใช่การหาสินค้า — ถ้าปล่อยผ่านจะไปขุดของจากประวัติ
        //    มาโชว์ทั้งที่เขาถามเรื่องขนส่ง (คำตอบเรื่องขนส่งมีอยู่แล้วในข้อความ = ไม่ใช่ทางตัน)
        $promised = $this->replyPromisesSearch($reply) && ! $this->looksLikeNonProductTalk($userMessage);

        if (($query === null || $query === '') && $promised) {
            // ชั้นที่ 2: ผ่อนกฎคำสัญญาณ — ลูกค้าพิมพ์ชื่อของเปล่าๆ ("หูฟังบลูทูธ", "สนใจปี่เซียะ")
            //           ปลอดภัยเพราะใช้เฉพาะตอน AI ตีความแล้วว่าเป็นการขอหาของ
            [$relaxedQuery, $relaxedBudget] = $this->extractSearchIntent($userMessage, true);
            if ($relaxedQuery !== null) {
                $query = $relaxedQuery;
                $budget ??= $relaxedBudget;
            } else {
                // ชั้นที่ 3: ย้อนดูเทิร์นก่อนหน้าของลูกค้า
                //           เคสจริง: Eve ถาม "งบเท่าไหร่คะ" ลูกค้าตอบ "500 บาท"
                //           ข้อความล่าสุดไม่มีชื่อสินค้าเลย → ต้องหยิบของที่คุยค้างไว้มาค้นต่อ
                $query = $this->recallQueryFromHistory($history);
            }
        }

        $products = [];
        $mood = $this->guessMood($reply);
        $search = null;

        if ($query !== null && $query !== '') {
            $products = $this->searchCatalog($query, $budget);
            $search = [
                'query' => $query,
                'budget' => $budget,
                'count' => count($products),
            ];

            if (empty($products)) {
                $this->recordWish($query, $budget, $userId);
                // 🙏 จริงใจ + ชวนปรับคำค้น (เลี่ยงสัญญา "รอ 10 นาที" ที่ยังไม่มีระบบส่งผลกลับจริง = ค้าง)
                $reply = trim($reply."\n\nค้นให้แล้วนะคะ แต่ตอนนี้ยังไม่เจอ \"{$query}\" ในร้านค่ะ 🙏 ลองบอกยี่ห้อ/รุ่น หรือพิมพ์คำค้นอื่นดูได้นะคะ เดี๋ยว Eve ค้นให้ใหม่ทันทีค่ะ");
                $mood = 'thinking';
            } else {
                $reply = trim($reply."\n\nเจอ ".count($products).' รายการที่น่าจะใช่ค่ะ 😊 ลองดูเลยนะคะ — ถ้าใช่กด "ดูสินค้า" ได้เลย หรืออยากให้หาแบบอื่นก็บอกได้ค่ะ');
                $mood = 'happy';
            }
        } elseif ($promised && ! $this->replyAlreadyAsks($reply)) {
            // ❗ สัญญาว่าจะหาแต่ไม่มีคำค้นเลย และก็ไม่ได้ถามอะไรกลับด้วย = ทางตัน (อาการ "เงียบไปเลย")
            //    ต้องถามกลับให้ชัดเสมอ ลูกค้าจะได้รู้ว่าต้องทำอะไรต่อ ไม่ใช่นั่งรอเก้อ
            Log::info('Eve: สัญญาว่าจะค้นแต่ดึงคำค้นไม่ได้ → ถามกลับแทนการเงียบ', [
                'message_preview' => mb_substr($userMessage, 0, 80),
            ]);

            $reply = trim($reply."\n\nรบกวนบอกชื่อสินค้าที่อยากได้อีกนิดได้ไหมคะ 🔎 เช่น \"หูฟังบลูทูธ งบ 500\" เดี๋ยว Eve ค้นให้ทันทีเลยค่ะ");
            $mood = 'thinking';
        }

        if ($reply === '') {
            $reply = 'ได้เลยค่ะ 😊';
        }

        return [$reply, $products, $mood, $search];
    }

    /**
     * ข้อความลูกค้าเป็น "เรื่องอื่นที่ไม่ใช่การหาสินค้า" หรือไม่
     *
     * ใช้ 2 ที่: (1) กันโหมดผ่อนกฎค้นมั่ว (2) กัน "สัญญาคนละเรื่อง" ไปขุดของเก่าจากประวัติมาโชว์
     */
    private function looksLikeNonProductTalk(string $message): bool
    {
        return (bool) preg_match(
            '/(สวัสดี|หวัดดี|ฮัลโหล|ขอบคุณ|ขอบใจ|ยินดี|เก่งมาก|น่ารัก|บาย|ลาก่อน|โอเค|ตกลง|ได้เลย|'
            .'สมัคร|สมาชิก|เข้าสู่ระบบ|ล็อกอิน|รหัสผ่าน|ยกเลิก|คืนเงิน|ค่าส่ง|กี่วัน|ส่งของ|ติดตามพัสดุ|'
            .'เธอคือใคร|คุณคือใคร|ชื่ออะไร|ทำอะไรได้)/u',
            $message
        );
    }

    /**
     * คำตอบของ Eve "สัญญาว่าจะไปหาของให้" หรือไม่
     *
     * ใช้เป็นสัญญาณว่า AI ตีความข้อความลูกค้าเป็น "การขอหาสินค้า" แล้ว
     * → ระบบต้องรับผิดชอบให้จบ (ค้นจริง หรือถามกลับ) ห้ามปล่อยเงียบ
     */
    private function replyPromisesSearch(string $reply): bool
    {
        if ($reply === '') {
            return false;
        }

        // ⚠️ ต้องเผื่อ "คำแทรก" ระหว่าง กำลัง/เดี๋ยว กับตัวกริยาเสมอ — โมเดลชอบพูดว่า
        //    "น้อง Eve กำลัง*ช่วยคัด*กระเป๋าให้เดี๋ยวนี้เลยค่ะ" / "กำลัง*ช่วยเลือก*ปี่เซียะ..."
        //    (ยืนยันจากคำตอบจริงบน prod) ถ้าจับแค่ "กำลังหา|กำลังค้น" ติดกันจะหลุดทั้งคู่
        //    = กลับไปเงียบเหมือนเดิมทั้งที่แก้แล้ว
        return (bool) preg_match(
            '/(กำลัง[^\n]{0,12}(?:ค้น|หา|จัดหา|เลือก|คัด|ดูให้)|ขอเวลา|เดี๋ยว[^\n]{0,14}(?:หา|ค้น|จัด|เลือก|คัด)|'
            .'(?:หา|ค้น|จัดหา|เลือก|คัด)ให้|รอสักครู่|รอแป๊บ|สักครู่นะคะ|ให้ทีมงาน|จัดให้|'
            .'ไปดูให้|ขอดูให้|เช็กให้|ตรวจสอบให้|เดี๋ยวนี้เลย)/u',
            $reply
        );
    }

    /**
     * คำตอบของ Eve "ถามกลับ" อยู่แล้วหรือไม่ (งบ/ยี่ห้อ/รุ่น/แบบไหน)
     *
     * ถ้าถามอยู่แล้ว ไม่ต้องต่อท้ายคำถามซ้ำอีก — ลูกค้ารู้อยู่แล้วว่าต้องตอบอะไร
     */
    private function replyAlreadyAsks(string $reply): bool
    {
        return (bool) preg_match(
            '/(\?|ไหมคะ|มั้ยคะ|อะไรคะ|แบบไหน|รุ่นไหน|ยี่ห้อ|งบ|ประมาณเท่าไหร่|เท่าไหร่|กี่บาท|สีอะไร)/u',
            $reply
        );
    }

    /**
     * ย้อนหา "คำค้นสินค้า" จากเทิร์นก่อนหน้าของลูกค้า
     *
     * เคสจริงที่ทำให้เงียบ: Eve ถามงบ → ลูกค้าตอบ "500 บาท" → ข้อความล่าสุดไม่มีชื่อสินค้า
     * ต้องหยิบสินค้าที่คุยค้างไว้ก่อนหน้ามาค้นต่อ ไม่งั้นบทสนทนาตายกลางคัน
     *
     * @param  array<int,array{role:string,content:string}>  $history
     */
    private function recallQueryFromHistory(array $history): ?string
    {
        // ดูย้อนหลังไม่เกิน 6 เทิร์นล่าสุดพอ — เก่ากว่านั้นมักเป็นคนละเรื่องแล้ว
        $recent = array_slice($history, -6);

        foreach (array_reverse($recent) as $turn) {
            if (($turn['role'] ?? '') !== 'user') {
                continue;
            }

            $content = (string) ($turn['content'] ?? '');

            // ลองแบบมีคำสัญญาณก่อน (แม่นกว่า) แล้วค่อยผ่อนกฎ
            [$q] = $this->extractSearchIntent($content);
            if ($q === null) {
                [$q] = $this->extractSearchIntent($content, true);
            }

            if ($q !== null) {
                return $q;
            }
        }

        return null;
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
     * @param  bool  $relaxed  ผ่อนกฎ "ต้องมีคำสัญญาณ" — ใช้เฉพาะตอน AI ตีความแล้วว่าลูกค้าขอหาของ
     *                         (ลูกค้าพิมพ์ชื่อของเปล่าๆ เช่น "หูฟังบลูทูธ" ไม่มีคำว่าหา/อยากได้เลย)
     * @return array{0: ?string, 1: ?float} [คำค้น (null = ไม่ใช่การหาสินค้า), งบ]
     */
    private function extractSearchIntent(string $message, bool $relaxed = false): array
    {
        $q = trim((string) preg_replace('/\s+/u', ' ', $message));
        if ($q === '') {
            return [null, null];
        }

        // 💰 ดึงงบ "ก่อน" ด่านคำสัญญาณเสมอ — ข้อความอย่าง "500 บาท" (ตอบคำถามงบของ Eve)
        //    ไม่มีชื่อสินค้าให้ค้น แต่ "งบ" ยังต้องส่งกลับไปใช้กับคำค้นที่ค้างอยู่ในประวัติ
        $budget = null;
        if (preg_match('/(?:งบ|ไม่เกิน|ภายใน|ไม่ถึง|ราคา)\s*([0-9][0-9,.]*)/u', $q, $b)
            || preg_match('/([0-9][0-9,.]*)\s*บาท/u', $q, $b)) {
            $num = (float) preg_replace('/[^0-9.]/', '', $b[1]);
            $budget = $num > 0 ? $num : null;
        }

        // 🚫 ทักทาย/ขอบคุณ/ถามเรื่องระบบ → ไม่ใช่การหาสินค้าแน่นอน (กันโหมดผ่อนกฎค้นมั่ว)
        if ($this->looksLikeNonProductTalk($q)) {
            return [null, $budget];
        }

        // ต้องมีสัญญาณว่ากำลังหา/อยากได้สินค้า ไม่งั้นถือเป็นคุยเล่น/ทักทาย (ไม่ค้น)
        // ⚠️ โหมดผ่อนกฎข้ามด่านนี้ได้ เพราะมี "คำสัญญาของ Eve" เป็นสัญญาณแทนแล้ว
        if (! $relaxed && ! preg_match('/(หา|อยากได้|อยากซื้อ|อยากดู|อยากลอง|หาซื้อ|ซื้อ|ขาย|มีขาย|สนใจ|ต้องการ|มองหา|ตามหา|'
            .'มี.{0,20}(ไหม|มั้ย|ป่าว|รึเปล่า|อีก)|แนะนำ|อยากหา|ช่วยหา|ช่วยดู|ขอดู|เอา|สั่ง|รุ่นไหน|ยี่ห้อ|ราคา|งบ)/u', $q)) {
            return [null, $budget];
        }

        // ตัดวลีงบ/ราคา (มีตัวเลข = ปลอดภัย ไม่ทับชื่อสินค้า)
        $q = preg_replace('/(?:งบ|ไม่เกิน|ภายใน|ไม่ถึง|ราคาไม่เกิน|ราคา)\s*[0-9][0-9,.]*\s*(?:บาท)?/u', ' ', $q);
        $q = preg_replace('/[0-9][0-9,.]*\s*บาท/u', ' ', $q);

        // ตัดคำสั่ง "หัวประโยค" (anchored ^) ทีละชั้น — คำประสมที่ยาว/ชัดก่อน
        $q = preg_replace('/^\s*(ช่วย|รบกวน)\s*/u', '', (string) $q);
        $q = preg_replace('/^\s*(ขอถาม|สอบถาม|อยากถาม|ขอสอบถาม)\s*/u', '', (string) $q);
        $q = preg_replace('/^\s*(ขอดู|อยากได้|อยากซื้อ|อยากดู|อยากลอง|อยากหา|หาซื้อ|หาให้|ช่วยหา|ช่วยดู|แนะนำ|สนใจ|ต้องการ|มองหา|ตามหา|สั่งซื้อ|หา|ซื้อ|สั่ง|เอา)\s*/u', '', (string) $q);

        // ตัดคำลงท้าย/คำถาม "ท้ายประโยค" (anchored $)
        // หมายเหตุ: "อีก/อย่างอื่น/อื่นๆ" ต้องตัดด้วย — "มีอย่างอื่นอีกไหม" คือขอดูตัวอื่นของ "สินค้าเดิม"
        //          เหลือคำเปล่าแล้วจะไปหยิบคำค้นเดิมจากประวัติมาค้นต่อ ตรงใจกว่าค้นคำว่า "อย่างอื่น"
        $q = preg_replace('/\s*(ราคาถูกๆ|ราคาถูก|ราคาประหยัด|เท่าไหร่|เท่าไร|ถูกๆ|ถูก|ดีๆ|สวยๆ|หน่อยค่ะ|หน่อยครับ|หน่อย|ด้วยค่ะ|ด้วยครับ|ด้วย|ให้หน่อย|ให้ที|ทีนะ|นะคะ|นะครับ|จ้า|ค่ะ|คะ|ครับ|มีไหม|มีมั้ย|ไหม|มั้ย|ป่าว|รึเปล่า|บ้าง|อย่างอื่น|อื่นๆ|อื่น|อีก|อะ|อ่ะ)+\s*$/u', '', (string) $q);

        // ตัดเครื่องหมายวรรคตอนหัว-ท้าย ("เอ่อ..." → "เอ่อ") ก่อนตัดสินว่าเป็นคำค้นได้ไหม
        $q = trim((string) preg_replace('/^[\p{P}\p{S}\s]+|[\p{P}\p{S}\s]+$/u', '', (string) $q));
        $q = trim((string) preg_replace('/\s+/u', ' ', $q));

        // เหลือสั้นไป / เป็นคำถามล้วน / เป็นคำอุทานลังเล → ไม่ค้น
        // (สำคัญ: คำค้นขยะจะถูกบันทึกเข้าคิว "ของที่ลูกค้าอยากได้" ของแอดมินด้วย ต้องกันตั้งแต่ตรงนี้)
        if (mb_strlen($q) < 2
            || preg_match('/^(อะไร|เท่าไหร่|เท่าไร|ยังไง|ที่ไหน|อันไหน|แบบไหน|ไง|ดี|มี|ของ|ราคา|อีก|อื่น|อย่างอื่น|'
                .'เอ่อ|เอิ่ม|อืม|อึม|อ่า|อ๋อ|เอ๊ะ|หืม|งง|โอ้|ฮะ|ฮ่ะ|555+)$/u', $q)) {
            return [null, $budget];
        }

        // ต้องมี "ตัวอักษร/ตัวเลข" อย่างน้อย 2 ตัว — กันคำค้นที่เหลือแต่สัญลักษณ์/อีโมจิ
        if (preg_match_all('/[\p{L}\p{N}]/u', $q) < 2) {
            return [null, $budget];
        }

        // ยาวผิดปกติ = เป็นประโยคเล่าเรื่อง ไม่ใช่ชื่อสินค้า → ค้นไปก็มั่ว (ปล่อยให้ไปถามกลับแทน)
        if (mb_strlen($q) > 60) {
            return [null, $budget];
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
                    // 🔒 escape ไวลด์การ์ดของ LIKE — ลูกค้าพิมพ์ "%" หรือ "_" ต้องหมายถึงตัวอักษรนั้นจริงๆ
                    //    ไม่งั้น "%%" = ตรงกับสินค้าทุกชิ้น (ผลลัพธ์มั่วไม่เกี่ยวกับที่ขอ)
                    $t = addcslashes($t, '%_\\');
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
