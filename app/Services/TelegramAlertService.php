<?php

namespace App\Services;

use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🔔 แจ้งเตือนแอดมินผ่าน Telegram
 *
 * ## ทำไมต้องมี (เคสจริง 2026-09-19)
 * เครดิต Pollinations หมดตอนเที่ยงคืน 18 ก.ย. → รูปดวงรายวันล้มวันละ 8 ใบ
 * ระบบ **ยังโพสต่อโดยไม่มีรูป** และเขียนแค่ `Log::warning` ⇒ ไม่มีใครรู้ 2 วันเต็ม
 * กว่าเจ้าของจะเห็นก็ต้องไปเปิดเพจดูเอง
 *
 * ## ทำไมเป็น Telegram ไม่ใช่ LINE/FCM
 * - LINE push มีโควตา **300 ครั้ง/เดือน** และสงวนไว้ให้ของลูกค้าที่จ่ายเงินแล้ว
 *   ([[rule_line_push_is_emergency_reserve_only]]) — เอามาใช้เตือนระบบ = เผาโควตา
 * - FCM ต้องมีแอปมือถือติดตั้งและเปิดสิทธิ์ไว้
 * - Telegram ฟรี ไม่จำกัด และเจ้าของใช้กับโปรเจกต์อื่นอยู่แล้ว
 *
 * ## 🚨 เป็นบอทคนละตัวกับแม่หมอ
 * เจ้าของยืนยัน (2026-09-19): **"bot แจ้งเตือน กับบอท แม่หมอ คนละตัวกันนะ"**
 * ⇒ ห้ามยืม `telegram_bot_token` ของแม่หมอมาส่งแจ้งเตือนเด็ดขาด
 *   บอทแม่หมอ = คุยกับลูกค้า · บอทตัวนี้ = คุยกับแอดมินเท่านั้น
 *   (ยืม token กัน = ข้อความระบบจะไปโผล่ในบอทที่ลูกค้าทักอยู่ และ log/ประวัติปนกัน)
 *
 * ## ตั้งค่า — กรอกที่หลังบ้าน `/admin/fortune/channels` การ์ด "แจ้งเตือนแอดมิน"
 *   telegram_alert_bot_token  ← จาก @BotFather (คนละบอทกับแม่หมอ)
 *   telegram_alert_chat_id    ← chat id ของเจ้าของ (ทักบอทตัวนั้นก่อน 1 ครั้ง)
 *
 * `.env` ใช้ได้ในฐานะ **ตัวสำรอง** (`TELEGRAM_ALERT_BOT_TOKEN` / `TELEGRAM_ALERT_CHAT_ID`)
 * — ค่าใน DB ชนะเสมอ เพราะเจ้าของแก้เองได้โดยไม่ต้องแตะเซิร์ฟเวอร์
 *
 * ⚠️ **ต้องมีทั้งคู่** — บอทส่งหาคนที่ไม่เคยทักมันไม่ได้ ขาด chat id = ส่งไม่ออก
 *
 * ## ข้อบังคับของคลาสนี้
 * 1. **ห้ามโยน exception ออกไป** — ตัวเตือนพังต้องไม่ทำให้งานที่มันเฝ้าพังตาม
 * 2. **ห้าม log token** — token อยู่ใน URL ของ Bot API ⇒ ต้อง redact ก่อนเขียน log เสมอ
 * 3. **ต้องมีตัวกันรัว** — เคส 6 ก.ย. error เดียวกันยิงซ้ำ 161 ครั้งใน 13 ชม.
 *    ถ้าเตือนทุกครั้งคือสแปมจนเจ้าของปิดการแจ้งเตือนทิ้ง แล้วก็กลับไปไม่มีใครรู้เหมือนเดิม
 */
class TelegramAlertService
{
    private const API_BASE = 'https://api.telegram.org';

    /** วินาที — สั้นโดยตั้งใจ: นี่คือ side-effect ของ cron ห้ามหน่วงงานหลัก */
    private const TIMEOUT = 10;

    /** กันรัวเริ่มต้น: เรื่องเดียวกันเตือนซ้ำได้ทุก 6 ชม. */
    public const DEFAULT_THROTTLE_MINUTES = 360;

    private ?string $token = null;

    private ?string $chatId = null;

    private bool $resolved = false;

    /**
     * พร้อมส่งหรือยัง — ต้องมีทั้ง token และ chat id
     */
    public function isConfigured(): bool
    {
        $this->resolve();

        return $this->token !== null && $this->token !== ''
            && $this->chatId !== null && $this->chatId !== '';
    }

