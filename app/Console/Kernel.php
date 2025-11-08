<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Dynamic Threat Intelligence Update Schedule
        $enabled = \App\Models\Setting::get('threat_auto_update_enabled', 'boolean', true);

        if ($enabled) {
            $frequency = \App\Models\Setting::get('threat_update_frequency', 'string', 'daily');
            $time = \App\Models\Setting::get('threat_update_time', 'string', '03:00');
            $customCron = \App\Models\Setting::get('threat_update_cron', 'string', '');

            $command = $schedule->command('threat:update');

            switch ($frequency) {
                case 'hourly':
                    $command->hourly();
                    break;

                case 'daily':
                    $command->dailyAt($time);
                    break;

                case 'weekly':
                    $dayOfWeek = \App\Models\Setting::get('threat_update_day', 'integer', 0); // 0 = Sunday
                    $command->weeklyOn($dayOfWeek, $time);
                    break;

                case 'custom':
                    if (!empty($customCron)) {
                        $command->cron($customCron);
                    } else {
                        // Fallback to daily if custom is selected but no cron provided
                        $command->dailyAt($time);
                    }
                    break;

                default:
                    $command->dailyAt($time);
            }
        }

        // Investment ROI Distribution Schedule
        // Run daily at 00:05 (after midnight)
        $schedule->command('investment:distribute-roi')
            ->dailyAt('00:05')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Daily ROI distribution completed successfully');
            })
            ->onFailure(function () {
                \Log::error('Daily ROI distribution failed');
            });

        // Retry failed ROI distributions every 6 hours
        $schedule->command('investment:distribute-roi --retry')
            ->everySixHours()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
