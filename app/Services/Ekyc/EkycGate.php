<?php

namespace App\Services\Ekyc;

use App\Exceptions\FreshMarketException;
use App\Models\User;

/**
 * 🪪 ด่านยืนยันตัวตน (AI eKYC) กลาง — ตัดสินฝั่งเซิร์ฟเวอร์ ไม่ขึ้นกับช่องทาง (แอป / เว็บ / LINE) — 2026-10-13
 *
 * กฎเจ้าของระบบ (ตัดสินแล้ว ห้ามถามซ้ำ):
 *   1. ผู้ซื้อ: ต้องยืนยันตัวตนก่อนสั่งซื้อ "ทุกช่องทาง" — ยืนยันครั้งเดียวในแอป Thai Prompt APP แล้วสั่งผ่านเว็บ/LINE ได้ด้วย
 *      ยังไม่ยืนยัน → เว็บ/LINE บอกให้ไปยืนยันในแอป (ลิงก์ /app/download)
 *   2. ร้านตลาดสด: สมัครได้ทุกช่องทาง แต่จะ "ขายได้" (ผู้ซื้อเห็นร้าน + รับออเดอร์) เมื่อเจ้าของร้านยืนยันตัวตนแล้ว
 *      แอดมินกดยืนยันร้านเองในหลังบ้านได้ (override)
 *
 * สวิตช์รวม: config('ekyc.enforce') — ปิด = ไม่บังคับทั้งผู้ซื้อและร้าน (สวิตช์ฉุกเฉินตอนบริการ AI ล่ม)
 *
 * ไม่บังคับกับ: POS หน้าร้าน (routes/pos.php), ออเดอร์ที่แอดมิน/ระบบสร้าง, เติมเงิน wallet, ดูดวง
 *
 * @example
 * if (EkycGate::buyerBlocked($user)) {
 *     throw EkycGate::freshMarketException();
 * }
 */
final class EkycGate
{
    /** รหัสข้อผิดพลาดเดียวกับ middleware `ekyc.verified` (แอปเปิดหน้ายืนยันตัวตนเมื่อเจอรหัสนี้) */
    public const CODE = 'KYC_REQUIRED';

    /** ผู้ซื้อยังไม่ยืนยันตัวตน (ข้อความกลาง — เว็บ/LINE ต่อท้ายลิงก์ดาวน์โหลดแอปเอง) */
    public const BUYER_MESSAGE = 'กรุณายืนยันตัวตนในแอป Thai Prompt APP ก่อนสั่งซื้อ (ทำครั้งเดียว แล้วสั่งผ่านเว็บและ LINE ได้ด้วย)';

    /** แอปรุ่นเก่าที่ยังไม่มีหน้ายืนยันตัวตน */
    public const UPDATE_APP_MESSAGE = 'กรุณาอัปเดตแอปเป็นรุ่นล่าสุด แล้วยืนยันตัวตนก่อนสั่งซื้อ';

    /** ร้านตลาดสดที่เจ้าของยังไม่ยืนยันตัวตน */
    public const SELLER_PENDING_KYC_MESSAGE = 'ร้านจะเริ่มขายได้เมื่อยืนยันตัวตนในแอปแล้ว';

    /** คีย์ flash ของเว็บ — layout แสดงแถบ "ยืนยันตัวตนในแอป" พร้อมปุ่มดาวน์โหลด (components/ekyc-required-banner) */
    public const FLASH_KEY = 'ekyc_required';

    /**
     * เปิดบังคับยืนยันตัวตนอยู่ไหม (config/ekyc.php → enforce)
     */
    public static function enforced(): bool
    {
        return filter_var(config('ekyc.enforce', true), FILTER_VALIDATE_BOOL);
    }

    /**
     * ผู้ใช้คนนี้ถูกกันไม่ให้สั่งซื้อไหม (ยังไม่ล็อกอิน = false → ให้ด่าน auth ตัดสินเอง)
     */
    public static function buyerBlocked(?User $user): bool
    {
        return self::enforced() && $user !== null && ! $user->isKycVerified();
    }

    /**
     * ลิงก์ดาวน์โหลดแอป Thai Prompt APP (หน้า /app/download)
     */
    public static function downloadUrl(): string
    {
        try {
            return route('app.download');
        } catch (\Throwable $e) {
            return url('/app/download');
        }
    }

    /**
     * ข้อความสำหรับเว็บ (ใส่ลิงก์ไว้ในข้อความด้วย — หน้าที่ไม่มีแถบดาวน์โหลดก็ยังรู้ว่าต้องไปที่ไหน)
     */
    public static function buyerWebMessage(): string
    {
        return self::BUYER_MESSAGE.' ดาวน์โหลดแอปได้ที่ '.self::downloadUrl();
    }

    /**
     * ข้อความตอบกลับใน LINE (reply เท่านั้น — ห้าม push) · LINE ทำลิงก์ในข้อความให้กดได้เอง
     */
    public static function buyerLineMessage(): string
    {
        return '⚠️ '.self::BUYER_MESSAGE."\n\n📲 ดาวน์โหลดแอปแล้วกดยืนยันตัวตน (ใช้เวลาประมาณ 1 นาที):\n".self::downloadUrl();
    }

    /**
     * ข้อผิดพลาดของตลาดสดเมื่อผู้ซื้อยังไม่ยืนยันตัวตน (controller/LINE แสดงข้อความได้ทันที)
     */
    public static function freshMarketException(): FreshMarketException
    {
        return FreshMarketException::make(self::CODE, self::BUYER_MESSAGE, 422);
    }
}
