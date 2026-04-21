<?php

namespace App\Services;

use App\Models\AiApiKey;
use App\Models\AiContentSetting;
use App\Models\FortuneTellingSetting;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Fortune AI Service
 *
 * บริการสำหรับเชื่อมต่อกับ AI providers ต่างๆ
 * รองรับ: Gemini, Groq, Qwen, OpenRouter, Grok, DeepSeek, Typhoon
 *
 * รองรับ API Key Pool สำหรับวนใช้หลาย keys
 */
class FortuneAIService
{
    protected $settings;

    protected $provider;

    protected $apiKey;

    protected $model;

    protected ?AiApiKeyPoolService $poolService = null;

    protected ?AiApiKey $currentKey = null;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();

        // ใช้ methods ใหม่ที่รองรับ global AI settings
        $this->provider = $this->settings->getActualAIProvider();
        $this->model = $this->settings->getActualAIModel();

        // ลองใช้ API Key จาก Pool ก่อน (ครอบด้วย try-catch เผื่อตาราง pool ยังไม่มี)
        try {
            $this->poolService = new AiApiKeyPoolService;
            $this->currentKey = $this->poolService->acquireKey($this->provider);
        } catch (\Exception $e) {
            Log::warning('FortuneAIService: Pool service ใช้ไม่ได้ ข้ามไป', [
                'error' => $e->getMessage(),
            ]);
            $this->poolService = null;
            $this->currentKey = null;
        }

        if ($this->currentKey) {
            $this->apiKey = $this->currentKey->api_key;
            Log::debug('FortuneAIService: ใช้ API Key จาก Pool', [
                'provider' => $this->provider,
                'key_id' => $this->currentKey->id,
                'key_name' => $this->currentKey->name,
            ]);
        } else {
            // Fallback ไปใช้ key จาก settings
            $this->apiKey = $this->settings->getActualAIApiKey();
            Log::debug('FortuneAIService: ใช้ API Key จาก Settings (ไม่พบใน Pool)', [
                'provider' => $this->provider,
            ]);
        }
    }

    /**
     * ⭐ คืน key กลับ pool อัตโนมัติเมื่อ service ถูกทำลาย (จบ request)
     * ลด in-flight counter + บันทึก rpm
     */
    public function __destruct()
    {
        if ($this->poolService && $this->currentKey) {
            try {
                $this->poolService->releaseKey($this->provider, $this->currentKey->id);
            } catch (\Exception $e) {
                // ไม่ throw ใน destructor — log เฉยๆ
            }
        }
    }

    /**
     * Override provider/model/key สำหรับ Playground ทดสอบ
     */
    public function overrideForPlayground(string $provider, ?string $model, string $apiKey): void
    {
        $this->provider = $provider;
        $this->model = $model ?? $this->getDefaultModelForProvider($provider);
        $this->apiKey = $apiKey;
    }

    /**
     * กำหนด maxTokens และ temperature ตาม reading type
     */
    protected const READING_CONFIG = [
        'basic' => [
            'max_tokens' => 2048,
            'temperature' => 0.75,
        ],
        'deep' => [
            'max_tokens' => 1500,
            'temperature' => 0.8,
        ],
    ];

    /**
     * System message สำหรับ AI "แม่หมอจันทรา"
     * เป็นหมอดูสาวสวยวัย 35 ปี ใช้พลังหยั่งรู้ในการทำนาย ใช้คำแทนตัวว่า "หมอจันทรา"
     *
     * V3.1: ปรับปรุงให้ตอบธรรมชาติมากขึ้น — ไม่เบิ้ลคำลงท้าย ("ค่ะ/คะ" ใช้เท่าที่จำเป็น)
     * + สุ่มไพ่ทาโร่ 1 ใบ + เคล็ดเสริมดวง + ธรรมะทิ้งท้าย
     *
     * กฎพิเศษ:
     * - ถ้าถูกถามว่าเป็น AI: ตอบว่า "หมอจันทรามีทีมงานช่วยกันทำนายนะ"
     * - ถ้าถามนอกเรื่องดูดวง: ปฏิเสธสุภาพและชวนกลับมาดูดวง
     */
    protected const SYSTEM_MESSAGE = 'คุณชื่อ "หมอจันทรา" เป็นหมอดูสาววัย 35 ปี ผู้เชี่ยวชาญโหราศาสตร์ไทย (โหราศาสตร์เจ้าชนะ) โหราศาสตร์สากล ไพ่ทาโรต์ และเลขศาสตร์ ได้รับการถ่ายทอดวิชาจากครูบาอาจารย์สายลังกา คุณใช้คำแทนตัวเองว่า "หมอจันทรา" เช่น "หมอจันทราเห็นว่า..." "หมอจันทราขอบอกตรงๆ..."

[โทนการพูด — สำคัญมาก ต้องธรรมชาติเหมือนคนจริง]
- พูดอบอุ่น เป็นกันเอง เหมือนพี่สาวที่ห่วงใย แต่ฟันธง ฉะฉาน ไม่อ้อมค้อม
- ห้ามเบิ้ลคำลงท้าย "ค่ะ/คะ/นะคะ/นะค่ะ" ทุกประโยค! ให้ใช้เฉพาะ **ประโยคปิดท้ายย่อหน้าสำคัญ** เท่านั้น (ไม่เกิน 2-3 ครั้งต่อคำตอบ 1 ชุด)
- ภายในย่อหน้า ใช้ประโยคธรรมดา ไม่มีหางเสียง เช่น "ดวงการเงินเดือนหน้าจะเริ่มดีขึ้น ราว 15-20 ต.ค. จะมีรายได้เข้ามาก้อนหนึ่ง"
- สลับการลงท้ายให้หลากหลาย: "...นะ", "...เลย", "...ได้", "...ล่ะ", "..." (ไม่ลงท้ายอะไรก็ได้) แทนการใช้ "ค่ะ" ซ้ำๆ
- ห้ามใช้คำ "อาจจะ" "น่าจะ" "เป็นไปได้ว่า" — ให้ใช้ "จะ" "เห็นว่า" "คือ"
- ระบุช่วงเวลาชัดเจนเสมอ (เช่น "ภายใน 2 สัปดาห์" "เดือนหน้า") ห้าม "เร็วๆ นี้"

[หลักการวิเคราะห์ — ทำเบื้องหลัง ไม่ต้องบรรยาย]
ขั้นตอนวิเคราะห์ก่อนพยากรณ์ (คำนวณเงียบๆ ไม่ต้องแสดงให้ผู้ถามเห็น):
1) วิเคราะห์ราศีและลัคนาจากวันเดือนปีเกิด
2) หาดาวเจ้าชนะจากวันเกิด→ดูธาตุประจำราศี (ไฟ ดิน ลม น้ำ)→วิเคราะห์ลักษณะนิสัย จุดแข็ง จุดอ่อน
3) ดูตำแหน่งดาวเคราะห์ปัจจุบัน (transit) ที่ส่งผลต่อราศีเจ้าชะตา→ดูมิตร/ศัตรู→ดูภพที่ได้รับผล
4) เอาข้อมูลทั้งหมดมาวิเคราะห์แล้วค่อยพยากรณ์ตรงคำถาม

สำคัญ: ห้ามบรรยายกระบวนการคำนวณดาว/ราศี/ธาตุยืดยาวในคำตอบ! ให้วิเคราะห์เบื้องหลังแล้ว **ตอบตรงคำถามเลย** ถ้าถามเรื่องการเงิน→ตอบเรื่องการเงิน ถ้าถามเรื่องความรัก→ตอบเรื่องความรัก ห้ามอ้อมค้อมไปเล่าว่า "ดาวอาทิตย์อยู่ภพกัมมะ..." ให้สรุปผลที่เจ้าชะตาควรรู้เลย

[หลักเจ้าชนะ — ใช้คำนวณเบื้องหลัง]
ดาว 9 ดวง: อาทิตย์ จันทร์ อังคาร พุธ พฤหัสบดี ศุกร์ เสาร์ ราหู เกตุ
ภพ 12 ภพ: ตนุ กดุมภ สหัชชะ พันธุ ปุตตะ อริ ปัตนิ มรณะ ศุภะ กัมมะ ลาภะ วินาศ
ธาตุ 4: ไฟ(ร้อนแรง) ดิน(มั่นคง) ลม(ยืดหยุ่น) น้ำ(อ่อนโยน)

[ไพ่ทาโร่ — สุ่มจับ 1 ใบทุกครั้ง]
ทุกครั้งที่ทำนาย ให้สุ่มจับไพ่ทาโร่ 1 ใบจาก Major Arcana (22 ใบ) หรือ Minor Arcana ประกอบคำทำนาย:
- ระบุชื่อไพ่ที่จับได้ (ภาษาไทย+อังกฤษ) และหงาย/คว่ำ
- ตีความไพ่ให้สอดคล้องกับคำถามและดวงดาว
ตัวอย่าง: "🃏 ไพ่ที่จับได้: The Star (ดาว) ✨ หงาย — สัญลักษณ์ของความหวังและโอกาสใหม่"

[เคล็ดเสริมดวง]
แนะนำเคล็ดเสริมดวงที่ถูกต้องตามหลักโหราศาสตร์:
- สีมงคล (จากดาวมิตร) / สีที่ควรหลีกเลี่ยง (จากดาวศัตรู)
- เลขมงคล (จากดาวเจ้าชนะ)
- วันมงคล / ทิศมงคล
- ถ้าดวงไม่ดีในด้านที่ถาม: แนะนำวิธีแก้เคล็ดที่ทำได้จริง (ทำบุญ ไหว้พระ สวดมนต์ ใส่เครื่องราง หินสี) — ต้องถูกต้องตามความเชื่อจริง ห้ามแต่ง

[ธรรมะทิ้งท้าย — บังคับ]
ทุกคำทำนาย ลงท้ายด้วยข้อคิดธรรมะสั้นๆ 1-2 ประโยค เตือนสติอย่างอบอุ่น เลือกให้เหมาะบริบท:
- เรื่องเงิน→สันโดษ/ขยัน  เรื่องรัก→เมตตา/ให้อภัย  เรื่องงาน→วิริยะ/ความเพียร
ตัวอย่าง: "ดวงเป็นแนวโน้ม กรรมดีคือที่พึ่งที่แท้จริง 🙏" / "ดวงดาวชี้ทาง แต่คนเดินเอง 🌟"

[เข้าใจบริบทผู้ถาม]
- ถ้ารู้อายุ: ปรับน้ำเสียง (วัยรุ่น→สนุก, วัยทำงาน→เชิงกลยุทธ์, สูงอายุ→สุภาพ)
- LGBTQ+: ทำนายอย่างเคารพ เท่าเทียม ใช้คำว่า "คนที่ใจรัก" "คนพิเศษ"
- ถ้ามีวันเกิด: วิเคราะห์ดาวเจ้าชนะ ราศี ลัคนา ธาตุ
- ถ้าไม่มีวันเกิด: ถามท้ายคำทำนาย "บอกวันเดือนปีเกิดให้หมอจันทราหน่อยได้ไหม จะได้ทำนายแม่นขึ้น 🎂" (แบบนี้พอ — ไม่ต้องซ้ำ "ค่ะ/นะคะ" อีก)

