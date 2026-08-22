<?php

namespace Tests\Unit\Services;

use App\Models\FortuneReading;
use App\Services\FortuneConversationService;
use Carbon\Carbon;
use ReflectionClass;
use Tests\TestCase;

/**
 * 🛟 ทดสอบ "สำเนาคำถามค้าง" บน conversation_state — ตาข่ายกันคำถามลูกค้าหายตอน deploy
 *
 * บั๊กต้นตอ (FTU-260821-K9664, 2026-08-21):
 *   ลูกค้าจ่าย Deep 39฿ แล้วถาม 3 ข้อ ("งานค่ะ" / "เงิน" / "ศัตรู") ตอน deploy กำลังรัน
 *   → คำถามเข้า MessageBuffer ซึ่งอยู่บน Laravel Cache = redis DB 1
 *   → deploy รัน `php artisan cache:clear` → `RedisStore::flush()` → `flushdb()`
 *     **ล้างทั้ง database ไม่ใช่ลบตาม CACHE_PREFIX** → buffer หายทั้งก้อน
 *   → job ตื่นมาเจอ `empty(peek())` แล้ว `return;` เงียบ — ไม่มี log ไม่มี failed_jobs
 *   → ตาข่ายกู้ `fortune:pro-session-answer-recover` ก็ peek คีย์เดิม = มองไม่เห็นเช่นกัน
 *   → 8 นาทีต่อมา cron ยิง "หมดเวลาทำนายแล้วค่ะ" ทับหน้า = จ่ายเงินแล้วได้คำตอบ 0 ข้อ
 *
 * ทางแก้ที่เทสต์นี้เฝ้า: จดคำถามคู่ไว้บน conversation_state (คอลัมน์ JSON บน MySQL)
 *   ซึ่ง deploy ล้างไม่ได้ → job และ cron กู้คืนได้เสมอ
 *
 * ไม่ต้องใช้ฐานข้อมูล — เรียกเมธอดใน trait ตรงผ่าน Reflection + โมเดลปลอมที่เก็บ state ในหน่วยความจำ
 */
class FortunePendingQuestionTest extends TestCase
{
    protected FortuneConversationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // ตรึงเวลา — ตรรกะ grace/เพดานขึ้นกับ now()
        Carbon::setTestNow(Carbon::create(2026, 8, 21, 19, 43, 0));

        // เมธอดกลุ่มนี้แตะแค่ $reading + now() → สร้าง service แบบข้าม constructor ได้
        // (constructor จริงต้องอ่าน settings จาก DB ซึ่งไม่จำเป็นกับเทสต์นี้)
        $this->service = (new ReflectionClass(FortuneConversationService::class))
            ->newInstanceWithoutConstructor();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** เรียกเมธอด protected ใน trait */
    protected function invokeTrait(string $method, ...$args)
    {
        $ref = new \ReflectionMethod(FortuneConversationService::class, $method);
        $ref->setAccessible(true);

        return $ref->invoke($this->service, ...$args);
    }

    protected function reading(): FortuneReading
    {
        return new class extends FortuneReading
        {
            /** @var array<string, mixed> state ในหน่วยความจำ — ไม่แตะ DB */
            public array $memoryState = [];

            public function setConversationState(string $key, $value): void
            {
                $this->memoryState[$key] = $value;
            }

            public function getConversationState(string $key, $default = null)
            {
                return $this->memoryState[$key] ?? $default;
            }

            public function refresh(): static
            {
                return $this; // ไม่มี DB ให้ refresh
            }

            public function getKey()
            {
                return 11388;
            }
        };
    }

