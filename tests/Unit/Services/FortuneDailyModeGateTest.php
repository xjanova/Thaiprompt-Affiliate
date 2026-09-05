<?php

namespace Tests\Unit\Services;

use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneBotMode;
use App\Services\FortuneConversationService;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * ทดสอบตัวตีความคำตอบของโหมด DM ดูดวงรายวัน (DailyHoroscopeModeTrait)
 *
 * ทดสอบเฉพาะฟังก์ชันบริสุทธิ์ที่ไม่แตะ DB/Cache — ส่วน guard ที่แตะ DB
 * (จ่ายเงินแล้ว/บิลค้าง/ธง pending) ต้องทดสอบด้วย Feature test แยก
 */
class FortuneDailyModeGateTest extends TestCase
{
    protected FortuneConversationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = (new ReflectionClass(FortuneConversationService::class))
            ->newInstanceWithoutConstructor();
    }

    protected function invokeMethod(string $method, mixed ...$args): mixed
    {
        $m = new ReflectionMethod($this->service, $method);
        $m->setAccessible(true);

        return $m->invoke($this->service, ...$args);
    }

    /**
     * ชื่อวันในสัปดาห์ → index 0-6 (ต้องตรงกับคอลัมน์ birth_day)
     *
     * @test
     */
    public function จับชื่อวันในสัปดาห์ได้ถูกต้อง(): void
    {
        $expect = [
            'อาทิตย์' => 0, 'วันอาทิตย์' => 0, 'เกิดวันอาทิตย์ค่ะ' => 0,
            'จันทร์' => 1, 'วันจันทร์' => 1, 'เกิดวันจันทร์' => 1,
            'อังคาร' => 2, 'วันอังคาร' => 2,
            'พุธ' => 3, 'วันพุธ' => 3,
            'พฤหัสบดี' => 4, 'พฤหัส' => 4, 'วันพฤหัสบดีค่ะ' => 4,
            'ศุกร์' => 5, 'วันศุกร์' => 5,
            'เสาร์' => 6, 'วันเสาร์' => 6,
        ];

        foreach ($expect as $text => $index) {
            $this->assertSame($index, $this->invokeMethod('detectThaiDayName', $text), "ข้อความ: {$text}");
        }
    }

    /**
     * ข้อความที่ไม่ใช่ชื่อวัน ต้องไม่ถูกจับ
     *
     * @test
     */
    public function ไม่จับข้อความที่ไม่ใช่ชื่อวัน(): void
    {
        foreach (['สวัสดีค่ะ', 'ขอบคุณค่ะ', '0872804575', 'อยากดูดวง', ''] as $text) {
            $this->assertNull($this->invokeMethod('detectThaiDayName', $text), "ข้อความ: {$text}");
        }
    }

    /**
     * ด่านเช็คเร็ว "หน้าตาเป็นวันที่" — กันข้อความทั่วไปไปโดน AI fallback (0.7-7.5 วิ)
     *
     * @test
     */
    public function ด่านเช็คเร็วต้องแยกวันที่ออกจากข้อความทั่วไป(): void
    {
        // ✅ หน้าตาเป็นวันที่
        foreach (['12/05/2530', '12-5-30', '12.5.2530', '12 5 2530', '15 มีนาคม 2538', '15 มี.ค. 38'] as $t) {
            $this->assertTrue($this->invokeMethod('looksLikeDateInput', $t), "ควรผ่าน: {$t}");
        }

        // ❌ ไม่ใช่วันที่ — ต้องไม่เรียก parser ตัวเต็ม
        foreach (['สวัสดีค่ะ', 'ขอบคุณมากนะคะ', 'อยากรู้ดวงจัง', '0872804575'] as $t) {
            $this->assertFalse($this->invokeMethod('looksLikeDateInput', $t), "ไม่ควรผ่าน: {$t}");
        }
    }

    /**
     * ลูกค้าเปลี่ยนเรื่อง — ต้องคืนธงแล้วปล่อย flow เดิม ห้ามดันวันเกิดต่อ
     *
     * @test
     */
    public function จับคำที่ลูกค้าเปลี่ยนเรื่องได้(): void
    {
        foreach (['โอนแล้วค่ะ', 'ขอคุยกับคน', 'ยกเลิก', 'อยากดูดวง', 'เช็คสถานะ'] as $t) {
            $this->assertTrue($this->invokeMethod('looksLikeDailyEscape', $t), "ควรจับ: {$t}");
        }

        foreach (['วันจันทร์', '12/05/2530', 'สวัสดีค่ะ'] as $t) {
            $this->assertFalse($this->invokeMethod('looksLikeDailyEscape', $t), "ไม่ควรจับ: {$t}");
        }
    }

    /**
     * "ขอบคุณ" ล้วน → ตอบคำอวยพร · มีคำถามตามหลัง → ต้องไม่กิน
     *
     * บทเรียนเคส R4543: "ขอบคุณค่ะ แล้วเรื่องงานล่ะ" เคยถูกกินเป็น ack
     * ทำให้คำถามของลูกค้าหายไป
     *
     * @test
     */
    public function จับคำขอบคุณล้วนได้และไม่กินคำถามที่ตามหลัง(): void
    {
        // ✅ ขอบคุณล้วน → ตอบคำอวยพรได้
        foreach ([
            'ขอบคุณ', 'ขอบคุณค่ะ', 'ขอบคุณครับ', 'ขอบคุณมากค่ะ', 'ขอบคุณค่ะแม่หมอ',
            'ขอบคุณมากๆนะคะ', 'ขอบพระคุณค่ะ', 'ขอบใจจ้า', 'thank you', 'thanks',
        ] as $t) {
            $this->assertTrue($this->invokeMethod('looksLikePureThanks', $t), "ควรจับ: {$t}");
        }

        // ❌ มีเนื้อหาตามหลัง → ห้ามกิน (คำถามลูกค้าต้องไหลไป flow ปกติ)
        foreach ([
            'ขอบคุณค่ะ แล้วเรื่องงานล่ะ',
            'ขอบคุณค่ะ อยากถามเรื่องความรัก',
            'ขอบคุณนะคะ ดูดวงอีกได้ไหม',
            'สวัสดีค่ะ',
            '',
        ] as $t) {
            $this->assertFalse($this->invokeMethod('looksLikePureThanks', $t), "ไม่ควรจับ: {$t}");
        }
    }

    /**
     * ปุ่มวันเกิด — payload ต้องตรงกับ index และมีครบ 8 ปุ่ม
     *
     * 🌙 (2026-09-05) เพิ่มปุ่มที่ 8 "พุธกลางคืน" (ราหู) — ตำราไทยมี 8 วันเกิด ไม่ใช่ 7
     *    ปุ่มสุดท้ายต้องเป็น index 7 และป้ายต้องอ่านออกด้วยตัวจับชื่อวัน (เผื่อ payload หาย)
     *
     * @test
     */
    public function ปุ่มแปดวันเกิดต้องครบและpayloadตรงindex(): void
    {
        $buttons = FortuneConversationService::dailyBirthdayQuickReplies();

        $this->assertCount(8, $buttons);
        $this->assertStringContainsString('พุธกลางคืน', $buttons[7]['title']);
        $this->assertSame('DAILY_BDAY_7', $buttons[7]['payload']);

        // 🚨 ปุ่มต้องไม่ตาย — ทุก payload ต้องมี case จริงใน handleQuickReply
        //    (เพิ่มปุ่มแล้วลืม case = ลูกค้ากดแล้ว payload ดิบไหลเข้า processMessage)
        $controller = file_get_contents(app_path('Http/Controllers/FacebookWebhookController.php'));
        foreach ($buttons as $btn) {
            $this->assertStringContainsString("'{$btn['payload']}'", (string) $controller,
                "payload {$btn['payload']} ไม่มี case รองรับ = ปุ่มตาย");
        }

        foreach ($buttons as $index => $btn) {
            $this->assertSame('DAILY_BDAY_'.$index, $btn['payload']);
            $this->assertSame('text', $btn['content_type']);
            // FB จำกัด title 20 ตัวอักษร
            $this->assertLessThanOrEqual(20, mb_strlen($btn['title']), "ปุ่มยาวเกิน: {$btn['title']}");
        }
    }

    /**
     * 💎 ปุ่มทางลัดจ่ายเงิน — ต้องไม่ติดไปกับ DM ขาออก
     *
     * 🚨 นี่คือด่านกันถอยหลัง: ถ้าวันหนึ่งมีคนย้ายปุ่มนี้เข้าไปใน builder ที่ DM ใช้
     *    DM เย็น ๆ จะกลายเป็นสแปมขายของอีกครั้ง (เจ้าของสั่งไว้ว่า DM ส่งแต่คำเชิญฟรี)
     *
     * @test
     */
    public function ปุ่มจ่ายเงินต้องอยู่เฉพาะในแชทไม่ติดไปกับdm(): void
    {
        $upgrade = FortuneConversationService::dailyUpgradeQuickReply();

        // ไม่ใส่ตัวเลขราคา — ราคาแอดมินแก้ได้ ให้ tier menu เป็นคนบอก
        $this->assertDoesNotMatchRegularExpression('/\d/u', $upgrade['title']);
        $this->assertLessThanOrEqual(20, mb_strlen($upgrade['title']));
        // 🔧 (2026-08-10) แก้เทสต์ค้าง — payload เปลี่ยนเป็น DAILY_VIP_PACKAGES ตั้งแต่ 2026-08-07
        //    (เดิมอาศัย default branch ด้วยข้อความดิบ 'ดูดวง' แต่ถ้า FB ส่ง title กลับมาเป็น
        //     ข้อความ คำว่า "ค่าครู" จะเข้า looksLikePricingQuestion = ได้กล่องราคาแทนบิล
        //     ดู rule_fb_quickreply_label_arrives_as_text) — เทสต์ไม่ได้ตามไปแก้ = ค้างแดงมาตั้งแต่นั้น
        $this->assertSame('DAILY_VIP_PACKAGES', $upgrade['payload'],
            'payload ต้องเป็นตัวเฉพาะที่มี case ใน handleQuickReply ไม่ใช่ข้อความดิบ');

        // 🚨 ปุ่มต้องไม่ตาย — payload ต้องมีคนรับจริงใน FacebookWebhookController::handleQuickReply
        $controller = file_get_contents(app_path('Http/Controllers/FacebookWebhookController.php'));
        $this->assertStringContainsString("'{$upgrade['payload']}' =>", (string) $controller,
            'payload ไม่มี case รองรับ = ลูกค้ากดปุ่มแล้วไม่มีอะไรเกิดขึ้น');

        // ❌ builder ที่ DM ใช้ ต้องไม่มีปุ่มจ่ายเงินปนอยู่
        foreach ([
            'dailyBirthdayQuickReplies' => FortuneConversationService::dailyBirthdayQuickReplies(),
            'dailyShowMineQuickReplies' => FortuneConversationService::dailyShowMineQuickReplies(),
        ] as $name => $buttons) {
            $payloads = array_column($buttons, 'payload');
            $this->assertNotContains($upgrade['payload'], $payloads, "{$name} ต้องไม่มีปุ่มจ่ายเงิน (DM ใช้ตัวนี้)");
        }

        // ✅ ต่อท้ายได้เฉพาะตอนเรียก withDailyUpgrade เท่านั้น
        $m = new ReflectionMethod(FortuneConversationService::class, 'withDailyUpgrade');
        $m->setAccessible(true);
        $withUpgrade = $m->invoke(null, FortuneConversationService::dailyBirthdayQuickReplies());

        // 8 วันเกิด + ปุ่มอัปเกรด = 9 (ยังอยู่ใต้เพดาน 13 ปุ่มของทั้ง FB และ LINE)
        $this->assertCount(9, $withUpgrade);
        $this->assertSame($upgrade['payload'], end($withUpgrade)['payload']);
    }

    /**
     * 🎁 (2026-08-01) ด่านขาเข้าต้องเปิดในโหมด classic ด้วย
     *
     * เพราะปุ่มรับดวงประจำวันเกิดถูกยื่นให้ตอนลูกค้าขอดูฟรีแต่สิทธิ์หมด — ซึ่งเกิดได้
     * ทุกโหมด ถ้าด่านนี้ยังบังคับ daily อยู่ ปุ่มในโหมด classic จะกดแล้วเงียบ
     *
     * @test
     */
    public function ด่านตอบวันเกิดต้องเปิดทุกโหมดยกเว้นtransfer(): void
    {
        $expect = [
            FortuneBotMode::MODE_CLASSIC => true,
            FortuneBotMode::MODE_DAILY => true,
            FortuneBotMode::MODE_TRANSFER => false,
        ];

        foreach ($expect as $mode => $allowed) {
            $botMode = new FortuneBotMode(new FortuneTellingSetting(['fortune_bot_mode' => $mode]));

            $this->assertSame(
                $allowed,
                $botMode->dailyReplyAllowedFor('facebook', 'PSID_1'),
                "โหมด {$mode} บน facebook"
            );

            // 🌙 (2026-08-21) LINE ต้องได้เหมือน FB ทุกโหมด — เลนดวงฟรีรายวันเปิดฝั่ง LINE แล้ว
            //   เดิมด่านนี้ผูกกับ INTERCEPT_PLATFORM ('facebook') ⇒ ลูกค้า LINE พิมพ์
            //   "อยากดูดวงรายวัน" / "ผมเกิดวันจันทร์" แล้วตกด่านแรกทันที = เลนตายสนิท
            $this->assertSame(
                $allowed,
                $botMode->dailyReplyAllowedFor('line', 'U_1'),
                "โหมด {$mode} บน line"
            );

            // ไม่มี user id = ไม่มีใครให้ตอบ
            foreach (['facebook', 'line'] as $platform) {
                $this->assertFalse(
                    $botMode->dailyReplyAllowedFor($platform, ''),
                    "โหมด {$mode} บน {$platform} user id ว่าง ต้องไม่ผ่าน"
                );
            }

            // ❌ ช่องทางที่ไม่รู้จัก ต้องไม่หลุด (fail-closed)
            $this->assertFalse(
                $botMode->dailyReplyAllowedFor('instagram', 'IG_1'),
                "โหมด {$mode} บนช่องทางที่ไม่รู้จัก ต้องไม่ผ่าน"
            );
        }
    }

    /**
     * 🚨 (2026-08-21) ด่านทั้ง 2 ตัวของเลนดวงรายวันต้องเปิดช่องทาง**พร้อมกัน**เสมอ
     *
     * ถ้าเปิดแค่ dailyReplyAllowedFor: คนที่เคยได้ดวงรายวันแล้วพิมพ์วันเกิดเต็ม จะได้กล่อง
     * ชวนดูเชิงลึก แต่คนที่**ยังไม่เคยได้** จะโดน buildDailyReadingForDetectedBirthdate
     * ตีตกแล้วไหลไปกล่อง "ค่าครู 39 บาท" ⇒ คนที่ควรได้ของฟรีที่สุดคือคนเดียวที่เจอใบเสนอราคา
     *
     * @test
     */
    public function ด่านเลนดวงรายวันต้องอ้างลิสต์ช่องทางตัวเดียวกัน(): void
    {
        $this->assertSame(
            ['facebook', 'line'],
            FortuneBotMode::DAILY_PLATFORMS,
            'เปลี่ยนลิสต์ช่องทางแล้วต้องไล่แก้ครบ 6 จุด (ดู docblock ของ looksLikeDailyIntent)'
        );

        // buildDailyReadingForDetectedBirthdate ต้องไม่กลับไปใช้ INTERCEPT_PLATFORM อีก
        $trait = (string) file_get_contents(app_path('Services/Fortune/DailyHoroscopeModeTrait.php'));

        $this->assertStringNotContainsString(
            'FortuneBotMode::INTERCEPT_PLATFORM',
            $trait,
            'เลนดวงรายวันต้องใช้ DAILY_PLATFORMS — INTERCEPT_PLATFORM เป็นของโหมด transfer'
        );

        // 🧹 ปุ่มเลนดวงรายวันต้องรอด stripFortuneStartQuickReplies ของ LINE
        //    (ป้าย "🔮 ดูดวงวันนี้เลย" มีคำว่า "ดูดวง" + ไม่มีตัวเลข = เข้าเงื่อนไขลบเป๊ะ ๆ)
        $manager = (string) file_get_contents(app_path('Services/FortuneChannelManager.php'));

        $this->assertStringContainsString(
            "str_starts_with(\$payload, 'DAILY_')",
            $manager,
            'ไม่มี whitelist DAILY_ = ปุ่มดูดวงวันนี้ถูกลบทิ้งเงียบ ๆ ฝั่ง LINE'
        );
    }

    /**
     * 🔘 (2026-08-21) ปุ่มเลนดวงรายวันต้องมีคนรับจริงทั้ง 2 ช่องทาง
     *
     * LINE quick reply เป็น type=message ⇒ ป้ายปุ่มไหลกลับมาเป็นข้อความ ไม่มี payload
     * ถ้า controller ฝั่ง LINE ไม่มีตัวแปลงป้าย→payload ปุ่ม VIP จะโดน
     * looksLikePricingQuestion ตอบเป็นกล่องราคาแทนเมนูแพคเกจ = ปุ่มพาไปผิดที่
     *
     * @test
     */
    public function ปุ่มเลนดวงรายวันต้องมีคนรับทั้งfbและline(): void
    {
        $payloads = array_merge(
            [FortuneConversationService::dailyFreeStartQuickReply()['payload']],
            array_column(FortuneConversationService::dailyShowMineQuickReplies(), 'payload'),
            [FortuneConversationService::dailyUpgradeQuickReply()['payload']],
        );

        $fb = (string) file_get_contents(app_path('Http/Controllers/FacebookWebhookController.php'));
        $line = (string) file_get_contents(app_path('Http/Controllers/LineFortuneWebhookController.php'));

        foreach ($payloads as $payload) {
            $this->assertStringContainsString("'{$payload}' =>", $fb,
                "FB ไม่มี case รองรับ {$payload} = กดปุ่มแล้วไม่มีอะไรเกิดขึ้น");
        }

        // ฝั่ง LINE ใช้ตัว resolver กลาง (อ่าน payload จากนิยามปุ่มตัวจริง) แทน case ทีละใบ
        foreach ([
            'resolveDailyButtonPayload',
            'resolveDailyButtonPayloadFromLabel',
            'handleDailyButtonPayload',
        ] as $method) {
            $this->assertStringContainsString("function {$method}(", $line,
                "LINE ขาด {$method}() = ปุ่มเลนดวงรายวันตายทั้งชุด");
        }

        // ปุ่ม VIP ต้องถูกแปลงเป็น "ดูดวง" ไม่ปล่อยป้าย "ค่าครู" ไหลเป็นข้อความ
        $this->assertStringContainsString("'DAILY_VIP_PACKAGES'", $line,
            'LINE ไม่ได้ดักปุ่ม VIP = ลูกค้าได้กล่องราคาแทนเมนูแพคเกจ');
    }

    /**
     * 🐛 (2026-08-21) ปุ่มวันเกิดฝั่ง LINE ต้องส่ง "วัน{ชื่อวัน}" ไม่ใช่ป้ายปุ่ม
     *
     * LINE quick reply เป็น type=message ⇒ ป้ายปุ่มคือข้อความที่ถูกส่งกลับมา
     * ป้ายวันอาทิตย์คือ "☀️ อาทิตย์" ซึ่งมี **U+FE0F (Variation Selector-16)** ติดมาด้วย
     * และ VS16 มี Unicode category **Mn (Mark)** ⇒ ตัวปอกของ resolveBirthDayNameIndex()
     * (`[^\p{L}\p{N}\p{M}\s]` → space) เก็บมันไว้เพราะมันคือ \p{M}
     * ⇒ ได้ "️ อาทิตย์" เทียบ DAILY_DAY_ALIASES ไม่ติด → คืน null
     *
     * บั๊กนี้โผล่ **วันเดียว** (อีโมจิของวันอื่นไม่มี VS16) = หายากมากถ้าไม่ล็อกไว้
     *
     * @test
     */
    public function ปุ่มวันเกิดฝั่งlineต้องส่งชื่อวันที่parserอ่านออก(): void
    {
        // 🌙 index 7 = พุธกลางคืน (ราหู) — ป้ายที่ส่งกลับคือ "วันพุธกลางคืน"
        //    ซึ่ง parser ต้องอ่านเป็น 7 ไม่ใช่ 3 (คนละดาวเจ้าเรือน)
        $dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'พุธกลางคืน'];

        $converted = FortuneConversationService::dailyQuickRepliesForLine(
            FortuneConversationService::dailyBirthdayQuickReplies()
        );

        $this->assertCount(8, $converted);

        foreach ($converted as $index => $btn) {
            $this->assertSame('วัน'.$dayNames[$index], $btn['text'],
                "ปุ่มวัน{$dayNames[$index]} ต้องส่งชื่อวันที่ประกอบจาก payload ไม่ใช่ป้ายปุ่ม");

            // LINE จำกัดป้าย 20 ตัวอักษร
            $this->assertLessThanOrEqual(20, mb_strlen($btn['label']));

            // ตัวจับต้องอ่าน index ได้จริง — นี่คือหัวใจของเทสต์นี้
            $this->assertSame($index, $this->invokeMethod('resolveBirthDayNameIndex', $btn['text']),
                "parser อ่าน \"{$btn['text']}\" ไม่ออก = ลูกค้ากดปุ่มแล้วบอทไม่รู้จักวันเกิด");
        }

        // 🚨 ด่านกันถอยหลัง: ยืนยันว่า "ป้ายดิบ" ของวันอาทิตย์อ่านไม่ออกจริง
        //    ถ้าวันหนึ่งมีคนเปลี่ยนกลับไปส่งป้ายเป็น text เทสต์ข้างบนจะจับได้ทันที
        $sundayLabel = FortuneConversationService::dailyBirthdayQuickReplies()[0]['title'];

        $this->assertNull($this->invokeMethod('resolveBirthDayNameIndex', $sundayLabel),
            'ถ้าอันนี้อ่านออกแล้ว แปลว่าตัวปอกถูกแก้ — ทบทวน dailyQuickRepliesForLine() ได้');

        // ปุ่มอื่น: ปอกอีโมจิออกจากป้าย + พก payload ไปให้ strip ใช้เป็น whitelist
        $others = FortuneConversationService::dailyQuickRepliesForLine([
            FortuneConversationService::dailyFreeStartQuickReply(),
            FortuneConversationService::dailyShowMineQuickReplies()[0],
            FortuneConversationService::dailyUpgradeQuickReply(),
        ]);

        $this->assertSame(['รับดวงฟรีประจำวัน', 'ดูดวงวันนี้เลย', 'ดูvipส่วนตัวมีค่าครู'],
            array_column($others, 'text'));
        $this->assertSame(['DAILY_FREE_START', 'DAILY_SHOW_MINE', 'DAILY_VIP_PACKAGES'],
            array_column($others, 'payload'));

        // ขยะต้องไม่ระเบิด + LINE รับสูงสุด 13 ปุ่ม
        $this->assertSame([], FortuneConversationService::dailyQuickRepliesForLine([]));
        $this->assertSame([], FortuneConversationService::dailyQuickRepliesForLine(['ไม่ใช่ array', ['title' => '  ']]));
        $this->assertCount(13, FortuneConversationService::dailyQuickRepliesForLine(
            array_fill(0, 20, ['title' => 'ปุ่ม', 'payload' => 'X'])
        ));

        // ป้ายอีโมจิล้วน → ปอกแล้วว่าง ต้อง fallback เป็นป้ายเดิม (LINE ห้าม text ว่าง)
        $emojiOnly = FortuneConversationService::dailyQuickRepliesForLine([['title' => '🔮✨', 'payload' => 'DAILY_Y']]);
        $this->assertNotSame('', $emojiOnly[0]['text'] ?? '');
    }

    /**
     * 🚫 (2026-08-21) ชื่อวันเกิดต้องอยู่ใน whitelist สแปมฝั่ง LINE
     *
     * กฎ fallback ของ isUserSpamming() คือ `mb_strlen <= 4` ซึ่งไม่ครอบชื่อวันไทยเลย
     * ("จันทร์" 6 · "อาทิตย์" 7) และด่าน bypass ตาม status ก็ช่วยไม่ได้ เพราะเลนดวงรายวัน
     * คืน `'reading' => null` — ไม่มีแถว reading ให้ whereIn() เจอ
     * ⇒ ลูกค้าพิมพ์ชื่อวันเกิดซ้ำ 5 ครั้งเพราะบอทตอบช้า = โดนปิดปาก 1 ชั่วโมง
     *
     * @test
     */
    public function ชื่อวันเกิดต้องรอดด่านสแปมฝั่งline(): void
    {
        $line = (string) file_get_contents(app_path('Http/Controllers/LineFortuneWebhookController.php'));

        // ตัดเอาเฉพาะบล็อก $stateExpectedInputs — ไม่งั้นไปเจอชื่อวันในเมธอดอื่นแล้วได้ผลลวง
        $this->assertSame(
            1,
            preg_match('/\$stateExpectedInputs\s*=\s*\[(.*?)\];/s', $line, $m),
            'หา $stateExpectedInputs ไม่เจอ — โครงด่านสแปมฝั่ง LINE เปลี่ยนไปแล้ว'
        );

        $whitelist = $m[1];

        foreach (['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'] as $day) {
            $this->assertStringContainsString("'{$day}'", $whitelist,
                "ชื่อวัน \"{$day}\" ไม่อยู่ใน whitelist = ลูกค้าพิมพ์ซ้ำ 5 ครั้งแล้วโดนปิดปาก 1 ชม.");
        }

        foreach (['ดวงฟรี', 'ดูดวงฟรี', 'ดวงฟรีประจำวัน', 'รับดวงฟรีประจำวัน', 'ดวงประจำวัน', 'ขอดวงวันนี้'] as $kw) {
            $this->assertStringContainsString("'{$kw}'", $whitelist,
                "คำขอดวงฟรี \"{$kw}\" ไม่อยู่ใน whitelist");
        }
    }

    /**
     * 👍 "เอาค่ะ" = เท่ากับกดปุ่มดูดวงวันนี้ · "ไม่เอา" ต้องไม่ใช่
     *
     * เทียบตรงตัวเท่านั้น — ถ้าเผลอเปลี่ยนเป็น substring วันหนึ่ง "ไม่เอาค่ะ" จะกลายเป็น
     * การตอบรับ แล้วบอทจะยัดคำทำนายใส่คนที่เพิ่งปฏิเสธ
     *
     * @test
     */
    public function จับคำตอบรับสั้นได้และไม่กินคำปฏิเสธ(): void
    {
        // ✅ ตอบรับล้วน
        foreach ([
            'เอา', 'เอาค่ะ', 'เอาเลยค่ะ', 'ดูเลย', 'ดูเลยครับ', 'อยากดู', 'ขอดูหน่อย',
            'ตกลง', 'ได้เลยค่ะ', 'โอเค', 'ใช่ค่ะ', 'สนใจ', 'ส่งมาเลย', 'ok', 'yes',
        ] as $t) {
            $this->assertTrue($this->invokeMethod('looksLikeShortYes', $t), "ควรจับ: {$t}");
        }

        // ❌ ห้ามจับ — ปฏิเสธ / คำลงท้ายเปล่า ๆ / มีเนื้อหาอื่น
        foreach ([
            'ไม่เอา', 'ไม่เอาค่ะ', 'ไม่ดู', 'ไม่สนใจ', 'ยังไม่เอา',
            'ค่ะ', 'ครับ', 'จ้า', '',
            'เอาไว้ก่อนนะคะ', 'ขอดูราคาก่อน', 'อยากดูดวงความรัก',
        ] as $t) {
            $this->assertFalse($this->invokeMethod('looksLikeShortYes', $t), "ไม่ควรจับ: {$t}");
        }
    }

    /**
     * คำชวนบอกวันเกิดรับดวงฟรี — ต้องไม่มีตัวเลขราคาหลุดเข้าไป
     *
     * ลูกค้าเพิ่งขอของฟรี การเด้งราคาใส่คือสิ่งที่ฟีเจอร์นี้แก้อยู่พอดี
     *
     * @test
     */
    public function คำชวนรับดวงฟรีต้องไม่มีราคา(): void
    {
        foreach (['PSID_A', 'PSID_B', 'PSID_C', 'PSID_D', 'PSID_E'] as $uid) {
            $line = $this->invokeMethod('pickDailyFreeOffer', $uid);

            $this->assertIsString($line);
            $this->assertNotSame('', trim($line));
            $this->assertStringContainsString('ฟรี', $line, "ต้องบอกว่าฟรี: {$line}");
            $this->assertDoesNotMatchRegularExpression('/\d/u', $line, "มีตัวเลข (ราคา?) หลุดมา: {$line}");
        }
    }

    /**
     * 🚧 (2026-08-17) "ยืนอ่านเมนูอยู่" ต้องไม่ถูกนับเป็นบิล — ไม่งั้นดวงฟรีถูกตัดสิทธิ์
     *
     * เคสจริง 2026-08-16 22:47 (user 26895114853414011): กดปุ่ม [🎁 รับดวงฟรีประจำวัน]
     * แต่มี reading status=tier_choice ค้างอยู่ (bill_number/amount = null ทั้งคู่)
     * → ด่านดวงฟรีตีตก → ชื่อวันไหลไป handleTierChoice → ตอบเมนูแพคเกจ 39/99
     * = ขอของฟรี ได้ใบเสนอราคากลับไป
     *
     * เทสต์นี้ล็อกลิสต์สถานะที่ dailyBlockingReadingExists() ใช้ ไม่ให้ใครเผลอ
     * เอาสถานะ "ก่อนจ่ายเงิน" กลับเข้ามาอีก
     *
     * @test
     */
    public function สถานะก่อนจ่ายเงินต้องไม่บล็อกดวงฟรี(): void
    {
        // ลิสต์เดียวกับที่ DailyHoroscopeModeTrait::dailyBlockingReadingExists() ประกอบ
        $blocking = array_values(array_unique(array_merge(
            \App\Models\FortuneReading::PENDING_PAYMENT_STATUSES,
            \App\Models\FortuneReading::DEEP_ACTIVE_STATUSES,
            \App\Models\FortuneReading::CELTIC_ACTIVE_STATUSES,
            \App\Models\FortuneReading::IN_PREDICTION_STATUSES,
        )));

        // ❌ ยังไม่มีบิล ไม่มียอดเงิน = แค่เปิดเมนูค้างไว้ → ห้ามบล็อก
        foreach ([
            \App\Models\FortuneReading::STATUS_TIER_CHOICE,
            \App\Models\FortuneReading::STATUS_AWAITING_CONFIRMATION,
            \App\Models\FortuneReading::STATUS_DISCOVERY_CHAT,
            \App\Models\FortuneReading::STATUS_DISCOVERY_CONFIRM,
        ] as $status) {
            $this->assertNotContains($status, $blocking, "สถานะก่อนจ่ายเงินหลุดเข้าลิสต์บล็อก: {$status}");
        }

        // ✅ มีเงินเกี่ยว / กำลังทำนาย → ต้องบล็อกเสมอ
        //    (feedback_never_interrupt_payment_to_prediction_flow)
        foreach ([
            \App\Models\FortuneReading::STATUS_PENDING_PAYMENT,
            \App\Models\FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
            \App\Models\FortuneReading::STATUS_COLLECTING_BIRTHDATE,
            \App\Models\FortuneReading::STATUS_COLLECTING_QUESTIONS,
            \App\Models\FortuneReading::STATUS_COLLECTING_TAROT,
            \App\Models\FortuneReading::STATUS_PAID,
            \App\Models\FortuneReading::STATUS_CELTIC_PICKING,
            \App\Models\FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            \App\Models\FortuneReading::STATUS_CELTIC_GENERATING,
            \App\Models\FortuneReading::STATUS_CELTIC_QA_PROMPT,
        ] as $status) {
            $this->assertContains($status, $blocking, "สถานะที่ต้องบล็อกหายไป: {$status}");
        }

        // เอกสารประกอบ: ตัวเดิม (hasActiveReading) กว้างกว่าจริง — นี่คือเหตุผลที่ต้องแยกลิสต์
        $this->assertContains(
            \App\Models\FortuneReading::STATUS_TIER_CHOICE,
            \App\Models\FortuneReading::ACTIVE_READING_STATUSES,
            'ถ้าวันหนึ่ง tier_choice ถูกถอดออกจาก ACTIVE_READING_STATUSES ให้ทบทวนคอมเมนต์ใน dailyBlockingReadingExists()'
        );
    }

    // ════════════════════════════════════════════════════════════
    // 🎁 (2026-08-28) สวิตช์ "ระบบชวนรับดวงรายวันฟรี"
    // ════════════════════════════════════════════════════════════

    /**
     * ค่าที่อ่านไม่ได้ / ยังไม่ migrate ต้องแปลว่า "เปิด" — ไม่ใช่ปิดฟีเจอร์ที่วิ่งอยู่เงียบ ๆ
     *
     * ระหว่าง deploy ไฟล์โค้ดขึ้นก่อน migrate เสมอ ถ้าตีค่า null เป็นปิด
     * DM ทั้งระบบจะเปลี่ยนพฤติกรรมเองในช่วงนั้นโดยไม่มีใครสั่ง
     *
     * @test
     */
    public function สวิตช์ดวงฟรีไม่มีคอลัมน์ต้องอ่านเป็นเปิด(): void
    {
        $expect = [
            'ยังไม่ migrate (ไม่มีคีย์เลย)' => [[], true],
            'เปิดชัดเจน' => [['daily_free_horoscope_enabled' => true], true],
            'ปิดชัดเจน' => [['daily_free_horoscope_enabled' => false], false],
            'ค่าจาก DB เป็น 1' => [['daily_free_horoscope_enabled' => 1], true],
            'ค่าจาก DB เป็น 0' => [['daily_free_horoscope_enabled' => 0], false],
        ];

        foreach ($expect as $label => [$attrs, $enabled]) {
            $settings = new FortuneTellingSetting($attrs);

            $this->assertSame(
                $enabled,
                $settings->isDailyFreeHoroscopeEnabled(),
                "โมเดล: {$label}"
            );

            $this->assertSame(
                $enabled,
                (new FortuneBotMode($settings))->dailyFreeOutboundEnabled(),
                "FortuneBotMode: {$label}"
            );
        }
    }

    /**
     * 🚨 ปิดสวิตช์ = ปิด **ฝั่งชวน** เท่านั้น ห้ามแตะด่านขาเข้า
     *
     * เจ้าของสั่ง 2026-08-28: "แต่ยังพิมพ์ ดูดวงฟรี ก็จะพาไปรับดวงฟรี การ์ด 7 ใบ ได้"
     *
     * ถ้าเผลอเอาสวิตช์ไปแปะ dailyReplyAllowedFor() ผลคือ:
     *   - คนพิมพ์ "ดูดวงฟรี" ไหลไป startDeepReadingFlow() = **เมนูราคา**
     *     (rule_free_request_never_hits_paywall)
     *   - ปุ่ม 🎁 / การ์ด 7 วัน ที่ยิงออกไปแล้วในแชทเก่า กดแล้วเงียบ = ปุ่มตาย
     *
     * @test
     */
    public function ปิดสวิตช์ดวงฟรีต้องไม่ปิดด่านขาเข้า(): void
    {
        $off = new FortuneBotMode(new FortuneTellingSetting([
            'fortune_bot_mode' => FortuneBotMode::MODE_DAILY,
            'daily_free_horoscope_enabled' => false,
        ]));

        // ขาเข้า — ต้องยังเปิดครบทุกช่องทางเหมือนเดิม
        foreach (FortuneBotMode::DAILY_PLATFORMS as $platform) {
            $this->assertTrue(
                $off->dailyReplyAllowedFor($platform, 'UID_1'),
                "ปิดสวิตช์แล้วด่านขาเข้าบน {$platform} ต้องยังเปิด (ลูกค้าขอเองต้องได้ของ)"
            );
        }

        // ⚠️ ยืนยันว่าอ่านสวิตช์ได้จริง — dailyFreeOutboundEnabled() เป็น fail-open
        //    ถ้าเมธอดในโมเดลหาย มันจะคืน true เงียบ ๆ แล้ว assertFalse ข้างล่างจะผ่าน
        //    ด้วยเหตุผลผิด ๆ (dailyArticlesReadyToday คืน false เพราะไม่มีบทความในเทสต์)
        $this->assertFalse(
            $off->dailyFreeOutboundEnabled(),
            'อ่านสวิตช์ไม่ได้ (fail-open) — เช็คว่า FortuneTellingSetting::isDailyFreeHoroscopeEnabled() ยังอยู่'
        );

        // ขาออก — ต้องเงียบ (ตัวนี้คือสิ่งที่สวิตช์ปิด)
        //   ⚠️ ไม่แตะ DB เลย: ด่านสวิตช์อยู่ **ก่อน** dailyArticlesReadyToday()
        $this->assertFalse(
            $off->isDailyServing(),
            'ปิดสวิตช์แล้ว DM ขาออกต้องกลับไปใช้ชุดข้อความชวนดูดวงชุดแรก'
        );

        // โหมดยังเป็น daily อยู่ — สวิตช์ไม่ควรไปแก้ค่าโหมดที่แอดมินตั้งไว้
        $this->assertTrue($off->isDaily(), 'สวิตช์ต้องไม่ไปเปลี่ยนโหมดบอทที่แอดมินตั้งไว้');
    }

    /**
     * 🔒 ล็อกไว้ในระดับซอร์ส — ด่านขาเข้าห้ามอ้างสวิตช์นี้เด็ดขาด
     *
     * เทสต์ข้างบนจับได้เฉพาะตอนที่พฤติกรรมพังจริง แต่เคสนี้คือ "แก้ผิดที่แล้วดูสมเหตุสมผล"
     * ซึ่งเป็นแบบที่หลุด review ได้ง่ายที่สุด — ล็อกที่ตัวโค้ดไปเลย
     *
     * @test
     */
    public function ด่านขาเข้าห้ามอ้างสวิตช์ดวงฟรี(): void
    {
        $src = (string) file_get_contents(app_path('Services/Fortune/FortuneBotMode.php'));

        // ตัดเฉพาะตัว dailyReplyAllowedFor() ออกมาดู (จบที่เมธอดถัดไป)
        $start = strpos($src, 'public function dailyReplyAllowedFor');
        $this->assertNotFalse($start, 'หา dailyReplyAllowedFor() ไม่เจอ — เปลี่ยนชื่อแล้วต้องแก้เทสต์ตาม');

        $end = strpos($src, 'public function ', $start + 20);
        $body = substr($src, $start, $end !== false ? $end - $start : null);

        $this->assertStringNotContainsString(
            'dailyFreeOutboundEnabled',
            $body,
            'ด่านขาเข้าต้องไม่ขึ้นกับสวิตช์ชวนดวงฟรี — ลูกค้าที่พิมพ์ขอเองต้องได้ของเสมอ'
        );

        $this->assertStringNotContainsString(
            'daily_free_horoscope_enabled',
            $body,
            'ด่านขาเข้าต้องไม่อ่านคอลัมน์สวิตช์โดยตรง'
        );
    }
}
