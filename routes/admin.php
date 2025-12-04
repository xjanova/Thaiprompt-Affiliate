<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\InvestmentController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\UserGuideController;
use App\Http\Controllers\Admin\HeaderSettingsController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\CacheSettingsController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Admin\WalletSettingsController;
use App\Http\Controllers\Admin\CashbackSettingController;
use App\Http\Controllers\Admin\LanguageSettingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\NotificationManagementController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\VisualBuilderController;
use App\Http\Controllers\Admin\RankController;
use App\Http\Controllers\Admin\IdCardController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\MembershipRetentionController as AdminRetentionController;
use App\Http\Controllers\Admin\LineOaController;
use App\Http\Controllers\Admin\LineMessageAnalyticsController;
use App\Http\Controllers\Admin\LineBotAiController;
use App\Http\Controllers\Admin\LineRichMenuController;
use App\Http\Controllers\Admin\LineBroadcastController;
use App\Http\Controllers\Admin\LineSignupRewardController;
use App\Http\Controllers\Admin\LineRecruitmentController;
use App\Http\Controllers\Admin\OtpSettingsController;
use App\Http\Controllers\Admin\TwoFactorSettingsController;
use App\Http\Controllers\Admin\AiInstallationController;
use App\Http\Controllers\Admin\AiProviderManagementController;
use App\Http\Controllers\Admin\AiBotController;
use App\Http\Controllers\Admin\AiMonitoringController;
use App\Http\Controllers\Admin\KnowledgeBaseController;
use App\Http\Controllers\Admin\WebPManagementController;
use App\Http\Controllers\Admin\ECommerceController;
use App\Http\Controllers\Admin\FeaturedStoreController;
use App\Http\Controllers\Admin\KycController;
use App\Http\Controllers\Admin\MlmGlobalSettingController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\AcademySettingsController;
use App\Http\Controllers\Admin\CertificateManagementController;
use App\Http\Controllers\Admin\TrendManagementController;
use App\Http\Controllers\Admin\Accounting\AccountingDashboardController;
use App\Http\Controllers\Admin\Accounting\InvoiceController;
use App\Http\Controllers\Admin\Accounting\ExpenseController;
use App\Http\Controllers\Admin\Accounting\ContactController;
use App\Http\Controllers\Admin\HotelOwnerController;
use App\Http\Controllers\Admin\SuperAdminHotelController;
use App\Http\Controllers\Admin\Accounting\ProductController;
use App\Http\Controllers\Admin\Accounting\ReportController;
use App\Http\Controllers\Admin\Accounting\FlowAccountController;
use App\Http\Controllers\Admin\PosDashboardController;
use App\Http\Controllers\Admin\PosDeviceController;
use App\Http\Controllers\Admin\PosTransactionController;
use App\Http\Controllers\Admin\PosAdvertisementController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\AppSettingController;
use App\Http\Controllers\Admin\AppThemeSettingController;
use App\Http\Controllers\Admin\AppFeatureController;
use App\Http\Controllers\Admin\AppBannerController;
use App\Http\Controllers\Admin\AppMaintenanceController;
use App\Http\Controllers\Admin\AppControlSectionController;
use App\Http\Controllers\Admin\ComponentSettingController;
use App\Http\Controllers\Admin\PageBuilderController;
use App\Http\Controllers\Admin\PageBuilderSectionController;
use App\Http\Controllers\Admin\HomepageManagerController;
use App\Http\Controllers\Admin\SystemResetController;
use App\Http\Controllers\Admin\ApiEndpointController;
use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\NFCCardController;
use App\Http\Controllers\Admin\NFCReaderController;
use App\Http\Controllers\Admin\NFCTransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// Redirect /admin to /admin/dashboard
Route::redirect('/', '/dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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
});

// Unified Reports Center - ศูนย์รายงานรวม
Route::prefix('unified-reports')->name('unified-reports.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'index'])->name('index');

    // รายงานภาพรวมผู้บริหาร (Executive Summary)
    Route::get('/executive', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'executive'])->name('executive');

    // Business Intelligence Dashboard
    Route::get('/business-intelligence', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'businessIntelligence'])->name('business-intelligence');

    // รายงานแต่ละระบบ
    Route::get('/mlm', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'mlm'])->name('mlm');
    Route::get('/ecommerce', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'ecommerce'])->name('ecommerce');
    Route::get('/finance', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'finance'])->name('finance');
    Route::get('/ai-bot', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'aiBot'])->name('ai_bot');
    Route::get('/hotel', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'hotel'])->name('hotel');
    Route::get('/pos', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'pos'])->name('pos');
    Route::get('/crypto', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'crypto'])->name('crypto');
    Route::get('/hrm', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'hrm'])->name('hrm');
    Route::get('/learning', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'learning'])->name('learning');

    // API สำหรับแนวโน้มและการเปรียบเทียบ
    Route::get('/trends', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'trends'])->name('trends');
    Route::get('/compare', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'compare'])->name('compare');

    // Export รายงาน
    Route::get('/export/{type?}', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'export'])->name('export');
    Route::get('/export-csv/{type?}', [\App\Http\Controllers\Admin\UnifiedReportController::class, 'exportCsv'])->name('export-csv');
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
    Route::get('/', [\App\Http\Controllers\Admin\StakingPlanController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\StakingPlanController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\StakingPlanController::class, 'store'])->name('store');
    Route::get('/{stakingPlan}', [\App\Http\Controllers\Admin\StakingPlanController::class, 'show'])->name('show');
    Route::get('/{stakingPlan}/edit', [\App\Http\Controllers\Admin\StakingPlanController::class, 'edit'])->name('edit');
    Route::put('/{stakingPlan}', [\App\Http\Controllers\Admin\StakingPlanController::class, 'update'])->name('update');
    Route::delete('/{stakingPlan}', [\App\Http\Controllers\Admin\StakingPlanController::class, 'destroy'])->name('destroy');

    // การจัดการแผน
    Route::post('/{stakingPlan}/pause', [\App\Http\Controllers\Admin\StakingPlanController::class, 'pause'])->name('pause');
    Route::post('/{stakingPlan}/resume', [\App\Http\Controllers\Admin\StakingPlanController::class, 'resume'])->name('resume');
    Route::post('/{stakingPlan}/toggle-active', [\App\Http\Controllers\Admin\StakingPlanController::class, 'toggleActive'])->name('toggle-active');

    // ตั้งค่า Coin
    Route::get('/settings/coin', [\App\Http\Controllers\Admin\StakingPlanController::class, 'coinSettings'])->name('coin-settings');
    Route::put('/settings/coin', [\App\Http\Controllers\Admin\StakingPlanController::class, 'updateCoinSettings'])->name('coin-settings.update');

    // รายงาน Positions
    Route::get('/reports/positions', [\App\Http\Controllers\Admin\StakingPlanController::class, 'positions'])->name('positions');
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
    Route::get('/', [\App\Http\Controllers\Admin\DemoDataController::class, 'index'])->name('index');
    Route::post('/clean', [\App\Http\Controllers\Admin\DemoDataController::class, 'clean'])->name('clean');
    Route::get('/stats', [\App\Http\Controllers\Admin\DemoDataController::class, 'stats'])->name('stats');
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
    Route::get('/', [\App\Http\Controllers\Admin\WindowsUiController::class, 'index'])->name('index');
    Route::put('/', [\App\Http\Controllers\Admin\WindowsUiController::class, 'update'])->name('update');
    Route::put('/start-button-settings', [\App\Http\Controllers\Admin\WindowsUiController::class, 'updateStartButtonSettings'])->name('start-button-settings.update');
    Route::put('/menu-settings', [\App\Http\Controllers\Admin\WindowsUiController::class, 'updateMenuSettings'])->name('menu-settings.update');
    Route::put('/menu-rgb-settings', [\App\Http\Controllers\Admin\WindowsUiController::class, 'updateMenuRgbSettings'])->name('menu-rgb-settings.update');
    Route::get('/rgb-settings', [\App\Http\Controllers\Admin\WindowsUiController::class, 'rgbSettings'])->name('rgb-settings');
    Route::put('/rgb-settings', [\App\Http\Controllers\Admin\WindowsUiController::class, 'updateRgbSettings'])->name('rgb-settings.update');
});

// Classic X Theme Settings (WordPress-Inspired Premium Theme)
Route::prefix('classic-x-settings')->name('classic-x-settings.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ClassicXSettingsController::class, 'index'])->name('index');
    Route::put('/', [\App\Http\Controllers\Admin\ClassicXSettingsController::class, 'update'])->name('update');
    Route::post('/reset', [\App\Http\Controllers\Admin\ClassicXSettingsController::class, 'reset'])->name('reset');
    Route::get('/preview', [\App\Http\Controllers\Admin\ClassicXSettingsController::class, 'preview'])->name('preview');
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
        Route::get('/', [Admin\EmailCampaignController::class, 'index'])->name('index');
        Route::get('/create', [Admin\EmailCampaignController::class, 'create'])->name('create');
        Route::post('/', [Admin\EmailCampaignController::class, 'store'])->name('store');
        Route::get('/{campaign}', [Admin\EmailCampaignController::class, 'show'])->name('show');
        Route::get('/{campaign}/edit', [Admin\EmailCampaignController::class, 'edit'])->name('edit');
        Route::put('/{campaign}', [Admin\EmailCampaignController::class, 'update'])->name('update');
        Route::delete('/{campaign}', [Admin\EmailCampaignController::class, 'destroy'])->name('destroy');

        // Campaign Actions
        Route::post('/{campaign}/start', [Admin\EmailCampaignController::class, 'start'])->name('start');
        Route::post('/{campaign}/pause', [Admin\EmailCampaignController::class, 'pause'])->name('pause');
        Route::post('/{campaign}/cancel', [Admin\EmailCampaignController::class, 'cancel'])->name('cancel');
    });

    // Email Queue (⭐ NEW)
    Route::prefix('queue')->name('queue.')->group(function () {
        Route::get('/', [Admin\EmailQueueController::class, 'index'])->name('index');
        Route::get('/{recipient}', [Admin\EmailQueueController::class, 'show'])->name('show');
        Route::post('/{recipient}/retry', [Admin\EmailQueueController::class, 'retry'])->name('retry');
        Route::post('/campaign/{campaign}/retry-all', [Admin\EmailQueueController::class, 'retryAll'])->name('retry-all');
        Route::delete('/failed/clear', [Admin\EmailQueueController::class, 'clearFailed'])->name('clear-failed');
    });

    // Email Analytics (⭐ NEW)
    Route::get('/analytics', [Admin\EmailAnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/export', [Admin\EmailAnalyticsController::class, 'export'])->name('analytics.export');

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

// Membership Retention Management
Route::prefix('retention')->name('retention.')->group(function () {
    Route::get('/', [AdminRetentionController::class, 'index'])->name('index');
    Route::get('/users', [AdminRetentionController::class, 'users'])->name('users');
    Route::get('/users/{userId}', [AdminRetentionController::class, 'showUser'])->name('users.show');

    // Settings
    Route::put('/settings', [AdminRetentionController::class, 'updateSettings'])->name('settings.update');

    // Manual Operations
    Route::post('/initialize-user', [AdminRetentionController::class, 'initializeUser'])->name('initialize-user');
    Route::post('/process-renewal', [AdminRetentionController::class, 'processRenewal'])->name('process-renewal');
    Route::post('/expire-users', [AdminRetentionController::class, 'expireUsers'])->name('expire-users');

    // Reports
    Route::get('/expiring-users', [AdminRetentionController::class, 'getExpiringUsers'])->name('expiring-users');
    Route::get('/export-report', [AdminRetentionController::class, 'exportReport'])->name('export-report');
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

    // Analytics Dashboard (Legacy)
    Route::get('/analytics', [\App\Http\Controllers\Admin\LineAnalyticsController::class, 'index'])->name('analytics');
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
        Route::get('/{id}/edit', [LineBotAiController::class, 'edit'])->name('edit');
        Route::put('/{id}', [LineBotAiController::class, 'update'])->name('update');
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

    // Signup Flow Management
    Route::prefix('signup-flow')->name('signup-flow.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\LineSignupFlowController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\LineSignupFlowController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\LineSignupFlowController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\Admin\LineSignupFlowController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\Admin\LineSignupFlowController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\LineSignupFlowController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [\App\Http\Controllers\Admin\LineSignupFlowController::class, 'reorder'])->name('reorder');
    });

    // Hybrid Bot Keywords Management
    Route::prefix('keywords')->name('keywords.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\LineBotKeywordController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\LineBotKeywordController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\LineBotKeywordController::class, 'store'])->name('store');
        Route::get('/{keyword}/edit', [\App\Http\Controllers\Admin\LineBotKeywordController::class, 'edit'])->name('edit');
        Route::put('/{keyword}', [\App\Http\Controllers\Admin\LineBotKeywordController::class, 'update'])->name('update');
        Route::delete('/{keyword}', [\App\Http\Controllers\Admin\LineBotKeywordController::class, 'destroy'])->name('destroy');
        Route::post('/test', [\App\Http\Controllers\Admin\LineBotKeywordController::class, 'test'])->name('test');

        // Analytics & Advanced Features
        Route::get('/analytics/dashboard', [\App\Http\Controllers\Admin\LineBotKeywordAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/analytics/export', [\App\Http\Controllers\Admin\LineBotKeywordAnalyticsController::class, 'export'])->name('export');
        Route::post('/analytics/import', [\App\Http\Controllers\Admin\LineBotKeywordAnalyticsController::class, 'import'])->name('import');
        Route::post('/{keyword}/clone', [\App\Http\Controllers\Admin\LineBotKeywordAnalyticsController::class, 'clone'])->name('clone');
        Route::post('/analytics/bulk-update-status', [\App\Http\Controllers\Admin\LineBotKeywordAnalyticsController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
        Route::post('/analytics/bulk-delete', [\App\Http\Controllers\Admin\LineBotKeywordAnalyticsController::class, 'bulkDelete'])->name('bulk-delete');

        // Activity Logs & Monitoring
        Route::prefix('activity')->name('activity.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\KeywordActivityLogController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Admin\KeywordActivityLogController::class, 'export'])->name('export');
            Route::get('/daily-chart', [\App\Http\Controllers\Admin\KeywordActivityLogController::class, 'getDailyActivityChart'])->name('daily-chart');
            Route::get('/keyword-stats', [\App\Http\Controllers\Admin\KeywordActivityLogController::class, 'getKeywordStats'])->name('keyword-stats');
            Route::get('/user-history', [\App\Http\Controllers\Admin\KeywordActivityLogController::class, 'getUserHistory'])->name('user-history');
            Route::post('/clear-old-logs', [\App\Http\Controllers\Admin\KeywordActivityLogController::class, 'clearOldLogs'])->name('clear-old-logs');
        });

        // Performance Dashboard & Analytics
        Route::prefix('performance')->name('performance.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\KeywordPerformanceDashboardController::class, 'index'])->name('index');
            Route::get('/chart-data', [\App\Http\Controllers\Admin\KeywordPerformanceDashboardController::class, 'getChartData'])->name('chart-data');
            Route::get('/comparison-data', [\App\Http\Controllers\Admin\KeywordPerformanceDashboardController::class, 'getComparisonData'])->name('comparison-data');
            Route::get('/response-time-data', [\App\Http\Controllers\Admin\KeywordPerformanceDashboardController::class, 'getResponseTimeData'])->name('response-time-data');
            Route::get('/trend-data', [\App\Http\Controllers\Admin\KeywordPerformanceDashboardController::class, 'getTrendData'])->name('trend-data');
            Route::get('/{keyword}/details', [\App\Http\Controllers\Admin\KeywordPerformanceDashboardController::class, 'getKeywordDetails'])->name('details');
            Route::get('/export', [\App\Http\Controllers\Admin\KeywordPerformanceDashboardController::class, 'exportReport'])->name('export');
        });

        // Keyword Suggestions Engine
        Route::prefix('suggestions')->name('suggestions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\KeywordSuggestionController::class, 'index'])->name('index');
            Route::get('/json', [\App\Http\Controllers\Admin\KeywordSuggestionController::class, 'getSuggestionsJson'])->name('json');
            Route::get('/stats', [\App\Http\Controllers\Admin\KeywordSuggestionController::class, 'getStatistics'])->name('stats');
            Route::get('/recommendations', [\App\Http\Controllers\Admin\KeywordSuggestionController::class, 'getRecommendations'])->name('recommendations');
            Route::post('/preview', [\App\Http\Controllers\Admin\KeywordSuggestionController::class, 'preview'])->name('preview');
            Route::post('/approve', [\App\Http\Controllers\Admin\KeywordSuggestionController::class, 'approve'])->name('approve');
            Route::post('/approve-batch', [\App\Http\Controllers\Admin\KeywordSuggestionController::class, 'approveBatch'])->name('approve-batch');
            Route::post('/reject', [\App\Http\Controllers\Admin\KeywordSuggestionController::class, 'reject'])->name('reject');
            Route::get('/detail', [\App\Http\Controllers\Admin\KeywordSuggestionController::class, 'getDetail'])->name('detail');
            Route::get('/refresh', [\App\Http\Controllers\Admin\KeywordSuggestionController::class, 'refresh'])->name('refresh');
            Route::get('/export', [\App\Http\Controllers\Admin\KeywordSuggestionController::class, 'export'])->name('export');
        });

        // A/B Testing System
        Route::prefix('ab-tests')->name('ab-tests.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'store'])->name('store');
            Route::get('/{test}', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'show'])->name('show');
            Route::get('/{test}/edit', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'edit'])->name('edit');
            Route::put('/{test}', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'update'])->name('update');
            Route::post('/{test}/start', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'start'])->name('start');
            Route::post('/{test}/pause', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'pause'])->name('pause');
            Route::post('/{test}/complete', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'complete'])->name('complete');
            Route::post('/{test}/apply-winner', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'applyWinner'])->name('apply-winner');
            Route::delete('/{test}', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'destroy'])->name('destroy');
            Route::get('/api/list', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'listJson'])->name('list-json');
            Route::get('/api/statistics', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'statistics'])->name('statistics');
            Route::get('/api/recommendations', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'recommendations'])->name('recommendations');
            Route::get('/{test}/chart-data', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'chartData'])->name('chart-data');
            Route::get('/{test}/timeline-data', [\App\Http\Controllers\Admin\KeywordABTestController::class, 'timelineData'])->name('timeline-data');
        });

        // Sentiment Analysis System
        Route::prefix('sentiment-analysis')->name('sentiment-analysis.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SentimentAnalysisController::class, 'index'])->name('index');
            Route::get('/{sentiment}', [\App\Http\Controllers\Admin\SentimentAnalysisController::class, 'show'])->name('show');
            Route::delete('/{sentiment}', [\App\Http\Controllers\Admin\SentimentAnalysisController::class, 'destroy'])->name('destroy');
            Route::get('/api/list', [\App\Http\Controllers\Admin\SentimentAnalysisController::class, 'listJson'])->name('list-json');
            Route::get('/api/statistics', [\App\Http\Controllers\Admin\SentimentAnalysisController::class, 'statistics'])->name('statistics');
            Route::get('/api/trend-data', [\App\Http\Controllers\Admin\SentimentAnalysisController::class, 'trendData'])->name('trend-data');
            Route::get('/api/pain-points', [\App\Http\Controllers\Admin\SentimentAnalysisController::class, 'painPointsData'])->name('pain-points');
            Route::get('/api/emotions', [\App\Http\Controllers\Admin\SentimentAnalysisController::class, 'emotionData'])->name('emotions');
            Route::get('/api/recommendations', [\App\Http\Controllers\Admin\SentimentAnalysisController::class, 'recommendations'])->name('recommendations');
            Route::get('/api/top-complaints', [\App\Http\Controllers\Admin\SentimentAnalysisController::class, 'topComplaints'])->name('top-complaints');
            Route::get('/api/urgent-issues', [\App\Http\Controllers\Admin\SentimentAnalysisController::class, 'urgentIssues'])->name('urgent-issues');
            Route::get('/api/export-report', [\App\Http\Controllers\Admin\SentimentAnalysisController::class, 'exportReport'])->name('export-report');
        });

        // NLP Enhancement System (Entity Extraction, Intent Recognition, Clustering - Phase 2.4)
        Route::prefix('nlp-analysis')->name('nlp-analysis.')->group(function () {
            // Dashboard
            Route::get('/', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'index'])->name('index');

            // Entities Management
            Route::get('/entities', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'entities'])->name('entities');
            Route::delete('/entities/{entity}', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'deleteEntity'])->name('delete-entity');

            // Intents Management
            Route::get('/intents', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'intents'])->name('intents');
            Route::delete('/intents/{intent}', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'deleteIntent'])->name('delete-intent');

            // Clusters Management
            Route::get('/clusters', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'clusters'])->name('clusters');
            Route::get('/clusters/{cluster}', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'showCluster'])->name('show-cluster');
            Route::post('/clusters', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'createCluster'])->name('create-cluster');
            Route::put('/clusters/{cluster}', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'updateCluster'])->name('update-cluster');
            Route::delete('/clusters/{cluster}', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'deleteCluster'])->name('delete-cluster');

            // API Endpoints for Data & Analytics
            Route::get('/api/entity-statistics', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'entityStatistics'])->name('entity-statistics');
            Route::get('/api/intent-statistics', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'intentStatistics'])->name('intent-statistics');
            Route::get('/api/cluster-usage', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'clusterUsageData'])->name('cluster-usage');
            Route::get('/api/cluster-recommendations', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'clusterRecommendations'])->name('cluster-recommendations');
            Route::get('/api/related-keywords', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'relatedKeywords'])->name('related-keywords');
            Route::get('/api/entity-cooccurrence', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'entityCoOccurrence'])->name('entity-cooccurrence');
            Route::get('/api/export-report', [\App\Http\Controllers\Admin\NLPAnalysisController::class, 'exportReport'])->name('export-report');
        });

        // Advanced NLP System (Context, Conversation History, Semantic Matching - Phase 2.5)
        Route::prefix('advanced-nlp')->name('advanced-nlp.')->group(function () {
            Route::get('/conversations', [\App\Http\Controllers\Admin\AdvancedNLPController::class, 'conversations'])->name('conversations');
            Route::get('/api/conversation-analytics/{userId}', [\App\Http\Controllers\Admin\AdvancedNLPController::class, 'conversationAnalytics'])->name('conversation-analytics');
            Route::get('/api/similar-messages', [\App\Http\Controllers\Admin\AdvancedNLPController::class, 'similarMessages'])->name('similar-messages');
        });

        // Advanced Analytics System (Prediction, Anomaly Detection, Forecasting - Phase 3)
        Route::prefix('advanced-analytics')->name('advanced-analytics.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'dashboard'])->name('dashboard');
            Route::get('/predictions', [\App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'predictions'])->name('predictions');
            Route::get('/anomalies', [\App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'anomalies'])->name('anomalies');
            Route::get('/forecasts', [\App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'forecasts'])->name('forecasts');
            Route::get('/insights', [\App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'insights'])->name('insights');
            Route::get('/api/forecast-data', [\App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'forecastData'])->name('forecast-data');
            Route::get('/api/anomaly-data', [\App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'anomalyData'])->name('anomaly-data');
            Route::get('/api/prediction-data', [\App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'predictionData'])->name('prediction-data');
        });
    });
});

