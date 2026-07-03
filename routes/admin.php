<?php

use App\Http\Controllers\Admin\AcademySettingsController;
use App\Http\Controllers\Admin\Accounting\AccountingDashboardController;
use App\Http\Controllers\Admin\Accounting\ContactController;
use App\Http\Controllers\Admin\Accounting\ExpenseController;
use App\Http\Controllers\Admin\Accounting\FlowAccountController;
use App\Http\Controllers\Admin\Accounting\InvoiceController;
use App\Http\Controllers\Admin\Accounting\ProductController;
use App\Http\Controllers\Admin\Accounting\ReportController;
// 🩹 (2026-05-08) ลบ — class ไม่มีใน codebase ทำให้ artisan route:list crash
//   ส่งผลให้ webhook line/fortune 500 (Laravel boot fail) → LINE ดูดวงไม่ได้
// use App\Http\Controllers\Admin\AdvancedAnalyticsController;
// use App\Http\Controllers\Admin\AdvancedNLPController;
use App\Http\Controllers\Admin\AiApiKeyController;
use App\Http\Controllers\Admin\AiBotController;
use App\Http\Controllers\Admin\AiContentWriterController;
use App\Http\Controllers\Admin\AICoreAlertController;
use App\Http\Controllers\Admin\AICoreAnalyticsController;
use App\Http\Controllers\Admin\AICoreController;
use App\Http\Controllers\Admin\AICoreFeatureController;
use App\Http\Controllers\Admin\AICoreQuotaController;
use App\Http\Controllers\Admin\AICoreScheduleController;
use App\Http\Controllers\Admin\AICoreTenantController;
use App\Http\Controllers\Admin\AiGenAdminController;
use App\Http\Controllers\Admin\AiInstallationController;
use App\Http\Controllers\Admin\AiMonitoringController;
use App\Http\Controllers\Admin\AiProviderManagementController;
use App\Http\Controllers\Admin\AiRentalCloudProviderController;
use App\Http\Controllers\Admin\AiRentalConfigController;
use App\Http\Controllers\Admin\AiRentalController;
use App\Http\Controllers\Admin\AiRentalDeploymentController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AntiAbuseController;
use App\Http\Controllers\Admin\ApiEndpointController;
use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\ArrowXThemeController;
use App\Http\Controllers\Admin\ArticleManagementController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\CacheSettingsController;
use App\Http\Controllers\Admin\CashbackSettingController;
use App\Http\Controllers\Admin\CategoryManagementController;
use App\Http\Controllers\Admin\CentralAiController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\CertificateManagementController;
use App\Http\Controllers\Admin\ClassicXSettingsController;
use App\Http\Controllers\Admin\CloudflareController;
use App\Http\Controllers\Admin\CoinShopController;
use App\Http\Controllers\Admin\CryptoManagementController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DebtController;
use App\Http\Controllers\Admin\DemoDataController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\Dev\DevReleaseController;
use App\Http\Controllers\Admin\DeveloperApprovalController;
use App\Http\Controllers\Admin\ECommerceController;
use App\Http\Controllers\Admin\EmailAnalyticsController;
use App\Http\Controllers\Admin\EmailCampaignController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\EmailQueueController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\FacebookOAuthController;
use App\Http\Controllers\Admin\FeaturedStoreController;
use App\Http\Controllers\Admin\FloatingToolsController;
use App\Http\Controllers\Admin\FortuneAdminQAController;
use App\Http\Controllers\Admin\FortuneAstrologyController;
use App\Http\Controllers\Admin\FortuneBanController;
use App\Http\Controllers\Admin\FortuneBannerController;
use App\Http\Controllers\Admin\FortuneBillingController;
use App\Http\Controllers\Admin\FortuneCategoriesController;
use App\Http\Controllers\Admin\FortuneCelticCrossController;
use App\Http\Controllers\Admin\FortuneChannelController;
use App\Http\Controllers\Admin\FortuneCommissionController;
use App\Http\Controllers\Admin\FortuneConsentController;
use App\Http\Controllers\Admin\FortuneDebugToolsController;
use App\Http\Controllers\Admin\FortuneHoroscopeController;
use App\Http\Controllers\Admin\FortuneInviteMessageController;
use App\Http\Controllers\Admin\FortuneKnowledgeController;
use App\Http\Controllers\Admin\FortuneMarketingController;
use App\Http\Controllers\Admin\FortuneMysticController;
use App\Http\Controllers\Admin\FortunePersonasController;
use App\Http\Controllers\Admin\FortuneReadingsController;
use App\Http\Controllers\Admin\FortuneResponseTemplatesController;
use App\Http\Controllers\Admin\FortuneRichMenuDeployController;
use App\Http\Controllers\Admin\FortuneRichMenuEditorController;
use App\Http\Controllers\Admin\FortuneSavedQuestionsController;
use App\Http\Controllers\Admin\FortuneSettingsController;
use App\Http\Controllers\Admin\FortuneSlipLogController;
use App\Http\Controllers\Admin\FortuneTakeoverController;
use App\Http\Controllers\Admin\FortuneUserCreditController;
use App\Http\Controllers\Admin\FortuneUsersController;
use App\Http\Controllers\Admin\FortuneVoiceController;
use App\Http\Controllers\Admin\FortuneVoiceDiagnosticController;
use App\Http\Controllers\Admin\FortuneVoicePresetController;
use App\Http\Controllers\Admin\FortuneVoiceStorageController;
use App\Http\Controllers\Admin\ForumAdminController;
use App\Http\Controllers\Admin\FreshMarketController;
use App\Http\Controllers\Admin\GameController;
use App\Http\Controllers\Admin\GameSettingsController;
use App\Http\Controllers\Admin\GpsMonitoringController;
use App\Http\Controllers\Admin\HDWalletManagementController;
use App\Http\Controllers\Admin\HeaderSettingsController;
use App\Http\Controllers\Admin\HomepageManagerController;
use App\Http\Controllers\Admin\HoroscopeAnalyticsController;
use App\Http\Controllers\Admin\HoroscopeDreamManagementController;
use App\Http\Controllers\Admin\HoroscopePublicSettingsController;
use App\Http\Controllers\Admin\HoroscopeZodiacController;
use App\Http\Controllers\Admin\HotelBookingManagementController;
use App\Http\Controllers\Admin\HotelFacilityController;
use App\Http\Controllers\Admin\HotelManagementController;
use App\Http\Controllers\Admin\HotelOwnerController;
use App\Http\Controllers\Admin\HotelReviewManagementController;
use App\Http\Controllers\Admin\HotelSpecialOfferController;
use App\Http\Controllers\Admin\HrmDashboardController;
use App\Http\Controllers\Admin\IconController;
use App\Http\Controllers\Admin\IdCardController;
use App\Http\Controllers\Admin\InvestmentController;
use App\Http\Controllers\Admin\JobApplicationController;
use App\Http\Controllers\Admin\JobPostingController;
use App\Http\Controllers\Admin\KeywordABTestController;
use App\Http\Controllers\Admin\KeywordActivityLogController;
use App\Http\Controllers\Admin\KeywordPerformanceDashboardController;
use App\Http\Controllers\Admin\KeywordSuggestionController;
use App\Http\Controllers\Admin\KnowledgeBaseController;
use App\Http\Controllers\Admin\KycController;
use App\Http\Controllers\Admin\LanguageSettingController;
use App\Http\Controllers\Admin\LazadaImportController;
use App\Http\Controllers\Admin\LearningCenterController;
use App\Http\Controllers\Admin\LeaveController;
use App\Http\Controllers\Admin\LineBotAiController;
use App\Http\Controllers\Admin\LineBotKeywordAnalyticsController;
use App\Http\Controllers\Admin\LineBotKeywordController;
use App\Http\Controllers\Admin\LineBroadcastController;
use App\Http\Controllers\Admin\LineConnectionsController;
use App\Http\Controllers\Admin\LineMessageAnalyticsController;
use App\Http\Controllers\Admin\LineOaController;
use App\Http\Controllers\Admin\LineRecruitmentController;
use App\Http\Controllers\Admin\LineRichMenuController;
use App\Http\Controllers\Admin\MarketplaceAccountController;
use App\Http\Controllers\Admin\MarketplaceCommissionController;
use App\Http\Controllers\Admin\MarketplaceOrderController;
use App\Http\Controllers\Admin\MarketplaceProductController;
use App\Http\Controllers\Admin\MenuManagementController;
use App\Http\Controllers\Admin\MlmCommissionController;
use App\Http\Controllers\Admin\MlmGlobalSettingController;
use App\Http\Controllers\Admin\MlmMemberController;
use App\Http\Controllers\Admin\MlmPlanController;
use App\Http\Controllers\Admin\MlmProductPvController;
use App\Http\Controllers\Admin\MlmProspectController;
use App\Http\Controllers\Admin\MlmReportController;
use App\Http\Controllers\Admin\MobileAppController;
use App\Http\Controllers\Admin\MobilePairController;
use App\Http\Controllers\Admin\NFCCardController;
use App\Http\Controllers\Admin\NFCReaderController;
use App\Http\Controllers\Admin\NFCTransactionController;
use App\Http\Controllers\Admin\NLPAnalysisController;
use App\Http\Controllers\Admin\NotificationManagementController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\OfficialShopAdminController;
use App\Http\Controllers\Admin\OfficialShopSelectionController;
use App\Http\Controllers\Admin\OtpSettingsController;
use App\Http\Controllers\Admin\PageBuilderController;
use App\Http\Controllers\Admin\PageBuilderSectionController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PageViewAnalyticsController;
use App\Http\Controllers\Admin\PaymentBankAccountController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\PerformanceGoalController;
use App\Http\Controllers\Admin\PerformanceReviewController;
use App\Http\Controllers\Admin\PerformanceTemplateController;
use App\Http\Controllers\Admin\PlatformRevenueController;
use App\Http\Controllers\Admin\Pos\PosLabelController;
use App\Http\Controllers\Admin\PosAdvertisementController;
use App\Http\Controllers\Admin\PosDashboardController;
use App\Http\Controllers\Admin\PosDeviceController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\PosTransactionController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\QuizManagementController;
use App\Http\Controllers\Admin\RankController;
use App\Http\Controllers\Admin\RecruitTemplateController;
use App\Http\Controllers\Admin\RiderController;
use App\Http\Controllers\Admin\RiderJobController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\RoomTypeManagementController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SentimentAnalysisController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\ServiceBookingController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ServicePricingRuleController;
use App\Http\Controllers\Admin\ServiceProviderController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\SloganController;
use App\Http\Controllers\Admin\SmartSlideController;
use App\Http\Controllers\Admin\SmartSlideLayerController;
use App\Http\Controllers\Admin\SmartSliderController;
use App\Http\Controllers\Admin\SmsCheckerAdminController;
use App\Http\Controllers\Admin\SmsGatewayAdminController;
use App\Http\Controllers\Admin\StakingPlanController;
use App\Http\Controllers\Admin\StarUpgradePriceController;
use App\Http\Controllers\Admin\StorefrontSettingsController;
use App\Http\Controllers\Admin\SuperAdminHotelController;
use App\Http\Controllers\Admin\SystemResetController;
use App\Http\Controllers\Admin\TarotManagementController;
use App\Http\Controllers\Admin\TeamTransferController;
use App\Http\Controllers\Admin\ThaipromptRichMenuController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\TokenManagementController;
use App\Http\Controllers\Admin\TPIXController;
use App\Http\Controllers\Admin\TpixDeploymentController;
use App\Http\Controllers\Admin\TrainingCourseController;
use App\Http\Controllers\Admin\TrainingEnrollmentController;
use App\Http\Controllers\Admin\TrendManagementController;
use App\Http\Controllers\Admin\TwoFactorSettingsController;
use App\Http\Controllers\Admin\UnifiedReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserGuideController;
use App\Http\Controllers\Admin\VendorStoreController;
use App\Http\Controllers\Admin\VideoAutomationController;
use App\Http\Controllers\Admin\VideoMissionController;
use App\Http\Controllers\Admin\VideoRewardAdminController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\WalletSettingsController;
use App\Http\Controllers\Admin\WebPManagementController;
use App\Http\Controllers\Admin\WindowsUiController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Instructor\InstructorDashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Middleware\DevMode;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// Redirect /admin to /admin/dashboard
Route::redirect('/', '/dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ─────────────────────────────────────────────────────────────
// Admin Mobile App Pairing (QR-based device pairing)
// ─────────────────────────────────────────────────────────────
Route::prefix('mobile-pair')->name('mobile-pair.')->group(function () {
    Route::get('/', [MobilePairController::class, 'index'])->name('index');
    Route::post('/init', [MobilePairController::class, 'init'])->name('init');
    Route::get('/status', [MobilePairController::class, 'status'])->name('status');
    Route::post('/cancel', [MobilePairController::class, 'cancel'])->name('cancel');
});

// System Analytics
Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/', [AnalyticsController::class, 'index'])->name('index');
    Route::get('/realtime', [AnalyticsController::class, 'realtime'])->name('realtime');
    Route::get('/historical', [AnalyticsController::class, 'historical'])->name('historical');
    Route::get('/performance', [AnalyticsController::class, 'performance'])->name('performance');
    Route::get('/database', [AnalyticsController::class, 'database'])->name('database');
    Route::get('/cache', [AnalyticsController::class, 'cache'])->name('cache');
    Route::get('/traffic', [AnalyticsController::class, 'traffic'])->name('traffic');
    Route::get('/business', [AnalyticsController::class, 'business'])->name('business');
    Route::get('/security', [AnalyticsController::class, 'security'])->name('security');
    Route::get('/capacity', [AnalyticsController::class, 'capacity'])->name('capacity');
    Route::get('/export', [AnalyticsController::class, 'export'])->name('export');
    Route::post('/collect', [AnalyticsController::class, 'collect'])->name('collect');

    // Page Views Analytics - สถิติการเข้าชมหน้าเว็บ
    Route::prefix('page-views')->name('page-views.')->group(function () {
        Route::get('/', [PageViewAnalyticsController::class, 'index'])->name('index');
        Route::get('/realtime', [PageViewAnalyticsController::class, 'realtime'])->name('realtime');
        Route::get('/realtime-data', [PageViewAnalyticsController::class, 'realtimeData'])->name('realtime-data');
        Route::get('/pages', [PageViewAnalyticsController::class, 'pages'])->name('pages');
        Route::get('/sources', [PageViewAnalyticsController::class, 'sources'])->name('sources');
        Route::get('/devices', [PageViewAnalyticsController::class, 'devices'])->name('devices');
        Route::get('/geography', [PageViewAnalyticsController::class, 'geography'])->name('geography');
        Route::get('/export', [PageViewAnalyticsController::class, 'export'])->name('export');
        Route::get('/chart-data', [PageViewAnalyticsController::class, 'chartData'])->name('chart-data');
    });
});

// Unified Reports Center - ศูนย์รายงานรวม
Route::prefix('unified-reports')->name('unified-reports.')->group(function () {
    Route::get('/', [UnifiedReportController::class, 'index'])->name('index');

    // รายงานภาพรวมผู้บริหาร (Executive Summary)
    Route::get('/executive', [UnifiedReportController::class, 'executive'])->name('executive');

    // Business Intelligence Dashboard
    Route::get('/business-intelligence', [UnifiedReportController::class, 'businessIntelligence'])->name('business-intelligence');

    // รายงานแต่ละระบบ
    Route::get('/mlm', [UnifiedReportController::class, 'mlm'])->name('mlm');
    Route::get('/ecommerce', [UnifiedReportController::class, 'ecommerce'])->name('ecommerce');
    Route::get('/finance', [UnifiedReportController::class, 'finance'])->name('finance');
    Route::get('/ai-bot', [UnifiedReportController::class, 'aiBot'])->name('ai_bot');
    Route::get('/hotel', [UnifiedReportController::class, 'hotel'])->name('hotel');
    Route::get('/pos', [UnifiedReportController::class, 'pos'])->name('pos');
    Route::get('/crypto', [UnifiedReportController::class, 'crypto'])->name('crypto');
    Route::get('/hrm', [UnifiedReportController::class, 'hrm'])->name('hrm');
    Route::get('/learning', [UnifiedReportController::class, 'learning'])->name('learning');

    // API สำหรับแนวโน้มและการเปรียบเทียบ
    Route::get('/trends', [UnifiedReportController::class, 'trends'])->name('trends');
    Route::get('/compare', [UnifiedReportController::class, 'compare'])->name('compare');

    // Export รายงาน
    Route::get('/export/{type?}', [UnifiedReportController::class, 'export'])->name('export');
    Route::get('/export-csv/{type?}', [UnifiedReportController::class, 'exportCsv'])->name('export-csv');
});

// CSRF Token Refresh (for long-running forms)
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf-token');

// User Management
Route::resource('users', UserController::class);
Route::get('users/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');
Route::put('users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('users.permissions.update');
Route::get('users/{user}/dashboard', [UserController::class, 'viewDashboard'])->name('users.dashboard');
Route::post('users/{user}/generate-member-number', [UserController::class, 'generateMemberNumber'])->name('users.generate-member-number');

// Role Management
Route::resource('roles', RoleController::class);
Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');

// Investment & Staking Management
Route::prefix('investments')->name('investments.')->group(function () {
    Route::get('/', [InvestmentController::class, 'index'])->name('index');

    // Investment Plans
    Route::get('/plans', [InvestmentController::class, 'plans'])->name('plans.index');
    Route::get('/plans/create', [InvestmentController::class, 'createPlan'])->name('plans.create');
    Route::post('/plans', [InvestmentController::class, 'storePlan'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [InvestmentController::class, 'editPlan'])->name('plans.edit');
    Route::put('/plans/{plan}', [InvestmentController::class, 'updatePlan'])->name('plans.update');
    Route::delete('/plans/{plan}', [InvestmentController::class, 'destroyPlan'])->name('plans.destroy');

    // Staking Positions
    Route::get('/positions', [InvestmentController::class, 'positions'])->name('positions.index');
    Route::get('/positions/{position}', [InvestmentController::class, 'showPosition'])->name('positions.show');
    Route::post('/positions/{position}/approve', [InvestmentController::class, 'approvePosition'])->name('positions.approve');
    Route::post('/positions/{position}/reject', [InvestmentController::class, 'rejectPosition'])->name('positions.reject');

    // ROI Distributions
    Route::get('/distributions', [InvestmentController::class, 'distributions'])->name('distributions.index');
    Route::post('/distributions/trigger', [InvestmentController::class, 'triggerDistribution'])->name('distributions.trigger');
    Route::post('/distributions/retry-failed', [InvestmentController::class, 'retryFailedDistributions'])->name('distributions.retry-failed');

    // Utilities
    Route::post('/process-mature', [InvestmentController::class, 'processMaturePositions'])->name('process-mature');
});

// Staking Plans Management (Enhanced V3)
Route::prefix('staking-plans')->name('staking-plans.')->group(function () {
    Route::get('/', [StakingPlanController::class, 'index'])->name('index');
    Route::get('/create', [StakingPlanController::class, 'create'])->name('create');
    Route::post('/', [StakingPlanController::class, 'store'])->name('store');
    Route::get('/{stakingPlan}', [StakingPlanController::class, 'show'])->name('show');
    Route::get('/{stakingPlan}/edit', [StakingPlanController::class, 'edit'])->name('edit');
    Route::put('/{stakingPlan}', [StakingPlanController::class, 'update'])->name('update');
    Route::delete('/{stakingPlan}', [StakingPlanController::class, 'destroy'])->name('destroy');

    // การจัดการแผน
    Route::post('/{stakingPlan}/pause', [StakingPlanController::class, 'pause'])->name('pause');
    Route::post('/{stakingPlan}/resume', [StakingPlanController::class, 'resume'])->name('resume');
    Route::post('/{stakingPlan}/toggle-active', [StakingPlanController::class, 'toggleActive'])->name('toggle-active');

    // ตั้งค่า Coin
    Route::get('/settings/coin', [StakingPlanController::class, 'coinSettings'])->name('coin-settings');
    Route::put('/settings/coin', [StakingPlanController::class, 'updateCoinSettings'])->name('coin-settings.update');

    // รายงาน Positions
    Route::get('/reports/positions', [StakingPlanController::class, 'positions'])->name('positions');
});

// KYC Verification Management
Route::prefix('kyc')->name('kyc.')->group(function () {
    Route::get('/', [KycController::class, 'index'])->name('index');
    Route::get('/{kycVerification}', [KycController::class, 'show'])->name('show');
    Route::post('/{kycVerification}/approve', [KycController::class, 'approve'])->name('approve');
    Route::post('/{kycVerification}/reject', [KycController::class, 'reject'])->name('reject');
    Route::delete('/{kycVerification}', [KycController::class, 'destroy'])->name('destroy');
});

// Settings
Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
Route::post('settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding');
Route::put('settings/theme', [SettingsController::class, 'updateTheme'])->name('settings.theme');

// Profile (V3)
Route::get('profile', [SettingsController::class, 'profile'])->name('profile.index');
Route::post('profile/update', [SettingsController::class, 'updateProfile'])->name('profile.update');
Route::post('profile/change-password', [SettingsController::class, 'changePassword'])->name('profile.change-password');

// User Guide (V3)
Route::get('user-guide', [UserGuideController::class, 'index'])->name('user-guide.index');

// Cache Settings (V3) - ระบบจัดการแคช
Route::prefix('cache')->name('cache.')->group(function () {
    Route::get('/', [CacheSettingsController::class, 'index'])->name('index');
    Route::get('/status', [CacheSettingsController::class, 'getDriversStatus'])->name('status');
    Route::get('/stats', [CacheSettingsController::class, 'getStats'])->name('stats');
    Route::post('/test', [CacheSettingsController::class, 'testConnection'])->name('test');
    Route::post('/clear', [CacheSettingsController::class, 'clearCache'])->name('clear');
    Route::post('/clear-specific', [CacheSettingsController::class, 'clearSpecific'])->name('clear-specific');
    Route::post('/change-driver', [CacheSettingsController::class, 'changeDriver'])->name('change-driver');
    Route::post('/optimize', [CacheSettingsController::class, 'optimize'])->name('optimize');
    Route::get('/installation-guide', [CacheSettingsController::class, 'getInstallationGuide'])->name('installation-guide');
    Route::get('/guide', [CacheSettingsController::class, 'getInstallationGuide'])->name('guide'); // Alias สำหรับ installation-guide
});

// Site Settings (โลโก้, Favicon, ชื่อเว็บไซต์, SEO, Social Media)
Route::prefix('site-settings')->name('site-settings.')->group(function () {
    Route::get('/', [SiteSettingsController::class, 'index'])->name('index');
    Route::put('/', [SiteSettingsController::class, 'update'])->name('update');
    Route::delete('/logo', [SiteSettingsController::class, 'deleteLogo'])->name('logo.delete');
});

// Demo Data Management (จัดการข้อมูลทดสอบ)
Route::prefix('demo-data')->name('demo-data.')->group(function () {
    Route::get('/', [DemoDataController::class, 'index'])->name('index');
    Route::post('/clean', [DemoDataController::class, 'clean'])->name('clean');
    Route::get('/stats', [DemoDataController::class, 'stats'])->name('stats');
});

// Header Settings
Route::prefix('header-settings')->name('header-settings.')->group(function () {
    Route::get('/', [HeaderSettingsController::class, 'index'])->name('index');
    Route::put('/', [HeaderSettingsController::class, 'update'])->name('update');
});

// WebP Management
Route::prefix('webp')->name('webp.')->group(function () {
    Route::get('/', [WebPManagementController::class, 'index'])->name('index');
    Route::post('/convert', [WebPManagementController::class, 'convert'])->name('convert');
    Route::get('/progress', [WebPManagementController::class, 'progress'])->name('progress');
    Route::get('/directory-details', [WebPManagementController::class, 'directoryDetails'])->name('directory-details');
});

// Security Management
Route::prefix('security')->name('security.')->group(function () {
    Route::get('/', [SecurityController::class, 'index'])->name('index');
    Route::get('/analytics', [SecurityController::class, 'analytics'])->name('analytics');
    Route::get('/analytics/data', [SecurityController::class, 'getAnalyticsData'])->name('analytics.data');
    Route::put('/turnstile', [SecurityController::class, 'updateTurnstile'])->name('turnstile.update');
    Route::put('/rate-limiting', [SecurityController::class, 'updateRateLimiting'])->name('rate-limiting.update');
    Route::put('/auto-ban', [SecurityController::class, 'updateAutoBan'])->name('auto-ban.update');
    Route::post('/ip/block', [SecurityController::class, 'blockIp'])->name('ip.block');
    Route::delete('/ip/{id}', [SecurityController::class, 'unblockIp'])->name('ip.unblock');
    Route::get('/export/logs', [SecurityController::class, 'exportLogs'])->name('export.logs');
    Route::get('/export/analytics', [SecurityController::class, 'exportAnalytics'])->name('export.analytics');

    // Threat Intelligence
    Route::get('/threat-intelligence', [SecurityController::class, 'threatIntelligence'])->name('threat-intelligence');
    Route::post('/threat-intelligence/update', [SecurityController::class, 'updateThreatIntelligence'])->name('threat-intelligence.update');
    Route::get('/threat-intelligence/progress', [SecurityController::class, 'getThreatUpdateProgress'])->name('threat-intelligence.progress');
    Route::post('/threat-intelligence/check', [SecurityController::class, 'checkIpThreat'])->name('threat-intelligence.check');
    Route::put('/threat-intelligence/settings', [SecurityController::class, 'updateThreatSettings'])->name('threat-intelligence.settings');
});

// Pages Management (CMS)
Route::resource('pages', PageController::class);
Route::post('pages/reorder', [PageController::class, 'reorder'])->name('pages.reorder');

// Windows UI Management (Visual Customization Only - Menus are hard-coded)
Route::prefix('windows-ui')->name('windows-ui.')->group(function () {
    Route::get('/', [WindowsUiController::class, 'index'])->name('index');
    Route::put('/', [WindowsUiController::class, 'update'])->name('update');
    Route::put('/start-button-settings', [WindowsUiController::class, 'updateStartButtonSettings'])->name('start-button-settings.update');
    Route::put('/menu-settings', [WindowsUiController::class, 'updateMenuSettings'])->name('menu-settings.update');
    Route::put('/menu-rgb-settings', [WindowsUiController::class, 'updateMenuRgbSettings'])->name('menu-rgb-settings.update');
    Route::get('/rgb-settings', [WindowsUiController::class, 'rgbSettings'])->name('rgb-settings');
    Route::put('/rgb-settings', [WindowsUiController::class, 'updateRgbSettings'])->name('rgb-settings.update');
});

// Classic X Theme Settings (WordPress-Inspired Premium Theme)
Route::prefix('classic-x-settings')->name('classic-x-settings.')->group(function () {
    Route::get('/', [ClassicXSettingsController::class, 'index'])->name('index');
    Route::put('/', [ClassicXSettingsController::class, 'update'])->name('update');
    Route::post('/reset', [ClassicXSettingsController::class, 'reset'])->name('reset');
    Route::get('/preview', [ClassicXSettingsController::class, 'preview'])->name('preview');
});

// SEO Management
Route::resource('seo', SeoController::class);

// Wallet Management
Route::prefix('wallet')->name('wallet.')->group(function () {
    // Admin's own wallet
    Route::get('/', [WalletController::class, 'index'])->name('index');
    Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');
    Route::get('/logs', [WalletController::class, 'logs'])->name('logs');
    Route::get('/settings', [WalletController::class, 'settings'])->name('settings');
    Route::post('/set-pin', [WalletController::class, 'setPin'])->name('set-pin');
    Route::post('/deposit', [WalletController::class, 'deposit'])->name('deposit');
    Route::post('/withdraw', [WalletController::class, 'withdraw'])->name('withdraw');
    Route::post('/transfer', [WalletController::class, 'transfer'])->name('transfer');
    Route::post('/lock', [WalletController::class, 'lock'])->name('lock');
    Route::post('/unlock', [WalletController::class, 'unlock'])->name('unlock');

    // Manage all wallets (Admin only)
    Route::get('/all', [WalletController::class, 'allWallets'])->name('all');
    Route::get('/all-transactions', [WalletController::class, 'allTransactions'])->name('all-transactions');
    Route::get('/all-logs', [WalletController::class, 'allLogs'])->name('all-logs');
    Route::get('/wallets-dropdown', [WalletController::class, 'getWalletsDropdown'])->name('wallets-dropdown');
    Route::get('/export-transactions', [WalletController::class, 'exportTransactions'])->name('export-transactions');
    Route::post('/admin-transfer', [WalletController::class, 'adminTransfer'])->name('admin-transfer');
    Route::get('/{id}/show', [WalletController::class, 'showWallet'])->name('show');
    Route::post('/{id}/adjust-balance', [WalletController::class, 'adjustBalance'])->name('adjust-balance');
    Route::post('/{id}/refund', [WalletController::class, 'refund'])->name('refund');
    Route::post('/{id}/rollback-transaction', [WalletController::class, 'rollbackTransaction'])->name('rollback-transaction');
    Route::post('/{id}/lock', [WalletController::class, 'lockUserWallet'])->name('lock-user');
    Route::post('/{id}/unlock', [WalletController::class, 'unlockUserWallet'])->name('unlock-user');
    Route::post('/{id}/suspend', [WalletController::class, 'suspendUserWallet'])->name('suspend-user');
    Route::post('/{id}/unsuspend', [WalletController::class, 'unsuspendUserWallet'])->name('unsuspend-user');
    Route::post('/{id}/reset-pin', [WalletController::class, 'resetUserPin'])->name('reset-pin');
});

// Withdrawal Management (Admin)
Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
    Route::get('/', [WithdrawalController::class, 'index'])->name('index');
    Route::get('/pending', [WithdrawalController::class, 'pending'])->name('pending');
    Route::get('/{withdrawal}', [WithdrawalController::class, 'show'])->name('show');
    Route::post('/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('approve');
    Route::post('/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('reject');
    Route::post('/{withdrawal}/complete', [WithdrawalController::class, 'complete'])->name('complete');
    Route::post('/batch-approve', [WithdrawalController::class, 'batchApprove'])->name('batch-approve');
});

// Wallet Settings Management
Route::prefix('wallet-settings')->name('wallet-settings.')->group(function () {
    Route::get('/', [WalletSettingsController::class, 'index'])->name('index');
    Route::put('/{key}', [WalletSettingsController::class, 'update'])->name('update');
    Route::post('/bulk-update', [WalletSettingsController::class, 'bulkUpdate'])->name('bulk-update');
    Route::post('/{id}/toggle', [WalletSettingsController::class, 'toggle'])->name('toggle');
    Route::post('/calculate-fee', [WalletSettingsController::class, 'calculateFee'])->name('calculate-fee');
    Route::post('/reset-defaults', [WalletSettingsController::class, 'resetToDefaults'])->name('reset-defaults');
});

// Cashback Management (in wallet section)
Route::prefix('cashback')->name('cashback.')->group(function () {
    Route::get('/', [CashbackSettingController::class, 'index'])->name('index');
    Route::get('/create', [CashbackSettingController::class, 'create'])->name('create');
    Route::post('/', [CashbackSettingController::class, 'store'])->name('store');
    Route::get('/{cashback}/edit', [CashbackSettingController::class, 'edit'])->name('edit');
    Route::put('/{cashback}', [CashbackSettingController::class, 'update'])->name('update');
    Route::delete('/{cashback}', [CashbackSettingController::class, 'destroy'])->name('destroy');
    Route::post('/{cashback}/toggle-active', [CashbackSettingController::class, 'toggleActive'])->name('toggle-active');
    Route::get('/statistics', [CashbackSettingController::class, 'statistics'])->name('statistics');
});

// Payment Gateway Management
Route::prefix('payment-gateways')->name('payment-gateways.')->group(function () {
    Route::get('/', [PaymentGatewayController::class, 'index'])->name('index');
    Route::get('/{paymentGateway}/edit', [PaymentGatewayController::class, 'edit'])->name('edit');
    Route::put('/{paymentGateway}', [PaymentGatewayController::class, 'update'])->name('update');
    Route::post('/{paymentGateway}/toggle', [PaymentGatewayController::class, 'toggle'])->name('toggle');
    Route::post('/{paymentGateway}/test', [PaymentGatewayController::class, 'testConnection'])->name('test');
    Route::post('/update-order', [PaymentGatewayController::class, 'updateOrder'])->name('update-order');
});

// SMS Payment Checker Management (ระบบตรวจสอบ SMS สำหรับชำระเงิน)
Route::prefix('smschecker')->name('smschecker.')->group(function () {
    Route::get('/', [SmsCheckerAdminController::class, 'index'])->name('index');
    Route::get('/settings', [SmsCheckerAdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [SmsCheckerAdminController::class, 'updateSettings'])->name('settings-update');
    Route::post('/settings/fcm', [SmsCheckerAdminController::class, 'updateFcmSettings'])->name('settings-fcm');
    Route::post('/settings/fcm-test', [SmsCheckerAdminController::class, 'testFcm'])->name('settings-fcm-test');
    Route::post('/settings/download-url', [SmsCheckerAdminController::class, 'updateDownloadUrl'])->name('settings-download-url');
    Route::get('/devices', [SmsCheckerAdminController::class, 'devices'])->name('devices');
    Route::get('/devices/create', [SmsCheckerAdminController::class, 'createDevice'])->name('device-create');
    Route::post('/devices', [SmsCheckerAdminController::class, 'storeDevice'])->name('device-store');
    Route::get('/devices/{device}', [SmsCheckerAdminController::class, 'showDevice'])->name('device-show');
    Route::post('/devices/{device}/toggle-status', [SmsCheckerAdminController::class, 'toggleDeviceStatus'])->name('device-toggle');
    Route::post('/devices/{device}/regenerate-keys', [SmsCheckerAdminController::class, 'regenerateKeys'])->name('device-regenerate');
    Route::post('/devices/{device}/clear-fcm-token', [SmsCheckerAdminController::class, 'clearFcmToken'])->name('device-clear-fcm');
    Route::delete('/devices/{device}', [SmsCheckerAdminController::class, 'destroyDevice'])->name('device-destroy');
    Route::get('/devices/{device}/qr', [SmsCheckerAdminController::class, 'qrCode'])->name('device-qr');
    Route::get('/devices/{device}/qr.json', [SmsCheckerAdminController::class, 'qrCodeJson'])->name('device-qr-json');
    Route::get('/notifications', [SmsCheckerAdminController::class, 'notifications'])->name('notifications');
    Route::get('/pending-orders', [SmsCheckerAdminController::class, 'pendingOrders'])->name('pending-orders');
    Route::post('/orders/{order}/confirm', [SmsCheckerAdminController::class, 'confirmPayment'])->name('order-confirm');
    Route::post('/orders/{order}/reject', [SmsCheckerAdminController::class, 'rejectPayment'])->name('order-reject');
    if (config('app.debug')) {
        Route::get('/debug-fortune', [SmsCheckerAdminController::class, 'debugFortuneSmsChecker'])->name('debug-fortune');
    }
    Route::post('/transactions/{transaction}/retry-complete', [SmsCheckerAdminController::class, 'retryCompleteTransaction'])->name('retry-complete');
});

// Payment Bank Accounts Management (จัดการบัญชีธนาคารรับชำระเงิน)
Route::prefix('payment-bank-accounts')->name('payment-bank-accounts.')->group(function () {
    Route::get('/', [PaymentBankAccountController::class, 'index'])->name('index');
    Route::get('/create', [PaymentBankAccountController::class, 'create'])->name('create');
    Route::post('/', [PaymentBankAccountController::class, 'store'])->name('store');
    Route::get('/{account}/edit', [PaymentBankAccountController::class, 'edit'])->name('edit');
    Route::put('/{account}', [PaymentBankAccountController::class, 'update'])->name('update');
    Route::delete('/{account}', [PaymentBankAccountController::class, 'destroy'])->name('destroy');
    Route::post('/{account}/toggle', [PaymentBankAccountController::class, 'toggle'])->name('toggle');
    Route::post('/{account}/set-default', [PaymentBankAccountController::class, 'setDefault'])->name('set-default');
});

// NFC Card Management
Route::prefix('nfc-cards')->name('nfc-cards.')->group(function () {
    Route::get('/', [NFCCardController::class, 'index'])->name('index');
    Route::get('/create', [NFCCardController::class, 'create'])->name('create');
    Route::post('/', [NFCCardController::class, 'store'])->name('store');
    Route::post('/read', [NFCCardController::class, 'read'])->name('read');
    Route::get('/export', [NFCCardController::class, 'export'])->name('export');
    Route::get('/templates', [NFCCardController::class, 'getTemplates'])->name('templates');
    Route::post('/build-template-records', [NFCCardController::class, 'buildTemplateRecords'])->name('build-template-records');

    // 🆕 V3: NFC Writer - เขียนข้อมูลประเภทต่างๆ ลงบัตร NFC (ต้องอยู่ก่อน {nfcCard})
    Route::get('/writer', [NFCCardController::class, 'nfcWriter'])->name('writer');
    Route::post('/save-nfc-write-log', [NFCCardController::class, 'saveNfcWriteLog'])->name('save-nfc-write-log');
    Route::post('/generate-anti-counterfeit', [NFCCardController::class, 'generateAntiCounterfeitCode'])->name('generate-anti-counterfeit');
    Route::post('/verify-anti-counterfeit', [NFCCardController::class, 'verifyAntiCounterfeitCode'])->name('verify-anti-counterfeit');

    // Routes ที่มี {nfcCard} parameter ต้องอยู่หลังสุด
    Route::get('/{nfcCard}', [NFCCardController::class, 'show'])->name('show');
    Route::get('/{nfcCard}/edit', [NFCCardController::class, 'edit'])->name('edit');
    Route::put('/{nfcCard}', [NFCCardController::class, 'update'])->name('update');
    Route::delete('/{nfcCard}', [NFCCardController::class, 'destroy'])->name('destroy');
    Route::get('/{nfcCard}/pair', [NFCCardController::class, 'pairForm'])->name('pair-form');
    Route::get('/{nfcCard}/pair-and-write', [NFCCardController::class, 'pairAndWrite'])->name('pair-and-write');
    Route::post('/{nfcCard}/pair', [NFCCardController::class, 'pair'])->name('pair');
    Route::post('/{nfcCard}/unpair', [NFCCardController::class, 'unpair'])->name('unpair');
    Route::post('/{nfcCard}/activate', [NFCCardController::class, 'activate'])->name('activate');
    Route::post('/{nfcCard}/deactivate', [NFCCardController::class, 'deactivate'])->name('deactivate');
    Route::post('/{nfcCard}/block', [NFCCardController::class, 'block'])->name('block');
    Route::post('/{nfcCard}/unblock', [NFCCardController::class, 'unblock'])->name('unblock');
    Route::get('/{nfcCard}/topup', [NFCCardController::class, 'topUpForm'])->name('topup-form');
    Route::post('/{nfcCard}/topup', [NFCCardController::class, 'topUp'])->name('topup');

    // 🆕 V2: Enhanced NFC Features
    Route::post('/{nfcCard}/suspend', [NFCCardController::class, 'suspend'])->name('suspend');
    Route::put('/{nfcCard}/spending-limits', [NFCCardController::class, 'updateSpendingLimits'])->name('spending-limits.update');
    Route::post('/{nfcCard}/link-wallet', [NFCCardController::class, 'linkWallet'])->name('link-wallet');
    Route::post('/{nfcCard}/unlink-wallet', [NFCCardController::class, 'unlinkWallet'])->name('unlink-wallet');
    Route::put('/{nfcCard}/auto-topup', [NFCCardController::class, 'configureAutoTopUp'])->name('auto-topup.configure');
    Route::post('/{nfcCard}/enable-tpix', [NFCCardController::class, 'enableTPIX'])->name('enable-tpix');
    Route::post('/{nfcCard}/disable-tpix', [NFCCardController::class, 'disableTPIX'])->name('disable-tpix');
    Route::post('/{nfcCard}/save-nfc-uid', [NFCCardController::class, 'saveNFCUID'])->name('save-nfc-uid');

    // 🆕 V3: NFC Card Lock/Unlock System (Admin Only)
    Route::post('/{nfcCard}/lock', [NFCCardController::class, 'lockCard'])->name('lock');
    Route::post('/{nfcCard}/unlock', [NFCCardController::class, 'unlockCard'])->name('unlock');

    // 🆕 V3: NFC Card Info
    Route::post('/{nfcCard}/save-card-info', [NFCCardController::class, 'saveCardInfo'])->name('save-card-info');
});

// 🆕 NFC System Dashboard & Analytics
Route::prefix('nfc')->name('nfc.')->group(function () {
    Route::get('/dashboard', [NFCCardController::class, 'dashboard'])->name('dashboard');
    Route::get('/transactions', [NFCCardController::class, 'transactions'])->name('transactions');
});

// NFC Reader Management
Route::prefix('nfc-readers')->name('nfc-readers.')->group(function () {
    Route::get('/', [NFCReaderController::class, 'index'])->name('index');
    Route::get('/create', [NFCReaderController::class, 'create'])->name('create');
    Route::post('/', [NFCReaderController::class, 'store'])->name('store');
    Route::get('/{nfcReader}', [NFCReaderController::class, 'show'])->name('show');
    Route::get('/{nfcReader}/edit', [NFCReaderController::class, 'edit'])->name('edit');
    Route::put('/{nfcReader}', [NFCReaderController::class, 'update'])->name('update');
    Route::delete('/{nfcReader}', [NFCReaderController::class, 'destroy'])->name('destroy');
    Route::post('/{nfcReader}/activate', [NFCReaderController::class, 'activate'])->name('activate');
    Route::post('/{nfcReader}/deactivate', [NFCReaderController::class, 'deactivate'])->name('deactivate');
    Route::post('/{nfcReader}/maintenance', [NFCReaderController::class, 'maintenance'])->name('maintenance');
    Route::post('/{nfcReader}/heartbeat', [NFCReaderController::class, 'heartbeat'])->name('heartbeat');
    Route::get('/{nfcReader}/status', [NFCReaderController::class, 'status'])->name('status');
});

// NFC Transaction Management
Route::prefix('nfc-transactions')->name('nfc-transactions.')->group(function () {
    Route::get('/', [NFCTransactionController::class, 'index'])->name('index');
    Route::get('/{nfcTransaction}', [NFCTransactionController::class, 'show'])->name('show');
    Route::get('/export', [NFCTransactionController::class, 'export'])->name('export');
    Route::get('/statistics/chart', [NFCTransactionController::class, 'statistics'])->name('statistics');
    Route::get('/feed/realtime', [NFCTransactionController::class, 'feed'])->name('feed');
});

// Language Settings
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('languages', [LanguageSettingController::class, 'index'])->name('languages');
    Route::post('languages', [LanguageSettingController::class, 'update'])->name('languages.update');
    Route::post('languages/{code}/toggle', [LanguageSettingController::class, 'toggle'])->name('languages.toggle');
    Route::put('languages/{code}', [LanguageSettingController::class, 'updateLanguage'])->name('languages.update-single');
    Route::post('languages/reorder', [LanguageSettingController::class, 'reorder'])->name('languages.reorder');
    Route::get('languages/switcher', [LanguageSettingController::class, 'getSwitcherSettings'])->name('languages.switcher');
    Route::put('languages/switcher', [LanguageSettingController::class, 'updateSwitcherSettings'])->name('languages.switcher.update');

    // OCR Settings
    Route::get('ocr', [SettingsController::class, 'ocr'])->name('ocr');
    Route::post('ocr', [SettingsController::class, 'updateOcr'])->name('ocr.update');
    Route::post('ocr/test', [SettingsController::class, 'testOcrConnection'])->name('ocr.test');
    Route::get('ocr/setup-guide', [SettingsController::class, 'setupGuide'])->name('ocr.setup-guide');

    // Google Maps Settings
    Route::get('google-maps', [SettingsController::class, 'googleMaps'])->name('google-maps');
    Route::post('google-maps', [SettingsController::class, 'updateGoogleMaps'])->name('google-maps.update');
    Route::post('google-maps/test', [SettingsController::class, 'testGoogleMapsConnection'])->name('google-maps.test');
    Route::post('google-maps/capabilities', [SettingsController::class, 'checkGoogleMapsCapabilities'])->name('google-maps.capabilities');
    Route::post('google-maps/calculate-distance', [SettingsController::class, 'calculateDistance'])->name('google-maps.calculate-distance');
    Route::get('google-maps/guide', [SettingsController::class, 'googleMapsGuide'])->name('google-maps.guide');
});

// Notification Bell API (for notification bell component)
Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/unread', [NotificationController::class, 'unread'])->name('unread');
    Route::get('/immediate', [NotificationController::class, 'immediate'])->name('immediate');
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::post('/{id}/archive', [NotificationController::class, 'archive'])->name('archive');
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('delete');
    Route::post('/bulk-delete', [NotificationController::class, 'bulkDelete'])->name('bulk-delete');
    Route::post('/bulk-mark-as-read', [NotificationController::class, 'bulkMarkAsRead'])->name('bulk-mark-as-read');
});