    /**
     * ส่งข้อความเตือนแอดมิน
     *
     * @param  string  $text  ข้อความ (plain text — ตัวนี้ไม่ใช้ parse_mode จะได้ไม่ต้อง escape)
     * @param  string|null  $dedupeKey  คีย์กันรัว — null = ส่งทุกครั้ง (ใช้กับของที่ยิงมือ)
     * @param  int|null  $throttleMinutes  กี่นาทีถึงจะเตือนเรื่องเดิมซ้ำได้
     * @return bool ส่งออกจริงหรือไม่ (false = ไม่ได้ตั้งค่า / ติดตัวกันรัว / ยิงไม่สำเร็จ)
     */
    public function send(string $text, ?string $dedupeKey = null, ?int $throttleMinutes = null): bool
    {
        $text = trim($text);
        if ($text === '' || ! $this->isConfigured()) {
            return false;
        }

        // 🔇 ตัวกันรัว — Cache::add เป็น atomic (คนแรกได้ true คนหลังได้ false)
        //    ตั้งก่อนยิงโดยตั้งใจ: ถ้า Telegram ล่มแล้วเราปล่อยให้ retry ทุกนาที
        //    คิวจะกองแล้วระเบิดใส่เจ้าของทีเดียวตอนมันกลับมา
        if ($dedupeKey !== null) {
            $minutes = $throttleMinutes ?? self::DEFAULT_THROTTLE_MINUTES;
            $gate = 'telegram_alert:'.md5($dedupeKey);

            try {
                if (! Cache::add($gate, 1, now()->addMinutes(max(1, $minutes)))) {
                    return false;
                }
            } catch (\Throwable $e) {
                // Cache ล่ม → ยอมให้ส่ง (เตือนซ้ำ ดีกว่าเงียบ)
            }
        }

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->post(self::API_BASE.'/bot'.$this->token.'/sendMessage', [
                    'chat_id' => $this->chatId,
                    'text' => mb_substr($text, 0, 4000),
                    'disable_web_page_preview' => true,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('TelegramAlert: ส่งไม่สำเร็จ', [
                'status' => $response->status(),
                'body' => $this->redact(mb_substr($response->body(), 0, 300)),
            ]);
        } catch (\Throwable $e) {
            Log::warning('TelegramAlert: ยิง API ไม่ได้', [
                'error' => $this->redact($e->getMessage()),
            ]);
        }

        return false;
    }

    /**
     * 🧪 ทดสอบคู่ token+chat id ที่แอดมิน "กำลังจะบันทึก" — ยังไม่แตะค่าใน DB
     *
     * ใช้ตอนบันทึกในหลังบ้าน: ยิงจริงก่อน ถ้าไม่ถึงมือก็ไม่ต้องบันทึก
     * (ตั้งค่าเสร็จแล้วเห็นข้อความบนมือถือทันที = รู้แน่ว่าใช้ได้ ไม่ต้องรอของพังจริงถึงจะรู้ว่าเตือนไม่ออก)
     *
     * @return array{success:bool, message:string}
     */
    public function probe(string $token, string $chatId, string $text): array
    {
        $token = trim($token);
        $chatId = trim($chatId);

        if ($token === '' || $chatId === '') {
            return ['success' => false, 'message' => 'ต้องมีทั้ง Bot Token และ Chat ID'];
        }

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->post(self::API_BASE.'/bot'.$token.'/sendMessage', [
                    'chat_id' => $chatId,
                    'text' => mb_substr($text, 0, 4000),
                    'disable_web_page_preview' => true,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'ส่งข้อความทดสอบสำเร็จ'];
            }

            // Telegram บอกสาเหตุมาชัดเจน (chat not found / unauthorized) — ส่งต่อให้แอดมินอ่าน
            $why = (string) ($response->json('description') ?? 'HTTP '.$response->status());

            return ['success' => false, 'message' => str_replace($token, '***', $why)];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => str_replace($token, '***', $e->getMessage())];
        }
    }

    /**
     * อ่าน token + chat id ครั้งเดียวต่อ instance
     */
    private function resolve(): void
    {
        if ($this->resolved) {
            return;
        }
        $this->resolved = true;

        // 1) ค่าในหลังบ้านชนะก่อน — เจ้าของแก้เองได้ ไม่ต้องแตะ .env บนเซิร์ฟเวอร์
        //    (คอลัมน์ token เข้ารหัสไว้ — APP_KEY เปลี่ยนแล้วถอดไม่ได้ = ถือว่ายังไม่ได้ตั้ง)
        try {
            $settings = FortuneTellingSetting::getSettings();
            $this->token = trim((string) ($settings->telegram_alert_bot_token ?? ''));
            $this->chatId = trim((string) ($settings->telegram_alert_chat_id ?? ''));
        } catch (\Throwable $e) {
            $this->token = '';
            $this->chatId = '';
        }

        // 2) .env เป็นตัวสำรอง — เติมเฉพาะช่องที่หลังบ้านยังว่าง
        if ($this->token === '') {
            $this->token = trim((string) config('services.telegram_alert.token', ''));
        }
        if ($this->chatId === '') {
            $this->chatId = trim((string) config('services.telegram_alert.chat_id', ''));
        }

        // 🚫 จงใจ **ไม่** ตกไปใช้ `telegram_bot_token` ของบอทแม่หมอ
        //    เจ้าของสั่งไว้ว่าเป็นคนละบอท — ยืมกัน = ข้อความระบบไปโผล่ในบอทที่ลูกค้าทักอยู่
    }

    /**
     * ตัด token ออกจากข้อความก่อน log — token อยู่ใน URL ของ Bot API
     */
    private function redact(string $text): string
    {
        if ($this->token === null || $this->token === '') {
            return $text;
        }

        return str_replace($this->token, '***', $text);
    }
}
