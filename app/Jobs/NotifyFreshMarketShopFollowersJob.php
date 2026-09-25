<?php

namespace App\Jobs;

use App\Services\FreshMarketShopPresenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * แจ้งผู้ติดตามว่าร้านตลาดสดเปิดแล้ว (ในแอป + Expo push ผ่าน NotificationService)
 *
 * เข้าคิวจาก FreshMarketShopPresenceService::open() เมื่อร้านเปลี่ยนจากปิด → เปิดเท่านั้น
 * กันแจ้งถี่ (3 ชม. ต่อร้านต่อคน) อยู่ใน notifyFollowersShopOpened() — รันซ้ำได้ปลอดภัย
 * ❌ ไม่ใช้ LINE push (โควต้า 300 ครั้ง/เดือน)
 */
class NotifyFreshMarketShopFollowersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** ไม่ retry อัตโนมัติ — แจ้งไปแล้วบางคน retry จะถูกตัวกันซ้ำข้ามอยู่แล้ว แต่ไม่จำเป็นต้องเสียรอบ */
    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public int $sellerId) {}

    public function handle(FreshMarketShopPresenceService $presence): void
    {
        try {
            $presence->notifyFollowersShopOpened($this->sellerId);
        } catch (\Throwable $e) {
            Log::error('FreshMarketShop: follower notification job failed', [
                'seller_id' => $this->sellerId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
