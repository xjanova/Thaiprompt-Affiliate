<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Services\AutoBanService;
use App\Services\IpIntelligenceService;
use App\Services\ThreatIntelligenceService;
use App\Models\ThreatIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class SecurityController extends Controller
{
    /**
     * Display security dashboard
     */
    public function index()
    {
        $settings = Setting::whereIn('group', ['security'])->get()->groupBy('group');

        // Get blocked IPs
        $blockedIps = BlockedIp::with('blocker')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get recent security logs
        $securityLogs = SecurityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Get statistics
        $stats = [
            'total_blocked_ips' => BlockedIp::blacklist()->active()->count(),
            'total_whitelisted_ips' => BlockedIp::whitelist()->active()->count(),
            'failed_logins_today' => SecurityLog::byType('login_failed')
                ->whereDate('created_at', today())
                ->count(),
            'rate_limit_hits_today' => SecurityLog::byType('rate_limit_exceeded')
                ->whereDate('created_at', today())
                ->count(),
            'turnstile_failures_today' => SecurityLog::byType('turnstile_failed')
                ->whereDate('created_at', today())
                ->count(),
        ];

        return view('admin.security.index', compact('settings', 'blockedIps', 'securityLogs', 'stats'));
    }

    /**
     * Update Turnstile settings
     */
    public function updateTurnstile(Request $request)
    {
        $validated = $request->validate([
            'turnstile_enabled' => ['nullable'],
            'turnstile_site_key' => ['nullable', 'string'],
            'turnstile_secret_key' => ['nullable', 'string'],
            'turnstile_bypass_admin' => ['nullable'],
            'turnstile_theme' => ['nullable', 'string', 'in:auto,light,dark'],
            'turnstile_size' => ['nullable', 'string', 'in:normal,compact'],
            'turnstile_login' => ['nullable'],
            'turnstile_register' => ['nullable'],
            'turnstile_password_change' => ['nullable'],
            'turnstile_profile_update' => ['nullable'],
            'turnstile_withdrawal' => ['nullable'],
            'turnstile_affiliate_app' => ['nullable'],
        ]);

        // Handle checkbox values
        $validated['turnstile_enabled'] = $request->has('turnstile_enabled');
        $validated['turnstile_bypass_admin'] = $request->has('turnstile_bypass_admin');
        $validated['turnstile_login'] = $request->has('turnstile_login');
        $validated['turnstile_register'] = $request->has('turnstile_register');
        $validated['turnstile_password_change'] = $request->has('turnstile_password_change');
        $validated['turnstile_profile_update'] = $request->has('turnstile_profile_update');
        $validated['turnstile_withdrawal'] = $request->has('turnstile_withdrawal');
        $validated['turnstile_affiliate_app'] = $request->has('turnstile_affiliate_app');

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $type = is_bool($value) ? 'boolean' : 'string';
                Setting::set($key, $value, $type, 'security');
            }
        }

        // Update .env file
        $this->updateEnvFile($request);

        return back()->with('success', 'บันทึกการตั้งค่า Turnstile เรียบร้อยแล้ว');
    }

    /**
     * Update Rate Limiting settings
     */
    public function updateRateLimiting(Request $request)
    {
        $validated = $request->validate([
            'rate_limiting_enabled' => ['nullable'],
            'rate_limit_login_max_attempts' => ['nullable', 'integer', 'min:1', 'max:50'],
            'rate_limit_login_decay_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'rate_limit_login_lockout_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ]);

        $validated['rate_limiting_enabled'] = $request->has('rate_limiting_enabled');

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $type = is_bool($value) ? 'boolean' : 'integer';
                Setting::set($key, $value, $type, 'security');
            }
        }

        // Update .env file
        $this->updateEnvFileRateLimit($request);

        // Clear cache
        Cache::flush();

        return back()->with('success', 'บันทึกการตั้งค่า Rate Limiting เรียบร้อยแล้ว');
    }

    /**
     * Update .env file with Turnstile settings
     */
    protected function updateEnvFile(Request $request)
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        $envSettings = [
            'CLOUDFLARE_TURNSTILE_ENABLED' => $request->has('turnstile_enabled') ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_SITE_KEY' => $request->input('turnstile_site_key', ''),
            'CLOUDFLARE_TURNSTILE_SECRET_KEY' => $request->input('turnstile_secret_key', ''),
            'CLOUDFLARE_TURNSTILE_BYPASS_ADMIN' => $request->has('turnstile_bypass_admin') ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_THEME' => $request->input('turnstile_theme', 'auto'),
            'CLOUDFLARE_TURNSTILE_SIZE' => $request->input('turnstile_size', 'normal'),
            'CLOUDFLARE_TURNSTILE_LOGIN' => $request->has('turnstile_login') ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_REGISTER' => $request->has('turnstile_register') ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_PASSWORD_CHANGE' => $request->has('turnstile_password_change') ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_PROFILE_UPDATE' => $request->has('turnstile_profile_update') ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_WITHDRAWAL' => $request->has('turnstile_withdrawal') ? 'true' : 'false',
            'CLOUDFLARE_TURNSTILE_AFFILIATE_APP' => $request->has('turnstile_affiliate_app') ? 'true' : 'false',
        ];

        foreach ($envSettings as $key => $value) {
            if (preg_match("/^{$key}=/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
    }

    /**
     * Update .env file with Rate Limiting settings
     */
    protected function updateEnvFileRateLimit(Request $request)
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        $envSettings = [
            'RATE_LIMITING_ENABLED' => $request->has('rate_limiting_enabled') ? 'true' : 'false',
            'RATE_LIMIT_LOGIN_MAX_ATTEMPTS' => $request->input('rate_limit_login_max_attempts', 5),
            'RATE_LIMIT_LOGIN_DECAY_MINUTES' => $request->input('rate_limit_login_decay_minutes', 15),
            'RATE_LIMIT_LOGIN_LOCKOUT_MINUTES' => $request->input('rate_limit_login_lockout_minutes', 30),
        ];

        foreach ($envSettings as $key => $value) {
            if (preg_match("/^{$key}=/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
    }

    /**
     * Block an IP address
     */
    public function blockIp(Request $request)
    {
        $validated = $request->validate([
            'ip_type' => ['required', 'in:single,range,cidr'],
            'ip_address' => ['required_if:ip_type,single', 'nullable'],
            'ip_range_start' => ['required_if:ip_type,range', 'nullable'],
            'ip_range_end' => ['required_if:ip_type,range', 'nullable'],
            'ip_cidr' => ['required_if:ip_type,cidr', 'nullable'],
            'type' => ['required', 'in:blacklist,whitelist'],
            'reason' => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        // Validate IP format based on type
        if ($validated['ip_type'] === 'single') {
            if (!filter_var($validated['ip_address'], FILTER_VALIDATE_IP)) {
                return back()->withErrors(['ip_address' => 'Invalid IP address format.']);
            }
        } elseif ($validated['ip_type'] === 'range') {
            if (!filter_var($validated['ip_range_start'], FILTER_VALIDATE_IP) ||
                !filter_var($validated['ip_range_end'], FILTER_VALIDATE_IP)) {
                return back()->withErrors(['ip_range_start' => 'Invalid IP range format.']);
            }
        } elseif ($validated['ip_type'] === 'cidr') {
            if (!$this->validateCidr($validated['ip_cidr'])) {
                return back()->withErrors(['ip_cidr' => 'Invalid CIDR notation.']);
            }
        }

        // Create the blocked IP record
        $data = [
            'type' => $validated['type'],
            'ip_type' => $validated['ip_type'],
            'reason' => $validated['reason'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'blocked_by' => auth()->id(),
            'is_active' => true,
        ];

        // Add IP-specific fields
        switch ($validated['ip_type']) {
            case 'single':
                $data['ip_address'] = $validated['ip_address'];
                $ipDisplay = $validated['ip_address'];
                break;
            case 'range':
                $data['ip_address'] = $validated['ip_range_start']; // Store first IP for reference
                $data['ip_range_start'] = $validated['ip_range_start'];
                $data['ip_range_end'] = $validated['ip_range_end'];
                $ipDisplay = "{$validated['ip_range_start']} - {$validated['ip_range_end']}";
                break;
            case 'cidr':
                list($network, $mask) = explode('/', $validated['ip_cidr']);
                $data['ip_address'] = $network; // Store network IP for reference
                $data['ip_cidr'] = $validated['ip_cidr'];
                $ipDisplay = $validated['ip_cidr'];
                break;
        }

        BlockedIp::create($data);

        // Log the action
        SecurityLog::create([
            'event_type' => 'ip_' . $validated['type'],
            'ip_address' => $data['ip_address'],
            'user_id' => auth()->id(),
            'severity' => 'medium',
            'description' => "{$validated['ip_type']} IP {$ipDisplay} added to {$validated['type']} by " . auth()->user()->name,
            'user_agent' => request()->userAgent(),
        ]);

        return back()->with('success', 'เพิ่ม IP Address เรียบร้อยแล้ว');
    }

    /**
     * Validate CIDR notation
     */
    protected function validateCidr(string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return false;
        }

        list($ip, $mask) = explode('/', $cidr);

        // Validate IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Validate mask
        $mask = (int)$mask;

        // Check if IPv4 or IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $mask >= 0 && $mask <= 32;
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $mask >= 0 && $mask <= 128;
        }

        return false;
    }

    /**
     * Unblock an IP address
     */
    public function unblockIp($id)
    {
        $blockedIp = BlockedIp::findOrFail($id);
        $ipAddress = $blockedIp->ip_address;

        // Log the action before deleting
        SecurityLog::create([
            'event_type' => 'ip_unblocked',
            'ip_address' => $ipAddress,
            'user_id' => auth()->id(),
            'severity' => 'low',
            'description' => "IP address {$ipAddress} removed from {$blockedIp->type} by " . auth()->user()->name,
            'user_agent' => request()->userAgent(),
        ]);

        $blockedIp->delete();

        return back()->with('success', 'ลบ IP Address เรียบร้อยแล้ว');
    }

    /**
     * Analytics Dashboard
     */
    public function analytics(Request $request)
    {
        $days = $request->input('days', 30);

        $analytics = SecurityLog::getAnalytics($days);
        $autoBanStats = AutoBanService::getStatistics();

        // Get timeline data for charts
        $timelineAll = SecurityLog::getTimeline(null, $days);
        $timelineFailedLogins = SecurityLog::getTimeline('login_failed', $days);
        $timelineTurnstile = SecurityLog::getTimeline('turnstile_failed', $days);
        $timelineRateLimit = SecurityLog::getTimeline('rate_limit_exceeded', $days);

        return view('admin.security.analytics', compact(
            'analytics',
            'autoBanStats',
            'timelineAll',
            'timelineFailedLogins',
            'timelineTurnstile',
            'timelineRateLimit',
            'days'
        ));
    }

    /**
     * Get analytics data as JSON (for AJAX requests)
     */
    public function getAnalyticsData(Request $request)
    {
        $days = $request->input('days', 30);
        $analytics = SecurityLog::getAnalytics($days);

        return response()->json($analytics);
    }

    /**
     * Update Auto-Ban settings
     */
    public function updateAutoBan(Request $request)
    {
        $validated = $request->validate([
            'auto_ban_enabled' => ['nullable'],
            // Failed Login
            'auto_ban_failed_login_enabled' => ['nullable'],
            'auto_ban_failed_login_threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
            'auto_ban_failed_login_time_window' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'auto_ban_failed_login_ban_duration' => ['nullable', 'integer', 'min:1', 'max:10080'],
            // Turnstile
            'auto_ban_turnstile_enabled' => ['nullable'],
            'auto_ban_turnstile_threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
            'auto_ban_turnstile_time_window' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'auto_ban_turnstile_ban_duration' => ['nullable', 'integer', 'min:1', 'max:10080'],
            // Rate Limit
            'auto_ban_rate_limit_enabled' => ['nullable'],
            'auto_ban_rate_limit_threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
            'auto_ban_rate_limit_time_window' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'auto_ban_rate_limit_ban_duration' => ['nullable', 'integer', 'min:1', 'max:10080'],
            // Notifications
            'auto_ban_notifications_enabled' => ['nullable'],
            'auto_ban_email_enabled' => ['nullable'],
            'auto_ban_email_recipients' => ['nullable', 'string'],
        ]);

        // Update .env file
        $this->updateEnvFileAutoBan($request);

        return back()->with('success', 'บันทึกการตั้งค่า Auto-Ban เรียบร้อยแล้ว');
    }

    /**
     * Update .env file with Auto-Ban settings
     */
    protected function updateEnvFileAutoBan(Request $request)
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        $envSettings = [
            'AUTO_BAN_ENABLED' => $request->has('auto_ban_enabled') ? 'true' : 'false',
            // Failed Login
            'AUTO_BAN_FAILED_LOGIN_ENABLED' => $request->has('auto_ban_failed_login_enabled') ? 'true' : 'false',
            'AUTO_BAN_FAILED_LOGIN_THRESHOLD' => $request->input('auto_ban_failed_login_threshold', 10),
            'AUTO_BAN_FAILED_LOGIN_TIME_WINDOW' => $request->input('auto_ban_failed_login_time_window', 30),
            'AUTO_BAN_FAILED_LOGIN_BAN_DURATION' => $request->input('auto_ban_failed_login_ban_duration', 1440),
            // Turnstile
            'AUTO_BAN_TURNSTILE_ENABLED' => $request->has('auto_ban_turnstile_enabled') ? 'true' : 'false',
            'AUTO_BAN_TURNSTILE_THRESHOLD' => $request->input('auto_ban_turnstile_threshold', 15),
            'AUTO_BAN_TURNSTILE_TIME_WINDOW' => $request->input('auto_ban_turnstile_time_window', 60),
            'AUTO_BAN_TURNSTILE_BAN_DURATION' => $request->input('auto_ban_turnstile_ban_duration', 720),
            // Rate Limit
            'AUTO_BAN_RATE_LIMIT_ENABLED' => $request->has('auto_ban_rate_limit_enabled') ? 'true' : 'false',
            'AUTO_BAN_RATE_LIMIT_THRESHOLD' => $request->input('auto_ban_rate_limit_threshold', 20),
            'AUTO_BAN_RATE_LIMIT_TIME_WINDOW' => $request->input('auto_ban_rate_limit_time_window', 60),
            'AUTO_BAN_RATE_LIMIT_BAN_DURATION' => $request->input('auto_ban_rate_limit_ban_duration', 480),
            // Notifications
            'AUTO_BAN_NOTIFICATIONS_ENABLED' => $request->has('auto_ban_notifications_enabled') ? 'true' : 'false',
            'AUTO_BAN_EMAIL_ENABLED' => $request->has('auto_ban_email_enabled') ? 'true' : 'false',
            'AUTO_BAN_EMAIL_RECIPIENTS' => $request->input('auto_ban_email_recipients', 'admin@example.com'),
        ];

        foreach ($envSettings as $key => $value) {
            if (preg_match("/^{$key}=/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
    }

    /**
     * Export security logs as CSV
     */
    public function exportLogs(Request $request)
    {
        $query = SecurityLog::with('user')->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('country_code')) {
            $query->where('country_code', $request->country_code);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->limit(10000)->get(); // Limit to prevent memory issues

        $filename = 'security-logs-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');

            // CSV Header
            fputcsv($file, [
                'Date/Time',
                'Event Type',
                'IP Address',
                'Country',
                'City',
                'User',
                'Email',
                'OS',
                'Browser',
                'Device',
                'Severity',
                'Description',
                'VPN/Proxy',
            ]);

            // CSV Rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->event_type,
                    $log->ip_address,
                    $log->country_name ?? '-',
                    $log->city ?? '-',
                    $log->user->name ?? '-',
                    $log->email ?? '-',
                    $log->os ?? '-',
                    $log->browser ?? '-',
                    $log->device_type ?? '-',
                    $log->severity,
                    $log->description ?? '-',
                    ($log->is_vpn || $log->is_proxy || $log->is_tor) ? 'Yes' : 'No',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export analytics as PDF
     */
    public function exportAnalytics(Request $request)
    {
        $days = $request->input('days', 30);
        $analytics = SecurityLog::getAnalytics($days);
        $autoBanStats = AutoBanService::getStatistics();

        // For now, return JSON (can be enhanced with PDF library later)
        $data = [
            'analytics' => $analytics,
            'auto_ban_stats' => $autoBanStats,
            'period' => $days . ' days',
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        $filename = 'security-analytics-' . now()->format('Y-m-d-His') . '.json';

        return response()->json($data)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Threat Intelligence Dashboard
     */
    public function threatIntelligence(Request $request, ThreatIntelligenceService $service)
    {
        $stats = $service->getStatistics();

        // Build query with filters
        $query = ThreatIp::active();

        // Apply filters
        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
        }

        if ($request->filled('threat_type')) {
            $query->where('threat_type', $request->threat_type);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('confidence_min')) {
            $query->where('confidence_score', '>=', $request->confidence_min);
        }

        if ($request->filled('confidence_max')) {
            $query->where('confidence_score', '<=', $request->confidence_max);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('last_seen', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('last_seen', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'last_seen');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = ['ip_address', 'threat_type', 'source', 'confidence_score', 'last_seen', 'expires_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Get recent threats with pagination
        $recentThreats = $query->paginate($request->input('per_page', 20))
            ->appends($request->except('page'));

        // Get threat distribution
        $threatsByType = ThreatIp::active()
            ->selectRaw('threat_type, COUNT(*) as count')
            ->groupBy('threat_type')
            ->orderBy('count', 'desc')
            ->get();

        $threatsBySource = ThreatIp::active()
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->orderBy('count', 'desc')
            ->get();

        // Get unique values for filters
        $threatTypes = ThreatIp::active()
            ->distinct()
            ->pluck('threat_type')
            ->filter()
            ->sort()
            ->values();

        $sources = ThreatIp::active()
            ->distinct()
            ->pluck('source')
            ->filter()
            ->sort()
            ->values();

        return view('admin.security.threat-intelligence', compact(
            'stats',
            'recentThreats',
            'threatsByType',
            'threatsBySource',
            'threatTypes',
            'sources'
        ));
    }

    /**
     * Update threat intelligence manually
     */
    public function updateThreatIntelligence(Request $request, ThreatIntelligenceService $service)
    {
        // If this is an AJAX request, run in foreground and return JSON
        if ($request->ajax()) {
            try {
                $results = $service->updateAllSources();
                return response()->json([
                    'success' => true,
                    'message' => "อัปเดต Threat Intelligence สำเร็จ! อัปเดต {$results['total_updated']} รายการ",
                    'results' => $results,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                ], 500);
            }
        }

        // For regular requests, redirect with message
        try {
            $results = $service->updateAllSources();
            return back()->with('success', "อัปเดต Threat Intelligence สำเร็จ! อัปเดต {$results['total_updated']} รายการ");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }

    /**
     * Get threat intelligence update progress
     */
    public function getThreatUpdateProgress(ThreatIntelligenceService $service)
    {
        $progress = $service->getProgress();

        if (!$progress) {
            return response()->json([
                'percentage' => 0,
                'message' => 'ไม่มีการอัปเดตอยู่',
                'is_running' => false,
            ]);
        }

        return response()->json([
            'percentage' => $progress['percentage'],
            'message' => $progress['message'],
            'details' => $progress['details'] ?? [],
            'is_running' => $progress['percentage'] < 100,
            'updated_at' => $progress['updated_at'],
        ]);
    }

    /**
     * Check IP against threat databases
     */
    public function checkIpThreat(Request $request, ThreatIntelligenceService $service)
    {
        $validated = $request->validate([
            'ip_address' => ['required', 'ip'],
        ]);

        $ip = $validated['ip_address'];

        // Check local database first
        $localThreat = ThreatIp::getThreatInfo($ip);

        // Check external APIs if configured
        $abuseIpDb = $service->checkAbuseIPDB($ip);
        $ipQualityScore = $service->checkIPQualityScore($ip);

        return response()->json([
            'ip' => $ip,
            'is_threat' => ThreatIp::isThreatIp($ip),
            'local_threat' => $localThreat,
            'abuseipdb' => $abuseIpDb,
            'ipqualityscore' => $ipQualityScore,
        ]);
    }

    /**
     * Update threat intelligence settings
     */
    public function updateThreatSettings(Request $request)
    {
        $validated = $request->validate([
            'threat_intelligence_enabled' => ['nullable'],
            'threat_block_proxy' => ['nullable'],
            'threat_block_vpn' => ['nullable'],
            'threat_block_tor' => ['nullable'],
            'threat_block_abuse' => ['nullable'],
            'threat_confidence_threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
            'abuseipdb_api_key' => ['nullable', 'string'],
            'ipqualityscore_api_key' => ['nullable', 'string'],
            // Schedule settings
            'threat_auto_update_enabled' => ['nullable'],
            'threat_update_frequency' => ['nullable', 'string', 'in:hourly,daily,weekly,custom'],
            'threat_update_time' => ['nullable', 'string', 'regex:/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'threat_update_day' => ['nullable', 'integer', 'min:0', 'max:6'],
            'threat_update_cron' => ['nullable', 'string'],
        ]);

        // Validate custom cron if selected
        if ($request->input('threat_update_frequency') === 'custom' && $request->filled('threat_update_cron')) {
            if (!$this->validateCronExpression($request->input('threat_update_cron'))) {
                return back()->withErrors(['threat_update_cron' => 'รูปแบบ Cron Expression ไม่ถูกต้อง']);
            }
        }

        // Handle checkbox values
        $validated['threat_intelligence_enabled'] = $request->has('threat_intelligence_enabled');
        $validated['threat_block_proxy'] = $request->has('threat_block_proxy');
        $validated['threat_block_vpn'] = $request->has('threat_block_vpn');
        $validated['threat_block_tor'] = $request->has('threat_block_tor');
        $validated['threat_block_abuse'] = $request->has('threat_block_abuse');
        $validated['threat_auto_update_enabled'] = $request->has('threat_auto_update_enabled');

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string');
                Setting::set($key, $value, $type, 'security');
            }
        }

        // Update .env file for API keys
        $this->updateEnvFileThreatSettings($request);

        return back()->with('success', 'บันทึกการตั้งค่า Threat Intelligence เรียบร้อยแล้ว');
    }

    /**
     * Validate cron expression
     */
    protected function validateCronExpression(string $cron): bool
    {
        // Basic cron expression validation (5 parts: minute hour day month weekday)
        $parts = preg_split('/\s+/', trim($cron));

        if (count($parts) !== 5) {
            return false;
        }

        // Validate each part
        foreach ($parts as $index => $part) {
            // Allow *, numbers, ranges (1-5), steps (*/5), and lists (1,2,3)
            if (!preg_match('/^(\*|[0-9,\-\/]+)$/', $part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update .env file with Threat Intelligence API keys
     */
    protected function updateEnvFileThreatSettings(Request $request)
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        $envSettings = [
            'ABUSEIPDB_API_KEY' => $request->input('abuseipdb_api_key', ''),
            'IPQUALITYSCORE_API_KEY' => $request->input('ipqualityscore_api_key', ''),
        ];

        foreach ($envSettings as $key => $value) {
            if (preg_match("/^{$key}=/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
    }
}