ท้ายคำทำนายชวนแชร์สั้นๆ เช่น "ถ้าถูกใจ ฝากส่งต่อให้เพื่อนมาลองดูดวงด้วย 🔮" (ไม่ต้องเติมหางเสียง)

[โครงสร้างคำตอบ]
1) ทักทายสั้น 1 บรรทัด (เช่น "หมอจันทราสวัสดี 🙏")
2) ตอบตรงคำถาม — ฟันธง ชัดเจน (ห้ามบรรยายดาวยืดยาว)
3) 🃏 ไพ่ที่จับได้ + ตีความสั้น สอดคล้องคำถาม
4) ✨ เคล็ดเสริมดวง (สี/เลข/วัน/วิธีแก้เคล็ด)
5) 🙏 ธรรมะทิ้งท้าย 1-2 ประโยค
6) ชวนถามต่อหรือชวนแชร์

[วิธีตอบข้อความ]
1) ทักทาย → ทักทายกลับสั้นๆ แล้วทำนายดวงภาพรวมให้เลย (ห้ามตอบแค่ทักทาย)
2) คำถามดูดวง → ทำนายเต็มที่ ตรงประเด็น ฟันธง
3) คำถามนอกเรื่อง → ตอบสั้น แล้วชวนดูดวง
4) ถามว่าเป็น AI → "หมอจันทรามีทีมงานช่วยกันทำนายนะ ไม่ต้องห่วง 🔮"
5) พิมพ์ "เช็คสิทธิ์" → บอกให้พิมพ์ "เช็คสิทธิ์" เพื่อดูสิทธิ์ที่เหลือ

[กฎสำคัญ — ต้องทำทุกครั้ง]
- ตอบธรรมชาติเหมือนคนจริง — **ห้ามเบิ้ล "ค่ะ/คะ/นะคะ" ทุกประโยค**! ใช้เฉพาะปิดประโยคสำคัญ 2-3 จุดต่อคำตอบ
- ฟันธง ตรงประเด็น บอกตรงๆ ทั้งดีและร้าย
- สุ่มไพ่ทาโร่ 1 ใบทุกครั้ง + เคล็ดเสริมดวง + ธรรมะทิ้งท้าย
- ห้ามเขียนโค้ด ห้ามข้อมูลอันตราย
- ⚠️ เรียกชื่อลูกค้าแค่ครั้งเดียวตอนทักทาย หลังจากนั้นใช้ "เจ้าชะตา" แทน (ประหยัด token)';

    /**
     * System message สำหรับ AI Chat ทั่วไป (สนทนาไม่ใช่ทำนาย)
     *
     * ใช้ persona "หมอจันทรา" เหมือนกัน แต่โหมดสนทนาเป็นมิตร
     * ข้อมูลระบบดูดวง (ราคา, commission ฯลฯ) จะถูก inject แบบ dynamic
     * ผ่าน buildChatSystemMessage() เพื่อให้ตรงกับ settings จริง
     */
    protected const CHAT_SYSTEM_MESSAGE_TEMPLATE = 'คุณชื่อ "หมอจันทรา" เป็นผู้หญิงไทยวัย 35 ปี ผู้เชี่ยวชาญด้านโหราศาสตร์และที่ปรึกษาประจำระบบดูดวง Thaiprompt ใช้คำแทนตัวว่า "หมอจันทรา" เช่น "หมอจันทราว่า..." คุณอบอุ่น เป็นกันเอง พูดจาเพราะเหมือนพี่สาวที่ห่วงใย ใส่ emoji น่ารักบ้าง

[โทนการพูด — สำคัญ]
- พูดธรรมชาติเหมือนคนจริง — ประโยคส่วนใหญ่ **ไม่ต้องใส่ "ค่ะ/คะ/นะคะ"**
- ใช้หางเสียง "ค่ะ/คะ" เฉพาะ **ปิดท้ายประโยคสำคัญ** ไม่เกิน 1-2 ครั้งต่อข้อความ 1 ชุด (2-4 ประโยค)
- สลับได้: "...นะ" "...เลย" "...ได้" หรือไม่ลงท้ายอะไรก็ได้
- ตัวอย่างที่ดี: "หมอจันทราแนะนำให้ลองพิมพ์ ดูดวงความรัก ดูก่อน รอบเดียวเห็นภาพรวมเลย 🔮"
- ห้าม: "หมอจันทราแนะนำให้ลองดูดวงความรักค่ะ พิมพ์ดูดวงความรักมาเลยนะคะ รอบเดียวค่ะ 🔮"

[ข้อมูลระบบดูดวง Thaiprompt ที่คุณต้องรู้]
- ดูดวงฟรีได้วันละ {maxFreeReadings} ครั้ง
- ดูดวงเชิงลึก (Deep Reading) ค่าครู {deepReadingPrice} บาท/ครั้ง โดยหมอจันทราวิเคราะห์จากวันเกิดและคำถามของผู้ใช้ (ใช้คำว่า "ค่าครู" เสมอ ไม่ใช่ "ค่าบริการ")
- หัวข้อดูดวงที่ได้: ความรัก, การเงิน, การงาน, สุขภาพ, โชคลาภ, ครอบครัว, การเรียน, เดินทาง
- วิธีดูดวง: พิมพ์ "ดูดวง" หรือพิมพ์หัวข้อตรงๆ เช่น "ดวงความรัก" "ดวงการเงินปีนี้"

[ระบบแชร์รายได้/Affiliate — แผนค่าแนะนำ 2 ชั้น]
- ชั้น 1 (สายตรง): ได้ {level1Commission} บาท ทุกครั้งที่คนที่คุณแนะนำดูดวงเชิงลึก
- ชั้น 2 (ชั้นหลาน): ได้ {level2Commission} บาท ทุกครั้งที่คนที่สายตรงแนะนำต่อดูดวงเชิงลึก
- วิธีเริ่ม: พิมพ์ "แชร์" เพื่อรับลิงก์เชิญเพื่อน
- ค่าแนะนำเข้า Wallet อัตโนมัติ ถอนได้ใน 1-3 วันทำการ
- คำสั่ง: "สายงาน" / "รายได้" / "แชร์" / "แผนการตลาด"

[กฎสำคัญ]
1) ตอบภาษาไทย กระชับ 2-4 ประโยค — **โดยรวมใช้ "ค่ะ/คะ" ไม่เกิน 1-2 จุดต่อข้อความ**
2) ตอบคำถามเกี่ยวกับระบบดูดวง (วิธีใช้, ราคา, แชร์รายได้, Wallet, ถอนเงิน) และคำถามทั่วไปได้
3) ท้ายข้อความ แนะนำสั้นๆ เช่น "พิมพ์ ดูดวง หรือ เมนู เพื่อดูคำสั่งทั้งหมด" (ไม่ต้องเติมหางเสียงซ้ำ)
4) ไม่เข้าใจคำถาม → ถามกลับแบบธรรมชาติ เช่น "หมายถึงเรื่องไหนเหรอ? เช่น วิธีดูดวง แชร์รายได้ หรือถอนเงิน 🤔"
5) ห้ามแต่งข้อมูลที่ไม่รู้ ถ้าไม่รู้ให้ถามเพิ่มหรือชี้ไปเว็บไซต์
6) ถามว่าเป็น AI → "หมอจันทรามีทีมงานช่วยกันทำนายนะ ไม่ต้องห่วง 🔮"
7) ห้ามเขียนโค้ด ห้ามข้อมูลอันตราย ห้ามการเมืองอ่อนไหว
8) พูดให้กำลังใจเมื่อคนทุกข์ใจ
9) สำคัญ: ถ้าตอบไม่ได้หรือต้องให้แอดมิน ให้ใส่ [ASK_SAVE] ท้ายข้อความ + ตอบ "หมอจันทราไม่แน่ใจเรื่องนี้ จะฝากถึงแอดมินให้มาตอบกลับไหม 📝"
10) สำคัญ: ถ้าผู้ใช้สนใจดูดวงเชิงลึก/ละเอียด/แบบเสียเงิน (เช่น "อยากดูแบบละเอียด", "สนใจเจาะลึก", "มีแบบพรีเมียมไหม") ให้ใส่ [DEEP_READING] ท้ายข้อความ + แนะนำบริการ

[สร้าง rapport ก่อนเสนอดูดวง — สำคัญมาก]
- **รอบ 1-2**: คุยทำความเข้าใจบริบทก่อน — ถามต่อเพื่อรู้สถานการณ์ ไม่เสนอดูดวงทันที เช่น
  * ยูสเซ่อร์: "หนูเครียดเรื่องงาน" → หมอจันทรา: "เข้าใจเลย... ช่วยเล่าหน่อยได้ไหมว่าเป็นเรื่องอะไรเป็นพิเศษ? เพื่อนร่วมงาน เงินเดือน หรือเจ้านาย?"
  * ให้เหตุผล/ทางออกเบื้องต้น 1-2 ประเด็น ไม่ว่างเปล่า
- **รอบ 2-4 ขึ้นไป** (เมื่อเข้าใจบริบทแล้ว): เสนอดูดวงโดยใส่ **[OFFER_FORTUNE]** ท้ายข้อความ + สรุปว่าน่าดูดวงเรื่องไหน เช่น
  * "จากที่เล่ามา หมอจันทราว่าควรดูดวงเรื่องการงานช่วง 3 เดือนข้างหน้า จะเห็นชัดว่าดาวจะหนุนทางไหน [OFFER_FORTUNE]"
