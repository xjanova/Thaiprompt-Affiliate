<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * 🔨 ล็อก "ลำดับด่าน" ใน BillTrollGuardService::maybeBanAfterUnpaidCancel()
 *
 * 🚨 ทำไมต้องมีเทสต์นี้ (2026-09-08):
 *   วันที่แก้ให้ quiz-gating แบนชั่วคราวแทนที่จะ `return` เปล่า ผมย้ายด่าน
 *   `troll_warning_shown` ขึ้นมาข้างบน — และเกือบทับเส้น `quiz_gate_accepted`
 *   ซึ่ง **จงใจ** ให้ข้ามด่านคำเตือน (คนที่กดยอมรับกติกา 5 ข้อเห็น contract ชัดกว่า)
 *
 *   ถ้าสลับลำดับผิด: คนที่ยอมรับ quiz แล้วไม่จ่าย จะหลุดออกทาง `return` ของด่านคำเตือน
 *   ⇒ เส้นแบนที่ทำงานได้เส้นเดียวในระบบตายเงียบ โดยไม่มีเทสต์ไหนแดง
 *
 *   ลำดับที่ถูกต้อง (ห้ามสลับ):
 *     1. quiz_gate_accepted     → แบนชั่วคราวตาม contract (ข้ามด่านคำเตือน + ข้าม strike)
 *     2. troll_warning_shown    → ไม่เคยเตือน = ไม่แบน
 *     3. strikeCount >= 3
 *     4. quizGating             → แบนชั่วคราว (ห้ามถาวร)
 *     5. legacy                 → แบนถาวร
 *
 * ⚠️ อ่าน source ตรงๆ แทนการรัน — เมธอดนี้แตะ DB + ส่งข้อความจริง
 *    เครื่อง dev ที่ไม่มี MySQL รันได้ ([[rule_verification_script_can_lie]] — ตรวจของจริง ไม่ใช่ของที่เดา)
 */
class BillTrollGuardOrderTest extends TestCase
{
    protected function methodSource(): string
    {
        $path = __DIR__.'/../../../app/Services/Fortune/BillTrollGuardService.php';
        $this->assertFileExists($path, 'ไม่เจอ BillTrollGuardService');

        $src = file_get_contents($path);
        $start = strpos($src, 'function maybeBanAfterUnpaidCancel');
        $this->assertNotFalse($start, 'ไม่เจอเมธอด maybeBanAfterUnpaidCancel');

        $end = strpos($src, 'function banForUnpaidStrikes', $start);
        $this->assertNotFalse($end, 'ไม่เจอเมธอด banForUnpaidStrikes (เส้นแบนชั่วคราวหายไป?)');

        return substr($src, $start, $end - $start);
    }

    public function test_เส้น_quiz_gate_accepted_ต้องมาก่อนด่านคำเตือน(): void
    {
        $src = $this->methodSource();

        $quizAccepted = strpos($src, "getConversationState('quiz_gate_accepted')");
        $warningShown = strpos($src, "getConversationState('troll_warning_shown')");

        $this->assertNotFalse($quizAccepted, 'ด่าน quiz_gate_accepted หายไป');
        $this->assertNotFalse($warningShown, 'ด่าน troll_warning_shown หายไป');

        $this->assertLessThan(
            $warningShown,
            $quizAccepted,
            'quiz_gate_accepted ต้องมาก่อน troll_warning_shown — คนที่ยอมรับกติกาแล้วจงใจให้ข้ามด่านคำเตือน'
        );
    }

    public function test_สาขา_quiz_เปิด_ต้องแบนจริง_ไม่ใช่_return_เปล่า(): void
    {
        $src = $this->methodSource();

        $this->assertStringContainsString(
            'banForUnpaidStrikes(',
            $src,
            'สาขา quiz เปิด ต้องเรียกแบนชั่วคราว — ถ้ากลับไป return เปล่า คนเปิดบิลเล่นจะลอยนวลอีก (prod วัดได้ 12/12 ครั้ง)'
        );
    }

    public function test_ห้ามแบนถาวรอัตโนมัติเมื่อ_quiz_เปิด(): void
    {
        $src = $this->methodSource();

        $quizBranch = strpos($src, '$quizGating');
        $permanentBan = strpos($src, 'null, // null = ถาวร');

        $this->assertNotFalse($quizBranch, 'ตัวแปร $quizGating หายไป');

        if ($permanentBan !== false) {
            $this->assertLessThan(
                $permanentBan,
                $quizBranch,
                'สาขา quizGating ต้องอยู่ก่อนเส้นแบนถาวร — ไม่งั้น quiz เปิดแล้วยังแบนถาวรได้ ผิดกฎเจ้าของ'
            );
        }
    }
}
