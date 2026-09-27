<?php

namespace Tests\Unit\Services;

use App\Models\FortuneReading;
use App\Services\Fortune\BirthdateResolver;
use App\Services\Fortune\ThaiAstrologyService;
use App\Services\FortuneConversationService;
use Illuminate\Support\Carbon;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

/**
 * 🎂 (2026-09-27, owner) *"ลูกค้าเปลี่ยนวันเกิด แม่หมอบอทรับว่ารับวันเกิดแล้ว แต่ไม่เปลี่ยนในบิลให้ตรงจริง
 *    วันเวลา เมืองเกิดด้วย"*
 *
 * เคสจริง FTU-260927-A4514 (LINE · Celtic 99 · ระหว่างรอโอน):
 *   17:30:39 ลูกค้า "อย่าทำนายผิดวันเกิดนะคะ" → 17:30:49 "27/6/2521"
 *   แม่หมอ (AI): "ได้ข้อมูลวันเกิด 27 มิถุนายน 2521 แล้วนะคะลูก แม่หมอรับทราบ…" — birth_date = NULL
 *   บิลเก่าของลูกค้าคนเดียวกันมีวันเกิดผิดปน (FTU-260725-J8315 = 1978-07-27)
 *
 * ไม่ใช้ DB — บิลเป็นตัวในหน่วยความจำ (update() = เติมค่าเฉย ๆ) · AI/ประวัติแชทใช้ตัวแทน
 */
class FortuneBirthInfoCaptureTest extends TestCase
{
    protected BirthInfoListenerDouble $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = (new ReflectionClass(BirthInfoListenerDouble::class))->newInstanceWithoutConstructor();
        $this->service->resetDouble();

        // คอลัมน์ birth_province มีจริงบน prod — เช็คด้วย Schema (ต้องมี DB) ⇒ ตั้งค่าที่จำไว้แทน
        (new ReflectionProperty(FortuneReading::class, 'birthProvinceColumn'))->setValue(null, true);

