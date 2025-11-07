<?php

namespace App\Providers;

use App\Models\Theme;
use App\Services\ThemeService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ThemeService::class, function ($app) {
            return new ThemeService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share theme data with all views
        View::composer('*', function ($view) {
            try {
                // Only load theme if user is authenticated
                if (auth()->check()) {
                    $themeService = app(ThemeService::class);
                    $userTheme = $themeService->getThemeForUser(auth()->id());

                    $view->with([
                        'currentTheme' => $userTheme['theme'] ?? Theme::getDefault(),
                        'currentThemeMode' => $userTheme['mode'] ?? 'auto',
                    ]);
                } else {
                    // For guests, use default theme
                    $view->with([
                        'currentTheme' => Theme::getDefault(),
                        'currentThemeMode' => 'auto',
                    ]);
                }
            } catch (\Exception $e) {
                // If theme system not initialized or has errors, provide safe defaults
                $view->with([
                    'currentTheme' => null,
                    'currentThemeMode' => 'auto',
                ]);
            }
        });
    }
}
