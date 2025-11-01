<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBlockedIp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Check if IP is whitelisted (bypass all checks)
        if (BlockedIp::isWhitelisted($ip)) {
            return $next($request);
        }

        // Check if IP is blocked
        if (BlockedIp::isBlocked($ip)) {
            // Log the blocked attempt
            SecurityLog::logEvent(
                eventType: 'blocked_ip_attempt',
                ipAddress: $ip,
                severity: 'high',
                description: 'Blocked IP attempted to access the application',
                userAgent: $request->userAgent(),
                metadata: [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]
            );

            // Return 403 Forbidden
            return response()->view('errors.blocked', [
                'message' => 'Your IP address has been blocked due to security reasons.',
                'ip' => $ip,
            ], 403);
        }

        return $next($request);
    }
}
