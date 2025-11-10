<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HotelAdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Super admin can access everything
        if ($user->is_admin) {
            return $next($request);
        }

        // Check if user is hotel admin
        if (!$user->is_hotel_admin || !$user->managed_hotel_id) {
            abort(403, 'Unauthorized access. You need hotel admin privileges.');
        }

        return $next($request);
    }
}