// Notification Management (Admin Panel)
Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationManagementController::class, 'index'])->name('index');
    Route::get('/statistics', [NotificationManagementController::class, 'statistics'])->name('statistics');
    Route::get('/create', [NotificationManagementController::class, 'create'])->name('create');
    Route::post('/', [NotificationManagementController::class, 'store'])->name('store');
    Route::get('/{notification}', [NotificationManagementController::class, 'show'])->name('show');
    Route::delete('/{notification}', [NotificationManagementController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-delete', [NotificationManagementController::class, 'bulkDelete'])->name('bulk-delete');
});

// Notification Templates
Route::prefix('notification-templates')->name('notification-templates.')->group(function () {
    Route::get('/', [NotificationTemplateController::class, 'index'])->name('index');
    Route::get('/create', [NotificationTemplateController::class, 'create'])->name('create');
    Route::post('/', [NotificationTemplateController::class, 'store'])->name('store');
    Route::get('/{template}', [NotificationTemplateController::class, 'show'])->name('show');
    Route::get('/{template}/edit', [NotificationTemplateController::class, 'edit'])->name('edit');
    Route::put('/{template}', [NotificationTemplateController::class, 'update'])->name('update');
    Route::delete('/{template}', [NotificationTemplateController::class, 'destroy'])->name('destroy');
    Route::post('/{template}/toggle', [NotificationTemplateController::class, 'toggleStatus'])->name('toggle');
    Route::get('/{template}/data', [NotificationTemplateController::class, 'getTemplate'])->name('data');
});

// Rank Management
Route::prefix('ranks')->name('ranks.')->group(function () {
    Route::get('/', [RankController::class, 'index'])->name('index');
    Route::get('/create', [RankController::class, 'create'])->name('create');
    Route::post('/', [RankController::class, 'store'])->name('store');
    Route::get('/{rank}/edit', [RankController::class, 'edit'])->name('edit');
    Route::put('/{rank}', [RankController::class, 'update'])->name('update');
    Route::delete('/{rank}', [RankController::class, 'destroy'])->name('destroy');

    // Promotions Management
    Route::get('/promotions', [RankController::class, 'promotions'])->name('promotions.index');
    Route::post('/promotions/{promotion}/approve', [RankController::class, 'approvePromotion'])->name('promotions.approve');
    Route::post('/promotions/{promotion}/reject', [RankController::class, 'rejectPromotion'])->name('promotions.reject');

    // Avatar Frames Management
    Route::get('/avatar-frames', [RankController::class, 'avatarFrames'])->name('avatar-frames');
    Route::post('/{rank}/upload-frame', [RankController::class, 'uploadAvatarFrame'])->name('upload-frame');
    Route::delete('/{rank}/delete-frame', [RankController::class, 'deleteAvatarFrame'])->name('delete-frame');
    Route::post('/{rank}/update-animation', [RankController::class, 'updateFrameAnimation'])->name('update-animation');

    // Rank Settings (Requirements & Bonuses Management)
    Route::get('/{rank}/settings', [RankController::class, 'settings'])->name('settings');
    Route::post('/{rank}/privileges', [RankController::class, 'updatePrivileges'])->name('update-privileges');

    // Requirements Management
    Route::post('/{rank}/requirements', [RankController::class, 'storeRequirement'])->name('requirements.store');
    Route::put('/requirements/{requirement}', [RankController::class, 'updateRequirement'])->name('requirements.update');
    Route::delete('/requirements/{requirement}', [RankController::class, 'deleteRequirement'])->name('requirements.destroy');

    // Bonuses Management
    Route::post('/{rank}/bonuses', [RankController::class, 'storeBonus'])->name('bonuses.store');
    Route::put('/bonuses/{bonus}', [RankController::class, 'updateBonus'])->name('bonuses.update');
    Route::delete('/bonuses/{bonus}', [RankController::class, 'deleteBonus'])->name('bonuses.destroy');
});

// Virtual ID Card Designer
Route::prefix('id-card')->name('id-card.')->group(function () {
    Route::get('/', [IdCardController::class, 'index'])->name('index');
    Route::get('/designer', [IdCardController::class, 'designer'])->name('designer');
    Route::post('/save', [IdCardController::class, 'save'])->name('save');
    Route::post('/upload-background', [IdCardController::class, 'uploadBackground'])->name('upload-background');
    Route::get('/preview-data', [IdCardController::class, 'previewData'])->name('preview-data');
    Route::get('/user/{user}', [IdCardController::class, 'viewUserCard'])->name('view-user');
    Route::delete('/{setting}', [IdCardController::class, 'destroy'])->name('destroy');
    Route::post('/{setting}/duplicate', [IdCardController::class, 'duplicate'])->name('duplicate');
});

// Email Management (Email Delivery System)
Route::prefix('email')->name('email.')->group(function () {
    // Dashboard
    Route::get('/', [EmailController::class, 'index'])->name('index');
    Route::get('/statistics', [EmailController::class, 'statistics'])->name('statistics');

    // Email Campaigns (⭐ NEW)
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [EmailCampaignController::class, 'index'])->name('index');
        Route::get('/create', [EmailCampaignController::class, 'create'])->name('create');
        Route::post('/', [EmailCampaignController::class, 'store'])->name('store');
        Route::get('/{campaign}', [EmailCampaignController::class, 'show'])->name('show');
        Route::get('/{campaign}/edit', [EmailCampaignController::class, 'edit'])->name('edit');
        Route::put('/{campaign}', [EmailCampaignController::class, 'update'])->name('update');
        Route::delete('/{campaign}', [EmailCampaignController::class, 'destroy'])->name('destroy');

        // Campaign Actions
        Route::post('/{campaign}/start', [EmailCampaignController::class, 'start'])->name('start');
        Route::post('/{campaign}/pause', [EmailCampaignController::class, 'pause'])->name('pause');
        Route::post('/{campaign}/cancel', [EmailCampaignController::class, 'cancel'])->name('cancel');
    });

    // Email Queue (⭐ NEW)
    Route::prefix('queue')->name('queue.')->group(function () {
        Route::get('/', [EmailQueueController::class, 'index'])->name('index');
        Route::get('/{recipient}', [EmailQueueController::class, 'show'])->name('show');
        Route::post('/{recipient}/retry', [EmailQueueController::class, 'retry'])->name('retry');
        Route::post('/campaign/{campaign}/retry-all', [EmailQueueController::class, 'retryAll'])->name('retry-all');
        Route::delete('/failed/clear', [EmailQueueController::class, 'clearFailed'])->name('clear-failed');
    });

    // Email Analytics (⭐ NEW)
    Route::get('/analytics', [EmailAnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/export', [EmailAnalyticsController::class, 'export'])->name('analytics.export');

    // Email Logs
    Route::get('/logs', [EmailController::class, 'logs'])->name('logs');
    Route::get('/logs/{log}', [EmailController::class, 'showLog'])->name('logs.show');

    // Email Providers
    Route::get('/providers', [EmailController::class, 'providers'])->name('providers');
    Route::get('/providers/create', [EmailController::class, 'createProvider'])->name('providers.create');
    Route::post('/providers', [EmailController::class, 'storeProvider'])->name('providers.store');
    Route::get('/providers/{provider}/edit', [EmailController::class, 'editProvider'])->name('providers.edit');
    Route::put('/providers/{provider}', [EmailController::class, 'updateProvider'])->name('providers.update');
    Route::delete('/providers/{provider}', [EmailController::class, 'destroyProvider'])->name('providers.destroy');
    Route::post('/providers/{provider}/test', [EmailController::class, 'testProvider'])->name('providers.test');
    Route::post('/providers/{provider}/set-default', [EmailController::class, 'setDefaultProvider'])->name('providers.set-default');

    // Email Templates
    Route::get('/templates', [EmailController::class, 'templates'])->name('templates.index');
    Route::get('/templates/create', [EmailController::class, 'createTemplate'])->name('templates.create');
    Route::post('/templates', [EmailController::class, 'storeTemplate'])->name('templates.store');
    Route::get('/templates/{template}/edit', [EmailController::class, 'editTemplate'])->name('templates.edit');
    Route::put('/templates/{template}', [EmailController::class, 'updateTemplate'])->name('templates.update');
    Route::delete('/templates/{template}', [EmailController::class, 'destroyTemplate'])->name('templates.destroy');
    Route::get('/templates/{template}/preview', [EmailController::class, 'previewTemplate'])->name('templates.preview');

    // Send Test Email
    Route::post('/test', [EmailController::class, 'sendTest'])->name('test');
});

// Authentication Providers (login methods configuration)
Route::prefix('auth')->name('auth.')->group(function () {
    // Facebook OAuth Login Settings (DB-backed config)
    Route::prefix('facebook-oauth')->name('facebook-oauth.')->group(function () {
        Route::get('/', [FacebookOAuthController::class, 'index'])->name('index');
        Route::put('/', [FacebookOAuthController::class, 'update'])->name('update');
        Route::post('/test', [FacebookOAuthController::class, 'test'])->name('test');
    });
});

// LINE OA Management
Route::prefix('line-oa')->name('line-oa.')->group(function () {
    Route::get('/', [LineOaController::class, 'index'])->name('index');
    Route::put('/update', [LineOaController::class, 'update'])->name('update');
    Route::patch('/quick-update', [LineOaController::class, 'quickUpdate'])->name('quick-update'); // Quick Settings Panel API
    Route::post('/test-message', [LineOaController::class, 'testMessage'])->name('test-message');
    Route::post('/test-connection', [LineOaController::class, 'testConnection'])->name('test-connection');
    Route::get('/line-users', [LineOaController::class, 'getLineUsers'])->name('line-users');
    Route::get('/logs', [LineOaController::class, 'logs'])->name('logs');

    // Analytics Dashboard (Legacy) → ถูกลบแล้ว ใช้ LINE Message Analytics แทน
});

// LINE Message Analytics (Phase 2 - New Smart Analytics)
Route::prefix('line-analytics')->name('line-analytics.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [LineMessageAnalyticsController::class, 'dashboard'])->name('dashboard');

    // API Endpoints
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/overview', [LineMessageAnalyticsController::class, 'apiOverview'])->name('overview');
        Route::get('/trending', [LineMessageAnalyticsController::class, 'apiTrending'])->name('trending');
        Route::get('/errors', [LineMessageAnalyticsController::class, 'apiErrors'])->name('errors');
        Route::get('/recovery', [LineMessageAnalyticsController::class, 'apiRecovery'])->name('recovery');
        Route::get('/message-types', [LineMessageAnalyticsController::class, 'apiMessageTypes'])->name('message-types');
        Route::get('/user-engagement', [LineMessageAnalyticsController::class, 'apiUserEngagement'])->name('user-engagement');
    });

    // Utilities
    Route::post('/clear-cache', [LineMessageAnalyticsController::class, 'clearCache'])->name('clear-cache');
});

