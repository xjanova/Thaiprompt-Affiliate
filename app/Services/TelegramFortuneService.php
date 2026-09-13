<?php

namespace App\Services;

use App\Contracts\FortuneMessengerSender;
use App\Contracts\MessagingPlatformInterface;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneRecipient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ✈️ TelegramFortuneService — ส่ง/รับข้อความบอทแม่หมอผ่าน Telegram Bot API (2026-09-13)
 *
 * ## หลักการ
 * ใช้ "สมอง" ตัวเดียวกับ FB/LINE (FortuneConversationService) และใช้ **ตัวเรนเดอร์ของ Facebook**
 * ใน FortuneChannelManager ทั้งก้อน — คลาสนี้แค่แปลงของทรง Messenger เป็นของ Telegram:
 *   - quick reply  [['title','payload']]              → ปุ่ม inline (callback) ใต้ข้อความ
 *   - button template (web_url / postback)             → ปุ่ม inline (url / callback)
 *   - generic template (รูป + หัวข้อ + ปุ่ม)             → รูปพร้อมคำบรรยาย + ปุ่ม
 *   - รูป / เสียง                                        → sendPhoto / sendVoice
 *
 * ## id
 * ในระบบเราลูกค้า Telegram = 'tg_<chat id>' (ดู FortuneRecipient::TELEGRAM_ID_PREFIX)
 * ทุกเมธอดตัดคำนำหน้าก่อนยิง API · id ที่ไม่ใช่ทรง 'tg_' = ยิงผิดช่องทาง → ไม่ส่ง
 *
 * ## ความปลอดภัย
 * token อยู่ใน URL ของ Bot API (`/bot<token>/…`) ⇒ **ห้าม log URL / ข้อความ exception ดิบ**
 * ทุกข้อความ error ผ่าน redact() ก่อนเสมอ
 *
 * ## ข้อจำกัดของ Telegram ที่ต้องจำ
 *   - ข้อความ ≤ 4096 (นับแบบ UTF-16) · คำบรรยายรูป ≤ 1024 · callback_data ≤ 64 ไบต์
 *   - ~1 ข้อความ/วินาที/ห้อง · ~30 ข้อความ/วินาทีรวม → 429 + retry_after
 *   - บอททักลูกค้าก่อนไม่ได้ (ลูกค้าต้องกด Start) · ลูกค้าบล็อกบอท = 403
 */
class TelegramFortuneService implements FortuneMessengerSender, MessagingPlatformInterface
{
    public const API_BASE = 'https://api.telegram.org';

    /** เพดานความยาวข้อความ (UTF-16) — เผื่อจากเพดานจริง 4096 */
    public const MAX_TEXT_UNITS = 4000;

    /** เพดานคำบรรยายรูป (UTF-16) — เผื่อจากเพดานจริง 1024 */
    public const MAX_CAPTION_UNITS = 1000;

    /** callback_data: ใส่ payload ตรง ๆ */
    public const CALLBACK_PAYLOAD_PREFIX = 'p|';

    /** callback_data: payload ยาวเกิน 64 ไบต์ → เก็บใน cache แล้วส่งแค่ hash */
    public const CALLBACK_HASH_PREFIX = 'h|';

    /** ไฟล์ที่ยอมอัปโหลดตรงจากดิสก์ / ดาวน์โหลดจาก Telegram */
    protected const MAX_FILE_BYTES = 10 * 1024 * 1024;

    protected FortuneTellingSetting $settings;

    protected string $token = '';

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();

