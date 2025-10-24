<?php

namespace App\Http\Middleware;

use App\Models\SystemInfo;
use Closure;
use Illuminate\Http\Request;

class CheckSetupCompleted
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip check for setup routes
        if ($request->is('setup*') || $request->is('api/setup*')) {
            return $next($request);
        }

        // Check if setup is completed
        if (!SystemInfo::isSetupCompleted()) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }
}
