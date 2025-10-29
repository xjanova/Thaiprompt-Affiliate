<?php

namespace App\Http\Middleware;

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
        // Get locale from session, query parameter, or default
        $locale = $request->get('lang', Session::get('locale', config('app.locale')));

        // Validate locale (only allow supported languages)
        $supportedLocales = config('app.supported_locales', ['en', 'th']);
        if (!in_array($locale, $supportedLocales)) {
            $locale = config('app.fallback_locale', 'en');
        }

        // Set application locale
        App::setLocale($locale);

        // Store in session for persistence
        Session::put('locale', $locale);

        return $next($request);
    }
}
