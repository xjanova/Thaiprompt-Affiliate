<?php

namespace Database\Seeders;

use App\Models\LineBotKeyword;
use Illuminate\Database\Seeder;

/**
 * Seed keywords สำหรับระบบดูดวง Fortune Bot
 *
 * สร้าง auto-reply keywords ครอบคลุมบทสนทนาทั่วไป
 * ใช้ category 'fortune' แยกจาก Hybrid Bot keywords
 * Admin จัดการได้ที่ /admin/line-bot/keywords/
 *
 * @version 1.0
 */
class FortuneKeywordSeeder extends Seeder
{
    /**
     * สร้าง keywords สำหรับ Fortune Bot
     *
     * ใช้ firstOrCreate → idempotent, seed ซ้ำได้ไม่สร้างซ้ำ
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🔮 กำลัง seed keywords สำหรับ Fortune Bot...');

        $keywords = $this->getKeywords();
        $created = 0;

        foreach ($keywords as $data) {
            $keyword = LineBotKeyword::firstOrCreate(
                ['keyword' => $data['keyword']],
                $data
            );

            if ($keyword->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info("✅ Seed Fortune Keywords สำเร็จ! สร้างใหม่ {$created} จากทั้งหมด ".count($keywords).' keywords');
    }

    /**
     * รายการ keywords ทั้งหมด
     *
     * @return array<int, array>
     */
    protected function getKeywords(): array
    {
        return array_merge(
            $this->getGreetingKeywords(),
            $this->getGratitudeKeywords(),
            $this->getPricingKeywords(),
            $this->getBotIdentityKeywords(),
            $this->getEmotionKeywords(),
            $this->getSmallTalkKeywords(),
            $this->getFaqKeywords(),
            $this->getReferralKeywords(),
            $this->getReactionKeywords(),
        );
    }

    // =====================================================================
    // ทักทาย & ลาก่อน
    // =====================================================================

    /**
     * Keywords ทักทายและลาก่อน
     */
    protected function getGreetingKeywords(): array
    {
        return [
            [
                'keyword' => 'fortune_greeting',
                'description' => 'ทักทายทั่วไป',
                'trigger_words' => ['สวัสดี', 'หวัดดี', 'ดีจ้า', 'ดีค่ะ', 'ดีครับ', 'hello', 'hi', 'ฮัลโหล', 'สวัสดีค่ะ', 'สวัสดีครับ'],
                'response_type' => 'text',
                'response_text' => "สวัสดีค่ะ 🙏✨ จันทรายินดีต้อนรับค่ะ\n\nวันนี้อยากรู้เรื่องอะไร จันทราพร้อมดูดวงให้ค่ะ 🔮\n\nพิมพ์คำถามมาได้เลย เช่น\n• ดวงความรักปีนี้เป็นอย่างไร\n• ควรเปลี่ยนงานไหม\n• การเงินช่วงนี้จะดีไหม",
                'category' => 'fortune',
                'priority' => 70,
                'is_active' => true,
                'notes' => 'ทักทายทั่วไป — ชวนดูดวง',
            ],
            [
                'keyword' => 'fortune_greeting_morning',
                'description' => 'ทักทายตอนเช้า',
                'trigger_words' => ['อรุณสวัสดิ์', 'สวัสดีตอนเช้า', 'เช้านี้'],
                'response_type' => 'text',
                'response_text' => "อรุณสวัสดิ์ 🌅✨ เช้านี้ดวงดีนะ\n\nเริ่มต้นวันใหม่ด้วยการดูดวงกันไหม? พิมพ์คำถามมาได้เลย 🔮",
                'category' => 'fortune',
                'priority' => 65,
                'is_active' => true,
                'notes' => 'ทักทายตอนเช้า',
            ],
            [
                'keyword' => 'fortune_greeting_evening',
                'description' => 'ทักทายตอนเย็น/กลางคืน',
                'trigger_words' => ['สวัสดีตอนเย็น', 'ราตรีสวัสดิ์', 'สวัสดีตอนค่ำ'],
                'response_type' => 'text',
                'response_text' => "สวัสดียามค่ำ 🌙✨ คืนนี้อยากรู้เรื่องอะไรไหม?\n\nจันทราพร้อมดูดวงให้ พิมพ์คำถามมาได้เลย 🔮",
                'category' => 'fortune',
                'priority' => 65,
                'is_active' => true,
                'notes' => 'ทักทายตอนเย็น',
            ],
            [
                'keyword' => 'fortune_greeting_howru',
                'description' => 'ถามสารทุกข์สุขดิบ',
                'trigger_words' => ['สบายดีไหม', 'เป็นไงบ้าง', 'how are you', 'เป็นอย่างไรบ้าง'],
                'response_type' => 'text',
                'response_text' => "จันทราสบายดี ขอบคุณที่ถาม 😊✨\n\nแล้วคุณล่ะ วันนี้เป็นอย่างไรบ้าง? ถ้าอยากรู้ว่าดวงจะดีขึ้นไหม พิมพ์คำถามมาได้เลย 🔮",
                'category' => 'fortune',
                'priority' => 65,
                'is_active' => true,
                'notes' => 'ถามสบายดีไหม',
            ],
            [
                'keyword' => 'fortune_farewell',
                'description' => 'ลาก่อน',
                'trigger_words' => ['บ๊ายบาย', 'ลาก่อน', 'ไปก่อนนะ', 'bye', 'ไปแล้วนะ', 'ไปก่อน'],
                'response_type' => 'text',
                'response_text' => "ลาก่อน 👋✨ ขอให้โชคดี!\n\nเมื่อไหร่อยากดูดวงอีก กลับมาหาจันทราได้เลย 🔮🌙",
                'category' => 'fortune',
                'priority' => 65,
                'is_active' => true,
                'notes' => 'ลาก่อน',
            ],
            [
                'keyword' => 'fortune_comeback',
                'description' => 'กลับมาใหม่',
                'trigger_words' => ['กลับมาแล้ว', 'มาใหม่', 'มาอีกแล้ว', 'กลับมา'],
                'response_type' => 'text',
                'response_text' => "ยินดีต้อนรับกลับ! 🎉✨ คิดถึงเหมือนกัน\n\nวันนี้อยากรู้เรื่องอะไร? พิมพ์คำถามมาได้เลย 🔮",
                'category' => 'fortune',
                'priority' => 65,
                'is_active' => true,
                'notes' => 'กลับมาใหม่',
            ],
        ];
    }

