<?php

use App\Http\Controllers\Auth\LineLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SetupController;
use App\Http\Controllers\Frontend\GameController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TPIXWhitepaperController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ⚠️ ROOT ROUTE - ต้องอยู่ก่อน routes อื่นทั้งหมด
// FIX: MethodNotAllowedHttpException - The GET method is not supported for route /
// ใช้ Route::match() เพื่อรองรับทั้ง GET และ HEAD methods อย่างชัดเจน
Route::match(['GET', 'HEAD'], '/', [HomeController::class, 'index'])->name('home');

// PWA Offline Fallback Page
Route::get('/offline', function () {
    return view('offline');
})->name('offline');

// PWA Manifest - ดึงไอคอนจาก SiteSetting ที่แอดมินอัพโหลดไว้
Route::get('/manifest.json', function () {
    $site = \App\Models\SiteSetting::getSetting();
    $appName = $site->app_name ?: ($site->site_name ?: config('app.name', 'TP-Affiliate'));
    $appIcon = $site->app_icon_url;
    $favicon = $site->favicon_url;
    $logo = $site->logo_url;

    return response()->json([
        'name' => $appName . ' - ระบบ Affiliate Marketing',
        'short_name' => $appName,
        'description' => $site->app_description ?: ($site->site_description ?: 'ระบบ Affiliate Marketing ครบวงจร'),
        'start_url' => '/user/dashboard?standalone=true',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'any',
        'background_color' => '#111827',
        'theme_color' => '#8B5CF6',
        'lang' => 'th',
        'dir' => 'ltr',
        'categories' => ['business', 'finance', 'shopping'],
        'icons' => array_values(array_filter([
            // ไอคอนแอปจาก admin (หลัก - 512x512)
            $appIcon ? [
                'src' => $appIcon,
                'sizes' => '512x512',
                'type' => 'image/webp',
                'purpose' => 'any maskable',
            ] : null,
            // Favicon จาก admin
            $favicon ? [
                'src' => $favicon,
                'sizes' => '64x64',
                'type' => 'image/x-icon',
                'purpose' => 'any',
            ] : null,
            // โลโก้จาก admin
            $logo ? [
                'src' => $logo,
                'sizes' => '512x512',
                'type' => str_contains($logo, '.svg') ? 'image/svg+xml' : 'image/webp',
                'purpose' => 'any',
            ] : null,
            // Fallback SVG logo
            [
                'src' => '/images/logo.svg',
                'sizes' => 'any',
                'type' => 'image/svg+xml',
                'purpose' => 'any',
            ],
        ])),
        'shortcuts' => [
            [
                'name' => 'แดชบอร์ด',
                'short_name' => 'หน้าแรก',
                'description' => 'เปิดหน้าแดชบอร์ด',
                'url' => '/user/dashboard',
            ],
            [
                'name' => 'กระเป๋าเงิน',
                'short_name' => 'กระเป๋า',
                'description' => 'ดูยอดเงินและประวัติ',
                'url' => '/user/wallet',
            ],
            [
                'name' => 'คอมมิชชั่น',
                'short_name' => 'คอมมิชชั่น',
                'description' => 'ดูรายได้คอมมิชชั่น',
                'url' => '/user/commissions',
            ],
        ],
        'related_applications' => [],
        'prefer_related_applications' => false,
        'launch_handler' => [
            'client_mode' => ['navigate-existing', 'auto'],
        ],
        'display_override' => ['standalone', 'minimal-ui'],
    ], 200, [
        'Content-Type' => 'application/manifest+json',
        'Cache-Control' => 'public, max-age=3600',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
})->name('manifest.json');

// Demo Routes
Route::get('/demo/loading', function () {
    return view('demo-loading');
})->name('demo.loading');

Route::get('/demo/3d-navigation', function () {
    return view('demo-3d-navigation');
})->name('demo.3d-navigation');

Route::get('/demo/space-shooter', function () {
    return view('demo-space-shooter');
})->name('demo.space-shooter');

// V3 UI Themes Demo Routes
Route::prefix('demo')->name('demo.')->group(function () {
    Route::get('/', function () {
        return view('demo.index');
    })->name('index');

    Route::get('/theme1', function () {
        return view('demo.theme1');
    })->name('theme1');

    Route::get('/theme2', function () {
        return view('demo.theme2');
    })->name('theme2');

    Route::get('/theme3', function () {
        return view('demo.theme3');
    })->name('theme3');

    // Dashboard Examples
    Route::get('/dashboard1', function () {
        return view('demo.dashboard1');
    })->name('dashboard1');

    Route::get('/dashboard2', function () {
        return view('demo.dashboard2');
    })->name('dashboard2');

    Route::get('/dashboard3', function () {
        return view('demo.dashboard3');
    })->name('dashboard3');

    Route::get('/dashboard4', function () {
        return view('demo.dashboard4');
    })->name('dashboard4');

    // Arrow X Components Showcase
    Route::get('/components', function () {
        return view('demo.components');
    })->name('components');
});

// Tournament Routes
// ⚠️ SEO: Tournament pages มี SEO value สูง
Route::prefix('tournaments')->name('tournaments.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\TournamentController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/{slug}', [\App\Http\Controllers\TournamentController::class, 'show'])->name('show');
    Route::match(['GET', 'HEAD'], '/{slug}/leaderboard', [\App\Http\Controllers\TournamentController::class, 'leaderboard'])->name('leaderboard');

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::post('/{slug}/register', [\App\Http\Controllers\TournamentController::class, 'register'])->name('register');
        Route::post('/{slug}/submit-score', [\App\Http\Controllers\TournamentController::class, 'submitScore'])->name('submit-score');
    });
});

// Rewards & Missions Routes (Authenticated)
Route::middleware('auth')->prefix('rewards')->name('rewards.')->group(function () {
    Route::get('/daily', [\App\Http\Controllers\RewardController::class, 'daily'])->name('daily');
    Route::post('/daily/claim', [\App\Http\Controllers\RewardController::class, 'claimDaily'])->name('daily.claim');

    Route::get('/missions', [\App\Http\Controllers\RewardController::class, 'missions'])->name('missions');
    Route::post('/missions/{missionId}/claim', [\App\Http\Controllers\RewardController::class, 'claimMission'])->name('missions.claim');
    Route::get('/missions/progress', [\App\Http\Controllers\RewardController::class, 'getMissionsProgress'])->name('missions.progress');
});

// Games Routes
// ⚠️ SEO Critical: Game pages สำคัญสำหรับ organic traffic
Route::prefix('games')->name('games.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\GameController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/{slug}', [\App\Http\Controllers\GameController::class, 'show'])->name('show');

    // API Routes for game progress
    Route::middleware('auth')->group(function () {
        Route::post('/{slug}/save-progress', [\App\Http\Controllers\GameController::class, 'saveProgress'])->name('save-progress');
        Route::post('/{slug}/change-loadout', [\App\Http\Controllers\GameController::class, 'changeLoadout'])->name('change-loadout');

        // Game Shop Routes
        Route::prefix('{slug}/shop')->name('shop.')->group(function () {
            Route::get('/', [\App\Http\Controllers\GameShopController::class, 'index'])->name('index');
            Route::post('/purchase/{skinId}', [\App\Http\Controllers\GameShopController::class, 'purchaseSkin'])->name('purchase');
            Route::get('/owned', [\App\Http\Controllers\GameShopController::class, 'getOwnedSkins'])->name('owned');
        });

        // Game Achievements Routes
        Route::get('/{slug}/achievements', [\App\Http\Controllers\GameController::class, 'achievements'])->name('achievements');

        // Multiplayer API Routes
        Route::prefix('{slug}/multiplayer')->name('multiplayer.')->group(function () {
            Route::post('/create-room', [\App\Http\Controllers\MultiplayerController::class, 'createRoom'])->name('create-room');
            Route::get('/rooms', [\App\Http\Controllers\MultiplayerController::class, 'listRooms'])->name('rooms');
            Route::post('/join/{roomCode}', [\App\Http\Controllers\MultiplayerController::class, 'joinRoom'])->name('join');
            Route::post('/leave/{roomCode}', [\App\Http\Controllers\MultiplayerController::class, 'leaveRoom'])->name('leave');
            Route::post('/update/{roomCode}', [\App\Http\Controllers\MultiplayerController::class, 'updatePosition'])->name('update');
            Route::get('/state/{roomCode}', [\App\Http\Controllers\MultiplayerController::class, 'getRoomState'])->name('state');
            Route::post('/death/{roomCode}', [\App\Http\Controllers\MultiplayerController::class, 'reportDeath'])->name('death');
        });
    });

    Route::match(['GET', 'HEAD'], '/{slug}/leaderboard', [\App\Http\Controllers\GameController::class, 'leaderboard'])->name('leaderboard');
});

Route::get('/demo/game-selector', [GameController::class, 'index'])->name('demo.game-selector');
Route::get('/api/games', [GameController::class, 'getGames'])->name('api.games');

Route::get('/demo/audio-spectrum', function () {
    return view('demo-audio-spectrum');
})->name('demo.audio-spectrum');

Route::get('/demo/snooker', function () {
    return view('demo-snooker');
})->name('demo.snooker');

// Prompt to Web Routes
// ⚠️ Public Tool: Prompt-to-web generator ต้องถูก index โดย search engines
Route::prefix('prompt-to-web')->name('prompt-to-web.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\PromptToWebController::class, 'index'])->name('index');
    Route::post('/generate', [\App\Http\Controllers\PromptToWebController::class, 'generate'])->name('generate');
    Route::post('/{id}/improve', [\App\Http\Controllers\PromptToWebController::class, 'improve'])->name('improve');
    Route::match(['GET', 'HEAD'], '/preview/{slug}', [\App\Http\Controllers\PromptToWebController::class, 'preview'])->name('preview');
    Route::match(['GET', 'HEAD'], '/show/{slug}', [\App\Http\Controllers\PromptToWebController::class, 'show'])->name('show');
    Route::match(['GET', 'HEAD'], '/list', [\App\Http\Controllers\PromptToWebController::class, 'list'])->name('list');
    Route::delete('/{id}', [\App\Http\Controllers\PromptToWebController::class, 'delete'])->name('delete');
});

