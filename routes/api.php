<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\TreeController;
use App\Http\Controllers\Api\RankController;
use App\Http\Controllers\Api\InvestmentController;
use App\Http\Controllers\Api\CryptoWalletApiController;
use App\Http\Controllers\Api\NFCCardApiController;
use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\AiGenController;
use App\Http\Controllers\Api\AiGenPackageController;
use App\Http\Controllers\Api\TrendApiController;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\Api\VideoRewardController;
use App\Http\Controllers\Api\VideoWatchController;
use App\Http\Controllers\Api\VideoQuestController;
use App\Http\Controllers\Api\CoinExchangeController;
use App\Http\Controllers\Api\FoodPassportController;
use App\Http\Controllers\Api\TraceabilityController;
use App\Http\Controllers\Api\QualityController;
use App\Http\Controllers\Api\CarbonCreditController;
use App\Http\Controllers\Api\CertificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Webhooks (no CSRF, no auth)
// LINE Webhook with rate limiting and signature verification
Route::post('/webhook/line', [LineWebhookController::class, 'handle'])
    ->middleware(['line.webhook.throttle'])
    ->name('api.line.webhook');

// LINE Membership Signup Webhook
Route::post('/webhook/line-membership-signup', [\App\Http\Controllers\LineMembershipSignupController::class, 'webhook'])
    ->middleware(['line.webhook.throttle'])
    ->name('api.line.membership.signup.webhook');

// GitHub Release Webhook (auto-clear version cache)
Route::post('/webhooks/github/release', [\App\Http\Controllers\Api\WebhookController::class, 'handleGitHubRelease'])
    ->name('api.webhook.github.release');

// Cookie Consent API (no auth required)
Route::post('/cookie-consent', [\App\Http\Controllers\CookieConsentController::class, 'store']);
Route::get('/cookie-consent', [\App\Http\Controllers\CookieConsentController::class, 'getConsent']);
Route::post('/cookie-track-page', [\App\Http\Controllers\CookieConsentController::class, 'trackPage']);
Route::post('/cookie-track-keyword', [\App\Http\Controllers\CookieConsentController::class, 'trackKeyword']);
Route::post('/cookie-track-product', [\App\Http\Controllers\CookieConsentController::class, 'trackProduct']);

// Payment Gateway Webhooks
Route::prefix('webhook')->name('api.webhook.')->group(function () {
    Route::post('/paysolutions', [\App\Http\Controllers\PaymentWebhookController::class, 'handlePaySolutions'])
        ->middleware('webhook.verify:paysolutions')
        ->name('paysolutions');

    Route::post('/promptpay', [\App\Http\Controllers\PaymentWebhookController::class, 'handlePromptPay'])
        ->middleware('webhook.verify:promptpay')
        ->name('promptpay');

    Route::post('/stripe', [\App\Http\Controllers\PaymentWebhookController::class, 'handleStripe'])
        ->middleware('webhook.verify:stripe')
        ->name('stripe');

    Route::post('/omise', [\App\Http\Controllers\PaymentWebhookController::class, 'handleOmise'])
        ->middleware('webhook.verify:omise')
        ->name('omise');
});