    // =====================================================================
    // ขอบคุณ & ขอโทษ & ชม & บ่น
    // =====================================================================

    protected function getGratitudeKeywords(): array
    {
        return [
            [
                'keyword' => 'fortune_thanks',
                'description' => 'ขอบคุณ',
                'trigger_words' => ['ขอบคุณ', 'ขอบใจ', 'thanks', 'thx', 'ขอบคุณมาก', 'ขอบคุณค่ะ', 'ขอบคุณครับ', 'ขอบคุณนะ', 'thank you'],
                'response_type' => 'text',
                'response_text' => "ยินดี 🙏💜 จันทราดีใจที่ได้ช่วย\n\nเมื่อไหร่อยากดูดวงเรื่องอื่นอีก พิมพ์มาได้ตลอด 🔮✨",
                'category' => 'fortune',
                'priority' => 70,
                'is_active' => true,
                'notes' => 'ขอบคุณ',
            ],
            [
                'keyword' => 'fortune_sorry',
                'description' => 'ขอโทษ',
                'trigger_words' => ['ขอโทษ', 'sorry', 'ขอโทษที', 'ขอโทษค่ะ', 'ขอโทษครับ'],
                'response_type' => 'text',
                'response_text' => "ไม่เป็นไรเลย 😊 ไม่ต้องขอโทษ\n\nจันทราพร้อมช่วยเสมอ มีอะไรสงสัยหรืออยากดูดวง พิมพ์มาได้เลย 🔮",
                'category' => 'fortune',
                'priority' => 65,
                'is_active' => true,
                'notes' => 'ขอโทษ',
            ],
            [
                'keyword' => 'fortune_praise',
                'description' => 'ชมเชย',
                'trigger_words' => ['เก่ง', 'แม่น', 'สุดยอด', 'ดีมาก', 'เยี่ยม', 'เจ๋ง', 'เก่งมาก', 'แม่นมาก', 'ถูกต้อง', 'ตรงเลย', 'ใช่เลย'],
                'response_type' => 'text',
                'response_text' => "ขอบคุณมาก 😊💜✨ จันทราดีใจที่คำทำนายตรงใจ\n\nถ้าอยากรู้เรื่องอื่นเพิ่มเติม พิมพ์คำถามมาได้เลย 🔮\n\n💎 หรือลอง ดูดวงละเอียด เพื่อคำทำนายเชิงลึกยิ่งขึ้น",
                'category' => 'fortune',
                'priority' => 60,
                'is_active' => true,
                'notes' => 'ชมเชย — ชวนดูละเอียด',
            ],
            [
                'keyword' => 'fortune_complaint',
                'description' => 'บ่น/ไม่พอใจ',
                'trigger_words' => ['ไม่แม่น', 'ผิด', 'ไม่ดี', 'ไม่ถูก', 'ห่วย', 'แย่', 'ไม่ตรง', 'ไม่จริง'],
                'response_type' => 'text',
                'response_text' => "ขอโทษด้วยนะคะ 🙏 จันทราเสียใจที่คำทำนายไม่ตรงใจค่ะ\n\nดวงเป็นเรื่องที่ตีความได้หลายแง่มุม ลองถามคำถามให้ชัดเจนขึ้นอีกครั้งนะคะ\n\n💎 หรือลอง ดูดวงละเอียด ที่ใช้วันเกิดจริงเพื่อความแม่นยำสูงสุดค่ะ",
                'category' => 'fortune',
                'priority' => 60,
                'is_active' => true,
                'notes' => 'บ่น — ชวนดูละเอียด',
            ],
        ];
    }

    // =====================================================================
    // ราคา & บริการ & การชำระเงิน
    // =====================================================================

