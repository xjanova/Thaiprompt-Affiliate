<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\SlipQrReader;
use App\Services\FortuneConversationService;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use PHPUnit\Framework\TestCase;

/**
 * 🔎 แยก "สลิป" ออกจาก "รูปอื่น" โดยไม่ใช้ AI — ถอด QR ตรวจสลิปในเครื่อง
 *
 * (2026-09-27) owner: "ทำไงให้บอทแยกได้ว่าอันไหนสลิป หรือภาพ โดยไม่เปิดโหมดดูรูปของบอท"
 *   → returningImageLooksLikeSlip() ลองถอด QR สลิปก่อน เจอ = สลิป ไม่ต้องจ่ายเงินถาม AI
 *   (prod 20-27 ก.ย. AI pre-check ถูกเรียก 0-6 ครั้ง/วัน ตอบ payment_slip 0.99 ทุกครั้ง)
 *
 * ไม่แตะ DB — เมธอดที่เทสต์ไม่ใช้ settings จึงสร้าง service แบบไม่เรียก constructor
 */
class SlipQrFirstTest extends TestCase
{
    /** เลขอ้างอิงรายการ 20 หลัก (ตัวอย่างเดียวกับใน SlipQrReader) */
    private const TRANS_REF = '016240234342DTF05267';

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('gd') || ! class_exists(\Zxing\QrReader::class)) {
            $this->markTestSkipped('ต้องมี gd + khanamiryan/qrcode-detector-decoder');
        }
    }

    /** payload QR ตรวจสลิป: tag 00 (id 000001 · ธนาคารผู้โอน 004 · เลขอ้างอิง) + 51 TH + 91 CRC */
    private static function slipPayload(): string
    {
        $inner = '0006000001'.'0103004'.'0220'.self::TRANS_REF;

        return '00'.sprintf('%02d', strlen($inner)).$inner.'5102TH'.'910487A7';
    }

    private static function qrPng(string $payload): string
    {
        return (new Writer(new GDLibRenderer(400)))->writeString($payload);
    }

    private static function blankPng(): string
    {
        $img = imagecreatetruecolor(300, 300);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        ob_start();
        imagepng($img);
        imagedestroy($img);

        return (string) ob_get_clean();
    }

    private function callProtected(string $method, array $args): mixed
    {
        $svc = (new \ReflectionClass(FortuneConversationService::class))->newInstanceWithoutConstructor();
        $m = new \ReflectionMethod(FortuneConversationService::class, $method);
        $m->setAccessible(true);

        return $m->invokeArgs($svc, $args);
    }

    public function test_ถอด_q_r_สลิปจากรูปได้จริง(): void
    {
        $reader = new SlipQrReader;
        $payload = $reader->payloadFromBytes(self::qrPng(self::slipPayload()));

        $this->assertSame(self::slipPayload(), $payload);
        $this->assertContains(self::TRANS_REF, $reader->candidateRefs($payload));
    }

    public function test_รูปมี_q_r_สลิป_นับเป็นสลิป(): void
    {
        $this->assertTrue($this->callProtected('imageHasSlipQr', [self::qrPng(self::slipPayload())]));
    }

    public function test_q_r_พร้อมเพย์ของบิลเรา_ไม่ใช่สลิป(): void
    {
        // ลูกค้าแคป QR บิลที่เราส่งไปแล้วส่งกลับมา — ต้องไม่นับเป็นสลิป (EMVCo ขึ้นต้น 000201)
        $promptPay = '00020101021229370016A000000677010111011300668123456785802TH530376454053916630412AB';

        $this->assertFalse($this->callProtected('imageHasSlipQr', [self::qrPng($promptPay)]));
    }

    public function test_รูปไม่มี_q_r_ไม่นับเป็นสลิป(): void
    {
        $this->assertFalse($this->callProtected('imageHasSlipQr', [self::blankPng()]));
    }

    public function test_ทางลัด_รับ_base64_ได้ทั้งแบบดิบและ_data_uri(): void
    {
        $b64 = base64_encode(self::qrPng(self::slipPayload()));

        $this->assertTrue($this->callProtected('slipQrFoundQuick', [null, $b64]));
        $this->assertTrue($this->callProtected('slipQrFoundQuick', [null, 'data:image/png;base64,'.$b64]));
        $this->assertFalse($this->callProtected('slipQrFoundQuick', [null, base64_encode(self::blankPng())]));
        $this->assertFalse($this->callProtected('slipQrFoundQuick', [null, null]));
    }

    public function test_ด่าน_q_r_ต้องมาก่อนเรียก_ai(): void
    {
        // checkout บน Windows อาจเป็น CRLF — แปลงก่อนหาปีกกาปิดเมธอด
        $src = str_replace("\r\n", "\n", (string) file_get_contents(__DIR__.'/../../../app/Services/FortuneConversationService.php'));
        $start = strpos($src, 'protected function returningImageLooksLikeSlip');
        $this->assertNotFalse($start, 'ไม่เจอ returningImageLooksLikeSlip');

        $end = strpos($src, "\n    }\n", $start);
        $this->assertNotFalse($end, 'หาจุดจบเมธอดไม่เจอ');
        $body = substr($src, $start, $end - $start);

        $qr = strpos($body, '$this->slipQrFoundQuick(');
        $ai = strpos($body, 'ImageIntentClassifier(');

        $this->assertNotFalse($qr, 'ด่านถอด QR หายไป — สลิปจริงจะกลับไปจ่ายเงินถาม AI ทุกใบ');
        $this->assertNotFalse($ai, 'ด่าน AI หายไป (รูปที่ถอด QR ไม่ออกต้องยังถาม AI)');
        $this->assertLessThan($ai, $qr, 'ต้องลองถอด QR ก่อนเรียก AI');
    }
}
