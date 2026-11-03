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
 * - token = base64url(JSON payload) . '.' . HMAC-SHA256(payload, APP_KEY)
 * - ลูกค้ากดลิงก์ → WebFortuneGatewayController ตรวจ token → หา/สร้าง User
 *   → Auth::login → redirect ไป SSO ของ juntraweb (Passport OAuth auto-approve)
 * - สวิตช์หลัก: enable_web_fortune_button (default OFF — ปิดอยู่ = ไม่มีปุ่ม/ลิงก์ใช้ไม่ได้)
 *   และสำหรับ FB เท่านั้น: facebook_web_only (ดู isEnabledFor)
 *
 * ความปลอดภัย:
 * - HMAC ผูก APP_KEY — ปลอม token ไม่ได้ถ้าไม่รู้ key
 * - มีอายุ (TOKEN_TTL_HOURS) — ลิงก์เก่าในแชทหมดอายุเอง
 * - hash_equals เทียบ signature (constant-time)
 *
 * 🔐 (2026-11-03) Token v2 — FB web-only:
 * - payload = {v:2, p, u, pg?, h?, j, exp, t?}
 *   pg = fortune_page_id สาขาต้นทาง · h = handoff nonce (uuid ของ fortune_web_handoffs)
 *   j  = jti (16 ไบต์ hex) ให้ gateway ใช้กันการใช้ token ซ้ำ (single-use)
 * - verifyToken ยังรับ v1 ({p,u,exp,t}) — v1 ไม่มี j จึงสร้าง j จาก signature แทน
 *   เพื่อให้ gateway ใช้กฎ single-use เดียวกันได้ (v1 หมดอายุเองใน ≤72 ชม. หลัง deploy)
 * - verifyToken(..., allowExpired: true) สำหรับหน้า "ส่งลิงก์ใหม่เข้าแชท" — คืน payload ที่มี
 *   expired=true แต่ไม่เกิน RESEND_MAX_EXPIRED_DAYS วัน (ลายเซ็นยังต้องถูกเสมอ)
 */
class FortuneWebLinkService
{
    /**
     * อายุ token (ชั่วโมง) — ลิงก์ค้างในแชทต้องหมดอายุได้
     * (2026-11-03) ลดจาก 72 → 24 เพราะปุ่ม FB สร้างลิงก์ใหม่ทุกครั้งที่ส่งกล่อง
     */
    public const TOKEN_TTL_HOURS = 24;

    /**
     * 🔐 (รีวิว) อายุ token ของ LINE — คงเดิม 72 ชม. (การ์ดใน LINE เขียนว่า "ใช้ได้ 3 วัน"
     * ลิงก์ค้างในประวัติแชทและ LINE ขอลิงก์ใหม่จากหน้าเว็บไม่ได้ — ห้ามกระทบ LINE)
     */
    public const LINE_TOKEN_TTL_HOURS = 72;

    /**
     * อายุ token (ชั่วโมง) ตามแพลตฟอร์ม
     */
    public static function ttlHoursFor(string $platform): int
    {
        return $platform === 'line' ? self::LINE_TOKEN_TTL_HOURS : self::TOKEN_TTL_HOURS;
    }

    /** เวอร์ชัน token ปัจจุบัน */
    public const TOKEN_VERSION = 2;

    /** token หมดอายุแล้วแต่ยังขอ "ส่งลิงก์ใหม่" ได้ไม่เกินกี่วัน */
    public const RESEND_MAX_EXPIRED_DAYS = 14;

    /** แพลตฟอร์มที่ยอมรับใน token */
    public const ALLOWED_PLATFORMS = ['facebook', 'line'];

    /**
     * path ปลายทางที่ยอมรับ — เฉพาะ path ภายใน (ไม่มี scheme/host/query/จุด) กัน open redirect
     * (2026-11-03) ห้ามขึ้นต้น // ด้วย (protocol-relative URL = ไปโดเมนอื่นได้)
     */
    public const TO_PATH_REGEX = '#^/(?!/)[A-Za-z0-9/_\-]*$#';

