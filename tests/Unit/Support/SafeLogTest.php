<?php

namespace Tests\Unit\Support;

use App\Support\SafeLog;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 🔐 SafeLog::redactSecrets() — ปิดบัง token/secret ในข้อความ error ก่อนลง log (2026-09-27)
 *
 * เหตุจริง: ConnectException ของ Guzzle พิมพ์ URL เต็มของ Graph API ที่มี access_token
 * (app access token = app_id|app_secret) ลง storage/logs/laravel.log บน prod
 *
 * เทสต์ล้วน ไม่ boot แอป ไม่แตะ DB
 * ⚠️ token ตัวอย่างสร้างด้วย str_repeat — ถ้าเขียนเป็นสตริงยาวตรง ๆ secret scanning ของ GitHub
 *    อาจมองว่าเป็นคีย์จริงแล้วบล็อก push
 */
class SafeLogTest extends TestCase
{
    /** page token ปลอมรูปแบบเดียวกับของจริง (ขึ้นต้น EAA) */
    private static function pageToken(): string
    {
        return 'EAAG'.str_repeat('Zx9', 40);
    }

    /** app access token ปลอม = app_id|app_secret (32 hex) */
    private static function appToken(): string
    {
        return '1234567890123456|'.str_repeat('ab12', 8);
    }

    private static function telegramToken(): string
    {
        return '123456789:AAH'.str_repeat('k7', 16);
    }

    private static function googleKey(): string
    {
        return 'AIza'.str_repeat('Sy_q', 9);
    }

    /**
     * ข้อความจริงที่เจอบน prod (ConnectException ของ Guzzle) — token ต้องหาย ส่วนอื่นต้องอยู่ครบให้ไล่ปัญหาได้
     */
    #[Test]
    public function graph_connect_exception_message_hides_page_token_but_keeps_context(): void
    {
        $token = self::pageToken();
        $msg = 'cURL error 56: OpenSSL SSL_read: Connection reset by peer, errno 104 (see https://curl.haxx.se/libcurl/c/libcurl-errors.html)'
            ." for https://graph.facebook.com/v22.0/123456/comments?fields=id%2Cmessage&limit=50&access_token={$token}&after=QVFIUk";

        $clean = SafeLog::redactSecrets($msg);

        $this->assertStringNotContainsString($token, $clean);
        $this->assertStringContainsString('access_token=[REDACTED]&after=QVFIUk', $clean);
        $this->assertStringContainsString('cURL error 56', $clean);
        $this->assertStringContainsString('https://graph.facebook.com/v22.0/123456/comments?fields=id%2Cmessage&limit=50', $clean);
    }

    /**
     * debug_token = ส่งทั้ง input_token และ app access token (app_id|app_secret) ใน query — ตัวที่หลุดจริง 2026-09-27
     */
    #[Test]
    public function debug_token_url_hides_input_token_and_app_access_token(): void
    {
        $page = self::pageToken();
        $app = self::appToken();
        $msg = "cURL error 28: Operation timed out after 15001 milliseconds for https://graph.facebook.com/v21.0/debug_token?input_token={$page}&access_token={$app}";

        $clean = SafeLog::redactSecrets($msg);

        $this->assertStringNotContainsString($page, $clean);
        $this->assertStringNotContainsString(str_repeat('ab12', 8), $clean, 'app secret ต้องไม่เหลือแม้แต่ท่อนเดียว');
        $this->assertStringContainsString('input_token=[REDACTED]&access_token=[REDACTED]', $clean);
    }

    /**
     * แลก long-lived token: client_secret + fb_exchange_token อยู่ใน GET query
     */
    #[Test]
    public function token_exchange_url_hides_client_secret_and_exchange_token(): void
    {
        $secret = str_repeat('cd34', 8);
        $page = self::pageToken();
        $msg = 'Client error: `GET https://graph.facebook.com/v21.0/oauth/access_token?grant_type=fb_exchange_token'
            ."&client_id=1234567890123456&client_secret={$secret}&fb_exchange_token={$page}` resulted in a `400 Bad Request` response";

        $clean = SafeLog::redactSecrets($msg);

        $this->assertStringNotContainsString($secret, $clean);
        $this->assertStringNotContainsString($page, $clean);
        // Guzzle ครอบ URL ด้วย backtick — ค่าที่ปิดต้องหยุดก่อน backtick ไม่กินข้อความต่อท้าย
        $this->assertStringContainsString('fb_exchange_token=[REDACTED]` resulted in a `400 Bad Request` response', $clean);
        $this->assertStringContainsString('grant_type=fb_exchange_token&client_id=1234567890123456&client_secret=[REDACTED]', $clean);
    }