// API v1
Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'login']);

    // App settings (public)
    Route::get('/settings', [DashboardController::class, 'settings']);

    // Ranks (public - for marketing tools)
    Route::get('/ranks', [RankController::class, 'index']);

    // App Configuration (public)
    Route::prefix('app')->group(function () {
        Route::get('/maintenance-status', [AppConfigController::class, 'maintenanceStatus']);

        // Emergency Alert Banners (public - anyone can view)
        Route::get('/banners', [\App\Http\Controllers\Admin\AppBannerController::class, 'apiBanners']);
        Route::post('/banners/{appBanner}/view', [\App\Http\Controllers\Admin\AppBannerController::class, 'trackView']);
        Route::post('/banners/{appBanner}/click', [\App\Http\Controllers\Admin\AppBannerController::class, 'trackClick']);
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Dashboard
        Route::get('/dashboard/statistics', [DashboardController::class, 'statistics']);
        Route::get('/dashboard/commissions', [DashboardController::class, 'commissions']);
        Route::get('/dashboard/referrals', [DashboardController::class, 'referrals']);

        // Tree (Organization Chart)
        Route::prefix('tree')->group(function () {
            Route::get('/user', [TreeController::class, 'getUserTree']);
            Route::get('/admin/{affiliateId?}', [TreeController::class, 'getAdminTree']);

            // Binary Tree
            Route::get('/binary', [TreeController::class, 'getUserBinaryTree']);
            Route::get('/binary/admin/{affiliateId?}', [TreeController::class, 'getAdminBinaryTree']);
        });

        // Ranks
        Route::prefix('ranks')->group(function () {
            Route::get('/', [RankController::class, 'index']);
            Route::get('/{rank}', [RankController::class, 'show']);
            Route::get('/user/progress', [RankController::class, 'userProgress']);
            Route::get('/leaderboard', [RankController::class, 'leaderboard']);
            Route::get('/user/eligibility', [RankController::class, 'checkEligibility']);
            Route::post('/promotions/request', [RankController::class, 'requestPromotion']);
        });

        // Investments & Staking
        Route::prefix('investments')->group(function () {
            Route::get('/plans', [InvestmentController::class, 'plans']);
            Route::get('/plans/{plan}', [InvestmentController::class, 'showPlan']);
            Route::post('/calculate-roi', [InvestmentController::class, 'calculateROI']);
            Route::post('/invest', [InvestmentController::class, 'store']);
            Route::get('/summary', [InvestmentController::class, 'summary']);
            Route::get('/positions', [InvestmentController::class, 'positions']);
            Route::get('/positions/{position}', [InvestmentController::class, 'showPosition']);
            Route::post('/positions/{position}/withdraw', [InvestmentController::class, 'withdraw']);
            Route::get('/distributions', [InvestmentController::class, 'distributions']);
        });

        // Crypto Wallet API
        Route::prefix('crypto')->group(function () {
            Route::post('/verify-signature', [CryptoWalletApiController::class, 'verifySignature']);
            Route::get('/balances', [CryptoWalletApiController::class, 'getBalances']);
            Route::get('/address/{currency}', [CryptoWalletApiController::class, 'getAddress']);
            Route::get('/prices', [CryptoWalletApiController::class, 'getPrices']);
            Route::get('/transaction/{txHash}', [CryptoWalletApiController::class, 'checkTransaction']);
            Route::get('/gas-price', [CryptoWalletApiController::class, 'getGasPrice']);
        });

        // NFC Card API
        Route::prefix('nfc')->group(function () {
            Route::get('/cards', [NFCCardApiController::class, 'index']);
            Route::get('/cards/{cardId}', [NFCCardApiController::class, 'show']);
            Route::post('/cards/verify', [NFCCardApiController::class, 'verify']);
            Route::post('/cards/payment', [NFCCardApiController::class, 'processPayment']);
            Route::get('/cards/{cardId}/transactions', [NFCCardApiController::class, 'transactions']);
            Route::get('/cards/{cardId}/balance', [NFCCardApiController::class, 'balance']);
            Route::get('/readers/nearby', [NFCCardApiController::class, 'nearbyReaders']);
        });

        // TPIX Native Blockchain API
        Route::prefix('tpix')->group(function () {
            Route::get('/network-info', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'getNetworkInfo']);
            Route::get('/balance', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'getBalance']);
            Route::get('/block-number', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'getBlockNumber']);
            Route::get('/transaction', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'getTransaction']);
            Route::get('/transaction-receipt', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'getTransactionReceipt']);
            Route::post('/send-raw-transaction', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'sendRawTransaction']);
            Route::post('/estimate-gas', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'estimateGas']);
            Route::get('/gas-price', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'getGasPrice']);
            Route::get('/transaction-count', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'getTransactionCount']);
            Route::post('/validate-address', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'validateAddress']);
            Route::post('/to-wei', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'toWei']);
            Route::post('/from-wei', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'fromWei']);
        });

        // App Configuration (protected)
        Route::prefix('app')->group(function () {
            Route::get('/config', [AppConfigController::class, 'config']);
            Route::get('/settings', [AppConfigController::class, 'settings']);
            Route::get('/theme', [AppConfigController::class, 'theme']);
            Route::get('/complete-theme', [AppConfigController::class, 'completeTheme']);
            Route::get('/control-sections', [AppConfigController::class, 'controlSections']);
            Route::get('/component-settings', [AppConfigController::class, 'componentSettings']);
            Route::get('/features', [AppConfigController::class, 'features']);
            Route::get('/banners', [AppConfigController::class, 'banners']);
            Route::post('/banners/{bannerId}/view', [AppConfigController::class, 'trackBannerView']);
            Route::post('/banners/{bannerId}/click', [AppConfigController::class, 'trackBannerClick']);
        });

        // AI Gen - Image & Video Generation
        Route::prefix('ai-gen')->group(function () {
            // Dashboard & User Info
            Route::get('/dashboard', [AiGenController::class, 'dashboard']);

            // Generation
            Route::post('/generate', [AiGenController::class, 'generate']);
            Route::get('/generations', [AiGenController::class, 'myGenerations']);
            Route::get('/generations/{generationId}', [AiGenController::class, 'getGeneration']);
            Route::get('/generations/{generationId}/status', [AiGenController::class, 'checkStatus']);
            Route::post('/generations/{generationId}/favorite', [AiGenController::class, 'toggleFavorite']);
            Route::delete('/generations/{generationId}', [AiGenController::class, 'deleteGeneration']);

            // Packages
            Route::get('/packages', [AiGenPackageController::class, 'index']);
            Route::get('/packages/{packageId}', [AiGenPackageController::class, 'show']);
            Route::post('/packages/{packageId}/purchase', [AiGenPackageController::class, 'purchase']);
        });

        // Video Reward System
        Route::prefix('video-rewards')->group(function () {
            // Dashboard & Overview
            Route::get('/dashboard', [VideoRewardController::class, 'dashboard']);
            Route::get('/statistics', [VideoRewardController::class, 'statistics']);
            Route::get('/leaderboard', [VideoRewardController::class, 'leaderboard']);

            // Channels & Videos
            Route::get('/channels', [VideoRewardController::class, 'channels']);
            Route::get('/channels/{channelId}/videos', [VideoRewardController::class, 'channelVideos']);
            Route::get('/videos/{videoId}', [VideoRewardController::class, 'videoDetails']);

            // Video Watching
            Route::post('/watch/start', [VideoWatchController::class, 'startWatch']);
            Route::post('/watch/heartbeat', [VideoWatchController::class, 'heartbeat']);
            Route::post('/watch/end', [VideoWatchController::class, 'endWatch']);
            Route::post('/watch/claim-reward', [VideoWatchController::class, 'claimReward']);

            // Quests
            Route::get('/quests', [VideoQuestController::class, 'index']);
            Route::get('/quests/{questId}', [VideoQuestController::class, 'show']);
            Route::post('/quests/{questId}/claim', [VideoQuestController::class, 'claimReward']);
            Route::get('/quests/history', [VideoQuestController::class, 'history']);

            // Coin Exchange
            Route::get('/exchange/rates', [CoinExchangeController::class, 'rates']);
            Route::post('/exchange/calculate', [CoinExchangeController::class, 'calculate']);
            Route::post('/exchange/request', [CoinExchangeController::class, 'exchange']);
            Route::get('/exchange/history', [CoinExchangeController::class, 'history']);
            Route::get('/exchange/requests/{requestId}', [CoinExchangeController::class, 'show']);
        });

        // Viral Trend Detection API
        Route::prefix('trends')->group(function () {
            Route::get('/dashboard', [TrendApiController::class, 'dashboard']);
            Route::get('/', [TrendApiController::class, 'index']);
            Route::get('/{trend}', [TrendApiController::class, 'show']);
            Route::post('/{trend}/generate-content', [TrendApiController::class, 'generateContent']);

            // Keywords
            Route::get('/keywords/trending', [TrendApiController::class, 'trendingKeywords']);
            Route::get('/keywords/emerging', [TrendApiController::class, 'emergingKeywords']);
            Route::get('/keywords/{keyword}/related', [TrendApiController::class, 'relatedKeywords']);

            // Sources
            Route::get('/sources', [TrendApiController::class, 'sources']);
            Route::post('/sources', [TrendApiController::class, 'createSource']);
            Route::put('/sources/{source}', [TrendApiController::class, 'updateSource']);
            Route::delete('/sources/{source}', [TrendApiController::class, 'deleteSource']);

            // Analytics
            Route::get('/analytics', [TrendApiController::class, 'analytics']);
        });

        // LINE OA Signup Analytics API
        Route::prefix('line-analytics')->name('api.line-analytics.')->group(function () {
            // Real-time Analytics Data
            Route::get('/dashboard', [\App\Http\Controllers\Admin\LineAnalyticsController::class, 'getDashboardData'])->name('dashboard');
            Route::get('/stats', [\App\Http\Controllers\Admin\LineAnalyticsController::class, 'getOverallStats'])->name('stats');
            Route::get('/funnel', [\App\Http\Controllers\Admin\LineAnalyticsController::class, 'getConversionFunnel'])->name('funnel');
            Route::get('/dropout', [\App\Http\Controllers\Admin\LineAnalyticsController::class, 'getDropoutAnalysis'])->name('dropout');
            Route::get('/trends', [\App\Http\Controllers\Admin\LineAnalyticsController::class, 'getSignupTrends'])->name('trends');
            Route::get('/leaderboard', [\App\Http\Controllers\Admin\LineAnalyticsController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/active', [\App\Http\Controllers\Admin\LineAnalyticsController::class, 'getActiveConversations'])->name('active');
            Route::get('/export', [\App\Http\Controllers\Admin\LineAnalyticsController::class, 'export'])->name('export');

            // Sponsor-specific Analytics (self or admin)
            Route::get('/sponsor/{sponsorId}', [\App\Http\Controllers\Admin\LineAnalyticsController::class, 'getSponsorAnalytics'])->name('sponsor');

            // Cache Management (admin only)
            Route::post('/clear-cache', [\App\Http\Controllers\Admin\LineAnalyticsController::class, 'clearCache'])
                ->middleware('can:manage-analytics')
                ->name('clear-cache');
        });

        // Taskbar Shortcuts API
        Route::prefix('taskbar-shortcuts')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\TaskbarShortcutController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\TaskbarShortcutController::class, 'store']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\TaskbarShortcutController::class, 'destroy']);
            Route::post('/reorder', [\App\Http\Controllers\Api\TaskbarShortcutController::class, 'reorder']);
        });
    });
});