    protected function getPricingKeywords(): array
    {
        return [
            [
                'keyword' => 'fortune_price',
                'description' => 'ถามราคา/ค่าครู',
                'trigger_words' => ['ราคา', 'ค่าบริการ', 'ค่าครู', 'เท่าไหร่', 'กี่บาท', 'แพงไหม', 'ราคาเท่าไร'],
                'response_type' => 'text',
                'response_text' => "💰 ค่าครูดูดวง:\n\n🆓 ดูดวงพื้นฐาน — ฟรี!\nพิมพ์คำถามมาได้เลย\n\n💎 ดูดวงละเอียด — ค่าครู 49 บาท\nถาม 2 คำถาม + วันเกิด → คำทำนายเชิงลึก\nพิมพ์ \"ดูดวงละเอียด\" เพื่อเริ่ม\n\n🔮 อยากลองดูฟรีก่อน พิมพ์คำถามมาได้เลย!",
                'category' => 'fortune',
                'priority' => 70,
                'is_active' => true,
                'notes' => 'ถามราคา',
            ],
            [
                'keyword' => 'fortune_free_info',
                'description' => 'ถามเกี่ยวกับบริการฟรี',
                'trigger_words' => ['ฟรีไหม', 'ฟรีกี่ครั้ง', 'ดูฟรี', 'ไม่เสียเงิน', 'ฟรีมั้ย', 'เสียเงินไหม', 'ต้องจ่ายไหม'],
                'response_type' => 'text',
                'response_text' => "🆓 ดูดวงพื้นฐาน ฟรี ค่ะ!\n\nมีสิทธิ์ดูฟรีวันละ 5 ครั้ง แค่พิมพ์คำถามมาเลยค่ะ ไม่เสียเงิน 🎉\n\n💎 ถ้าอยากดูละเอียดขึ้น (ใช้วันเกิด + 2 คำถาม) มีบริการ ดูดวงละเอียด 49 บาทค่ะ\n\n🔮 พิมพ์คำถามมาได้เลย!",
                'category' => 'fortune',
                'priority' => 68,
                'is_active' => true,
                'notes' => 'ถามบริการฟรี',
            ],
            [
                'keyword' => 'fortune_deep_info',
                'description' => 'ถามเกี่ยวกับดูดวงละเอียด',
                'trigger_words' => ['ดูละเอียดคืออะไร', 'ละเอียดยังไง', 'ต่างกันยังไง', 'ต่างจากฟรียังไง', 'ดีกว่ายังไง'],
                'response_type' => 'text',
                'response_text' => "💎 ดูดวงละเอียด vs ดูดวงฟรี:\n\n🆓 ดูดวงฟรี:\n• ทำนายจากคำถาม 1 ข้อ\n• คำทำนายพื้นฐาน\n\n💎 ดูดวงละเอียด (49 บาท):\n• ใช้วันเดือนปีเกิดจริง\n• ถาม 2 คำถามเชิงลึก\n• คำทำนายละเอียด + แนวทางแก้ไข\n• แม่นยำกว่ามาก!\n\nพิมพ์ \"ดูดวงละเอียด\" เพื่อเริ่มค่ะ ✨",
                'category' => 'fortune',
                'priority' => 68,
                'is_active' => true,
                'notes' => 'เปรียบเทียบฟรี vs ละเอียด',
            ],
            [
                'keyword' => 'fortune_promo',
                'description' => 'ถามโปรโมชั่น',
                'trigger_words' => ['โปรโมชั่น', 'ส่วนลด', 'ลดราคา', 'คูปอง', 'โปร'],
                'response_type' => 'text',
                'response_text' => "🎁 ตอนนี้ดูดวงพื้นฐาน ฟรี วันละ 5 ครั้งค่ะ!\n\nและดูดวงละเอียดเพียง 49 บาท 💎\n\n👥 แนะนำเพื่อนมาดูดวง ยังได้ค่าคอมมิชชั่นทุกยอดอีกด้วยค่ะ!\n\n🔮 ลองดูฟรีก่อนเลย พิมพ์คำถามมาได้เลย",
                'category' => 'fortune',
                'priority' => 60,
                'is_active' => true,
                'notes' => 'ถามโปร',
            ],
            [
                'keyword' => 'fortune_payment_how',
                'description' => 'ถามวิธีชำระเงิน',
                'trigger_words' => ['จ่ายยังไง', 'ช่องทางชำระ', 'วิธีจ่าย', 'promptpay', 'พร้อมเพย์', 'จ่ายผ่านอะไร', 'วิธีชำระ'],
                'response_type' => 'text',
                'response_text' => "💳 วิธีชำระเงินค่ะ:\n\n1️⃣ เลือก ดูดวงละเอียด\n2️⃣ ระบบจะแจ้งยอดเงินพร้อมทศนิยม (เช่น 49.37 บาท)\n3️⃣ โอนเงินตามยอดที่แจ้ง ผ่านบัญชีธนาคาร\n4️⃣ ระบบตรวจสอบอัตโนมัติ ส่งคำทำนายทันที!\n\n🚨 สำคัญ: ต้องโอนตรงทศนิยมเท่านั้นนะคะ!\n\nพิมพ์ \"บัญชี\" เพื่อดูเลขบัญชีค่ะ 🏦",
                'category' => 'fortune',
                'priority' => 68,
                'is_active' => true,
                'notes' => 'วิธีชำระเงิน',
            ],
            [
                'keyword' => 'fortune_payment_proof',
                'description' => 'แจ้งว่าโอนแล้ว',
                'trigger_words' => ['โอนแล้ว', 'จ่ายแล้ว', 'ชำระแล้ว', 'ส่งสลิป', 'โอนเงินแล้ว'],
                'response_type' => 'text',
                'response_text' => "🙏 ขอบคุณค่ะ! ระบบจะตรวจสอบยอดโอนอัตโนมัติค่ะ\n\n⏳ ปกติใช้เวลาไม่เกิน 1-2 นาที\n\nถ้าโอนตรงยอดแล้วยังไม่ได้รับคำทำนาย รอสักครู่นะคะ ระบบกำลังตรวจสอบอยู่ค่ะ ✨\n\n🚨 ถ้ารอเกิน 5 นาที พิมพ์ \"บัญชี\" เพื่อเช็คสถานะค่ะ",
                'category' => 'fortune',
                'priority' => 68,
                'is_active' => true,
                'notes' => 'แจ้งโอนแล้ว',
            ],
        ];
    }

    // =====================================================================
    // ตัวตน Bot
    // =====================================================================

