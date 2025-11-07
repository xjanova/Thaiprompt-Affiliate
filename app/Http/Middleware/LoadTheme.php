<?php

namespace App\Http\Middleware;

use App\Services\ThemeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoadTheme
{
    protected $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Load theme for authenticated users
        if (auth()->check()) {
            $userTheme = $this->themeService->getThemeForUser(auth()->id());

            // Share theme CSS with the view
            view()->share('themeCss', $this->generateThemeCss($userTheme));
        }

        return $next($request);
    }

    /**
     * Generate theme CSS
     */
    protected function generateThemeCss($userTheme)
    {
        $theme = $userTheme['theme'];
        $mode = $userTheme['mode'];

        if (!$theme) {
            return '';
        }

        // Determine actual mode (light or dark)
        $actualMode = $mode === 'auto' ? 'light' : $mode;

        return $theme->generateCss($actualMode);
    }
}
