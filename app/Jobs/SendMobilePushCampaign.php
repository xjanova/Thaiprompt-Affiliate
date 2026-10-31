<?php

namespace App\Jobs;

use App\Models\MobilePushNotification;
use App\Services\ExpoPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 🔔 (2026-10-16) ส่ง push campaign (mobile_push_notifications) ถึงทุกเครื่องนอก request
 *
 * ใช้กับประกาศ "ถึงผู้ใช้ทุกคน" จากหลังบ้าน › แจ้งเตือน — ยิงเครื่องทั้งหมดใน request จะช้า
 * กันส่งซ้ำด้วย ExpoPushService::sendCampaign (จองแถวก่อนยิง) · ห้าม retry (เด้งซ้ำ)
 */
class SendMobilePushCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public int $campaignId) {}

    public function handle(ExpoPushService $expo): void
    {
        $campaign = MobilePushNotification::find($this->campaignId);
        if ($campaign) {
            $expo->sendCampaign($campaign);
        }
    }
}
