<?php

namespace Tests\Unit\Services;

use App\Jobs\SendBillReminderJob;
use App\Models\FortuneReading;
use App\Services\FortuneConversationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 👂 (2026-09-15) ระหว่างรอโอน บอทต้อง "ฟังลูกค้า" ไม่ใช่ส่งกล่องทวงเงินซ้ำทุกข้อความ
 *
 * เคสจริง FTU-260915-D0350 (FB Max Hawley) — 6 ข้อความหลังได้ QR ได้กล่องเดิม
 * "💸 รอเจ้าชะตาโอนค่าครู 99.54 บาทตาม QR ที่ส่งให้นะคะ" ทั้ง 6 ครั้ง (Gemini ล่ม + ข้อความสั้นไม่ถึง AI)
 *
 * ข้อความทดสอบทุกตัวในชุด "จริง" ดึงจาก text_preview ใน log prod 13-15 ก.ย. (ถูกตัดที่ 30 ตัวอักษร —
 * คำพิมพ์ผิดคงไว้ตามจริง "ปัณชี" / "แอบ" / "พรุ้งนี้" / "ยกเลือก")
 *
 * ไม่ใช้ DB — ตัวจำแนก/ตัวตัดสินใจเป็นฟังก์ชันล้วน · ด้าน AI/บันทึกประวัติ/แจ้งแอดมิน ใช้ตัวแทน (double)
 */
class FortunePendingPaymentListenerTest extends TestCase
{
    protected PendingListenerDouble $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = (new ReflectionClass(PendingListenerDouble::class))->newInstanceWithoutConstructor();
        $this->service->resetDouble();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function reading(): FortuneReading
    {
        $reading = new FortuneReading;
        $reading->forceFill([
            'id' => 13253,
            'facebook_user_id' => '28189749290647980',
            'facebook_user_name' => 'Max Hawley',
            'platform' => 'facebook',
            'bill_reference' => 'FTU-260915-D0350',
            'reading_type' => 'celtic_cross',
        ]);

        return $reading;
    }

    private function ctx(string $action = 'celtic_awaiting_payment', string $partial = ''): array
    {
        return [
            'action' => $action,
            'pay_amount' => '99.54',
            'expires_at' => Carbon::parse('2026-09-16 02:12:39', 'Asia/Bangkok'),
            'remaining_minutes' => 177,
            'partial_line' => $partial,
            'footer' => "💸 *ค่าครู: 99.54 บาท* (ทศนิยมต้องตรง)\n📌 พิมพ์ \"เช็คสถานะ\" เมื่อโอนแล้ว · \"ยกเลิก\" เพื่อไม่ทำต่อ",
        ];
    }

    private function respond(FortuneReading $reading, string $text, array $ctx): array
    {
        $m = new ReflectionMethod($this->service, 'respondWhilePendingPayment');
        $m->setAccessible(true);

        return $m->invoke($this->service, $reading, $text, $ctx);
    }

