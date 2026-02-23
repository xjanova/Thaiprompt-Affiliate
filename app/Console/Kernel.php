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
        // ข้าม schedule ขณะรัน test เพื่อไม่ให้ DB queries ตอน boot ทำ artisan test พัง
        if ($this->app->runningUnitTests() || $this->app->environment('testing')) {
            return;
        }

        try {
            $this->registerScheduledTasks($schedule);
        } catch (\Exception $e) {
            // ถ้า DB ยังไม่พร้อม (เช่น CI ก่อน migrate) → ข้ามไป ไม่ให้ boot พัง
            \Log::warning('Schedule registration failed: '.$e->getMessage());
        }
    }

    /**
     * ลงทะเบียน scheduled tasks ทั้งหมด (แยกออกมาเพื่อ try-catch ได้)
     */
    protected function registerScheduledTasks(Schedule $schedule): void
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
                    if (! empty($customCron)) {
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

        // ========================================
        // AI Rental GPU System - Automation Tasks
        // ========================================

        // Health Check - ตรวจสอบสุขภาพ deployments ทุก 5 นาที
        $schedule->command('ai-rental:check-health --frequency=5min')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[AI Rental] Health check completed successfully');
            })
            ->onFailure(function () {
                \Log::error('[AI Rental] Health check failed');
            });

        // Reset Budgets - รีเซ็ต budget limits ทุกวันเวลา 00:00
        $schedule->command('ai-rental:reset-budgets')
            ->dailyAt('00:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[AI Rental] Budget reset completed successfully');
            })
            ->onFailure(function () {
                \Log::error('[AI Rental] Budget reset failed');
            });

        // Cleanup Alerts - Auto-resolve alerts ที่หมดอายุทุก 1 ชั่วโมง
        $schedule->command('ai-rental:cleanup-alerts')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[AI Rental] Alert cleanup completed successfully');
            })
            ->onFailure(function () {
                \Log::error('[AI Rental] Alert cleanup failed');
            });

        // Cleanup Audit Logs - ลบ audit logs เก่าทุกวันเวลา 02:00
        $schedule->command('ai-rental:cleanup-logs --days=90')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[AI Rental] Audit log cleanup completed successfully');
            })
            ->onFailure(function () {
                \Log::error('[AI Rental] Audit log cleanup failed');
            });

        // ========================================
        // Video Automation System - สร้างวิดีโออัตโนมัติ
        // ========================================

        // ประมวลผล Schedules - ตรวจสอบ schedule ที่ถึงเวลาทุก 5 นาที
        $schedule->command('video-automation:process --schedules --limit=5')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[Video Automation] Schedule processing completed');
            })
            ->onFailure(function () {
                \Log::error('[Video Automation] Schedule processing failed');
            });

        // ประมวลผล Pending Jobs - ทำงานทุก 2 นาที
        $schedule->command('video-automation:process --pending --limit=3')
            ->everyTwoMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[Video Automation] Pending jobs processing completed');
            })
            ->onFailure(function () {
                \Log::error('[Video Automation] Pending jobs processing failed');
            });

        // Retry Failed Jobs - ทุก 30 นาที
        $schedule->command('video-automation:process --retry --limit=5')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[Video Automation] Failed jobs retry completed');
            })
            ->onFailure(function () {
                \Log::error('[Video Automation] Failed jobs retry failed');
            });

        // Cleanup Source Files - ลบไฟล์ต้นฉบับหลังโพสต์สำเร็จ ทุกชั่วโมง
        $schedule->command('video-automation:process --cleanup --limit=10')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[Video Automation] Source files cleanup completed');
            })
            ->onFailure(function () {
                \Log::error('[Video Automation] Source files cleanup failed');
            });

        // ========================================
        // E-Commerce Payment Distribution System
        // ========================================

        // Process Pending Order Distributions - ทุก 5 นาที
        // สำหรับ Orders ที่ชำระเงินแล้วแต่ยังไม่ได้ distribute
        $schedule->command('orders:process-distribution --limit=50')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[E-Commerce] Pending order distribution processing completed');
            })
            ->onFailure(function () {
                \Log::error('[E-Commerce] Pending order distribution processing failed');
            });

        // Release Pending Earnings - ทุก 10 นาที
        // ปล่อย earnings ที่ pending และถึงเวลา available
        $schedule->command('earnings:release-pending --limit=100')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[E-Commerce] Pending earnings release completed');
            })
            ->onFailure(function () {
                \Log::error('[E-Commerce] Pending earnings release failed');
            });

        // Process Scheduled Payouts - ทุก 15 นาที
        // ประมวลผล payout requests ที่ถูก schedule ไว้
        $schedule->command('payouts:process-scheduled')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[E-Commerce] Scheduled payouts processing completed');
            })
            ->onFailure(function () {
                \Log::error('[E-Commerce] Scheduled payouts processing failed');
            });

        // ========================================
        // SMS Checker Payment - ยกเลิก Orders หมดเวลาและทำความสะอาดข้อมูล
        // ========================================

        // รันทุก 5 นาที: ยกเลิก orders/transactions ที่หมดเวลาชำระ (30 นาที)
        // และทำความสะอาด expired amounts, old nonces, old notifications
        $schedule->command('smschecker:cleanup')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[SMS Checker] Cleanup completed successfully');
            })
            ->onFailure(function () {
                \Log::error('[SMS Checker] Cleanup failed');
            });

        // ========================================
        // Fortune Check Pending - เช็คบิลที่ชำระแล้วแต่ยังไม่ได้คำทำนาย
        // ========================================
        $schedule->command('fortune:check-pending')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                // ไม่ log ทุกครั้ง เพราะรันทุกนาที (log เฉพาะใน command เมื่อมี dispatch)
            })
            ->onFailure(function () {
                \Log::error('[Fortune Check Pending] ตรวจสอบบิลรอคำทำนายล้มเหลว');
            });

        // ========================================
        // Fortune Cleanup Free Readings - ล้างคำทำนายฟรีเก่าเพื่อลดภาระ DB
        // ========================================
        $schedule->command('fortune:cleanup-free')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[Fortune Cleanup] ล้างคำทำนายฟรีเก่าสำเร็จ');
            })
            ->onFailure(function () {
                \Log::error('[Fortune Cleanup] ล้างคำทำนายฟรีเก่าล้มเหลว');
            });

        // ========================================
        // Fortune Marketing - ส่งแคมเปญการตลาดดูดวงอัตโนมัติ
        // ========================================
        $schedule->command('fortune:marketing-send')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[Fortune Marketing] Campaign check completed');
            })
            ->onFailure(function () {
                \Log::error('[Fortune Marketing] Campaign check failed');
            });

        // ========================================
        // Fortune Daily Horoscope - สร้าง+โพสดวงรายวันอัตโนมัติ
        // ========================================

        // สร้างเนื้อหาดวง (เช็คทุก 15 นาทีว่ามีแคมเปญถึงเวลาสร้างหรือยัง)
        $schedule->command('fortune:horoscope-process --generate --sync')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[Fortune Horoscope] Content generation check completed');
            })
            ->onFailure(function () {
                \Log::error('[Fortune Horoscope] Content generation check failed');
            });

        // โพสเนื้อหาที่พร้อมแล้ว (เช็คทุก 5 นาที)
        $schedule->command('fortune:horoscope-process --publish')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[Fortune Horoscope] Post publishing check completed');
            })
            ->onFailure(function () {
                \Log::error('[Fortune Horoscope] Post publishing check failed');
            });

        // ========================================
        // Horoscope Public — ดวงรายวัน 12 ราศี + 7 วันเกิด (AI)
        // ========================================

        // สร้างดวงรายวันทุกวันเวลา 06:00 น.
        $schedule->command('horoscope:generate-daily')
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('[Horoscope Public] สร้างดวงรายวันสำเร็จ');
            })
            ->onFailure(function () {
                \Log::error('[Horoscope Public] สร้างดวงรายวันล้มเหลว');
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
