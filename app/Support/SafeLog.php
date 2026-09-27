<?php

namespace App\Support;

use Throwable;

/**
 * 🔐 ปิดบัง secret ในข้อความก่อนเขียนลง log / ส่งกลับหน้าเว็บ / เก็บลง DB / ส่งออกนอกระบบ
 *
 * ทำไมต้องมี (2026-09-27): error ของ Guzzle / Laravel Http client พิมพ์ "URL เต็ม" ไว้ในข้อความ
 *   เช่น `cURL error 56 ... for https://graph.facebook.com/v22.0/debug_token?input_token=…&access_token=<app_id>|<app_secret>`
 *   โค้ดที่ส่ง token ทาง query string (GET ของ Graph API, paging.next ที่ Facebook ฝัง access_token มาเอง,
 *   Gemini `?key=`, Telegram `/bot<token>/`) แล้ว `Log::*(... $e->getMessage())` ใน catch
 *   ⇒ token/secret ของจริงไหลลง storage/logs/laravel.log — เกิดจริงบน prod กับ app access token
 *
 * วิธีใช้:
 *   Log::warning('ดึงคอมเมนต์ไม่สำเร็จ: '.SafeLog::exceptionMessage($e));
 *   $this->lastError = SafeLog::redactSecrets($anyText);
 *
 * ตาข่ายชั้นที่สอง: App\Logging\RedactSecretsProcessor เรียกตัวนี้กับ "ทุก" log record อัตโนมัติ
 *   แต่ข้อความที่ออกทางอื่น (JSON ตอบแอดมิน, คอลัมน์ last_error, ข้อความ Telegram, stdout ของ artisan)
 *   ไม่ผ่าน processor — จุดพวกนั้นต้องเรียกตัวนี้เองเสมอ
 *
 * กติกา: แทนเฉพาะ "ค่า" ด้วย [REDACTED] เก็บชื่อพารามิเตอร์ / bot id / app id ไว้ให้ไล่ปัญหาต่อได้
 *   และเรียกซ้ำกี่รอบก็ได้ผลเท่าเดิม (idempotent) เพราะ processor อาจทำงานซ้ำกับ record เดียวกัน
 */
final class SafeLog
{
    public const MASK = '[REDACTED]';

    /**
     * ชื่อพารามิเตอร์ที่เป็นความลับเสมอ ไม่ว่าจะอยู่ใน query string, form body หรือ JSON
     * (จับชื่อที่ "ลงท้าย" ด้วยคำเหล่านี้ด้วย เช่น page_access_token, facebook_app_secret)
     * usertoken = Lazada Affiliate ส่ง userToken ทาง GET query (ไม่สนตัวพิมพ์เล็ก/ใหญ่)
     */
    private const SECRET_PARAMS = 'access_token|refresh_token|id_token|input_token|fb_exchange_token'
        .'|client_secret|app_secret|appsecret_proof|channel_secret|api_key|apikey|password|usertoken|user_token';

    /**
     * ชื่อกว้าง ๆ ที่นับเป็นความลับ "เฉพาะตอนเป็นพารามิเตอร์ใน URL" (ขึ้นต้นด้วย ? หรือ &)
     *   key   = Google API key (Gemini / Vision / TTS / Maps)
     *   token = โทเคนดาวน์โหลด Firebase Storage, โทเคนล็อกอินจากแอป
     * ถ้าจับแบบไม่ดูตำแหน่ง ข้อความธรรมดาอย่าง "cache key=..." จะโดนปิดทิ้งไปด้วย
     */
    private const QUERY_ONLY_PARAMS = 'key|token|secret';

    /**
     * ตัวอักษรที่ถือว่า "จบค่า" ของพารามิเตอร์ — ช่องว่าง, & ถัดไป, เครื่องหมายคำพูด/backtick
     * ที่ Guzzle ครอบ URL ไว้, [ ] เพื่อไม่ให้ไปจับ [REDACTED] ซ้ำ (รักษา idempotent)
     * และไบต์ \x80-\xFF (ตัวอักษรไทย/UTF-8) — token ไม่มีวันเป็น non-ASCII ไม่ต้องกินข้อความไทยที่ติดท้ายไปด้วย
     */
    private const VALUE = '[^&\s"\'`<>#,;()\[\]{}\x80-\xFF]+';

    /**
     * ตัวกรองเร็ว — ข้อความ log ส่วนใหญ่ไม่มีคำพวกนี้เลย ข้ามการแทนทั้งชุดได้ทันที
     */
    private const QUICK_CHECK = '/token|secret|key|proof|password|bearer|bot\d|EAA|AIza|\d(?:\||%7C)|\d{8}:/i';

    /**
     * @var array<string, string>|null แพตเทิร์น => ข้อความแทน (สร้างครั้งเดียวต่อ process)
     */
    private static ?array $rules = null;

