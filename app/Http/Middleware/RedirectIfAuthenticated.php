<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                // Redirect to appropriate dashboard based on user role
                return redirect($this->getDashboardRoute($user));
            }
        }

        return $next($request);
    }

    /**
     * Get the appropriate dashboard route based on user role
     */
    private function getDashboardRoute($user): string
    {
        // Check if user is super admin or admin role
        if ($user->is_super_admin || $user->role === 'admin' || $user->role === 'super_admin') {
            return route('admin.dashboard');
        }

        // Check if user is seller
        if ($user->role === 'seller') {
            return route('seller.dashboard');
        }

        // Default to user dashboard for regular users
        return route('user.dashboard');
    }
}
