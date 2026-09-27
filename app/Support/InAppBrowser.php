<?php

namespace App\Support;

/**
 * ตรวจว่าเปิดหน้าเว็บจากเบราว์เซอร์ฝังในแอป (LINE / Facebook / Instagram / Android WebView) หรือไม่
 *
 * Google ปฏิเสธการเข้าสู่ระบบจากเบราว์เซอร์ฝัง (403 disallowed_useragent) → ซ่อนปุ่ม Google
 * แล้วบอกให้เปิดหน้าใน Chrome/Safari แทน
 *
 * แอป Thai Prompt ใช้ Chrome Custom Tab / ASWebAuthenticationSession ซึ่ง UA เป็นของ Chrome/Safari ปกติ
 * (ไม่มี "; wv)" / Line/ / FBAN ...) → ไม่เข้าเงื่อนไขนี้ ปุ่ม Google ในแอปยังใช้ได้
 */
final class InAppBrowser
{
    /**
     * ร่องรอยใน User-Agent ของเบราว์เซอร์ฝัง
     *
     * - Line/        เบราว์เซอร์ในแอป LINE (เช่น "Line/13.19.1")
     * - FBAN / FBAV / FB_IAB  เบราว์เซอร์ในแอป Facebook / Messenger
     * - Instagram    เบราว์เซอร์ในแอป Instagram
     * - ; wv)        Android WebView ทั่วไป (Custom Tab ไม่มีคำนี้)
     */
    private const PATTERN = '/\bLine\/|FBAN|FBAV|FB_IAB|Instagram|; wv\)/';

    /**
     * User-Agent นี้เป็นเบราว์เซอร์ฝังในแอปหรือไม่
     */
    public static function isEmbedded(?string $userAgent): bool
    {
        if ($userAgent === null || $userAgent === '') {
            return false;
        }

        return preg_match(self::PATTERN, $userAgent) === 1;
    }

    /**
     * คำขอปัจจุบันมาจากเบราว์เซอร์ฝังในแอปหรือไม่
     */
    public static function current(): bool
    {
        try {
            return self::isEmbedded(request()->userAgent());
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * ข้อความแนะนำให้เปิดใน Chrome/Safari เพื่อใช้ Google
     */
    public const GOOGLE_HINT = 'ต้องการใช้บัญชี Google? กรุณาเปิดหน้านี้ใน Chrome หรือ Safari (กด ⋮ หรือ ··· แล้วเลือก "เปิดในเบราว์เซอร์")';
}