Route::get('/demo/tetris', function () {
    return view('demo-tetris');
})->name('demo.tetris');

// Sitemap
// ⚠️ SEO Critical: Search engines ใช้ HEAD request ตรวจสอบ sitemap
Route::match(['GET', 'HEAD'], '/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// robots.txt (dynamic) — ต้องลบไฟล์ public/robots.txt ออกแล้ว route นี้ถึงจะทำงาน
// (web server เสิร์ฟไฟล์ static ก่อน routing ตาม .htaccess REQUEST_FILENAME !-f)
Route::match(['GET', 'HEAD'], '/robots.txt', [SitemapController::class, 'robots'])->name('robots');

// Language Switching
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');

// Translation API Routes (with rate limiting: 60 requests per minute)
Route::prefix('api/translate')->name('api.translate.')->middleware('throttle:60,1')->group(function () {
    Route::post('/', [\App\Http\Controllers\TranslationController::class, 'translate'])->name('text');
    Route::post('/batch', [\App\Http\Controllers\TranslationController::class, 'translateBatch'])->name('batch');
    Route::get('/languages', [\App\Http\Controllers\TranslationController::class, 'languages'])->name('languages');
    Route::post('/detect', [\App\Http\Controllers\TranslationController::class, 'detect'])->name('detect');
    Route::get('/status', [\App\Http\Controllers\TranslationController::class, 'status'])->name('status');
});

// Public Certificate Verification
// ⚠️ Public Service: Certificate verification ต้องเข้าถึงได้สาธารณะ
Route::prefix('certificate')->name('certificate.')->group(function () {
    Route::match(['GET', 'HEAD'], '/verify/{verificationCode}', [\App\Http\Controllers\Admin\CertificateController::class, 'verify'])->name('verify');
    Route::match(['GET', 'HEAD'], '/share/{id}', [\App\Http\Controllers\Admin\CertificateController::class, 'share'])->name('share');
});

// Setup Routes
// ⚠️ Setup: ระบบติดตั้งครั้งแรก - สร้าง Super Admin (Step-by-Step Wizard)
Route::prefix('setup')->name('setup.')->group(function () {
    Route::get('/', [SetupController::class, 'index'])->name('index');
    Route::post('/', [SetupController::class, 'store'])->name('store');

    // API endpoints สำหรับ wizard
    Route::post('/test-database', [SetupController::class, 'testDatabase'])->name('test-database');
    Route::post('/save-database', [SetupController::class, 'saveDatabase'])->name('save-database');
    Route::post('/run-migrations', [SetupController::class, 'runMigrations'])->name('run-migrations');
    Route::post('/run-seeders', [SetupController::class, 'runSeeders'])->name('run-seeders');
});

// Authentication Routes
// ⚠️ Authentication: หน้า login/register ต้องรองรับ HEAD method
Route::middleware('guest')->group(function () {
    Route::match(['GET', 'HEAD'], '/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware(['turnstile:login', 'throttle.login']);

    Route::match(['GET', 'HEAD'], '/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('turnstile:register');

    // LINE Login Routes (redirect ต้องอยู่ใน guest — สำหรับผู้ใช้ที่ยังไม่ได้ login)
    Route::match(['GET', 'HEAD'], '/auth/line', [LineLoginController::class, 'redirect'])->name('line.login');
    Route::match(['GET', 'HEAD'], '/auth/line/register-guide', function () {
        return view('auth.line-register-guide');
    })->name('line.register.guide');

    // Facebook OAuth Login (Socialite)
    Route::match(['GET', 'HEAD'], '/auth/facebook', [\App\Http\Controllers\Auth\FacebookLoginController::class, 'redirect'])->name('facebook.login');
});

// ✅ Facebook callback ต้องอยู่นอก guest middleware
// เพราะ FB redirect กลับมาเฉพาะ guest flow (ไม่มี link mode)
Route::match(['GET', 'HEAD'], '/auth/facebook/callback', [\App\Http\Controllers\Auth\FacebookLoginController::class, 'callback'])->name('facebook.callback');

// ✅ LINE callback ต้องอยู่นอก guest middleware
// เพราะทั้ง guest (login) และ authenticated users (link LINE) ต้องเข้าถึงได้
// ถ้าอยู่ใน guest → authenticated users ที่ทำ LINE link จะถูก redirect ไป /home แทน
Route::match(['GET', 'HEAD'], '/auth/line/callback', [LineLoginController::class, 'callback'])->name('line.callback');

// LINE Login Mobile App Callback (public - รับ callback จาก LINE OAuth แล้ว redirect ไป app deep link)
Route::match(['GET', 'HEAD'], '/auth/line/mobile-callback', [LineLoginController::class, 'mobileCallback'])
    ->name('line.mobile.callback');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Public Recruit Page Routes
// ⚠️ Affiliate Critical: Recruit links สำคัญสำหรับระบบ MLM/Affiliate
Route::prefix('recruit')->name('recruit.')->group(function () {
    Route::match(['GET', 'HEAD'], '/{member_code}', [\App\Http\Controllers\RecruitController::class, 'show'])->name('show');
    Route::post('/track-behavior', [\App\Http\Controllers\RecruitController::class, 'trackBehavior'])->name('track-behavior');
});

// LINE Account Linking (for authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/auth/line/link', [LineLoginController::class, 'link'])->name('line.link');
    Route::get('/auth/line/link/callback', [LineLoginController::class, 'linkCallback'])->name('line.link.callback');
    Route::post('/auth/line/unlink', [LineLoginController::class, 'unlink'])->name('line.unlink');
});

// Mobile App Web-Based Login (PKCE Authentication)
// สำหรับ mobile app login ผ่านเว็บอย่างปลอดภัย
Route::prefix('mobile-login')->name('mobile-login.')->group(function () {
    // แสดงหน้า login (public)
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\MobileLoginController::class, 'show'])->name('show');

    // ดำเนินการ login
    Route::post('/login', [\App\Http\Controllers\MobileLoginController::class, 'login'])->name('login');

    // อนุมัติให้แอพเข้าถึง (ต้อง auth แล้ว)
    // 🩹 (2026-05-08) action: authorize → authorizeApp (PHP signature clash with Controller::authorize)
    //   URL คงเดิม /authorize เพื่อ backward-compat กับ mobile app ที่ deploy แล้ว
    Route::post('/authorize', [\App\Http\Controllers\MobileLoginController::class, 'authorizeApp'])
        ->middleware('auth')
        ->name('authorize');

    // ปฏิเสธการ authorize
    Route::post('/deny', [\App\Http\Controllers\MobileLoginController::class, 'deny'])->name('deny');
});

// Mobile Web Session (เปิดหน้าเว็บจากแอพพร้อม authentication)
// ใช้สำหรับ: Wallet topup, Payment pages, Settings
Route::get('/mobile-web-session', function (\Illuminate\Http\Request $request) {
    $token = $request->get('token');

    if (! $token) {
        return redirect('/login')->with('error', 'ลิงก์ไม่ถูกต้อง');
    }

    // ตรวจสอบ token
    $tokenHash = hash('sha256', $token);
    $cacheKey = 'web_session_token:'.$tokenHash;
    $sessionData = \Cache::get($cacheKey);

    if (! $sessionData) {
        return redirect('/login')->with('error', 'ลิงก์หมดอายุหรือไม่ถูกต้อง กรุณาลองใหม่จากแอพ');
    }

    // ลบ token (ใช้ได้ครั้งเดียว)
    \Cache::forget($cacheKey);

    // ค้นหา user
    $user = \App\Models\User::find($sessionData['user_id']);
    if (! $user) {
        return redirect('/login')->with('error', 'ไม่พบบัญชีผู้ใช้');
    }

    // Login user
    \Auth::login($user);
    $request->session()->regenerate();

    // รับ query params เพิ่มเติม (เช่น amount)
    $queryParams = $request->except(['token']);
    $redirectPath = $sessionData['redirect_path'] ?? '/user/wallet/topup';

    // เพิ่ม query params ลงใน redirect path
    if (! empty($queryParams)) {
        $redirectPath .= (strpos($redirectPath, '?') !== false ? '&' : '?').http_build_query($queryParams);
    }

    // Redirect ไปหน้าที่ต้องการ
    return redirect($redirectPath);
})->name('mobile-web-session');

// Language Switcher (Public - no auth required)
Route::prefix('language')->name('language.')->group(function () {
    Route::get('/switch/{lang}', [\App\Http\Controllers\LanguageSwitcherController::class, 'switch'])->name('switch');
    Route::get('/current', [\App\Http\Controllers\LanguageSwitcherController::class, 'current'])->name('current');
});

// LINE Registration - บังคับเพิ่มเพื่อน LINE OA ก่อนสมัคร
// ✅ ระบบใหม่: polling session + auto-redirect หลังสมัครเสร็จ
Route::prefix('line/registration')->name('line.registration.')->group(function () {
    // หน้าสมัครสมาชิก - แสดง QR Code และ polling status
    Route::get('/', [\App\Http\Controllers\LineRegistrationController::class, 'showRegister'])->name('register');

    // API: เช็คสถานะ session (สำหรับ polling)
    Route::get('/status/{token}', [\App\Http\Controllers\LineRegistrationController::class, 'checkStatus'])->name('status');

    // API: บันทึกการคลิกปุ่มเพิ่มเพื่อน
    Route::post('/click/{token}', [\App\Http\Controllers\LineRegistrationController::class, 'recordAddFriendClick'])->name('click');

    // หน้า complete - auto-login และ redirect ไปอัพเดทข้อมูล
    Route::get('/complete/{token}', [\App\Http\Controllers\LineRegistrationController::class, 'completeRegistration'])->name('complete');

    // API: ยกเลิก session
    Route::post('/cancel/{token}', [\App\Http\Controllers\LineRegistrationController::class, 'cancelSession'])->name('cancel');
});