    protected function getBotIdentityKeywords(): array
    {
        return [
            [
                'keyword' => 'fortune_who',
                'description' => 'ถามว่าเป็นใคร',
                'trigger_words' => ['คุณเป็นใคร', 'ชื่ออะไร', 'เป็นใคร', 'แนะนำตัว', 'คุณชื่ออะไร'],
                'response_type' => 'text',
                'response_text' => "🔮 จันทราค่ะ ยินดีที่ได้รู้จักนะคะ ✨\n\nจันทราเป็นหมอดูที่ใช้ AI ช่วยวิเคราะห์ดวงชะตาค่ะ\nรับดูดวงหลายเรื่อง: ความรัก การงาน การเงิน สุขภาพ\n\n💜 อยากรู้เรื่องอะไร พิมพ์คำถามมาได้เลย 🔮",
                'category' => 'fortune',
                'priority' => 60,
                'is_active' => true,
                'notes' => 'แนะนำตัว',
            ],
            [
                'keyword' => 'fortune_is_ai',
                'description' => 'ถามว่าเป็น AI/Bot ไหม',
                'trigger_words' => ['เป็นบอทไหม', 'เป็น ai ไหม', 'คนจริงไหม', 'เป็นหุ่นยนต์ไหม', 'เป็นคนจริงหรือเปล่า', 'เป็นบอตไหม', 'เป็นคนไหม'],
                'response_type' => 'text',
                'response_text' => "🤖✨ จันทราใช้ AI อัจฉริยะในการวิเคราะห์ดวงค่ะ\n\nผสมผสานศาสตร์โหราศาสตร์กับเทคโนโลยี AI ทำให้ทำนายได้แม่นยำและรวดเร็วค่ะ 💜\n\nลองพิมพ์คำถามมาสิคะ จะได้เห็นว่าจันทราเก่งแค่ไหน 🔮",
                'category' => 'fortune',
                'priority' => 60,
                'is_active' => true,
                'notes' => 'ถามเป็น AI ไหม',
            ],
            [
                'keyword' => 'fortune_method',
                'description' => 'ถามวิธีดูดวง',
                'trigger_words' => ['ดูดวงแม่นไหม', 'ใช้วิธีอะไร', 'ดูยังไง', 'ศาสตร์อะไร', 'ใช้อะไรดู', 'วิเคราะห์ยังไง'],
                'response_type' => 'text',
                'response_text' => "🔮 จันทราใช้การผสมผสานหลายศาสตร์ค่ะ:\n\n⭐ โหราศาสตร์ไทย\n🌙 ดาราศาสตร์\n🃏 ไพ่ทาโรต์ดิจิทัล\n🤖 AI วิเคราะห์เชิงลึก\n\n💎 ดูดวงละเอียด ที่ใช้วันเกิดจริง จะแม่นยำสูงสุดค่ะ!\n\nอยากลองไหมคะ? พิมพ์คำถามมาได้เลย ✨",
                'category' => 'fortune',
                'priority' => 60,
                'is_active' => true,
                'notes' => 'ถามวิธีดูดวง',
            ],
            [
                'keyword' => 'fortune_developer',
                'description' => 'ถามว่าใครสร้าง',
                'trigger_words' => ['ใครสร้าง', 'ใครทำ', 'developer', 'คนเขียน', 'ใครพัฒนา'],
                'response_type' => 'text',
                'response_text' => "💻 จันทราถูกพัฒนาโดยทีม ThaiPrompt\n\nเป็นส่วนหนึ่งของ TP-Affiliate Platform ✨\n\n🔮 แต่ตอนนี้อยากรู้ดวงอะไรไหม? พิมพ์คำถามมาได้เลย!",
                'category' => 'fortune',
                'priority' => 55,
                'is_active' => true,
                'notes' => 'ถามใครพัฒนา',
            ],
        ];
    }

    // =====================================================================
    // อารมณ์ & ความรู้สึก
    // =====================================================================

