<?php

namespace App\Services\Fortune;

use Illuminate\Support\Facades\Log;

/**
 * 🔎 (2026-08-29, owner "ของดีนี่ เช็คได้ก่อน ส่งไปเช็ค") อ่าน QR ในสลิปด้วยตัวเอง ก่อนส่งไป SlipOK
 *
 * ทำไมถึงคุ้ม: เลขอ้างอิงรายการ (transRef) **ฝังอยู่ในตัว QR ของสลิป** อยู่แล้ว
 *   qrcodeData : 0041 0006 000001 0103 004 0220 016240234342DTF05267 5102TH 910487A7
 *   transRef   :                            └──────────────────────┘
 *   → ถอด QR เองได้ = รู้เลขอ้างอิงโดยไม่ต้องจ่ายเครดิตถาม SlipOK
 *   → ถ้าเลขนี้เคยใช้ไปแล้ว (มีใน slip_verifications) ตอบได้เลยว่า "สลิปใบนี้ใช้แล้ว" ฟรีๆ
 *
 * 🛡️ หลักการที่ห้ามละเมิด — **fail-open เสมอ**
 *   ใช้ผลลัพธ์ "เชิงบวก" เท่านั้น (ถอดได้ + เลขตรงกับที่เคยใช้ = ปฏิเสธได้)
 *   ห้ามใช้ผลลัพธ์ "เชิงลบ" (ถอดไม่ได้ ≠ ไม่ใช่สลิป) — ตัวถอด PHP ล้วนอ่อนกว่าระบบของ SlipOK
 *   สลิปจริงที่ถ่ายจากจอ/แสงสะท้อน/ความละเอียดต่ำ อาจถอดไม่ออก → ต้องปล่อยผ่านไปให้ SlipOK ตัดสิน
 *   (บล็อกผิด = ลูกค้าจ่ายเงินแล้วไม่ได้ของ ผิดกฎข้อสำคัญที่สุด)
 */
class SlipQrReader
{
    /** ไม่ถอด QR กับไฟล์ใหญ่เกินนี้ (ไบต์) — กันกิน CPU ในจังหวะ webhook */
    public const MAX_BYTES = 6 * 1024 * 1024;

    /**
     * ย่อด้านยาวลงเหลือกี่พิกเซลก่อนถอด QR
     *
     * ⚡ วัดจริงบนสกรีนช็อตมือถือ 1080×2400: ถอดตรงๆ ใช้ **1.7-3.1 วินาที** = ช้าเกินไป
     *    สำหรับจังหวะ webhook (flow นี้ทำงาน synchronous) → ย่อก่อนแล้วค่อยถอด
     *    QR ไม่ต้องการความละเอียดเต็ม ย่อแล้วยังอ่านออก แต่เร็วขึ้นหลายเท่า
     */
    public const MAX_EDGE = 1000;

    /** เพดานจำนวนพิกเซลที่ยอมคลายลงหน่วยความจำ (~24MP) — ใหญ่กว่านี้ไม่ย่อ ไม่ถอด */
    public const MAX_PIXELS = 24_000_000;

    /** เพดานจำนวนผู้ต้องสงสัยที่ส่งไปเทียบฐานข้อมูล — กัน payload ปลอมยัดค่ามาเป็นร้อย */
    public const MAX_CANDIDATES = 25;

    /**
     * ความยาวขั้นต่ำของค่าที่จะถือเป็น "ผู้ต้องสงสัยว่าเป็นเลขอ้างอิง"
     * transRef จริงยาว 15-25 ตัว — ตัดค่าสั้นๆ อย่าง 'TH' / '004' ทิ้ง กัน match มั่ว
     */
    public const MIN_REF_LENGTH = 10;