// OTP Routes
Route::prefix('otp')->name('otp.')->group(function () {
    Route::post('/send', [OtpController::class, 'send'])->name('send');
    Route::post('/verify', [OtpController::class, 'verify'])->name('verify');
    Route::post('/resend', [OtpController::class, 'resend'])->name('resend');
    Route::get('/status', [OtpController::class, 'status'])->name('status');
});

// Frontend Routes
// NOTE: Root route '/' ถูกย้ายไปไว้ต้นไฟล์แล้ว (line 26)

// Original Home Page
// ⚠️ SEO Critical: Landing pages ต้องรองรับ HEAD method
Route::match(['GET', 'HEAD'], '/home', [HomeController::class, 'index'])->name('home.original');
Route::match(['GET', 'HEAD'], '/about', [HomeController::class, 'about'])->name('about');
Route::match(['GET', 'HEAD'], '/about-us', [HomeController::class, 'aboutProfessional'])->name('about.professional');
Route::match(['GET', 'HEAD'], '/contact', [HomeController::class, 'contact'])->name('contact');

// เอกสารวิธีการสมัครสมาชิก (สำหรับผู้ใช้ทั่วไป)
Route::match(['GET', 'HEAD'], '/how-to-register', function () {
    return view('frontend.pages.how-to-register');
})->name('documents.how-to-register');

// หน้าเก่า 3 Doors และ Presentation ถูกลบออกแล้ว (เปลี่ยนไปใช้หน้าแรกใหม่)
// @deprecated 2025-12-03

// Wiki Routes (New modular system)
// ⚠️ SEO High: Wiki pages มี content value สูง
Route::prefix('wiki')->name('wiki.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\Frontend\WikiController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/content/{category}/{section?}', [\App\Http\Controllers\Frontend\WikiController::class, 'getContent'])->name('content');
    Route::match(['GET', 'HEAD'], '/search', [\App\Http\Controllers\Frontend\WikiController::class, 'search'])->name('search');
});

// Legacy wiki route (redirect to new system)
Route::get('/platform-wiki', function () {
    return redirect()->route('wiki.index');
})->name('platform.wiki');

// 🌸 น้อง Eve — ผู้ช่วย AI หน้าเว็บ (ฝั่งลูกค้า) — public + throttle กัน spam
Route::post('/eve/chat', [\App\Http\Controllers\EveAssistantController::class, 'chat'])
    ->middleware('throttle:30,1')->name('eve.chat');

// Dynamic Page Routes (Privacy Policy, Terms, etc.)
// ⚠️ Legal Critical: Privacy Policy, Terms of Service ต้องเข้าถึงได้ตลอดเวลา
Route::match(['GET', 'HEAD'], '/page/{slug}', [PageController::class, 'show'])->name('page.show');

// Marketplace Routes (Public browsing, Auth for renting)
// ⚠️ E-commerce Critical: Marketplace pages ต้องถูก index โดย search engines
Route::prefix('marketplace')->name('marketplace.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\MarketplaceController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/{id}', [\App\Http\Controllers\MarketplaceController::class, 'show'])->name('show');

    // Bot marketplace routes (alias to chatbot.marketplace)
    Route::prefix('bots')->name('bots.')->group(function () {
        Route::match(['GET', 'HEAD'], '/{id}', [\App\Http\Controllers\Chatbot\MarketplaceWebController::class, 'show'])->name('show');
    });

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::post('/{id}/rent', [\App\Http\Controllers\MarketplaceController::class, 'rent'])->name('rent');
        Route::get('/rent-success/{rentalId}', [\App\Http\Controllers\MarketplaceController::class, 'rentSuccess'])->name('rent-success');
    });
});

// My Rentals Routes (Authenticated users)
Route::middleware('auth')->prefix('my-rentals')->name('my-rentals.')->group(function () {
    Route::get('/', [\App\Http\Controllers\RentalManagementController::class, 'index'])->name('index');
    Route::get('/{id}', [\App\Http\Controllers\RentalManagementController::class, 'show'])->name('show');
    Route::post('/{id}/cancel', [\App\Http\Controllers\RentalManagementController::class, 'cancel'])->name('cancel');
    Route::post('/{id}/toggle-auto-renew', [\App\Http\Controllers\RentalManagementController::class, 'toggleAutoRenew'])->name('toggle-auto-renew');
    Route::get('/transactions/all', [\App\Http\Controllers\RentalManagementController::class, 'transactions'])->name('transactions');
});

// Owner Dashboard Routes (Bot owners)
Route::middleware('auth')->prefix('owner-dashboard')->name('owner-dashboard.')->group(function () {
    Route::get('/', [\App\Http\Controllers\OwnerDashboardController::class, 'index'])->name('index');
    Route::get('/earnings', [\App\Http\Controllers\OwnerDashboardController::class, 'earnings'])->name('earnings');
    Route::get('/rentals', [\App\Http\Controllers\OwnerDashboardController::class, 'rentals'])->name('rentals');
    Route::get('/payouts', [\App\Http\Controllers\OwnerDashboardController::class, 'payouts'])->name('payouts');
    Route::post('/request-payout', [\App\Http\Controllers\OwnerDashboardController::class, 'requestPayout'])->name('request-payout');
});

// ========================================
// E-COMMERCE ROUTES (Shop System)
// ========================================

// NEW: Storefront Routes (AliExpress-style Premium Storefront)
// ⚠️ E-commerce Critical: หน้าร้านหลักแบบ AliExpress ต้องถูก index
Route::prefix('storefront')->name('storefront.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\StorefrontController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/stores', [\App\Http\Controllers\StorefrontController::class, 'stores'])->name('stores');
    Route::match(['GET', 'HEAD'], '/search', [\App\Http\Controllers\StorefrontController::class, 'quickSearch'])->name('search');
    Route::match(['GET', 'HEAD'], '/products', [\App\Http\Controllers\StorefrontController::class, 'loadMoreProducts'])->name('products');
});

// หน้าร้าน affiliate เฉพาะแพลตฟอร์ม (Lazada / AliExpress)
// ใช้ StorefrontController::showStore + view storefront.store (การ์ด affiliate + แบรนด์ร้าน/สีของร้าน)
Route::match(['GET', 'HEAD'], '/lazada', fn (\App\Http\Controllers\StorefrontController $c) => $c->showStore(\App\Models\VendorStore::LAZADA_STORE_SLUG))->name('mall.lazada');
Route::match(['GET', 'HEAD'], '/aliexpress', fn (\App\Http\Controllers\StorefrontController $c) => $c->showStore(\App\Models\VendorStore::ALIEXPRESS_STORE_SLUG))->name('mall.aliexpress');

// Store Pages (Individual Vendor Store Pages)
// ⚠️ E-commerce Critical: หน้าร้านค้าแต่ละร้าน ต้องถูก index
Route::prefix('store')->name('store.')->group(function () {
    Route::match(['GET', 'HEAD'], '/{slug}', [\App\Http\Controllers\VendorStoreController::class, 'show'])->name('show');
    Route::match(['GET', 'HEAD'], '/{storeSlug}/product/{productSlug}', [\App\Http\Controllers\VendorStoreController::class, 'showProduct'])->name('product');
});

// Shop Routes (Legacy - Redirect to Storefront)
// ⚠️ Redirects: เปลี่ยนมาใช้ Storefront ใหม่แทน
Route::prefix('shop')->name('shop.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', function () {
        return redirect()->route('storefront.index');
    })->name('index');
    Route::match(['GET', 'HEAD'], '/category/{slug}', function ($slug) {
        return redirect()->route('storefront.index', ['category' => $slug]);
    })->name('category');
    Route::match(['GET', 'HEAD'], '/search', function (\Illuminate\Http\Request $request) {
        return redirect()->route('storefront.index', ['q' => $request->q]);
    })->name('search');
    Route::match(['GET', 'HEAD'], '/{slug}', [\App\Http\Controllers\ShopController::class, 'show'])->name('show');
});

// Vendor Store Routes (Legacy - Redirect to Storefront)
// ⚠️ Redirects: เปลี่ยนมาใช้ Storefront ใหม่แทน
Route::prefix('stores')->name('vendor.stores.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', function () {
        return redirect()->route('storefront.index');
    })->name('index');
    Route::match(['GET', 'HEAD'], '/{slug}', function ($slug) {
        return redirect()->route('store.show', $slug);
    })->name('show');
});

// Official Shop Routes (Ultra Premium Design V3)
// ⚠️ E-commerce Critical: ร้านของระบบ พรีเมี่ยมหรูหรา
Route::prefix('official-shop')->name('official-shop.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\OfficialShopController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/featured', [\App\Http\Controllers\OfficialShopController::class, 'featured'])->name('featured');
    Route::match(['GET', 'HEAD'], '/category/{slug}', [\App\Http\Controllers\OfficialShopController::class, 'category'])->name('category');
    Route::match(['GET', 'HEAD'], '/search', [\App\Http\Controllers\OfficialShopController::class, 'quickSearch'])->name('search');

    // Coin Purchase Routes (ต้อง login)
    Route::middleware('auth')->group(function () {
        Route::post('/{slug}/purchase-with-coin', [\App\Http\Controllers\OfficialShopController::class, 'purchaseWithCoin'])->name('purchase-with-coin');
        Route::match(['GET', 'HEAD'], '/order/{orderNumber}/success', [\App\Http\Controllers\OfficialShopController::class, 'purchaseSuccess'])->name('purchase-success');
    });

    // Product Detail (ต้องอยู่หลัง routes อื่น)
    Route::match(['GET', 'HEAD'], '/{slug}', [\App\Http\Controllers\OfficialShopController::class, 'show'])->name('show');
});