// Public Crypto Wallet API (no auth required)
Route::post('/crypto/generate-nonce', [CryptoWalletApiController::class, 'generateNonce']);

/*
|--------------------------------------------------------------------------
| Bot Automation API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/bot-automation')->middleware('auth:sanctum')->name('api.bot-automation.')->group(function () {
    // Automations
    Route::apiResource('automations', \App\Http\Controllers\Api\BotAutomationApiController::class);
    Route::post('automations/{automation}/execute', [\App\Http\Controllers\Api\BotAutomationApiController::class, 'execute'])->name('automations.execute');
    Route::get('automations/{automation}/statistics', [\App\Http\Controllers\Api\BotAutomationApiController::class, 'statistics'])->name('automations.statistics');
    
    // Marketplace
    Route::get('marketplace', [\App\Http\Controllers\Api\BotAutomationApiController::class, 'marketplace'])->name('marketplace');
    Route::get('marketplace/{listing}', [\App\Http\Controllers\Api\BotMarketplaceApiController::class, 'show'])->name('marketplace.show');
    Route::post('marketplace/{listing}/subscribe', [\App\Http\Controllers\Api\BotMarketplaceApiController::class, 'subscribe'])->name('marketplace.subscribe');
    Route::post('marketplace/{listing}/review', [\App\Http\Controllers\Api\BotMarketplaceApiController::class, 'review'])->name('marketplace.review');
    
    // Platform Connections
    Route::get('platforms', [\App\Http\Controllers\Api\BotPlatformApiController::class, 'index'])->name('platforms.index');
    Route::get('platforms/available', [\App\Http\Controllers\Api\BotPlatformApiController::class, 'available'])->name('platforms.available');
    Route::post('platforms/connect', [\App\Http\Controllers\Api\BotPlatformApiController::class, 'connect'])->name('platforms.connect');
    Route::delete('platforms/{connection}', [\App\Http\Controllers\Api\BotPlatformApiController::class, 'disconnect'])->name('platforms.disconnect');
    
    // Templates
    Route::apiResource('templates', \App\Http\Controllers\Api\BotTemplateApiController::class);
    Route::post('templates/{template}/duplicate', [\App\Http\Controllers\Api\BotTemplateApiController::class, 'duplicate'])->name('templates.duplicate');
    
    // Support
    Route::get('support/conversations', [\App\Http\Controllers\Api\BotSupportApiController::class, 'conversations'])->name('support.conversations');
    Route::get('support/conversations/{conversation}', [\App\Http\Controllers\Api\BotSupportApiController::class, 'show'])->name('support.show');
    Route::post('support/conversations/{conversation}/messages', [\App\Http\Controllers\Api\BotSupportApiController::class, 'sendMessage'])->name('support.send');
    
    // Sales
    Route::get('sales/conversations', [\App\Http\Controllers\Api\BotSalesApiController::class, 'conversations'])->name('sales.conversations');
    Route::get('sales/conversations/{conversation}', [\App\Http\Controllers\Api\BotSalesApiController::class, 'show'])->name('sales.show');
    Route::post('sales/conversations/{conversation}/recommend', [\App\Http\Controllers\Api\BotSalesApiController::class, 'recommend'])->name('sales.recommend');
    
    // Analytics
    Route::get('analytics/overview', [\App\Http\Controllers\Api\BotAnalyticsApiController::class, 'overview'])->name('analytics.overview');
    Route::get('analytics/posts', [\App\Http\Controllers\Api\BotAnalyticsApiController::class, 'posts'])->name('analytics.posts');
    Route::get('analytics/platforms', [\App\Http\Controllers\Api\BotAnalyticsApiController::class, 'platforms'])->name('analytics.platforms');
});

// Bot Automation Webhooks (no auth)
Route::prefix('webhook/bot')->name('api.webhook.bot.')->group(function () {
    Route::post('facebook', [\App\Http\Controllers\Api\BotWebhookController::class, 'facebook'])->name('facebook');
    Route::post('instagram', [\App\Http\Controllers\Api\BotWebhookController::class, 'instagram'])->name('instagram');
    Route::post('twitter', [\App\Http\Controllers\Api\BotWebhookController::class, 'twitter'])->name('twitter');
    Route::post('tiktok', [\App\Http\Controllers\Api\BotWebhookController::class, 'tiktok'])->name('tiktok');
});

/*
|--------------------------------------------------------------------------
| Chatbot Rental System API Routes
|--------------------------------------------------------------------------
| ระบบให้เช่าบอทแชทอัจฉริยะแบบไฮบริด
| - Keyword-based + AI Fallback
| - Multi-platform Integration
| - Auto Content Posting
| - Marketplace
*/