    /**
     * 📖 ถอดข้อความใน QR จากไบต์รูป — คืน null ถ้าอ่านไม่ได้ (ทุกกรณี)
     */
    public function payloadFromBytes(?string $bytes): ?string
    {
        if (empty($bytes) || strlen($bytes) > self::MAX_BYTES) {
            return null;
        }

        // ไม่มี package ถอด QR (ยังไม่ได้ติดตั้ง / ติดตั้งพลาด) → ปล่อยผ่านเงียบๆ
        if (! class_exists(\Zxing\QrReader::class)) {
            return null;
        }

        if (! extension_loaded('gd')) {
            return null;
        }

        try {
            $bytes = $this->shrinkForDecode($bytes);

            $reader = new \Zxing\QrReader($bytes, \Zxing\QrReader::SOURCE_TYPE_BLOB);
            $text = $reader->text();

            if (! is_string($text) || trim($text) === '') {
                return null;
            }

            return trim($text);
        } catch (\Throwable $e) {
            // ถอดไม่ได้ = เรื่องปกติ (รูปเบลอ/ไม่ใช่สลิป/ลิบเรอรีสะดุด) → ไม่ใช่ error ที่ต้องดัง
            Log::debug('🔎 SlipQrReader: ถอด QR ไม่สำเร็จ (ปล่อยผ่านให้ SlipOK ตัดสิน)', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * ⚡ ย่อรูปก่อนถอด QR — คืนไบต์เดิมถ้าย่อไม่ได้/ไม่จำเป็น (ห้าม throw)
     *
     * สกรีนช็อตมือถือด้านยาว 2400px ทำให้ตัวถอดใช้เวลาหลักวินาที ย่อเหลือ 1000px
     * เร็วขึ้นหลายเท่าโดยยังอ่าน QR ออก (QR ในสลิปกินพื้นที่ ~40-50% ของความกว้าง)
     */
    protected function shrinkForDecode(string $bytes): string
    {
        $info = @getimagesizefromstring($bytes);
        if ($info === false) {
            return $bytes;
        }

        [$w, $h] = $info;
        $long = max((int) $w, (int) $h);
        if ($long <= self::MAX_EDGE || $long <= 0) {
            return $bytes;
        }

        // 🧠 กันหน่วยความจำระเบิด — รูปกล้องความละเอียดสูงคลายเป็น truecolor กินราว 4 ไบต์/พิกเซล
        //   (12MP ≈ 48MB) ถ้าใหญ่เกินเพดานนี้ ไม่คุ้มเสี่ยง → ปล่อยผ่านไปให้ SlipOK ตัดสิน
        if (((int) $w * (int) $h) > self::MAX_PIXELS) {
            return $bytes;
        }

        $src = @imagecreatefromstring($bytes);
        if ($src === false) {
            return $bytes;
        }

        $dst = null;
        $buffered = false;
        try {
            $ratio = self::MAX_EDGE / $long;
            $nw = max(1, (int) round($w * $ratio));
            $nh = max(1, (int) round($h * $ratio));

            $dst = imagescale($src, $nw, $nh, IMG_BICUBIC);
            if ($dst === false) {
                return $bytes;
            }

            ob_start();
            $buffered = true;
            $ok = imagepng($dst, null, 1); // ระดับบีบ 1 = เร็วสุด (อยู่ในหน่วยความจำ ไม่แคร์ขนาดไฟล์)
            $out = (string) ob_get_clean();
            $buffered = false;

            return ($ok && $out !== '') ? $out : $bytes;
        } catch (\Throwable $e) {
            return $bytes;
        } finally {
            // ⚠️ ถ้า imagepng โยน exception กลางคัน buffer จะค้าง → ไปปนกับ response ของ webhook
            //    ต้องปิดให้แน่ใจเสมอ ไม่ว่าออกทางไหน
            if ($buffered) {
                ob_end_clean();
            }
            if ($dst !== false && $dst !== null) {
                imagedestroy($dst);
            }
            imagedestroy($src);
        }
    }

    /**
     * 🧩 ดึง "ค่าที่อาจเป็นเลขอ้างอิงรายการ" ออกจาก payload ของ QR
     *
     * QR สลิปไทยเป็น EMVCo TLV (tag 2 หลัก + ความยาว 2 หลัก + ค่า) และซ้อนชั้นได้
     * แต่ละธนาคารวางเลขอ้างอิงคนละ tag (KBank เจอที่ 00→02, เจ้าอื่นรูปแบบต่างออกไป)
     * → ไม่ผูกกับ tag ใด ๆ : แกะทุกใบของต้นไม้ TLV ออกมาเป็นผู้ต้องสงสัย แล้วให้ชั้นบน
     *   เอาไปเทียบกับ trans_ref ที่เรามีจริงในฐานข้อมูล (ตรงเป๊ะเท่านั้นถึงนับ)
     *
     * @return array<int, string> รายการค่าที่อาจเป็นเลขอ้างอิง (unique, ยาวพอ)
     */
    public function candidateRefs(?string $payload): array
    {
        if (empty($payload)) {
            return [];
        }

        $found = [];
        $this->walkTlv($payload, $found, 0);

        // เผื่อธนาคารที่ QR ไม่ใช่ TLV — ใส่ทั้งก้อนเป็นผู้ต้องสงสัยด้วย
        $whole = trim($payload);
        if ($this->looksLikeRef($whole)) {
            $found[] = $whole;
        }

        return array_slice(array_values(array_unique($found)), 0, self::MAX_CANDIDATES);
    }

    /**
     * เดินต้นไม้ TLV เก็บค่าใบที่ "หน้าตาเหมือนเลขอ้างอิง"
     *
     * @param  array<int, string>  $found
     */
    protected function walkTlv(string $s, array &$found, int $depth): void
    {
        // ซ้อนลึกเกิน = ข้อมูลเพี้ยน/วนซ้ำ → หยุด (กัน stack ระเบิดจาก payload ปลอม)
        if ($depth > 4) {
            return;
        }

        $len = strlen($s);
        $i = 0;

        while ($i + 4 <= $len) {
            $tag = substr($s, $i, 2);
            $lenStr = substr($s, $i + 2, 2);

            // TLV ต้องเป็นตัวเลขล้วนทั้ง tag และความยาว — ไม่ใช่ = ไม่ต้องแกะต่อ
            if (! ctype_digit($tag) || ! ctype_digit($lenStr)) {
                return;
            }

            $vLen = (int) $lenStr;
            if ($vLen <= 0 || $i + 4 + $vLen > $len) {
                return;
            }

            $value = substr($s, $i + 4, $vLen);

            if ($this->looksLikeRef($value)) {
                $found[] = $value;
            }

            // ค่าที่ยาวพอและเป็น TLV ซ้อน → แกะต่อ
            if ($vLen >= 8) {
                $this->walkTlv($value, $found, $depth + 1);
            }

            $i += 4 + $vLen;
        }
    }

    /** หน้าตาเหมือนเลขอ้างอิงไหม — ยาวพอ + เป็นตัวอักษร/ตัวเลขล้วน */
    protected function looksLikeRef(string $v): bool
    {
        return strlen($v) >= self::MIN_REF_LENGTH && ctype_alnum($v);
    }
}