// Admin Store Routes (Legacy - Redirect to Storefront)
// ⚠️ Redirects: เปลี่ยนมาใช้ Storefront ใหม่แทน
Route::prefix('admin-store')->name('admin-store.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', function () {
        return redirect()->route('storefront.index', ['filter' => 'official']);
    })->name('index');
    Route::match(['GET', 'HEAD'], '/{slug}', function ($slug) {
        return redirect()->route('shop.show', $slug);
    })->name('show');
});

// Cart Routes (Authenticated)
Route::middleware('auth')->prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CartController::class, 'index'])->name('index');
    Route::post('/add', [\App\Http\Controllers\CartController::class, 'add'])->name('add');
    Route::put('/{id}', [\App\Http\Controllers\CartController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\CartController::class, 'remove'])->name('remove');
    Route::delete('/', [\App\Http\Controllers\CartController::class, 'clear'])->name('clear');
    Route::get('/count', [\App\Http\Controllers\CartController::class, 'count'])->name('count');
    Route::get('/mini', [\App\Http\Controllers\CartController::class, 'mini'])->name('mini');
});

// Checkout Routes (Authenticated)
Route::middleware('auth')->prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CheckoutController::class, 'index'])->name('index');
    Route::post('/process', [\App\Http\Controllers\CheckoutController::class, 'process'])->middleware('turnstile:checkout')->name('process');
    Route::get('/payment/{orderId}', [\App\Http\Controllers\CheckoutController::class, 'payment'])->name('payment');
    Route::post('/payment/{orderId}/process', [\App\Http\Controllers\CheckoutController::class, 'processPayment'])->name('payment.process');
    Route::get('/processing/{orderId}', [\App\Http\Controllers\CheckoutController::class, 'paymentProcessing'])->name('processing');
    Route::get('/success/{orderId}', [\App\Http\Controllers\CheckoutController::class, 'success'])->name('success');
    // 💳 Stripe (บัตรเครดิต/เดบิต) — กลับมาจากหน้าจ่ายของ Stripe
    Route::get('/stripe/{orderId}/return', [\App\Http\Controllers\CheckoutController::class, 'stripeReturn'])->name('stripe.return');
    Route::get('/stripe/{orderId}/cancel', [\App\Http\Controllers\CheckoutController::class, 'stripeCancel'])->name('stripe.cancel');
});

// Payment Callback Routes (for webhooks - no auth required)
Route::post('/payment/callback/{transactionId}', [\App\Http\Controllers\CheckoutController::class, 'paymentCallback'])->name('payment.callback');

// Shipping Addresses Routes (Authenticated)
Route::middleware('auth')->prefix('shipping-addresses')->name('shipping-addresses.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ShippingAddressController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\ShippingAddressController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\ShippingAddressController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [\App\Http\Controllers\ShippingAddressController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\ShippingAddressController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\ShippingAddressController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/set-default', [\App\Http\Controllers\ShippingAddressController::class, 'setDefault'])->name('set-default');
});

// Orders Routes (Customer orders - Authenticated)
Route::middleware('auth')->prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [\App\Http\Controllers\OrderController::class, 'index'])->name('index');
    Route::get('/{id}', [\App\Http\Controllers\OrderController::class, 'show'])->name('show');
    Route::post('/{id}/cancel', [\App\Http\Controllers\OrderController::class, 'cancel'])->name('cancel');
    Route::post('/{id}/retry-payment', [\App\Http\Controllers\OrderController::class, 'retryPayment'])->name('retry-payment');
    Route::post('/{id}/confirm-received', [\App\Http\Controllers\OrderController::class, 'confirmReceived'])->name('confirm-received');

    // Reviews
    Route::get('/{orderId}/items/{itemId}/review', [\App\Http\Controllers\OrderController::class, 'showReviewForm'])->name('review.form');
    Route::post('/{orderId}/items/{itemId}/review', [\App\Http\Controllers\OrderController::class, 'submitReview'])->middleware('turnstile:review')->name('review.submit');

    // Tracking (AJAX)
    Route::get('/{id}/tracking/realtime', [\App\Http\Controllers\OrderController::class, 'trackingRealtime'])->name('tracking.realtime');
});

// ========================================
// HOTEL BOOKING ROUTES
// ========================================

// Public Hotel Browsing Routes
// ⚠️ E-commerce Critical: Hotel booking system ต้องถูก index โดย search engines
Route::prefix('hotels')->name('hotels.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\HotelController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/featured', [\App\Http\Controllers\HotelController::class, 'featured'])->name('featured');
    Route::match(['GET', 'HEAD'], '/city/{city}', [\App\Http\Controllers\HotelController::class, 'byCity'])->name('by-city');
    Route::match(['GET', 'HEAD'], '/search', [\App\Http\Controllers\HotelController::class, 'search'])->name('search');
    Route::match(['GET', 'HEAD'], '/autocomplete', [\App\Http\Controllers\HotelController::class, 'autocomplete'])->name('autocomplete');
    Route::post('/check-availability', [\App\Http\Controllers\HotelController::class, 'checkAvailability'])->name('check-availability');

    // Province & Region Routes (GPS Location Search)
    Route::match(['GET', 'HEAD'], '/province/{provinceId}', [\App\Http\Controllers\HotelController::class, 'byProvince'])->name('by-province');
    Route::match(['GET', 'HEAD'], '/region/{region}', [\App\Http\Controllers\HotelController::class, 'byRegion'])->name('by-region');
    Route::post('/nearby', [\App\Http\Controllers\HotelController::class, 'nearby'])->name('nearby');
    Route::get('/provinces', [\App\Http\Controllers\HotelController::class, 'getProvinces'])->name('provinces');

    Route::match(['GET', 'HEAD'], '/{slug}', [\App\Http\Controllers\HotelController::class, 'show'])->name('show');

    // Hotel Reviews (Public viewing)
    Route::match(['GET', 'HEAD'], '/{slug}/reviews', [\App\Http\Controllers\HotelReviewController::class, 'index'])->name('reviews.index');
});

// Hotel Booking Routes (Authenticated)
Route::middleware('auth')->prefix('hotels')->name('hotels.')->group(function () {
    // Booking Management
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\HotelBookingController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\HotelBookingController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\HotelBookingController::class, 'store'])->name('store');
        Route::get('/{bookingNumber}', [\App\Http\Controllers\HotelBookingController::class, 'show'])->name('show');
        Route::get('/{bookingNumber}/edit', [\App\Http\Controllers\HotelBookingController::class, 'edit'])->name('edit');
        Route::put('/{bookingNumber}', [\App\Http\Controllers\HotelBookingController::class, 'update'])->name('update');
        Route::post('/{bookingNumber}/cancel', [\App\Http\Controllers\HotelBookingController::class, 'cancel'])->name('cancel');
        Route::get('/{bookingNumber}/payment', [\App\Http\Controllers\HotelBookingController::class, 'payment'])->name('payment');
        Route::post('/{bookingNumber}/payment', [\App\Http\Controllers\HotelBookingController::class, 'processPayment'])->name('process-payment');
        Route::post('/calculate-price', [\App\Http\Controllers\HotelBookingController::class, 'calculatePrice'])->name('calculate-price');
    });

    // Review Management
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/booking/{bookingNumber}', [\App\Http\Controllers\HotelReviewController::class, 'create'])->name('create');
        Route::post('/booking/{bookingNumber}', [\App\Http\Controllers\HotelReviewController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\HotelReviewController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [\App\Http\Controllers\HotelReviewController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\HotelReviewController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\HotelReviewController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/helpful', [\App\Http\Controllers\HotelReviewController::class, 'markHelpful'])->name('helpful');
        Route::post('/{id}/not-helpful', [\App\Http\Controllers\HotelReviewController::class, 'markNotHelpful'])->name('not-helpful');
    });
});

// ========================================
// SMART SLIDER FRONTEND ROUTES
// ========================================
use App\Http\Controllers\Frontend\SliderController;

// ⚠️ Public Component: Slider component ต้องรองรับ HEAD method
Route::prefix('sliders')->name('sliders.')->group(function () {
    Route::match(['GET', 'HEAD'], '/{idOrAlias}', [SliderController::class, 'show'])->name('show');
    Route::match(['GET', 'HEAD'], '/{idOrAlias}/data', [SliderController::class, 'getData'])->name('data');
    Route::post('/{slider}/track-click', [SliderController::class, 'trackClick'])->name('track-click');
    Route::post('/{slider}/track-slide-change', [SliderController::class, 'trackSlideChange'])->name('track-slide-change');
});

// User Routes (Protected by auth middleware and role check)
Route::middleware(['auth', 'role:user'])->prefix('user')->name('user.')->group(function () {
    require __DIR__.'/user.php';
});

// Admin Routes (Protected by auth middleware and role check)
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    require __DIR__.'/admin.php';
});

// Seller Routes (Protected by auth middleware and role check)
Route::middleware(['auth', 'role:seller,super_admin'])->prefix('seller')->name('seller.')->group(function () {
    require __DIR__.'/seller.php';
});

// POS Public Routes (ไม่ต้อง auth - สำหรับ health check และ MAUI App)
Route::prefix('pos')->name('pos.')->group(function () {
    // Health Check API - สำหรับ MAUI App ตรวจสอบการเชื่อมต่อ
    Route::get('/api/health', [\App\Http\Controllers\Pos\PosApiController::class, 'health'])->name('api.health');
});

