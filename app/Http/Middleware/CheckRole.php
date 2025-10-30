<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user is super admin (has access to everything)
        if ($user->is_super_admin) {
            return $next($request);
        }

        // Check if user's role matches any of the allowed roles
        foreach ($roles as $role) {
            if ($user->role === $role) {
                return $next($request);
            }
        }

        // User doesn't have permission, redirect to their appropriate dashboard
        return $this->redirectToDashboard($user);
    }

    /**
     * Redirect user to their appropriate dashboard
     */
    private function redirectToDashboard($user)
    {
        if ($user->is_super_admin || $user->role === 'admin' || $user->role === 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        if ($user->role === 'seller') {
            return redirect()->route('seller.dashboard')->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        return redirect()->route('user.dashboard')->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
    }
}
