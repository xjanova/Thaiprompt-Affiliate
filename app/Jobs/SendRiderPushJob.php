<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\ExpoPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ส่ง Expo push ของระบบไรเดอร์ (งานใหม่, สถานะงาน, แจ้งเตือน GPS ฯลฯ) แบบคิว
 *
 * แยกเป็นคิวเพราะการกระจายงานอาจต้องยิง push หลายสิบคน —
 * ถ้ายิงตรงใน request ที่ผู้ขายกด "พร้อมส่ง" จะช้ามาก
 *
 * ใช้เฉพาะตอนที่ไม่มีสะพานกลาง App\Jobs\SendNotificationPush
 * (ถ้ามีสะพาน RiderNotificationService จะปล่อยให้สะพานส่งแทน — ดู bridgeHandlesPush())
 *
 * ❗ ไม่ใช้ LINE push เด็ดขาด (โควต้า 300/เดือน)
 */
class SendRiderPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    /**
     * @param  array<int, int>  $userIds  ผู้รับ
     * @param  array<string, mixed>  $data  payload ให้แอปเลือกหน้าจอ (type, job_id ...)
     * @param  array<int, int>  $notificationIds  in-app notification ที่ผูกกับ push นี้ (จะ mark push_sent)
     */
    public function __construct(
        public array $userIds,
        public string $title,
        public string $body,
        public array $data = [],
        public array $notificationIds = [],
    ) {}

    public function backoff(): array
    {
        return [10];
    }

    public function handle(ExpoPushService $expo): void
    {
        $sentAny = false;

        foreach (array_unique(array_map('intval', $this->userIds)) as $userId) {
            if ($userId <= 0) {
                continue;
            }

            try {
                $result = $expo->sendToUser($userId, $this->title, $this->body, $this->data);
                $sentAny = $sentAny || (($result['success'] ?? 0) > 0);
            } catch (\Throwable $e) {
                Log::warning('SendRiderPushJob: push failed', [
                    'user_id' => $userId,
                    'type' => $this->data['type'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // push_sent ถูกตั้งเป็น true ไว้ตั้งแต่สร้าง (กัน bridge อื่นส่งซ้ำ) — ที่นี่แค่บันทึกเวลาที่ส่งสำเร็จจริง
        if ($sentAny && ! empty($this->notificationIds)) {
            Notification::whereIn('id', $this->notificationIds)->update([
                'push_sent' => true,
                'push_sent_at' => now(),
            ]);
        }
    }
}