// POS Routes (Protected by auth middleware)
Route::middleware('auth')->prefix('pos')->name('pos.')->group(function () {
    require __DIR__.'/pos.php';
});

// Tarot Reading Routes (Public)
// ⚠️ Public Service: Tarot reading service ต้องถูก index โดย search engines
Route::prefix('tarot')->name('tarot.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\TarotReadingController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/category/{slug}', [\App\Http\Controllers\TarotReadingController::class, 'showCategory'])->name('category');
    Route::post('/start', [\App\Http\Controllers\TarotReadingController::class, 'startReading'])->name('start');
    Route::match(['GET', 'HEAD'], '/select-cards/{readingId}', [\App\Http\Controllers\TarotReadingController::class, 'showCardSelection'])->name('select-cards');
    Route::post('/save-selection', [\App\Http\Controllers\TarotReadingController::class, 'saveCardSelection'])->name('save-selection');
    Route::match(['GET', 'HEAD'], '/reading/{id}', [\App\Http\Controllers\TarotReadingController::class, 'showReading'])->name('reading.show');
    Route::match(['GET', 'HEAD'], '/card-backs', [\App\Http\Controllers\TarotReadingController::class, 'getCardBackImages'])->name('card-backs');

    // Cart routes
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\TarotCartController::class, 'index'])->name('index');
        Route::post('/add', [\App\Http\Controllers\TarotCartController::class, 'addToCart'])->name('add');
        Route::delete('/remove/{id}', [\App\Http\Controllers\TarotCartController::class, 'removeItem'])->name('remove');
        Route::post('/clear', [\App\Http\Controllers\TarotCartController::class, 'clearCart'])->name('clear');
        Route::match(['GET', 'HEAD'], '/checkout', [\App\Http\Controllers\TarotCartController::class, 'checkout'])->name('checkout');
        Route::post('/checkout/process', [\App\Http\Controllers\TarotCartController::class, 'processCheckout'])->name('processCheckout');
    });

    // Payment routes
    Route::match(['GET', 'HEAD'], '/payment', [\App\Http\Controllers\TarotReadingController::class, 'payment'])->name('payment');
    Route::post('/payment/process', [\App\Http\Controllers\TarotReadingController::class, 'processPayment'])->name('payment.process');
    Route::match(['GET', 'HEAD'], '/payment/waiting/{transaction}', [\App\Http\Controllers\TarotCartController::class, 'paymentWaiting'])->name('payment.waiting');
    Route::match(['GET', 'HEAD'], '/payment/status/{transaction}', [\App\Http\Controllers\TarotCartController::class, 'paymentStatus'])->name('payment.status');

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::post('/reading/{id}/save', [\App\Http\Controllers\TarotReadingController::class, 'saveReading'])->name('reading.save');
        Route::get('/history', [\App\Http\Controllers\TarotReadingController::class, 'history'])->name('history');
        Route::get('/saved', [\App\Http\Controllers\TarotReadingController::class, 'savedReadings'])->name('saved');
    });
});

// ================================================
// Horoscope Public Routes (ระบบดูดวงออนไลน์สาธารณะ)
// ⚠️ Public Service: ดูดวงฟรี ต้องถูก index โดย search engines (SEO friendly)
// ================================================
Route::prefix('horoscope')->name('horoscope.')->group(function () {
    // หน้าแรกดูดวง (แสดงทุกหมวด)
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\Frontend\HoroscopeHomeController::class, 'index'])->name('home');

    // ดวงรายวัน 12 ราศี + 7 วันเกิด
    Route::prefix('daily')->name('daily.')->group(function () {
        Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\Frontend\HoroscopeDailyController::class, 'index'])->name('index');
        Route::match(['GET', 'HEAD'], '/zodiac/{slug}', [\App\Http\Controllers\Frontend\HoroscopeDailyController::class, 'showZodiac'])->name('zodiac');
        Route::match(['GET', 'HEAD'], '/birth-day/{day}', [\App\Http\Controllers\Frontend\HoroscopeDailyController::class, 'showBirthDay'])->name('birth-day')
            ->where('day', '[0-6]');
    });

    // ไพ่ทาโรต์ Interactive
    Route::prefix('tarot')->name('tarot.')->group(function () {
        Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\Frontend\HoroscopeTarotController::class, 'index'])->name('index');
        Route::match(['GET', 'HEAD'], '/quick', [\App\Http\Controllers\Frontend\HoroscopeTarotController::class, 'quickReading'])->name('quick');
        Route::post('/quick-interpret', [\App\Http\Controllers\Frontend\HoroscopeTarotController::class, 'quickInterpret'])->name('quick-interpret');
    });

    // ทำนายชื่อ/เลขศาสตร์
    Route::prefix('numerology')->name('numerology.')->group(function () {
        Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\Frontend\HoroscopeNumerologyController::class, 'index'])->name('index');
        Route::post('/analyze-name', [\App\Http\Controllers\Frontend\HoroscopeNumerologyController::class, 'analyzeName'])->name('analyze-name');
        Route::post('/analyze-phone', [\App\Http\Controllers\Frontend\HoroscopeNumerologyController::class, 'analyzePhone'])->name('analyze-phone');
        Route::post('/analyze-license', [\App\Http\Controllers\Frontend\HoroscopeNumerologyController::class, 'analyzeLicensePlate'])->name('analyze-license');
        Route::post('/analyze-idcard', [\App\Http\Controllers\Frontend\HoroscopeNumerologyController::class, 'analyzeIdCard'])->name('analyze-idcard');
        Route::post('/analyze-birthday', [\App\Http\Controllers\Frontend\HoroscopeNumerologyController::class, 'analyzeBirthday'])->name('analyze-birthday');
        Route::match(['GET', 'HEAD'], '/result/{id}', [\App\Http\Controllers\Frontend\HoroscopeNumerologyController::class, 'showResult'])->name('result');
    });

    // ทำนายฝัน + เลขเด็ด
    Route::prefix('dream')->name('dream.')->group(function () {
        Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\Frontend\HoroscopeDreamController::class, 'index'])->name('index');
        Route::post('/search', [\App\Http\Controllers\Frontend\HoroscopeDreamController::class, 'search'])->name('search');
        Route::post('/interpret', [\App\Http\Controllers\Frontend\HoroscopeDreamController::class, 'interpret'])->name('interpret');
        Route::match(['GET', 'HEAD'], '/category/{slug}', [\App\Http\Controllers\Frontend\HoroscopeDreamController::class, 'showCategory'])->name('category');
        Route::match(['GET', 'HEAD'], '/result/{id}', [\App\Http\Controllers\Frontend\HoroscopeDreamController::class, 'showResult'])->name('result');
    });
});

// QR Code & Barcode Generator Routes (Public)
// ⚠️ Public Tool: QR/Barcode generator เป็น SEO-friendly tool
Route::prefix('qr-barcode')->name('qr-barcode.')->group(function () {
    // Public routes
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\QrBarcodeController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/scanner', [\App\Http\Controllers\QrBarcodeController::class, 'scanner'])->name('scanner');
    Route::post('/decode', [\App\Http\Controllers\QrBarcodeController::class, 'decode'])->name('decode');
    Route::match(['GET', 'HEAD'], '/templates', [\App\Http\Controllers\QrBarcodeController::class, 'templates'])->name('templates');
    Route::match(['GET', 'HEAD'], '/gallery', [\App\Http\Controllers\QrBarcodeController::class, 'gallery'])->name('gallery');
    Route::match(['GET', 'HEAD'], '/show/{id}', [\App\Http\Controllers\QrBarcodeController::class, 'show'])->name('show');
    Route::match(['GET', 'HEAD'], '/r/{shortUrl}', [\App\Http\Controllers\QrBarcodeController::class, 'redirect'])->name('redirect');

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::post('/store', [\App\Http\Controllers\QrBarcodeController::class, 'store'])->name('store');
        Route::get('/history', [\App\Http\Controllers\QrBarcodeController::class, 'history'])->name('history');
        Route::get('/analytics', [\App\Http\Controllers\QrBarcodeController::class, 'analytics'])->name('analytics');
        Route::put('/update/{id}', [\App\Http\Controllers\QrBarcodeController::class, 'update'])->name('update');
        Route::delete('/delete/{id}', [\App\Http\Controllers\QrBarcodeController::class, 'destroy'])->name('destroy');
        Route::post('/favorite/{id}', [\App\Http\Controllers\QrBarcodeController::class, 'toggleFavorite'])->name('favorite');
        Route::post('/batch-generate', [\App\Http\Controllers\QrBarcodeController::class, 'batchGenerate'])->name('batch-generate');
    });
});

// ========================================
// LABEL DESIGNER SYSTEM ROUTES
// ========================================
// 🏷️ ระบบออกแบบฉลาก บาร์โค้ด QR Code สำหรับสติ๊กเกอร์ติดผลิตภัณฑ์
// เครื่องมือฟรีสำหรับร้านค้าทุกร้าน รองรับเครื่องพิมพ์หลากหลาย

