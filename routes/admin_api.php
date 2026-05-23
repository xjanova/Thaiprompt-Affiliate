<?php

use App\Http\Controllers\Api\Admin\Ai\AiBotsController;
use App\Http\Controllers\Api\Admin\Ai\AiDashboardController;
use App\Http\Controllers\Api\Admin\Ai\AiPlaygroundController;
use App\Http\Controllers\Api\Admin\Ai\AiProvidersController;
use App\Http\Controllers\Api\Admin\AnalyticsController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\ChatController;
use App\Http\Controllers\Api\Admin\EveController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\Finance\PaymentReconController;
use App\Http\Controllers\Api\Admin\Finance\WalletController;
use App\Http\Controllers\Api\Admin\Finance\WithdrawalController;
use App\Http\Controllers\Api\Admin\Fortune\FortuneDashboardController;
use App\Http\Controllers\Api\Admin\Fortune\FortuneReadingsController;
use App\Http\Controllers\Api\Admin\Marketplace\MarketplaceDashboardController;
use App\Http\Controllers\Api\Admin\Marketplace\MarketplaceOrdersController;
use App\Http\Controllers\Api\Admin\ModerationController;
use App\Http\Controllers\Api\Admin\PairingController;
use App\Http\Controllers\Api\Admin\RanksController;
use App\Http\Controllers\Api\Admin\UsersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Mobile API Routes
|--------------------------------------------------------------------------
|
| Routes สำหรับ Thaiprompt Admin Mobile App (Flutter)
| Mounted ภายใต้ /api/admin/*
|
| Authentication: Laravel Sanctum + AdminApiMiddleware
| Token abilities: ['admin'] (ออกจาก /api/admin/login หรือ /api/admin/pair/claim)
|
| Convention:
| - JSON envelope: { success: bool, data: any, message?: string, errors?: object }
| - HTTP status: 200/201 success, 401 unauth, 403 forbidden, 422 validation, 500 server
| - Pagination: Laravel default { data, current_page, last_page, per_page, total, links }
|
*/

// ────────────────────────────────────────────────────────────
// Public endpoints (ไม่ต้อง login) — สำหรับ login flow
// ────────────────────────────────────────────────────────────
Route::prefix('auth')->name('api.admin.auth.')->group(function () {

    // Login ด้วย email + password (จะคืน { requires_2fa, challenge_token } ถ้าเปิด 2FA)
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    // ยืนยัน 2FA จาก challenge_token → ได้ admin Sanctum token จริง
    Route::post('/verify-2fa', [AuthController::class, 'verifyTwoFactor'])->name('verify-2fa');

    // App ส่ง pair_code (จาก QR ที่สแกน) → ได้ admin token
    Route::post('/pair/claim', [PairingController::class, 'claim'])->name('pair.claim');

    // App polling สถานะ pair_code (เผื่อต้องรอ admin กดยืนยันที่ web)
    Route::get('/pair/status', [PairingController::class, 'status'])->name('pair.status');
});