    #[Test]
    public function appsecret_proof_is_hidden(): void
    {
        $proof = str_repeat('9f', 32);

        $clean = SafeLog::redactSecrets("for https://graph.facebook.com/me?appsecret_proof={$proof}&fields=id");

        $this->assertSame('for https://graph.facebook.com/me?appsecret_proof=[REDACTED]&fields=id', $clean);
    }

    /** Lazada Affiliate ส่ง userToken (ตัวใหญ่ T) ทาง GET query */
    #[Test]
    public function lazada_user_token_is_hidden(): void
    {
        $clean = SafeLog::redactSecrets('for https://api.lazada.co.th/rest/marketing/product/feed?app_key=123&userToken='.str_repeat('u7', 20).'&page=1');

        $this->assertSame('for https://api.lazada.co.th/rest/marketing/product/feed?app_key=123&userToken=[REDACTED]&page=1', $clean);
    }

    /**
     * Telegram ใส่ bot token ไว้ใน path ไม่ใช่ query — เก็บ bot id ไว้ (เปิดเผยอยู่แล้ว) ปิดเฉพาะท่อน secret
     */
    #[Test]
    public function telegram_bot_token_in_path_is_hidden_but_bot_id_kept(): void
    {
        $token = self::telegramToken();
        $msg = "cURL error 7: Failed to connect for https://api.telegram.org/bot{$token}/sendMessage"
            ." and https://api.telegram.org/file/bot{$token}/photos/file_1.jpg";

        $clean = SafeLog::redactSecrets($msg);

        $this->assertStringNotContainsString($token, $clean);
        $this->assertStringContainsString('https://api.telegram.org/bot123456789:[REDACTED]/sendMessage', $clean);
        $this->assertStringContainsString('https://api.telegram.org/file/bot123456789:[REDACTED]/photos/file_1.jpg', $clean);
    }

    /**
     * Gemini / Vision / TTS ส่ง API key ทาง ?key= — และคีย์ Google ขึ้นต้น AIza เสมอ แม้หลุดมาแบบไม่มีชื่อพารามิเตอร์
     */
    #[Test]
    public function google_api_key_in_query_or_bare_is_hidden(): void
    {
        $key = self::googleKey();

        $inUrl = SafeLog::redactSecrets("for https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$key}");
        $bare = SafeLog::redactSecrets("API key not valid: {$key}");

        $this->assertSame('for https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=[REDACTED]', $inUrl);
        $this->assertSame('API key not valid: AIza[REDACTED]', $bare);
    }

    /**
     * token ที่หลุดมาเปล่า ๆ ไม่มีชื่อพารามิเตอร์ (เช่นอยู่ใน body ที่ถูก dump) ก็ต้องปิด
     */
    #[Test]
    public function bare_facebook_tokens_are_hidden(): void
    {
        $page = self::pageToken();

        $this->assertSame('token EAA[REDACTED] ใช้ไม่ได้', SafeLog::redactSecrets("token {$page} ใช้ไม่ได้"));
        $this->assertSame(
            'app token 1234567890123456|[REDACTED] และ 1234567890123456%7C[REDACTED]',
            SafeLog::redactSecrets('app token '.self::appToken().' และ 1234567890123456%7C'.str_repeat('ab12', 8))
        );
    }

    /** ข้อความไทยที่ติดท้าย token (ไม่มีช่องว่างคั่น) ต้องไม่ถูกกินไปด้วย */
    #[Test]
    public function thai_text_right_after_a_token_is_kept(): void
    {
        $this->assertSame(
            'access_token=[REDACTED]ไม่ถูกต้อง',
            SafeLog::redactSecrets('access_token='.self::pageToken().'ไม่ถูกต้อง')
        );
    }

    /** Telegram token หลุดมาเปล่า ๆ ไม่มี /bot นำหน้า — เก็บ bot id ไว้ */
    #[Test]
    public function bare_telegram_token_is_hidden(): void
    {
        $this->assertSame('โทเคนบอท 123456789:[REDACTED] ใช้ไม่ได้', SafeLog::redactSecrets('โทเคนบอท '.self::telegramToken().' ใช้ไม่ได้'));
    }