// LINE Membership Signup Management (AI-Powered Signup System)
Route::prefix('line-membership-signup')->name('line-membership-signup.')->group(function () {
    // Dashboard
    Route::get('/', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'index'])->name('index');

    // Settings Management
    Route::get('/settings', [\App\Http\Controllers\Admin\LineSignupSettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [\App\Http\Controllers\Admin\LineSignupSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/reset', [\App\Http\Controllers\Admin\LineSignupSettingsController::class, 'reset'])->name('settings.reset');
    Route::post('/settings/test-connection', [\App\Http\Controllers\Admin\LineSignupSettingsController::class, 'testConnection'])->name('settings.test-connection');

    // Sessions Management
    Route::get('/sessions', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'sessions'])->name('sessions');
    Route::get('/sessions/{session}', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'showSession'])->name('sessions.show');

    // Templates Management
    Route::get('/templates', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'templates'])->name('templates');
    Route::get('/templates/create', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'create'])->name('templates.create');
    Route::post('/templates', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}/edit', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{template}', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'update'])->name('templates.update');
    Route::post('/templates/{template}/reset', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'resetTemplate'])->name('templates.reset');
    Route::post('/templates/{template}/duplicate', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'duplicateTemplate'])->name('templates.duplicate');
    Route::delete('/templates/{template}', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'deleteTemplate'])->name('templates.delete');

    // Invitations Management
    Route::get('/invitations', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'invitations'])->name('invitations');

    // Rewards Management (Full CRUD)
    Route::prefix('rewards')->name('rewards.')->group(function () {
        Route::get('/', [LineSignupRewardController::class, 'index'])->name('index');
        Route::get('/create', [LineSignupRewardController::class, 'create'])->name('create');
        Route::post('/', [LineSignupRewardController::class, 'store'])->name('store');
        Route::get('/{reward}', [LineSignupRewardController::class, 'show'])->name('show');
        Route::get('/{reward}/edit', [LineSignupRewardController::class, 'edit'])->name('edit');
        Route::put('/{reward}', [LineSignupRewardController::class, 'update'])->name('update');
        Route::delete('/{reward}', [LineSignupRewardController::class, 'destroy'])->name('destroy');
        Route::post('/{reward}/toggle-active', [LineSignupRewardController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/update-order', [LineSignupRewardController::class, 'updateOrder'])->name('update-order');
        Route::get('/statistics/overview', [LineSignupRewardController::class, 'statistics'])->name('statistics');
    });

    // Analytics API
    Route::get('/analytics/data', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'analyticsData'])->name('analytics.data');

    // Export
    Route::get('/export/sessions', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'exportSessions'])->name('export.sessions');

    // LINE Connections Management (ผู้ใช้ที่เชื่อมต่อ LINE)
    Route::prefix('connections')->name('connections.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\LineConnectionsController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\Admin\LineConnectionsController::class, 'export'])->name('export');
        Route::get('/{user}', [\App\Http\Controllers\Admin\LineConnectionsController::class, 'show'])->name('show');
        Route::post('/{user}/disconnect', [\App\Http\Controllers\Admin\LineConnectionsController::class, 'disconnect'])->name('disconnect');
        Route::post('/bulk-disconnect', [\App\Http\Controllers\Admin\LineConnectionsController::class, 'bulkDisconnect'])->name('bulk-disconnect');
    });
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
    Route::get('/', [\App\Http\Controllers\Admin\MlmProspectController::class, 'index'])->name('index');
    Route::get('/{id}', [\App\Http\Controllers\Admin\MlmProspectController::class, 'show'])->name('show');
    Route::post('/expire-old', [\App\Http\Controllers\Admin\MlmProspectController::class, 'expireOld'])->name('expire-old');
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
    Route::get('/dashboard', [\App\Http\Controllers\Admin\AICoreController::class, 'dashboard'])->name('dashboard');

    // Features Management
    Route::prefix('features')->name('features.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AICoreFeatureController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\AICoreFeatureController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\AICoreFeatureController::class, 'store'])->name('store');
        Route::get('/{feature}', [\App\Http\Controllers\Admin\AICoreFeatureController::class, 'show'])->name('show');
        Route::get('/{feature}/edit', [\App\Http\Controllers\Admin\AICoreFeatureController::class, 'edit'])->name('edit');
        Route::put('/{feature}', [\App\Http\Controllers\Admin\AICoreFeatureController::class, 'update'])->name('update');
        Route::delete('/{feature}', [\App\Http\Controllers\Admin\AICoreFeatureController::class, 'destroy'])->name('destroy');
        Route::post('/{feature}/toggle', [\App\Http\Controllers\Admin\AICoreFeatureController::class, 'toggle'])->name('toggle');
    });

    // Tenants Management
    Route::prefix('tenants')->name('tenants.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AICoreTenantController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\AICoreTenantController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\AICoreTenantController::class, 'store'])->name('store');
        Route::get('/{tenant}', [\App\Http\Controllers\Admin\AICoreTenantController::class, 'show'])->name('show');
        Route::get('/{tenant}/edit', [\App\Http\Controllers\Admin\AICoreTenantController::class, 'edit'])->name('edit');
        Route::put('/{tenant}', [\App\Http\Controllers\Admin\AICoreTenantController::class, 'update'])->name('update');
        Route::delete('/{tenant}', [\App\Http\Controllers\Admin\AICoreTenantController::class, 'destroy'])->name('destroy');
        Route::post('/{tenant}/toggle', [\App\Http\Controllers\Admin\AICoreTenantController::class, 'toggle'])->name('toggle');

        // Feature Access for Tenant
        Route::get('/{tenant}/features', [\App\Http\Controllers\Admin\AICoreTenantController::class, 'features'])->name('features');
        Route::post('/{tenant}/features/{feature}/enable', [\App\Http\Controllers\Admin\AICoreTenantController::class, 'enableFeature'])->name('features.enable');
        Route::post('/{tenant}/features/{feature}/disable', [\App\Http\Controllers\Admin\AICoreTenantController::class, 'disableFeature'])->name('features.disable');
    });

    // Quotas Management
    Route::prefix('quotas')->name('quotas.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AICoreQuotaController::class, 'index'])->name('index');
        Route::get('/{tenant}/{feature}', [\App\Http\Controllers\Admin\AICoreQuotaController::class, 'manage'])->name('manage');
        Route::post('/{tenant}/{feature}/add-bonus', [\App\Http\Controllers\Admin\AICoreQuotaController::class, 'addBonus'])->name('add-bonus');
        Route::post('/{tenant}/{feature}/reset', [\App\Http\Controllers\Admin\AICoreQuotaController::class, 'reset'])->name('reset');
        Route::post('/reset-all-expired', [\App\Http\Controllers\Admin\AICoreQuotaController::class, 'resetAllExpired'])->name('reset-all-expired');
    });

    // Schedules Management
    Route::prefix('schedules')->name('schedules.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AICoreScheduleController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\AICoreScheduleController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\AICoreScheduleController::class, 'store'])->name('store');
        Route::get('/{schedule}', [\App\Http\Controllers\Admin\AICoreScheduleController::class, 'show'])->name('show');
        Route::get('/{schedule}/edit', [\App\Http\Controllers\Admin\AICoreScheduleController::class, 'edit'])->name('edit');
        Route::put('/{schedule}', [\App\Http\Controllers\Admin\AICoreScheduleController::class, 'update'])->name('update');
        Route::delete('/{schedule}', [\App\Http\Controllers\Admin\AICoreScheduleController::class, 'destroy'])->name('destroy');
        Route::post('/{schedule}/toggle', [\App\Http\Controllers\Admin\AICoreScheduleController::class, 'toggle'])->name('toggle');
        Route::post('/{schedule}/execute', [\App\Http\Controllers\Admin\AICoreScheduleController::class, 'execute'])->name('execute');
    });

    // Alerts Management
    Route::prefix('alerts')->name('alerts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AICoreAlertController::class, 'index'])->name('index');
        Route::get('/{alert}', [\App\Http\Controllers\Admin\AICoreAlertController::class, 'show'])->name('show');
        Route::post('/{alert}/read', [\App\Http\Controllers\Admin\AICoreAlertController::class, 'markAsRead'])->name('read');
        Route::post('/{alert}/acknowledge', [\App\Http\Controllers\Admin\AICoreAlertController::class, 'acknowledge'])->name('acknowledge');
        Route::post('/{alert}/resolve', [\App\Http\Controllers\Admin\AICoreAlertController::class, 'resolve'])->name('resolve');
        Route::post('/{alert}/dismiss', [\App\Http\Controllers\Admin\AICoreAlertController::class, 'dismiss'])->name('dismiss');
        Route::post('/mark-all-read', [\App\Http\Controllers\Admin\AICoreAlertController::class, 'markAllAsRead'])->name('mark-all-read');
    });

    // Usage Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AICoreAnalyticsController::class, 'index'])->name('index');
        Route::get('/feature/{feature}', [\App\Http\Controllers\Admin\AICoreAnalyticsController::class, 'featureUsage'])->name('feature');
        Route::get('/tenant/{tenant}', [\App\Http\Controllers\Admin\AICoreAnalyticsController::class, 'tenantUsage'])->name('tenant');
        Route::get('/export', [\App\Http\Controllers\Admin\AICoreAnalyticsController::class, 'export'])->name('export');
    });

    // Global Settings
    Route::get('/settings', [\App\Http\Controllers\Admin\AICoreController::class, 'settings'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\Admin\AICoreController::class, 'updateSettings'])->name('settings.update');
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
    Route::get('/', [\App\Http\Controllers\Admin\LearningCenterController::class, 'index'])->name('index');
    Route::get('/category/{slug}', [\App\Http\Controllers\Admin\LearningCenterController::class, 'category'])->name('category');
    Route::get('/article/{slug}', [\App\Http\Controllers\Admin\LearningCenterController::class, 'article'])->name('article');
    Route::post('/article/{slug}/complete', [\App\Http\Controllers\Admin\LearningCenterController::class, 'complete'])->name('article.complete');
    Route::post('/article/{slug}/progress', [\App\Http\Controllers\Admin\LearningCenterController::class, 'updateProgress'])->name('article.progress');
    Route::get('/article/{slug}/check-access', [\App\Http\Controllers\Admin\LearningCenterController::class, 'checkAccess'])->name('article.check-access');
    Route::get('/my-stats', [\App\Http\Controllers\Admin\LearningCenterController::class, 'getMyStats'])->name('my-stats');
});