        Carbon::setTestNow(Carbon::parse('2026-09-27 17:30:00', 'Asia/Bangkok'));
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(FortuneReading::class, 'birthProvinceColumn'))->setValue(null, null);
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function celticReading(?string $birthDate = null, array $state = []): InMemoryFortuneReading
    {
        $reading = new InMemoryFortuneReading;
        $reading->forceFill([
            'id' => 13780,
            'facebook_user_id' => 'U6343edd96a0797d37cea6bfada5afc25',
            'platform_user_id' => 'U6343edd96a0797d37cea6bfada5afc25',
            'platform' => 'line',
            'bill_reference' => 'FTU-260927-A4514',
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'birth_date' => $birthDate,
            'conversation_state' => $state,
        ]);

        return $reading;
    }

    private function ctx(): array
    {
        return [
            'action' => 'celtic_awaiting_payment',
            'pay_amount' => '99.33',
            'expires_at' => Carbon::parse('2026-09-27 20:27:47', 'Asia/Bangkok'),
            'remaining_minutes' => 177,
            'footer' => '💸 *ค่าครู: 99.33 บาท* (ทศนิยมต้องตรง)',
        ];
    }

    private function callProtected(string $method, ...$args)
    {
        $m = new ReflectionMethod($this->service, $method);
        $m->setAccessible(true);

        return $m->invoke($this->service, ...$args);
    }

    // ─── เลนรอโอน ──────────────────────────────────────────────

    public function test_incident_a4514_birthdate_typed_while_waiting_lands_in_the_bill(): void
    {
        $reading = $this->celticReading();
        $this->service->aiReplies = ['ได้เลยค่ะลูก แม่หมอดูให้ตรงจุดแน่นอนค่ะ'];

        // 1) เตือนเฉย ๆ ไม่มีวันที่ → AI ตอบ แต่ต้องถูกกำกับว่า "ยังไม่ได้บันทึก" ห้ามรับปาก
        $this->callProtected('respondWhilePendingPayment', $reading, 'อย่าทำนายผิดวันเกิดนะคะ', $this->ctx());
        $this->assertCount(1, $this->service->aiCalls);
        $this->assertStringContainsString('ยังไม่ได้บันทึก', $this->service->birthHints[0]);
        $this->assertStringContainsString('ห้ามพูดว่ารับ', $this->service->birthHints[0]);
        $this->assertNull($reading->birth_date);

        // 2) "27/6/2521" → บันทึกลงบิลจริง + ทวนด้วยข้อความของระบบ (ไม่ใช่ AI รับปาก)
        $result = $this->callProtected('respondWhilePendingPayment', $reading, '27/6/2521', $this->ctx());
        $this->assertSame('1978-06-27', $reading->birth_date?->format('Y-m-d'), 'วันเกิดต้องลงคอลัมน์จริง');
        $this->assertCount(1, $this->service->aiCalls, 'ข้อความที่มีแต่วันเกิด ไม่ต้องให้ AI ตอบ');
        $this->assertSame('celtic_awaiting_payment', $result['action']);
        $this->assertStringContainsString('บันทึกข้อมูลเกิดของลูกลงบิลให้แล้ว', $result['message']);
        $this->assertStringContainsString('27 มิถุนายน 2521', $result['message']);
    }

    public function test_date_time_and_province_in_one_message_are_all_saved(): void
    {
        $reading = $this->celticReading();

        $result = $this->callProtected('respondWhilePendingPayment', $reading, '27 มิ.ย. 2521 ตี 2 ครึ่ง เชียงใหม่ค่ะ', $this->ctx());

        $this->assertSame('1978-06-27', $reading->birth_date?->format('Y-m-d'));
        $this->assertTrue($reading->birthTimeIsKnown());
        $this->assertSame('02:30', substr((string) $reading->birth_time, 0, 5));
        $this->assertSame('เชียงใหม่', $reading->birthProvinceIfKnown());
        $this->assertStringContainsString('02:30 น.', $result['message']);
        $this->assertStringContainsString('เชียงใหม่', $result['message']);
        $this->assertSame([], $this->service->aiCalls);
        // ⚠️ เลนรอโอนห้ามแตะ conversation_state (เส้นยืนยันการจ่ายเงินเขียนพร้อมกันได้)
        $this->assertSame([], (array) $reading->conversation_state);
    }

    public function test_mixed_message_saves_then_ai_answers_the_rest_without_repeating(): void
    {
        $reading = $this->celticReading();
        $this->service->aiReplies = ['ไม่เป็นไรเลยค่ะลูก ค่อย ๆ หานะคะ'];

        $result = $this->callProtected('respondWhilePendingPayment', $reading, 'หนูเกิด 5/3/2530 แต่ตอนนี้ยังไม่มีเงินโอนเลยค่ะ', $this->ctx());

        $this->assertSame('1987-03-05', $reading->birth_date?->format('Y-m-d'));
        $this->assertCount(1, $this->service->aiCalls);
        $this->assertStringContainsString('ห้ามพูดเรื่องวันเกิด', $this->service->birthHints[0]);
        $this->assertStringStartsWith('📝 แม่หมอบันทึกข้อมูลเกิดของลูกลงบิลให้แล้วค่ะ', $result['message']);
        $this->assertStringContainsString('ไม่เป็นไรเลยค่ะลูก', $result['message']);
    }

    public function test_partner_birthdate_is_never_saved_as_the_customers(): void
    {
        $reading = $this->celticReading();
        $this->service->aiReplies = ['เรื่องแฟนแม่หมอดูให้ได้หลังโอนนะคะ'];

        $this->callProtected('respondWhilePendingPayment', $reading, 'แฟนเกิด 3/6/2497 ดูให้ได้ไหมคะ', $this->ctx());

        $this->assertNull($reading->birth_date, 'วันเกิดแฟนห้ามลงบิลเป็นของเจ้าชะตา');
        $this->assertStringContainsString('ยังไม่ได้บันทึก', $this->service->birthHints[0]);
    }

    public function test_weekday_that_disagrees_asks_back_instead_of_saving(): void
    {
        $reading = $this->celticReading();

        $result = $this->callProtected('respondWhilePendingPayment', $reading, 'เกิดวันอาทิตย์ 27/6/2521', $this->ctx());

        $this->assertNull($reading->birth_date);
        $this->assertStringContainsString('วันอาทิตย์', $result['message']);
        $this->assertStringContainsString('วันอังคาร', $result['message']);
        $this->assertSame([], $this->service->aiCalls);
    }

    // ─── หลังจ่าย: วันเกิดในบิลนี้ชนะบิลเก่า ───────────────────────────

    public function test_resolver_prefers_the_date_already_in_this_bill(): void
    {
        $hit = BirthdateResolver::forReading($this->celticReading('1978-06-27'));

        $this->assertSame('1978-06-27', $hit['ymd']);
        $this->assertSame(BirthdateResolver::SRC_THIS_READING, $hit['source']);
        $this->assertNotSame('', BirthdateResolver::sourceLabel($hit['source']));
    }

    // ─── เลน 99 กลางวงถาม-ตอบ: แก้วันเกิด = กล่องยืนยัน → แก้ในบิลจริง ─────

    public function test_celtic_correction_confirms_then_rewrites_every_place_the_chart_reads(): void
    {
        // สภาพเดียวกับบิล 10226: ผังตั้งต้นจากบิลเก่าที่ผิด (27/07/1978)
        $reading = $this->celticReading('1978-07-27', [
            'celtic_birthdate_text' => "เจ้าชะตาเกิด 27/07/1978\nเกิดวันอังคาร27/6/2521",
        ]);

        $ask = $this->callProtected('handleCelticQaBirthStatement', $reading, 'วันเกิดที่ถูกคือ 27/6/2521 ตี 2 ครึ่งค่ะ');
        $this->assertSame('birthdate_correction_confirm', $ask['action']);
        $this->assertTrue($ask['show_quick_replies']);
        $this->assertCount(2, $ask['quick_replies']);
        foreach ($ask['quick_replies'] as $b) {
            $this->assertLessThanOrEqual(20, mb_strlen($b['title']), 'ชื่อปุ่มเกิน 20 ตัว = FB/LINE ตัดทิ้ง');
        }
        $this->assertSame('1978-07-27', $reading->birth_date?->format('Y-m-d'), 'ยังไม่ยืนยัน = ยังไม่แก้');

        $done = $this->callProtected('handleCelticQaBirthStatement', $reading, 'ยืนยันวันเกิดใหม่');
        $this->assertSame('birthdate_correction_applied', $done['action']);
        $this->assertSame('1978-06-27', $reading->birth_date?->format('Y-m-d'));
        $this->assertSame('02:30', substr((string) $reading->birth_time, 0, 5), 'เวลาที่พิมพ์มากับคำขอแก้ต้องลงด้วย');
        $this->assertStringStartsWith('เจ้าชะตาเกิด 27/06/1978', $reading->getConversationState('celtic_birthdate_text'));
        $this->assertStringNotContainsString('27/07/1978', $reading->getConversationState('celtic_birthdate_text'));
        $this->assertSame(['1978-07-27'], $reading->replacedBirthDates());
        $this->assertNull($reading->getConversationState('birthdate_correction_pending_date'));

        // กดปุ่มยืนยันเดิมซ้ำ (ปุ่มยังค้างบนจอ) → ต้องบอกว่าแก้แล้ว ไม่ใช่ "ยังไม่ได้แก้"
        $again = $this->callProtected('handleCelticQaBirthStatement', $reading, 'ยืนยันวันเกิดใหม่');
        $this->assertSame('birthdate_correction_applied', $again['action']);
        $this->assertStringContainsString('27 มิถุนายน 2521', $again['message']);

        // ผังเลน 99: เจ้าชะตา = วันเกิดใหม่ และวันเดิมที่ถูกแก้ต้องไม่กลับมาเป็น "คนที่ 2"
        $block = (new ThaiAstrologyService)->buildCelticBirthAstrologyBlock(
            $reading->celticBirthAstroSource('แฟนเกิด 3/6/2497 จะกลับมาไหม'),
            null,
            null,
            $reading->replacedBirthDates()
        );
        $this->assertStringContainsString('คนที่ 1 (เกิด 27/06/1978)', $block);
        $this->assertStringContainsString('3/6/2497', $block);
        $this->assertStringNotContainsString('27/07/1978', $block);
    }

    public function test_celtic_someone_elses_date_can_be_declined(): void
    {
        $reading = $this->celticReading('1978-06-27');

        $ask = $this->callProtected('handleCelticQaBirthStatement', $reading, '3/6/2497');
        $this->assertSame('birthdate_correction_confirm', $ask['action'], 'กลางวงคุย วันที่เปล่า ๆ ต้องถามก่อนแก้');

        $no = $this->callProtected('handleCelticQaBirthStatement', $reading, 'ไม่ใช่ค่ะ');
        $this->assertSame('birthdate_correction_cancelled', $no['action']);
        $this->assertSame('1978-06-27', $reading->birth_date?->format('Y-m-d'));
        $this->assertStringContainsString('27 มิถุนายน 2521', $no['message']);
    }

    public function test_pending_confirmation_never_swallows_a_real_question(): void
    {
        $reading = $this->celticReading('1978-06-27');
        $this->callProtected('handleCelticQaBirthStatement', $reading, '3/6/2497');

        $next = $this->callProtected('handleCelticQaBirthStatement', $reading, 'แล้วเรื่องงานปีหน้าจะดีขึ้นไหมคะ');

        $this->assertNull($next, 'พิมพ์คำถามมาแทนการยืนยัน = ไม่แก้ ปล่อยไปตอบตามปกติ');
        $this->assertNull($reading->getConversationState('birthdate_correction_pending_date'));
        $this->assertSame('1978-06-27', $reading->birth_date?->format('Y-m-d'));
    }

    /**
     * เคสที่ owner แจ้งจริง (17:38:12 บิลเดียวกัน หลังพื้นดวงเปิดตัว):
     *   ลูกค้า "เกิด27/6/2521ปีมะเมีย เวลาเที่ยงวัน เกิดที่ ขอนแก่น"
     *   AI ตอบ "รับข้อมูลวันเกิด 27 มิถุนายน 2521 เวลาเที่ยงวัน ที่ขอนแก่นแล้ว" แต่บิลยังเป็น
     *   เวลามาตรฐาน (ป้าย default) + ไม่มีจังหวัด — แอดมินต้องกรอกเอง ("วันเวลา เมืองเกิดด้วย")
     */
    public function test_incident_a4514_qa_time_and_city_are_saved_not_just_promised(): void
    {
        $reading = $this->celticReading('1978-06-27', ['celtic_birthdate_text' => 'เจ้าชะตาเกิด 27/06/1978']);
        $reading->forceFill(['birth_time' => '12:00:00', 'birth_time_source' => FortuneReading::BIRTH_TIME_SOURCE_DEFAULT]);
        $this->assertFalse($reading->birthTimeIsKnown());

        $result = $this->callProtected('handleCelticQaBirthStatement', $reading, 'เกิด27/6/2521ปีมะเมีย เวลาเที่ยงวัน เกิดที่ ขอนแก่น');

        $this->assertSame('celtic_invite_question', $result['action'], 'ตอบเองแบบตายตัว ไม่ส่งเข้า AI เป็นคำถาม');
        $this->assertTrue($reading->birthTimeIsKnown(), 'เที่ยงวัน = เวลาที่รู้จริง ไม่ใช่ค่ามาตรฐาน');
        $this->assertSame('12:00', substr((string) $reading->birth_time, 0, 5));
        $this->assertSame('ขอนแก่น', $reading->birthProvinceIfKnown());
        $this->assertStringContainsString('12:00 น.', $result['message']);
        $this->assertStringContainsString('ขอนแก่น', $result['message']);
    }

    public function test_celtic_same_date_again_is_acknowledged_without_burning_a_question(): void
    {
        $reading = $this->celticReading('1978-06-27');

        $result = $this->callProtected('handleCelticQaBirthStatement', $reading, '27/6/2521 เกิดที่ขอนแก่นค่ะ');

        $this->assertSame('celtic_invite_question', $result['action']);
        $this->assertStringContainsString('ตรงกับที่แม่หมอผูกดวงให้อยู่แล้ว', $result['message']);
        $this->assertSame('ขอนแก่น', $reading->birthProvinceIfKnown());
    }

    public function test_deep_bare_date_goes_to_the_one_time_regenerate_confirmation(): void
    {
        $reading = $this->celticReading('1978-07-27');
        $reading->forceFill(['reading_type' => FortuneReading::READING_TYPE_DEEP]);

        $ask = $this->callProtected('handleBirthdateCorrection', $reading, 'หนูเกิด 27/6/2521 นะคะ');

        $this->assertSame('birthdate_correction_confirm', $ask['action']);
        $this->assertStringContainsString('ครั้งเดียวต่อบิล', $ask['message']);
        $this->assertSame('1978-06-27', $reading->getConversationState('birthdate_correction_pending_date'));
    }

    // ─── เลน 99 เปิดไพ่ ────────────────────────────────────────────

    public function test_birthdate_typed_while_picking_cards_is_saved_not_counted_as_ready(): void
    {
        $reading = $this->celticReading();

        $saved = $this->callProtected('captureCelticPickingBirthInfo', $reading, '27/6/2521');

        $this->assertNotNull($saved);
        $this->assertTrue($saved['only']);
        $this->assertSame('1978-06-27', $reading->birth_date?->format('Y-m-d'));
        $this->assertNull($this->callProtected('captureCelticPickingBirthInfo', $this->celticReading(), 'พร้อม'));
    }
}

