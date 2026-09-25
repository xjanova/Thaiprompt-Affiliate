<?php

namespace App\Support\Shop;

/**
 * ค่าวิธีชำระเงินของออเดอร์ร้านค้า (orders.payment_method) — แหล่งเดียวของการแปลงชื่อ
 *
 * เดิมแต่ละจุดเขียนค่าไม่เหมือนกัน (เว็บ 'cash_on_delivery', แอป 'bank'/'card') แต่คอลัมน์เป็น enum
 * ทำให้ insert พังใน strict mode และ OrderObserver ที่เช็ค 'cod' ไม่เคยทำงานกับออเดอร์จากเว็บ
 * → ทุกจุดที่เขียน orders.payment_method ต้องผ่าน normalize() (Order มี mutator เรียกให้อัตโนมัติ)
 */
final class PaymentMethod
{
    public const WALLET = 'wallet';

    public const PROMPTPAY = 'promptpay';

    public const BANK_TRANSFER = 'bank_transfer';

    public const CREDIT_CARD = 'credit_card';

    public const COD = 'cod';

    public const PAYSOLUTIONS = 'paysolutions';

    public const COINS = 'coins';

    /** ค่าที่คอลัมน์ orders.payment_method รับได้ (ตรงกับ migration 2026_09_25_170000) */
    public const ORDER_VALUES = [
        self::PROMPTPAY,
        self::BANK_TRANSFER,
        self::CREDIT_CARD,
        self::COD,
        self::WALLET,
        self::PAYSOLUTIONS,
        self::COINS,
    ];

    /** วิธีชำระที่แอปมือถือเลือกได้ตอน checkout */
    public const APP_CHECKOUT_VALUES = [self::WALLET, self::PROMPTPAY, self::COD];

    /** ชื่อเรียกอื่นที่เคยใช้ → ค่ามาตรฐาน */
    private const ALIASES = [
        'cash_on_delivery' => self::COD,
        'cash-on-delivery' => self::COD,
        'cashondelivery' => self::COD,
        'bank' => self::BANK_TRANSFER,
        'transfer' => self::BANK_TRANSFER,
        'card' => self::CREDIT_CARD,
        'credit' => self::CREDIT_CARD,
        'qr' => self::PROMPTPAY,
        'prompt_pay' => self::PROMPTPAY,
    ];

    /**
     * provider ของ PaymentService ที่ใช้ชื่อไม่ตรงกับค่าในออเดอร์
     * (PaymentService ลงทะเบียน COD ไว้ในชื่อ 'cash_on_delivery')
     */
    private const PROVIDER_KEYS = [
        self::COD => 'cash_on_delivery',
    ];

    private function __construct() {}

    /**
     * แปลงเป็นค่ามาตรฐาน (ไม่รู้จัก → คืนค่าตัวพิมพ์เล็กเดิม ให้ validator เป็นคนปฏิเสธ)
     */
    public static function normalize(?string $method): ?string
    {
        if ($method === null) {
            return null;
        }

        $clean = strtolower(trim($method));
        if ($clean === '') {
            return null;
        }

        return self::ALIASES[$clean] ?? $clean;
    }

    /**
     * ค่านี้บันทึกลง orders.payment_method ได้หรือไม่ (หลัง normalize)
     */
    public static function isOrderValue(?string $method): bool
    {
        $normalized = self::normalize($method);

        return $normalized !== null && in_array($normalized, self::ORDER_VALUES, true);
    }

    /**
     * ชื่อ provider ที่ PaymentService ใช้ (เช่น cod → cash_on_delivery)
     */
    public static function providerKey(string $method): string
    {
        $normalized = self::normalize($method) ?? $method;

        return self::PROVIDER_KEYS[$normalized] ?? $normalized;
    }

    /**
     * ชื่อภาษาไทยสำหรับแสดงผล
     */
    public static function labelTh(?string $method): string
    {
        return match (self::normalize($method)) {
            self::WALLET => 'กระเป๋าเงิน',
            self::PROMPTPAY => 'พร้อมเพย์ (สแกน QR)',
            self::BANK_TRANSFER => 'โอนเงินผ่านธนาคาร',
            self::CREDIT_CARD => 'บัตรเครดิต/เดบิต',
            self::COD => 'เก็บเงินปลายทาง',
            self::PAYSOLUTIONS => 'PaySolutions',
            self::COINS => 'เหรียญ',
            default => 'ไม่ระบุ',
        };
    }
}