// Instructor Dashboard - For Course Instructors (แยกจาก Admin)
Route::prefix('instructor')->name('instructor.')->group(function () {
    // Dashboard หลัก
    Route::get('/', [\App\Http\Controllers\Instructor\InstructorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [\App\Http\Controllers\Instructor\InstructorDashboardController::class, 'index'])->name('index');

    // จัดการคอร์ส
    Route::prefix('courses')->name('courses.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Instructor\InstructorDashboardController::class, 'courses'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Instructor\InstructorDashboardController::class, 'createCourse'])->name('create');
        Route::post('/', [\App\Http\Controllers\Instructor\InstructorDashboardController::class, 'storeCourse'])->name('store');
        Route::get('/{article}/edit', [\App\Http\Controllers\Instructor\InstructorDashboardController::class, 'editCourse'])->name('edit');
        Route::put('/{article}', [\App\Http\Controllers\Instructor\InstructorDashboardController::class, 'updateCourse'])->name('update');
        Route::get('/{article}/stats', [\App\Http\Controllers\Instructor\InstructorDashboardController::class, 'courseStats'])->name('stats');
        Route::get('/{article}/quiz', [\App\Http\Controllers\Instructor\InstructorDashboardController::class, 'manageQuiz'])->name('quiz');
        Route::post('/{article}/submit-approval', [\App\Http\Controllers\Instructor\InstructorDashboardController::class, 'submitForApproval'])->name('submit-approval');
    });

    // รายได้และสถิติ
    Route::get('/earnings', [\App\Http\Controllers\Instructor\InstructorDashboardController::class, 'earnings'])->name('earnings');
});

// Quiz - Student View
Route::prefix('quiz')->name('quiz.')->group(function () {
    Route::get('/{id}', [\App\Http\Controllers\Admin\QuizController::class, 'show'])->name('show');
    Route::post('/{id}/submit', [\App\Http\Controllers\Admin\QuizController::class, 'submit'])->name('submit');
    Route::get('/results/{attemptId}', [\App\Http\Controllers\Admin\QuizController::class, 'results'])->name('results');
    Route::get('/article/{articleSlug}', [\App\Http\Controllers\Admin\QuizController::class, 'index'])->name('index');
});

// Quiz Management - Instructor/Admin
Route::prefix('quiz-management')->name('quiz-management.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\QuizManagementController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\QuizManagementController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\QuizManagementController::class, 'store'])->name('store');
    Route::get('/{id}', [\App\Http\Controllers\Admin\QuizManagementController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\App\Http\Controllers\Admin\QuizManagementController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\Admin\QuizManagementController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\Admin\QuizManagementController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/attempts', [\App\Http\Controllers\Admin\QuizManagementController::class, 'attempts'])->name('attempts');
});

// Certificates - User Certificates
Route::prefix('certificates')->name('certificates.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\CertificateController::class, 'index'])->name('index');
    Route::post('/generate/{articleId}', [\App\Http\Controllers\Admin\CertificateController::class, 'generate'])->name('generate');
    Route::get('/{id}', [\App\Http\Controllers\Admin\CertificateController::class, 'show'])->name('show');
    Route::get('/{id}/download', [\App\Http\Controllers\Admin\CertificateController::class, 'download'])->name('download');
});

// Article Management - Admin Only
Route::prefix('articles')->name('articles.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ArticleManagementController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\ArticleManagementController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\ArticleManagementController::class, 'store'])->name('store');
    Route::get('/{article}/edit', [\App\Http\Controllers\Admin\ArticleManagementController::class, 'edit'])->name('edit');
    Route::put('/{article}', [\App\Http\Controllers\Admin\ArticleManagementController::class, 'update'])->name('update');
    Route::delete('/{article}', [\App\Http\Controllers\Admin\ArticleManagementController::class, 'destroy'])->name('destroy');
    Route::get('/{article}/permissions', [\App\Http\Controllers\Admin\ArticleManagementController::class, 'permissions'])->name('permissions');
    Route::put('/{article}/permissions', [\App\Http\Controllers\Admin\ArticleManagementController::class, 'updatePermissions'])->name('permissions.update');
});

// Category Management - Admin Only
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\CategoryManagementController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\CategoryManagementController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\CategoryManagementController::class, 'store'])->name('store');
    Route::get('/{category}/edit', [\App\Http\Controllers\Admin\CategoryManagementController::class, 'edit'])->name('edit');
    Route::put('/{category}', [\App\Http\Controllers\Admin\CategoryManagementController::class, 'update'])->name('update');
    Route::delete('/{category}', [\App\Http\Controllers\Admin\CategoryManagementController::class, 'destroy'])->name('destroy');
    Route::post('/reorder', [\App\Http\Controllers\Admin\CategoryManagementController::class, 'reorder'])->name('reorder');
});

// AI Provider Management
Route::prefix('ai-providers')->name('ai-providers.')->group(function () {
    Route::get('/', [AiProviderManagementController::class, 'index'])->name('index');
    Route::post('/{id}/toggle', [AiProviderManagementController::class, 'toggleProvider'])->name('toggle');
    Route::match(['post', 'put'], '/{id}/config', [AiProviderManagementController::class, 'updateConfig'])->name('config');
    Route::post('/{id}/test', [AiProviderManagementController::class, 'testConnection'])->name('test');
    Route::get('/{id}/models', [AiProviderManagementController::class, 'getProviderModels'])->name('models');
    Route::post('/models/{id}/toggle', [AiProviderManagementController::class, 'toggleModel'])->name('models.toggle');

    // Local AI Control
    Route::post('/local/start', [AiProviderManagementController::class, 'startLocalAi'])->name('local.start');
    Route::post('/local/stop', [AiProviderManagementController::class, 'stopLocalAi'])->name('local.stop');
    Route::post('/local/restart', [AiProviderManagementController::class, 'restartLocalAi'])->name('local.restart');
    Route::get('/local/status', [AiProviderManagementController::class, 'getLocalAiStatus'])->name('local.status');
    Route::post('/local/load-model', [AiProviderManagementController::class, 'loadModel'])->name('local.load-model');
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

    // Orders Management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [ECommerceController::class, 'orders'])->name('index');
        Route::get('/{order}', [ECommerceController::class, 'showOrder'])->name('show');
        Route::post('/{order}/status', [ECommerceController::class, 'updateOrderStatus'])->name('status.update');
        Route::post('/{order}/payment-status', [ECommerceController::class, 'updatePaymentStatus'])->name('payment-status.update');
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
    Route::get('/', [\App\Http\Controllers\Admin\StorefrontSettingsController::class, 'index'])->name('index');

    // Theme Settings
    Route::put('/theme', [\App\Http\Controllers\Admin\StorefrontSettingsController::class, 'updateTheme'])->name('update-theme');

    // Layout Settings
    Route::put('/layout', [\App\Http\Controllers\Admin\StorefrontSettingsController::class, 'updateLayout'])->name('update-layout');

    // Banners Management
    Route::prefix('banners')->name('banners.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\StorefrontSettingsController::class, 'banners'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\StorefrontSettingsController::class, 'createBanner'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\StorefrontSettingsController::class, 'storeBanner'])->name('store');
        Route::get('/{banner}/edit', [\App\Http\Controllers\Admin\StorefrontSettingsController::class, 'editBanner'])->name('edit');
        Route::put('/{banner}', [\App\Http\Controllers\Admin\StorefrontSettingsController::class, 'updateBanner'])->name('update');
        Route::delete('/{banner}', [\App\Http\Controllers\Admin\StorefrontSettingsController::class, 'destroyBanner'])->name('destroy');
        Route::post('/reorder', [\App\Http\Controllers\Admin\StorefrontSettingsController::class, 'reorderBanners'])->name('reorder');
        Route::post('/{banner}/toggle', [\App\Http\Controllers\Admin\StorefrontSettingsController::class, 'toggleBannerStatus'])->name('toggle');
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
        Route::get('/', [\App\Http\Controllers\Admin\MlmPlanController::class, 'index'])->name('index');
        // Note: ปิดการใช้งาน create, store, edit, update, destroy เพราะใช้แผน Global
    });

    // MLM Members
    Route::prefix('members')->name('members.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\MlmMemberController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\MlmMemberController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\MlmMemberController::class, 'store'])->name('store');
        Route::get('/{member}', [\App\Http\Controllers\Admin\MlmMemberController::class, 'show'])->name('show');
        Route::post('/{member}/status', [\App\Http\Controllers\Admin\MlmMemberController::class, 'updateStatus'])->name('status');
        Route::post('/{member}/toggle-qualification', [\App\Http\Controllers\Admin\MlmMemberController::class, 'toggleQualification'])->name('toggle-qualification');
        Route::get('/{member}/genealogy', [\App\Http\Controllers\Admin\MlmMemberController::class, 'genealogy'])->name('genealogy');
        Route::get('/{member}/tree-data', [\App\Http\Controllers\Admin\MlmMemberController::class, 'getTreeData'])->name('tree-data');
        Route::get('/{member}/bloodline-data', [\App\Http\Controllers\Admin\MlmMemberController::class, 'getBloodlineData'])->name('bloodline-data');
        Route::get('/{member}/statistics', [\App\Http\Controllers\Admin\MlmMemberController::class, 'statistics'])->name('statistics');
    });

    // MLM Commissions
    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\MlmCommissionController::class, 'index'])->name('index');
        Route::get('/{commission}', [\App\Http\Controllers\Admin\MlmCommissionController::class, 'show'])->name('show');
        Route::post('/approve', [\App\Http\Controllers\Admin\MlmCommissionController::class, 'approve'])->name('approve');
        Route::post('/approve-all', [\App\Http\Controllers\Admin\MlmCommissionController::class, 'approveAll'])->name('approve-all');
        Route::post('/{commission}/reject', [\App\Http\Controllers\Admin\MlmCommissionController::class, 'reject'])->name('reject');
        Route::post('/pay', [\App\Http\Controllers\Admin\MlmCommissionController::class, 'pay'])->name('pay');
        Route::post('/pay-all', [\App\Http\Controllers\Admin\MlmCommissionController::class, 'payAll'])->name('pay-all');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\MlmCommissionController::class, 'bulkAction'])->name('bulk-action');
    });

    // Product PV Management
    Route::prefix('product-pv')->name('product-pv.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\MlmProductPvController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\MlmProductPvController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\MlmProductPvController::class, 'store'])->name('store');
        Route::get('/{productPv}/edit', [\App\Http\Controllers\Admin\MlmProductPvController::class, 'edit'])->name('edit');
        Route::put('/{productPv}', [\App\Http\Controllers\Admin\MlmProductPvController::class, 'update'])->name('update');
        Route::delete('/{productPv}', [\App\Http\Controllers\Admin\MlmProductPvController::class, 'destroy'])->name('destroy');
        Route::get('/products/{product}/preview', [\App\Http\Controllers\Admin\MlmProductPvController::class, 'preview'])->name('preview');
        Route::post('/bulk-create', [\App\Http\Controllers\Admin\MlmProductPvController::class, 'bulkCreate'])->name('bulk-create');
        Route::post('/bulk-update', [\App\Http\Controllers\Admin\MlmProductPvController::class, 'bulkUpdate'])->name('bulk-update');
    });

    // MLM Reports & Analytics
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\MlmReportController::class, 'index'])->name('index');
        Route::get('/dashboard', [\App\Http\Controllers\Admin\MlmReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/member-growth', [\App\Http\Controllers\Admin\MlmReportController::class, 'memberGrowth'])->name('member-growth');
        Route::get('/commission-trends', [\App\Http\Controllers\Admin\MlmReportController::class, 'commissionTrends'])->name('commission-trends');
        Route::get('/pv-analytics', [\App\Http\Controllers\Admin\MlmReportController::class, 'pvAnalytics'])->name('pv-analytics');
        Route::get('/top-performers', [\App\Http\Controllers\Admin\MlmReportController::class, 'topPerformers'])->name('top-performers');
        Route::get('/commission-by-type', [\App\Http\Controllers\Admin\MlmReportController::class, 'commissionByType'])->name('commission-by-type');
        Route::get('/level-analysis', [\App\Http\Controllers\Admin\MlmReportController::class, 'levelAnalysis'])->name('level-analysis');
        Route::get('/binary-analysis', [\App\Http\Controllers\Admin\MlmReportController::class, 'binaryAnalysis'])->name('binary-analysis');
        Route::get('/export-members', [\App\Http\Controllers\Admin\MlmReportController::class, 'exportMembers'])->name('export-members');
        Route::get('/export-commissions', [\App\Http\Controllers\Admin\MlmReportController::class, 'exportCommissions'])->name('export-commissions');
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
    Route::get('/genealogy', [\App\Http\Controllers\Admin\MlmPlanController::class, 'genealogy'])->name('genealogy.index');
    Route::get('/genealogy/workflow', [\App\Http\Controllers\Admin\MlmPlanController::class, 'genealogyWorkflow'])->name('genealogy.workflow');

    // MLM Bloodline Viewer (ผังสายเลือด - แสดงเส้นทางจาก root ถึงสมาชิก)
    Route::get('/genealogy/bloodline', [\App\Http\Controllers\Admin\MlmPlanController::class, 'bloodline'])->name('genealogy.bloodline');
    Route::get('/genealogy/bloodline/workflow', [\App\Http\Controllers\Admin\MlmPlanController::class, 'bloodlineWorkflow'])->name('genealogy.bloodline.workflow');

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
        Route::get('/', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'index'])->name('index');
        Route::post('/basic', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'updateBasic'])->name('update-basic');
        Route::post('/certificate', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'updateCertificate'])->name('update-certificate');
        Route::post('/email', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'updateEmail'])->name('update-email');
        Route::post('/course', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'updateCourse'])->name('update-course');
        Route::post('/instructor', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'updateInstructor'])->name('update-instructor');
        Route::post('/toggle-active', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'toggleActive'])->name('toggle-active');

        // File Uploads
        Route::post('/upload-logo', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'uploadLogo'])->name('upload-logo');
        Route::post('/upload-certificate-background', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'uploadCertificateBackground'])->name('upload-certificate-background');
        Route::post('/upload-certificate-template', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'uploadCertificateTemplate'])->name('upload-certificate-template');

        // Signatures
        Route::post('/add-signature', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'addSignature'])->name('add-signature');
        Route::delete('/remove-signature/{index}', [\App\Http\Controllers\Admin\AcademySettingsController::class, 'removeSignature'])->name('remove-signature');
    });

    // Certificate Management (Admin)
    Route::prefix('certificates')->name('certificates.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CertificateManagementController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\CertificateManagementController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\CertificateManagementController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Admin\CertificateManagementController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [\App\Http\Controllers\Admin\CertificateManagementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\Admin\CertificateManagementController::class, 'update'])->name('update');
        Route::post('/{id}/revoke', [\App\Http\Controllers\Admin\CertificateManagementController::class, 'revoke'])->name('revoke');
        Route::post('/{id}/restore', [\App\Http\Controllers\Admin\CertificateManagementController::class, 'restore'])->name('restore');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\CertificateManagementController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-generate', [\App\Http\Controllers\Admin\CertificateManagementController::class, 'bulkGenerate'])->name('bulk-generate');
        Route::get('/export/csv', [\App\Http\Controllers\Admin\CertificateManagementController::class, 'export'])->name('export');
    });

    // Courses alias (redirects to certificates)
    Route::prefix('courses')->name('courses.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CertificateManagementController::class, 'index'])->name('index');
    });
});


