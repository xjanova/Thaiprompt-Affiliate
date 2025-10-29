<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleInertiaRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
