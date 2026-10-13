<?php

namespace App\Services\TpixPay;

/**
 * คณิตศาสตร์ยอดเหรียญแบบไม่ใช้ float (bcmath) — wei = 10^-18 TPIX
 *
 * ❗ ห้ามใช้ float กับยอดเงิน/ยอดเหรียญที่ต้องเทียบตรงตัว (float ปัดเศษ ทำให้ยอด wei ไม่ตรงกับที่โอนจริง)
 */
final class TpixAmount
{
    public const DECIMALS = 18;

    private function __construct() {}

    /**
     * เลขฐาน 16 จาก JSON-RPC (เช่น "0x1bc16d674ec80000") → เลขฐาน 10 แบบ string
     */
    public static function hexToDec(?string $hex): string
    {
        $hex = strtolower(trim((string) $hex));
        if (str_starts_with($hex, '0x')) {
            $hex = substr($hex, 2);
        }
        $hex = ltrim($hex, '0');
        if ($hex === '') {
            return '0';
        }
        if (! ctype_xdigit($hex)) {
            throw new \InvalidArgumentException('ค่า hex ไม่ถูกต้อง');
        }

        $dec = '0';
        foreach (str_split($hex) as $char) {
            $dec = bcadd(bcmul($dec, '16', 0), (string) hexdec($char), 0);
        }

        return $dec;
    }

    /**
     * เลขฐาน 10 (string) → "0x…" สำหรับส่งเข้ากระเป๋า (eth_sendTransaction value)
     */
    public static function decToHex(string $dec): string
    {
        $dec = self::normalizeInteger($dec);
        if ($dec === '0') {
            return '0x0';
        }

        $hex = '';
        while (bccomp($dec, '0', 0) > 0) {
            $hex = dechex((int) bcmod($dec, '16')).$hex;
            $dec = bcdiv($dec, '16', 0);
        }

        return '0x'.$hex;
    }

    /**
     * wei → TPIX (ทศนิยม 18 ตำแหน่ง)
     */
    public static function weiToTpix(string $wei): string
    {
        return bcdiv(self::normalizeInteger($wei), bcpow('10', (string) self::DECIMALS, 0), self::DECIMALS);
    }

    /**
     * TPIX (string ทศนิยมไม่เกิน 18 ตำแหน่ง) → wei (ปัดทิ้งส่วนที่เกิน 18 ตำแหน่ง)
     */
    public static function tpixToWei(string $tpix): string
    {
        self::assertDecimal($tpix);

        return bcmul($tpix, bcpow('10', (string) self::DECIMALS, 0), 0);
    }

    /**
     * ปัดขึ้นที่ทศนิยม $decimals ตำแหน่ง (ค่าบวกเท่านั้น) — ลูกค้าจ่ายไม่ขาดแม้แต่เศษเล็กที่สุด
     */
    public static function ceil(string $value, int $decimals): string
    {
        self::assertDecimal($value);

        $truncated = bcadd($value, '0', $decimals);
        if (bccomp($truncated, $value, self::DECIMALS) < 0) {
            $step = $decimals > 0 ? '0.'.str_repeat('0', $decimals - 1).'1' : '1';
            $truncated = bcadd($truncated, $step, $decimals);
        }

        return $truncated;
    }

    /**
     * ยอดเหรียญสำหรับแสดงผล: คั่นหลักพัน ตัดศูนย์ท้าย คงทศนิยมอย่างน้อย $minDecimals ตำแหน่ง
     */
    public static function display(string $tpix, int $maxDecimals = 4, int $minDecimals = 2): string
    {
        self::assertDecimal($tpix);

        $fixed = bcadd($tpix, '0', $maxDecimals);
        [$int, $frac] = array_pad(explode('.', $fixed, 2), 2, '');
        $frac = rtrim($frac, '0');
        if (strlen($frac) < $minDecimals) {
            $frac = str_pad($frac, $minDecimals, '0');
        }

        $negative = str_starts_with($int, '-');
        $int = ltrim($int, '-');

        return ($negative ? '-' : '').self::groupThousands($int).($frac !== '' ? '.'.$frac : '');
    }

    /**
     * ค่าทศนิยมจาก float/int/string → string ที่ bcmath ใช้ได้ (ไม่มีรูปแบบ 1.0E-5)
     */
    public static function fromNumber(float|int|string $value, int $scale = 12): string
    {
        if (is_string($value)) {
            self::assertDecimal($value);

            return $value;
        }

        return rtrim(rtrim(number_format((float) $value, $scale, '.', ''), '0'), '.') ?: '0';
    }

    private static function groupThousands(string $int): string
    {
        $int = ltrim($int, '0') ?: '0';

        return strrev(implode(',', str_split(strrev($int), 3)));
    }

    private static function normalizeInteger(string $value): string
    {
        $value = trim($value);
        if (! preg_match('/^\d+$/', $value)) {
            throw new \InvalidArgumentException('ต้องเป็นจำนวนเต็มบวก');
        }

        return ltrim($value, '0') ?: '0';
    }

    private static function assertDecimal(string $value): void
    {
        if (! preg_match('/^-?\d+(\.\d+)?$/', trim($value))) {
            throw new \InvalidArgumentException('รูปแบบตัวเลขไม่ถูกต้อง');
        }
    }
}