    /**
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function realMessages(): array
    {
        return [
            // FTU-260915-D0350 ครบทั้ง 6 ข้อความ
            'D0350 #1 มีปัญหา (พิมพ์ผิด)' => ['มีป๊ณหานิดหนึ่งคั', 'problem'],
            'D0350 #2 ไม่มีบัญชี (พิมพ์ผิด)' => ['คือไม่มีปัณชี', 'no_bank'],
            'D0350 #3 กำกวม → ให้ AI' => ['โอรนเงินที่ไทยคะ', null],
            'D0350 #4 สักพัก' => ['สักพัก', 'wait'],
            'D0350 #5 นะะ' => ['นะะ', 'ack'],
            'D0350 #6 ให้ญาติโอน (พิมพ์ผิดหนัก) → ให้ AI' => ['จะลองให้ญืติฌอนให้ระคะ', null],
            // 2026-09-13/14/15 — ได้กล่องเดิมกลับไปทั้งหมด
            'ค่ะแม่' => ['ค่ะแม่', 'ack'],
            'รอแป๊บนึง' => ['รอแป๊บนึงค่ะ', 'wait'],
            'สวัดีคะ (พิมพ์ผิด)' => ['สวัดีคะ', 'greeting'],
            'ขอโทษนะคะ' => ['ขอโทษนะคะ', 'apology'],
            'ไม่มีแอบ (แอป พิมพ์ผิด)' => ['ไม่มีแอบ', 'no_bank'],
            'ต้องดูพรุ่งนี้' => ['ขอโืษนะค่ะต้องดูพรุ่งนี้,แล้วค', 'later'],
            'ขอบคุณค่' => ['ขอบคุณค่', 'thanks'],
            'ไม่ยกเลือก (พิมพ์ผิด)' => ['ไม่ยกเลือกค่ะ', 'keep_bill'],
            'ไช่ค่ะ' => ['ไช่ค่ะ', 'ack'],
            'รู้และเข้าใจ' => ['รู้และเข้าใจค่ะ', 'ack'],
            'ขอนอนก่อนพรุ้งนี้โอน' => ['งั้นขอนอนก่อนพรุ้งนี้โอนจะได้ไ', 'later'],
            'ให้เขาโอนพรุ้งนี้ (พรุ่งนี้ชนะ)' => ['แดงต้องไปให้เขาโอนพรุ้งนี้พรุ้', 'later'],
            'ไม่ทำพร้อมเฟีย' => ['แดงไม่ทำพร้อมเฟียค่ะ', 'no_bank'],
            'ก็บอกพรุ้งนี้' => ['ก็บอกพรุ้งนี้ค่ะ', 'later'],
            'รอพรุ้งนี้' => ['ยังงั้นรอพรุ้งนี้นะถ้าไว้ใจก็ว', 'later'],
            'ยังไม่ต้องเปิดวันนี้' => ['ยังไม่ต้องเปิดวันนี้คืนนี้', 'later'],
            'เดี๋ยวนี้เดือดร้อน (ไม่ใช่ขอเวลา)' => ['เดี๋ยวนี้เดือดร้อนจะโอนเงินยัง', 'no_money'],
            'โอนเงินแปบ' => ['โอนเงินแปบค่ะ', 'wait'],
            'กำลังถามร้านค้า' => ['กำลังถามร้านค้าอยู่จ้า', 'wait'],
            // จริงๆ คือ "ติดขัดเรื่องเงิน" ที่พิมพ์ผิดทั้งสองคำ — ระบุ "เรื่อง…" ที่ไม่ใช่เรื่องโอน → ให้ AI ตีความ
            'ติดขะดเรื่องดงิน (พิมพ์ผิดหนัก) → ให้ AI' => ['ลูกติดขะดเรื่องดงินค่ะ', null],
            'ยืมเพื่อน' => ['วาจะยืมเพื่อนสัก300วันนี้ไม่รู', 'no_money'],
            'นึกได้ → ให้ AI' => ['นึกได้', null],
            'ถามราคา → ให้ AI' => ['ทั้งหมดกันเท่าไหร่คะแม่', null],
            'มีบัญชีออมสิน → ให้ AI' => ['มีบัญชีออมสิน', null],
        ];
    }

    /**
     * @dataProvider realMessages
     */
    public function test_real_messages_are_classified(string $text, ?string $expected): void
    {
        $this->assertSame($expected, $this->service->classifyPendingPaymentListening($text), $text);
    }

