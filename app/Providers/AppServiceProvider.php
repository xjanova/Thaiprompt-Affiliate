<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register WalletService as singleton
        $this->app->singleton(\App\Services\WalletService::class);

        // Register InvestmentService as singleton
        $this->app->singleton(\App\Services\InvestmentService::class);

        // Register StakingService as singleton
        $this->app->singleton(\App\Services\StakingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ ตรวจสอบและสร้าง storage directories ที่จำเป็น
        // แก้ปัญหา session storage error เมื่อ directory ไม่มีอยู่
        $this->ensureStorageDirectoriesExist();

        // ⚠️ CRITICAL: Force HTTPS สำหรับ Production
        // แก้ปัญหา redirect loop เมื่อใช้ Cloudflare/Reverse Proxy
        if (config('app.env') === 'production' || env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        // Use Tailwind pagination views
        Paginator::useTailwind();

        // เพิ่ม Carbon macro สำหรับแสดงวันที่ภาษาไทย
        Carbon::macro('thaidate', function (string $format = 'j M Y') {
            /** @var Carbon $this */
            $thaiMonths = [
                1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
                5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
                9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
            ];

            $thaiMonthsFull = [
                1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
            ];

            $result = $this->format($format);
            $month = (int) $this->format('n');
            $thaiYear = $this->format('Y') + 543;

            // แทนที่ชื่อเดือนแบบเต็ม (F)
            $result = str_replace($this->format('F'), $thaiMonthsFull[$month], $result);
            // แทนที่ชื่อเดือนแบบย่อ (M)
            $result = str_replace($this->format('M'), $thaiMonths[$month], $result);
            // แทนที่ปี ค.ศ. เป็น พ.ศ. (Y หรือ y)
            $result = str_replace($this->format('Y'), $thaiYear, $result);
            $result = str_replace($this->format('y'), substr($thaiYear, -2), $result);

            return $result;
        });

        // Register KYC Verification Observer for Admin Notifications
        \App\Models\KycVerification::observe(\App\Observers\KycVerificationObserver::class);

        // Register Ticket Observer for Admin Notifications
        \App\Models\Ticket::observe(\App\Observers\TicketObserver::class);

        // Register TicketReply Observer for User Notifications
        \App\Models\TicketReply::observe(\App\Observers\TicketReplyObserver::class);

        // Register Order Observer for MLM System
        \App\Models\Order::observe(\App\Observers\OrderObserver::class);

        // Register Product Observer for Image Cleanup
        \App\Models\Product::observe(\App\Observers\ProductObserver::class);

        // Register Food Passport Observers
        \App\Models\FoodProduct::observe(\App\Observers\FoodProductObserver::class);
        \App\Models\ProductJourney::observe(\App\Observers\ProductJourneyObserver::class);
        \App\Models\QualityCheckpoint::observe(\App\Observers\QualityCheckpointObserver::class);
        \App\Models\CarbonCredit::observe(\App\Observers\CarbonCreditObserver::class);

        // Register AI Rental GPU System Observers
        \App\Models\AiRentalDeployment::observe(\App\Observers\AiRentalDeploymentObserver::class);
        \App\Models\AiRentalBudgetLimit::observe(\App\Observers\AiRentalBudgetLimitObserver::class);
    }

    /**
     * ตรวจสอบและสร้าง storage directories ที่จำเป็น
     *
     * แก้ปัญหา:
     * - Session storage error เมื่อ storage/framework/sessions ไม่มีอยู่
     * - Cache error เมื่อ storage/framework/cache ไม่มีอยู่
     * - View error เมื่อ storage/framework/views ไม่มีอยู่
     *
     * @return void
     */
    protected function ensureStorageDirectoriesExist(): void
    {
        $directories = [
            storage_path('framework/sessions'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                try {
                    mkdir($directory, 0755, true);
                } catch (\Exception $e) {
                    // ไม่สามารถสร้าง directory ได้ (permission denied)
                    // ข้ามไปเพื่อให้ application ยังทำงานต่อไปได้
                    // Error จะถูก handle โดย Laravel session/cache driver
                }
            }
        }
    }
}