    protected function getEmotionKeywords(): array
    {
        return [
            [
                'keyword' => 'fortune_sad',
                'description' => 'เศร้า/เสียใจ',
                'trigger_words' => ['เศร้า', 'เสียใจ', 'ร้องไห้', 'ไม่สบายใจ', 'ทุกข์', 'เศร้าจัง', 'เศร้าเลย'],
                'response_type' => 'text',
                'response_text' => "🫂 จันทราเข้าใจ... ไม่ต้องเก็บเอาไว้คนเดียว\n\nเรื่องที่กังวลอยู่ บางทีดวงอาจบอกทางออกให้ได้ 💜\n\n🔮 อยากให้จันทราดูดวงเรื่องที่กังวลไหม? พิมพ์มาได้เลย",
                'category' => 'fortune',
                'priority' => 60,
                'is_active' => true,
                'notes' => 'เศร้า — ปลอบใจ + ชวนดูดวง',
            ],
            [
                'keyword' => 'fortune_happy',
                'description' => 'มีความสุข/ดีใจ',
                'trigger_words' => ['ดีใจ', 'มีความสุข', 'สนุก', 'ยินดี', 'ดีใจจัง', 'แฮปปี้'],
                'response_type' => 'text',
                'response_text' => "🎉 ดีใจด้วย! เห็นมีความสุขแบบนี้ จันทราก็ยิ้มตาม 😊💜\n\nอยากรู้ไหมว่าความสุขนี้จะต่อเนื่องไปอีกนานแค่ไหน? 🔮✨\nพิมพ์คำถามมาได้เลย!",
                'category' => 'fortune',
                'priority' => 55,
                'is_active' => true,
                'notes' => 'มีความสุข',
            ],
            [
                'keyword' => 'fortune_worried',
                'description' => 'กังวล/เครียด',
                'trigger_words' => ['กังวล', 'เครียด', 'กลัว', 'ห่วง', 'ไม่แน่ใจ', 'เครียดมาก', 'กลัวจัง', 'วิตก'],
                'response_type' => 'text',
                'response_text' => "💜 ใจเย็นๆ นะคะ จันทราอยู่ตรงนี้ค่ะ\n\nเรื่องที่กังวล ลองเล่าให้จันทราฟังไหมคะ? อาจมองเห็นทางออกจากดวงชะตาได้ค่ะ 🔮\n\nพิมพ์สิ่งที่กังวลมาเลยนะคะ เช่น \"ดวงการงานจะดีไหม\" ✨",
                'category' => 'fortune',
                'priority' => 60,
                'is_active' => true,
                'notes' => 'กังวล — ชวนดูดวง',
            ],
            [
                'keyword' => 'fortune_tired',
                'description' => 'เหนื่อย/ท้อ',
                'trigger_words' => ['เหนื่อย', 'ท้อ', 'หมดแรง', 'เบื่อ', 'ท้อแท้', 'เหนื่อยมาก'],
                'response_type' => 'text',
                'response_text' => "💪 สู้ๆ! ช่วงนี้อาจเหนื่อยบ้าง แต่จะดีขึ้น\n\nอยากรู้ไหมว่าช่วงที่จะดีขึ้นอยู่ตรงไหน? จันทราดูดวงให้ได้ 🔮✨\n\nพิมพ์คำถามมาได้เลย!",
                'category' => 'fortune',
                'priority' => 55,
                'is_active' => true,
                'notes' => 'เหนื่อย — ให้กำลังใจ',
            ],
            [
                'keyword' => 'fortune_angry',
                'description' => 'โกรธ/หงุดหงิด',
                'trigger_words' => ['โกรธ', 'โมโห', 'หงุดหงิด', 'อารมณ์เสีย', 'โกรธมาก'],
                'response_type' => 'text',
                'response_text' => "😤→😌 ใจเย็นๆ ก่อน หายใจลึกๆ...\n\nบางทีดวงอาจบอกได้ว่าเหตุการณ์นี้จะคลี่คลายเมื่อไหร่ 💜\n\n🔮 อยากให้จันทราดูดวงเรื่องนี้ไหม? พิมพ์มาได้เลย",
                'category' => 'fortune',
                'priority' => 55,
                'is_active' => true,
                'notes' => 'โกรธ — สงบสติ + ชวนดูดวง',
            ],
            [
                'keyword' => 'fortune_confused',
                'description' => 'สับสน/ไม่เข้าใจ',
                'trigger_words' => ['สับสน', 'ไม่เข้าใจ', 'งง', 'มึน', 'งงจัง', 'ไม่เข้าใจเลย'],
                'response_type' => 'text',
                'response_text' => "🤔 ไม่ต้องงงค่ะ จันทราจะอธิบายให้นะคะ!\n\n📋 วิธีใช้งาน:\n• พิมพ์คำถาม → ดูดวงฟรี\n• พิมพ์ \"ดูดวงละเอียด\" → ดูดวงเสียเงิน (49 บาท)\n• พิมพ์ \"เช็คสิทธิ์\" → ดูสิทธิ์คงเหลือ\n• พิมพ์ \"ดูคำทำนาย\" → ดูคำทำนายล่าสุด\n\n🔮 ลองพิมพ์คำถามมาเลยค่ะ!",
                'category' => 'fortune',
                'priority' => 60,
                'is_active' => true,
                'notes' => 'สับสน — แนะนำวิธีใช้',
            ],
            [
                'keyword' => 'fortune_hopeful',
                'description' => 'มีหวัง/อยากให้',
                'trigger_words' => ['มีหวัง', 'หวังว่า', 'อยากให้', 'ขอให้', 'หวังจะ'],
                'response_type' => 'text',
                'response_text' => "🌟 ความหวังเป็นสิ่งดี! จันทราเชื่อว่าทุกคนมีดวงที่ดีรออยู่ 💜\n\nอยากรู้ไหมว่าสิ่งที่หวังจะเป็นจริงเมื่อไหร่? 🔮\nพิมพ์คำถามมาเลย ✨",
                'category' => 'fortune',
                'priority' => 55,
                'is_active' => true,
                'notes' => 'มีหวัง',
            ],
            [
                'keyword' => 'fortune_lonely',
                'description' => 'เหงา/คิดถึง',
                'trigger_words' => ['เหงา', 'อ้างว้าง', 'คิดถึง', 'อยู่คนเดียว', 'เหงาจัง'],
                'response_type' => 'text',
                'response_text' => "🫂 จันทราอยู่ตรงนี้ ไม่ได้อยู่คนเดียว 💜\n\nอยากรู้ไหมว่าจะมีคนพิเศษเข้ามาในชีวิตเมื่อไหร่? 🔮✨\n\nพิมพ์ \"ดวงความรัก\" มาได้เลย 💕",
                'category' => 'fortune',
                'priority' => 55,
                'is_active' => true,
                'notes' => 'เหงา — ชวนดูดวงความรัก',
            ],
        ];
    }

    // =====================================================================
    // Small Talk
    // =====================================================================

