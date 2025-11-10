<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if user is in Developer Mode
 * Only developers with whitelisted IPs can access special features
 */
class DevMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if dev mode is enabled
        if (!$this->isDevMode()) {
            abort(404); // Hide the feature completely
        }

        return $next($request);
    }

    /**
     * Check if current request is from developer
     */
    public static function isDevMode(): bool
    {
        // Check if dev mode is enabled in config
        if (!config('app.dev_mode_enabled', false)) {
            return false;
        }

        // Get current IP
        $currentIp = request()->ip();

        // Get whitelisted IPs from config
        $whitelistedIps = config('app.dev_ips', []);

        // Allow localhost for development
        if (app()->environment('local')) {
            $whitelistedIps[] = '127.0.0.1';
            $whitelistedIps[] = '::1';
        }

        // Check if current IP is whitelisted
        return in_array($currentIp, $whitelistedIps);
    }

    /**
     * Check if a specific IP is whitelisted
     */
    public static function isWhitelistedIp(string $ip): bool
    {
        $whitelistedIps = config('app.dev_ips', []);

        if (app()->environment('local')) {
            $whitelistedIps[] = '127.0.0.1';
            $whitelistedIps[] = '::1';
        }

        return in_array($ip, $whitelistedIps);
    }
}