// HRM (Human Resource Management) System
Route::prefix('hrm')->name('hrm.')->group(function () {
    // HRM Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Admin\HrmDashboardController::class, 'index'])->name('dashboard');

    // Employee Management
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\EmployeeController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\EmployeeController::class, 'store'])->name('store');
        Route::get('/{employee}', [\App\Http\Controllers\Admin\EmployeeController::class, 'show'])->name('show');
        Route::get('/{employee}/edit', [\App\Http\Controllers\Admin\EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [\App\Http\Controllers\Admin\EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [\App\Http\Controllers\Admin\EmployeeController::class, 'destroy'])->name('destroy');
        Route::get('/export/csv', [\App\Http\Controllers\Admin\EmployeeController::class, 'export'])->name('export');
    });

    // Department Management
    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DepartmentController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\DepartmentController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\DepartmentController::class, 'store'])->name('store');
        Route::get('/{department}', [\App\Http\Controllers\Admin\DepartmentController::class, 'show'])->name('show');
        Route::get('/{department}/edit', [\App\Http\Controllers\Admin\DepartmentController::class, 'edit'])->name('edit');
        Route::put('/{department}', [\App\Http\Controllers\Admin\DepartmentController::class, 'update'])->name('update');
        Route::delete('/{department}', [\App\Http\Controllers\Admin\DepartmentController::class, 'destroy'])->name('destroy');
    });

    // Position Management (simplified routes)
    Route::resource('positions', \App\Http\Controllers\Admin\PositionController::class);

    // Attendance Management
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AttendanceController::class, 'index'])->name('index');
        Route::get('/employee/{employee}', [\App\Http\Controllers\Admin\AttendanceController::class, 'employeeReport'])->name('employee-report');
        Route::post('/mark-absent', [\App\Http\Controllers\Admin\AttendanceController::class, 'markAbsent'])->name('mark-absent');
        Route::post('/bulk-import', [\App\Http\Controllers\Admin\AttendanceController::class, 'bulkImport'])->name('bulk-import');
    });

    // Leave Management
    Route::prefix('leave')->name('leave.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\LeaveController::class, 'index'])->name('index');
        Route::get('/{leaveRequest}', [\App\Http\Controllers\Admin\LeaveController::class, 'show'])->name('show');
        Route::post('/{leaveRequest}/approve', [\App\Http\Controllers\Admin\LeaveController::class, 'approve'])->name('approve');
        Route::post('/{leaveRequest}/reject', [\App\Http\Controllers\Admin\LeaveController::class, 'reject'])->name('reject');
        Route::get('/calendar/view', [\App\Http\Controllers\Admin\LeaveController::class, 'calendar'])->name('calendar');

        // Leave Types Management
        Route::get('/types/manage', [\App\Http\Controllers\Admin\LeaveController::class, 'leaveTypes'])->name('types');
        Route::get('/types/create', [\App\Http\Controllers\Admin\LeaveController::class, 'createLeaveType'])->name('types.create');
        Route::post('/types', [\App\Http\Controllers\Admin\LeaveController::class, 'storeLeaveType'])->name('types.store');
        Route::get('/types/{leaveType}/edit', [\App\Http\Controllers\Admin\LeaveController::class, 'editLeaveType'])->name('types.edit');
        Route::put('/types/{leaveType}', [\App\Http\Controllers\Admin\LeaveController::class, 'updateLeaveType'])->name('types.update');
    });

    // Payroll Management
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PayrollController::class, 'index'])->name('index');
        Route::get('/{payroll}', [\App\Http\Controllers\Admin\PayrollController::class, 'show'])->name('show');
        Route::post('/generate', [\App\Http\Controllers\Admin\PayrollController::class, 'generate'])->name('generate');
        Route::post('/{payroll}/approve', [\App\Http\Controllers\Admin\PayrollController::class, 'approve'])->name('approve');
        Route::post('/{payroll}/mark-paid', [\App\Http\Controllers\Admin\PayrollController::class, 'markAsPaid'])->name('mark-paid');
        Route::get('/{payroll}/payslip', [\App\Http\Controllers\Admin\PayrollController::class, 'downloadPayslip'])->name('payslip');
        Route::post('/bulk-approve', [\App\Http\Controllers\Admin\PayrollController::class, 'bulkApprove'])->name('bulk-approve');
        Route::get('/export/csv', [\App\Http\Controllers\Admin\PayrollController::class, 'export'])->name('export');
    });

    // Performance Management
    Route::prefix('performance')->name('performance.')->group(function () {
        // Performance Reviews
        Route::resource('reviews', \App\Http\Controllers\Admin\PerformanceReviewController::class);

        // Performance Goals
        Route::resource('goals', \App\Http\Controllers\Admin\PerformanceGoalController::class);

        // Performance Templates
        Route::resource('templates', \App\Http\Controllers\Admin\PerformanceTemplateController::class);
    });

    // Recruitment Management
    Route::prefix('recruitment')->name('recruitment.')->group(function () {
        // Job Postings
        Route::resource('jobs', \App\Http\Controllers\Admin\JobPostingController::class);

        // Job Applications
        Route::resource('applications', \App\Http\Controllers\Admin\JobApplicationController::class);
    });

    // Training Management
    Route::prefix('training')->name('training.')->group(function () {
        // Training Courses
        Route::resource('courses', \App\Http\Controllers\Admin\TrainingCourseController::class);

        // Training Enrollments (also accessible as schedules)
        Route::resource('enrollments', \App\Http\Controllers\Admin\TrainingEnrollmentController::class);

        // Alias for schedules (maps to enrollments)
        Route::get('/schedules', [\App\Http\Controllers\Admin\TrainingEnrollmentController::class, 'index'])->name('schedules');
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
        Route::get('/', [FlowAccountController::class, 'index'])->name('index');
        Route::get('/connect', [FlowAccountController::class, 'showConnectForm'])->name('connect.form');
        Route::post('/connect', [FlowAccountController::class, 'connect'])->name('connect');
        Route::get('/callback', [FlowAccountController::class, 'callback'])->name('callback');
        Route::post('/disconnect', [FlowAccountController::class, 'disconnect'])->name('disconnect');
        Route::post('/sync', [FlowAccountController::class, 'sync'])->name('sync');
        Route::post('/sync/{type}', [FlowAccountController::class, 'syncType'])->name('sync.type');
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
});

// Theme Management
Route::prefix('themes')->name('themes.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ThemeController::class, 'index'])->name('index');
    Route::get('/builder/{id?}', [\App\Http\Controllers\Admin\ThemeController::class, 'builder'])->name('builder');
    Route::post('/', [\App\Http\Controllers\Admin\ThemeController::class, 'store'])->name('store');
    Route::put('/{id}', [\App\Http\Controllers\Admin\ThemeController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\Admin\ThemeController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/duplicate', [\App\Http\Controllers\Admin\ThemeController::class, 'duplicate'])->name('duplicate');
    Route::post('/preset/{presetId}', [\App\Http\Controllers\Admin\ThemeController::class, 'createFromPreset'])->name('create-from-preset');
    Route::post('/{id}/preview', [\App\Http\Controllers\Admin\ThemeController::class, 'uploadPreview'])->name('upload-preview');
    Route::get('/{id}/statistics', [\App\Http\Controllers\Admin\ThemeController::class, 'statistics'])->name('statistics');
    Route::get('/{id}/export', [\App\Http\Controllers\Admin\ThemeController::class, 'export'])->name('export');
    Route::post('/import', [\App\Http\Controllers\Admin\ThemeController::class, 'import'])->name('import');
    Route::post('/initialize', [\App\Http\Controllers\Admin\ThemeController::class, 'initialize'])->name('initialize');
    Route::post('/{id}/set-default', [\App\Http\Controllers\Admin\ThemeController::class, 'setDefault'])->name('set-default');
    Route::post('/{id}/toggle-active', [\App\Http\Controllers\Admin\ThemeController::class, 'toggleActive'])->name('toggle-active');
});

// Icon Management
Route::prefix('icons')->name('icons.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\IconController::class, 'index'])->name('index');
    Route::post('/upload', [\App\Http\Controllers\Admin\IconController::class, 'upload'])->name('upload');
    Route::delete('/', [\App\Http\Controllers\Admin\IconController::class, 'destroy'])->name('destroy');
    Route::get('/list', [\App\Http\Controllers\Admin\IconController::class, 'list'])->name('list');
});

// Hotel Management
Route::prefix('hotels')->name('hotels.')->group(function () {
    // Hotel Management - Basic Routes
    Route::get('/', [\App\Http\Controllers\Admin\HotelManagementController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\HotelManagementController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\HotelManagementController::class, 'store'])->name('store');

    // Facilities Management
    Route::prefix('facilities')->name('facilities.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\HotelFacilityController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\HotelFacilityController::class, 'store'])->name('store');
        Route::put('/{id}', [\App\Http\Controllers\Admin\HotelFacilityController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\HotelFacilityController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [\App\Http\Controllers\Admin\HotelFacilityController::class, 'reorder'])->name('reorder');
    });

    // Booking Management
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\HotelBookingManagementController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\HotelBookingManagementController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\HotelBookingManagementController::class, 'store'])->name('store');
        Route::get('/calendar', [\App\Http\Controllers\Admin\HotelBookingManagementController::class, 'calendar'])->name('calendar');
        Route::get('/analytics', [\App\Http\Controllers\Admin\HotelBookingManagementController::class, 'analytics'])->name('analytics');
        Route::get('/export', [\App\Http\Controllers\Admin\HotelBookingManagementController::class, 'export'])->name('export');
        Route::get('/{id}', [\App\Http\Controllers\Admin\HotelBookingManagementController::class, 'show'])->name('show');
        Route::post('/{id}/update-status', [\App\Http\Controllers\Admin\HotelBookingManagementController::class, 'updateStatus'])->name('update-status');
        Route::post('/{id}/cancel', [\App\Http\Controllers\Admin\HotelBookingManagementController::class, 'cancel'])->name('cancel');
    });

    // Review Management
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\HotelReviewManagementController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Admin\HotelReviewManagementController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [\App\Http\Controllers\Admin\HotelReviewManagementController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [\App\Http\Controllers\Admin\HotelReviewManagementController::class, 'reject'])->name('reject');
        Route::post('/{id}/toggle-featured', [\App\Http\Controllers\Admin\HotelReviewManagementController::class, 'toggleFeatured'])->name('toggle-featured');
        Route::post('/{id}/respond', [\App\Http\Controllers\Admin\HotelReviewManagementController::class, 'respond'])->name('respond');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\HotelReviewManagementController::class, 'destroy'])->name('destroy');
    });

    // Special Offers Management
    Route::prefix('special-offers')->name('special-offers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\HotelSpecialOfferController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\HotelSpecialOfferController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\HotelSpecialOfferController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Admin\HotelSpecialOfferController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [\App\Http\Controllers\Admin\HotelSpecialOfferController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\Admin\HotelSpecialOfferController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\HotelSpecialOfferController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [\App\Http\Controllers\Admin\HotelSpecialOfferController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{id}/toggle-featured', [\App\Http\Controllers\Admin\HotelSpecialOfferController::class, 'toggleFeatured'])->name('toggle-featured');
    });

    // Hotel Management - Individual Hotel Routes (must come after specific prefixes)
    Route::get('/{id}', [\App\Http\Controllers\Admin\HotelManagementController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\App\Http\Controllers\Admin\HotelManagementController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\Admin\HotelManagementController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\Admin\HotelManagementController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [\App\Http\Controllers\Admin\HotelManagementController::class, 'toggleStatus'])->name('toggle-status');
    Route::post('/{id}/toggle-featured', [\App\Http\Controllers\Admin\HotelManagementController::class, 'toggleFeatured'])->name('toggle-featured');

    // Room Type Management
    Route::prefix('{hotelId}/rooms')->name('rooms.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\RoomTypeManagementController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\RoomTypeManagementController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\RoomTypeManagementController::class, 'store'])->name('store');
        Route::get('/{roomTypeId}', [\App\Http\Controllers\Admin\RoomTypeManagementController::class, 'show'])->name('show');
        Route::get('/{roomTypeId}/edit', [\App\Http\Controllers\Admin\RoomTypeManagementController::class, 'edit'])->name('edit');
        Route::put('/{roomTypeId}', [\App\Http\Controllers\Admin\RoomTypeManagementController::class, 'update'])->name('update');
        Route::delete('/{roomTypeId}', [\App\Http\Controllers\Admin\RoomTypeManagementController::class, 'destroy'])->name('destroy');
        Route::get('/{roomTypeId}/availability', [\App\Http\Controllers\Admin\RoomTypeManagementController::class, 'availability'])->name('availability');
        Route::post('/{roomTypeId}/availability', [\App\Http\Controllers\Admin\RoomTypeManagementController::class, 'updateAvailability'])->name('availability.update');
    });
});

// Floating Tools Management
Route::prefix('floating-tools')->name('floating-tools.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\FloatingToolsController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Admin\FloatingToolsController::class, 'update'])->name('update');
});

// Developer Release Manager (IP-locked, Developer Only)
Route::prefix('dev/releases')->middleware(\App\Http\Middleware\DevMode::class)->name('dev.releases.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\Dev\DevReleaseController::class, 'index'])->name('index');
    Route::post('/create', [\App\Http\Controllers\Admin\Dev\DevReleaseController::class, 'create'])->name('create');
    Route::post('/{tag}/publish', [\App\Http\Controllers\Admin\Dev\DevReleaseController::class, 'publish'])->name('publish');
    Route::delete('/{tag}/delete', [\App\Http\Controllers\Admin\Dev\DevReleaseController::class, 'delete'])->name('delete');
    Route::get('/refresh', [\App\Http\Controllers\Admin\Dev\DevReleaseController::class, 'refresh'])->name('refresh');
    Route::get('/realtime', [\App\Http\Controllers\Admin\Dev\DevReleaseController::class, 'realtimeInfo'])->name('realtime');
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
    Route::get('/', [\App\Http\Controllers\Admin\TarotManagementController::class, 'index'])->name('index');
    Route::get('/analytics', [\App\Http\Controllers\Admin\TarotManagementController::class, 'analytics'])->name('analytics');

    // Tarot Cards Management
    Route::prefix('cards')->name('cards.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TarotManagementController::class, 'cardsIndex'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\TarotManagementController::class, 'cardsCreate'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\TarotManagementController::class, 'cardsStore'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\Admin\TarotManagementController::class, 'cardsEdit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\Admin\TarotManagementController::class, 'cardsUpdate'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\TarotManagementController::class, 'cardsDestroy'])->name('destroy');
        // AJAX upload endpoint
        Route::post('/{id}/upload-image', [\App\Http\Controllers\Admin\TarotManagementController::class, 'cardsUploadImage'])->name('upload-image');
    });

    // Categories Management
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TarotManagementController::class, 'categoriesIndex'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\TarotManagementController::class, 'categoriesCreate'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\TarotManagementController::class, 'categoriesStore'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\Admin\TarotManagementController::class, 'categoriesEdit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\Admin\TarotManagementController::class, 'categoriesUpdate'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\TarotManagementController::class, 'categoriesDestroy'])->name('destroy');
    });

    // Card Back Images Management
    Route::prefix('card-backs')->name('card-backs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TarotManagementController::class, 'cardBacksIndex'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\TarotManagementController::class, 'cardBacksStore'])->name('store');
        Route::post('/{id}/set-default', [\App\Http\Controllers\Admin\TarotManagementController::class, 'cardBacksSetDefault'])->name('set-default');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\TarotManagementController::class, 'cardBacksDestroy'])->name('destroy');
    });

    // Spread Types Management
    Route::prefix('spread-types')->name('spread-types.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TarotManagementController::class, 'spreadTypesIndex'])->name('index');
    });

    // Interpretations Management - จัดการคำทำนายตามหมวดหมู่
    Route::prefix('interpretations')->name('interpretations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TarotManagementController::class, 'interpretationsIndex'])->name('index');
        Route::get('/{id}/edit', [\App\Http\Controllers\Admin\TarotManagementController::class, 'interpretationsEdit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\Admin\TarotManagementController::class, 'interpretationsUpdate'])->name('update');
        Route::post('/{id}/copy-defaults', [\App\Http\Controllers\Admin\TarotManagementController::class, 'interpretationsCopyDefaults'])->name('copy-defaults');
        Route::post('/copy-all-defaults', [\App\Http\Controllers\Admin\TarotManagementController::class, 'interpretationsCopyAllDefaults'])->name('copy-all-defaults');
    });

    // Readings Management
    Route::prefix('readings')->name('readings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TarotManagementController::class, 'readingsIndex'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Admin\TarotManagementController::class, 'readingsShow'])->name('show');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\TarotManagementController::class, 'readingsDestroy'])->name('destroy');
    });

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Admin\TarotManagementController::class, 'settings'])->name('settings');
    Route::put('/settings', [\App\Http\Controllers\Admin\TarotManagementController::class, 'settingsUpdate'])->name('settings.update');
});

