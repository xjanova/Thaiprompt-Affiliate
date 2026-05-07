<?php

namespace App\Console\Commands;

use App\Models\FortuneBanner;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneBannerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * วินิจฉัยระบบส่งแบนเนอร์ DM (Fortune Banner)
 *
 * รัน:
 *   php artisan fortune:banner-diagnose
 *   php artisan fortune:banner-diagnose --user=facebook_user_id  (เช็ค cooldown ของ user)
 *   php artisan fortune:banner-diagnose --clear-cooldown=facebook_user_id  (ล้าง cooldown)
 *
 * ตรวจ:
 * 1. master toggle (enable_dm_banner)
 * 2. per-channel toggles (welcome/reaction/comment)
 * 3. จำนวนแบนเนอร์ active ในตาราง
 * 4. cooldown ของ user (ถ้าระบุ --user)
 * 5. ลองดึง banner ตาม strategy
 */
class FortuneBannerDiagnose extends Command
{
    protected $signature = 'fortune:banner-diagnose
                            {--user= : Facebook/LINE user id ที่จะเช็ค cooldown}
                            {--clear-cooldown= : ล้าง cooldown ของ user (welcome/reaction/comment ทั้งหมด)}
                            {--clear-all : ล้าง cooldown ของ "ทุก user" — ใช้เมื่อเปลี่ยน cooldown logic ใหม่}
                            {--last-sent : โชว์ last_sent_at ของแต่ละ banner}
                            {--test-send= : ส่ง banner welcome จริงให้ Facebook user (PSID) — ใช้ debug/test}';

    protected $description = 'วินิจฉัยระบบส่งแบนเนอร์ DM — เช็คทุกชั้น gate';

