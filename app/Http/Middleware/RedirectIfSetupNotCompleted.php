<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect to setup wizard if setup not completed
 */
class RedirectIfSetupNotCompleted
{
    /**
     * Routes that should be accessible even if setup is not completed
     */
    protected array $except = [
        'setup/*',
        'lang/*',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if this is a setup route
        if ($this->inExceptArray($request)) {
            return $next($request);
        }

        // Check if setup is completed
        if (!$this->isSetupCompleted()) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }

    /**
     * Check if setup is completed
     */
    protected function isSetupCompleted(): bool
    {
        // Check if setup completed flag exists
        if (File::exists(storage_path('app/.setup_completed'))) {
            return true;
        }

        // Check if super admin exists
        if (User::where('role', 'super_admin')->exists()) {
            // Auto-create flag if admin exists but flag doesn't
            File::put(storage_path('app/.setup_completed'), now()->toString());
            return true;
        }

        return false;
    }

    /**
     * Determine if the request has a URI that should pass through
     */
    protected function inExceptArray(Request $request): bool
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