- **ห้ามใส่ [OFFER_FORTUNE] ตั้งแต่รอบแรก** — ต้องสร้าง rapport 2 ครั้งเป็นอย่างน้อย
- ข้อมูล turn count จะอยู่ใน context message ถ้ามี (เช่น "TURN 3") — ใช้เป็นตัวตัดสิน';

    /**
     * สร้าง AI Chat Response สำหรับสนทนาทั่วไป (ไม่ใช่ทำนาย)
     *
     * ใช้ provider แยกจากการทำนาย (Gemini สำหรับ chat, Grok สำหรับ fortune)
     * ตั้งค่าแยกกันได้ที่ Admin → Fortune Settings → AI Chat ทั่วไป
     *
     * @param  string  $messageText  ข้อความจากผู้ใช้
     * @param  array|null  $userProfile  ข้อมูลโปรไฟล์ผู้ใช้
     * @return array ['response' => string, 'provider' => string, 'model' => string]
     *
     * @throws Exception เมื่อไม่มี API Key หรือ API ล้มเหลว
     */
    public function generateChatResponse(string $messageText, ?array $userProfile = null): array
    {
        // ดึง chat-specific settings
        $chatProvider = $this->settings->getChatAIProvider();
        $chatModel = $this->settings->getChatAIModel();
        $chatApiKey = $this->settings->getChatAIApiKey();
        $customPrompt = $this->settings->getChatSystemPrompt();

        if (empty($chatApiKey)) {
            throw new Exception("ไม่พบ API Key สำหรับ Chat AI ({$chatProvider})");
        }

        // เลือก system message: ใช้ custom ถ้ามี, ไม่งั้นใช้ default + inject ข้อมูลจริง
        $systemMessage = ! empty($customPrompt) ? $customPrompt : $this->buildChatSystemMessage();

        // สร้าง prompt สั้นๆ สำหรับ chat
        $userName = $userProfile['name'] ?? '';
        $prompt = $messageText;
        if (! empty($userName) && $userName !== 'คุณ') {
            $prompt = "(ผู้ใช้ชื่อ: {$userName}) {$messageText}";
        }

        $config = [
            'temperature' => 0.8,
            'max_tokens' => 512,
        ];

        $startTime = microtime(true);

        try {
            $result = match ($chatProvider) {
                'gemini' => $this->callChatGemini($prompt, $systemMessage, $chatApiKey, $chatModel, $config),
                default => $this->callChatOpenAICompatible($prompt, $systemMessage, $chatApiKey, $chatModel, $chatProvider, $config),
            };

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            Log::info('FortuneAIService: Chat response สำเร็จ', [
                'provider' => $chatProvider,
                'model' => $chatModel,
                'response_time_ms' => $responseTime,
                'tokens' => $result['tokens_used'] ?? 0,
            ]);

            return $result;

        } catch (Exception $e) {
            Log::warning('FortuneAIService: Chat response ล้มเหลว', [
                'provider' => $chatProvider,
                'model' => $chatModel,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * สร้าง AI Chat Response พร้อม conversation history (ความจำ)
     *
     * ใช้สำหรับสนทนาต่อเนื่อง — AI จะจำบริบทจากข้อความก่อนหน้า
     * ส่ง history เป็น messages array ไป AI provider
     *
     * @param  string  $messageText  ข้อความจากผู้ใช้
     * @param  array|null  $userProfile  ข้อมูลโปรไฟล์ผู้ใช้
     * @param  array  $history  ประวัติสนทนา [['role' => 'user'|'assistant', 'content' => '...'], ...]
     * @return array ['response' => string, 'provider' => string, 'model' => string]
     *
     * @throws Exception เมื่อไม่มี API Key หรือ API ล้มเหลว
     */
    public function generateChatResponseWithHistory(
        string $messageText,
        ?array $userProfile = null,
        array $history = []
    ): array {
        // ถ้าไม่มี history → ใช้ generateChatResponse ปกติ
        if (empty($history)) {
            return $this->generateChatResponse($messageText, $userProfile);
        }

        // ดึง chat-specific settings
        $chatProvider = $this->settings->getChatAIProvider();
        $chatModel = $this->settings->getChatAIModel();
        $chatApiKey = $this->settings->getChatAIApiKey();
        $customPrompt = $this->settings->getChatSystemPrompt();

        if (empty($chatApiKey)) {
            throw new Exception("ไม่พบ API Key สำหรับ Chat AI ({$chatProvider})");
        }

        // เลือก system message
        $systemMessage = ! empty($customPrompt) ? $customPrompt : $this->buildChatSystemMessage();

        // สร้าง prompt พร้อมชื่อผู้ใช้
        $userName = $userProfile['name'] ?? '';
        $prompt = $messageText;
        if (! empty($userName) && $userName !== 'คุณ') {
            $prompt = "(ผู้ใช้ชื่อ: {$userName}) {$messageText}";
        }

        $config = [
            'temperature' => 0.8,
            'max_tokens' => 512,
        ];

        $startTime = microtime(true);

        try {
            $result = match ($chatProvider) {
                'gemini' => $this->callChatGeminiWithHistory($prompt, $systemMessage, $chatApiKey, $chatModel, $config, $history),
                default => $this->callChatOpenAICompatibleWithHistory($prompt, $systemMessage, $chatApiKey, $chatModel, $chatProvider, $config, $history),
            };

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            Log::info('FortuneAIService: Chat with history สำเร็จ', [
                'provider' => $chatProvider,
                'model' => $chatModel,
                'response_time_ms' => $responseTime,
                'history_count' => count($history),
                'tokens' => $result['tokens_used'] ?? 0,
            ]);

            return $result;

        } catch (Exception $e) {
            Log::warning('FortuneAIService: Chat with history ล้มเหลว ลอง fallback ไม่มี history', [
                'provider' => $chatProvider,
                'error' => $e->getMessage(),
            ]);
            // Fallback: ลองส่งโดยไม่มี history
            return $this->generateChatResponse($messageText, $userProfile);
        }
    }

    /**
     * เรียก Gemini API พร้อม conversation history
     *
     * Gemini ใช้ format: contents[] = [{role: 'user', parts: [...]}, {role: 'model', parts: [...]}]
     */
    protected function callChatGeminiWithHistory(
        string $prompt,
        string $systemMessage,
        string $apiKey,
        string $model,
        array $config,
        array $history
    ): array {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        // สร้าง contents จาก history (Gemini ใช้ 'model' แทน 'assistant')
        $contents = [];
        foreach ($history as $msg) {
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]],
            ];
        }
        // เพิ่มข้อความปัจจุบัน
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $prompt]],
        ];

        $response = Http::timeout(20)->post($url, [
            'system_instruction' => [
                'parts' => [['text' => $systemMessage]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $config['temperature'] ?? 0.8,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => $config['max_tokens'] ?? 512,
            ],
        ]);

        if (! $response->successful()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? $response->body();
            throw new Exception("Gemini Chat (history) Error: {$errorMessage}");
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty($text)) {
            throw new Exception('Gemini Chat (history): ไม่ได้รับคำตอบ');
        }

        return [
            'response' => $text,
            'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
            'provider' => 'gemini',
            'model' => $model,
        ];
    }

    /**
     * เรียก OpenAI-compatible API พร้อม conversation history
     *
     * OpenAI format: messages[] = [{role: 'system', ...}, {role: 'user', ...}, {role: 'assistant', ...}]
     */
    protected function callChatOpenAICompatibleWithHistory(
        string $prompt,
        string $systemMessage,
        string $apiKey,
        string $model,
        string $provider,
        array $config,
        array $history
    ): array {
        $url = match ($provider) {
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'grok' => 'https://api.x.ai/v1/chat/completions',
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'deepseek' => 'https://api.deepseek.com/chat/completions',
            'typhoon' => 'https://api.opentyphoon.ai/v1/chat/completions',
            'qwen' => 'https://router.huggingface.co/v1/chat/completions',
            default => throw new Exception("Chat provider '{$provider}' ไม่รองรับ"),
        };

        $headers = ['Authorization' => "Bearer {$apiKey}"];
        if ($provider === 'openrouter') {
            $headers['HTTP-Referer'] = config('app.url');
        }

        // สร้าง messages array: system → history → user message ปัจจุบัน
        $messages = [
            ['role' => 'system', 'content' => $systemMessage],
        ];

        // เพิ่ม history (จำกัด 10 ข้อความล่าสุด เพื่อประหยัด tokens)
        $recentHistory = array_slice($history, -10);
        foreach ($recentHistory as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        // เพิ่มข้อความปัจจุบัน
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $response = Http::timeout(20)
            ->withHeaders($headers)
            ->post($url, [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $config['temperature'] ?? 0.8,
                'max_tokens' => $config['max_tokens'] ?? 512,
            ])->throw();

        $data = $response->json();
        $text = $data['choices'][0]['message']['content'] ?? '';

        if (empty($text)) {
            throw new Exception("Chat {$provider} (history): ไม่ได้รับคำตอบ");
        }

        return [
            'response' => $text,
            'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            'provider' => $provider,
            'model' => $model,
        ];
    }

    /**
     * สร้าง system message สำหรับ AI Chat โดย inject ข้อมูลจริงจาก settings
     *
     * ทำให้ AI รู้จักราคา, จำนวนฟรี, ค่าคอมมิชชั่น ตามที่ตั้งค่าจริง
     * ไม่ hardcode ตัวเลข — แปรผันตาม admin settings
     */
    protected function buildChatSystemMessage(): string
    {
        $maxFreeReadings = (int) ($this->settings->max_free_readings ?? 3);
        $deepReadingPrice = number_format((float) ($this->settings->deep_reading_price ?? 99), 0);
        $freeEnabled = $this->settings->isFreeReadingEnabled();

        // หากปิดบริการฟรี → ใช้ข้อความแบบไม่พูดถึงฟรีเลย
        $freeLineForPrompt = $freeEnabled
            ? "- ดูดวงฟรีได้วันละ {$maxFreeReadings} ครั้ง"
            : "- บริการดูดวงฟรีปิดอยู่ — ทุกคำถามคิดเป็นค่าครูตามราคา";

        // คำนวณค่าคอมมิชชั่นจาก settings
        $mode = $this->settings->getFortuneCommissionMode();
        $level1Commission = '0';
        $level2Commission = '0';
        if ($mode === 'static') {
            $commissionAmount = $this->settings->getFortuneStaticCommissionAmount();
            $commissionText = number_format($commissionAmount, 0).' บาท';
            $level1Commission = number_format($this->settings->getFortuneLevel1Amount((float) ($this->settings->deep_reading_price ?? 0)), 0);
            $level2Commission = number_format($this->settings->getFortuneLevel2Amount((float) ($this->settings->deep_reading_price ?? 0)), 0);
        } else {
            $preview = $this->settings->calculateFortuneCommissionPreview();
            $level1 = $preview['levels'][0] ?? null;
            $level2 = $preview['levels'][1] ?? null;
            $commissionAmount = $level1 ? $level1['amount'] : 0;
            $commissionText = number_format($commissionAmount, 2).' บาท';
            $level1Commission = number_format($level1 ? $level1['amount'] : 0, 0);
            $level2Commission = number_format($level2 ? $level2['amount'] : 0, 0);
        }

        // แทนที่ placeholder ด้วยข้อมูลจริง
        $message = str_replace(
            ['{maxFreeReadings}', '{deepReadingPrice}', '{commissionText}', '{level1Commission}', '{level2Commission}'],
            [$maxFreeReadings, $deepReadingPrice, $commissionText, $level1Commission, $level2Commission],
            self::CHAT_SYSTEM_MESSAGE_TEMPLATE
        );

        // แทนที่บรรทัดที่พูดถึงฟรี — conditional ตามสถานะจริง
        $message = str_replace(
            "- ดูดวงฟรีได้วันละ {$maxFreeReadings} ครั้ง",
            $freeLineForPrompt,
            $message
        );

        // ถ้าปิดฟรี → เปลี่ยนคำว่า "ค่าบริการ" ให้เป็น "ค่าครู" และเน้นว่าไม่มีฟรี
        if (! $freeEnabled) {
            $message .= "\n\n[สำคัญ] บริการดูดวงฟรีถูกปิดชั่วคราว — ห้ามพูดถึงฟรีเลย ถ้าลูกค้าถามเรื่องราคา ให้ตอบว่าเป็น 'ค่าครู' ทุกครั้ง ไม่ใช่ 'ค่าบริการ'";
        }

        return $message;
    }

    /**
     * เรียก Gemini API สำหรับ Chat (ใช้ system message + API key เฉพาะ chat)
     */
    protected function callChatGemini(string $prompt, string $systemMessage, string $apiKey, string $model, array $config): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(15)->post($url, [
            'system_instruction' => [
                'parts' => [['text' => $systemMessage]],
            ],
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => $config['temperature'] ?? 0.8,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => $config['max_tokens'] ?? 512,
            ],
        ]);

        if (! $response->successful()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? $response->body();
            throw new Exception("Gemini Chat API Error: {$errorMessage}");
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty($text)) {
            throw new Exception('Gemini Chat: ไม่ได้รับคำตอบ (empty response)');
        }

        return [
            'response' => $text,
            'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
            'provider' => 'gemini',
            'model' => $model,
        ];
    }

    /**
     * เรียก OpenAI-compatible API สำหรับ Chat (Groq, Grok, DeepSeek, Typhoon, etc.)
     */
    protected function callChatOpenAICompatible(string $prompt, string $systemMessage, string $apiKey, string $model, string $provider, array $config): array
    {
        $url = match ($provider) {
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'grok' => 'https://api.x.ai/v1/chat/completions',
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'deepseek' => 'https://api.deepseek.com/chat/completions',
            'typhoon' => 'https://api.opentyphoon.ai/v1/chat/completions',
            'qwen' => 'https://router.huggingface.co/v1/chat/completions',
            default => throw new Exception("Chat provider '{$provider}' ไม่รองรับ"),
        };

        $headers = ['Authorization' => "Bearer {$apiKey}"];
        if ($provider === 'openrouter') {
            $headers['HTTP-Referer'] = config('app.url');
        }

        $response = Http::timeout(15)
            ->withHeaders($headers)
            ->post($url, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $config['temperature'] ?? 0.8,
                'max_tokens' => $config['max_tokens'] ?? 512,
            ])->throw();

        $data = $response->json();
        $text = $data['choices'][0]['message']['content'] ?? '';

        if (empty($text)) {
            throw new Exception("Chat {$provider}: ไม่ได้รับคำตอบ (empty response)");
        }

        return [
            'response' => $text,
            'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            'provider' => $provider,
            'model' => $model,
        ];
    }

    /**
     * สร้างคำทำนายจาก AI
     *
     * @param  array  $questions  คำถามที่ต้องการทำนาย
     * @param  array|null  $userProfile  ข้อมูลโปรไฟล์ผู้ใช้
     * @param  array|null  $userPosts  โพสล่าสุดของผู้ใช้ (เฉพาะเชิงลึก)
     * @param  string|null  $promptTemplate  Prompt template ที่กำหนดเอง (ถ้าไม่ระบุจะใช้ค่าเริ่มต้น)
     * @param  string  $readingType  ประเภทคำทำนาย: 'basic' หรือ 'deep' (ส่งผลต่อ maxTokens/temperature)
     * @param  string|null  $birthDate  วันเดือนปีเกิด (Y-m-d) เพื่อทำนายตามราศี/ลัคนา
     */
    public function generateFortuneTelling(
        array $questions,
        ?array $userProfile = null,
        ?array $userPosts = null,
        ?string $promptTemplate = null,
        string $readingType = 'basic',
        ?string $birthDate = null
    ): array {
        // ตรวจสอบว่ามี API Key ก่อนเรียก AI
        if (empty($this->apiKey)) {
            Log::error('FortuneAIService: ไม่มี API Key สำหรับ provider', [
                'provider' => $this->provider,
                'model' => $this->model,
            ]);
            throw new Exception("ไม่พบ API Key สำหรับ {$this->provider} - กรุณาตั้งค่า API Key ในระบบ Admin");
        }

        $prompt = $this->buildPrompt($questions, $userProfile, $userPosts, $promptTemplate, $birthDate);
        $config = self::READING_CONFIG[$readingType] ?? self::READING_CONFIG['basic'];

        $startTime = microtime(true);

        try {
            $result = match ($this->provider) {
                'gemini' => $this->callGemini($prompt, $config),
                'groq' => $this->callGroq($prompt, $config),
                'grok' => $this->callGrok($prompt, $config),
                'qwen' => $this->callQwen($prompt, $config),
                'openrouter' => $this->callOpenRouter($prompt, $config),
                'deepseek' => $this->callDeepSeek($prompt, $config),
                'typhoon' => $this->callTyphoon($prompt, $config),
                default => throw new Exception("AI Provider '{$this->provider}' ไม่รองรับ"),
            };

            // บันทึกการใช้งาน tokens ผ่าน Pool
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            $this->recordUsage($result['tokens_used'] ?? 0, $result['model'] ?? $this->model, $responseTime, $readingType);

            return $result;
        } catch (Exception $e) {
            // บันทึก error ผ่าน Pool
            $this->recordError($e->getMessage(), $this->model);
            throw $e;
        }
    }

    /**
     * สร้างคำทำนายพร้อม retry + สลับ provider อัตโนมัติ
     *
     * ลองต่อ AI หลายครั้ง ถ้า provider หลักล้มเหลว จะลองสลับไป provider อื่นอัตโนมัติ
     * ไม่ใช้ fallback ข้อความเขียนไว้ล่วงหน้า - ต้องต่อ AI ให้ได้จริง
     *
     * ลำดับการลอง:
     * 1. Provider หลัก - ลอง 2 ครั้ง (เว้น 2 วินาที)
     * 2. Provider สำรองจาก API Key Pool
     * 3. Provider สำรองจาก Global AI Settings
     *
     * @param  array  $questions  คำถาม
     * @param  array|null  $userProfile  โปรไฟล์ผู้ใช้
     * @param  array|null  $userPosts  โพสล่าสุด
     * @param  string|null  $promptTemplate  Prompt template
     * @param  string  $readingType  ประเภทคำทำนาย: 'basic' หรือ 'deep'
     * @param  string|null  $birthDate  วันเดือนปีเกิด
     * @return array ผลลัพธ์จาก AI
     *
     * @throws Exception เมื่อทุก provider ล้มเหลวหมด
     */
    public function generateWithRetryAndFallback(
        array $questions,
        ?array $userProfile = null,
        ?array $userPosts = null,
        ?string $promptTemplate = null,
        string $readingType = 'basic',
        ?string $birthDate = null
    ): array {
        $errors = [];
        $prompt = $this->buildPrompt($questions, $userProfile, $userPosts, $promptTemplate, $birthDate);
        $config = self::READING_CONFIG[$readingType] ?? self::READING_CONFIG['basic'];
        $startTime = microtime(true);

        // ✅ รวม keys ทั้งหมดจาก Pool + Settings + Global Settings เป็นรายการเดียว
        // เรียงลำดับ: primary provider keys ก่อน → providers อื่น
        // วนลองทุก key — สลับทันทีเมื่อโดน 429 ไม่ต้องรอ 60 วินาที
        $allKeys = $this->getAllAvailableKeys();

        Log::info('FortuneAI: เริ่มสร้างคำทำนาย — รวม keys ทั้งหมด', [
            'primary_provider' => $this->provider,
            'total_keys' => count($allKeys),
            'keys' => array_map(fn ($k) => "{$k['provider']}/{$k['name']}({$k['source']})", $allKeys),
        ]);

        if (empty($allKeys)) {
            // Fallback: ถ้าไม่มี key จาก Pool/Settings เลย ลอง key เดิมจาก constructor
            if (! empty($this->apiKey)) {
                $allKeys = [[
                    'provider' => $this->provider,
                    'api_key' => $this->apiKey,
                    'model' => $this->model,
                    'pool_key' => $this->currentKey,
                    'source' => 'constructor',
                    'name' => 'Constructor Key',
                ]];
            } else {
                throw new Exception('ไม่มี API Key ที่ใช้ได้เลย — กรุณาเพิ่ม key ในระบบ AI API Key Pool');
            }
        }

        // วนลอง keys ทั้งหมด — สลับทันทีเมื่อโดน error/429
        foreach ($allKeys as $index => $keyInfo) {
            try {
                $keyLabel = "{$keyInfo['provider']}/{$keyInfo['name']}";
                $keyNum = $index + 1;
                $totalKeys = count($allKeys);
                Log::info("FortuneAI: ลอง key [{$keyNum}/{$totalKeys}] {$keyLabel}");

                $result = $this->callProviderDirect(
                    $keyInfo['provider'], $keyInfo['api_key'], $keyInfo['model'], $prompt, $config
                );

                // สำเร็จ! — บันทึก usage
                $responseTime = (int) ((microtime(true) - $startTime) * 1000);
                if ($keyInfo['pool_key'] instanceof AiApiKey) {
                    $this->recordUsageForKey($keyInfo['pool_key'], $result['tokens_used'] ?? 0, $result['model'] ?? $keyInfo['model'], $responseTime, $readingType);
                }

                Log::info("FortuneAI: สำเร็จ! ใช้ key [{$keyNum}/{$totalKeys}] {$keyLabel}", [
                    'tokens_used' => $result['tokens_used'] ?? 0,
                    'response_time_ms' => $responseTime,
                ]);

                return $result;
            } catch (Exception $e) {
                $keyLabel = "{$keyInfo['provider']}/{$keyInfo['name']}";
                $keyNum = $index + 1;
                $totalKeys = count($allKeys);
                $errors[] = "{$keyLabel}: " . Str::limit($e->getMessage(), 150);

                // บันทึก error ลง Pool key (ถ้ามี)
                if ($keyInfo['pool_key'] instanceof AiApiKey) {
                    $this->recordErrorForKey($keyInfo['pool_key'], $e->getMessage(), $keyInfo['model']);
                }

                $is429 = str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'rate_limit');
                Log::warning("FortuneAI: key [{$keyNum}/{$totalKeys}] {$keyLabel} ล้ม", [
                    'error' => Str::limit($e->getMessage(), 200),
                    'is_rate_limit' => $is429,
                    'remaining_keys' => $totalKeys - $keyNum,
                ]);

                // Circuit Breaker: track 429 per provider
                // ถ้าโดน 429 ≥ 5 ครั้ง ใน 60 วินาที → mark provider down 2 นาที
                if ($is429) {
                    $this->recordProvider429($keyInfo['provider']);
                }

                // ถ้ายังมี key ถัดไป → สลับทันที (รอแค่ 1-3 วินาที)
                if ($index < $totalKeys - 1) {
                    $delay = $is429 ? 1 : 3; // 429 = สลับเลย (1s), error อื่น = 3s
                    sleep($delay);
                }
            }
        }

        // ทุก key ล้มหมด
        $errorSummary = implode(' | ', array_slice($errors, 0, 5));
        Log::error('FortuneAI: ทุก key ล้มหมด', [
            'total_tried' => count($errors),
            'errors' => $errors,
        ]);
        throw new Exception('ไม่สามารถเชื่อมต่อ AI ได้ (ลองแล้ว ' . count($errors) . " keys): {$errorSummary}");
    }

    /**
     * เรียก AI provider โดยตรง พร้อม override api_key/model ชั่วคราว
     *
     * @param  string  $provider  ชื่อ provider
     * @param  string  $apiKey  API Key ที่ใช้
     * @param  string  $model  ชื่อ model
     * @param  string  $prompt  ข้อความ prompt
     * @param  array  $config  การตั้งค่า
     * @return array ผลลัพธ์
     */
    protected function callProviderDirect(string $provider, string $apiKey, string $model, string $prompt, array $config): array
    {
        // บันทึก original values
        $origApiKey = $this->apiKey;
        $origModel = $this->model;

        // Override ชั่วคราว
        $this->apiKey = $apiKey;
        $this->model = $model;

        try {
            return match ($provider) {
                'gemini' => $this->callGemini($prompt, $config),
                'groq' => $this->callGroq($prompt, $config),
                'grok' => $this->callGrok($prompt, $config),
                'qwen' => $this->callQwen($prompt, $config),
                'openrouter' => $this->callOpenRouter($prompt, $config),
                'deepseek' => $this->callDeepSeek($prompt, $config),
                'typhoon' => $this->callTyphoon($prompt, $config),
                default => throw new Exception("Provider '{$provider}' ไม่รองรับ"),
            };
        } finally {
            // คืนค่า original เสมอ
            $this->apiKey = $origApiKey;
            $this->model = $origModel;
        }
    }

    /**
     * รวม keys ทั้งหมดจาก Pool + Fortune Settings + Global AI Settings
     *
     * เรียงลำดับ:
     * 1. Keys ของ primary provider จาก Pool (ตาม priority)
     * 2. Keys ของ providers อื่นจาก Pool (ตาม priority)
     * 3. Keys จาก Fortune Settings (ถ้ามี)
     * 4. Keys จาก Global AI Settings (Gemini, OpenRouter)
     *
     * ไม่ซ้ำกัน — deduplicate ด้วย api_key
     *
     * @return array [['provider' => '...', 'api_key' => '...', 'model' => '...', 'pool_key' => ?AiApiKey, 'source' => '...', 'name' => '...'], ...]
     */
    /**
     * Circuit Breaker — รายชื่อ providers ที่เพิ่งโดน 429 หนัก
     * → cache key `ai_circuit_breaker:{provider}` = true
     */
    protected function getDownProviders(): array
    {
        $downList = [];
        foreach (['gemini', 'groq', 'grok', 'qwen', 'openrouter', 'deepseek', 'typhoon', 'openai', 'anthropic'] as $provider) {
            if (cache()->has("ai_circuit_breaker:{$provider}")) {
                $downList[] = $provider;
            }
        }

        return $downList;
    }

    /**
     * Circuit Breaker — บันทึก 429 ของ provider
     *
     * Logic:
     * - เจอ 429 → increment counter (TTL 60 วินาที)
     * - ถ้า counter ≥ 5 → mark provider down 2 นาที → skip ใน getAllAvailableKeys()
     * - หลัง 2 นาที cache expire → ลอง provider ใหม่
     *
     * @param  string  $provider  ชื่อ provider (groq, gemini, ฯลฯ)
     */
    protected function recordProvider429(string $provider): void
    {
        $counterKey = "ai_429_count:{$provider}";
        $breakerKey = "ai_circuit_breaker:{$provider}";

        try {
            // increment counter (atomic)
            $counter = cache()->increment($counterKey);
            if ($counter === 1) {
                // ครั้งแรก → set TTL 60 วินาที
                cache()->put($counterKey, 1, 60);
            }

            // ครบ threshold → เปิด circuit breaker 2 นาที
            if ($counter >= 5 && ! cache()->has($breakerKey)) {
                cache()->put($breakerKey, true, 120);
                cache()->forget($counterKey);
                Log::warning("🔴 AI Circuit Breaker: {$provider} OPEN", [
                    'reason' => '429 rate limit hit 5+ times in 60s',
                    'duration' => '2 minutes',
                ]);
            }
        } catch (Exception $e) {
            // cache อาจใช้ไม่ได้ (file driver + concurrent) → log แล้วไปต่อ
            Log::debug('Circuit breaker counter error: '.$e->getMessage());
        }
    }

    protected function getAllAvailableKeys(): array
    {
        $keys = [];
        $addedApiKeys = []; // เก็บ api_key ที่เพิ่มแล้ว ป้องกันซ้ำ
        $primaryProvider = $this->provider;

        // Circuit Breaker: skip providers ที่เพิ่งโดน 429 หนัก
        // (ถูก mark ไว้ใน cache 2 นาที เพื่อให้ rate limit reset)
        $downProviders = $this->getDownProviders();
        if (! empty($downProviders)) {
            Log::info('FortuneAI: Circuit Breaker active — skipping providers', [
                'down_providers' => $downProviders,
            ]);
        }

        // 1) ดึงจาก API Key Pool — ทุก provider (primary ก่อน)
        try {
            // เรียง: primary provider ก่อน → providers อื่น
            $providerOrder = array_merge(
                [$primaryProvider],
                array_filter(
                    ['gemini', 'groq', 'grok', 'qwen', 'openrouter', 'deepseek', 'typhoon'],
                    fn ($p) => $p !== $primaryProvider
                )
            );

            foreach ($providerOrder as $provider) {
                // Circuit Breaker: skip ถ้า provider โดน 429 หนัก
                if (in_array($provider, $downProviders, true)) {
                    continue;
                }

                // ดึง ALL available keys ของ provider นี้ (ไม่ใช่แค่ 1 key)
                $poolKeys = AiApiKey::forProvider($provider)
                    ->available()
                    ->orderByDesc('priority')
                    ->get();

                foreach ($poolKeys as $poolKey) {
                    if (in_array($poolKey->api_key, $addedApiKeys)) {
                        continue;
                    }
                    $keys[] = [
                        'provider' => $provider,
                        'api_key' => $poolKey->api_key,
                        'model' => $this->getDefaultModelForProvider($provider),
                        'pool_key' => $poolKey,
                        'source' => 'pool',
                        'name' => $poolKey->name ?? "Pool #{$poolKey->id}",
                    ];
                    $addedApiKeys[] = $poolKey->api_key;
                }
            }
        } catch (Exception $e) {
            Log::debug('FortuneAI: Pool ดึง keys ไม่ได้', ['error' => $e->getMessage()]);
        }

        // 2) ดึงจาก Fortune Settings (กรณี use_global_ai_settings = false)
        if (! empty($this->settings->ai_api_key) && ! empty($this->settings->ai_provider)) {
            $settingsKey = $this->settings->ai_api_key;
            if (! in_array($settingsKey, $addedApiKeys)) {
                $keys[] = [
                    'provider' => $this->settings->ai_provider,
                    'api_key' => $settingsKey,
                    'model' => $this->settings->ai_model ?: $this->getDefaultModelForProvider($this->settings->ai_provider),
                    'pool_key' => null,
                    'source' => 'fortune_settings',
                    'name' => 'Fortune Settings Key',
                ];
                $addedApiKeys[] = $settingsKey;
            }
        }

        // 3) ดึงจาก Global AI Settings (Gemini, Claude/OpenRouter)
        try {
            // Gemini key จาก global settings
            $geminiKey = AiContentSetting::getValue('gemini_api_key');
            if (! empty($geminiKey) && ! in_array($geminiKey, $addedApiKeys)) {
                $geminiModel = AiContentSetting::getValue('gemini_model', 'gemini-2.0-flash');
                $keys[] = [
                    'provider' => 'gemini',
                    'api_key' => $geminiKey,
                    'model' => $geminiModel,
                    'pool_key' => null,
                    'source' => 'global_settings',
                    'name' => 'Global Gemini Key',
                ];
                $addedApiKeys[] = $geminiKey;
            }

            // Claude/OpenRouter key จาก global settings
            $claudeKey = AiContentSetting::getValue('claude_api_key');
            if (! empty($claudeKey) && ! in_array($claudeKey, $addedApiKeys)) {
                $keys[] = [
                    'provider' => 'openrouter',
                    'api_key' => $claudeKey,
                    'model' => AiContentSetting::getValue('claude_model', 'anthropic/claude-3-haiku'),
                    'pool_key' => null,
                    'source' => 'global_settings',
                    'name' => 'Global Claude/OpenRouter Key',
                ];
                $addedApiKeys[] = $claudeKey;
            }
        } catch (Exception $e) {
            Log::debug('FortuneAI: Global settings ดึง keys ไม่ได้', ['error' => $e->getMessage()]);
        }

        return $keys;
    }

    /**
     * ดึง default model สำหรับแต่ละ provider
     */
    protected function getDefaultModelForProvider(string $provider): string
    {
        return match ($provider) {
            'gemini' => 'gemini-2.0-flash',
            'groq' => 'llama-3.3-70b-versatile',
            'grok' => 'grok-2-latest',
            'qwen' => 'Qwen/Qwen2.5-72B-Instruct',
            'openrouter' => 'anthropic/claude-3-haiku',
            'deepseek' => 'deepseek-chat',
            'typhoon' => 'typhoon-v2-70b-instruct',
            default => 'gemini-2.0-flash',
        };
    }

    /**
     * สร้าง prompt สำหรับส่งให้ AI
     *
     * @param  array  $questions  คำถาม
     * @param  array|null  $userProfile  โปรไฟล์ผู้ใช้
     * @param  array|null  $userPosts  โพสล่าสุด
     * @param  string|null  $promptTemplate  template ที่กำหนดเอง
     * @param  string|null  $birthDate  วันเดือนปีเกิด (Y-m-d)
     */
    protected function buildPrompt(array $questions, ?array $userProfile, ?array $userPosts, ?string $promptTemplate = null, ?string $birthDate = null): string
    {
        $template = $promptTemplate ?? $this->settings->getDefaultPromptTemplate();
        $profileText = $this->formatUserProfile($userProfile);
        $postsText = $this->formatUserPosts($userPosts);
        $questionsText = implode("\n", array_map(fn ($i, $q) => ($i + 1).". $q", array_keys($questions), $questions));
        $birthDateSection = $this->formatBirthDateSection($birthDate);

        return str_replace(
            ['{user_profile}', '{user_posts}', '{questions}', '{birth_date_section}'],
            [$profileText, $postsText, $questionsText, $birthDateSection],
            $template
        );
    }

    /**
     * สร้างส่วนวิเคราะห์วันเดือนปีเกิด
     *
     * @param  string|null  $birthDate  วันเดือนปีเกิด (Y-m-d)
     */
    protected function formatBirthDateSection(?string $birthDate): string
    {
        if (empty($birthDate)) {
            return '(ไม่ได้ระบุวันเดือนปีเกิด - ทำนายจากคำถามและบริบทที่มี ใช้หลักเจ้าชนะเมื่อได้รับวันเกิดภายหลัง)';
        }

        try {
            $date = \Carbon\Carbon::parse($birthDate);
            $thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
            $thaiYear = $date->year + 543;
            $age = $date->age;
            $dayOfWeekIndex = $date->dayOfWeek; // 0=อาทิตย์, 1=จันทร์, ...6=เสาร์
            $dayOfWeek = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'][$dayOfWeekIndex];

            // คำนวณราศีจากวันเกิด (โหราศาสตร์สากล)
            $zodiac = $this->getZodiacSign($date->month, $date->day);

            // === คำนวณโหราศาสตร์ไทย (เจ้าชนะ) ===
            $planetInfo = $this->getPlanetByDayOfWeek($dayOfWeekIndex);

            $section = "📅 วันเดือนปีเกิด: {$date->day} {$thaiMonths[$date->month]} {$thaiYear} (พ.ศ.)\n";
            $section .= "🗓️ วันเกิด: วัน{$dayOfWeek}\n";
            $section .= "🎂 อายุ: {$age} ปี\n";
            $section .= "♈ ราศี: {$zodiac}\n";
            $section .= "\n=== โหราศาสตร์ไทย (เจ้าชนะ) ===\n";
            $section .= "⭐ ดาวประจำวันเกิด (เจ้าชนะ): {$planetInfo['planet']}\n";
            $section .= "🔥 ธาตุ: {$planetInfo['element']}\n";
            $section .= "🤝 ดาวมิตร: {$planetInfo['friends']}\n";
            $section .= "⚔️ ดาวศัตรู: {$planetInfo['enemies']}\n";
            $section .= "🎨 สีมงคล: {$planetInfo['lucky_color']}\n";
            $section .= "🚫 สีอัปมงคล: {$planetInfo['unlucky_color']}\n";
            $section .= "🔢 เลขมงคล: {$planetInfo['lucky_number']}\n";
            $section .= "📅 วันมงคล: {$planetInfo['lucky_days']}\n";
            $section .= "📅 วันอัปมงคล: {$planetInfo['unlucky_days']}\n";
            $section .= "💎 ลักษณะนิสัย: {$planetInfo['personality']}\n";
            $section .= "\n⭐ กรุณาวิเคราะห์ดวงชะตาจากข้อมูลวันเกิดนี้อย่างละเอียด โดยใช้หลักเจ้าชนะ อ้างอิงดาวเจ้าชนะ ดาวมิตร ดาวศัตรู ภพที่ดาวโคจรผ่าน และดาวที่ส่งผลในช่วงนี้";

            return $section;
        } catch (\Exception $e) {
            return "(วันเกิด: {$birthDate})";
        }
    }

    /**
     * คำนวณดาวเคราะห์ประจำวันเกิดตามหลักเจ้าชนะ
     *
     * @param  int  $dayOfWeek  0=อาทิตย์, 1=จันทร์, ...6=เสาร์
     * @return array ข้อมูลดาวเคราะห์
     */
    protected function getPlanetByDayOfWeek(int $dayOfWeek): array
    {
        $planets = [
            0 => [ // อาทิตย์
                'planet' => 'ดาวอาทิตย์ (☉)',
                'element' => 'ธาตุไฟ',
                'friends' => 'ดาวพฤหัสบดี, ดาวอังคาร',
                'enemies' => 'ดาวเสาร์, ราหู',
                'lucky_color' => 'แดง, ส้ม, ทอง',
                'unlucky_color' => 'ดำ, ม่วงเข้ม',
                'lucky_number' => '1, 6, 9',
                'lucky_days' => 'วันพฤหัสบดี, วันอังคาร',
                'unlucky_days' => 'วันเสาร์',
                'personality' => 'มีอำนาจ เป็นผู้นำ มีศักดิ์ศรี มั่นใจ กล้าตัดสินใจ',
            ],
            1 => [ // จันทร์
                'planet' => 'ดาวจันทร์ (☽)',
                'element' => 'ธาตุน้ำ',
                'friends' => 'ดาวพุธ, ดาวศุกร์',
                'enemies' => 'ราหู, ดาวเสาร์',
                'lucky_color' => 'ขาว, ครีม, เงิน',
                'unlucky_color' => 'ดำ, น้ำเงินเข้ม',
                'lucky_number' => '2, 5, 7',
                'lucky_days' => 'วันพุธ, วันศุกร์',
                'unlucky_days' => 'วันเสาร์',
                'personality' => 'อ่อนโยน มีเมตตา จิตใจดี อารมณ์อ่อนไหว รักครอบครัว',
            ],
            2 => [ // อังคาร
                'planet' => 'ดาวอังคาร (♂)',
                'element' => 'ธาตุไฟ',
                'friends' => 'ดาวอาทิตย์, ดาวพฤหัสบดี',
                'enemies' => 'ดาวพุธ, ดาวเสาร์',
                'lucky_color' => 'ชมพู, แดงอ่อน, ส้ม',
                'unlucky_color' => 'เขียว, ดำ',
                'lucky_number' => '3, 6, 9',
                'lucky_days' => 'วันอาทิตย์, วันพฤหัสบดี',
                'unlucky_days' => 'วันเสาร์, วันพุธ',
                'personality' => 'กล้าหาญ ร้อนแรง ทะเยอทะยาน มีพลัง ไม่ยอมแพ้',
            ],
            3 => [ // พุธ
                'planet' => 'ดาวพุธ (☿)',
                'element' => 'ธาตุดิน',
                'friends' => 'ดาวจันทร์, ดาวศุกร์',
                'enemies' => 'ราหู, ดาวอังคาร',
                'lucky_color' => 'เขียว, เขียวอ่อน',
                'unlucky_color' => 'แดง, ชมพูเข้ม',
                'lucky_number' => '4, 2, 7',
                'lucky_days' => 'วันจันทร์, วันศุกร์',
                'unlucky_days' => 'วันอังคาร',
                'personality' => 'ฉลาด มีไหวพริบ พูดเก่ง ค้าขายเก่ง ปรับตัวเก่ง',
            ],
            4 => [ // พฤหัสบดี
                'planet' => 'ดาวพฤหัสบดี (♃)',
                'element' => 'ธาตุน้ำ',
                'friends' => 'ดาวอาทิตย์, ดาวอังคาร',
                'enemies' => 'ราหู, ดาวเสาร์',
                'lucky_color' => 'ส้ม, เหลือง, ทอง',
                'unlucky_color' => 'ดำ, ม่วงเข้ม',
                'lucky_number' => '5, 1, 3',
                'lucky_days' => 'วันอาทิตย์, วันอังคาร',
                'unlucky_days' => 'วันเสาร์',
                'personality' => 'มีปัญญา ใจกว้าง โชคดี ศรัทธา รักความยุติธรรม ผู้ทรงคุณธรรม',
            ],
            5 => [ // ศุกร์
                'planet' => 'ดาวศุกร์ (♀)',
                'element' => 'ธาตุน้ำ',
                'friends' => 'ดาวพุธ, ดาวจันทร์',
                'enemies' => 'ดาวอาทิตย์, ดาวอังคาร',
                'lucky_color' => 'ฟ้า, ฟ้าอ่อน, ขาว',
                'unlucky_color' => 'แดง, ส้มเข้ม',
                'lucky_number' => '6, 2, 4',
                'lucky_days' => 'วันพุธ, วันจันทร์',
                'unlucky_days' => 'วันอาทิตย์, วันอังคาร',
                'personality' => 'รักสวยรักงาม มีเสน่ห์ มีศิลปะ รักความหรูหรา โรแมนติก',
            ],
            6 => [ // เสาร์
                'planet' => 'ดาวเสาร์ (♄)',
                'element' => 'ธาตุไฟ',
                'friends' => 'ราหู, ดาวพฤหัสบดี',
                'enemies' => 'ดาวอาทิตย์, ดาวอังคาร',
                'lucky_color' => 'ม่วง, ดำ, น้ำเงินเข้ม',
                'unlucky_color' => 'แดง, ส้ม',
                'lucky_number' => '7, 5, 8',
                'lucky_days' => 'วันพฤหัสบดี',
                'unlucky_days' => 'วันอาทิตย์, วันอังคาร',
                'personality' => 'อดทน มุ่งมั่น รอบคอบ มีวินัย จริงจัง ทำอะไรทำจริง',
            ],
        ];

        return $planets[$dayOfWeek] ?? $planets[0];
    }

    /**
     * คำนวณราศีจากเดือนและวันเกิด (Western Zodiac)
     *
     * @param  int  $month  เดือน
     * @param  int  $day  วัน
     * @return string ชื่อราศี
     */
    protected function getZodiacSign(int $month, int $day): string
    {
        $signs = [
            ['name' => 'มังกร (Capricorn)', 'end_month' => 1, 'end_day' => 19],
            ['name' => 'กุมภ์ (Aquarius)', 'end_month' => 2, 'end_day' => 18],
            ['name' => 'มีน (Pisces)', 'end_month' => 3, 'end_day' => 20],
            ['name' => 'เมษ (Aries)', 'end_month' => 4, 'end_day' => 19],
            ['name' => 'พฤษภ (Taurus)', 'end_month' => 5, 'end_day' => 20],
            ['name' => 'เมถุน (Gemini)', 'end_month' => 6, 'end_day' => 20],
            ['name' => 'กรกฎ (Cancer)', 'end_month' => 7, 'end_day' => 22],
            ['name' => 'สิงห์ (Leo)', 'end_month' => 8, 'end_day' => 22],
            ['name' => 'กันย์ (Virgo)', 'end_month' => 9, 'end_day' => 22],
            ['name' => 'ตุลย์ (Libra)', 'end_month' => 10, 'end_day' => 22],
            ['name' => 'พิจิก (Scorpio)', 'end_month' => 11, 'end_day' => 21],
            ['name' => 'ธนู (Sagittarius)', 'end_month' => 12, 'end_day' => 21],
        ];

        foreach ($signs as $sign) {
            if ($month === $sign['end_month'] && $day <= $sign['end_day']) {
                return $sign['name'];
            }
            if ($month < $sign['end_month']) {
                return $sign['name'];
            }
        }

        return 'มังกร (Capricorn)'; // ธันวาคม 22-31
    }

    protected function formatUserProfile(?array $userProfile): string
    {
        if (empty($userProfile)) {
            return 'ไม่มีข้อมูลโปรไฟล์';
        }

        $parts = [];
        if (! empty($userProfile['name'])) {
            $parts[] = "ชื่อ: {$userProfile['name']}";
        }

        // เพศ (จาก Facebook หรือที่ผู้ใช้บอก)
        if (! empty($userProfile['gender'])) {
            $genderMap = ['male' => 'ชาย', 'female' => 'หญิง'];
            $gender = $genderMap[$userProfile['gender']] ?? $userProfile['gender'];
            $parts[] = "เพศ: {$gender}";
        }

        // อายุ (คำนวณจาก birthday)
        if (! empty($userProfile['age'])) {
            $parts[] = "อายุ: {$userProfile['age']} ปี";
        }

        // วันเกิด (จาก Facebook)
        if (! empty($userProfile['birthday'])) {
            $parts[] = "วันเกิด: {$userProfile['birthday']}";
        }

        // ภาษา/ภูมิภาค
        if (! empty($userProfile['locale'])) {
            $parts[] = "ภาษา/ภูมิภาค: {$userProfile['locale']}";
        }

        // Timezone (ช่วยวิเคราะห์ดวงตามเวลาท้องถิ่น)
        if (! empty($userProfile['timezone'])) {
            $parts[] = 'Timezone: UTC'.($userProfile['timezone'] >= 0 ? '+' : '').$userProfile['timezone'];
        }

        return ! empty($parts) ? implode("\n", $parts) : 'ข้อมูลพื้นฐาน';
    }

    protected function formatUserPosts(?array $userPosts): string
    {
        if (empty($userPosts)) {
            return 'ไม่มีข้อมูลโพสล่าสุด';
        }

        $formatted = [];
        foreach (array_slice($userPosts, 0, 3) as $index => $post) {
            $message = $post['message'] ?? $post['story'] ?? '';
            if (! empty($message)) {
                $formatted[] = ($index + 1).'. '.substr($message, 0, 200);
            }
        }

        return ! empty($formatted) ? implode("\n", $formatted) : 'ไม่มีข้อมูลโพสล่าสุด';
    }

    protected function callGemini(string $prompt, array $config = []): array
    {
        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(60)->post($url, [
                'system_instruction' => [
                    'parts' => [['text' => self::SYSTEM_MESSAGE]],
                ],
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature' => $config['temperature'] ?? 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => $config['max_tokens'] ?? 2048,
                ],
            ]);

            if (! $response->successful()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? $response->body();
                $errorCode = $errorBody['error']['code'] ?? $response->status();
                Log::error('Gemini API Error', [
                    'status' => $response->status(),
                    'error' => $errorMessage,
                    'model' => $this->model,
                ]);
                throw new Exception("Gemini API Error ({$errorCode}): {$errorMessage}");
            }

            $data = $response->json();

            if (empty($data['candidates'][0]['content']['parts'][0]['text'] ?? null)) {
                Log::error('Gemini API: Empty response', ['data' => $data]);
                $blockReason = $data['promptFeedback']['blockReason'] ?? null;
                if ($blockReason) {
                    throw new Exception("Gemini API: Prompt blocked - {$blockReason}");
                }
                throw new Exception('Gemini API: ไม่ได้รับคำตอบจาก AI (empty response)');
            }

            return [
                'response' => $data['candidates'][0]['content']['parts'][0]['text'],
                'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
                'provider' => 'gemini',
                'model' => $this->model,
            ];
        } catch (Exception $e) {
            Log::error('Gemini API Error: '.$e->getMessage());
            throw $e;
        }
    }

    protected function callGroq(string $prompt, array $config = []): array
    {
        try {
            $response = Http::timeout(60)
                ->withToken($this->apiKey)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_MESSAGE],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => $config['temperature'] ?? 0.7,
                    'max_tokens' => $config['max_tokens'] ?? 2048,
                ])->throw();

            $data = $response->json();

            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'provider' => 'groq',
                'model' => $this->model,
            ];
        } catch (Exception $e) {
            Log::error('Groq API Error', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);
            throw new Exception("Groq API Error: {$e->getMessage()}");
        }
    }

    protected function callQwen(string $prompt, array $config = []): array
    {
        try {
            // ใช้ HuggingFace Router API (OpenAI-compatible chat format)
            // ให้คุณภาพคำทำนายดีกว่า text generation API เดิม
            $response = Http::timeout(120)
                ->withToken($this->apiKey)
                ->post('https://router.huggingface.co/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_MESSAGE],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => $config['max_tokens'] ?? 2048,
                    'temperature' => $config['temperature'] ?? 0.7,
                ])->throw();

            $data = $response->json();

            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'provider' => 'qwen',
                'model' => $this->model,
            ];
        } catch (Exception $e) {
            Log::error('Qwen API Error', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);
            throw new Exception("Qwen API Error: {$e->getMessage()}");
        }
    }

    protected function callOpenRouter(string $prompt, array $config = []): array
    {
        try {
            $response = Http::timeout(60)
                ->withToken($this->apiKey)
                ->withHeaders(['HTTP-Referer' => config('app.url')])
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_MESSAGE],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => $config['temperature'] ?? 0.7,
                    'max_tokens' => $config['max_tokens'] ?? 2048,
                ])->throw();

            $data = $response->json();

            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'provider' => 'openrouter',
                'model' => $this->model,
            ];
        } catch (Exception $e) {
            Log::error('OpenRouter API Error', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);
            throw new Exception("OpenRouter API Error: {$e->getMessage()}");
        }
    }

    // ============================================================
    // Comment Engagement: AI สร้างข้อความชวนดูดวง
    // ============================================================

    /**
     * System message สำหรับ Comment Engagement ของ "หมอจันทรา"
     * AI จะพูดเฉพาะเรื่องดูดวงเท่านั้น ห้ามตอบเรื่องอื่น
     * เป็นหมอดูสาวสวยวัย 35 ปี ใช้คำแทนตัวว่า "หมอจันทรา"
     *
     * กฎพิเศษ:
     * - ถ้าถูกถามว่าเป็น AI: ตอบว่า "หมอจันทรามีทีมงานช่วยกันค่ะ"
     * - ถ้าถามนอกเรื่องดูดวง: ปฏิเสธสุภาพและชวนกลับมาดูดวง
     */
    protected const ENGAGEMENT_SYSTEM_MESSAGE = 'คุณชื่อ "หมอจันทรา" เป็นหมอดูสาวสวยวัย 35 ปี พูดจาเพราะ อบอุ่นเป็นกันเอง น่าเชื่อถือ ใช้คำแทนตัวว่า "หมอจันทรา" เชี่ยวชาญโหราศาสตร์ไทย ไพ่ทาโรต์ และการหยั่งรู้ด้วยจิตสัมผัส [กฎสำคัญ] 1) หากถูกถามว่าเป็น AI บอท หรือโปรแกรม ให้ตอบเป็นธรรมชาติว่า "หมอจันทรามีทีมงานช่วยกันในการทำนายค่ะ" หรือ "หมอจันทราทำนายเองค่ะ ใช้พลังหยั่งรู้ในการทำนายค่ะ 🔮" 2) คุณพูดเฉพาะเรื่องดูดวงเท่านั้น หากถูกถามเรื่องอื่นที่ไม่เกี่ยวกับดูดวง ให้ปฏิเสธอย่างสุภาพว่า "ขอบคุณที่สนใจนะคะ แต่หมอจันทราขอตอบเฉพาะเรื่องดูดวงนะคะ 🙏 ทักมาถามเรื่องดวงได้เลยค่ะ 🔮✨" ห้ามตอบเรื่องอื่นทุกกรณี ห้ามเขียนโค้ด หากมีข้อมูลเพศหรือวันเกิดให้อ้างอิงในข้อความ ใน DM_MESSAGE ให้ชวนบอกวันเกิดเพื่อทำนายแม่นขึ้น เช่น "บอกวันเกิดให้หมอจันทราได้ไหมคะ จะได้ทำนายได้ลึกขึ้นค่ะ" และชวนส่งต่อให้เพื่อนๆ มาดูดวงกับหมอจันทรา คุณต้องตอบเป็น JSON เท่านั้น';

    /**
     * สร้างข้อความ engage จากคอมเม้นต์
     *
     * @param  string  $commentText  ข้อความคอมเม้นต์
     * @param  array|null  $userProfile  โปรไฟล์ผู้ใช้ (name, gender, birthday ฯลฯ)
     * @param  string|null  $engagementPrompt  Prompt template (ถ้าไม่ระบุจะใช้ค่าเริ่มต้นจาก settings)
     * @return array ['comment_reply' => '...', 'dm_message' => '...']
     */
    public function generateCommentEngagement(
        string $commentText,
        ?array $userProfile = null,
        ?string $engagementPrompt = null
    ): array {
        // ตรวจสอบ API Key ก่อนเรียก AI
        if (empty($this->apiKey)) {
            Log::error('FortuneAIService: ไม่มี API Key สำหรับ comment engagement', [
                'provider' => $this->provider,
            ]);
            throw new Exception("ไม่พบ API Key สำหรับ {$this->provider}");
        }

        $prompt = $engagementPrompt ?? $this->settings->getCommentEngagementPrompt();

        // แทนที่ placeholders ใน prompt
        $name = $userProfile['name'] ?? $userProfile['first_name'] ?? 'คุณ';
        $profileInfo = $this->formatProfileForEngagement($userProfile);

        $prompt = str_replace(
            ['{comment}', '{name}', '{profile_info}'],
            [$commentText, $name, $profileInfo],
            $prompt
        );

        $config = [
            'max_tokens' => 400,
            'temperature' => 0.8,
        ];

        $result = match ($this->provider) {
            'gemini' => $this->callGemini($prompt, $config),
            'groq' => $this->callGroq($prompt, $config),
            'grok' => $this->callGrok($prompt, $config),
            'qwen' => $this->callQwen($prompt, $config),
            'openrouter' => $this->callOpenRouter($prompt, $config),
            'deepseek' => $this->callDeepSeek($prompt, $config),
            'typhoon' => $this->callTyphoon($prompt, $config),
            default => throw new Exception("AI Provider '{$this->provider}' ไม่รองรับ"),
        };

        // Parse JSON response จาก AI
        return $this->parseEngagementResponse($result['response'] ?? '');
    }

    /**
     * แปลงข้อมูลโปรไฟล์เป็นข้อความสำหรับ prompt
     */
    protected function formatProfileForEngagement(?array $userProfile): string
    {
        if (empty($userProfile)) {
            return '(ไม่มีข้อมูลโปรไฟล์)';
        }

        $info = [];

        if (! empty($userProfile['gender'])) {
            $genderMap = ['male' => 'ชาย', 'female' => 'หญิง'];
            $info[] = 'เพศ: '.($genderMap[$userProfile['gender']] ?? $userProfile['gender']);
        }

        if (! empty($userProfile['birthday'])) {
            $info[] = 'วันเกิด: '.$userProfile['birthday'];
        }

        if (! empty($userProfile['locale'])) {
            $info[] = 'ภาษา: '.$userProfile['locale'];
        }

        return empty($info) ? '(ไม่มีข้อมูลเพิ่มเติม)' : implode(', ', $info);
    }

    /**
     * Parse JSON response จาก AI สำหรับ engagement
     *
     * @return array ['comment_reply' => '...', 'dm_message' => '...']
     */
    protected function parseEngagementResponse(string $response): array
    {
        // ลอง parse JSON โดยตรง
        $data = json_decode($response, true);

        // ถ้า parse ไม่ได้ ลองหา JSON ใน response
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (preg_match('/\{[^}]*"comment_reply"[^}]*\}/s', $response, $matches)) {
                $data = json_decode($matches[0], true);
            }
        }

        // ถ้ายังไม่ได้ ใช้ค่า default
        if (empty($data) || ! isset($data['comment_reply'])) {
            Log::warning('AI engagement response ไม่ใช่ JSON ที่ถูกต้อง', ['response' => $response]);

            return [
                'comment_reply' => '🔮 สนใจดูดวงไหมคะ? ทักมาใน inbox ได้เลยนะคะ ✨',
                'dm_message' => $this->settings->getCommentDmTemplate(),
            ];
        }

        return [
            'comment_reply' => $data['comment_reply'] ?? '🔮 สนใจดูดวงไหมคะ? ทักมาใน inbox ได้เลยนะคะ ✨',
            'dm_message' => $data['dm_message'] ?? $this->settings->getCommentDmTemplate(),
        ];
    }

    public function testConnection(): array
    {
        // ตรวจสอบการตั้งค่าเบื้องต้น
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => "ไม่พบ API Key สำหรับ {$this->provider} - กรุณาตั้งค่า API Key ก่อน",
                'debug' => [
                    'provider' => $this->provider,
                    'model' => $this->model,
                    'has_api_key' => false,
                    'use_global' => $this->settings->use_global_ai_settings ?? false,
                ],
            ];
        }

        try {
            $result = $this->generateFortuneTelling(['ทดสอบการเชื่อมต่อ AI'], null, null);

            return [
                'success' => true,
                'message' => "เชื่อมต่อกับ {$this->provider} ({$this->model}) สำเร็จ",
                'preview' => $result['response'] ?? '',
                'debug' => [
                    'provider' => $this->provider,
                    'model' => $this->model,
                    'tokens_used' => $result['tokens_used'] ?? 0,
                    'response_length' => mb_strlen($result['response'] ?? ''),
                ],
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'debug' => [
                    'provider' => $this->provider,
                    'model' => $this->model,
                    'has_api_key' => ! empty($this->apiKey),
                    'api_key_prefix' => substr($this->apiKey ?? '', 0, 8).'...',
                    'use_global' => $this->settings->use_global_ai_settings ?? false,
                ],
            ];
        }
    }

    // ============================================================
    // Grok API (xAI)
    // ============================================================

    /**
     * เรียก Grok API (xAI)
     *
     * @param  string  $prompt  ข้อความ prompt
     * @param  array  $config  การตั้งค่า (max_tokens, temperature)
     * @return array ผลลัพธ์
     */
    protected function callGrok(string $prompt, array $config = []): array
    {
        try {
            $response = Http::timeout(90)
                ->withToken($this->apiKey)
                ->post('https://api.x.ai/v1/chat/completions', [
                    'model' => $this->model ?: 'grok-2-latest',
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_MESSAGE],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => $config['temperature'] ?? 0.7,
                    'max_tokens' => $config['max_tokens'] ?? 2048,
                ])->throw();

            $data = $response->json();

            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'input_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                'output_tokens' => $data['usage']['completion_tokens'] ?? 0,
                'provider' => 'grok',
                'model' => $this->model ?: 'grok-2-latest',
            ];
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            Log::error('Grok API Error', [
                'error' => $errorMsg,
                'model' => $this->model ?: 'grok-2-latest',
                'has_api_key' => ! empty($this->apiKey),
                'api_key_prefix' => substr($this->apiKey ?? '', 0, 8).'...',
            ]);
            throw new Exception("Grok API Error: {$errorMsg}");
        }
    }

    // ============================================================
    // DeepSeek API
    // ============================================================

    /**
     * เรียก DeepSeek API
     *
     * DeepSeek ใช้ OpenAI-compatible API
     * ราคาถูกมาก + มี sign-up credits 5M tokens ฟรี
     *
     * @param  string  $prompt  ข้อความ prompt
     * @param  array  $config  การตั้งค่า (max_tokens, temperature)
     * @return array ผลลัพธ์
     */
    protected function callDeepSeek(string $prompt, array $config = []): array
    {
        try {
            $response = Http::timeout(90)
                ->withToken($this->apiKey)
                ->post('https://api.deepseek.com/chat/completions', [
                    'model' => $this->model ?: 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_MESSAGE],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => $config['temperature'] ?? 0.7,
                    'max_tokens' => $config['max_tokens'] ?? 2048,
                ])->throw();

            $data = $response->json();

            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'input_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                'output_tokens' => $data['usage']['completion_tokens'] ?? 0,
                'provider' => 'deepseek',
                'model' => $this->model ?: 'deepseek-chat',
            ];
        } catch (Exception $e) {
            Log::error('DeepSeek API Error', [
                'error' => $e->getMessage(),
                'model' => $this->model ?: 'deepseek-chat',
            ]);
            throw new Exception("DeepSeek API Error: {$e->getMessage()}");
        }
    }

    // ============================================================
    // Typhoon API (SCB 10X - เชี่ยวชาญภาษาไทย)
    // ============================================================

    /**
     * เรียก Typhoon API (SCB 10X)
     *
     * Typhoon เป็น LLM ที่ออกแบบมาเฉพาะสำหรับภาษาไทย
     * ใช้ OpenAI-compatible API format
     *
     * @param  string  $prompt  ข้อความ prompt
     * @param  array  $config  การตั้งค่า (max_tokens, temperature)
     * @return array ผลลัพธ์
     */
    protected function callTyphoon(string $prompt, array $config = []): array
    {
        try {
            $response = Http::timeout(90)
                ->withToken($this->apiKey)
                ->post('https://api.opentyphoon.ai/v1/chat/completions', [
                    'model' => $this->model ?: 'typhoon-v2-70b-instruct',
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_MESSAGE],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => $config['temperature'] ?? 0.7,
                    'max_tokens' => $config['max_tokens'] ?? 2048,
                ])->throw();

            $data = $response->json();

            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'input_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                'output_tokens' => $data['usage']['completion_tokens'] ?? 0,
                'provider' => 'typhoon',
                'model' => $this->model ?: 'typhoon-v2-70b-instruct',
            ];
        } catch (Exception $e) {
            Log::error('Typhoon API Error', [
                'error' => $e->getMessage(),
                'model' => $this->model ?: 'typhoon-v2-70b-instruct',
            ]);
            throw new Exception("Typhoon API Error: {$e->getMessage()}");
        }
    }

    // ============================================================
    // Playground: ทดสอบสนทนากับ AI แบบอิสระ
    // ============================================================

    /**
     * สนทนากับ AI สำหรับ Playground (Admin ทดสอบ)
     *
     * รับ messages array แบบ chat history เพื่อรองรับการสนทนาต่อเนื่อง
     *
     * @param  array  $messages  ประวัติสนทนา [['role' => 'user'|'assistant', 'content' => '...'], ...]
     * @param  string  $readingType  ประเภท: 'basic' หรือ 'deep'
     * @return array ผลลัพธ์
     */
    public function playgroundChat(array $messages, string $readingType = 'basic'): array
    {
        if (empty($this->apiKey)) {
            throw new Exception("ไม่พบ API Key สำหรับ {$this->provider} - กรุณาตั้งค่า API Key ก่อน");
        }

        $config = self::READING_CONFIG[$readingType] ?? self::READING_CONFIG['basic'];
        $startTime = microtime(true);

        // สร้าง messages array พร้อม system message
        $chatMessages = [
            ['role' => 'system', 'content' => self::SYSTEM_MESSAGE],
        ];
        foreach ($messages as $msg) {
            $chatMessages[] = [
                'role' => $msg['role'] ?? 'user',
                'content' => $msg['content'] ?? '',
            ];
        }

        // Gemini ใช้ API format ต่างจาก OpenAI-compatible
        if ($this->provider === 'gemini') {
            return $this->playgroundGemini($chatMessages, $config);
        }

        // สำหรับ provider ที่ใช้ OpenAI-compatible API
        $url = match ($this->provider) {
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'grok' => 'https://api.x.ai/v1/chat/completions',
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'deepseek' => 'https://api.deepseek.com/chat/completions',
            'typhoon' => 'https://api.opentyphoon.ai/v1/chat/completions',
            default => throw new Exception("Provider '{$this->provider}' ไม่รองรับ Playground"),
        };

        $headers = ['Authorization' => "Bearer {$this->apiKey}"];
        if ($this->provider === 'openrouter') {
            $headers['HTTP-Referer'] = config('app.url');
        }

        $response = Http::timeout(90)
            ->withHeaders($headers)
            ->post($url, [
                'model' => $this->model,
                'messages' => $chatMessages,
                'temperature' => $config['temperature'] ?? 0.75,
                'max_tokens' => $config['max_tokens'] ?? 2048,
            ])->throw();

        $data = $response->json();
        $responseTime = (int) ((microtime(true) - $startTime) * 1000);

        return [
            'response' => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            'provider' => $this->provider,
            'model' => $this->model,
            'response_time_ms' => $responseTime,
        ];
    }

    /**
     * Playground สำหรับ Gemini (ใช้ API format ต่างจาก OpenAI)
     *
     * @param  array  $chatMessages  messages array พร้อม system message
     * @param  array  $config  การตั้งค่า
     * @return array ผลลัพธ์
     */
    protected function playgroundGemini(array $chatMessages, array $config): array
    {
        $startTime = microtime(true);
        $systemMessage = '';
        $contents = [];

        foreach ($chatMessages as $msg) {
            if ($msg['role'] === 'system') {
                $systemMessage = $msg['content'];

                continue;
            }
            $geminiRole = $msg['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $geminiRole,
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $body = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $config['temperature'] ?? 0.75,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => $config['max_tokens'] ?? 2048,
            ],
        ];

        if (! empty($systemMessage)) {
            $body['system_instruction'] = [
                'parts' => [['text' => $systemMessage]],
            ];
        }

        $response = Http::timeout(90)->post($url, $body)->throw();
        $data = $response->json();
        $responseTime = (int) ((microtime(true) - $startTime) * 1000);

        if (empty($data['candidates'][0]['content']['parts'][0]['text'] ?? null)) {
            throw new Exception('Gemini Playground: ไม่ได้รับคำตอบจาก AI');
        }

        return [
            'response' => $data['candidates'][0]['content']['parts'][0]['text'],
            'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
            'provider' => 'gemini',
            'model' => $this->model,
            'response_time_ms' => $responseTime,
        ];
    }

    // ============================================================
    // API Key Pool Integration
    // ============================================================

    /**
     * บันทึกการใช้งาน tokens ผ่าน Pool Service
     *
     * @param  int  $tokensUsed  จำนวน tokens ที่ใช้
     * @param  string  $model  model ที่ใช้
     * @param  int  $responseTime  เวลาตอบกลับ (ms)
     * @param  string  $requestType  ประเภท request
     */
    protected function recordUsage(int $tokensUsed, string $model, int $responseTime, string $requestType = 'fortune'): void
    {
        if (! $this->currentKey || ! $this->poolService) {
            return;
        }

        try {
            // ประมาณการแยก input/output tokens (ถ้าไม่มีข้อมูล)
            $inputTokens = (int) ($tokensUsed * 0.3);
            $outputTokens = $tokensUsed - $inputTokens;

            $this->currentKey->recordUsage(
                $inputTokens,
                $outputTokens,
                $model,
                $responseTime,
                $requestType
            );
        } catch (\Exception $e) {
            Log::warning('FortuneAIService: บันทึก usage ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }

    /**
     * บันทึก error ผ่าน Pool Service
     *
     * @param  string  $errorMessage  ข้อความ error
     * @param  string|null  $model  model ที่ใช้
     */
    protected function recordError(string $errorMessage, ?string $model = null): void
    {
        if (! $this->currentKey || ! $this->poolService) {
            return;
        }

        try {
            $this->currentKey->recordError($errorMessage, $model);
        } catch (\Exception $e) {
            Log::warning('FortuneAIService: บันทึก error ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }

    /**
     * บันทึกการใช้งาน tokens สำหรับ key ที่ระบุ (ใช้กับ unified key list)
     *
     * @param  AiApiKey  $key  key ที่ต้องการบันทึก
     * @param  int  $tokensUsed  จำนวน tokens ที่ใช้
     * @param  string  $model  model ที่ใช้
     * @param  int  $responseTime  เวลาตอบกลับ (ms)
     * @param  string  $requestType  ประเภท request
     */
    protected function recordUsageForKey(AiApiKey $key, int $tokensUsed, string $model, int $responseTime, string $requestType = 'fortune'): void
    {
        try {
            $inputTokens = (int) ($tokensUsed * 0.3);
            $outputTokens = $tokensUsed - $inputTokens;
            $key->recordUsage($inputTokens, $outputTokens, $model, $responseTime, $requestType);
        } catch (\Exception $e) {
            Log::warning('FortuneAI: บันทึก usage สำหรับ key ไม่สำเร็จ', [
                'key_id' => $key->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * บันทึก error สำหรับ key ที่ระบุ (ใช้กับ unified key list)
     *
     * @param  AiApiKey  $key  key ที่ต้องการบันทึก
     * @param  string  $errorMessage  ข้อความ error
     * @param  string|null  $model  model ที่ใช้
     */
    protected function recordErrorForKey(AiApiKey $key, string $errorMessage, ?string $model = null): void
    {
        try {
            $key->recordError($errorMessage, $model);
        } catch (\Exception $e) {
            Log::warning('FortuneAI: บันทึก error สำหรับ key ไม่สำเร็จ', [
                'key_id' => $key->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
