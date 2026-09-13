<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\FortuneBillsController;
use App\Models\FortuneReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 🧾 หน้าบิลดูดวง: แยก "แถวแชทที่ไม่เคยออกบิล" ออกจากบิลจริง (2026-09-13)
 *
 * เจ้าของถาม "เขายังไม่ได้ออกบิล ทำไมต้องมีรายการบิลมารอ มันรกตาราง"
 * บอทสร้างแถว reading ตั้งแต่ตอนโชว์เมนูแพคเกจ และแถว deep/celtic ได้เลข FTU อัตโนมัติ
 * ⇒ prod 72% ของตารางคือแถวแชท ไม่ใช่บิล (เคส FTU-260913-K3863 → E7850)
 *
 * ล็อก 3 อย่าง:
 *   1. scopeNeverBilled() ได้ชุดเดียวกับ isNeverBilled() เป๊ะ และรวมกับ exceptNeverBilled() = ทั้งตาราง
 *      (NOT (...) บนคอลัมน์ NULL ต้องไม่ทำแถวหาย)
 *   2. ร่องรอยบิลทุกแบบ (ยอด / UPA / Stripe / บิลลอย / รอชำระ / จ่ายแล้ว) + แพคเกจฟรี ต้องไม่ถูกนับเป็นซาก
 *   3. หน้าบิล: ค่าเริ่มต้นซ่อนซาก · ตัวกรอง no_bill เจอครบ · ยกเลิก/ยังไม่จ่าย/ปิดเงียบ ไม่มีซากปน · KPI นับแยก
 */
class FortuneReadingNeverBilledTest extends TestCase
{
    use RefreshDatabase;

    /** สร้างแถวทดสอบ — คืน id (forceFill เพราะบางคอลัมน์ไม่อยู่ใน $fillable) */
    private function makeReading(array $attrs = [], ?array $state = null): int
    {
        $reading = new FortuneReading;
        $reading->forceFill(array_merge([
            'platform' => 'facebook',
            'platform_user_id' => '61550000000077',
            'facebook_user_id' => '61550000000077',
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'questions' => [],
            'is_paid' => false,
            'amount_paid' => 0,
        ], $attrs, ['conversation_state' => $state]))->save();

        return $reading->id;
    }

    /** ชุดแถวครบทุกรูปแบบ — key = ชื่อเคส */
    private function seedShapes(): array
    {
        return [
            // ⬇️ ซาก — แพคเกจเสียเงินที่ไม่มีร่องรอยบิลเลย
            'menu_left' => $this->makeReading(), // เคส K3863: เห็นเมนูแล้วหาย / กดแพคเกจแล้วระบบปิดแถวเดิม
            'menu_live' => $this->makeReading(['conversation_status' => FortuneReading::STATUS_TIER_CHOICE]),
            'menu_declined' => $this->makeReading([], ['cancellation_reason' => 'user_rejected_question']),
            'celtic_shell' => $this->makeReading(['reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS]),

            // ⬇️ มีร่องรอยบิล — ต้องไม่นับเป็นซาก
            'cancelled_bill' => $this->makeReading(['amount_paid' => 39.46], ['cancellation_reason' => 'user_cancelled']), // เคส E7850
            'rejected_from_app' => $this->makeReading(['conversation_status' => 'cancelled', 'amount_paid' => 39.21]),
            'abandoned_bill' => $this->makeReading(['amount_paid' => 39.33]),
            'pending' => $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'amount_paid' => 39.12]),
            // เลือกแพคเกจแล้ว รอเลือกวิธีจ่าย — ยังไม่มียอด แต่เป็นบิลรอชำระ
            'awaiting_method' => $this->makeReading([
                'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
                'conversation_status' => FortuneReading::STATUS_AWAITING_PAYMENT_METHOD,
            ]),
            'paid_no_amount' => $this->makeReading(['is_paid' => true]), // บิลเก่าตัดผ่าน SMS/แอดมิน ยอดบิล 0
            'floating' => $this->makeReading(['is_floating' => true]),
            'upa_used' => $this->makeReading(['unique_payment_amount_id' => 999999]), // prod มี 2 ใบ: ยอด 0 แต่ UPA ถูกใช้แล้ว
            'stripe' => $this->makeReading(['stripe_session_id' => 'cs_test_never_billed']),

            // ⬇️ แพคเกจฟรี — ไม่ใช่ซาก (เป็นบริการที่ส่งให้ลูกค้าจริง)
            'free_basic' => $this->makeReading(['reading_type' => FortuneReading::READING_TYPE_BASIC]),
        ];
    }