    protected function getSmallTalkKeywords(): array
    {
        return [
            [
                'keyword' => 'fortune_what_doing',
                'description' => 'ถามว่าทำอะไรอยู่',
                'trigger_words' => ['ทำอะไรอยู่', 'ว่างไหม', 'อยู่ไหม', 'ว่างมั้ย', 'อยู่มั้ย'],
                'response_type' => 'text',
                'response_text' => "🔮 จันทราว่างเสมอ! พร้อมดูดวงให้ตลอด 24 ชั่วโมง ✨\n\nวันนี้อยากรู้เรื่องอะไร? พิมพ์คำถามมาได้เลย 💜",
                'category' => 'fortune',
                'priority' => 55,
                'is_active' => true,
                'notes' => 'ถามทำอะไรอยู่',
            ],
            [
                'keyword' => 'fortune_eat',
                'description' => 'ถามกินข้าวยัง',
                'trigger_words' => ['กินข้าวยัง', 'กินอะไร', 'ทานข้าวหรือยัง', 'กินข้าวหรือยัง', 'หิวไหม'],
                'response_type' => 'text',
                'response_text' => "😋 จันทราไม่ต้องกินข้าว แต่ขอบคุณที่ห่วง!\n\nแต่ถ้าถามว่าวันนี้ควรกินอะไร... ลองถามดวงดูไหม? 😄🔮\n\nพิมพ์คำถามมาได้เลย!",
                'category' => 'fortune',
                'priority' => 50,
                'is_active' => true,
                'notes' => 'ถามกินข้าว',
            ],
            [
                'keyword' => 'fortune_weather',
                'description' => 'ถามเกี่ยวกับอากาศ',
                'trigger_words' => ['อากาศ', 'ฝนตก', 'ร้อน', 'หนาว', 'อากาศวันนี้'],
                'response_type' => 'text',
                'response_text' => "☁️ จันทราดูดวงเก่งกว่าดูอากาศ 😄\n\nแต่ถ้าอยากรู้ว่าดวงวันนี้จะเป็นอย่างไร จันทราช่วยได้เลย! 🔮✨\n\nพิมพ์คำถามมาได้เลย",
                'category' => 'fortune',
                'priority' => 50,
                'is_active' => true,
                'notes' => 'ถามอากาศ',
            ],
            [
                'keyword' => 'fortune_joke',
                'description' => 'ขอเล่ามุก/ตลก',
                'trigger_words' => ['เล่ามุกให้ฟัง', 'ตลก', 'ขำ', 'joke', 'เล่ามุก', 'มุกตลก'],
                'response_type' => 'text',
                'response_text' => "😄 จันทราเล่ามุกไม่เก่งเท่าดูดวงค่ะ\n\nแต่ขอเล่าสักมุก: \"ถามหมอดูว่าดวงจะเฮงเมื่อไหร่ หมอดูตอบว่า... พรุ่งนี้ เพราะวันนี้ยังไม่หมด\" 😂\n\n🔮 ลองถามดวงจริงๆ ดีกว่าค่ะ พิมพ์คำถามมาเลย! ✨",
                'category' => 'fortune',
                'priority' => 50,
                'is_active' => true,
                'notes' => 'ขอมุกตลก',
            ],
            [
                'keyword' => 'fortune_age',
                'description' => 'ถามอายุ',
                'trigger_words' => ['อายุเท่าไหร่', 'เกิดปีอะไร', 'กี่ขวบ', 'อายุเท่าไร'],
                'response_type' => 'text',
                'response_text' => "🌙 จันทราอยู่มานานเท่ากับดวงจันทร์เลย ✨ (ไม่บอกอายุนะ 😄)\n\nแต่ถ้าอยากรู้ดวงตามปีเกิด จันทราช่วยได้!\nพิมพ์ \"ดูดวงละเอียด\" จะได้ใช้วันเกิดจริงดูดวง 💎",
                'category' => 'fortune',
                'priority' => 50,
                'is_active' => true,
                'notes' => 'ถามอายุ',
            ],
            [
                'keyword' => 'fortune_love_bot',
                'description' => 'แสดงความรัก/ชอบ',
                'trigger_words' => ['รักนะ', 'ชอบนะ', 'น่ารัก', 'ชอบจัง', 'รักค่ะ', 'รักครับ'],
                'response_type' => 'text',
                'response_text' => "😊💜 อ๊ะ~ ขอบคุณ! จันทราก็รักทุกคนที่มาดูดวงเหมือนกัน ✨\n\nถ้าอยากรู้เรื่องความรัก จันทราดูให้ได้ 💕\nพิมพ์ \"ดวงความรัก\" มาได้เลย 🔮",
                'category' => 'fortune',
                'priority' => 55,
                'is_active' => true,
                'notes' => 'แสดงความรัก',
            ],
        ];
    }

    // =====================================================================
    // FAQ ระบบดูดวง
    // =====================================================================

