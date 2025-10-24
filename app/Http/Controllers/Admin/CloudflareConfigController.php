<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CloudflareConfig;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CloudflareConfigController extends Controller
{
    /**
     * Display Cloudflare configuration
     */
    public function index()
    {
        $config = CloudflareConfig::getActive() ?? new CloudflareConfig();

        return view('admin.cloudflare.index', compact('config'));
    }

    /**
     * Update Cloudflare configuration
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'tunnel_enabled' => 'boolean',
            'tunnel_id' => 'nullable|string',
            'tunnel_secret' => 'nullable|string',
            'verify_cf_headers' => 'boolean',
            'filter_comments' => 'boolean',
            'filter_orders' => 'boolean',
            'filter_contact_forms' => 'boolean',
            'filter_registrations' => 'boolean',
            'challenge_mode' => 'required|in:off,managed,high,under_attack',
            'enable_bot_fight_mode' => 'boolean',
            'enable_ddos_protection' => 'boolean',
            'security_level' => 'required|in:off,essentially_off,low,medium,high,under_attack',
            'browser_integrity_check' => 'boolean',
            'enable_waf' => 'boolean',
            'challenge_ttl' => 'integer|min:300|max:86400',
            'custom_spam_keywords' => 'nullable|array',
            'ip_whitelist' => 'nullable|array',
            'ip_blacklist' => 'nullable|array',
            'country_whitelist' => 'nullable|array',
            'country_blacklist' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $config = CloudflareConfig::getActive();

        if (!$config) {
            $config = new CloudflareConfig();
        }

        $config->fill($request->only([
            'name',
            'tunnel_enabled',
            'tunnel_id',
            'tunnel_secret',
            'verify_cf_headers',
            'filter_comments',
            'filter_orders',
            'filter_contact_forms',
            'filter_registrations',
            'challenge_mode',
            'enable_bot_fight_mode',
            'enable_ddos_protection',
            'security_level',
            'browser_integrity_check',
            'enable_waf',
            'challenge_ttl',
            'custom_spam_keywords',
            'ip_whitelist',
            'ip_blacklist',
            'country_whitelist',
            'country_blacklist',
        ]));

        // Handle rate limit rules
        if ($request->has('rate_limit_rules')) {
            $config->rate_limit_rules = $request->input('rate_limit_rules');
        }

        $config->save();

        // Log configuration change
        SecurityLog::logEvent('cloudflare_config_changed', [
            'severity' => 'medium',
            'description' => 'Cloudflare configuration updated',
            'metadata' => [
                'config_id' => $config->id,
                'changes' => $request->only([
                    'tunnel_enabled',
                    'filter_comments',
                    'filter_orders',
                    'security_level',
                ]),
            ]
        ]);

        return redirect()->route('admin.cloudflare.index')
            ->with('success', 'Cloudflare configuration updated successfully.');
    }

    /**
     * Test Cloudflare connection
     */
    public function testConnection(Request $request)
    {
        $config = CloudflareConfig::getActive();

        if (!$config || !$config->tunnel_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare Tunnel is not enabled'
            ]);
        }

        // Test Cloudflare headers
        $cfHeaders = [
            'CF-Ray' => $request->header('CF-Ray'),
            'CF-Connecting-IP' => $request->header('CF-Connecting-IP'),
            'CF-IPCountry' => $request->header('CF-IPCountry'),
            'CF-Visitor' => $request->header('CF-Visitor'),
        ];

        $isCloudflare = !empty($cfHeaders['CF-Ray']);

        return response()->json([
            'success' => $isCloudflare,
            'message' => $isCloudflare
                ? 'Connection through Cloudflare detected'
                : 'Not connected through Cloudflare',
            'headers' => $cfHeaders,
            'ip' => $request->ip(),
        ]);
    }

    /**
     * Get security statistics
     */
    public function statistics()
    {
        $stats = [
            'spam_blocked_24h' => SecurityLog::byEventType('spam_detected')
                ->recent(24)
                ->count(),
            'rate_limit_24h' => SecurityLog::byEventType('rate_limit_exceeded')
                ->recent(24)
                ->count(),
            'unauthorized_access_24h' => SecurityLog::byEventType('unauthorized_access')
                ->recent(24)
                ->count(),
            'total_security_events_24h' => SecurityLog::suspicious()
                ->recent(24)
                ->count(),
            'login_failures_24h' => SecurityLog::byEventType('login_failure')
                ->recent(24)
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * View security logs
     */
    public function logs(Request $request)
    {
        $query = SecurityLog::query()
            ->with('user')
            ->orderBy('created_at', 'desc');

        // Filter by event type
        if ($request->has('event_type') && $request->event_type !== 'all') {
            $query->where('event_type', $request->event_type);
        }

        // Filter by severity
        if ($request->has('severity') && $request->severity !== 'all') {
            $query->where('severity', $request->severity);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50);

        return view('admin.cloudflare.logs', compact('logs'));
    }
}