/**
 * บิลในหน่วยความจำ — update() เติมค่าเฉย ๆ ไม่แตะ DB
 */
class InMemoryFortuneReading extends FortuneReading
{
    public function update(array $attributes = [], array $options = [])
    {
        $this->forceFill($attributes);

        return true;
    }
}

/**
 * ตัวแทน FortuneConversationService — ตัดทาง AI / DB / แชทล็อกออก
 */
class BirthInfoListenerDouble extends FortuneConversationService
{
    /** @var array<int, string> */
    public array $aiReplies = [];

    /** @var array<int, array{0: string, 1: string|null}> */
    public array $aiCalls = [];

    /** @var array<int, string> */
    public array $birthHints = [];

    public function resetDouble(): void
    {
        $this->aiReplies = [];
        $this->aiCalls = [];
        $this->birthHints = [];
    }

    protected function pendingListenStillPending(FortuneReading $reading): bool
    {
        return true;
    }

    protected function pendingListenAiReply(FortuneReading $reading, string $messageText, ?string $intent, int $remainingMinutes, string $birthHint = ''): array
    {
        $this->aiCalls[] = [$messageText, $intent];
        $this->birthHints[] = $birthHint;

        return ['text' => (string) (array_shift($this->aiReplies) ?? ''), 'history_saved' => false];
    }

    protected function pendingListenRecordTurn(string $userId, string $role, string $text): void
    {
        // ไม่บันทึกประวัติแชท (ไม่มี DB)
    }

    protected function pendingListenNotifyAdmin(FortuneReading $reading, string $userId, string $messageText): void
    {
        // ไม่ส่งต่อแอดมิน
    }

    protected function botAskedQuestionRecently(string $userId): bool
    {
        return false;
    }

    protected function pendingListenBillIssuedAt(FortuneReading $reading): ?string
    {
        return Carbon::parse('2026-09-27 17:27:47', 'Asia/Bangkok')->toIso8601String();
    }
}