        try {
            $this->token = trim((string) $this->settings->telegram_bot_token);
        } catch (\Throwable $e) {
            // APP_KEY เปลี่ยน → ถอดรหัสไม่ได้ = ถือว่ายังไม่ได้ตั้งค่า (ห้ามทำให้ผู้เรียกล่ม)
            $this->token = '';
            Log::warning('Telegram: ถอดรหัส bot token ไม่ได้ (APP_KEY เปลี่ยน?) — ต้องกรอกใหม่ที่หลังบ้าน');
        }
    }

    // ============================================================
    // Low-level Bot API
    // ============================================================

    /**
     * มี token พร้อมยิง API หรือไม่
     */
    public function isConfigured(): bool
    {
        return $this->token !== '';
    }

    /**
     * เรียก Bot API หนึ่งเมธอด
     *
     * - 429 → รอตาม retry_after (ไม่เกิน 15 วิ) แล้วลองซ้ำ 1 ครั้ง
     * - 403 (ลูกค้าบล็อกบอท) → จดไว้ 1 วัน ไม่ถือเป็น error
     *
     * @param  array<string, mixed>  $params
     * @param  array<string, array{path:string,name:string}>  $files  ไฟล์แนบ multipart (ชื่อฟิลด์ => ไฟล์)
     * @return array{ok:bool,result?:mixed,error_code?:int,description?:string}
     */
    public function call(string $method, array $params = [], array $files = [], int $timeout = 25): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error_code' => 0, 'description' => 'telegram_not_configured'];
        }

        $url = self::API_BASE.'/bot'.$this->token.'/'.$method;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                if ($files === []) {
                    $response = Http::timeout($timeout)->asJson()->post($url, $params);
                } else {
                    $request = Http::timeout($timeout);
                    $handles = [];
                    foreach ($files as $field => $file) {
                        $handle = fopen($file['path'], 'r');
                        if ($handle === false) {
                            foreach ($handles as $opened) {
                                if (is_resource($opened)) {
                                    fclose($opened);
                                }
                            }

                            return ['ok' => false, 'error_code' => 0, 'description' => 'file_open_failed'];
                        }
                        $handles[] = $handle;
                        $request = $request->attach($field, $handle, $file['name']);
                    }

                    // multipart: ค่า array (reply_markup) ต้องเป็น JSON string
                    $fields = [];
                    foreach ($params as $key => $value) {
                        $fields[$key] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
                    }

                    try {
                        $response = $request->post($url, $fields);
                    } finally {
                        foreach ($handles as $handle) {
                            if (is_resource($handle)) {
                                fclose($handle);
                            }
                        }
                    }
                }

                $data = $response->json();
                if (! is_array($data)) {
                    $data = ['ok' => false, 'error_code' => $response->status(), 'description' => 'invalid_json'];
                }

                if (! empty($data['ok'])) {
                    return $data;
                }

                $code = (int) ($data['error_code'] ?? $response->status());
                $retryAfter = (int) ($data['parameters']['retry_after'] ?? 0);

                // 429 = ยิงถี่เกิน (~1 ข้อความ/วิ/ห้อง) → รอตามที่ Telegram บอกแล้วลองอีกครั้ง
                //    เพดาน 15 วิ — นานกว่านั้นให้ผู้เรียกจัดการ (คิวจะ retry เอง) ไม่แช่ worker ทิ้ง
                if ($code === 429 && $attempt === 1 && $retryAfter > 0 && $retryAfter <= 15) {
                    sleep($retryAfter);

                    continue;
                }

                if ($code === 403) {
                    $this->markBlocked($params['chat_id'] ?? null, (string) ($data['description'] ?? ''));
                } else {
                    Log::warning('Telegram API ล้มเหลว', [
                        'method' => $method,
                        'error_code' => $code,
                        'description' => $this->redact((string) ($data['description'] ?? '')),
                    ]);
                }

                return [
                    'ok' => false,
                    'error_code' => $code,
                    'description' => (string) ($data['description'] ?? ''),
                ];
            } catch (\Throwable $e) {
                Log::warning('Telegram API exception', [
                    'method' => $method,
                    'attempt' => $attempt,
                    'error' => $this->redact($e->getMessage()),
                ]);

                if ($attempt === 2) {
                    return ['ok' => false, 'error_code' => 0, 'description' => 'exception'];
                }

                usleep(300000);
            }
        }

        return ['ok' => false, 'error_code' => 0, 'description' => 'unknown'];
    }

    /**
     * ลบ token ออกจากข้อความก่อน log (token อยู่ใน URL ของ Bot API)
     */
    public function redact(string $text): string
    {
        if ($this->token === '') {
            return $text;
        }

        return str_replace($this->token, '***', $text);
    }

    /**
     * ลูกค้าบล็อกบอท / ลบแชท → จดไว้ 1 วัน (ใช้ดูสถานะ + กัน job ยิงซ้ำไม่รู้จบ)
     */
    protected function markBlocked(mixed $chatId, string $description): void
    {
        $uid = FortuneRecipient::telegramUserId(is_scalar($chatId) ? (string) $chatId : '');
        if ($uid === '') {
            return;
        }

        Cache::put('tg_blocked:'.$uid, true, now()->addDay());

        Log::info('Telegram: ลูกค้าบล็อกบอท/ส่งไม่ได้ (403)', [
            'user_id' => $uid,
            'description' => $this->redact($description),
        ]);
    }

    /**
     * ลูกค้าคนนี้บล็อกบอทอยู่หรือไม่ (จากการส่งครั้งล่าสุด)
     */
    public function isBlocked(string $userId): bool
    {
        return Cache::has('tg_blocked:'.$userId);
    }

    /**
     * ลูกค้าทักเข้ามา = ปลดบล็อกแล้ว
     */
    public function clearBlocked(string $userId): void
    {
        Cache::forget('tg_blocked:'.$userId);
    }

    /**
     * id ในระบบ ('tg_…') → chat id · ไม่ใช่ทรง Telegram = log แล้วคืน ''
     */
    protected function chatIdOrFail(string $recipientId, string $caller): string
    {
        $chatId = FortuneRecipient::telegramChatId($recipientId);

        if ($chatId === '') {
            Log::warning('Telegram: ผู้รับไม่ใช่ id ทรง tg_ — ข้าม (ยิงผิดช่องทาง)', [
                'caller' => $caller,
                'recipient' => $recipientId,
            ]);
        }

        return $chatId;
    }

    // ============================================================
    // FortuneMessengerSender — ของที่ตัวเรนเดอร์ FB เรียก
    // ============================================================

    /**
     * ส่งข้อความตัวอักษร (ตัดเป็นหลายข้อความถ้ายาวเกิน) — options['quick_replies'] = แนบปุ่ม
     */
    public function sendMessage(string $recipientId, string $message, array $options = []): bool
    {
        if (! empty($options['quick_replies']) && is_array($options['quick_replies'])) {
            return $this->sendQuickReplies($recipientId, $message, $options['quick_replies'], $options);
        }

        return $this->sendTextWithKeyboard($recipientId, $message, null);
    }

    /**
     * ส่งข้อความพร้อมปุ่มตัวเลือก — ปุ่มชุดล่าสุดชุดเดียวที่กดได้ (เหมือน quick reply ของ FB)
     */
    public function sendQuickReplies(string $recipientId, string $message, array $quickReplies, array $options = []): bool
    {
        $buttons = [];
        foreach (array_slice($quickReplies, 0, 13) as $reply) {
            if (! is_array($reply)) {
                continue;
            }
            $normalized = $this->normalizeButton($reply);
            if ($normalized === null) {
                continue;
            }
            $buttons[] = $normalized;
        }

        if ($buttons === []) {
            return $this->sendTextWithKeyboard($recipientId, $message, null);
        }

        $keyboard = $this->layoutCallbackButtons($buttons);

        return $this->sendTextWithKeyboard($recipientId, $message, $keyboard, true);
    }

    /**
     * ส่งรูป — ไฟล์บนเซิร์ฟเวอร์เราอัปโหลดตรง (ไม่พึ่งให้ Telegram มาดึง URL) · ที่อื่นส่งเป็น URL
     *
     * options: caption (string), reply_markup (array)
     */
    public function sendImage(string $recipientId, string $imageUrl, ?string $previewUrl = null, array $options = []): bool
    {
        $chatId = $this->chatIdOrFail($recipientId, __FUNCTION__);
        if ($chatId === '' || trim($imageUrl) === '') {
            return false;
        }

        $params = ['chat_id' => $chatId];

        $caption = trim((string) ($options['caption'] ?? ''));
        if ($caption !== '') {
            $params['caption'] = $this->truncateUnits($caption, self::MAX_CAPTION_UNITS);
        }
        if (! empty($options['reply_markup']) && is_array($options['reply_markup'])) {
            $params['reply_markup'] = $options['reply_markup'];
        }

        $localPath = $this->localPathForUrl($imageUrl);
        if ($localPath !== null) {
            $result = $this->call('sendPhoto', $params, ['photo' => ['path' => $localPath, 'name' => basename($localPath)]]);
            if (! empty($result['ok'])) {
                return true;
            }
        }

        $params['photo'] = $imageUrl;
        $result = $this->call('sendPhoto', $params);

        return ! empty($result['ok']);
    }

    /**
     * ส่ง template ทรง FB — button → ข้อความ + ปุ่ม · generic → การ์ดรูป + ปุ่ม
     */
    public function sendButtonTemplate(string $recipientId, array $templatePayload, array $options = []): bool
    {
        $payload = $templatePayload['attachment']['payload']
            ?? $templatePayload['payload']
            ?? $templatePayload;

        if (! is_array($payload)) {
            return false;
        }

        $type = (string) ($payload['template_type'] ?? 'button');

        // quick_replies ที่แนบมากับ template (FB อนุญาต) → ต่อเป็นแถวปุ่มท้าย
        $extraReplies = is_array($templatePayload['quick_replies'] ?? null) ? $templatePayload['quick_replies'] : [];

        if ($type === 'generic') {
            return $this->sendGenericElements($recipientId, (array) ($payload['elements'] ?? []), $extraReplies);
        }

        $text = trim((string) ($payload['text'] ?? ''));
        $keyboard = $this->templateButtonsToKeyboard((array) ($payload['buttons'] ?? []));

        if ($extraReplies !== []) {
            $extraButtons = array_values(array_filter(array_map(
                fn ($r) => is_array($r) ? $this->normalizeButton($r) : null,
                $extraReplies
            )));
            $keyboard = array_merge($keyboard, $this->layoutCallbackButtons($extraButtons));
        }

        if ($text === '') {
            // template ไม่มีข้อความ (ไม่ควรเกิด) — Telegram ต้องมีข้อความเสมอ
            $text = '👇';
        }

        return $this->sendTextWithKeyboard($recipientId, $text, $keyboard === [] ? null : $keyboard);
    }

    /**
     * ส่งการ์ด generic (รูป + หัวข้อ + ปุ่ม)
     */
    public function sendGenericTemplate(string $recipientId, array $elements, array $options = []): bool
    {
        return $this->sendGenericElements($recipientId, $elements, []);
    }

    /**
     * ส่งเสียง — ขึ้นเป็น "ข้อความเสียง" (sendVoice รับ mp3/m4a/ogg) · ไม่ได้ค่อยตกไป sendAudio
     */
    public function sendAudio(string $recipientId, string $audioUrl, array $options = []): bool
    {
        $chatId = $this->chatIdOrFail($recipientId, __FUNCTION__);
        if ($chatId === '' || trim($audioUrl) === '') {
            return false;
        }

        $localPath = $this->localPathForUrl($audioUrl);

        foreach (['sendVoice' => 'voice', 'sendAudio' => 'audio'] as $method => $field) {
            if ($localPath !== null) {
                $result = $this->call($method, ['chat_id' => $chatId], [$field => ['path' => $localPath, 'name' => basename($localPath)]], 60);
                if (! empty($result['ok'])) {
                    return true;
                }
            }

            $result = $this->call($method, ['chat_id' => $chatId, $field => $audioUrl], [], 60);
            if (! empty($result['ok'])) {
                return true;
            }

            // ลูกค้าบล็อกบอท → ไม่ต้องลองอีกวิธี
            if ((int) ($result['error_code'] ?? 0) === 403) {
                return false;
            }
        }

        return false;
    }

    /**
     * "กำลังพิมพ์..." — Telegram แสดง ~5 วิ แล้วหายเอง (ปิดเองไม่ได้ → on=false ไม่ต้องทำอะไร)
     */
    public function sendTypingIndicator(string $recipientId, bool $on = true): void
    {
        if (! $on) {
            return;
        }

        $chatId = FortuneRecipient::telegramChatId($recipientId);
        if ($chatId === '') {
            return;
        }

        $this->call('sendChatAction', ['chat_id' => $chatId, 'action' => 'typing'], [], 5);
    }

    public function getPlatformName(): string
    {
        return FortuneRecipient::PLATFORM_TELEGRAM;
    }

    // ============================================================
    // MessagingPlatformInterface
    // ============================================================

    public function sendRichMessage(string $recipientId, array $richContent): bool
    {
        if (isset($richContent['attachment']['payload']) || isset($richContent['template_type'])) {
            return $this->sendButtonTemplate($recipientId, $richContent);
        }

        $text = (string) ($richContent['text'] ?? '');
        if ($text === '') {
            return false;
        }

        return $this->sendMessage($recipientId, $text, [
            'quick_replies' => $richContent['quick_replies'] ?? [],
        ]);
    }

    /**
     * โปรไฟล์ลูกค้า — ได้จาก webhook ทุกข้อความ (cache 30 วัน) · ไม่มีค่อยถาม getChat
     *
     * @return array{id:string,name:?string,first_name:?string,last_name:?string,username:?string}|null
     */
    public function getUserProfile(string $userId): ?array
    {
        $cached = Cache::get('tg_profile:'.$userId);
        if (is_array($cached)) {
            return $cached;
        }

        $chatId = FortuneRecipient::telegramChatId($userId);
        if ($chatId === '') {
            return null;
        }

        $result = $this->call('getChat', ['chat_id' => $chatId], [], 10);
        if (empty($result['ok']) || ! is_array($result['result'] ?? null)) {
            return null;
        }

        return $this->rememberProfile($userId, $result['result']);
    }

    /**
     * จำโปรไฟล์จากก้อน `from` / `chat` ของ Telegram (เรียกจาก webhook ทุกข้อความ)
     *
     * @param  array<string, mixed>  $from
     * @return array{id:string,name:?string,first_name:?string,last_name:?string,username:?string}
     */
    public function rememberProfile(string $userId, array $from): array
    {
        // ชื่อ Telegram ตั้งเองได้ยาว/มีขึ้นบรรทัดใหม่ และไหลเข้า prompt ของ AI ในฐานะ "ชื่อลูกค้า"
        //    → ตัดอักขระควบคุม/ขึ้นบรรทัด + จำกัดความยาว (กันแอบฝังคำสั่งในชื่อ)
        $clean = static fn ($v): string => mb_substr(trim((string) preg_replace('/[\p{Cc}\p{Cf}\p{Zl}\p{Zp}]+/u', ' ', (string) $v)), 0, 40);
        $first = $clean($from['first_name'] ?? '');
        $last = $clean($from['last_name'] ?? '');
        $username = $clean($from['username'] ?? '');
        $name = trim($first.' '.$last);

        $profile = [
            'id' => $userId,
            // ไม่มีชื่อจริง → null (ให้ FortuneChannelManager หาชื่อจากประวัติเอง) — ห้ามใช้ @username เป็นชื่อเรียก
            'name' => $name !== '' ? mb_substr($name, 0, 100) : null,
            'first_name' => $first !== '' ? $first : null,
            'last_name' => $last !== '' ? $last : null,
            'username' => $username !== '' ? $username : null,
        ];

        Cache::put('tg_profile:'.$userId, $profile, now()->addDays(30));

        return $profile;
    }

    public function isMessageEvent(array $event): bool
    {
        return isset($event['message']['text']);
    }

    public function getMessageText(array $event): ?string
    {
        return $event['message']['text'] ?? null;
    }

    public function getUserIdFromEvent(array $event): ?string
    {
        $chatId = $event['message']['chat']['id']
            ?? $event['callback_query']['message']['chat']['id']
            ?? null;

        $uid = FortuneRecipient::telegramUserId($chatId !== null ? (string) $chatId : '');

        return $uid !== '' ? $uid : null;
    }

    public function supportsRichMessage(): bool
    {
        return true;
    }

    // ============================================================
    // ปุ่ม inline
    // ============================================================

    /**
     * แปลงปุ่มทรง FB/LINE → ['title','payload'] (ลำดับ payload เดียวกับ FB normalizeButtonShape)
     *
     * @param  array<string, mixed>  $reply
     * @return array{title:string,payload:string}|null
     */
    public function normalizeButton(array $reply): ?array
    {
        $title = trim((string) ($reply['title'] ?? $reply['label'] ?? ''));
        $payload = trim((string) ($reply['payload'] ?? $reply['text'] ?? $reply['title'] ?? $reply['label'] ?? ''));

        if ($title === '' && $payload === '') {
            return null;
        }

        return [
            // Telegram แสดงป้ายยาวได้ (FB ตัดที่ 20) — กันเกินจอด้วยเพดาน 40 ตัว
            'title' => mb_substr($title !== '' ? $title : $payload, 0, 40),
            'payload' => $payload !== '' ? $payload : $title,
        ];
    }

    /**
     * สร้าง callback_data (≤ 64 ไบต์) — payload สั้นใส่ตรง · ยาวเก็บ cache แล้วส่ง hash
     *
     * ⚠️ cache ถูกล้างทุก deploy ⇒ ปุ่ม hash ที่ค้างข้าม deploy จะหา payload ไม่เจอ
     *    ฝั่ง webhook ต้อง fallback เป็น "ข้อความบนปุ่ม" เสมอ (เหมือน FB ที่ป้ายปุ่มหล่นมาเป็นข้อความ)
     */
    public function encodeCallback(string $payload, string $title): string
    {
        $direct = self::CALLBACK_PAYLOAD_PREFIX.$payload;
        if (strlen($direct) <= 64) {
            return $direct;
        }

        $hash = substr(hash('sha256', $payload."\x1F".$title), 0, 24);
        Cache::put('tg_cb:'.$hash, ['payload' => $payload, 'title' => $title], now()->addDays(14));

        return self::CALLBACK_HASH_PREFIX.$hash;
    }

    /**
     * ถอด callback_data → payload (null = หาไม่เจอ ให้ caller ใช้ป้ายปุ่มแทน)
     */
    public function decodeCallback(string $data): ?string
    {
        if (str_starts_with($data, self::CALLBACK_PAYLOAD_PREFIX)) {
            $payload = substr($data, strlen(self::CALLBACK_PAYLOAD_PREFIX));

            return $payload !== '' ? $payload : null;
        }

        if (str_starts_with($data, self::CALLBACK_HASH_PREFIX)) {
            $stored = Cache::get('tg_cb:'.substr($data, strlen(self::CALLBACK_HASH_PREFIX)));

            return is_array($stored) && ($stored['payload'] ?? '') !== '' ? (string) $stored['payload'] : null;
        }

        return null;
    }

    /**
     * จัดปุ่ม callback เป็นแถว — ป้ายสั้นเรียง 2-3 ปุ่ม/แถว · ป้ายยาว 1 ปุ่ม/แถว
     *
     * @param  array<int, array{title:string,payload:string}>  $buttons
     * @return array<int, array<int, array{text:string,callback_data:string}>>
     */
    public function layoutCallbackButtons(array $buttons): array
    {
        $rows = [];
        $row = [];
        $rowCapacity = 0.0;

        foreach ($buttons as $button) {
            $len = mb_strlen($button['title']);
            $width = $len <= 6 ? 1 / 3 : ($len <= 14 ? 0.5 : 1.0);

            if ($row !== [] && $rowCapacity + $width > 1.0001) {
                $rows[] = $row;
                $row = [];
                $rowCapacity = 0.0;
            }

            $row[] = [
                'text' => $button['title'],
                'callback_data' => $this->encodeCallback($button['payload'], $button['title']),
            ];
            $rowCapacity += $width;
        }

        if ($row !== []) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * ปุ่มของ template FB → แถวปุ่ม inline (ปุ่มละแถว เหมือนหน้าตาปุ่มใน template ของ FB)
     *
     * - web_url  → ปุ่มลิงก์ (http/https เท่านั้น — Telegram ไม่รับ scheme อื่น)
     * - postback → ปุ่ม callback
     * - phone_number → Telegram ไม่มีปุ่มโทร → ใช้ปุ่ม callback ที่พาเบอร์ไปเป็นข้อความ
     *
     * @param  array<int, mixed>  $buttons
     * @return array<int, array<int, array<string, string>>>
     */
    public function templateButtonsToKeyboard(array $buttons): array
    {
        $rows = [];

        foreach ($buttons as $button) {
            if (! is_array($button)) {
                continue;
            }

            $title = mb_substr(trim((string) ($button['title'] ?? '')), 0, 40);
            $type = (string) ($button['type'] ?? 'postback');

            if ($type === 'web_url') {
                $url = trim((string) ($button['url'] ?? ''));
                if ($title !== '' && preg_match('#^https?://#i', $url)) {
                    $rows[] = [['text' => $title, 'url' => $url]];
                }

                continue;
            }

            $payload = trim((string) ($button['payload'] ?? $button['title'] ?? ''));
            if ($title === '' || $payload === '') {
                continue;
            }

            $rows[] = [['text' => $title, 'callback_data' => $this->encodeCallback($payload, $title)]];
        }

        return $rows;
    }

    // ============================================================
    // ภายใน
    // ============================================================

    /**
     * ส่งข้อความ (ตัดหลายท่อนถ้ายาว) — ปุ่มแนบไปกับท่อนสุดท้าย
     *
     * @param  array<int, array<int, array<string, string>>>|null  $keyboard
     * @param  bool  $ephemeral  true = ปุ่มชุดนี้แทนที่ชุดก่อน (quick reply) — ชุดเก่าถูกถอดออก
     */
    protected function sendTextWithKeyboard(string $recipientId, string $message, ?array $keyboard, bool $ephemeral = false): bool
    {
        $chatId = $this->chatIdOrFail($recipientId, __FUNCTION__);
        if ($chatId === '') {
            return false;
        }

        $message = trim($message);
        if ($message === '') {
            if ($keyboard === null) {
                return false;
            }
            $message = '👇';
        }

        $chunks = $this->splitMessage($message);
        $lastIndex = count($chunks) - 1;
        $delivered = 0;

        foreach ($chunks as $i => $chunk) {
            $params = [
                'chat_id' => $chatId,
                'text' => $chunk,
                // ลิงก์สินค้า/ลิงก์พันธมิตรในคำตอบ ไม่ต้องกางการ์ดตัวอย่าง — แชทรกและดูไม่เป็นมืออาชีพ
                'link_preview_options' => ['is_disabled' => true],
            ];

            if ($i === $lastIndex && $keyboard !== null && $keyboard !== []) {
                $params['reply_markup'] = ['inline_keyboard' => $keyboard];
            }

            $result = $this->call('sendMessage', $params);

            // ท่อนที่ล้มชั่วคราว (เน็ต/เซิร์ฟเวอร์) → ลองซ้ำอีกครั้งเดียว ไม่งั้นคำทำนายขาดกลางเรื่อง
            if (empty($result['ok']) && ! in_array((int) ($result['error_code'] ?? 0), [400, 403], true)) {
                usleep(800000);
                $result = $this->call('sendMessage', $params);
            }

            if (empty($result['ok'])) {
                // บล็อกบอท → ท่อนที่เหลือก็ส่งไม่ได้
                if ((int) ($result['error_code'] ?? 0) === 403) {
                    return $delivered > 0;
                }

                Log::warning('Telegram: ส่งท่อนข้อความไม่สำเร็จ', [
                    'user_id' => $recipientId,
                    'chunk' => ($i + 1).'/'.count($chunks),
                    'error_code' => $result['error_code'] ?? null,
                ]);

                continue;
            }

            $delivered++;

            if ($i === $lastIndex && $ephemeral && isset($params['reply_markup'])) {
                $this->rememberEphemeralKeyboard($recipientId, $chatId, (int) ($result['result']['message_id'] ?? 0));
            }

            if ($i < $lastIndex) {
                usleep(350000); // ~1 ข้อความ/วิ/ห้อง — กัน 429 ระหว่างท่อน
            }
        }

        // ถึงลูกค้าแล้วอย่างน้อยหนึ่งท่อน = ส่งแล้ว (ผู้เรียกบางเส้นส่งซ้ำทั้งก้อนเมื่อได้ false → ลูกค้าได้ซ้ำ)
        //    ท่อนที่หายถูก log ไว้ข้างบนแล้ว
        return $delivered > 0;
    }

    /**
     * ปุ่มชุดใหม่ออกไปแล้ว → ถอดปุ่มชุดก่อนหน้าออก (quick reply ของ FB ก็หายเมื่อมีข้อความใหม่)
     *
     * กันลูกค้ากดปุ่มเก่าที่ค้างอยู่เหนือแชท ([[rule_button_outlives_the_record_behind_it]])
     */
    protected function rememberEphemeralKeyboard(string $recipientId, string $chatId, int $messageId): void
    {
        if ($messageId <= 0) {
            return;
        }

        $key = 'tg_last_kb:'.$recipientId;
        $previous = (int) Cache::get($key, 0);
        Cache::put($key, $messageId, now()->addDays(2));

        if ($previous > 0 && $previous !== $messageId) {
            $this->clearInlineKeyboard($chatId, $previous);
        }
    }

    /**
     * ถอดปุ่ม inline ออกจากข้อความหนึ่ง (ข้อความยังอยู่)
     */
    public function clearInlineKeyboard(string $chatId, int $messageId): void
    {
        if ($chatId === '' || $messageId <= 0) {
            return;
        }

        $this->call('editMessageReplyMarkup', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => ['inline_keyboard' => []],
        ], [], 10);
    }

    /**
     * ตอบรับการกดปุ่ม (ปิดวงกลมหมุนบนปุ่มของลูกค้า) — ต้องเรียกทุกครั้งที่ได้ callback_query
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): void
    {
        $params = ['callback_query_id' => $callbackQueryId];
        if ($text !== null && $text !== '') {
            $params['text'] = mb_substr($text, 0, 180);
        }

        $this->call('answerCallbackQuery', $params, [], 10);
    }

    /**
     * การ์ด generic ของ FB → รูปพร้อมคำบรรยาย + ปุ่ม (การ์ดละข้อความ สูงสุด 10)
     *
     * @param  array<int, mixed>  $elements
     * @param  array<int, mixed>  $extraReplies
     */
    protected function sendGenericElements(string $recipientId, array $elements, array $extraReplies): bool
    {
        $elements = array_values(array_filter($elements, 'is_array'));
        if ($elements === []) {
            return false;
        }

        $anyOk = false;
        $lastIndex = min(count($elements), 10) - 1;

        foreach (array_slice($elements, 0, 10) as $i => $element) {
            $title = trim((string) ($element['title'] ?? ''));
            $subtitle = trim((string) ($element['subtitle'] ?? ''));
            $text = trim($title.($subtitle !== '' ? "\n".$subtitle : ''));

            $keyboard = $this->templateButtonsToKeyboard((array) ($element['buttons'] ?? []));

            // default_action (แตะทั้งการ์ด) → ปุ่มลิงก์ ถ้ายังไม่มีปุ่มลิงก์เดียวกัน
            $defaultUrl = trim((string) ($element['default_action']['url'] ?? ''));
            if ($defaultUrl !== '' && preg_match('#^https?://#i', $defaultUrl)
                && ! in_array($defaultUrl, array_column(array_merge(...($keyboard ?: [[]])), 'url'), true)) {
                $keyboard[] = [['text' => '🔗 เปิดดู', 'url' => $defaultUrl]];
            }

            if ($i === $lastIndex && $extraReplies !== []) {
                $extraButtons = array_values(array_filter(array_map(
                    fn ($r) => is_array($r) ? $this->normalizeButton($r) : null,
                    $extraReplies
                )));
                $keyboard = array_merge($keyboard, $this->layoutCallbackButtons($extraButtons));
            }

            $imageUrl = trim((string) ($element['image_url'] ?? ''));

            if ($imageUrl !== '') {
                $ok = $this->sendImage($recipientId, $imageUrl, null, [
                    'caption' => $text,
                    'reply_markup' => $keyboard !== [] ? ['inline_keyboard' => $keyboard] : null,
                ]);

                // รูปส่งไม่ได้ → อย่าให้ข้อความ+ปุ่มหายไปด้วย
                if (! $ok && $text !== '') {
                    $ok = $this->sendTextWithKeyboard($recipientId, $text, $keyboard !== [] ? $keyboard : null);
                }
            } else {
                $ok = $this->sendTextWithKeyboard($recipientId, $text !== '' ? $text : '👇', $keyboard !== [] ? $keyboard : null);
            }

            $anyOk = $anyOk || $ok;

            if ($i < $lastIndex) {
                usleep(350000);
            }
        }

        return $anyOk;
    }

    /**
     * ตัดข้อความยาวเป็นหลายท่อน — ตัดที่ย่อหน้า → บรรทัด → ช่องว่าง → ตัดแข็ง
     *
     * @return array<int, string>
     */
    public function splitMessage(string $message, int $maxUnits = self::MAX_TEXT_UNITS): array
    {
        $message = trim($message);
        if ($this->utf16Length($message) <= $maxUnits) {
            return [$message];
        }

        $chunks = [];
        $current = '';

        foreach (preg_split("/(\n\n)/u", $message, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$message] as $piece) {
            $candidate = $current.$piece;
            if ($this->utf16Length($candidate) <= $maxUnits) {
                $current = $candidate;

                continue;
            }

            if (trim($current) !== '') {
                $chunks[] = trim($current);
            }
            $current = '';

            // ย่อหน้าเดียวยาวเกิน → ตัดตามบรรทัด/ช่องว่าง/แข็ง
            foreach ($this->hardSplit($piece, $maxUnits) as $part) {
                if ($this->utf16Length($current.$part) <= $maxUnits) {
                    $current .= $part;
                } else {
                    if (trim($current) !== '') {
                        $chunks[] = trim($current);
                    }
                    $current = $part;
                }
            }
        }

        if (trim($current) !== '') {
            $chunks[] = trim($current);
        }

        return $chunks !== [] ? $chunks : [mb_substr($message, 0, 1000)];
    }

    /**
     * ตัดท่อนที่ยาวเกินเพดาน — บรรทัด → คำ → ตัวอักษร (ไม่ตัดกลางตัวอักษร/สระไทย)
     *
     * @return array<int, string>
     */
    protected function hardSplit(string $text, int $maxUnits): array
    {
        if ($this->utf16Length($text) <= $maxUnits) {
            return [$text];
        }

        foreach (["\n", ' '] as $separator) {
            if (! str_contains($text, $separator)) {
                continue;
            }

            $parts = [];
            $current = '';
            foreach (explode($separator, $text) as $segment) {
                $candidate = $current === '' ? $segment : $current.$separator.$segment;
                if ($this->utf16Length($candidate) <= $maxUnits) {
                    $current = $candidate;

                    continue;
                }
                if ($current !== '') {
                    $parts[] = $current.$separator;
                }
                $current = $segment;
            }
            if ($current !== '') {
                $parts[] = $current;
            }

            // ทุกท่อนต้องสั้นพอ ถ้ายังมีท่อนยาวเกิน (คำเดียวยาวมาก) ค่อยไปตัดแข็งข้างล่าง
            $allFit = true;
            foreach ($parts as $p) {
                if ($this->utf16Length($p) > $maxUnits) {
                    $allFit = false;
                    break;
                }
            }
            if ($allFit) {
                return $parts;
            }
        }

        // ตัดแข็งตามกลุ่มตัวอักษร (grapheme) — ไม่ผ่าสระ/วรรณยุกต์ไทยออกจากพยัญชนะ
        $parts = [];
        $current = '';
        preg_match_all('/\X/u', $text, $graphemes);
        foreach ($graphemes[0] as $g) {
            // PCRE บางรุ่นนับอีโมจิเรียงติดกันยาว ๆ เป็น grapheme เดียว → ยาวเกินเพดานเอง ต้องผ่าเป็นตัวอักษร
            $pieces = $this->utf16Length($g) > $maxUnits ? mb_str_split($g) : [$g];
            foreach ($pieces as $piece) {
                if ($current !== '' && $this->utf16Length($current.$piece) > $maxUnits) {
                    $parts[] = $current;
                    $current = '';
                }
                $current .= $piece;
            }
        }
        if ($current !== '') {
            $parts[] = $current;
        }

        return $parts;
    }

    /**
     * ความยาวแบบที่ Telegram นับ (UTF-16 code units) — อีโมจินับ 2
     */
    public function utf16Length(string $text): int
    {
        return intdiv(strlen(mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')), 2);
    }

    /**
     * ตัดข้อความให้ไม่เกินเพดาน (สำหรับคำบรรยายรูป) — ตัดท้ายแล้วใส่ "…"
     */
    protected function truncateUnits(string $text, int $maxUnits): string
    {
        if ($this->utf16Length($text) <= $maxUnits) {
            return $text;
        }

        preg_match_all('/\X/u', $text, $graphemes);
        $out = '';
        foreach ($graphemes[0] as $g) {
            if ($this->utf16Length($out.$g) > $maxUnits - 1) {
                break;
            }
            $out .= $g;
        }

        return rtrim($out).'…';
    }

    /**
     * URL ที่ชี้ไฟล์บนเซิร์ฟเวอร์เราเอง → path บนดิสก์ (อัปโหลดตรงได้ ไม่ต้องให้ Telegram มาดึง)
     *
     * รับเฉพาะโดเมนของแอปเรา + ไฟล์ใต้ public/ จริง (กัน path traversal ด้วย realpath)
     */
    protected function localPathForUrl(string $url): ?string
    {
        try {
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            $host = parse_url($url, PHP_URL_HOST);
            $path = parse_url($url, PHP_URL_PATH);

            if (! $appHost || ! $host || ! is_string($path) || strcasecmp($appHost, $host) !== 0) {
                return null;
            }

            $publicRoot = realpath(public_path());
            $candidate = realpath(public_path(ltrim(rawurldecode($path), '/')));

            if ($publicRoot === false || $candidate === false || ! is_file($candidate)) {
                return null;
            }

            // symlink storage → storage/app/public: realpath ออกนอก public/ ได้ ⇒ อนุญาตแค่ 2 ราก
            $storageRoot = realpath(storage_path('app/public'));
            $insidePublic = str_starts_with($candidate, $publicRoot.DIRECTORY_SEPARATOR);
            $insideStorage = $storageRoot !== false && str_starts_with($candidate, $storageRoot.DIRECTORY_SEPARATOR);

            if (! $insidePublic && ! $insideStorage) {
                return null;
            }

            $size = filesize($candidate);
            if ($size === false || $size <= 0 || $size > self::MAX_FILE_BYTES) {
                return null;
            }

            return $candidate;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ============================================================
    // รับไฟล์จากลูกค้า (รูปสลิป / รูปประกอบคำถาม)
    // ============================================================

    /**
     * ดาวน์โหลดไฟล์ที่ลูกค้าส่งมา → base64 (null = ไม่ได้/ใหญ่เกิน)
     *
     * ⚠️ URL ดาวน์โหลดมี token อยู่ในตัว — ห้าม log / ห้ามส่งต่อ URL ให้ใคร (vision/SlipOK)
     *    ต้องส่งเป็น base64 เท่านั้น
     */
    public function downloadFileAsBase64(string $fileId, int $maxBytes = self::MAX_FILE_BYTES): ?string
    {
        if ($fileId === '' || ! $this->isConfigured()) {
            return null;
        }

        $info = $this->call('getFile', ['file_id' => $fileId], [], 15);
        $filePath = (string) ($info['result']['file_path'] ?? '');
        $fileSize = (int) ($info['result']['file_size'] ?? 0);

        if (empty($info['ok']) || $filePath === '' || ! preg_match('#^[A-Za-z0-9_./-]+$#', $filePath) || str_contains($filePath, '..')) {
            return null;
        }

        if ($fileSize > $maxBytes) {
            Log::info('Telegram: ไฟล์ใหญ่เกินเพดาน — ข้าม', ['file_size' => $fileSize, 'max' => $maxBytes]);

            return null;
        }

        try {
            $response = Http::timeout(30)->get(self::API_BASE.'/file/bot'.$this->token.'/'.$filePath);
            if (! $response->successful()) {
                return null;
            }

            $body = $response->body();
            if ($body === '' || strlen($body) > $maxBytes) {
                return null;
            }

            return base64_encode($body);
        } catch (\Throwable $e) {
            Log::warning('Telegram: ดาวน์โหลดไฟล์ไม่สำเร็จ', ['error' => $this->redact($e->getMessage())]);

            return null;
        }
    }

    // ============================================================
    // หลังบ้าน: ทดสอบ / ตั้ง webhook
    // ============================================================

    /**
     * ทดสอบ token — คืนข้อมูลบอท (id, username, first_name)
     *
     * @return array{success:bool,message:string,data?:array}
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'ยังไม่ได้กรอก Bot Token'];
        }

        $result = $this->call('getMe', [], [], 10);
        if (empty($result['ok']) || ! is_array($result['result'] ?? null)) {
            $code = (int) ($result['error_code'] ?? 0);

            return [
                'success' => false,
                'message' => $code === 401 || $code === 404
                    ? 'Token ไม่ถูกต้อง — ตรวจอีกครั้งที่ @BotFather'
                    : 'เชื่อมต่อ Telegram ไม่สำเร็จ ('.$this->redact((string) ($result['description'] ?? 'ไม่ทราบสาเหตุ')).')',
            ];
        }

        $bot = $result['result'];

        return [
            'success' => true,
            'message' => 'เชื่อมต่อสำเร็จ: @'.($bot['username'] ?? '?'),
            'data' => [
                'id' => $bot['id'] ?? null,
                'username' => $bot['username'] ?? null,
                'first_name' => $bot['first_name'] ?? null,
            ],
        ];
    }

    /**
     * ตั้ง webhook ให้ Telegram ส่งข้อความมาที่เรา + ค่าลับใน header
     *
     * @return array{ok:bool,description?:string}
     */
    public function setWebhook(string $url, string $secret): array
    {
        return $this->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['message', 'callback_query', 'my_chat_member'],
            'max_connections' => 40,
            'drop_pending_updates' => false,
        ], [], 15);
    }

    /**
     * สถานะ webhook ปัจจุบัน (url, pending_update_count, last_error_message)
     *
     * @return array<string, mixed>
     */
    public function getWebhookInfo(): array
    {
        $result = $this->call('getWebhookInfo', [], [], 10);

        return is_array($result['result'] ?? null) ? $result['result'] : [];
    }

    /**
     * ตั้งเมนูคำสั่ง (ปุ่ม / มุมซ้ายล่าง) + คำอธิบายบอท — ให้ห้องแชทดูเป็นระบบตั้งแต่ครั้งแรก
     */
    public function setupBotProfile(string $brandName): void
    {
        $this->call('setMyCommands', [
            'commands' => [
                ['command' => 'start', 'description' => 'เริ่มคุยกับแม่หมอ'],
                ['command' => 'menu', 'description' => 'ดูแพคเกจดูดวง'],
                ['command' => 'help', 'description' => 'วิธีใช้งาน'],
            ],
        ], [], 10);

        $this->call('setMyShortDescription', [
            'short_description' => mb_substr($brandName.' — ดูดวง ไพ่ยิปซี โหราศาสตร์ไทย', 0, 120),
        ], [], 10);
    }
}