// Chatbot Rental System (Protected Routes)
Route::prefix('v1/chatbot')->middleware('auth:sanctum')->name('api.chatbot.')->group(function () {

    // Bot Management
    Route::prefix('bots')->name('bots.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'show'])->name('show');
        Route::put('/{id}', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/test', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'test'])->name('test');
        Route::post('/{id}/clone', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'clone'])->name('clone');
        Route::get('/{id}/stats', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'stats'])->name('stats');

        // Keyword Responses
        Route::prefix('{botId}/keywords')->name('keywords.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Chatbot\KeywordResponseController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\Chatbot\KeywordResponseController::class, 'store'])->name('store');
            Route::put('/{id}', [\App\Http\Controllers\Api\Chatbot\KeywordResponseController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Api\Chatbot\KeywordResponseController::class, 'destroy'])->name('destroy');
            Route::post('/update-order', [\App\Http\Controllers\Api\Chatbot\KeywordResponseController::class, 'updateOrder'])->name('update-order');
            Route::post('/test-match', [\App\Http\Controllers\Api\Chatbot\KeywordResponseController::class, 'testMatch'])->name('test-match');
        });

        // Platform Integrations
        Route::prefix('{botId}/integrations')->name('integrations.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Chatbot\PlatformIntegrationController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\Chatbot\PlatformIntegrationController::class, 'store'])->name('store');
            Route::put('/{id}', [\App\Http\Controllers\Api\Chatbot\PlatformIntegrationController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Api\Chatbot\PlatformIntegrationController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/verify', [\App\Http\Controllers\Api\Chatbot\PlatformIntegrationController::class, 'verify'])->name('verify');
            Route::get('/{id}/webhook-url', [\App\Http\Controllers\Api\Chatbot\PlatformIntegrationController::class, 'getWebhookUrl'])->name('webhook-url');
        });

        // Auto Content Posting
        Route::prefix('{botId}/auto-content')->name('auto-content.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Chatbot\AutoContentController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\Chatbot\AutoContentController::class, 'store'])->name('store');
            Route::post('/{id}/generate', [\App\Http\Controllers\Api\Chatbot\AutoContentController::class, 'generate'])->name('generate');
            Route::post('/{id}/schedule', [\App\Http\Controllers\Api\Chatbot\AutoContentController::class, 'schedule'])->name('schedule');
            Route::post('/{id}/post-now', [\App\Http\Controllers\Api\Chatbot\AutoContentController::class, 'postNow'])->name('post-now');
            Route::put('/{id}', [\App\Http\Controllers\Api\Chatbot\AutoContentController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Api\Chatbot\AutoContentController::class, 'destroy'])->name('destroy');
        });
    });

    // Marketplace
    Route::prefix('marketplace')->name('marketplace.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Chatbot\MarketplaceController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Api\Chatbot\MarketplaceController::class, 'show'])->name('show');
        Route::post('/{id}/rent', [\App\Http\Controllers\Api\Chatbot\MarketplaceController::class, 'rent'])->name('rent');
        Route::get('/my-rentals', [\App\Http\Controllers\Api\Chatbot\MarketplaceController::class, 'myRentals'])->name('my-rentals');
        Route::get('/my-earnings', [\App\Http\Controllers\Api\Chatbot\MarketplaceController::class, 'myEarnings'])->name('my-earnings');
        Route::post('/rentals/{rentalId}/cancel', [\App\Http\Controllers\Api\Chatbot\MarketplaceController::class, 'cancelRental'])->name('cancel-rental');
        Route::post('/rentals/{rentalId}/renew', [\App\Http\Controllers\Api\Chatbot\MarketplaceController::class, 'renewRental'])->name('renew-rental');
    });
});

