<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Cloudflare Cache Purge Command
 *
 * ใช้สำหรับ clear cache ของ Cloudflare หลังจาก deploy
 * ป้องกันปัญหา stale cache ที่อาจทำให้เกิด routing errors
 *
 * Usage:
 *   php artisan cloudflare:purge         # Purge ทั้งหมด
 *   php artisan cloudflare:purge --urls  # Purge เฉพาะ URLs ที่ระบุ
 */
class CloudflarePurgeCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloudflare:purge
                            {--urls=* : URLs เฉพาะที่ต้องการ purge (ถ้าไม่ระบุจะ purge ทั้งหมด)}
                            {--dry-run : ทดสอบโดยไม่ purge จริง}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge Cloudflare cache หลัง deploy เพื่อป้องกัน stale cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $zoneId = config('services.cloudflare.zone_id');
        $apiToken = config('services.cloudflare.api_token');

        // ตรวจสอบ configuration
        if (empty($zoneId) || empty($apiToken)) {
            $this->error('❌ Cloudflare credentials ไม่ครบ!');
            $this->line('');
            $this->line('กรุณาเพิ่มใน .env:');
            $this->line('  CLOUDFLARE_ZONE_ID=your_zone_id');
            $this->line('  CLOUDFLARE_API_TOKEN=your_api_token');
            $this->line('');
            $this->line('วิธีหา Zone ID:');
            $this->line('  1. ไปที่ Cloudflare Dashboard');
            $this->line('  2. เลือก domain ของคุณ');
            $this->line('  3. Zone ID อยู่ที่ sidebar ขวา');
            $this->line('');
            $this->line('วิธีสร้าง API Token:');
            $this->line('  1. ไปที่ My Profile → API Tokens');
            $this->line('  2. Create Token → Edit zone DNS หรือ Custom');
            $this->line('  3. ต้องมี permission: Zone → Cache Purge → Purge');

            return Command::FAILURE;
        }

        $urls = $this->option('urls');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - จะไม่ purge จริง');
        }

        $this->info('🚀 กำลัง purge Cloudflare cache...');

        if (empty($urls)) {
            // Purge ทั้งหมด
            return $this->purgeEverything($zoneId, $apiToken, $dryRun);
        } else {
            // Purge เฉพาะ URLs
            return $this->purgeUrls($zoneId, $apiToken, $urls, $dryRun);
        }
    }

    /**
     * Purge cache ทั้งหมด
     */
    protected function purgeEverything(string $zoneId, string $apiToken, bool $dryRun): int
    {
        $this->line('   Mode: Purge Everything');

        if ($dryRun) {
            $this->info('✅ DRY RUN: จะ purge cache ทั้งหมด');

            return Command::SUCCESS;
        }

        try {
            $response = Http::withToken($apiToken)
                ->timeout(30)
                ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                    'purge_everything' => true,
                ]);

            if ($response->successful() && $response->json('success')) {
                $this->info('✅ Purge cache ทั้งหมดสำเร็จ!');

                return Command::SUCCESS;
            } else {
                $errors = $response->json('errors', []);
                $this->error('❌ Purge ล้มเหลว!');
                foreach ($errors as $error) {
                    $this->error("   - {$error['message']}");
                }

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Purge cache เฉพาะ URLs
     */
    protected function purgeUrls(string $zoneId, string $apiToken, array $urls, bool $dryRun): int
    {
        $this->line('   Mode: Purge Specific URLs');
        $this->line('   URLs: '.count($urls));

        foreach ($urls as $url) {
            $this->line("   - {$url}");
        }

        if ($dryRun) {
            $this->info('✅ DRY RUN: จะ purge URLs ด้านบน');

            return Command::SUCCESS;
        }

        try {
            $response = Http::withToken($apiToken)
                ->timeout(30)
                ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                    'files' => $urls,
                ]);

            if ($response->successful() && $response->json('success')) {
                $this->info('✅ Purge URLs สำเร็จ!');

                return Command::SUCCESS;
            } else {
                $errors = $response->json('errors', []);
                $this->error('❌ Purge ล้มเหลว!');
                foreach ($errors as $error) {
                    $this->error("   - {$error['message']}");
                }

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