    protected function getFaqKeywords(): array
    {
        return [
            [
                'keyword' => 'fortune_faq_times',
                'description' => 'ถามจำนวนครั้ง',
                'trigger_words' => ['ดูได้กี่ครั้ง', 'วันละกี่ครั้ง', 'จำกัดกี่ครั้ง', 'เหลือกี่ครั้ง'],
                'response_type' => 'text',
                'response_text' => "📊 สิทธิ์การดูดวง:\n\n🆓 ดูดวงฟรี — วันละ 5 ครั้ง\n💎 ดูดวงละเอียด — ไม่จำกัด (49 บาท/ครั้ง)\n\nพิมพ์ \"เช็คสิทธิ์\" เพื่อดูสิทธิ์คงเหลือ ✨\n\n🔮 พิมพ์คำถามมาได้เลย!",
                'category' => 'fortune',
                'priority' => 65,
                'is_active' => true,
                'notes' => 'จำนวนครั้ง',
            ],
            [
                'keyword' => 'fortune_faq_accuracy',
                'description' => 'ถามความแม่นยำ',
                'trigger_words' => ['แม่นแค่ไหน', 'แม่นไหม', 'ดูแม่นยำไหม', 'เชื่อถือได้ไหม', 'น่าเชื่อถือไหม'],
                'response_type' => 'text',
                'response_text' => "🎯 จันทราใช้ AI วิเคราะห์ร่วมกับหลักโหราศาสตร์ค่ะ\n\nผู้ใช้ส่วนใหญ่ชื่นชอบคำทำนายที่ได้ 😊\n\n💎 ดูดวงละเอียด ที่ใช้วันเกิดจริง จะแม่นยำกว่าดูฟรีมากค่ะ\n\n🔮 ลองพิมพ์คำถามมาเลย แล้วตัดสินเองค่ะ! ✨",
                'category' => 'fortune',
                'priority' => 60,
                'is_active' => true,
                'notes' => 'ถามความแม่นยำ',
            ],
            [
                'keyword' => 'fortune_faq_privacy',
                'description' => 'ถามความเป็นส่วนตัว',
                'trigger_words' => ['เป็นความลับไหม', 'ข้อมูลปลอดภัยไหม', 'ใครเห็นบ้าง', 'ข้อมูลหายไหม', 'ปลอดภัยไหม'],
                'response_type' => 'text',
                'response_text' => "🔒 ข้อมูลทุกอย่างเป็นความลับค่ะ!\n\n• คำถามและคำทำนาย ไม่มีใครเห็นนอกจากคุณ\n• ข้อมูลวันเกิดใช้เฉพาะการดูดวงเท่านั้น\n• ไม่แชร์ข้อมูลให้บุคคลที่สาม\n\n💜 ไว้วางใจได้ค่ะ พิมพ์คำถามมาได้เลย 🔮",
                'category' => 'fortune',
                'priority' => 60,
                'is_active' => true,
                'notes' => 'ถามความเป็นส่วนตัว',
            ],
            [
                'keyword' => 'fortune_faq_reset',
                'description' => 'เริ่มใหม่/รีเซ็ต',
                'trigger_words' => ['เริ่มใหม่', 'reset', 'รีเซ็ต', 'เคลียร์', 'ล้างข้อมูล'],
                'response_type' => 'text',
                'response_text' => "🔄 ถ้าอยากเริ่มดูดวงใหม่ พิมพ์ \"ยกเลิก\" ก่อน แล้วพิมพ์คำถามใหม่ได้เลย ✨\n\nหรือพิมพ์คำถามใหม่มาตรงๆ ก็ได้ 🔮\n\nจันทราพร้อมเสมอ!",
                'category' => 'fortune',
                'priority' => 60,
                'is_active' => true,
                'notes' => 'เริ่มใหม่',
            ],
            [
                'keyword' => 'fortune_faq_history',
                'description' => 'ดูประวัติ/ย้อนหลัง',
                'trigger_words' => ['ประวัติ', 'ดูย้อนหลัง', 'ดูเก่า', 'history', 'คำทำนายเก่า'],
                'response_type' => 'text',
                'response_text' => "📖 ดูคำทำนายล่าสุดได้!\n\nพิมพ์ \"ดูคำทำนาย\" หรือกดปุ่ม \"คำทำนายล่าสุด\" ที่เมนูด้านล่าง ✨\n\n🔮 หรือพิมพ์คำถามใหม่มาเลยก็ได้!",
                'category' => 'fortune',
                'priority' => 60,
                'is_active' => true,
                'notes' => 'ดูประวัติ',
            ],
            [
                'keyword' => 'fortune_faq_time_response',
                'description' => 'ถามเวลาตอบ',
                'trigger_words' => ['รอนานไหม', 'ใช้เวลานานไหม', 'กี่นาที', 'ใช้เวลาเท่าไหร่'],
                'response_type' => 'text',
                'response_text' => "⚡ เวลาในการดูดวง:\n\n🆓 ดูดวงฟรี — ประมาณ 10-30 วินาที\n💎 ดูดวงละเอียด — ประมาณ 1-2 นาที (หลังชำระเงิน)\n\nรวดเร็วมากเลย! พิมพ์คำถามมาได้เลย 🔮✨",
                'category' => 'fortune',
                'priority' => 58,
                'is_active' => true,
                'notes' => 'ถามเวลาตอบ',
            ],
            [
                'keyword' => 'fortune_faq_topics',
                'description' => 'ถามดูเรื่องอะไรได้',
                'trigger_words' => ['ดูเรื่องอะไรได้บ้าง', 'ถามเรื่องอะไรได้', 'ดูได้กี่เรื่อง', 'ดูอะไรได้บ้าง'],
                'response_type' => 'text',
                'response_text' => "🔮 จันทราดูดวงได้หลายเรื่องค่ะ:\n\n💕 ความรัก คู่ครอง เนื้อคู่\n💼 การงาน อาชีพ เปลี่ยนงาน\n💰 การเงิน โชคลาภ การลงทุน\n🏥 สุขภาพ\n📚 การเรียน สอบ\n👨‍👩‍👧 ครอบครัว\n🏠 บ้าน การเดินทาง\n\n✨ พิมพ์คำถามมาได้เลย!",
                'category' => 'fortune',
                'priority' => 65,
                'is_active' => true,
                'notes' => 'ถามดูเรื่องอะไรได้',
            ],
            [
                'keyword' => 'fortune_faq_birthdate',
                'description' => 'ถามเรื่องวันเกิด',
                'trigger_words' => ['ต้องใช้วันเกิดไหม', 'ทำไมต้องวันเกิด', 'ไม่รู้วันเกิด', 'ไม่อยากให้วันเกิด'],
                'response_type' => 'text',
                'response_text' => "📅 เรื่องวันเกิดค่ะ:\n\n🆓 ดูดวงฟรี — ไม่ต้องใช้วันเกิด ถามได้เลย!\n💎 ดูดวงละเอียด — ใช้วันเกิดเพื่อความแม่นยำสูงสุด\n\nวันเกิดช่วยให้ AI วิเคราะห์ดวงได้แม่นยำกว่ามากค่ะ ✨\nข้อมูลเป็นความลับ ไม่เปิดเผยให้ใครค่ะ 🔒\n\n🔮 ลองดูฟรีก่อนเลย ไม่ต้องใช้วันเกิดค่ะ!",
                'category' => 'fortune',
                'priority' => 58,
                'is_active' => true,
                'notes' => 'ถามเรื่องวันเกิด',
            ],
        ];
    }

