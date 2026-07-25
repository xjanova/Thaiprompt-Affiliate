<?php

namespace App\Providers;

use App\Events\NewTransactionCreated;
use App\Listeners\SendNewTransactionFcmNotification;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;
use Laravel\Passport\Passport;

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

        // 🌙 (2026-06-07) Bind FortuneTellingSetting → live DB row (getSettings)
        //   ปัญหา: service ที่ constructor เป็น `?FortuneTellingSetting $settings = null`
        //   เมื่อ resolve ผ่าน app(Service::class) โดยไม่ส่ง settings → Laravel container
        //   auto-wire `new FortuneTellingSetting()` (empty model) แทน null → `$settings ?? getSettings()`
        //   ใช้ empty model → อ่านค่าจาก `$attributes` default ไม่ใช่ค่าจริงใน DB
        //   (เคสจริง: admin ตั้ง celtic_cross_max_questions=0 ใน DB แต่ app(CelticCrossService)->getMaxQuestions()=5)
        //   แก้รวมศูนย์: bind ให้ทุก auto-wire ของ FortuneTellingSetting คืน getSettings() (DB จริง)
        //   ปลอดภัย: ไม่มีโค้ดใช้ app(FortuneTellingSetting::class) เพื่อขอ empty model (grep แล้ว 0 จุด)
        //   getSettings() แคช per-request + เคลียร์ที่ Queue::before อยู่แล้ว
        $this->app->bind(
            \App\Models\FortuneTellingSetting::class,
            fn () => \App\Models\FortuneTellingSetting::getSettings()
        );

        // 🔐 (2026-07-26 SECURITY) ปิด route ชุด "จัดการ client เอง" ของ Passport
        //
        //   ปัญหาเดิม (ติดมาตั้งแต่เปิด Passport 2026-07-17):
        //   Passport ลงทะเบียน route ให้ครบชุดโดยอัตโนมัติ 18 เส้น ซึ่งรวม
        //   POST /oauth/clients (middleware แค่ 'web','auth') → **ผู้ใช้ที่ล็อกอิน
        //   main.thaiprompt.online คนไหนก็สร้าง OAuth client ของตัวเองได้**
        //   พร้อม client_id + secret แล้วเอาไปหลอกลูกค้าที่ล็อกอินค้างอยู่
        //   (magic link ใช้ Auth::login remember=true) ให้กดหน้า consent ที่เขียนว่า
        //   "อ่านโปรไฟล์" → ได้ token ของเหยื่อไปยิง GET /api/user
        //   → หลุด id / email / name / line_user_id
        //
        //   ซ้ำร้าย ClientController::store() ของ vendor ตั้ง provider = null และ
        //   TokenGuard เช็คแบบ ($client->provider && ...) → null = falsy = ข้ามการเช็ค
        //   → token ของ client เถื่อนผ่าน guard 'api-oauth' ได้
        //   (ชั้นที่ 2 ปิดไปแล้วที่ ResolveJuntraUser — บังคับ provider='oauth_users'
        //    สำหรับ /api/v1/juntra/* ; ตรงนี้คือปิดที่ต้นทาง)
        //
        //   วิธีแก้: ปิด auto-register ทั้งชุด แล้วประกาศเองเฉพาะเส้นที่ SSO ใช้จริง
        //   ที่ registerPassportRoutes() ใน boot()
        //
        //   ⚠️ ต้องเรียกใน register() เท่านั้น — PassportServiceProvider::boot()
        //      เป็นคนลงทะเบียน route และ package provider boot ก่อน AppServiceProvider
        //      (register() ของทุก provider จบก่อน boot() ตัวแรกเสมอ จึงทัน)
        Passport::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ ตรวจสอบและสร้าง storage directories ที่จำเป็น
        // แก้ปัญหา session storage error เมื่อ directory ไม่มีอยู่
        $this->ensureStorageDirectoriesExist();

        // 🔐 (2026-07-17) Passport OAuth2 SSO server (สำหรับ juntraweb / จันทรา.online)
        //   - useClientModel: client model ที่ override skipsAuthorization (auto-login)
        //   - hashClientSecrets: เก็บ secret เป็น hash ใน DB (SECURITY_REVIEW) —
        //     plain secret โผล่ครั้งเดียวตอนสร้าง client (ProvisionJuntraOAuthClient)
        //   - tokensCan: บังคับประกาศ scope 'read profile email' ไม่งั้น juntra
        //     ขอ scope เหล่านี้แล้วโดน invalid_scope ที่ /oauth/token
        //   - เป็น runtime config (ไม่ใช่ config file) → ปลอดภัยกับ config:cache
        Passport::useClientModel(\App\Models\Passport\Client::class);
        Passport::hashClientSecrets();
        Passport::tokensCan([
            'read' => 'อ่านข้อมูลพื้นฐาน',
            'profile' => 'เข้าถึงชื่อโปรไฟล์',
            'email' => 'เข้าถึงอีเมล',
        ]);
        Passport::setDefaultScope(['read', 'profile', 'email']);
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));

        // 🔐 คู่กับ Passport::ignoreRoutes() ใน register() — ประกาศเองเฉพาะเส้นที่ใช้
        $this->registerPassportRoutes();

        // ✅ รัน pending migrations อัตโนมัติ (ตรวจสอบวันละครั้ง)
        $this->autoRunPendingMigrations();

        // ⚠️ CRITICAL: Force HTTPS สำหรับ Production
        // แก้ปัญหา redirect loop เมื่อใช้ Cloudflare/Reverse Proxy
        if (config('app.env') === 'production' || config('app.force_https', false)) {
            URL::forceScheme('https');
        }

        // Use Tailwind pagination views
        Paginator::useTailwind();

        // 🔄 (2026-06-06) Queue worker daemon — เคลียร์ static settings cache ก่อนทุก job
        //   FortuneTellingSetting::getSettings() แคชใน static $cachedInstance (ไม่มี TTL)
        //   → worker (long-running) ถือ snapshot ค้างข้าม job ตลอดอายุ process
        //   → ถ้าแอดมินแก้ setting (เปิด/ปิด SlipOK, แบนเนอร์ DM ฯลฯ) job เห็นค่าเก่า → ทำงานผิด
        //   เคสจริง: VerifySlipFallbackJob เห็น SlipOK "ปิด" (stale) → ไม่ตรวจสลิป (บิล FTU-260606-C8706)
        //   แก้รวมศูนย์: clear ก่อนทุก job → job ที่อ่าน settings ได้ค่าสดจาก DB เสมอ
        //   (web request สด per-request อยู่แล้ว ; เป็น no-op สำหรับ job ที่ไม่แตะ settings)
        Queue::before(function () {
            \App\Models\FortuneTellingSetting::clearSettingsCache();
        });

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

        // Register MLM Member Observer — สร้าง FortuneReferral อัตโนมัติเมื่อมีสมาชิกใหม่
        \App\Models\MlmMember::observe(\App\Observers\MlmMemberObserver::class);

        // ส่ง FCM push ไปยัง SmsChecker app เมื่อมีบิลใหม่
        // แอพจะโหลดบิลทันทีโดยไม่ต้องรอ periodic sync
        Event::listen(
            NewTransactionCreated::class,
            SendNewTransactionFcmNotification::class
        );

        // แชร์ตัวแปร $freeFortuneEnabled ให้ทุก view ในระบบดูดวงสาธารณะ
        // ใช้ซ่อน/แสดงคำว่า "ดูดวงฟรี" เมื่อ admin ตั้งโควต้าฟรีเป็น 0 ทั้งหมด
        View::composer('frontend.horoscope.*', function ($view) {
            try {
                $settings = \App\Models\FortuneTellingSetting::first();
                $view->with(
                    'freeFortuneEnabled',
                    $settings ? $settings->isHoroscopePublicFreeEnabled() : true
                );
            } catch (\Exception $e) {
                // ถ้ายังไม่ได้ migrate หรือ DB ไม่พร้อม → ให้แสดงฟรีเป็น default
                $view->with('freeFortuneEnabled', true);
            }
        });
    }

    /**
     * ประกาศ route ของ Passport เองเฉพาะเส้นที่ SSO เว็บจันทราใช้จริง
     *
     * 🔐 (2026-07-26 SECURITY) แทนที่ชุด 18 เส้นที่ Passport ลงทะเบียนให้เอง
     *    (ดูเหตุผลเต็มที่ Passport::ignoreRoutes() ใน register())
     *
     * เก็บไว้ 4 เส้น:
     *   - POST   /oauth/token     juntraweb แลก authorization_code → access token
     *                             (server-to-server ไม่ผ่าน 'web' จึงไม่โดน CSRF —
     *                              เหมือน vendor เป๊ะ ห้ามใส่ 'web' เด็ดขาด)
     *   - GET    /oauth/authorize เริ่ม flow (ต้องมี session ของ Thaiprompt)
     *   - POST   /oauth/authorize ปุ่ม "อนุญาต" บนหน้า consent
     *   - DELETE /oauth/authorize ปุ่ม "ไม่อนุญาต" บนหน้า consent
     *
     * ทำไมยังเก็บ approve/deny ทั้งที่ client ของจันทราตั้ง skip_authorization=true:
     *   view `passport::authorize` ของ vendor ยิงไปที่ route ชื่อ
     *   passport.authorizations.{approve,deny} ตรงๆ — ถ้าไม่ประกาศไว้ แล้ววันหน้า
     *   แอดมิน provision client ที่ไม่ skip consent จะ RouteNotFoundException
     *   (เก็บไว้ไม่เพิ่มความเสี่ยง เพราะจะกดได้ต้องมี client อยู่ก่อน ซึ่งตอนนี้
     *    สร้างเองไม่ได้แล้ว — ต้องผ่าน artisan oauth:provision-juntra-client)
     *
     * ที่ตัดทิ้ง (ไม่มีโค้ดในระบบเรียกใช้ — grep แล้ว 0 จุด):
     *   /oauth/clients*, /oauth/personal-access-tokens*, /oauth/tokens*,
     *   /oauth/scopes, /oauth/token/refresh
     *   (token/refresh เป็นของ SPA cookie auth ที่ต้องมี session บนโดเมนเดียวกัน
     *    — juntra อยู่คนละโดเมน ใช้ grant_type=refresh_token ที่ POST /oauth/token แทน)
     *
     * ⚠️ middleware + ชื่อ route ต้องตรงกับ vendor/laravel/passport/routes/web.php
     *    ห้ามแก้ชื่อ ไม่งั้น SSO ของ จันทรา.online พัง
     */
    protected function registerPassportRoutes(): void
    {
        Route::group([
            'prefix' => config('passport.path', 'oauth'),
            'as' => 'passport.',
        ], function () {
            Route::post('/token', [AccessTokenController::class, 'issueToken'])
                ->middleware('throttle')
                ->name('token');

            Route::get('/authorize', [AuthorizationController::class, 'authorize'])
                ->middleware('web')
                ->name('authorizations.authorize');

            // ลอกนิพจน์นี้จาก vendor ตรงๆ ห้ามย่อ — โปรเจกต์ไม่มี config/passport.php
            // แต่ PassportServiceProvider mergeConfigFrom config ของตัวเองที่ตั้ง
            // 'guard' => 'web' ไว้ → ผลลัพธ์จริงคือ 'auth:web' (ไม่ใช่ 'auth' เฉยๆ)
            $guard = config('passport.guard');

            Route::middleware(['web', $guard ? 'auth:'.$guard : 'auth'])->group(function () {
                Route::post('/authorize', [ApproveAuthorizationController::class, 'approve'])
                    ->name('authorizations.approve');

                Route::delete('/authorize', [DenyAuthorizationController::class, 'deny'])
                    ->name('authorizations.deny');
            });
        });
    }

    /**
     * รัน pending migrations อัตโนมัติ
     *
     * ตรวจสอบวันละครั้งว่ามี migration ค้างหรือไม่
     * ถ้ามี จะรันให้อัตโนมัติเพื่อให้ระบบพร้อมใช้งานเสมอ
     * ใช้ cache file เพื่อไม่ให้ตรวจสอบทุก request
     */
    protected function autoRunPendingMigrations(): void
    {
        // ข้ามถ้ากำลังรัน migrate command อยู่แล้ว (ป้องกัน nested migration
        // ที่อาจทำให้เกิดสถานะ partial - ตารางถูกสร้างแต่ migration ไม่ถูกบันทึก)
        if ($this->app->runningInConsole()) {
            $argv = $_SERVER['argv'] ?? [];
            $command = implode(' ', $argv);
            if (str_contains($command, 'migrate')) {
                return;
            }
        }

        // ใช้ file cache เพื่อตรวจสอบวันละครั้ง (ไม่ใช้ DB cache เพราะ DB อาจยังไม่พร้อม)
        $cacheFile = storage_path('framework/cache/migration_check.txt');
        $today = date('Y-m-d');

        // ถ้าเช็คแล้ววันนี้ ข้ามไป
        if (file_exists($cacheFile) && trim(file_get_contents($cacheFile)) === $today) {
            return;
        }

        // ⚠️ CRITICAL: บันทึก cache ก่อนรัน migrate
        // เหตุผล: ถ้า migrate throw exception (เช่น DB down) แล้ว cache ถูกเขียนหลัง
        //         → ทุก request จะพยายาม migrate ใหม่ → spam log + timeout
        // ยอมรับความเสี่ยงว่า migration ที่ fail จะต้องรอถึงพรุ่งนี้ (หรือรัน artisan migrate เอง)
        @file_put_contents($cacheFile, $today);

        try {
            // รัน migrate --force (จะไม่ทำอะไรถ้าไม่มี pending)
            \Artisan::call('migrate', ['--force' => true]);
            $output = \Artisan::output();

            // Log ถ้ามี migration ที่รัน
            if (! str_contains($output, 'Nothing to migrate')) {
                \Log::info('Auto-migration: รัน pending migrations สำเร็จ', [
                    'output' => trim($output),
                ]);
            }
        } catch (\Exception $e) {
            // ไม่ให้ migration error ทำให้ทั้งระบบล่ม
            \Log::warning('Auto-migration: ไม่สามารถรันได้', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ตรวจสอบและสร้าง storage directories ที่จำเป็น
     *
     * แก้ปัญหา:
     * - Session storage error เมื่อ storage/framework/sessions ไม่มีอยู่
     * - Cache error เมื่อ storage/framework/cache ไม่มีอยู่
     * - View error เมื่อ storage/framework/views ไม่มีอยู่
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
            if (! is_dir($directory)) {
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
