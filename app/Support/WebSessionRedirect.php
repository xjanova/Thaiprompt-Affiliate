<?php

namespace App\Support;

/**
 * 🔐 กติกาปลายทางของ "เปิดเว็บแบบล็อกอินจากแอป" (/mobile-web-session)
 *
 * กันช่องโหว่ open redirect + login CSRF (audit PLAY-16 / SHOP-15):
 *   เดิมแอปส่ง redirect_path อะไรก็ได้ → ผู้โจมตีออก token ของบัญชีตัวเองพร้อม
 *   redirect_path = https://evil.example แล้วส่งลิงก์ main.thaiprompt.online ให้เหยื่อ
 *
 * กติกา (ใช้ทั้งตอนออก token และตอนใช้ token — ตรวจซ้ำสองชั้น):
 *   - ต้องเป็น path ภายในเว็บเท่านั้น: ขึ้นต้นด้วย '/' แต่ห้าม '//' ห้ามมี scheme/host/backslash
 *   - ต้องอยู่ใต้ prefix ที่อนุญาต (ALLOWED_PREFIXES) แบบเต็ม segment (/user ✓, /username ✗)
 *   - ห้ามมี '..' / อักขระควบคุม
 *   - query string ของปลายทางต้องมาจากตอนออก token เท่านั้น และคีย์/ค่าต้องผ่าน sanitizeQuery()
 *
 * คลาสนี้ไม่แตะ DB/Request — เทสต์ได้ล้วนๆ (tests/Feature/Platform/WebSessionRedirectRulesTest.php)
 */
final class WebSessionRedirect
{
    /** ปลายทางเริ่มต้นเมื่อแอปไม่ส่ง redirect_path มา */
    public const DEFAULT_PATH = '/user/wallet/topup';

    /** อายุ token (วินาที) */
    public const TTL_SECONDS = 300;

    /**
     * พื้นที่ที่แอปเปิดเว็บแบบล็อกอินให้ได้
     *
     * @var array<int, string>
     */
    public const ALLOWED_PREFIXES = [
        '/user',
        '/seller',
        '/taladsod',
        '/shop',
        '/storefront',
        '/wallet',
        '/account',
    ];

    /** จำนวน query param สูงสุดที่แอปแนบได้ */
    private const MAX_QUERY_PARAMS = 10;

    /**
     * ตรวจและทำให้ path ปลอดภัย — คืน null ถ้าไม่ผ่านกติกา
     *
     * path ที่มี query string ติดมา (/user/wallet?tab=x) จะถูกแยก query ออก
     * แล้วคืนเฉพาะ path (query ต้องส่งผ่าน query_params และผ่าน sanitizeQuery() เท่านั้น)
     */
    public static function sanitizePath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $path = trim($path);

        if ($path === '' || mb_strlen($path) > 255) {
            return null;
        }

        // อักขระควบคุม / backslash (บางเบราว์เซอร์ตีความ /\evil.com เป็น //evil.com)
        if (preg_match('/[\x00-\x1F\x7F\\\\]/', $path)) {
            return null;
        }

        // ต้องเป็น path ภายใน: ขึ้นต้น '/' ตัวเดียว
        if (! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        // ตัด fragment + query ออก เหลือ path ล้วน
        $pathOnly = explode('#', $path, 2)[0];
        $pathOnly = explode('?', $pathOnly, 2)[0];

        // path ที่ encode ไว้ต้องถอดแล้วยังปลอดภัย (%2F%2F, %5C, %2E%2E)
        $decoded = rawurldecode($pathOnly);
        if (str_starts_with($decoded, '//') || str_contains($decoded, '\\') || preg_match('/[\x00-\x1F\x7F]/', $decoded)) {
            return null;
        }

        // ห้ามมี scheme ปนมา (เช่น /user/../https://x) และห้ามถอยโฟลเดอร์
        if (str_contains($decoded, '://') || preg_match('#(^|/)\.\.?(/|$)#', $decoded)) {
            return null;
        }

        // parse_url ต้องไม่เห็น host/scheme
        $parts = parse_url($pathOnly);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return null;
        }

        // ต้องอยู่ใต้ prefix ที่อนุญาต แบบเต็ม segment
        $normalized = '/'.ltrim($pathOnly, '/');
        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if ($normalized === $prefix || str_starts_with($normalized, $prefix.'/')) {
                return rtrim($normalized, '/') ?: $prefix;
            }
        }

        return null;
    }

    /**
     * กรอง query params ที่แอปแนบมา (เช่น ['amount' => 1000])
     *
     * คีย์ต้องเป็น a-z0-9_ ยาวไม่เกิน 32, ค่าเป็น scalar ยาวไม่เกิน 200 ตัวอักษร, ไม่เกิน 10 คู่
     * ค่าที่ไม่ผ่านจะถูกทิ้งเงียบๆ (ไม่ทำให้ทั้งคำขอล้ม)
     *
     * @return array<string, string>
     */
    public static function sanitizeQuery(mixed $params): array
    {
        if (! is_array($params)) {
            return [];
        }

        $clean = [];
        foreach ($params as $key => $value) {
            if (count($clean) >= self::MAX_QUERY_PARAMS) {
                break;
            }

            if (! is_string($key) || ! preg_match('/^[A-Za-z0-9_]{1,32}$/', $key)) {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            if (! is_scalar($value)) {
                continue;
            }

            $value = (string) $value;
            if ($value === '' || mb_strlen($value) > 200 || preg_match('/[\x00-\x1F\x7F]/', $value)) {
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    /**
     * ประกอบปลายทางสุดท้าย (path ที่ผ่านกติกาแล้ว + query ที่กรองแล้ว)
     *
     * @param  array<string, string>  $query
     */
    public static function buildTarget(string $safePath, array $query = []): string
    {
        return empty($query) ? $safePath : $safePath.'?'.http_build_query($query);
    }

    /**
     * ปกปิดอีเมลสำหรับแสดงบนหน้ายืนยัน เช่น somchai@gmail.com → so****@gmail.com
     */
    public static function maskEmail(?string $email): string
    {
        $email = (string) $email;
        if (! str_contains($email, '@')) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible.str_repeat('*', max(3, mb_strlen($local) - mb_strlen($visible))).'@'.$domain;
    }
}
