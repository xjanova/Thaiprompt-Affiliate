<?php

namespace App\Services;

use App\Models\FortuneTellingSetting;
use App\Models\FortuneReading;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Facebook Webhook Service
 *
 * จัดการ Facebook Messenger Platform API
 * รับ webhook events และส่ง messages
 */
class FacebookWebhookService
{
    protected $settings;
    protected $pageAccessToken;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
        $this->pageAccessToken = $this->settings->facebook_page_token;
    }

    /**
     * ดึงข้อมูลโปรไฟล์จาก Facebook Graph API
     */
    public function getUserProfile(string $facebookUserId): ?array
    {
        try {
            $response = Http::get("https://graph.facebook.com/v18.0/{$facebookUserId}", [
                'fields' => 'id,name,first_name,last_name,profile_pic',
                'access_token' => $this->pageAccessToken,
            ])->throw();

            return $response->json();
        } catch (Exception $e) {
            Log::error('Facebook Graph API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ดึงโพสล่าสุดของผู้ใช้ (ถ้ามี permission)
     */
    public function getUserPosts(string $facebookUserId, int $limit = 3): ?array
    {
        try {
            $response = Http::get("https://graph.facebook.com/v18.0/{$facebookUserId}/posts", [
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
     * ส่งข้อความผ่าน Messenger API
     */
    public function sendMessage(string $recipientId, string $message): bool
    {
        try {
            $response = Http::post('https://graph.facebook.com/v18.0/me/messages', [
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => $message],
                'access_token' => $this->pageAccessToken,
            ])->throw();

            Log::info('ส่งข้อความสำเร็จ', ['recipient' => $recipientId]);
            return true;
        } catch (Exception $e) {
            Log::error('ส่งข้อความไม่สำเร็จ: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ตอบกลับในคอมเมนต์
     */
    public function replyToComment(string $commentId, string $message): bool
    {
        try {
            $response = Http::post("https://graph.facebook.com/v18.0/{$commentId}/comments", [
                'message' => $message,
                'access_token' => $this->pageAccessToken,
            ])->throw();

            Log::info('ตอบคอมเมนต์สำเร็จ', ['comment_id' => $commentId]);
            return true;
        } catch (Exception $e) {
            Log::error('ตอบคอมเมนต์ไม่สำเร็จ: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * แยกคำถามจากข้อความ
     * รูปแบบ: "ดูดวง เรื่องความรัก เรื่องการเงิน เรื่องสุขภาพ"
     */
    public function parseQuestions(string $message): ?array
    {
        // ตรวจสอบว่าข้อความขึ้นต้นด้วย "ดูดวง" หรือไม่
        if (!preg_match('/^ดูดวง/u', trim($message))) {
            return null;
        }

        // ลบคำว่า "ดูดวง" ออก
        $text = preg_replace('/^ดูดวง\s*/u', '', trim($message));

        // แยกคำถามตามเครื่องหมาย หรือ ขึ้นบรรทัดใหม่
        $questions = preg_split('/[\n,]/', $text);
        
        // กรองคำถามที่ว่าง
        $questions = array_filter(array_map('trim', $questions));

        // ต้องมีอย่างน้อย 1 คำถาม ไม่เกิน 5 คำถาม
        if (count($questions) < 1 || count($questions) > 5) {
            return null;
        }

        return array_values($questions);
    }

    /**
     * ตรวจสอบว่าผู้ใช้ใช้งานครบจำนวนฟรีหรือยัง
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
     * สร้างข้อความเมื่อครบจำนวนฟรี
     */
    public function getLimitExceededMessage(): string
    {
        $message = $this->settings->limit_exceeded_message ?? 
            "คุณได้ใช้งานครบจำนวนฟรีวันนี้แล้ว ({$this->settings->max_free_readings} ครั้ง)\n\n";

        if ($this->settings->reading_price > 0) {
            $message .= "💰 ราคาการทำนายต่อครั้ง: {$this->settings->reading_price} บาท\n\n";
            
            if ($this->settings->payment_qr_image) {
                $message .= "📸 โอนเงินผ่าน QR Code:\n";
                $message .= $this->settings->getPaymentQrUrl() . "\n\n";
            }
        }

        $message .= "หรือสมัครสมาชิกเพื่อใช้งานไม่จำกัด: " . url('/register');

        return $message;
    }

    /**
     * ส่งคำทำนายกลับไปยังผู้ใช้
     */
    public function sendFortuneTelling(
        FortuneReading $reading,
        string $response
    ): bool {
        $message = "🔮 คำทำนายสำหรับคุณ\n\n{$response}\n\n";
        $message .= "---\n";
        $message .= "ให้คะแนนความพึงพอใจ: " . url("/fortune/{$reading->id}/rate");

        if ($this->settings->respond_in_comment && $reading->facebook_comment_id) {
            return $this->replyToComment($reading->facebook_comment_id, $message);
        } else {
            return $this->sendMessage($reading->facebook_user_id, $message);
        }
    }
}