// LINE Bot AI Management
Route::prefix('line-bot')->name('line-bot.')->group(function () {
    // AI Settings
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/', [LineBotAiController::class, 'index'])->name('index');
        Route::get('/create', [LineBotAiController::class, 'create'])->name('create');
        Route::post('/', [LineBotAiController::class, 'store'])->name('store');
        // ลบ edit/update - ใช้ line-recruitment/settings แทน
        Route::delete('/{id}', [LineBotAiController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/test', [LineBotAiController::class, 'test'])->name('test');

        // Conversations & Analytics
        Route::get('/conversations', [LineBotAiController::class, 'conversations'])->name('conversations');
        Route::get('/conversations/{id}', [LineBotAiController::class, 'conversationDetail'])->name('conversations.detail');
        Route::get('/analytics', [LineBotAiController::class, 'analytics'])->name('analytics');

        // Knowledge Base
        Route::get('/{aiSettingId}/knowledge', [LineBotAiController::class, 'knowledgeIndex'])->name('knowledge.index');
        Route::get('/{aiSettingId}/knowledge/create', [LineBotAiController::class, 'knowledgeCreate'])->name('knowledge.create');
        Route::post('/{aiSettingId}/knowledge', [LineBotAiController::class, 'knowledgeStore'])->name('knowledge.store');
        Route::post('/{aiSettingId}/knowledge/{knowledgeId}/sync', [LineBotAiController::class, 'knowledgeSync'])->name('knowledge.sync');
        Route::delete('/{aiSettingId}/knowledge/{knowledgeId}', [LineBotAiController::class, 'knowledgeDestroy'])->name('knowledge.destroy');
    });

    // Rich Menus
    Route::prefix('rich-menu')->name('rich-menu.')->group(function () {
        Route::get('/', [LineRichMenuController::class, 'index'])->name('index');
        Route::get('/create', [LineRichMenuController::class, 'create'])->name('create');
        Route::post('/', [LineRichMenuController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [LineRichMenuController::class, 'edit'])->name('edit');
        Route::put('/{id}', [LineRichMenuController::class, 'update'])->name('update');
        Route::delete('/{id}', [LineRichMenuController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/set-default', [LineRichMenuController::class, 'setDefault'])->name('set-default');

        // Thaiprompt OA Rich Menu Editor
        Route::get('/thaiprompt', [ThaipromptRichMenuController::class, 'editor'])->name('thaiprompt');
        Route::get('/thaiprompt/config', [ThaipromptRichMenuController::class, 'loadConfig'])->name('thaiprompt.config');
        Route::post('/thaiprompt/deploy', [ThaipromptRichMenuController::class, 'deploy'])->name('thaiprompt.deploy');
        Route::post('/thaiprompt/upload-image', [ThaipromptRichMenuController::class, 'uploadImage'])->name('thaiprompt.upload-image');
    });

    // Broadcast
    Route::prefix('broadcast')->name('broadcast.')->group(function () {
        Route::get('/', [LineBroadcastController::class, 'index'])->name('index');
        Route::get('/create', [LineBroadcastController::class, 'create'])->name('create');
        Route::post('/', [LineBroadcastController::class, 'store'])->name('store');
        Route::get('/{id}', [LineBroadcastController::class, 'show'])->name('show');
        Route::post('/{id}/send', [LineBroadcastController::class, 'send'])->name('send');
        Route::post('/{id}/retry', [LineBroadcastController::class, 'retry'])->name('retry');
        Route::delete('/{id}', [LineBroadcastController::class, 'destroy'])->name('destroy');
    });

    // Hybrid Bot Keywords Management
    Route::prefix('keywords')->name('keywords.')->group(function () {
        Route::get('/', [LineBotKeywordController::class, 'index'])->name('index');
        Route::get('/create', [LineBotKeywordController::class, 'create'])->name('create');
        Route::post('/', [LineBotKeywordController::class, 'store'])->name('store');
        Route::get('/{keyword}/edit', [LineBotKeywordController::class, 'edit'])->name('edit');
        Route::put('/{keyword}', [LineBotKeywordController::class, 'update'])->name('update');
        Route::delete('/{keyword}', [LineBotKeywordController::class, 'destroy'])->name('destroy');
        Route::post('/test', [LineBotKeywordController::class, 'test'])->name('test');

        // Analytics & Advanced Features
        Route::get('/analytics/dashboard', [LineBotKeywordAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/analytics/export', [LineBotKeywordAnalyticsController::class, 'export'])->name('export');
        Route::post('/analytics/import', [LineBotKeywordAnalyticsController::class, 'import'])->name('import');
        Route::post('/{keyword}/clone', [LineBotKeywordAnalyticsController::class, 'clone'])->name('clone');
        Route::post('/analytics/bulk-update-status', [LineBotKeywordAnalyticsController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
        Route::post('/analytics/bulk-delete', [LineBotKeywordAnalyticsController::class, 'bulkDelete'])->name('bulk-delete');

        // Activity Logs & Monitoring
        Route::prefix('activity')->name('activity.')->group(function () {
            Route::get('/', [KeywordActivityLogController::class, 'index'])->name('index');
            Route::get('/export', [KeywordActivityLogController::class, 'export'])->name('export');
            Route::get('/daily-chart', [KeywordActivityLogController::class, 'getDailyActivityChart'])->name('daily-chart');
            Route::get('/keyword-stats', [KeywordActivityLogController::class, 'getKeywordStats'])->name('keyword-stats');
            Route::get('/user-history', [KeywordActivityLogController::class, 'getUserHistory'])->name('user-history');
            Route::post('/clear-old-logs', [KeywordActivityLogController::class, 'clearOldLogs'])->name('clear-old-logs');
        });

        // Performance Dashboard & Analytics
        Route::prefix('performance')->name('performance.')->group(function () {
            Route::get('/', [KeywordPerformanceDashboardController::class, 'index'])->name('index');
            Route::get('/chart-data', [KeywordPerformanceDashboardController::class, 'getChartData'])->name('chart-data');
            Route::get('/comparison-data', [KeywordPerformanceDashboardController::class, 'getComparisonData'])->name('comparison-data');
            Route::get('/response-time-data', [KeywordPerformanceDashboardController::class, 'getResponseTimeData'])->name('response-time-data');
            Route::get('/trend-data', [KeywordPerformanceDashboardController::class, 'getTrendData'])->name('trend-data');
            Route::get('/{keyword}/details', [KeywordPerformanceDashboardController::class, 'getKeywordDetails'])->name('details');
            Route::get('/export', [KeywordPerformanceDashboardController::class, 'exportReport'])->name('export');
        });

        // Keyword Suggestions Engine
        Route::prefix('suggestions')->name('suggestions.')->group(function () {
            Route::get('/', [KeywordSuggestionController::class, 'index'])->name('index');
            Route::get('/json', [KeywordSuggestionController::class, 'getSuggestionsJson'])->name('json');
            Route::get('/stats', [KeywordSuggestionController::class, 'getStatistics'])->name('stats');
            Route::get('/recommendations', [KeywordSuggestionController::class, 'getRecommendations'])->name('recommendations');
            Route::post('/preview', [KeywordSuggestionController::class, 'preview'])->name('preview');
            Route::post('/approve', [KeywordSuggestionController::class, 'approve'])->name('approve');
            Route::post('/approve-batch', [KeywordSuggestionController::class, 'approveBatch'])->name('approve-batch');
            Route::post('/reject', [KeywordSuggestionController::class, 'reject'])->name('reject');
            Route::get('/detail', [KeywordSuggestionController::class, 'getDetail'])->name('detail');
            Route::get('/refresh', [KeywordSuggestionController::class, 'refresh'])->name('refresh');
            Route::get('/export', [KeywordSuggestionController::class, 'export'])->name('export');
        });

        // A/B Testing System
        Route::prefix('ab-tests')->name('ab-tests.')->group(function () {
            Route::get('/', [KeywordABTestController::class, 'index'])->name('index');
            Route::get('/create', [KeywordABTestController::class, 'create'])->name('create');
            Route::post('/', [KeywordABTestController::class, 'store'])->name('store');
            Route::get('/{test}', [KeywordABTestController::class, 'show'])->name('show');
            Route::get('/{test}/edit', [KeywordABTestController::class, 'edit'])->name('edit');
            Route::put('/{test}', [KeywordABTestController::class, 'update'])->name('update');
            Route::post('/{test}/start', [KeywordABTestController::class, 'start'])->name('start');
            Route::post('/{test}/pause', [KeywordABTestController::class, 'pause'])->name('pause');
            Route::post('/{test}/complete', [KeywordABTestController::class, 'complete'])->name('complete');
            Route::post('/{test}/apply-winner', [KeywordABTestController::class, 'applyWinner'])->name('apply-winner');
            Route::delete('/{test}', [KeywordABTestController::class, 'destroy'])->name('destroy');
            Route::get('/api/list', [KeywordABTestController::class, 'listJson'])->name('list-json');
            Route::get('/api/statistics', [KeywordABTestController::class, 'statistics'])->name('statistics');
            Route::get('/api/recommendations', [KeywordABTestController::class, 'recommendations'])->name('recommendations');
            Route::get('/{test}/chart-data', [KeywordABTestController::class, 'chartData'])->name('chart-data');
            Route::get('/{test}/timeline-data', [KeywordABTestController::class, 'timelineData'])->name('timeline-data');
        });

        // Sentiment Analysis System
        Route::prefix('sentiment-analysis')->name('sentiment-analysis.')->group(function () {
            Route::get('/', [SentimentAnalysisController::class, 'index'])->name('index');
            Route::get('/{sentiment}', [SentimentAnalysisController::class, 'show'])->name('show');
            Route::delete('/{sentiment}', [SentimentAnalysisController::class, 'destroy'])->name('destroy');
            Route::get('/api/list', [SentimentAnalysisController::class, 'listJson'])->name('list-json');
            Route::get('/api/statistics', [SentimentAnalysisController::class, 'statistics'])->name('statistics');
            Route::get('/api/trend-data', [SentimentAnalysisController::class, 'trendData'])->name('trend-data');
            Route::get('/api/pain-points', [SentimentAnalysisController::class, 'painPointsData'])->name('pain-points');
            Route::get('/api/emotions', [SentimentAnalysisController::class, 'emotionData'])->name('emotions');
            Route::get('/api/recommendations', [SentimentAnalysisController::class, 'recommendations'])->name('recommendations');
            Route::get('/api/top-complaints', [SentimentAnalysisController::class, 'topComplaints'])->name('top-complaints');
            Route::get('/api/urgent-issues', [SentimentAnalysisController::class, 'urgentIssues'])->name('urgent-issues');
            Route::get('/api/export-report', [SentimentAnalysisController::class, 'exportReport'])->name('export-report');
        });

        // NLP Enhancement System (Entity Extraction, Intent Recognition, Clustering - Phase 2.4)
        Route::prefix('nlp-analysis')->name('nlp-analysis.')->group(function () {
            // Dashboard
            Route::get('/', [NLPAnalysisController::class, 'index'])->name('index');

            // Entities Management
            Route::get('/entities', [NLPAnalysisController::class, 'entities'])->name('entities');
            Route::delete('/entities/{entity}', [NLPAnalysisController::class, 'deleteEntity'])->name('delete-entity');

            // Intents Management
            Route::get('/intents', [NLPAnalysisController::class, 'intents'])->name('intents');
            Route::delete('/intents/{intent}', [NLPAnalysisController::class, 'deleteIntent'])->name('delete-intent');

            // Clusters Management
            Route::get('/clusters', [NLPAnalysisController::class, 'clusters'])->name('clusters');
            Route::get('/clusters/{cluster}', [NLPAnalysisController::class, 'showCluster'])->name('show-cluster');
            Route::post('/clusters', [NLPAnalysisController::class, 'createCluster'])->name('create-cluster');
            Route::put('/clusters/{cluster}', [NLPAnalysisController::class, 'updateCluster'])->name('update-cluster');
            Route::delete('/clusters/{cluster}', [NLPAnalysisController::class, 'deleteCluster'])->name('delete-cluster');

            // API Endpoints for Data & Analytics
            Route::get('/api/entity-statistics', [NLPAnalysisController::class, 'entityStatistics'])->name('entity-statistics');
            Route::get('/api/intent-statistics', [NLPAnalysisController::class, 'intentStatistics'])->name('intent-statistics');
            Route::get('/api/cluster-usage', [NLPAnalysisController::class, 'clusterUsageData'])->name('cluster-usage');
            Route::get('/api/cluster-recommendations', [NLPAnalysisController::class, 'clusterRecommendations'])->name('cluster-recommendations');
            Route::get('/api/related-keywords', [NLPAnalysisController::class, 'relatedKeywords'])->name('related-keywords');
            Route::get('/api/entity-cooccurrence', [NLPAnalysisController::class, 'entityCoOccurrence'])->name('entity-cooccurrence');
            Route::get('/api/export-report', [NLPAnalysisController::class, 'exportReport'])->name('export-report');
        });

        // 🩹 (2026-05-08) Advanced NLP + Advanced Analytics — ลบทั้งบล็อก
        //   เหตุผล: AdvancedNLPController + AdvancedAnalyticsController ไม่มีใน codebase
        //   ทำให้ route:list throw "Class does not exist" → autoload fail → LINE webhook 500
        // === START ลบทิ้ง ===
        // Route::prefix('advanced-nlp')->name('advanced-nlp.')->group(function () {
        //     Route::get('/conversations', [AdvancedNLPController::class, 'conversations'])->name('conversations');
        //     Route::get('/api/conversation-analytics/{userId}', [AdvancedNLPController::class, 'conversationAnalytics'])->name('conversation-analytics');
        //     Route::get('/api/similar-messages', [AdvancedNLPController::class, 'similarMessages'])->name('similar-messages');
        // });
        //
        // Route::prefix('advanced-analytics')->name('advanced-analytics.')->group(function () {
        //     Route::get('/', [AdvancedAnalyticsController::class, 'dashboard'])->name('dashboard');
        //     Route::get('/predictions', [AdvancedAnalyticsController::class, 'predictions'])->name('predictions');
        //     Route::get('/anomalies', [AdvancedAnalyticsController::class, 'anomalies'])->name('anomalies');
        //     Route::get('/forecasts', [AdvancedAnalyticsController::class, 'forecasts'])->name('forecasts');
        //     Route::get('/insights', [AdvancedAnalyticsController::class, 'insights'])->name('insights');
        //     Route::get('/api/forecast-data', [AdvancedAnalyticsController::class, 'forecastData'])->name('forecast-data');
        //     Route::get('/api/anomaly-data', [AdvancedAnalyticsController::class, 'anomalyData'])->name('anomaly-data');
        //     Route::get('/api/prediction-data', [AdvancedAnalyticsController::class, 'predictionData'])->name('prediction-data');
        // });
        // === END ลบทิ้ง ===
    });
});

// LINE Connections Management (ผู้ใช้ที่เชื่อมต่อ LINE)
Route::prefix('line-connections')->name('line-connections.')->group(function () {
    Route::get('/', [LineConnectionsController::class, 'index'])->name('index');
    Route::get('/export', [LineConnectionsController::class, 'export'])->name('export');
    Route::get('/{user}', [LineConnectionsController::class, 'show'])->name('show');
    Route::post('/{user}/disconnect', [LineConnectionsController::class, 'disconnect'])->name('disconnect');
    Route::post('/bulk-disconnect', [LineConnectionsController::class, 'bulkDisconnect'])->name('bulk-disconnect');
});

// LINE Recruitment Management (AI-Powered Recruitment System)
Route::prefix('line-recruitment')->name('line-recruitment.')->group(function () {
    // Dashboard
    Route::get('/', [LineRecruitmentController::class, 'index'])->name('index');

    // Settings Management
    Route::get('/settings', [LineRecruitmentController::class, 'settings'])->name('settings');
    Route::put('/settings/{id}', [LineRecruitmentController::class, 'updateSettings'])->name('settings.update');

    // Conversations Management
    Route::get('/conversations', [LineRecruitmentController::class, 'conversations'])->name('conversations');
    Route::get('/conversations/{id}', [LineRecruitmentController::class, 'conversationDetail'])->name('conversations.show');
    Route::delete('/conversations/{id}', [LineRecruitmentController::class, 'deleteConversation'])->name('conversations.delete');
    Route::get('/export-conversations', [LineRecruitmentController::class, 'exportConversations'])->name('conversations.export');

    // Topic Boundaries Management
    Route::get('/{aiSettingId}/topic-boundaries', [LineRecruitmentController::class, 'topicBoundaries'])->name('topic-boundaries');
    Route::post('/{aiSettingId}/topic-boundaries', [LineRecruitmentController::class, 'storeTopicBoundary'])->name('topic-boundaries.store');
    Route::put('/{aiSettingId}/topic-boundaries/{topicId}', [LineRecruitmentController::class, 'updateTopicBoundary'])->name('topic-boundaries.update');
    Route::delete('/{aiSettingId}/topic-boundaries/{topicId}', [LineRecruitmentController::class, 'deleteTopicBoundary'])->name('topic-boundaries.delete');

    // Knowledge Base Management
    Route::get('/{aiSettingId}/knowledge-base', [LineRecruitmentController::class, 'knowledgeBase'])->name('knowledge-base');
    Route::post('/{aiSettingId}/knowledge-base', [LineRecruitmentController::class, 'storeKnowledgeBase'])->name('knowledge-base.store');
    Route::put('/{aiSettingId}/knowledge-base/{knowledgeId}', [LineRecruitmentController::class, 'updateKnowledgeBase'])->name('knowledge-base.update');
    Route::delete('/{aiSettingId}/knowledge-base/{knowledgeId}', [LineRecruitmentController::class, 'deleteKnowledgeBase'])->name('knowledge-base.delete');

    // AI Testing
    Route::post('/test-ai/{id}', [LineRecruitmentController::class, 'testAi'])->name('test-ai');
    Route::post('/test-topic-filter/{id}', [LineRecruitmentController::class, 'testTopicFilter'])->name('test-topic-filter');
});

// MLM Prospects Management
Route::prefix('mlm-prospects')->name('mlm-prospects.')->group(function () {
    Route::get('/', [MlmProspectController::class, 'index'])->name('index');
    Route::get('/{id}', [MlmProspectController::class, 'show'])->name('show');
    Route::post('/expire-old', [MlmProspectController::class, 'expireOld'])->name('expire-old');
});

// OTP Settings Management
Route::prefix('otp')->name('otp.')->group(function () {
    Route::get('/settings', [OtpSettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [OtpSettingsController::class, 'update'])->name('settings.update');
    Route::post('/test', [OtpSettingsController::class, 'test'])->name('test');
});

// Two-Factor Authentication Settings
Route::prefix('two-factor')->name('two-factor.')->group(function () {
    Route::get('/settings', [TwoFactorSettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [TwoFactorSettingsController::class, 'update'])->name('settings.update');
});

// =============================================================================
// AI CORE - CENTRALIZED AI MANAGEMENT SYSTEM
// =============================================================================
// ระบบควบคุม AI ทั้งหมดแบบรวมศูนย์
// จัดการ Features, Tenants, Quotas, Schedules, Alerts
Route::prefix('ai-core')->name('ai-core.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AICoreController::class, 'dashboard'])->name('dashboard');

    // Features Management
    Route::prefix('features')->name('features.')->group(function () {
        Route::get('/', [AICoreFeatureController::class, 'index'])->name('index');
        Route::get('/create', [AICoreFeatureController::class, 'create'])->name('create');
        Route::post('/', [AICoreFeatureController::class, 'store'])->name('store');
        Route::get('/{feature}', [AICoreFeatureController::class, 'show'])->name('show');
        Route::get('/{feature}/edit', [AICoreFeatureController::class, 'edit'])->name('edit');
        Route::put('/{feature}', [AICoreFeatureController::class, 'update'])->name('update');
        Route::delete('/{feature}', [AICoreFeatureController::class, 'destroy'])->name('destroy');
        Route::post('/{feature}/toggle', [AICoreFeatureController::class, 'toggle'])->name('toggle');
    });

    // Tenants Management
    Route::prefix('tenants')->name('tenants.')->group(function () {
        Route::get('/', [AICoreTenantController::class, 'index'])->name('index');
        Route::get('/create', [AICoreTenantController::class, 'create'])->name('create');
        Route::post('/', [AICoreTenantController::class, 'store'])->name('store');
        Route::get('/{tenant}', [AICoreTenantController::class, 'show'])->name('show');
        Route::get('/{tenant}/edit', [AICoreTenantController::class, 'edit'])->name('edit');
        Route::put('/{tenant}', [AICoreTenantController::class, 'update'])->name('update');
        Route::delete('/{tenant}', [AICoreTenantController::class, 'destroy'])->name('destroy');
        Route::post('/{tenant}/toggle', [AICoreTenantController::class, 'toggle'])->name('toggle');

        // Feature Access for Tenant
        Route::get('/{tenant}/features', [AICoreTenantController::class, 'features'])->name('features');
        Route::post('/{tenant}/features/{feature}/enable', [AICoreTenantController::class, 'enableFeature'])->name('features.enable');
        Route::post('/{tenant}/features/{feature}/disable', [AICoreTenantController::class, 'disableFeature'])->name('features.disable');
    });

    // Quotas Management
    Route::prefix('quotas')->name('quotas.')->group(function () {
        Route::get('/', [AICoreQuotaController::class, 'index'])->name('index');
        Route::get('/{tenant}/{feature}', [AICoreQuotaController::class, 'manage'])->name('manage');
        Route::post('/{tenant}/{feature}/add-bonus', [AICoreQuotaController::class, 'addBonus'])->name('add-bonus');
        Route::post('/{tenant}/{feature}/reset', [AICoreQuotaController::class, 'reset'])->name('reset');
        Route::post('/reset-all-expired', [AICoreQuotaController::class, 'resetAllExpired'])->name('reset-all-expired');
    });

    // Schedules Management
    Route::prefix('schedules')->name('schedules.')->group(function () {
        Route::get('/', [AICoreScheduleController::class, 'index'])->name('index');
        Route::get('/create', [AICoreScheduleController::class, 'create'])->name('create');
        Route::post('/', [AICoreScheduleController::class, 'store'])->name('store');
        Route::get('/{schedule}', [AICoreScheduleController::class, 'show'])->name('show');
        Route::get('/{schedule}/edit', [AICoreScheduleController::class, 'edit'])->name('edit');
        Route::put('/{schedule}', [AICoreScheduleController::class, 'update'])->name('update');
        Route::delete('/{schedule}', [AICoreScheduleController::class, 'destroy'])->name('destroy');
        Route::post('/{schedule}/toggle', [AICoreScheduleController::class, 'toggle'])->name('toggle');
        Route::post('/{schedule}/execute', [AICoreScheduleController::class, 'execute'])->name('execute');
    });

    // Alerts Management
    Route::prefix('alerts')->name('alerts.')->group(function () {
        Route::get('/', [AICoreAlertController::class, 'index'])->name('index');
        Route::get('/{alert}', [AICoreAlertController::class, 'show'])->name('show');
        Route::post('/{alert}/read', [AICoreAlertController::class, 'markAsRead'])->name('read');
        Route::post('/{alert}/acknowledge', [AICoreAlertController::class, 'acknowledge'])->name('acknowledge');
        Route::post('/{alert}/resolve', [AICoreAlertController::class, 'resolve'])->name('resolve');
        Route::post('/{alert}/dismiss', [AICoreAlertController::class, 'dismiss'])->name('dismiss');
        Route::post('/mark-all-read', [AICoreAlertController::class, 'markAllAsRead'])->name('mark-all-read');
    });

    // Usage Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AICoreAnalyticsController::class, 'index'])->name('index');
        Route::get('/feature/{feature}', [AICoreAnalyticsController::class, 'featureUsage'])->name('feature');
        Route::get('/tenant/{tenant}', [AICoreAnalyticsController::class, 'tenantUsage'])->name('tenant');
        Route::get('/export', [AICoreAnalyticsController::class, 'export'])->name('export');
    });

    // Global Settings
    Route::get('/settings', [AICoreController::class, 'settings'])->name('settings.index');
    Route::post('/settings', [AICoreController::class, 'updateSettings'])->name('settings.update');
});

// Central AI Management (Ollama + PostXAgent)
Route::prefix('central-ai')->name('central-ai.')->group(function () {
    // Main index (แสดง Wizard หรือ Dashboard)
    Route::get('/', [CentralAiController::class, 'index'])->name('index');
    Route::get('/wizard', [CentralAiController::class, 'wizard'])->name('wizard');
    Route::get('/dashboard', [CentralAiController::class, 'dashboard'])->name('dashboard');

    // System Resources
    Route::get('/system/resources', [CentralAiController::class, 'checkSystemResources'])->name('system.resources');

    // Ollama Management
    Route::prefix('ollama')->name('ollama.')->group(function () {
        Route::get('/', [CentralAiController::class, 'ollamaIndex'])->name('index');
        Route::get('/status', [CentralAiController::class, 'checkOllamaStatus'])->name('status');
        Route::post('/install', [CentralAiController::class, 'installOllama'])->name('install');
        Route::post('/start', [CentralAiController::class, 'startOllama'])->name('start');
        Route::post('/stop', [CentralAiController::class, 'stopOllama'])->name('stop');
        Route::post('/restart', [CentralAiController::class, 'restartOllama'])->name('restart');
        Route::post('/download-model', [CentralAiController::class, 'downloadModel'])->name('download-model');
        Route::post('/delete-model', [CentralAiController::class, 'deleteModel'])->name('delete-model');
        Route::post('/chat-test', [CentralAiController::class, 'chatTest'])->name('chat-test');
        Route::get('/running-models', [CentralAiController::class, 'getRunningModels'])->name('running-models');
        Route::get('/resource-usage', [CentralAiController::class, 'getResourceUsage'])->name('resource-usage');
    });

    // PostXAgent Management
    Route::prefix('postxagent')->name('postxagent.')->group(function () {
        Route::get('/status', [CentralAiController::class, 'checkPostXAgentStatus'])->name('status');
    });

    // Settings
    Route::get('/settings', [CentralAiController::class, 'settings'])->name('settings');
    Route::post('/settings', [CentralAiController::class, 'saveSettings'])->name('settings.save');
    Route::post('/setup/complete', [CentralAiController::class, 'completeSetup'])->name('setup.complete');

    // Health Check
    Route::get('/health-check', [CentralAiController::class, 'performHealthCheck'])->name('health-check');
});

// AI Installation & Management
Route::prefix('ai-installation')->name('ai-installation.')->group(function () {
    // Installation Wizard
    Route::get('/', [AiInstallationController::class, 'index'])->name('index');

    // System Requirements
    Route::get('/check-requirements', [AiInstallationController::class, 'checkRequirements'])->name('check-requirements');
    Route::get('/recommendations', [AiInstallationController::class, 'getRecommendations'])->name('recommendations');
    Route::post('/analyze-model', [AiInstallationController::class, 'analyzeModel'])->name('analyze-model');
    Route::post('/calculate-disk-space', [AiInstallationController::class, 'calculateDiskSpace'])->name('calculate-disk-space');
    Route::post('/optimal-settings', [AiInstallationController::class, 'getOptimalSettings'])->name('optimal-settings');

    // Installation Process
    Route::post('/start', [AiInstallationController::class, 'startInstallation'])->name('start');
    Route::get('/progress/{installationId}', [AiInstallationController::class, 'getProgress'])->name('progress');
    Route::post('/cancel/{installationId}', [AiInstallationController::class, 'cancelInstallation'])->name('cancel');

    // Model Management
    Route::get('/installed-models', [AiInstallationController::class, 'getInstalledModels'])->name('installed-models');
    Route::post('/uninstall', [AiInstallationController::class, 'uninstallModel'])->name('uninstall');

    // Ollama Status
    Route::get('/ollama-status', [AiInstallationController::class, 'checkOllamaStatus'])->name('ollama-status');

    // Installation History
    Route::get('/history', [AiInstallationController::class, 'getInstallationHistory'])->name('history');
    Route::get('/logs/{installationId}', [AiInstallationController::class, 'getInstallationLog'])->name('logs');
});

// Learning Center - User View
Route::prefix('learning-center')->name('learning-center.')->group(function () {
    Route::get('/', [LearningCenterController::class, 'index'])->name('index');
    Route::get('/category/{slug}', [LearningCenterController::class, 'category'])->name('category');
    Route::get('/article/{slug}', [LearningCenterController::class, 'article'])->name('article');
    Route::post('/article/{slug}/complete', [LearningCenterController::class, 'complete'])->name('article.complete');
    Route::post('/article/{slug}/progress', [LearningCenterController::class, 'updateProgress'])->name('article.progress');
    Route::get('/article/{slug}/check-access', [LearningCenterController::class, 'checkAccess'])->name('article.check-access');
    Route::get('/my-stats', [LearningCenterController::class, 'getMyStats'])->name('my-stats');
});

// Instructor Dashboard - For Course Instructors (แยกจาก Admin)
Route::prefix('instructor')->name('instructor.')->group(function () {
    // Dashboard หลัก
    Route::get('/', [InstructorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [InstructorDashboardController::class, 'index'])->name('index');

    // จัดการคอร์ส
    Route::prefix('courses')->name('courses.')->group(function () {
        Route::get('/', [InstructorDashboardController::class, 'courses'])->name('index');
        Route::get('/create', [InstructorDashboardController::class, 'createCourse'])->name('create');
        Route::post('/', [InstructorDashboardController::class, 'storeCourse'])->name('store');
        Route::get('/{article}/edit', [InstructorDashboardController::class, 'editCourse'])->name('edit');
        Route::put('/{article}', [InstructorDashboardController::class, 'updateCourse'])->name('update');
        Route::get('/{article}/stats', [InstructorDashboardController::class, 'courseStats'])->name('stats');
        Route::get('/{article}/quiz', [InstructorDashboardController::class, 'manageQuiz'])->name('quiz');
        Route::post('/{article}/submit-approval', [InstructorDashboardController::class, 'submitForApproval'])->name('submit-approval');
        Route::post('/{article}/issue-certificate/{user}', [InstructorDashboardController::class, 'issueCertificate'])->name('issue-certificate');
    });

    // รายได้และสถิติ
    Route::get('/earnings', [InstructorDashboardController::class, 'earnings'])->name('earnings');
});

// Quiz - Student View
Route::prefix('quiz')->name('quiz.')->group(function () {
    Route::get('/{id}', [QuizController::class, 'show'])->name('show');
    Route::post('/{id}/submit', [QuizController::class, 'submit'])->name('submit');
    Route::get('/results/{attemptId}', [QuizController::class, 'results'])->name('results');
    Route::get('/article/{articleSlug}', [QuizController::class, 'index'])->name('index');
});

// Quiz Management - Instructor/Admin
Route::prefix('quiz-management')->name('quiz-management.')->group(function () {
    Route::get('/', [QuizManagementController::class, 'index'])->name('index');
    Route::get('/create', [QuizManagementController::class, 'create'])->name('create');
    Route::post('/', [QuizManagementController::class, 'store'])->name('store');
    Route::get('/{id}', [QuizManagementController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [QuizManagementController::class, 'edit'])->name('edit');
    Route::put('/{id}', [QuizManagementController::class, 'update'])->name('update');
    Route::delete('/{id}', [QuizManagementController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/attempts', [QuizManagementController::class, 'attempts'])->name('attempts');
});

// Certificates - User Certificates
Route::prefix('certificates')->name('certificates.')->group(function () {
    Route::get('/', [CertificateController::class, 'index'])->name('index');
    Route::post('/generate/{articleId}', [CertificateController::class, 'generate'])->name('generate');
    Route::get('/{id}', [CertificateController::class, 'show'])->name('show');
    Route::get('/{id}/download', [CertificateController::class, 'download'])->name('download');
});

// Article Management - Admin Only
Route::prefix('articles')->name('articles.')->group(function () {
    Route::get('/', [ArticleManagementController::class, 'index'])->name('index');
    Route::get('/create', [ArticleManagementController::class, 'create'])->name('create');
    Route::post('/', [ArticleManagementController::class, 'store'])->name('store');
    Route::get('/{article}/edit', [ArticleManagementController::class, 'edit'])->name('edit');
    Route::put('/{article}', [ArticleManagementController::class, 'update'])->name('update');
    Route::delete('/{article}', [ArticleManagementController::class, 'destroy'])->name('destroy');
    Route::get('/{article}/permissions', [ArticleManagementController::class, 'permissions'])->name('permissions');
    Route::put('/{article}/permissions', [ArticleManagementController::class, 'updatePermissions'])->name('permissions.update');
});

// Category Management - Admin Only
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryManagementController::class, 'index'])->name('index');
    Route::get('/create', [CategoryManagementController::class, 'create'])->name('create');
    Route::post('/', [CategoryManagementController::class, 'store'])->name('store');
    Route::get('/{category}/edit', [CategoryManagementController::class, 'edit'])->name('edit');
    Route::put('/{category}', [CategoryManagementController::class, 'update'])->name('update');
    Route::delete('/{category}', [CategoryManagementController::class, 'destroy'])->name('destroy');
    Route::post('/reorder', [CategoryManagementController::class, 'reorder'])->name('reorder');
});

// AI Provider Management
Route::prefix('ai-providers')->name('ai-providers.')->group(function () {
    Route::get('/', [AiProviderManagementController::class, 'index'])->name('index');
    Route::post('/{id}/toggle', [AiProviderManagementController::class, 'toggleProvider'])->name('toggle');
    Route::match(['post', 'put'], '/{id}/config', [AiProviderManagementController::class, 'updateConfig'])->name('config');
    Route::post('/{id}/test', [AiProviderManagementController::class, 'testConnection'])->name('test');
    Route::post('/{id}/test-chat', [AiProviderManagementController::class, 'testChat'])->name('test-chat');
    Route::get('/{id}/models', [AiProviderManagementController::class, 'getProviderModels'])->name('models');
    Route::post('/models/{id}/toggle', [AiProviderManagementController::class, 'toggleModel'])->name('models.toggle');

    // Local AI Control
    Route::post('/local/start', [AiProviderManagementController::class, 'startLocalAi'])->name('local.start');
    Route::post('/local/stop', [AiProviderManagementController::class, 'stopLocalAi'])->name('local.stop');
    Route::post('/local/restart', [AiProviderManagementController::class, 'restartLocalAi'])->name('local.restart');
    Route::get('/local/status', [AiProviderManagementController::class, 'getLocalAiStatus'])->name('local.status');
    Route::post('/local/load-model', [AiProviderManagementController::class, 'loadModel'])->name('local.load-model');

    // Llama Installation
    Route::get('/install', [AiProviderManagementController::class, 'installPage'])->name('install');
    Route::post('/install/start', [AiProviderManagementController::class, 'startInstall'])->name('install.start');
    Route::get('/install/progress', [AiProviderManagementController::class, 'getInstallProgress'])->name('install.progress');
    Route::post('/install/cancel', [AiProviderManagementController::class, 'cancelInstall'])->name('install.cancel');
    Route::get('/install/log', [AiProviderManagementController::class, 'getInstallLog'])->name('install.log');

    // PostXAgent AI Manager Control
    Route::prefix('postxagent')->name('postxagent.')->group(function () {
        Route::get('/status', [AiProviderManagementController::class, 'getPostXAgentStatus'])->name('status');
        Route::post('/test', [AiProviderManagementController::class, 'testPostXAgentConnection'])->name('test');
        Route::post('/clear-cache', [AiProviderManagementController::class, 'clearPostXAgentCache'])->name('clear-cache');
        Route::get('/providers', [AiProviderManagementController::class, 'getPostXAgentProviders'])->name('providers');
        Route::get('/providers/{providerId}/models', [AiProviderManagementController::class, 'getPostXAgentModels'])->name('providers.models');
        Route::post('/config', [AiProviderManagementController::class, 'updatePostXAgentConfig'])->name('config');
        Route::post('/test-chat', [AiProviderManagementController::class, 'testPostXAgentChat'])->name('test-chat');
    });
});

// AI Monitoring & Analytics
Route::prefix('ai-monitoring')->name('ai-monitoring.')->group(function () {
    Route::get('/', [AiMonitoringController::class, 'index'])->name('index');
    Route::get('/realtime', [AiMonitoringController::class, 'getRealtimeMetrics'])->name('realtime');
    Route::get('/system', [AiMonitoringController::class, 'getSystemMetrics'])->name('system');
    Route::get('/requests', [AiMonitoringController::class, 'getRequestMetrics'])->name('requests');
    Route::get('/conversations', [AiMonitoringController::class, 'getConversationMetrics'])->name('conversations');
    Route::get('/models', [AiMonitoringController::class, 'getModelMetrics'])->name('models');
    Route::get('/providers', [AiMonitoringController::class, 'getProviderMetrics'])->name('providers');
    Route::get('/timeseries', [AiMonitoringController::class, 'getTimeSeriesData'])->name('timeseries');
    Route::post('/record', [AiMonitoringController::class, 'recordMetrics'])->name('record');
    Route::get('/dashboard-summary', [AiMonitoringController::class, 'getDashboardSummary'])->name('dashboard-summary');
});

// AI Bot Management (CRUD for Bot Profiles)
Route::prefix('ai-bots')->name('ai-bots.')->group(function () {
    Route::get('/', [AiBotController::class, 'index'])->name('index');
    Route::get('/create', [AiBotController::class, 'create'])->name('create');
    Route::post('/', [AiBotController::class, 'store'])->name('store');
    Route::get('/{id}', [AiBotController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [AiBotController::class, 'edit'])->name('edit');
    Route::put('/{id}', [AiBotController::class, 'update'])->name('update');
    Route::delete('/{id}', [AiBotController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle', [AiBotController::class, 'toggle'])->name('toggle');
    Route::post('/{id}/test', [AiBotController::class, 'test'])->name('test');
    Route::post('/{id}/duplicate', [AiBotController::class, 'duplicate'])->name('duplicate');
    Route::get('/providers/{providerId}/models', [AiBotController::class, 'getModelsByProvider'])->name('providers.models');
});

// API Endpoint Management
Route::prefix('api-endpoints')->name('api-endpoints.')->group(function () {
    Route::get('/', [ApiEndpointController::class, 'index'])->name('index');
    Route::get('/create', [ApiEndpointController::class, 'create'])->name('create');
    Route::post('/', [ApiEndpointController::class, 'store'])->name('store');
    Route::get('/{apiEndpoint}', [ApiEndpointController::class, 'show'])->name('show');
    Route::get('/{apiEndpoint}/edit', [ApiEndpointController::class, 'edit'])->name('edit');
    Route::put('/{apiEndpoint}', [ApiEndpointController::class, 'update'])->name('update');
    Route::delete('/{apiEndpoint}', [ApiEndpointController::class, 'destroy'])->name('destroy');
    Route::post('/{apiEndpoint}/toggle-status', [ApiEndpointController::class, 'toggleStatus'])->name('toggle-status');
    Route::get('/{apiEndpoint}/analytics', [ApiEndpointController::class, 'analytics'])->name('analytics');
});

// Knowledge Base Management (RAG System)
Route::prefix('ai-bots/{botId}/knowledge-bases')->name('knowledge-bases.')->group(function () {
    Route::get('/', [KnowledgeBaseController::class, 'index'])->name('index');
    Route::get('/create', [KnowledgeBaseController::class, 'create'])->name('create');
    Route::post('/', [KnowledgeBaseController::class, 'store'])->name('store');
    Route::get('/{id}', [KnowledgeBaseController::class, 'show'])->name('show');
    Route::delete('/{id}', [KnowledgeBaseController::class, 'destroy'])->name('destroy');
    Route::post('/test-search', [KnowledgeBaseController::class, 'testSearch'])->name('test-search');
});

// ═══════════════════════════════════════════════════════════════════════
// Official Shop Management (ร้านของระบบ - Premium V3)
// สินค้าที่สร้างจากที่นี่จะมี seller_id = null เพื่อแสดงใน Official Shop
// ═══════════════════════════════════════════════════════════════════════
Route::prefix('official-shop')->name('official-shop.')->group(function () {
    // Dashboard
    Route::get('/', [OfficialShopAdminController::class, 'dashboard'])->name('dashboard');

    // Products Management
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [OfficialShopAdminController::class, 'index'])->name('index');
        Route::get('/create', [OfficialShopAdminController::class, 'create'])->name('create');
        Route::post('/', [OfficialShopAdminController::class, 'store'])->name('store');
        Route::get('/{product}', [OfficialShopAdminController::class, 'show'])->name('show');
        Route::get('/{product}/edit', [OfficialShopAdminController::class, 'edit'])->name('edit');
        Route::put('/{product}', [OfficialShopAdminController::class, 'update'])->name('update');
        Route::delete('/{product}', [OfficialShopAdminController::class, 'destroy'])->name('destroy');

        // Quick Actions
        Route::post('/{product}/toggle-active', [OfficialShopAdminController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{product}/toggle-featured', [OfficialShopAdminController::class, 'toggleFeatured'])->name('toggle-featured');
    });

    // Import existing products to Official Shop
    Route::post('/import', [OfficialShopAdminController::class, 'importToOfficial'])->name('import');

    // ═══════════════════════════════════════════════════════════════════════
    // AI Selection - คัดเลือกสินค้าจากร้านค้าต่างๆ เข้า Official Shop
    // ═══════════════════════════════════════════════════════════════════════
    Route::prefix('selection')->name('selection.')->group(function () {
        // Dashboard
        Route::get('/', [OfficialShopSelectionController::class, 'index'])->name('index');

        // AI Selection
        Route::post('/run', [OfficialShopSelectionController::class, 'runSelection'])->name('run');

        // Warnings
        Route::get('/warnings', [OfficialShopSelectionController::class, 'warnings'])->name('warnings');
        Route::post('/warnings/process', [OfficialShopSelectionController::class, 'processWarnings'])->name('warnings.process');

        // Remove product
        Route::delete('/products/{entry}', [OfficialShopSelectionController::class, 'removeProduct'])->name('products.remove');

        // Add product manually
        Route::post('/products/{product}/add', [OfficialShopSelectionController::class, 'addProductManually'])->name('products.add');

        // Preview score
        Route::get('/products/{product}/preview-score', [OfficialShopSelectionController::class, 'previewScore'])->name('products.preview-score');

        // Settings
        Route::get('/settings', [OfficialShopSelectionController::class, 'settings'])->name('settings');
        Route::put('/settings', [OfficialShopSelectionController::class, 'updateSettings'])->name('settings.update');

        // New Product Promotions
        Route::get('/new-promotions', [OfficialShopSelectionController::class, 'newProductPromotions'])->name('new-promotions');
        Route::delete('/new-promotions/{promotion}', [OfficialShopSelectionController::class, 'cancelPromotion'])->name('new-promotions.cancel');

        // Best Sellers
        Route::get('/best-sellers', [OfficialShopSelectionController::class, 'bestSellers'])->name('best-sellers');
        Route::post('/best-sellers/calculate', [OfficialShopSelectionController::class, 'calculateBestSellers'])->name('best-sellers.calculate');
    });
});

// E-Commerce Management
Route::prefix('ecommerce')->name('ecommerce.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [ECommerceController::class, 'dashboard'])->name('dashboard');

    // Products Management
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ECommerceController::class, 'products'])->name('index');
        Route::get('/blocked', [ECommerceController::class, 'blockedProducts'])->name('blocked');
        Route::post('/', [ECommerceController::class, 'storeProduct'])->name('store');
        Route::get('/{product}', [ECommerceController::class, 'showProduct'])->name('show');
        Route::get('/{product}/edit', [ECommerceController::class, 'editProduct'])->name('edit');
        Route::put('/{product}', [ECommerceController::class, 'updateProduct'])->name('update');
        Route::delete('/{product}', [ECommerceController::class, 'deleteProduct'])->name('delete');
        // Block/Unblock สินค้า
        Route::post('/{product}/block', [ECommerceController::class, 'blockProduct'])->name('block');
        Route::post('/{product}/unblock', [ECommerceController::class, 'unblockProduct'])->name('unblock');
    });

    // นำเข้าสินค้าจาก Lazada (แอดมินวางลิงก์เลือกเอง → preview → นำเข้า)
    Route::prefix('lazada-import')->name('lazada-import.')->group(function () {
        Route::get('/', [LazadaImportController::class, 'form'])->name('form');
        Route::post('/preview', [LazadaImportController::class, 'preview'])->name('preview');
        Route::post('/import', [LazadaImportController::class, 'import'])->name('import');
    });

    // Orders Management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [ECommerceController::class, 'orders'])->name('index');
        Route::get('/unread-messages', [ECommerceController::class, 'ordersWithUnreadMessages'])->name('unread-messages');
        Route::get('/{order}', [ECommerceController::class, 'showOrder'])->name('show');
        Route::post('/{order}/status', [ECommerceController::class, 'updateOrderStatus'])->name('status.update');
        Route::post('/{order}/payment-status', [ECommerceController::class, 'updatePaymentStatus'])->name('payment-status.update');
        // Order Tracking
        Route::get('/{order}/tracking', [ECommerceController::class, 'orderTracking'])->name('tracking');
        Route::post('/{order}/tracking', [ECommerceController::class, 'updateOrderTracking'])->name('tracking.update');
        Route::post('/{order}/tracking/history', [ECommerceController::class, 'addTrackingHistory'])->name('tracking.history');
        // Order Messages
        Route::get('/{order}/messages', [ECommerceController::class, 'getOrderMessages'])->name('messages');
        Route::post('/{order}/messages', [ECommerceController::class, 'sendOrderMessage'])->name('messages.send');
        Route::post('/{order}/messages/read', [ECommerceController::class, 'markOrderMessagesRead'])->name('messages.read');
    });

    // Categories Management
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [ECommerceController::class, 'categories'])->name('index');
        Route::post('/', [ECommerceController::class, 'storeCategory'])->name('store');
        Route::put('/{category}', [ECommerceController::class, 'updateCategory'])->name('update');
        Route::delete('/{category}', [ECommerceController::class, 'deleteCategory'])->name('delete');
    });

    // Reviews Management
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [ECommerceController::class, 'reviews'])->name('index');
        Route::post('/{review}/status', [ECommerceController::class, 'updateReviewStatus'])->name('status.update');
        Route::delete('/{review}', [ECommerceController::class, 'deleteReview'])->name('delete');
    });

    // Reports
    Route::get('/reports', [ECommerceController::class, 'reports'])->name('reports');
});

