<?php

use App\Http\Controllers\Admin\BotAutomation\BotAutomationController;
use App\Http\Controllers\Admin\BotAutomation\BotPlatformController;
use App\Http\Controllers\Admin\BotAutomation\BotTemplateController;
use App\Http\Controllers\Admin\BotAutomation\BotMarketplaceController;
use App\Http\Controllers\Admin\BotAutomation\BotSupportController;
use App\Http\Controllers\Admin\BotAutomation\BotSalesController;
use App\Http\Controllers\Admin\BotAutomation\BotAnalyticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bot Automation Routes (Admin)
|--------------------------------------------------------------------------
*/

// Dashboard
Route::get('bot-automation', [BotAutomationController::class, 'dashboard'])->name('bot-automation.dashboard');

// Platform Connections
Route::prefix('bot-automation/platforms')->name('bot-automation.platforms.')->group(function () {
    Route::get('/', [BotPlatformController::class, 'index'])->name('index');
    Route::get('/connect/{platform}', [BotPlatformController::class, 'connect'])->name('connect');
    Route::post('/store', [BotPlatformController::class, 'store'])->name('store');
    Route::delete('/{connection}', [BotPlatformController::class, 'destroy'])->name('destroy');
    Route::post('/{connection}/refresh', [BotPlatformController::class, 'refreshToken'])->name('refresh');
    Route::post('/{connection}/toggle', [BotPlatformController::class, 'toggle'])->name('toggle');
});

// Automation Management
Route::prefix('bot-automation/automations')->name('bot-automation.automations.')->group(function () {
    Route::get('/', [BotAutomationController::class, 'index'])->name('index');
    Route::get('/create', [BotAutomationController::class, 'create'])->name('create');
    Route::post('/', [BotAutomationController::class, 'store'])->name('store');
    Route::get('/{automation}', [BotAutomationController::class, 'show'])->name('show');
    Route::get('/{automation}/edit', [BotAutomationController::class, 'edit'])->name('edit');
    Route::put('/{automation}', [BotAutomationController::class, 'update'])->name('update');
    Route::delete('/{automation}', [BotAutomationController::class, 'destroy'])->name('destroy');
    Route::post('/{automation}/execute', [BotAutomationController::class, 'execute'])->name('execute');
    Route::post('/{automation}/toggle', [BotAutomationController::class, 'toggle'])->name('toggle');
    Route::get('/{automation}/analytics', [BotAutomationController::class, 'analytics'])->name('analytics');
    Route::get('/{automation}/logs', [BotAutomationController::class, 'logs'])->name('logs');
});

// Content Templates
Route::prefix('bot-automation/templates')->name('bot-automation.templates.')->group(function () {
    Route::get('/', [BotTemplateController::class, 'index'])->name('index');
    Route::get('/create', [BotTemplateController::class, 'create'])->name('create');
    Route::post('/', [BotTemplateController::class, 'store'])->name('store');
    Route::get('/{template}', [BotTemplateController::class, 'show'])->name('show');
    Route::get('/{template}/edit', [BotTemplateController::class, 'edit'])->name('edit');
    Route::put('/{template}', [BotTemplateController::class, 'update'])->name('update');
    Route::delete('/{template}', [BotTemplateController::class, 'destroy'])->name('destroy');
    Route::post('/{template}/duplicate', [BotTemplateController::class, 'duplicate'])->name('duplicate');
    Route::post('/{template}/preview', [BotTemplateController::class, 'preview'])->name('preview');
});

// Marketplace Management
Route::prefix('bot-automation/marketplace')->name('bot-automation.marketplace.')->group(function () {
    Route::get('/', [BotMarketplaceController::class, 'index'])->name('index');
    Route::get('/listings', [BotMarketplaceController::class, 'listings'])->name('listings');
    Route::get('/listings/create', [BotMarketplaceController::class, 'createListing'])->name('listings.create');
    Route::post('/listings', [BotMarketplaceController::class, 'storeListing'])->name('listings.store');
    Route::get('/listings/{listing}/edit', [BotMarketplaceController::class, 'editListing'])->name('listings.edit');
    Route::put('/listings/{listing}', [BotMarketplaceController::class, 'updateListing'])->name('listings.update');
    Route::delete('/listings/{listing}', [BotMarketplaceController::class, 'destroyListing'])->name('listings.destroy');
    Route::post('/listings/{listing}/publish', [BotMarketplaceController::class, 'publish'])->name('listings.publish');
    Route::post('/listings/{listing}/unpublish', [BotMarketplaceController::class, 'unpublish'])->name('listings.unpublish');

    // Reviews Management
    Route::get('/reviews', [BotMarketplaceController::class, 'reviews'])->name('reviews');
    Route::post('/reviews/{review}/approve', [BotMarketplaceController::class, 'approveReview'])->name('reviews.approve');
    Route::delete('/reviews/{review}', [BotMarketplaceController::class, 'destroyReview'])->name('reviews.destroy');

    // Subscriptions
    Route::get('/subscriptions', [BotMarketplaceController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/subscriptions/{subscription}', [BotMarketplaceController::class, 'showSubscription'])->name('subscriptions.show');
});

// Customer Support
Route::prefix('bot-automation/support')->name('bot-automation.support.')->group(function () {
    Route::get('/', [BotSupportController::class, 'index'])->name('index');
    Route::get('/conversations', [BotSupportController::class, 'conversations'])->name('conversations');
    Route::get('/conversations/{conversation}', [BotSupportController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{conversation}/assign', [BotSupportController::class, 'assign'])->name('conversations.assign');
    Route::post('/conversations/{conversation}/resolve', [BotSupportController::class, 'resolve'])->name('conversations.resolve');
    Route::post('/conversations/{conversation}/messages', [BotSupportController::class, 'sendMessage'])->name('messages.send');
    Route::get('/analytics', [BotSupportController::class, 'analytics'])->name('analytics');
});

// Sales Assistant
Route::prefix('bot-automation/sales')->name('bot-automation.sales.')->group(function () {
    Route::get('/', [BotSalesController::class, 'index'])->name('index');
    Route::get('/conversations', [BotSalesController::class, 'conversations'])->name('conversations');
    Route::get('/conversations/{conversation}', [BotSalesController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{conversation}/assign', [BotSalesController::class, 'assign'])->name('conversations.assign');
    Route::post('/conversations/{conversation}/messages', [BotSalesController::class, 'sendMessage'])->name('messages.send');
    Route::post('/conversations/{conversation}/recommend', [BotSalesController::class, 'recommendProducts'])->name('recommend');
    Route::get('/analytics', [BotSalesController::class, 'analytics'])->name('analytics');
    Route::get('/funnel', [BotSalesController::class, 'funnel'])->name('funnel');
});

// Analytics
Route::prefix('bot-automation/analytics')->name('bot-automation.analytics.')->group(function () {
    Route::get('/', [BotAnalyticsController::class, 'index'])->name('index');
    Route::get('/overview', [BotAnalyticsController::class, 'overview'])->name('overview');
    Route::get('/posts', [BotAnalyticsController::class, 'posts'])->name('posts');
    Route::get('/platforms', [BotAnalyticsController::class, 'platforms'])->name('platforms');
    Route::get('/engagement', [BotAnalyticsController::class, 'engagement'])->name('engagement');
    Route::get('/revenue', [BotAnalyticsController::class, 'revenue'])->name('revenue');
    Route::get('/export', [BotAnalyticsController::class, 'export'])->name('export');
});
