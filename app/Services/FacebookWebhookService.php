<?php

namespace App\Services;

use App\Models\FortuneTellingSetting;
use App\Models\FortuneReading;
use App\Models\FortuneResponseTemplate;
use App\Contracts\MessagingPlatformInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

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
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
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
     * @param string $recipientId Facebook User ID
     * @param string $message ข้อความที่ต้องการส่ง
     * @return bool สำเร็จหรือไม่
     */
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

        foreach ($chunks as $chunkIndex => $chunk) {
            $sent = false;

            // ลองส่งแต่ละ chunk สูงสุด 2 ครั้ง
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $response = Http::timeout(30)
                        ->post($this->graphUrl('/me/messages'), [
                            'recipient' => ['id' => $recipientId],
                            'message' => ['text' => $chunk],
                            'messaging_type' => 'RESPONSE',
                            'access_token' => $this->pageAccessToken,
                        ]);

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
                        'token_prefix' => substr($this->pageAccessToken, 0, 10) . '...',
                        'chunk' => $chunkIndex + 1,
                    ]);

                    // ถ้าเป็น token error (190) หรือ permission error (10) ไม่ต้อง retry
                    if (in_array($errorCode, [190, 10, 200])) {
                        Log::error('❌ Facebook Token/Permission Error - หยุด retry', [
                            'error_code' => $errorCode,
                            'message' => $errorMsg,
                        ]);
                        return false;
                    }

                    if ($attempt < $maxRetries) {
                        usleep(1000000); // รอ 1 วินาทีก่อน retry
                    }

                } catch (Exception $e) {
                    Log::error("❌ ส่งข้อความไม่สำเร็จ (ครั้งที่ {$attempt}): " . $e->getMessage(), [
                        'recipient' => $recipientId,
                        'chunk' => $chunkIndex + 1,
                    ]);

                    if ($attempt < $maxRetries) {
                        usleep(1000000); // รอ 1 วินาที
                    }
                }
            }

            if (!$sent) {
                Log::error('❌ ส่งข้อความล้มเหลวหลังลอง ' . $maxRetries . ' ครั้ง', [
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
     * @param string $recipientId Facebook User ID
     * @param string $imageUrl URL ของรูปภาพ (ต้องเป็น HTTPS public URL)
     * @param string|null $caption ข้อความกำกับรูป (ส่งแยก message ถ้ามี)
     * @return bool สำเร็จหรือไม่
     */
    public function sendImage(string $recipientId, string $imageUrl, ?string $previewUrl = null): bool
    {
        try {
            // ส่งรูปภาพ
            Http::timeout(30)
                ->post($this->graphUrl('/me/messages'), [
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
                    'messaging_type' => 'RESPONSE',
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            // ส่งข้อความกำกับรูป (ถ้ามี previewUrl ใช้เป็น caption)
            if (!empty($previewUrl)) {
                $this->sendMessage($recipientId, $previewUrl);
            }

            Log::info('ส่งรูปภาพสำเร็จ', [
                'recipient' => $recipientId,
                'image_url' => $imageUrl,
            ]);
            return true;
        } catch (Exception $e) {
            Log::error('ส่งรูปภาพไม่สำเร็จ: ' . $e->getMessage(), [
                'recipient' => $recipientId,
                'image_url' => $imageUrl,
            ]);
            return false;
        }
    }

    /**
     * ส่ง typing indicator (แสดงจุดสามจุดว่ากำลังพิมพ์)
     *
     * @param string $recipientId Facebook User ID
     * @param bool $on เปิด/ปิด typing indicator
     * @return void
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
            Log::debug('ส่ง typing indicator ไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    /**
     * ส่งข้อความพร้อม Quick Reply buttons
     *
     * @param string $recipientId Facebook User ID
     * @param string $message ข้อความหลัก
     * @param array $quickReplies ปุ่ม quick reply [['title' => 'ข้อความ', 'payload' => 'DATA']]
     * @return bool
     */
    public function sendQuickReplies(string $recipientId, string $message, array $quickReplies): bool
    {
        try {
            $formattedReplies = array_map(function ($reply) {
                return [
                    'content_type' => 'text',
                    'title' => mb_substr($reply['title'], 0, 20),
                    'payload' => $reply['payload'] ?? $reply['title'],
                ];
            }, array_slice($quickReplies, 0, 13)); // Facebook จำกัด 13 quick replies

            Http::timeout(30)
                ->post($this->graphUrl('/me/messages'), [
                    'recipient' => ['id' => $recipientId],
                    'message' => [
                        'text' => $message,
                        'quick_replies' => $formattedReplies,
                    ],
                    'messaging_type' => 'RESPONSE',
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            return true;
        } catch (Exception $e) {
            Log::error('ส่ง quick replies ไม่สำเร็จ: ' . $e->getMessage());
            // Fallback: ส่งข้อความธรรมดา
            return $this->sendMessage($recipientId, $message);
        }
    }

    /**
     * ตอบกลับในคอมเมนต์
     *
     * @param string $commentId Comment ID
     * @param string $message ข้อความตอบกลับ
     * @return bool
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
            Log::error('ตอบคอมเมนต์ไม่สำเร็จ: ' . $e->getMessage(), [
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
     *
     * @param string $facebookUserId
     * @return array|null
     */
    public function getUserProfile(string $facebookUserId): ?array
    {
        try {
            // ดึงข้อมูลพื้นฐาน + ลอง fields เพิ่มเติม (gender, birthday, locale)
            // หมายเหตุ: gender/birthday/locale อาจไม่ได้รับจาก PSID เนื่องจาก Facebook API restrictions
            // แต่ใส่ไว้เผื่อ App ได้รับ permission แล้ว
            $response = Http::timeout(15)
                ->get($this->graphUrl("/{$facebookUserId}"), [
                    'fields' => 'id,name,first_name,last_name,profile_pic,gender,birthday,locale,timezone',
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            $profile = $response->json();

            // ถ้าได้ birthday มา → คำนวณอายุ
            if (!empty($profile['birthday'])) {
                try {
                    $birthDate = \Carbon\Carbon::parse($profile['birthday']);
                    $profile['age'] = $birthDate->age;
                    $profile['birth_date_formatted'] = $birthDate->format('Y-m-d');
                } catch (\Exception $e) {
                    // birthday format อาจเป็นแค่ MM/DD
                }
            }

            Log::info('ดึงโปรไฟล์ผู้ใช้สำเร็จ', [
                'user_id' => $facebookUserId,
                'has_gender' => !empty($profile['gender']),
                'has_birthday' => !empty($profile['birthday']),
                'has_locale' => !empty($profile['locale']),
            ]);

            return $profile;
        } catch (Exception $e) {
            Log::warning('ไม่สามารถดึงโปรไฟล์ผู้ใช้ได้: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ดึงโพสล่าสุดของผู้ใช้ (ถ้ามี permission)
     *
     * @param string $facebookUserId
     * @param int $limit จำนวนโพสที่ต้องการ
     * @return array|null
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
            Log::warning('ไม่สามารถดึงโพสของผู้ใช้ได้: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ดึงรูปภาพจาก Messenger attachment
     *
     * @param array $attachments Facebook message attachments array
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
     *
     * @param string $message
     * @return bool
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
     *
     * @param string $message
     * @return array|null
     */
    public function parseQuestions(string $message): ?array
    {
        // ตรวจสอบว่าข้อความขึ้นต้นด้วย "ดูดวง" หรือไม่
        if (!preg_match('/^ดูดวง/u', trim($message))) {
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

        return !empty($questions) ? array_values($questions) : null;
    }

    /**
     * แยกวันเกิดจากข้อความผู้ใช้
     *
     * รองรับรูปแบบ:
     * - "เกิด 15 มกราคม 2540" / "เกิดวันที่ 15/01/2540"
     * - "วันเกิด 15-01-1997" / "วันเกิด 15 ม.ค. 40"
     * - "เกิด 15/1/40" / "เกิด 15/1/97"
     *
     * @param string $message ข้อความจากผู้ใช้
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
        if (preg_match('/(?:เกิด|วันเกิด)(?:วันที่)?\s*(\d{1,2})\s*(' . $monthNames . ')\s*(\d{2,4})/u', $message, $matches)) {
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
     * @param int $day วัน
     * @param int $month เดือน (1-12)
     * @param int $year ปี (พ.ศ. หรือ ค.ศ. แบบ 2 หรือ 4 หลัก)
     * @return string|null
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
     *
     * @param string $facebookUserId
     * @return array
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
     *
     * @param string $facebookUserId
     * @return array
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
     * @param string|null $userName ชื่อผู้ใช้
     * @return string
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
            $message .= "🌟 หรือลอง 'ดูดวงละเอียด' เพื่อรับคำทำนายเชิงลึก\n";
        }

        if ($this->settings->payment_qr_image) {
            $message .= "\n📸 โอนเงินผ่าน QR Code:\n";
            $message .= $this->settings->getPaymentQrUrl() . "\n";
        }

        $message .= "\n📱 สมัครสมาชิกเพื่อใช้งานไม่จำกัด: " . url('/register');

        return $message;
    }

    /**
     * สร้างข้อความเมื่อครบจำนวนฟรีเชิงลึก
     *
     * ใช้เทมเพลต limit_exceeded ถ้ามี มิฉะนั้นใช้ข้อความ hardcoded
     *
     * @param string|null $userName ชื่อผู้ใช้
     * @return string
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
                $message .= $this->settings->getPaymentQrUrl() . "\n\n";
            }
            $message .= "📱 ชำระเงิน/สมัครสมาชิก: " . url('/register');
        }

        return $message;
    }

    /**
     * ส่งข้อความต้อนรับจากเทมเพลต
     *
     * @param string $recipientId Facebook User ID
     * @return bool
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
        return $this->sendMessage($recipientId,
            "🔮 สวัสดีค่ะ ยินดีต้อนรับสู่ระบบดูดวง!\n\n" .
            "พิมพ์: \"ดูดวง\" ตามด้วยคำถาม\n" .
            "🌟 พิมพ์: \"ดูดวงละเอียด\" เพื่อรับคำทำนายเชิงลึก"
        );
    }

    /**
     * ส่งข้อความแจ้งชำระเงินจากเทมเพลต (พร้อมรูป QR Code)
     *
     * @param string $recipientId Facebook User ID
     * @param string|null $userName ชื่อผู้ใช้
     * @return bool
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
            "💰 กรุณาชำระเงินเพื่อใช้งานต่อ\n" .
            "📱 สมัครสมาชิก: " . url('/register')
        );
    }

    /**
     * ส่งข้อความเมื่อเกิดข้อผิดพลาดจากเทมเพลต
     *
     * @param string $recipientId Facebook User ID
     * @return bool
     */
    public function sendErrorMessage(string $recipientId): bool
    {
        $template = FortuneResponseTemplate::getDefault('error');
        if ($template) {
            return $this->sendMessage($recipientId, $template->render());
        }

        return $this->sendMessage($recipientId,
            "😔 ขออภัยค่ะ ขณะนี้ระบบมีปัญหา\n" .
            "กรุณาลองใหม่อีกครั้งในอีกสักครู่ค่ะ\n" .
            "พิมพ์ \"ดูดวง\" เพื่อลองใหม่"
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
     * @param FortuneReading $reading
     * @param string $response คำทำนายจาก AI
     * @return bool
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
            $message .= "ให้คะแนนความพึงพอใจ: " . url("/fortune/{$reading->id}/rate");
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
     * @param string $payload Raw request body
     * @param string $signature X-Hub-Signature-256 header value
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $appSecret = $this->settings->facebook_app_secret;

        if (empty($appSecret) || empty($signature)) {
            Log::warning('Webhook signature verification skipped: missing app_secret or signature');
            return true; // อนุญาตผ่านถ้าไม่ได้ตั้งค่า (dev mode)
        }

        // Facebook ส่ง format: "sha256=xxxxx"
        if (!str_starts_with($signature, 'sha256=')) {
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
     * @param string $message ข้อความทั้งหมด
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
     * @param \Carbon\Carbon $date
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
     * @param string $path API path (เช่น /me/messages)
     * @return string
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
            // ตั้งค่า Get Started button และ Persistent Menu
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
                        'text' => "🔮 ยินดีต้อนรับสู่ระบบดูดวง AI\n\nกดปุ่ม \"เริ่มต้นใช้งาน\" เพื่อเริ่มดูดวงได้เลยค่ะ ✨",
                    ],
                    [
                        'locale' => 'th_TH',
                        'text' => "🔮 ยินดีต้อนรับสู่ระบบดูดวง AI\n\nกดปุ่ม \"เริ่มต้นใช้งาน\" เพื่อเริ่มดูดวงได้เลยค่ะ ✨",
                    ],
                ],

                // Persistent Menu - เมนูถาวรที่แสดงตลอดเวลา (ปุ่มลัดในแชท)
                'persistent_menu' => [
                    [
                        'locale' => 'default',
                        'composer_input_disabled' => false, // อนุญาตให้พิมพ์ข้อความได้
                        'call_to_actions' => [
                            [
                                'type' => 'postback',
                                'title' => '🔮 ดูดวงฟรี',
                                'payload' => 'MENU_FORTUNE',
                            ],
                            [
                                'type' => 'postback',
                                'title' => '💎 ดูดวงละเอียด',
                                'payload' => 'MENU_DEEP_FORTUNE',
                            ],
                            [
                                'type' => 'postback',
                                'title' => '📊 เช็คสิทธิ์ดูดวง',
                                'payload' => 'MENU_CHECK_REMAINING',
                            ],
                            [
                                'type' => 'postback',
                                'title' => '❓ วิธีใช้งาน',
                                'payload' => 'MENU_HELP',
                            ],
                        ],
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
                'message' => 'ตั้งค่าไม่สำเร็จ: ' . ($error['message'] ?? 'Unknown error'),
                'data' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error('Messenger Profile setup error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
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
                'message' => 'ลบไม่สำเร็จ: ' . ($response->json('error.message') ?? 'Unknown error'),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
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
                'message' => 'ดึงข้อมูลไม่สำเร็จ: ' . ($response->json('error.message') ?? 'Unknown error'),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
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
            Log::error('ส่ง Rich Message ไม่สำเร็จ: ' . $e->getMessage());
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
