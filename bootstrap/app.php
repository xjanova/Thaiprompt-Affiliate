<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware - Check if system is installed
        $middleware->append(\App\Http\Middleware\CheckInstalled::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Web middleware group
        $middleware->web(append: [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Middleware aliases
        $middleware->alias([
            'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
            'setup.check' => \App\Http\Middleware\RedirectIfSetupCompleted::class,
            'setup.required' => \App\Http\Middleware\CheckSetupCompleted::class,
            'sanitize' => \App\Http\Middleware\SanitizeInput::class,
            'cloudflare' => \App\Http\Middleware\CheckCloudflareChallenge::class,
            'spam.filter' => \App\Http\Middleware\SpamFilter::class,
            'admin.employee' => \App\Http\Middleware\CheckAdminEmployeePermission::class,
            'vendor.employee' => \App\Http\Middleware\CheckVendorEmployeePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function ($schedule) {
        // Check for version updates daily
        $schedule->call(function () {
            app(\App\Services\Version\VersionUpdateService::class)->checkForUpdates();
        })->daily();

        // Process commissions daily
        $schedule->command('commissions:process')->daily();

        // Update MLM ranks daily
        $schedule->command('ranks:update')->daily();

        // Process withdrawals hourly
        $schedule->command('withdrawals:process')->hourly();
    })
    ->create();