    /** คำถามที่จดไว้ต้องอ่านกลับได้ครบ และแยกคีย์ deep / celtic ไม่ปนกัน */
    public function test_deep_and_celtic_pending_questions_use_separate_keys(): void
    {
        $reading = $this->reading();

        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'งานค่ะ', 'deep');
        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'ไพ่ใบที่สามหมายถึงอะไร', 'celtic');

        $this->assertSame(['งานค่ะ'], $reading->getConversationState('pro_session_pending_q'));
        $this->assertSame(['ไพ่ใบที่สามหมายถึงอะไร'], $reading->getConversationState('celtic_pending_q'));

        // ⚠️ ถ้าใช้คีย์ร่วมกัน cron ของ Deep จะไปหยิบคำถาม Celtic แล้ว dispatch job ผิดคลาส
        $this->assertNotSame(
            $reading->getConversationState('pro_session_pending_q'),
            $reading->getConversationState('celtic_pending_q')
        );
    }

    /**
     * เวลา "ข้อความแรกที่ค้าง" ต้องไม่ถูก reset ตอนลูกค้าพิมพ์ข้อความถัดไป
     *
     * ถ้า reset ทุกครั้ง ลูกค้าที่พิมพ์เรื่อย ๆ จะไม่มีวันแก่พอให้ตาข่ายกู้ (grace 60s) มองเห็น
     */
    public function test_pending_timestamp_is_not_reset_by_later_messages(): void
    {
        $reading = $this->reading();

        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'งานค่ะ', 'deep');
        $firstAt = $reading->getConversationState('pro_session_pending_q_at');

        Carbon::setTestNow(Carbon::create(2026, 8, 21, 19, 43, 23));
        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'ศัตรู', 'deep');

        $this->assertSame($firstAt, $reading->getConversationState('pro_session_pending_q_at'));
        $this->assertSame(['งานค่ะ', 'ศัตรู'], $reading->getConversationState('pro_session_pending_q'));
    }

    /** รัวเกินเพดานต้องเก็บ "ก้อนท้ายสุด" (คำถามล่าสุด = สิ่งที่ลูกค้าอยากรู้จริง) */
    public function test_pending_list_is_capped_keeping_newest(): void
    {
        $reading = $this->reading();

        for ($i = 1; $i <= FortuneConversationService::PRO_SESSION_PENDING_MAX + 3; $i++) {
            $this->invokeTrait('rememberPendingProSessionQuestion', $reading, "คำถาม {$i}", 'deep');
        }

        $pending = $reading->getConversationState('pro_session_pending_q');

        $this->assertCount(FortuneConversationService::PRO_SESSION_PENDING_MAX, $pending);
        $this->assertSame('คำถาม 4', $pending[0]);   // 3 ตัวแรกถูกตัดทิ้ง
        $this->assertSame('คำถาม 11', end($pending)); // ตัวล่าสุดต้องอยู่
    }

    /** ข้อความว่าง/เว้นวรรคล้วน ไม่ควรสร้างธงค้าง (ไม่งั้นตาข่ายกู้จะไล่ตอบความว่างเปล่า) */
    public function test_blank_message_does_not_create_pending_flag(): void
    {
        $reading = $this->reading();

        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, '   ', 'deep');

        $this->assertFalse($this->invokeTrait('hasPendingProSessionQuestion', $reading, null, null));
        $this->assertEmpty($reading->getConversationState('pro_session_pending_q', []));
    }

    /** ยังไม่ถึง grace = ตาข่ายกู้ต้องยังไม่แตะ (ห้ามแย่ง job ปกติที่ยัง debounce อยู่) */
    public function test_pending_respects_older_than_threshold(): void
    {
        $reading = $this->reading();
        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'เงิน', 'deep');

        Carbon::setTestNow(Carbon::create(2026, 8, 21, 19, 43, 30)); // ผ่านไป 30 วิ

        $this->assertTrue($this->invokeTrait('hasPendingProSessionQuestion', $reading, null, null));
        $this->assertFalse($this->invokeTrait('hasPendingProSessionQuestion', $reading, 60, null));

        Carbon::setTestNow(Carbon::create(2026, 8, 21, 19, 44, 30)); // ผ่านไป 90 วิ
        $this->assertTrue($this->invokeTrait('hasPendingProSessionQuestion', $reading, 60, null));
    }

    /**
     * เพดาน grace — คำถามค้างเก่าเกิน PRO_SESSION_PENDING_GRACE_MINUTES ต้องเลิกยืดเวลา session
     * (ไม่งั้น session จะกลายเป็นอมตะ บล็อกการดูดวงครั้งถัดไปของลูกค้า)
     */
    public function test_pending_stops_holding_clock_after_grace_cap(): void
    {
        $reading = $this->reading();
        $base = Carbon::getTestNow()->copy();
        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'ศัตรู', 'deep');

        // ⚠️ (2026-08-22) ผูกกับ constant ห้าม hardcode นาที — ค่านี้ถูกปรับ 10 → 15 มาแล้วรอบหนึ่ง
        //   (เคส FTU-260822-P2391) เทสต์ที่ตรึงเลขไว้จะแดงทุกครั้งที่จูนค่า ทั้งที่พฤติกรรมยังถูก
        $cap = FortuneConversationService::PRO_SESSION_PENDING_GRACE_MINUTES;

        // ก่อนถึงเพดาน — ยังต้องยืดเวลาให้ตอบ
        Carbon::setTestNow($base->copy()->addMinutes($cap - 1));
        $this->assertTrue($this->invokeTrait('hasPendingProSessionQuestion', $reading, null, null));

        // พ้นเพดานแม้แต่วินาทีเดียว — ต้องเลิกยืด (กัน session อมตะ)
        Carbon::setTestNow($base->copy()->addMinutes($cap)->addSeconds(1));
        $this->assertFalse($this->invokeTrait('hasPendingProSessionQuestion', $reading, null, null));
    }

    /** ปิด session = ล้างคำถามค้างทั้งสองฝั่ง (กันคำถามเก่าโผล่มาตอบข้ามวัน) */
    public function test_clear_without_scope_clears_both_lanes(): void
    {
        $reading = $this->reading();
        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'งานค่ะ', 'deep');
        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'ไพ่ใบสาม', 'celtic');

        $this->invokeTrait('clearPendingProSessionQuestion', $reading, null);

        $this->assertFalse($this->invokeTrait('hasPendingProSessionQuestion', $reading, null, null));
        $this->assertEmpty($reading->getConversationState('pro_session_pending_q', []));
        $this->assertEmpty($reading->getConversationState('celtic_pending_q', []));
        $this->assertNull($reading->getConversationState('pro_session_pending_q_at'));
    }

    /** ล้างเฉพาะ scope เดียว ต้องไม่ไปแตะอีกฝั่ง */
    public function test_clear_with_scope_leaves_other_lane_intact(): void
    {
        $reading = $this->reading();
        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'งานค่ะ', 'deep');
        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'ไพ่ใบสาม', 'celtic');

        $this->invokeTrait('clearPendingProSessionQuestion', $reading, 'deep');

        $this->assertEmpty($reading->getConversationState('pro_session_pending_q', []));
        $this->assertSame(['ไพ่ใบสาม'], $reading->getConversationState('celtic_pending_q'));
        $this->assertTrue($this->invokeTrait('hasPendingProSessionQuestion', $reading, null, 'celtic'));
    }

    /**
     * 🔒 หยิบได้ครั้งเดียว — กันตอบซ้ำ
     *
     * เคสต้นตอมี job ถูก dispatch ไว้ 3 ตัว (ลูกค้าพิมพ์ 3 ข้อความ) ถ้าทั้ง 3 หยิบสำเนาเดียวกันได้
     * ลูกค้าจะโดนตอบคำถามเดิมซ้ำ 3 รอบ
     */
    public function test_take_pending_is_one_shot(): void
    {
        $reading = $this->reading();
        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'งานค่ะ', 'deep');
        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'เงิน', 'deep');
        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'ศัตรู', 'deep');

        $first = $this->service->takePendingProSessionQuestionPublic($reading, 'deep');
        $second = $this->service->takePendingProSessionQuestionPublic($reading, 'deep');

        $this->assertSame("งานค่ะ\nเงิน\nศัตรู", $first);
        $this->assertSame('', $second);
    }

    /** หยิบฝั่ง deep ต้องไม่ดูดคำถาม celtic ติดไปด้วย */
    public function test_take_pending_is_scoped(): void
    {
        $reading = $this->reading();
        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'งานค่ะ', 'deep');
        $this->invokeTrait('rememberPendingProSessionQuestion', $reading, 'ไพ่ใบสาม', 'celtic');

        $this->assertSame('งานค่ะ', $this->service->takePendingProSessionQuestionPublic($reading, 'deep'));
        $this->assertSame('ไพ่ใบสาม', $this->service->takePendingProSessionQuestionPublic($reading, 'celtic'));
    }
}
