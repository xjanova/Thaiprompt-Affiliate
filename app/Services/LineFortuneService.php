<?php

namespace App\Services;

use App\Contracts\MessagingPlatformInterface;
use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LINE Fortune Service
 *
 * บริการส่งข้อความดูดวงผ่าน LINE Official Account
 * รองรับ Flex Message สำหรับแสดงผลที่สวยงาม
 */
class LineFortuneService implements MessagingPlatformInterface
{
    protected FortuneTellingSetting $settings;

    protected string $channelAccessToken;

    protected string $channelSecret;

    /**
     * LINE Messaging API Endpoint
     */
    protected const API_ENDPOINT = 'https://api.line.me/v2/bot';

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
        // 🛡️ (2026-05-16) Cast to string + empty fallback — กัน TypeError ถ้า config + setting ทั้งคู่ null
        //   เคสจริง: tinker / artisan command ที่รัน LineFortuneService ก่อน config load
        //   → ทั้ง 2 path return null → assign null to typed string property → crash
        $this->channelAccessToken = (string) ($this->settings->line_channel_access_token ?? config('services.line.channel_token') ?? '');
        $this->channelSecret = (string) ($this->settings->line_channel_secret ?? config('services.line.channel_secret') ?? '');
    }

    /**
     * ดึงราคาดูดวงละเอียดจาก settings (ใช้ logic เดียวกับ FortuneConversationService)
     *
     * @return float ราคา (บาท)
     */
    /**
     * Sanitize user name — ป้องกันซ้อน "คุณคุณ" ใน greeting
     *
     * ถ้า $userName เป็น fallback 'คุณ' หรือว่าง → return empty string
     * → ใน template `"สวัสดี คุณ{$userName}"` จะได้ `"สวัสดี คุณ"` (ไม่ซ้อน)
     *
     * @param  string|null  $userName  ชื่อจาก caller
     */
    protected function sanitizeUserName(?string $userName): string
    {
        $userName = trim((string) $userName);
        if ($userName === '' || $userName === 'คุณ') {
            return '';
        }

        return $userName;
    }

    public function getDeepReadingPrice(): float
    {
        $deepPrice = (float) ($this->settings->deep_reading_price ?? 0);
        if ($deepPrice > 0) {
            return $deepPrice;
        }

        $readingPrice = (float) ($this->settings->reading_price ?? 0);
        if ($readingPrice > 0) {
            return $readingPrice;
        }

        return FortuneConversationService::DEEP_READING_PRICE;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlatformName(): string
    {
        return 'line';
    }

    /**
     * เช็คโควต้า LINE push message
     *
     * ดึงข้อมูลจาก LINE Messaging API:
     * - /message/quota → จำนวน push ที่ใช้ได้ต่อเดือน
     * - /message/quota/consumption → จำนวนที่ใช้ไปแล้ว
     *
     * @return array{quota: int, used: int, remaining: int, percentage: float, error: string|null}
     */
    public function getMessageQuota(): array
    {
        $result = [
            'quota' => 0,
            'used' => 0,
            'remaining' => 0,
            'percentage' => 0,
            'error' => null,
        ];

        try {
            // ดึง quota limit
            $quotaResponse = Http::withToken($this->channelAccessToken)
                ->timeout(10)
                ->get(self::API_ENDPOINT.'/message/quota');

            if ($quotaResponse->successful()) {
                $quotaData = $quotaResponse->json();
                // type: "limited" มี value, type: "none" ไม่จำกัด
                $result['quota'] = $quotaData['value'] ?? ($quotaData['type'] === 'none' ? 999999 : 0);
            } else {
                $result['error'] = 'ดึง quota ไม่สำเร็จ: '.$quotaResponse->status().' '.$quotaResponse->body();

                return $result;
            }

            // ดึงจำนวนที่ใช้ไปแล้ว
            $usageResponse = Http::withToken($this->channelAccessToken)
                ->timeout(10)
                ->get(self::API_ENDPOINT.'/message/quota/consumption');

            if ($usageResponse->successful()) {
                $usageData = $usageResponse->json();
                $result['used'] = $usageData['totalUsage'] ?? 0;
                $result['remaining'] = max(0, $result['quota'] - $result['used']);
                $result['percentage'] = $result['quota'] > 0
                    ? round(($result['used'] / $result['quota']) * 100, 1)
                    : 0;
            } else {
                $result['error'] = 'ดึง usage ไม่สำเร็จ: '.$usageResponse->status();
            }
        } catch (\Exception $e) {
            $result['error'] = 'Exception: '.$e->getMessage();
        }

        return $result;
    }

    /**
     * ทดสอบส่ง push message ไปหา user (สำหรับ debug)
     *
     * @param  string  $userId  LINE user ID
     * @param  string  $message  ข้อความทดสอบ
     * @return array{success: bool, status: int|null, body: string|null, error: string|null}
     */
    public function testPushMessage(string $userId, string $message = '🔔 ทดสอบ push notification'): array
    {
        try {
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(10)
                ->connectTimeout(5)
                ->post(self::API_ENDPOINT.'/message/push', [
                    'to' => $userId,
                    'messages' => [
                        ['type' => 'text', 'text' => $message],
                    ],
                ]);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->body(),
                'error' => $response->successful() ? null : $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRichMessage(): bool
    {
        return true; // LINE รองรับ Flex Message
    }

    /**
     * {@inheritdoc}
     */
    public function sendMessage(string $recipientId, string $message, array $options = []): bool
    {
        $messages = [
            [
                'type' => 'text',
                'text' => $message,
            ],
        ];

        // ถ้ามี quick replies
        if (! empty($options['quick_replies'])) {
            $messages[0]['quickReply'] = [
                'items' => $this->buildQuickReplyItems($options['quick_replies']),
            ];
        }

        return $this->pushMessage($recipientId, $messages);
    }

    /**
     * ส่งข้อความแบบ priority (ข้าม Gatekeeper) — สำหรับแจ้งเตือนสำคัญหลังชำระเงิน
     *
     * @param  string  $recipientId  LINE user ID
     * @param  string  $message  ข้อความ
     * @param  array  $options  quick_replies, etc.
     */
    public function sendMessagePriority(string $recipientId, string $message, array $options = []): bool
    {
        $messages = [
            [
                'type' => 'text',
                'text' => $message,
            ],
        ];

        // ถ้ามี quick replies
        if (! empty($options['quick_replies'])) {
            $messages[0]['quickReply'] = [
                'items' => $this->buildQuickReplyItems($options['quick_replies']),
            ];
        }

        return $this->pushMessagePriority($recipientId, $messages);
    }

    /**
     * {@inheritdoc}
     */
    public function sendRichMessage(string $recipientId, array $richContent): bool
    {
        $messages = [
            [
                'type' => 'flex',
                'altText' => $richContent['alt_text'] ?? 'ข้อความจากระบบดูดวง',
                'contents' => $richContent['contents'] ?? $richContent,
            ],
        ];

        return $this->pushMessage($recipientId, $messages);
    }

    /**
     * 🚀 (2026-05-21) ส่ง image + text + quick replies ใน 1 push call เดียว
     *
     * เคสจริง: ลูกค้าเปิดไพ่เร็ว ๆ → 2 push calls ต่อใบ (image + text) = 20 calls/10 ใบ
     *   → LINE rate limit / dedup → บาง message ไม่ถึงลูกค้า
     * Fix: รวมเป็น 1 push call (atomic) — ทั้ง image + text + quickReply ส่งพร้อมกัน
     *   ลดเป็น 10 calls/10 ใบ → ลด rate limit pressure ครึ่ง
     *
     * @param  string  $recipientId  LINE user ID
     * @param  string  $imageUrl  รูปภาพ (HTTPS — auto convert)
     * @param  string  $text  ข้อความ
     * @param  array  $quickReplies  [['label'=>..., 'text'=>...], ...] (optional)
     */
    public function sendImageAndText(string $recipientId, string $imageUrl, string $text, array $quickReplies = []): bool
    {
        $imageUrl = $this->ensureHttps($imageUrl);

        $messages = [
            [
                'type' => 'image',
                'originalContentUrl' => $imageUrl,
                'previewImageUrl' => $imageUrl,
            ],
            [
                'type' => 'text',
                'text' => mb_substr($text, 0, 4900),  // LINE text max 5000
            ],
        ];

        // เพิ่ม quick replies ไปที่ text message (LINE: quickReply attaches to last message)
        if (! empty($quickReplies)) {
            $messages[1]['quickReply'] = [
                'items' => $this->buildQuickReplyItems($quickReplies),
            ];
        }

        return $this->pushMessage($recipientId, $messages);
    }

    /**
     * 💸 (2026-07-24) ส่ง image + text + quick replies โดย "reply ก่อน (ฟรี)" → fallback push
     *
     * ประหยัด LINE push quota: 1 reply call ใส่ image+text ได้ (สูงสุด 5 objects) = ฟรี
     *   ใช้ตอน replyToken ยังสด (มาจาก webhook ที่ลูกค้าเพิ่งพิมพ์) + รูปสร้าง sync ทันที
     *   เช่น Celtic เปิดไพ่ทีละใบ, ทำนายฟรี (free card)
     * ⚠️ ต้องมี push fallback เสมอ — ถ้า reply timeout/token หมด → push (sendImageAndText)
     *   เพื่อคงความเชื่อถือได้ (ลูกค้าจ่ายแล้วต้องเห็นไพ่)
     *
     * @param  string  $recipientId  LINE user ID
     * @param  string  $imageUrl  รูปภาพ (HTTPS — auto convert)
     * @param  string  $text  ข้อความ
     * @param  array  $quickReplies  [['label'=>..., 'text'=>...], ...] (optional)
     * @param  string|null  $replyToken  Reply token (ถ้ามี → ลอง reply ฟรีก่อน)
     */
    public function sendImageAndTextWithReplyFallback(string $recipientId, string $imageUrl, string $text, array $quickReplies = [], ?string $replyToken = null): bool
    {
        // ลอง reply ก่อน (ฟรี — รวม image + text ใน call เดียว)
        if ($replyToken) {
            $imageHttps = $this->ensureHttps($imageUrl);
            $messages = [
                [
                    'type' => 'image',
                    'originalContentUrl' => $imageHttps,
                    'previewImageUrl' => $imageHttps,
                ],
                [
                    'type' => 'text',
                    'text' => mb_substr($text, 0, 4900),
                ],
            ];
            if (! empty($quickReplies)) {
                $messages[1]['quickReply'] = [
                    'items' => $this->buildQuickReplyItems($quickReplies),
                ];
            }

            if ($this->replyMessage($replyToken, $messages)) {
                return true; // ✅ reply สำเร็จ = ฟรี (0 push)
            }
            // reply ล้มเหลว/timeout → fallback push
        }

        // Fallback: push (reliability — ลูกค้าต้องเห็นรูปเสมอ)
        return $this->sendImageAndText($recipientId, $imageUrl, $text, $quickReplies);
    }

    /**
     * 💸 (2026-07-24) ส่งหลาย message object ใน call เดียว — reply ก่อน (ฟรี) → fallback push
     *
     * ประหยัด push: เดิมยิงทีละชิ้น (รูป1 push + รูป2 push + ข้อความ push/reply) = หลาย call
     *   รวมเป็น 1 call (LINE รับสูงสุด 5 objects) → reply ฟรี หรือ push 1 ครั้ง
     * ใช้ตอนส่งรูปหลายใบ + ข้อความปิด (เช่น celtic_all_picked, session_ended, voice intro+audio)
     *
     * @param  string  $recipientId  LINE user ID
     * @param  array  $messages  LINE message objects (สูงสุด 5) — ['type'=>'image'|'text'|'audio', ...]
     * @param  string|null  $replyToken  Reply token (ถ้ามี → ลอง reply ฟรีก่อน)
     */
    public function sendMessagesWithReplyFallback(string $recipientId, array $messages, ?string $replyToken = null): bool
    {
        if (empty($messages)) {
            return true;
        }

        // LINE รับสูงสุด 5 objects/call
        $messages = array_slice($messages, 0, 5);

        if ($replyToken && $this->replyMessage($replyToken, $messages)) {
            return true; // ✅ reply สำเร็จ = ฟรี
        }

        return $this->pushMessage($recipientId, $messages);
    }

    /**
     * สร้าง LINE image message object (helper สำหรับรวมหลายข้อความใน call เดียว)
     */
    public function buildImageObject(string $imageUrl): array
    {
        $imageUrl = $this->ensureHttps($imageUrl);

        return [
            'type' => 'image',
            'originalContentUrl' => $imageUrl,
            'previewImageUrl' => $imageUrl,
        ];
    }

    /**
     * สร้าง LINE text message object (+ quick reply ถ้ามี) — helper สำหรับรวมหลายข้อความใน call เดียว
     *
     * @param  string  $text  ข้อความ
     * @param  array  $quickReplies  [['label'=>..., 'text'=>...], ...] (optional)
     */
    public function buildTextObject(string $text, array $quickReplies = []): array
    {
        $obj = [
            'type' => 'text',
            'text' => mb_substr($text, 0, 4900),
        ];
        if (! empty($quickReplies)) {
            $obj['quickReply'] = [
                'items' => $this->buildQuickReplyItems($quickReplies),
            ];
        }

        return $obj;
    }

    /**
     * สร้าง LINE audio message object — helper สำหรับรวมกับ text ใน call เดียว
     *
     * @param  string  $audioUrl  URL ไฟล์เสียง (m4a — HTTPS)
     * @param  int  $durationMs  ความยาว (มิลลิวินาที)
     */
    public function buildAudioObject(string $audioUrl, int $durationMs = 180000): array
    {
        return [
            'type' => 'audio',
            'originalContentUrl' => $this->ensureHttps($audioUrl),
            'duration' => max(1000, min($durationMs, 600000)),  // 1s - 10 min (ตรงกับ sendAudio)
        ];
    }

    /**
     * 🎨 (2026-07-25) สร้าง LINE flex message object (+ quick reply ถ้ามี)
     *
     * helper สำหรับรวม Flex กับ message อื่นใน call เดียว (reply/push ผ่าน sendMessagesWithReplyFallback)
     *
     * @param  array  $flexContent  Flex bubble/carousel contents
     * @param  string  $altText  ข้อความ preview บน notification
     * @param  array  $quickReplies  [['label'=>..., 'text'=>...], ...] (optional)
     */
    public function buildFlexObject(array $flexContent, string $altText, array $quickReplies = []): array
    {
        $obj = [
            'type' => 'flex',
            'altText' => mb_substr($altText, 0, 400),
            'contents' => $flexContent,
        ];
        if (! empty($quickReplies)) {
            $obj['quickReply'] = [
                'items' => $this->buildQuickReplyItems($quickReplies),
            ];
        }

        return $obj;
    }

    /**
     * 🔘 (2026-07-25) ปุ่มกดใน Flex ที่ "คุมสีตัวอักษรได้เอง"
     *
     * 🐛 ทำไมไม่ใช้ component `button`: LINE บังคับสีตัวอักษรตาม style
     *   - style=primary → ตัวอักษรขาวเสมอ (บนพื้นทองอ่อน = จาง อ่านยาก)
     *   - style=secondary → ตัวอักษรเข้ม #111111 เสมอ (บนพื้นม่วงเข้ม = มองไม่เห็น!)
     *   เคสจริง: ปุ่ม "🪬 ดูคุณไสย" (secondary + พื้น #3A2360) ตัวหนังสือกลืนหายทั้งปุ่ม
     *
     * FIX: ใช้ box + action (LINE ให้ box มี action ได้) → กำหนด backgroundColor + สีตัวอักษรคู่กันเอง
     *   คู่สีตามปุ่มจริงบนเว็บจันทรา: พื้นทอง #E7C97A + ตัวหนังสือเข้ม #1A0E2E
     *
     * @param  string  $label  ข้อความบนปุ่ม
     * @param  array  $action  LINE action object (message/uri/postback)
     * @param  string  $variant  'gold' (CTA หลัก) | 'violet' (ปุ่มรอง) | 'ghost' (ปุ่มเบา)
     */
    public function buildFlexTapButton(string $label, array $action, string $variant = 'gold'): array
    {
        // [พื้นหลัง, สีตัวอักษร] — ทุกคู่ผ่านเกณฑ์ contrast อ่านชัด (ลูกค้าส่วนมากสูงอายุ)
        [$bg, $fg] = match ($variant) {
            'violet' => ['#7A3DF0', '#FFFFFF'],
            'ghost' => ['#2E1548', '#F6E3A8'],
            default => ['#E7C97A', '#1A0E2E'],
        };

        return [
            'type' => 'box',
            'layout' => 'vertical',
            'backgroundColor' => $bg,
            'cornerRadius' => 'md',
            'paddingAll' => 'md',
            'action' => $action,
            'contents' => [
                [
                    'type' => 'text',
                    'text' => $label,
                    'color' => $fg,
                    'size' => 'md',
                    'weight' => 'bold',
                    'align' => 'center',
                    'wrap' => true,
                ],
            ],
        ];
    }

    /**
     * 🃏 (2026-07-25) Flex bubble "เปิดไพ่ Celtic" — รูปไพ่ + คำแนะนำในกล่องเดียว
     *
     * user spec: "รูปและคำแนะนำเป็นกราฟฟิคอยู่กล่องเดียวกัน เพื่อประหยัดพุช"
     * โทนสีตามธีมเว็บจันทรา (juntra-payakorn): ม่วงเข้ม + ทอง
     *
     * @param  string  $imageUrl  รูปไพ่ (portrait ~9:16)
     * @param  array  $card  ['position_name','card_name_th','card_name_en','reversed_label','meaning','next_prompt','picked_count']
     */
    public function buildCelticCardFlexBubble(string $imageUrl, array $card): array
    {
        $count = (int) ($card['picked_count'] ?? 0);
        $positionName = (string) ($card['position_name'] ?? '');
        $nextPrompt = trim((string) ($card['next_prompt'] ?? ''));

        $bodyContents = [
            // ซ้าย: รูปไพ่ (สัดส่วนไพ่ทาโรต์ 9:16)
            [
                'type' => 'image',
                'url' => $this->ensureHttps($imageUrl),
                'aspectRatio' => '9:16',
                'aspectMode' => 'cover',
                'size' => 'full',
                'flex' => 4,
                'gravity' => 'top',
            ],
            // ขวา: ชื่อไพ่ + ความหมาย
            [
                'type' => 'box',
                'layout' => 'vertical',
                'flex' => 6,
                'spacing' => 'sm',
                'contents' => [
                    ['type' => 'text', 'text' => (string) ($card['card_name_th'] ?? 'ไพ่'), 'weight' => 'bold', 'size' => 'lg', 'color' => '#F6E3A8', 'wrap' => true],
                    ['type' => 'text', 'text' => trim(($card['card_name_en'] ?? '').' '.($card['reversed_label'] ?? '')), 'size' => 'xs', 'color' => '#C8B8DE', 'wrap' => true],
                    ['type' => 'separator', 'margin' => 'md', 'color' => '#4C1D95'],
                    ['type' => 'text', 'text' => '📖 '.(string) ($card['meaning'] ?? ''), 'size' => 'sm', 'color' => '#F3E9FF', 'wrap' => true, 'margin' => 'md', 'lineSpacing' => '4px'],
                ],
            ],
        ];

        $bubble = [
            'type' => 'bubble',
            'size' => 'mega',
            'header' => [
                'type' => 'box',
                'layout' => 'horizontal',
                'paddingAll' => 'lg',
                'backgroundColor' => '#241038',
                'contents' => [
                    ['type' => 'text', 'text' => "🃏 ไพ่ใบที่ {$count}/10", 'color' => '#E7C97A', 'size' => 'md', 'weight' => 'bold', 'flex' => 0],
                    ['type' => 'text', 'text' => $positionName, 'color' => '#F3E9FF', 'size' => 'sm', 'align' => 'end', 'gravity' => 'center', 'wrap' => true],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'horizontal',
                'spacing' => 'lg',
                'paddingAll' => 'lg',
                'backgroundColor' => '#160A26',
                'contents' => $bodyContents,
            ],
        ];

        // แถบล่าง: ขั้นตอนถัดไป (เชิญเปิดไพ่ใบต่อไป)
        if ($nextPrompt !== '') {
            $bubble['footer'] = [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'lg',
                'backgroundColor' => '#0F081C',
                'contents' => [
                    ['type' => 'text', 'text' => $nextPrompt, 'size' => 'sm', 'color' => '#C8B8DE', 'wrap' => true, 'lineSpacing' => '4px'],
                ],
            ];
        }

        return $bubble;
    }

    /**
     * 💎 (2026-07-25) Flex เมนูเลือกแพคเกจดูดวง (tier choice) — กล่องสวยพร้อมกติกา
     *
     * user spec: "กดปุ่มดูดวงแล้วให้เลือกอีกทีในแชท เป็นกล่องสวยๆ และระบุรายละเอียดกติกา"
     * ปุ่มส่ง text "39"/"99"/"เริ่มเลย" → tier-direct logic เดิมจับ (ไม่แตะ state machine)
     * โทนสีธีมเว็บจันทรา (juntra-payakorn): ม่วงเข้ม + ทอง — ตัวหนังสือใหญ่เพื่อผู้สูงอายุ
     *
     * @param  array  $m  ['welcome_line','deep_enabled','celtic_enabled','deep_window','qa_window','q_limit_text','voice_enabled']
     * @param  string  $deepPrice  ราคา 39 (formatted)
     * @param  string  $celticPrice  ราคา 99 (formatted)
     * @param  bool  $celticOnlyIntro  โหมด Celtic-only (ปุ่ม "เริ่มเลย")
     * @param  bool  $blackMagicEnabled  เพิ่มปุ่มดูคุณไสย
     * @return array Flex bubble หรือ carousel
     */
    public function buildTierChoiceFlexMessage(array $m, string $deepPrice, string $celticPrice, bool $celticOnlyIntro = false, bool $blackMagicEnabled = false): array
    {
        // แถวกติกา 1 รายการ (✦ ทอง + ข้อความ)
        $ruleRow = function (string $text): array {
            return [
                'type' => 'box',
                'layout' => 'horizontal',
                'spacing' => 'sm',
                'contents' => [
                    ['type' => 'text', 'text' => '✦', 'color' => '#E7C97A', 'size' => 'md', 'flex' => 0],
                    ['type' => 'text', 'text' => $text, 'color' => '#F3E9FF', 'size' => 'md', 'wrap' => true, 'flex' => 1, 'lineSpacing' => '4px'],
                ],
            ];
        };

        // สร้าง bubble ต่อแพคเกจ
        $makeBubble = function (string $badge, string $title, string $price, array $rules, array $buttons, bool $highlight = false) use ($ruleRow): array {
            $ruleBoxes = array_map($ruleRow, $rules);

            // 🔘 (2026-07-25) ปุ่ม box+action — คุมสีตัวอักษรเอง (component button บังคับสีจนกลืนพื้น)
            $footerButtons = [];
            foreach ($buttons as $btn) {
                $footerButtons[] = $this->buildFlexTapButton(
                    $btn['label'],
                    ['type' => 'message', 'label' => mb_substr($btn['label'], 0, 20), 'text' => $btn['text']],
                    $btn['primary'] ? 'gold' : 'violet'
                );
            }

            return [
                'type' => 'bubble',
                'size' => 'mega',
                'header' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'paddingAll' => 'xl',
                    'backgroundColor' => $highlight ? '#2E1548' : '#241038',
                    'contents' => [
                        ['type' => 'text', 'text' => $badge, 'color' => '#E7C97A', 'size' => 'sm', 'weight' => 'bold'],
                        ['type' => 'text', 'text' => $title, 'color' => '#FFFFFF', 'size' => 'xl', 'weight' => 'bold', 'wrap' => true, 'margin' => 'sm'],
                        [
                            'type' => 'box',
                            'layout' => 'baseline',
                            'margin' => 'md',
                            'contents' => [
                                ['type' => 'text', 'text' => "฿{$price}", 'color' => '#F6E3A8', 'size' => '3xl', 'weight' => 'bold', 'flex' => 0],
                                ['type' => 'text', 'text' => ' ค่าครู/ครั้ง', 'color' => '#C8B8DE', 'size' => 'sm', 'margin' => 'sm'],
                            ],
                        ],
                    ],
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'md',
                    'paddingAll' => 'xl',
                    'backgroundColor' => '#160A26',
                    'contents' => $ruleBoxes,
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'paddingAll' => 'lg',
                    'backgroundColor' => '#160A26',
                    'contents' => $footerButtons,
                ],
            ];
        };

        $deepWindow = (int) ($m['deep_window'] ?? 7);
        $qaWindow = (int) ($m['qa_window'] ?? 15);
        $qLimitText = (string) ($m['q_limit_text'] ?? 'ไม่จำกัด');

        // 👑 Bubble Celtic 99
        $celticRules = [
            'เปิดไพ่โบราณ Celtic Cross ครบ 10 ใบ ฟันธงทีละใบ',
            "ถามแม่หมอได้ {$qLimitText} ภายใน {$qaWindow} นาที ตอบสดไม่มีรอ",
            'พร้อมภาพไพ่สวยๆ เก็บเป็นที่ระลึก ดูย้อนหลังได้',
        ];
        if (! empty($m['voice_enabled'])) {
            $celticRules[] = 'ฟังเสียงสรุปคำทำนายท้ายรอบได้';
        }
        $celticButtons = $celticOnlyIntro
            ? [['label' => "✨ เริ่มเลย {$celticPrice} บาท", 'text' => 'เริ่มเลย', 'primary' => true]]
            : [['label' => "👑 เลือกแพคเกจ {$celticPrice} บาท", 'text' => '99', 'primary' => true]];
        if ($blackMagicEnabled) {
            $celticButtons[] = ['label' => '🪬 ดูคุณไสย', 'text' => 'ดูคุณไสย', 'primary' => false];
        }
        $celticBubble = $makeBubble('👑 VIP ยอดนิยม', 'ไพ่เต็มสำรับ Celtic', $celticPrice, $celticRules, $celticButtons, true);

        // 🔹 Bubble Deep 39
        $deepBubble = $makeBubble('🔹 เริ่มต้น', 'ดูพื้นดวง', $deepPrice, [
            'ใช้วันเดือนปีเกิด คำนวณดาวประจำตัว ราศี ลัคนา ผสมกับไพ่',
            'อ่านภาพรวมชีวิตให้ทันที',
            "คุยถามแม่หมอต่อได้ {$deepWindow} นาที",
        ], [
            ['label' => "🔹 เลือกแพคเกจ {$deepPrice} บาท", 'text' => '39', 'primary' => true],
        ]);

        // Celtic-only → bubble เดียว / มีทั้งคู่ → carousel (Celtic นำ — ยอดนิยม)
        if ($celticOnlyIntro || empty($m['deep_enabled'])) {
            return $celticBubble;
        }
        if (empty($m['celtic_enabled'])) {
            return $deepBubble;
        }

        return [
            'type' => 'carousel',
            'contents' => [$celticBubble, $deepBubble],
        ];
    }

    /**
     * 📚 (2026-07-25) Flex รายการคำทำนายย้อนหลัง (สูงสุด 3 บิลล่าสุด) — เลือกอ่านได้
     *
     * user spec: "ระบบอ่านคำทำนายย้อนหลังจะล้ำกว่าเดิม เลือกย้อนหลังได้ถึง 3 บิลล่าสุด"
     * ปุ่มส่ง text "ดูบิล FTU-..." → handleViewBill เดิม (มี ownership check แล้ว)
     *
     * @param  array  $bills  [['ref','date','amount','type_label','question_preview'], ...]
     */
    public function buildMyBillsFlexMessage(array $bills): array
    {
        $rows = [];
        foreach ($bills as $i => $bill) {
            if ($i > 0) {
                $rows[] = ['type' => 'separator', 'margin' => 'lg', 'color' => '#3A2360'];
            }

            $detail = [
                ['type' => 'text', 'text' => (string) ($bill['type_label'] ?? 'คำทำนาย'), 'color' => '#F6E3A8', 'size' => 'lg', 'weight' => 'bold', 'wrap' => true],
                ['type' => 'text', 'text' => '🧾 '.($bill['ref'] ?? '').'  ·  ฿'.($bill['amount'] ?? ''), 'color' => '#C8B8DE', 'size' => 'sm', 'margin' => 'sm'],
                ['type' => 'text', 'text' => '📅 '.($bill['date'] ?? ''), 'color' => '#C8B8DE', 'size' => 'sm'],
            ];
            if (! empty($bill['question_preview'])) {
                $detail[] = ['type' => 'text', 'text' => '❓ '.$bill['question_preview'], 'color' => '#8A7AA8', 'size' => 'sm', 'wrap' => true, 'margin' => 'sm'];
            }
            // 🔘 ปุ่ม box+action — ตัวหนังสือเข้มบนพื้นทอง (อ่านชัดกว่าขาวบนทอง)
            $readButton = $this->buildFlexTapButton(
                '📖 อ่านคำทำนายบิลนี้',
                ['type' => 'message', 'label' => '📖 อ่านบิลนี้', 'text' => 'ดูบิล '.($bill['ref'] ?? '')],
                'gold'
            );
            $readButton['margin'] = 'md';
            $detail[] = $readButton;

            $rows[] = [
                'type' => 'box',
                'layout' => 'vertical',
                'margin' => $i > 0 ? 'lg' : 'none',
                'contents' => $detail,
            ];
        }

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'backgroundColor' => '#241038',
                'contents' => [
                    ['type' => 'text', 'text' => '📚 คำทำนายย้อนหลัง', 'color' => '#E7C97A', 'size' => 'xl', 'weight' => 'bold'],
                    ['type' => 'text', 'text' => 'เลือกอ่านได้ 3 ครั้งล่าสุด — กดปุ่มบิลที่ต้องการ', 'color' => '#C8B8DE', 'size' => 'sm', 'margin' => 'sm', 'wrap' => true],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'backgroundColor' => '#160A26',
                'contents' => $rows,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function sendImage(string $recipientId, string $imageUrl, ?string $previewUrl = null): bool
    {
        // ✅ LINE Messaging API ต้องใช้ HTTPS เท่านั้น
        $imageUrl = $this->ensureHttps($imageUrl);
        $previewUrl = $previewUrl ? $this->ensureHttps($previewUrl) : $imageUrl;

        $messages = [
            [
                'type' => 'image',
                'originalContentUrl' => $imageUrl,
                'previewImageUrl' => $previewUrl,
            ],
        ];

        return $this->pushMessage($recipientId, $messages);
    }

    /**
     * แปลง URL ให้เป็น HTTPS (LINE Messaging API ต้องใช้ HTTPS เท่านั้น)
     */
    protected function ensureHttps(string $url): string
    {
        if (str_starts_with($url, 'http://')) {
            return 'https://'.substr($url, 7);
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function sendQuickReplies(string $recipientId, string $message, array $quickReplies): bool
    {
        return $this->sendMessage($recipientId, $message, ['quick_replies' => $quickReplies]);
    }

    /**
     * 🎙️ (2026-05-08) ส่ง audio message ไป LINE
     *
     * LINE Audio Message API:
     *   - originalContentUrl: ต้องเป็น HTTPS + .m4a/.mp3 (LINE จะ transcode เอง แต่แนะนำ m4a/mp3)
     *   - duration: ต้องระบุเป็น ms (ถ้าไม่รู้ ใส่ 60000 = 1 นาที — LINE ใช้แค่แสดง progress bar)
     *   - max file size: 200MB
     *
     * @param  string  $recipientId  LINE userId
     * @param  string  $audioUrl  HTTPS URL ของ mp3 file (public)
     * @param  int  $durationMs  ระยะเวลาประมาณการ (ms)
     */
    public function sendAudio(string $recipientId, string $audioUrl, int $durationMs = 60000): bool
    {
        $audioUrl = $this->ensureHttps($audioUrl);

        $messages = [
            [
                'type' => 'audio',
                'originalContentUrl' => $audioUrl,
                'duration' => max(1000, min($durationMs, 600000)),  // 1s - 10 min
            ],
        ];

        return $this->pushMessage($recipientId, $messages);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile(string $userId): ?array
    {
        // ✅ Cache profile 24 ชั่วโมง — ลด LINE API calls (profile ไม่ค่อยเปลี่ยน)
        $cacheKey = "line_profile:{$userId}";
        $cached = cache()->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            // ⚡ timeout สั้น — getProfile ไม่สำคัญเท่าส่งข้อความ
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(5)
                ->connectTimeout(3)
                ->get(self::API_ENDPOINT."/profile/{$userId}");

            if ($response->successful()) {
                $data = $response->json();

                $profile = [
                    'id' => $data['userId'] ?? $userId,
                    'name' => $data['displayName'] ?? null,
                    'picture_url' => $data['pictureUrl'] ?? null,
                    'status_message' => $data['statusMessage'] ?? null,
                    'language' => $data['language'] ?? 'th',
                    'platform' => 'line',
                ];

                // ✅ Cache สำเร็จ 24 ชม.
                cache()->put($cacheKey, $profile, 86400);

                return $profile;
            }

            Log::warning('LINE: ไม่สามารถดึงโปรไฟล์ได้', [
                'user_id' => $userId,
                'status' => $response->status(),
            ]);

            return null;

        } catch (\Exception $e) {
            // Timeout หรือ error → แค่ log ไม่ block (Gatekeeper จัดการ rate limit แทน)
            Log::warning('LINE getProfile: Exception (ลองใหม่ครั้งหน้า)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isMessageEvent(array $event): bool
    {
        return ($event['type'] ?? null) === 'message' &&
               ($event['message']['type'] ?? null) === 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageText(array $event): ?string
    {
        if (! $this->isMessageEvent($event)) {
            return null;
        }

        return $event['message']['text'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserIdFromEvent(array $event): ?string
    {
        return $event['source']['userId'] ?? null;
    }

    // ============================================================
    // Flex Message Templates - สำหรับแสดงผลสวยงาม
    // ============================================================

    /**
     * สร้าง Flex Message สำหรับคำทำนาย
     *
     * @param  string  $prediction  คำทำนาย
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  string|null  $billRef  เลขที่บิล
     * @return array Flex Message content
     */
    public function buildFortuneFlexMessage(string $prediction, string $userName, ?string $billRef = null, ?string $paidAt = null): array
    {
        $bodyContents = [
            // ชื่อผู้ใช้
            [
                'type' => 'box',
                'layout' => 'horizontal',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🌙',
                        'size' => 'xl',
                        'flex' => 0,
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'flex' => 1,
                        'paddingStart' => 'md',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => "สำหรับคุณ{$userName}",
                                'size' => 'md',
                                'weight' => 'bold',
                                'color' => '#6B46C1',
                            ],
                            [
                                'type' => 'text',
                                'text' => 'ดวงชะตาของคุณวันนี้',
                                'size' => 'xs',
                                'color' => '#999999',
                                'margin' => 'xs',
                            ],
                        ],
                    ],
                ],
                'alignItems' => 'center',
            ],
            // เส้นแบ่ง
            [
                'type' => 'separator',
                'margin' => 'xl',
                'color' => '#E8E0FF',
            ],
            // คำทำนาย
            [
                'type' => 'text',
                'text' => $prediction,
                'wrap' => true,
                'size' => 'md',
                'color' => '#333333',
                'margin' => 'xl',
                'lineSpacing' => '6px',
            ],
        ];

        // เพิ่มเลขที่บิล + วันเวลาชำระเงิน (ถ้ามี)
        if ($billRef || $paidAt) {
            $bodyContents[] = [
                'type' => 'separator',
                'margin' => 'xl',
                'color' => '#E8E0FF',
            ];

            $infoRows = [];

            if ($billRef) {
                $infoRows[] = [
                    'type' => 'box',
                    'layout' => 'horizontal',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '🔖 เลขที่บิล:',
                            'size' => 'xs',
                            'color' => '#888888',
                            'flex' => 2,
                        ],
                        [
                            'type' => 'text',
                            'text' => $billRef,
                            'size' => 'xs',
                            'color' => '#6B46C1',
                            'flex' => 3,
                            'weight' => 'bold',
                        ],
                    ],
                ];
            }

            if ($paidAt) {
                $infoRows[] = [
                    'type' => 'box',
                    'layout' => 'horizontal',
                    'margin' => 'xs',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '📅 วันที่ชำระ:',
                            'size' => 'xs',
                            'color' => '#888888',
                            'flex' => 2,
                        ],
                        [
                            'type' => 'text',
                            'text' => $paidAt,
                            'size' => 'xs',
                            'color' => '#6B46C1',
                            'flex' => 3,
                        ],
                    ],
                ];
            }

            $bodyContents[] = [
                'type' => 'box',
                'layout' => 'vertical',
                'margin' => 'md',
                'backgroundColor' => '#F8F7FF',
                'cornerRadius' => 'md',
                'paddingAll' => 'sm',
                'contents' => $infoRows,
            ];
        }

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => [
                'header' => ['backgroundColor' => '#6B46C1'],
                'footer' => ['backgroundColor' => '#F8F7FF'],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🔮',
                        'size' => '3xl',
                        'align' => 'center',
                    ],
                    [
                        'type' => 'text',
                        'text' => '✨ หมอจันทราดูดวง ✨',
                        'color' => '#FFFFFF',
                        'size' => 'lg',
                        'weight' => 'bold',
                        'align' => 'center',
                        'margin' => 'md',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'ผลคำทำนายดวงชะตา',
                        'color' => '#FFFFFFCC',
                        'size' => 'sm',
                        'align' => 'center',
                        'margin' => 'sm',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => $bodyContents,
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'md',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '✨ แชร์ให้เพื่อนมาดูดวงด้วยกัน',
                        'size' => 'xs',
                        'color' => '#9B8EC4',
                        'align' => 'center',
                    ],
                ],
            ],
        ];
    }

    /**
     * แบ่งคำทำนายยาวเป็น Flex bubbles หลายใบ
     *
     * ⚡ ลดปัญหาข้อความยาวเกิน → LINE ไม่ส่ง หรือ Flex body ล้น
     * แบ่งตามย่อหน้า (\n\n) หรือตามขนาด (~800 ตัวอักษรต่อ bubble)
     *
     * @param  string  $prediction  คำทำนายทั้งหมด
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  string|null  $billRef  เลขที่บิล
     * @return array[] Array ของ Flex bubble arrays
     */
    public function buildSplitFortuneMessages(string $prediction, string $userName, ?string $billRef = null, ?string $paidAt = null): array
    {
        $maxCharsPerBubble = 800;

        // ถ้าสั้นพอ → ส่ง bubble เดียว
        if (mb_strlen($prediction) <= $maxCharsPerBubble) {
            return [$this->buildFortuneFlexMessage($prediction, $userName, $billRef, $paidAt)];
        }

        // แบ่งตามย่อหน้า (\n\n)
        $paragraphs = preg_split('/\n{2,}/', trim($prediction));
        $chunks = [];
        $currentChunk = '';

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (empty($para)) {
                continue;
            }

            // ถ้าเพิ่มย่อหน้านี้แล้วยาวเกิน → ปิด chunk เริ่มใหม่
            if (mb_strlen($currentChunk) > 0 && mb_strlen($currentChunk."\n\n".$para) > $maxCharsPerBubble) {
                $chunks[] = trim($currentChunk);
                $currentChunk = $para;
            } else {
                $currentChunk .= ($currentChunk ? "\n\n" : '').$para;
            }
        }
        if (! empty(trim($currentChunk))) {
            $chunks[] = trim($currentChunk);
        }

        // ถ้าแบ่งไม่ได้ (1 ย่อหน้ายาวมาก) → บังคับตัด
        if (count($chunks) === 1 && mb_strlen($chunks[0]) > $maxCharsPerBubble) {
            $text = $chunks[0];
            $chunks = [];
            while (mb_strlen($text) > 0) {
                $chunks[] = mb_substr($text, 0, $maxCharsPerBubble);
                $text = mb_substr($text, $maxCharsPerBubble);
            }
        }

        // สร้าง bubbles
        $bubbles = [];
        $total = count($chunks);
        foreach ($chunks as $i => $chunk) {
            $partNum = $i + 1;
            $isFirst = ($i === 0);
            $isLast = ($i === $total - 1);

            $bodyContents = [];

            if ($isFirst) {
                // bubble แรก — มี header ชื่อผู้ใช้
                $bodyContents[] = [
                    'type' => 'box', 'layout' => 'horizontal',
                    'contents' => [
                        ['type' => 'text', 'text' => '🌙', 'size' => 'xl', 'flex' => 0],
                        [
                            'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                            'contents' => [
                                ['type' => 'text', 'text' => "สำหรับคุณ{$userName}", 'size' => 'md', 'weight' => 'bold', 'color' => '#6B46C1'],
                                ['type' => 'text', 'text' => 'ดวงชะตาของคุณวันนี้', 'size' => 'xs', 'color' => '#999999', 'margin' => 'xs'],
                            ],
                        ],
                    ],
                    'alignItems' => 'center',
                ];
                $bodyContents[] = ['type' => 'separator', 'margin' => 'xl', 'color' => '#E8E0FF'];
            } else {
                // bubble ต่อๆ ไป — แสดงลำดับ
                $bodyContents[] = [
                    'type' => 'text',
                    'text' => "📄 ส่วนที่ {$partNum} / {$total}",
                    'size' => 'xs',
                    'color' => '#6B46C1',
                    'weight' => 'bold',
                ];
                $bodyContents[] = ['type' => 'separator', 'margin' => 'md', 'color' => '#E8E0FF'];
            }

            // เนื้อหาคำทำนาย
            $bodyContents[] = [
                'type' => 'text',
                'text' => $chunk,
                'wrap' => true,
                'size' => 'md',
                'color' => '#333333',
                'margin' => 'lg',
                'lineSpacing' => '6px',
            ];

            // Bill ref + วันเวลาชำระ ใน bubble แรก
            if ($isFirst && ($billRef || $paidAt)) {
                $bodyContents[] = ['type' => 'separator', 'margin' => 'xl', 'color' => '#E8E0FF'];

                $infoRows = [];
                if ($billRef) {
                    $infoRows[] = [
                        'type' => 'box', 'layout' => 'horizontal',
                        'contents' => [
                            ['type' => 'text', 'text' => '🔖 บิล:', 'size' => 'xs', 'color' => '#888888', 'flex' => 2],
                            ['type' => 'text', 'text' => $billRef, 'size' => 'xs', 'color' => '#6B46C1', 'flex' => 3, 'weight' => 'bold'],
                        ],
                    ];
                }
                if ($paidAt) {
                    $infoRows[] = [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'xs',
                        'contents' => [
                            ['type' => 'text', 'text' => '📅 วันที่:', 'size' => 'xs', 'color' => '#888888', 'flex' => 2],
                            ['type' => 'text', 'text' => $paidAt, 'size' => 'xs', 'color' => '#6B46C1', 'flex' => 3],
                        ],
                    ];
                }

                $bodyContents[] = [
                    'type' => 'box', 'layout' => 'vertical', 'margin' => 'md',
                    'backgroundColor' => '#F8F7FF', 'cornerRadius' => 'md', 'paddingAll' => 'sm',
                    'contents' => $infoRows,
                ];
            }

            $bubble = [
                'type' => 'bubble',
                'size' => 'mega',
                'body' => [
                    'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                    'contents' => $bodyContents,
                ],
            ];

            // Header เฉพาะ bubble แรก
            if ($isFirst) {
                $bubble['styles'] = ['header' => ['backgroundColor' => '#6B46C1'], 'footer' => ['backgroundColor' => '#F8F7FF']];
                $bubble['header'] = [
                    'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                    'contents' => [
                        ['type' => 'text', 'text' => '🔮', 'size' => '3xl', 'align' => 'center'],
                        ['type' => 'text', 'text' => '✨ หมอจันทราดูดวง ✨', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold', 'align' => 'center', 'margin' => 'md'],
                    ],
                ];
            }

            // Footer ใน bubble สุดท้าย
            if ($isLast) {
                $bubble['footer'] = [
                    'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'md',
                    'contents' => [
                        ['type' => 'text', 'text' => '✨ แชร์ให้เพื่อนมาดูดวงด้วยกัน', 'size' => 'xs', 'color' => '#9B8EC4', 'align' => 'center'],
                    ],
                ];
            }

            $bubbles[] = $bubble;
        }

        return $bubbles;
    }

    /**
     * สร้าง Flex Message สำหรับเสนอดูดวงละเอียด (Upsell)
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  float  $price  ราคา
     * @return array Flex Message content
     */
    public function buildUpsellFlexMessage(string $userName, float $price): array
    {
        $userName = $this->sanitizeUserName($userName);

        return [
            'type' => 'bubble',
            'styles' => [
                'header' => [
                    'backgroundColor' => '#F6AD55',
                ],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🌟 ดูดวง 🌟',
                        'color' => '#FFFFFF',
                        'size' => 'lg',
                        'weight' => 'bold',
                        'align' => 'center',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => "คุณ{$userName} อยากรู้ลึกกว่านี้ไหมคะ?",
                        'wrap' => true,
                        'size' => 'md',
                    ],
                    [
                        'type' => 'separator',
                        'margin' => 'lg',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'lg',
                        'contents' => [
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'contents' => [
                                    ['type' => 'text', 'text' => '📅', 'flex' => 1],
                                    ['type' => 'text', 'text' => 'บอกวันเดือนปีเกิด', 'flex' => 5, 'size' => 'sm'],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'margin' => 'sm',
                                'contents' => [
                                    ['type' => 'text', 'text' => '❓', 'flex' => 1],
                                    ['type' => 'text', 'text' => 'ถามได้ '.FortuneConversationService::REQUIRED_QUESTIONS.' คำถาม', 'flex' => 5, 'size' => 'sm'],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'margin' => 'sm',
                                'contents' => [
                                    ['type' => 'text', 'text' => '💰', 'flex' => 1],
                                    ['type' => 'text', 'text' => "เพียง {$price} บาท", 'flex' => 5, 'size' => 'sm', 'weight' => 'bold', 'color' => '#6B46C1'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'horizontal',
                'spacing' => 'md',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#6B46C1',
                        'action' => [
                            'type' => 'message',
                            'label' => '✨ ต้องการ',
                            'text' => 'ดูดวง',
                        ],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'action' => [
                            'type' => 'message',
                            'label' => 'ไม่ต้องการ',
                            'text' => 'ไม่ต้องการ',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message สำหรับแสดงบัญชีธนาคาร
     *
     * @param  array  $bankAccounts  รายการบัญชีธนาคาร
     * @param  float  $amount  ยอดเงินที่ต้องโอน
     * @param  string  $expiresAt  เวลาหมดอายุ
     * @param  string|null  $billRef  เลขที่บิล
     * @return array Flex Message content
     */
    public function buildPaymentFlexMessage(array $bankAccounts, float $amount, string $expiresAt, ?string $billRef = null): array
    {
        $bankContents = [];

        foreach ($bankAccounts as $account) {
            $isPromptpay = $account['is_promptpay'] ?? false;
            $contents = [];

            // ชื่อธนาคาร/พร้อมเพย์
            $contents[] = [
                'type' => 'text',
                'text' => $isPromptpay ? "📱 {$account['bank_name']}" : "🏦 {$account['bank_name']}",
                'weight' => 'bold',
                'size' => 'sm',
            ];

            // เลขบัญชี/พร้อมเพย์
            $numberLabel = $isPromptpay ? 'พร้อมเพย์' : 'เลขบัญชี';
            $contents[] = [
                'type' => 'text',
                'text' => "{$numberLabel}: {$account['account_number']}",
                'size' => 'sm',
                'margin' => 'sm',
            ];

            // ชื่อบัญชี
            $contents[] = [
                'type' => 'text',
                'text' => "ชื่อ: {$account['account_name']}",
                'size' => 'sm',
            ];

            // PromptPay เสริม (กรณี both mode)
            if (! $isPromptpay && ! empty($account['promptpay_id'])) {
                $contents[] = [
                    'type' => 'text',
                    'text' => "📱 พร้อมเพย์: {$account['promptpay_id']}",
                    'size' => 'sm',
                    'color' => '#6B46C1',
                    'margin' => 'sm',
                ];
            }

            $bankContents[] = [
                'type' => 'box',
                'layout' => 'vertical',
                'margin' => 'lg',
                'paddingAll' => 'md',
                'backgroundColor' => $isPromptpay ? '#F0E6FF' : '#F7FAFC',
                'cornerRadius' => 'md',
                'contents' => $contents,
            ];
        }

        $headerContents = [
            [
                'type' => 'text',
                'text' => '฿'.number_format($amount, 2),
                'color' => '#FFFFFF',
                'size' => 'xxl',
                'weight' => 'bold',
                'align' => 'center',
            ],
            [
                'type' => 'text',
                'text' => '⚠️ โอนตรงเป๊ะ — ทศนิยมต้องตรง',
                'color' => '#FED7D7',
                'size' => 'sm',
                'align' => 'center',
                'margin' => 'sm',
                'weight' => 'bold',
            ],
            [
                'type' => 'text',
                'text' => '✅ ตรง = ส่งคำทำนายใน 1-3 นาที',
                'color' => '#FFFFFF',
                'size' => 'xs',
                'align' => 'center',
            ],
            [
                'type' => 'text',
                'text' => '❌ ผิด = รอแอดมินตรวจ (อาจช้าหลายชม.)',
                'color' => '#FFE0B2',
                'size' => 'xs',
                'align' => 'center',
                'margin' => 'sm',
            ],
        ];

        if ($billRef) {
            $headerContents[] = [
                'type' => 'text',
                'text' => "🔖 บิล: {$billRef}",
                'color' => '#E9D8FD',
                'size' => 'xs',
                'align' => 'center',
                'margin' => 'sm',
            ];
        }

        return [
            'type' => 'bubble',
            'styles' => [
                'header' => [
                    'backgroundColor' => '#6B46C1',
                ],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => $headerContents,
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => array_merge(
                    [
                        [
                            'type' => 'text',
                            'text' => isset($bankAccounts[0]['is_promptpay']) && $bankAccounts[0]['is_promptpay']
                                ? '📱 ช่องทางโอน (พร้อมเพย์)'
                                : '💳 บัญชีรับโอน',
                            'weight' => 'bold',
                            'size' => 'md',
                        ],
                    ],
                    $bankContents,
                    [
                        [
                            'type' => 'separator',
                            'margin' => 'lg',
                        ],
                        [
                            'type' => 'text',
                            'text' => "⏰ หมดอายุ: {$expiresAt} น.",
                            'size' => 'sm',
                            'color' => '#E53E3E',
                            'margin' => 'lg',
                            'align' => 'center',
                        ],
                    ]
                ),
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => 'เมื่อโอนแล้วระบบจะตรวจสอบอัตโนมัติ',
                        'size' => 'xs',
                        'color' => '#888888',
                        'align' => 'center',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'และส่งคำทำนายให้ทันที ✨',
                        'size' => 'xs',
                        'color' => '#888888',
                        'align' => 'center',
                    ],
                    [
                        'type' => 'text',
                        'text' => '📌 บริการดิจิทัล — งดคืนเงินทุกกรณี',
                        'size' => 'xxs',
                        'color' => '#999999',
                        'align' => 'center',
                        'margin' => 'md',
                        'wrap' => true,
                    ],
                    [
                        'type' => 'text',
                        'text' => "💡 หากโอนแล้วระบบไม่แจ้งเตือน\nให้พิมพ์ว่า 'โอนแล้ว' ระบบจะส่งคำทำนายให้",
                        'size' => 'xs',
                        'color' => '#E65100',
                        'align' => 'center',
                        'margin' => 'md',
                        'wrap' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message สำหรับ Welcome / Help
     *
     * @return array Flex Message content
     */
    /**
     * @param  string|null  $lineUserId  LINE user ID — ถ้าระบุ จะตรวจ hasUsedFreeCard ซ่อนปุ่ม/การ์ดฟรีถ้าใช้แล้ว
     */
    public function buildWelcomeFlexMessage(string $userName = '', ?string $lineUserId = null): array
    {
        $userName = $this->sanitizeUserName($userName);
        $brandName = $this->settings->getFortuneBrandName();
        $primaryColor = $this->settings->getLineFlexPrimaryColor();

        // สร้างข้อความทักทาย — ถ้ามีชื่อจะใส่ชื่อด้วย
        $greeting = $userName
            ? "สวัสดี คุณ{$userName} ✨"
            : 'สวัสดี ✨';

        // ตรวจสอบว่าเปิดระบบดูดวงฟรีหรือไม่
        // ถ้า max_free_readings = 0 → ไม่แสดงการ์ด "ดูดวงพื้นฐาน (ฟรี)"
        $freeEnabled = $this->settings->isFreeReadingEnabled();
        // 🩹 (2026-05-04) ซ่อนการ์ดฟรี ถ้า user คนนี้ใช้สิทธิ์ไปแล้ว
        //    user request: "คนใช้สิทธิ์ฟรีไปแล้ว ไม่ต้องขึ้นดูฟรี + นำออกจากกล่องรายการ"
        if ($freeEnabled && $lineUserId) {
            try {
                if (\App\Models\FortuneReading::hasUsedFreeCard('line', $lineUserId)) {
                    $freeEnabled = false;
                }
            } catch (\Throwable $e) {
                // DB error → fall back behave เดิม (ไม่ block welcome)
            }
        }

        // สร้างรายการ service cards แบบมีเงื่อนไข
        $serviceCards = [
            [
                'type' => 'text',
                'text' => '📋 บริการของเรา',
                'weight' => 'bold',
                'size' => 'sm',
                'color' => $primaryColor,
            ],
        ];

        // การ์ด "ดูดวงพื้นฐาน (ฟรี)" — แสดงเฉพาะเมื่อระบบฟรีเปิดอยู่
        if ($freeEnabled) {
            $serviceCards[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'backgroundColor' => '#F0FFF4',
                'cornerRadius' => 'md',
                'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => '🆓', 'size' => 'lg', 'flex' => 0],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'flex' => 1,
                        'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => 'ดูดวงพื้นฐาน (ฟรี)', 'size' => 'sm', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => 'ทำนายเรื่องทั่วไป', 'size' => 'xs', 'color' => '#888888'],
                        ],
                    ],
                ],
            ];
        }

        // 🌐 (2026-07-24) ปุ่ม "ดูดวงฟรีบนเว็บ" — Magic Link (เฉพาะเมื่อเปิด enable_web_fortune_button)
        //   null = ไม่แสดงปุ่ม (สวิตช์ปิด/ไม่มี lineUserId/สร้างลิงก์ไม่ได้) → พฤติกรรมเดิม 100%
        $webFortuneButton = null;
        if ($lineUserId) {
            try {
                $webLinkService = app(\App\Services\FortuneWebLinkService::class);
                if ($webLinkService->isEnabled()) {
                    $webFortuneButton = [
                        'type' => 'button',
                        'style' => 'secondary',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'uri',
                            'label' => '🌐 ดูดวงฟรีบนเว็บ',
                            'uri' => $webLinkService->generateChatLink('line', $lineUserId),
                        ],
                    ];
                }
            } catch (\Throwable $e) {
                // สร้างลิงก์ไม่ได้ → ข้ามปุ่ม (ไม่กระทบ welcome)
            }
        }

        // การ์ด "ดูดวงละเอียด" — แสดงเสมอ
        $serviceCards[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'backgroundColor' => '#F8F7FF',
            'cornerRadius' => 'md',
            'paddingAll' => 'md',
            'contents' => [
                ['type' => 'text', 'text' => '💎', 'size' => 'lg', 'flex' => 0],
                [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'flex' => 1,
                    'paddingStart' => 'md',
                    'contents' => [
                        ['type' => 'text', 'text' => 'ดูดวง ('.number_format($this->getDeepReadingPrice(), 0).' บาท)', 'size' => 'sm', 'weight' => 'bold'],
                        ['type' => 'text', 'text' => 'ถาม '.FortuneConversationService::REQUIRED_QUESTIONS.' คำถาม พร้อมวันเกิด', 'size' => 'xs', 'color' => '#888888'],
                    ],
                ],
            ],
        ];

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => [
                'header' => [
                    'backgroundColor' => $primaryColor,
                ],
                'footer' => [
                    'backgroundColor' => '#F8F7FF',
                ],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🔮',
                        'size' => '3xl',
                        'align' => 'center',
                    ],
                    [
                        'type' => 'text',
                        'text' => "🔮 {$brandName}ยินดีต้อนรับ 🔮",
                        'color' => '#FFFFFF',
                        'size' => 'lg',
                        'weight' => 'bold',
                        'align' => 'center',
                        'margin' => 'md',
                    ],
                    [
                        'type' => 'text',
                        'text' => $greeting,
                        'color' => '#FFFFFFCC',
                        'size' => 'sm',
                        'align' => 'center',
                        'margin' => 'sm',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => "{$brandName}รับดูดวงเรื่องต่างๆ",
                        'wrap' => true,
                        'size' => 'md',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'lg',
                        'spacing' => 'sm',
                        'contents' => [
                            ['type' => 'text', 'text' => '💕 ความรัก คู่ครอง', 'size' => 'sm'],
                            ['type' => 'text', 'text' => '💼 การงาน อาชีพ', 'size' => 'sm'],
                            ['type' => 'text', 'text' => '💰 การเงิน โชคลาภ', 'size' => 'sm'],
                            ['type' => 'text', 'text' => '🏥 สุขภาพ', 'size' => 'sm'],
                        ],
                    ],
                    [
                        'type' => 'separator',
                        'margin' => 'xl',
                        'color' => '#E8E0FF',
                    ],
                    // บริการของเรา — ใช้ $serviceCards ที่ build มาตามเงื่อนไข isFreeReadingEnabled()
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'xl',
                        'spacing' => 'md',
                        'contents' => $serviceCards,
                    ],
                    [
                        'type' => 'separator',
                        'margin' => 'xl',
                        'color' => '#E8E0FF',
                    ],
                    [
                        'type' => 'text',
                        'text' => '💡 พิมพ์คำถามมาได้เลย เช่น',
                        'weight' => 'bold',
                        'size' => 'sm',
                        'margin' => 'xl',
                    ],
                    [
                        'type' => 'text',
                        'text' => '• ปีนี้จะมีคู่ครองไหม\n• ควรเปลี่ยนงานไหม\n• ดวงการเงินเป็นอย่างไร',
                        'wrap' => true,
                        'size' => 'sm',
                        'color' => '#666666',
                        'margin' => 'sm',
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'paddingAll' => 'lg',
                // 🌐 ปุ่มเว็บ (ถ้าเปิดสวิตช์) แทรกระหว่างปุ่มหลักกับปุ่มคำทำนายล่าสุด
                'contents' => array_values(array_filter([
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => $primaryColor,
                        'height' => 'sm',
                        'action' => [
                            'type' => 'message',
                            'label' => '🔮 เริ่มดูดวงเลย',
                            'text' => 'ดูดวง',
                        ],
                    ],
                    $webFortuneButton,
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'message',
                            'label' => '📖 คำทำนายล่าสุด',
                            'text' => 'ดูคำทำนายล่าสุด',
                        ],
                    ],
                ])),
            ],
        ];
    }

    /**
     * สร้าง Flex Message สำหรับคำทำนายละเอียด (Deep Reading) — แต่ละคำถาม
     *
     * ออกแบบเป็นการ์ดสวยๆ มี header สีตามหมวดคำถาม
     * มี icon, คำถาม, คำทำนาย แยกส่วนชัดเจน
     *
     * @param  int  $questionNum  ลำดับคำถาม (1, 2, 3)
     * @param  string  $question  คำถามของผู้ใช้
     * @param  string  $answer  คำทำนาย
     * @param  int  $totalQuestions  จำนวนคำถามทั้งหมด
     * @return array Flex Message bubble
     */
    public function buildDeepReadingFlexMessage(int $questionNum, string $question, string $answer, int $totalQuestions = 3): array
    {
        // กำหนดสี + icon ตามหมวดคำถาม
        $category = $this->detectQuestionCategory($question);
        $theme = $this->getCategoryTheme($category);

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => [
                'header' => ['backgroundColor' => $theme['color']],
                'footer' => ['backgroundColor' => '#F8F7FF'],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'lg',
                'contents' => [
                    // แถวบน: icon + หมวด
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => $theme['icon'],
                                'size' => 'xxl',
                                'flex' => 0,
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'vertical',
                                'flex' => 1,
                                'paddingStart' => 'md',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => "คำทำนายข้อที่ {$questionNum}/{$totalQuestions}",
                                        'color' => '#FFFFFF',
                                        'size' => 'md',
                                        'weight' => 'bold',
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $theme['label'],
                                        'color' => '#FFFFFFCC',
                                        'size' => 'sm',
                                        'margin' => 'xs',
                                    ],
                                ],
                            ],
                        ],
                        'alignItems' => 'center',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => [
                    // คำถาม
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'backgroundColor' => '#F3F0FF',
                        'cornerRadius' => 'lg',
                        'paddingAll' => 'lg',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '❓ คำถาม',
                                'size' => 'xs',
                                'color' => '#6B46C1',
                                'weight' => 'bold',
                            ],
                            [
                                'type' => 'text',
                                'text' => $question,
                                'wrap' => true,
                                'size' => 'md',
                                'color' => '#4A3880',
                                'margin' => 'sm',
                                'weight' => 'bold',
                            ],
                        ],
                    ],
                    // เส้นแบ่ง
                    [
                        'type' => 'separator',
                        'margin' => 'xl',
                        'color' => '#E8E0FF',
                    ],
                    // คำทำนาย
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'xl',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '🔮 คำทำนาย',
                                'size' => 'sm',
                                'color' => $theme['color'],
                                'weight' => 'bold',
                            ],
                            [
                                'type' => 'text',
                                'text' => $answer,
                                'wrap' => true,
                                'size' => 'md',
                                'color' => '#333333',
                                'margin' => 'md',
                                'lineSpacing' => '8px',
                            ],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'horizontal',
                'paddingAll' => 'md',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '✨ หมอจันทราดูดวง',
                        'size' => 'xs',
                        'color' => '#9B8EC4',
                        'flex' => 1,
                    ],
                    [
                        'type' => 'text',
                        'text' => "Q{$questionNum}/{$totalQuestions}",
                        'size' => 'xs',
                        'color' => '#9B8EC4',
                        'align' => 'end',
                        'flex' => 0,
                    ],
                ],
            ],
        ];
    }

    /**
     * ส่ง Flex คำทำนาย — แยก bubble อัตโนมัติถ้าข้อความยาวเกิน
     *
     * LINE Flex bubble มี JSON size limit ~50KB
     * ถ้าข้อความยาว → แยกเป็นหลาย bubble ใน carousel
     * ถ้า carousel ก็ใหญ่เกิน → fallback ส่งทีละ bubble
     *
     * @param  string  $userId  LINE user ID
     * @param  int  $questionNum  ลำดับคำถาม
     * @param  string  $question  คำถาม/หัวข้อ
     * @param  string  $answer  คำตอบจาก AI
     * @param  int  $totalQuestions  จำนวนคำถามทั้งหมด
     * @param  string  $altText  ข้อความ alt สำหรับ notification
     * @return bool ส่งสำเร็จหรือไม่
     */
    public function sendDeepReadingFlexSafe(string $userId, int $questionNum, string $question, string $answer, int $totalQuestions, string $altText = ''): bool
    {
        // กำหนด alt text
        if (empty($altText)) {
            $altText = "🔮 คำทำนายข้อ {$questionNum}/{$totalQuestions}";
        }

        // ตัดข้อความยาวมากกว่า 10000 ตัวอักษร (ป้องกัน AI ตอบยาวเกิน)
        $answer = mb_substr($answer, 0, 10000);

        // ถ้าข้อความสั้น → ใช้ bubble เดียวตามปกติ (เร็วที่สุด)
        $answerLen = mb_strlen($answer);
        if ($answerLen <= 1500) {
            $flex = $this->buildDeepReadingFlexMessage($questionNum, $question, $answer, $totalQuestions);
            $jsonSize = strlen(json_encode($flex, JSON_UNESCAPED_UNICODE));

            if ($jsonSize < 45000) {
                $sent = $this->sendRichMessagePriority($userId, [
                    'alt_text' => $altText,
                    'contents' => $flex,
                ]);
                if ($sent) {
                    return true;
                }
                Log::warning("LINE sendDeepReadingFlexSafe: single bubble ส่งไม่ได้ (json={$jsonSize}B) → fallback split", [
                    'user_id' => $userId, 'answer_len' => $answerLen,
                ]);
            }
        }

        // ข้อความยาว → แบ่งเป็น chunks → carousel
        $chunks = $this->splitTextForFlex($answer, 1200);

        if (count($chunks) === 1) {
            // มี chunk เดียว → ลอง bubble เดียว
            $flex = $this->buildDeepReadingFlexMessage($questionNum, $question, $chunks[0], $totalQuestions);
            $sent = $this->sendRichMessagePriority($userId, [
                'alt_text' => $altText,
                'contents' => $flex,
            ]);
            if ($sent) {
                return true;
            }
        }

        // หลาย chunks → สร้าง carousel
        $bubbles = [];
        foreach ($chunks as $i => $chunk) {
            $partLabel = count($chunks) > 1
                ? "{$question} (ต่อ ".($i + 1).'/'.count($chunks).')'
                : $question;
            $bubbles[] = $this->buildDeepReadingFlexMessage($questionNum, $partLabel, $chunk, $totalQuestions);
        }

        // ลอง carousel (max 12 bubbles)
        $bubbles = array_slice($bubbles, 0, 12);
        $carousel = [
            'type' => 'carousel',
            'contents' => $bubbles,
        ];

        $carouselJson = json_encode($carousel, JSON_UNESCAPED_UNICODE);
        if (strlen($carouselJson) < 45000) {
            $sent = $this->sendRichMessagePriority($userId, [
                'alt_text' => $altText,
                'contents' => $carousel,
            ]);
            if ($sent) {
                return true;
            }
        }

        // Carousel ใหญ่เกิน → ส่งทีละ bubble แยก
        Log::info('LINE sendDeepReadingFlexSafe: carousel ใหญ่เกิน → ส่งทีละ bubble ('.count($bubbles).' bubbles)', [
            'user_id' => $userId, 'carousel_size' => strlen($carouselJson ?? ''),
        ]);

        $anySent = false;
        foreach ($bubbles as $idx => $bubble) {
            $partAlt = $altText.' (ส่วนที่ '.($idx + 1).')';
            $sent = $this->sendRichMessagePriority($userId, [
                'alt_text' => $partAlt,
                'contents' => $bubble,
            ]);
            if ($sent) {
                $anySent = true;
            }
            if ($idx < count($bubbles) - 1) {
                usleep(500_000); // 0.5s ระหว่าง bubble
            }
        }

        if ($anySent) {
            return true;
        }

        // Flex ทุกวิธีล้มเหลว → fallback ส่ง text ธรรมดา (ตัดไม่เกิน 5000)
        Log::warning('LINE sendDeepReadingFlexSafe: Flex ทุกวิธีล้มเหลว → fallback text', [
            'user_id' => $userId, 'answer_len' => $answerLen,
        ]);
        $textFallback = "🔮 คำทำนายข้อที่ {$questionNum}/{$totalQuestions}\n❓ {$question}\n\n{$answer}";

        return $this->sendMessagePriority($userId, mb_substr($textFallback, 0, 5000));
    }

    /**
     * ส่ง Flex ด้วย pushMessagePriority (มี retry)
     *
     * @param  string  $recipientId  LINE user ID
     * @param  array  $richContent  ['alt_text' => ..., 'contents' => ...]
     */
    public function sendRichMessagePriority(string $recipientId, array $richContent): bool
    {
        $messages = [
            [
                'type' => 'flex',
                'altText' => mb_substr($richContent['alt_text'] ?? 'ข้อความจากระบบดูดวง', 0, 400),
                'contents' => $richContent['contents'] ?? $richContent,
            ],
        ];

        return $this->pushMessagePriority($recipientId, $messages);
    }

    /**
     * แบ่งข้อความยาวเป็น chunks สำหรับ Flex bubbles
     *
     * แบ่งตาม paragraph (\n\n) ก่อน ถ้ายาวเกินค่อย force split
     *
     * @param  string  $text  ข้อความที่จะแบ่ง
     * @param  int  $maxChars  จำนวนตัวอักษรสูงสุดต่อ chunk
     * @return array<string>
     */
    /**
     * Public wrapper สำหรับ splitTextForFlex — ใช้จาก FortuneChannelManager
     */
    public function splitTextForFlexPublic(string $text, int $maxChars = 1200): array
    {
        return $this->splitTextForFlex($text, $maxChars);
    }

    protected function splitTextForFlex(string $text, int $maxChars = 1200): array
    {
        $text = trim($text);
        if (mb_strlen($text) <= $maxChars) {
            return [$text];
        }

        // แบ่งตาม paragraph
        $paragraphs = preg_split('/\n\n+/', $text);
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                continue;
            }

            // ถ้า paragraph เดียวยาวเกิน → force split ตาม newline/sentence
            if (mb_strlen($para) > $maxChars) {
                if ($current !== '') {
                    $chunks[] = trim($current);
                    $current = '';
                }
                // แบ่ง paragraph ยาวตามบรรทัด
                $lines = explode("\n", $para);
                foreach ($lines as $line) {
                    if (mb_strlen($current) + mb_strlen($line) + 1 > $maxChars && $current !== '') {
                        $chunks[] = trim($current);
                        $current = '';
                    }
                    $current .= ($current !== '' ? "\n" : '').$line;
                }

                continue;
            }

            if (mb_strlen($current) + mb_strlen($para) + 2 > $maxChars && $current !== '') {
                $chunks[] = trim($current);
                $current = '';
            }
            $current .= ($current !== '' ? "\n\n" : '').$para;
        }

        if ($current !== '') {
            $chunks[] = trim($current);
        }

        return $chunks ?: [$text];
    }

    /**
     * สร้าง Flex Message สำหรับข้อความขอบคุณปิดท้ายคำทำนายละเอียด
     *
     * มีปุ่ม "ดูดวงอีกครั้ง" + "เช็คสิทธิ์" เพื่อกระตุ้น engagement
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @return array Flex Message bubble
     */
    public function buildThankYouFlexMessage(string $userName = 'คุณ'): array
    {
        $userName = $this->sanitizeUserName($userName);

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => [
                'header' => ['backgroundColor' => '#6B46C1'],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🙏',
                        'size' => '3xl',
                        'align' => 'center',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'ขอบคุณที่ไว้วางใจ',
                        'color' => '#FFFFFF',
                        'size' => 'lg',
                        'weight' => 'bold',
                        'align' => 'center',
                        'margin' => 'md',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => "คุณ{$userName} ✨",
                        'size' => 'md',
                        'weight' => 'bold',
                        'color' => '#6B46C1',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'ขอให้โชคดี มีสุขภาพแข็งแรง การงานการเงินเจริญรุ่งเรือง สมหวังทุกประการ ✨',
                        'wrap' => true,
                        'size' => 'md',
                        'color' => '#555555',
                        'margin' => 'md',
                        'lineSpacing' => '6px',
                    ],
                    [
                        'type' => 'separator',
                        'margin' => 'xl',
                        'color' => '#E8E0FF',
                    ],
                    // Section: ชวนเพื่อน / รับรายได้
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'xl',
                        'backgroundColor' => '#FFF8E1',
                        'cornerRadius' => 'lg',
                        'paddingAll' => 'lg',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '📢 ถ้าคำทำนายถูกใจ...',
                                'wrap' => true,
                                'size' => 'sm',
                                'weight' => 'bold',
                                'color' => '#E8890C',
                            ],
                            [
                                'type' => 'text',
                                'text' => 'ชวนเพื่อนมาดูดวง ได้ค่าแนะนำเข้า Wallet ทันที! กดปุ่มด้านล่างเพื่อดูรายละเอียด',
                                'wrap' => true,
                                'size' => 'xs',
                                'color' => '#888888',
                                'margin' => 'sm',
                            ],
                        ],
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'md',
                        'backgroundColor' => '#F8F7FF',
                        'cornerRadius' => 'lg',
                        'paddingAll' => 'md',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '💡 พิมพ์ "ดูคำทำนาย" เพื่อดูอีกครั้ง',
                                'wrap' => true,
                                'size' => 'xs',
                                'color' => '#6B46C1',
                            ],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'paddingAll' => 'lg',
                'contents' => [
                    // ปุ่มดูดวงอีกครั้ง (primary)
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#6B46C1',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'message',
                            'label' => '🔮 ดูดวงอีกครั้ง',
                            'text' => 'ดูดวง',
                        ],
                    ],
                    // ปุ่มดูวิธีรับรายได้ (ชวนเพื่อน — เปิด LINE LIFF/redirect ไปหน้า recruit)
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'uri',
                            'label' => '📢 ชวนเพื่อน/ดูรายได้',
                            'uri' => rtrim(config('app.url', 'https://main.thaiprompt.online'), '/').'/auth/line?redirect=/user/fortune-referral/recruit',
                        ],
                    ],
                    // ปุ่มแชร์ OA
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'uri',
                            'label' => '📤 แชร์ OA ให้เพื่อน',
                            'uri' => 'https://line.me/R/nv/recommendOA/'.($this->settings->line_bot_basic_id ?? config('services.line.bot_basic_id', '@002dqcls')),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message สำหรับเลือกหมวดคำถาม (ดูดวงละเอียด)
     *
     * แสดงปุ่มหมวดคำถามสวยๆ พร้อมไอคอนและสี
     * ผู้ใช้สามารถกดเลือกหมวดหรือพิมพ์เองได้
     *
     * @param  int  $questionNumber  คำถามข้อที่ (1, 2)
     * @param  int  $totalQuestions  จำนวนคำถามทั้งหมด
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  string|null  $previousQuestion  คำถามก่อนหน้า (ถ้ามี)
     * @return array Flex Message bubble
     */
    public function buildQuestionSelectionFlexMessage(int $questionNumber, int $totalQuestions, string $userName = 'คุณ', ?string $previousQuestion = null): array
    {
        $bodyContents = [];

        // ถ้าเป็นข้อที่ 2+ → แสดงว่ารับคำถามก่อนหน้าแล้ว
        if ($previousQuestion) {
            $bodyContents[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'backgroundColor' => '#E8F5E9',
                'cornerRadius' => 'lg',
                'paddingAll' => 'md',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '✅',
                        'size' => 'md',
                        'flex' => 0,
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'flex' => 1,
                        'paddingStart' => 'sm',
                        'justifyContent' => 'center',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => 'รับคำถามข้อที่ '.($questionNumber - 1).' แล้ว',
                                'size' => 'sm',
                                'color' => '#2E7D32',
                            ],
                        ],
                    ],
                ],
            ];
            $bodyContents[] = [
                'type' => 'separator',
                'margin' => 'lg',
                'color' => '#E8E0FF',
            ];
        }

        // ข้อความแนะนำ
        $bodyContents[] = [
            'type' => 'text',
            'text' => 'เลือกหมวดที่สนใจ หรือพิมพ์คำถามเองได้เลย 👇',
            'wrap' => true,
            'size' => 'sm',
            'color' => '#666666',
            'margin' => $previousQuestion ? 'lg' : 'none',
        ];

        // ปุ่มหมวดคำถาม — แถวที่ 1: ความรัก + การงาน
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'margin' => 'lg',
            'spacing' => 'md',
            'contents' => [
                $this->buildCategoryButton('💕', 'ความรัก คู่ครอง', '#E91E8C', 'ดูดวงความรัก'),
                $this->buildCategoryButton('💼', 'การงาน อาชีพ', '#1976D2', 'ดูดวงการงาน'),
            ],
        ];

        // ปุ่มหมวดคำถาม — แถวที่ 2: การเงิน + สุขภาพ
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'margin' => 'md',
            'spacing' => 'md',
            'contents' => [
                $this->buildCategoryButton('💰', 'การเงิน โชคลาภ', '#E8890C', 'ดูดวงการเงิน'),
                $this->buildCategoryButton('🏥', 'สุขภาพ', '#43A047', 'ดูดวงสุขภาพ'),
            ],
        ];

        // ปุ่มหมวดคำถาม — แถวที่ 3: ดวงรวม + การเรียน
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'margin' => 'md',
            'spacing' => 'md',
            'contents' => [
                $this->buildCategoryButton('🔮', 'ดวงชะตารวม', '#6B46C1', 'ดูดวงรวม'),
                $this->buildCategoryButton('📚', 'การเรียน สอบ', '#5D4037', 'ดูดวงการเรียน'),
            ],
        ];

        // ปุ่มหมวดคำถาม — แถวที่ 4: ครอบครัว + ธุรกิจ
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'margin' => 'md',
            'spacing' => 'md',
            'contents' => [
                $this->buildCategoryButton('👨‍👩‍👧‍👦', 'ครอบครัว', '#00897B', 'ดูดวงครอบครัว'),
                $this->buildCategoryButton('🏢', 'ธุรกิจ ลงทุน', '#FF6F00', 'ดูดวงธุรกิจ'),
            ],
        ];

        // ปุ่มหมวดคำถาม — แถวที่ 5: การเดินทาง + ฮวงจุ้ย/โชค
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'margin' => 'md',
            'spacing' => 'md',
            'contents' => [
                $this->buildCategoryButton('✈️', 'การเดินทาง', '#0288D1', 'ดูดวงการเดินทาง'),
                $this->buildCategoryButton('🍀', 'โชคลาภ เลขเด็ด', '#2E7D32', 'ดูดวงโชคลาภ'),
            ],
        ];

        // คำแนะนำเพิ่มเติม
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'vertical',
            'margin' => 'xl',
            'backgroundColor' => '#F8F7FF',
            'cornerRadius' => 'lg',
            'paddingAll' => 'md',
            'contents' => [
                [
                    'type' => 'text',
                    'text' => '💡 หรือพิมพ์คำถามเองได้ เช่น',
                    'size' => 'xs',
                    'color' => '#6B46C1',
                ],
                [
                    'type' => 'text',
                    'text' => '"ปีนี้จะมีแฟนไหม" "ควรเปลี่ยนงานดีไหม" "เปิดร้านอาหารดีไหม"',
                    'size' => 'xs',
                    'color' => '#999999',
                    'wrap' => true,
                    'margin' => 'sm',
                ],
            ],
        ];

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => [
                'header' => ['backgroundColor' => '#6B46C1'],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'lg',
                'contents' => [
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '📝',
                                'size' => 'xxl',
                                'flex' => 0,
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'vertical',
                                'flex' => 1,
                                'paddingStart' => 'md',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => "คำถามข้อที่ {$questionNumber} จาก {$totalQuestions}",
                                        'color' => '#FFFFFF',
                                        'size' => 'lg',
                                        'weight' => 'bold',
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => "คุณ{$userName} อยากถามเรื่องอะไรคะ?",
                                        'color' => '#FFFFFFCC',
                                        'size' => 'sm',
                                        'margin' => 'xs',
                                    ],
                                ],
                            ],
                        ],
                        'alignItems' => 'center',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => $bodyContents,
            ],
        ];
    }

    /**
     * สร้างปุ่มหมวดคำถาม (ใช้ภายใน buildQuestionSelectionFlexMessage)
     *
     * @param  string  $icon  Emoji ไอคอน
     * @param  string  $label  ชื่อหมวด
     * @param  string  $color  สี hex
     * @param  string  $text  ข้อความที่จะส่งเมื่อกด
     * @return array Flex Message button component
     */
    protected function buildCategoryButton(string $icon, string $label, string $color, string $text): array
    {
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'flex' => 1,
            'backgroundColor' => $color.'15',  // สีจาง 15% opacity
            'cornerRadius' => 'xl',
            'paddingAll' => 'md',
            'action' => [
                'type' => 'message',
                'label' => "{$icon} {$label}",
                'text' => $text,
            ],
            'contents' => [
                [
                    'type' => 'text',
                    'text' => $icon,
                    'size' => 'xl',
                    'align' => 'center',
                ],
                [
                    'type' => 'text',
                    'text' => $label,
                    'size' => 'xs',
                    'align' => 'center',
                    'color' => $color,
                    'weight' => 'bold',
                    'margin' => 'sm',
                ],
            ],
        ];
    }

    /**
     * ตรวจจับหมวดคำถาม จาก keyword ในคำถาม
     *
     * @param  string  $question  คำถามของผู้ใช้
     * @return string หมวดคำถาม (love, work, money, health, general)
     */
    protected function detectQuestionCategory(string $question): string
    {
        $question = mb_strtolower($question);

        $categories = [
            'love' => ['รัก', 'คู่', 'แฟน', 'สามี', 'ภรรยา', 'แต่งงาน', 'หย่า', 'ความสัมพันธ์', 'คนรัก', 'เนื้อคู่', 'คู่ครอง', 'จีบ'],
            'work' => ['งาน', 'อาชีพ', 'เปลี่ยนงาน', 'เลื่อนตำแหน่ง', 'ธุรกิจ', 'ค้าขาย', 'หุ้นส่วน', 'ลาออก', 'สัมภาษณ์', 'เจ้านาย'],
            'money' => ['เงิน', 'การเงิน', 'โชค', 'ลาภ', 'หวย', 'หนี้', 'ร่ำรวย', 'รายได้', 'ลงทุน', 'กำไร', 'เศรษฐี', 'ทรัพย์'],
            'health' => ['สุขภาพ', 'ป่วย', 'โรค', 'หมอ', 'ผ่าตัด', 'อุบัติเหตุ', 'ร่างกาย', 'แข็งแรง'],
        ];

        foreach ($categories as $cat => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($question, $kw) !== false) {
                    return $cat;
                }
            }
        }

        return 'general';
    }

    /**
     * ดึง theme สี + icon ตามหมวดคำถาม
     *
     * @param  string  $category  หมวดคำถาม
     * @return array ['color', 'icon', 'label']
     */
    protected function getCategoryTheme(string $category): array
    {
        return match ($category) {
            'love' => [
                'color' => '#E91E8C',
                'icon' => '💕',
                'label' => 'ความรัก คู่ครอง',
            ],
            'work' => [
                'color' => '#1976D2',
                'icon' => '💼',
                'label' => 'การงาน อาชีพ',
            ],
            'money' => [
                'color' => '#E8890C',
                'icon' => '💰',
                'label' => 'การเงิน โชคลาภ',
            ],
            'health' => [
                'color' => '#43A047',
                'icon' => '🏥',
                'label' => 'สุขภาพ',
            ],
            default => [
                'color' => '#6B46C1',
                'icon' => '🔮',
                'label' => 'คำทำนาย',
            ],
        };
    }

    // ============================================================
    // 🆕 Flex Message Templates — ข้อความสวยงามทุกจุด
    // ============================================================

    /**
     * สร้าง Flex Message ยืนยันดูดวง (awaiting_confirmation)
     *
     * แสดงสิทธิ์ฟรีที่เหลือ + ราคาดูดวงละเอียด + ปุ่มเลือก
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  int  $remaining  จำนวนสิทธิ์ฟรีที่เหลือ
     * @param  float  $deepReadingPrice  ราคาดูดวงละเอียด
     * @param  bool  $deepReadingEnabled  เปิดดูดวงละเอียดหรือไม่
     * @param  bool  $isUnlimited  มีสิทธิ์ไม่จำกัดหรือไม่
     * @return array Flex Message bubble
     */
    public function buildConfirmationFlexMessage(string $userName, int $remaining, float $deepReadingPrice, bool $deepReadingEnabled = true, bool $isUnlimited = false): array
    {
        $userName = $this->sanitizeUserName($userName);
        // แสดงสิทธิ์ฟรี
        $creditText = $isUnlimited || $remaining >= 99
            ? '✨ ไม่จำกัด ✨'
            : ($remaining > 0 ? "{$remaining} ครั้ง" : 'หมดแล้ว');
        $creditColor = $remaining > 0 || $isUnlimited ? '#43A047' : '#E53935';

        // greeting — ถ้าไม่มีชื่อ ใช้ "สวัสดี ✨" (กันห้อย "คุณ ✨")
        $greetingText = $userName !== '' ? "สวัสดี คุณ{$userName} ✨" : 'สวัสดี ✨';
        $bodyContents = [
            // สวัสดี
            [
                'type' => 'text',
                'text' => $greetingText,
                'size' => 'md',
                'weight' => 'bold',
                'color' => '#333333',
            ],
            [
                'type' => 'text',
                'text' => 'หมอจันทราพร้อมดูดวงให้',
                'size' => 'sm',
                'color' => '#999999',
                'margin' => 'sm',
            ],
        ];

        $freeEnabled = $this->settings->isFreeReadingEnabled();

        // สิทธิ์ฟรีวันนี้ — แสดงเฉพาะเมื่อเปิดบริการฟรี
        if ($freeEnabled) {
            $bodyContents[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'margin' => 'xl',
                'backgroundColor' => $remaining > 0 || $isUnlimited ? '#E8F5E9' : '#FFEBEE',
                'cornerRadius' => 'lg',
                'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => '📊 สิทธิ์ฟรีวันนี้:', 'size' => 'sm', 'flex' => 3, 'color' => '#555555'],
                    ['type' => 'text', 'text' => $creditText, 'size' => 'sm', 'weight' => 'bold', 'color' => $creditColor, 'flex' => 2, 'align' => 'end'],
                ],
            ];
            $bodyContents[] = ['type' => 'separator', 'margin' => 'xl', 'color' => '#E8E0FF'];
        }

        // หัวข้อบริการ
        $bodyContents[] = [
            'type' => 'text',
            'text' => '🎁 บริการของหมอจันทรา',
            'size' => 'md',
            'weight' => 'bold',
            'color' => '#6B46C1',
            'margin' => 'xl',
        ];

        // บริการฟรี — แสดงเฉพาะเมื่อเปิดบริการฟรี
        if ($freeEnabled) {
            $bodyContents[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'margin' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🆓', 'size' => 'lg', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => 'ดูดวงพื้นฐาน (ฟรี)', 'size' => 'sm', 'weight' => 'bold', 'color' => '#333333'],
                            ['type' => 'text', 'text' => 'ทำนายเรื่องทั่วไปแบบสั้นๆ', 'size' => 'xs', 'color' => '#999999'],
                        ],
                    ],
                ],
            ];
        }

        // บริการเสียเงิน (ถ้าเปิด)
        if ($deepReadingEnabled) {
            $priceDisplay = number_format($deepReadingPrice, 0);
            $bodyContents[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'margin' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '💎', 'size' => 'lg', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => "ดูดวง (ค่าครู {$priceDisplay} บาท)", 'size' => 'sm', 'weight' => 'bold', 'color' => '#E8890C'],
                            ['type' => 'text', 'text' => '1 คำถาม + ดาวเจ้าชนะ + ไพ่ยิปซีจริง', 'size' => 'xs', 'color' => '#999999'],
                        ],
                    ],
                ],
            ];
        }

        // ปุ่ม footer
        $footerContents = [];
        if ($remaining > 0 || $isUnlimited) {
            $footerContents[] = [
                'type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm',
                'action' => ['type' => 'message', 'label' => '🔮 ดูดวงเลย', 'text' => 'ดู'],
            ];
        }
        if ($deepReadingEnabled) {
            $footerContents[] = [
                'type' => 'button', 'style' => 'secondary', 'height' => 'sm',
                'action' => ['type' => 'message', 'label' => '💎 ดูดวง', 'text' => 'ดูดวง'],
            ];
        }
        if (empty($footerContents)) {
            $footerContents[] = [
                'type' => 'button', 'style' => 'secondary', 'height' => 'sm',
                'action' => ['type' => 'message', 'label' => '🔮 ดูดวง', 'text' => 'ดูดวง'],
            ];
        }

        // ปุ่มดูคำทำนายย้อนหลัง
        $footerContents[] = [
            'type' => 'button', 'style' => 'secondary', 'height' => 'sm',
            'action' => ['type' => 'message', 'label' => '📖 ย้อนหลัง', 'text' => 'ดูคำทำนายล่าสุด'],
        ];

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => ['header' => ['backgroundColor' => '#6B46C1']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🔮', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => $this->settings->getFortuneBrandName(), 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => 'ยินดีต้อนรับ ✨', 'color' => '#FFFFFFCC', 'size' => 'sm'],
                        ],
                    ],
                ],
            ],
            'body' => ['type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl', 'contents' => $bodyContents],
            'footer' => ['type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg', 'contents' => $footerContents],
        ];
    }

    /**
     * สร้าง Flex Message ขอวันเกิด (collecting_birthdate)
     *
     * @param  float  $deepReadingPrice  ราคาดูดวงละเอียด
     * @return array Flex Message bubble
     */
    public function buildBirthdateRequestFlexMessage(float $deepReadingPrice): array
    {
        $priceDisplay = number_format($deepReadingPrice, 0);

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => ['header' => ['backgroundColor' => '#7C3AED']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🎂', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => 'ดูดวง', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => "{$priceDisplay} บาท • 1 คำถามโฟกัสเดียว", 'color' => '#FFFFFFCC', 'size' => 'xs'],
                        ],
                    ],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'กรุณาบอกวันเดือนปีเกิด', 'size' => 'md', 'weight' => 'bold', 'color' => '#333333'],
                    ['type' => 'text', 'text' => 'เพื่อวิเคราะห์ดวงชะตาได้แม่นยำ ✨', 'size' => 'sm', 'color' => '#999999', 'margin' => 'sm'],
                    ['type' => 'separator', 'margin' => 'xl', 'color' => '#E8E0FF'],
                    // รูปแบบที่รับ
                    [
                        'type' => 'box', 'layout' => 'vertical', 'margin' => 'xl', 'backgroundColor' => '#F8F7FF', 'cornerRadius' => 'lg', 'paddingAll' => 'lg',
                        'contents' => [
                            ['type' => 'text', 'text' => '📅 รูปแบบที่รับ', 'size' => 'sm', 'weight' => 'bold', 'color' => '#6B46C1'],
                            ['type' => 'text', 'text' => '• 15/08/1990', 'size' => 'sm', 'color' => '#555555', 'margin' => 'md'],
                            ['type' => 'text', 'text' => '• 15/08/2533', 'size' => 'sm', 'color' => '#555555', 'margin' => 'sm'],
                            ['type' => 'text', 'text' => '• 15 สิงหาคม 2533', 'size' => 'sm', 'color' => '#555555', 'margin' => 'sm'],
                        ],
                    ],
                    // ราคา + สิ่งที่ได้
                    [
                        'type' => 'box', 'layout' => 'vertical', 'margin' => 'lg', 'backgroundColor' => '#FFF8E1', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => "💎 สิ่งที่คุณจะได้รับ ({$priceDisplay} บาท)", 'size' => 'xs', 'weight' => 'bold', 'color' => '#E8890C'],
                            ['type' => 'text', 'text' => '✅ 1 คำถามโฟกัสเดียว — แม่นยำกว่า', 'size' => 'xs', 'color' => '#666666', 'margin' => 'sm', 'wrap' => true],
                            ['type' => 'text', 'text' => '✅ ดาวเจ้าชนะ + ไพ่ยิปซีจริง (ไม่ยกเมฆ)', 'size' => 'xs', 'color' => '#666666', 'margin' => 'sm', 'wrap' => true],
                            ['type' => 'text', 'text' => '✅ สีมงคล เลขมงคล ฤกษ์ดี', 'size' => 'xs', 'color' => '#666666', 'margin' => 'sm'],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '❌ ยกเลิก', 'text' => 'ยกเลิก']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message วันเกิดผิดรูปแบบ (invalid_birthdate)
     *
     * @return array Flex Message bubble
     */
    public function buildInvalidBirthdateFlexMessage(): array
    {
        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#E53935']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '⚠️', 'size' => 'xl', 'flex' => 0],
                    ['type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md', 'justifyContent' => 'center', 'contents' => [['type' => 'text', 'text' => 'รูปแบบวันเกิดไม่ถูกต้อง', 'color' => '#FFFFFF', 'size' => 'md', 'weight' => 'bold']]],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'กรุณาพิมพ์วันเกิดใหม่', 'size' => 'md', 'color' => '#333333'],
                    ['type' => 'separator', 'margin' => 'lg', 'color' => '#FFCDD2'],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'margin' => 'lg', 'backgroundColor' => '#FFF3E0', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '📅 ตัวอย่างที่ถูกต้อง', 'size' => 'sm', 'weight' => 'bold', 'color' => '#E65100'],
                            ['type' => 'text', 'text' => '• 15/08/1990', 'size' => 'sm', 'color' => '#555555', 'margin' => 'md'],
                            ['type' => 'text', 'text' => '• 15/08/2533', 'size' => 'sm', 'color' => '#555555', 'margin' => 'sm'],
                            ['type' => 'text', 'text' => '• 15 สิงหาคม 2533', 'size' => 'sm', 'color' => '#555555', 'margin' => 'sm'],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '❌ ยกเลิก', 'text' => 'ยกเลิก']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message หมดสิทธิ์ฟรี (ai_limit)
     *
     * @param  float  $deepReadingPrice  ราคาดูดวงละเอียด
     * @param  bool  $deepReadingEnabled  เปิดดูดวงละเอียดหรือไม่
     * @return array Flex Message bubble
     */
    public function buildAiLimitFlexMessage(float $deepReadingPrice, bool $deepReadingEnabled = true): array
    {
        $priceDisplay = number_format($deepReadingPrice, 0);
        $freeEnabled = $this->settings->isFreeReadingEnabled();

        // 🆕 (2026-05-03 audit fix #7) แทน "ฟรีวันละ X คำถาม" → "ทำนายฟรี 1 ใบ/platform"
        //    ระบบใหม่ไม่ใช่ daily quota แต่เป็น 1 ใบ/ตลอดชีวิต
        $brandName = $this->settings->getFortuneBrandName();
        $headerText = $freeEnabled ? 'สิทธิ์ทำนายฟรีถูกใช้แล้ว' : "ดูดวงโดย{$brandName}";
        $subText = $freeEnabled
            ? 'ทำนายฟรี 1 ใบ/ท่าน (ใช้แล้ว)'
            : "ค่าครู {$priceDisplay} บาท/ครั้ง";

        $bodyContents = [
            [
                'type' => 'box', 'layout' => 'horizontal', 'backgroundColor' => '#FFEBEE', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => $freeEnabled ? '⏰' : '💎', 'size' => 'lg', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => $headerText, 'size' => 'sm', 'weight' => 'bold', 'color' => '#C62828'],
                            ['type' => 'text', 'text' => $subText, 'size' => 'xs', 'color' => '#999999'],
                        ],
                    ],
                ],
            ],
        ];

        if ($deepReadingEnabled) {
            $bodyContents[] = ['type' => 'separator', 'margin' => 'xl', 'color' => '#E8E0FF'];
            $bodyContents[] = [
                'type' => 'text', 'text' => '💎 แนะนำ: ดูดวงเชิงลึก', 'size' => 'md', 'weight' => 'bold', 'color' => '#E8890C', 'margin' => 'xl',
            ];
            $bodyContents[] = [
                'type' => 'box', 'layout' => 'vertical', 'margin' => 'lg', 'backgroundColor' => '#FFF8E1', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => "ค่าครู {$priceDisplay} บาท", 'size' => 'lg', 'weight' => 'bold', 'color' => '#E8890C', 'align' => 'center'],
                    ['type' => 'separator', 'margin' => 'md', 'color' => '#FFE082'],
                    ['type' => 'text', 'text' => '📌 1 คำถามโฟกัสเดียว — แม่นยำกว่า', 'size' => 'sm', 'color' => '#555555', 'margin' => 'md', 'wrap' => true],
                    ['type' => 'text', 'text' => '📌 ดาวเจ้าชนะของเจ้าชะตา + ไพ่ยิปซีจริง', 'size' => 'sm', 'color' => '#555555', 'margin' => 'sm', 'wrap' => true],
                    ['type' => 'text', 'text' => '📌 สีมงคล เลขมงคล ฤกษ์ดี', 'size' => 'sm', 'color' => '#555555', 'margin' => 'sm'],
                ],
            ];
        }

        $footerContents = [];
        if ($deepReadingEnabled) {
            $footerContents[] = [
                'type' => 'button', 'style' => 'primary', 'color' => '#E8890C', 'height' => 'sm',
                'action' => ['type' => 'message', 'label' => "💎 ดูดวง ค่าครู {$priceDisplay} บาท", 'text' => 'ดูดวง'],
            ];
        }

        return [
            'type' => 'bubble', 'size' => 'mega',
            'styles' => ['header' => ['backgroundColor' => '#6B46C1']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🔮', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => $this->settings->getFortuneBrandName(), 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => 'ยินดีต้อนรับ ✨', 'color' => '#FFFFFFCC', 'size' => 'sm'],
                        ],
                    ],
                ],
            ],
            'body' => ['type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl', 'contents' => $bodyContents],
            'footer' => ['type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg', 'contents' => $footerContents],
        ];
    }

    /**
     * สร้าง Flex Message เช็คสิทธิ์ (check_remaining)
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  int  $remaining  สิทธิ์ฟรีที่เหลือ
     * @param  int  $used  ใช้ไปแล้ว
     * @param  int  $total  สิทธิ์ทั้งหมด
     * @param  float  $deepReadingPrice  ราคาดูดวงละเอียด
     * @param  bool  $deepReadingEnabled  เปิดดูดวงละเอียดหรือไม่
     * @param  bool  $isUnlimited  มีสิทธิ์ไม่จำกัดหรือไม่
     * @return array Flex Message bubble
     */
    public function buildCheckRemainingFlexMessage(string $userName, int $remaining, int $used, int $total, float $deepReadingPrice, bool $deepReadingEnabled = true, bool $isUnlimited = false, float $walletBalance = 0, float $totalCommission = 0): array
    {
        $userName = $this->sanitizeUserName($userName);

        $creditText = $isUnlimited || $remaining >= 99 ? '✨ ไม่จำกัด ✨' : "{$remaining} ครั้ง";
        $statusColor = $remaining > 0 || $isUnlimited ? '#43A047' : '#E53935';
        $priceDisplay = number_format($deepReadingPrice, 0);
        $freeEnabled = $this->settings->isFreeReadingEnabled();

        $bodyContents = [
            ['type' => 'text', 'text' => "คุณ{$userName}", 'size' => 'md', 'weight' => 'bold', 'color' => '#333333'],
        ];

        // สิทธิ์คงเหลือ — แสดงเฉพาะเมื่อเปิดบริการฟรี
        if ($freeEnabled) {
            $bodyContents[] = [
                'type' => 'box', 'layout' => 'vertical', 'margin' => 'xl', 'backgroundColor' => '#F8F7FF', 'cornerRadius' => 'lg', 'paddingAll' => 'lg',
                'contents' => [
                    [
                        'type' => 'box', 'layout' => 'horizontal',
                        'contents' => [
                            ['type' => 'text', 'text' => '📊 ใช้ไปแล้ว:', 'size' => 'sm', 'flex' => 3, 'color' => '#555555'],
                            ['type' => 'text', 'text' => $isUnlimited ? '-' : "{$used}/{$total} ครั้ง", 'size' => 'sm', 'weight' => 'bold', 'color' => '#333333', 'flex' => 2, 'align' => 'end'],
                        ],
                    ],
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '🆓 สิทธิ์ฟรีคงเหลือ:', 'size' => 'sm', 'flex' => 3, 'color' => '#555555'],
                            ['type' => 'text', 'text' => $creditText, 'size' => 'sm', 'weight' => 'bold', 'color' => $statusColor, 'flex' => 2, 'align' => 'end'],
                        ],
                    ],
                ],
            ];
        } else {
            // ปิดบริการฟรี → แสดง info card ชี้ไปที่ดูดวงเสียค่าครู
            $bodyContents[] = [
                'type' => 'box', 'layout' => 'vertical', 'margin' => 'xl', 'backgroundColor' => '#FFF8E1', 'cornerRadius' => 'lg', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '💎 บริการดูดวงโดย'.$this->settings->getFortuneBrandName(), 'size' => 'sm', 'weight' => 'bold', 'color' => '#E8890C'],
                    ['type' => 'text', 'text' => "ค่าครู {$priceDisplay} บาท/ครั้ง", 'size' => 'xs', 'color' => '#999999', 'margin' => 'sm'],
                ],
            ];
        }

        // แนะนำดูดวงละเอียด (ถ้าเปิด)
        if ($deepReadingEnabled) {
            $bodyContents[] = ['type' => 'separator', 'margin' => 'xl', 'color' => '#E8E0FF'];
            $bodyContents[] = [
                'type' => 'box', 'layout' => 'horizontal', 'margin' => 'xl', 'backgroundColor' => '#FFF8E1', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => '💎', 'size' => 'lg', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => "ดูดวง ค่าครู {$priceDisplay} บาท", 'size' => 'sm', 'weight' => 'bold', 'color' => '#E8890C'],
                            ['type' => 'text', 'text' => '1 คำถาม + ดาวเจ้าชนะ + ไพ่ยิปซีที่จิตเลือก', 'size' => 'xs', 'color' => '#999999', 'wrap' => true],
                        ],
                    ],
                ],
            ];
        }

        // ✅ แสดง Wallet + รายได้ค่าคอม (ถ้ามี)
        if ($walletBalance > 0 || $totalCommission > 0) {
            $walletDisplay = number_format($walletBalance, 2);
            $commDisplay = number_format($totalCommission, 2);
            $bodyContents[] = ['type' => 'separator', 'margin' => 'xl', 'color' => '#E8E0FF'];
            $bodyContents[] = [
                'type' => 'box', 'layout' => 'vertical', 'margin' => 'xl', 'backgroundColor' => '#F0FFF4', 'cornerRadius' => 'lg', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '💰 Wallet & รายได้', 'size' => 'sm', 'weight' => 'bold', 'color' => '#2E7D32'],
                    ['type' => 'separator', 'margin' => 'md', 'color' => '#C8E6C9'],
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '💰 ยอดใน Wallet:', 'size' => 'sm', 'flex' => 3, 'color' => '#555555'],
                            ['type' => 'text', 'text' => "฿{$walletDisplay}", 'size' => 'sm', 'weight' => 'bold', 'color' => '#06C755', 'flex' => 2, 'align' => 'end'],
                        ],
                    ],
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'sm',
                        'contents' => [
                            ['type' => 'text', 'text' => '📈 รายได้ค่าคอมรวม:', 'size' => 'sm', 'flex' => 3, 'color' => '#555555'],
                            ['type' => 'text', 'text' => "฿{$commDisplay}", 'size' => 'sm', 'weight' => 'bold', 'color' => '#FF8F00', 'flex' => 2, 'align' => 'end'],
                        ],
                    ],
                ],
            ];
        }

        $appUrl = config('app.url', 'https://main.thaiprompt.online');

        $footerContents = [
            ['type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูดวง', 'text' => 'ดูดวง']],
        ];
        if ($deepReadingEnabled) {
            $footerContents[] = ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => "💎 ดูดวง {$priceDisplay}.-", 'text' => 'ดูดวง']];
        }

        // ปุ่ม Wallet + คำทำนายล่าสุด + แชร์
        $footerContents[] = [
            'type' => 'box', 'layout' => 'horizontal', 'spacing' => 'sm',
            'contents' => [
                ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'uri', 'label' => '💰 Wallet', 'uri' => $appUrl.'/auth/line?redirect=/user/wallet']],
                ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '📖 คำทำนายล่าสุด', 'text' => 'ดูคำทำนายล่าสุด']],
            ],
        ];

        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#1976D2']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '📊', 'size' => 'xxl', 'flex' => 0],
                    ['type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md', 'justifyContent' => 'center', 'contents' => [['type' => 'text', 'text' => 'สิทธิ์ / Wallet', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold']]],
                ],
            ],
            'body' => ['type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl', 'contents' => $bodyContents],
            'footer' => ['type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg', 'contents' => $footerContents],
        ];
    }

    /**
     * สร้าง Flex Message ถามหัวข้อดูดวง (เมื่อผู้ใช้พิมพ์ "ดูดวง" เฉยๆ)
     *
     * แสดง topic buttons ให้เลือก: ความรัก, การงาน, การเงิน, ดวงรวม
     * หรือพิมพ์คำถามเอง
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  int  $remaining  สิทธิ์ฟรีคงเหลือ
     * @param  bool  $isUnlimited  สิทธิ์ไม่จำกัด
     * @return array Flex Message bubble
     */
    public function buildQuestionTopicFlexMessage(string $userName, int $remaining, bool $isUnlimited = false): array
    {
        $userName = $this->sanitizeUserName($userName);

        $creditText = $isUnlimited || $remaining >= 99
            ? '✨ ไม่จำกัด ✨'
            : "{$remaining} ครั้ง";
        $creditColor = $remaining > 0 || $isUnlimited ? '#43A047' : '#E53935';

        // greeting — ถ้าไม่มีชื่อ ใช้ "สวัสดี ✨" (กันห้อย "คุณ ✨")
        $greetingText = $userName !== '' ? "สวัสดี คุณ{$userName} ✨" : 'สวัสดี ✨';
        $bodyContents = [
            // ทักทาย
            [
                'type' => 'text',
                'text' => $greetingText,
                'size' => 'md',
                'weight' => 'bold',
                'color' => '#333333',
            ],
            [
                'type' => 'text',
                'text' => 'หมอจันทราพร้อมทำนายให้แล้ว',
                'size' => 'sm',
                'color' => '#999999',
                'margin' => 'sm',
            ],
            // สิทธิ์ฟรี
            [
                'type' => 'box',
                'layout' => 'horizontal',
                'margin' => 'xl',
                'backgroundColor' => $remaining > 0 || $isUnlimited ? '#E8F5E9' : '#FFEBEE',
                'cornerRadius' => 'lg',
                'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => '🆓 สิทธิ์ฟรีวันนี้:', 'size' => 'sm', 'flex' => 3, 'color' => '#555555'],
                    ['type' => 'text', 'text' => $creditText, 'size' => 'sm', 'weight' => 'bold', 'color' => $creditColor, 'flex' => 2, 'align' => 'end'],
                ],
            ],
            // เส้นแบ่ง
            ['type' => 'separator', 'margin' => 'xl', 'color' => '#E8E0FF'],
            // คำถาม
            [
                'type' => 'text',
                'text' => '📝 อยากถามเรื่องอะไรคะ?',
                'size' => 'lg',
                'weight' => 'bold',
                'color' => '#6B46C1',
                'margin' => 'xl',
            ],
            [
                'type' => 'text',
                'text' => 'เลือกหัวข้อด้านล่าง หรือพิมพ์คำถามเองได้เลยค่ะ',
                'size' => 'xs',
                'color' => '#999999',
                'margin' => 'sm',
                'wrap' => true,
            ],
        ];

        // ปุ่มเลือกหัวข้อ 2x2
        $footerContents = [
            // แถวที่ 1: ความรัก + การงาน
            [
                'type' => 'box',
                'layout' => 'horizontal',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#E91E63',
                        'height' => 'sm',
                        'action' => ['type' => 'message', 'label' => '💕 ความรัก', 'text' => 'ดูดวงความรัก'],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#1565C0',
                        'height' => 'sm',
                        'action' => ['type' => 'message', 'label' => '💼 การงาน', 'text' => 'ดูดวงการงาน'],
                    ],
                ],
            ],
            // แถวที่ 2: การเงิน + ดวงรวม
            [
                'type' => 'box',
                'layout' => 'horizontal',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#F57C00',
                        'height' => 'sm',
                        'action' => ['type' => 'message', 'label' => '💰 การเงิน', 'text' => 'ดูดวงการเงิน'],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#6B46C1',
                        'height' => 'sm',
                        'action' => ['type' => 'message', 'label' => '🌟 ดวงรวม', 'text' => 'ดูดวงรวมทุกด้าน'],
                    ],
                ],
            ],
            // แถวที่ 3: สุขภาพ
            [
                'type' => 'button',
                'style' => 'secondary',
                'height' => 'sm',
                'action' => ['type' => 'message', 'label' => '🏥 สุขภาพ', 'text' => 'ดูดวงสุขภาพ'],
            ],
        ];

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => ['header' => ['backgroundColor' => '#6B46C1']],
            'header' => [
                'type' => 'box',
                'layout' => 'horizontal',
                'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🔮', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'flex' => 1,
                        'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => $this->settings->getFortuneBrandName(), 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => 'เลือกเรื่องที่อยากถามค่ะ 🌙', 'color' => '#FFFFFFCC', 'size' => 'sm'],
                        ],
                    ],
                ],
            ],
            'body' => ['type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl', 'contents' => $bodyContents],
            'footer' => ['type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg', 'contents' => $footerContents],
        ];
    }

    /**
     * สร้าง Flex Message แสดงสถานะ/สิทธิ์ (check_status)
     *
     * แสดงข้อมูลรวม: สิทธิ์ฟรี, เครดิตพิเศษ, สถานะสมาชิก
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  int  $remaining  สิทธิ์ฟรีคงเหลือ
     * @param  int  $used  จำนวนที่ใช้ไปแล้ว
     * @param  int  $total  จำนวนสิทธิ์ทั้งหมดต่อวัน
     * @param  int  $specialCredits  เครดิตพิเศษจาก admin
     * @param  bool  $isUnlimited  สิทธิ์ไม่จำกัด
     * @param  string|null  $memberStatus  สถานะสมาชิก (ถ้ามี)
     * @return array Flex Message bubble
     */
    public function buildStatusFlexMessage(
        string $userName,
        int $remaining,
        int $used,
        int $total,
        int $specialCredits = 0,
        bool $isUnlimited = false,
        ?string $memberStatus = null,
        float $walletBalance = 0,
        float $totalCommission = 0,
    ): array {
        $creditText = $isUnlimited || $remaining >= 99
            ? '✨ ไม่จำกัด ✨'
            : "{$remaining} ครั้ง";
        $statusColor = $remaining > 0 || $isUnlimited ? '#43A047' : '#E53935';

        $bodyContents = [
            // ชื่อผู้ใช้
            [
                'type' => 'text',
                'text' => "คุณ{$userName}",
                'size' => 'lg',
                'weight' => 'bold',
                'color' => '#333333',
            ],
        ];

        // กล่องสถานะสมาชิก (ถ้ามี)
        if ($memberStatus) {
            $bodyContents[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'margin' => 'lg',
                'backgroundColor' => '#E8F5E9',
                'cornerRadius' => 'lg',
                'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => '👤 สมาชิก:', 'size' => 'sm', 'flex' => 2, 'color' => '#555555'],
                    ['type' => 'text', 'text' => $memberStatus, 'size' => 'sm', 'weight' => 'bold', 'color' => '#2E7D32', 'flex' => 3, 'align' => 'end'],
                ],
            ];
        }

        // กล่องสิทธิ์ฟรี — ถ้า admin ปิดบริการฟรีและไม่มีเครดิตพิเศษ → ใช้ label "สิทธิ์ดูดวง" แทน
        $freeEnabled = $this->settings->isFreeReadingEnabled();
        $creditBoxTitle = ($freeEnabled || $isUnlimited || $specialCredits > 0)
            ? '🔮 สิทธิ์ดูดวงฟรี'
            : '🔮 สิทธิ์ดูดวง';
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'vertical',
            'margin' => 'lg',
            'backgroundColor' => '#F3E5F5',
            'cornerRadius' => 'lg',
            'paddingAll' => 'lg',
            'contents' => [
                [
                    'type' => 'text',
                    'text' => $creditBoxTitle,
                    'size' => 'md',
                    'weight' => 'bold',
                    'color' => '#6B46C1',
                ],
                ['type' => 'separator', 'margin' => 'md', 'color' => '#E1BEE7'],
                [
                    'type' => 'box',
                    'layout' => 'horizontal',
                    'margin' => 'md',
                    'contents' => [
                        ['type' => 'text', 'text' => '📊 ใช้วันนี้:', 'size' => 'sm', 'flex' => 3, 'color' => '#555555'],
                        ['type' => 'text', 'text' => $isUnlimited ? '—' : "{$used}/{$total} ครั้ง", 'size' => 'sm', 'weight' => 'bold', 'color' => '#333333', 'flex' => 2, 'align' => 'end'],
                    ],
                ],
                [
                    'type' => 'box',
                    'layout' => 'horizontal',
                    'margin' => 'sm',
                    'contents' => [
                        ['type' => 'text', 'text' => '🆓 คงเหลือ:', 'size' => 'md', 'flex' => 3, 'color' => '#555555'],
                        ['type' => 'text', 'text' => $creditText, 'size' => 'md', 'weight' => 'bold', 'color' => $statusColor, 'flex' => 2, 'align' => 'end'],
                    ],
                ],
            ],
        ];

        // เครดิตพิเศษ (ถ้ามี)
        if ($specialCredits > 0) {
            $bodyContents[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'margin' => 'lg',
                'backgroundColor' => '#FFF8E1',
                'cornerRadius' => 'lg',
                'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => '🎁 เครดิตพิเศษ:', 'size' => 'sm', 'flex' => 3, 'color' => '#555555'],
                    ['type' => 'text', 'text' => "{$specialCredits} ครั้ง", 'size' => 'sm', 'weight' => 'bold', 'color' => '#F57C00', 'flex' => 2, 'align' => 'end'],
                ],
            ];
        }

        // ✅ แสดง Wallet + รายได้ค่าคอม (ถ้ามี)
        if ($walletBalance > 0 || $totalCommission > 0) {
            $walletDisplay = number_format($walletBalance, 2);
            $commDisplay = number_format($totalCommission, 2);
            $bodyContents[] = [
                'type' => 'box',
                'layout' => 'vertical',
                'margin' => 'lg',
                'backgroundColor' => '#F0FFF4',
                'cornerRadius' => 'lg',
                'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '💰 Wallet & รายได้', 'size' => 'md', 'weight' => 'bold', 'color' => '#2E7D32'],
                    ['type' => 'separator', 'margin' => 'md', 'color' => '#C8E6C9'],
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '💰 ยอดใน Wallet:', 'size' => 'sm', 'flex' => 3, 'color' => '#555555'],
                            ['type' => 'text', 'text' => "฿{$walletDisplay}", 'size' => 'sm', 'weight' => 'bold', 'color' => '#06C755', 'flex' => 2, 'align' => 'end'],
                        ],
                    ],
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'sm',
                        'contents' => [
                            ['type' => 'text', 'text' => '📈 รายได้ค่าคอมรวม:', 'size' => 'sm', 'flex' => 3, 'color' => '#555555'],
                            ['type' => 'text', 'text' => "฿{$commDisplay}", 'size' => 'sm', 'weight' => 'bold', 'color' => '#FF8F00', 'flex' => 2, 'align' => 'end'],
                        ],
                    ],
                ],
            ];
        }

        // เส้นแบ่ง + คำแนะนำ
        $bodyContents[] = ['type' => 'separator', 'margin' => 'xl', 'color' => '#E8E0FF'];
        $bodyContents[] = [
            'type' => 'text',
            'text' => '💡 พิมพ์ "ดูดวง" เพื่อเริ่มถามดวงชะตาได้เลยค่ะ',
            'size' => 'xs',
            'color' => '#999999',
            'margin' => 'lg',
            'wrap' => true,
        ];

        $appUrl = config('app.url', 'https://main.thaiprompt.online');
        $brandName = $this->settings->getFortuneBrandName();

        // ปุ่ม footer
        $footerContents = [
            [
                'type' => 'button',
                'style' => 'primary',
                'color' => '#6B46C1',
                'height' => 'sm',
                'action' => ['type' => 'message', 'label' => '🔮 ดูดวงเลย', 'text' => 'ดูดวง'],
            ],
            [
                'type' => 'box',
                'layout' => 'horizontal',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'height' => 'sm',
                        'action' => ['type' => 'uri', 'label' => '💰 Wallet', 'uri' => $appUrl.'/auth/line?redirect=/user/wallet'],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'height' => 'sm',
                        'action' => ['type' => 'message', 'label' => '📖 คำทำนายล่าสุด', 'text' => 'ดูคำทำนายล่าสุด'],
                    ],
                ],
            ],
        ];

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => ['header' => ['backgroundColor' => '#00897B']],
            'header' => [
                'type' => 'box',
                'layout' => 'horizontal',
                'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '✅', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'flex' => 1,
                        'paddingStart' => 'md',
                        'justifyContent' => 'center',
                        'contents' => [
                            ['type' => 'text', 'text' => 'สถานะ / Wallet', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => "{$brandName}ดูดวง 🌙", 'color' => '#FFFFFFCC', 'size' => 'xs'],
                        ],
                    ],
                ],
            ],
            'body' => ['type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl', 'contents' => $bodyContents],
            'footer' => ['type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg', 'contents' => $footerContents],
        ];
    }

    /**
     * สร้าง Flex Message ปฏิเสธ/ยกเลิก (declined, cancelled)
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  string  $type  'declined' หรือ 'cancelled'
     * @return array Flex Message bubble
     */
    public function buildDeclinedFlexMessage(string $userName, string $type = 'declined'): array
    {
        $userName = $this->sanitizeUserName($userName);

        $title = $type === 'cancelled' ? 'ยกเลิกแล้วค่ะ' : 'ไม่เป็นไรค่ะ';
        $subtitle = "คุณ{$userName} สามารถกลับมาดูดวงได้ทุกเมื่อนะคะ ✨";
        $headerColor = $type === 'cancelled' ? '#78909C' : '#8E24AA';

        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => $headerColor]],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => $type === 'cancelled' ? '✅' : '🙏', 'size' => 'xl', 'flex' => 0],
                    ['type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md', 'justifyContent' => 'center', 'contents' => [['type' => 'text', 'text' => $title, 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold']]],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => $subtitle, 'size' => 'sm', 'color' => '#555555', 'wrap' => true],
                    ['type' => 'text', 'text' => 'ขอให้โชคดีค่ะ 🌟', 'size' => 'sm', 'color' => '#999999', 'margin' => 'lg'],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'horizontal', 'spacing' => 'md', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูดวงใหม่', 'text' => 'ดูดวง']],
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'uri', 'label' => '📤 แชร์ให้เพื่อน', 'uri' => 'https://line.me/R/nv/recommendOA/'.($this->settings->line_bot_basic_id ?? config('services.line.bot_basic_id', '@002dqcls'))]],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message บิลหมดอายุ (payment_expired)
     *
     * @param  float  $deepReadingPrice  ราคาดูดวงละเอียด
     * @return array Flex Message bubble
     */
    public function buildPaymentExpiredFlexMessage(float $deepReadingPrice): array
    {
        $priceDisplay = number_format($deepReadingPrice, 0);

        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#E53935']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '⏰', 'size' => 'xxl', 'flex' => 0],
                    ['type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md', 'justifyContent' => 'center', 'contents' => [['type' => 'text', 'text' => 'บิลหมดอายุแล้ว', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold']]],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'ระยะเวลาชำระเงินหมดแล้ว', 'size' => 'sm', 'color' => '#555555'],
                    ['type' => 'text', 'text' => 'สามารถเริ่มดูดวงใหม่ได้เลยค่ะ', 'size' => 'sm', 'color' => '#999999', 'margin' => 'lg', 'wrap' => true],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#E8890C', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => "💎 ดูดวง ค่าครู {$priceDisplay} บาท", 'text' => 'ดูดวง']],
                    ...($this->settings->isFreeReadingEnabled()
                        ? [['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูดวงฟรี', 'text' => 'ดูดวง']]]
                        : []),
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message รอชำระเงิน (waiting_payment)
     *
     * @param  float  $amount  ยอดชำระ
     * @param  string  $billRef  เลขที่บิล
     * @param  string  $expiresAt  เวลาหมดอายุ
     * @param  int  $remainingMinutes  เวลาที่เหลือ (นาที)
     * @return array Flex Message bubble
     */
    public function buildWaitingPaymentFlexMessage(float $amount, string $billRef, string $expiresAt, int $remainingMinutes): array
    {
        $amountDisplay = number_format($amount, 2);
        $urgentColor = $remainingMinutes <= 10 ? '#E53935' : '#E8890C';

        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#E8890C']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '💰', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => 'รอชำระเงิน', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => "บิล: {$billRef}", 'color' => '#FFFFFFCC', 'size' => 'xs'],
                        ],
                    ],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    // ยอดชำระ
                    ['type' => 'text', 'text' => "฿{$amountDisplay}", 'size' => 'xxl', 'weight' => 'bold', 'color' => '#E8890C', 'align' => 'center'],
                    ['type' => 'text', 'text' => '⚠️ โอนตรงตามทศนิยม', 'size' => 'xs', 'color' => '#E53935', 'align' => 'center', 'margin' => 'sm'],
                    ['type' => 'separator', 'margin' => 'xl', 'color' => '#FFF3E0'],
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'lg',
                        'contents' => [
                            ['type' => 'text', 'text' => '⏰ โอนก่อน:', 'size' => 'sm', 'color' => '#555555', 'flex' => 2],
                            ['type' => 'text', 'text' => "{$expiresAt} น.", 'size' => 'sm', 'weight' => 'bold', 'color' => $urgentColor, 'flex' => 2, 'align' => 'end'],
                        ],
                    ],
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'sm',
                        'contents' => [
                            ['type' => 'text', 'text' => '⏳ เหลือเวลา:', 'size' => 'sm', 'color' => '#555555', 'flex' => 2],
                            ['type' => 'text', 'text' => "{$remainingMinutes} นาที", 'size' => 'sm', 'weight' => 'bold', 'color' => $urgentColor, 'flex' => 2, 'align' => 'end'],
                        ],
                    ],
                    // ข้อความเตือน
                    ['type' => 'text', 'text' => 'เมื่อโอนแล้วรอสักครู่ ระบบจะส่งคำทำนายให้ทันทีค่ะ ✨', 'size' => 'xs', 'color' => '#999999', 'margin' => 'xl', 'wrap' => true],
                    ['type' => 'text', 'text' => "💡 หากโอนแล้วระบบไม่แจ้งเตือน ให้พิมพ์ว่า 'โอนแล้ว' ระบบจะส่งคำทำนายให้", 'size' => 'xs', 'color' => '#E65100', 'margin' => 'md', 'wrap' => true],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🏦 ดูบัญชีธนาคาร', 'text' => 'บัญชี']],
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '❌ ยกเลิก', 'text' => 'ยกเลิก']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message กำลังประมวลผล (view_reading_processing)
     *
     * @param  string  $billRef  เลขที่บิล
     * @return array Flex Message bubble
     */
    public function buildProcessingFlexMessage(string $billRef): array
    {
        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#1976D2']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '⏳', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => 'กำลังสร้างคำทำนาย', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => "บิล: {$billRef}", 'color' => '#FFFFFFCC', 'size' => 'xs'],
                        ],
                    ],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'AI กำลังวิเคราะห์ดวงชะตาให้ค่ะ', 'size' => 'sm', 'color' => '#333333'],
                    ['type' => 'text', 'text' => 'ใช้เวลาประมาณ 1-2 นาที', 'size' => 'sm', 'color' => '#999999', 'margin' => 'sm'],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'margin' => 'xl', 'backgroundColor' => '#E3F2FD', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '💡 พิมพ์ "ดูผล" เพื่อเช็คสถานะ', 'size' => 'xs', 'color' => '#1565C0'],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#1976D2', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔍 เช็คสถานะ', 'text' => 'ดูผล']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message ยืนยันชำระเงินสำเร็จ (payment_confirmed_wait)
     *
     * ✅ สีเขียว — แยกจาก processing (สีฟ้า) เพื่อให้ลูกค้าเห็นชัดว่า "จ่ายเงินผ่านแล้ว"
     *
     * @param  string  $billRef  เลขที่บิล
     * @param  string  $userName  ชื่อผู้ใช้
     * @return array Flex Message bubble
     */
    public function buildPaymentConfirmedFlexMessage(string $billRef, string $userName = 'คุณ'): array
    {
        $userName = $this->sanitizeUserName($userName);

        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#2E7D32']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '✅', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => 'ชำระเงินสำเร็จ!', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => "บิล: {$billRef}", 'color' => '#FFFFFFCC', 'size' => 'xs'],
                        ],
                    ],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => "ขอบคุณค่ะ คุณ{$userName}! 🙏", 'size' => 'md', 'color' => '#333333', 'weight' => 'bold'],
                    ['type' => 'text', 'text' => 'ได้รับการชำระเงินเรียบร้อยแล้ว', 'size' => 'sm', 'color' => '#2E7D32', 'margin' => 'sm'],
                    ['type' => 'separator', 'margin' => 'lg'],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'margin' => 'lg', 'backgroundColor' => '#FFF8E1', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '🔮 จันทราจะวิเคราะห์ดวงชะตาให้นะคะ', 'size' => 'sm', 'color' => '#E65100', 'wrap' => true],
                            ['type' => 'text', 'text' => '⏳ รอสักครู่ประมาณ 3-5 นาทีค่ะ', 'size' => 'sm', 'color' => '#BF360C', 'margin' => 'sm', 'wrap' => true],
                        ],
                    ],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'margin' => 'lg', 'backgroundColor' => '#E8F5E9', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '💡 พิมพ์ "ดูผล" เพื่อเช็คสถานะได้ค่ะ', 'size' => 'xs', 'color' => '#1B5E20'],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#2E7D32', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔍 เช็คสถานะคำทำนาย', 'text' => 'ดูผล']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message แจ้ง "คำทำนายพร้อมแล้ว" (fortune_ready_notification)
     *
     * ✅ ใช้ Flex ที่สะดุดตา (สีม่วง+ทอง) แทน text ธรรมดา เพื่อให้ลูกค้าเห็นชัดเจน
     * มีปุ่ม "อ่านคำทำนาย" กดได้ทันที (เหมือน Facebook Button Template)
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  string|null  $billRef  เลขที่บิล
     * @return array Flex Message bubble
     */
    public function buildFortuneReadyFlexMessage(string $userName = 'คุณ', ?string $billRef = null): array
    {
        $userName = $this->sanitizeUserName($userName);

        $billText = $billRef ? "📋 เลขที่บิล: {$billRef}" : '';

        return [
            'type' => 'bubble',
            'styles' => [
                'header' => ['backgroundColor' => '#6B46C1'],
                'footer' => ['backgroundColor' => '#F3E8FF'],
            ],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🔮', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => 'คำทำนายพร้อมแล้ว!', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => '✨ วิเคราะห์เสร็จเรียบร้อย', 'color' => '#FFFFFFCC', 'size' => 'xs'],
                        ],
                    ],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl', 'spacing' => 'md',
                'contents' => array_filter([
                    ['type' => 'text', 'text' => "✨ คุณ{$userName}คะ", 'size' => 'md', 'color' => '#333333', 'weight' => 'bold'],
                    ['type' => 'text', 'text' => 'คำทำนายเชิงลึกของคุณพร้อมแล้วค่ะ!', 'size' => 'sm', 'color' => '#6B46C1', 'margin' => 'sm', 'wrap' => true],
                    $billRef ? ['type' => 'text', 'text' => $billText, 'size' => 'xs', 'color' => '#999999', 'margin' => 'sm'] : null,
                    ['type' => 'separator', 'margin' => 'lg'],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'margin' => 'lg', 'backgroundColor' => '#FFF8E1', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '💎 หมอจันทราได้วิเคราะห์ดวงชะตาของคุณ', 'size' => 'sm', 'color' => '#E65100', 'wrap' => true],
                            ['type' => 'text', 'text' => 'อย่างละเอียดเรียบร้อยแล้ว', 'size' => 'sm', 'color' => '#E65100', 'wrap' => true],
                            ['type' => 'text', 'text' => '🌟 กดปุ่มด้านล่างเพื่ออ่านได้เลยค่ะ', 'size' => 'sm', 'color' => '#BF360C', 'margin' => 'sm', 'wrap' => true],
                        ],
                    ],
                ]),
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'md',
                        'action' => ['type' => 'message', 'label' => '📖 อ่านคำทำนายเลย', 'text' => 'อ่านคำทำนาย']],
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm',
                        'action' => ['type' => 'message', 'label' => '⏰ ไว้ดูทีหลัง', 'text' => 'ไว้ดูทีหลัง']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message ไม่มีคำทำนาย (view_reading_empty)
     *
     * @return array Flex Message bubble
     */
    public function buildNoReadingFlexMessage(): array
    {
        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#6B46C1']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🔮', 'size' => 'xxl', 'flex' => 0],
                    ['type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md', 'justifyContent' => 'center', 'contents' => [['type' => 'text', 'text' => 'ยังไม่มีคำทำนาย', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold']]],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'เริ่มดูดวงกันเลยค่ะ!', 'size' => 'md', 'color' => '#333333'],
                    ['type' => 'text', 'text' => 'พิมพ์คำถามหรือกดปุ่มด้านล่างได้เลยนะคะ ✨', 'size' => 'sm', 'color' => '#999999', 'margin' => 'sm', 'wrap' => true],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูดวงเลย', 'text' => 'ดูดวง']],
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '💎 ดูดวง', 'text' => 'ดูดวง']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message ไว้ดูทีหลัง (view_later)
     *
     * @return array Flex Message bubble
     */
    public function buildViewLaterFlexMessage(): array
    {
        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#43A047']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '✅', 'size' => 'xl', 'flex' => 0],
                    ['type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md', 'justifyContent' => 'center', 'contents' => [['type' => 'text', 'text' => 'บันทึกแล้ว', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold']]],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'คำทำนายถูกบันทึกไว้แล้ว', 'size' => 'sm', 'color' => '#333333'],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'margin' => 'xl', 'backgroundColor' => '#E8F5E9', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '💡 พิมพ์ "ดูคำทำนาย" เพื่อดูได้ทุกเมื่อ', 'size' => 'xs', 'color' => '#2E7D32', 'wrap' => true],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูคำทำนาย', 'text' => 'ดูคำทำนาย']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message ข้อผิดพลาด (error)
     *
     * @param  string  $message  ข้อความ error (optional)
     * @return array Flex Message bubble
     */
    public function buildErrorFlexMessage(string $message = ''): array
    {
        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#E53935']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '⚠️', 'size' => 'xl', 'flex' => 0],
                    ['type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md', 'justifyContent' => 'center', 'contents' => [['type' => 'text', 'text' => 'เกิดข้อผิดพลาด', 'color' => '#FFFFFF', 'size' => 'md', 'weight' => 'bold']]],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'ขอโทษค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏', 'size' => 'sm', 'color' => '#555555', 'wrap' => true],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ลองอีกครั้ง', 'text' => 'ดูดวง']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message ดูดวงละเอียดถูกปิด (deep_reading_disabled)
     *
     * @return array Flex Message bubble
     */
    public function buildDeepReadingDisabledFlexMessage(): array
    {
        // ถ้า admin ปิดทั้งดูดวงละเอียด และ ดูดวงฟรี → ไม่พูดถึง "ฟรี"
        $freeEnabled = $this->settings->isFreeReadingEnabled();
        $bodyBottom = $freeEnabled
            ? 'ยังสามารถดูดวงฟรีได้ตามปกตินะคะ ✨'
            : 'กรุณาติดต่อแม่หมอเพื่อสอบถามบริการอื่นค่ะ';
        $buttonLabel = $freeEnabled ? '🔮 ดูดวงฟรี' : '🔮 เริ่มดูดวง';

        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#78909C']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🔒', 'size' => 'xl', 'flex' => 0],
                    ['type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md', 'justifyContent' => 'center', 'contents' => [['type' => 'text', 'text' => 'ปิดให้บริการชั่วคราว', 'color' => '#FFFFFF', 'size' => 'md', 'weight' => 'bold']]],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'บริการดูดวงปิดให้บริการชั่วคราวค่ะ', 'size' => 'sm', 'color' => '#555555', 'wrap' => true],
                    ['type' => 'text', 'text' => $bodyBottom, 'size' => 'sm', 'color' => '#999999', 'margin' => 'lg', 'wrap' => true],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => $buttonLabel, 'text' => 'ดูดวง']],
                ],
            ],
        ];
    }

    // ============================================================
    // Private Methods
    // ============================================================

    /**
     * ส่งข้อความผ่าน LINE Messaging API (Push)
     *
     * @param  string  $to  LINE User ID
     * @param  array  $messages  รายการข้อความ
     */
    /**
     * ส่ง push message แบบ admin priority — ข้าม Gatekeeper
     *
     * ใช้สำหรับ: แอดมินตอบคำถาม saved questions, แจ้งชำระเงิน, แจ้งคำทำนายพร้อม
     * ข้อความเหล่านี้สำคัญและต้องส่งถึงผู้ใช้ทันที
     */
    public function sendAdminMessage(string $recipientId, string $message): bool
    {
        $messages = [
            [
                'type' => 'text',
                'text' => $message,
            ],
        ];

        return $this->pushMessagePriority($recipientId, $messages);
    }

    /**
     * แสดง loading animation (typing indicator) ให้ลูกค้ารู้ว่าบอทยังทำงานอยู่
     *
     * LINE Messaging API: https://developers.line.biz/en/reference/messaging-api/#display-a-loading-indicator
     * ใช้ตอนรอ AI ประมวลผลคำทำนายนาน 45-90 วินาที
     *
     * @param  string  $userId  LINE user ID
     * @param  int  $loadingSeconds  ระยะเวลา loading (5-60 วินาที, ต้องหาร 5 ลงตัว)
     * @return bool สำเร็จหรือไม่
     */
    public function showLoadingAnimation(string $userId, int $loadingSeconds = 20): bool
    {
        // Clamp เป็น multiple of 5 ระหว่าง 5-60
        $loadingSeconds = max(5, min(60, (int) (round($loadingSeconds / 5) * 5)));

        $token = $this->settings->line_channel_access_token ?? '';
        if (empty($token)) {
            return false;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->timeout(5)
                ->post('https://api.line.me/v2/bot/chat/loading/start', [
                    'chatId' => $userId,
                    'loadingSeconds' => $loadingSeconds,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::debug('LINE loadingAnimation: ไม่สำเร็จ', [
                'user_id' => mb_substr($userId, 0, 20),
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 200),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::debug('LINE loadingAnimation: exception', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Push message แบบ priority — ข้าม Gatekeeper throttle
     *
     * ใช้สำหรับข้อความสำคัญที่ต้องส่งถึงผู้ใช้ทันที
     */
    protected function pushMessagePriority(string $to, array $messages): bool
    {
        // 🛡️ (2026-07-24) กัน recipient ตัวเลขล้วน (FB PSID) เหมือน pushMessage —
        //   ยิง LINE ไม่ได้อยู่แล้ว (400) + priority push retry 2 ครั้ง = เปลืองเปล่า
        if (ctype_digit($to)) {
            Log::warning('LINE pushMessagePriority: ปฏิเสธ recipient ที่ไม่ใช่ LINE userId (น่าจะเป็น FB PSID) — ข้ามไม่ยิง API', [
                'to' => $to,
                'msg_count' => count($messages),
            ]);

            return false;
        }

        // ✅ Priority push: retry แค่ 2 ครั้ง ด้วย delay สั้น (ไม่ block นาน)
        $maxRetries = 2;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            if ($attempt > 1) {
                // รอแค่ 200ms ก่อน retry
                usleep(200_000);
            }

            LineGatekeeperService::recordLinePush();

            try {
                $response = Http::withToken($this->channelAccessToken)
                    ->timeout(10)
                    ->connectTimeout(5)
                    ->post(self::API_ENDPOINT.'/message/push', [
                        'to' => $to,
                        'messages' => $messages,
                    ]);

                if ($response->successful()) {
                    LineGatekeeperService::clearLineBackoff();

                    return true;
                }

                $status = $response->status();

                if ($status === 429 && $attempt < $maxRetries) {
                    Log::warning("LINE pushMessagePriority: 429 → retry (attempt {$attempt})", [
                        'to' => $to,
                    ]);

                    continue;
                }

                Log::error('LINE pushMessagePriority: ส่งไม่สำเร็จ', [
                    'to' => $to,
                    'status' => $status,
                    'body' => mb_substr($response->body(), 0, 500),
                    'msg_json_size' => strlen(json_encode($messages, JSON_UNESCAPED_UNICODE)),
                    'msg_count' => count($messages),
                    'msg_types' => array_column($messages, 'type'),
                ]);

                return false;

            } catch (\Exception $e) {
                Log::error("LINE pushMessagePriority: Exception (attempt {$attempt})", [
                    'to' => $to,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $maxRetries) {
                    continue;
                }

                return false;
            }
        }

        return false;
    }

    protected function pushMessage(string $to, array $messages): bool
    {
        // 🛡️ (2026-07-24) กัน recipient ที่ไม่ใช่ LINE userId (ตัวเลขล้วน = FB PSID)
        //   LINE userId/groupId/roomId ขึ้นต้น U/C/R + hex เสมอ — ตัวเลขล้วนยิงไปได้ 400
        //   "invalid to" อย่างเดียว (เคส reading platform เพี้ยน). ตัดตั้งแต่ต้น → ไม่เปลือง
        //   LINE quota + ไม่เกิด log error ซ้ำทุกนาทีเมื่อ path ใดสร้าง reading platform ผิด
        if (ctype_digit($to)) {
            Log::warning('LINE pushMessage: ปฏิเสธ recipient ที่ไม่ใช่ LINE userId (น่าจะเป็น FB PSID) — ข้ามไม่ยิง API', [
                'to' => $to,
                'msg_count' => count($messages),
            ]);

            return false;
        }

        // ✅ V2: ส่งทันที ไม่ block ไม่ retry (เพื่อไม่ให้ webhook ช้า)
        // ถ้าโดน 429 → return false → fortune:check-pending จะ retry ทีหลัง
        LineGatekeeperService::recordLinePush();

        try {
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(15)
                ->connectTimeout(8)
                ->post(self::API_ENDPOINT.'/message/push', [
                    'to' => $to,
                    'messages' => $messages,
                ]);

            if ($response->successful()) {
                LineGatekeeperService::clearLineBackoff();

                return true;
            }

            $status = $response->status();

            if ($status === 429) {
                $retryAfter = (int) $response->header('retry-after', 0);
                LineGatekeeperService::recordLineRateLimit($retryAfter ?: null, 1);

                Log::warning('LINE pushMessage: HTTP 429 rate limited', [
                    'to' => $to,
                    'retry_after' => $retryAfter,
                    'headers' => [
                        'x-line-request-id' => $response->header('x-line-request-id'),
                        'x-ratelimit-remaining' => $response->header('x-ratelimit-remaining'),
                        'x-ratelimit-reset' => $response->header('x-ratelimit-reset'),
                    ],
                ]);

                return false;
            }

            Log::error('LINE Push Message Error', [
                'to' => $to,
                'status' => $status,
                'body' => mb_substr($response->body(), 0, 500),
                'msg_json_size' => strlen(json_encode($messages, JSON_UNESCAPED_UNICODE)),
                'msg_count' => count($messages),
                'msg_types' => array_column($messages, 'type'),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('LINE pushMessage: Exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ตอบกลับข้อความ (Reply) — เร็วกว่า pushMessage มาก!
     *
     * replyMessage ใช้ replyToken จาก webhook → ตอบกลับทันที ไม่ต้องสร้าง connection ใหม่
     * ⚡ เร็วกว่า pushMessage 2-3 เท่า + ฟรี (ไม่นับ quota)
     *
     * @param  string  $replyToken  Reply token จาก webhook (ใช้ได้ภายใน 1 นาที)
     * @param  array  $messages  รายการข้อความ (สูงสุด 5 ข้อความ)
     */
    public function replyMessage(string $replyToken, array $messages): bool
    {
        // ⚠️ ไม่มี circuit breaker สำหรับ reply — reply ฟรี ไม่นับ quota
        // ต้องลองส่งทุกครั้ง เพราะ timeout ครั้งก่อนไม่ได้หมายความว่าครั้งนี้จะ timeout
        try {
            // ⚡ (2026-05-16) Fail-fast timeout — replyToken หมดอายุ 60s
            //   เดิม timeout=12s + connect=8s → รอ 12s ก่อน fallback → "ดีเลย์" ชัดเจน
            //   ใหม่: 5s read + 3s connect → ถ้า LINE API ช้า → fallback push เร็ว
            //   user feedback: "LINE มันดีเลย์ ไม่เหมือนในเฟชบุ๊ค"
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(5)
                ->connectTimeout(3)
                ->post(self::API_ENDPOINT.'/message/reply', [
                    'replyToken' => $replyToken,
                    'messages' => $messages,
                ]);

            if (! $response->successful()) {
                Log::error('LINE Reply Message Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;

        } catch (\Exception $e) {
            // Timeout → แค่ log ไม่ block ครั้งถัดไป
            Log::warning('LINE replyMessage: Exception (ลองใหม่ครั้งหน้า)', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ตอบกลับด้วย Flex Message ผ่าน replyToken (เร็วที่สุด!)
     *
     * @param  string  $replyToken  Reply token จาก webhook
     * @param  array  $flexContent  Flex Message content
     * @param  string  $altText  ข้อความ fallback สำหรับ notification
     */
    public function replyWithFlex(string $replyToken, array $flexContent, string $altText = 'ข้อความจากระบบดูดวง'): bool
    {
        return $this->replyMessage($replyToken, [
            [
                'type' => 'flex',
                'altText' => $altText,
                'contents' => $flexContent,
            ],
        ]);
    }

    /**
     * ส่ง Flex Message ด้วย replyToken ก่อน ถ้าไม่ได้ → fallback เป็น pushMessage
     *
     * @param  string  $recipientId  LINE User ID
     * @param  array  $flexContent  Flex Message content
     * @param  string  $altText  ข้อความ fallback
     * @param  string|null  $replyToken  Reply token (ถ้ามี)
     */
    public function sendFlexWithReplyFallback(string $recipientId, array $flexContent, string $altText, ?string $replyToken = null): bool
    {
        // ลอง replyMessage ก่อน (เร็วกว่า + ฟรี)
        if ($replyToken) {
            $result = $this->replyWithFlex($replyToken, $flexContent, $altText);
            if ($result) {
                return true;
            }
            Log::warning('LINE: replyMessage ล้มเหลว fallback เป็น pushMessage', [
                'recipient_id' => $recipientId,
                'alt_text' => $altText,
            ]);
        }

        // Fallback: ใช้ pushMessage
        $pushResult = $this->sendRichMessage($recipientId, [
            'alt_text' => $altText,
            'contents' => $flexContent,
        ]);

        if (! $pushResult) {
            Log::error('LINE: pushMessage ล้มเหลวด้วย! ผู้ใช้ไม่ได้รับข้อความ', [
                'recipient_id' => $recipientId,
                'alt_text' => $altText,
                'flex_type' => $flexContent['type'] ?? 'unknown',
                'flex_json_size' => strlen(json_encode($flexContent)),
            ]);
        }

        return $pushResult;
    }

    /**
     * ส่งข้อความ text ด้วย replyToken ก่อน ถ้าไม่ได้ → fallback เป็น pushMessage
     *
     * @param  string  $recipientId  LINE User ID
     * @param  string  $message  ข้อความ
     * @param  string|null  $replyToken  Reply token (ถ้ามี)
     */
    public function sendMessageWithReplyFallback(string $recipientId, string $message, ?string $replyToken = null): bool
    {
        // ลอง replyMessage ก่อน
        if ($replyToken) {
            $result = $this->replyMessage($replyToken, [
                ['type' => 'text', 'text' => $message],
            ]);
            if ($result) {
                return true;
            }
        }

        // Fallback: pushMessage
        return $this->sendMessage($recipientId, $message);
    }

    /**
     * สร้าง Quick Reply Items สำหรับ LINE
     */
    protected function buildQuickReplyItems(array $quickReplies): array
    {
        $items = [];

        foreach ($quickReplies as $reply) {
            $items[] = [
                'type' => 'action',
                'action' => [
                    'type' => 'message',
                    'label' => $reply['label'] ?? $reply['title'] ?? $reply,
                    'text' => $reply['text'] ?? $reply['payload'] ?? $reply,
                ],
            ];
        }

        return $items;
    }

    /**
     * ตรวจสอบ Signature จาก LINE Webhook
     *
     * @param  string  $body  Request body
     * @param  string  $signature  X-Line-Signature header
     */
    public function verifySignature(string $body, string $signature): bool
    {
        $hash = base64_encode(hash_hmac('sha256', $body, $this->channelSecret, true));

        return hash_equals($hash, $signature);
    }

    /**
     * ทดสอบการเชื่อมต่อ LINE API
     *
     * @return array ผลการทดสอบ ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public function testConnection(): array
    {
        try {
            // ตรวจสอบว่ามี token หรือไม่
            if (empty($this->channelAccessToken)) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบ Channel Access Token กรุณาตั้งค่าก่อน',
                ];
            }

            // เรียก API เพื่อดึงข้อมูล Bot
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(15)
                ->connectTimeout(5)
                ->retry(2, 1000)
                ->get(self::API_ENDPOINT.'/info');

            if ($response->successful()) {
                $botInfo = $response->json();

                return [
                    'success' => true,
                    'message' => 'เชื่อมต่อสำเร็จ! Bot: '.($botInfo['displayName'] ?? 'Unknown'),
                    'data' => [
                        'bot_info' => $botInfo,
                    ],
                ];
            }

            // กรณี API Error
            $error = $response->json();
            $errorMessage = $error['message'] ?? 'Unknown error';

            return [
                'success' => false,
                'message' => "เชื่อมต่อไม่สำเร็จ: {$errorMessage}",
                'data' => [
                    'status' => $response->status(),
                    'error' => $error,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('LINE Test Connection Error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ];
        }
    }

    // =====================================================================
    // Flex Messages: แจ้งปัญหาโอน + วิธีใช้งาน
    // =====================================================================

    /**
     * สร้าง Flex Message แจ้งปัญหาการโอนเงิน
     *
     * แสดงคำเตือนตัวใหญ่ว่าต้องโอนตรงทศนิยม + ข้อมูลบิลที่รอชำระ
     *
     * @param  \App\Models\FortuneReading|null  $pendingReading  บิลที่รอชำระ
     * @param  float|null  $uniqueAmount  ยอดที่ต้องโอน (ทศนิยม)
     * @param  string|null  $expiresAt  เวลาหมดอายุ
     * @return array Flex Message bubble
     */
    public function buildPaymentProblemFlexMessage(
        ?\App\Models\FortuneReading $pendingReading = null,
        ?float $uniqueAmount = null,
        ?string $expiresAt = null
    ): array {
        $bodyContents = [
            // คำเตือนตัวใหญ่ XXL
            ['type' => 'text', 'text' => '🚨 ต้องโอนให้ตรงทศนิยม', 'size' => 'xl', 'weight' => 'bold', 'color' => '#E53935', 'align' => 'center', 'wrap' => true],
            ['type' => 'text', 'text' => 'เท่านั้น!', 'size' => 'xl', 'weight' => 'bold', 'color' => '#E53935', 'align' => 'center'],
            ['type' => 'text', 'text' => 'ถ้าโอนไม่ตรง ระบบจะไม่ส่งคำทำนาย', 'size' => 'md', 'weight' => 'bold', 'color' => '#D84315', 'align' => 'center', 'margin' => 'lg', 'wrap' => true],
            ['type' => 'separator', 'margin' => 'xl', 'color' => '#FFCDD2'],
        ];

        if ($pendingReading && $uniqueAmount) {
            // มีบิลที่รอชำระ — แสดงข้อมูล
            $amountDisplay = number_format($uniqueAmount, 2);
            $billRef = $pendingReading->bill_reference ?? '-';

            $bodyContents[] = ['type' => 'text', 'text' => '📋 บิลที่รอชำระ', 'size' => 'md', 'weight' => 'bold', 'color' => '#333333', 'margin' => 'xl'];
            $bodyContents[] = [
                'type' => 'box', 'layout' => 'horizontal', 'margin' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => 'เลขที่บิล:', 'size' => 'sm', 'color' => '#555555', 'flex' => 2],
                    ['type' => 'text', 'text' => $billRef, 'size' => 'sm', 'weight' => 'bold', 'color' => '#333333', 'flex' => 3, 'align' => 'end'],
                ],
            ];
            $bodyContents[] = [
                'type' => 'box', 'layout' => 'horizontal', 'margin' => 'sm',
                'contents' => [
                    ['type' => 'text', 'text' => 'ยอดที่ต้องโอน:', 'size' => 'sm', 'color' => '#555555', 'flex' => 2],
                    ['type' => 'text', 'text' => "฿{$amountDisplay}", 'size' => 'lg', 'weight' => 'bold', 'color' => '#E53935', 'flex' => 3, 'align' => 'end'],
                ],
            ];

            if ($expiresAt) {
                $bodyContents[] = [
                    'type' => 'box', 'layout' => 'horizontal', 'margin' => 'sm',
                    'contents' => [
                        ['type' => 'text', 'text' => 'หมดอายุ:', 'size' => 'sm', 'color' => '#555555', 'flex' => 2],
                        ['type' => 'text', 'text' => "{$expiresAt} น.", 'size' => 'sm', 'weight' => 'bold', 'color' => '#E8890C', 'flex' => 3, 'align' => 'end'],
                    ],
                ];
            }

            // เน้นย้ำ
            $bodyContents[] = [
                'type' => 'box', 'layout' => 'vertical', 'margin' => 'xl', 'paddingAll' => 'md', 'backgroundColor' => '#FFF3E0', 'cornerRadius' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => "⚠️ โอนยอด ฿{$amountDisplay} ให้ตรงเป๊ะ!", 'size' => 'sm', 'weight' => 'bold', 'color' => '#E65100', 'wrap' => true],
                    ['type' => 'text', 'text' => 'ห้ามปัดเศษ! ต้องโอนตามทศนิยมเท่านั้น', 'size' => 'xs', 'color' => '#BF360C', 'margin' => 'sm', 'wrap' => true],
                ],
            ];
        } else {
            // ไม่มีบิลที่รอชำระ
            $bodyContents[] = ['type' => 'text', 'text' => 'ไม่พบบิลที่รอชำระค่ะ', 'size' => 'md', 'color' => '#555555', 'align' => 'center', 'margin' => 'xl'];
            $bodyContents[] = ['type' => 'text', 'text' => 'ถ้าโอนเงินไปแล้วแต่ยังไม่ได้คำทำนาย กรุณากดปุ่ม "แจ้งว่าโอนแล้ว" ด้านล่างค่ะ', 'size' => 'xs', 'color' => '#999999', 'margin' => 'lg', 'wrap' => true];
        }

        // Footer buttons
        $footerContents = [];
        if ($pendingReading) {
            $readingId = $pendingReading->id;
            $footerContents[] = ['type' => 'button', 'style' => 'primary', 'color' => '#E53935', 'height' => 'sm', 'action' => ['type' => 'postback', 'label' => '✅ แจ้งว่าโอนแล้ว', 'data' => "action=confirm_transfer&reading_id={$readingId}", 'displayText' => 'แจ้งว่าโอนเงินแล้ว']];
        }
        $footerContents[] = ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูดวงใหม่', 'text' => 'ดูดวง']];

        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#C62828']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '⚠️', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md', 'justifyContent' => 'center',
                        'contents' => [
                            ['type' => 'text', 'text' => 'แจ้งปัญหาการโอนเงิน', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                        ],
                    ],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => $bodyContents,
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg',
                'contents' => $footerContents,
            ],
        ];
    }

    /**
     * สร้าง Flex Message วิธีใช้งาน (help)
     *
     * @return array Flex Message bubble
     */
    public function buildHelpFlexMessage(): array
    {
        $price = number_format($this->getDeepReadingPrice(), 0);
        $questions = FortuneConversationService::REQUIRED_QUESTIONS;
        $brandName = $this->settings->getFortuneBrandName();
        $primaryColor = $this->settings->getLineFlexPrimaryColor();
        // ถ้าระบบฟรีปิด → ไม่แสดงการ์ด "ดูดวงฟรี"
        $freeEnabled = $this->settings->isFreeReadingEnabled();

        // สร้างการ์ด "ดูดวงฟรี" (optional) + "ดูดวงละเอียด" (always)
        $fortuneServiceCards = [];
        if ($freeEnabled) {
            $fortuneServiceCards[] = [
                'type' => 'box', 'layout' => 'horizontal', 'margin' => 'sm', 'paddingAll' => 'sm',
                'backgroundColor' => '#F0FFF4', 'cornerRadius' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => '🆓', 'size' => 'md', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'sm',
                        'contents' => [
                            ['type' => 'text', 'text' => 'ดูดวงฟรี', 'size' => 'sm', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => 'พิมพ์ "ดูดวง" หรือตั้งคำถามได้เลย', 'size' => 'xs', 'color' => '#888888', 'wrap' => true],
                        ],
                    ],
                ],
            ];
        }
        $fortuneServiceCards[] = [
            'type' => 'box', 'layout' => 'horizontal', 'margin' => 'sm', 'paddingAll' => 'sm',
            'backgroundColor' => '#FFF8E1', 'cornerRadius' => 'md',
            'contents' => [
                ['type' => 'text', 'text' => '💎', 'size' => 'md', 'flex' => 0],
                [
                    'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'sm',
                    'contents' => [
                        ['type' => 'text', 'text' => "ดูดวง ({$price} บาท)", 'size' => 'sm', 'weight' => 'bold'],
                        ['type' => 'text', 'text' => "ถาม {$questions} คำถาม + วันเกิด → คำทำนายเชิงลึก", 'size' => 'xs', 'color' => '#888888', 'wrap' => true],
                    ],
                ],
            ],
        ];

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => ['header' => ['backgroundColor' => $primaryColor]],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🔮', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md', 'justifyContent' => 'center',
                        'contents' => [
                            ['type' => 'text', 'text' => "วิธีใช้งาน{$brandName}", 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => 'คู่มือการใช้งานทุกฟีเจอร์', 'color' => '#FFFFFFCC', 'size' => 'xs', 'margin' => 'sm'],
                        ],
                    ],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl', 'spacing' => 'sm',
                'contents' => [
                    // ═══ บริการดูดวง ═══ (การ์ด "ดูดวงฟรี" ซ่อนเมื่อ isFreeReadingEnabled() = false)
                    ['type' => 'text', 'text' => '🔮 บริการดูดวง', 'size' => 'md', 'weight' => 'bold', 'color' => $primaryColor],

                    ...$fortuneServiceCards,

                    ['type' => 'separator', 'margin' => 'md', 'color' => '#E1BEE7'],

                    // ═══ ฟีเจอร์อื่นๆ ═══
                    ['type' => 'text', 'text' => '📋 ฟีเจอร์อื่นๆ', 'size' => 'md', 'weight' => 'bold', 'color' => $primaryColor, 'margin' => 'sm'],

                    // เช็คสิทธิ์ / Wallet
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'sm', 'paddingAll' => 'sm',
                        'backgroundColor' => '#E8F5E9', 'cornerRadius' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '💰', 'size' => 'md', 'flex' => 0],
                            [
                                'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'sm',
                                'contents' => [
                                    ['type' => 'text', 'text' => 'สิทธิ์ / Wallet', 'size' => 'sm', 'weight' => 'bold'],
                                    ['type' => 'text', 'text' => 'กดปุ่ม "สิทธิ์/Wallet" ดูยอด Wallet รายได้ค่าคอม และสิทธิ์ดูดวงคงเหลือ', 'size' => 'xs', 'color' => '#888888', 'wrap' => true],
                                ],
                            ],
                        ],
                    ],

                    // ดูคำทำนายล่าสุด
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'sm', 'paddingAll' => 'sm',
                        'backgroundColor' => '#F3E5F5', 'cornerRadius' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '📖', 'size' => 'md', 'flex' => 0],
                            [
                                'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'sm',
                                'contents' => [
                                    ['type' => 'text', 'text' => 'ดูคำทำนายล่าสุด', 'size' => 'sm', 'weight' => 'bold'],
                                    ['type' => 'text', 'text' => 'พิมพ์ "ดูคำทำนาย" หรือกดปุ่มคำทำนายล่าสุดที่เมนูด้านล่าง', 'size' => 'xs', 'color' => '#888888', 'wrap' => true],
                                ],
                            ],
                        ],
                    ],

                    // ดูบัญชีธนาคาร
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'sm', 'paddingAll' => 'sm',
                        'backgroundColor' => '#E3F2FD', 'cornerRadius' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '🏦', 'size' => 'md', 'flex' => 0],
                            [
                                'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'sm',
                                'contents' => [
                                    ['type' => 'text', 'text' => 'ดูบัญชีธนาคาร', 'size' => 'sm', 'weight' => 'bold'],
                                    ['type' => 'text', 'text' => 'พิมพ์ "บัญชี" หรือ "ดูบัญชี" ดูเลขบัญชีสำหรับโอนเงิน', 'size' => 'xs', 'color' => '#888888', 'wrap' => true],
                                ],
                            ],
                        ],
                    ],

                    // แนะนำเพื่อน
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'sm', 'paddingAll' => 'sm',
                        'backgroundColor' => '#FFF3E0', 'cornerRadius' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '👥', 'size' => 'md', 'flex' => 0],
                            [
                                'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'sm',
                                'contents' => [
                                    ['type' => 'text', 'text' => 'แนะนำเพื่อน รับค่าคอม', 'size' => 'sm', 'weight' => 'bold'],
                                    ['type' => 'text', 'text' => 'แชร์ให้เพื่อนมาดูดวง รับค่าคอมมิชชั่นทุกยอด', 'size' => 'xs', 'color' => '#888888', 'wrap' => true],
                                ],
                            ],
                        ],
                    ],

                    // ยกเลิก
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'sm', 'paddingAll' => 'sm',
                        'backgroundColor' => '#FFEBEE', 'cornerRadius' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '❌', 'size' => 'md', 'flex' => 0],
                            [
                                'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'sm',
                                'contents' => [
                                    ['type' => 'text', 'text' => 'ยกเลิกการดูดวง', 'size' => 'sm', 'weight' => 'bold'],
                                    ['type' => 'text', 'text' => 'พิมพ์ "ยกเลิก" เพื่อหยุดการดูดวงปัจจุบัน', 'size' => 'xs', 'color' => '#888888', 'wrap' => true],
                                ],
                            ],
                        ],
                    ],

                    ['type' => 'separator', 'margin' => 'md', 'color' => '#E1BEE7'],

                    // คำเตือนโอนเงิน
                    [
                        'type' => 'box', 'layout' => 'vertical', 'margin' => 'sm', 'paddingAll' => 'md',
                        'backgroundColor' => '#FFEBEE', 'cornerRadius' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '🚨 คำเตือนการโอนเงิน', 'size' => 'sm', 'weight' => 'bold', 'color' => '#C62828'],
                            ['type' => 'text', 'text' => 'ระบบจะแจ้งยอดพร้อมทศนิยม เช่น 49.37 บาท ต้องโอนให้ตรงทศนิยมเท่านั้น! ถ้าไม่ตรง ระบบจะไม่ส่งคำทำนาย', 'size' => 'xs', 'color' => '#B71C1C', 'margin' => 'sm', 'wrap' => true],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => $primaryColor, 'height' => 'sm',
                        'action' => ['type' => 'message', 'label' => '🔮 เริ่มดูดวง', 'text' => 'ดูดวง']],
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm',
                        'action' => ['type' => 'postback', 'label' => '📊 เช็คสิทธิ์ / Wallet', 'data' => 'action=check_remaining']],
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm',
                        'action' => ['type' => 'uri', 'label' => '📤 แชร์ให้เพื่อน',
                            'uri' => 'https://line.me/R/nv/recommendOA/'.($this->settings->line_bot_basic_id ?? config('services.line.bot_basic_id', '@002dqcls'))]],
                ],
            ],
        ];
    }

    // =====================================================================
    // Rich Menu API Methods
    // =====================================================================

    /**
     * สร้าง Rich Menu บน LINE Platform
     *
     * @param  array  $data  ข้อมูล Rich Menu (size, selected, name, chatBarText, areas)
     * @return string|null Rich Menu ID หรือ null ถ้าไม่สำเร็จ
     */
    public function createRichMenu(array $data): ?string
    {
        try {
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(10)
                ->connectTimeout(5)
                ->post(self::API_ENDPOINT.'/richmenu', $data);

            if ($response->successful()) {
                $richMenuId = $response->json('richMenuId');
                Log::info('LINE Rich Menu: สร้างสำเร็จ', ['rich_menu_id' => $richMenuId]);

                return $richMenuId;
            }

            Log::error('LINE Rich Menu: สร้างไม่สำเร็จ', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('LINE Rich Menu: Exception ขณะสร้าง', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * อัปโหลดภาพ Rich Menu (PNG/JPEG, max 1MB)
     *
     * 🖼️ (2026-07-25) รองรับ JPEG — พื้นหลังรูปถ่าย (richmenu-bg) export PNG จะเกิน 1MB
     *   ตรวจ magic bytes อัตโนมัติ: \xFF\xD8 = JPEG, อื่น = PNG
     *
     * @param  string  $richMenuId  Rich Menu ID ที่สร้างไว้
     * @param  string  $pngBinary  ข้อมูลไบนารีของภาพ (PNG หรือ JPEG)
     * @return bool สำเร็จหรือไม่
     */
    public function uploadRichMenuImage(string $richMenuId, string $pngBinary): bool
    {
        try {
            // ตรวจชนิดภาพจาก magic bytes
            $contentType = str_starts_with($pngBinary, "\xFF\xD8") ? 'image/jpeg' : 'image/png';

            // ใช้ api-data.line.me (ไม่ใช่ api.line.me) สำหรับอัปโหลดไฟล์
            $response = Http::withToken($this->channelAccessToken)
                ->withHeaders(['Content-Type' => $contentType])
                ->timeout(30)
                ->connectTimeout(10)
                ->withBody($pngBinary, $contentType)
                ->post("https://api-data.line.me/v2/bot/richmenu/{$richMenuId}/content");

            if ($response->successful()) {
                Log::info('LINE Rich Menu: อัปโหลดภาพสำเร็จ', ['rich_menu_id' => $richMenuId]);

                return true;
            }

            Log::error('LINE Rich Menu: อัปโหลดภาพไม่สำเร็จ', [
                'rich_menu_id' => $richMenuId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (\Throwable $e) {
            Log::error('LINE Rich Menu: Exception ขณะอัปโหลดภาพ', [
                'rich_menu_id' => $richMenuId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ตั้ง Rich Menu เป็น default สำหรับทุก user
     *
     * @param  string  $richMenuId  Rich Menu ID
     * @return bool สำเร็จหรือไม่
     */
    public function setDefaultRichMenu(string $richMenuId): bool
    {
        try {
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(10)
                ->connectTimeout(5)
                ->post(self::API_ENDPOINT."/user/all/richmenu/{$richMenuId}");

            if ($response->successful()) {
                Log::info('LINE Rich Menu: ตั้ง default สำเร็จ', ['rich_menu_id' => $richMenuId]);

                return true;
            }

            Log::error('LINE Rich Menu: ตั้ง default ไม่สำเร็จ', [
                'rich_menu_id' => $richMenuId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (\Throwable $e) {
            Log::error('LINE Rich Menu: Exception ขณะตั้ง default', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * ลบ Rich Menu ออกจาก LINE Platform
     *
     * @param  string  $richMenuId  Rich Menu ID ที่ต้องการลบ
     * @return bool สำเร็จหรือไม่
     */
    public function deleteRichMenu(string $richMenuId): bool
    {
        try {
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(10)
                ->connectTimeout(5)
                ->delete(self::API_ENDPOINT."/richmenu/{$richMenuId}");

            if ($response->successful()) {
                Log::info('LINE Rich Menu: ลบสำเร็จ', ['rich_menu_id' => $richMenuId]);

                return true;
            }

            Log::error('LINE Rich Menu: ลบไม่สำเร็จ', [
                'rich_menu_id' => $richMenuId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (\Throwable $e) {
            Log::error('LINE Rich Menu: Exception ขณะลบ', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