// Chatbot Webhooks (Public - No Auth)
Route::prefix('webhook/chatbot')->name('api.webhook.chatbot.')->group(function () {
    Route::post('{platform}/{integration_id}', function ($platform, $integrationId) {
        // Handle webhook from different platforms
        // This will be implemented based on each platform's webhook specification
        return response()->json(['success' => true]);
    })->name('handle');
});

/*
|--------------------------------------------------------------------------
| TPIX Token System API Routes
|--------------------------------------------------------------------------
| Complete REST API for TPIX Token Management
| - Token marketplace
| - Token creation & deployment
| - Trading (buy/sell)
| - Portfolio management
| - Staking
| - Referrals
*/

Route::prefix('v1/tpix')->name('api.tpix.')->group(function () {

    // Public endpoints
    Route::get('/tokens', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'index'])->name('tokens.index');
    Route::get('/tokens/{id}', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'show'])->name('tokens.show');
    Route::get('/tokens/{id}/transactions', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'transactions'])->name('tokens.transactions');

    // Protected endpoints
    Route::middleware('auth:sanctum')->group(function () {

        // Token Management
        Route::post('/tokens', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'store'])
            ->middleware('rate_limit_token_operations:create')
            ->name('tokens.store');
        Route::post('/tokens/{id}/deploy', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'deploy'])
            ->middleware(['check_token_ownership', 'rate_limit_token_operations:deploy'])
            ->name('tokens.deploy');

        // Token Trading
        Route::post('/tokens/{id}/transfer', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'transfer'])
            ->middleware(['verify_token_deployment', 'rate_limit_token_operations:transfer'])
            ->name('tokens.transfer');
        Route::post('/tokens/{id}/buy', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'buy'])
            ->middleware(['verify_token_deployment', 'rate_limit_token_operations:trade'])
            ->name('tokens.buy');
        Route::post('/tokens/{id}/sell', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'sell'])
            ->middleware(['verify_token_deployment', 'rate_limit_token_operations:trade'])
            ->name('tokens.sell');

        // Portfolio
        Route::get('/portfolio', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'portfolio'])->name('portfolio');
        Route::get('/balances', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'balances'])->name('balances');
    });

    // DEX Routes
    Route::prefix('dex')->name('dex.')->group(function () {
        // Public endpoints
        Route::get('/pools', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'pools'])->name('pools');
        Route::get('/pools/{id}', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'poolDetails'])->name('pools.show');
        Route::get('/statistics', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'statistics'])->name('statistics');

        // Protected endpoints (requires authentication)
        Route::middleware('auth:sanctum')->group(function () {
            // Quote
            Route::post('/quote', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'quote'])
                ->name('quote');

            // Swap
            Route::post('/swap', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'swap'])
                ->middleware('rate_limit_token_operations:trade')
                ->name('swap');

            // Liquidity
            Route::post('/liquidity/add', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'addLiquidity'])
                ->middleware('rate_limit_token_operations:trade')
                ->name('liquidity.add');

            Route::post('/liquidity/remove', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'removeLiquidity'])
                ->middleware('rate_limit_token_operations:trade')
                ->name('liquidity.remove');

            // User positions & history
            Route::get('/my/positions', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'myPositions'])->name('my.positions');
            Route::get('/my/swaps', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'mySwaps'])->name('my.swaps');
        });
    });

    // Staking Routes
    Route::prefix('staking')->name('staking.')->group(function () {
        // Public endpoints
        Route::get('/pools', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'getPools'])->name('pools');
        Route::get('/pools/{poolId}', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'getPool'])->name('pools.show');
        Route::get('/pools/{poolId}/recent', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'getRecentStakes'])->name('pools.recent');

        // Protected endpoints (requires authentication)
        Route::middleware('auth:sanctum')->group(function () {
            // Get user stake
            Route::get('/my-stake/{poolId}', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'getMyStake'])->name('my-stake');

            // Stake actions
            Route::post('/stake', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'stake'])
                ->middleware('tpix.rate.limit:trade')
                ->name('stake');

            Route::post('/unstake', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'unstake'])
                ->middleware('tpix.rate.limit:trade')
                ->name('unstake');

            Route::post('/claim', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'claim'])
                ->name('claim');

            // History
            Route::get('/history', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'getHistory'])->name('history');

            // Individual stake details
            Route::get('/stakes/{stakeId}', function ($stakeId) {
                $stake = \App\Models\TPIXStake::with('pool.token')->findOrFail($stakeId);

                // Ensure user owns this stake
                if ($stake->user_id !== auth()->id()) {
                    abort(403, 'Unauthorized');
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $stake->id,
                        'pool' => [
                            'id' => $stake->pool->id,
                            'pair' => $stake->pool->token->name,
                            'token' => $stake->pool->token,
                        ],
                        'amount' => (float) $stake->staked_amount,
                        'lock_period_days' => (int) $stake->lock_period_days,
                        'apy' => (float) $stake->apy,
                        'exchange_rate' => '1 ' . $stake->pool->token->symbol,
                        'price_impact' => 0,
                        'slippage_tolerance' => 0,
                        'created_at' => $stake->created_at->toISOString(),
                        'unlock_date' => $stake->unlock_date ? $stake->unlock_date->toISOString() : null,
                        'status' => $stake->status,
                        'blockchain_tx_hash' => $stake->blockchain_tx_hash,
                        'blockchain_tx_url' => $stake->blockchain_tx_hash
                            ? config('tpix.blockchain.explorer_url') . '/tx/' . $stake->blockchain_tx_hash
                            : null,
                    ],
                ]);
            })->name('stakes.show');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Food Passport API Routes
