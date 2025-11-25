<?php

namespace App\Http\Middleware;

use App\Models\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyCloudfareTurnstile
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $point  The specific point to check (login, register, etc.)
     */
    public function handle(Request $request, Closure $next, ?string $point = null): Response
    {
        // Check if Turnstile is globally enabled
        if (!config('turnstile.enabled')) {
            return $next($request);
        }

        // Check if Site Key and Secret Key are configured
        $siteKey = config('turnstile.site_key');
        $secretKey = config('turnstile.secret_key');

        if (empty($siteKey) || empty($secretKey)) {
            // ⚠️ WARNING: Turnstile เปิดใช้งานแต่ไม่ได้ตั้งค่า keys!
            // นี่คือ False Sense of Security - ระบบจะ BYPASS ทั้งหมด
            Log::warning('🚨 Cloudflare Turnstile SECURITY WARNING: enabled=true แต่ไม่มี keys!', [
                'ip' => $request->ip(),
                'point' => $point,
                'site_key_empty' => empty($siteKey),
                'secret_key_empty' => empty($secretKey),
                'warning' => 'Turnstile is BYPASSED - no actual protection!',
                'solution' => 'ตั้งค่า CLOUDFLARE_TURNSTILE_SITE_KEY และ CLOUDFLARE_TURNSTILE_SECRET_KEY ใน .env หรือปิด CLOUDFLARE_TURNSTILE_ENABLED=false',
            ]);
            return $next($request);
        }

        // Check if this specific point is enabled
        if ($point && !config("turnstile.points.{$point}", false)) {
            return $next($request);
        }

        // Bypass verification for admin users if configured
        if (config('turnstile.bypass_admin') && $this->isAdmin($request)) {
            Log::info('Cloudflare Turnstile bypassed for admin user', [
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'point' => $point,
            ]);
            return $next($request);
        }

        // Get the Turnstile token from request
        $token = $request->input('cf-turnstile-response');

        if (!$token) {
            return $this->failedVerification($request, $point, 'Missing Turnstile token');
        }

        // Verify the token with Cloudflare
        if (!$this->verifyToken($token, $request->ip())) {
            return $this->failedVerification($request, $point, 'Invalid Turnstile token');
        }

        // Log successful verification
        Log::info('Cloudflare Turnstile verification successful', [
            'ip' => $request->ip(),
            'point' => $point,
            'user_id' => $request->user()?->id,
        ]);

        return $next($request);
    }

    /**
     * Check if the current user is an admin.
     */
    protected function isAdmin(Request $request): bool
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        // Check if user has admin or super_admin role
        return in_array($user->role, ['admin', 'super_admin']);
    }

    /**
     * Verify the Turnstile token with Cloudflare API.
     */
    protected function verifyToken(string $token, ?string $ip): bool
    {
        $secretKey = config('turnstile.secret_key');

        if (!$secretKey) {
            Log::error('Cloudflare Turnstile secret key not configured');
            return false;
        }

        try {
            $response = Http::asForm()->post(config('services.cloudflare.turnstile.verify_url'), [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            if (!$response->successful()) {
                Log::error('Cloudflare Turnstile API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            $data = $response->json();

            if (!isset($data['success']) || !$data['success']) {
                Log::warning('Cloudflare Turnstile verification failed', [
                    'ip' => $ip,
                    'error_codes' => $data['error-codes'] ?? [],
                ]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Cloudflare Turnstile verification exception', [
                'message' => $e->getMessage(),
                'ip' => $ip,
            ]);
            return false;
        }
    }

    /**
     * Handle failed verification.
     */
    protected function failedVerification(Request $request, ?string $point, string $reason): Response
    {
        Log::warning('Cloudflare Turnstile verification failed', [
            'ip' => $request->ip(),
            'point' => $point,
            'reason' => $reason,
            'user_agent' => $request->userAgent(),
        ]);

        // Log to security logs
        SecurityLog::logTurnstileFailure(
            ipAddress: $request->ip(),
            point: $point ?? 'unknown',
            userAgent: $request->userAgent()
        );

        // For API requests, return JSON response
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Security verification failed. Please try again.'),
                'error' => 'turnstile_verification_failed',
            ], 422);
        }

        // For web requests, redirect back with error
        return back()->withErrors([
            'turnstile' => __('Security verification failed. Please try again.'),
        ])->withInput($request->except('cf-turnstile-response', 'password', 'password_confirmation'));
    }
}