    /**
     * สะกดถูก + ประโยคกับดัก (คำเดียวกันแต่ความหมายตรงข้าม / เป็นคำถามดูดวง)
     *
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function edgeMessages(): array
    {
        return [
            'ให้ญาติโอน (สะกดถูก)' => ['จะลองให้ญาติโอนให้นะคะ', 'third_party'],
            'ให้แฟนจ่าย' => ['ให้แฟนจ่ายให้ได้ไหมคะ', 'third_party'],
            'ฝากโอน' => ['ฝากโอนให้ได้ไหม', 'third_party'],
            'ไม่มีบัญชีธนาคาร' => ['ไม่มีบัญชีธนาคารค่ะ', 'no_bank'],
            'ไม่มีพร้อมเพย์' => ['ไม่มีพร้อมเพย์ค่ะ', 'no_bank'],
            'พรุ่งนี้โอนได้ไหม = ถามเรื่องจ่ายจริง' => ['พรุ่งนี้โอนได้ไหมคะ', 'later'],
            'มีปัญหา นำด้วยสวัสดี → ปัญหาชนะ' => ['สวัสดีค่ะ มีปัญหาเรื่องโอนค่ะ', 'problem'],
            'สแกนไม่ได้' => ['สแกนไม่ได้ค่ะ', 'problem'],
            'อย่ายกเลิก' => ['อย่ายกเลิกนะคะ', 'keep_bill'],
            'แจ้งโอนแล้ว (ตกมาถึงตัวฟัง) → ขอสลิป' => ['โอนแล้วค่ะ', 'claim'],
            'แจ้งโอนแล้วแบบหลวม' => ['หนูโอนให้แล้วนะคะ', 'claim'],
            'สติกเกอร์อีโมจิล้วน' => ['👍', 'ack'],
            'โอเค' => ['โอเคค่ะ', 'ack'],
            // ── กับดัก: ต้องไม่ติด ──
            'ไม่มีปัญหา ≠ มีปัญหา' => ['ไม่มีปัญหาค่ะ', null],
            'ไม่มีเงินในบัญชี = เรื่องเงิน ไม่ใช่ไม่มีบัญชี' => ['ไม่มีเงินในบัญชีค่ะ', 'no_money'],
            'คำถามดูดวงเรื่องพรุ่งนี้' => ['พรุ่งนี้จะได้งานไหมคะ', null],
            'พรุ่งนี้ต้องไปสัมภาษณ์จะผ่านไหม' => ['พรุ่งนี้ต้องไปสัมภาษณ์จะผ่านไหม', null],
            'เดี๋ยวนี้ = ตอนนี้' => ['เดี๋ยวนี้ดวงไม่ดีเลย', null],
            'แฟนโอนมา = เงินเข้า ไม่ใช่ให้คนอื่นจ่าย' => ['แฟนโอนมาแล้ว', null],
            'เล่าเรื่องยาว' => ['ลูกอยากถามเรื่องงานว่าจะได้ย้ายไปอยู่สาขาใหม่ไหมคะ เพราะหัวหน้าบอกว่าเดือนหน้าจะมีการปรับตำแหน่ง', null],
            // ── กับดักที่รีวิวจับผีเจอ (2026-09-15) ──
            'พรุ่งนี้ต้องระวังอะไร = ถามดวง' => ['ลูกอยากรู้ว่าพรุ่งนี้ต้องระวังอะไร', null],
            'พรุ่งนี้สัมภาษณ์งาน' => ['พรุ่งนี้ลูกต้องไปสัมภาษณ์งานค่ะ', null],
            'พรุ่งนี้วันเกิด ("ขอ" ในคำว่า "ของ")' => ['พรุ่งนี้เป็นวันเกิดของลูก', null],
            'ให้แม่หมอดูก่อนจ่าย ≠ ให้แม่โอนแทน' => ['ให้แม่หมอดูก่อนจ่ายนะ', null],
            'ให้แม่หมอเช็คยอด ≠ ให้แม่โอนแทน' => ['ให้แม่หมอเช็คว่าโอนเข้ายัง', null],
            'ลูก = ลูกค้าเรียกตัวเอง' => ['จะให้ลูกโอนยังไงคะ', null],
            'มีปัญหาเรื่องแฟน = ถามดวง' => ['มีปัญหาเรื่องแฟนค่ะ', null],
            'ไม่ได้มีปัญหา' => ['ไม่ได้มีปัญหาอะไร', null],
            'มีปัญหาเรื่องโอน' => ['มีปัญหาเรื่องโอนค่ะ', 'problem'],
            'มีปัญหาเรื่องเงิน = ติดขัดเรื่องเงิน' => ['มีปัญหาเรื่องเงินค่ะ', 'no_money'],
            'เครื่องหมายคำถามล้วน = งง ห้ามเงียบ' => ['?', null],
            'ค่ะ? = ถามกลับ' => ['ค่ะ?', null],
        ];
    }

    public function test_screenshot_is_not_money_trouble(): void
    {
        $this->assertNotSame('no_money', $this->service->classifyPendingPaymentListening('ส่งสกรีนช็อตให้แล้วค่ะ'));
    }

    /**
     * @dataProvider edgeMessages
     */
    public function test_edge_messages(string $text, ?string $expected): void
    {
        $this->assertSame($expected, $this->service->classifyPendingPaymentListening($text), $text);
    }

