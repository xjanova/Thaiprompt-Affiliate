<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Models\ThreatIp;
use App\Models\Setting;
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

        // Check if IP is blocked manually
        if (BlockedIp::isBlocked($ip)) {
            // Get the blocked IP record to get the reason
            $blockedRecord = BlockedIp::where('ip_address', $ip)
                ->where('type', 'blacklist')
                ->where('is_active', true)
                ->first();

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
                'reason' => $blockedRecord?->reason,
            ], 403);
        }

        // Check Threat Intelligence if enabled
        if ($this->shouldBlockThreatIp($ip)) {
            $threatInfo = ThreatIp::getThreatInfo($ip);

            // Log the blocked threat attempt
            SecurityLog::logEvent(
                eventType: 'threat_ip_blocked',
                ipAddress: $ip,
                severity: 'high',
                description: "Threat IP blocked: {$threatInfo->threat_type} (confidence: {$threatInfo->confidence_score}%)",
                userAgent: $request->userAgent(),
                metadata: [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'threat_type' => $threatInfo->threat_type,
                    'threat_source' => $threatInfo->source,
                    'confidence_score' => $threatInfo->confidence_score,
                ]
            );

            // Return 403 Forbidden
            return response()->view('errors.blocked', [
                'message' => 'Your IP address has been identified as a security threat and has been blocked.',
                'ip' => $ip,
                'reason' => "Threat Type: {$threatInfo->getThreatTypeLabel()} (Source: {$threatInfo->source})",
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if IP should be blocked based on threat intelligence
     */
    protected function shouldBlockThreatIp(string $ip): bool
    {
        // Check if threat intelligence is enabled
        $enabled = Setting::get('threat_intelligence_enabled', 'boolean', false);
        if (!$enabled) {
            return false;
        }

        // Get threat info
        $threatInfo = ThreatIp::getThreatInfo($ip);
        if (!$threatInfo) {
            return false;
        }

        // Check confidence threshold
        $threshold = Setting::get('threat_confidence_threshold', 'integer', 70);
        if ($threatInfo->confidence_score < $threshold) {
            return false;
        }

        // Check if this threat type should be blocked
        $blockTypes = [
            'proxy' => Setting::get('threat_block_proxy', 'boolean', false),
            'vpn' => Setting::get('threat_block_vpn', 'boolean', false),
            'tor' => Setting::get('threat_block_tor', 'boolean', false),
            'abuse' => Setting::get('threat_block_abuse', 'boolean', false),
            'spam' => Setting::get('threat_block_abuse', 'boolean', false), // Use abuse setting for spam
            'malware' => Setting::get('threat_block_abuse', 'boolean', false), // Use abuse setting for malware
            'scanner' => Setting::get('threat_block_abuse', 'boolean', false), // Use abuse setting for scanner
        ];

        return $blockTypes[$threatInfo->threat_type] ?? false;
    }
}
