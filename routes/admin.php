<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\AffiliateController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PremiumPageController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Admin\WalletSettingsController;
use App\Http\Controllers\Admin\CashbackSettingController;
use App\Http\Controllers\Admin\LanguageSettingController;
use App\Http\Controllers\Admin\TranslationMappingController;
use App\Http\Controllers\Admin\NotificationManagementController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\HeaderEditorController;
use App\Http\Controllers\Admin\VisualBuilderController;
use App\Http\Controllers\Admin\TemplateController;
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
use App\Http\Controllers\Admin\KycController;
use App\Http\Controllers\Admin\MlmGlobalSettingController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\AcademySettingsController;
use App\Http\Controllers\Admin\CertificateManagementController;
use App\Http\Controllers\Admin\Accounting\AccountingDashboardController;
use App\Http\Controllers\Admin\Accounting\InvoiceController;
use App\Http\Controllers\Admin\Accounting\ExpenseController;
use App\Http\Controllers\Admin\Accounting\ContactController;
use App\Http\Controllers\Admin\Accounting\ProductController;
use App\Http\Controllers\Admin\Accounting\ReportController;
use App\Http\Controllers\Admin\Accounting\FlowAccountController;
use App\Http\Controllers\Admin\PosDashboardController;
use App\Http\Controllers\Admin\PosDeviceController;
use App\Http\Controllers\Admin\PosTransactionController;
use App\Http\Controllers\Admin\PosAdvertisementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// User Management
Route::resource('users', UserController::class);
Route::get('users/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');
Route::put('users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('users.permissions.update');
Route::get('users/{user}/dashboard', [UserController::class, 'viewDashboard'])->name('users.dashboard');

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

// Header Editor
Route::get('header-editor', [HeaderEditorController::class, 'index'])->name('header-editor.index');
Route::post('header-editor', [HeaderEditorController::class, 'update'])->name('header-editor.update');
Route::post('header-editor/reset', [HeaderEditorController::class, 'reset'])->name('header-editor.reset');
Route::post('header-editor/template', [HeaderEditorController::class, 'updateTemplate'])->name('header-editor.template');
Route::get('header-editor/menu-items', [HeaderEditorController::class, 'getMenuItems'])->name('header-editor.menu-items.index');
Route::post('header-editor/menu-items', [HeaderEditorController::class, 'storeMenuItem'])->name('header-editor.menu-items.store');
Route::put('header-editor/menu-items/{id}', [HeaderEditorController::class, 'updateMenuItem'])->name('header-editor.menu-items.update');
Route::delete('header-editor/menu-items/{id}', [HeaderEditorController::class, 'deleteMenuItem'])->name('header-editor.menu-items.delete');
Route::post('header-editor/menu-items/reorder', [HeaderEditorController::class, 'reorderMenuItems'])->name('header-editor.menu-items.reorder');

// Pages Management (CMS)
Route::resource('pages', PageController::class);
Route::post('pages/reorder', [PageController::class, 'reorder'])->name('pages.reorder');

// Premium Landing Page Management
Route::get('premium-page', [PremiumPageController::class, 'index'])->name('premium-page.index');
Route::get('premium-page/{section}/edit', [PremiumPageController::class, 'edit'])->name('premium-page.edit');
Route::put('premium-page/{section}', [PremiumPageController::class, 'update'])->name('premium-page.update');
Route::post('premium-page/{section}/toggle', [PremiumPageController::class, 'toggle'])->name('premium-page.toggle');

// Visual Builder (Drag & Drop Page Builder) - Now Template Based
Route::prefix('templates')->name('templates.')->group(function () {
    // Template Management
    Route::get('/', [TemplateController::class, 'index'])->name('index');
    Route::get('/create', [TemplateController::class, 'create'])->name('create');
    Route::post('/', [TemplateController::class, 'store'])->name('store');
    Route::get('/{template}', [TemplateController::class, 'show'])->name('show');
    Route::put('/{template}', [TemplateController::class, 'update'])->name('update');
    Route::delete('/{template}', [TemplateController::class, 'destroy'])->name('destroy');
    Route::post('/{template}/duplicate', [TemplateController::class, 'duplicate'])->name('duplicate');
    Route::post('/{template}/set-default', [TemplateController::class, 'setDefault'])->name('set-default');

    // Section Management within Template
    Route::get('/{template}/sections', [TemplateController::class, 'getSections'])->name('sections.index');
    Route::post('/{template}/sections', [TemplateController::class, 'addSection'])->name('sections.store');
    Route::put('/{template}/sections/{section}', [TemplateController::class, 'updateSection'])->name('sections.update');
    Route::delete('/{template}/sections/{section}', [TemplateController::class, 'deleteSection'])->name('sections.destroy');
    Route::post('/{template}/sections/{section}/duplicate', [TemplateController::class, 'duplicateSection'])->name('sections.duplicate');
    Route::post('/{template}/sections/reorder', [TemplateController::class, 'reorderSections'])->name('sections.reorder');

    // Import/Export
    Route::get('/{template}/export', [TemplateController::class, 'export'])->name('export');
    Route::post('/import', [TemplateController::class, 'import'])->name('import');
});

// Keep old Visual Builder route for backward compatibility (redirect to templates)
Route::prefix('visual-builder')->name('visual-builder.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.templates.index');
    })->name('index');
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

// Translation Mapping Management (Custom Translations)
Route::prefix('translations')->name('translations.')->group(function () {
    Route::get('/', [TranslationMappingController::class, 'index'])->name('index');
    Route::get('/create', [TranslationMappingController::class, 'create'])->name('create');
    Route::post('/', [TranslationMappingController::class, 'store'])->name('store');
    Route::get('/{mapping}/edit', [TranslationMappingController::class, 'edit'])->name('edit');
    Route::put('/{mapping}', [TranslationMappingController::class, 'update'])->name('update');
    Route::delete('/{mapping}', [TranslationMappingController::class, 'destroy'])->name('destroy');
    Route::post('/{mapping}/toggle', [TranslationMappingController::class, 'toggle'])->name('toggle');
    Route::post('/import', [TranslationMappingController::class, 'import'])->name('import');
    Route::get('/export', [TranslationMappingController::class, 'export'])->name('export');
});

// Notification Management
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
    Route::get('/templates', [EmailController::class, 'templates'])->name('templates');
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

// MLM System Management
Route::prefix('mlm')->name('mlm.')->group(function () {
    // MLM Plans
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\MlmPlanController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\MlmPlanController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\MlmPlanController::class, 'store'])->name('store');
        Route::get('/{plan}/edit', [\App\Http\Controllers\Admin\MlmPlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [\App\Http\Controllers\Admin\MlmPlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [\App\Http\Controllers\Admin\MlmPlanController::class, 'destroy'])->name('destroy');
        Route::post('/{plan}/toggle-status', [\App\Http\Controllers\Admin\MlmPlanController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{plan}/set-default', [\App\Http\Controllers\Admin\MlmPlanController::class, 'setDefault'])->name('set-default');
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
        Route::get('/get-settings', [MlmGlobalSettingController::class, 'getSettings'])->name('get-settings');
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