// Storefront Management (Banner, Settings)
Route::prefix('storefront')->name('storefront.')->group(function () {
    // Storefront Settings Index
    Route::get('/', [StorefrontSettingsController::class, 'index'])->name('index');

    // Theme Settings
    Route::put('/theme', [StorefrontSettingsController::class, 'updateTheme'])->name('update-theme');

    // Layout Settings
    Route::put('/layout', [StorefrontSettingsController::class, 'updateLayout'])->name('update-layout');

    // Banners Management
    Route::prefix('banners')->name('banners.')->group(function () {
        Route::get('/', [StorefrontSettingsController::class, 'banners'])->name('index');
        Route::get('/create', [StorefrontSettingsController::class, 'createBanner'])->name('create');
        Route::post('/', [StorefrontSettingsController::class, 'storeBanner'])->name('store');
        Route::get('/{banner}/edit', [StorefrontSettingsController::class, 'editBanner'])->name('edit');
        Route::put('/{banner}', [StorefrontSettingsController::class, 'updateBanner'])->name('update');
        Route::delete('/{banner}', [StorefrontSettingsController::class, 'destroyBanner'])->name('destroy');
        Route::post('/reorder', [StorefrontSettingsController::class, 'reorderBanners'])->name('reorder');
        Route::post('/{banner}/toggle', [StorefrontSettingsController::class, 'toggleBannerStatus'])->name('toggle');
    });

    // Vendor Stores Management (Admin จัดการร้านค้าทั้งหมด)
    Route::prefix('vendor-stores')->name('vendor-stores.')->group(function () {
        Route::get('/', [VendorStoreController::class, 'index'])->name('index');
        Route::get('/{store}', [VendorStoreController::class, 'show'])->name('show');
        Route::get('/{store}/edit', [VendorStoreController::class, 'edit'])->name('edit');
        Route::put('/{store}', [VendorStoreController::class, 'update'])->name('update');
        Route::post('/{store}/toggle-status', [VendorStoreController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{store}/toggle-featured', [VendorStoreController::class, 'toggleFeatured'])->name('toggle-featured');
        Route::delete('/{store}', [VendorStoreController::class, 'destroy'])->name('destroy');
    });
});

// Featured Stores Management (Homepage)
Route::prefix('featured-stores')->name('featured-stores.')->group(function () {
    Route::get('/', [FeaturedStoreController::class, 'index'])->name('index');
    Route::post('/{store}/add', [FeaturedStoreController::class, 'addToFeatured'])->name('add');
    Route::delete('/{store}/remove', [FeaturedStoreController::class, 'removeFromFeatured'])->name('remove');
    Route::put('/update-order', [FeaturedStoreController::class, 'updateOrder'])->name('update-order');
});

// MLM System Management
Route::prefix('mlm')->name('mlm.')->group(function () {
    // MLM Plans - ระบบใช้แผนคอมมิชชัน Global บังคับทั้งระบบ
    // เหลือเพียง index เพื่อแสดงข้อความว่าใช้แผน Global
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [MlmPlanController::class, 'index'])->name('index');
        // Note: ปิดการใช้งาน create, store, edit, update, destroy เพราะใช้แผน Global
    });

    // MLM Members
    Route::prefix('members')->name('members.')->group(function () {
        Route::get('/', [MlmMemberController::class, 'index'])->name('index');
        Route::get('/create', [MlmMemberController::class, 'create'])->name('create');
        Route::post('/', [MlmMemberController::class, 'store'])->name('store');
        Route::get('/{member}', [MlmMemberController::class, 'show'])->name('show');
        Route::post('/{member}/status', [MlmMemberController::class, 'updateStatus'])->name('status');
        Route::post('/{member}/toggle-qualification', [MlmMemberController::class, 'toggleQualification'])->name('toggle-qualification');
        Route::get('/{member}/genealogy', [MlmMemberController::class, 'genealogy'])->name('genealogy');
        Route::get('/{member}/tree-data', [MlmMemberController::class, 'getTreeData'])->name('tree-data');
        Route::get('/{member}/bloodline-data', [MlmMemberController::class, 'getBloodlineData'])->name('bloodline-data');
        Route::get('/{member}/statistics', [MlmMemberController::class, 'statistics'])->name('statistics');
    });

    // MLM Commissions
    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::get('/', [MlmCommissionController::class, 'index'])->name('index');
        Route::get('/{commission}', [MlmCommissionController::class, 'show'])->name('show');
        Route::post('/approve', [MlmCommissionController::class, 'approve'])->name('approve');
        Route::post('/approve-all', [MlmCommissionController::class, 'approveAll'])->name('approve-all');
        Route::post('/{commission}/reject', [MlmCommissionController::class, 'reject'])->name('reject');
        Route::post('/pay', [MlmCommissionController::class, 'pay'])->name('pay');
        Route::post('/pay-all', [MlmCommissionController::class, 'payAll'])->name('pay-all');
        Route::post('/bulk-action', [MlmCommissionController::class, 'bulkAction'])->name('bulk-action');
    });

    // Product PV Management
    Route::prefix('product-pv')->name('product-pv.')->group(function () {
        Route::get('/', [MlmProductPvController::class, 'index'])->name('index');
        Route::get('/create', [MlmProductPvController::class, 'create'])->name('create');
        Route::post('/', [MlmProductPvController::class, 'store'])->name('store');
        Route::get('/{productPv}/edit', [MlmProductPvController::class, 'edit'])->name('edit');
        Route::put('/{productPv}', [MlmProductPvController::class, 'update'])->name('update');
        Route::delete('/{productPv}', [MlmProductPvController::class, 'destroy'])->name('destroy');
        Route::get('/products/{product}/preview', [MlmProductPvController::class, 'preview'])->name('preview');
        Route::post('/bulk-create', [MlmProductPvController::class, 'bulkCreate'])->name('bulk-create');
        Route::post('/bulk-update', [MlmProductPvController::class, 'bulkUpdate'])->name('bulk-update');
    });

    // MLM Reports & Analytics
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [MlmReportController::class, 'index'])->name('index');
        Route::get('/dashboard', [MlmReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/member-growth', [MlmReportController::class, 'memberGrowth'])->name('member-growth');
        Route::get('/commission-trends', [MlmReportController::class, 'commissionTrends'])->name('commission-trends');
        Route::get('/pv-analytics', [MlmReportController::class, 'pvAnalytics'])->name('pv-analytics');
        Route::get('/top-performers', [MlmReportController::class, 'topPerformers'])->name('top-performers');
        Route::get('/commission-by-type', [MlmReportController::class, 'commissionByType'])->name('commission-by-type');
        Route::get('/level-analysis', [MlmReportController::class, 'levelAnalysis'])->name('level-analysis');
        Route::get('/binary-analysis', [MlmReportController::class, 'binaryAnalysis'])->name('binary-analysis');
        Route::get('/export-members', [MlmReportController::class, 'exportMembers'])->name('export-members');
        Route::get('/export-commissions', [MlmReportController::class, 'exportCommissions'])->name('export-commissions');
    });

    // MLM Global Settings (Premium Edition)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [MlmGlobalSettingController::class, 'index'])->name('index');
        Route::put('/', [MlmGlobalSettingController::class, 'update'])->name('update');
        Route::post('/preview-calculation', [MlmGlobalSettingController::class, 'previewCalculation'])->name('preview-calculation');
        Route::get('/get', [MlmGlobalSettingController::class, 'getSettings'])->name('get');
        Route::post('/update-placement', [MlmGlobalSettingController::class, 'updatePlacement'])->name('update-placement');

        // Unilevel Width Change & Tree Rebuild
        Route::post('/update-width', [MlmGlobalSettingController::class, 'updateUnilevelWidth'])->name('update-width');
        Route::get('/rebuild-status', [MlmGlobalSettingController::class, 'getRebuildStatus'])->name('rebuild-status');
        Route::post('/cancel-rebuild', [MlmGlobalSettingController::class, 'cancelRebuild'])->name('cancel-rebuild');
        Route::post('/start-rebuild', [MlmGlobalSettingController::class, 'startManualRebuild'])->name('start-rebuild');
        Route::post('/preview-width-change', [MlmGlobalSettingController::class, 'previewWidthChange'])->name('preview-width-change');
    });

    // MLM Genealogy Viewer
    Route::get('/genealogy', [MlmPlanController::class, 'genealogy'])->name('genealogy.index');
    Route::get('/genealogy/workflow', [MlmPlanController::class, 'genealogyWorkflow'])->name('genealogy.workflow');

    // MLM Bloodline Viewer (ผังสายเลือด - แสดงเส้นทางจาก root ถึงสมาชิก)
    Route::get('/genealogy/bloodline', [MlmPlanController::class, 'bloodline'])->name('genealogy.bloodline');
    Route::get('/genealogy/bloodline/workflow', [MlmPlanController::class, 'bloodlineWorkflow'])->name('genealogy.bloodline.workflow');

    // MLM Placement Examples
    Route::get('/placement-examples', function () {
        return view('admin.mlm.placement-examples');
    })->name('placement-examples');

    // MLM Smart Calculator
    Route::get('/calculator', function () {
        return view('admin.mlm.calculator');
    })->name('calculator');
});

// Academy System
Route::prefix('academy')->name('academy.')->group(function () {
    // Academy Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [AcademySettingsController::class, 'index'])->name('index');
        Route::post('/basic', [AcademySettingsController::class, 'updateBasic'])->name('update-basic');
        Route::post('/certificate', [AcademySettingsController::class, 'updateCertificate'])->name('update-certificate');
        Route::post('/email', [AcademySettingsController::class, 'updateEmail'])->name('update-email');
        Route::post('/course', [AcademySettingsController::class, 'updateCourse'])->name('update-course');
        Route::post('/instructor', [AcademySettingsController::class, 'updateInstructor'])->name('update-instructor');
        Route::post('/toggle-active', [AcademySettingsController::class, 'toggleActive'])->name('toggle-active');

        // File Uploads
        Route::post('/upload-logo', [AcademySettingsController::class, 'uploadLogo'])->name('upload-logo');
        Route::post('/upload-certificate-background', [AcademySettingsController::class, 'uploadCertificateBackground'])->name('upload-certificate-background');
        Route::post('/upload-certificate-template', [AcademySettingsController::class, 'uploadCertificateTemplate'])->name('upload-certificate-template');

        // Signatures
        Route::post('/add-signature', [AcademySettingsController::class, 'addSignature'])->name('add-signature');
        Route::delete('/remove-signature/{index}', [AcademySettingsController::class, 'removeSignature'])->name('remove-signature');
    });

    // Certificate Management (Admin)
    Route::prefix('certificates')->name('certificates.')->group(function () {
        Route::get('/', [CertificateManagementController::class, 'index'])->name('index');
        Route::get('/create', [CertificateManagementController::class, 'create'])->name('create');
        Route::post('/', [CertificateManagementController::class, 'store'])->name('store');
        Route::get('/{id}', [CertificateManagementController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [CertificateManagementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CertificateManagementController::class, 'update'])->name('update');
        Route::post('/{id}/revoke', [CertificateManagementController::class, 'revoke'])->name('revoke');
        Route::post('/{id}/restore', [CertificateManagementController::class, 'restore'])->name('restore');
        Route::delete('/{id}', [CertificateManagementController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-generate', [CertificateManagementController::class, 'bulkGenerate'])->name('bulk-generate');
        Route::get('/export/csv', [CertificateManagementController::class, 'export'])->name('export');
    });

    // Courses alias (redirects to certificates)
    Route::prefix('courses')->name('courses.')->group(function () {
        Route::get('/', [CertificateManagementController::class, 'index'])->name('index');
    });
});

// HRM (Human Resource Management) System
Route::prefix('hrm')->name('hrm.')->group(function () {
    // HRM Dashboard
    Route::get('/dashboard', [HrmDashboardController::class, 'index'])->name('dashboard');

    // Employee Management
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
        Route::get('/export/csv', [EmployeeController::class, 'export'])->name('export');
    });

    // Department Management
    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/', [DepartmentController::class, 'index'])->name('index');
        Route::get('/create', [DepartmentController::class, 'create'])->name('create');
        Route::post('/', [DepartmentController::class, 'store'])->name('store');
        Route::get('/{department}', [DepartmentController::class, 'show'])->name('show');
        Route::get('/{department}/edit', [DepartmentController::class, 'edit'])->name('edit');
        Route::put('/{department}', [DepartmentController::class, 'update'])->name('update');
        Route::delete('/{department}', [DepartmentController::class, 'destroy'])->name('destroy');
    });

    // Position Management (simplified routes)
    Route::resource('positions', PositionController::class);

    // Attendance Management
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/employee/{employee}', [AttendanceController::class, 'employeeReport'])->name('employee-report');
        Route::post('/mark-absent', [AttendanceController::class, 'markAbsent'])->name('mark-absent');
        Route::post('/bulk-import', [AttendanceController::class, 'bulkImport'])->name('bulk-import');
    });

    // Leave Management
    Route::prefix('leave')->name('leave.')->group(function () {
        Route::get('/', [LeaveController::class, 'index'])->name('index');
        Route::get('/{leaveRequest}', [LeaveController::class, 'show'])->name('show');
        Route::post('/{leaveRequest}/approve', [LeaveController::class, 'approve'])->name('approve');
        Route::post('/{leaveRequest}/reject', [LeaveController::class, 'reject'])->name('reject');
        Route::get('/calendar/view', [LeaveController::class, 'calendar'])->name('calendar');

        // Leave Types Management
        Route::get('/types/manage', [LeaveController::class, 'leaveTypes'])->name('types');
        Route::get('/types/create', [LeaveController::class, 'createLeaveType'])->name('types.create');
        Route::post('/types', [LeaveController::class, 'storeLeaveType'])->name('types.store');
        Route::get('/types/{leaveType}/edit', [LeaveController::class, 'editLeaveType'])->name('types.edit');
        Route::put('/types/{leaveType}', [LeaveController::class, 'updateLeaveType'])->name('types.update');
    });

    // Payroll Management
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/', [PayrollController::class, 'index'])->name('index');
        Route::get('/{payroll}', [PayrollController::class, 'show'])->name('show');
        Route::post('/generate', [PayrollController::class, 'generate'])->name('generate');
        Route::post('/{payroll}/approve', [PayrollController::class, 'approve'])->name('approve');
        Route::post('/{payroll}/mark-paid', [PayrollController::class, 'markAsPaid'])->name('mark-paid');
        Route::get('/{payroll}/payslip', [PayrollController::class, 'downloadPayslip'])->name('payslip');
        Route::post('/bulk-approve', [PayrollController::class, 'bulkApprove'])->name('bulk-approve');
        Route::get('/export/csv', [PayrollController::class, 'export'])->name('export');
    });

    // Performance Management
    Route::prefix('performance')->name('performance.')->group(function () {
        // Performance Reviews
        Route::resource('reviews', PerformanceReviewController::class);

        // Performance Goals
        Route::resource('goals', PerformanceGoalController::class);

        // Performance Templates
        Route::resource('templates', PerformanceTemplateController::class);
    });

    // Recruitment Management
    Route::prefix('recruitment')->name('recruitment.')->group(function () {
        // Job Postings
        Route::resource('jobs', JobPostingController::class);

        // Job Applications
        Route::resource('applications', JobApplicationController::class);
    });

    // Training Management
    Route::prefix('training')->name('training.')->group(function () {
        // Training Courses
        Route::resource('courses', TrainingCourseController::class);

        // Training Enrollments (also accessible as schedules)
        Route::resource('enrollments', TrainingEnrollmentController::class);

        // Alias for schedules (maps to enrollments)
        Route::get('/schedules', [TrainingEnrollmentController::class, 'index'])->name('schedules');
    });
});

/*
|--------------------------------------------------------------------------
| Accounting System Routes
|--------------------------------------------------------------------------
*/
Route::prefix('accounting')->name('accounting.')->group(function () {
    // Dashboard
    Route::get('/', [AccountingDashboardController::class, 'index'])->name('dashboard');
    Route::get('/setup', [AccountingDashboardController::class, 'setup'])->name('setup');
    Route::post('/setup', [AccountingDashboardController::class, 'saveSetup'])->name('setup.save');

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/items', [InvoiceController::class, 'addItem'])->name('invoices.items.add');
    Route::delete('invoices/{invoice}/items/{item}', [InvoiceController::class, 'removeItem'])->name('invoices.items.remove');
    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'sendEmail'])->name('invoices.send');

    // Expenses
    Route::resource('expenses', ExpenseController::class);
    Route::post('expenses/{expense}/items', [ExpenseController::class, 'addItem'])->name('expenses.items.add');
    Route::delete('expenses/{expense}/items/{item}', [ExpenseController::class, 'removeItem'])->name('expenses.items.remove');
    Route::post('expenses/{expense}/payments', [ExpenseController::class, 'recordPayment'])->name('expenses.payments');

    // Contacts (Customers & Vendors)
    Route::resource('contacts', ContactController::class);
    Route::get('contacts/{contact}/statement', [ContactController::class, 'statement'])->name('contacts.statement');

    // Products & Services
    Route::resource('products', ProductController::class);
    Route::post('products/bulk-import', [ProductController::class, 'bulkImport'])->name('products.bulk-import');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/cash-flow', [ReportController::class, 'cashFlow'])->name('cash-flow');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/expenses', [ReportController::class, 'expenses'])->name('expenses');
        Route::get('/tax', [ReportController::class, 'tax'])->name('tax');
    });

    // FlowAccount Integration
    Route::prefix('flowaccount')->name('flowaccount.')->group(function () {
        // Dashboard และหน้าหลัก
        Route::get('/', [FlowAccountController::class, 'index'])->name('index');

        // การเชื่อมต่อ OAuth
        Route::get('/connect', [FlowAccountController::class, 'showConnectForm'])->name('connect.form');
        Route::post('/connect', [FlowAccountController::class, 'connect'])->name('connect');
        Route::get('/callback', [FlowAccountController::class, 'callback'])->name('callback');
        Route::post('/disconnect', [FlowAccountController::class, 'disconnect'])->name('disconnect');
        Route::get('/test', [FlowAccountController::class, 'testConnection'])->name('test');

        // การตั้งค่า
        Route::get('/settings', [FlowAccountController::class, 'settings'])->name('settings');
        Route::post('/settings', [FlowAccountController::class, 'saveSettings'])->name('settings.save');

        // การซิงค์ข้อมูล
        Route::post('/sync', [FlowAccountController::class, 'sync'])->name('sync');
        Route::post('/sync/{type}', [FlowAccountController::class, 'syncType'])->name('sync.type');

        // ประวัติการซิงค์
        Route::get('/logs', [FlowAccountController::class, 'syncLogs'])->name('logs');
        Route::post('/logs/clear', [FlowAccountController::class, 'clearLogs'])->name('logs.clear');

        // สถิติ
        Route::get('/stats', [FlowAccountController::class, 'getStats'])->name('stats');

        // ดึงข้อมูลจาก FlowAccount API
        Route::get('/fetch/{endpoint}', [FlowAccountController::class, 'fetchFromFlowAccount'])->name('fetch');
    });
});

// POS Management
Route::prefix('pos')->name('pos.')->group(function () {
    // Dashboard
    Route::get('/', [PosDashboardController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [PosDashboardController::class, 'analytics'])->name('analytics');
    Route::get('/sales-chart', [PosDashboardController::class, 'salesChart'])->name('sales-chart');

    // Devices Management
    Route::resource('devices', PosDeviceController::class);
    Route::post('devices/{device}/regenerate-license', [PosDeviceController::class, 'regenerateLicenseKey'])->name('devices.regenerate-license');
    Route::post('devices/{device}/toggle-status', [PosDeviceController::class, 'toggleStatus'])->name('devices.toggle-status');
    Route::post('devices/{device}/extend-subscription', [PosDeviceController::class, 'extendSubscription'])->name('devices.extend-subscription');
    Route::post('devices/{device}/suspend', [PosDeviceController::class, 'suspend'])->name('devices.suspend');
    Route::post('devices/{device}/reactivate', [PosDeviceController::class, 'reactivate'])->name('devices.reactivate');
    Route::post('devices/{device}/force-offline', [PosDeviceController::class, 'forceOffline'])->name('devices.force-offline');
    Route::get('devices-export', [PosDeviceController::class, 'export'])->name('devices.export');

    // Transactions Management
    Route::get('transactions', [PosTransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/{transaction}', [PosTransactionController::class, 'show'])->name('transactions.show');
    Route::post('transactions/{transaction}/refund', [PosTransactionController::class, 'refund'])->name('transactions.refund');
    Route::post('transactions/{transaction}/void', [PosTransactionController::class, 'void'])->name('transactions.void');
    Route::get('transactions/{transaction}/receipt', [PosTransactionController::class, 'receipt'])->name('transactions.receipt');
    Route::get('transactions/{transaction}/print', [PosTransactionController::class, 'printReceipt'])->name('transactions.print');
    Route::get('transactions-export', [PosTransactionController::class, 'export'])->name('transactions.export');
    Route::get('transactions-analytics', [PosTransactionController::class, 'analytics'])->name('transactions.analytics');

    // Advertisements Management
    Route::resource('advertisements', PosAdvertisementController::class);
    Route::post('advertisements/{advertisement}/toggle-status', [PosAdvertisementController::class, 'toggleStatus'])->name('advertisements.toggle-status');
    Route::post('advertisements/reorder', [PosAdvertisementController::class, 'reorder'])->name('advertisements.reorder');
    Route::get('advertisements/{advertisement}/preview', [PosAdvertisementController::class, 'preview'])->name('advertisements.preview');
    Route::post('advertisements/{advertisement}/duplicate', [PosAdvertisementController::class, 'duplicate'])->name('advertisements.duplicate');
    Route::get('advertisements-analytics', [PosAdvertisementController::class, 'analytics'])->name('advertisements.analytics');

    // Label Printing Management
    Route::prefix('labels')->name('labels.')->group(function () {
        // Dashboard
        Route::get('/', [PosLabelController::class, 'index'])->name('index');

        // Product Label Printing
        Route::get('/print-product', [PosLabelController::class, 'printProductLabels'])->name('print-product');

        // Shipping Label Printing
        Route::get('/print-shipping', [PosLabelController::class, 'printShippingLabel'])->name('print-shipping');
        Route::get('/print-shipping/{transaction}', [PosLabelController::class, 'printShippingLabel'])->name('print-shipping.transaction');

        // Preview Page (แสดงฉลากสำหรับพิมพ์)
        Route::get('/preview', [PosLabelController::class, 'showPreview'])->name('preview');

        // Template Management (จัดการ Templates)
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [PosLabelController::class, 'listTemplates'])->name('index');
            Route::get('/create', [PosLabelController::class, 'createTemplate'])->name('create');
            Route::post('/', [PosLabelController::class, 'storeTemplate'])->name('store');
            Route::get('/{template}/edit', [PosLabelController::class, 'editTemplate'])->name('edit');
            Route::get('/{template}/designer', [PosLabelController::class, 'designerTemplate'])->name('designer');
            Route::put('/{template}', [PosLabelController::class, 'updateTemplate'])->name('update');
            Route::delete('/{template}', [PosLabelController::class, 'destroyTemplate'])->name('destroy');
            Route::post('/{template}/duplicate', [PosLabelController::class, 'duplicateTemplate'])->name('duplicate');
        });

        // API endpoints
        Route::get('/api/products', [PosLabelController::class, 'getProducts'])->name('api.products');
        Route::get('/api/search-products', [PosLabelController::class, 'searchProducts'])->name('api.search-products');
        Route::post('/api/preview', [PosLabelController::class, 'preview'])->name('api.preview');
        Route::post('/api/print', [PosLabelController::class, 'print'])->name('api.print');
        Route::post('/api/print-shipping/{transaction}', [PosLabelController::class, 'printShippingFromTransaction'])->name('api.print-shipping');
        Route::get('/api/templates', [PosLabelController::class, 'getTemplates'])->name('api.templates');
        Route::get('/api/paper-sizes', [PosLabelController::class, 'getPaperSizes'])->name('api.paper-sizes');
        Route::post('/api/batch-session', [PosLabelController::class, 'createBatchSession'])->name('api.batch-session');

        // Print History
        Route::get('/history', [PosLabelController::class, 'history'])->name('history');
        Route::get('/history/{print}', [PosLabelController::class, 'show'])->name('show');
        Route::delete('/history/{print}', [PosLabelController::class, 'destroy'])->name('destroy');
    });
});

// Theme Management
Route::prefix('themes')->name('themes.')->group(function () {
    Route::get('/', [ThemeController::class, 'index'])->name('index');
    Route::get('/builder/{id?}', [ThemeController::class, 'builder'])->name('builder');
    Route::post('/', [ThemeController::class, 'store'])->name('store');
    Route::put('/{id}', [ThemeController::class, 'update'])->name('update');
    Route::delete('/{id}', [ThemeController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/duplicate', [ThemeController::class, 'duplicate'])->name('duplicate');
    Route::post('/preset/{presetId}', [ThemeController::class, 'createFromPreset'])->name('create-from-preset');
    Route::post('/{id}/preview', [ThemeController::class, 'uploadPreview'])->name('upload-preview');
    Route::get('/{id}/statistics', [ThemeController::class, 'statistics'])->name('statistics');
    Route::get('/{id}/export', [ThemeController::class, 'export'])->name('export');
    Route::post('/import', [ThemeController::class, 'import'])->name('import');
    Route::post('/initialize', [ThemeController::class, 'initialize'])->name('initialize');
    Route::post('/{id}/set-default', [ThemeController::class, 'setDefault'])->name('set-default');
    Route::post('/{id}/toggle-active', [ThemeController::class, 'toggleActive'])->name('toggle-active');
});

// Icon Management
Route::prefix('icons')->name('icons.')->group(function () {
    Route::get('/', [IconController::class, 'index'])->name('index');
    Route::post('/upload', [IconController::class, 'upload'])->name('upload');
    Route::delete('/', [IconController::class, 'destroy'])->name('destroy');
    Route::get('/list', [IconController::class, 'list'])->name('list');
});

// Hotel Management
Route::prefix('hotels')->name('hotels.')->group(function () {
    // Hotel Management - Basic Routes
    Route::get('/', [HotelManagementController::class, 'index'])->name('index');
    Route::get('/create', [HotelManagementController::class, 'create'])->name('create');
    Route::post('/', [HotelManagementController::class, 'store'])->name('store');

    // Facilities Management
    Route::prefix('facilities')->name('facilities.')->group(function () {
        Route::get('/', [HotelFacilityController::class, 'index'])->name('index');
        Route::post('/', [HotelFacilityController::class, 'store'])->name('store');
        Route::put('/{id}', [HotelFacilityController::class, 'update'])->name('update');
        Route::delete('/{id}', [HotelFacilityController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [HotelFacilityController::class, 'reorder'])->name('reorder');
    });

    // Booking Management
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [HotelBookingManagementController::class, 'index'])->name('index');
        Route::get('/create', [HotelBookingManagementController::class, 'create'])->name('create');
        Route::post('/', [HotelBookingManagementController::class, 'store'])->name('store');
        Route::get('/calendar', [HotelBookingManagementController::class, 'calendar'])->name('calendar');
        Route::get('/analytics', [HotelBookingManagementController::class, 'analytics'])->name('analytics');
        Route::get('/export', [HotelBookingManagementController::class, 'export'])->name('export');
        Route::get('/{id}', [HotelBookingManagementController::class, 'show'])->name('show');
        Route::post('/{id}/update-status', [HotelBookingManagementController::class, 'updateStatus'])->name('update-status');
        Route::post('/{id}/cancel', [HotelBookingManagementController::class, 'cancel'])->name('cancel');
    });

    // Review Management
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [HotelReviewManagementController::class, 'index'])->name('index');
        Route::get('/{id}', [HotelReviewManagementController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [HotelReviewManagementController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [HotelReviewManagementController::class, 'reject'])->name('reject');
        Route::post('/{id}/toggle-featured', [HotelReviewManagementController::class, 'toggleFeatured'])->name('toggle-featured');
        Route::post('/{id}/respond', [HotelReviewManagementController::class, 'respond'])->name('respond');
        Route::delete('/{id}', [HotelReviewManagementController::class, 'destroy'])->name('destroy');
    });

    // Special Offers Management
    Route::prefix('special-offers')->name('special-offers.')->group(function () {
        Route::get('/', [HotelSpecialOfferController::class, 'index'])->name('index');
        Route::get('/create', [HotelSpecialOfferController::class, 'create'])->name('create');
        Route::post('/', [HotelSpecialOfferController::class, 'store'])->name('store');
        Route::get('/{id}', [HotelSpecialOfferController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [HotelSpecialOfferController::class, 'edit'])->name('edit');
        Route::put('/{id}', [HotelSpecialOfferController::class, 'update'])->name('update');
        Route::delete('/{id}', [HotelSpecialOfferController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [HotelSpecialOfferController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{id}/toggle-featured', [HotelSpecialOfferController::class, 'toggleFeatured'])->name('toggle-featured');
    });

    // Hotel Management - Individual Hotel Routes (must come after specific prefixes)
    Route::get('/{id}', [HotelManagementController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [HotelManagementController::class, 'edit'])->name('edit');
    Route::put('/{id}', [HotelManagementController::class, 'update'])->name('update');
    Route::delete('/{id}', [HotelManagementController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [HotelManagementController::class, 'toggleStatus'])->name('toggle-status');
    Route::post('/{id}/toggle-featured', [HotelManagementController::class, 'toggleFeatured'])->name('toggle-featured');

    // Room Type Management
    Route::prefix('{hotelId}/rooms')->name('rooms.')->group(function () {
        Route::get('/', [RoomTypeManagementController::class, 'index'])->name('index');
        Route::get('/create', [RoomTypeManagementController::class, 'create'])->name('create');
        Route::post('/', [RoomTypeManagementController::class, 'store'])->name('store');
        Route::get('/{roomTypeId}', [RoomTypeManagementController::class, 'show'])->name('show');
        Route::get('/{roomTypeId}/edit', [RoomTypeManagementController::class, 'edit'])->name('edit');
        Route::put('/{roomTypeId}', [RoomTypeManagementController::class, 'update'])->name('update');
        Route::delete('/{roomTypeId}', [RoomTypeManagementController::class, 'destroy'])->name('destroy');
        Route::get('/{roomTypeId}/availability', [RoomTypeManagementController::class, 'availability'])->name('availability');
        Route::post('/{roomTypeId}/availability', [RoomTypeManagementController::class, 'updateAvailability'])->name('availability.update');
    });
});

// Floating Tools Management
Route::prefix('floating-tools')->name('floating-tools.')->group(function () {
    Route::get('/', [FloatingToolsController::class, 'index'])->name('index');
    Route::post('/', [FloatingToolsController::class, 'update'])->name('update');
});

// Developer Release Manager (IP-locked, Developer Only)
Route::prefix('dev/releases')->middleware(DevMode::class)->name('dev.releases.')->group(function () {
    Route::get('/', [DevReleaseController::class, 'index'])->name('index');
    Route::post('/create', [DevReleaseController::class, 'create'])->name('create');
    Route::post('/{tag}/publish', [DevReleaseController::class, 'publish'])->name('publish');
    Route::delete('/{tag}/delete', [DevReleaseController::class, 'delete'])->name('delete');
    Route::get('/refresh', [DevReleaseController::class, 'refresh'])->name('refresh');
    Route::get('/realtime', [DevReleaseController::class, 'realtimeInfo'])->name('realtime');
});

// Ticket Support System
Route::prefix('tickets')->name('tickets.')->group(function () {
    // Dashboard & Analytics
    Route::get('/', [TicketController::class, 'index'])->name('index');
    Route::get('/analytics', [TicketController::class, 'analytics'])->name('analytics');
    Route::get('/ratings', [TicketController::class, 'ratings'])->name('ratings');

    // Settings (must be before /{ticket})
    Route::get('/settings', [TicketController::class, 'settings'])->name('settings');
    Route::put('/settings', [TicketController::class, 'updateSettings'])->name('settings.update');

    // Categories Management (must be before /{ticket})
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [TicketController::class, 'categories'])->name('index');
        Route::post('/', [TicketController::class, 'storeCategory'])->name('store');
        Route::put('/{id}', [TicketController::class, 'updateCategoryData'])->name('update');
        Route::delete('/{id}', [TicketController::class, 'destroyCategory'])->name('destroy');
    });

    // Canned Responses Management (must be before /{ticket})
    Route::prefix('canned-responses')->name('canned-responses.')->group(function () {
        Route::get('/', [TicketController::class, 'cannedResponses'])->name('index');
        Route::post('/', [TicketController::class, 'storeCannedResponse'])->name('store');
        Route::put('/{id}', [TicketController::class, 'updateCannedResponse'])->name('update');
        Route::delete('/{id}', [TicketController::class, 'destroyCannedResponse'])->name('destroy');
    });

    // SLA Policies Management (must be before /{ticket})
    Route::prefix('sla-policies')->name('sla-policies.')->group(function () {
        Route::get('/', [TicketController::class, 'slaPolicies'])->name('index');
        Route::post('/', [TicketController::class, 'storeSlaPolicy'])->name('store');
        Route::put('/{id}', [TicketController::class, 'updateSlaPolicy'])->name('update');
        Route::delete('/{id}', [TicketController::class, 'destroySlaPolicy'])->name('destroy');
    });

    // Assignment Rules Management (must be before /{ticket})
    Route::prefix('assignment-rules')->name('assignment-rules.')->group(function () {
        Route::get('/', [TicketController::class, 'assignmentRules'])->name('index');
        Route::post('/', [TicketController::class, 'storeAssignmentRule'])->name('store');
        Route::put('/{id}', [TicketController::class, 'updateAssignmentRule'])->name('update');
        Route::delete('/{id}', [TicketController::class, 'destroyAssignmentRule'])->name('destroy');
        Route::post('/{id}/toggle', [TicketController::class, 'toggleAssignmentRule'])->name('toggle');
    });

    // Knowledge Base Articles Management (must be before /{ticket})
    Route::prefix('kb-articles')->name('kb-articles.')->group(function () {
        Route::get('/', [TicketController::class, 'kbArticles'])->name('index');
        Route::get('/create', [TicketController::class, 'createKbArticle'])->name('create');
        Route::post('/', [TicketController::class, 'storeKbArticle'])->name('store');
        Route::get('/{id}/edit', [TicketController::class, 'editKbArticle'])->name('edit');
        Route::put('/{id}', [TicketController::class, 'updateKbArticle'])->name('update');
        Route::delete('/{id}', [TicketController::class, 'destroyKbArticle'])->name('destroy');
        Route::post('/{id}/toggle', [TicketController::class, 'toggleKbArticle'])->name('toggle');
    });

    // Ticket Operations (MUST BE LAST - dynamic routes catch everything)
    Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
    Route::post('/{ticket}/reply', [TicketController::class, 'reply'])->name('reply');
    Route::post('/{ticket}/assign', [TicketController::class, 'assign'])->name('assign');
    Route::put('/{ticket}/status', [TicketController::class, 'updateStatus'])->name('update-status');
    Route::put('/{ticket}/priority', [TicketController::class, 'updatePriority'])->name('update-priority');
    Route::put('/{ticket}/category', [TicketController::class, 'updateCategory'])->name('update-category');
    Route::post('/{ticket}/merge', [TicketController::class, 'merge'])->name('merge');
    Route::post('/{ticket}/link', [TicketController::class, 'link'])->name('link');
    Route::delete('/{ticket}', [TicketController::class, 'destroy'])->name('destroy');
});

// Tarot Reading Management
Route::prefix('tarot')->name('tarot.')->group(function () {
    // Dashboard
    Route::get('/', [TarotManagementController::class, 'index'])->name('index');
    Route::get('/analytics', [TarotManagementController::class, 'analytics'])->name('analytics');

    // Tarot Cards Management
    Route::prefix('cards')->name('cards.')->group(function () {
        Route::get('/', [TarotManagementController::class, 'cardsIndex'])->name('index');
        Route::get('/create', [TarotManagementController::class, 'cardsCreate'])->name('create');
        Route::post('/', [TarotManagementController::class, 'cardsStore'])->name('store');
        Route::get('/{id}/edit', [TarotManagementController::class, 'cardsEdit'])->name('edit');
        Route::put('/{id}', [TarotManagementController::class, 'cardsUpdate'])->name('update');
        Route::delete('/{id}', [TarotManagementController::class, 'cardsDestroy'])->name('destroy');
        // AJAX upload endpoint
        Route::post('/{id}/upload-image', [TarotManagementController::class, 'cardsUploadImage'])->name('upload-image');
    });

    // Categories Management
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [TarotManagementController::class, 'categoriesIndex'])->name('index');
        Route::get('/create', [TarotManagementController::class, 'categoriesCreate'])->name('create');
        Route::post('/', [TarotManagementController::class, 'categoriesStore'])->name('store');
        Route::get('/{id}/edit', [TarotManagementController::class, 'categoriesEdit'])->name('edit');
        Route::put('/{id}', [TarotManagementController::class, 'categoriesUpdate'])->name('update');
        Route::delete('/{id}', [TarotManagementController::class, 'categoriesDestroy'])->name('destroy');
    });

    // Card Back Images Management
    Route::prefix('card-backs')->name('card-backs.')->group(function () {
        Route::get('/', [TarotManagementController::class, 'cardBacksIndex'])->name('index');
        Route::post('/', [TarotManagementController::class, 'cardBacksStore'])->name('store');
        Route::post('/{id}/set-default', [TarotManagementController::class, 'cardBacksSetDefault'])->name('set-default');
        Route::delete('/{id}', [TarotManagementController::class, 'cardBacksDestroy'])->name('destroy');
    });

    // Spread Types Management
    Route::prefix('spread-types')->name('spread-types.')->group(function () {
        Route::get('/', [TarotManagementController::class, 'spreadTypesIndex'])->name('index');
    });

    // Interpretations Management - จัดการคำทำนายตามหมวดหมู่
    Route::prefix('interpretations')->name('interpretations.')->group(function () {
        Route::get('/', [TarotManagementController::class, 'interpretationsIndex'])->name('index');
        Route::get('/{id}/edit', [TarotManagementController::class, 'interpretationsEdit'])->name('edit');
        Route::put('/{id}', [TarotManagementController::class, 'interpretationsUpdate'])->name('update');
        Route::post('/{id}/copy-defaults', [TarotManagementController::class, 'interpretationsCopyDefaults'])->name('copy-defaults');
        Route::post('/copy-all-defaults', [TarotManagementController::class, 'interpretationsCopyAllDefaults'])->name('copy-all-defaults');
    });

    // Readings Management
    Route::prefix('readings')->name('readings.')->group(function () {
        Route::get('/', [TarotManagementController::class, 'readingsIndex'])->name('index');
        Route::get('/{id}', [TarotManagementController::class, 'readingsShow'])->name('show');
        Route::delete('/{id}', [TarotManagementController::class, 'readingsDestroy'])->name('destroy');
    });

    // Settings
    Route::get('/settings', [TarotManagementController::class, 'settings'])->name('settings');
    Route::put('/settings', [TarotManagementController::class, 'settingsUpdate'])->name('settings.update');
});