|--------------------------------------------------------------------------
| ระบบ Food Passport - ตรวจสอบคุณภาพและความปลอดภัยอาหาร
| - Farm-to-Fork Traceability
| - Quality Control & Certifications
| - Carbon Footprint & Credits
| - IoT Sensor Integration
| - Google Maps Integration
| - Blockchain Verification
*/

// Public Food Passport Routes (No Auth)
Route::prefix('v1/food-passport')->middleware('food-passport.ratelimit:public')->name('api.food-passport.')->group(function () {

    // Public Product Scan (QR Code)
    Route::get('/scan/{passportId}', [FoodPassportController::class, 'scan'])
        ->name('scan');

    // Public Product View
    Route::get('/products/{id}', [FoodPassportController::class, 'show'])
        ->name('products.show');

    // Public Product Search
    Route::get('/products', [FoodPassportController::class, 'index'])
        ->name('products.index');

    // Public Certification Verification
    Route::get('/certifications/{id}/verify', [CertificationController::class, 'verify'])
        ->name('certifications.verify');

    // Public Carbon Data View
    Route::get('/products/{id}/carbon', [FoodPassportController::class, 'carbonData'])
        ->name('products.carbon');

    // Public Journey Tracking
    Route::get('/products/{id}/journey', [TraceabilityController::class, 'journey'])
        ->name('products.journey');
});

