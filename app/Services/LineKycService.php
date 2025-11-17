<?php

namespace App\Services;

use App\Models\User;
use App\Models\KycVerification;
use App\Services\OCR\ThaiIdCardOcrService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * LINE KYC Service
 *
 * จัดการกระบวนการ KYC (ยืนยันตัวตน) ผ่าน LINE Messaging API
 * รองรับการส่งรูปภาพบัตรประชาชนและรูปถ่ายตัวเอง (Selfie) ผ่าน LINE Chat
 *
 * Features:
 * - รับรูปภาพจาก LINE Messaging API
 * - ดาวน์โหลดและบันทึกรูปภาพ
 * - ส่งไปยัง OCR Service เพื่อแปลงข้อความ
 * - สร้าง KYC Verification record
 * - ส่ง notification และ Rich Menu กลับไปยัง LINE
 *
 * @package App\Services
 */
class LineKycService
{
    /**
     * OCR Service สำหรับแปลงรูปบัตรประชาชนเป็นข้อความ
     */
    protected ThaiIdCardOcrService $ocrService;

    /**
     * Notification Service สำหรับส่งการแจ้งเตือน
     */
    protected NotificationService $notificationService;

    /**
     * LINE Channel Access Token
     */
    protected ?string $channelAccessToken;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ocrService = new ThaiIdCardOcrService();
        $this->notificationService = app(NotificationService::class);
        $this->channelAccessToken = config('services.line.channel_access_token');
    }

    /**
     * เริ่มกระบวนการ KYC ผ่าน LINE
     *
     * ส่ง Rich Menu และคำแนะนำให้ผู้ใช้ส่งรูปบัตรประชาชน
     *
     * @param string $lineUserId LINE User ID
     * @param User $user User model
     * @return array ผลลัพธ์การดำเนินการ
     */
    public function startKycProcess(string $lineUserId, User $user): array
    {
        try {
            // ตรวจสอบสถานะ KYC ปัจจุบัน
            if ($user->kyc_status === 'approved') {
                return [
                    'success' => false,
                    'message' => '✅ คุณผ่านการยืนยันตัวตนแล้ว\n\nไม่จำเป็นต้องทำ KYC ใหม่อีกครั้ง',
                    'already_verified' => true,
                ];
            }

            if ($user->kyc_status === 'pending') {
                return [
                    'success' => false,
                    'message' => '⏳ KYC ของคุณอยู่ระหว่างการตรวจสอบ\n\nโปรดรอผลการตรวจสอบจากแอดมิน',
                    'pending_review' => true,
                ];
            }

            // สร้างข้อความแนะนำ
            $message = $this->getStartKycMessage();

            // ส่งข้อความไปยัง LINE
            $this->sendLineMessage($lineUserId, $message);

            return [
                'success' => true,
                'message' => 'ส่งคำแนะนำ KYC ไปยัง LINE แล้ว',
            ];

        } catch (\Exception $e) {
            Log::error('LINE KYC: Start process failed', [
                'line_user_id' => $lineUserId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเริ่มกระบวนการ KYC',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * รับและประมวลผลรูปภาพจาก LINE
     *
     * @param string $messageId LINE Message ID ของรูปภาพ
     * @param string $lineUserId LINE User ID
     * @param User $user User model
     * @param string $imageType ประเภทรูปภาพ: 'id_card' หรือ 'selfie'
     * @return array ผลลัพธ์การดำเนินการ
     */
    public function processImageFromLine(
        string $messageId,
        string $lineUserId,
        User $user,
        string $imageType = 'id_card'
    ): array {
        try {
            // 1. ดาวน์โหลดรูปภาพจาก LINE
            $imageData = $this->downloadLineImage($messageId);

            if (!$imageData['success']) {
                return $imageData;
            }

            // 2. บันทึกรูปภาพลง Storage
            $savedImage = $this->saveImage($imageData['content'], $user->id, $imageType);

            if (!$savedImage['success']) {
                return $savedImage;
            }

            $imagePath = $savedImage['path'];

            // 3. ดำเนินการตามประเภทรูปภาพ
            if ($imageType === 'id_card') {
                return $this->processIdCardImage($user, $imagePath, $lineUserId);
            } elseif ($imageType === 'selfie') {
                return $this->processSelfieImage($user, $imagePath, $lineUserId);
            }

            return [
                'success' => false,
                'message' => 'ประเภทรูปภาพไม่ถูกต้อง',
            ];

        } catch (\Exception $e) {
            Log::error('LINE KYC: Process image failed', [
                'message_id' => $messageId,
                'line_user_id' => $lineUserId,
                'user_id' => $user->id,
                'image_type' => $imageType,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'ไม่สามารถประมวลผลรูปภาพได้',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ประมวลผลรูปบัตรประชาชน
     *
     * - ใช้ OCR แปลงข้อความจากบัตร
     * - สร้าง/อัพเดท KYC Verification record
     * - ส่ง notification กลับไปยัง LINE
     *
     * @param User $user
     * @param string $imagePath
     * @param string $lineUserId
     * @return array
     */
    protected function processIdCardImage(User $user, string $imagePath, string $lineUserId): array
    {
        // 1. ใช้ OCR แปลงข้อความจากบัตร
        $ocrResult = $this->ocrService->extractData($imagePath);

        // 2. ตรวจสอบผลลัพธ์ OCR
        if (!$ocrResult['success']) {
            $errorMessage = "❌ ไม่สามารถอ่านข้อมูลจากบัตรประชาชนได้\n\n";
            $errorMessage .= "สาเหตุ: {$ocrResult['error']}\n\n";
            $errorMessage .= "คำแนะนำ: {$ocrResult['suggestion']}";

            $this->sendLineMessage($lineUserId, $errorMessage);

            return [
                'success' => false,
                'message' => $ocrResult['error'],
                'ocr_failed' => true,
            ];
        }

        // 3. สร้างหรืออัพเดท KYC Verification
        $kyc = KycVerification::updateOrCreate(
            ['user_id' => $user->id],
            [
                'id_card_image' => $imagePath,
                'extracted_data' => $ocrResult['data'] ?? null,
                'status' => 'pending',
                'submitted_at' => now(),
            ]
        );

        // 4. ส่ง notification กลับไปยัง LINE
        $successMessage = "✅ รับรูปบัตรประชาชนเรียบร้อยแล้ว!\n\n";

        if (!empty($ocrResult['data'])) {
            $successMessage .= "📝 ข้อมูลที่อ่านได้:\n";
            $successMessage .= $this->formatOcrDataForLine($ocrResult['data']);
            $successMessage .= "\n\n";
        }

        $successMessage .= "📸 ขั้นตอนต่อไป:\nกรุณาส่งรูปถ่ายตัวเอง (Selfie) ถือบัตรประชาชน";

        $this->sendLineMessage($lineUserId, $successMessage);

        // 5. บันทึก log
        Log::info('LINE KYC: ID card processed', [
            'user_id' => $user->id,
            'line_user_id' => $lineUserId,
            'ocr_success' => $ocrResult['success'],
            'kyc_id' => $kyc->id,
        ]);

        return [
            'success' => true,
            'message' => 'ประมวลผลรูปบัตรประชาชนสำเร็จ',
            'kyc_id' => $kyc->id,
            'ocr_data' => $ocrResult['data'] ?? null,
        ];
    }

    /**
     * ประมวลผลรูป Selfie
     *
     * @param User $user
     * @param string $imagePath
     * @param string $lineUserId
     * @return array
     */
    protected function processSelfieImage(User $user, string $imagePath, string $lineUserId): array
    {
        // 1. ตรวจสอบว่ามี KYC record อยู่แล้วหรือไม่
        $kyc = KycVerification::where('user_id', $user->id)->first();

        if (!$kyc) {
            $message = "❌ กรุณาส่งรูปบัตรประชาชนก่อน\n\nพิมพ์ 'KYC' เพื่อเริ่มต้นใหม่";
            $this->sendLineMessage($lineUserId, $message);

            return [
                'success' => false,
                'message' => 'ยังไม่มีรูปบัตรประชาชน',
            ];
        }

        // 2. อัพเดท Selfie image
        $kyc->update([
            'selfie_image' => $imagePath,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        // 3. อัพเดทสถานะ user
        $user->update([
            'kyc_status' => 'pending',
        ]);

        // 4. ส่ง notification ไปยัง LINE
        $successMessage = "🎉 ส่ง KYC เรียบร้อยแล้ว!\n\n";
        $successMessage .= "✅ รูปบัตรประชาชน: อัพโหลดแล้ว\n";
        $successMessage .= "✅ รูปถ่ายตัวเอง: อัพโหลดแล้ว\n\n";
        $successMessage .= "⏳ สถานะ: รอการตรวจสอบ\n\n";
        $successMessage .= "แอดมินจะตรวจสอบภายใน 1-3 วันทำการ\nเราจะแจ้งเตือนคุณเมื่อมีผลการตรวจสอบ";

        $this->sendLineMessage($lineUserId, $successMessage);

        // 5. ส่ง notification ไปยัง Admin
        $this->notificationService->notifyAdminsNewKycSubmission($kyc);

        // 6. บันทึก log
        Log::info('LINE KYC: Selfie processed', [
            'user_id' => $user->id,
            'line_user_id' => $lineUserId,
            'kyc_id' => $kyc->id,
        ]);

        return [
            'success' => true,
            'message' => 'KYC สมบูรณ์ รอการตรวจสอบ',
            'kyc_id' => $kyc->id,
        ];
    }

    /**
     * ดาวน์โหลดรูปภาพจาก LINE Messaging API
     *
     * @param string $messageId LINE Message ID
     * @return array
     */
    protected function downloadLineImage(string $messageId): array
    {
        try {
            if (!$this->channelAccessToken) {
                throw new \Exception('LINE Channel Access Token not configured');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->channelAccessToken,
            ])->get("https://api-data.line.me/v2/bot/message/{$messageId}/content");

            if (!$response->successful()) {
                Log::error('LINE KYC: Failed to download image', [
                    'message_id' => $messageId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'ไม่สามารถดาวน์โหลดรูปภาพจาก LINE ได้',
                ];
            }

            return [
                'success' => true,
                'content' => $response->body(),
                'content_type' => $response->header('Content-Type'),
            ];

        } catch (\Exception $e) {
            Log::error('LINE KYC: Download image exception', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดาวน์โหลดรูปภาพ',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * บันทึกรูปภาพลง Storage
     *
     * @param string $imageContent ข้อมูลรูปภาพ (binary)
     * @param int $userId User ID
     * @param string $type ประเภทรูป: 'id_card' หรือ 'selfie'
     * @return array
     */
    protected function saveImage(string $imageContent, int $userId, string $type): array
    {
        try {
            // สร้างชื่อไฟล์แบบ unique
            $filename = sprintf(
                'kyc/%s/%s_%s.jpg',
                $userId,
                $type,
                Str::random(16)
            );

            // บันทึกไฟล์
            Storage::disk('public')->put($filename, $imageContent);

            return [
                'success' => true,
                'path' => $filename,
                'url' => Storage::disk('public')->url($filename),
            ];

        } catch (\Exception $e) {
            Log::error('LINE KYC: Save image failed', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'ไม่สามารถบันทึกรูปภาพได้',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ส่งข้อความไปยัง LINE User
     *
     * @param string $lineUserId LINE User ID
     * @param string $message ข้อความที่จะส่ง
     * @return bool
     */
    protected function sendLineMessage(string $lineUserId, string $message): bool
    {
        try {
            if (!$this->channelAccessToken) {
                throw new \Exception('LINE Channel Access Token not configured');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->channelAccessToken,
                'Content-Type' => 'application/json',
            ])->post('https://api.line.me/v2/bot/message/push', [
                'to' => $lineUserId,
                'messages' => [
                    [
                        'type' => 'text',
                        'text' => $message,
                    ],
                ],
            ]);

            if (!$response->successful()) {
                Log::error('LINE KYC: Failed to send message', [
                    'line_user_id' => $lineUserId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('LINE KYC: Send message exception', [
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * ส่ง Flex Message แบบสวยงามไปยัง LINE
     *
     * @param string $lineUserId
     * @param array $flexMessage
     * @return bool
     */
    protected function sendLineFlexMessage(string $lineUserId, array $flexMessage): bool
    {
        try {
            if (!$this->channelAccessToken) {
                throw new \Exception('LINE Channel Access Token not configured');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->channelAccessToken,
                'Content-Type' => 'application/json',
            ])->post('https://api.line.me/v2/bot/message/push', [
                'to' => $lineUserId,
                'messages' => [
                    [
                        'type' => 'flex',
                        'altText' => $flexMessage['altText'] ?? 'KYC Notification',
                        'contents' => $flexMessage['contents'],
                    ],
                ],
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('LINE KYC: Send flex message failed', [
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * แปลงข้อมูล OCR เป็นข้อความสำหรับแสดงใน LINE
     *
     * @param array $ocrData
     * @return string
     */
    protected function formatOcrDataForLine(array $ocrData): string
    {
        $formatted = "";

        $fields = [
            'id_number' => '🆔 เลขบัตร',
            'thai_name' => '👤 ชื่อ (ไทย)',
            'english_name' => '👤 ชื่อ (อังกฤษ)',
            'birth_date' => '🎂 วันเกิด',
            'issue_date' => '📅 วันออกบัตร',
            'expiry_date' => '📅 วันหมดอายุ',
        ];

        foreach ($fields as $key => $label) {
            if (!empty($ocrData[$key])) {
                $formatted .= "{$label}: {$ocrData[$key]}\n";
            }
        }

        return $formatted;
    }

    /**
     * ข้อความแนะนำเริ่มต้น KYC
     *
     * @return string
     */
    protected function getStartKycMessage(): string
    {
        return <<<MESSAGE
🔐 ยืนยันตัวตน (KYC)

เพื่อความปลอดภัยและเพิ่มความน่าเชื่อถือของบัญชี กรุณาทำตามขั้นตอนต่อไปนี้:

📋 ขั้นตอนการยืนยันตัวตน:

1️⃣ ส่งรูปบัตรประชาชน (ด้านหน้า)
   • ถ่ายรูปให้ชัดเจน
   • เห็นข้อความทั้งหมด
   • ไม่มีแสงสะท้อน

2️⃣ ส่งรูปถ่ายตัวเอง (Selfie)
   • ถือบัตรประชาชนข้างใบหน้า
   • เห็นใบหน้าชัดเจน
   • แสงสว่างเพียงพอ

⏱️ ระยะเวลาตรวจสอบ: 1-3 วันทำการ

กรุณาส่งรูปบัตรประชาชนมาเลยครับ 👇
MESSAGE;
    }

    /**
     * ส่งการแจ้งเตือนผลการตรวจสอบ KYC ไปยัง LINE
     *
     * @param User $user
     * @param KycVerification $kyc
     * @param string $status 'approved' หรือ 'rejected'
     * @return bool
     */
    public function notifyKycResult(User $user, KycVerification $kyc, string $status): bool
    {
        // ดึง LINE User ID จาก user
        $lineUserId = $user->line_user_id;

        if (!$lineUserId) {
            Log::warning('LINE KYC: Cannot notify - LINE User ID not found', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        if ($status === 'approved') {
            $message = "🎉 ยินดีด้วย! KYC ผ่านการตรวจสอบ\n\n";
            $message .= "✅ บัญชีของคุณได้รับการยืนยันตัวตนแล้ว\n";
            $message .= "✨ ตอนนี้คุณสามารถใช้งานฟีเจอร์ครบทุกอย่างได้แล้ว";
        } else {
            $message = "❌ KYC ไม่ผ่านการตรวจสอบ\n\n";
            $message .= "สาเหตุ: {$kyc->rejection_reason}\n\n";
            $message .= "📝 กรุณาทำ KYC ใหม่อีกครั้ง\nพิมพ์ 'KYC' เพื่อเริ่มต้นใหม่";
        }

        return $this->sendLineMessage($lineUserId, $message);
    }
}
