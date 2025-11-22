<?php

namespace App\Jobs;

use App\Models\LineBroadcastMessage;
use App\Models\User;
use App\Services\LineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job สำหรับส่ง LINE Broadcast Message แบบอัตโนมัติ
 *
 * รองรับ:
 * - การส่งตามเวลาที่กำหนด (scheduled)
 * - การ retry อัตโนมัติเมื่อล้มเหลว (max 3 ครั้ง)
 * - การแบ่งส่งเป็น batch เพื่อไม่ให้ rate limit
 */
class SendScheduledBroadcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * จำนวนครั้งสูงสุดที่จะ retry
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Timeout ของ job (seconds)
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Broadcast message instance
     *
     * @var LineBroadcastMessage
     */
    protected $broadcast;

    /**
     * สร้าง job instance
     *
     * @param LineBroadcastMessage $broadcast
     * @return void
     */
    public function __construct(LineBroadcastMessage $broadcast)
    {
        $this->broadcast = $broadcast;
        $this->onQueue('broadcasts'); // ใช้ queue แยก
    }

    /**
     * Execute the job
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info("🚀 เริ่มส่ง broadcast: {$this->broadcast->name} (ID: {$this->broadcast->id})");

        // ตรวจสอบสถานะ
        if (!in_array($this->broadcast->status, ['draft', 'scheduled', 'failed'])) {
            Log::warning("⚠️ Broadcast {$this->broadcast->id} มีสถานะไม่เหมาะสมสำหรับการส่ง: {$this->broadcast->status}");
            return;
        }

        // อัปเดตสถานะเป็น sending
        $this->broadcast->update([
            'status' => 'sending',
            'sent_at' => now(),
        ]);

        try {
            // ดึงรายชื่อผู้รับ
            $recipients = $this->getRecipients();

            // อัปเดตจำนวนผู้รับ
            $this->broadcast->update([
                'total_recipients' => count($recipients),
            ]);

            Log::info("📊 พบผู้รับทั้งหมด: " . count($recipients) . " คน");

            // ส่งข้อความ
            $this->sendMessages($recipients);

            // อัปเดตสถานะเป็น sent
            $this->broadcast->update([
                'status' => 'sent',
                'error_message' => null,
            ]);

            Log::info("✅ ส่ง broadcast {$this->broadcast->id} สำเร็จ! ส่งได้ {$this->broadcast->sent_count}/{$this->broadcast->total_recipients}");

        } catch (\Exception $e) {
            Log::error("❌ เกิดข้อผิดพลาดในการส่ง broadcast {$this->broadcast->id}: " . $e->getMessage());

            // บันทึก error และเพิ่ม retry count
            $this->broadcast->update([
                'status' => 'failed',
                'retry_count' => $this->broadcast->retry_count + 1,
                'last_retry_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            // Throw exception เพื่อให้ Laravel retry job
            throw $e;
        }
    }

    /**
     * ดึงรายชื่อผู้รับตาม target_type
     *
     * @return array
     */
    protected function getRecipients(): array
    {
        switch ($this->broadcast->target_type) {
            case 'all':
                return User::whereNotNull('line_user_id')
                    ->pluck('line_user_id')
                    ->toArray();

            case 'users':
                return User::whereNotNull('line_user_id')
                    ->where('role', 'user')
                    ->pluck('line_user_id')
                    ->toArray();

            case 'sellers':
                return User::whereNotNull('line_user_id')
                    ->where('role', 'seller')
                    ->pluck('line_user_id')
                    ->toArray();

            case 'custom':
                return User::whereNotNull('line_user_id')
                    ->whereIn('id', $this->broadcast->target_users ?? [])
                    ->pluck('line_user_id')
                    ->toArray();

            default:
                return [];
        }
    }

    /**
     * ส่งข้อความไปยังผู้รับทั้งหมด
     *
     * @param array $recipients
     * @return void
     */
    protected function sendMessages(array $recipients): void
    {
        $lineService = new LineService();
        $sentCount = 0;
        $failedCount = 0;
        $batchSize = 100; // ส่งทีละ 100 คนเพื่อหลีกเลี่ยง rate limit

        // แบ่งผู้รับเป็น batches
        $batches = array_chunk($recipients, $batchSize);

        foreach ($batches as $batchIndex => $batch) {
            Log::info("📦 กำลังส่ง batch " . ($batchIndex + 1) . "/" . count($batches));

            foreach ($batch as $lineUserId) {
                try {
                    $success = $this->sendToRecipient($lineService, $lineUserId);

                    if ($success) {
                        $sentCount++;
                        $this->broadcast->incrementSent();
                    } else {
                        $failedCount++;
                        $this->broadcast->incrementFailed();
                    }

                } catch (\Exception $e) {
                    Log::error("❌ ส่งไปยัง {$lineUserId} ล้มเหลว: " . $e->getMessage());
                    $failedCount++;
                    $this->broadcast->incrementFailed();
                }
            }

            // หน่วงเวลาระหว่าง batch เพื่อหลีกเลี่ยง rate limit (0.5 วินาที)
            if ($batchIndex < count($batches) - 1) {
                usleep(500000);
            }
        }

        Log::info("📊 สรุปผลการส่ง - สำเร็จ: {$sentCount}, ล้มเหลว: {$failedCount}");
    }

    /**
     * ส่งข้อความไปยังผู้รับคนเดียว
     *
     * @param LineService $lineService
     * @param string $lineUserId
     * @return bool
     */
    protected function sendToRecipient(LineService $lineService, string $lineUserId): bool
    {
        try {
            switch ($this->broadcast->message_type) {
                case 'text':
                    return $lineService->sendPushMessage($lineUserId, $this->broadcast->content);

                case 'flex':
                    if (!$this->broadcast->flexTemplate) {
                        Log::error("❌ ไม่พบ Flex Template สำหรับ broadcast {$this->broadcast->id}");
                        return false;
                    }

                    $flexMessage = [
                        'type' => 'flex',
                        'altText' => $this->broadcast->name,
                        'contents' => $this->broadcast->flexTemplate->flex_content,
                    ];

                    return $lineService->sendPushMessage($lineUserId, $this->broadcast->name, [$flexMessage]);

                case 'image':
                    $imageUrl = $this->broadcast->message_data['image_url'] ?? null;
                    $previewUrl = $this->broadcast->message_data['preview_url'] ?? $imageUrl;

                    if (!$imageUrl) {
                        return false;
                    }

                    $imageMessage = [
                        'type' => 'image',
                        'originalContentUrl' => $imageUrl,
                        'previewImageUrl' => $previewUrl,
                    ];

                    return $lineService->sendPushMessage($lineUserId, $this->broadcast->name, [$imageMessage]);

                case 'video':
                    $videoUrl = $this->broadcast->message_data['video_url'] ?? null;
                    $previewUrl = $this->broadcast->message_data['preview_url'] ?? null;

                    if (!$videoUrl || !$previewUrl) {
                        return false;
                    }

                    $videoMessage = [
                        'type' => 'video',
                        'originalContentUrl' => $videoUrl,
                        'previewImageUrl' => $previewUrl,
                    ];

                    return $lineService->sendPushMessage($lineUserId, $this->broadcast->name, [$videoMessage]);

                default:
                    return false;
            }

        } catch (\Exception $e) {
            Log::error("❌ ข้อผิดพลาดในการส่งข้อความ: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle job failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("❌ Job SendScheduledBroadcast ล้มเหลวถาวร สำหรับ broadcast {$this->broadcast->id}: " . $exception->getMessage());

        $this->broadcast->update([
            'status' => 'failed',
            'error_message' => 'Job failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
        ]);
    }
}