// Protected Food Passport Routes (Auth Required)
Route::prefix('v1/food-passport')->middleware(['auth:sanctum', 'food-passport.ratelimit'])->name('api.food-passport.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Product Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('products')->name('products.')->group(function () {
        // Create new food product (Farmers & Admins)
        Route::post('/', [FoodPassportController::class, 'store'])
            ->middleware(['food-passport.ratelimit:write', 'can:create,App\Models\FoodProduct'])
            ->name('store');

        // Update product (Owner or Admin)
        Route::put('/{id}', [FoodPassportController::class, 'update'])
            ->middleware(['food-passport.ratelimit:write', 'can:update,id'])
            ->name('update');

        // Delete product (Owner or Super Admin)
        Route::delete('/{id}', [FoodPassportController::class, 'destroy'])
            ->middleware(['food-passport.ratelimit:write', 'can:delete,id'])
            ->name('destroy');

        // Bulk import products (Verified Farmers & Admins)
        Route::post('/bulk-import', [FoodPassportController::class, 'bulkImport'])
            ->middleware(['food-passport.ratelimit:write', 'can:bulkImport,App\Models\FoodProduct'])
            ->name('bulk-import');

        // Statistics & Dashboard
        Route::get('/statistics', [FoodPassportController::class, 'statistics'])
            ->name('statistics');
    });

    /*
    |--------------------------------------------------------------------------
    | Traceability & Journey Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('traceability')->name('traceability.')->group(function () {
        // Add journey stage
        Route::post('/products/{id}/journey', [TraceabilityController::class, 'addStage'])
            ->middleware('food-passport.ratelimit:write')
            ->name('add-stage');

        // Update journey stage
        Route::put('/journey/{journeyId}', [TraceabilityController::class, 'updateStage'])
            ->middleware('food-passport.ratelimit:write')
            ->name('update-stage');

        // Complete journey stage
        Route::post('/journey/{journeyId}/complete', [TraceabilityController::class, 'completeStage'])
            ->middleware('food-passport.ratelimit:write')
            ->name('complete-stage');

        // IoT Sensor Data Recording
        Route::post('/journey/{journeyId}/sensor-data', [TraceabilityController::class, 'recordSensorData'])
            ->middleware('food-passport.ratelimit:iot')
            ->name('record-sensor-data');

        // Bulk IoT Sensor Data Upload
        Route::post('/journey/{journeyId}/sensor-data/bulk', [TraceabilityController::class, 'bulkRecordSensorData'])
            ->middleware('food-passport.ratelimit:iot')
            ->name('bulk-sensor-data');

        // Google Maps Integration
        Route::post('/geocode', [TraceabilityController::class, 'geocodeLocation'])
            ->name('geocode');

        Route::post('/calculate-distance', [TraceabilityController::class, 'calculateDistance'])
            ->name('calculate-distance');

        // Get current location
        Route::get('/products/{id}/current-location', [TraceabilityController::class, 'currentLocation'])
            ->name('current-location');
    });

    /*
    |--------------------------------------------------------------------------
    | Quality Control & Checkpoints
    |--------------------------------------------------------------------------
    */
    Route::prefix('quality')->name('quality.')->group(function () {
        // Create quality checkpoint
        Route::post('/products/{id}/checkpoints', [QualityController::class, 'createCheckpoint'])
            ->middleware(['food-passport.ratelimit:write', 'can:create,App\Models\QualityCheckpoint'])
            ->name('create-checkpoint');

        // Update checkpoint
        Route::put('/checkpoints/{id}', [QualityController::class, 'updateCheckpoint'])
            ->middleware(['food-passport.ratelimit:write', 'can:update,id'])
            ->name('update-checkpoint');

        // Add corrective actions
        Route::post('/checkpoints/{id}/corrective-actions', [QualityController::class, 'addCorrectiveActions'])
            ->middleware('food-passport.ratelimit:write')
            ->name('add-corrective-actions');

        // Require retest
        Route::post('/checkpoints/{id}/retest', [QualityController::class, 'requireRetest'])
            ->middleware('food-passport.ratelimit:write')
            ->name('require-retest');

        // Get product quality history
        Route::get('/products/{id}/history', [QualityController::class, 'qualityHistory'])
            ->name('quality-history');

        // Quality statistics
        Route::get('/statistics', [QualityController::class, 'statistics'])
            ->name('statistics');
    });

    /*
    |--------------------------------------------------------------------------
    | Carbon Credits & Trading
    |--------------------------------------------------------------------------
    */
    Route::prefix('carbon')->name('carbon.')->group(function () {
        // Issue carbon credit (Admins & Verifiers)
        Route::post('/products/{id}/issue-credit', [CarbonCreditController::class, 'issueCredit'])
            ->middleware(['food-passport.ratelimit:blockchain', 'can:issue,App\Models\CarbonCredit'])
            ->name('issue-credit');

        // View marketplace
        Route::get('/marketplace', [CarbonCreditController::class, 'marketplace'])
            ->name('marketplace');

        // View credit details
        Route::get('/credits/{id}', [CarbonCreditController::class, 'show'])
            ->name('credits.show');

        // Trade credit
        Route::post('/credits/{id}/trade', [CarbonCreditController::class, 'trade'])
            ->middleware(['food-passport.ratelimit:trading', 'can:trade,id'])
            ->name('credits.trade');

        // Purchase credit
        Route::post('/credits/{id}/purchase', [CarbonCreditController::class, 'purchase'])
            ->middleware(['food-passport.ratelimit:trading', 'can:purchase,id'])
            ->name('credits.purchase');

        // Retire credit
        Route::post('/credits/{id}/retire', [CarbonCreditController::class, 'retire'])
            ->middleware(['food-passport.ratelimit:trading', 'can:retire,id'])
            ->name('credits.retire');

        // My credits
        Route::get('/my-credits', [CarbonCreditController::class, 'myCredits'])
            ->name('my-credits');

        // Credit statistics
        Route::get('/statistics', [CarbonCreditController::class, 'statistics'])
            ->name('statistics');

        // Transaction history
        Route::get('/credits/{id}/transactions', [CarbonCreditController::class, 'transactionHistory'])
            ->name('credits.transactions');
    });

    /*
    |--------------------------------------------------------------------------
    | Certifications Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('certifications')->name('certifications.')->group(function () {
        // List certifications
        Route::get('/', [CertificationController::class, 'index'])
            ->name('index');

        // Create certification (Certification Bodies & Admins)
        Route::post('/', [CertificationController::class, 'store'])
            ->middleware(['food-passport.ratelimit:write', 'can:create,App\Models\FoodCertification'])
            ->name('store');

        // View certification details
        Route::get('/{id}', [CertificationController::class, 'show'])
            ->name('show');

        // Update certification
        Route::put('/{id}', [CertificationController::class, 'update'])
            ->middleware(['food-passport.ratelimit:write', 'can:update,id'])
            ->name('update');

        // Renew certification
        Route::post('/{id}/renew', [CertificationController::class, 'renew'])
            ->middleware(['food-passport.ratelimit:write', 'can:renew,id'])
            ->name('renew');

        // Revoke certification
        Route::post('/{id}/revoke', [CertificationController::class, 'revoke'])
            ->middleware(['food-passport.ratelimit:write', 'can:revoke,id'])
            ->name('revoke');

        // Upload documents
        Route::post('/{id}/documents', [CertificationController::class, 'uploadDocument'])
            ->middleware(['food-passport.ratelimit:write', 'can:uploadDocuments,id'])
            ->name('upload-document');

        // Generate QR code
        Route::post('/{id}/generate-qr', [CertificationController::class, 'generateQRCode'])
            ->middleware(['food-passport.ratelimit:write', 'can:generateQRCode,id'])
            ->name('generate-qr');

        // Record on blockchain
        Route::post('/{id}/blockchain', [CertificationController::class, 'recordOnBlockchain'])
            ->middleware(['food-passport.ratelimit:blockchain', 'can:recordOnBlockchain,id'])
            ->name('blockchain');

        // Get product certifications
        Route::get('/products/{productId}', [CertificationController::class, 'productCertifications'])
            ->name('product-certifications');

        // Certification statistics
        Route::get('/statistics/overview', [CertificationController::class, 'statistics'])
            ->name('statistics');
    });
});

/*
|--------------------------------------------------------------------------
| Snake.io Multiplayer Game API
|--------------------------------------------------------------------------
*/
Route::prefix('games/snake-io')->name('api.games.snake-io.')->group(function () {
    // ⚡ Stateless Mode: ใช้ in-memory สำหรับ testing environment
    // ไม่ต้อง setup MySQL/database - เหมาะสำหรับ Claude Code environment
    $controller = \App\Http\Controllers\SnakeGameControllerStateless::class;

    // เข้าร่วมเกม (ไม่บังคับ auth)
    Route::post('/join', [$controller, 'join'])
        ->name('join');

    // ออกจากห้อง
    Route::post('/leave', [$controller, 'leave'])
        ->name('leave');

    // อัปเดตสถานะผู้เล่น
    Route::post('/update-state', [$controller, 'updateState'])
        ->name('update-state');

    // ผู้เล่นตาย
    Route::post('/player-died', [$controller, 'playerDied'])
        ->name('player-died');

    // เก็บไอเทม
    Route::post('/collect-item', [$controller, 'collectItem'])
        ->name('collect-item');

    // ดึงสถานะห้อง
    Route::get('/room-state/{roomId}', [$controller, 'getRoomState'])
        ->name('room-state');

    // บันทึกคะแนน (ไม่บังคับ auth ใน stateless mode)
    Route::post('/save-score', [$controller, 'saveScore'])
        ->name('save-score');

    // ตรวจสอบ wallet
    Route::get('/check-wallet', [$controller, 'checkWallet'])
        ->name('check-wallet');
});
