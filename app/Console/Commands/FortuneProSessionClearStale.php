<?php

namespace App\Console\Commands;

use App\Models\FortuneTellingSetting;
use App\Services\FortuneConversationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🧹 (2026-07-08) Safety-net: กวาด flag pro_session_active ที่ค้างบน reading completed
 *
 * ต้นตอ incident 82-customer (Siripon Schröter, 2026-07-08):
 *   Celtic Grand Finale ที่จบผ่าน max_questions_reached/ai_signal จะ "คง" pro_session_active=true
 *   ไว้ให้ linger (Celtic Q&A 15 นาที) — แต่ถ้าลูกค้าเงียบหลัง finale ไม่มี cron ไหนกวาด flag เลย:
 *     - fortune:celtic-auto-finalize / fortune:deep-auto-finalize จับเฉพาะ status ที่ยังไม่ completed
 *     - isInProSession() clear ได้แต่ lazy (เฉพาะตอนลูกค้าทักครั้งถัดไป)
 *   → flag ค้างถาวร → isInPrediction() มองว่า "ทำนายอยู่" → "ดูดวง" ครั้งใหม่ถูก silent-skip
 *   → ลูกค้าเห็นแต่ "ระบบกำลังดำเนินการ 🙏" เปิดดวงใหม่ไม่ได้เลย
 *
 * Command นี้ปิด gap เชิงรุก: หา completed + pro_session_active=true ที่ window หมดเวลาแล้ว → clear
 *   (ใช้ proSessionWindowExpired() แบบ time-bound — reading ที่ยัง linger จริงไม่ถูกแตะ)
 *
 * Schedule: every 10 minutes (routes/console.php)
 *
 * Usage:
 *   php artisan fortune:prosession-clear-stale           # รันจริง
 *   php artisan fortune:prosession-clear-stale --dry     # dry run (รายงานอย่างเดียว ไม่ clear)
 *   php artisan fortune:prosession-clear-stale --limit=200
 */
class FortuneProSessionClearStale extends Command
{
    protected $signature = 'fortune:prosession-clear-stale
                            {--dry : Dry run — รายงานจำนวนที่จะ clear แต่ไม่ clear จริง}
                            {--limit=100 : จำนวนสูงสุดที่จะกวาดต่อรอบ}';

    protected $description = 'กวาด flag pro_session_active ที่ค้างบน reading completed (กันลูกค้าถูกบล็อกดูดวงด้วย "ระบบกำลังดำเนินการ")';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $dry = (bool) $this->option('dry');

        $settings = FortuneTellingSetting::getSettings();
        $service = new FortuneConversationService($settings);

        $result = $service->clearStaleLingeringProSessions($limit, ! $dry);

        $prefix = $dry ? '[DRY] ' : '';
        $this->info("🧹 {$prefix}scanned {$result['scanned']} | cleared {$result['cleared']} | kept_active (ยัง linger) {$result['kept_active']}");

        if (! empty($result['cleared_ids'])) {
            $idList = implode(', ', array_slice($result['cleared_ids'], 0, 30));
            $this->line("   reading ids: {$idList}".(count($result['cleared_ids']) > 30 ? ' …' : ''));

            // Log เฉพาะเมื่อ clear จริง — ช่วย audit ว่าลูกค้าคนไหนถูกปลดล็อก
            if (! $dry) {
                Log::info('Fortune ProSession sweep: cleared stale linger flags', [
                    'scanned' => $result['scanned'],
                    'cleared' => $result['cleared'],
                    'kept_active' => $result['kept_active'],
                    'cleared_ids' => $result['cleared_ids'],
                ]);
            }
        }

        return 0;
    }
}
