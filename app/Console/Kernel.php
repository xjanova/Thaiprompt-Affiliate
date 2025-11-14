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

        // System Analytics Collection
        // Collect metrics every 5 minutes for detailed monitoring
        $schedule->command('analytics:collect')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('System analytics collected successfully');
            })
            ->onFailure(function () {
                \Log::error('System analytics collection failed');
            });

        // Clean old analytics data daily at 02:00 AM (keep last 30 days)
        $schedule->command('analytics:collect --cleanup')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Old analytics data cleaned successfully');
            });

        // Crypto Payment Gateway Scheduled Tasks
        // Scan for new cryptocurrency deposits every minute
        $schedule->command('crypto:scan-deposits')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Crypto deposit scan completed');
            })
            ->onFailure(function () {
                \Log::error('Crypto deposit scan failed');
            });

        // Process pending withdrawals every 2 minutes
        $schedule->command('crypto:process-withdrawals')
            ->everyTwoMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Crypto withdrawal processing completed');
            })
            ->onFailure(function () {
                \Log::error('Crypto withdrawal processing failed');
            });

        // LINE Token Security & Maintenance
        // Clean up expired LINE tokens daily at 03:30 AM
        $schedule->command('line:cleanup-tokens --force')
            ->dailyAt('03:30')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('LINE token cleanup completed successfully');
            })
            ->onFailure(function () {
                \Log::error('LINE token cleanup failed');
            });

        // LINE Conversation Management
        // Check and cleanup expired conversations every 5 minutes
        $schedule->command('line:cleanup-conversations')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('LINE conversation cleanup completed');
            })
            ->onFailure(function () {
                \Log::error('LINE conversation cleanup failed');
            });

        // Snake.io Game Item Spawning
        // Spawn powerups and maintain food items every minute
        $schedule->command('snake-game:spawn-items')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[Snake Game] Item spawning completed successfully');
            })
            ->onFailure(function () {
                \Log::error('[Snake Game] Item spawning failed');
            });
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