// Cryptocurrency Payment Gateway Management
Route::prefix('crypto')->name('crypto.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Admin\CryptoManagementController::class, 'dashboard'])->name('dashboard');

    // Withdrawal Management - Direct route for backward compatibility
    Route::get('/withdrawals', [\App\Http\Controllers\Admin\CryptoManagementController::class, 'withdrawals'])->name('withdrawals');

    // Withdrawal actions
    Route::post('/withdrawals/{id}/approve', [\App\Http\Controllers\Admin\CryptoManagementController::class, 'approveWithdrawal'])->name('withdrawals.approve');
    Route::post('/withdrawals/{id}/reject', [\App\Http\Controllers\Admin\CryptoManagementController::class, 'rejectWithdrawal'])->name('withdrawals.reject');

    // Transaction Monitor
    Route::get('/transactions', [\App\Http\Controllers\Admin\CryptoManagementController::class, 'transactions'])->name('transactions');

    // Wallet Management
    Route::get('/wallets', [\App\Http\Controllers\Admin\CryptoManagementController::class, 'wallets'])->name('wallets');

    // Currency Management - Direct route for backward compatibility
    Route::get('/currencies', [\App\Http\Controllers\Admin\CryptoManagementController::class, 'currencies'])->name('currencies');
    Route::put('/currencies/{id}', [\App\Http\Controllers\Admin\CryptoManagementController::class, 'updateCurrency'])->name('currencies.update');

    // Manual Operations
    Route::post('/scan-deposits', [\App\Http\Controllers\Admin\CryptoManagementController::class, 'scanDeposits'])->name('scan-deposits');
    Route::post('/process-withdrawals', [\App\Http\Controllers\Admin\CryptoManagementController::class, 'processWithdrawals'])->name('process-withdrawals');

    // System Settings - Direct route for backward compatibility
    Route::get('/settings', [\App\Http\Controllers\Admin\CryptoManagementController::class, 'settings'])->name('settings');
    Route::post('/settings', [\App\Http\Controllers\Admin\CryptoManagementController::class, 'updateSettings'])->name('settings.update');

    // HD Wallet Management (Hierarchical Deterministic Wallets)
    Route::prefix('hd-wallets')->name('hd-wallets.')->group(function () {
        // Overview
        Route::get('/', [\App\Http\Controllers\Admin\HDWalletManagementController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\Admin\HDWalletManagementController::class, 'export'])->name('export');

        // Master Wallets
        Route::get('/master', [\App\Http\Controllers\Admin\HDWalletManagementController::class, 'masterWallets'])->name('master');
        Route::get('/master/{masterWalletId}/children', [\App\Http\Controllers\Admin\HDWalletManagementController::class, 'childWallets'])->name('master.children');

        // User Wallets
        Route::get('/user/{userId}', [\App\Http\Controllers\Admin\HDWalletManagementController::class, 'userWallets'])->name('user');

        // Wallet Details
        Route::get('/{id}', [\App\Http\Controllers\Admin\HDWalletManagementController::class, 'show'])->name('show');

        // Wallet Actions
        Route::post('/{id}/lock', [\App\Http\Controllers\Admin\HDWalletManagementController::class, 'lockWallet'])->name('lock');
        Route::post('/{id}/unlock', [\App\Http\Controllers\Admin\HDWalletManagementController::class, 'unlockWallet'])->name('unlock');
        Route::post('/{id}/suspend', [\App\Http\Controllers\Admin\HDWalletManagementController::class, 'suspendWallet'])->name('suspend');
        Route::post('/{id}/reactivate', [\App\Http\Controllers\Admin\HDWalletManagementController::class, 'reactivateWallet'])->name('reactivate');
    });
});

// TPIX Native Blockchain Management
Route::prefix('tpix')->name('tpix.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Admin\TPIXController::class, 'dashboard'])->name('dashboard');

    // Network Status
    Route::get('/network-status', [\App\Http\Controllers\Admin\TPIXController::class, 'networkStatus'])->name('network-status');

    // Wallets
    Route::get('/wallets', [\App\Http\Controllers\Admin\TPIXController::class, 'wallets'])->name('wallets');

    // Transactions
    Route::get('/transactions', [\App\Http\Controllers\Admin\TPIXController::class, 'transactions'])->name('transactions');
    Route::get('/transactions/{id}', [\App\Http\Controllers\Admin\TPIXController::class, 'transactionDetails'])->name('transactions.details');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Admin\TPIXController::class, 'settings'])->name('settings');
    Route::put('/settings', [\App\Http\Controllers\Admin\TPIXController::class, 'updateSettings'])->name('update-settings');

    // API endpoint for checking blockchain connection
    Route::get('/check-connection', [\App\Http\Controllers\Admin\TPIXController::class, 'checkConnection'])->name('check-connection');
});

// TPIX Token Management
Route::prefix('tokens')->name('tokens.')->group(function () {
    // Token List & Overview
    Route::get('/', [\App\Http\Controllers\Admin\TokenManagementController::class, 'index'])->name('index');
    Route::get('/{id}', [\App\Http\Controllers\Admin\TokenManagementController::class, 'show'])->name('show');

    // Token Approval & Verification
    Route::post('/{id}/approve', [\App\Http\Controllers\Admin\TokenManagementController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [\App\Http\Controllers\Admin\TokenManagementController::class, 'reject'])->name('reject');
    Route::post('/{id}/verify', [\App\Http\Controllers\Admin\TokenManagementController::class, 'verify'])->name('verify');
    Route::post('/{id}/feature', [\App\Http\Controllers\Admin\TokenManagementController::class, 'feature'])->name('feature');
    Route::post('/{id}/unfeature', [\App\Http\Controllers\Admin\TokenManagementController::class, 'unfeature'])->name('unfeature');

    // Coin Control Operations
    Route::post('/{id}/mint', [\App\Http\Controllers\Admin\TokenManagementController::class, 'mint'])->name('mint');
    Route::post('/{id}/burn', [\App\Http\Controllers\Admin\TokenManagementController::class, 'burn'])->name('burn');
    Route::post('/{id}/freeze-address', [\App\Http\Controllers\Admin\TokenManagementController::class, 'freezeAddress'])->name('freeze-address');
    Route::post('/{id}/unfreeze-address', [\App\Http\Controllers\Admin\TokenManagementController::class, 'unfreezeAddress'])->name('unfreeze-address');
    Route::post('/{id}/pause', [\App\Http\Controllers\Admin\TokenManagementController::class, 'pause'])->name('pause');
    Route::post('/{id}/unpause', [\App\Http\Controllers\Admin\TokenManagementController::class, 'unpause'])->name('unpause');

    // CoinMarketCap Integration
    Route::post('/{id}/sync-cmc', [\App\Http\Controllers\Admin\TokenManagementController::class, 'syncWithCMC'])->name('sync-cmc');
    Route::post('/{id}/link-cmc', [\App\Http\Controllers\Admin\TokenManagementController::class, 'linkCMC'])->name('link-cmc');
    Route::get('/{id}/cmc-logs', [\App\Http\Controllers\Admin\TokenManagementController::class, 'cmcLogs'])->name('cmc-logs');

    // Import from CoinMarketCap
    Route::get('/import-cmc', [\App\Http\Controllers\Admin\TokenManagementController::class, 'showImportCMC'])->name('import-cmc');
    Route::post('/import-cmc', [\App\Http\Controllers\Admin\TokenManagementController::class, 'importFromCMC'])->name('import-cmc.store');

    // Control Actions History
    Route::get('/{id}/control-actions', [\App\Http\Controllers\Admin\TokenManagementController::class, 'controlActions'])->name('control-actions');

    // Token Settings
    Route::get('/{id}/edit', [\App\Http\Controllers\Admin\TokenManagementController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\Admin\TokenManagementController::class, 'update'])->name('update');
});

// App Management (Mobile App Configuration)
Route::prefix('app-management')->name('app-management.')->group(function () {
    // App Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [AppSettingController::class, 'index'])->name('index');
        Route::put('/', [AppSettingController::class, 'update'])->name('update');
    });

    // App Theme Settings
    Route::prefix('theme')->name('theme.')->group(function () {
        Route::get('/', [AppThemeSettingController::class, 'index'])->name('index');
        Route::put('/', [AppThemeSettingController::class, 'update'])->name('update');
    });

    // App Features
    Route::prefix('features')->name('features.')->group(function () {
        Route::get('/', [AppFeatureController::class, 'index'])->name('index');
        Route::get('/create', [AppFeatureController::class, 'create'])->name('create');
        Route::post('/', [AppFeatureController::class, 'store'])->name('store');
        Route::get('/{appFeature}/edit', [AppFeatureController::class, 'edit'])->name('edit');
        Route::put('/{appFeature}', [AppFeatureController::class, 'update'])->name('update');
        Route::delete('/{appFeature}', [AppFeatureController::class, 'destroy'])->name('destroy');
        Route::post('/{appFeature}/toggle', [AppFeatureController::class, 'toggle'])->name('toggle');
    });

    // App Banners
    Route::prefix('banners')->name('banners.')->group(function () {
        Route::get('/', [AppBannerController::class, 'index'])->name('index');
        Route::get('/create', [AppBannerController::class, 'create'])->name('create');
        Route::post('/', [AppBannerController::class, 'store'])->name('store');
        Route::get('/{appBanner}/edit', [AppBannerController::class, 'edit'])->name('edit');
        Route::put('/{appBanner}', [AppBannerController::class, 'update'])->name('update');
        Route::delete('/{appBanner}', [AppBannerController::class, 'destroy'])->name('destroy');
        Route::post('/{appBanner}/toggle', [AppBannerController::class, 'toggle'])->name('toggle');
        Route::post('/{appBanner}/track-view', [AppBannerController::class, 'trackView'])->name('track-view');
        Route::post('/{appBanner}/track-click', [AppBannerController::class, 'trackClick'])->name('track-click');
    });

    // App Maintenance
    Route::prefix('app-maintenance')->name('app-maintenance.')->group(function () {
        Route::get('/', [AppMaintenanceController::class, 'index'])->name('index');
        Route::put('/', [AppMaintenanceController::class, 'update'])->name('update');
        Route::post('/toggle', [AppMaintenanceController::class, 'toggle'])->name('toggle');
        Route::post('/enable', [AppMaintenanceController::class, 'enable'])->name('enable');
        Route::post('/disable', [AppMaintenanceController::class, 'disable'])->name('disable');
    });

    // App Control Sections
    Route::prefix('control-sections')->name('control-sections.')->group(function () {
        Route::get('/', [AppControlSectionController::class, 'index'])->name('index');
        Route::get('/create', [AppControlSectionController::class, 'create'])->name('create');
        Route::post('/', [AppControlSectionController::class, 'store'])->name('store');
        Route::get('/{appControlSection}/edit', [AppControlSectionController::class, 'edit'])->name('edit');
        Route::put('/{appControlSection}', [AppControlSectionController::class, 'update'])->name('update');
        Route::delete('/{appControlSection}', [AppControlSectionController::class, 'destroy'])->name('destroy');
        Route::post('/{appControlSection}/toggle-visibility', [AppControlSectionController::class, 'toggleVisibility'])->name('toggle-visibility');
        Route::post('/{appControlSection}/toggle-active', [AppControlSectionController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/update-order', [AppControlSectionController::class, 'updateOrder'])->name('update-order');
    });

    // Component Settings
    Route::prefix('component-settings')->name('component-settings.')->group(function () {
        Route::get('/', [ComponentSettingController::class, 'index'])->name('index');
        Route::get('/create', [ComponentSettingController::class, 'create'])->name('create');
        Route::post('/', [ComponentSettingController::class, 'store'])->name('store');
        Route::get('/{componentSetting}/edit', [ComponentSettingController::class, 'edit'])->name('edit');
        Route::put('/{componentSetting}', [ComponentSettingController::class, 'update'])->name('update');
        Route::delete('/{componentSetting}', [ComponentSettingController::class, 'destroy'])->name('destroy');
        Route::post('/{componentSetting}/toggle-enabled', [ComponentSettingController::class, 'toggleEnabled'])->name('toggle-enabled');
        Route::post('/{componentSetting}/duplicate', [ComponentSettingController::class, 'duplicate'])->name('duplicate');
    });

    // Viral Trend Detection Routes
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
});

// Video Reward System Admin
Route::prefix('video-rewards')->name('video-rewards.')->group(function () {
    // Dashboard & Statistics
    Route::get('/dashboard', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'dashboard'])->name('dashboard');

    // Coin Exchange Management
    Route::get('/exchange-requests', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'exchangeRequests'])->name('exchange.requests');
    Route::post('/exchange-requests/{requestId}/approve', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'approveExchange'])->name('exchange.approve');
    Route::post('/exchange-requests/{requestId}/reject', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'rejectExchange'])->name('exchange.reject');

    // Channel Management
    Route::get('/channels', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'channels'])->name('channels.index');
    Route::post('/channels', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'storeChannel'])->name('channels.store');
    Route::put('/channels/{channelId}', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'updateChannel'])->name('channels.update');

    // Video Management
    Route::get('/videos', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'videos'])->name('videos.index');
    Route::post('/videos', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'storeVideo'])->name('videos.store');
    Route::put('/videos/{videoId}', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'updateVideo'])->name('videos.update');

    // Quest Management
    Route::get('/quests', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'quests'])->name('quests.index');
    Route::post('/quests', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'storeQuest'])->name('quests.store');
    Route::put('/quests/{questId}', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'updateQuest'])->name('quests.update');

    // Exchange Rate Management
    Route::get('/exchange-rates', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'exchangeRates'])->name('exchange-rates.index');
    Route::put('/exchange-rates/{rateId}', [\App\Http\Controllers\Admin\VideoRewardAdminController::class, 'updateExchangeRate'])->name('exchange-rates.update');
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
use App\Http\Controllers\Admin\SmartSliderController;
use App\Http\Controllers\Admin\SmartSlideController;
use App\Http\Controllers\Admin\SmartSlideLayerController;

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
use App\Http\Controllers\Admin\AiGenAdminController;

Route::prefix('ai-gen')->name('ai-gen.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AiGenAdminController::class, 'dashboard'])->name('dashboard');

    // Providers Management
    Route::get('/providers', [AiGenAdminController::class, 'providers'])->name('providers.index');
    Route::post('/providers', [AiGenAdminController::class, 'createProvider'])->name('providers.store');
    Route::put('/providers/{providerId}', [AiGenAdminController::class, 'updateProvider'])->name('providers.update');
    Route::post('/providers/{providerId}/config', [AiGenAdminController::class, 'updateProviderConfig'])->name('providers.config');
    Route::post('/providers/{providerId}/test', [AiGenAdminController::class, 'testProvider'])->name('providers.test');

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
});