    /**
     * ปิดบัง secret ที่รู้จักทั้งหมดในข้อความ
     *
     * @param  string  $msg  ข้อความดิบ (มักมาจาก $e->getMessage())
     * @return string ข้อความที่ค่า secret ถูกแทนด้วย [REDACTED] แล้ว
     *
     * @example
     * SafeLog::redactSecrets('cURL error 28 for https://graph.facebook.com/me?fields=id&access_token=EAAB123');
     * // ผลลัพธ์: 'cURL error 28 for https://graph.facebook.com/me?fields=id&access_token=[REDACTED]'
     */
    public static function redactSecrets(string $msg): string
    {
        if ($msg === '' || ! preg_match(self::QUICK_CHECK, $msg)) {
            return $msg;
        }

        // รันทีละกฎ — ถ้า regex engine ล้มกับกฎไหน (ข้อความยักษ์ชน JIT stack / backtrack limit)
        // ข้ามเฉพาะกฎนั้น ผลของกฎอื่นยังอยู่ ไม่ปล่อยทั้งข้อความออกไปดิบ ๆ และไม่ทำ log หาย
        foreach (self::rules() as $pattern => $replacement) {
            $next = preg_replace($pattern, $replacement, $msg);
            if ($next !== null) {
                $msg = $next;
            }
        }

        return $msg;
    }

    /**
     * ข้อความของ exception ที่ปิดบัง secret แล้ว — ใช้แทน $e->getMessage() ทุกที่ที่ข้อความจะออกไปนอกฟังก์ชัน
     */
    public static function exceptionMessage(Throwable $e): string
    {
        return self::redactSecrets($e->getMessage());
    }

    /**
     * ชื่อ key ของ array (context ของ log) ที่ค่าเป็นความลับทั้งก้อน
     *
     * @example SafeLog::isSecretKey('facebook_page_access_token') // true
     * @example SafeLog::isSecretKey('token_type') // false
     *
     * "token" นับเฉพาะชื่อเดี่ยว ๆ — ถ้านับ *_token ทั้งหมดจะไปปิด reply_token / device_token ที่ใช้ไล่ปัญหา
     */
    public static function isSecretKey(string $key): bool
    {
        return (bool) preg_match(
            '/^token$|(?:^|_)(?:'.self::SECRET_PARAMS.'|page_token|bot_token|verify_token|secret|secret_key|private_key)$/i',
            $key
        );
    }

    /**
     * @return array<string, string>
     */
    private static function rules(): array
    {
        if (self::$rules !== null) {
            return self::$rules;
        }

        $mask = self::MASK;
        $value = self::VALUE;

        return self::$rules = [
            // 1) query string / form body: ...&access_token=xxx, page_access_token=xxx, access_token%3Dxxx (URL ซ้อน URL)
            //    ไม่ต้องจับ prefix (page_) — อยู่นอก match อยู่แล้วจึงคงไว้เหมือนเดิม และไม่ต้อง backtrack กับสตริงยาว
            '/((?:'.self::SECRET_PARAMS.')(?:=|%3D))'.$value.'/i' => '$1'.$mask,

            // 2) ชื่อกว้างที่นับเฉพาะใน URL: ?key=AIza..., &token=...
            '/([?&](?:'.self::QUERY_ONLY_PARAMS.')=)'.$value.'/i' => '$1'.$mask,

            // 3) JSON: "access_token":"xxx" / "client_secret" : "xxx" (รองรับ \" ในค่า)
            //    ใช้ possessive (++ / *+) กัน regex engine ล้มกับค่ายาว/escape เยอะ (JIT stack, backtrack limit)
            '/("[A-Za-z0-9_]{0,64}(?:'.self::SECRET_PARAMS.')"\s*:\s*")(?:[^"\\\\]++|\\\\.)*+"/i' => '$1'.$mask.'"',

            // 4) Telegram bot token อยู่ใน path: api.telegram.org/bot<id>:<secret>/sendMessage — เก็บ bot id ไว้
            '/\bbot(\d{3,}):[A-Za-z0-9_-]{20,}/' => 'bot$1:'.$mask,

            // 4b) Telegram bot token หลุดมาเปล่า ๆ (ไม่มี bot นำหน้า) = <bot id 8-10 หลัก>:<secret 35 ตัว>
            '/\b(\d{8,10}):[A-Za-z0-9_-]{30,}/' => '$1:'.$mask,

            // 5) App access token ของ Facebook = <app_id>|<app_secret 32 hex> (หรือ %7C ตอนถูก encode) — เก็บ app id ไว้
            '/\b(\d{8,20})(\||%7C)[0-9a-f]{32}(?![0-9a-f])/i' => '$1$2'.$mask,

            // 6) token ของ Facebook (user/page) หลุดมาแบบไม่มีชื่อพารามิเตอร์นำหน้า — ขึ้นต้น EAA เสมอ
            '/\bEAA[A-Za-z0-9]{30,}/' => 'EAA'.$mask,

            // 7) Google API key ขึ้นต้น AIza เสมอ
            '/\bAIza[0-9A-Za-z_-]{30,}/' => 'AIza'.$mask,

            // 8) header Authorization ที่ถูก dump ออกมา
            '/\b(Bearer)\s+[A-Za-z0-9\-._~+\/]{16,}=*/i' => '$1 '.$mask,
        ];
    }
}
