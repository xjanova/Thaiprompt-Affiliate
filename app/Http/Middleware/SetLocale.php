<?php

namespace App\Http\Middleware;

use App\Models\LanguageSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get locale from multiple sources (priority order):
        // 1. Query parameter (?lang=en)
        // 2. Session
        // 3. User's preferred language (if authenticated)
        // 4. Default language from database
        // 5. Default config
        $locale = $request->get('lang');

        if (!$locale) {
            $locale = Session::get('locale');
        }

        if (!$locale && $request->user()) {
            $locale = $request->user()->preferred_language;
        }

        if (!$locale) {
            // Try to get default language from database
            $defaultLang = LanguageSetting::getDefault();
            if ($defaultLang) {
                $locale = $defaultLang->code;
            }
        }

        if (!$locale) {
            $locale = config('app.locale', 'en');
        }

        // Validate locale (get from database instead of config)
        $supportedLocales = LanguageSetting::getEnabledCodes();

        // Fallback to config if database is not available
        if (empty($supportedLocales)) {
            $supportedLocales = config('app.supported_locales', ['en', 'th']);
        }

        if (!in_array($locale, $supportedLocales)) {
            // Try to use default language from database first
            $defaultLang = LanguageSetting::getDefault();
            $locale = $defaultLang ? $defaultLang->code : config('app.fallback_locale', 'en');
        }

        // Set application locale
        App::setLocale($locale);

        // Store in session for persistence
        if (!Session::has('locale') || Session::get('locale') !== $locale) {
            Session::put('locale', $locale);
        }

        return $next($request);
    }
}
