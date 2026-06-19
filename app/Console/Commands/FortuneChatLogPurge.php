<?php

namespace App\Console\Commands;

use App\Services\Fortune\FortuneChatLogService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Midnight cleanup for the realtime warroom chat log (Redis).
 *
 * Keys are date-stamped and TTL'd to ~00:30, so they self-expire — this command
 * is the explicit "ลบทิ้งทุกเที่ยงคืน" backstop that frees memory immediately and
 * sweeps a few prior days in case a run was missed. Scheduled in routes/console.php.
 */
class FortuneChatLogPurge extends Command
{
    protected $signature = 'fortune:chatlog:purge
        {--date= : Specific Bangkok date (YYYY-MM-DD) to purge instead of the default sweep}
        {--days=3 : Sweep this many days back (yesterday → N days ago)}';

    protected $description = 'Delete past-day realtime chat-log keys from Redis (TTL is the safety net)';

    public function handle(FortuneChatLogService $log): int
    {
        if ($date = $this->option('date')) {
            $removed = $log->purgeDate($date);
            $this->info("FortuneChatLog purge {$date}: removed {$removed} key(s)");

            return self::SUCCESS;
        }

        $days = max(1, (int) $this->option('days'));
        $total = 0;
        for ($i = 1; $i <= $days; $i++) {
            $d = Carbon::now(FortuneChatLogService::TZ)->subDays($i)->toDateString();
            $total += $log->purgeDate($d);
        }
        $this->info("FortuneChatLog purge (last {$days}d): removed {$total} key(s)");

        return self::SUCCESS;
    }
}
