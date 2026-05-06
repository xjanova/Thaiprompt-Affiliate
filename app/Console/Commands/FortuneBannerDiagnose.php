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
                            {--clear-cooldown= : ล้าง cooldown ของ user (welcome/reaction/comment ทั้งหมด)}';

    protected $description = 'วินิจฉัยระบบส่งแบนเนอร์ DM — เช็คทุกชั้น gate';

    public function handle(): int
    {
        $clearUser = $this->option('clear-cooldown');
        if ($clearUser) {
            return $this->clearCooldown($clearUser);
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
            $banners = FortuneBanner::active()->ordered()->limit(5)->get();
            $this->line('  รายการ active (top 5):');
            foreach ($banners as $b) {
                $this->line(sprintf(
                    '    #%d  %s  ส่งไปแล้ว %d ครั้ง  ภาพ: %s',
                    $b->id,
                    $b->name,
                    $b->send_count,
                    $b->image_path
                ));
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
        $this->newLine();
        $this->info("ล้าง cooldown {$cleared} รายการ สำหรับ user {$userId}");

        return self::SUCCESS;
    }
}