// Game Management Routes
Route::prefix('games')->name('games.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\GameController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Admin\GameController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Admin\GameController::class, 'store'])->name('store');
    Route::get('/{game}', [App\Http\Controllers\Admin\GameController::class, 'show'])->name('show');
    Route::get('/{game}/edit', [App\Http\Controllers\Admin\GameController::class, 'edit'])->name('edit');
    Route::put('/{game}', [App\Http\Controllers\Admin\GameController::class, 'update'])->name('update');
    Route::delete('/{game}', [App\Http\Controllers\Admin\GameController::class, 'destroy'])->name('destroy');
    Route::patch('/{game}/toggle-active', [App\Http\Controllers\Admin\GameController::class, 'toggleActive'])->name('toggle-active');
    Route::post('/update-order', [App\Http\Controllers\Admin\GameController::class, 'updateOrder'])->name('update-order');

    // ✅ Snake.io Multiplayer Service Monitor
    Route::prefix('snake-io')->name('snake-io.')->group(function () {
        Route::get('/monitor', [App\Http\Controllers\Admin\SnakeGameAdminController::class, 'dashboard'])
            ->name('monitor');
    });

    // ✅ Game Settings Management (IP, Port, Server Configuration)
    Route::prefix('game-settings')->name('game-settings.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\GameSettingsController::class, 'index'])
            ->name('index');
        Route::put('/update', [App\Http\Controllers\Admin\GameSettingsController::class, 'update'])
            ->name('update');
    });
});

// Arrow X Theme System Routes
Route::prefix('arrow-x-theme')->name('arrow-x-theme.')->group(function () {
    // Dashboard
    Route::get('/', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'index'])
        ->name('index');

    // General Settings
    Route::get('/general-settings', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'generalSettings'])
        ->name('general-settings');
    Route::put('/general-settings', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'updateGeneralSettings'])
        ->name('general-settings.update');

    // Color Settings
    Route::get('/color-settings', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'colorSettings'])
        ->name('color-settings');
    Route::put('/color-settings', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'updateColorSettings'])
        ->name('color-settings.update');
    Route::post('/apply-preset', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'applyPreset'])
        ->name('apply-preset');

    // RGB Effects
    Route::get('/rgb-effects', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'rgbEffects'])
        ->name('rgb-effects');
    Route::post('/rgb-effects', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'storeRgbEffect'])
        ->name('rgb-effects.store');
    Route::put('/rgb-effects/{rgbEffect}', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'updateRgbEffect'])
        ->name('rgb-effects.update');
    Route::delete('/rgb-effects/{rgbEffect}', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'destroyRgbEffect'])
        ->name('rgb-effects.destroy');

    // Typography
    Route::get('/typography', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'typography'])
        ->name('typography');
    Route::put('/typography', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'updateTypography'])
        ->name('typography.update');

    // Upload Assets
    Route::post('/upload-logo', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'uploadLogo'])
        ->name('upload-logo');
    Route::post('/upload-favicon', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'uploadFavicon'])
        ->name('upload-favicon');

    // Cache Management
    Route::post('/compile', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'compileTheme'])
        ->name('compile');
    Route::post('/clear-cache', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'clearCache'])
        ->name('clear-cache');
    Route::post('/compile-files', [App\Http\Controllers\Admin\ArrowXThemeController::class, 'compileToFiles'])
        ->name('compile-files');
});

// Recruit Template Management Routes
Route::prefix('recruit-templates')->name('recruit-templates.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\RecruitTemplateController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Admin\RecruitTemplateController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Admin\RecruitTemplateController::class, 'store'])->name('store');
    Route::get('/{recruitTemplate}', [App\Http\Controllers\Admin\RecruitTemplateController::class, 'show'])->name('show');
    Route::get('/{recruitTemplate}/edit', [App\Http\Controllers\Admin\RecruitTemplateController::class, 'edit'])->name('edit');
    Route::put('/{recruitTemplate}', [App\Http\Controllers\Admin\RecruitTemplateController::class, 'update'])->name('update');
    Route::delete('/{recruitTemplate}', [App\Http\Controllers\Admin\RecruitTemplateController::class, 'destroy'])->name('destroy');
    Route::post('/{recruitTemplate}/set-default', [App\Http\Controllers\Admin\RecruitTemplateController::class, 'setDefault'])->name('set-default');
    Route::post('/{recruitTemplate}/toggle-active', [App\Http\Controllers\Admin\RecruitTemplateController::class, 'toggleActive'])->name('toggle-active');
    Route::get('/{recruitTemplate}/preview', [App\Http\Controllers\Admin\RecruitTemplateController::class, 'preview'])->name('preview');
});

// Bot Automation System Routes
require __DIR__.'/bot_automation.php';

// =====================================
// TPIX Native Coin Deployment Wizard
// =====================================
Route::prefix('tpix/deployment')->name('tpix.deployment.')->group(function () {
    // Index & Management
    Route::get('/', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'store'])->name('store');

    // Tutorial Route - คู่มือการ Deploy TPIX สู่ Blockchain จริง
    Route::get('/tutorial', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'tutorial'])->name('tutorial');

    // Wizard Routes (แยกตาม slug)
    Route::prefix('{slug}')->group(function () {
        // Main Wizard (Redirect to current step)
        Route::get('/', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'wizard'])->name('wizard');

        // Step 1: Prerequisites Check
        Route::get('/step-1', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'step1'])->name('step1');
        Route::post('/step-1', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'saveStep1'])->name('step1.save');

        // Step 2: Token Configuration
        Route::get('/step-2', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'step2'])->name('step2');
        Route::post('/step-2', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'saveStep2'])->name('step2.save');

        // Step 3: Tokenomics
        Route::get('/step-3', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'step3'])->name('step3');
        Route::post('/step-3', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'saveStep3'])->name('step3.save');

        // Step 4: Smart Contract
        Route::get('/step-4', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'step4'])->name('step4');
        Route::post('/step-4', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'saveStep4'])->name('step4.save');

        // Payment Confirmation & Processing
        Route::get('/payment', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'showPaymentConfirmation'])->name('payment');
        Route::post('/payment', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'processPayment'])->name('payment.process');
        Route::post('/payment/verify', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'verifyPayment'])->name('payment.verify');
        Route::post('/payment/refund', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'refundPayment'])->name('payment.refund');

        // Step 5: Deploy & Verify
        Route::get('/step-5', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'step5'])->name('step5');
        Route::post('/step-5/deploy', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'deployContract'])->name('step5.deploy');
        Route::post('/step-5/verify', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'verifyContract'])->name('step5.verify');

        // Step 6: DEX Integration
        Route::get('/step-6', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'step6'])->name('step6');
        Route::post('/step-6/create-pool', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'createLiquidityPool'])->name('step6.create-pool');
        Route::post('/step-6/enable-trading', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'enableTrading'])->name('step6.enable-trading');

        // Step 7: Listing & Marketing
        Route::get('/step-7', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'step7'])->name('step7');
        Route::post('/step-7/submit-cmc', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'submitToCMC'])->name('step7.submit-cmc');
        Route::post('/step-7/submit-coingecko', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'submitToCoinGecko'])->name('step7.submit-coingecko');
        Route::post('/step-7/complete', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'complete'])->name('step7.complete');

        // Delete Configuration
        Route::delete('/', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'destroy'])->name('destroy');
    });

    // API Routes
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/check-prerequisites', [\App\Http\Controllers\Admin\TpixDeploymentController::class, 'checkPrerequisitesApi'])
            ->name('check-prerequisites');
    });
});

// Team Transfer Management (Admin - MLM)
Route::prefix('team-transfer')->name('team-transfer.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\TeamTransferController::class, 'index'])->name('index');
    Route::get('/statistics', [\App\Http\Controllers\Admin\TeamTransferController::class, 'statistics'])->name('statistics');
    Route::get('/export', [\App\Http\Controllers\Admin\TeamTransferController::class, 'export'])->name('export');
    Route::get('/{teamTransfer}', [\App\Http\Controllers\Admin\TeamTransferController::class, 'show'])->name('show');
    Route::get('/{teamTransfer}/edit', [\App\Http\Controllers\Admin\TeamTransferController::class, 'edit'])->name('edit');
    Route::post('/{teamTransfer}/process', [\App\Http\Controllers\Admin\TeamTransferController::class, 'process'])->name('process');
    Route::delete('/{teamTransfer}', [\App\Http\Controllers\Admin\TeamTransferController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/restore', [\App\Http\Controllers\Admin\TeamTransferController::class, 'restore'])->name('restore');
    Route::get('/member/{memberId}/history', [\App\Http\Controllers\Admin\TeamTransferController::class, 'history'])->name('history');
});

// ============================================
// Service Booking System Routes
// ============================================

// Service Categories Management
Route::resource('service-categories', \App\Http\Controllers\Admin\ServiceCategoryController::class);
Route::post('service-categories/{serviceCategory}/toggle-active', [\App\Http\Controllers\Admin\ServiceCategoryController::class, 'toggleActive'])
    ->name('service-categories.toggle-active');
Route::post('service-categories/{serviceCategory}/toggle-featured', [\App\Http\Controllers\Admin\ServiceCategoryController::class, 'toggleFeatured'])
    ->name('service-categories.toggle-featured');
Route::post('service-categories/reorder', [\App\Http\Controllers\Admin\ServiceCategoryController::class, 'reorder'])
    ->name('service-categories.reorder');

// Services Management
Route::resource('services', \App\Http\Controllers\Admin\ServiceController::class);
Route::get('services-blocked', [\App\Http\Controllers\Admin\ServiceController::class, 'blocked'])
    ->name('services.blocked');
Route::post('services/{service}/toggle-active', [\App\Http\Controllers\Admin\ServiceController::class, 'toggleActive'])
    ->name('services.toggle-active');
Route::post('services/{service}/toggle-featured', [\App\Http\Controllers\Admin\ServiceController::class, 'toggleFeatured'])
    ->name('services.toggle-featured');
Route::post('services/{service}/calculate-price', [\App\Http\Controllers\Admin\ServiceController::class, 'calculatePrice'])
    ->name('services.calculate-price');
// Block/Unblock บริการ
Route::post('services/{service}/block', [\App\Http\Controllers\Admin\ServiceController::class, 'blockService'])
    ->name('services.block');
Route::post('services/{service}/unblock', [\App\Http\Controllers\Admin\ServiceController::class, 'unblockService'])
    ->name('services.unblock');

// Service Bookings Management
Route::prefix('service-bookings')->name('service-bookings.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ServiceBookingController::class, 'index'])->name('index');
    Route::get('/analytics', [\App\Http\Controllers\Admin\ServiceBookingController::class, 'analytics'])->name('analytics');
    Route::get('/available-providers', [\App\Http\Controllers\Admin\ServiceBookingController::class, 'availableProviders'])->name('available-providers');
    Route::get('/export', [\App\Http\Controllers\Admin\ServiceBookingController::class, 'export'])->name('export');
    Route::get('/{serviceBooking}', [\App\Http\Controllers\Admin\ServiceBookingController::class, 'show'])->name('show');
    Route::post('/{serviceBooking}/assign-provider', [\App\Http\Controllers\Admin\ServiceBookingController::class, 'assignProvider'])->name('assign-provider');
    Route::post('/{serviceBooking}/cancel', [\App\Http\Controllers\Admin\ServiceBookingController::class, 'cancel'])->name('cancel');
    Route::post('/{serviceBooking}/update-status', [\App\Http\Controllers\Admin\ServiceBookingController::class, 'updateStatus'])->name('update-status');
});

// ============================================
// Anti-Abuse Protection System 🛡️
// ============================================
Route::prefix('anti-abuse')->name('anti-abuse.')->group(function () {
    // Dashboard
    Route::get('/', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'dashboard'])->name('dashboard');

    // Disputes Management
    Route::get('/disputes', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'disputes'])->name('disputes');
    Route::get('/disputes/{dispute}', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'showDispute'])->name('disputes.show');
    Route::post('/disputes/{dispute}/status', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'updateDisputeStatus'])->name('disputes.status');
    Route::post('/disputes/{dispute}/resolve', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'resolveDispute'])->name('disputes.resolve');

    // Trust Scores Management
    Route::get('/trust-scores', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'trustScores'])->name('trust-scores');
    Route::get('/trust-scores/{trustScore}', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'showTrustScore'])->name('trust-scores.show');
    Route::post('/trust-scores/{trustScore}/adjust', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'adjustTrustScore'])->name('trust-scores.adjust');
    Route::post('/trust-scores/{trustScore}/suspend', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'suspendUser'])->name('trust-scores.suspend');
    Route::post('/trust-scores/{trustScore}/ban', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'banUser'])->name('trust-scores.ban');
    Route::post('/trust-scores/{trustScore}/unban', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'unbanUser'])->name('trust-scores.unban');

    // Penalties Management
    Route::get('/penalties', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'penalties'])->name('penalties');
    Route::post('/penalties/{penalty}/charge', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'chargePenalty'])->name('penalties.charge');
    Route::post('/penalties/{penalty}/waive', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'waivePenalty'])->name('penalties.waive');

    // Blocks Management
    Route::get('/blocks', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'blocks'])->name('blocks');
    Route::delete('/blocks/{block}', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'removeBlock'])->name('blocks.remove');

    // Location History
    Route::get('/location-history', [\App\Http\Controllers\Admin\AntiAbuseController::class, 'locationHistory'])->name('location-history');
});

// ============================================
// GPS Monitoring Center 📡
// ============================================
Route::prefix('gps-monitoring')->name('gps-monitoring.')->group(function () {
    // Dashboard หลัก
    Route::get('/', [\App\Http\Controllers\Admin\GpsMonitoringController::class, 'index'])->name('index');

    // API สำหรับดึงข้อมูล GPS
    Route::get('/data', [\App\Http\Controllers\Admin\GpsMonitoringController::class, 'getData'])->name('data');

    // ดูประวัติ GPS ของ booking
    Route::get('/booking/{booking}/history', [\App\Http\Controllers\Admin\GpsMonitoringController::class, 'getBookingHistory'])->name('booking.history');

    // Playback การเดินทาง
    Route::get('/booking/{booking}/playback', [\App\Http\Controllers\Admin\GpsMonitoringController::class, 'playback'])->name('booking.playback');
});

// Service Providers Management (Admin)
Route::prefix('service-providers')->name('service-providers.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ServiceProviderController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\ServiceProviderController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\ServiceProviderController::class, 'store'])->name('store');
    Route::get('/{serviceProvider}', [\App\Http\Controllers\Admin\ServiceProviderController::class, 'show'])->name('show');
    Route::get('/{serviceProvider}/edit', [\App\Http\Controllers\Admin\ServiceProviderController::class, 'edit'])->name('edit');
    Route::put('/{serviceProvider}', [\App\Http\Controllers\Admin\ServiceProviderController::class, 'update'])->name('update');
    Route::delete('/{serviceProvider}', [\App\Http\Controllers\Admin\ServiceProviderController::class, 'destroy'])->name('destroy');
    Route::post('/{serviceProvider}/verify', [\App\Http\Controllers\Admin\ServiceProviderController::class, 'verify'])->name('verify');
    Route::post('/{serviceProvider}/reject', [\App\Http\Controllers\Admin\ServiceProviderController::class, 'reject'])->name('reject');
    Route::post('/{serviceProvider}/toggle-active', [\App\Http\Controllers\Admin\ServiceProviderController::class, 'toggleActive'])->name('toggle-active');
});

// Service Pricing Rules Management
Route::prefix('service-pricing-rules')->name('service-pricing-rules.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ServicePricingRuleController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\ServicePricingRuleController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\ServicePricingRuleController::class, 'store'])->name('store');
    Route::get('/{pricingRule}', [\App\Http\Controllers\Admin\ServicePricingRuleController::class, 'show'])->name('show');
    Route::get('/{pricingRule}/edit', [\App\Http\Controllers\Admin\ServicePricingRuleController::class, 'edit'])->name('edit');
    Route::put('/{pricingRule}', [\App\Http\Controllers\Admin\ServicePricingRuleController::class, 'update'])->name('update');
    Route::delete('/{pricingRule}', [\App\Http\Controllers\Admin\ServicePricingRuleController::class, 'destroy'])->name('destroy');
    Route::post('/{pricingRule}/toggle-active', [\App\Http\Controllers\Admin\ServicePricingRuleController::class, 'toggleActive'])->name('toggle-active');
});


