<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CloudflareConfig;
use Illuminate\Support\Facades\Log;

class CheckCloudflareChallenge
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get Cloudflare configuration
        $config = CloudflareConfig::where('is_active', true)->first();

        if (!$config) {
            return $next($request);
        }

        // Skip if Cloudflare Tunnel is not enabled
        if (!$config->tunnel_enabled) {
            return $next($request);
        }

        // Verify Cloudflare headers
        if ($config->verify_cf_headers) {
            if (!$this->verifyCloudflareRequest($request, $config)) {
                Log::warning('Request failed Cloudflare verification', [
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'user_agent' => $request->userAgent()
                ]);

                return response()->json([
                    'error' => 'Security validation failed'
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Verify that the request came through Cloudflare
     *
     * @param Request $request
     * @param CloudflareConfig $config
     * @return bool
     */
    private function verifyCloudflareRequest(Request $request, CloudflareConfig $config): bool
    {
        // Check for Cloudflare headers
        $cfConnectingIp = $request->header('CF-Connecting-IP');
        $cfRay = $request->header('CF-Ray');
        $cfVisitor = $request->header('CF-Visitor');

        // If any Cloudflare header is missing, it might not be from Cloudflare
        if (!$cfConnectingIp || !$cfRay) {
            return false;
        }

        // Additional verification: check if IP is from Cloudflare IP ranges
        // This would require maintaining a list of Cloudflare IPs
        // For now, we'll just check for the headers

        return true;
    }
}
