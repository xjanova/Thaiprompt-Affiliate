<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\AffiliateController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\InvestmentController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\UserGuideController;
use App\Http\Controllers\Admin\HeaderSettingsController;
use App\Http\Controllers\Admin\SecurityController;
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
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\MembershipRetentionController as AdminRetentionController;
use App\Http\Controllers\Admin\LineOaController;
use App\Http\Controllers\Admin\LineBotAiController;
use App\Http\Controllers\Admin\LineFlexMessageController;
use App\Http\Controllers\Admin\LineRichMenuController;
use App\Http\Controllers\Admin\LineChatWidgetController;
use App\Http\Controllers\Admin\LineAvatarController;
use App\Http\Controllers\Admin\LineBroadcastController;
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

// Affiliate Management
// Note: Specific routes must be defined BEFORE Route::resource to avoid conflicts
Route::get('affiliates/tree-view', [AffiliateController::class, 'treeView'])->name('affiliates.tree');
Route::get('affiliates/tree-interactive', [AffiliateController::class, 'treeViewInteractive'])->name('affiliates.tree.interactive');
Route::get('affiliates/{affiliate}/tree', [AffiliateController::class, 'tree'])->name('affiliates.tree.single');
Route::post('affiliates/{affiliate}/move', [AffiliateController::class, 'move'])->name('affiliates.move');
Route::resource('affiliates', AffiliateController::class);

// Commission Management
Route::resource('commissions', CommissionController::class);
Route::post('commissions/{commission}/approve', [CommissionController::class, 'approve'])->name('commissions.approve');
Route::post('commissions/{commission}/reject', [CommissionController::class, 'reject'])->name('commissions.reject');
Route::post('commissions/{commission}/pay', [CommissionController::class, 'pay'])->name('commissions.pay');
Route::post('commissions/bulk-approve', [CommissionController::class, 'bulkApprove'])->name('commissions.bulk-approve');
Route::post('commissions/bulk-reject', [CommissionController::class, 'bulkReject'])->name('commissions.bulk-reject');

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

// Site Settings (โลโก้, Favicon, ชื่อเว็บไซต์, SEO, Social Media)
Route::prefix('site-settings')->name('site-settings.')->group(function () {
    Route::get('/', [SiteSettingsController::class, 'index'])->name('index');
    Route::put('/', [SiteSettingsController::class, 'update'])->name('update');
    Route::delete('/logo', [SiteSettingsController::class, 'deleteLogo'])->name('logo.delete');
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
    Route::get('/{id}/show', [WalletController::class, 'showWallet'])->name('show');
    Route::post('/{id}/adjust-balance', [WalletController::class, 'adjustBalance'])->name('adjust-balance');
    Route::post('/{id}/refund', [WalletController::class, 'refund'])->name('refund');
    Route::post('/{id}/rollback-transaction', [WalletController::class, 'rollbackTransaction'])->name('rollback-transaction');
    Route::post('/{id}/lock', [WalletController::class, 'lockUserWallet'])->name('lock-user');
    Route::post('/{id}/unlock', [WalletController::class, 'unlockUserWallet'])->name('unlock-user');
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
    Route::get('/{nfcCard}', [NFCCardController::class, 'show'])->name('show');
    Route::get('/{nfcCard}/edit', [NFCCardController::class, 'edit'])->name('edit');
    Route::put('/{nfcCard}', [NFCCardController::class, 'update'])->name('update');
    Route::delete('/{nfcCard}', [NFCCardController::class, 'destroy'])->name('destroy');
    Route::get('/{nfcCard}/pair', [NFCCardController::class, 'pairForm'])->name('pair-form');
    Route::post('/{nfcCard}/pair', [NFCCardController::class, 'pair'])->name('pair');
    Route::post('/{nfcCard}/unpair', [NFCCardController::class, 'unpair'])->name('unpair');
    Route::post('/{nfcCard}/activate', [NFCCardController::class, 'activate'])->name('activate');
    Route::post('/{nfcCard}/deactivate', [NFCCardController::class, 'deactivate'])->name('deactivate');
    Route::post('/{nfcCard}/block', [NFCCardController::class, 'block'])->name('block');
    Route::post('/{nfcCard}/unblock', [NFCCardController::class, 'unblock'])->name('unblock');
    Route::get('/{nfcCard}/topup', [NFCCardController::class, 'topUpForm'])->name('topup-form');
    Route::post('/{nfcCard}/topup', [NFCCardController::class, 'topUp'])->name('topup');
    Route::post('/read', [NFCCardController::class, 'read'])->name('read');
    Route::get('/export', [NFCCardController::class, 'export'])->name('export');
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
});

