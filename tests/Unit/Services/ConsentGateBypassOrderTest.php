<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * 📋 ล็อก "ลำดับด่าน" ใน FortuneConsentGateTrait::consentGateOrNull()
 *
 * 🚨 ทำไมต้องมีเทสต์นี้ (2026-09-08):
 *   `consent_gate_bypass=1` เคยอยู่บรรทัดแรกและ `return null` ทันที ⇒ ปิดทั้ง 3 ด่านรวด
 *   (แบบสอบถาม 5 ข้อ + รหัสเสียง + กล่องกติกา) **ทั้งที่ทุกสวิตช์เปิดอยู่**
 *
 *   prod วัดได้: `enable_consent_quiz=1` แต่ log "แสดงกล่องกติกาก่อนสร้างบิล" = 0 ครั้ง/7 วัน
 *   ⇒ ด่านกันคนเปิดบิลเล่นตายสนิท และ BillTrollGuard ที่รอธง `quiz_gate_accepted`
 *      ก็ไม่มีวันได้ธงนั้น (ออกทางประตู "ข้าม" 12/12 ครั้ง)
 *
 *   ลำดับที่ถูกต้อง (ห้ามสลับ):
 *     1. hasPaidActiveReading  → ลูกค้าจ่ายแล้ว ห้ามโดนขวางเด็ดขาด
 *     2. $mustQuiz             → คำนวณก่อน bypass
 *     3. bypass && !$mustQuiz  → bypass ลด friction ให้ "ลูกค้าปกติ" เท่านั้น
 *     4. CONSENT_OK pull       → ต้องมาก่อนด่าน quiz ไม่งั้นตอบครบ 5 ข้อแล้วเด้งซ้ำไม่รู้จบ
 *     5. buildConsentQuizGate
 *
 * ⚠️ อ่าน source ตรงๆ — เมธอดนี้แตะ Cache/DB/settings จริง
 *    เครื่อง dev ที่ไม่มี MySQL รันได้
 */
class ConsentGateBypassOrderTest extends TestCase
{
    protected function methodSource(): string
    {
        $path = __DIR__.'/../../../app/Services/Fortune/FortuneConsentGateTrait.php';
        $this->assertFileExists($path, 'ไม่เจอ FortuneConsentGateTrait');

        $src = file_get_contents($path);
        $start = strpos($src, 'function consentGateOrNull');
        $this->assertNotFalse($start, 'ไม่เจอเมธอด consentGateOrNull');

        // จบที่เมธอดถัดไป
        $end = strpos($src, "\n    protected function ", $start + 10);
        $this->assertNotFalse($end, 'หาจุดจบเมธอดไม่ได้');

        return substr($src, $start, $end - $start);
    }

    public function test_bypass_ต้องไม่ปิดแบบสอบถามของคนค้างบิล(): void
    {
        $src = $this->methodSource();

        $this->assertStringContainsString(
            '$mustQuiz',
            $src,
            'ต้องคำนวณ $mustQuiz ไว้ตัดสินร่วมกับ bypass'
        );

        $this->assertMatchesRegularExpression(
            '/\$bypass\s*&&\s*!\s*\$mustQuiz/u',
            $src,
            'bypass ต้อง return เฉพาะตอน !$mustQuiz — ไม่งั้นคนเปิดบิลซ้ำๆ จะลอดด่านเหมือนเดิม'
        );
    }

    public function test_ลูกค้าจ่ายแล้วต้องถูกเช็คก่อน_bypass(): void
    {
        $src = $this->methodSource();

        $paid = strpos($src, 'hasPaidActiveReading');
        $bypass = strpos($src, 'consent_gate_bypass');

        $this->assertNotFalse($paid, 'ด่าน hasPaidActiveReading หายไป');
        $this->assertNotFalse($bypass, 'สวิตช์ consent_gate_bypass หายไป');

        $this->assertLessThan(
            $bypass,
            $paid,
            'ลูกค้าจ่ายเงินแล้วต้องถูกปล่อยผ่านก่อนเสมอ ไม่ว่า bypass จะเปิดหรือปิด'
        );
    }

    public function test_ธงยอมรับกติกาต้องถูกกินก่อนด่านแบบสอบถาม(): void
    {
        $src = $this->methodSource();

        $consentOk = strpos($src, 'CONSENT_OK_PREFIX');
        $quizGate = strpos($src, 'buildConsentQuizGate');

        $this->assertNotFalse($consentOk, 'ไม่เจอการ pull CONSENT_OK');
        $this->assertNotFalse($quizGate, 'ไม่เจอ buildConsentQuizGate');

        $this->assertLessThan(
            $quizGate,
            $consentOk,
            'ต้อง pull CONSENT_OK ก่อนสร้างแบบสอบถาม — ไม่งั้นตอบครบ 5 ข้อแล้วเด้งคำถามซ้ำไม่รู้จบ'
        );
    }

    public function test_bypass_เปิดแล้วแบบสอบถามล้ม_ต้องปล่อยผ่าน_ไม่ใช้ด่านสำรอง(): void
    {
        $src = $this->methodSource();

        $quizGate = strpos($src, 'buildConsentQuizGate');
        $audioGate = strpos($src, 'shouldUseAudioCode');

        $this->assertNotFalse($quizGate);
        $this->assertNotFalse($audioGate);

        $between = substr($src, $quizGate, $audioGate - $quizGate);

        $this->assertMatchesRegularExpression(
            '/if\s*\(\s*\$bypass\s*\)/u',
            $between,
            'ระหว่างด่านแบบสอบถามกับด่านรหัสเสียง ต้องมีทางออกของ bypass — '
            .'ไม่งั้น bypass ที่เจ้าของตั้งใจเปิด จะถูกด่านสำรองแอบเปิดแทน'
        );
    }
}