    // =====================================================================
    // Referral / Affiliate
    // =====================================================================

    protected function getReferralKeywords(): array
    {
        return [
            [
                'keyword' => 'fortune_referral',
                'description' => 'แนะนำเพื่อน',
                'trigger_words' => ['แนะนำเพื่อน', 'ชวนเพื่อน', 'แชร์ให้เพื่อน', 'ได้เงินไหม', 'ชวนคนมาดูดวง'],
                'response_type' => 'text',
                'response_text' => "👥 ระบบแนะนำเพื่อนค่ะ!\n\nแชร์ให้เพื่อนมาดูดวง → รับค่าคอมมิชชั่นทุกยอดดูดวงละเอียด! 💰\n\n📱 กดปุ่ม \"แชร์ให้เพื่อน\" ที่เมนูด้านล่างเพื่อเชิญเพื่อนค่ะ\n\n💎 เพื่อนดูดวงละเอียด → คุณได้ค่าคอม!\nเช็คยอดที่ \"สิทธิ์/Wallet\" ค่ะ ✨",
                'category' => 'fortune',
                'priority' => 58,
                'is_active' => true,
                'notes' => 'แนะนำเพื่อน',
            ],
            [
                'keyword' => 'fortune_commission',
                'description' => 'ถามค่าคอมมิชชั่น',
                'trigger_words' => ['ค่าคอมมิชชั่น', 'ได้ค่าคอม', 'รายได้', 'commission', 'ค่าคอม'],
                'response_type' => 'text',
                'response_text' => "💰 ค่าคอมมิชชั่นค่ะ!\n\nเมื่อแนะนำเพื่อนมาดูดวงละเอียด คุณจะได้ค่าคอมมิชชั่นทุกยอดค่ะ 🎉\n\n📊 เช็คยอดรายได้: กดปุ่ม \"สิทธิ์/Wallet\"\n💰 ดูยอด Wallet: กดปุ่ม \"💰 Wallet\" ในหน้าสถานะ\n\n👥 แชร์ให้เพื่อนเลย เพื่อเริ่มสร้างรายได้ค่ะ! ✨",
                'category' => 'fortune',
                'priority' => 55,
                'is_active' => true,
                'notes' => 'ถามค่าคอม',
            ],
            [
                'keyword' => 'fortune_wallet',
                'description' => 'ถามเกี่ยวกับ Wallet',
                'trigger_words' => ['wallet คืออะไร', 'วอลเล็ต', 'ถอนเงินยังไง', 'ถอนเงิน', 'wallet', 'กระเป๋าเงิน'],
                'response_type' => 'text',
                'response_text' => "💰 Wallet คือกระเป๋าเงินในระบบค่ะ!\n\nเงินจากค่าคอมมิชชั่น (แนะนำเพื่อน) จะเข้า Wallet โดยอัตโนมัติค่ะ\n\n📊 เช็คยอด: กดปุ่ม \"สิทธิ์/Wallet\" ที่เมนูด้านล่าง\n\n✨ แนะนำเพื่อนมาดูดวง = ได้เงินเข้า Wallet ทุกยอดค่ะ!",
                'category' => 'fortune',
                'priority' => 55,
                'is_active' => true,
                'notes' => 'ถาม Wallet',
            ],
        ];
    }

    // =====================================================================
    // Reactions
    // =====================================================================

    protected function getReactionKeywords(): array
    {
        return [
            [
                'keyword' => 'fortune_laugh',
                'description' => 'หัวเราะ',
                'trigger_words' => ['555', '5555', '55555', 'ฮ่าๆ', 'อิอิ', 'lol', 'ฮาๆ', 'ขำ'],
                'response_type' => 'text',
                'response_text' => "555 😆 มีอารมณ์ดี ดวงวันนี้น่าจะดี ✨\n\nอยากรู้ไหมว่าดวงดีจริงไหม? พิมพ์คำถามมาได้เลย 🔮",
                'category' => 'fortune',
                'priority' => 50,
                'is_active' => true,
                'notes' => 'หัวเราะ',
            ],
            [
                'keyword' => 'fortune_wow',
                'description' => 'ตกใจ/ตื่นเต้น',
                'trigger_words' => ['ว้าว', 'โอ้โห', 'เว่อ', 'จริงเหรอ', 'ไม่จริง', 'จริงหรอ', 'โอโห'],
                'response_type' => 'text',
                'response_text' => "😲✨ จริง! จันทราดูดวงได้แม่นจริงๆ\n\nลองถามเรื่องอื่นดูอีกไหม? 🔮💜\nพิมพ์คำถามมาได้เลย!",
                'category' => 'fortune',
                'priority' => 50,
                'is_active' => true,
                'notes' => 'ตกใจ/ตื่นเต้น',
            ],
            [
                'keyword' => 'fortune_ok',
                'description' => 'รับทราบ/โอเค',
                'trigger_words' => ['โอเค', 'โอเคค่ะ', 'ได้เลย', 'เข้าใจแล้ว', 'ok', 'oke', 'โอเคครับ', 'เข้าใจ'],
                'response_type' => 'text',
                'response_text' => "👍 รับทราบ! มีอะไรอยากถามเพิ่ม พิมพ์มาได้เลย 🔮✨\n\nจันทราพร้อมดูดวงให้ตลอด 💜",
                'category' => 'fortune',
                'priority' => 50,
                'is_active' => true,
                'notes' => 'รับทราบ',
            ],
        ];
    }
}