Route::prefix('label-designer')->name('label-designer.')->group(function () {
    // Public routes (ไม่ต้อง login)
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\LabelDesignerController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/create', [\App\Http\Controllers\LabelDesignerController::class, 'create'])->name('create');

    // API routes สำหรับดึงข้อมูล (public)
    Route::get('/api/templates', [\App\Http\Controllers\LabelDesignerController::class, 'templates'])->name('api.templates');
    Route::get('/api/paper-sizes', [\App\Http\Controllers\LabelDesignerController::class, 'paperSizes'])->name('api.paper-sizes');
    Route::get('/api/barcode-formats', [\App\Http\Controllers\LabelDesignerController::class, 'barcodeFormats'])->name('api.barcode-formats');
    Route::post('/api/generate-barcode', [\App\Http\Controllers\LabelDesignerController::class, 'generateBarcode'])->name('api.generate-barcode');
    Route::post('/api/generate-qrcode', [\App\Http\Controllers\LabelDesignerController::class, 'generateQrcode'])->name('api.generate-qrcode');

    // Authenticated routes (ต้อง login)
    Route::middleware('auth')->group(function () {
        // CRUD ฉลาก
        Route::post('/store', [\App\Http\Controllers\LabelDesignerController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [\App\Http\Controllers\LabelDesignerController::class, 'edit'])->name('edit');
        Route::put('/update/{id}', [\App\Http\Controllers\LabelDesignerController::class, 'update'])->name('update');
        Route::delete('/delete/{id}', [\App\Http\Controllers\LabelDesignerController::class, 'destroy'])->name('destroy');

        // ฟังก์ชันเพิ่มเติม
        Route::get('/my-labels', [\App\Http\Controllers\LabelDesignerController::class, 'myLabels'])->name('my-labels');
        Route::post('/favorite/{id}', [\App\Http\Controllers\LabelDesignerController::class, 'toggleFavorite'])->name('favorite');
        Route::post('/duplicate/{id}', [\App\Http\Controllers\LabelDesignerController::class, 'duplicate'])->name('duplicate');
        Route::post('/print/{id}', [\App\Http\Controllers\LabelDesignerController::class, 'recordPrint'])->name('print');
        Route::get('/export-pdf/{id}', [\App\Http\Controllers\LabelDesignerController::class, 'exportPdf'])->name('export-pdf');
        Route::post('/create-template/{id}', [\App\Http\Controllers\LabelDesignerController::class, 'createTemplateFromLabel'])->name('create-template');
    });
});

// ========================================
// SOFTWARE SALES SYSTEM ROUTES
// ========================================
require __DIR__.'/software_sales.php';

// Legal Pages
// ⚠️ Legal Compliance: Legal pages ต้องเข้าถึงได้ตลอดเวลา
Route::match(['GET', 'HEAD'], '/cookie-policy', function () {
    return view('cookie-policy');
})->name('cookie-policy');

Route::match(['GET', 'HEAD'], '/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy-policy');

Route::match(['GET', 'HEAD'], '/terms-of-service', function () {
    return view('terms-of-service');
})->name('terms-of-service');

// 🔗 รองรับ URL เดิม .html (เคยเป็นไฟล์ static ธีมเก่า — ลบไฟล์แล้ว ให้ route V4 ทำงานแทน)
Route::match(['GET', 'HEAD'], '/terms-of-service.html', function () {
    return view('terms-of-service');
})->name('terms-of-service.html');

Route::match(['GET', 'HEAD'], '/privacy-policy.html', function () {
    return view('privacy-policy');
})->name('privacy-policy.html');

// การรับประกันซอฟต์แวร์ (รับประกันโดย บริษัท เอ็กซ์แมน เอ็นเตอร์ไพรส์ จำกัด)
Route::match(['GET', 'HEAD'], '/software-warranty', function () {
    return view('software-warranty');
})->name('software-warranty');

// Facebook App Review Demo Page
// หน้าสาธิตสำหรับ Facebook App Review - แสดงการใช้งาน permissions
Route::match(['GET', 'HEAD'], '/facebook-app-review-demo', function () {
    return view('facebook-app-review-demo');
})->name('facebook-app-review-demo');

// Cryptocurrency Price Charts
// ⚠️ Public Tool: Crypto charts ต้องถูก index โดย search engines
Route::prefix('crypto')->name('crypto.')->group(function () {
    Route::match(['GET', 'HEAD'], '/charts', [\App\Http\Controllers\CryptoPriceChartController::class, 'index'])->name('charts');
});

// Cryptocurrency API Routes
Route::prefix('api/crypto')->name('api.crypto.')->group(function () {
    Route::get('/chart/{currency}', [\App\Http\Controllers\CryptoPriceChartController::class, 'getChartData'])->name('chart');
    Route::get('/compare', [\App\Http\Controllers\CryptoPriceChartController::class, 'getComparisonData'])->name('compare');
    Route::get('/market-overview', [\App\Http\Controllers\CryptoPriceChartController::class, 'getMarketOverview'])->name('market-overview');
    Route::get('/realtime-prices', [\App\Http\Controllers\CryptoPriceChartController::class, 'getRealTimePrices'])->name('realtime-prices');
});

// ========================================
// CHATBOT RENTAL SYSTEM ROUTES
// ========================================
Route::prefix('chatbot')->name('chatbot.')->middleware('auth')->group(function () {
    // Bot Management
    Route::get('/', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'store'])->name('store');
    Route::get('/{id}', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/keywords', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'keywords'])->name('keywords');
    Route::get('/{id}/integrations', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'integrations'])->name('integrations');
    Route::get('/{id}/auto-content', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'autoContent'])->name('auto-content');
    Route::get('/{id}/analytics', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'analytics'])->name('analytics');
    Route::get('/{id}/playground', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'playground'])->name('playground');
});

// Chatbot Marketplace
// ⚠️ Marketplace: Chatbot marketplace ต้องถูก index โดย search engines
Route::prefix('chatbot/marketplace')->name('chatbot.marketplace.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\Chatbot\MarketplaceWebController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/{id}', [\App\Http\Controllers\Chatbot\MarketplaceWebController::class, 'show'])->name('show');

    Route::middleware('auth')->group(function () {
        Route::get('/my-rentals', [\App\Http\Controllers\Chatbot\MarketplaceWebController::class, 'myRentals'])->name('my-rentals');
        Route::get('/my-earnings', [\App\Http\Controllers\Chatbot\MarketplaceWebController::class, 'myEarnings'])->name('my-earnings');
    });
});

// ========================================
// TRADING BOT SYSTEM ROUTES
// ========================================

use App\Http\Controllers\Admin\TradingBotAdminController;
use App\Http\Controllers\TradingBotController;

// Public Trading Bot Marketplace
// ⚠️ Marketplace: Trading bot marketplace ต้องถูก index โดย search engines
Route::prefix('trading-bot')->name('trading-bot.')->group(function () {
    Route::match(['GET', 'HEAD'], '/marketplace', [TradingBotController::class, 'marketplace'])->name('marketplace');
});

// Authenticated Trading Bot Routes
Route::middleware('auth')->prefix('trading-bot')->name('trading-bot.')->group(function () {
    // Dashboard
    Route::get('/', [TradingBotController::class, 'index'])->name('index');

    // Subscription
    Route::post('/subscribe/{package}', [TradingBotController::class, 'subscribe'])->name('subscribe');

    // Bot Management
    Route::get('/bots/create', [TradingBotController::class, 'create'])->name('create');
    Route::post('/bots', [TradingBotController::class, 'store'])->name('store');
    Route::get('/bots/{bot}', [TradingBotController::class, 'show'])->name('show');
    Route::get('/bots/{bot}/edit', [TradingBotController::class, 'edit'])->name('edit');
    Route::put('/bots/{bot}', [TradingBotController::class, 'update'])->name('update');
    Route::delete('/bots/{bot}', [TradingBotController::class, 'destroy'])->name('destroy');
    Route::post('/bots/{bot}/start', [TradingBotController::class, 'start'])->name('start');
    Route::post('/bots/{bot}/stop', [TradingBotController::class, 'stop'])->name('stop');
    Route::get('/bots/{bot}/analytics', [TradingBotController::class, 'analytics'])->name('analytics');
    Route::get('/bots/{bot}/advanced-config', [TradingBotController::class, 'advancedConfig'])->name('advanced-config');
    Route::get('/bots/{bot}/pro-analytics', [TradingBotController::class, 'proAnalytics'])->name('pro-analytics');

    // Multi-Exchange & Risk Management
    Route::get('/multi-exchange', [TradingBotController::class, 'multiExchange'])->name('multi-exchange');
    Route::get('/risk-management', [TradingBotController::class, 'riskManagement'])->name('risk-management');

    // Trading Accounts
    Route::get('/accounts', [TradingBotController::class, 'accounts'])->name('accounts');
    Route::post('/accounts', [TradingBotController::class, 'storeAccount'])->name('accounts.store');

    // Strategies
    Route::get('/strategies', [TradingBotController::class, 'strategies'])->name('strategies');
    Route::get('/strategies/create', [TradingBotController::class, 'createStrategy'])->name('strategies.create');
    Route::post('/strategies', [TradingBotController::class, 'storeStrategy'])->name('strategies.store');
});

