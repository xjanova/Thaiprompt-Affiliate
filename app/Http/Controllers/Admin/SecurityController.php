<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\BlockedIp;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
            'turnstile_enabled' => ['nullable', 'boolean'],
            'turnstile_site_key' => ['nullable', 'string'],
            'turnstile_secret_key' => ['nullable', 'string'],
            'turnstile_bypass_admin' => ['nullable', 'boolean'],
            'turnstile_theme' => ['nullable', 'string', 'in:auto,light,dark'],
            'turnstile_size' => ['nullable', 'string', 'in:normal,compact'],
            'turnstile_login' => ['nullable', 'boolean'],
            'turnstile_register' => ['nullable', 'boolean'],
            'turnstile_password_change' => ['nullable', 'boolean'],
            'turnstile_profile_update' => ['nullable', 'boolean'],
            'turnstile_withdrawal' => ['nullable', 'boolean'],
            'turnstile_affiliate_app' => ['nullable', 'boolean'],
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
            'rate_limiting_enabled' => ['nullable', 'boolean'],
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
}
