<?php

namespace App\Console\Commands;

use App\Models\MobilePushNotification;
use App\Services\ExpoPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🔔 (2026-10-16) ส่ง push โปร/ประกาศที่แอดมินตั้งเวลาไว้ (หลังบ้าน › Mobile App › Push)
 *
 * เดิมแถวถูกบันทึกเป็น status=scheduled แต่ไม่มีใครส่ง → โปรตั้งเวลาไม่เคยถึงมือถือ
 *
 * - ส่งแถวที่ถึงเวลาแล้วผ่าน ExpoPushService::sendCampaign (จองแถวก่อนยิง → แถวเดียวไม่มีวันส่งสองครั้ง)
 * - แถวที่เลยเวลามานานเกิน --max-late-hours (ค้างจากก่อนมีคำสั่งนี้) → ปิดเป็น failed ไม่ส่ง
 *   โปรเก่าที่หมดเวลาแล้วเด้งพร้อมกันทีเดียวแย่กว่าไม่ส่ง
 * ❌ ไม่เกี่ยวกับ LINE (ส่งเข้าแอปเท่านั้น)
 */
class SendScheduledMobilePush extends Command
{
    protected $signature = 'mobile-push:send-scheduled
                            {--limit=20 : จำนวนแถวสูงสุดต่อรอบ}
                            {--max-late-hours=6 : เลยเวลานานกว่านี้ = ไม่ส่ง}';

    protected $description = 'ส่ง push โปรโมชั่น/ประกาศที่ตั้งเวลาไว้และถึงเวลาแล้ว';

    public function handle(ExpoPushService $expo): int
    {
        $maxLate = max(1, (int) $this->option('max-late-hours'));

        // ค้างนานเกิน → ปิดทิ้ง (เงื่อนไข status=scheduled กันชนกับรอบที่กำลังส่ง)
        $expired = MobilePushNotification::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now()->subHours($maxLate))
            ->update(['status' => 'failed']);
        if ($expired > 0) {
            Log::info('mobile-push:send-scheduled: closed stale scheduled pushes', ['count' => $expired]);
        }

        $due = MobilePushNotification::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        $sent = 0;
        foreach ($due as $campaign) {
            try {
                $result = $expo->sendCampaign($campaign);
                if ($result === null) {
                    continue; // อีกรอบจองไปก่อนแล้ว
                }
                $sent++;
                $this->info("ส่ง #{$campaign->id} สำเร็จ {$result['success']} เครื่อง ล้ม {$result['failed']}");
            } catch (\Throwable $e) {
                Log::error('mobile-push:send-scheduled failed', ['id' => $campaign->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("ส่ง push ตั้งเวลา {$sent} รายการ".($expired ? " · ปิดรายการค้าง {$expired}" : ''));

        return self::SUCCESS;
    }
}