// ────────────────────────────────────────────────────────────
// Authenticated endpoints (ต้อง admin token)
// ────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'admin.api'])->group(function () {

    // ── Auth/Profile ──
    Route::prefix('auth')->name('api.admin.auth.')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');

        // สร้าง pair_code (เฉพาะ admin ที่ login web อยู่ → POST จาก admin web เพื่อสร้าง QR)
        Route::post('/pair/init', [PairingController::class, 'init'])->name('pair.init');
        Route::post('/pair/cancel', [PairingController::class, 'cancel'])->name('pair.cancel');
    });

    // ── Dashboard ──
    Route::prefix('dashboard')->name('api.admin.dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/sparkline', [DashboardController::class, 'sparkline'])->name('sparkline');
    });

    // ── Finance: Wallets ──
    Route::prefix('finance/wallets')->name('api.admin.finance.wallets.')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::get('/system-stats', [WalletController::class, 'systemStats'])->name('system-stats');
        Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');
        Route::get('/{wallet}', [WalletController::class, 'show'])->name('show');
        Route::post('/{wallet}/adjust', [WalletController::class, 'adjustBalance'])->name('adjust');
        Route::post('/{wallet}/lock', [WalletController::class, 'lock'])->name('lock');
        Route::post('/{wallet}/unlock', [WalletController::class, 'unlock'])->name('unlock');
        Route::post('/{wallet}/suspend', [WalletController::class, 'suspend'])->name('suspend');
        Route::post('/{wallet}/unsuspend', [WalletController::class, 'unsuspend'])->name('unsuspend');
    });

    // ── Finance: Withdrawals ──
    Route::prefix('finance/withdrawals')->name('api.admin.finance.withdrawals.')->group(function () {
        Route::get('/', [WithdrawalController::class, 'index'])->name('index');
        Route::get('/pending', [WithdrawalController::class, 'pending'])->name('pending');
        Route::get('/{withdrawal}', [WithdrawalController::class, 'show'])->name('show');
        Route::post('/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('approve');
        Route::post('/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('reject');
        Route::post('/{withdrawal}/complete', [WithdrawalController::class, 'complete'])->name('complete');
        Route::post('/batch-approve', [WithdrawalController::class, 'batchApprove'])->name('batch-approve');
    });

    // ── AI: Dashboard ──
    Route::prefix('ai/dashboard')->name('api.admin.ai.dashboard.')->group(function () {
        Route::get('/', [AiDashboardController::class, 'index'])->name('index');
        Route::get('/timeseries', [AiDashboardController::class, 'timeseries'])->name('timeseries');
    });

    // ── AI: Per-provider Usage (Warroom /usage) ──
    Route::get('ai/usage/per-provider', [AiDashboardController::class, 'perProviderUsage'])
        ->name('api.admin.ai.usage.per-provider');

    // ── Fortune: Worker queue monitor (Warroom /workers) ──
    Route::get('fortune/workers/queue', [FortuneDashboardController::class, 'workersQueue'])
        ->name('api.admin.fortune.workers.queue');

    // ── Fortune: Behavioral triage (Warroom dashboard triage queue) ──
    Route::get('fortune/triage/behavior', [FortuneDashboardController::class, 'triageBehavior'])
        ->name('api.admin.fortune.triage.behavior');

    // ── AI: Providers ──
    Route::prefix('ai/providers')->name('api.admin.ai.providers.')->group(function () {
        Route::get('/', [AiProvidersController::class, 'index'])->name('index');
        Route::get('/{provider}', [AiProvidersController::class, 'show'])->name('show');
        Route::post('/{provider}/toggle', [AiProvidersController::class, 'toggle'])->name('toggle');
        Route::post('/{provider}/test-connection', [AiProvidersController::class, 'testConnection'])->name('test-connection');
    });

    // ── AI: Bots ──
    Route::prefix('ai/bots')->name('api.admin.ai.bots.')->group(function () {
        Route::get('/', [AiBotsController::class, 'index'])->name('index');
        Route::get('/{bot}', [AiBotsController::class, 'show'])->name('show');
        Route::post('/{bot}/toggle', [AiBotsController::class, 'toggle'])->name('toggle');
        Route::post('/{bot}/test', [AiBotsController::class, 'test'])->name('test');
    });

    // ── AI Playground (Warroom /predict) ──
    Route::prefix('ai/playground')->name('api.admin.ai.playground.')->group(function () {
        Route::get('/providers', [AiPlaygroundController::class, 'providers'])->name('providers');
        Route::post('/run', [AiPlaygroundController::class, 'run'])->name('run');
    });

    // ── Payment Reconciliation (Warroom /payment) ──
    Route::prefix('payment')->name('api.admin.payment.')->group(function () {
        Route::get('/sms/inbox', [PaymentReconController::class, 'inbox'])->name('sms.inbox');
        Route::get('/recon/stats', [PaymentReconController::class, 'stats'])->name('recon.stats');
        Route::post('/sms/{sms}/match', [PaymentReconController::class, 'match'])->name('sms.match');
        Route::post('/sms/{sms}/reject', [PaymentReconController::class, 'reject'])->name('sms.reject');
    });

    // ── Chat (Warroom /chat takeover compose) ──
    Route::prefix('chat')->name('api.admin.chat.')->group(function () {
        Route::post('/send', [ChatController::class, 'send'])->name('send');
        Route::post('/suggest', [ChatController::class, 'suggest'])->name('suggest');
    });

    // ── Eve (Warroom AI assistant — operator-facing) ──
    Route::prefix('eve')->name('api.admin.eve.')->group(function () {
        Route::post('/chat', [EveController::class, 'chat'])->name('chat');
        // Aggregated mission-control signals Eve reasons over.
        Route::get('/signals', [EveController::class, 'signals'])->name('signals');
    });

    // ── Moderation (Warroom /moderation) ──
    Route::prefix('moderation')->name('api.admin.moderation.')->group(function () {
        Route::get('/suspects', [ModerationController::class, 'suspects'])->name('suspects');
        Route::get('/banned', [ModerationController::class, 'banned'])->name('banned');
        Route::post('/ban', [ModerationController::class, 'ban'])->name('ban');
        Route::post('/unban/{ban}', [ModerationController::class, 'unban'])->name('unban');
        Route::get('/rules', [ModerationController::class, 'rules'])->name('rules');
        Route::put('/rules', [ModerationController::class, 'updateRules'])->name('rules.update');
    });

    // ── Fortune (ดูดวง) ──
    Route::prefix('fortune')->name('api.admin.fortune.')->group(function () {
        Route::get('/dashboard', [FortuneDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('readings')->name('readings.')->group(function () {
            Route::get('/', [FortuneReadingsController::class, 'index'])->name('index');
            Route::get('/stats', [FortuneReadingsController::class, 'stats'])->name('stats');
            Route::get('/{reading}', [FortuneReadingsController::class, 'show'])->name('show');
            // Conversation history (initial Q+A from row + admin replies from fortune_admin_qa)
            Route::get('/{reading}/transcript', [FortuneReadingsController::class, 'transcript'])->name('transcript');
            // Admin actions on a reading (used by warroom /bills page)
            Route::post('/{reading}/mark-paid', [FortuneReadingsController::class, 'markPaid'])->name('mark-paid');
            Route::post('/{reading}/refund', [FortuneReadingsController::class, 'refund'])->name('refund');
            Route::post('/{reading}/cancel', [FortuneReadingsController::class, 'cancel'])->name('cancel');
        });
    });

    // ── Users + MLM ──
    Route::prefix('users')->name('api.admin.users.')->group(function () {
        Route::get('/', [UsersController::class, 'index'])->name('index');
        Route::get('/stats', [UsersController::class, 'stats'])->name('stats');
        // Live presence — must come BEFORE /{user} to avoid matching "admins" as a user id.
        Route::get('/admins/online', [UsersController::class, 'adminsOnline'])->name('admins.online');
        Route::get('/{user}', [UsersController::class, 'show'])->name('show');
        Route::get('/{user}/readings', [UsersController::class, 'readings'])->name('readings');
    });

    Route::prefix('ranks')->name('api.admin.ranks.')->group(function () {
        Route::get('/', [RanksController::class, 'index'])->name('index');
    });

    // ── Marketplace ──
    Route::prefix('marketplace')->name('api.admin.marketplace.')->group(function () {
        Route::get('/dashboard', [MarketplaceDashboardController::class, 'index'])->name('dashboard');
        Route::get('/orders', [MarketplaceOrdersController::class, 'index'])->name('orders.index');
    });

    // ── Analytics ──
    Route::prefix('analytics')->name('api.admin.analytics.')->group(function () {
        Route::get('/overview', [AnalyticsController::class, 'overview'])->name('overview');
    });
});
