<?php

namespace App\Services;

use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Log;

/**
 * FortuneWebLinkService
 *
 * 🌐 (2026-07-24) Magic Link พาลูกค้าจากบอท FB/LINE ไปดูดวงบนเว็บ จันทรา.online
 *
 * หลักการ:
 * - บอท generate ลิงก์ /web-fortune/go?tk=<token> ต่อลูกค้าแต่ละคน
 * - token = base64url(JSON{p,u,exp}) . '.' . HMAC-SHA256(payload, APP_KEY)
 * - ลูกค้ากดลิงก์ → WebFortuneGatewayController ตรวจ token → หา/สร้าง User
 *   → Auth::login → redirect ไป SSO ของ juntraweb (Passport OAuth auto-approve)
 * - สวิตช์หลัก: enable_web_fortune_button (default OFF — ปิดอยู่ = ไม่มีปุ่ม/ลิงก์ใช้ไม่ได้)
 *
 * ความปลอดภัย:
 * - HMAC ผูก APP_KEY — ปลอม token ไม่ได้ถ้าไม่รู้ key
 * - มีอายุ (TOKEN_TTL_HOURS) — ลิงก์เก่าในแชทหมดอายุเอง
 * - hash_equals เทียบ signature (constant-time)
 */
class FortuneWebLinkService
{
    /** อายุ token (ชั่วโมง) — ลิงก์ค้างในแชทต้องหมดอายุได้ */
    public const TOKEN_TTL_HOURS = 72;

    /** URL SSO default ของเว็บจันทรา (จันทรา.online = xn--82c4af5bzdj.online) */
    public const DEFAULT_SSO_URL = 'https://xn--82c4af5bzdj.online/auth/thaiprompt/redirect';

    protected FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * เปิดใช้งานระบบปุ่มดูดวงบนเว็บหรือไม่ (default OFF)
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->settings->enable_web_fortune_button ?? false);
    }

    /**
     * URL ปลายทาง SSO ของเว็บจันทรา
     */
    public function getSsoUrl(): string
    {
        $url = trim((string) ($this->settings->web_fortune_sso_url ?? ''));

        return $url !== '' ? $url : self::DEFAULT_SSO_URL;
    }

    /**
     * สร้าง Magic Link สำหรับลูกค้าคนหนึ่ง (ผูก platform + user id + วันหมดอายุ)
     *
     * @param  string  $platform  'facebook' | 'line'
     * @param  string  $platformUserId  PSID (FB) หรือ LINE user id
     * @param  string|null  $to  🌳 (2026-07-25) path ปลายทางบนเว็บจันทรา (เช่น '/mlm' = ผังงาน)
     *                           — ฝังใน token (HMAC ครอบ ปลอมไม่ได้) gateway ส่งต่อเป็น ?to= ของ SSO
     * @return string URL เต็มของ gateway
     */
    public function generateChatLink(string $platform, string $platformUserId, ?string $to = null): string
    {
        $payload = [
            'p' => $platform,
            'u' => $platformUserId,
            'exp' => now()->addHours(self::TOKEN_TTL_HOURS)->timestamp,
        ];
        // เฉพาะ path ภายใน (ขึ้นต้น / ไม่มี scheme/host) — กัน open redirect ตั้งแต่ตอนสร้าง
        if ($to !== null && preg_match('#^/[A-Za-z0-9/_\-]*$#', $to)) {
            $payload['t'] = $to;
        }

        $encoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = $this->sign($encoded);

        return url('/web-fortune/go').'?tk='.$encoded.'.'.$signature;
    }

    /**
     * ตรวจสอบ token — คืน payload ถ้าถูกต้องและยังไม่หมดอายุ
     *
     * @return array|null ['p' => platform, 'u' => platform_user_id] หรือ null ถ้าไม่ผ่าน
     */
    public function verifyToken(?string $token): ?array
    {
        if (empty($token) || substr_count($token, '.') !== 1) {
            return null;
        }

        [$encoded, $signature] = explode('.', $token, 2);

        // เทียบ signature แบบ constant-time — กัน timing attack
        if (! hash_equals($this->sign($encoded), $signature)) {
            Log::warning('WebFortune: token signature ไม่ถูกต้อง', [
                'token_prefix' => mb_substr($token, 0, 16),
            ]);

            return null;
        }

        $payload = json_decode($this->base64UrlDecode($encoded), true);
        if (! is_array($payload) || empty($payload['p']) || empty($payload['u']) || empty($payload['exp'])) {
            return null;
        }

        // เช็ควันหมดอายุ
        if ((int) $payload['exp'] < now()->timestamp) {
            Log::info('WebFortune: token หมดอายุ', ['platform' => $payload['p']]);

            return null;
        }

        // จำกัด platform ที่ยอมรับ — กันค่าแปลกปลอมหลุดเข้า user lookup
        if (! in_array($payload['p'], ['facebook', 'line'], true)) {
            return null;
        }

        $result = ['p' => (string) $payload['p'], 'u' => (string) $payload['u']];

        // 🌳 (2026-07-25) path ปลายทาง (optional) — ตรวจซ้ำอีกชั้นตอน verify (defense-in-depth)
        $to = (string) ($payload['t'] ?? '');
        if ($to !== '' && preg_match('#^/[A-Za-z0-9/_\-]*$#', $to)) {
            $result['to'] = $to;
        }

        return $result;
    }

    /**
     * เซ็น payload ด้วย APP_KEY (HMAC-SHA256)
     */
    protected function sign(string $encoded): string
    {
        // ตัด prefix "base64:" ของ APP_KEY ออกก่อนใช้เป็น HMAC key
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return hash_hmac('sha256', 'web-fortune|'.$encoded, $key);
    }

    /**
     * base64 แบบ URL-safe (ไม่มี + / =)
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * ถอด base64 แบบ URL-safe
     */
    protected function base64UrlDecode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