    public function handle(): int
    {
        $clearUser = $this->option('clear-cooldown');
        if ($clearUser) {
            return $this->clearCooldown($clearUser);
        }

        if ($this->option('clear-all')) {
            return $this->clearAllCooldowns();
        }

        $testUser = $this->option('test-send');
        if ($testUser) {
            return $this->testSendToUser($testUser);
        }

        $this->info('🔍 Fortune Banner Diagnose');
        $this->newLine();

        // ── 1. Settings ────────────────────────────────────────
        $settings = FortuneTellingSetting::getSettings();
        if (! $settings) {
            $this->error('❌ ไม่มี FortuneTellingSetting ในระบบ');
            return self::FAILURE;
        }

        $this->line('📋 Settings:');
        $masterOn = (bool) ($settings->enable_dm_banner ?? false);
        $welcomeOn = (bool) ($settings->banner_send_on_welcome ?? true);
        $reactionOn = (bool) ($settings->banner_send_on_reaction ?? true);
        $commentOn = (bool) ($settings->banner_send_on_comment ?? true);
        $strategy = $settings->banner_pick_strategy ?? 'rotation';

        $this->line('  enable_dm_banner       : '.($masterOn ? '✅ ON' : '❌ OFF'));
        $this->line('  banner_send_on_welcome : '.($welcomeOn ? '✅ ON' : '❌ OFF'));
        $this->line('  banner_send_on_reaction: '.($reactionOn ? '✅ ON' : '❌ OFF'));
        $this->line('  banner_send_on_comment : '.($commentOn ? '✅ ON' : '❌ OFF'));
        $this->line('  banner_pick_strategy   : '.$strategy);
        $this->newLine();

        // ── 2. Banners table ───────────────────────────────────
        $totalBanners = FortuneBanner::count();
        $activeBanners = FortuneBanner::active()->count();

        $this->line('🖼️  Banners:');
        $this->line('  รวมทั้งหมด: '.$totalBanners);
        $this->line('  active   : '.$activeBanners);
        $this->newLine();

        if ($activeBanners === 0) {
            $this->error('❌ ไม่มี banner active ในตาราง — ต้องเพิ่มที่ /admin/fortune/banners');
        } else {
            $banners = FortuneBanner::active()->ordered()->limit(10)->get();
            $this->line('  รายการ active:');
            foreach ($banners as $b) {
                $lastSent = $b->last_sent_at ? $b->last_sent_at->format('Y-m-d H:i:s') : '(never)';
                $this->line(sprintf(
                    '    #%d  %s  ส่ง %d ครั้ง  last: %s',
                    $b->id,
                    $b->name,
                    $b->send_count,
                    $lastSent
                ));
            }

            // เตือนถ้า last_sent_at เก่ามาก
            $latest = FortuneBanner::active()->orderByDesc('last_sent_at')->first();
            if ($latest && $latest->last_sent_at) {
                $hoursAgo = (int) abs($latest->last_sent_at->diffInHours(now()));
                if ($hoursAgo >= 6) {
                    $this->warn(sprintf(
                        '  ⚠️  Banner ล่าสุดถูกส่งเมื่อ %d ชั่วโมงที่แล้ว (#%d %s) — ถ้ามี webhook traffic แต่ไม่ส่ง = ติด cooldown',
                        $hoursAgo,
                        $latest->id,
                        $latest->name
                    ));
                } else {
                    $this->info(sprintf('  ✅ ส่งล่าสุดเมื่อ %d ชม.ที่แล้ว — ระบบทำงาน', $hoursAgo));
                }
            } elseif ($latest) {
                $this->warn('  ⚠️  ยังไม่เคยส่ง banner เลย (last_sent_at = null) — ลอง --clear-all แล้วทดสอบใหม่');
            }
            $this->newLine();
        }

        // ── 3. Pick test ───────────────────────────────────────
        $this->line('🎯 ลองดึง banner สำหรับแต่ละ channel:');
        $service = new FortuneBannerService($settings);

        foreach (['welcome', 'reaction', 'comment'] as $ch) {
            $isEnabled = $service->isEnabledFor($ch);
            $picked = $service->pickForChannel($ch);

            $status = $isEnabled
                ? ($picked ? '✅ ดึงได้: #'.$picked->id.' '.$picked->name : '⚠️ enable แต่ไม่มี active banner')
                : '❌ ปิดอยู่';

            $this->line('  '.str_pad($ch, 9).': '.$status);
        }
        $this->newLine();

        // ── 4. User cooldown (optional) ────────────────────────
        // 🆕 (2026-05-06) cache key รวมวันที่ — รีเซ็ตทุกเที่ยงคืน
        $userId = $this->option('user');
        if ($userId) {
            $today = now()->format('Y-m-d');
            $this->line('⏱️  Cooldown ของ user '.$userId.' (วันนี้: '.$today.'):');
            foreach (['welcome', 'reaction', 'comment'] as $ch) {
                // ลอง key ใหม่ (date-based) ก่อน, fallback key เก่า (legacy)
                $newKey = "fortune_banner_sent:{$ch}:{$userId}:{$today}";
                $oldKey = "fortune_banner_sent:{$ch}:{$userId}";
                $existsNew = Cache::has($newKey);
                $existsOld = Cache::has($oldKey);
                $status = $existsNew
                    ? '🔒 ส่งให้วันนี้ไปแล้ว (รอเที่ยงคืน)'
                    : ($existsOld ? '🔒 (legacy 24hr cooldown)' : '🟢 พร้อมส่ง');
                $this->line('  '.str_pad($ch, 9).': '.$status);
            }
            $this->newLine();

            // 🆕 (2026-05-07) Per-user 551 unreachable cache — ลด log spam + skip API calls ที่ fail แน่
            $unreachableKey = "fb_user_unreachable:{$userId}:{$today}";
            if (Cache::has($unreachableKey)) {
                $this->warn("🚫 user marked unreachable today (24hr window expired) — image จะถูก skip");
                $this->line("   ล้าง: php artisan fortune:banner-diagnose --clear-cooldown={$userId}");
            } else {
                $this->line("🟢 user reachable status: OK (ยังไม่เคย fail วันนี้)");
            }
            $this->newLine();

            $this->line('💡 ล้าง cooldown: php artisan fortune:banner-diagnose --clear-cooldown='.$userId);
            $this->newLine();
        }

        // ── 5. สรุป ─────────────────────────────────────────────
        $this->line('🩺 สรุป:');
        if (! $masterOn) {
            $this->warn('  ⚠️  enable_dm_banner = OFF — ไปเปิดที่ /admin/fortune/banners');
        } elseif ($activeBanners === 0) {
            $this->warn('  ⚠️  ไม่มี banner active — อัพโหลด + tick is_active');
        } elseif (! $welcomeOn) {
            $this->warn('  ⚠️  banner_send_on_welcome = OFF — เปิดที่ admin');
        } else {
            $this->info('  ✅ Banner system พร้อมส่ง — ถ้ายังไม่ส่ง ตรวจ:');
            $this->line('     1. user อยู่ใน cooldown หรือไม่ (ใช้ --user=ID)');
            $this->line('     2. log: storage/logs/laravel.log → grep "Fortune banner" / "FortuneBannerService"');
            $this->line('     3. FB webhook ถูก trigger ผ่าน processConversationalMessage หรือไม่');
        }

        return self::SUCCESS;
    }

    /**
     * ล้าง cooldown ของทุก user — ใช้เมื่อ deploy logic ใหม่ + ต้องการรีเซ็ต
     *
     * Cache::flush() จะลบ cache ทั้งหมด (ไม่ใช่แค่ banner) — ดังนั้นใช้ pattern หากเป็น Redis
     * ถ้า file driver: ใช้ artisan cache:clear ภายนอก เพราะ Laravel ไม่มี wildcard delete
     */
    protected function clearAllCooldowns(): int
    {
        $driver = config('cache.default');
        $this->line('🗑️  Clearing ALL banner cooldowns (cache driver: ' . $driver . ')');

        if ($driver === 'redis') {
            try {
                $redis = \Illuminate\Support\Facades\Cache::store('redis')->getRedis();
                $prefix = config('cache.prefix') . ':';
                $patterns = [
                    $prefix . 'fortune_banner_sent:*',
                    $prefix . 'fb_user_unreachable:*',  // 🆕 (2026-05-07) per-user 551 cache
                ];
                $deleted = 0;
                foreach ($patterns as $p) {
                    $keys = $redis->keys($p);
                    if (! empty($keys)) {
                        $redis->del($keys);
                        $deleted += count($keys);
                    }
                }
                $this->info("✅ Redis: ลบ key ที่ match แล้ว: {$deleted}");
                return self::SUCCESS;
            } catch (\Throwable $e) {
                $this->error('Redis pattern delete fail: ' . $e->getMessage());
                $this->line('Fallback: ใช้ php artisan cache:clear แทน');
                return self::FAILURE;
            }
        }

        // file/database driver — ไม่มี wildcard delete → แนะนำ cache:clear
        $this->warn('⚠️  driver = ' . $driver . ' — ไม่รองรับ wildcard delete');
        $this->line('   ใช้: php artisan cache:clear (ระวัง: ลบ cache ทั้งหมด ไม่ใช่แค่ banner)');
        return self::SUCCESS;
    }