// Admin Trading Bot Routes
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin/trading-bot')->name('admin.trading-bot.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [TradingBotAdminController::class, 'dashboard'])->name('dashboard');

    // Package Management
    Route::get('/packages', [TradingBotAdminController::class, 'packages'])->name('packages.index');
    Route::get('/packages/create', [TradingBotAdminController::class, 'createPackage'])->name('packages.create');
    Route::post('/packages', [TradingBotAdminController::class, 'storePackage'])->name('packages.store');
    Route::get('/packages/{package}/edit', [TradingBotAdminController::class, 'editPackage'])->name('packages.edit');
    Route::put('/packages/{package}', [TradingBotAdminController::class, 'updatePackage'])->name('packages.update');
    Route::delete('/packages/{package}', [TradingBotAdminController::class, 'destroyPackage'])->name('packages.destroy');

    // Subscription Management
    Route::get('/subscriptions', [TradingBotAdminController::class, 'subscriptions'])->name('subscriptions.index');
    Route::get('/subscriptions/{subscription}', [TradingBotAdminController::class, 'showSubscription'])->name('subscriptions.show');
    Route::post('/subscriptions/{subscription}/cancel', [TradingBotAdminController::class, 'cancelSubscription'])->name('subscriptions.cancel');

    // Bot Management
    Route::get('/bots', [TradingBotAdminController::class, 'bots'])->name('bots.index');
    Route::get('/bots/{bot}', [TradingBotAdminController::class, 'showBot'])->name('bots.show');
    Route::post('/bots/{bot}/stop', [TradingBotAdminController::class, 'stopBot'])->name('bots.stop');
    Route::post('/bots/{bot}/start', [TradingBotAdminController::class, 'startBot'])->name('bots.start');
    Route::delete('/bots/{bot}', [TradingBotAdminController::class, 'destroyBot'])->name('bots.destroy');

    // Exchange Management
    Route::get('/exchanges', [TradingBotAdminController::class, 'exchanges'])->name('exchanges.index');
    Route::post('/exchanges', [TradingBotAdminController::class, 'storeExchange'])->name('exchanges.store');
    Route::put('/exchanges/{exchange}', [TradingBotAdminController::class, 'updateExchange'])->name('exchanges.update');

    // Analytics
    Route::get('/analytics', [TradingBotAdminController::class, 'analytics'])->name('analytics');

    // Arbitrage Monitor
    Route::get('/arbitrage-monitor', [TradingBotAdminController::class, 'arbitrageMonitor'])->name('arbitrage-monitor');

    // Settings
    Route::get('/settings', [TradingBotAdminController::class, 'settings'])->name('settings');
    Route::put('/settings', [TradingBotAdminController::class, 'updateSettings'])->name('settings.update');
});

/*
|--------------------------------------------------------------------------
| TPIX Whitepaper Routes
|--------------------------------------------------------------------------
|
| Routes สำหรับแสดงและ export ไวท์เปเปอร์ของ TPIX
|
*/
// ⚠️ Marketing Critical: TPIX Whitepaper ต้องถูก index โดย search engines
Route::prefix('tpix')->name('tpix.')->group(function () {
    // แสดงหน้าไวท์เปเปอร์
    Route::match(['GET', 'HEAD'], '/whitepaper', [TPIXWhitepaperController::class, 'index'])
        ->name('whitepaper.index');

    // Export PDF
    Route::match(['GET', 'HEAD'], '/whitepaper/pdf', [TPIXWhitepaperController::class, 'exportPdf'])
        ->name('whitepaper.pdf');
});

/*
|--------------------------------------------------------------------------
| Feature Documents Routes (Public)
|--------------------------------------------------------------------------
|
| Routes สำหรับหน้าเอกสารอธิบาย Feature ของระบบ
| ใช้สำหรับแสดงรายละเอียดจากหน้าแรก "ทำไมต้องเลือกร้านค้าชุมชนไทยพร๊อม"
|
*/
use App\Http\Controllers\FeatureDocumentController;

// ⚠️ SEO Critical: Feature documents ต้องถูก index โดย search engines
Route::prefix('documents')->name('documents.')->group(function () {
    // All-in-One Platform
    Route::match(['GET', 'HEAD'], '/all-in-one', [FeatureDocumentController::class, 'allInOne'])
        ->name('all-in-one');

    // AI-Powered Automation
    Route::match(['GET', 'HEAD'], '/ai-automation', [FeatureDocumentController::class, 'aiAutomation'])
        ->name('ai-automation');

    // Blockchain & TPIX Token (รวม Whitepaper ทั้งหมด)
    Route::match(['GET', 'HEAD'], '/blockchain-tpix', [FeatureDocumentController::class, 'blockchainTpix'])
        ->name('blockchain-tpix');

    // Multi-Currency Wallet
    Route::match(['GET', 'HEAD'], '/multi-currency-wallet', [FeatureDocumentController::class, 'multiCurrencyWallet'])
        ->name('multi-currency-wallet');

    // MLM & Commission System
    Route::match(['GET', 'HEAD'], '/mlm-commission', [FeatureDocumentController::class, 'mlmCommission'])
        ->name('mlm-commission');

    // Enterprise Security
    Route::match(['GET', 'HEAD'], '/enterprise-security', [FeatureDocumentController::class, 'enterpriseSecurity'])
        ->name('enterprise-security');
});

/*
|--------------------------------------------------------------------------
| NFC Card Verification Routes (Public)
|--------------------------------------------------------------------------
|
| Routes สำหรับตรวจสอบความถูกต้องของบัตร NFC แบบ Public
| ผู้ใช้ทั่วไปสามารถเข้าถึงได้โดยไม่ต้อง login
|
*/
use App\Http\Controllers\NFCVerificationController;

// ⚠️ Security: Public NFC verification - ไม่ต้อง login
Route::prefix('nfc')->name('nfc.')->group(function () {
    // หน้าตรวจสอบบัตร NFC
    Route::match(['GET', 'HEAD'], '/verify/{cardNumber?}', [NFCVerificationController::class, 'show'])
        ->name('verify');

    // API สำหรับตรวจสอบบัตร (Full Verification)
    Route::post('/verify', [NFCVerificationController::class, 'verify'])
        ->name('verify.api');

    // API สำหรับตรวจสอบด่วน (Quick Verification)
    Route::post('/verify/quick', [NFCVerificationController::class, 'quickVerify'])
        ->name('verify.quick');

    // สถิติการตรวจสอบ (สำหรับ Admin หรือ Card Owner)
    Route::middleware('auth')->get('/statistics/{cardNumber}', [NFCVerificationController::class, 'statistics'])
        ->name('statistics');
});

/*
|--------------------------------------------------------------------------
| Unified Marketplace Routes (V3 Design)
|--------------------------------------------------------------------------
|
| Marketplace รวม: AI Chatbots, Trading Bots, Software Products
| ใช้ V3 Design System (Tailwind + Alpine.js + Glassmorphism)
|
*/

// Unified Marketplace → Redirect ไป Storefront (ลบระบบ marketplace แล้ว ใช้ storefront แทน)
Route::prefix('marketplace')->name('marketplace.v3.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', function () {
        return redirect()->route('storefront.index');
    })->name('index');
    Route::match(['GET', 'HEAD'], '/category/{category}', function ($category) {
        return redirect()->route('storefront.index', ['category' => $category]);
    })->name('category');
    Route::match(['GET', 'HEAD'], '/search', function (\Illuminate\Http\Request $request) {
        return redirect()->route('storefront.index', ['q' => $request->q]);
    })->name('search');
    Route::match(['GET', 'HEAD'], '/{type}/{slug}', function ($type, $slug) {
        return redirect()->route('shop.show', $slug);
    })->name('show')->where('type', 'chatbot|trading|software');
});

/*
|--------------------------------------------------------------------------
| Developer Routes
|--------------------------------------------------------------------------
|
| Routes สำหรับนักพัฒนาซอฟต์แวร์
| - ลงทะเบียนและ KYC
| - Dashboard และจัดการสินค้า
|
*/

use App\Http\Controllers\Developer\DeveloperRegistrationController;