    public function test_not_cancelling_is_not_a_cancel_request(): void
    {
        $m = new ReflectionMethod($this->service, 'isCancelRequest');
        $m->setAccessible(true);

        foreach (['ไม่ยกเลิกค่ะ', 'อย่ายกเลิกนะคะ', 'ยังไม่ยกเลิกนะ', 'ไม่ต้องยกเลิกค่ะ'] as $text) {
            $this->assertFalse($m->invoke($this->service, $text), "ขอให้ไม่ยกเลิก ต้องไม่ถูกอ่านเป็นยกเลิก: {$text}");
        }
        foreach ([
            'ยกเลิก', 'ยกเลิกค่ะ', 'ขอยกเลิกบิลค่ะ', 'cancel',
            // ต่อว่าที่ยังไม่ยกเลิกให้ = สั่งยกเลิก (รีวิวจับผีเจอ)
            'ทำไมยังไม่ยกเลิกให้', 'ทำไมไม่ยกเลิกให้สักที', 'บอกให้ยกเลิกแล้วทำไมยังไม่ยกเลิก',
            'กดยกเลิกแล้วแต่ยังไม่ยกเลิก', 'ไม่ ยกเลิก',
        ] as $text) {
            $this->assertTrue($m->invoke($this->service, $text), "สั่งยกเลิกจริง ต้องยังยกเลิกได้: {$text}");
            $this->assertNotSame('keep_bill', $this->service->classifyPendingPaymentListening($text), "ห้ามตอบว่าเปิดบิลไว้ให้: {$text}");
        }
    }

    public function test_payment_landing_while_ai_thinks_gets_no_reply(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 23:40:00', 'Asia/Bangkok'));
        $this->service->aiReplies = ['ได้เลยค่ะ ค่อยๆ โอนนะคะ'];
        $this->service->stillPending = false; // SMS จับยอดได้ระหว่าง AI คิด

        $result = $this->respond($this->reading(), 'กำลังโอนค่ะ', $this->ctx());

        $this->assertSame('silent_skip', $result['action'], 'ห้ามส่ง "บิลยังรออยู่" ตามหลังข้อความยืนยันการจ่าย');
        $this->assertSame([], $this->service->turns);
    }

    public function test_admin_forwarding_is_capped_per_bill(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 23:40:00', 'Asia/Bangkok'));
        $reading = $this->reading();

        foreach (['นึกได้', 'สาบอน', 'โอรนเงินที่ไทยคะ', 'จะลองให้ญืติฌอนให้ระคะ', 'หือ'] as $i => $text) {
            Carbon::setTestNow(Carbon::parse('2026-09-15 23:40:00', 'Asia/Bangkok')->addSeconds($i * 20));
            $this->respond($reading, $text, $this->ctx());
        }

        $this->assertCount(3, $this->service->adminNotes, 'ส่งต่อแอดมินได้ไม่เกิน 3 ข้อความ / 10 นาที / บิล');
    }

    public function test_expired_bill_says_so_instead_of_take_your_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-16 02:20:00', 'Asia/Bangkok'));
        $ctx = $this->ctx(); // หมดอายุ 02:12:39 — ผ่านมาแล้ว (ตัวเก็บกวาดยังไม่ปิด)

        $this->assertStringNotContainsString('02:12', $this->service->pendingListenReplyText('wait', $ctx));
        $this->assertStringNotContainsString('02:12', $this->service->pendingListenReplyText('later', $ctx));