// Email Management (Email Delivery System)
Route::prefix('email')->name('email.')->group(function () {
    // Dashboard
    Route::get('/', [EmailController::class, 'index'])->name('index');
    Route::get('/statistics', [EmailController::class, 'statistics'])->name('statistics');

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
    Route::post('/test-message', [LineOaController::class, 'testMessage'])->name('test-message');
    Route::post('/test-connection', [LineOaController::class, 'testConnection'])->name('test-connection');
    Route::get('/line-users', [LineOaController::class, 'getLineUsers'])->name('line-users');
    Route::get('/logs', [LineOaController::class, 'logs'])->name('logs');

    // Analytics Dashboard
    Route::get('/analytics', [\App\Http\Controllers\Admin\LineAnalyticsController::class, 'index'])->name('analytics');
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

    // Flex Messages
    Route::prefix('flex')->name('flex.')->group(function () {
        Route::get('/', [LineFlexMessageController::class, 'index'])->name('index');
        Route::get('/create', [LineFlexMessageController::class, 'create'])->name('create');
        Route::post('/', [LineFlexMessageController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [LineFlexMessageController::class, 'edit'])->name('edit');
        Route::put('/{id}', [LineFlexMessageController::class, 'update'])->name('update');
        Route::delete('/{id}', [LineFlexMessageController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/preview', [LineFlexMessageController::class, 'preview'])->name('preview');
        Route::post('/{id}/test', [LineFlexMessageController::class, 'testSend'])->name('test');
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

    // Chat Widget
    Route::prefix('chat-widget')->name('chat-widget.')->group(function () {
        Route::get('/', [LineChatWidgetController::class, 'index'])->name('index');
        Route::put('/', [LineChatWidgetController::class, 'update'])->name('update');
    });

    // Avatars
    Route::prefix('avatars')->name('avatars.')->group(function () {
        Route::get('/', [LineAvatarController::class, 'index'])->name('index');
        Route::get('/create', [LineAvatarController::class, 'create'])->name('create');
        Route::post('/', [LineAvatarController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [LineAvatarController::class, 'edit'])->name('edit');
        Route::put('/{id}', [LineAvatarController::class, 'update'])->name('update');
        Route::delete('/{id}', [LineAvatarController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/set-default', [LineAvatarController::class, 'setDefault'])->name('set-default');
    });

    // Broadcast
    Route::prefix('broadcast')->name('broadcast.')->group(function () {
        Route::get('/', [LineBroadcastController::class, 'index'])->name('index');
        Route::get('/create', [LineBroadcastController::class, 'create'])->name('create');
        Route::post('/', [LineBroadcastController::class, 'store'])->name('store');
        Route::get('/{id}', [LineBroadcastController::class, 'show'])->name('show');
        Route::post('/{id}/send', [LineBroadcastController::class, 'send'])->name('send');
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
    });
});

// LINE Membership Signup Management (AI-Powered Signup System)
Route::prefix('line-membership-signup')->name('line-membership-signup.')->group(function () {
    // Dashboard
    Route::get('/', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'index'])->name('index');

    // Sessions Management
    Route::get('/sessions', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'sessions'])->name('sessions');
    Route::get('/sessions/{session}', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'showSession'])->name('sessions.show');

    // Templates Management
    Route::get('/templates', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'templates'])->name('templates');
    Route::post('/templates', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'createTemplate'])->name('templates.create');
    Route::put('/templates/{template}', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'updateTemplate'])->name('templates.update');
    Route::delete('/templates/{template}', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'deleteTemplate'])->name('templates.delete');

    // Invitations Management
    Route::get('/invitations', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'invitations'])->name('invitations');

    // Rewards Management
    Route::get('/rewards', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'rewards'])->name('rewards');
    Route::post('/rewards/{reward}/grant', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'grantReward'])->name('rewards.grant');

    // Analytics API
    Route::get('/analytics/data', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'analyticsData'])->name('analytics.data');

    // Export
    Route::get('/export/sessions', [\App\Http\Controllers\Admin\LineMembershipSignupAdminController::class, 'exportSessions'])->name('export.sessions');
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
});

// Instructor Dashboard - For Course Instructors
Route::prefix('instructor')->name('instructor.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\InstructorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/course/{id}/analytics', [\App\Http\Controllers\Admin\InstructorDashboardController::class, 'courseAnalytics'])->name('course.analytics');
    Route::get('/earnings', [\App\Http\Controllers\Admin\InstructorDashboardController::class, 'earnings'])->name('earnings');
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
        Route::post('/', [ECommerceController::class, 'storeProduct'])->name('store');
        Route::get('/{product}', [ECommerceController::class, 'showProduct'])->name('show');
        Route::get('/{product}/edit', [ECommerceController::class, 'editProduct'])->name('edit');
        Route::put('/{product}', [ECommerceController::class, 'updateProduct'])->name('update');
        Route::delete('/{product}', [ECommerceController::class, 'deleteProduct'])->name('delete');
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
    });

    // MLM Genealogy Viewer
    Route::get('/genealogy', [\App\Http\Controllers\Admin\MlmPlanController::class, 'genealogy'])->name('genealogy.index');

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

// Hotel Owner Management (Super Admin)
Route::prefix('hotel-owners')->name('hotel-owners.')->group(function () {
    Route::get('/', [HotelOwnerController::class, 'index'])->name('index');
    Route::get('/statistics', [HotelOwnerController::class, 'statistics'])->name('statistics');
    Route::post('/', [HotelOwnerController::class, 'store'])->name('store');
    Route::get('/{id}', [HotelOwnerController::class, 'show'])->name('show');
    Route::put('/{id}', [HotelOwnerController::class, 'update'])->name('update');
    Route::delete('/{id}', [HotelOwnerController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/block', [HotelOwnerController::class, 'block'])->name('block');
    Route::post('/{id}/unblock', [HotelOwnerController::class, 'unblock'])->name('unblock');
    Route::post('/{id}/assign-hotel', [HotelOwnerController::class, 'assignHotel'])->name('assign-hotel');
    Route::delete('/{id}/unassign-hotel', [HotelOwnerController::class, 'unassignHotel'])->name('unassign-hotel');
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

// Bot Automation System Routes
require __DIR__.'/bot_automation.php';