// ============================================
// AI Rental with Cloud GPU Routes 🚀🤖
// ============================================

Route::prefix('ai-rental')->name('ai-rental.')->group(function () {
    // Dashboard
    Route::get('/', [\App\Http\Controllers\Admin\AiRentalController::class, 'dashboard'])
        ->name('dashboard');

    // Setup Guide & Tools
    Route::get('/setup-guide', [\App\Http\Controllers\Admin\AiRentalController::class, 'setupGuide'])
        ->name('setup-guide');
    Route::get('/cost-calculator', [\App\Http\Controllers\Admin\AiRentalController::class, 'costCalculator'])
        ->name('cost-calculator');

    // API Endpoints
    Route::prefix('api')->name('api.')->group(function () {
        Route::post('/calculate-cost', [\App\Http\Controllers\Admin\AiRentalController::class, 'calculateCost'])
            ->name('calculate-cost');
        Route::get('/stats', [\App\Http\Controllers\Admin\AiRentalController::class, 'getStats'])
            ->name('stats');
    });

    // Cloud Providers Management ✅ พร้อมใช้งาน!
    Route::prefix('cloud-providers')->name('cloud-providers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AiRentalCloudProviderController::class, 'index'])
            ->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\AiRentalCloudProviderController::class, 'create'])
            ->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\AiRentalCloudProviderController::class, 'store'])
            ->name('store');
        Route::get('/{cloudProvider}', [\App\Http\Controllers\Admin\AiRentalCloudProviderController::class, 'show'])
            ->name('show');
        Route::get('/{cloudProvider}/edit', [\App\Http\Controllers\Admin\AiRentalCloudProviderController::class, 'edit'])
            ->name('edit');
        Route::patch('/{cloudProvider}', [\App\Http\Controllers\Admin\AiRentalCloudProviderController::class, 'update'])
            ->name('update');
        Route::delete('/{cloudProvider}', [\App\Http\Controllers\Admin\AiRentalCloudProviderController::class, 'destroy'])
            ->name('destroy');

        // Status Management
        Route::patch('/{cloudProvider}/activate', [\App\Http\Controllers\Admin\AiRentalCloudProviderController::class, 'activate'])
            ->name('activate');
        Route::patch('/{cloudProvider}/deactivate', [\App\Http\Controllers\Admin\AiRentalCloudProviderController::class, 'deactivate'])
            ->name('deactivate');

        // Rating
        Route::post('/{cloudProvider}/rating', [\App\Http\Controllers\Admin\AiRentalCloudProviderController::class, 'updateRating'])
            ->name('rating');

        // Order (for drag & drop)
        Route::post('/update-order', [\App\Http\Controllers\Admin\AiRentalCloudProviderController::class, 'updateOrder'])
            ->name('update-order');
    });

    // My Configurations ✅ พร้อมใช้งาน!
    Route::prefix('configs')->name('configs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AiRentalConfigController::class, 'index'])
            ->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\AiRentalConfigController::class, 'create'])
            ->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\AiRentalConfigController::class, 'store'])
            ->name('store');
        Route::get('/{config}', [\App\Http\Controllers\Admin\AiRentalConfigController::class, 'show'])
            ->name('show');
        Route::get('/{config}/edit', [\App\Http\Controllers\Admin\AiRentalConfigController::class, 'edit'])
            ->name('edit');
        Route::patch('/{config}', [\App\Http\Controllers\Admin\AiRentalConfigController::class, 'update'])
            ->name('update');
        Route::delete('/{config}', [\App\Http\Controllers\Admin\AiRentalConfigController::class, 'destroy'])
            ->name('destroy');

        // Actions
        Route::post('/{config}/test', [\App\Http\Controllers\Admin\AiRentalConfigController::class, 'testConnection'])
            ->name('test');
        Route::patch('/{config}/set-default', [\App\Http\Controllers\Admin\AiRentalConfigController::class, 'setDefault'])
            ->name('set-default');
    });

    // Analytics ✅ พร้อมใช้งาน!
    Route::get('/analytics', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'analytics'])
        ->name('analytics');

    // Deployments ✅ พร้อมใช้งาน!
    Route::prefix('deployments')->name('deployments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'index'])
            ->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'create'])
            ->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'store'])
            ->name('store');
        Route::get('/{deployment}', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'show'])
            ->name('show');
        Route::delete('/{deployment}', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'destroy'])
            ->name('destroy');

        // Control Actions
        Route::patch('/{deployment}/start', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'start'])
            ->name('start');
        Route::patch('/{deployment}/stop', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'stop'])
            ->name('stop');
        Route::patch('/{deployment}/restart', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'restart'])
            ->name('restart');

        // Logs
        Route::get('/{deployment}/logs', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'logs'])
            ->name('logs');
        Route::get('/{deployment}/logs/fetch', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'fetchLogs'])
            ->name('logs.fetch');

        // Test Deployment
        Route::get('/{deployment}/test', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'test'])
            ->name('test');

        // Status Update (for callbacks)
        Route::post('/{deployment}/status', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'updateStatus'])
            ->name('update-status');
    });

    // API Endpoints ✅ พร้อมใช้งาน!
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'getStats'])
            ->name('stats');
        Route::get('/chart-data', [\App\Http\Controllers\Admin\AiRentalDeploymentController::class, 'getChartData'])
            ->name('chart-data');
    });

    // Hugging Face News ✅ พร้อมใช้งาน!
    Route::prefix('news')->name('news.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AiRentalController::class, 'news'])
            ->name('index');
        Route::get('/{news}', [\App\Http\Controllers\Admin\AiRentalController::class, 'showNews'])
            ->name('show');
    });

    // Trending Models ✅ พร้อมใช้งาน!
    Route::prefix('trending-models')->name('trending-models.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AiRentalController::class, 'trendingModels'])
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
    Route::get('/', [\App\Http\Controllers\Admin\PlatformRevenueController::class, 'index'])
        ->name('index');

    // API Stats (real-time)
    Route::get('/api/stats', [\App\Http\Controllers\Admin\PlatformRevenueController::class, 'apiStats'])
        ->name('api.stats');

    // Transactions
    Route::get('/transactions', [\App\Http\Controllers\Admin\PlatformRevenueController::class, 'transactions'])
        ->name('transactions');

    // Reports
    Route::get('/reports', [\App\Http\Controllers\Admin\PlatformRevenueController::class, 'reports'])
        ->name('reports');
    Route::get('/reports/export', [\App\Http\Controllers\Admin\PlatformRevenueController::class, 'exportReport'])
        ->name('reports.export');

    // Wallets
    Route::get('/wallets/{wallet}', [\App\Http\Controllers\Admin\PlatformRevenueController::class, 'showWallet'])
        ->name('wallets.show');

    // Payouts
    Route::prefix('payouts')->name('payouts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PayoutController::class, 'index'])
            ->name('index');
        Route::get('/settings', [\App\Http\Controllers\Admin\PayoutController::class, 'settings'])
            ->name('settings');
        Route::put('/settings/{setting}', [\App\Http\Controllers\Admin\PayoutController::class, 'updateSetting'])
            ->name('settings.update');
        Route::post('/bulk-approve', [\App\Http\Controllers\Admin\PayoutController::class, 'bulkApprove'])
            ->name('bulk-approve');
        Route::get('/api/pending', [\App\Http\Controllers\Admin\PayoutController::class, 'apiPendingPayouts'])
            ->name('api.pending');
        Route::get('/{payout}', [\App\Http\Controllers\Admin\PayoutController::class, 'show'])
            ->name('show');
        Route::post('/{payout}/approve', [\App\Http\Controllers\Admin\PayoutController::class, 'approve'])
            ->name('approve');
        Route::post('/{payout}/reject', [\App\Http\Controllers\Admin\PayoutController::class, 'reject'])
            ->name('reject');
        Route::post('/{payout}/process', [\App\Http\Controllers\Admin\PayoutController::class, 'process'])
            ->name('process');
    });

    // Earnings
    Route::prefix('earnings')->name('earnings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PayoutController::class, 'earnings'])
            ->name('index');
        Route::get('/{earning}', [\App\Http\Controllers\Admin\PayoutController::class, 'showEarning'])
            ->name('show');
    });

    // Debts
    Route::prefix('debts')->name('debts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DebtController::class, 'index'])
            ->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\DebtController::class, 'create'])
            ->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\DebtController::class, 'store'])
            ->name('store');
        Route::get('/export', [\App\Http\Controllers\Admin\DebtController::class, 'export'])
            ->name('export');
        Route::post('/batch-collect', [\App\Http\Controllers\Admin\DebtController::class, 'batchCollect'])
            ->name('batch-collect');
        Route::get('/api/stats', [\App\Http\Controllers\Admin\DebtController::class, 'apiStats'])
            ->name('api.stats');
        Route::get('/api/search-users', [\App\Http\Controllers\Admin\DebtController::class, 'searchUsers'])
            ->name('api.search-users');
        Route::get('/user/{user}', [\App\Http\Controllers\Admin\DebtController::class, 'userDebts'])
            ->name('user');
        Route::get('/{debt}', [\App\Http\Controllers\Admin\DebtController::class, 'show'])
            ->name('show');
        Route::post('/{debt}/waive', [\App\Http\Controllers\Admin\DebtController::class, 'waive'])
            ->name('waive');
        Route::delete('/{debt}', [\App\Http\Controllers\Admin\DebtController::class, 'cancel'])
            ->name('cancel');
    });

    // =========================================
    // Video Automation System
    // ระบบสร้างวีดีโออัตโนมัติ (Suno + Freepik + YouTube)
    // =========================================
    Route::prefix('video-automation')->name('video-automation.')->group(function () {

        // Dashboard
        Route::get('/', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'dashboard'])
            ->name('dashboard');
        Route::get('/stats', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'getDashboardStats'])
            ->name('stats');

        // Settings (API Keys, Credentials)
        Route::get('/settings', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'settings'])
            ->name('settings');
        Route::post('/settings', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'saveSettings'])
            ->name('settings.save');
        Route::post('/settings/test/{apiType}', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'testApiConnection'])
            ->name('settings.test');

        // Platforms (YouTube, Facebook, Instagram, TikTok, etc.)
        Route::get('/platforms', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'platforms'])
            ->name('platforms');
        Route::post('/platforms', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'savePlatform'])
            ->name('platforms.save');
        Route::delete('/platforms/{id}', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'deletePlatform'])
            ->name('platforms.delete');

        // YouTube OAuth
        Route::get('/youtube/connect', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'connectYouTube'])
            ->name('youtube.connect');
        Route::get('/youtube/callback', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'youtubeCallback'])
            ->name('youtube.callback');

        // Templates (เทมเพลตสำหรับสร้างวีดีโอ)
        Route::get('/templates', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'templates'])
            ->name('templates');
        Route::get('/templates/create', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'createTemplate'])
            ->name('templates.create');
        Route::post('/templates', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'storeTemplate'])
            ->name('templates.store');
        Route::get('/templates/{id}/edit', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'editTemplate'])
            ->name('templates.edit');
        Route::put('/templates/{id}', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'updateTemplate'])
            ->name('templates.update');
        Route::delete('/templates/{id}', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'deleteTemplate'])
            ->name('templates.delete');

        // Projects (โปรเจกต์สร้างวีดีโอ)
        Route::get('/projects', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'projects'])
            ->name('projects');
        Route::get('/projects/create', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'createProject'])
            ->name('projects.create');
        Route::post('/projects', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'storeProject'])
            ->name('projects.store');
        Route::get('/projects/{id}', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'showProject'])
            ->name('projects.show');
        Route::post('/projects/{id}/run', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'runProject'])
            ->name('projects.run');
        Route::delete('/projects/{id}', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'deleteProject'])
            ->name('projects.delete');

        // Jobs (งานที่รัน)
        Route::get('/jobs', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'jobs'])
            ->name('jobs');
        Route::get('/jobs/{id}/logs', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'getJobLogs'])
            ->name('jobs.logs');
        Route::post('/jobs/{id}/retry', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'retryJob'])
            ->name('jobs.retry');

        // Schedules (ตารางเวลาอัตโนมัติ)
        Route::get('/schedules', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'schedules'])
            ->name('schedules');
        Route::post('/schedules', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'saveSchedule'])
            ->name('schedules.save');
        Route::delete('/schedules/{id}', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'deleteSchedule'])
            ->name('schedules.delete');

        // Publish History (ประวัติการโพสต์)
        Route::get('/publish-history', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'publishHistory'])
            ->name('publish-history');
        Route::get('/publish-history/stats', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'getPublishStats'])
            ->name('publish-history.stats');
        Route::get('/publish-history/{id}', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'showPublishHistory'])
            ->name('publish-history.show');
        Route::put('/publish-history/{id}/engagement', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'updatePublishEngagement'])
            ->name('publish-history.engagement');
        Route::delete('/publish-history/{id}', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'deletePublishHistory'])
            ->name('publish-history.delete');
        Route::delete('/publish-history/{id}/source-files', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'deletePublishSourceFiles'])
            ->name('publish-history.delete-source');

        // Documentation (คู่มือการใช้งาน)
        Route::get('/documentation', [\App\Http\Controllers\Admin\VideoAutomationController::class, 'documentation'])
            ->name('documentation');
    });

    // =========================================
    // AI Content Writer System
    // =========================================
    Route::prefix('ai-content-writer')->name('ai-content-writer.')->group(function () {

        // Dashboard
        Route::get('/', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'dashboard'])
            ->name('dashboard');

        // Settings (API Keys)
        Route::get('/settings', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'settings'])
            ->name('settings');
        Route::post('/settings', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'saveSettings'])
            ->name('settings.save');
        Route::post('/settings/test/{provider}', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'testApiConnection'])
            ->name('settings.test');

        // Templates (เทมเพลตสร้าง Content)
        Route::get('/templates', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'templates'])
            ->name('templates');
        Route::get('/templates/create', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'createTemplate'])
            ->name('templates.create');
        Route::post('/templates', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'storeTemplate'])
            ->name('templates.store');
        Route::get('/templates/{id}/edit', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'editTemplate'])
            ->name('templates.edit');
        Route::put('/templates/{id}', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'updateTemplate'])
            ->name('templates.update');
        Route::delete('/templates/{id}', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'deleteTemplate'])
            ->name('templates.delete');

        // Projects (โปรเจกต์ Content)
        Route::get('/projects', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'projects'])
            ->name('projects');
        Route::get('/projects/{id}', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'showProject'])
            ->name('projects.show');
        Route::delete('/projects/{id}', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'deleteProject'])
            ->name('projects.delete');

        // Generations (ประวัติการสร้าง)
        Route::get('/generations', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'generations'])
            ->name('generations');
        Route::get('/generations/{id}', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'showGeneration'])
            ->name('generations.show');

        // Usage Logs
        Route::get('/usage-logs', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'usageLogs'])
            ->name('usage-logs');

        // Playground (ทดสอบสร้าง Content)
        Route::get('/playground', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'playground'])
            ->name('playground');
        Route::post('/playground/generate', [\App\Http\Controllers\Admin\AiContentWriterController::class, 'quickGenerate'])
            ->name('playground.generate');
    });

    // =========================================
    // Forum Management System - ระบบจัดการฟอรั่มชุมชน
    // =========================================
    Route::prefix('forum')->name('forum.')->group(function () {

        // Categories (จัดการหมวดหมู่)
        Route::get('/categories', [\App\Http\Controllers\Admin\ForumAdminController::class, 'categories'])
            ->name('categories.index');
        Route::get('/categories/create', [\App\Http\Controllers\Admin\ForumAdminController::class, 'createCategory'])
            ->name('categories.create');
        Route::post('/categories', [\App\Http\Controllers\Admin\ForumAdminController::class, 'storeCategory'])
            ->name('categories.store');
        Route::get('/categories/{category}/edit', [\App\Http\Controllers\Admin\ForumAdminController::class, 'editCategory'])
            ->name('categories.edit');
        Route::put('/categories/{category}', [\App\Http\Controllers\Admin\ForumAdminController::class, 'updateCategory'])
            ->name('categories.update');
        Route::delete('/categories/{category}', [\App\Http\Controllers\Admin\ForumAdminController::class, 'deleteCategory'])
            ->name('categories.delete');
        Route::post('/categories/reorder', [\App\Http\Controllers\Admin\ForumAdminController::class, 'reorderCategories'])
            ->name('categories.reorder');

        // Threads (จัดการกระทู้)
        Route::get('/threads', [\App\Http\Controllers\Admin\ForumAdminController::class, 'threads'])
            ->name('threads.index');
        Route::get('/threads/{thread}', [\App\Http\Controllers\Admin\ForumAdminController::class, 'showThread'])
            ->name('threads.show');
        Route::put('/threads/{thread}/pin', [\App\Http\Controllers\Admin\ForumAdminController::class, 'togglePin'])
            ->name('threads.pin');
        Route::put('/threads/{thread}/lock', [\App\Http\Controllers\Admin\ForumAdminController::class, 'toggleLock'])
            ->name('threads.lock');
        Route::put('/threads/{thread}/feature', [\App\Http\Controllers\Admin\ForumAdminController::class, 'toggleFeature'])
            ->name('threads.feature');
        Route::delete('/threads/{thread}', [\App\Http\Controllers\Admin\ForumAdminController::class, 'deleteThread'])
            ->name('threads.delete');

        // Reports (จัดการรายงาน)
        Route::get('/reports', [\App\Http\Controllers\Admin\ForumAdminController::class, 'reports'])
            ->name('reports.index');
        Route::get('/reports/{report}', [\App\Http\Controllers\Admin\ForumAdminController::class, 'showReport'])
            ->name('reports.show');
        Route::put('/reports/{report}/resolve', [\App\Http\Controllers\Admin\ForumAdminController::class, 'resolveReport'])
            ->name('reports.resolve');
        Route::put('/reports/{report}/dismiss', [\App\Http\Controllers\Admin\ForumAdminController::class, 'dismissReport'])
            ->name('reports.dismiss');

        // Trophies (จัดการถ้วยรางวัล)
        Route::get('/trophies', [\App\Http\Controllers\Admin\ForumAdminController::class, 'trophies'])
            ->name('trophies.index');
        Route::get('/trophies/create', [\App\Http\Controllers\Admin\ForumAdminController::class, 'createTrophy'])
            ->name('trophies.create');
        Route::post('/trophies', [\App\Http\Controllers\Admin\ForumAdminController::class, 'storeTrophy'])
            ->name('trophies.store');
        Route::get('/trophies/{trophy}/edit', [\App\Http\Controllers\Admin\ForumAdminController::class, 'editTrophy'])
            ->name('trophies.edit');
        Route::put('/trophies/{trophy}', [\App\Http\Controllers\Admin\ForumAdminController::class, 'updateTrophy'])
            ->name('trophies.update');
        Route::delete('/trophies/{trophy}', [\App\Http\Controllers\Admin\ForumAdminController::class, 'deleteTrophy'])
            ->name('trophies.delete');
        Route::post('/trophies/{trophy}/award/{user}', [\App\Http\Controllers\Admin\ForumAdminController::class, 'awardTrophy'])
            ->name('trophies.award');

        // Analytics (สถิติ)
        Route::get('/analytics', [\App\Http\Controllers\Admin\ForumAdminController::class, 'analytics'])
            ->name('analytics.index');

        // Settings (ตั้งค่า)
        Route::get('/settings', [\App\Http\Controllers\Admin\ForumAdminController::class, 'settings'])
            ->name('settings.index');
        Route::post('/settings', [\App\Http\Controllers\Admin\ForumAdminController::class, 'saveSettings'])
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
        Route::get('/', [\App\Http\Controllers\Admin\MarketplaceAccountController::class, 'index'])
            ->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\MarketplaceAccountController::class, 'create'])
            ->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\MarketplaceAccountController::class, 'store'])
            ->name('store');
        Route::get('/{account}', [\App\Http\Controllers\Admin\MarketplaceAccountController::class, 'show'])
            ->name('show');
        Route::get('/{account}/edit', [\App\Http\Controllers\Admin\MarketplaceAccountController::class, 'edit'])
            ->name('edit');
        Route::put('/{account}', [\App\Http\Controllers\Admin\MarketplaceAccountController::class, 'update'])
            ->name('update');
        Route::delete('/{account}', [\App\Http\Controllers\Admin\MarketplaceAccountController::class, 'destroy'])
            ->name('destroy');

        // API Actions
        Route::post('/{account}/test-connection', [\App\Http\Controllers\Admin\MarketplaceAccountController::class, 'testConnection'])
            ->name('test-connection');
        Route::post('/{account}/sync-products', [\App\Http\Controllers\Admin\MarketplaceAccountController::class, 'syncProducts'])
            ->name('sync-products');
        Route::post('/{account}/sync-orders', [\App\Http\Controllers\Admin\MarketplaceAccountController::class, 'syncOrders'])
            ->name('sync-orders');
        Route::post('/{account}/sync-all', [\App\Http\Controllers\Admin\MarketplaceAccountController::class, 'syncAll'])
            ->name('sync-all');
    });

    // =========================================
    // Marketplace Products - สินค้าจาก Marketplace
    // =========================================
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\MarketplaceProductController::class, 'index'])
            ->name('index');
        Route::get('/{product}', [\App\Http\Controllers\Admin\MarketplaceProductController::class, 'show'])
            ->name('show');
        Route::put('/{product}', [\App\Http\Controllers\Admin\MarketplaceProductController::class, 'update'])
            ->name('update');
        Route::delete('/{product}', [\App\Http\Controllers\Admin\MarketplaceProductController::class, 'destroy'])
            ->name('destroy');

        // Bulk Actions
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\MarketplaceProductController::class, 'bulkAction'])
            ->name('bulk-action');
    });

    // =========================================
    // Marketplace Orders - ออเดอร์จาก Marketplace
    // =========================================
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\MarketplaceOrderController::class, 'index'])
            ->name('index');
        Route::get('/{order}', [\App\Http\Controllers\Admin\MarketplaceOrderController::class, 'show'])
            ->name('show');
        Route::put('/{order}/status', [\App\Http\Controllers\Admin\MarketplaceOrderController::class, 'updateStatus'])
            ->name('update-status');
        Route::delete('/{order}', [\App\Http\Controllers\Admin\MarketplaceOrderController::class, 'destroy'])
            ->name('destroy');

        // Commission Actions
        Route::post('/{order}/calculate-commission', [\App\Http\Controllers\Admin\MarketplaceOrderController::class, 'calculateCommission'])
            ->name('calculate-commission');
    });

    // =========================================
    // Marketplace Commissions - คอมมิชชั่น
    // =========================================
    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\MarketplaceCommissionController::class, 'index'])
            ->name('index');
        Route::get('/{commission}', [\App\Http\Controllers\Admin\MarketplaceCommissionController::class, 'show'])
            ->name('show');
        Route::delete('/{commission}', [\App\Http\Controllers\Admin\MarketplaceCommissionController::class, 'destroy'])
            ->name('destroy');

        // Approval Actions
        Route::post('/{commission}/approve', [\App\Http\Controllers\Admin\MarketplaceCommissionController::class, 'approve'])
            ->name('approve');
        Route::post('/{commission}/pay', [\App\Http\Controllers\Admin\MarketplaceCommissionController::class, 'pay'])
            ->name('pay');
        Route::post('/{commission}/reject', [\App\Http\Controllers\Admin\MarketplaceCommissionController::class, 'reject'])
            ->name('reject');

        // Bulk Actions
        Route::post('/bulk-approve', [\App\Http\Controllers\Admin\MarketplaceCommissionController::class, 'bulkApprove'])
            ->name('bulk-approve');
        Route::post('/bulk-pay', [\App\Http\Controllers\Admin\MarketplaceCommissionController::class, 'bulkPay'])
            ->name('bulk-pay');
    });
});



