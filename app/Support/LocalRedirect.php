<?php

namespace App\Support;

/**
 * 🔐 ปลายทางหลังล็อกอินด้วย LINE / Facebook / Google (?redirect=...) ต้องเป็นหน้าในเว็บเราเท่านั้น
 *
 * กัน open redirect: เดิม /auth/facebook?redirect=https://evil.example
 *   → ล็อกอินเสร็จเด้งไปเว็บปลอมได้ (ใช้ทำฟิชชิงต่อจากหน้าล็อกอินจริงของเรา)
 *
 * รับ:  "/user/wallet" · "/user/wallet?tab=x" · URL เต็มที่ host เป็นของเว็บเราเอง (ตัดเหลือ path)
 * ไม่รับ: host อื่น · "//evil" · "/\evil" · scheme อื่น (javascript:, thaiprompt:) · อักขระควบคุม
 *
 * ของที่ระบบส่งมาจริง (บอท LINE/FB) เป็น path ล้วนเสมอ เช่น /user/wallet → ผ่านเหมือนเดิม
 */
final class LocalRedirect
{
    /**
     * คืน path ภายในเว็บที่ปลอดภัย หรือ null ถ้าไม่ผ่านกติกา
     */
    public static function sanitize(mixed $target): ?string
    {
        if (! is_string($target)) {
            return null;
        }

        $target = trim($target);

        if ($target === '' || strlen($target) > 2000) {
            return null;
        }

        // อักขระควบคุม / backslash (บางเบราว์เซอร์ตีความ /\evil.com เป็น //evil.com)
        if (preg_match('/[\x00-\x1F\x7F\\\\]/', $target)) {
            return null;
        }

        // URL เต็ม → ต้องเป็น http(s) และ host ของเว็บเราเท่านั้น แล้วตัดเหลือ path + query
        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $target)) {
            $parts = parse_url($target);

            if ($parts === false || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
                return null;
            }

            if (! in_array(strtolower($parts['host'] ?? ''), self::ownHosts(), true)) {
                return null;
            }

            $target = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
        }

        // ต้องเป็น path ภายใน: ขึ้นต้นด้วย '/' ตัวเดียว
        if (! str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return null;
        }

        // path ที่ encode ไว้ ถอดแล้วต้องยังปลอดภัย (%2F%2F → //, %5C → \)
        $decoded = rawurldecode($target);
        if (str_starts_with($decoded, '//') || str_contains($decoded, '\\') || preg_match('/[\x00-\x1F\x7F]/', $decoded)) {
            return null;
        }

        return $target;
    }

    /**
     * host ของเว็บเรา (APP_URL + host ที่เบราว์เซอร์กำลังเปิดอยู่)
     *
     * @return array<int, string>
     */
    private static function ownHosts(): array
    {
        $hosts = [strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST))];

        try {
            $hosts[] = strtolower(request()->getHost());
        } catch (\Throwable) {
            // นอก HTTP request (console) — ใช้ APP_URL อย่างเดียว
        }

        return array_values(array_filter(array_unique($hosts)));
    }
}
