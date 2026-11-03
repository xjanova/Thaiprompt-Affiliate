<?php

namespace Tests\Feature;

use App\Models\FortuneTellingSetting;
use App\Models\FortuneWebHandoff;
use App\Services\FortuneWebLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 🔐 (2026-11-03) T2 — Token v2 ของ Magic Link + ตาราง fortune_web_handoffs (FB web-only)
 *
 * ครอบคลุม: round-trip pg/h/j, token ถูกแก้ = null, allowExpired, v1 ยังใช้ได้,
 * regex ของ to (กัน open redirect), isEnabledFor (LINE ต้องไม่เปลี่ยน), model handoff ใช้ครั้งเดียว
 *
 * @group security
 */
class FortuneWebLinkTokenV2Test extends TestCase
{
    use RefreshDatabase;

    private const PSID = '24680135792468013';

    /**
     * สร้าง service โดยไม่แตะ DB settings (ส่ง settings ที่ยังไม่ save เข้าไป)
     */
    private function service(array $settings = []): FortuneWebLinkService
    {
        $model = new FortuneTellingSetting;
        $model->forceFill($settings);

        return new FortuneWebLinkService($model);
    }

    /**
     * เรียก method protected ของ service (sign / base64UrlEncode) เพื่อปลอม token ที่เซ็นถูกต้อง
     */
    private function callProtected(FortuneWebLinkService $svc, string $method, ...$args)
    {
        $ref = new \ReflectionMethod($svc, $method);
        $ref->setAccessible(true);

        return $ref->invoke($svc, ...$args);
    }

    /**
     * เซ็น payload เองด้วย key จริง — จำลอง token ที่ระบบเคยออก (เช่น v1)
     */
    private function signedToken(FortuneWebLinkService $svc, array $payload): string
    {
        $encoded = $this->callProtected($svc, 'base64UrlEncode', json_encode($payload, JSON_UNESCAPED_SLASHES));

        return $encoded.'.'.$this->callProtected($svc, 'sign', $encoded);
    }

    private function decodePayload(string $token): array
    {
        [$encoded] = explode('.', $token, 2);

        return json_decode(base64_decode(strtr($encoded, '-_', '+/')), true);
    }

    public function test_v2_round_trip_carries_page_handoff_and_jti(): void
    {
        $svc = $this->service();
        $nonce = (string) Str::uuid();

        $token = $svc->generateToken('facebook', self::PSID, '/chat/continue/'.$nonce, $nonce, 7);
        $result = $svc->verifyToken($token);

        $this->assertNotNull($result);
        $this->assertSame(2, $result['v']);
        $this->assertSame('facebook', $result['p']);
        $this->assertSame(self::PSID, $result['u']);
        $this->assertSame(7, $result['pg']);
        $this->assertSame($nonce, $result['h']);
        $this->assertSame('/chat/continue/'.$nonce, $result['to']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $result['j']);
        $this->assertFalse($result['expired']);
    }

    public function test_each_link_gets_a_unique_jti_and_24h_ttl(): void
    {
        $this->freezeTime();
        $svc = $this->service();

        $a = $svc->verifyToken($svc->generateToken('facebook', self::PSID, '/chat'));
        $b = $svc->verifyToken($svc->generateToken('facebook', self::PSID, '/chat'));

        $this->assertNotSame($a['j'], $b['j']);
        $this->assertSame(24, FortuneWebLinkService::TOKEN_TTL_HOURS);
        $this->assertSame(now()->addHours(24)->timestamp, $a['exp']);
    }

