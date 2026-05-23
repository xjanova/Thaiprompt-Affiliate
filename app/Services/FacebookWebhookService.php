<?php

namespace App\Services;

use App\Contracts\MessagingPlatformInterface;
use App\Models\FortuneReading;
use App\Models\FortuneResponseTemplate;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Facebook Webhook Service
 *
 * จัดการ Facebook Messenger Platform API (Graph API v21.0)
 * รับ webhook events, ส่ง messages, รูปภาพ, typing indicator
 *
 * รองรับ:
 * - ส่งข้อความยาวแบบแบ่ง (message splitting)
 * - ส่งรูปภาพผ่าน Messenger
 * - Typing indicator ระหว่างประมวลผล
 * - Webhook signature verification
 * - Quick replies buttons
 */
class FacebookWebhookService implements MessagingPlatformInterface
{
    protected $settings;

    protected $pageAccessToken;

    /**
     * ความยาวสูงสุดของข้อความ Messenger (Facebook กำหนด 2000 characters)
     */
    protected const MAX_MESSAGE_LENGTH = 2000;

    /**
     * Graph API version
     */
    protected const GRAPH_API_VERSION = 'v21.0';

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        // 🐛 (2026-05-10) Laravel auto-DI inject empty model instance — ไม่ใช่ null
        //   ทำให้ ?? fallback ไม่ทำงาน → settings ทั้งหมดเป็น null
        //   แก้: เช็ค $settings->exists (true เมื่อ load จาก DB) ก่อน fallback
        $this->settings = ($settings && $settings->exists)
            ? $settings
            : FortuneTellingSetting::getSettings();
        $this->pageAccessToken = $this->settings->facebook_page_token;
    }

    // ============================================================
    // Facebook Graph API: การส่งข้อความ
    // ============================================================

    /**
     * ส่งข้อความผ่าน Messenger API (รองรับข้อความยาว)
     *
     * ถ้าข้อความยาวเกิน 2000 ตัวอักษร จะแบ่งส่งหลาย messages อัตโนมัติ
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  string  $message  ข้อความที่ต้องการส่ง
     * @return bool สำเร็จหรือไม่
     */
    /**
     * 🌐 Default Quick Replies — แสดงในทุก DM ที่ไม่ได้ระบุ quick_replies
     *
     * 2 tier shortcuts (39฿ Deep / 99฿ Celtic) + เช็คสิทธิ์
     * Locale-aware (อ่านจาก FortuneLocaleService::current())
     * ราคาดึงจาก settings (admin override ได้)
     *
     * ⚠️ Title FB Quick Reply max 20 chars — ต้องสั้น
     *
     * @return array<int, array{content_type: string, title: string, payload: string}>
     */
    public function getDefaultQuickReplies(): array
    {
        $isLao = FortuneLocaleService::current() === FortuneLocaleService::LOCALE_LO;
        // 🌙 (2026-05-23) Deep 39฿ ปิดเพราะลูกค้าสับสน — เช็ค toggle ก่อนโชว์ปุ่ม
        $deepEnabled = $this->settings->isDeepReadingEnabled();
        $deepPrice = (int) ($this->settings->deep_reading_price ?? 39);
        $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);
        $celticPrice = 99;
        try {
            $celticPrice = (int) app(CelticCrossService::class)->getPrice();
        } catch (\Throwable $e) {
            // ใช้ default 99
        }

        $items = [];

        // 🌙 (2026-05-23) ปุ่ม Deep — แสดงเฉพาะเมื่อเปิด toggle
        if ($deepEnabled) {
            $items[] = [
                'content_type' => 'text',
                'title' => $isLao ? "🔹 ດວງ {$deepPrice}฿" : "🔹 ดูดวง {$deepPrice}฿",
                'payload' => 'TIER_DEEP_39',
            ];
        }

        if ($celticEnabled) {
            $items[] = [
                'content_type' => 'text',
                'title' => $isLao ? "🔮 ໄພ່ 10 ໃບ {$celticPrice}฿" : "🔮 ไพ่ 10 ใบ {$celticPrice}฿",
                'payload' => 'TIER_CELTIC_99',
            ];
        }

        $items[] = [
            'content_type' => 'text',
            'title' => $isLao ? '📋 ເມນູ' : '📋 เมนู',
            'payload' => 'MENU_OPEN',
        ];

        return $items;
    }

    public function sendMessage(string $recipientId, string $message, array $options = []): bool
    {
        // ตรวจสอบ Page Access Token ก่อนส่ง
        if (empty($this->pageAccessToken)) {
            Log::error('❌ ส่งข้อความไม่ได้: ไม่มี Page Access Token', [
                'recipient' => $recipientId,
            ]);

            return false;
        }

        $chunks = $this->splitLongMessage($message);
        $maxRetries = 2;

        // กำหนด messaging_type
        // - RESPONSE: ส่งภายใน 24 ชม. หลังผู้ใช้ส่งมา (default)
        // - MESSAGE_TAG: ส่งนอก 24 ชม. ต้องมี tag
        $messagingType = $options['messaging_type'] ?? 'RESPONSE';
        $messageTag = $options['message_tag'] ?? null;

        // เก็บ fallback tag ไว้ใช้กรณี RESPONSE ล้มเหลว (เกิน 24 ชม.)
        // ⚠️ HUMAN_AGENT ต้องได้รับ approval จาก Facebook ก่อนใช้
        // ✅ POST_PURCHASE_UPDATE ใช้ได้ทันทีสำหรับ update หลังชำระเงิน
        $fallbackTag = $messageTag ?? 'POST_PURCHASE_UPDATE';

        // ถ้า force_tag → ใช้ MESSAGE_TAG ทันที (admin ส่งมือจริงๆ)
        if (! empty($options['force_tag'])) {
            $messagingType = 'MESSAGE_TAG';
            $messageTag = $messageTag ?? 'POST_PURCHASE_UPDATE';
        }

        // ถ้า from_admin (ระบบส่งอัตโนมัติ) → ลอง RESPONSE ก่อน
        // เพราะส่วนใหญ่ลูกค้าเพิ่งคุยกับบอทใน 24 ชม.
        // ถ้าล้มเหลว จะ fallback เป็น MESSAGE_TAG + POST_PURCHASE_UPDATE
        if (! empty($options['from_admin']) && empty($options['force_tag'])) {
            $messagingType = 'RESPONSE';
        }

        foreach ($chunks as $chunkIndex => $chunk) {
            $sent = false;

            // ลองส่งแต่ละ chunk สูงสุด 2 ครั้ง
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $messagePayload = ['text' => $chunk];

                    // ✅ เพิ่ม Quick Reply buttons ใน chunk สุดท้าย (เฉพาะที่ caller ตั้งใจ pass มา)
                    //
                    // 🚫 (2026-05-21) เลิก auto-inject Default QR "ดูดวง 39฿ / ไพ่ 10 ใบ 99฿ / เมนู"
                    //   ใต้ทุก DM — เปลี่ยนเป็น chatbot mode (เฟรนลี่ ไม่ฮาร์ดเซล)
                    //   ลูกค้าบอก: ปุ่มราคาติดท้ายทุกข้อความ → vending machine ไม่ใช่แม่หมอ
                    //   หลังทำนายจบ ลูกค้าควรพิมพ์ "ดูดวง" เองตามที่ closing message บอก
                    //   `no_default_qr` flag กับ getDefaultQuickReplies() ยังคงไว้กันโค้ดเก่าแตก
                    $isLastChunk = ($chunkIndex === count($chunks) - 1);
                    if ($isLastChunk && ! empty($options['quick_replies'])) {
                        $messagePayload['quick_replies'] = array_map(function ($reply) {
                            return [
                                'content_type' => 'text',
                                'title' => mb_substr($reply['label'] ?? $reply['title'] ?? '', 0, 20),
                                'payload' => $reply['text'] ?? $reply['payload'] ?? $reply['label'] ?? '',
                            ];
                        }, array_slice($options['quick_replies'], 0, 13));
                    }

                    $payload = [
                        'recipient' => ['id' => $recipientId],
                        'message' => $messagePayload,
                        'messaging_type' => $messagingType,
                        'access_token' => $this->pageAccessToken,
                    ];

                    // เพิ่ม tag ถ้ามี
                    if ($messagingType === 'MESSAGE_TAG' && $messageTag) {
                        $payload['tag'] = $messageTag;
                    }

                    $response = Http::timeout(30)
                        ->post($this->graphUrl('/me/messages'), $payload);

                    // เช็ค HTTP status อย่างละเอียด
                    if ($response->successful()) {
                        $sent = true;
                        break;
                    }

                    // Facebook API error - log รายละเอียด
                    $errorBody = $response->json();
                    $errorMsg = $errorBody['error']['message'] ?? $response->body();
                    $errorCode = $errorBody['error']['code'] ?? $response->status();
                    $errorSubcode = $errorBody['error']['error_subcode'] ?? 0;

                    Log::error("❌ Facebook API Error (ครั้งที่ {$attempt})", [
                        'recipient' => $recipientId,
                        'http_status' => $response->status(),
                        'error_code' => $errorCode,
                        'error_subcode' => $errorSubcode,
                        'error_message' => $errorMsg,
                        'messaging_type' => $messagingType,
                        'tag' => $messageTag,
                        'token_prefix' => substr($this->pageAccessToken, 0, 10).'...',
                        'chunk' => $chunkIndex + 1,
                    ]);

                    // ถ้าใช้ RESPONSE แล้ว error (outside 24hr) → ลองใหม่ด้วย MESSAGE_TAG
                    // ⚠️ ใช้ POST_PURCHASE_UPDATE แทน HUMAN_AGENT (ไม่ต้องขออนุมัติ Facebook)
                    if ($messagingType === 'RESPONSE' && in_array($errorSubcode, [2018278, 2018065])) {
                        Log::info('🔄 เกิน 24 ชม. ลองใหม่ด้วย MESSAGE_TAG + POST_PURCHASE_UPDATE', [
                            'recipient' => $recipientId,
                            'fallback_tag' => $fallbackTag,
                        ]);
                        $messagingType = 'MESSAGE_TAG';
                        $messageTag = $fallbackTag;

                        continue; // retry ด้วย tag ใหม่
                    }

                    // ถ้าใช้ MESSAGE_TAG แล้วยัง error (tag ไม่ได้รับอนุมัติ) → log แจ้งเตือน
                    if ($messagingType === 'MESSAGE_TAG' && in_array($errorSubcode, [2018276])) {
                        Log::warning("⚠️ Facebook MESSAGE_TAG '{$messageTag}' ไม่ได้รับอนุมัติ", [
                            'recipient' => $recipientId,
                            'tag' => $messageTag,
                            'hint' => 'ตรวจสอบ Facebook App → Messenger → Advanced Messaging',
                        ]);

                        return false;
                    }

                    // ถ้าเป็น token error (190) หรือ permission error (10) ไม่ต้อง retry
                    if (in_array($errorCode, [190, 10, 200])) {
                        Log::error('❌ Facebook Token/Permission Error - หยุด retry', [
                            'error_code' => $errorCode,
                            'message' => $errorMsg,
                        ]);

                        return false;
                    }

                    // ✅ ผู้ใช้ block/ปิด DM/ลบบัญชี (#551 subcode 1545041) หรือ user not found (2018108)
                    //    → retry ไม่มีประโยชน์ เสีย worker time (~2s/user) — short-circuit ทันที
                    if (in_array($errorSubcode, [1545041, 2018108])) {
                        Log::info('ℹ️ ผู้ใช้ไม่พร้อมรับข้อความ — ข้าม retry', [
                            'recipient' => $recipientId,
                            'subcode' => $errorSubcode,
                            'message' => $errorMsg,
                        ]);

                        return false;
                    }

                    if ($attempt < $maxRetries) {
                        usleep(1000000); // รอ 1 วินาทีก่อน retry
                    }

                } catch (Exception $e) {
                    Log::error("❌ ส่งข้อความไม่สำเร็จ (ครั้งที่ {$attempt}): ".$e->getMessage(), [
                        'recipient' => $recipientId,
                        'chunk' => $chunkIndex + 1,
                    ]);

                    if ($attempt < $maxRetries) {
                        usleep(1000000); // รอ 1 วินาที
                    }
                }
            }

            if (! $sent) {
                Log::error('❌ ส่งข้อความล้มเหลวหลังลอง '.$maxRetries.' ครั้ง', [
                    'recipient' => $recipientId,
                    'chunk_text_preview' => mb_substr($chunk, 0, 100),
                ]);

                return false;
            }
        }

        Log::info('✅ ส่งข้อความสำเร็จ', [
            'recipient' => $recipientId,
            'chunks' => count($chunks),
        ]);

        return true;
    }

    /**
     * ส่งรูปภาพผ่าน Messenger API
     *
     * 🆕 (2026-05-07) รองรับ Private Replies endpoint สำหรับ comment engagement
     *   ถ้า $options['comment_id'] ระบุไว้ → ลอง /me/messages + recipient.comment_id ก่อน
     *   bypass 24hr window + แก้ error 551 "บุคคลนี้ไม่พร้อมใช้งาน"
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  string  $imageUrl  URL ของรูปภาพ (ต้องเป็น HTTPS public URL)
     * @param  string|null  $previewUrl  ข้อความกำกับรูป (ส่งแยก message ถ้ามี)
     * @param  array  $options  options เพิ่มเติม:
     *                          - comment_id: string|null  ใช้ Private Replies endpoint (bypass 24hr/551)
     *                          - skip_unreachable_cache: bool  ข้าม per-user 551 cache (สำหรับ test command)
     * @return bool สำเร็จหรือไม่
     */
    public function sendImage(string $recipientId, string $imageUrl, ?string $previewUrl = null, array $options = []): bool
    {
        $commentId = $options['comment_id'] ?? null;
        $skipUnreachableCache = $options['skip_unreachable_cache'] ?? false;

        // 🔒 (2026-05-07) Per-user 551-cache — skip user ที่รู้แล้วว่ารับ DM ไม่ได้วันนี้
        //   ลด log spam + ลด API calls ที่ fail แน่ๆ
        //   ยกเว้นกรณีมี comment_id → Private Replies bypass 24hr window ได้
        $unreachableKey = "fb_user_unreachable:{$recipientId}:".now()->format('Y-m-d');
        if (! $skipUnreachableCache && empty($commentId) && Cache::has($unreachableKey)) {
            Log::info('sendImage: skip — user marked unreachable today (no comment_id)', [
                'recipient' => $recipientId,
            ]);

            return false;
        }

        // 🎯 (2026-05-07) ถ้ามี comment_id → ลอง Private Replies endpoint ก่อน
        //   รองรับ 7 วันหลังคอมเม้นต์ + bypass error 551 สำหรับ user ที่ไม่เคยทักเพจ
        if (! empty($commentId)) {
            $sentViaPR = $this->sendPrivateReplyImage($commentId, $imageUrl);
            if ($sentViaPR) {
                if (! empty($previewUrl)) {
                    $this->sendMessage($recipientId, $previewUrl);
                }

                return true;
            }
            // ถ้า Private Replies fail → fallback ไป /me/messages ปกติ (อาจ work ถ้า user อยู่ใน 24hr window)
            Log::info('sendImage: Private Replies fail → fallback /me/messages', [
                'recipient' => $recipientId,
                'comment_id' => $commentId,
            ]);
        }

        // 🩹 (2026-05-07) Auto-fallback ถ้า RESPONSE fail (error 551 / 24hr window expired)
        //   เคสจริง: banner welcome push หลังลูกค้าเก่าทักอีกครั้ง — บางครั้ง 24hr window ปิดแล้ว
        //   FB error_subcode 1545041 → RESPONSE rejected → ต้อง MESSAGE_TAG=POST_PURCHASE_UPDATE
        $messagingTypes = ['RESPONSE', 'MESSAGE_TAG'];
        $lastSubcode = 0;
        $lastErrMsg = '';

        foreach ($messagingTypes as $msgType) {
            $payload = [
                'recipient' => ['id' => $recipientId],
                'message' => [
                    'attachment' => [
                        'type' => 'image',
                        'payload' => [
                            'url' => $imageUrl,
                            'is_reusable' => true,
                        ],
                    ],
                ],
                'messaging_type' => $msgType,
                'access_token' => $this->pageAccessToken,
            ];

            if ($msgType === 'MESSAGE_TAG') {
                $payload['tag'] = 'POST_PURCHASE_UPDATE';
            }

            try {
                $response = Http::timeout(30)->post($this->graphUrl('/me/messages'), $payload);

                if ($response->successful()) {
                    if (! empty($previewUrl)) {
                        $this->sendMessage($recipientId, $previewUrl);
                    }

                    Log::info('ส่งรูปภาพสำเร็จ', [
                        'recipient' => $recipientId,
                        'image_url' => $imageUrl,
                        'messaging_type' => $msgType,
                    ]);

                    return true;
                }

                $errBody = $response->json();
                $errSubcode = $errBody['error']['error_subcode'] ?? 0;
                $errMsg = $errBody['error']['message'] ?? $response->body();
                $lastSubcode = $errSubcode;
                $lastErrMsg = $errMsg;

                Log::warning('ส่งรูปภาพ '.$msgType.' fail', [
                    'recipient' => $recipientId,
                    'http_status' => $response->status(),
                    'error_subcode' => $errSubcode,
                    'error_message' => $errMsg,
                ]);

                // ถ้า RESPONSE fail ด้วย 24hr-window error → ลอง MESSAGE_TAG ต่อ
                // 1545041 = "บุคคลนี้ไม่พร้อมใช้งาน" (24hr expired)
                // 2018278/2018065 = window expired
                if ($msgType === 'RESPONSE' && in_array($errSubcode, [1545041, 2018278, 2018065])) {
                    continue; // fallback to MESSAGE_TAG
                }

                // Token/permission errors → หยุด ไม่ retry
                $errCode = $errBody['error']['code'] ?? 0;
                if (in_array($errCode, [190, 10, 200])) {
                    Log::error('ส่งรูปภาพ: token/permission error — หยุด retry', [
                        'recipient' => $recipientId,
                        'error_code' => $errCode,
                    ]);

                    return false;
                }

                // ถ้า MESSAGE_TAG ก็ fail แล้ว → return false
                if ($msgType === 'MESSAGE_TAG') {
                    Log::error('ส่งรูปภาพล้มเหลว ทั้ง RESPONSE และ MESSAGE_TAG', [
                        'recipient' => $recipientId,
                        'image_url' => $imageUrl,
                        'last_error' => $errMsg,
                    ]);
                    break; // ไป cache mark unreachable ข้างล่าง
                }
            } catch (Exception $e) {
                Log::error('ส่งรูปภาพ '.$msgType.' exception: '.$e->getMessage(), [
                    'recipient' => $recipientId,
                ]);
                if ($msgType === 'MESSAGE_TAG') {
                    break;
                }
            }
        }

        // 🔒 (2026-05-07) Mark user unreachable วันนี้ ถ้าเป็น 24hr-window error
        //   1545041 / 2018278 / 2018065 = user ไม่อยู่ใน 24hr window — ส่งภาพไม่ได้แน่ๆ จนกว่าจะทักก่อน
        //   cache กัน log spam + กัน API calls สิ้นเปลือง
        if (in_array($lastSubcode, [1545041, 2018278, 2018065])) {
            $secondsUntilMidnight = max(60, now()->endOfDay()->diffInSeconds(now(), absolute: true));
            Cache::put($unreachableKey, true, $secondsUntilMidnight);
            Log::info('sendImage: mark user unreachable today', [
                'recipient' => $recipientId,
                'subcode' => $lastSubcode,
                'ttl_seconds' => $secondsUntilMidnight,
            ]);
        }

        return false;
    }

    /**
     * ส่งภาพผ่าน Private Replies endpoint
     *
     * 🆕 (2026-05-07) แก้ปัญหา banner ส่งไม่ได้ผ่าน comment engagement
     *   เดิม: sendImage ใช้ /me/messages + recipient.id → fail 551 ถ้า user ไม่เคยทักเพจ
     *   ใหม่: ใช้ /me/messages + recipient.comment_id → bypass 24hr + รองรับ 7 วันหลังคอมเม้นต์
     *
     * @param  string  $commentId  Comment ID ที่จะตอบ
     * @param  string  $imageUrl  URL ของรูปภาพ
     */
    public function sendPrivateReplyImage(string $commentId, string $imageUrl): bool
    {
        try {
            $payload = [
                'recipient' => ['comment_id' => $commentId],
                'message' => [
                    'attachment' => [
                        'type' => 'image',
                        'payload' => [
                            'url' => $imageUrl,
                            'is_reusable' => true,
                        ],
                    ],
                ],
                'access_token' => $this->pageAccessToken,
            ];

            $response = Http::timeout(30)
                ->post($this->graphUrl('/me/messages'), $payload);

            if ($response->successful()) {
                Log::info('✅ ส่งภาพผ่าน Private Reply สำเร็จ', [
                    'comment_id' => $commentId,
                    'image_url' => $imageUrl,
                ]);

                return true;
            }

            $err = $response->json();
            Log::warning('Private Reply image ล้ม', [
                'comment_id' => $commentId,
                'http_status' => $response->status(),
                'error_code' => $err['error']['code'] ?? null,
                'error_subcode' => $err['error']['error_subcode'] ?? null,
                'error_message' => $err['error']['message'] ?? $response->body(),
            ]);

            return false;
        } catch (Exception $e) {
            Log::warning('Private Reply image exception: '.$e->getMessage(), [
                'comment_id' => $commentId,
            ]);

            return false;
        }
    }

    /**
     * 🎙️ (2026-05-08) ส่ง audio attachment ไป Facebook Messenger
     *
     * FB Audio Attachment API:
     *   - attachment.type = 'audio'
     *   - payload.url ต้องเป็น HTTPS + .mp3/.m4a (max 25MB)
     *   - is_reusable = true → cache attachment_id ใช้ซ้ำได้
     *   - รองรับทั้ง RESPONSE (24hr window) และ MESSAGE_TAG (POST_PURCHASE_UPDATE)
     *
     * Reference: https://developers.facebook.com/docs/messenger-platform/send-messages#audio
     *
     * @param  string  $recipientId  Facebook PSID
     * @param  string  $audioUrl  HTTPS URL ของ mp3 file
     * @param  array  $options  ['comment_id' => ?, 'message_tag' => 'POST_PURCHASE_UPDATE'|null]
     */
    public function sendAudio(string $recipientId, string $audioUrl, array $options = []): bool
    {
        $commentId = $options['comment_id'] ?? null;
        $messageTag = $options['message_tag'] ?? null;

        // ลองผ่าน Private Replies (comment_id) ก่อนถ้ามี
        if (! empty($commentId)) {
            try {
                $prPayload = [
                    'recipient' => ['comment_id' => $commentId],
                    'message' => [
                        'attachment' => [
                            'type' => 'audio',
                            'payload' => [
                                'url' => $audioUrl,
                                'is_reusable' => true,
                            ],
                        ],
                    ],
                    'access_token' => $this->pageAccessToken,
                ];

                $prResponse = Http::timeout(30)->post($this->graphUrl('/me/messages'), $prPayload);
                if ($prResponse->successful()) {
                    Log::info('✅ ส่ง audio ผ่าน Private Reply สำเร็จ', [
                        'comment_id' => $commentId,
                    ]);

                    return true;
                }
                Log::info('sendAudio: Private Reply fail → fallback /me/messages', [
                    'comment_id' => $commentId,
                ]);
            } catch (Exception $e) {
                Log::warning('sendAudio: Private Reply exception', ['error' => $e->getMessage()]);
            }
        }

        // ลอง RESPONSE → fallback MESSAGE_TAG (POST_PURCHASE_UPDATE)
        $messagingTypes = $messageTag ? ['MESSAGE_TAG'] : ['RESPONSE', 'MESSAGE_TAG'];

        foreach ($messagingTypes as $msgType) {
            $payload = [
                'recipient' => ['id' => $recipientId],
                'message' => [
                    'attachment' => [
                        'type' => 'audio',
                        'payload' => [
                            'url' => $audioUrl,
                            'is_reusable' => true,
                        ],
                    ],
                ],
                'messaging_type' => $msgType,
                'access_token' => $this->pageAccessToken,
            ];

            if ($msgType === 'MESSAGE_TAG') {
                $payload['tag'] = $messageTag ?: 'POST_PURCHASE_UPDATE';
            }

            try {
                $response = Http::timeout(30)->post($this->graphUrl('/me/messages'), $payload);

                if ($response->successful()) {
                    Log::info('✅ ส่ง audio สำเร็จ', [
                        'recipient' => $recipientId,
                        'audio_url' => $audioUrl,
                        'messaging_type' => $msgType,
                    ]);

                    return true;
                }

                $errBody = $response->json();
                $errSubcode = $errBody['error']['error_subcode'] ?? 0;
                $errMsg = $errBody['error']['message'] ?? $response->body();

                Log::warning('sendAudio '.$msgType.' fail', [
                    'recipient' => $recipientId,
                    'http_status' => $response->status(),
                    'error_subcode' => $errSubcode,
                    'error_message' => $errMsg,
                ]);

                // RESPONSE fail ด้วย 24hr expired → ไป MESSAGE_TAG
                if ($msgType === 'RESPONSE' && in_array($errSubcode, [1545041, 2018278, 2018065])) {
                    continue;
                }

                // permission error → หยุด
                $errCode = $errBody['error']['code'] ?? 0;
                if (in_array($errCode, [190, 10, 200])) {
                    return false;
                }
            } catch (Exception $e) {
                Log::warning('sendAudio exception: '.$e->getMessage(), [
                    'recipient' => $recipientId,
                    'messaging_type' => $msgType,
                ]);
            }
        }

        return false;
    }

    /**
     * ส่ง typing indicator (แสดงจุดสามจุดว่ากำลังพิมพ์)
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  bool  $on  เปิด/ปิด typing indicator
     */
    public function sendTypingIndicator(string $recipientId, bool $on = true): void
    {
        try {
            Http::timeout(10)
                ->post($this->graphUrl('/me/messages'), [
                    'recipient' => ['id' => $recipientId],
                    'sender_action' => $on ? 'typing_on' : 'typing_off',
                    'access_token' => $this->pageAccessToken,
                ]);
        } catch (Exception $e) {
            // ไม่ต้อง throw error ถ้า typing indicator ส่งไม่ได้
            Log::debug('ส่ง typing indicator ไม่สำเร็จ: '.$e->getMessage());
        }
    }

    /**
     * ส่งข้อความพร้อม Quick Reply buttons
     *
     * รองรับ options:
     * - messaging_type: 'RESPONSE' (default) หรือ 'MESSAGE_TAG'
     * - message_tag: tag สำหรับ MESSAGE_TAG เช่น 'POST_PURCHASE_UPDATE'
     * - from_comment_engagement: true เมื่อเรียกจาก comment engagement
     *   → จะ fallback เป็น MESSAGE_TAG อัตโนมัติถ้า RESPONSE ล้มเหลว
     * - comment_id: ถ้ามี + from_comment_engagement=true จะลองส่งผ่าน Private Replies
     *   endpoint (/{comment-id}/private_replies) ก่อน เพื่อ bypass 24hr window และ
     *   error 551 "บุคคลนี้ไม่พร้อมใช้งาน" (user ไม่เคยทักเพจมาก่อน)
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  string  $message  ข้อความหลัก
     * @param  array  $quickReplies  ปุ่ม quick reply [['title' => 'ข้อความ', 'payload' => 'DATA']]
     * @param  array  $options  ตัวเลือกเพิ่มเติม
     */
    public function sendQuickReplies(string $recipientId, string $message, array $quickReplies, array $options = []): bool
    {
        try {
            $formattedReplies = array_map(function ($reply) {
                return [
                    'content_type' => 'text',
                    'title' => mb_substr($reply['title'], 0, 20),
                    'payload' => $reply['payload'] ?? $reply['title'],
                ];
            }, array_slice($quickReplies, 0, 13)); // Facebook จำกัด 13 quick replies

            // 🚨 (2026-05-14) Bug fix — message + quick_replies > 2000 chars → FB reject 400
            //   user report: "AI เหมือนจะตอบแต่เงียบ"
            //   เคสจริง reading 2537: message_length=2874 + quick_replies → HTTP 400 → ข้อความไม่ถึงลูกค้า
            //   Fix: ถ้ายาว → split + ส่ง body ผ่าน sendMessage (มี chunking) + last chunk แนบ quick_replies
            //   Threshold: 1800 chars (เผื่อ JSON overhead + quick_replies metadata ~150 chars)
            if (mb_strlen($message) > 1800) {
                $allChunks = $this->splitLongMessage($message);
                $lastChunk = array_pop($allChunks);

                // ส่ง chunks 1..N-1 เป็น plain message (sendMessage มี chunking + retry เอง)
                foreach ($allChunks as $chunk) {
                    $this->sendMessage($recipientId, $chunk, [
                        'messaging_type' => $options['messaging_type'] ?? 'RESPONSE',
                        'message_tag' => $options['message_tag'] ?? null,
                        'no_default_qr' => true,
                        'from_admin' => $options['from_admin'] ?? false,
                    ]);
                    usleep(300000); // 0.3s gap between chunks
                }

                // แทนที่ message ด้วย last chunk + ส่งต่อด้านล่าง (จะมี quick_replies แนบ)
                $message = $lastChunk;
            }

            $messagingType = $options['messaging_type'] ?? 'RESPONSE';
            $messageTag = $options['message_tag'] ?? null;
            $fromCommentEngagement = $options['from_comment_engagement'] ?? false;
            $commentId = $options['comment_id'] ?? null;
            $fromAdmin = $options['from_admin'] ?? false;

            // 🔧 (2026-05-03) ถ้า from_admin + ระบุ message_tag → ใช้ MESSAGE_TAG ตั้งแต่แรก
            //   เคสหลัก: SMS payment confirmation push หลัง 24hr (ลูกค้าจ่ายแล้วเงียบ)
            if ($fromAdmin && ! empty($messageTag)) {
                $messagingType = 'MESSAGE_TAG';
            }

            // ถ้าเป็น comment engagement + มี comment_id → ลอง Private Replies ก่อน
            // Private Replies รองรับ 7 วันหลังคอมเม้นต์ และไม่ต้องอยู่ใน conversation window
            // แก้ปัญหา error 551 "บุคคลนี้ไม่พร้อมใช้งาน"
            if ($fromCommentEngagement && $commentId) {
                $privateReplySuccess = $this->sendPrivateReply($commentId, $message, $formattedReplies);
                if ($privateReplySuccess) {
                    return true;
                }
                // ถ้า Private Replies ล้มเหลว → fallback ไปยัง /me/messages ข้างล่าง
                Log::info('🔄 Private Replies ล้มเหลว → fallback ไปยัง /me/messages', [
                    'comment_id' => $commentId,
                    'recipient' => $recipientId,
                ]);
            }

            // 🩹 (2026-05-04) Defensive guard — ถ้า quick_replies ว่าง (caller ตั้งใจไม่มีปุ่ม)
            //    Facebook reject empty array ใน quick_replies key
            //    → ส่งข้อความปกติแทน (ไม่ใส่ quick_replies key) ผ่าน sendMessage
            //    เพื่อรักษา 24hr-fallback + chunk-split ใน sendMessage
            if (empty($formattedReplies)) {
                return $this->sendMessage($recipientId, $message, [
                    'messaging_type' => $messagingType,
                    'message_tag' => $messageTag,
                    'no_default_qr' => true,
                    'from_admin' => $fromAdmin,
                ]);
            }

            $payload = [
                'recipient' => ['id' => $recipientId],
                'message' => [
                    'text' => $message,
                    'quick_replies' => $formattedReplies,
                ],
                'messaging_type' => $messagingType,
                'access_token' => $this->pageAccessToken,
            ];

            if ($messagingType === 'MESSAGE_TAG' && $messageTag) {
                $payload['tag'] = $messageTag;
            }

            $response = Http::timeout(30)
                ->post($this->graphUrl('/me/messages'), $payload);

            if ($response->successful()) {
                return true;
            }

            // ตรวจสอบ error เพื่อ fallback
            $errorBody = $response->json();
            $errorSubcode = $errorBody['error']['error_subcode'] ?? 0;
            $errorCode = $errorBody['error']['code'] ?? 0;

            // กรณี 24hr expired → ลองใหม่ด้วย MESSAGE_TAG + POST_PURCHASE_UPDATE
            // 🔧 (2026-05-03) ขยายเงื่อนไข — ไม่ต้องเป็นแค่ comment engagement
            //   เคสที่กันตอนนี้: SMS payment confirmation push หลัง 24hr (ลูกค้า paid แล้วเงียบ)
            $is24hrError = in_array($errorSubcode, [2018278, 2018065, 2018001]);
            if ($is24hrError && $messagingType === 'RESPONSE') {
                Log::info('🔄 Quick Replies: RESPONSE ล้มเหลว (24hr expired) → ลองใหม่ด้วย MESSAGE_TAG', [
                    'recipient' => $recipientId,
                    'error_subcode' => $errorSubcode,
                    'from_comment_engagement' => $fromCommentEngagement,
                    'from_admin' => $fromAdmin,
                ]);

                $payload['messaging_type'] = 'MESSAGE_TAG';
                $payload['tag'] = $messageTag ?? 'POST_PURCHASE_UPDATE';

                $retryResponse = Http::timeout(30)
                    ->post($this->graphUrl('/me/messages'), $payload);

                if ($retryResponse->successful()) {
                    return true;
                }

                Log::warning('⚠️ Quick Replies: MESSAGE_TAG fallback ล้มเหลวเช่นกัน', [
                    'recipient' => $recipientId,
                    'error' => $retryResponse->json()['error']['message'] ?? $retryResponse->body(),
                ]);
            }

            // Token/Permission error → ไม่ต้อง fallback
            if (in_array($errorCode, [190, 10, 200])) {
                Log::error('❌ Quick Replies: Token/Permission Error', [
                    'recipient' => $recipientId,
                    'error_code' => $errorCode,
                    'error' => $errorBody['error']['message'] ?? '',
                ]);

                return false;
            }

            // throw เพื่อให้ catch ข้างล่าง fallback เป็นข้อความธรรมดา
            $response->throw();

            return true;
        } catch (Exception $e) {
            Log::error('ส่ง quick replies ไม่สำเร็จ: '.$e->getMessage());

            // Fallback: ส่งข้อความธรรมดา
            return $this->sendMessage($recipientId, $message);
        }
    }

    /**
     * ตอบกลับในคอมเมนต์
     *
     * @param  string  $commentId  Comment ID
     * @param  string  $message  ข้อความตอบกลับ
     */
    public function replyToComment(string $commentId, string $message): bool
    {
        try {
            Http::timeout(30)
                ->post($this->graphUrl("/{$commentId}/comments"), [
                    'message' => mb_substr($message, 0, 8000), // Facebook comment limit
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            Log::info('ตอบคอมเมนต์สำเร็จ', ['comment_id' => $commentId]);

            return true;
        } catch (Exception $e) {
            // HTTP 403 = token ไม่มี pages_manage_engagement permission
            // ต้องไปขออนุมัติ App Review ที่ Facebook Developer Console
            $msg = $e->getMessage();
            $is403 = str_contains($msg, '403');
            Log::error('ตอบคอมเมนต์ไม่สำเร็จ: '.$msg, [
                'comment_id' => $commentId,
                'hint' => $is403
                    ? '⚠️ HTTP 403 → Page Access Token ขาด pages_manage_engagement scope — เช็คที่ Facebook Dev Console'
                    : null,
            ]);

            return false;
        }
    }

    /**
     * 💗 ส่ง reaction (LIKE/LOVE/HAHA/WOW/SAD/ANGRY) ให้ comment
     *
     * ใช้ Graph API: POST /{comment-id}/reactions?type=LOVE
     *
     * ผลพลอยได้:
     * - FB algorithm เห็นว่า Page engage กับ comment → boost reach
     * - User รู้สึกว่าเพจ active + ใส่ใจ
     * - ลูกค้าจะ react กลับมาเยอะขึ้น (ส่งสัญญาณดีให้ FB)
     *
     * @param  string  $type  LIKE | LOVE | HAHA | WOW | SAD | ANGRY (default: LOVE ❤️)
     */
    public function reactToComment(string $commentId, string $type = 'LOVE'): bool
    {
        try {
            Http::timeout(15)
                ->post($this->graphUrl("/{$commentId}/reactions"), [
                    'type' => $type,
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            Log::info('💗 React comment สำเร็จ', [
                'comment_id' => $commentId,
                'type' => $type,
            ]);

            return true;
        } catch (Exception $e) {
            $msg = $e->getMessage();
            // 100/3 = comment ไม่พบหรือถูกลบ
            // 403 = token ขาด pages_manage_engagement
            Log::warning('React comment ล้มเหลว: '.mb_substr($msg, 0, 200), [
                'comment_id' => $commentId,
                'type' => $type,
            ]);

            return false;
        }
    }

    /**
     * 🙈 ซ่อนคอมเม้นต์ (ผู้คอมยังเห็นเอง คนอื่นไม่เห็น)
     *
     * ใช้ Graph API: POST /{comment-id} body is_hidden=true
     * ต้องการ scope: pages_manage_engagement
     *
     * แนะนำใช้แทน deleteComment เพราะ:
     * - ผู้คอมไม่รู้ตัวว่าโดนซ่อน → ไม่กลับมาแก้แค้น
     * - กลับคืนได้ (set is_hidden=false)
     * - ไม่กระทบ FB algorithm signal เหมือน delete
     */
    public function hideComment(string $commentId): bool
    {
        try {
            Http::timeout(15)
                ->post($this->graphUrl("/{$commentId}"), [
                    'is_hidden' => 'true',
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            Log::info('🙈 ซ่อนคอมเม้นต์สำเร็จ', ['comment_id' => $commentId]);

            return true;
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $is403 = str_contains($msg, '403');
            Log::error('ซ่อนคอมเม้นต์ไม่สำเร็จ: '.mb_substr($msg, 0, 200), [
                'comment_id' => $commentId,
                'hint' => $is403
                    ? '⚠️ HTTP 403 → Page Token ขาด pages_manage_engagement scope'
                    : null,
            ]);

            return false;
        }
    }

    /**
     * 🗑️ ลบคอมเม้นต์ (ลบถาวร — irreversible)
     *
     * ใช้ Graph API: DELETE /{comment-id}
     * ต้องการ scope: pages_manage_engagement
     *
     * ⚠️ ใช้เฉพาะกรณีจำเป็น (สแปมหนักๆ ที่ใส่หาก) — ปกติแนะนำ hideComment กว่า
     */
    public function deleteComment(string $commentId): bool
    {
        try {
            Http::timeout(15)
                ->delete($this->graphUrl("/{$commentId}"), [
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            Log::info('🗑️ ลบคอมเม้นต์สำเร็จ', ['comment_id' => $commentId]);

            return true;
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $is403 = str_contains($msg, '403');
            Log::error('ลบคอมเม้นต์ไม่สำเร็จ: '.mb_substr($msg, 0, 200), [
                'comment_id' => $commentId,
                'hint' => $is403
                    ? '⚠️ HTTP 403 → Page Token ขาด pages_manage_engagement scope'
                    : null,
            ]);

            return false;
        }
    }

    /**
     * 🔍 ตรวจว่าข้อความมีลิงค์ภายนอก (ที่ไม่อยู่ใน whitelist) หรือไม่
     *
     * จับรูปแบบทั่วไป:
     * - URL: http://, https://, www.xxx
     * - Shorteners: bit.ly, t.me, t.co, lin.ee/<id>, m.me/<id>
     * - Lazy domain: xxx.com, xxx.net, xxx.io (ที่ไม่มี protocol)
     * - Dot-evasion: "thaiprompt dot online" → ก็จับ
     *
     * @param  array<string>  $whitelistDomains  โดเมนที่อนุญาต (ไม่ตรวจ scheme)
     * @return bool true = พบลิงค์ที่ไม่ใช่ whitelist
     */
    public function containsExternalLink(string $message, array $whitelistDomains = []): bool
    {
        $normalized = mb_strtolower($message);

        // Normalize "dot" / "จุด" evasion → "."
        $normalized = preg_replace('/\s+(dot|จุด)\s+/u', '.', $normalized);

        // Pattern: protocol/www/shortener/TLD
        $pattern = '/(https?:\/\/|www\.|[a-z0-9-]+\.(com|net|io|me|co|xyz|info|biz|org|shop|store|app|tk|ml|ga|cf)(\/|\b))/i';

        if (! preg_match_all($pattern, $normalized, $matches)) {
            return false;
        }

        // ทุก match → strip ออกมาเป็น domain เพียงๆ → เช็ค whitelist
        foreach ($matches[0] as $hit) {
            $domain = $this->extractDomain($hit);
            if (empty($domain)) {
                continue;
            }

            $isWhitelisted = false;
            foreach ($whitelistDomains as $allowed) {
                $allowed = mb_strtolower(trim($allowed));
                if (empty($allowed)) {
                    continue;
                }
                // exact หรือ subdomain match
                if ($domain === $allowed || str_ends_with($domain, '.'.$allowed)) {
                    $isWhitelisted = true;
                    break;
                }
            }

            if (! $isWhitelisted) {
                return true; // พบลิงค์ที่ไม่ใช่ whitelist
            }
        }

        return false;
    }

    /**
     * Helper: แยก domain จากชิ้น match (best-effort)
     */
    protected function extractDomain(string $hit): string
    {
        $hit = mb_strtolower(trim($hit));
        $hit = preg_replace('#^https?://#', '', $hit);
        $hit = preg_replace('#^www\.#', '', $hit);
        // ตัด path ทิ้ง
        $hit = explode('/', $hit, 2)[0];

        return rtrim($hit, '.');
    }

    /**
     * 📜 ดึงโพสของเพจย้อนหลัง (สำหรับ scan คอมเก่า)
     *
     * ใช้ /{page}/published_posts (ครอบคลุมกว่า /posts) — รวม Reels ใน Graph v17+
     *
     * @param  int  $sinceTimestamp  unix timestamp ตั้งแต่เมื่อไหร่
     * @param  int  $limit  จำนวนโพสสูงสุด (default 100)
     * @return array<array{id:string, created_time:string}>
     */
    public function listRecentPosts(int $sinceTimestamp, int $limit = 100): array
    {
        return $this->fetchPagedFeed("/published_posts", $sinceTimestamp, $limit);
    }

    /**
     * 🎬 ดึง Reels ของเพจย้อนหลัง (สแปมในคลิปไวรัลมักเยอะ)
     *
     * Graph API endpoint /{page}/video_reels — แยกจาก /published_posts บางครั้ง
     *
     * @return array<array{id:string, created_time:string}>
     */
    public function listRecentReels(int $sinceTimestamp, int $limit = 100): array
    {
        return $this->fetchPagedFeed('/video_reels', $sinceTimestamp, $limit);
    }

    /**
     * Helper: paginate Graph API feed-style endpoint
     */
    protected function fetchPagedFeed(string $relPath, int $sinceTimestamp, int $limit): array
    {
        $this->lastFetchError = null; // reset
        $pageId = $this->settings->facebook_page_id ?? null;
        if (empty($pageId)) {
            $this->lastFetchError = 'ไม่พบ facebook_page_id ใน FortuneTellingSetting';

            return [];
        }
        if (empty($this->pageAccessToken)) {
            $this->lastFetchError = 'ไม่พบ facebook_page_token ใน FortuneTellingSetting';

            return [];
        }

        $items = [];
        $url = $this->graphUrl("/{$pageId}{$relPath}");
        $params = [
            'access_token' => $this->pageAccessToken,
            'fields' => 'id,created_time',
            'since' => $sinceTimestamp,
            'limit' => 25,
        ];

        try {
            while (count($items) < $limit && $url) {
                $resp = Http::timeout(20)->get($url, $params);
                if (! $resp->successful()) {
                    $errorMsg = $resp->json('error.message') ?? 'HTTP '.$resp->status();
                    $errorCode = $resp->json('error.code');
                    $errorType = $resp->json('error.type');
                    $this->lastFetchError = "Graph API {$relPath} → HTTP {$resp->status()} | code={$errorCode} | type={$errorType} | {$errorMsg}";
                    Log::warning("fetchPagedFeed ล้มเหลว ({$relPath})", [
                        'status' => $resp->status(),
                        'error' => $resp->json('error'),
                        'page_id' => $pageId,
                    ]);
                    break;
                }
                $data = $resp->json('data', []);
                foreach ($data as $row) {
                    $items[] = $row;
                    if (count($items) >= $limit) {
                        break;
                    }
                }
                $url = $resp->json('paging.next');
                $params = []; // pagination URL มี params ในตัวแล้ว
            }
        } catch (Exception $e) {
            $this->lastFetchError = 'Exception: '.$e->getMessage();
            Log::warning("fetchPagedFeed exception ({$relPath}): ".$e->getMessage());
        }

        return $items;
    }

    /** Last error message from fetchPagedFeed — for command-level diagnostics */
    public ?string $lastFetchError = null;

    /**
     * 💬 ดึงคอมเม้นต์ทั้งหมดของโพสหนึ่ง (paginated)
     *
     * @return array<array{id:string, message:string, from?:array}>
     */
    public function listCommentsForPost(string $postId, int $limit = 200): array
    {
        $this->lastFetchError = null;
        if (empty($this->pageAccessToken)) {
            $this->lastFetchError = 'ไม่พบ pageAccessToken';

            return [];
        }

        $comments = [];
        $url = $this->graphUrl("/{$postId}/comments");
        $params = [
            'access_token' => $this->pageAccessToken,
            'fields' => 'id,message,from,is_hidden,created_time',
            // ❌ filter=stream เคยทำให้ comments หายในบางเคส — ใช้ default (toplevel)
            'limit' => 50,
        ];

        try {
            while (count($comments) < $limit && $url) {
                $resp = Http::timeout(20)->get($url, $params);
                if (! $resp->successful()) {
                    $errorMsg = $resp->json('error.message') ?? 'HTTP '.$resp->status();
                    $errorCode = $resp->json('error.code');
                    $this->lastFetchError = "comments {$postId} → HTTP {$resp->status()} | code={$errorCode} | {$errorMsg}";
                    Log::warning('listCommentsForPost ล้มเหลว', [
                        'post_id' => $postId,
                        'status' => $resp->status(),
                        'error' => $resp->json('error'),
                    ]);
                    break;
                }
                $data = $resp->json('data', []);
                foreach ($data as $row) {
                    $comments[] = $row;
                    if (count($comments) >= $limit) {
                        break;
                    }
                }
                $url = $resp->json('paging.next');
                $params = [];
            }
        } catch (Exception $e) {
            $this->lastFetchError = 'Exception: '.$e->getMessage();
            Log::warning('listCommentsForPost exception: '.$e->getMessage(), [
                'post_id' => $postId,
            ]);
        }

        return $comments;
    }

    /**
     * ส่งข้อความ Private Reply ตอบคอมเม้นต์ (ไป DM/Messenger)
     *
     * ใช้ endpoint POST /{comment-id}/private_replies ซึ่ง:
     * - รองรับการส่ง DM ตอบกลับคอมเม้นต์ได้ 7 วันหลังคอมเม้นต์
     * - ไม่ต้องอยู่ใน 24-hour conversation window
     * - แก้ปัญหา error 551 "บุคคลนี้ไม่พร้อมใช้งาน" สำหรับ user ที่ไม่เคยทักเพจ
     *
     * Flow:
     * 1. ลอง structured message (text + quick_replies) ก่อน
     * 2. ถ้า FB ปฏิเสธ structure → fallback เป็น text-only
     *
     * @param  string  $commentId  Comment ID ที่จะตอบ
     * @param  string  $message  ข้อความที่จะส่งไป DM
     * @param  array  $quickReplies  Formatted quick replies (optional)
     */
    public function sendPrivateReply(string $commentId, string $message, array $quickReplies = []): bool
    {
        try {
            // 🎯 (2026-05-04) ลอง Send API + recipient.comment_id ก่อนเสมอ (รวมเคส empty quick_replies)
            //   เหตุผล: endpoint นี้รองรับ Reels comment ที่ /private_replies endpoint ไม่รองรับ
            //   (regression จาก commit 67bbbd4da ที่ลบปุ่ม Quick Reply → fall back ไป /private_replies
            //    → Reels comment_id error 100 "Object does not exist")
            //   ส่ง message เป็น array (ไม่ json_encode) เพราะ Laravel Http::post serialize ให้
            $messagePayload = ['text' => $message];
            if (! empty($quickReplies)) {
                $messagePayload['quick_replies'] = $quickReplies;
            }

            $structuredPayload = [
                'recipient' => ['comment_id' => $commentId],
                'message' => $messagePayload,
                'access_token' => $this->pageAccessToken,
            ];

            $response = Http::timeout(30)
                ->post($this->graphUrl('/me/messages'), $structuredPayload);

            if ($response->successful()) {
                Log::info('✅ Private Reply สำเร็จ (Send API + recipient.comment_id)', [
                    'comment_id' => $commentId,
                    'has_quick_replies' => ! empty($quickReplies),
                ]);

                return true;
            }

            Log::info('Private Reply (Send API) ล้มเหลว → fallback text-only', [
                'comment_id' => $commentId,
                'error' => $response->json()['error']['message'] ?? $response->body(),
            ]);

            // Fallback: text-only ผ่าน /{comment-id}/private_replies
            $textResponse = Http::timeout(30)
                ->post($this->graphUrl("/{$commentId}/private_replies"), [
                    'message' => $message,
                    'access_token' => $this->pageAccessToken,
                ]);

            if ($textResponse->successful()) {
                Log::info('✅ Private Reply สำเร็จ (text-only)', [
                    'comment_id' => $commentId,
                ]);

                return true;
            }

            $errorBody = $textResponse->json();
            Log::warning('Private Reply ล้มเหลว', [
                'comment_id' => $commentId,
                'http_status' => $textResponse->status(),
                'error_code' => $errorBody['error']['code'] ?? null,
                'error_subcode' => $errorBody['error']['error_subcode'] ?? null,
                'error_message' => $errorBody['error']['message'] ?? $textResponse->body(),
            ]);

            return false;
        } catch (Exception $e) {
            Log::warning('Private Reply exception: '.$e->getMessage(), [
                'comment_id' => $commentId,
            ]);

            return false;
        }
    }

    // ============================================================
    // Facebook Graph API: การดึงข้อมูลผู้ใช้
    // ============================================================

    /**
     * ดึงข้อมูลโปรไฟล์จาก Facebook Graph API
     */
    public function getUserProfile(string $facebookUserId): ?array
    {
        // 🚀 (2026-05-06) Cache 24hr — กัน sync HTTP call ทุกข้อความ (200-800ms)
        //   FB profile name ไม่เปลี่ยนบ่อย — 24hr TTL พอ
        //   ถ้า cache hit → return ทันที (ไม่ทำ HTTP call)
        $cacheKey = "fb:user_profile:{$facebookUserId}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['name'])) {
            return $cached;
        }

        try {
            // ดึงข้อมูลพื้นฐาน + ลอง fields เพิ่มเติม (gender, birthday, locale)
            // หมายเหตุ: gender/birthday/locale อาจไม่ได้รับจาก PSID เนื่องจาก Facebook API restrictions
            // แต่ใส่ไว้เผื่อ App ได้รับ permission แล้ว
            //
            // 🩹 (2026-05-08) Try minimal fields first — ลด FB privacy 400 error
            //   FB Graph 2018+ privacy: full fields list อาจ trigger 400 ถ้า App ไม่มี advanced perms
            //   ลำดับ: name+first_name+last_name (สำคัญสุด) → fallback ถ้ายัง fail
            $response = Http::timeout(15)
                ->get($this->graphUrl("/{$facebookUserId}"), [
                    'fields' => 'id,name,first_name,last_name,profile_pic',
                    'access_token' => $this->pageAccessToken,
                ]);

            // 🩹 ถ้า 400 — ลอง minimal fields (id+first_name+last_name อย่างเดียว)
            if (! $response->successful() && $response->status() === 400) {
                Log::info('FB getUserProfile: HTTP 400 — retry with minimal fields', [
                    'user_id' => $facebookUserId,
                    'first_attempt_body' => mb_substr($response->body(), 0, 300),
                ]);
                $response = Http::timeout(15)
                    ->get($this->graphUrl("/{$facebookUserId}"), [
                        'fields' => 'first_name,last_name',
                        'access_token' => $this->pageAccessToken,
                    ]);
            }

            if (! $response->successful()) {
                throw new Exception('FB Graph HTTP '.$response->status().': '.mb_substr($response->body(), 0, 300));
            }

            $profile = $response->json();

            // ⚠️ FB Graph API Privacy (2018+): field `name` บางครั้งไม่ return
            // แต่ `first_name` / `last_name` มักจะได้ → compose ให้ always มี `name` field
            if (empty($profile['name'])) {
                $composed = trim(
                    ($profile['first_name'] ?? '').' '.($profile['last_name'] ?? '')
                );
                if ($composed !== '') {
                    $profile['name'] = $composed;
                }
            }

            // ถ้าได้ birthday มา → คำนวณอายุ
            if (! empty($profile['birthday'])) {
                try {
                    $birthDate = Carbon::parse($profile['birthday']);
                    $profile['age'] = $birthDate->age;
                    $profile['birth_date_formatted'] = $birthDate->format('Y-m-d');
                } catch (Exception $e) {
                    // birthday format อาจเป็นแค่ MM/DD
                }
            }

            Log::info('ดึงโปรไฟล์ผู้ใช้สำเร็จ', [
                'user_id' => $facebookUserId,
                'has_name' => ! empty($profile['name']),
                'has_first_name' => ! empty($profile['first_name']),
                'has_gender' => ! empty($profile['gender']),
                'has_birthday' => ! empty($profile['birthday']),
                'has_locale' => ! empty($profile['locale']),
            ]);

            // 💾 cache profile 24hr (เฉพาะตอนได้ name) — กัน HTTP roundtrip ทุก message
            if (! empty($profile['name'])) {
                Cache::put($cacheKey, $profile, now()->addHours(24));
            }

            return $profile;
        } catch (Exception $e) {
            // 🩹 (2026-05-08) เพิ่ม error body + token snippet ใน log — debug FB privacy/token issues
            //    เคสที่พบ: HTTP 400 ทุก call → เคส page token expired หรือ permission revoked
            Log::warning('ไม่สามารถดึงโปรไฟล์ผู้ใช้ได้', [
                'user_id' => $facebookUserId,
                'error' => $e->getMessage(),
                'token_first_8' => mb_substr($this->pageAccessToken ?? '', 0, 8),
                'token_len' => strlen($this->pageAccessToken ?? ''),
                'hint' => 'ถ้าทุก request 400 → page token หมดอายุ — ตรวจ admin/fortune/channels',
            ]);

            return null;
        }
    }

    /**
     * ดึงโพสล่าสุดของผู้ใช้ (ถ้ามี permission)
     *
     * @param  int  $limit  จำนวนโพสที่ต้องการ
     */
    public function getUserPosts(string $facebookUserId, int $limit = 3): ?array
    {
        try {
            $response = Http::timeout(15)
                ->get($this->graphUrl("/{$facebookUserId}/posts"), [
                    'fields' => 'message,story,created_time',
                    'limit' => $limit,
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            $data = $response->json();

            return $data['data'] ?? [];
        } catch (Exception $e) {
            Log::warning('ไม่สามารถดึงโพสของผู้ใช้ได้: '.$e->getMessage());

            return null;
        }
    }

    /**
     * ดึงรูปภาพจาก Messenger attachment
     *
     * @param  array  $attachments  Facebook message attachments array
     * @return string|null URL ของรูปภาพ
     */
    public function extractImageFromAttachments(array $attachments): ?string
    {
        foreach ($attachments as $attachment) {
            if (($attachment['type'] ?? '') === 'image') {
                return $attachment['payload']['url'] ?? null;
            }
        }

        return null;
    }

    // ============================================================
    // การแยกประเภทคำขอและข้อความ
    // ============================================================

    /**
     * ตรวจสอบว่าเป็นคำขอดูดวงเชิงลึกหรือไม่
     *
     * รูปแบบ: "ดูดวงละเอียด", "ดูดวงเชิงลึก", "ดูดวงแบบละเอียด", "ดูดวงdeep"
     */
    public function isDeepReadingRequest(string $message): bool
    {
        $trimmed = trim($message);

        return (bool) preg_match('/^ดูดวง(ละเอียด|เชิงลึก|แบบละเอียด|deep)/u', $trimmed);
    }

    /**
     * แยกคำถามจากข้อความ
     *
     * รูปแบบ: "ดูดวง เรื่องความรัก เรื่องการเงิน เรื่องสุขภาพ"
     * หรือ: "ดูดวงละเอียด เรื่องความรัก, เรื่องการเงิน"
     */
    public function parseQuestions(string $message): ?array
    {
        // ตรวจสอบว่าข้อความขึ้นต้นด้วย "ดูดวง" หรือไม่
        if (! preg_match('/^ดูดวง/u', trim($message))) {
            return null;
        }

        // ลบคำว่า "ดูดวง" พร้อมคำขยาย
        $text = preg_replace('/^ดูดวง(ละเอียด|เชิงลึก|แบบละเอียด|deep)?\s*/u', '', trim($message));

        // ถ้าไม่มีคำถาม ใช้คำถามเริ่มต้น
        $text = trim($text);
        if (empty($text)) {
            return ['ดูดวงทั่วไป ความรัก การเงิน การงาน สุขภาพ'];
        }

        // แยกคำถามตามเครื่องหมาย หรือ ขึ้นบรรทัดใหม่
        $questions = preg_split('/[\n,]/', $text);

        // กรองคำถามที่ว่าง
        $questions = array_filter(array_map('trim', $questions));

        // จำกัดไม่เกิน 5 คำถาม
        if (count($questions) > 5) {
            $questions = array_slice($questions, 0, 5);
        }

        return ! empty($questions) ? array_values($questions) : null;
    }

    /**
     * แยกวันเกิดจากข้อความผู้ใช้
     *
     * รองรับรูปแบบ:
     * - "เกิด 15 มกราคม 2540" / "เกิดวันที่ 15/01/2540"
     * - "วันเกิด 15-01-1997" / "วันเกิด 15 ม.ค. 40"
     * - "เกิด 15/1/40" / "เกิด 15/1/97"
     *
     * @param  string  $message  ข้อความจากผู้ใช้
     * @return string|null วันเกิดในรูปแบบ Y-m-d (ค.ศ.) หรือ null ถ้าไม่พบ
     */
    public function parseBirthDate(string $message): ?string
    {
        $thaiMonths = [
            'มกราคม' => 1, 'ม.ค.' => 1, 'มค' => 1,
            'กุมภาพันธ์' => 2, 'ก.พ.' => 2, 'กพ' => 2,
            'มีนาคม' => 3, 'มี.ค.' => 3, 'มีค' => 3,
            'เมษายน' => 4, 'เม.ย.' => 4, 'เมย' => 4,
            'พฤษภาคม' => 5, 'พ.ค.' => 5, 'พค' => 5,
            'มิถุนายน' => 6, 'มิ.ย.' => 6, 'มิย' => 6,
            'กรกฎาคม' => 7, 'ก.ค.' => 7, 'กค' => 7,
            'สิงหาคม' => 8, 'ส.ค.' => 8, 'สค' => 8,
            'กันยายน' => 9, 'ก.ย.' => 9, 'กย' => 9,
            'ตุลาคม' => 10, 'ต.ค.' => 10, 'ตค' => 10,
            'พฤศจิกายน' => 11, 'พ.ย.' => 11, 'พย' => 11,
            'ธันวาคม' => 12, 'ธ.ค.' => 12, 'ธค' => 12,
        ];

        // รูปแบบ: เกิด 15 มกราคม 2540
        $monthNames = implode('|', array_keys($thaiMonths));
        if (preg_match('/(?:เกิด|วันเกิด)(?:วันที่)?\s*(\d{1,2})\s*('.$monthNames.')\s*(\d{2,4})/u', $message, $matches)) {
            $day = (int) $matches[1];
            $month = $thaiMonths[$matches[2]] ?? null;
            $year = (int) $matches[3];

            if ($month) {
                return $this->normalizeBirthDate($day, $month, $year);
            }
        }

        // รูปแบบ: เกิด 15/01/2540 หรือ 15-01-2540
        if (preg_match('/(?:เกิด|วันเกิด)(?:วันที่)?\s*(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/u', $message, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            return $this->normalizeBirthDate($day, $month, $year);
        }

        return null;
    }

    /**
     * แปลงวัน/เดือน/ปี เป็นรูปแบบ Y-m-d (ค.ศ.)
     *
     * @param  int  $day  วัน
     * @param  int  $month  เดือน (1-12)
     * @param  int  $year  ปี (พ.ศ. หรือ ค.ศ. แบบ 2 หรือ 4 หลัก)
     */
    protected function normalizeBirthDate(int $day, int $month, int $year): ?string
    {
        // แปลงปี พ.ศ. เป็น ค.ศ.
        if ($year > 2400) {
            $year -= 543;
        } elseif ($year < 100) {
            // ปี 2 หลัก: ลองแปลงเป็น พ.ศ. ก่อน (เช่น 40 → 2540 → 1997 ค.ศ.)
            $ceFromThai = $year + 2500 - 543;
            if ($ceFromThai >= 1900 && $ceFromThai <= now()->year) {
                $year = $ceFromThai;
            } else {
                // Fallback: ตีความเป็น ค.ศ. (เช่น 97 → 1997)
                $year = ($year > 50) ? (1900 + $year) : (2000 + $year);
            }
        }

        // ตรวจสอบความถูกต้อง
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31 || $year < 1900 || $year > now()->year) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    // ============================================================
    // ตรวจสอบ Free Limit (Freemium)
    // ============================================================

    /**
     * ตรวจสอบว่าผู้ใช้ใช้งานครบจำนวนฟรี (พื้นฐาน) หรือยัง
     */
    public function checkFreeLimit(string $facebookUserId): array
    {
        $maxFree = $this->settings->max_free_readings;
        $todayCount = FortuneReading::countTodayReadings($facebookUserId);
        $remaining = max(0, $maxFree - $todayCount);

        return [
            'has_reached_limit' => $todayCount >= $maxFree,
            'today_count' => $todayCount,
            'max_free' => $maxFree,
            'remaining' => $remaining,
        ];
    }

    /**
     * ตรวจสอบว่าผู้ใช้ใช้งานครบจำนวนฟรี (เชิงลึก) หรือยัง
     *
     * ใช้ reading_type = 'deep' แทนการเดาจาก tokens_used
     */
    public function checkDeepFreeLimit(string $facebookUserId): array
    {
        $maxFreeDeep = $this->settings->free_deep_per_day ?? 1;
        $todayDeepCount = FortuneReading::countTodayDeepReadings($facebookUserId);
        $remaining = max(0, $maxFreeDeep - $todayDeepCount);

        return [
            'has_reached_limit' => $todayDeepCount >= $maxFreeDeep,
            'today_count' => $todayDeepCount,
            'max_free' => $maxFreeDeep,
            'remaining' => $remaining,
        ];
    }

    // ============================================================
    // ข้อความอัตโนมัติ
    // ============================================================

    /**
     * สร้างข้อความเมื่อครบจำนวนฟรี (พื้นฐาน)
     *
     * ใช้เทมเพลต limit_exceeded ถ้ามี มิฉะนั้นใช้ข้อความจาก settings
     *
     * @param  string|null  $userName  ชื่อผู้ใช้
     */
    public function getLimitExceededMessage(?string $userName = null): string
    {
        // ลองใช้เทมเพลตก่อน
        $template = FortuneResponseTemplate::getDefault('limit_exceeded');
        if ($template) {
            return $template->render([
                'user_name' => $userName ?? 'ท่าน',
                'max_free' => (string) ($this->settings->max_free_readings ?? 3),
                'remaining_free' => '0',
                'price' => (string) ($this->settings->reading_price ?? 0),
                'register_url' => url('/register'),
            ]);
        }

        // Fallback: ข้อความจาก settings
        $message = $this->settings->limit_exceeded_message ??
            "คุณได้ใช้งานครบจำนวนฟรีวันนี้แล้ว ({$this->settings->max_free_readings} ครั้ง)\n\n";

        if ($this->settings->reading_price > 0) {
            $message .= "💰 ราคาการทำนายต่อครั้ง: {$this->settings->reading_price} บาท\n\n";
        }

        // แนะนำดูดวงเชิงลึก (ถ้าเปิดใช้งาน)
        if ($this->settings->isDeepReadingEnabled()) {
            $message .= "🌟 หรือลอง 'ดูดวง' เพื่อรับคำทำนายเชิงลึก\n";
        }

        if ($this->settings->payment_qr_image) {
            $message .= "\n📸 โอนเงินผ่าน QR Code:\n";
            $message .= $this->settings->getPaymentQrUrl()."\n";
        }

        $message .= "\n📱 สมัครสมาชิกเพื่อใช้งานไม่จำกัด: ".url('/register');

        return $message;
    }

    /**
     * สร้างข้อความเมื่อครบจำนวนฟรีเชิงลึก
     *
     * ใช้เทมเพลต limit_exceeded ถ้ามี มิฉะนั้นใช้ข้อความ hardcoded
     *
     * @param  string|null  $userName  ชื่อผู้ใช้
     */
    public function getDeepLimitExceededMessage(?string $userName = null): string
    {
        // ลองใช้เทมเพลตก่อน
        $template = FortuneResponseTemplate::getDefault('limit_exceeded');
        if ($template) {
            return $template->render([
                'user_name' => $userName ?? 'ท่าน',
                'max_free' => (string) ($this->settings->free_deep_per_day ?? 1),
                'remaining_free' => '0',
                'price' => (string) ($this->settings->deep_reading_price ?? 0),
                'register_url' => url('/register'),
            ]);
        }

        // Fallback: ข้อความ hardcoded
        $message = "🌟 คุณได้ใช้สิทธิ์ดูดวงเชิงลึกฟรีวันนี้ครบแล้ว ({$this->settings->free_deep_per_day} ครั้ง)\n\n";

        if ($this->settings->deep_reading_price > 0) {
            $message .= "💰 ดูดวงเชิงลึกเพิ่ม: {$this->settings->deep_reading_price} บาท/ครั้ง\n\n";
        }

        if ($this->settings->isSubscriptionEnabled()) {
            $message .= $this->settings->getSubscriptionMessage();
        } else {
            if ($this->settings->payment_qr_image) {
                $message .= "📸 โอนเงินผ่าน QR Code:\n";
                $message .= $this->settings->getPaymentQrUrl()."\n\n";
            }
            $message .= '📱 ชำระเงิน/สมัครสมาชิก: '.url('/register');
        }

        return $message;
    }

    /**
     * ส่งข้อความต้อนรับจากเทมเพลต
     *
     * @param  string  $recipientId  Facebook User ID
     */
    public function sendWelcomeMessage(string $recipientId): bool
    {
        $template = FortuneResponseTemplate::getDefault('welcome');
        if ($template) {
            $message = $template->render([
                'max_free' => (string) ($this->settings->max_free_readings ?? 3),
            ]);

            // ส่งรูปส่วนหัว (ถ้ามี)
            if ($template->hasHeaderImage()) {
                $this->sendImage($recipientId, $template->header_image_url);
            }

            $result = $this->sendMessage($recipientId, $message);

            // ส่งรูปส่วนท้าย (ถ้ามี)
            if ($template->hasFooterImage()) {
                $this->sendImage($recipientId, $template->footer_image_url);
            }

            return $result;
        }

        // Fallback: ข้อความต้อนรับเดิม
        // กระชับเมื่อปิด free — ไม่แนะนำ "ดูดวง" (ซึ่งไม่มี basic flow ให้ใช้)
        $freeEnabled = $this->settings->isFreeReadingEnabled();
        if ($freeEnabled) {
            $welcomeText = "🔮 สวัสดี ยินดีต้อนรับสู่ระบบดูดวง!\n\n"
                ."พิมพ์: \"ดูดวง\" ตามด้วยคำถาม\n"
                .'🌟 หรือกดปุ่ม "ดูดวง" เพื่อเริ่มเลือกแพคเกจ';
        } else {
            $welcomeText = "🔮 สวัสดี ยินดีต้อนรับสู่ระบบดูดวง!\n\n"
                .'พิมพ์ "ดูดวง" เพื่อเริ่มค่ะ 🙏';
        }

        return $this->sendMessage($recipientId, $welcomeText);
    }

    /**
     * ส่งข้อความแจ้งชำระเงินจากเทมเพลต (พร้อมรูป QR Code)
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  string|null  $userName  ชื่อผู้ใช้
     */
    public function sendPaymentMessage(string $recipientId, ?string $userName = null): bool
    {
        $template = FortuneResponseTemplate::getDefault('payment');
        if ($template) {
            $message = $template->render([
                'user_name' => $userName ?? 'ท่าน',
                'price' => (string) ($this->settings->reading_price ?? 0),
                'register_url' => url('/register'),
                'payment_url' => url('/payment'),
            ]);

            // ส่งรูปส่วนหัว (ถ้ามี)
            if ($template->hasHeaderImage()) {
                $this->sendImage($recipientId, $template->header_image_url);
            }

            $result = $this->sendMessage($recipientId, $message);

            // ส่งรูปส่วนท้าย เช่น QR Code (ถ้ามี)
            if ($template->hasFooterImage()) {
                $this->sendImage($recipientId, $template->footer_image_url);
            }

            return $result;
        }

        // Fallback: ส่ง QR Code จาก settings
        if ($this->settings->payment_qr_image) {
            $this->sendImage($recipientId, $this->settings->getPaymentQrUrl());
        }

        return $this->sendMessage($recipientId,
            "💰 กรุณาชำระเงินเพื่อใช้งานต่อ\n".
            '📱 สมัครสมาชิก: '.url('/register')
        );
    }

    /**
     * ส่งข้อความเมื่อเกิดข้อผิดพลาดจากเทมเพลต
     *
     * @param  string  $recipientId  Facebook User ID
     */
    public function sendErrorMessage(string $recipientId): bool
    {
        $template = FortuneResponseTemplate::getDefault('error');
        if ($template) {
            return $this->sendMessage($recipientId, $template->render());
        }

        return $this->sendMessage($recipientId,
            "😔 ขออภัย ขณะนี้ระบบมีปัญหา\n".
            "กรุณาลองใหม่อีกครั้งในอีกสักครู่\n".
            'พิมพ์ "ดูดวง" เพื่อลองใหม่'
        );
    }

    // ============================================================
    // ส่งคำทำนายกลับไปยังผู้ใช้
    // ============================================================

    /**
     * ส่งคำทำนายกลับไปยังผู้ใช้ (ใช้เทมเพลต + รูปภาพ)
     *
     * ลำดับการส่ง:
     * 1. รูปส่วนหัว (header_image_url) ถ้ามี
     * 2. รูปจากผู้ใช้ (user_image_url) ถ้ามี
     * 3. ข้อความคำทำนาย (render จากเทมเพลต)
     * 4. รูปคำทำนาย (reading_image_url) ถ้ามี
     * 5. รูปส่วนท้าย (footer_image_url เช่น QR Code) ถ้ามี
     *
     * @param  string  $response  คำทำนายจาก AI
     */
    public function sendFortuneTelling(FortuneReading $reading, string $response): bool
    {
        $recipientId = $reading->facebook_user_id;
        $readingType = $reading->reading_type ?? 'basic';

        // ดึงเทมเพลตตามประเภทคำทำนาย (basic/deep)
        $template = FortuneResponseTemplate::getDefault($readingType);

        // เตรียมข้อมูลสำหรับ placeholders
        $data = [
            'response' => $response,
            'user_name' => $reading->facebook_user_name ?? 'ท่าน',
            'date' => now()->format('d/m/Y'),
            'questions' => $reading->getQuestionsText(),
            'reading_type' => $reading->getReadingTypeLabel(),
            'reading_id' => (string) $reading->id,
            'rate_url' => url("/fortune/{$reading->id}/rate"),
            'register_url' => url('/register'),
            'payment_url' => url('/payment'),
            'remaining_free' => '0',
            'max_free' => (string) ($this->settings->max_free_readings ?? 3),
            'price' => (string) ($reading->isDeep()
                ? ($this->settings->deep_reading_price ?? 0)
                : ($this->settings->reading_price ?? 0)),
            'zodiac' => $reading->birth_date
                ? $this->getZodiacFromDate($reading->birth_date)
                : '',
            'birth_date' => $reading->birth_date
                ? $reading->birth_date->format('d/m/Y')
                : '',
        ];

        // render ข้อความจากเทมเพลต (หรือ fallback ถ้าไม่มีเทมเพลต)
        if ($template) {
            $message = $template->render($data);
        } else {
            $readingTypeLabel = $reading->isDeep() ? '🌟 คำทำนายเชิงลึก' : '🔮 คำทำนาย';
            $message = "{$readingTypeLabel}สำหรับคุณ\n\n{$response}\n\n";
            $message .= "---\n";
            $message .= 'ให้คะแนนความพึงพอใจ: '.url("/fortune/{$reading->id}/rate");
        }

        // 1. ส่งรูปส่วนหัว (header image) ถ้ามี
        if ($template && $template->hasHeaderImage()) {
            $this->sendImage($recipientId, $template->header_image_url);
        }

        // 2. ส่งรูปจากผู้ใช้ (user image) ถ้ามี
        if ($reading->hasUserImage()) {
            $this->sendImage($recipientId, $reading->user_image_url);
        }

        // 3. ส่งข้อความคำทำนาย
        if ($this->settings->respond_in_comment && $reading->facebook_comment_id) {
            $this->replyToComment($reading->facebook_comment_id, $message);
        } else {
            $this->sendMessage($recipientId, $message);
        }

        // 4. ส่งรูปคำทำนาย (reading image) ถ้ามี
        if ($reading->hasReadingImage()) {
            $this->sendImage($recipientId, $reading->reading_image_url);
        }

        // 5. ส่งรูปส่วนท้าย (footer image เช่น QR Code) ถ้ามี
        if ($template && $template->hasFooterImage()) {
            $this->sendImage($recipientId, $template->footer_image_url);
        }

        return true;
    }

    // ============================================================
    // Webhook Security
    // ============================================================

    /**
     * ตรวจสอบ webhook signature จาก Facebook
     *
     * Facebook ส่ง X-Hub-Signature-256 header สำหรับ verify payload
     *
     * @param  string  $payload  Raw request body
     * @param  string  $signature  X-Hub-Signature-256 header value
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $appSecret = $this->settings->facebook_app_secret;

        if (empty($appSecret) || empty($signature)) {
            // ⚠️ Production: ต้องมี app_secret → ปฏิเสธ request ถ้าไม่มี
            if (app()->environment('production')) {
                Log::error('❌ Webhook signature verification FAILED: missing app_secret or signature ใน production', [
                    'has_app_secret' => ! empty($appSecret),
                    'has_signature' => ! empty($signature),
                ]);

                return false;
            }

            // Dev/Local: อนุญาตผ่านเพื่อความสะดวกในการพัฒนา
            Log::warning('⚠️ Webhook signature verification skipped (dev mode): missing app_secret or signature');

            return true;
        }

        // Facebook ส่ง format: "sha256=xxxxx"
        if (! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expectedHash = hash_hmac('sha256', $payload, $appSecret);
        $receivedHash = substr($signature, 7); // ตัด "sha256=" ออก

        return hash_equals($expectedHash, $receivedHash);
    }

    // ============================================================
    // Helper Methods
    // ============================================================

    /**
     * แบ่งข้อความยาวเป็นหลาย chunks (Messenger จำกัด 2000 ตัวอักษร)
     *
     * แบ่งที่จุดสิ้นสุดบรรทัดใกล้สุดเพื่อไม่ให้ข้อความขาดกลาง
     *
     * @param  string  $message  ข้อความทั้งหมด
     * @return array chunks ของข้อความ
     */
    public function splitLongMessage(string $message): array
    {
        $maxLength = self::MAX_MESSAGE_LENGTH;

        if (mb_strlen($message) <= $maxLength) {
            return [$message];
        }

        $chunks = [];
        $remaining = $message;

        while (mb_strlen($remaining) > 0) {
            if (mb_strlen($remaining) <= $maxLength) {
                $chunks[] = $remaining;
                break;
            }

            // หาตำแหน่งขึ้นบรรทัดใหม่ที่ใกล้ที่สุดก่อน limit
            $cutPosition = $maxLength;
            $segment = mb_substr($remaining, 0, $maxLength);

            // หาตำแหน่ง newline สุดท้ายในส่วนที่จะตัด
            $lastNewline = mb_strrpos($segment, "\n");
            if ($lastNewline !== false && $lastNewline > ($maxLength * 0.5)) {
                $cutPosition = $lastNewline + 1;
            }

            $chunks[] = trim(mb_substr($remaining, 0, $cutPosition));
            $remaining = trim(mb_substr($remaining, $cutPosition));
        }

        return $chunks;
    }

    /**
     * คำนวณราศีจากวันเกิด
     *
     * @param  Carbon  $date
     * @return string ชื่อราศี
     */
    protected function getZodiacFromDate($date): string
    {
        $month = $date->month;
        $day = $date->day;

        $signs = [
            ['name' => 'มังกร (Capricorn)', 'end_month' => 1, 'end_day' => 19],
            ['name' => 'กุมภ์ (Aquarius)', 'end_month' => 2, 'end_day' => 18],
            ['name' => 'มีน (Pisces)', 'end_month' => 3, 'end_day' => 20],
            ['name' => 'เมษ (Aries)', 'end_month' => 4, 'end_day' => 19],
            ['name' => 'พฤษภ (Taurus)', 'end_month' => 5, 'end_day' => 20],
            ['name' => 'เมถุน (Gemini)', 'end_month' => 6, 'end_day' => 20],
            ['name' => 'กรกฎ (Cancer)', 'end_month' => 7, 'end_day' => 22],
            ['name' => 'สิงห์ (Leo)', 'end_month' => 8, 'end_day' => 22],
            ['name' => 'กันย์ (Virgo)', 'end_month' => 9, 'end_day' => 22],
            ['name' => 'ตุลย์ (Libra)', 'end_month' => 10, 'end_day' => 22],
            ['name' => 'พิจิก (Scorpio)', 'end_month' => 11, 'end_day' => 21],
            ['name' => 'ธนู (Sagittarius)', 'end_month' => 12, 'end_day' => 21],
        ];

        foreach ($signs as $sign) {
            if ($month === $sign['end_month'] && $day <= $sign['end_day']) {
                return $sign['name'];
            }
            if ($month < $sign['end_month']) {
                return $sign['name'];
            }
        }

        return 'มังกร (Capricorn)';
    }

    /**
     * สร้าง Graph API URL
     *
     * @param  string  $path  API path (เช่น /me/messages)
     */
    protected function graphUrl(string $path): string
    {
        $version = self::GRAPH_API_VERSION;

        return "https://graph.facebook.com/{$version}{$path}";
    }

    // ============================================================
    // Messenger Profile Setup
    // ============================================================

    /**
     * ตั้งค่า Messenger Profile (Get Started button และ Persistent Menu)
     *
     * เรียกใช้ครั้งเดียวหลังจากตั้งค่า Facebook Page เสร็จ
     * สามารถเรียกผ่าน admin panel หรือ artisan command
     *
     * @return array ผลลัพธ์การตั้งค่า ['success' => bool, 'message' => string]
     */
    public function setupMessengerProfile(): array
    {
        if (empty($this->pageAccessToken)) {
            return [
                'success' => false,
                'message' => 'ไม่พบ Page Access Token',
            ];
        }

        try {
            // ตั้งค่า Get Started + Greeting + Persistent Menu + Ice Breakers
            $response = Http::post($this->graphUrl('/me/messenger_profile'), [
                'access_token' => $this->pageAccessToken,

                // Get Started button - ส่ง payload GET_STARTED เมื่อผู้ใช้กด
                'get_started' => [
                    'payload' => 'GET_STARTED',
                ],

                // Greeting text - แสดงก่อนผู้ใช้ส่งข้อความแรก
                'greeting' => [
                    [
                        'locale' => 'default',
                        'text' => "🔮 ยินดีต้อนรับสู่ \"แม่หมอจันทรา\"\n\nหมอดูเชี่ยวชาญโหราศาสตร์ไทย-สากล ทำนายแม่นยำด้วยระบบหลักล้าน 💎\n\nกดปุ่มด้านล่างเพื่อเริ่มได้เลย ✨",
                    ],
                ],

                // ✨ Ice Breakers — 4 คำถามให้ลูกค้าใหม่กดเลือกตอนเปิดแชท
                'ice_breakers' => [
                    [
                        'locale' => 'default',
                        'call_to_actions' => $this->buildIceBreakerActions(),
                    ],
                ],

                // Persistent Menu - เมนูถาวรที่แสดงตลอดเวลา (ปุ่มลัดในแชท)
                // Facebook จำกัดสูงสุด 3 top-level items
                'persistent_menu' => [
                    [
                        'locale' => 'default',
                        'composer_input_disabled' => false, // อนุญาตให้พิมพ์ข้อความได้
                        'call_to_actions' => $this->buildPersistentMenuActions(),
                    ],
                ],
            ]);

            if ($response->successful()) {
                Log::info('✅ Messenger Profile setup successful', [
                    'response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'message' => 'ตั้งค่า Messenger Profile สำเร็จ (Get Started button + Persistent Menu)',
                    'data' => $response->json(),
                ];
            }

            $error = $response->json('error', []);
            Log::error('❌ Messenger Profile setup failed', [
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'ตั้งค่าไม่สำเร็จ: '.($error['message'] ?? 'Unknown error'),
                'data' => $response->json(),
            ];

        } catch (Exception $e) {
            Log::error('Messenger Profile setup error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ];
        }
    }

    /**
     * สร้างรายการเมนูสำหรับ Persistent Menu (เมนูถาวรล่างซ้ายของแชท)
     *
     * โครงสร้างตาม spec ของ user (2026-04-27):
     * 1. 📝 สมัครสมาชิก → URL ไป Facebook OAuth ของ thaiprompt
     * 2. 💎 ดูดวงละเอียด → postback เริ่ม deep reading flow
     * 3. 🔍 รู้จักเรา → postback อธิบายศาสตร์ + ติดต่อ xman studio
     *    (มี submenu: ชวนเพื่อน + เพิ่มเพื่อน LINE ถ้ามี)
     *
     * Facebook จำกัด 3 top-level items + nested ได้
     *
     * @return array call_to_actions
     */
    protected function buildPersistentMenuActions(): array
    {
        $appUrl = rtrim(config('app.url', 'https://main.thaiprompt.online'), '/');

        // ❌ Note: nested submenu สร้าง "Invalid button type" จาก FB API
        // → ใช้ flat menu 3 items เท่านั้น (FB max 3 top-level)
        // → ปุ่ม "ชวนเพื่อน" / "LINE" ทำได้ผ่าน Ice Breakers แทน
        return [
            // 1️⃣ สมัครสมาชิก → URL ไป FB OAuth
            [
                'type' => 'web_url',
                'title' => '📝 สมัครสมาชิก',
                'url' => $appUrl.'/auth/facebook',
            ],
            // 2️⃣ ดูดวงละเอียด → postback เริ่ม flow
            [
                'type' => 'postback',
                'title' => '💎 ดูดวง',
                'payload' => 'MENU_DEEP_FORTUNE',
            ],
            // 3️⃣ รู้จักเรา → postback แสดงข้อมูลศาสตร์ + xman studio + ปุ่มต่างๆ
            [
                'type' => 'postback',
                'title' => '🔍 รู้จักเรา',
                'payload' => 'MENU_ABOUT_US',
            ],
        ];
    }

    /**
     * 🚫 Deprecated nested submenu (เก็บไว้เผื่อ reuse — แต่ไม่ใช้แล้ว)
     */
    protected function _buildPersistentMenuActions_OLD(): array
    {
        $appUrl = rtrim(config('app.url', 'https://main.thaiprompt.online'), '/');

        // Submenu ของ "🔍 รู้จักเรา"
        $aboutSubmenu = [
            [
                'type' => 'postback',
                'title' => '✨ ศาสตร์ที่เราใช้',
                'payload' => 'MENU_ABOUT_US',
            ],
            [
                'type' => 'postback',
                'title' => '👥 ชวนเพื่อน รับรายได้',
                'payload' => 'MENU_REFERRAL',
            ],
        ];

        // เพิ่ม LINE ใน submenu ถ้ามีการตั้งค่า
        $basicId = $this->settings->line_bot_basic_id ?? null;
        if (! empty($basicId)) {
            if (! str_starts_with($basicId, '@')) {
                $basicId = '@'.$basicId;
            }
            $aboutSubmenu[] = [
                'type' => 'web_url',
                'title' => '💚 เพิ่มเพื่อน LINE',
                'url' => 'https://line.me/R/ti/p/'.$basicId,
            ];
        }

        return [
            // 1️⃣ สมัครสมาชิก → ไป FB OAuth (auto-login + redirect ไป wallet)
            [
                'type' => 'web_url',
                'title' => '📝 สมัครสมาชิก',
                'url' => $appUrl.'/auth/facebook',
                'webview_height_ratio' => 'full',
            ],

            // 2️⃣ ดูดวงละเอียด → เริ่ม flow
            [
                'type' => 'postback',
                'title' => '💎 ดูดวง',
                'payload' => 'MENU_DEEP_FORTUNE',
            ],

            // 3️⃣ รู้จักเรา (มี submenu)
            [
                'type' => 'nested',
                'title' => '🔍 รู้จักเรา',
                'call_to_actions' => $aboutSubmenu,
            ],
        ];
    }

    /**
     * สร้าง Ice Breakers — 4 คำถามให้ลูกค้าใหม่กดเลือกตอนเปิดแชท
     *
     * ปรากฏก่อนลูกค้าพิมพ์ข้อความแรก ทำให้ลูกค้าใหม่เริ่ม flow ได้ง่าย
     * Facebook รองรับสูงสุด 4 questions
     *
     * @return array call_to_actions สำหรับ ice_breakers
     */
    protected function buildIceBreakerActions(): array
    {
        // ⚠️ Facebook จำกัด 4 ice breakers ต่อ persona — เกินจะ reject ทั้งชุด
        // 🌐 (2026-05-03) ใช้ "MENU_REFERRAL" + ไม่เพิ่ม language picker ตรงนี้
        //    เหตุผล: auto-detect ทำงานเป็น default แล้ว, manual picker ใช้ผ่าน
        //    chat command "Lao"/"ลาว"/"ไทย" หรือ postback LANG_TH/LANG_LO จากที่อื่น
        return [
            [
                'question' => '🔮 อยากดูดวงเชิงลึกแม่นๆ',
                'payload' => 'MENU_DEEP_FORTUNE',
            ],
            [
                'question' => '📝 สมัครสมาชิกเพื่อดูดวง+รับรายได้',
                'payload' => 'ICEBREAKER_REGISTER',
            ],
            [
                'question' => '✨ รู้จักเรา / ศาสตร์ที่ใช้ทำนาย',
                'payload' => 'MENU_ABOUT_US',
            ],
            [
                'question' => '🌐 ภาษา / ພາສາ / Language',
                'payload' => 'LANG_PICKER',
            ],
        ];
    }

    /**
     * ลบ Messenger Profile settings ทั้งหมด
     *
     * @return array ผลลัพธ์การลบ
     */
    public function deleteMessengerProfile(): array
    {
        if (empty($this->pageAccessToken)) {
            return [
                'success' => false,
                'message' => 'ไม่พบ Page Access Token',
            ];
        }

        try {
            $response = Http::delete($this->graphUrl('/me/messenger_profile'), [
                'access_token' => $this->pageAccessToken,
                'fields' => ['get_started', 'greeting', 'persistent_menu'],
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'ลบ Messenger Profile สำเร็จ',
                ];
            }

            return [
                'success' => false,
                'message' => 'ลบไม่สำเร็จ: '.($response->json('error.message') ?? 'Unknown error'),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ];
        }
    }

    /**
     * ดึงค่า Messenger Profile ปัจจุบัน
     *
     * @return array ค่า profile ปัจจุบัน
     */
    public function getMessengerProfile(): array
    {
        if (empty($this->pageAccessToken)) {
            return [
                'success' => false,
                'message' => 'ไม่พบ Page Access Token',
            ];
        }

        try {
            $response = Http::get($this->graphUrl('/me/messenger_profile'), [
                'access_token' => $this->pageAccessToken,
                'fields' => 'get_started,greeting,persistent_menu',
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data', []),
                ];
            }

            return [
                'success' => false,
                'message' => 'ดึงข้อมูลไม่สำเร็จ: '.($response->json('error.message') ?? 'Unknown error'),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ];
        }
    }

    // ============================================================
    // Facebook Messenger Templates: Button, Generic, Quick Replies
    // ============================================================

    /**
     * ส่ง Button Template (ข้อความ + ปุ่ม สูงสุด 3 ปุ่ม)
     *
     * ใช้สำหรับ: upsell, payment, check remaining, welcome
     * ข้อจำกัด Facebook: text สูงสุด 640 chars, สูงสุด 3 ปุ่ม
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  array  $templatePayload  payload จาก FacebookRichMessageService
     * @param  array  $options  ตัวเลือกเพิ่มเติม (messaging_type, from_admin)
     * @return bool สำเร็จหรือไม่
     */
    /**
     * 👁️ ส่งกล่องชวนติดตามเพจ + ปุ่มยืนยัน "ติดตามแล้ว"
     *
     * - เช็ค FortuneUserCredit::shouldPromptFollow() ก่อน → skip ถ้า:
     *   • user ยืนยันติดตามแล้ว (clicked FOLLOW_CONFIRMED postback)
     *   • หรือเพิ่งส่ง prompt ภายใน 7 วันที่ผ่านมา
     * - เรียกได้จากทุกที่ที่ส่ง DM (processMessage, sendTemplateEngagement, AI job)
     * - non-blocking — ถ้า fail ไม่กระทบ flow หลัก
     *
     * @param  string  $recipientId  Facebook PSID
     * @return bool true = ส่งสำเร็จ + mark prompted, false = gated/failed
     */
    public function sendFollowPagePromptToUser(string $recipientId): bool
    {
        // 🚫 (2026-05-08) Hard-disable per user feedback "เอาออกไปก่อน ไม่ต้องปรากฎ"
        //    follow-page prompt + group invite รบกวน UX → ลบทิ้งทั้งสอง
        //    ถ้าจะเปิดอนาคต → comment line ต่อไป
        return false;
        try {
            $credit = FortuneUserCredit::getOrCreate($recipientId, 'facebook');
            // 🔄 (2026-05-02) เปลี่ยนจาก shouldPromptFollow() (7-day cooldown)
            //    → shouldPromptFollowToday() (daily cooldown — ครั้งแรกของวันเท่านั้น)
            //    user request: "ในการทักแชทครั้งแรกของวันนั้น ถ้ายังไม่ติดตาม ให้ปรากฏ"
            if (! $credit->shouldPromptFollowToday()) {
                return false; // ติดตามแล้ว หรือ ส่งวันนี้ไปแล้ว
            }

            $pageId = $this->settings->facebook_page_id ?? null;
            if (empty($pageId)) {
                return false;
            }

            // 🌟 (2026-05-05) Redesign — ลบปุ่ม "ติดตามแล้ว" postback
            //                          + เพิ่มปุ่ม "เข้ากลุ่มแม่หมอจันทรา" + ใช้ tracking URLs
            //   user spec: "ปุ่มติดตามแล้วให้นำออก ให้เพิ่มเข้ากลุ่ม + ลิงก์ขอเข้ากลุ่ม
            //               + การกดปุ่มติดตามต้องเด้งให้กดติดตาม + บันทึกว่ายูสเซ่อร์กดติดตามแล้ว"
            //
            //   Tracking URL pattern: /fortune/track/fb-follow/{psid} → record + 302 redirect
            //   web_url button ไม่ส่ง postback กลับ — ต้องใช้ redirect tracking แทน
            $groupUrl = $this->settings->fortune_group_url
                ?? 'https://www.facebook.com/groups/1539006181120751';

            // 🔒 (2026-05-05 review) Signed URLs — ป้องกัน enumeration spam
            //   ใครๆ จะเรียก /fortune/track/fb-follow/{any_psid} ตรงไม่ได้ (signed middleware reject)
            //   URL จะมี ?signature=... + ?expires=... append อัตโนมัติ
            $trackFollowUrl = URL::signedRoute(
                'fortune.track.fb-follow',
                ['psid' => $recipientId],
                now()->addDays(30)  // ลิงก์ใช้ได้ 30 วัน — กัน user ที่เก็บ DM ไว้เปิดทีหลัง
            );
            $trackGroupUrl = URL::signedRoute(
                'fortune.track.fb-group',
                ['psid' => $recipientId],
                now()->addDays(30)
            );

            // 🌙 (2026-05-22) Tone reset — ไม่ประกาศ "สิทธิ์พิเศษ/ของขวัญ"
            //   บอกข้อมูลตรงๆ ว่ามีกลุ่ม + มีดวงประจำวัน ลูกค้าตัดสินใจเอง
            $message = "🌙 ติดตามเพจหรือเข้ากลุ่มแม่หมอจันทรา\n\n"
                ."👁️ *ติดตามเพจ* — ดูดวงประจำวันได้ตอนตี 1 - 7 โมงเช้า\n\n"
                ."👥 *กลุ่มแม่หมอจันทรา* — สายมูทำดวงด้วยกัน:\n"
                ."💬 ปรึกษาแม่หมอ + คุยกับสมาชิกในกลุ่ม\n"
                ."🃏 อัพเดต tarot tip + เคล็ดโหราศาสตร์\n"
                ."🌟 บอกบุญถึงกัน\n\n"
                .'👇 กดเลือกได้เลยค่ะ';

            $payload = [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text' => mb_substr($message, 0, 640),
                        'buttons' => [
                            ['type' => 'web_url', 'title' => '👁️ ติดตามเพจ', 'url' => $trackFollowUrl],
                            ['type' => 'web_url', 'title' => '👥 เข้ากลุ่มแม่หมอ', 'url' => $trackGroupUrl],
                        ],
                    ],
                ],
            ];

            $sent = $this->sendButtonTemplate($recipientId, $payload);
            if ($sent) {
                $credit->markFollowPrompted();
            }

            return (bool) $sent;
        } catch (\Throwable $e) {
            Log::debug('sendFollowPagePromptToUser failed (non-blocking)', [
                'user_id' => $recipientId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 🌟 (2026-05-04) ส่งกล่องเชิญเข้ากลุ่ม Facebook
     *
     * - Gated โดย settings.fortune_group_invite_enabled + fortune_group_url
     * - Cache cooldown 7 วัน/user (กันสแปม) — key: fb:group_invite_sent:{psid}
     * - non-blocking — ถ้า fail ไม่กระทบ flow หลัก
     *
     * @param  string  $recipientId  Facebook PSID
     * @return bool true = ส่งสำเร็จ + mark cooldown, false = gated/failed
     */
    public function sendGroupInvitePrompt(string $recipientId): bool
    {
        try {
            // 🚫 (2026-05-08) Hard-disable per user feedback "ส่งทำไม ไม่มีประโยชน์"
            //   เก็บฟังก์ชันไว้ — ถ้าอยากเปิดอนาคต comment line ต่อไป + เปิด toggle
            return false;

            // ตรวจ toggle + URL
            if (! ($this->settings->fortune_group_invite_enabled ?? false)) {
                return false;
            }

            $groupUrl = $this->settings->fortune_group_url ?? null;
            if (empty($groupUrl)) {
                return false;
            }

            // Cooldown 7 วัน/user — กันส่งซ้ำซ้อนกับ DM อื่นๆ
            $cacheKey = "fb:group_invite_sent:{$recipientId}";
            if (Cache::has($cacheKey)) {
                return false;
            }

            $message = trim($this->settings->fortune_group_invite_message ?? '');
            if (empty($message)) {
                $message = "🌟 อยากดูดวงฟรีทุกเดือนกับแม่หมอจันทรา?\n\n"
                    ."เข้ากลุ่มสมาชิกของเรา จะได้:\n"
                    ."🎁 สิทธิ์ดูไพ่ฟรี 1 ใบทุกเดือน\n"
                    ."🔮 สุ่มดูดวงส่วนตัวกับแม่หมอ\n"
                    ."✨ ความรู้ดีๆ + กิจกรรมพิเศษ\n\n"
                    .'👇 กดเข้ากลุ่มเลย';
            }

            $payload = [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text' => mb_substr($message, 0, 640),
                        'buttons' => [
                            [
                                'type' => 'web_url',
                                'title' => '🌟 เข้ากลุ่มแม่หมอ',
                                'url' => $groupUrl,
                            ],
                        ],
                    ],
                ],
            ];

            $sent = $this->sendButtonTemplate($recipientId, $payload);
            if ($sent) {
                // mark cooldown 7 วัน
                Cache::put($cacheKey, now()->toIso8601String(), now()->addDays(7));
            }

            return (bool) $sent;
        } catch (\Throwable $e) {
            Log::debug('sendGroupInvitePrompt failed (non-blocking)', [
                'user_id' => $recipientId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendButtonTemplate(string $recipientId, array $templatePayload, array $options = []): bool
    {
        try {
            $messagingType = 'RESPONSE';
            $requestBody = [
                'recipient' => ['id' => $recipientId],
                'message' => $templatePayload,
                'messaging_type' => $messagingType,
                'access_token' => $this->pageAccessToken,
            ];

            // 🆕 (2026-05-03) H8 — รองรับ from_admin เหมือน sendMessage()
            //    ถ้า from_admin (push หลัง payment / admin alert) → ลอง RESPONSE ก่อน
            //    ถ้าเกิน 24hr (subcode 2018278/2018065/2018001) → fallback เป็น MESSAGE_TAG
            //    เดิม: ขาด subcode 2018001 → push หลัง 24hr ของ button template เงียบไม่มี fallback
            $fromAdmin = ! empty($options['from_admin']);

            // เพิ่ม message_tag ถ้าระบุ (สำหรับส่งนอก 24 ชั่วโมง)
            if (! empty($options['message_tag'])) {
                $requestBody['messaging_type'] = 'MESSAGE_TAG';
                $requestBody['tag'] = $options['message_tag'];
                $messagingType = 'MESSAGE_TAG';
            }

            $response = Http::timeout(30)->post($this->graphUrl('/me/messages'), $requestBody);

            if ($response->successful()) {
                return true;
            }

            // ลอง fallback เป็น MESSAGE_TAG ถ้า 24 ชั่วโมงหมดอายุ
            // 🔒 (2026-05-03) H8 — เพิ่ม subcode 2018001 (มีใน sendMessage แต่ขาดที่นี่)
            //    ทำให้สอดคล้องกับ sendMessage:434 (ซึ่งมีครบทั้ง 3 subcode)
            $error = $response->json('error', []);
            $subcode = $error['error_subcode'] ?? 0;
            $is24hrError = in_array($subcode, [2018278, 2018065, 2018001]);

            if ($is24hrError && empty($options['message_tag']) && $messagingType === 'RESPONSE') {
                Log::info('Facebook Button Template: 24hr expired, retry with MESSAGE_TAG', [
                    'recipient' => $recipientId,
                    'subcode' => $subcode,
                    'from_admin' => $fromAdmin,
                ]);
                $requestBody['messaging_type'] = 'MESSAGE_TAG';
                $requestBody['tag'] = 'POST_PURCHASE_UPDATE';

                $retry = Http::timeout(30)->post($this->graphUrl('/me/messages'), $requestBody);

                if ($retry->successful()) {
                    return true;
                }

                Log::error('Facebook Button Template MESSAGE_TAG fallback ล้ม', [
                    'recipient' => $recipientId,
                    'retry_error' => $retry->json('error', []),
                ]);

                return false;
            }

            Log::error('Facebook Button Template ส่งไม่สำเร็จ', [
                'recipient' => $recipientId,
                'subcode' => $subcode,
                'error' => $error,
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('Facebook Button Template exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * ส่ง Generic Template (การ์ดพร้อมรูป + ปุ่ม, รองรับ carousel)
     *
     * ใช้สำหรับ: affiliate share, reading result
     * ข้อจำกัด Facebook: สูงสุด 10 cards, title 80 chars, subtitle 80 chars
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  array  $elements  Generic Template elements
     * @param  array  $options  ตัวเลือกเพิ่มเติม
     * @return bool สำเร็จหรือไม่
     */
    public function sendGenericTemplate(string $recipientId, array $elements, array $options = []): bool
    {
        try {
            $payload = [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'generic',
                        'elements' => $elements,
                    ],
                ],
            ];

            return $this->sendButtonTemplate($recipientId, $payload, $options);
        } catch (Exception $e) {
            Log::error('Facebook Generic Template exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * ส่งข้อความพร้อม Quick Replies และ Button Template ในครั้งเดียว
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  array  $templatePayload  payload จาก FacebookRichMessageService
     * @param  array  $quickReplies  Quick Replies array
     * @return bool สำเร็จหรือไม่
     */
    public function sendTemplateWithQuickReplies(string $recipientId, array $templatePayload, array $quickReplies): bool
    {
        try {
            // Facebook ไม่รองรับ Quick Replies กับ Template พร้อมกัน
            // ส่ง Template ก่อน แล้วส่ง Quick Replies แยก
            $sent = $this->sendButtonTemplate($recipientId, $templatePayload);

            if ($sent && ! empty($quickReplies)) {
                usleep(500000); // รอ 500ms
                $this->sendQuickReplies($recipientId, 'เลือกได้เลย 👇', $quickReplies);
            }

            return $sent;
        } catch (Exception $e) {
            Log::error('Facebook Template+QuickReplies exception: '.$e->getMessage());

            return false;
        }
    }

    // ============================================================
    // MessagingPlatformInterface: methods เพิ่มเติม
    // ============================================================

    /**
     * ส่งข้อความแบบ Rich (Facebook ใช้ Generic Template)
     */
    public function sendRichMessage(string $recipientId, array $richContent): bool
    {
        try {
            Http::timeout(30)
                ->post($this->graphUrl('/me/messages'), [
                    'recipient' => ['id' => $recipientId],
                    'message' => $richContent,
                    'messaging_type' => 'RESPONSE',
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            return true;
        } catch (Exception $e) {
            Log::error('ส่ง Rich Message ไม่สำเร็จ: '.$e->getMessage());

            return false;
        }
    }

    /**
     * ตรวจสอบว่า webhook event เป็นข้อความหรือไม่
     */
    public function isMessageEvent(array $event): bool
    {
        return isset($event['message']['text']);
    }

    /**
     * ดึงข้อความจาก webhook event
     */
    public function getMessageText(array $event): ?string
    {
        return $event['message']['text'] ?? null;
    }

    /**
     * ดึง User ID จาก webhook event
     */
    public function getUserIdFromEvent(array $event): ?string
    {
        return $event['sender']['id'] ?? null;
    }

    /**
     * ดึงชื่อ platform
     */
    public function getPlatformName(): string
    {
        return 'facebook';
    }

    /**
     * Facebook Messenger รองรับ Rich Message (Generic Template, Button Template)
     */
    public function supportsRichMessage(): bool
    {
        return true;
    }
}