// ========================================
// CLOUDFLARE MANAGEMENT SYSTEM
// ========================================
use App\Http\Controllers\Admin\CloudflareController;

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
    Route::get('/', [\App\Http\Controllers\Admin\VideoMissionController::class, 'index'])->name('index');

    // Missions CRUD
    Route::get('/missions', [\App\Http\Controllers\Admin\VideoMissionController::class, 'missions'])->name('missions');
    Route::get('/missions/create', [\App\Http\Controllers\Admin\VideoMissionController::class, 'create'])->name('create');
    Route::post('/missions', [\App\Http\Controllers\Admin\VideoMissionController::class, 'store'])->name('store');
    Route::get('/missions/{mission}', [\App\Http\Controllers\Admin\VideoMissionController::class, 'show'])->name('show');
    Route::get('/missions/{mission}/edit', [\App\Http\Controllers\Admin\VideoMissionController::class, 'edit'])->name('edit');
    Route::put('/missions/{mission}', [\App\Http\Controllers\Admin\VideoMissionController::class, 'update'])->name('update');
    Route::delete('/missions/{mission}', [\App\Http\Controllers\Admin\VideoMissionController::class, 'destroy'])->name('destroy');
    Route::post('/missions/{mission}/toggle-active', [\App\Http\Controllers\Admin\VideoMissionController::class, 'toggleActive'])->name('toggle-active');

    // Completions (การทำภารกิจ)
    Route::get('/completions', [\App\Http\Controllers\Admin\VideoMissionController::class, 'completions'])->name('completions');
    Route::get('/completions/{completion}', [\App\Http\Controllers\Admin\VideoMissionController::class, 'showCompletion'])->name('completion');
    Route::post('/completions/{completion}/verify', [\App\Http\Controllers\Admin\VideoMissionController::class, 'verifyCompletion'])->name('completion.verify');
    Route::post('/completions/{completion}/reject', [\App\Http\Controllers\Admin\VideoMissionController::class, 'rejectCompletion'])->name('completion.reject');

    // Rank Limits
    Route::get('/rank-limits', [\App\Http\Controllers\Admin\VideoMissionController::class, 'rankLimits'])->name('rank-limits');
    Route::put('/rank-limits', [\App\Http\Controllers\Admin\VideoMissionController::class, 'updateRankLimits'])->name('rank-limits.update');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Admin\VideoMissionController::class, 'settings'])->name('settings');
    Route::put('/settings', [\App\Http\Controllers\Admin\VideoMissionController::class, 'updateSettings'])->name('settings.update');

    // Reports
    Route::get('/reports', [\App\Http\Controllers\Admin\VideoMissionController::class, 'reports'])->name('reports');

    // YouTube Import
    Route::get('/import-youtube', [\App\Http\Controllers\Admin\VideoMissionController::class, 'importYouTube'])->name('import-youtube');
    Route::post('/import-youtube', [\App\Http\Controllers\Admin\VideoMissionController::class, 'processImportYouTube'])->name('import-youtube.process');
});

// ============================================
// Coin Shop Management Routes (ร้านค้า Coins)
// ============================================
Route::prefix('coin-shop')->name('coin-shop.')->group(function () {
    // Products CRUD
    Route::get('/', [\App\Http\Controllers\Admin\CoinShopController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\CoinShopController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\CoinShopController::class, 'store'])->name('store');
    Route::get('/{product}/edit', [\App\Http\Controllers\Admin\CoinShopController::class, 'edit'])->name('edit');
    Route::put('/{product}', [\App\Http\Controllers\Admin\CoinShopController::class, 'update'])->name('update');
    Route::delete('/{product}', [\App\Http\Controllers\Admin\CoinShopController::class, 'destroy'])->name('destroy');
    Route::post('/{product}/toggle-active', [\App\Http\Controllers\Admin\CoinShopController::class, 'toggleActive'])->name('toggle-active');

    // Purchases Management
    Route::get('/purchases', [\App\Http\Controllers\Admin\CoinShopController::class, 'purchases'])->name('purchases');
    Route::get('/purchases/{purchase}', [\App\Http\Controllers\Admin\CoinShopController::class, 'purchaseDetail'])->name('purchases.detail');
    Route::put('/purchases/{purchase}/status', [\App\Http\Controllers\Admin\CoinShopController::class, 'updatePurchaseStatus'])->name('purchases.update-status');
    Route::post('/purchases/{purchase}/refund', [\App\Http\Controllers\Admin\CoinShopController::class, 'refundPurchase'])->name('purchases.refund');
});

// ============================================
// Star Upgrade Price Management (ราคาอัพเกรดดาว)
// ============================================
Route::prefix('star-upgrade')->name('star-upgrade.')->group(function () {
    // จัดการราคาดาว
    Route::get('/', [\App\Http\Controllers\Admin\StarUpgradePriceController::class, 'index'])->name('index');
    Route::get('/{starPrice}/edit', [\App\Http\Controllers\Admin\StarUpgradePriceController::class, 'edit'])->name('edit');
    Route::put('/{starPrice}', [\App\Http\Controllers\Admin\StarUpgradePriceController::class, 'update'])->name('update');
    Route::post('/{starPrice}/toggle-active', [\App\Http\Controllers\Admin\StarUpgradePriceController::class, 'toggleActive'])->name('toggle-active');

    // ประวัติการอัพเกรด
    Route::get('/history', [\App\Http\Controllers\Admin\StarUpgradePriceController::class, 'history'])->name('history');
    Route::post('/history/{upgrade}/refund', [\App\Http\Controllers\Admin\StarUpgradePriceController::class, 'refund'])->name('refund');
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
// Menu Management Routes (จัดการสิทธิ์เมนู)
// ============================================
Route::prefix('menu-management')->name('menu-management.')->group(function () {
    // หน้าหลัก - แสดงรายการเมนูและการตั้งค่า
    Route::get('/', [\App\Http\Controllers\Admin\MenuManagementController::class, 'index'])
        ->name('index');

    // API: ดึงเมนูตาม Dashboard Type และ Role
    Route::get('/menus', [\App\Http\Controllers\Admin\MenuManagementController::class, 'getMenus'])
        ->name('menus');

    // API: อัพเดทการตั้งค่าเมนูสำหรับ Role
    Route::post('/role-setting', [\App\Http\Controllers\Admin\MenuManagementController::class, 'updateRoleSetting'])
        ->name('role-setting.update');

    // API: อัพเดทลำดับเมนู (Drag & Drop)
    Route::post('/order', [\App\Http\Controllers\Admin\MenuManagementController::class, 'updateOrder'])
        ->name('order.update');

    // API: อัพเดทลำดับเมนูสำหรับ Role
    Route::post('/role-order', [\App\Http\Controllers\Admin\MenuManagementController::class, 'updateRoleOrder'])
        ->name('role-order.update');

    // API: Toggle เปิด/ปิดเมนู
    Route::post('/{menuItem}/toggle-active', [\App\Http\Controllers\Admin\MenuManagementController::class, 'toggleActive'])
        ->name('toggle-active');

    // API: Toggle แสดง/ซ่อนเมนู
    Route::post('/{menuItem}/toggle-visible', [\App\Http\Controllers\Admin\MenuManagementController::class, 'toggleVisible'])
        ->name('toggle-visible');

    // API: Bulk toggle สำหรับ Role
    Route::post('/bulk-toggle-role', [\App\Http\Controllers\Admin\MenuManagementController::class, 'bulkToggleRole'])
        ->name('bulk-toggle-role');

    // API: แก้ไขข้อมูลเมนู
    Route::put('/{menuItem}', [\App\Http\Controllers\Admin\MenuManagementController::class, 'update'])
        ->name('update');

    // API: ซิงค์เมนูจาก config
    Route::post('/sync-from-config', [\App\Http\Controllers\Admin\MenuManagementController::class, 'syncFromConfig'])
        ->name('sync-from-config');

    // API: รีเซ็ตการตั้งค่า Role
    Route::post('/reset-role-settings', [\App\Http\Controllers\Admin\MenuManagementController::class, 'resetRoleSettings'])
        ->name('reset-role-settings');
});