    /** เปิดเมธอด protected ของหน้าบิลให้เทสต์เรียกได้ — ไม่ต้องผ่าน middleware/สิทธิ์แอดมิน */
    private function billsPage(): object
    {
        return new class extends FortuneBillsController
        {
            private function filters(string $status): array
            {
                return [
                    'search' => '', 'package' => '', 'platform' => '', 'status' => $status,
                    'date_from' => null, 'date_to' => null, 'ai_provider' => '', 'category' => '', 'fortune_page' => '',
                ];
            }

            public function ids(string $status): array
            {
                return $this->applyFilters(FortuneReading::query(), $this->filters($status))
                    ->orderBy('id')->pluck('id')->all();
            }

            public function stats(): array
            {
                return $this->buildStats($this->filters(''));
            }
        };
    }

    #[Test]
    public function never_billed_scope_matches_php_and_partitions_the_table(): void
    {
        $s = $this->seedShapes();

        $never = FortuneReading::neverBilled()->orderBy('id')->pluck('id')->all();
        $this->assertSame([$s['menu_left'], $s['menu_live'], $s['menu_declined'], $s['celtic_shell']], $never);

        // ชุดเดียวกับ isNeverBilled() ของ PHP ทุกแถว
        $byPhp = FortuneReading::orderBy('id')->get()
            ->filter(fn (FortuneReading $r) => $r->isNeverBilled())->pluck('id')->values()->all();
        $this->assertSame($byPhp, $never);

        // ซาก + ไม่ใช่ซาก = ทั้งตาราง (ไม่มีแถวตกหล่นเพราะ NULL)
        $billed = FortuneReading::exceptNeverBilled()->orderBy('id')->pluck('id')->all();
        $this->assertSame(FortuneReading::count(), count($never) + count($billed));
        $this->assertEmpty(array_intersect($never, $billed));
    }

    #[Test]
    public function bills_page_hides_never_billed_rows_by_default_but_keeps_them_findable(): void
    {
        $s = $this->seedShapes();
        $shells = [$s['menu_left'], $s['menu_live'], $s['menu_declined'], $s['celtic_shell']];
        $page = $this->billsPage();

        // ค่าเริ่มต้น "ทุกบิล" — ไม่มีซากสักแถว แต่บิลจริงอยู่ครบ
        $default = $page->ids('');
        $this->assertEmpty(array_intersect($shells, $default));
        $this->assertSame(FortuneReading::count() - count($shells), count($default));
        $this->assertContains($s['cancelled_bill'], $default);
        $this->assertContains($s['free_basic'], $default);

        // ตัวกรอง "ไม่เคยออกบิล" เจอครบทุกแบบ (หายไป / กำลังคุย / ปฏิเสธที่เมนู / Celtic)
        $this->assertSame($shells, $page->ids('no_bill'));

        // ยกเลิก = บิลยกเลิกจริง + ปฏิเสธจากแอป SMS Checker — ไม่มีคนที่ปฏิเสธตั้งแต่เมนูปน
        $this->assertSame([$s['cancelled_bill'], $s['rejected_from_app']], $page->ids('cancelled'));

        // ปิดเงียบ = ออกบิลแล้วไม่จ่าย (รวมใบยอด 0 ที่เคยจอง UPA / มี Stripe session)
        $this->assertSame([$s['abandoned_bill'], $s['upa_used'], $s['stripe']], $page->ids('abandoned'));

        // ยังไม่จ่าย — ไม่มีซากปน
        $this->assertEmpty(array_intersect($shells, $page->ids('unpaid')));

        // KPI: "บิลทั้งหมด" ตรงกับตาราง + บอกจำนวนที่ซ่อนไว้
        $stats = $page->stats();
        $this->assertSame(count($default), $stats['total']);
        $this->assertSame(count($shells), $stats['never_billed']);
    }
}
