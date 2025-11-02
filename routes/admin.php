<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
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
use App\Http\Controllers\Admin\OtpSettingsController;
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

// Settings
Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
Route::post('settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding');
Route::put('settings/theme', [SettingsController::class, 'updateTheme'])->name('settings.theme');

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

// Language Settings
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('languages', [LanguageSettingController::class, 'index'])->name('languages');
    Route::post('languages', [LanguageSettingController::class, 'update'])->name('languages.update');
    Route::post('languages/{code}/toggle', [LanguageSettingController::class, 'toggle'])->name('languages.toggle');
    Route::put('languages/{code}', [LanguageSettingController::class, 'updateLanguage'])->name('languages.update-single');
    Route::post('languages/reorder', [LanguageSettingController::class, 'reorder'])->name('languages.reorder');
    Route::get('languages/switcher', [LanguageSettingController::class, 'getSwitcherSettings'])->name('languages.switcher');
    Route::put('languages/switcher', [LanguageSettingController::class, 'updateSwitcherSettings'])->name('languages.switcher.update');
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
    Route::get('/logs', [LineOaController::class, 'logs'])->name('logs');
});

// OTP Settings Management
Route::prefix('otp')->name('otp.')->group(function () {
    Route::get('/settings', [OtpSettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [OtpSettingsController::class, 'update'])->name('settings.update');
    Route::post('/test', [OtpSettingsController::class, 'test'])->name('test');
});