// Cryptocurrency Payment Gateway Management
Route::prefix('crypto')->name('crypto.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [CryptoManagementController::class, 'dashboard'])->name('dashboard');

    // Withdrawal Management - Direct route for backward compatibility
    Route::get('/withdrawals', [CryptoManagementController::class, 'withdrawals'])->name('withdrawals');

    // Withdrawal actions
    Route::post('/withdrawals/{id}/approve', [CryptoManagementController::class, 'approveWithdrawal'])->name('withdrawals.approve');
    Route::post('/withdrawals/{id}/reject', [CryptoManagementController::class, 'rejectWithdrawal'])->name('withdrawals.reject');

    // Transaction Monitor
    Route::get('/transactions', [CryptoManagementController::class, 'transactions'])->name('transactions');

    // Wallet Management
    Route::get('/wallets', [CryptoManagementController::class, 'wallets'])->name('wallets');

    // Currency Management - Direct route for backward compatibility
    Route::get('/currencies', [CryptoManagementController::class, 'currencies'])->name('currencies');
    Route::put('/currencies/{id}', [CryptoManagementController::class, 'updateCurrency'])->name('currencies.update');

    // Manual Operations
    Route::post('/scan-deposits', [CryptoManagementController::class, 'scanDeposits'])->name('scan-deposits');
    Route::post('/process-withdrawals', [CryptoManagementController::class, 'processWithdrawals'])->name('process-withdrawals');

    // System Settings - Direct route for backward compatibility
    Route::get('/settings', [CryptoManagementController::class, 'settings'])->name('settings');
    Route::post('/settings', [CryptoManagementController::class, 'updateSettings'])->name('settings.update');

    // HD Wallet Management (Hierarchical Deterministic Wallets)
    Route::prefix('hd-wallets')->name('hd-wallets.')->group(function () {
        // Overview
        Route::get('/', [HDWalletManagementController::class, 'index'])->name('index');
        Route::get('/export', [HDWalletManagementController::class, 'export'])->name('export');

        // Master Wallets
        Route::get('/master', [HDWalletManagementController::class, 'masterWallets'])->name('master');
        Route::get('/master/{masterWalletId}/children', [HDWalletManagementController::class, 'childWallets'])->name('master.children');

        // User Wallets
        Route::get('/user/{userId}', [HDWalletManagementController::class, 'userWallets'])->name('user');

        // Wallet Details
        Route::get('/{id}', [HDWalletManagementController::class, 'show'])->name('show');

        // Wallet Actions
        Route::post('/{id}/lock', [HDWalletManagementController::class, 'lockWallet'])->name('lock');
        Route::post('/{id}/unlock', [HDWalletManagementController::class, 'unlockWallet'])->name('unlock');
        Route::post('/{id}/suspend', [HDWalletManagementController::class, 'suspendWallet'])->name('suspend');
        Route::post('/{id}/reactivate', [HDWalletManagementController::class, 'reactivateWallet'])->name('reactivate');
    });
});

// TPIX Native Blockchain Management
Route::prefix('tpix')->name('tpix.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [TPIXController::class, 'dashboard'])->name('dashboard');

    // Network Status
    Route::get('/network-status', [TPIXController::class, 'networkStatus'])->name('network-status');

    // Wallets
    Route::get('/wallets', [TPIXController::class, 'wallets'])->name('wallets');

    // Transactions
    Route::get('/transactions', [TPIXController::class, 'transactions'])->name('transactions');
    Route::get('/transactions/{id}', [TPIXController::class, 'transactionDetails'])->name('transactions.details');

    // Settings
    Route::get('/settings', [TPIXController::class, 'settings'])->name('settings');
    Route::put('/settings', [TPIXController::class, 'updateSettings'])->name('update-settings');

    // API endpoint for checking blockchain connection
    Route::get('/check-connection', [TPIXController::class, 'checkConnection'])->name('check-connection');
});

// TPIX Token Management
Route::prefix('tokens')->name('tokens.')->group(function () {
    // Token List & Overview
    Route::get('/', [TokenManagementController::class, 'index'])->name('index');
    Route::get('/{id}', [TokenManagementController::class, 'show'])->name('show');

    // Token Approval & Verification
    Route::post('/{id}/approve', [TokenManagementController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [TokenManagementController::class, 'reject'])->name('reject');
    Route::post('/{id}/verify', [TokenManagementController::class, 'verify'])->name('verify');
    Route::post('/{id}/feature', [TokenManagementController::class, 'feature'])->name('feature');
    Route::post('/{id}/unfeature', [TokenManagementController::class, 'unfeature'])->name('unfeature');

    // Coin Control Operations
    Route::post('/{id}/mint', [TokenManagementController::class, 'mint'])->name('mint');
    Route::post('/{id}/burn', [TokenManagementController::class, 'burn'])->name('burn');
    Route::post('/{id}/freeze-address', [TokenManagementController::class, 'freezeAddress'])->name('freeze-address');
    Route::post('/{id}/unfreeze-address', [TokenManagementController::class, 'unfreezeAddress'])->name('unfreeze-address');
    Route::post('/{id}/pause', [TokenManagementController::class, 'pause'])->name('pause');
    Route::post('/{id}/unpause', [TokenManagementController::class, 'unpause'])->name('unpause');

    // CoinMarketCap Integration
    Route::post('/{id}/sync-cmc', [TokenManagementController::class, 'syncWithCMC'])->name('sync-cmc');
    Route::post('/{id}/link-cmc', [TokenManagementController::class, 'linkCMC'])->name('link-cmc');
    Route::get('/{id}/cmc-logs', [TokenManagementController::class, 'cmcLogs'])->name('cmc-logs');

    // Import from CoinMarketCap
    Route::get('/import-cmc', [TokenManagementController::class, 'showImportCMC'])->name('import-cmc');
    Route::post('/import-cmc', [TokenManagementController::class, 'importFromCMC'])->name('import-cmc.store');

    // Control Actions History
    Route::get('/{id}/control-actions', [TokenManagementController::class, 'controlActions'])->name('control-actions');

    // Token Settings
    Route::get('/{id}/edit', [TokenManagementController::class, 'edit'])->name('edit');
    Route::put('/{id}', [TokenManagementController::class, 'update'])->name('update');
});

// =====================================================
// Mobile App Management (Standalone App - 3 อย่างเท่านั้น)
// Admin ควบคุมได้: Push Notifications, Banner โฆษณา, Device Analytics
// =====================================================
Route::prefix('mobile-app')->name('mobile-app.')->group(function () {
    // Dashboard รวม
    Route::get('/', [MobileAppController::class, 'index'])->name('index');

    // 1. Push Notifications
    Route::prefix('push')->name('push.')->group(function () {
        Route::get('/', [MobileAppController::class, 'pushIndex'])->name('index');
        Route::get('/create', [MobileAppController::class, 'pushCreate'])->name('create');
        Route::post('/', [MobileAppController::class, 'pushStore'])->name('store');
        Route::get('/{push}', [MobileAppController::class, 'pushShow'])->name('show');
    });

    // 2. Banner โฆษณา
    Route::prefix('banners')->name('banners.')->group(function () {
        Route::get('/', [MobileAppController::class, 'bannersIndex'])->name('index');
        Route::get('/create', [MobileAppController::class, 'bannersCreate'])->name('create');
        Route::post('/', [MobileAppController::class, 'bannersStore'])->name('store');
        Route::get('/{banner}/edit', [MobileAppController::class, 'bannersEdit'])->name('edit');
        Route::put('/{banner}', [MobileAppController::class, 'bannersUpdate'])->name('update');
        Route::delete('/{banner}', [MobileAppController::class, 'bannersDestroy'])->name('destroy');
        Route::post('/{banner}/toggle', [MobileAppController::class, 'bannersToggle'])->name('toggle');
        Route::post('/reorder', [MobileAppController::class, 'bannersReorder'])->name('reorder');
    });

    // 3. Device Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [MobileAppController::class, 'analytics'])->name('index');
        Route::get('/export', [MobileAppController::class, 'exportAnalytics'])->name('export');
    });
});

// =====================================================
// Deprecated: App Management (เก่า - ไม่ใช้แล้ว)
// แอพเป็น Standalone - การตั้งค่าอยู่ในแอพโดยตรง
// =====================================================
// Route::prefix('app-management')->name('app-management.')->group(function () { ... });

// Viral Trend Detection Routes (ยังใช้อยู่)
Route::prefix('trends')->name('trends.')->group(function () {
    Route::get('/', [TrendManagementController::class, 'index'])->name('index');
    Route::get('/keywords', [TrendManagementController::class, 'keywords'])->name('keywords');
    Route::get('/{trend}', [TrendManagementController::class, 'show'])->name('show');
    Route::post('/{trend}/generate-content', [TrendManagementController::class, 'generateContent'])->name('generate-content');

    // Trend Sources Management
    Route::prefix('sources')->name('sources.')->group(function () {
        Route::get('/', [TrendManagementController::class, 'sources'])->name('index');
        Route::get('/create', [TrendManagementController::class, 'createSource'])->name('create');
        Route::post('/', [TrendManagementController::class, 'storeSource'])->name('store');
        Route::get('/{source}/edit', [TrendManagementController::class, 'editSource'])->name('edit');
        Route::put('/{source}', [TrendManagementController::class, 'updateSource'])->name('update');
        Route::delete('/{source}', [TrendManagementController::class, 'deleteSource'])->name('delete');
        Route::post('/{source}/test-scrape', [TrendManagementController::class, 'testScrape'])->name('test-scrape');
    });
});

// Video Reward System Admin
Route::prefix('video-rewards')->name('video-rewards.')->group(function () {
    // Dashboard & Statistics
    Route::get('/dashboard', [VideoRewardAdminController::class, 'dashboard'])->name('dashboard');

    // Coin Exchange Management
    Route::get('/exchange-requests', [VideoRewardAdminController::class, 'exchangeRequests'])->name('exchange.requests');
    Route::post('/exchange-requests/{requestId}/approve', [VideoRewardAdminController::class, 'approveExchange'])->name('exchange.approve');
    Route::post('/exchange-requests/{requestId}/reject', [VideoRewardAdminController::class, 'rejectExchange'])->name('exchange.reject');

    // Channel Management
    Route::get('/channels', [VideoRewardAdminController::class, 'channels'])->name('channels.index');
    Route::post('/channels', [VideoRewardAdminController::class, 'storeChannel'])->name('channels.store');
    Route::put('/channels/{channelId}', [VideoRewardAdminController::class, 'updateChannel'])->name('channels.update');

    // Video Management
    Route::get('/videos', [VideoRewardAdminController::class, 'videos'])->name('videos.index');
    Route::post('/videos', [VideoRewardAdminController::class, 'storeVideo'])->name('videos.store');
    Route::put('/videos/{videoId}', [VideoRewardAdminController::class, 'updateVideo'])->name('videos.update');

    // Quest Management
    Route::get('/quests', [VideoRewardAdminController::class, 'quests'])->name('quests.index');
    Route::post('/quests', [VideoRewardAdminController::class, 'storeQuest'])->name('quests.store');
    Route::put('/quests/{questId}', [VideoRewardAdminController::class, 'updateQuest'])->name('quests.update');

    // Exchange Rate Management
    Route::get('/exchange-rates', [VideoRewardAdminController::class, 'exchangeRates'])->name('exchange-rates.index');
    Route::put('/exchange-rates/{rateId}', [VideoRewardAdminController::class, 'updateExchangeRate'])->name('exchange-rates.update');
});

// Page Builder (Homepage/Wiki Builder)
Route::prefix('page-builder')->name('page-builder.')->group(function () {
    // Page Management
    Route::get('/', [PageBuilderController::class, 'index'])->name('index');
    Route::get('/create', [PageBuilderController::class, 'create'])->name('create');
    Route::post('/', [PageBuilderController::class, 'store'])->name('store');
    Route::get('/{page}/edit', [PageBuilderController::class, 'edit'])->name('edit');
    Route::put('/{page}', [PageBuilderController::class, 'update'])->name('update');
    Route::delete('/{page}', [PageBuilderController::class, 'destroy'])->name('destroy');
    Route::post('/{page}/duplicate', [PageBuilderController::class, 'duplicate'])->name('duplicate');
    Route::post('/{page}/toggle-active', [PageBuilderController::class, 'toggleActive'])->name('toggle-active');
    Route::post('/{page}/reorder-sections', [PageBuilderController::class, 'reorderSections'])->name('reorder-sections');
    Route::get('/{page}/preview', [PageBuilderController::class, 'preview'])->name('preview');

    // Section Management
    Route::prefix('{page}/sections')->name('sections.')->group(function () {
        Route::post('/', [PageBuilderSectionController::class, 'store'])->name('store');
        Route::post('/from-template/{template}', [PageBuilderSectionController::class, 'createFromTemplate'])->name('from-template');
    });

    Route::prefix('sections/{section}')->name('sections.')->group(function () {
        Route::get('/edit', [PageBuilderSectionController::class, 'edit'])->name('edit');
        Route::put('/', [PageBuilderSectionController::class, 'update'])->name('update');
        Route::delete('/', [PageBuilderSectionController::class, 'destroy'])->name('destroy');
        Route::post('/duplicate', [PageBuilderSectionController::class, 'duplicate'])->name('duplicate');
        Route::post('/move-up', [PageBuilderSectionController::class, 'moveUp'])->name('move-up');
        Route::post('/move-down', [PageBuilderSectionController::class, 'moveDown'])->name('move-down');
        Route::post('/toggle-visibility', [PageBuilderSectionController::class, 'toggleVisibility'])->name('toggle-visibility');
        Route::post('/toggle-active', [PageBuilderSectionController::class, 'toggleActive'])->name('toggle-active');
    });
});

// Homepage Manager - Visual Page Builder สำหรับหน้าแรก
Route::prefix('homepage-manager')->name('homepage-manager.')->group(function () {
    // หน้าหลัก Visual Editor
    Route::get('/', [HomepageManagerController::class, 'index'])->name('index');
    Route::get('/preview', [HomepageManagerController::class, 'preview'])->name('preview');

    // หน้า Templates Gallery (UI)
    Route::get('/templates', [HomepageManagerController::class, 'templatesIndex'])->name('templates.index');
    Route::get('/templates/{template}/preview', [HomepageManagerController::class, 'previewTemplate'])->name('templates.preview');

    // หน้าจัดการ Sections (UI)
    Route::get('/sections', [HomepageManagerController::class, 'sectionsIndex'])->name('sections.index');

    // API สำหรับดึงข้อมูล
    Route::get('/sections/data', [HomepageManagerController::class, 'getSections'])->name('sections.data');
    Route::get('/api/templates', [HomepageManagerController::class, 'getTemplates'])->name('templates.get');

    // Section Management
    Route::post('/sections', [HomepageManagerController::class, 'storeSection'])->name('sections.store');
    Route::put('/sections/{section}', [HomepageManagerController::class, 'updateSection'])->name('sections.update');
    Route::delete('/sections/{section}', [HomepageManagerController::class, 'destroySection'])->name('sections.destroy');
    Route::post('/sections/{section}/toggle', [HomepageManagerController::class, 'toggleSection'])->name('sections.toggle');
    Route::post('/sections/{section}/duplicate', [HomepageManagerController::class, 'duplicateSection'])->name('sections.duplicate');
    Route::post('/sections/reorder', [HomepageManagerController::class, 'reorderSections'])->name('sections.reorder');

    // Element Management
    Route::post('/sections/{section}/elements', [HomepageManagerController::class, 'storeElement'])->name('elements.store');
    Route::put('/elements/{element}', [HomepageManagerController::class, 'updateElement'])->name('elements.update');
    Route::delete('/elements/{element}', [HomepageManagerController::class, 'destroyElement'])->name('elements.destroy');
    Route::post('/elements/{element}/duplicate', [HomepageManagerController::class, 'duplicateElement'])->name('elements.duplicate');
    Route::post('/sections/{section}/elements/reorder', [HomepageManagerController::class, 'reorderElements'])->name('elements.reorder');

    // Template Management
    Route::post('/templates/{template}/import', [HomepageManagerController::class, 'importTemplate'])->name('templates.import');
    Route::post('/templates/save', [HomepageManagerController::class, 'saveAsTemplate'])->name('templates.save');

    // Import/Export
    Route::get('/export', [HomepageManagerController::class, 'export'])->name('export');
    Route::post('/import', [HomepageManagerController::class, 'import'])->name('import');

    // Media Upload
    Route::post('/upload', [HomepageManagerController::class, 'uploadImage'])->name('upload');

    // Clear All
    Route::post('/clear', [HomepageManagerController::class, 'clearAll'])->name('clear');

    // Save All Sections
    Route::post('/save-all', [HomepageManagerController::class, 'saveAll'])->name('save-all');
});

