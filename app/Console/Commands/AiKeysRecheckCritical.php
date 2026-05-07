<?php

namespace App\Console\Commands;

use App\Models\AiApiKey;
use App\Services\AiApiKeyHealthProbeService;
use Illuminate\Console\Command;

/**
 * 🩺 (2026-05-07) Recheck critical AI API keys
 *
 * Schedule: hourly (ดู Console/Kernel.php)
 *
 * Logic:
 *   1. Query keys ที่ is_critical=true
 *   2. Filter: next_recheck_at <= now (ตาม backoff schedule)
 *      OR next_recheck_at IS NULL (ครั้งแรก)
 *   3. Probe แต่ละ key ผ่าน AiApiKeyHealthProbeService
 *   4. Success → unban + log auto_recovered_at + admin notification
 *   5. Fail → schedule next attempt (exponential backoff)
 *
 * Usage:
 *   php artisan ai:recheck-critical              # auto schedule
 *   php artisan ai:recheck-critical --limit=20   # batch size
 *   php artisan ai:recheck-critical --force      # ข้าม backoff (probe ทุก critical key)
 *   php artisan ai:recheck-critical --key=42     # เฉพาะ key ID
 */
class AiKeysRecheckCritical extends Command
{
    protected $signature = 'ai:recheck-critical
                            {--limit=20 : จำนวน key ที่จะ probe ใน batch นี้}
                            {--force : ข้าม backoff schedule — probe ทุก critical key}
                            {--key= : probe เฉพาะ key ID นี้}';

    protected $description = '🩺 ตรวจสอบ critical AI API keys อัตโนมัติ — unban ถ้า probe สำเร็จ';

    public function handle(AiApiKeyHealthProbeService $probeService): int
    {
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');
        $specificKey = $this->option('key');

        // Build query
        $query = AiApiKey::query()->where('is_critical', true);

        if ($specificKey) {
            $query->where('id', $specificKey);
        } elseif (! $force) {
            // ✅ Default: filter by backoff schedule
            $query->where(function ($q) {
                $q->whereNull('next_recheck_at')
                    ->orWhere('next_recheck_at', '<=', now());
            });
            // ⚠️ Rate limit guard: ถ้า last_recheck_at ภายใน 1 ชม. → skip (กัน race ระหว่าง cron)
            $query->where(function ($q) {
                $q->whereNull('last_recheck_at')
                    ->orWhere('last_recheck_at', '<=', now()->subHour());
            });
        }

        $keys = $query->limit($limit)->get();

        if ($keys->isEmpty()) {
            $this->info('🩺 ไม่มี critical keys ที่ถึงเวลา recheck — ทุกอย่างปกติ');
            return self::SUCCESS;
        }

        $this->info("🩺 เริ่ม recheck critical keys: {$keys->count()} keys");
        $this->newLine();

        $recovered = 0;
        $stillBad = 0;

        foreach ($keys as $key) {
            $this->line(sprintf('  Probing #%d %s (%s)...', $key->id, $key->name, $key->provider));

            try {
                $success = $probeService->recheckCritical($key);

                if ($success) {
                    $this->info('    ✅ Recovered!');
                    $recovered++;
                } else {
                    $fresh = $key->fresh();
                    $this->warn(sprintf(
                        '    ⏳ Still failing (failure #%d, next recheck: %s)',
                        $fresh->recheck_failure_count ?? 0,
                        $fresh->next_recheck_at?->diffForHumans() ?? 'N/A'
                    ));
                    $stillBad++;
                }
            } catch (\Throwable $e) {
                $this->error('    ❌ Probe exception: '.$e->getMessage());
                $stillBad++;
            }
        }

        $this->newLine();
        $this->info(sprintf('🏁 Done — %d recovered / %d still bad', $recovered, $stillBad));

        return self::SUCCESS;
    }
}
