<?php

namespace App\Http\Middleware;

use App\Models\SystemInfo;
use Closure;
use Illuminate\Http\Request;

class RedirectIfSetupCompleted
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // If setup is completed, redirect to home
        if (SystemInfo::isSetupCompleted()) {
            return redirect('/');
        }

        return $next($request);
    }
}