// Hotel Owner Management (Super Admin)
Route::prefix('hotel-owners')->name('hotel-owners.')->group(function () {
    // Web routes (return views)
    Route::get('/', [HotelOwnerController::class, 'index'])->name('index');
    Route::get('/create', [HotelOwnerController::class, 'create'])->name('create');
    Route::post('/', [HotelOwnerController::class, 'store'])->name('store');
    Route::get('/{id}', [HotelOwnerController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [HotelOwnerController::class, 'edit'])->name('edit');
    Route::put('/{id}', [HotelOwnerController::class, 'update'])->name('update');
    Route::delete('/{id}', [HotelOwnerController::class, 'destroy'])->name('destroy');

    // API routes (return JSON for AJAX)
    Route::post('/{id}/block', [HotelOwnerController::class, 'block'])->name('block');
    Route::post('/{id}/unblock', [HotelOwnerController::class, 'unblock'])->name('unblock');
    Route::post('/{id}/assign-hotel', [HotelOwnerController::class, 'assignHotel'])->name('assign-hotel');
    Route::delete('/{id}/unassign-hotel', [HotelOwnerController::class, 'unassignHotel'])->name('unassign-hotel');
    Route::get('/api/statistics', [HotelOwnerController::class, 'statistics'])->name('api.statistics');
});

// Hotel Management API (Super Admin) - Provides additional endpoints for hotel management
// Note: These routes extend the main hotels routes defined above (line 1069)
Route::prefix('hotels')->name('hotels.api.')->group(function () {
    Route::get('/statistics', [SuperAdminHotelController::class, 'statistics'])->name('statistics');
    Route::get('/cities', [SuperAdminHotelController::class, 'getCities'])->name('cities');
    Route::get('/available-owners', [SuperAdminHotelController::class, 'getAvailableOwners'])->name('available-owners');
    Route::patch('/{id}/toggle-active', [SuperAdminHotelController::class, 'toggleActive'])->name('toggle-active');
    Route::patch('/{id}/toggle-featured', [SuperAdminHotelController::class, 'toggleFeatured'])->name('toggle-featured');
    Route::delete('/{id}/gallery-image', [SuperAdminHotelController::class, 'removeGalleryImage'])->name('remove-gallery-image');
});

// System Reset (Super Admin Only)
Route::prefix('system-reset')->name('system-reset.')->group(function () {
    Route::get('/', [SystemResetController::class, 'index'])->name('index');
    Route::post('/reset', [SystemResetController::class, 'reset'])->name('reset');
    Route::get('/statistics', [SystemResetController::class, 'getStatistics'])->name('statistics');
    Route::get('/logs', [SystemResetController::class, 'getLogs'])->name('logs');
    Route::get('/logs/{id}', [SystemResetController::class, 'showLog'])->name('show');
});

// API Management - จัดการ API endpoints และ API keys
Route::prefix('api-management')->name('api-management.')->group(function () {

    // API Endpoints Management
    Route::prefix('endpoints')->name('endpoints.')->group(function () {
        Route::get('/', [ApiEndpointController::class, 'index'])->name('index');
        Route::get('/create', [ApiEndpointController::class, 'create'])->name('create');
        Route::post('/', [ApiEndpointController::class, 'store'])->name('store');
        Route::get('/{apiEndpoint}', [ApiEndpointController::class, 'show'])->name('show');
        Route::get('/{apiEndpoint}/edit', [ApiEndpointController::class, 'edit'])->name('edit');
        Route::put('/{apiEndpoint}', [ApiEndpointController::class, 'update'])->name('update');
        Route::delete('/{apiEndpoint}', [ApiEndpointController::class, 'destroy'])->name('destroy');
        Route::post('/{apiEndpoint}/toggle-status', [ApiEndpointController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{apiEndpoint}/analytics', [ApiEndpointController::class, 'analytics'])->name('analytics');
    });

    // API Keys Management
    Route::prefix('keys')->name('keys.')->group(function () {
        Route::get('/', [ApiKeyController::class, 'index'])->name('index');
        Route::get('/create', [ApiKeyController::class, 'create'])->name('create');
        Route::post('/', [ApiKeyController::class, 'store'])->name('store');
        Route::get('/{apiKey}', [ApiKeyController::class, 'show'])->name('show');
        Route::get('/{apiKey}/edit', [ApiKeyController::class, 'edit'])->name('edit');
        Route::put('/{apiKey}', [ApiKeyController::class, 'update'])->name('update');
        Route::delete('/{apiKey}', [ApiKeyController::class, 'destroy'])->name('destroy');
        Route::post('/{apiKey}/toggle-status', [ApiKeyController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{apiKey}/reset-usage', [ApiKeyController::class, 'resetUsage'])->name('reset-usage');
        Route::get('/{apiKey}/analytics', [ApiKeyController::class, 'analytics'])->name('analytics');
    });
});

// ========================================
// SMART SLIDER PRO SYSTEM
// ========================================

Route::prefix('smart-sliders')->name('smart-sliders.')->group(function () {
    // Main slider management
    Route::get('/', [SmartSliderController::class, 'index'])->name('index');
    Route::get('/create', [SmartSliderController::class, 'create'])->name('create');
    Route::post('/', [SmartSliderController::class, 'store'])->name('store');
    Route::get('/{smartSlider}/edit', [SmartSliderController::class, 'edit'])->name('edit');
    Route::put('/{smartSlider}', [SmartSliderController::class, 'update'])->name('update');
    Route::delete('/{smartSlider}', [SmartSliderController::class, 'destroy'])->name('destroy');

    // Slider operations
    Route::post('/{smartSlider}/duplicate', [SmartSliderController::class, 'duplicate'])->name('duplicate');
    Route::post('/{smartSlider}/toggle-publish', [SmartSliderController::class, 'togglePublish'])->name('toggle-publish');
    Route::get('/{smartSlider}/export', [SmartSliderController::class, 'export'])->name('export');
    Route::post('/import', [SmartSliderController::class, 'import'])->name('import');
    Route::get('/{smartSlider}/analytics', [SmartSliderController::class, 'analytics'])->name('analytics');

    // Slide management
    Route::post('/{slider}/slides', [SmartSlideController::class, 'store'])->name('slides.store');
    Route::put('/slides/{slide}', [SmartSlideController::class, 'update'])->name('slides.update');
    Route::delete('/slides/{slide}', [SmartSlideController::class, 'destroy'])->name('slides.destroy');
    Route::post('/slides/{slide}/duplicate', [SmartSlideController::class, 'duplicate'])->name('slides.duplicate');
    Route::post('/{slider}/slides/reorder', [SmartSlideController::class, 'reorder'])->name('slides.reorder');

    // Media uploads
    Route::post('/slides/upload-background', [SmartSlideController::class, 'uploadBackground'])->name('slides.upload-background');
    Route::post('/slides/upload-video', [SmartSlideController::class, 'uploadVideo'])->name('slides.upload-video');

    // Layer management
    Route::post('/slides/{slide}/layers', [SmartSlideLayerController::class, 'store'])->name('layers.store');
    Route::put('/layers/{layer}', [SmartSlideLayerController::class, 'update'])->name('layers.update');
    Route::delete('/layers/{layer}', [SmartSlideLayerController::class, 'destroy'])->name('layers.destroy');
    Route::post('/layers/{layer}/duplicate', [SmartSlideLayerController::class, 'duplicate'])->name('layers.duplicate');
    Route::post('/slides/{slide}/layers/reorder', [SmartSlideLayerController::class, 'reorder'])->name('layers.reorder');
    Route::post('/layers/upload-image', [SmartSlideLayerController::class, 'uploadImage'])->name('layers.upload-image');
    Route::put('/layers/{layer}/position', [SmartSlideLayerController::class, 'updatePosition'])->name('layers.update-position');
    Route::put('/slides/{slide}/layers/batch', [SmartSlideLayerController::class, 'batchUpdate'])->name('layers.batch-update');
});

// ========================================
// AI GEN - IMAGE & VIDEO GENERATION SYSTEM
// ========================================

Route::prefix('ai-gen')->name('ai-gen.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AiGenAdminController::class, 'dashboard'])->name('dashboard');

    // Providers Management
    Route::get('/providers', [AiGenAdminController::class, 'providers'])->name('providers.index');
    Route::post('/providers', [AiGenAdminController::class, 'createProvider'])->name('providers.store');
    Route::put('/providers/{providerId}', [AiGenAdminController::class, 'updateProvider'])->name('providers.update');
    Route::post('/providers/{providerId}/config', [AiGenAdminController::class, 'updateProviderConfig'])->name('providers.config');
    Route::post('/providers/{providerId}/test', [AiGenAdminController::class, 'testProvider'])->name('providers.test');
    Route::delete('/providers/{providerId}', [AiGenAdminController::class, 'deleteProvider'])->name('providers.destroy');

    // Packages Management
    Route::get('/packages', [AiGenAdminController::class, 'packages'])->name('packages.index');
    Route::post('/packages', [AiGenAdminController::class, 'createPackage'])->name('packages.store');
    Route::put('/packages/{packageId}', [AiGenAdminController::class, 'updatePackage'])->name('packages.update');
    Route::delete('/packages/{packageId}', [AiGenAdminController::class, 'deletePackage'])->name('packages.destroy');

    // Quotas Management
    Route::get('/quotas', [AiGenAdminController::class, 'quotas'])->name('quotas.index');
    Route::post('/quotas', [AiGenAdminController::class, 'saveQuota'])->name('quotas.store');
    Route::put('/quotas/{quotaId}', [AiGenAdminController::class, 'saveQuota'])->name('quotas.update');

    // Usage Logs & Analytics
    Route::get('/usage-logs', [AiGenAdminController::class, 'usageLogs'])->name('usage-logs');

    // Generations Gallery
    Route::get('/generations', [AiGenAdminController::class, 'generations'])->name('generations');

    // Subscriptions
    Route::get('/subscriptions', [AiGenAdminController::class, 'subscriptions'])->name('subscriptions');

    // Settings (Wallet, Pricing, General)
    Route::get('/settings', [AiGenAdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AiGenAdminController::class, 'saveSettings'])->name('settings.save');

    // Promotions Management
    Route::get('/promotions', [AiGenAdminController::class, 'promotions'])->name('promotions.index');
    Route::post('/promotions', [AiGenAdminController::class, 'createPromotion'])->name('promotions.store');
    Route::put('/promotions/{promotionId}', [AiGenAdminController::class, 'updatePromotion'])->name('promotions.update');
    Route::delete('/promotions/{promotionId}', [AiGenAdminController::class, 'deletePromotion'])->name('promotions.destroy');

    // Storage Management (จัดการพื้นที่เก็บภาพ)
    Route::get('/storage/info', [AiGenAdminController::class, 'storageInfo'])->name('storage.info');
    Route::post('/storage/cleanup', [AiGenAdminController::class, 'storageCleanup'])->name('storage.cleanup');
});

// Game Management Routes
Route::prefix('games')->name('games.')->group(function () {
    Route::get('/', [GameController::class, 'index'])->name('index');
    Route::get('/create', [GameController::class, 'create'])->name('create');
    Route::post('/', [GameController::class, 'store'])->name('store');
    Route::post('/update-order', [GameController::class, 'updateOrder'])->name('update-order');

    // ✅ Game Settings Management — ต้องอยู่ก่อน wildcard /{game}
    Route::prefix('game-settings')->name('game-settings.')->group(function () {
        Route::get('/', [GameSettingsController::class, 'index'])
            ->name('index');
        Route::put('/update', [GameSettingsController::class, 'update'])
            ->name('update');

        // ✅ อัพโหลด/ลบไฟล์เสียง (เพลง/เอฟเฟค)
        Route::post('/upload-audio', [GameSettingsController::class, 'uploadAudio'])
            ->name('upload-audio');
        Route::delete('/delete-audio', [GameSettingsController::class, 'deleteAudio'])
            ->name('delete-audio');

        // ✅ Seed ข้อมูล Music Settings (ถ้ายังไม่มี)
        Route::post('/seed-music', [GameSettingsController::class, 'seedMusic'])
            ->name('seed-music');

        // ✅ Seed ข้อมูล Server Limits (ถ้ายังไม่มี)
        Route::post('/seed-server-limits', [GameSettingsController::class, 'seedServerLimits'])
            ->name('seed-server-limits');
    });

    // ⚠️ Wildcard routes ต้องอยู่ท้ายสุด! (ไม่งั้นจะจับ /game-settings, /snake-io ไปก่อน)
    Route::get('/{game}', [GameController::class, 'show'])->name('show');
    Route::get('/{game}/edit', [GameController::class, 'edit'])->name('edit');
    Route::put('/{game}', [GameController::class, 'update'])->name('update');
    Route::delete('/{game}', [GameController::class, 'destroy'])->name('destroy');
    Route::patch('/{game}/toggle-active', [GameController::class, 'toggleActive'])->name('toggle-active');
});

// Arrow X Theme System Routes
Route::prefix('arrow-x-theme')->name('arrow-x-theme.')->group(function () {
    // Dashboard
    Route::get('/', [ArrowXThemeController::class, 'index'])
        ->name('index');

    // General Settings
    Route::get('/general-settings', [ArrowXThemeController::class, 'generalSettings'])
        ->name('general-settings');
    Route::put('/general-settings', [ArrowXThemeController::class, 'updateGeneralSettings'])
        ->name('general-settings.update');

    // Color Settings
    Route::get('/color-settings', [ArrowXThemeController::class, 'colorSettings'])
        ->name('color-settings');
    Route::put('/color-settings', [ArrowXThemeController::class, 'updateColorSettings'])
        ->name('color-settings.update');
    Route::post('/apply-preset', [ArrowXThemeController::class, 'applyPreset'])
        ->name('apply-preset');

    // RGB Effects
    Route::get('/rgb-effects', [ArrowXThemeController::class, 'rgbEffects'])
        ->name('rgb-effects');
    Route::post('/rgb-effects', [ArrowXThemeController::class, 'storeRgbEffect'])
        ->name('rgb-effects.store');
    Route::put('/rgb-effects/{rgbEffect}', [ArrowXThemeController::class, 'updateRgbEffect'])
        ->name('rgb-effects.update');
    Route::delete('/rgb-effects/{rgbEffect}', [ArrowXThemeController::class, 'destroyRgbEffect'])
        ->name('rgb-effects.destroy');

    // Typography
    Route::get('/typography', [ArrowXThemeController::class, 'typography'])
        ->name('typography');
    Route::put('/typography', [ArrowXThemeController::class, 'updateTypography'])
        ->name('typography.update');

    // Upload Assets
    Route::post('/upload-logo', [ArrowXThemeController::class, 'uploadLogo'])
        ->name('upload-logo');
    Route::post('/upload-favicon', [ArrowXThemeController::class, 'uploadFavicon'])
        ->name('upload-favicon');

    // Cache Management
    Route::post('/compile', [ArrowXThemeController::class, 'compileTheme'])
        ->name('compile');
    Route::post('/clear-cache', [ArrowXThemeController::class, 'clearCache'])
        ->name('clear-cache');
    Route::post('/compile-files', [ArrowXThemeController::class, 'compileToFiles'])
        ->name('compile-files');
});

// Recruit Template Management Routes
Route::prefix('recruit-templates')->name('recruit-templates.')->group(function () {
    Route::get('/', [RecruitTemplateController::class, 'index'])->name('index');
    Route::get('/create', [RecruitTemplateController::class, 'create'])->name('create');
    Route::post('/', [RecruitTemplateController::class, 'store'])->name('store');
    Route::get('/{recruitTemplate}', [RecruitTemplateController::class, 'show'])->name('show');
    Route::get('/{recruitTemplate}/edit', [RecruitTemplateController::class, 'edit'])->name('edit');
    Route::put('/{recruitTemplate}', [RecruitTemplateController::class, 'update'])->name('update');
    Route::delete('/{recruitTemplate}', [RecruitTemplateController::class, 'destroy'])->name('destroy');
    Route::post('/{recruitTemplate}/set-default', [RecruitTemplateController::class, 'setDefault'])->name('set-default');
    Route::post('/{recruitTemplate}/toggle-active', [RecruitTemplateController::class, 'toggleActive'])->name('toggle-active');
    Route::get('/{recruitTemplate}/preview', [RecruitTemplateController::class, 'preview'])->name('preview');
});

// Bot Automation System Routes
require __DIR__.'/bot_automation.php';

// =====================================
// TPIX Native Coin Deployment Wizard
// =====================================
Route::prefix('tpix/deployment')->name('tpix.deployment.')->group(function () {
    // Index & Management
    Route::get('/', [TpixDeploymentController::class, 'index'])->name('index');
    Route::get('/create', [TpixDeploymentController::class, 'create'])->name('create');
    Route::post('/', [TpixDeploymentController::class, 'store'])->name('store');

    // Tutorial Route - คู่มือการ Deploy TPIX สู่ Blockchain จริง
    Route::get('/tutorial', [TpixDeploymentController::class, 'tutorial'])->name('tutorial');

    // Wizard Routes (แยกตาม slug)
    Route::prefix('{slug}')->group(function () {
        // Main Wizard (Redirect to current step)
        Route::get('/', [TpixDeploymentController::class, 'wizard'])->name('wizard');

        // Step 1: Prerequisites Check
        Route::get('/step-1', [TpixDeploymentController::class, 'step1'])->name('step1');
        Route::post('/step-1', [TpixDeploymentController::class, 'saveStep1'])->name('step1.save');

        // Step 2: Token Configuration
        Route::get('/step-2', [TpixDeploymentController::class, 'step2'])->name('step2');
        Route::post('/step-2', [TpixDeploymentController::class, 'saveStep2'])->name('step2.save');

        // Step 3: Tokenomics
        Route::get('/step-3', [TpixDeploymentController::class, 'step3'])->name('step3');
        Route::post('/step-3', [TpixDeploymentController::class, 'saveStep3'])->name('step3.save');

        // Step 4: Smart Contract
        Route::get('/step-4', [TpixDeploymentController::class, 'step4'])->name('step4');
        Route::post('/step-4', [TpixDeploymentController::class, 'saveStep4'])->name('step4.save');

        // Payment Confirmation & Processing
        Route::get('/payment', [TpixDeploymentController::class, 'showPaymentConfirmation'])->name('payment');
        Route::post('/payment', [TpixDeploymentController::class, 'processPayment'])->name('payment.process');
        Route::post('/payment/verify', [TpixDeploymentController::class, 'verifyPayment'])->name('payment.verify');
        Route::post('/payment/refund', [TpixDeploymentController::class, 'refundPayment'])->name('payment.refund');

        // Step 5: Deploy & Verify
        Route::get('/step-5', [TpixDeploymentController::class, 'step5'])->name('step5');
        Route::post('/step-5/deploy', [TpixDeploymentController::class, 'deployContract'])->name('step5.deploy');
        Route::post('/step-5/verify', [TpixDeploymentController::class, 'verifyContract'])->name('step5.verify');

        // Step 6: DEX Integration
        Route::get('/step-6', [TpixDeploymentController::class, 'step6'])->name('step6');
        Route::post('/step-6/create-pool', [TpixDeploymentController::class, 'createLiquidityPool'])->name('step6.create-pool');
        Route::post('/step-6/enable-trading', [TpixDeploymentController::class, 'enableTrading'])->name('step6.enable-trading');

        // Step 7: Listing & Marketing
        Route::get('/step-7', [TpixDeploymentController::class, 'step7'])->name('step7');
        Route::post('/step-7/submit-cmc', [TpixDeploymentController::class, 'submitToCMC'])->name('step7.submit-cmc');
        Route::post('/step-7/submit-coingecko', [TpixDeploymentController::class, 'submitToCoinGecko'])->name('step7.submit-coingecko');
        Route::post('/step-7/complete', [TpixDeploymentController::class, 'complete'])->name('step7.complete');

        // Delete Configuration
        Route::delete('/', [TpixDeploymentController::class, 'destroy'])->name('destroy');
    });

    // API Routes
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/check-prerequisites', [TpixDeploymentController::class, 'checkPrerequisitesApi'])
            ->name('check-prerequisites');
    });
});

// Team Transfer Management (Admin - MLM)
Route::prefix('team-transfer')->name('team-transfer.')->group(function () {
    Route::get('/', [TeamTransferController::class, 'index'])->name('index');
    Route::get('/statistics', [TeamTransferController::class, 'statistics'])->name('statistics');
    Route::get('/export', [TeamTransferController::class, 'export'])->name('export');

    // ย้ายทีมโดยตรง (Admin Direct Transfer) - รองรับทั้ง Unilevel และ Binary
    Route::get('/direct', [TeamTransferController::class, 'directTransferForm'])->name('direct');
    Route::post('/direct', [TeamTransferController::class, 'directTransferProcess'])->name('direct.process');
    Route::get('/direct/search-members', [TeamTransferController::class, 'searchMembersApi'])->name('direct.search-members');
    Route::get('/direct/binary-positions/{member}', [TeamTransferController::class, 'getBinaryPositionsApi'])->name('direct.binary-positions');

    Route::get('/{teamTransfer}', [TeamTransferController::class, 'show'])->name('show');
    Route::get('/{teamTransfer}/edit', [TeamTransferController::class, 'edit'])->name('edit');
    Route::post('/{teamTransfer}/process', [TeamTransferController::class, 'process'])->name('process');
    Route::delete('/{teamTransfer}', [TeamTransferController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/restore', [TeamTransferController::class, 'restore'])->name('restore');
    Route::get('/member/{memberId}/history', [TeamTransferController::class, 'history'])->name('history');
});

// ============================================
// Service Booking System Routes
// ============================================

// Service Categories Management
Route::resource('service-categories', ServiceCategoryController::class);
Route::post('service-categories/{serviceCategory}/toggle-active', [ServiceCategoryController::class, 'toggleActive'])
    ->name('service-categories.toggle-active');
Route::post('service-categories/{serviceCategory}/toggle-featured', [ServiceCategoryController::class, 'toggleFeatured'])
    ->name('service-categories.toggle-featured');
Route::post('service-categories/reorder', [ServiceCategoryController::class, 'reorder'])
    ->name('service-categories.reorder');

// Services Management
Route::resource('services', ServiceController::class);
Route::get('services-blocked', [ServiceController::class, 'blocked'])
    ->name('services.blocked');
Route::post('services/{service}/toggle-active', [ServiceController::class, 'toggleActive'])
    ->name('services.toggle-active');
Route::post('services/{service}/toggle-featured', [ServiceController::class, 'toggleFeatured'])
    ->name('services.toggle-featured');
Route::post('services/{service}/calculate-price', [ServiceController::class, 'calculatePrice'])
    ->name('services.calculate-price');
// Block/Unblock บริการ
Route::post('services/{service}/block', [ServiceController::class, 'blockService'])
    ->name('services.block');
Route::post('services/{service}/unblock', [ServiceController::class, 'unblockService'])
    ->name('services.unblock');

// Service Bookings Management
Route::prefix('service-bookings')->name('service-bookings.')->group(function () {
    Route::get('/', [ServiceBookingController::class, 'index'])->name('index');
    Route::get('/analytics', [ServiceBookingController::class, 'analytics'])->name('analytics');
    Route::get('/available-providers', [ServiceBookingController::class, 'availableProviders'])->name('available-providers');
    Route::get('/export', [ServiceBookingController::class, 'export'])->name('export');
    Route::get('/{serviceBooking}', [ServiceBookingController::class, 'show'])->name('show');
    Route::post('/{serviceBooking}/assign-provider', [ServiceBookingController::class, 'assignProvider'])->name('assign-provider');
    Route::post('/{serviceBooking}/cancel', [ServiceBookingController::class, 'cancel'])->name('cancel');
    Route::post('/{serviceBooking}/update-status', [ServiceBookingController::class, 'updateStatus'])->name('update-status');
});

// ============================================
// Anti-Abuse Protection System 🛡️
// ============================================
Route::prefix('anti-abuse')->name('anti-abuse.')->group(function () {
    // Dashboard
    Route::get('/', [AntiAbuseController::class, 'dashboard'])->name('dashboard');

    // Disputes Management
    Route::get('/disputes', [AntiAbuseController::class, 'disputes'])->name('disputes');
    Route::get('/disputes/{dispute}', [AntiAbuseController::class, 'showDispute'])->name('disputes.show');
    Route::post('/disputes/{dispute}/status', [AntiAbuseController::class, 'updateDisputeStatus'])->name('disputes.status');
    Route::post('/disputes/{dispute}/resolve', [AntiAbuseController::class, 'resolveDispute'])->name('disputes.resolve');

    // Trust Scores Management
    Route::get('/trust-scores', [AntiAbuseController::class, 'trustScores'])->name('trust-scores');
    Route::get('/trust-scores/{trustScore}', [AntiAbuseController::class, 'showTrustScore'])->name('trust-scores.show');
    Route::post('/trust-scores/{trustScore}/adjust', [AntiAbuseController::class, 'adjustTrustScore'])->name('trust-scores.adjust');
    Route::post('/trust-scores/{trustScore}/suspend', [AntiAbuseController::class, 'suspendUser'])->name('trust-scores.suspend');
    Route::post('/trust-scores/{trustScore}/ban', [AntiAbuseController::class, 'banUser'])->name('trust-scores.ban');
    Route::post('/trust-scores/{trustScore}/unban', [AntiAbuseController::class, 'unbanUser'])->name('trust-scores.unban');

    // Penalties Management
    Route::get('/penalties', [AntiAbuseController::class, 'penalties'])->name('penalties');
    Route::post('/penalties/{penalty}/charge', [AntiAbuseController::class, 'chargePenalty'])->name('penalties.charge');
    Route::post('/penalties/{penalty}/waive', [AntiAbuseController::class, 'waivePenalty'])->name('penalties.waive');

    // Blocks Management
    Route::get('/blocks', [AntiAbuseController::class, 'blocks'])->name('blocks');
    Route::delete('/blocks/{block}', [AntiAbuseController::class, 'removeBlock'])->name('blocks.remove');

    // Location History
    Route::get('/location-history', [AntiAbuseController::class, 'locationHistory'])->name('location-history');
});

// ============================================
// GPS Monitoring Center 📡
// ============================================
Route::prefix('gps-monitoring')->name('gps-monitoring.')->group(function () {
    // Dashboard หลัก
    Route::get('/', [GpsMonitoringController::class, 'index'])->name('index');

    // API สำหรับดึงข้อมูล GPS
    Route::get('/data', [GpsMonitoringController::class, 'getData'])->name('data');

    // ดูประวัติ GPS ของ booking
    Route::get('/booking/{booking}/history', [GpsMonitoringController::class, 'getBookingHistory'])->name('booking.history');

    // Playback การเดินทาง
    Route::get('/booking/{booking}/playback', [GpsMonitoringController::class, 'playback'])->name('booking.playback');
});

// ============================================
// Rider Management System 🏍️
// ============================================
Route::prefix('riders')->name('riders.')->group(function () {
    // รายการไรเดอร์
    Route::get('/', [RiderController::class, 'index'])->name('index');

    // ไรเดอร์รอตรวจสอบ
    Route::get('/pending', [RiderController::class, 'pending'])->name('pending');

    // แผนที่ GPS ไรเดอร์ทั้งหมด
    Route::get('/map', [RiderController::class, 'map'])->name('map');

    // API: ดึงข้อมูล GPS ของไรเดอร์ทั้งหมด
    Route::get('/gps-data', [RiderController::class, 'getGpsData'])->name('gps-data');

    // รายละเอียดไรเดอร์
    Route::get('/{rider}', [RiderController::class, 'show'])->name('show');

    // อนุมัติไรเดอร์
    Route::post('/{rider}/approve', [RiderController::class, 'approve'])->name('approve');

    // ปฏิเสธไรเดอร์
    Route::post('/{rider}/reject', [RiderController::class, 'reject'])->name('reject');

    // ระงับไรเดอร์
    Route::post('/{rider}/suspend', [RiderController::class, 'suspend'])->name('suspend');

    // สลับสถานะ Active
    Route::post('/{rider}/toggle-active', [RiderController::class, 'toggleActive'])->name('toggle-active');

    // ดูตำแหน่ง GPS และประวัติ
    Route::get('/{rider}/locations', [RiderController::class, 'locations'])->name('locations');

    // API: ดึงตำแหน่ง GPS ล่าสุด
    Route::get('/{rider}/latest-location', [RiderController::class, 'getLatestLocation'])->name('latest-location');

    // Playback ประวัติตำแหน่ง
    Route::get('/{rider}/playback', [RiderController::class, 'locationPlayback'])->name('playback');

    // API: ดึงประวัติตำแหน่ง
    Route::get('/{rider}/location-history', [RiderController::class, 'getLocationHistory'])->name('location-history');
});

// ============================================
// Rider Jobs Management 📦
// ============================================
Route::prefix('rider-jobs')->name('rider-jobs.')->group(function () {
    // รายการงานทั้งหมด
    Route::get('/', [RiderJobController::class, 'index'])->name('index');

    // สถิติงาน (ต้องอยู่ก่อน {job} เพื่อหลีกเลี่ยง route conflict)
    Route::get('/statistics', [RiderJobController::class, 'statistics'])->name('statistics');

    // รายละเอียดงาน
    Route::get('/{job}', [RiderJobController::class, 'show'])->name('show');

    // ยกเลิกงาน
    Route::post('/{job}/cancel', [RiderJobController::class, 'cancel'])->name('cancel');

    // เปลี่ยนไรเดอร์
    Route::post('/{job}/reassign', [RiderJobController::class, 'reassign'])->name('reassign');
});

// Service Providers Management (Admin)
Route::prefix('service-providers')->name('service-providers.')->group(function () {
    Route::get('/', [ServiceProviderController::class, 'index'])->name('index');
    Route::get('/create', [ServiceProviderController::class, 'create'])->name('create');
    Route::post('/', [ServiceProviderController::class, 'store'])->name('store');
    Route::get('/{serviceProvider}', [ServiceProviderController::class, 'show'])->name('show');
    Route::get('/{serviceProvider}/edit', [ServiceProviderController::class, 'edit'])->name('edit');
    Route::put('/{serviceProvider}', [ServiceProviderController::class, 'update'])->name('update');
    Route::delete('/{serviceProvider}', [ServiceProviderController::class, 'destroy'])->name('destroy');
    Route::post('/{serviceProvider}/verify', [ServiceProviderController::class, 'verify'])->name('verify');
    Route::post('/{serviceProvider}/reject', [ServiceProviderController::class, 'reject'])->name('reject');
    Route::post('/{serviceProvider}/toggle-active', [ServiceProviderController::class, 'toggleActive'])->name('toggle-active');
});

// Service Pricing Rules Management
Route::prefix('service-pricing-rules')->name('service-pricing-rules.')->group(function () {
    Route::get('/', [ServicePricingRuleController::class, 'index'])->name('index');
    Route::get('/create', [ServicePricingRuleController::class, 'create'])->name('create');
    Route::post('/', [ServicePricingRuleController::class, 'store'])->name('store');
    Route::get('/{pricingRule}', [ServicePricingRuleController::class, 'show'])->name('show');
    Route::get('/{pricingRule}/edit', [ServicePricingRuleController::class, 'edit'])->name('edit');
    Route::put('/{pricingRule}', [ServicePricingRuleController::class, 'update'])->name('update');
    Route::delete('/{pricingRule}', [ServicePricingRuleController::class, 'destroy'])->name('destroy');
    Route::post('/{pricingRule}/toggle-active', [ServicePricingRuleController::class, 'toggleActive'])->name('toggle-active');
});

// ============================================
// AI Rental with Cloud GPU Routes 🚀🤖
// ============================================

Route::prefix('ai-rental')->name('ai-rental.')->group(function () {
    // Dashboard
    Route::get('/', [AiRentalController::class, 'dashboard'])
        ->name('dashboard');

    // Setup Guide & Tools
    Route::get('/setup-guide', [AiRentalController::class, 'setupGuide'])
        ->name('setup-guide');
    Route::get('/cost-calculator', [AiRentalController::class, 'costCalculator'])
        ->name('cost-calculator');

    // API Endpoints
    Route::prefix('api')->name('api.')->group(function () {
        Route::post('/calculate-cost', [AiRentalController::class, 'calculateCost'])
            ->name('calculate-cost');
        Route::get('/stats', [AiRentalController::class, 'getStats'])
            ->name('stats');
    });

    // Cloud Providers Management ✅ พร้อมใช้งาน!
    Route::prefix('cloud-providers')->name('cloud-providers.')->group(function () {
        Route::get('/', [AiRentalCloudProviderController::class, 'index'])
            ->name('index');
        Route::get('/create', [AiRentalCloudProviderController::class, 'create'])
            ->name('create');
        Route::post('/', [AiRentalCloudProviderController::class, 'store'])
            ->name('store');
        Route::get('/{cloudProvider}', [AiRentalCloudProviderController::class, 'show'])
            ->name('show');
        Route::get('/{cloudProvider}/edit', [AiRentalCloudProviderController::class, 'edit'])
            ->name('edit');
        Route::patch('/{cloudProvider}', [AiRentalCloudProviderController::class, 'update'])
            ->name('update');
        Route::delete('/{cloudProvider}', [AiRentalCloudProviderController::class, 'destroy'])
            ->name('destroy');

        // Status Management
        Route::patch('/{cloudProvider}/activate', [AiRentalCloudProviderController::class, 'activate'])
            ->name('activate');
        Route::patch('/{cloudProvider}/deactivate', [AiRentalCloudProviderController::class, 'deactivate'])
            ->name('deactivate');

        // Rating
        Route::post('/{cloudProvider}/rating', [AiRentalCloudProviderController::class, 'updateRating'])
            ->name('rating');

        // Order (for drag & drop)
        Route::post('/update-order', [AiRentalCloudProviderController::class, 'updateOrder'])
            ->name('update-order');
    });

    // My Configurations ✅ พร้อมใช้งาน!
    Route::prefix('configs')->name('configs.')->group(function () {
        Route::get('/', [AiRentalConfigController::class, 'index'])
            ->name('index');
        Route::get('/create', [AiRentalConfigController::class, 'create'])
            ->name('create');
        Route::post('/', [AiRentalConfigController::class, 'store'])
            ->name('store');
        Route::get('/{config}', [AiRentalConfigController::class, 'show'])
            ->name('show');
        Route::get('/{config}/edit', [AiRentalConfigController::class, 'edit'])
            ->name('edit');
        Route::patch('/{config}', [AiRentalConfigController::class, 'update'])
            ->name('update');
        Route::delete('/{config}', [AiRentalConfigController::class, 'destroy'])
            ->name('destroy');

        // Actions
        Route::post('/{config}/test', [AiRentalConfigController::class, 'testConnection'])
            ->name('test');
        Route::patch('/{config}/set-default', [AiRentalConfigController::class, 'setDefault'])
            ->name('set-default');
    });

    // Analytics ✅ พร้อมใช้งาน!
    Route::get('/analytics', [AiRentalDeploymentController::class, 'analytics'])
        ->name('analytics');

    // Deployments ✅ พร้อมใช้งาน!
    Route::prefix('deployments')->name('deployments.')->group(function () {
        Route::get('/', [AiRentalDeploymentController::class, 'index'])
            ->name('index');
        Route::get('/create', [AiRentalDeploymentController::class, 'create'])
            ->name('create');
        Route::post('/', [AiRentalDeploymentController::class, 'store'])
            ->name('store');
        Route::get('/{deployment}', [AiRentalDeploymentController::class, 'show'])
            ->name('show');
        Route::delete('/{deployment}', [AiRentalDeploymentController::class, 'destroy'])
            ->name('destroy');

        // Control Actions
        Route::patch('/{deployment}/start', [AiRentalDeploymentController::class, 'start'])
            ->name('start');
        Route::patch('/{deployment}/stop', [AiRentalDeploymentController::class, 'stop'])
            ->name('stop');
        Route::patch('/{deployment}/restart', [AiRentalDeploymentController::class, 'restart'])
            ->name('restart');

        // Logs
        Route::get('/{deployment}/logs', [AiRentalDeploymentController::class, 'logs'])
            ->name('logs');
        Route::get('/{deployment}/logs/fetch', [AiRentalDeploymentController::class, 'fetchLogs'])
            ->name('logs.fetch');

        // Test Deployment
        Route::get('/{deployment}/test', [AiRentalDeploymentController::class, 'test'])
            ->name('test');

        // Status Update (for callbacks)
        Route::post('/{deployment}/status', [AiRentalDeploymentController::class, 'updateStatus'])
            ->name('update-status');
    });

    // API Endpoints ✅ พร้อมใช้งาน!
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/stats', [AiRentalDeploymentController::class, 'getStats'])
            ->name('stats');
        Route::get('/chart-data', [AiRentalDeploymentController::class, 'getChartData'])
            ->name('chart-data');
    });

    // Hugging Face News ✅ พร้อมใช้งาน!
    Route::prefix('news')->name('news.')->group(function () {
        Route::get('/', [AiRentalController::class, 'news'])
            ->name('index');
        Route::get('/{news}', [AiRentalController::class, 'showNews'])
            ->name('show');
    });

    // Trending Models ✅ พร้อมใช้งาน!
    Route::prefix('trending-models')->name('trending-models.')->group(function () {
        Route::get('/', [AiRentalController::class, 'trendingModels'])
            ->name('index');
    });
});

/*
|--------------------------------------------------------------------------
| Platform Revenue Routes - ระบบรายได้ Platform
|--------------------------------------------------------------------------
|
| เส้นทางสำหรับจัดการ:
| - Dashboard รายได้ Platform
| - Platform Wallets (Fee, VAT, MLM Pool)
| - Payout Requests และ Settings
| - Wallet Debts (หนี้/ติดลบ)
| - Earnings Ledger
|
*/

Route::prefix('platform-revenue')->name('platform-revenue.')->group(function () {

    // Dashboard หลัก
    Route::get('/', [PlatformRevenueController::class, 'index'])
        ->name('index');

    // API Stats (real-time)
    Route::get('/api/stats', [PlatformRevenueController::class, 'apiStats'])
        ->name('api.stats');

    // Transactions
    Route::get('/transactions', [PlatformRevenueController::class, 'transactions'])
        ->name('transactions');

    // Reports
    Route::get('/reports', [PlatformRevenueController::class, 'reports'])
        ->name('reports');
    Route::get('/reports/export', [PlatformRevenueController::class, 'exportReport'])
        ->name('reports.export');

    // Wallets
    Route::get('/wallets', [PlatformRevenueController::class, 'wallets'])
        ->name('wallets.index');
    Route::get('/wallets/{wallet}', [PlatformRevenueController::class, 'showWallet'])
        ->name('wallets.show');

    // Payouts
    Route::prefix('payouts')->name('payouts.')->group(function () {
        Route::get('/', [PayoutController::class, 'index'])
            ->name('index');
        Route::get('/settings', [PayoutController::class, 'settings'])
            ->name('settings');
        Route::put('/settings/{setting}', [PayoutController::class, 'updateSetting'])
            ->name('settings.update');
        Route::post('/bulk-approve', [PayoutController::class, 'bulkApprove'])
            ->name('bulk-approve');
        Route::get('/api/pending', [PayoutController::class, 'apiPendingPayouts'])
            ->name('api.pending');
        Route::get('/{payout}', [PayoutController::class, 'show'])
            ->name('show');
        Route::post('/{payout}/approve', [PayoutController::class, 'approve'])
            ->name('approve');
        Route::post('/{payout}/reject', [PayoutController::class, 'reject'])
            ->name('reject');
        Route::post('/{payout}/process', [PayoutController::class, 'process'])
            ->name('process');
    });

    // Earnings
    Route::prefix('earnings')->name('earnings.')->group(function () {
        Route::get('/', [PayoutController::class, 'earnings'])
            ->name('index');
        Route::get('/{earning}', [PayoutController::class, 'showEarning'])
            ->name('show');
    });

    // Debts
    Route::prefix('debts')->name('debts.')->group(function () {
        Route::get('/', [DebtController::class, 'index'])
            ->name('index');
        Route::get('/create', [DebtController::class, 'create'])
            ->name('create');
        Route::post('/', [DebtController::class, 'store'])
            ->name('store');
        Route::get('/export', [DebtController::class, 'export'])
            ->name('export');
        Route::post('/batch-collect', [DebtController::class, 'batchCollect'])
            ->name('batch-collect');
        Route::get('/api/stats', [DebtController::class, 'apiStats'])
            ->name('api.stats');
        Route::get('/api/search-users', [DebtController::class, 'searchUsers'])
            ->name('api.search-users');
        Route::get('/user/{user}', [DebtController::class, 'userDebts'])
            ->name('user');
        Route::get('/{debt}', [DebtController::class, 'show'])
            ->name('show');
        Route::post('/{debt}/waive', [DebtController::class, 'waive'])
            ->name('waive');
        Route::delete('/{debt}', [DebtController::class, 'cancel'])
            ->name('cancel');
    });

});

// =========================================
// Video Automation System
// ระบบสร้างวีดีโออัตโนมัติ (Suno + Freepik + YouTube)
// ⚠️ ย้ายออกมาจาก platform-revenue group เพื่อให้ route name ตรงกับ views
// URL: /admin/platform-revenue/video-automation/*
// Route name: admin.video-automation.*
// =========================================
Route::prefix('platform-revenue/video-automation')->name('video-automation.')->group(function () {

    // Dashboard
    // ⚠️ บาง views ใช้ชื่อ 'index' บาง views ใช้ 'dashboard' — ลงทะเบียนทั้งสองชื่อ
    Route::get('/', [VideoAutomationController::class, 'dashboard'])
        ->name('dashboard');
    Route::get('/', [VideoAutomationController::class, 'dashboard'])
        ->name('index');
    Route::get('/stats', [VideoAutomationController::class, 'getDashboardStats'])
        ->name('stats');

    // Settings (API Keys, Credentials)
    Route::get('/settings', [VideoAutomationController::class, 'settings'])
        ->name('settings');
    Route::post('/settings', [VideoAutomationController::class, 'saveSettings'])
        ->name('settings.save');
    Route::post('/settings', [VideoAutomationController::class, 'saveSettings'])
        ->name('settings.update');
    Route::post('/settings/test/{apiType}', [VideoAutomationController::class, 'testApiConnection'])
        ->name('settings.test');

    // Platforms (YouTube, Facebook, Instagram, TikTok, etc.)
    Route::get('/platforms', [VideoAutomationController::class, 'platforms'])
        ->name('platforms');
    Route::post('/platforms', [VideoAutomationController::class, 'savePlatform'])
        ->name('platforms.save');
    Route::delete('/platforms/{id}', [VideoAutomationController::class, 'deletePlatform'])
        ->name('platforms.delete');
    Route::post('/platforms/{id}/disconnect', [VideoAutomationController::class, 'deletePlatform'])
        ->name('platforms.disconnect');

    // YouTube OAuth
    Route::get('/youtube/connect', [VideoAutomationController::class, 'connectYouTube'])
        ->name('youtube.connect');
    Route::get('/youtube/connect', [VideoAutomationController::class, 'connectYouTube'])
        ->name('youtube.auth');
    Route::get('/youtube/callback', [VideoAutomationController::class, 'youtubeCallback'])
        ->name('youtube.callback');

    // Templates (เทมเพลตสำหรับสร้างวีดีโอ)
    Route::get('/templates', [VideoAutomationController::class, 'templates'])
        ->name('templates');
    Route::get('/templates', [VideoAutomationController::class, 'templates'])
        ->name('templates.index');
    Route::get('/templates/create', [VideoAutomationController::class, 'createTemplate'])
        ->name('templates.create');
    Route::post('/templates', [VideoAutomationController::class, 'storeTemplate'])
        ->name('templates.store');
    Route::get('/templates/{id}/edit', [VideoAutomationController::class, 'editTemplate'])
        ->name('templates.edit');
    Route::put('/templates/{id}', [VideoAutomationController::class, 'updateTemplate'])
        ->name('templates.update');
    Route::delete('/templates/{id}', [VideoAutomationController::class, 'deleteTemplate'])
        ->name('templates.destroy');

    // Projects (โปรเจกต์สร้างวีดีโอ)
    Route::get('/projects', [VideoAutomationController::class, 'projects'])
        ->name('projects');
    Route::get('/projects', [VideoAutomationController::class, 'projects'])
        ->name('projects.index');
    Route::get('/projects/create', [VideoAutomationController::class, 'createProject'])
        ->name('projects.create');
    Route::post('/projects', [VideoAutomationController::class, 'storeProject'])
        ->name('projects.store');
    Route::get('/projects/{id}', [VideoAutomationController::class, 'showProject'])
        ->name('projects.show');
    Route::post('/projects/{id}/run', [VideoAutomationController::class, 'runProject'])
        ->name('projects.start');
    Route::post('/projects/{id}/retry', [VideoAutomationController::class, 'runProject'])
        ->name('projects.retry');
    Route::post('/projects/{id}/cancel', [VideoAutomationController::class, 'runProject'])
        ->name('projects.cancel');
    Route::delete('/projects/{id}', [VideoAutomationController::class, 'deleteProject'])
        ->name('projects.destroy');

    // Jobs (งานที่รัน)
    Route::get('/jobs', [VideoAutomationController::class, 'jobs'])
        ->name('jobs');
    Route::get('/jobs', [VideoAutomationController::class, 'jobs'])
        ->name('jobs.index');
    Route::get('/jobs/{id}/logs', [VideoAutomationController::class, 'getJobLogs'])
        ->name('jobs.logs');
    Route::post('/jobs/{id}/retry', [VideoAutomationController::class, 'retryJob'])
        ->name('jobs.retry');
    Route::post('/jobs/{id}/cancel', [VideoAutomationController::class, 'retryJob'])
        ->name('jobs.cancel');

    // Schedules (ตารางเวลาอัตโนมัติ)
    Route::get('/schedules', [VideoAutomationController::class, 'schedules'])
        ->name('schedules');
    Route::get('/schedules/create', [VideoAutomationController::class, 'schedules'])
        ->name('schedules.create');
    Route::get('/schedules/{id}/edit', [VideoAutomationController::class, 'schedules'])
        ->name('schedules.edit');
    Route::post('/schedules', [VideoAutomationController::class, 'saveSchedule'])
        ->name('schedules.save');
    Route::post('/schedules/{id}/run', [VideoAutomationController::class, 'saveSchedule'])
        ->name('schedules.run');
    Route::delete('/schedules/{id}', [VideoAutomationController::class, 'deleteSchedule'])
        ->name('schedules.destroy');

    // Publish History (ประวัติการโพสต์)
    Route::get('/publish-history', [VideoAutomationController::class, 'publishHistory'])
        ->name('publish-history');
    Route::get('/publish-history/stats', [VideoAutomationController::class, 'getPublishStats'])
        ->name('publish-history.stats');
    Route::get('/publish-history/{id}', [VideoAutomationController::class, 'showPublishHistory'])
        ->name('publish-history.show');
    Route::put('/publish-history/{id}/engagement', [VideoAutomationController::class, 'updatePublishEngagement'])
        ->name('publish-history.engagement');
    Route::delete('/publish-history/{id}', [VideoAutomationController::class, 'deletePublishHistory'])
        ->name('publish-history.delete');
    Route::delete('/publish-history/{id}/source-files', [VideoAutomationController::class, 'deletePublishSourceFiles'])
        ->name('publish-history.delete-source');

    // Documentation (คู่มือการใช้งาน)
    Route::get('/documentation', [VideoAutomationController::class, 'documentation'])
        ->name('documentation');
});

// =========================================
// ต่อจาก platform-revenue group (สำหรับ sub-modules ที่ต้องอยู่ใน namespace platform-revenue)
// =========================================
Route::prefix('platform-revenue')->name('platform-revenue.')->group(function () {

    // =========================================
    // AI Content Writer System
    // =========================================
    Route::prefix('ai-content-writer')->name('ai-content-writer.')->group(function () {

        // Dashboard
        Route::get('/', [AiContentWriterController::class, 'dashboard'])
            ->name('dashboard');

        // Settings (API Keys)
        Route::get('/settings', [AiContentWriterController::class, 'settings'])
            ->name('settings');
        Route::post('/settings', [AiContentWriterController::class, 'saveSettings'])
            ->name('settings.save');
        Route::post('/settings/test/{provider}', [AiContentWriterController::class, 'testApiConnection'])
            ->name('settings.test');

        // Templates (เทมเพลตสร้าง Content)
        Route::get('/templates', [AiContentWriterController::class, 'templates'])
            ->name('templates');
        Route::get('/templates/create', [AiContentWriterController::class, 'createTemplate'])
            ->name('templates.create');
        Route::post('/templates', [AiContentWriterController::class, 'storeTemplate'])
            ->name('templates.store');
        Route::get('/templates/{id}/edit', [AiContentWriterController::class, 'editTemplate'])
            ->name('templates.edit');
        Route::put('/templates/{id}', [AiContentWriterController::class, 'updateTemplate'])
            ->name('templates.update');
        Route::delete('/templates/{id}', [AiContentWriterController::class, 'deleteTemplate'])
            ->name('templates.delete');

        // Projects (โปรเจกต์ Content)
        Route::get('/projects', [AiContentWriterController::class, 'projects'])
            ->name('projects');
        Route::get('/projects/{id}', [AiContentWriterController::class, 'showProject'])
            ->name('projects.show');
        Route::delete('/projects/{id}', [AiContentWriterController::class, 'deleteProject'])
            ->name('projects.delete');

        // Generations (ประวัติการสร้าง)
        Route::get('/generations', [AiContentWriterController::class, 'generations'])
            ->name('generations');
        Route::get('/generations/{id}', [AiContentWriterController::class, 'showGeneration'])
            ->name('generations.show');

        // Usage Logs
        Route::get('/usage-logs', [AiContentWriterController::class, 'usageLogs'])
            ->name('usage-logs');

        // Playground (ทดสอบสร้าง Content)
        Route::get('/playground', [AiContentWriterController::class, 'playground'])
            ->name('playground');
        Route::post('/playground/generate', [AiContentWriterController::class, 'quickGenerate'])
            ->name('playground.generate');
    });

    // =========================================
    // Forum Management System - ระบบจัดการฟอรั่มชุมชน
    // =========================================
    Route::prefix('forum')->name('forum.')->group(function () {

        // Categories (จัดการหมวดหมู่)
        Route::get('/categories', [ForumAdminController::class, 'categories'])
            ->name('categories.index');
        Route::get('/categories/create', [ForumAdminController::class, 'createCategory'])
            ->name('categories.create');
        Route::post('/categories', [ForumAdminController::class, 'storeCategory'])
            ->name('categories.store');
        Route::get('/categories/{category}/edit', [ForumAdminController::class, 'editCategory'])
            ->name('categories.edit');
        Route::put('/categories/{category}', [ForumAdminController::class, 'updateCategory'])
            ->name('categories.update');
        Route::delete('/categories/{category}', [ForumAdminController::class, 'deleteCategory'])
            ->name('categories.delete');
        Route::post('/categories/reorder', [ForumAdminController::class, 'reorderCategories'])
            ->name('categories.reorder');

        // Threads (จัดการกระทู้)
        Route::get('/threads', [ForumAdminController::class, 'threads'])
            ->name('threads.index');
        Route::get('/threads/{thread}', [ForumAdminController::class, 'showThread'])
            ->name('threads.show');
        Route::put('/threads/{thread}/pin', [ForumAdminController::class, 'togglePin'])
            ->name('threads.pin');
        Route::put('/threads/{thread}/lock', [ForumAdminController::class, 'toggleLock'])
            ->name('threads.lock');
        Route::put('/threads/{thread}/feature', [ForumAdminController::class, 'toggleFeature'])
            ->name('threads.feature');
        Route::delete('/threads/{thread}', [ForumAdminController::class, 'deleteThread'])
            ->name('threads.delete');

        // Reports (จัดการรายงาน)
        Route::get('/reports', [ForumAdminController::class, 'reports'])
            ->name('reports.index');
        Route::get('/reports/{report}', [ForumAdminController::class, 'showReport'])
            ->name('reports.show');
        Route::put('/reports/{report}/resolve', [ForumAdminController::class, 'resolveReport'])
            ->name('reports.resolve');
        Route::put('/reports/{report}/dismiss', [ForumAdminController::class, 'dismissReport'])
            ->name('reports.dismiss');

        // Trophies (จัดการถ้วยรางวัล)
        Route::get('/trophies', [ForumAdminController::class, 'trophies'])
            ->name('trophies.index');
        Route::get('/trophies/create', [ForumAdminController::class, 'createTrophy'])
            ->name('trophies.create');
        Route::post('/trophies', [ForumAdminController::class, 'storeTrophy'])
            ->name('trophies.store');
        Route::get('/trophies/{trophy}/edit', [ForumAdminController::class, 'editTrophy'])
            ->name('trophies.edit');
        Route::put('/trophies/{trophy}', [ForumAdminController::class, 'updateTrophy'])
            ->name('trophies.update');
        Route::delete('/trophies/{trophy}', [ForumAdminController::class, 'deleteTrophy'])
            ->name('trophies.delete');
        Route::post('/trophies/{trophy}/award/{user}', [ForumAdminController::class, 'awardTrophy'])
            ->name('trophies.award');

        // Analytics (สถิติ)
        Route::get('/analytics', [ForumAdminController::class, 'analytics'])
            ->name('analytics.index');

        // Settings (ตั้งค่า)
        Route::get('/settings', [ForumAdminController::class, 'settings'])
            ->name('settings.index');
        Route::post('/settings', [ForumAdminController::class, 'saveSettings'])
            ->name('settings.save');
    });
});

/*
|--------------------------------------------------------------------------
| Marketplace Affiliate Routes - ระบบ Affiliate จาก Marketplace
|--------------------------------------------------------------------------
|
| เส้นทางสำหรับจัดการ:
| - บัญชี Marketplace (Lazada, Shopee, TikTok Shop)
| - สินค้าที่ Sync มาจาก Platform
| - ออเดอร์และคอมมิชชั่น
| - การเชื่อมต่อ API และ Sync ข้อมูล
|
*/

Route::prefix('marketplace')->name('marketplace.')->group(function () {

    // =========================================
    // Marketplace Accounts - บัญชี Marketplace
    // =========================================
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [MarketplaceAccountController::class, 'index'])
            ->name('index');
        Route::get('/create', [MarketplaceAccountController::class, 'create'])
            ->name('create');
        Route::post('/', [MarketplaceAccountController::class, 'store'])
            ->name('store');
        Route::get('/{account}', [MarketplaceAccountController::class, 'show'])
            ->name('show');
        Route::get('/{account}/edit', [MarketplaceAccountController::class, 'edit'])
            ->name('edit');
        Route::put('/{account}', [MarketplaceAccountController::class, 'update'])
            ->name('update');
        Route::delete('/{account}', [MarketplaceAccountController::class, 'destroy'])
            ->name('destroy');

        // API Actions
        Route::post('/{account}/test-connection', [MarketplaceAccountController::class, 'testConnection'])
            ->name('test-connection');
        Route::post('/{account}/sync-products', [MarketplaceAccountController::class, 'syncProducts'])
            ->name('sync-products');
        Route::post('/{account}/sync-orders', [MarketplaceAccountController::class, 'syncOrders'])
            ->name('sync-orders');
        Route::post('/{account}/sync-all', [MarketplaceAccountController::class, 'syncAll'])
            ->name('sync-all');
    });

    // =========================================
    // Marketplace Products - สินค้าจาก Marketplace
    // =========================================
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [MarketplaceProductController::class, 'index'])
            ->name('index');
        Route::get('/{product}', [MarketplaceProductController::class, 'show'])
            ->name('show');
        Route::put('/{product}', [MarketplaceProductController::class, 'update'])
            ->name('update');
        Route::delete('/{product}', [MarketplaceProductController::class, 'destroy'])
            ->name('destroy');

        // Bulk Actions
        Route::post('/bulk-action', [MarketplaceProductController::class, 'bulkAction'])
            ->name('bulk-action');
    });

    // =========================================
    // Marketplace Orders - ออเดอร์จาก Marketplace
    // =========================================
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [MarketplaceOrderController::class, 'index'])
            ->name('index');
        Route::get('/{order}', [MarketplaceOrderController::class, 'show'])
            ->name('show');
        Route::put('/{order}/status', [MarketplaceOrderController::class, 'updateStatus'])
            ->name('update-status');
        Route::delete('/{order}', [MarketplaceOrderController::class, 'destroy'])
            ->name('destroy');

        // Commission Actions
        Route::post('/{order}/calculate-commission', [MarketplaceOrderController::class, 'calculateCommission'])
            ->name('calculate-commission');
    });

    // =========================================
    // Marketplace Commissions - คอมมิชชั่น
    // =========================================
    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::get('/', [MarketplaceCommissionController::class, 'index'])
            ->name('index');
        Route::get('/{commission}', [MarketplaceCommissionController::class, 'show'])
            ->name('show');
        Route::delete('/{commission}', [MarketplaceCommissionController::class, 'destroy'])
            ->name('destroy');

        // Approval Actions
        Route::post('/{commission}/approve', [MarketplaceCommissionController::class, 'approve'])
            ->name('approve');
        Route::post('/{commission}/pay', [MarketplaceCommissionController::class, 'pay'])
            ->name('pay');
        Route::post('/{commission}/reject', [MarketplaceCommissionController::class, 'reject'])
            ->name('reject');

        // Bulk Actions
        Route::post('/bulk-approve', [MarketplaceCommissionController::class, 'bulkApprove'])
            ->name('bulk-approve');
        Route::post('/bulk-pay', [MarketplaceCommissionController::class, 'bulkPay'])
            ->name('bulk-pay');
    });
});

