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
 * ## ตั้งค่า (ทั้งคู่ต้องมี ไม่งั้นบริการนี้เงียบสนิท)
 *   TELEGRAM_ALERT_BOT_TOKEN=123456:ABC...     ← จาก @BotFather
 *   TELEGRAM_ALERT_CHAT_ID=123456789           ← chat id ของเจ้าของ (ทักบอทก่อน 1 ครั้ง)
 *
 * ไม่ได้ตั้ง `TELEGRAM_ALERT_BOT_TOKEN` → ตกไปใช้ token ของบอทแม่หมอใน DB ให้อัตโนมัติ
 * (ยังต้องมี chat id เสมอ — บอทส่งหาคนที่ไม่เคยทักมันไม่ได้)
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
     * อ่าน token + chat id ครั้งเดียวต่อ instance
     */
    private function resolve(): void
    {
        if ($this->resolved) {
            return;
        }
        $this->resolved = true;

        $this->token = trim((string) config('services.telegram_alert.token', ''));
        $this->chatId = trim((string) config('services.telegram_alert.chat_id', ''));

        if ($this->token !== '') {
            return;
        }

        // ตกมาใช้ token ของบอทแม่หมอ (คอลัมน์เข้ารหัสไว้ — ถอดไม่ได้ = ถือว่าไม่มี)
        try {
            $settings = FortuneTellingSetting::getSettings();
            $this->token = trim((string) ($settings->telegram_bot_token ?? ''));
        } catch (\Throwable $e) {
            $this->token = '';
        }
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
