<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\TreeController;
use App\Http\Controllers\Api\RankController;
use App\Http\Controllers\Api\InvestmentController;
use App\Http\Controllers\Api\CryptoWalletApiController;
use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\AiGenController;
use App\Http\Controllers\Api\AiGenPackageController;
use App\Http\Controllers\Api\TrendApiController;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\Api\VideoRewardController;
use App\Http\Controllers\Api\VideoWatchController;
use App\Http\Controllers\Api\VideoQuestController;
use App\Http\Controllers\Api\CoinExchangeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Webhooks (no CSRF, no auth)
Route::post('/webhook/line', [LineWebhookController::class, 'handle'])->name('api.line.webhook');

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
        Route::get('/check-update', [AppConfigController::class, 'checkUpdate']);

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