// ========================================
// LAZADA HUB — ศูนย์เชื่อมต่อ Lazada (Hybrid: affiliate + ขายเองบวกกำไร)
// ต่อยอดตาราง marketplace_* เดิม (reuse model/service) — เมนูแยกเฉพาะ Lazada
// ========================================
Route::prefix('lazada-hub')->name('lazada-hub.')->group(function () {
    // แดชบอร์ดภาพรวม
    Route::get('/', [\App\Http\Controllers\Admin\LazadaHub\DashboardController::class, 'index'])
        ->name('dashboard');

    // การเชื่อมต่อ (กรอก/ทดสอบ API credential)
    Route::prefix('connections')->name('connections.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\LazadaHub\ConnectionController::class, 'index'])->name('index');
        // OAuth callback (Lazada เด้งกลับมาที่นี่) — ต้องมาก่อน {account} กันชนกับ wildcard
        Route::get('/callback', [\App\Http\Controllers\Admin\LazadaHub\ConnectionController::class, 'callback'])->name('callback');
        Route::get('/create', [\App\Http\Controllers\Admin\LazadaHub\ConnectionController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\LazadaHub\ConnectionController::class, 'store'])->name('store');
        Route::get('/{account}/edit', [\App\Http\Controllers\Admin\LazadaHub\ConnectionController::class, 'edit'])->name('edit');
        Route::put('/{account}', [\App\Http\Controllers\Admin\LazadaHub\ConnectionController::class, 'update'])->name('update');
        Route::delete('/{account}', [\App\Http\Controllers\Admin\LazadaHub\ConnectionController::class, 'destroy'])->name('destroy');
        Route::post('/{account}/test', [\App\Http\Controllers\Admin\LazadaHub\ConnectionController::class, 'test'])->name('test');
        // เริ่ม OAuth (พาไปหน้าอนุญาต Lazada)
        Route::get('/{account}/connect', [\App\Http\Controllers\Admin\LazadaHub\ConnectionController::class, 'connect'])->name('connect');
    });

    // แคตตาล็อกสินค้า (ต้นทุน/markup/ราคาเรา/กำไร + นำเข้า scrape + แก้ราคา/โหมด)
    Route::prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\LazadaHub\CatalogController::class, 'index'])->name('index');
        Route::get('/import', [\App\Http\Controllers\Admin\LazadaHub\CatalogController::class, 'importForm'])->name('import');
        Route::post('/import/preview', [\App\Http\Controllers\Admin\LazadaHub\CatalogController::class, 'importPreview'])->name('import-preview');
        Route::post('/import', [\App\Http\Controllers\Admin\LazadaHub\CatalogController::class, 'importStore'])->name('import-store');
        Route::put('/{product}', [\App\Http\Controllers\Admin\LazadaHub\CatalogController::class, 'update'])->name('update');
        Route::post('/{product}/sync-price', [\App\Http\Controllers\Admin\LazadaHub\CatalogController::class, 'syncPrice'])->name('sync-price');
        Route::delete('/{product}', [\App\Http\Controllers\Admin\LazadaHub\CatalogController::class, 'destroy'])->name('destroy');
    });

    // นำเข้าอัตโนมัติ (คิว URL ค่อยๆ ดึง) + คำขอจากลูกค้า (Eve wishes)
    Route::prefix('auto-import')->name('auto-import.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\LazadaHub\AutoImportController::class, 'index'])->name('index');
        Route::post('/enqueue', [\App\Http\Controllers\Admin\LazadaHub\AutoImportController::class, 'enqueue'])->name('enqueue');
        Route::post('/toggle', [\App\Http\Controllers\Admin\LazadaHub\AutoImportController::class, 'toggle'])->name('toggle');
        Route::post('/clear', [\App\Http\Controllers\Admin\LazadaHub\AutoImportController::class, 'clearFinished'])->name('clear');
        Route::post('/{item}/retry', [\App\Http\Controllers\Admin\LazadaHub\AutoImportController::class, 'retry'])->name('retry');
        Route::delete('/{item}', [\App\Http\Controllers\Admin\LazadaHub\AutoImportController::class, 'destroy'])->name('destroy');
    });
    Route::post('/wishes/{wish}/status', [\App\Http\Controllers\Admin\LazadaHub\AutoImportController::class, 'wishStatus'])->name('wishes.status');
});

// ========================================
// CLOUDFLARE MANAGEMENT SYSTEM
// ========================================

Route::prefix('cloudflare')->name('cloudflare.')->group(function () {
    // Dashboard
    Route::get('/', [CloudflareController::class, 'index'])->name('index');

    // Cache Management
    Route::get('/cache', [CloudflareController::class, 'cache'])->name('cache');
    Route::post('/purge-all', [CloudflareController::class, 'purgeAll'])->name('purge-all');
    Route::post('/purge-urls', [CloudflareController::class, 'purgeUrls'])->name('purge-urls');
    Route::post('/purge-prefixes', [CloudflareController::class, 'purgePrefixes'])->name('purge-prefixes');

    // DNS Management
    Route::get('/dns', [CloudflareController::class, 'dns'])->name('dns');
    Route::post('/dns', [CloudflareController::class, 'createDns'])->name('dns.create');
    Route::put('/dns/{recordId}', [CloudflareController::class, 'updateDns'])->name('dns.update');
    Route::delete('/dns/{recordId}', [CloudflareController::class, 'deleteDns'])->name('dns.delete');

    // Security
    Route::get('/security', [CloudflareController::class, 'security'])->name('security');
    Route::post('/security-level', [CloudflareController::class, 'setSecurityLevel'])->name('set-security-level');
    Route::post('/enable-under-attack', [CloudflareController::class, 'enableUnderAttack'])->name('enable-under-attack');
    Route::post('/disable-under-attack', [CloudflareController::class, 'disableUnderAttack'])->name('disable-under-attack');

    // Development Mode
    Route::post('/toggle-dev-mode', [CloudflareController::class, 'toggleDevelopmentMode'])->name('toggle-dev-mode');

    // Analytics
    Route::get('/analytics', [CloudflareController::class, 'analytics'])->name('analytics');
    Route::get('/analytics/data', [CloudflareController::class, 'getAnalytics'])->name('analytics.data');

    // Page Rules
    Route::get('/page-rules', [CloudflareController::class, 'pageRules'])->name('page-rules');

    // Zone Info
    Route::get('/zones', [CloudflareController::class, 'getZones'])->name('zones');
    Route::get('/zone-info', [CloudflareController::class, 'getZoneInfo'])->name('zone-info');

    // Settings
    Route::get('/settings', [CloudflareController::class, 'settings'])->name('settings');
    Route::post('/settings', [CloudflareController::class, 'saveSettings'])->name('settings.save');

    // One-Click Optimization
    Route::get('/optimization', [CloudflareController::class, 'optimization'])->name('optimization');
    Route::get('/optimization/status', [CloudflareController::class, 'getOptimizationStatus'])->name('optimization.status');
    Route::post('/optimization/run', [CloudflareController::class, 'runOptimization'])->name('optimization.run');
    Route::get('/all-settings', [CloudflareController::class, 'getAllSettings'])->name('all-settings');

    // Test Connection
    Route::get('/test-connection', [CloudflareController::class, 'testConnection'])->name('test-connection');

    // Auto Under Attack Mode
    Route::get('/auto-under-attack', [CloudflareController::class, 'autoUnderAttack'])->name('auto-under-attack');
    Route::post('/auto-under-attack/settings', [CloudflareController::class, 'saveAutoUnderAttackSettings'])->name('auto-under-attack.settings');
    Route::get('/auto-under-attack/status', [CloudflareController::class, 'getAutoUnderAttackStatus'])->name('auto-under-attack.status');
    Route::post('/auto-under-attack/toggle', [CloudflareController::class, 'toggleUnderAttackMode'])->name('auto-under-attack.toggle');
    Route::get('/auto-under-attack/test', [CloudflareController::class, 'testAutoUnderAttack'])->name('auto-under-attack.test');
});

// ============================================
// Video Mission System Routes (ภารกิจดูคลิปรับรางวัล)
// ============================================
Route::prefix('video-missions')->name('video-missions.')->group(function () {
    // Dashboard
    Route::get('/', [VideoMissionController::class, 'index'])->name('index');

    // Missions CRUD
    Route::get('/missions', [VideoMissionController::class, 'missions'])->name('missions');
    Route::get('/missions/create', [VideoMissionController::class, 'create'])->name('create');
    Route::post('/missions', [VideoMissionController::class, 'store'])->name('store');
    Route::get('/missions/{mission}', [VideoMissionController::class, 'show'])->name('show');
    Route::get('/missions/{mission}/edit', [VideoMissionController::class, 'edit'])->name('edit');
    Route::put('/missions/{mission}', [VideoMissionController::class, 'update'])->name('update');
    Route::delete('/missions/{mission}', [VideoMissionController::class, 'destroy'])->name('destroy');
    Route::post('/missions/{mission}/toggle-active', [VideoMissionController::class, 'toggleActive'])->name('toggle-active');

    // Completions (การทำภารกิจ)
    Route::get('/completions', [VideoMissionController::class, 'completions'])->name('completions');
    Route::get('/completions/{completion}', [VideoMissionController::class, 'showCompletion'])->name('completion');
    Route::post('/completions/{completion}/verify', [VideoMissionController::class, 'verifyCompletion'])->name('completion.verify');
    Route::post('/completions/{completion}/reject', [VideoMissionController::class, 'rejectCompletion'])->name('completion.reject');

    // Rank Limits
    Route::get('/rank-limits', [VideoMissionController::class, 'rankLimits'])->name('rank-limits');
    Route::put('/rank-limits', [VideoMissionController::class, 'updateRankLimits'])->name('rank-limits.update');

    // Settings
    Route::get('/settings', [VideoMissionController::class, 'settings'])->name('settings');
    Route::put('/settings', [VideoMissionController::class, 'updateSettings'])->name('settings.update');

    // Reports
    Route::get('/reports', [VideoMissionController::class, 'reports'])->name('reports');

    // YouTube Import
    Route::get('/import-youtube', [VideoMissionController::class, 'importYouTube'])->name('import-youtube');
    Route::post('/import-youtube', [VideoMissionController::class, 'processImportYouTube'])->name('import-youtube.process');
});

// ============================================
// Coin Shop Management Routes (ร้านค้า Coins)
// ============================================
Route::prefix('coin-shop')->name('coin-shop.')->group(function () {
    // Products CRUD
    Route::get('/', [CoinShopController::class, 'index'])->name('index');
    Route::get('/create', [CoinShopController::class, 'create'])->name('create');
    Route::post('/', [CoinShopController::class, 'store'])->name('store');
    Route::get('/{product}/edit', [CoinShopController::class, 'edit'])->name('edit');
    Route::put('/{product}', [CoinShopController::class, 'update'])->name('update');
    Route::delete('/{product}', [CoinShopController::class, 'destroy'])->name('destroy');
    Route::post('/{product}/toggle-active', [CoinShopController::class, 'toggleActive'])->name('toggle-active');

    // Purchases Management
    Route::get('/purchases', [CoinShopController::class, 'purchases'])->name('purchases');
    Route::get('/purchases/{purchase}', [CoinShopController::class, 'purchaseDetail'])->name('purchases.detail');
    Route::put('/purchases/{purchase}/status', [CoinShopController::class, 'updatePurchaseStatus'])->name('purchases.update-status');
    Route::post('/purchases/{purchase}/refund', [CoinShopController::class, 'refundPurchase'])->name('purchases.refund');
});

// ============================================
// Star Upgrade Price Management (ราคาอัพเกรดดาว)
// ============================================
Route::prefix('star-upgrade')->name('star-upgrade.')->group(function () {
    // จัดการราคาดาว
    Route::get('/', [StarUpgradePriceController::class, 'index'])->name('index');
    Route::get('/{starPrice}/edit', [StarUpgradePriceController::class, 'edit'])->name('edit');
    Route::put('/{starPrice}', [StarUpgradePriceController::class, 'update'])->name('update');
    Route::post('/{starPrice}/toggle-active', [StarUpgradePriceController::class, 'toggleActive'])->name('toggle-active');

    // ประวัติการอัพเกรด
    Route::get('/history', [StarUpgradePriceController::class, 'history'])->name('history');
    Route::post('/history/{upgrade}/refund', [StarUpgradePriceController::class, 'refund'])->name('refund');
});

// ============================================
// Documentation Routes (เอกสารระบบ)
// ============================================
Route::prefix('documentation')->name('documentation.')->group(function () {
    // Registration Flow Documentation
    Route::get('/registration-flow', function () {
        return view('admin.documentation.registration-flow');
    })->name('registration-flow');
});

// ============================================
// Slogan Management Routes (จัดการคำขวัญ)
// ============================================
Route::prefix('slogans')->name('slogans.')->group(function () {
    // CRUD Routes
    Route::get('/', [SloganController::class, 'index'])->name('index');
    Route::get('/create', [SloganController::class, 'create'])->name('create');
    Route::post('/', [SloganController::class, 'store'])->name('store');
    Route::get('/{slogan}/edit', [SloganController::class, 'edit'])->name('edit');
    Route::put('/{slogan}', [SloganController::class, 'update'])->name('update');
    Route::delete('/{slogan}', [SloganController::class, 'destroy'])->name('destroy');

    // API Routes
    Route::post('/{slogan}/toggle-active', [SloganController::class, 'toggleActive'])->name('toggle-active');
    Route::get('/api/random', [SloganController::class, 'random'])->name('random');
});

// ============================================
// Menu Management Routes (จัดการสิทธิ์เมนู)
// ============================================
Route::prefix('menu-management')->name('menu-management.')->group(function () {
    // หน้าหลัก - แสดงรายการเมนูและการตั้งค่า
    Route::get('/', [MenuManagementController::class, 'index'])
        ->name('index');

    // API: ดึงเมนูตาม Dashboard Type และ Role
    Route::get('/menus', [MenuManagementController::class, 'getMenus'])
        ->name('menus');

    // API: อัพเดทการตั้งค่าเมนูสำหรับ Role
    Route::post('/role-setting', [MenuManagementController::class, 'updateRoleSetting'])
        ->name('role-setting.update');

    // API: อัพเดทลำดับเมนู (Drag & Drop)
    Route::post('/order', [MenuManagementController::class, 'updateOrder'])
        ->name('order.update');

    // API: อัพเดทลำดับเมนูสำหรับ Role
    Route::post('/role-order', [MenuManagementController::class, 'updateRoleOrder'])
        ->name('role-order.update');

    // API: Toggle เปิด/ปิดเมนู
    Route::post('/{menuItem}/toggle-active', [MenuManagementController::class, 'toggleActive'])
        ->name('toggle-active');

    // API: Toggle แสดง/ซ่อนเมนู
    Route::post('/{menuItem}/toggle-visible', [MenuManagementController::class, 'toggleVisible'])
        ->name('toggle-visible');

    // API: Bulk toggle สำหรับ Role
    Route::post('/bulk-toggle-role', [MenuManagementController::class, 'bulkToggleRole'])
        ->name('bulk-toggle-role');

    // API: แก้ไขข้อมูลเมนู
    Route::put('/{menuItem}', [MenuManagementController::class, 'update'])
        ->name('update');

    // API: ซิงค์เมนูจาก config
    Route::post('/sync-from-config', [MenuManagementController::class, 'syncFromConfig'])
        ->name('sync-from-config');

    // API: รีเซ็ตการตั้งค่า Role
    Route::post('/reset-role-settings', [MenuManagementController::class, 'resetRoleSettings'])
        ->name('reset-role-settings');
});

// ============================================
// Developer Management Routes (จัดการนักพัฒนา)
// ============================================
Route::prefix('developers')->name('developers.')->group(function () {
    // รายการนักพัฒนาทั้งหมด
    Route::get('/', [DeveloperApprovalController::class, 'index'])
        ->name('index');

    // รายละเอียดนักพัฒนา
    Route::get('/{developer}', [DeveloperApprovalController::class, 'show'])
        ->name('show');

    // อนุมัตินักพัฒนา
    Route::post('/{developer}/approve', [DeveloperApprovalController::class, 'approve'])
        ->name('approve');

    // ปฏิเสธนักพัฒนา
    Route::post('/{developer}/reject', [DeveloperApprovalController::class, 'reject'])
        ->name('reject');

    // ระงับนักพัฒนา
    Route::post('/{developer}/suspend', [DeveloperApprovalController::class, 'suspend'])
        ->name('suspend');

    // ยกเลิกการระงับ
    Route::post('/{developer}/unsuspend', [DeveloperApprovalController::class, 'unsuspend'])
        ->name('unsuspend');

    // อัพเดทค่าคอมมิชชั่น
    Route::post('/{developer}/commission', [DeveloperApprovalController::class, 'updateCommission'])
        ->name('commission.update');

    // ลบนักพัฒนา
    Route::delete('/{developer}', [DeveloperApprovalController::class, 'destroy'])
        ->name('destroy');
});

/*
|--------------------------------------------------------------------------
| Fortune Telling Routes (ระบบดูดวง Multi-Channel)
|--------------------------------------------------------------------------
| จัดการระบบดูดวงผ่าน Facebook Messenger และ LINE Official Account
*/