    public function test_generate_chat_link_points_to_gateway_and_verifies(): void
    {
        $svc = $this->service();
        $nonce = (string) Str::uuid();

        $url = $svc->generateChatLink('facebook', self::PSID, '/chat/continue/'.$nonce, $nonce, 3);

        $this->assertStringStartsWith(url('/web-fortune/go').'?tk=', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
        $this->assertSame($nonce, $svc->verifyToken($q['tk'])['h']);
    }

    public function test_optional_fields_are_omitted_when_invalid(): void
    {
        $svc = $this->service();

        // nonce ไม่ใช่ uuid / pg ไม่เป็นบวก → ไม่ใส่ใน token
        $token = $svc->generateToken('facebook', self::PSID, '/chat', 'not-a-uuid', 0);
        $raw = $this->decodePayload($token);
        $this->assertArrayNotHasKey('h', $raw);
        $this->assertArrayNotHasKey('pg', $raw);

        $result = $svc->verifyToken($token);
        $this->assertArrayNotHasKey('h', $result);
        $this->assertArrayNotHasKey('pg', $result);
    }

    public function test_tampered_tokens_are_rejected(): void
    {
        $svc = $this->service();
        $token = $svc->generateToken('facebook', self::PSID, '/chat', (string) Str::uuid(), 5);
        [$encoded, $sig] = explode('.', $token, 2);

        // แก้ payload (เปลี่ยน PSID / เพจ) แต่ใช้ลายเซ็นเดิม
        $payload = $this->decodePayload($token);
        $payload['u'] = '11111111111111111';
        $payload['pg'] = 99;
        $forged = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=').'.'.$sig;
        $this->assertNull($svc->verifyToken($forged));

        // แก้ลายเซ็น
        $badSig = $encoded.'.'.str_repeat('0', strlen($sig));
        $this->assertNull($svc->verifyToken($badSig));

        // รูปแบบผิด
        $this->assertNull($svc->verifyToken(null));
        $this->assertNull($svc->verifyToken(''));
        $this->assertNull($svc->verifyToken('abc'));
        $this->assertNull($svc->verifyToken($token.'.extra'));

        // key คนละตัว (เช่นหลังหมุน APP_KEY) → ใช้ไม่ได้
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        $this->assertNull($svc->verifyToken($token));
    }

    public function test_signed_but_malformed_v2_is_rejected(): void
    {
        $svc = $this->service();
        $exp = now()->addHour()->timestamp;

        // v2 ไม่มี jti / jti ผิดรูปแบบ
        $this->assertNull($svc->verifyToken($this->signedToken($svc, ['v' => 2, 'p' => 'facebook', 'u' => self::PSID, 'exp' => $exp])));
        $this->assertNull($svc->verifyToken($this->signedToken($svc, ['v' => 2, 'p' => 'facebook', 'u' => self::PSID, 'j' => 'xyz', 'exp' => $exp])));
        // เวอร์ชันที่ไม่รู้จัก
        $this->assertNull($svc->verifyToken($this->signedToken($svc, ['v' => 3, 'p' => 'facebook', 'u' => self::PSID, 'j' => str_repeat('a', 32), 'exp' => $exp])));
        // platform แปลก
        $this->assertNull($svc->verifyToken($this->signedToken($svc, ['v' => 2, 'p' => 'telegram', 'u' => self::PSID, 'j' => str_repeat('a', 32), 'exp' => $exp])));

        // h ที่ไม่ใช่ uuid / pg ที่ไม่ใช่ int ถูกทิ้งตอน verify (defense-in-depth)
        $r = $svc->verifyToken($this->signedToken($svc, [
            'v' => 2, 'p' => 'facebook', 'u' => self::PSID, 'j' => str_repeat('b', 32), 'exp' => $exp,
            'h' => '../../etc', 'pg' => '5',
        ]));
        $this->assertNotNull($r);
        $this->assertArrayNotHasKey('h', $r);
        $this->assertArrayNotHasKey('pg', $r);
    }

    public function test_expired_token_only_passes_with_allow_expired_within_14_days(): void
    {
        $svc = $this->service();
        $token = $svc->generateToken('facebook', self::PSID, '/chat');

        $this->travel(25)->hours();

        $this->assertNull($svc->verifyToken($token));
        $loose = $svc->verifyToken($token, allowExpired: true);
        $this->assertNotNull($loose);
        $this->assertTrue($loose['expired']);
        $this->assertSame(self::PSID, $loose['u']);

        // เกิน 14 วันหลังหมดอายุ → ใช้ไม่ได้แม้ allowExpired
        $this->travel(FortuneWebLinkService::RESEND_MAX_EXPIRED_DAYS)->days();
        $this->assertNull($svc->verifyToken($token, allowExpired: true));
    }

    public function test_v1_tokens_still_verify_with_stable_derived_jti(): void
    {
        $svc = $this->service();
        $v1 = $this->signedToken($svc, [
            'p' => 'line',
            'u' => 'U1234567890abcdef',
            'exp' => now()->addHours(72)->timestamp,
            't' => '/mlm',
        ]);

        $a = $svc->verifyToken($v1);
        $this->assertNotNull($a);
        $this->assertSame(1, $a['v']);
        $this->assertSame('line', $a['p']);
        $this->assertSame('U1234567890abcdef', $a['u']);
        $this->assertSame('/mlm', $a['to']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $a['j']);
        $this->assertArrayNotHasKey('h', $a);
        $this->assertArrayNotHasKey('pg', $a);

        // jti ของ v1 ต้องคงที่ต่อ token (ให้ gateway กันใช้ซ้ำได้)
        $this->assertSame($a['j'], $svc->verifyToken($v1)['j']);

        // v1 ที่หมดอายุยังถูกปฏิเสธ
        $old = $this->signedToken($svc, ['p' => 'facebook', 'u' => self::PSID, 'exp' => now()->subMinute()->timestamp]);
        $this->assertNull($svc->verifyToken($old));
    }

    public function test_to_path_regex_blocks_open_redirects(): void
    {
        $svc = $this->service();
        $nonce = (string) Str::uuid();

        $allowed = ['/chat', '/mlm', '/tarot/free', '/chat/continue/'.$nonce, '/'];
        foreach ($allowed as $to) {
            $this->assertSame($to, $svc->verifyToken($svc->generateToken('facebook', self::PSID, $to))['to'] ?? null, $to);
        }

        $rejected = ['https://evil.com', '//evil', '//evil.com/chat', 'chat', '/chat?x=1', '/chat/../admin', '/แชท', '/chat#x', '/a.b', '/chat%2F..'];
        foreach ($rejected as $to) {
            $result = $svc->verifyToken($svc->generateToken('facebook', self::PSID, $to));
            $this->assertNotNull($result, $to);
            $this->assertArrayNotHasKey('to', $result, $to);
        }

        // ปลอม t ใน token ที่เซ็นถูก (เช่นจาก key รั่วในอนาคต) — verify ตัดทิ้งอีกชั้น
        $forged = $this->signedToken($svc, ['v' => 2, 'p' => 'facebook', 'u' => self::PSID, 'j' => str_repeat('c', 32), 'exp' => now()->addHour()->timestamp, 't' => '//evil']);
        $this->assertArrayNotHasKey('to', $svc->verifyToken($forged));
    }

    public function test_is_enabled_for_keeps_line_unchanged(): void
    {
        // ปิดหมด
        $off = $this->service(['enable_web_fortune_button' => false, 'facebook_web_only' => false]);
        $this->assertFalse($off->isEnabledFor('facebook'));
        $this->assertFalse($off->isEnabledFor('line'));

        // FB web-only เปิด แต่ปุ่มเว็บรวมปิด → FB ได้ลิงก์ LINE ไม่ได้ (LINE ต้องไม่เปลี่ยน)
        $fbOnly = $this->service(['enable_web_fortune_button' => false, 'facebook_web_only' => true]);
        $this->assertTrue($fbOnly->isEnabledFor('facebook'));
        $this->assertFalse($fbOnly->isEnabledFor('line'));
        $this->assertFalse($fbOnly->isEnabledFor(null));
        $this->assertFalse($fbOnly->isEnabled());

        // ปุ่มเว็บรวมเปิด → ทั้งคู่
        $on = $this->service(['enable_web_fortune_button' => true, 'facebook_web_only' => false]);
        $this->assertTrue($on->isEnabledFor('facebook'));
        $this->assertTrue($on->isEnabledFor('line'));

        // ไม่มีค่า facebook_web_only เลย (ก่อน migrate T1) → ใช้สวิตช์เดิม
        $legacy = $this->service(['enable_web_fortune_button' => false]);
        $this->assertFalse($legacy->isEnabledFor('facebook'));
    }

    public function test_handoff_issue_find_and_single_use_consume(): void
    {
        $h = FortuneWebHandoff::issue([
            'platform_user_id' => self::PSID,
            'fortune_page_id' => 2,
            'intent_text' => str_repeat('ก', 400),
            'intent_topic' => 'love',
            'source' => 'web_only_gate',
        ]);

        $this->assertTrue(Str::isUuid($h->nonce));
        $this->assertSame('facebook', $h->platform);
        $this->assertSame(300, mb_strlen($h->fresh()->intent_text));
        $this->assertTrue($h->expires_at->isFuture());
        $this->assertTrue($h->isUsable());

        $found = FortuneWebHandoff::findUsableByNonce(strtoupper($h->nonce));
        $this->assertNotNull($found);
        $this->assertSame($h->id, $found->id);
        $this->assertNull(FortuneWebHandoff::findUsableByNonce('not-a-uuid'));
        $this->assertNull(FortuneWebHandoff::findUsableByNonce(null));

        // consume ได้ครั้งเดียว (instance ที่สองที่โหลดไว้ก่อนก็ consume ซ้ำไม่ได้)
        $stale = FortuneWebHandoff::find($h->id);
        $this->assertTrue($found->consume('juntra-42'));
        $this->assertFalse($stale->consume('juntra-99'));
        $this->assertFalse($found->consume('juntra-42'));

        $fresh = $h->fresh();
        $this->assertNotNull($fresh->consumed_at);
        $this->assertSame('juntra-42', $fresh->consumed_juntra_user_id);
        $this->assertFalse($fresh->isUsable());
        $this->assertNull(FortuneWebHandoff::findUsableByNonce($h->nonce));
    }

    public function test_handoff_expiry_topic_fallback_and_prune(): void
    {
        $expired = FortuneWebHandoff::issue([
            'platform_user_id' => self::PSID,
            'intent_topic' => 'lottery',
            'intent_text' => '   ',
            'expires_at' => now()->subMinute(),
        ]);
        $this->assertSame('general', $expired->fresh()->intent_topic);
        $this->assertNull($expired->fresh()->intent_text);
        $this->assertNull(FortuneWebHandoff::findUsableByNonce($expired->nonce));
        $this->assertSame(0, FortuneWebHandoff::query()->usable()->count());

        // issue ต้องไม่ยอมให้ส่ง consumed_* เข้ามาตั้งแต่สร้าง
        $clean = FortuneWebHandoff::issue(['platform_user_id' => self::PSID, 'consumed_at' => now()]);
        $this->assertNull($clean->fresh()->consumed_at);

        // prune เฉพาะแถวเก่ากว่า 7 วัน
        $this->travel(8)->days();
        $recent = FortuneWebHandoff::issue(['platform_user_id' => self::PSID]);
        $pruned = (new FortuneWebHandoff)->pruneAll();

        $this->assertSame(2, $pruned);
        $this->assertSame([$recent->id], FortuneWebHandoff::pluck('id')->all());
    }
}