    /**
     * ข้อความยักษ์ที่ทำให้ regex บางกฎล้ม (JIT stack / backtrack limit) ต้องไม่ปล่อยทั้งข้อความออกไปดิบ ๆ
     * — กฎอื่นยังต้องทำงาน และข้อความต้องไม่หาย
     */
    #[Test]
    public function huge_payloads_still_get_redacted(): void
    {
        $token = self::pageToken();
        $msg = '{"access_token":"'.str_repeat('\\"', 200000).'"} for https://graph.facebook.com/me?access_token='.$token;

        $clean = SafeLog::redactSecrets($msg);

        $this->assertStringNotContainsString($token, $clean);
        $this->assertStringContainsString('me?access_token=[REDACTED]', $clean);
    }

    #[Test]
    public function json_bodies_and_bearer_headers_are_hidden(): void
    {
        $page = self::pageToken();
        $json = '{"access_token":"'.$page.'","token_type":"bearer","expires_in":5183944,"client_secret" : "s3cr\"et"}';

        $clean = SafeLog::redactSecrets($json);

        $this->assertSame('{"access_token":"[REDACTED]","token_type":"bearer","expires_in":5183944,"client_secret" : "[REDACTED]"}', $clean);
        $this->assertSame(
            'Authorization: Bearer [REDACTED]',
            SafeLog::redactSecrets('Authorization: Bearer '.str_repeat('Ab3/+', 20).'==')
        );
    }

    /**
     * ข้อความธรรมดาห้ามโดนแตะ — ถ้าปิดเกิน log จะอ่านไม่ออกและคนจะเลิกใช้ helper
     */
    #[Test]
    #[DataProvider('harmlessMessages')]
    public function harmless_messages_are_left_untouched(string $msg): void
    {
        $this->assertSame($msg, SafeLog::redactSecrets($msg));
    }

    public static function harmlessMessages(): array
    {
        return [
            'ว่าง' => [''],
            'ภาษาไทยล้วน' => ['❌ ส่งข้อความไม่สำเร็จ (ครั้งที่ 2): ลองใหม่อีกครั้ง'],
            'error ของ Graph ที่พูดถึง token แต่ไม่มีค่า' => ['(#190) Error validating access token: Session has expired on Friday'],
            'ชื่อ cache key ธรรมดา' => ['cache key=fortune:reading:123 hit'],
            'จำนวน token ของ AI' => ['usage input_tokens=512 output_tokens=128 max_tokens=4096'],
            'HTTP status' => ['HTTP request returned status code 403: {"error":{"message":"(#10) permission","code":10}}'],
            'token สั้น ๆ ที่ตัดโชว์ไว้ debug' => ['token_first_8=EAAGZx9Z len=210'],
        ];
    }

    /**
     * processor อาจทำงานซ้ำกับ record เดียวกัน (channel stack) — ปิดซ้ำต้องได้ผลเท่าเดิม ไม่งอก ] ต่อท้าย
     */
    #[Test]
    public function redaction_is_idempotent(): void
    {
        $samples = [
            'for https://graph.facebook.com/me?access_token='.self::pageToken().'&fields=id',
            'https://api.telegram.org/bot'.self::telegramToken().'/getMe',
            '{"client_secret":"abc"} ?key='.self::googleKey(),
            'app '.self::appToken(),
            'Bearer '.str_repeat('x', 40),
        ];

        foreach ($samples as $sample) {
            $once = SafeLog::redactSecrets($sample);
            $this->assertSame($once, SafeLog::redactSecrets($once), $sample);
        }
    }

    #[Test]
    public function exception_message_is_redacted(): void
    {
        $token = self::pageToken();
        $e = new \RuntimeException("cURL error 35 for https://graph.facebook.com/v22.0/me/messenger_profile?fields=greeting&access_token={$token}");

        $this->assertSame(
            'cURL error 35 for https://graph.facebook.com/v22.0/me/messenger_profile?fields=greeting&access_token=[REDACTED]',
            SafeLog::exceptionMessage($e)
        );
    }

    #[Test]
    public function secret_context_keys_are_recognised(): void
    {
        foreach (['access_token', 'facebook_page_access_token', 'page_token', 'client_secret', 'webhook_secret', 'secret', 'api_key', 'gemini_api_key', 'apikey', 'password', 'appsecret_proof', 'fb_exchange_token', 'token', 'telegram_bot_token', 'verify_token', 'secret_key', 'private_key', 'userToken'] as $key) {
            $this->assertTrue(SafeLog::isSecretKey($key), $key);
        }

        foreach (['token_type', 'token_first_8', 'has_token', 'max_tokens', 'reply_token', 'token_len', 'user_id', 'error'] as $key) {
            $this->assertFalse(SafeLog::isSecretKey($key), $key);
        }
    }
}