Route::prefix('fortune')->name('fortune.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [FortuneSettingsController::class, 'dashboard'])->name('dashboard');

    // Astrology Settings (โหราศาสตร์)
    Route::get('/astrology', [FortuneAstrologyController::class, 'index'])->name('astrology.index');
    Route::put('/astrology', [FortuneAstrologyController::class, 'update'])->name('astrology.update');
    Route::post('/astrology/preview-chart', [FortuneAstrologyController::class, 'previewChart'])->name('astrology.preview-chart');
    Route::post('/astrology/test-calculation', [FortuneAstrologyController::class, 'testCalculation'])->name('astrology.test-calculation');
    Route::get('/astrology/test-png-chart', [FortuneAstrologyController::class, 'testPngChart'])->name('astrology.test-png-chart');

    // Rich Menu Deploy (แม่หมอจันทรา)
    Route::get('/rich-menu', [FortuneRichMenuDeployController::class, 'index'])->name('rich-menu.index');
    Route::post('/rich-menu/preview', [FortuneRichMenuDeployController::class, 'preview'])->name('rich-menu.preview');
    Route::post('/rich-menu/deploy', [FortuneRichMenuDeployController::class, 'deploy'])->name('rich-menu.deploy');
    Route::post('/rich-menu/re-set-default', [FortuneRichMenuDeployController::class, 'reSetDefault'])->name('rich-menu.re-set-default');
    Route::get('/rich-menu/check-line-status', [FortuneRichMenuDeployController::class, 'checkLineStatus'])->name('rich-menu.check-line-status');

    // Rich Menu Editor (แก้ไข Rich Menu ผ่าน Admin UI)
    Route::get('/rich-menu/editor', [FortuneRichMenuEditorController::class, 'index'])->name('rich-menu.editor');
    Route::get('/rich-menu/editor/config', [FortuneRichMenuEditorController::class, 'loadConfig'])->name('rich-menu.editor.config');
    Route::post('/rich-menu/editor/config', [FortuneRichMenuEditorController::class, 'saveConfig'])->name('rich-menu.editor.save-config');
    Route::post('/rich-menu/editor/preview', [FortuneRichMenuEditorController::class, 'preview'])->name('rich-menu.editor.preview');
    Route::post('/rich-menu/editor/upload', [FortuneRichMenuEditorController::class, 'uploadImage'])->name('rich-menu.editor.upload');
    Route::post('/rich-menu/editor/deploy', [FortuneRichMenuEditorController::class, 'deploy'])->name('rich-menu.editor.deploy');

    // การตั้งค่า
    Route::get('/settings', [FortuneSettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [FortuneSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-ai', [FortuneSettingsController::class, 'testAI'])->name('settings.test-ai');
    Route::post('/settings/test-slipok', [FortuneSettingsController::class, 'testSlipOk'])->name('settings.test-slipok');
    Route::get('/settings/diagnose', [FortuneSettingsController::class, 'diagnose'])->name('settings.diagnose');
    Route::post('/settings/run-migrations', [FortuneSettingsController::class, 'runMigrations'])->name('settings.run-migrations');
    if (config('app.debug')) {
        Route::get('/settings/debug-engagement', [FortuneSettingsController::class, 'debugEngagement'])->name('settings.debug-engagement');
        Route::get('/settings/debug-webhook-ai', [FortuneSettingsController::class, 'debugWebhookAI'])->name('settings.debug-webhook-ai');
    }

    // Affiliate Commission Preview (AJAX)
    Route::get('/settings/fortune-commission-preview', [FortuneSettingsController::class, 'fortuneCommissionPreview'])->name('settings.fortune-commission-preview');

    // บัญชีธนาคารเฉพาะระบบดูดวง (AJAX)
    Route::post('/settings/bank-accounts', [FortuneSettingsController::class, 'storeBankAccount'])->name('settings.bank-accounts.store');
    Route::put('/settings/bank-accounts/{account}', [FortuneSettingsController::class, 'updateBankAccount'])->name('settings.bank-accounts.update');
    Route::delete('/settings/bank-accounts/{account}', [FortuneSettingsController::class, 'deleteBankAccount'])->name('settings.bank-accounts.delete');

    // 🎨 (2026-05-17) Payment Banner Admin — composite QR + ลายธนาคาร (anti-FB-detection)
    Route::get('/payment-banner', [FortuneSettingsController::class, 'paymentBanner'])->name('payment-banner.index');
    Route::post('/payment-banner', [FortuneSettingsController::class, 'updatePaymentBanner'])->name('payment-banner.update');
    Route::post('/payment-banner/preview', [FortuneSettingsController::class, 'previewBanner'])->name('payment-banner.preview');
    Route::post('/payment-banner/reset', [FortuneSettingsController::class, 'resetBanner'])->name('payment-banner.reset');
    Route::get('/payment-banner/download-template', [FortuneSettingsController::class, 'downloadBannerTemplate'])->name('payment-banner.download');

    // 🎙️ (2026-05-08) Voice Presets — Library, Import, Test, Apply
    Route::prefix('voice-presets')->name('voice-presets.')->group(function () {
        Route::get('/', [FortuneVoicePresetController::class, 'index'])->name('index');
        Route::post('/', [FortuneVoicePresetController::class, 'store'])->name('store');
        Route::put('/{preset}', [FortuneVoicePresetController::class, 'update'])->name('update');
        Route::delete('/{preset}', [FortuneVoicePresetController::class, 'destroy'])->name('destroy');
        Route::post('/{preset}/apply', [FortuneVoicePresetController::class, 'apply'])->name('apply');
        Route::post('/{preset}/test', [FortuneVoicePresetController::class, 'test'])->name('test');
        Route::post('/import', [FortuneVoicePresetController::class, 'import'])->name('import');
        Route::post('/seed-thai', [FortuneVoicePresetController::class, 'seedThai'])->name('seed-thai');
    });

    // 🌥️ (2026-05-18) Voice Cloud Storage — config + test + migrate
    Route::prefix('voice-storage')->name('voice-storage.')->group(function () {
        Route::post('/save', [FortuneVoiceStorageController::class, 'save'])->name('save');
        Route::post('/test', [FortuneVoiceStorageController::class, 'test'])->name('test');
        Route::post('/fix-symlink', [FortuneVoiceStorageController::class, 'fixSymlink'])->name('fix-symlink');
        Route::post('/migrate', [FortuneVoiceStorageController::class, 'migrate'])->name('migrate');
        Route::get('/stats', [FortuneVoiceStorageController::class, 'stats'])->name('stats');
    });

    // 🎧 (2026-06-21) Voice Management — หน้ารวมจัดการเสียงทั้งหมด (ตั้งค่า TTS + คลังเสียงระบบ)
    Route::get('/voice', [FortuneVoiceController::class, 'index'])->name('voice.index');
    Route::put('/voice/settings', [FortuneVoiceController::class, 'updateSettings'])->name('voice.settings.update');
    Route::put('/voice/clips/{clip}', [FortuneVoiceController::class, 'updateClip'])->name('voice.clips.update');
    Route::post('/voice/clips/{clip}/generate', [FortuneVoiceController::class, 'generateClip'])->name('voice.clips.generate');
    Route::post('/voice/clips/{clip}/upload', [FortuneVoiceController::class, 'uploadClipAudio'])->name('voice.clips.upload');
    Route::delete('/voice/clips/{clip}/audio', [FortuneVoiceController::class, 'deleteClipAudio'])->name('voice.clips.delete-audio');
    // 🎚️ เลือกว่าจะใช้เสียงสล็อตไหน (tts | upload)
    Route::post('/voice/clips/{clip}/active-source', [FortuneVoiceController::class, 'setActiveClipSource'])->name('voice.clips.active-source');
    Route::post('/voice/preview', [FortuneVoiceController::class, 'previewText'])->name('voice.preview');
    // 🔄 รีเฟรชรายชื่อเสียง MiniMax (ล้าง cache ดึงใหม่ — ใช้หลังสร้างเสียงใหม่ที่เว็บ MiniMax)
    Route::post('/voice/refresh-minimax-voices', [FortuneVoiceController::class, 'refreshMinimaxVoices'])->name('voice.refresh-minimax-voices');

    // 🩺 Voice Diagnostic — (2026-06-21) รวมเข้าหน้า "จัดการเสียง" แล้ว → redirect หน้าเดิมไป voice
    //   คง route AJAX test/regenerate ไว้ (หน้า voice ที่รวมแล้วเรียกใช้)
    Route::get('/voice-diagnostic', fn () => redirect()->route('admin.fortune.voice.index'))->name('voice-diagnostic');
    Route::post('/voice-diagnostic/test/{provider}', [FortuneVoiceDiagnosticController::class, 'testProvider'])->name('voice-diagnostic.test');
    Route::post('/voice-diagnostic/regenerate/{reading}', [FortuneVoiceDiagnosticController::class, 'regenerateReading'])->name('voice-diagnostic.regenerate');

    // AI Playground - ทดสอบสนทนากับ AI
    Route::get('/playground', [FortuneSettingsController::class, 'playground'])->name('playground');
    Route::post('/playground/chat', [FortuneSettingsController::class, 'playgroundChat'])->name('playground.chat');
    // 🧪 (2026-05-02) ทดสอบสร้างคำทำนายเชิงลึกด้วย prompt จริง (เพื่อเลือก provider/priority)
    Route::post('/playground/test-deep', [FortuneSettingsController::class, 'testDeepPrediction'])->name('playground.test-deep');
    // 🌟 (2026-05-08) ทดสอบ Sensitive AI Mode — verify locked key + provider จริง
    Route::post('/playground/test-sensitive', [FortuneSettingsController::class, 'testSensitive'])->name('playground.test-sensitive');

    // ช่องทางรับข้อความ (Facebook, LINE, etc.)
    Route::get('/channels', [FortuneChannelController::class, 'index'])->name('channels.index');
    Route::put('/channels', [FortuneChannelController::class, 'update'])->name('channels.update');
    Route::post('/channels/test-line', [FortuneChannelController::class, 'testLine'])->name('channels.test-line');
    Route::post('/channels/test-facebook', [FortuneChannelController::class, 'testFacebook'])->name('channels.test-facebook');
    Route::post('/channels/setup-facebook-messenger', [FortuneChannelController::class, 'setupFacebookMessenger'])->name('channels.setup-facebook-messenger');
    Route::get('/channels/facebook-messenger-profile', [FortuneChannelController::class, 'getFacebookMessengerProfile'])->name('channels.facebook-messenger-profile');
    Route::get('/channels/stats', [FortuneChannelController::class, 'statsApi'])->name('channels.stats');
    Route::get('/channels/facebook-page-management', [FortuneChannelController::class, 'facebookPageManagement'])->name('channels.facebook-page-management');

    // Cloudflare Workers AI (สำหรับเจนภาพดวงประจำวัน)
    Route::put('/channels/cloudflare-ai', [FortuneChannelController::class, 'updateCloudflareAi'])->name('channels.cloudflare-ai.update');
    Route::post('/channels/test-cloudflare-ai', [FortuneChannelController::class, 'testCloudflareAi'])->name('channels.test-cloudflare-ai');

    // หมวดหมู่
    Route::resource('categories', FortuneCategoriesController::class)->except('show');

    // ประวัติการทำนาย
    Route::get('/readings/export/csv', [FortuneReadingsController::class, 'export'])->name('readings.export');
    Route::get('/readings', [FortuneReadingsController::class, 'index'])->name('readings.index');
    Route::get('/readings/{reading}', [FortuneReadingsController::class, 'show'])->name('readings.show');
    Route::get('/readings/{reading}/edit', [FortuneReadingsController::class, 'edit'])->name('readings.edit');
    Route::put('/readings/{reading}', [FortuneReadingsController::class, 'update'])->name('readings.update');
    Route::get('/readings/{reading}/status', [FortuneReadingsController::class, 'status'])->name('readings.status');
    Route::delete('/readings/{reading}', [FortuneReadingsController::class, 'destroy'])->name('readings.destroy');
    Route::post('/readings/{reading}/retry-deep', [FortuneReadingsController::class, 'retryDeepReading'])->name('readings.retry-deep');
    Route::post('/readings/{reading}/resend-deep', [FortuneReadingsController::class, 'resendDeepReading'])->name('readings.resend-deep');
    Route::post('/readings/{reading}/recover-pay-first', [FortuneReadingsController::class, 'recoverPayFirstReading'])->name('readings.recover-pay-first');

    // 🖼️ แบนเนอร์ DM
    Route::prefix('banners')->name('banners.')->group(function () {
        Route::get('/', [FortuneBannerController::class, 'index'])->name('index');
        Route::get('/create', [FortuneBannerController::class, 'create'])->name('create');
        Route::post('/', [FortuneBannerController::class, 'store'])->name('store');
        Route::get('/{banner}/edit', [FortuneBannerController::class, 'edit'])->name('edit');
        Route::put('/{banner}', [FortuneBannerController::class, 'update'])->name('update');
        Route::delete('/{banner}', [FortuneBannerController::class, 'destroy'])->name('destroy');
        Route::post('/{banner}/toggle', [FortuneBannerController::class, 'toggle'])->name('toggle');
        Route::post('/reorder', [FortuneBannerController::class, 'reorder'])->name('reorder');
        Route::post('/settings', [FortuneBannerController::class, 'saveSettings'])->name('settings');
    });

    // 📜 (2026-06-06) กติกาก่อนจองคิว (Consent Gate) — รูป + คำเตือน + เตือนตอนยกเลิก
    Route::prefix('consent')->name('consent.')->group(function () {
        Route::get('/', [FortuneConsentController::class, 'index'])->name('index');
        Route::post('/settings', [FortuneConsentController::class, 'saveSettings'])->name('settings');
        Route::get('/create', [FortuneConsentController::class, 'create'])->name('create');
        Route::post('/', [FortuneConsentController::class, 'store'])->name('store');
        Route::get('/{consent}/edit', [FortuneConsentController::class, 'edit'])->name('edit');
        Route::put('/{consent}', [FortuneConsentController::class, 'update'])->name('update');
        Route::delete('/{consent}', [FortuneConsentController::class, 'destroy'])->name('destroy');
        Route::post('/{consent}/toggle', [FortuneConsentController::class, 'toggle'])->name('toggle');
        Route::post('/reorder', [FortuneConsentController::class, 'reorder'])->name('reorder');
    });

    // 💬 ข้อความชวนดูดวง (สุ่ม) — ส่งแทนรูปเมื่อลูกค้าได้รูปในสัปดาห์นี้แล้ว
    Route::prefix('invite-messages')->name('invite-messages.')->group(function () {
        Route::get('/', [FortuneInviteMessageController::class, 'index'])->name('index');
        Route::post('/', [FortuneInviteMessageController::class, 'store'])->name('store');
        Route::put('/{inviteMessage}', [FortuneInviteMessageController::class, 'update'])->name('update');
        Route::delete('/{inviteMessage}', [FortuneInviteMessageController::class, 'destroy'])->name('destroy');
        Route::post('/{inviteMessage}/toggle', [FortuneInviteMessageController::class, 'toggle'])->name('toggle');
        Route::post('/reorder', [FortuneInviteMessageController::class, 'reorder'])->name('reorder');
        Route::post('/settings', [FortuneInviteMessageController::class, 'saveSettings'])->name('settings');
        // 🌍 (2026-06-07) ตัวกรองกลุ่มเป้าหมาย DM กลับ (สัญชาติ + อายุ) + เปิด/ปิดหมวด
        Route::post('/audience-filters', [FortuneInviteMessageController::class, 'saveAudienceFilters'])->name('audience-filters');
        Route::post('/categories', [FortuneInviteMessageController::class, 'saveCategories'])->name('categories');
    });

    // 📚 RAG Admin Q&A — เก็บ + ใช้คำตอบของแอดมินเป็น few-shot
    Route::prefix('admin-qa')->name('admin-qa.')->group(function () {
        Route::get('/', [FortuneAdminQAController::class, 'index'])->name('index');
        Route::get('/{qa}/edit', [FortuneAdminQAController::class, 'edit'])->name('edit');
        Route::put('/{qa}', [FortuneAdminQAController::class, 'update'])->name('update');
        Route::post('/{qa}/toggle', [FortuneAdminQAController::class, 'toggleActive'])->name('toggle');
        Route::post('/{qa}/reembed', [FortuneAdminQAController::class, 'reembed'])->name('reembed');
        Route::delete('/{qa}', [FortuneAdminQAController::class, 'destroy'])->name('destroy');
        Route::post('/settings', [FortuneAdminQAController::class, 'updateSettings'])->name('settings');
    });

    // 🧠 (2026-06-01) คลังความรู้แม่หมอ (RAG) — สุขภาพ/ฮวงจุ้ย/เจ้าที่/องค์เทพ/ไสยศาสตร์
    Route::prefix('knowledge')->name('knowledge.')->group(function () {
        Route::get('/', [FortuneKnowledgeController::class, 'index'])->name('index');
        Route::get('/create', [FortuneKnowledgeController::class, 'create'])->name('create');
        Route::post('/', [FortuneKnowledgeController::class, 'store'])->name('store');
        Route::get('/{knowledge}/edit', [FortuneKnowledgeController::class, 'edit'])->name('edit');
        Route::put('/{knowledge}', [FortuneKnowledgeController::class, 'update'])->name('update');
        Route::delete('/{knowledge}', [FortuneKnowledgeController::class, 'destroy'])->name('destroy');
        Route::post('/{knowledge}/toggle', [FortuneKnowledgeController::class, 'toggle'])->name('toggle');
    });

    // ระบบเทคโอเวอร์ (Takeover Control) — แม่หมอ/แอดมินคุยแทน AI
    Route::prefix('takeover')->name('takeover.')->group(function () {
        Route::get('/', [FortuneTakeoverController::class, 'index'])->name('index');
        Route::get('/{reading}', [FortuneTakeoverController::class, 'show'])->name('show');
        Route::get('/{reading}/status', [FortuneTakeoverController::class, 'status'])->name('status');
        Route::post('/{reading}/takeover', [FortuneTakeoverController::class, 'takeover'])->name('takeover');
        Route::post('/{reading}/resume', [FortuneTakeoverController::class, 'resume'])->name('resume');
        Route::post('/{reading}/extend', [FortuneTakeoverController::class, 'extend'])->name('extend');
        Route::post('/{reading}/send-message', [FortuneTakeoverController::class, 'sendMessage'])->name('send-message');
        // 🚫 (2026-05-23) แบน user จากหน้า takeover — กดที่ชื่อคนกำลังแชท แบนได้แม้ยังไม่สร้างบิล
        Route::post('/{reading}/ban', [FortuneTakeoverController::class, 'ban'])->name('ban');
    });

    // 🚫 (2026-05-22) ระบบคุก (Ban/Jail) — ห้ามบอทคุยกับ user ที่ไม่เหมาะสม
    Route::prefix('bans')->name('bans.')->group(function () {
        Route::get('/', [FortuneBanController::class, 'index'])->name('index');
        Route::post('/', [FortuneBanController::class, 'store'])->name('store');
        Route::delete('/{ban}', [FortuneBanController::class, 'destroy'])->name('destroy');
    });

    // คำถามที่ AI ตอบไม่ได้ — รอแอดมินตอบกลับ
    Route::prefix('saved-questions')->name('saved-questions.')->group(function () {
        Route::get('/', [FortuneSavedQuestionsController::class, 'index'])->name('index');
        Route::post('/{question}/reply', [FortuneSavedQuestionsController::class, 'reply'])->name('reply');
        Route::post('/{question}/resend', [FortuneSavedQuestionsController::class, 'resend'])->name('resend');
        Route::delete('/{question}', [FortuneSavedQuestionsController::class, 'destroy'])->name('destroy');
    });

    // จัดการผู้ใช้ดูดวง + ส่งข้อความ
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [FortuneUsersController::class, 'index'])->name('index');
        Route::get('/{platform}/{userId}', [FortuneUsersController::class, 'show'])->name('show');
        Route::post('/send-message', [FortuneUsersController::class, 'sendMessage'])->name('send-message');
        Route::post('/broadcast', [FortuneUsersController::class, 'broadcastMessage'])->name('broadcast');
        Route::post('/quick-add-credits', [FortuneUsersController::class, 'quickAddCredits'])->name('quick-add-credits');
        // 🔒 (2026-05-04) Reset pay-later eligibility — admin override
        Route::post('/{platform}/{userId}/reset-pay-later', [FortuneUsersController::class, 'resetPayLaterEligibility'])->name('reset-pay-later');
    });

    // 👤 (2026-05-14) บุคลิกลูกค้า (Persona Memory) — RPG Character Sheet
    Route::prefix('personas')->name('personas.')->group(function () {
        Route::get('/', [FortunePersonasController::class, 'index'])->name('index');
        Route::get('/{id}', [FortunePersonasController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/{id}/export', [FortunePersonasController::class, 'exportMarkdown'])->whereNumber('id')->name('export');
    });

    // เทมเพลตตอบกลับ
    Route::resource('response-templates', FortuneResponseTemplatesController::class)->except('show');
    Route::post('/response-templates/{response_template}/set-default', [FortuneResponseTemplatesController::class, 'setDefault'])
        ->name('response-templates.set-default');
    Route::post('/response-templates-preview', [FortuneResponseTemplatesController::class, 'preview'])
        ->name('response-templates.preview');

    // จัดการบิลดูดวง
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [FortuneBillingController::class, 'index'])->name('index');
        Route::get('/floating-bills', [FortuneBillingController::class, 'floatingBills'])->name('floating-bills');
        Route::post('/{reading}/assign', [FortuneBillingController::class, 'assignToUser'])->name('assign');
        Route::post('/{reading}/manual-confirm', [FortuneBillingController::class, 'manualConfirm'])->name('manual-confirm');
        Route::post('/{reading}/void', [FortuneBillingController::class, 'void'])->name('void');
        Route::post('/{reading}/retry-fortune', [FortuneBillingController::class, 'retryFortune'])->name('retry-fortune');
        Route::get('/export-revenue', [FortuneBillingController::class, 'exportRevenue'])->name('export-revenue');
        Route::get('/stats', [FortuneBillingController::class, 'statsApi'])->name('stats');

        // 💳 (2026-05-09) Stripe payment management
        Route::post('/{reading}/stripe-refund', [FortuneBillingController::class, 'stripeRefund'])->name('stripe-refund');
        Route::post('/{reading}/stripe-expire', [FortuneBillingController::class, 'stripeExpire'])->name('stripe-expire');
        Route::post('/{reading}/stripe-resync', [FortuneBillingController::class, 'stripeResync'])->name('stripe-resync');
    });

    // คอมมิชชั่นดูดวง (Fortune Commission Management)
    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::get('/', [FortuneCommissionController::class, 'index'])->name('index');
        Route::get('/manage', [FortuneCommissionController::class, 'manage'])->name('manage');
        Route::get('/export', [FortuneCommissionController::class, 'exportCsv'])->name('export');
        Route::get('/{commission}', [FortuneCommissionController::class, 'show'])->name('show');
        Route::post('/approve', [FortuneCommissionController::class, 'approve'])->name('approve');
        Route::post('/{commission}/reject', [FortuneCommissionController::class, 'reject'])->name('reject');
        Route::post('/{commission}/adjust', [FortuneCommissionController::class, 'adjustAmount'])->name('adjust');
        Route::post('/pay', [FortuneCommissionController::class, 'payOut'])->name('pay');
        Route::post('/settings', [FortuneCommissionController::class, 'updateSettings'])->name('update-settings');
        Route::post('/create-manual', [FortuneCommissionController::class, 'createManual'])->name('create-manual');
    });

    // ผังสายงานดูดวง (Fortune Referral Tree)
    Route::prefix('referral-tree')->name('referral-tree.')->group(function () {
        Route::get('/', [FortuneCommissionController::class, 'referralTree'])->name('index');
        Route::get('/{member}/tree-data', [FortuneCommissionController::class, 'getReferralTreeData'])->name('tree-data');
    });

    // จัดการเครดิตดูดวงฟรีรายคน
    Route::prefix('credits')->name('credits.')->group(function () {
        Route::get('/', [FortuneUserCreditController::class, 'index'])->name('index');
        Route::post('/add-credits', [FortuneUserCreditController::class, 'addCredits'])->name('add-credits');
        Route::post('/{credit}/reset-daily', [FortuneUserCreditController::class, 'resetDaily'])->name('reset-daily');
        Route::post('/{credit}/set-unlimited', [FortuneUserCreditController::class, 'setUnlimited'])->name('set-unlimited');
        Route::post('/{credit}/reset-all', [FortuneUserCreditController::class, 'resetAll'])->name('reset-all');
        Route::delete('/{credit}', [FortuneUserCreditController::class, 'destroy'])->name('destroy');
    });

    // การตลาดอัตโนมัติ (AI Marketing Campaigns)
    Route::prefix('marketing')->name('marketing.')->group(function () {
        Route::get('/', [FortuneMarketingController::class, 'index'])->name('index');
        Route::get('/create', [FortuneMarketingController::class, 'create'])->name('create');
        Route::post('/', [FortuneMarketingController::class, 'store'])->name('store');
        Route::get('/{campaign}/edit', [FortuneMarketingController::class, 'edit'])->name('edit');
        Route::put('/{campaign}', [FortuneMarketingController::class, 'update'])->name('update');
        Route::delete('/{campaign}', [FortuneMarketingController::class, 'destroy'])->name('destroy');
        Route::post('/{campaign}/generate-message', [FortuneMarketingController::class, 'generateMessage'])->name('generate-message');
        Route::post('/{campaign}/activate', [FortuneMarketingController::class, 'activate'])->name('activate');
        Route::post('/{campaign}/pause', [FortuneMarketingController::class, 'pause'])->name('pause');
        Route::post('/{campaign}/cancel', [FortuneMarketingController::class, 'cancel'])->name('cancel');
        Route::post('/{campaign}/send-now', [FortuneMarketingController::class, 'sendNow'])->name('send-now');
        Route::post('/preview', [FortuneMarketingController::class, 'preview'])->name('preview');
    });

    // ดวงรายวันอัตโนมัติ (Daily Horoscope Auto-Posting)
    Route::prefix('horoscope')->name('horoscope.')->group(function () {
        Route::get('/', [FortuneHoroscopeController::class, 'index'])->name('index');
        Route::get('/create', [FortuneHoroscopeController::class, 'create'])->name('create');
        Route::post('/', [FortuneHoroscopeController::class, 'store'])->name('store');
        Route::get('/{campaign}/edit', [FortuneHoroscopeController::class, 'edit'])->name('edit');
        Route::put('/{campaign}', [FortuneHoroscopeController::class, 'update'])->name('update');
        Route::delete('/{campaign}', [FortuneHoroscopeController::class, 'destroy'])->name('destroy');
        Route::post('/{campaign}/activate', [FortuneHoroscopeController::class, 'activate'])->name('activate');
        Route::post('/{campaign}/pause', [FortuneHoroscopeController::class, 'pause'])->name('pause');
        Route::post('/{campaign}/generate-now', [FortuneHoroscopeController::class, 'generateNow'])->name('generate-now');
        Route::post('/{campaign}/publish-now', [FortuneHoroscopeController::class, 'publishNow'])->name('publish-now');
        Route::get('/{campaign}/content-history', [FortuneHoroscopeController::class, 'contentHistory'])->name('content-history');
        Route::get('/{campaign}/post-history', [FortuneHoroscopeController::class, 'postHistory'])->name('post-history');
        Route::get('/{campaign}/preview/{date?}', [FortuneHoroscopeController::class, 'previewContent'])->name('preview');
    });

    // ========================================
    // SLIP VERIFICATION LOGS — ประวัติการตรวจสลิป (SlipOK) audit
    // ========================================
    Route::get('slip-logs', [FortuneSlipLogController::class, 'index'])->name('slip-logs.index');
    // 🖼️ (2026-06-03) สตรีมรูปสลิปที่ส่งไปตรวจ (auth admin เท่านั้น — PDPA) เพื่อ debug no_qr
    Route::get('slip-logs/{log}/image', [FortuneSlipLogController::class, 'image'])->name('slip-logs.image');

    // ========================================
    // 🪪 (2026-06-09) SLIPOK ACCOUNT POOL — หมุนหลายบัญชี SlipOK กัน quota ตัน
    // ========================================
    Route::prefix('slipok-accounts')->name('slipok-accounts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\FortuneSlipOkAccountController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\FortuneSlipOkAccountController::class, 'store'])->name('store');
        Route::put('/mode', [\App\Http\Controllers\Admin\FortuneSlipOkAccountController::class, 'updateMode'])->name('update-mode');
        Route::put('/{account}', [\App\Http\Controllers\Admin\FortuneSlipOkAccountController::class, 'update'])->name('update');
        Route::delete('/{account}', [\App\Http\Controllers\Admin\FortuneSlipOkAccountController::class, 'destroy'])->name('destroy');
        Route::post('/{account}/toggle', [\App\Http\Controllers\Admin\FortuneSlipOkAccountController::class, 'toggle'])->name('toggle');
        Route::post('/{account}/test', [\App\Http\Controllers\Admin\FortuneSlipOkAccountController::class, 'test'])->name('test');
    });

    // ========================================
    // CELTIC CROSS TAROT — โหมดดูดวงไพ่ยิปซีเต็มสำรับ 99฿
    // ========================================
    Route::prefix('celtic-cross')->name('celtic-cross.')->group(function () {
        Route::get('/', [FortuneCelticCrossController::class, 'index'])->name('index');
        Route::put('/settings', [FortuneCelticCrossController::class, 'updateSettings'])->name('settings.update');
        Route::get('/readings/{reading}', [FortuneCelticCrossController::class, 'showReading'])->name('show');
        // 🔄 (2026-05-04) Reset reading — admin force ให้ลูกค้าเปิดไพ่ใหม่ (route ขาดหาย ทำให้ show page crash)
        Route::post('/readings/{reading}/reset', [FortuneCelticCrossController::class, 'resetReading'])->name('reset');
        // 🔄 (2026-05-16) Restore active chat — เปิด Pro Session กลับให้ลูกค้าคุยต่อตามเวลาที่เหลือ
        Route::post('/readings/{reading}/restore', [FortuneCelticCrossController::class, 'restoreActiveChat'])->name('restore');
        // ⏰ (2026-05-22) Extend Pro Session — admin มอบเวลาคุยกับแม่หมอเพิ่ม (add หรือ reset)
        Route::post('/readings/{reading}/extend', [FortuneCelticCrossController::class, 'extendProSession'])->name('extend');
        // 🗑️ (2026-05-04) Cancel reading — ลบบิลที่ขัดกัน (pending payment ค้าง) — ปลอดภัยถ้ายังไม่จ่าย
        Route::post('/readings/{reading}/cancel', [FortuneCelticCrossController::class, 'cancelReading'])->name('cancel');
        // 🚀 (2026-05-08) Force Approve — โอนยอดไม่ตรง → admin มาร์คจ่ายแล้ว + push เริ่มเปิดไพ่ (ใช้แทนเปิด SMS app มือถือ)
        Route::post('/readings/{reading}/force-approve', [FortuneCelticCrossController::class, 'forceApprove'])->name('force-approve');
        // ⛔ (2026-06-08) Void Approval — ยกเลิกการอนุมัติบิลที่กดผิด/ลูกค้าไม่ได้จ่าย (reverse confirmPayment + UPA/SMS/commission)
        Route::post('/readings/{reading}/void-approval', [FortuneCelticCrossController::class, 'voidApproval'])->name('void-approval');
        // 🤖 (2026-05-17 Phase 2) Admin Ask AI — AJAX sync endpoint (return JSON, ไม่ใช่ redirect)
        Route::post('/readings/{reading}/ask-ai', [FortuneCelticCrossController::class, 'adminAskAi'])->name('ask-ai');
        // 🚨 (2026-05-05) Emergency Recovery — กู้บิลด่วน (ใส่เลขบิล / auto-scan)
        Route::get('/emergency-recover', [FortuneCelticCrossController::class, 'emergencyRecover'])->name('emergency-recover');
        Route::post('/emergency-recover', [FortuneCelticCrossController::class, 'emergencyRecoverAction'])->name('emergency-recover.action');
    });

    // ========================================
    // 🐛 DEBUG TOOLS — admin self-service debugging (tail log + AI sync test)
    // ========================================
    Route::prefix('debug-tools')->name('debug-tools.')->group(function () {
        Route::get('/', [FortuneDebugToolsController::class, 'index'])->name('index');
        Route::get('/logs', [FortuneDebugToolsController::class, 'tailLog'])->name('logs');
        Route::post('/test-ai', [FortuneDebugToolsController::class, 'testAi'])->name('test-ai');
        // 🔀 (2026-07-03) สลับแพ็กเกจบิล 39 (Deep) ↔ 99 (Celtic) — แอดมินแก้เคสลูกค้าเปิดผิดแพ็กเกจ
        Route::get('/bill-info', [FortuneDebugToolsController::class, 'billInfo'])->name('bill-info');
        Route::post('/switch-package', [FortuneDebugToolsController::class, 'switchPackage'])->name('switch-package');
    });

    // ========================================
    // MYSTIC CONTENT — โพสคอนเทนต์สายมูอัตโนมัติ (Facebook Page)
    // ========================================
    Route::prefix('mystic')->name('mystic.')->group(function () {
        Route::get('/', [FortuneMysticController::class, 'index'])->name('index');
        Route::put('/settings', [FortuneMysticController::class, 'updateSettings'])->name('settings.update');
        Route::put('/topics/{topic}', [FortuneMysticController::class, 'updateTopic'])->name('topics.update');
        Route::post('/publish-now', [FortuneMysticController::class, 'publishNow'])->name('publish-now');
        Route::get('/posts/{post}', [FortuneMysticController::class, 'showPost'])->name('posts.show');
    });

    // ========================================
    // HOROSCOPE PUBLIC — จัดการระบบดูดวงสาธารณะ (frontend)
    // ========================================
    Route::prefix('horoscope-public')->name('horoscope-public.')->group(function () {
        // ตั้งค่าทั่วไป
        Route::get('/settings', [HoroscopePublicSettingsController::class, 'index'])->name('settings');
        Route::put('/settings', [HoroscopePublicSettingsController::class, 'update'])->name('settings.update');

        // จัดการ 12 ราศี
        Route::prefix('zodiac')->name('zodiac.')->group(function () {
            Route::get('/', [HoroscopeZodiacController::class, 'index'])->name('index');
            Route::get('/{zodiac}/edit', [HoroscopeZodiacController::class, 'edit'])->name('edit');
            Route::put('/{zodiac}', [HoroscopeZodiacController::class, 'update'])->name('update');
            Route::post('/generate-daily', [HoroscopeZodiacController::class, 'generateDaily'])->name('generate-daily');
            Route::get('/predictions', [HoroscopeZodiacController::class, 'predictions'])->name('predictions');
            Route::delete('/predictions/{prediction}', [HoroscopeZodiacController::class, 'destroyPrediction'])->name('predictions.destroy');
        });

        // จัดการพจนานุกรมฝัน
        Route::prefix('dream')->name('dream.')->group(function () {
            // หมวดหมู่
            Route::get('/categories', [HoroscopeDreamManagementController::class, 'categories'])->name('categories');
            Route::get('/categories/create', [HoroscopeDreamManagementController::class, 'createCategory'])->name('categories.create');
            Route::post('/categories', [HoroscopeDreamManagementController::class, 'storeCategory'])->name('categories.store');
            Route::get('/categories/{category}/edit', [HoroscopeDreamManagementController::class, 'editCategory'])->name('categories.edit');
            Route::put('/categories/{category}', [HoroscopeDreamManagementController::class, 'updateCategory'])->name('categories.update');
            Route::delete('/categories/{category}', [HoroscopeDreamManagementController::class, 'destroyCategory'])->name('categories.destroy');

            // พจนานุกรม (สัญลักษณ์ฝัน)
            Route::get('/', [HoroscopeDreamManagementController::class, 'index'])->name('index');
            Route::get('/create', [HoroscopeDreamManagementController::class, 'create'])->name('create');
            Route::post('/', [HoroscopeDreamManagementController::class, 'store'])->name('store');
            Route::get('/{symbol}/edit', [HoroscopeDreamManagementController::class, 'edit'])->name('edit');
            Route::put('/{symbol}', [HoroscopeDreamManagementController::class, 'update'])->name('update');
            Route::delete('/{symbol}', [HoroscopeDreamManagementController::class, 'destroy'])->name('destroy');

            // ผลทำนายฝัน
            Route::get('/readings', [HoroscopeDreamManagementController::class, 'readings'])->name('readings');
            Route::get('/readings/{reading}', [HoroscopeDreamManagementController::class, 'showReading'])->name('readings.show');
            Route::delete('/readings/{reading}', [HoroscopeDreamManagementController::class, 'destroyReading'])->name('readings.destroy');
        });

        // Analytics
        Route::get('/analytics', [HoroscopeAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/analytics/data', [HoroscopeAnalyticsController::class, 'getData'])->name('analytics.data');
    });
});

// ========================================
// AI API KEYS MANAGEMENT
// ========================================
Route::prefix('ai-api-keys')->name('ai-api-keys.')->group(function () {
    // Dashboard
    Route::get('/', [AiApiKeyController::class, 'index'])->name('index');
    Route::get('/stats', [AiApiKeyController::class, 'stats'])->name('stats');
    Route::get('/logs', [AiApiKeyController::class, 'logs'])->name('logs');
    // 🆕 (2026-05-13) AI Usage Dashboard — กราฟเส้น token usage ย้อนหลัง
    Route::get('/usage-dashboard', [AiApiKeyController::class, 'usageDashboard'])->name('usage-dashboard');
    Route::get('/usage-dashboard/data', [AiApiKeyController::class, 'usageDashboardData'])->name('usage-dashboard.data');

    // Provider specific
    Route::get('/provider/{provider}', [AiApiKeyController::class, 'provider'])->name('provider');
    Route::get('/provider/{provider}/stats', [AiApiKeyController::class, 'stats'])->name('provider.stats');
    Route::put('/provider/{provider}/settings', [AiApiKeyController::class, 'updateSettings'])->name('provider.settings');

    // CRUD
    Route::get('/create/{provider?}', [AiApiKeyController::class, 'create'])->name('create');
    Route::post('/', [AiApiKeyController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [AiApiKeyController::class, 'edit'])->name('edit');
    Route::put('/{id}', [AiApiKeyController::class, 'update'])->name('update');
    Route::delete('/{id}', [AiApiKeyController::class, 'destroy'])->name('destroy');

    // Actions
    Route::post('/{id}/toggle', [AiApiKeyController::class, 'toggle'])->name('toggle');
    Route::post('/{id}/reset-errors', [AiApiKeyController::class, 'resetErrors'])->name('reset-errors');
    Route::post('/{id}/test', [AiApiKeyController::class, 'test'])->name('test');
    // 🔴 (2026-05-01) Clear critical state — admin ตรวจสอบแล้วและยืนยันให้ key กลับมาใช้
    Route::post('/{id}/clear-critical', [AiApiKeyController::class, 'clearCritical'])->name('clear-critical');

    // 🩺 (2026-05-07) Manual recheck — admin ลอง probe ทันที (ข้าม backoff)
    Route::post('/{id}/recheck-now', [AiApiKeyController::class, 'recheckNow'])->name('recheck-now');

    // Logs for specific key
    Route::get('/{id}/logs', [AiApiKeyController::class, 'logs'])->name('key.logs');
});

// ========================================
// SMS GATEWAY MANAGEMENT
// ========================================
Route::prefix('sms-gateway')->name('sms-gateway.')->group(function () {
    // แพ็กเกจราคา
    Route::get('/pricing', [SmsGatewayAdminController::class, 'pricing'])->name('pricing');
    Route::post('/pricing', [SmsGatewayAdminController::class, 'storePricing'])->name('pricing.store');
    Route::put('/pricing/{id}', [SmsGatewayAdminController::class, 'updatePricing'])->name('pricing.update');
    Route::delete('/pricing/{id}', [SmsGatewayAdminController::class, 'deletePricing'])->name('pricing.delete');

    // Subscriptions
    Route::get('/subscriptions', [SmsGatewayAdminController::class, 'subscriptions'])->name('subscriptions');
    Route::post('/subscriptions/{id}/toggle', [SmsGatewayAdminController::class, 'toggleSubscription'])->name('subscriptions.toggle');

    // ร้านค้าที่ใช้ระบบ
    Route::get('/stores', [SmsGatewayAdminController::class, 'stores'])->name('stores');

    // สถิติรายได้
    Route::get('/revenue', [SmsGatewayAdminController::class, 'revenue'])->name('revenue');
});

// ============================================
// ตลาดสดไทยพร๊อม (Fresh Market) 🏪
// ============================================
Route::prefix('fresh-market')->name('fresh-market.')->group(function () {
    // แดชบอร์ด
    Route::get('/', [FreshMarketController::class, 'dashboard'])->name('dashboard');

    // ตั้งค่า
    Route::get('/settings', [FreshMarketController::class, 'settings'])->name('settings');
    Route::put('/settings', [FreshMarketController::class, 'updateSettings'])->name('settings.update');

    // หมวดหมู่
    Route::get('/categories', [FreshMarketController::class, 'categories'])->name('categories');
    Route::post('/categories', [FreshMarketController::class, 'storeCategory'])->name('categories.store');
    Route::put('/categories/{category}', [FreshMarketController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [FreshMarketController::class, 'destroyCategory'])->name('categories.destroy');
    Route::post('/categories/reorder', [FreshMarketController::class, 'reorderCategories'])->name('categories.reorder');

    // ผู้ขาย
    Route::get('/sellers', [FreshMarketController::class, 'sellers'])->name('sellers');
    Route::get('/sellers/{seller}', [FreshMarketController::class, 'showSeller'])->name('sellers.show');
    Route::post('/sellers/{seller}/verify', [FreshMarketController::class, 'verifySeller'])->name('sellers.verify');
    Route::post('/sellers/{seller}/suspend', [FreshMarketController::class, 'suspendSeller'])->name('sellers.suspend');
    Route::post('/sellers/{seller}/activate', [FreshMarketController::class, 'activateSeller'])->name('sellers.activate');

    // สินค้า
    Route::get('/listings', [FreshMarketController::class, 'listings'])->name('listings');
    Route::get('/listings/{listing}', [FreshMarketController::class, 'showListing'])->name('listings.show');
    Route::post('/listings/{listing}/approve', [FreshMarketController::class, 'approveListing'])->name('listings.approve');
    Route::post('/listings/{listing}/suspend', [FreshMarketController::class, 'suspendListing'])->name('listings.suspend');

    // ออเดอร์
    Route::get('/orders', [FreshMarketController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [FreshMarketController::class, 'showOrder'])->name('orders.show');

    // คอมมิชชั่น & แคชแบ็ค
    Route::get('/commissions', [FreshMarketController::class, 'commissions'])->name('commissions');

    // ทดสอบ LINE Bot
    Route::get('/test-line', [FreshMarketController::class, 'testLine'])->name('test-line');
    Route::post('/test-line', [FreshMarketController::class, 'sendTestLine'])->name('test-line.send');
    Route::post('/verify-line', [FreshMarketController::class, 'verifyLineConnection'])->name('verify-line');
});
