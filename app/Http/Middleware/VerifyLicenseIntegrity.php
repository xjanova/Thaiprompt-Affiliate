<?php

namespace App\Http\Middleware;

use App\Services\IntegrityService;
use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify License Integrity Middleware
 * ตรวจสอบ file integrity และ license ทุกครั้งที่มี request สำคัญ
 */
class VerifyLicenseIntegrity
{
    protected IntegrityService $integrityService;
    protected LicenseService $licenseService;

    public function __construct(IntegrityService $integrityService, LicenseService $licenseService)
    {
        $this->integrityService = $integrityService;
        $this->licenseService = $licenseService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for certain routes (login, public pages)
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Check file integrity (cached for 1 hour)
        $integrityCheck = $this->integrityService->verify();

        if (!$integrityCheck['valid']) {
            Log::critical('Access blocked due to file integrity violations', [
                'violations' => $integrityCheck['violations'],
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            abort(403, 'System integrity check failed. Please contact support.');
        }

        // Check license periodically (every request in admin area)
        if ($this->shouldCheckLicense($request)) {
            $validation = $this->licenseService->validate();

            if (!$validation['valid']) {
                Log::warning('Access blocked due to invalid license', [
                    'error' => $validation['error'] ?? 'unknown',
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                ]);

                abort(403, 'Invalid or expired license. Please contact support.');
            }
        }

        return $next($request);
    }

    /**
     * Check if should skip integrity check for this request
     */
    protected function shouldSkip(Request $request): bool
    {
        $skipRoutes = [
            'login',
            'register',
            'password.*',
            'setup.*', // Allow setup wizard
        ];

        return $request->routeIs($skipRoutes);
    }

    /**
     * Check if should validate license for this request
     */
    protected function shouldCheckLicense(Request $request): bool
    {
        // Check license for admin routes
        return $request->is('admin/*') || $request->is('api/*');
    }
}