    /** handoff nonce ต้องเป็น uuid เท่านั้น */
    public const UUID_REGEX = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    /** URL SSO default ของเว็บจันทรา (จันทรา.online = xn--82c4af5bzdj.online) */
    public const DEFAULT_SSO_URL = 'https://xn--82c4af5bzdj.online/auth/thaiprompt/redirect';

    protected FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * เปิดใช้งานระบบปุ่มดูดวงบนเว็บหรือไม่ (default OFF) — สวิตช์รวมเดิม
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->settings->enable_web_fortune_button ?? false);
    }

    /**
     * 🔐 (2026-11-03) เปิดใช้ลิงก์เว็บสำหรับแพลตฟอร์มนี้หรือไม่
     *
     * - facebook = enable_web_fortune_button || facebook_web_only (โหมด FB web-only ต้องมีลิงก์เสมอ)
     * - line และอื่น ๆ = enable_web_fortune_button (เหมือนเดิมทุกประการ — ห้ามกระทบ LINE)
     */
    public function isEnabledFor(?string $platform): bool
    {
        if ($platform === 'facebook') {
            return $this->isEnabled() || (bool) ($this->settings->facebook_web_only ?? false);
        }

        return $this->isEnabled();
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
     * สร้าง Magic Link สำหรับลูกค้าคนหนึ่ง (ผูก platform + user id + วันหมดอายุ) — token v2
     *
     * @param  string  $platform  'facebook' | 'line'
     * @param  string  $platformUserId  PSID (FB) หรือ LINE user id
     * @param  string|null  $to  🌳 (2026-07-25) path ปลายทางบนเว็บจันทรา (เช่น '/mlm' = ผังงาน)
     *                           — ฝังใน token (HMAC ครอบ ปลอมไม่ได้) gateway ส่งต่อเป็น ?to= ของ SSO
     * @param  string|null  $handoffNonce  🔐 (2026-11-03) nonce (uuid) ของแถว fortune_web_handoffs — ไม่ใช่ uuid = ไม่ใส่
     * @param  int|null  $pageId  🔐 (2026-11-03) fortune_page_id ของสาขาต้นทาง (ใส่เฉพาะค่าบวก)
     * @return string URL เต็มของ gateway
     *
     * @example
     * $url = $svc->generateChatLink('facebook', $psid, '/chat/continue/'.$nonce, $nonce, $pageId);
     */
    public function generateChatLink(
        string $platform,
        string $platformUserId,
        ?string $to = null,
        ?string $handoffNonce = null,
        ?int $pageId = null
    ): string {
        return url('/web-fortune/go').'?tk='.$this->generateToken($platform, $platformUserId, $to, $handoffNonce, $pageId);
    }

    /**
     * สร้าง token v2 ล้วน ๆ (ไม่มี URL นำหน้า) — generateChatLink ใช้ตัวนี้
     */
    public function generateToken(
        string $platform,
        string $platformUserId,
        ?string $to = null,
        ?string $handoffNonce = null,
        ?int $pageId = null
    ): string {
        $payload = [
            'v' => self::TOKEN_VERSION,
            'p' => $platform,
            'u' => $platformUserId,
        ];
        // สาขาต้นทาง — ให้บิลเว็บตามไปถึงเพจที่ลูกค้ามาจาก
        if ($pageId !== null && $pageId > 0) {
            $payload['pg'] = $pageId;
        }
        // handoff nonce — ต้องเป็น uuid เท่านั้น
        if ($handoffNonce !== null && preg_match(self::UUID_REGEX, $handoffNonce)) {
            $payload['h'] = strtolower($handoffNonce);
        }
        // jti สุ่ม 16 ไบต์ — gateway ใช้กันการใช้ token ซ้ำ
        $payload['j'] = bin2hex(random_bytes(16));
        $payload['exp'] = now()->addHours(self::ttlHoursFor($platform))->timestamp;
        // เฉพาะ path ภายใน (ขึ้นต้น / ไม่มี scheme/host) — กัน open redirect ตั้งแต่ตอนสร้าง
        if ($to !== null && preg_match(self::TO_PATH_REGEX, $to)) {
            $payload['t'] = $to;
        }

        $encoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        return $encoded.'.'.$this->sign($encoded);
    }

    /**
     * ตรวจสอบ token — คืน payload ถ้าถูกต้องและยังไม่หมดอายุ
     *
     * รับทั้ง v1 ({p,u,exp,t}) และ v2 ({v:2,p,u,pg?,h?,j,exp,t?})
     *
     * @param  bool  $allowExpired  true = ยอมคืน token ที่หมดอายุแล้ว (ใส่ expired=true)
     *                              แต่ไม่เกิน RESEND_MAX_EXPIRED_DAYS วัน — ใช้เฉพาะหน้า "ส่งลิงก์ใหม่"
     *                              ห้ามใช้ผลที่ expired=true ไปล็อกอินเด็ดขาด
     * @return array|null ['v','p','u','j','exp','expired', 'to'?, 'pg'?, 'h'?] หรือ null ถ้าไม่ผ่าน
     */
    public function verifyToken(?string $token, bool $allowExpired = false): ?array
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

        // จำกัด platform ที่ยอมรับ — กันค่าแปลกปลอมหลุดเข้า user lookup
        if (! is_string($payload['p']) || ! in_array($payload['p'], self::ALLOWED_PLATFORMS, true)) {
            return null;
        }

        // ระบุเวอร์ชัน — ไม่มี v = v1 · รู้จักแค่ 1 และ 2
        $version = (int) ($payload['v'] ?? 1);
        if (! in_array($version, [1, 2], true)) {
            return null;
        }

        // v2 ต้องมี jti รูปแบบถูกต้อง (32 hex) — ไม่มี = token เสีย
        if ($version === 2 && ! (is_string($payload['j'] ?? null) && preg_match('/^[0-9a-f]{32}$/', $payload['j']))) {
            return null;
        }

        // เช็ควันหมดอายุ
        $exp = (int) $payload['exp'];
        $now = now()->timestamp;
        $expired = $exp < $now;
        if ($expired) {
            Log::info('WebFortune: token หมดอายุ', ['platform' => $payload['p'], 'allow_expired' => $allowExpired]);

            // หมดอายุ = ใช้ไม่ได้ ยกเว้นโหมดส่งลิงก์ใหม่ และต้องไม่เก่าเกิน RESEND_MAX_EXPIRED_DAYS
            if (! $allowExpired || $exp < $now - self::RESEND_MAX_EXPIRED_DAYS * 86400) {
                return null;
            }
        }

        $result = [
            'v' => $version,
            'p' => (string) $payload['p'],
            'u' => (string) $payload['u'],
            // v1 ไม่มี jti — ใช้ hash ของ signature แทน (คงที่ต่อ token) ให้กฎ single-use ใช้ได้เหมือนกัน
            'j' => $version === 2 ? (string) $payload['j'] : substr(hash('sha256', 'v1|'.$signature), 0, 32),
            'exp' => $exp,
            'expired' => $expired,
        ];

        // 🌳 (2026-07-25) path ปลายทาง (optional) — ตรวจซ้ำอีกชั้นตอน verify (defense-in-depth)
        $to = is_string($payload['t'] ?? null) ? $payload['t'] : '';
        if ($to !== '' && preg_match(self::TO_PATH_REGEX, $to)) {
            $result['to'] = $to;
        }

        // 🔐 (2026-11-03) สาขาต้นทาง + handoff nonce (เฉพาะ v2) — ตรวจรูปแบบซ้ำ
        if ($version === 2) {
            $pg = $payload['pg'] ?? null;
            if (is_int($pg) && $pg > 0) {
                $result['pg'] = $pg;
            }
            $h = $payload['h'] ?? null;
            if (is_string($h) && preg_match(self::UUID_REGEX, $h)) {
                $result['h'] = strtolower($h);
            }
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