    /**
     * ส่ง banner welcome จริงให้ Facebook user (debug/test)
     *
     * - ล้าง cooldown ของวันนี้ก่อน (กัน skip)
     * - เลือก banner ตาม strategy
     * - ส่งผ่าน FacebookWebhookService::sendImage
     * - แสดงผล + URL ที่ใช้ส่ง
     */
    protected function testSendToUser(string $userId): int
    {
        $this->info("🧪 ทดสอบส่ง banner welcome ไปที่ user: {$userId}");
        $this->newLine();

        // 1. ล้าง cooldown ของวันนี้ก่อน
        $today = now()->format('Y-m-d');
        $cacheKey = "fortune_banner_sent:welcome:{$userId}:{$today}";
        $legacyKey = "fortune_banner_sent:welcome:{$userId}";
        Cache::forget($cacheKey);
        Cache::forget($legacyKey);
        $this->line('🗑️  Cleared cooldown keys (today + legacy)');

        // 2. ดึง settings
        $settings = \App\Models\FortuneTellingSetting::getSettings();
        if (! ($settings->enable_dm_banner ?? false)) {
            $this->error('❌ enable_dm_banner = OFF — ต้องเปิดที่ /admin/fortune/banners ก่อน');
            return self::FAILURE;
        }

        // 3. Pick banner
        $bannerService = new \App\Services\FortuneBannerService($settings);
        $banner = $bannerService->pickForChannel('welcome');
        if (! $banner) {
            $this->error('❌ pickForChannel(welcome) return null — ไม่มี active banner');
            return self::FAILURE;
        }
        $this->line(sprintf('🖼️  Picked banner: #%d %s', $banner->id, $banner->name));
        $this->line('   URL: ' . $banner->image_url);
        $this->newLine();

        // 4. ส่งจริง
        $fbService = new \App\Services\FacebookWebhookService($settings);
        $this->line('📤 ส่งภาพไป Facebook Messenger...');

        try {
            // 🆕 (2026-05-07) skip_unreachable_cache=true → admin debug ต้องส่งแม้ user เคย fail วันนี้
            $sent = $fbService->sendImage($userId, $banner->image_url, null, [
                'skip_unreachable_cache' => true,
            ]);
            if ($sent) {
                $banner->recordSend();
                $this->info('  ✅ ส่งสำเร็จ! ลูกค้าควรเห็นภาพในแชท');
                $this->line('  📊 send_count = ' . ($banner->send_count + 1));
            } else {
                $this->error('  ❌ sendImage() return false — ดู log: storage/logs/laravel.log → grep "ส่งรูปภาพ"');
                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error('  ❌ Exception: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * ล้าง cooldown ของ user ทุก channel (ทั้ง key ใหม่ date-based และ legacy 24hr)
     */
    protected function clearCooldown(string $userId): int
    {
        $cleared = 0;
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        foreach (['welcome', 'reaction', 'comment'] as $ch) {
            // ล้าง key ทั้ง date-based (วันนี้+เมื่อวาน) และ legacy
            $keys = [
                "fortune_banner_sent:{$ch}:{$userId}:{$today}",
                "fortune_banner_sent:{$ch}:{$userId}:{$yesterday}",
                "fortune_banner_sent:{$ch}:{$userId}", // legacy
            ];
            foreach ($keys as $key) {
                if (Cache::has($key)) {
                    Cache::forget($key);
                    $cleared++;
                    $this->line("  ✅ ล้าง {$key}");
                }
            }
        }

        // 🆕 (2026-05-07) ล้าง per-user 551 unreachable cache ด้วย
        $unreachableKeys = [
            "fb_user_unreachable:{$userId}:{$today}",
            "fb_user_unreachable:{$userId}:{$yesterday}",
        ];
        foreach ($unreachableKeys as $key) {
            if (Cache::has($key)) {
                Cache::forget($key);
                $cleared++;
                $this->line("  ✅ ล้าง {$key} (551-cache)");
            }
        }

        $this->newLine();
        $this->info("ล้าง cooldown {$cleared} รายการ สำหรับ user {$userId}");

        return self::SUCCESS;
    }
}