        $result = $this->respond($this->reading(), 'เดี๋ยวโอนนะคะ', $ctx);
        $this->assertStringContainsString('หมดเวลาชำระแล้ว', $result['message']);
        $this->assertStringNotContainsString('ไม่ต้องรีบ', $result['message']);
        $this->assertSame([], $this->service->aiCalls, 'บิลหมดอายุ ไม่ต้องให้ AI คุยต่อ');
    }

    /**
     * ไล่เคส D0350 ทั้ง 6 ข้อความ ตอน AI ล้มทุกครั้ง (เหมือนคืนนั้น)
     */
    public function test_incident_d0350_replayed_with_ai_down_never_sends_the_payment_template(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 23:14:48', 'Asia/Bangkok'));
        // กล่องบิล + QR ส่งตอน 23:12:39
        $this->service->billIssuedAt = Carbon::parse('2026-09-15 23:12:39', 'Asia/Bangkok')->toIso8601String();
        $reading = $this->reading();

        $script = [
            ['23:14:48', 'มีป๊ณหานิดหนึ่งคั'],
            ['23:15:00', 'คือไม่มีปัณชี'],
            ['23:15:18', 'โอรนเงินที่ไทยคะ'],
            ['23:15:39', 'สักพัก'],
            ['23:15:45', 'นะะ'],
            ['23:15:56', 'จะลองให้ญืติฌอนให้ระคะ'],
        ];

        $sent = [];
        foreach ($script as [$time, $text]) {
            Carbon::setTestNow(Carbon::parse("2026-09-15 {$time}", 'Asia/Bangkok'));
            $result = $this->respond($reading, $text, $this->ctx());
            $sent[$text] = $result;

            $this->assertStringNotContainsString('รอเจ้าชะตาโอนค่าครู', (string) ($result['message'] ?? ''), "ห้ามกลับไปกล่องเดิม: {$text}");
        }

        // ทุกข้อความได้ลองให้ AI ฟังก่อน ยกเว้นคำรับทราบล้วน
        $this->assertSame(
            ['มีป๊ณหานิดหนึ่งคั', 'คือไม่มีปัณชี', 'โอรนเงินที่ไทยคะ', 'สักพัก', 'จะลองให้ญืติฌอนให้ระคะ'],
            array_column($this->service->aiCalls, 0)
        );

        $this->assertStringContainsString('ติดตรงไหน', $sent['มีป๊ณหานิดหนึ่งคั']['message']);

        $noBank = $sent['คือไม่มีปัณชี']['message'];
        $this->assertStringContainsString('ญาติ', $noBank);
        $this->assertStringContainsString('฿99.54', $noBank);

        // กำกวม + AI ล้ม → บอกว่าส่งต่อแอดมิน + ส่งต่อจริง
        $this->assertStringContainsString('แอดมิน', $sent['โอรนเงินที่ไทยคะ']['message']);

        $this->assertStringContainsString('ไม่ต้องรีบ', $sent['สักพัก']['message']);
        $this->assertStringContainsString('02:12 น.', $sent['สักพัก']['message']);

        // "นะะ" — บอทเพิ่งพูดไป 6 วินาทีก่อน → เงียบ
        $this->assertSame('silent_skip', $sent['นะะ']['action']);

        // กำกวมซ้ำภายใน 10 นาที → ไม่พูดประโยคส่งต่อแอดมินซ้ำ แต่ยังส่งต่อข้อความให้แอดมินครบ
        $this->assertSame('silent_skip', $sent['จะลองให้ญืติฌอนให้ระคะ']['action']);
        $this->assertCount(2, $this->service->adminNotes);
        $this->assertStringContainsString('FTU-260915-D0350', $this->service->adminNotes[0]);

        // ไม่มีข้อความไหนถูกส่งซ้ำคำต่อคำ
        $messages = array_values(array_filter(array_map(fn ($r) => $r['message'] ?? null, $sent)));
        $this->assertSame($messages, array_values(array_unique($messages)));
    }

    public function test_ai_reply_is_used_first_and_bill_footer_is_not_repeated_within_ten_minutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 23:40:00', 'Asia/Bangkok'));
        $this->service->billIssuedAt = Carbon::parse('2026-09-15 23:12:39', 'Asia/Bangkok')->toIso8601String();
        $reading = $this->reading();

        $this->service->aiReplies = ['ไม่เป็นไรเลยค่ะลูก ให้ญาติโอนแทนได้นะคะ', 'ได้เลยค่ะ ใครโอนก็ได้ค่ะ'];

        $first = $this->respond($reading, 'คือไม่มีบัญชี', $this->ctx());
        $this->assertSame('celtic_awaiting_payment', $first['action']);
        $this->assertStringStartsWith('ไม่เป็นไรเลยค่ะลูก', $first['message']);
        $this->assertStringContainsString('ค่าครู: 99.54 บาท', $first['message'], 'กล่องบิลส่งไปเกิน 10 นาทีแล้ว → แนบบรรทัดสรุปยอดได้');
        // AI เป็นคนตอบ → ต้องส่งเจตนาที่จับได้ไปเป็นคำใบ้
        $this->assertSame('no_bank', $this->service->aiCalls[0][1]);
        // บันทึกประวัติให้ AI รอบหน้าเห็น
        $this->assertSame([['user', 'คือไม่มีบัญชี'], ['assistant', 'ไม่เป็นไรเลยค่ะลูก ให้ญาติโอนแทนได้นะคะ']], $this->service->turns);

        Carbon::setTestNow(Carbon::parse('2026-09-15 23:42:00', 'Asia/Bangkok'));
        $second = $this->respond($reading, 'จะให้ญาติโอนให้นะคะ', $this->ctx());
        $this->assertSame('ได้เลยค่ะ ใครโอนก็ได้ค่ะ', $second['message'], 'เพิ่งแนบยอดไป 2 นาที → ไม่แนบซ้ำ');
    }

    public function test_short_yes_after_bot_question_goes_to_ai(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 23:40:00', 'Asia/Bangkok'));
        $this->service->billIssuedAt = Carbon::parse('2026-09-15 23:39:00', 'Asia/Bangkok')->toIso8601String();
        $this->service->botAsked = true;
        $this->service->aiReplies = ['ได้ค่ะ เดี๋ยวแม่หมอส่งเลขบัญชีให้นะคะ'];

        $result = $this->respond($this->reading(), 'ค่ะ', $this->ctx());

        $this->assertCount(1, $this->service->aiCalls, 'บอทเพิ่งถามคำถาม "ค่ะ" คือคำตอบ ต้องให้ AI ตีความ');
        $this->assertSame('ได้ค่ะ เดี๋ยวแม่หมอส่งเลขบัญชีให้นะคะ', $result['message']);
    }

    public function test_plain_ack_long_after_bill_gets_one_short_line_then_silence(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 23:40:00', 'Asia/Bangkok'));
        $this->service->billIssuedAt = Carbon::parse('2026-09-15 23:10:00', 'Asia/Bangkok')->toIso8601String();
        $reading = $this->reading();

        $first = $this->respond($reading, 'ค่ะ', $this->ctx());
        $this->assertSame('celtic_awaiting_payment', $first['action']);
        $this->assertStringNotContainsString('ค่าครู: 99.54', $first['message'], 'แค่ตอบรับ → ไม่แนบยอดทวง');
        $this->assertSame([], $this->service->aiCalls, 'คำรับทราบล้วน ไม่เผา AI');

        Carbon::setTestNow(Carbon::parse('2026-09-15 23:41:00', 'Asia/Bangkok'));
        $this->assertSame('silent_skip', $this->respond($reading, 'โอเคค่ะ', $this->ctx())['action']);
    }

    public function test_deep_bill_keeps_partial_payment_line_on_every_reply(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 23:40:00', 'Asia/Bangkok'));
        $this->service->billIssuedAt = Carbon::parse('2026-09-15 23:39:00', 'Asia/Bangkok')->toIso8601String();
        $partial = '💰 รับแล้ว ฿20.00 · ขาดอีก ฿19.43';

        $result = $this->respond($this->reading(), 'เดี๋ยวโอนส่วนที่เหลือนะคะ', $this->ctx('waiting_payment_reply', $partial));

        $this->assertSame('waiting_payment_reply', $result['action']);
        $this->assertStringContainsString($partial, $result['message'], 'owner 2026-08-29: บิลโอนขาดต้องบอกยอดรับแล้ว/ขาดอีกทุกกล่อง');
    }

    public function test_no_money_reply_never_pushes_payment(): void
    {
        $text = $this->service->pendingListenReplyText('no_money', $this->ctx());

        $this->assertStringNotContainsString('รีบ', $text);
        $this->assertStringNotContainsString('99.54', $text);
        $this->assertStringContainsString('ไม่ต้องฝืน', $text);
    }

    public function test_bill_reminder_waits_while_customer_is_talking(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 23:15:14', 'Asia/Bangkok'));
        $reading = $this->reading();
        $job = new SendBillReminderJob(13253, 1);
        $m = new ReflectionMethod($job, 'customerIsTalking');
        $m->setAccessible(true);

        $this->assertFalse($m->invoke($job, $reading), 'ไม่มีร่องรอยการคุย = เตือนตามปกติ');

        // ลูกค้าพิมพ์ "คือไม่มีบัญชี" 23:15:00 → ตัวจัดการบิลจดไว้
        Carbon::setTestNow(Carbon::parse('2026-09-15 23:15:00', 'Asia/Bangkok'));
        $note = new ReflectionMethod($this->service, 'notePendingPaymentActivity');
        $note->setAccessible(true);
        $note->invoke($this->service, $reading);

        Carbon::setTestNow(Carbon::parse('2026-09-15 23:15:14', 'Asia/Bangkok'));
        $this->assertTrue($m->invoke($job, $reading), '14 วินาทีหลังลูกค้าพิมพ์ ต้องไม่ทวงแทรก');

        Carbon::setTestNow(Carbon::parse('2026-09-15 23:21:00', 'Asia/Bangkok'));
        $this->assertFalse($m->invoke($job, $reading), 'ลูกค้าเงียบไปเกิน 5 นาที = เตือนได้');

        Cache::flush();
    }
}

