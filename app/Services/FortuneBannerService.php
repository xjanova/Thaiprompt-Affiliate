<?php

namespace App\Services;

use App\Models\FortuneBanner;
use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Log;

/**
 * Fortune Banner Service
 *
 * จัดการการเลือก + ส่งแบนเนอร์ภาพให้ลูกค้าผ่าน Facebook/LINE
 *
 * ใช้งาน:
 *   $banner = $bannerService->pickForChannel('reaction');  // หรือ 'comment', 'welcome'
 *   if ($banner) {
 *       $facebookService->sendImage($userId, $banner->image_url);
 *       $banner->recordSend();
 *   }
 */
class FortuneBannerService
{
    public function __construct(
        protected ?FortuneTellingSetting $settings = null
    ) {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * ตรวจสอบว่าแบนเนอร์ถูกเปิดใช้งานสำหรับ channel นี้หรือไม่
     *
     * @param  string  $channel  'reaction' | 'comment' | 'welcome'
     */
    public function isEnabledFor(string $channel): bool
    {
        // Master toggle — ปิดทั้งระบบ
        if (! ($this->settings->enable_dm_banner ?? false)) {
            return false;
        }

        return match ($channel) {
            'reaction' => (bool) ($this->settings->banner_send_on_reaction ?? true),
            'comment' => (bool) ($this->settings->banner_send_on_comment ?? true),
            'welcome' => (bool) ($this->settings->banner_send_on_welcome ?? true),
            default => false,
        };
    }

    /**
     * เลือกแบนเนอร์ที่จะส่งให้ลูกค้า ตาม strategy
     *
     * @param  string  $channel  ใช้ตัดสินใจว่าควรส่งหรือไม่
     * @return FortuneBanner|null  null ถ้าปิด หรือไม่มีแบนเนอร์ active
     */
    public function pickForChannel(string $channel): ?FortuneBanner
    {
        if (! $this->isEnabledFor($channel)) {
            return null;
        }

        $strategy = $this->settings->banner_pick_strategy ?? 'rotation';

        try {
            return FortuneBanner::pickByStrategy($strategy);
        } catch (\Throwable $e) {
            Log::warning('FortuneBannerService: pick failed', [
                'channel' => $channel,
                'strategy' => $strategy,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * ส่งแบนเนอร์ครั้งเดียวต่อ user/channel ภายใน cooldown
     *
     * ใช้สำหรับ welcome flow — ไม่อยากให้แบนเนอร์เด้งทุกข้อความที่ user พิมพ์
     *
     * @param  string  $userId  Facebook/LINE user ID
     * @param  callable  $sendFn  function($imageUrl): bool
     * @param  string  $channel  'welcome' | 'reaction' | 'comment'
     * @param  int  $cooldownHours  cooldown per user (default 24 ชม.)
     * @return bool
     */
    public function sendBannerOnce(string $userId, callable $sendFn, string $channel, int $cooldownHours = 24): bool
    {
        $cacheKey = "fortune_banner_sent:{$channel}:{$userId}";

        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return false; // ส่งไปแล้วใน cooldown — skip
        }

        $sent = $this->sendBannerThenWait($sendFn, $channel);

        if ($sent) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHours($cooldownHours));
        }

        return $sent;
    }

    /**
     * ส่งแบนเนอร์พร้อมหน่วงเวลาให้ image มาก่อน text
     *
     * @param  callable  $sendFn  function($imageUrl): bool — function ที่ส่งภาพจริง (FB/LINE)
     * @param  string  $channel  'reaction' | 'comment' | 'welcome'
     * @return bool true ถ้าส่งภาพสำเร็จ (เพื่อ caller ตัดสินใจว่ารอ delay หรือไม่)
     */
    public function sendBannerThenWait(callable $sendFn, string $channel): bool
    {
        $banner = $this->pickForChannel($channel);

        if (! $banner) {
            return false;
        }

        try {
            $sent = (bool) $sendFn($banner->image_url);

            if ($sent) {
                $banner->recordSend();

                // หน่วงให้ภาพมาก่อนข้อความ (FB ไม่การันตี ordering)
                usleep(300_000); // 300 ms

                Log::info('🖼️ Fortune banner sent', [
                    'banner_id' => $banner->id,
                    'banner_name' => $banner->name,
                    'channel' => $channel,
                    'send_count' => $banner->send_count + 1,
                ]);
            }

            return $sent;
        } catch (\Throwable $e) {
            Log::warning('FortuneBannerService: send failed', [
                'banner_id' => $banner->id,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