Route::middleware(['auth'])->prefix('developer')->name('developer.')->group(function () {
    // ลงทะเบียนนักพัฒนา
    Route::get('/register', [DeveloperRegistrationController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [DeveloperRegistrationController::class, 'register'])->name('register.submit');

    // หน้ารอการอนุมัติ
    Route::get('/pending', [DeveloperRegistrationController::class, 'pending'])->name('pending');

    // Dashboard (ต้องอนุมัติแล้ว) - ใช้ developer.approved middleware
    Route::middleware(['developer.approved'])->group(function () {
        Route::get('/dashboard', [DeveloperRegistrationController::class, 'dashboard'])->name('dashboard');
    });
});

/*
|--------------------------------------------------------------------------
| Software Download Routes
|--------------------------------------------------------------------------
|
| Routes สำหรับจัดการการดาวน์โหลดซอฟต์แวร์
| - รองรับ license-based downloads
| - One-time download tokens
| - Multi-cloud storage support
|
*/

use App\Http\Controllers\SoftwareDownloadController;

// Download routes (ต้อง authenticate)
Route::middleware(['auth'])->prefix('software')->name('software.')->group(function () {
    // สร้าง download token จาก license
    Route::post('/license/{license}/download-token', [SoftwareDownloadController::class, 'createToken'])
        ->name('license.download-token');

    // ดาวน์โหลดผ่าน product (ต้องมี license)
    Route::get('/download/{product}', [SoftwareDownloadController::class, 'download'])
        ->name('download');

    // ประวัติการดาวน์โหลด
    Route::get('/downloads/history', [SoftwareDownloadController::class, 'history'])
        ->name('downloads.history');
});

// Public download route (ใช้ token - ไม่ต้อง auth)
Route::get('/download/{token}', [SoftwareDownloadController::class, 'downloadByToken'])
    ->name('software.download.file')
    ->where('token', '[a-f0-9]{64}');

/*
|--------------------------------------------------------------------------
| Facebook Webhook Routes (Fortune Telling)
|--------------------------------------------------------------------------
| Routes สำหรับรับ webhook events จาก Facebook Messenger
| ใช้สำหรับระบบดูดวงผ่าน Facebook
|
| ⚠️ Rate Limiting Strategy:
| ไม่ใช้ FortuneRateLimitMiddleware ที่นี่ เพราะ:
| 1. Facebook ต้องได้รับ HTTP 200 เสมอ (429 จะทำให้ Facebook disable webhook)
| 2. Rate limiting จัดการภายใน controller (checkFreeLimit, checkDeepFreeLimit)
| 3. Webhook signature verification ป้องกัน fake requests แล้ว
| 4. Facebook ควบคุม event volume ที่ส่งมาเอง
|
| FortuneRateLimitMiddleware ใช้สำหรับ public API routes เท่านั้น
*/

use App\Http\Controllers\FacebookDataDeletionController;
use App\Http\Controllers\FacebookWebhookController;
use App\Http\Controllers\LineFortuneWebhookController;

Route::prefix('webhook')->name('webhook.')->group(function () {
    // Facebook Webhook verification (GET) และรับ events (POST)
    Route::match(['GET', 'POST'], '/facebook', [FacebookWebhookController::class, 'webhook'])
        ->name('facebook');

    // Verify webhook (GET only - สำหรับ Facebook verification)
    Route::get('/facebook/verify', [FacebookWebhookController::class, 'verify'])
        ->name('facebook.verify');

    // 🗑️ (2026-07-07) Facebook Data Deletion Callback (PDPA) — รับ signed_request แล้วคิวงานลบ
    //   อยู่ใต้ prefix webhook/* → ยกเว้น CSRF อัตโนมัติ (ดู bootstrap/app.php)
    Route::post('/facebook/data-deletion', [FacebookDataDeletionController::class, 'handle'])
        ->name('facebook.data-deletion');

    // LINE Fortune Webhook (สำหรับระบบดูดวงผ่าน LINE Official Account)
    // ⚡ withoutMiddleware: ลบ middleware ที่ไม่จำเป็นสำหรับ webhook (เร็วขึ้น ~30-50ms)
    //
    // 🩹 (2026-05-08) เพิ่ม VerifyCsrfToken เข้า list — แก้ "Session store not set"
    //   เคสจริง: parent VerifyCsrfToken::handle() เรียก addCookieToResponse()
    //   → access $request->session()->token() แม้ route จะอยู่ใน $except
    //   → เมื่อ StartSession ถูกตัด → session ไม่มี → RuntimeException → 500
    //   ลูกค้า LINE ดูดวงไม่ได้ทั้ง 39/99 เพราะ webhook 500 ทุก request
    Route::post('/line/fortune', [LineFortuneWebhookController::class, 'handle'])
        ->name('line.fortune')
        ->withoutMiddleware([
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            // 🩹 (2026-05-08 v2) เพิ่ม parent CsrfToken — Laravel 11 auto-register ผ่าน
            //    bootstrap/app.php → validateCsrfTokens() ทำให้ class นี้รันแยกจาก subclass
            //    parent เรียก addCookieToResponse() ผ่าน tap() ทุก request แม้อยู่ใน $except
            //    → access session token → throw RuntimeException → 500
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\TrackVendorStoreVisit::class,
            \App\Http\Middleware\TrackRequestMetrics::class,
            \App\Http\Middleware\TrackPageView::class,
        ]);

    // 💳 (2026-05-09) Stripe Checkout webhook — Fortune chat payment
    //   ⚠️ ห้ามมี middleware ที่อ่าน body / parse JSON ก่อน controller (ทำให้ signature verify fail)
    //   $except ใน VerifyCsrfToken (webhook/* wildcard) ครอบ /webhook/fortune-stripe แล้ว
    Route::post('/fortune-stripe', [\App\Http\Controllers\Webhook\FortuneStripeWebhookController::class, 'handle'])
        ->name('fortune.stripe.webhook')
        ->withoutMiddleware([
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\TrackVendorStoreVisit::class,
            \App\Http\Middleware\TrackRequestMetrics::class,
            \App\Http\Middleware\TrackPageView::class,
        ]);

    // 💳 Stripe Checkout webhook — E-commerce order payment (แยกจากดูดวง ใช้บัญชี Stripe เดียวกัน)
    //   $except ใน VerifyCsrfToken (webhook/* wildcard) ครอบ /webhook/order-stripe แล้ว
    Route::post('/order-stripe', [\App\Http\Controllers\Webhook\OrderStripeWebhookController::class, 'handle'])
        ->name('order.stripe.webhook')
        ->withoutMiddleware([
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\TrackVendorStoreVisit::class,
            \App\Http\Middleware\TrackRequestMetrics::class,
            \App\Http\Middleware\TrackPageView::class,
        ]);

    // LINE Fresh Market Webhook (ตลาดสดไทยพร๊อม - LINE OA แยกจากดูดวง)
    // ⚡ withoutMiddleware: ลบ middleware ที่ไม่จำเป็นสำหรับ webhook
    Route::post('/line/taladsod', [\App\Http\Controllers\FreshMarketWebhookController::class, 'handle'])
        ->name('line.taladsod')
        ->withoutMiddleware([
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\TrackVendorStoreVisit::class,
            \App\Http\Middleware\TrackRequestMetrics::class,
            \App\Http\Middleware\TrackPageView::class,
        ]);
});

// 🗑️ (2026-07-07) หน้าเช็คสถานะการลบข้อมูล (PDPA) — ลิงก์ที่ตอบกลับ Facebook ชี้มาที่นี่
Route::get('/privacy/data-deletion-status/{code}', [FacebookDataDeletionController::class, 'status'])
    ->name('fortune.data-deletion.status');

/*
|--------------------------------------------------------------------------
| Fortune Referral Routes (ลิงก์เชิญเพื่อนดูดวง)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\FortuneReferralController;

Route::get('/fortune/invite/{token}', [FortuneReferralController::class, 'landing'])
    ->name('fortune.invite');

// 🎁 (2026-05-04) แคมเปญรับสิทธิ์ดูฟรีรายเดือน — ลิงก์ในโพสต์กลุ่ม Facebook
Route::get('/fortune/monthly-claim', [\App\Http\Controllers\Frontend\FortuneMonthlyClaimController::class, 'show'])
    ->name('fortune.monthly-claim');

// 💳 (2026-05-09) Stripe Checkout success / cancel pages — ลูกค้า redirect กลับมาที่นี่หลังจ่าย
Route::get('/fortune/stripe/success/{reading}', [\App\Http\Controllers\Webhook\FortuneStripeWebhookController::class, 'success'])
    ->name('fortune.stripe.success')
    ->where('reading', '[0-9]+');

Route::get('/fortune/stripe/cancel/{reading}', [\App\Http\Controllers\Webhook\FortuneStripeWebhookController::class, 'cancel'])
    ->name('fortune.stripe.cancel')
    ->where('reading', '[0-9]+');

// 🌟 (2026-05-05) Tracking redirects — บันทึก click + redirect ไป FB page/group
//   ใช้ใน button template ที่ส่งใน Messenger DM (ปุ่ม web_url ไม่ส่ง postback กลับ)
//   🔒 (2026-05-05 review) signed middleware — กัน enumeration spam (มี signature ใน URL)
Route::get('/fortune/track/fb-follow/{psid}', [\App\Http\Controllers\Frontend\FortuneTrackingController::class, 'fbFollow'])
    ->middleware('signed')
    ->name('fortune.track.fb-follow')
    ->where('psid', '[A-Za-z0-9_-]+');

Route::get('/fortune/track/fb-group/{psid}', [\App\Http\Controllers\Frontend\FortuneTrackingController::class, 'fbGroup'])
    ->middleware('signed')
    ->name('fortune.track.fb-group')
    ->where('psid', '[A-Za-z0-9_-]+');

// ========= THAIPROMPT_THAIAPP_MANAGER (v1.0.21) =========
// `throttle:60,1` caps a compromised or scripted admin account at 60
// admin-panel requests/min — legitimate UI work never touches this
// (saving one form = 1 req; even rapid typing is well under the cap).
Route::middleware(['auth','role:admin,super_admin','throttle:60,1'])
    ->prefix('admin/thaiapp')->name('admin.thaiapp.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'hub'])->name('hub');

        Route::get('/nong-ying', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'nongYing'])->name('nong-ying');
        Route::put('/nong-ying', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'updateNongYing'])->name('nong-ying.update');

        Route::get('/ai-pool', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'aiPool'])->name('ai-pool');
        Route::post('/ai-pool/keys', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'storeApiKey'])->name('ai-pool.keys.store');
        Route::put('/ai-pool/keys/{id}', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'updateApiKey'])->name('ai-pool.keys.update');
        Route::delete('/ai-pool/keys/{id}', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'destroyApiKey'])->name('ai-pool.keys.destroy');

        Route::get('/ai-models', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'aiModels'])->name('ai-models');
        Route::post('/ai-models/{tier}/sync', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'syncModel'])->name('ai-models.sync');
        Route::delete('/ai-models/{tier}', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'destroyModel'])->name('ai-models.destroy');

        Route::get('/banners', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'banners'])->name('banners');
        Route::post('/banners', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'storeBanner'])->name('banners.store');
        Route::put('/banners/{id}', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'updateBanner'])->name('banners.update');
        Route::delete('/banners/{id}', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'destroyBanner'])->name('banners.destroy');

        Route::get('/sliders', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'sliders'])->name('sliders');
        Route::post('/sliders', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'storeSlider'])->name('sliders.store');
        Route::put('/sliders/{id}', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'updateSlider'])->name('sliders.update');
        Route::delete('/sliders/{id}', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'destroySlider'])->name('sliders.destroy');

        Route::get('/menus', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'menus'])->name('menus');
        Route::post('/menus', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'storeMenu'])->name('menus.store');
        Route::put('/menus/{id}', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'updateMenu'])->name('menus.update');
        Route::delete('/menus/{id}', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'destroyMenu'])->name('menus.destroy');

        Route::get('/config', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'config'])->name('config');
        Route::post('/config', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'storeConfig'])->name('config.store');
        Route::put('/config/{id}', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'updateConfig'])->name('config.update');
        Route::delete('/config/{id}', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'destroyConfig'])->name('config.destroy');

        Route::get('/releases', [\App\Http\Controllers\Admin\ThaiappManagerController::class, 'releases'])->name('releases');
    });
// ======= END THAIPROMPT_THAIAPP_MANAGER (v1.0.21) =======