/**
 * ตัวแทน FortuneConversationService — ตัดทาง AI / DB ออก ให้เทสต์ดูเฉพาะการตัดสินใจของตัวฟัง
 */
class PendingListenerDouble extends FortuneConversationService
{
    /** @var array<int, string> คิวคำตอบ AI (ว่าง = AI ล้ม) */
    public array $aiReplies = [];

    /** @var array<int, array{0: string, 1: string|null}> */
    public array $aiCalls = [];

    /** @var array<int, array{0: string, 1: string}> */
    public array $turns = [];

    /** @var array<int, string> */
    public array $adminNotes = [];

    public bool $botAsked = false;

    public ?string $billIssuedAt = null;

    public bool $stillPending = true;

    public function resetDouble(): void
    {
        $this->aiReplies = [];
        $this->aiCalls = [];
        $this->turns = [];
        $this->adminNotes = [];
        $this->botAsked = false;
        $this->billIssuedAt = null;
        $this->stillPending = true;
    }

    protected function pendingListenStillPending(FortuneReading $reading): bool
    {
        return $this->stillPending;
    }

    protected function pendingListenAiReply(FortuneReading $reading, string $messageText, ?string $intent, int $remainingMinutes): array
    {
        $this->aiCalls[] = [$messageText, $intent];

        return ['text' => (string) (array_shift($this->aiReplies) ?? ''), 'history_saved' => false];
    }

    protected function pendingListenRecordTurn(string $userId, string $role, string $text): void
    {
        $this->turns[] = [$role, $text];
    }

    protected function pendingListenNotifyAdmin(FortuneReading $reading, string $userId, string $messageText): void
    {
        $this->adminNotes[] = '['.($reading->bill_reference ?? '').'] '.$messageText;
    }

    protected function botAskedQuestionRecently(string $userId): bool
    {
        return $this->botAsked;
    }

    protected function pendingListenBillIssuedAt(FortuneReading $reading): ?string
    {
        return $this->billIssuedAt;
    }
}
