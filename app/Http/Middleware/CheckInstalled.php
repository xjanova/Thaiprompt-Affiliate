<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if installation lock file exists
        $installedFile = storage_path('installed');

        // If not installed, redirect to setup
        if (!File::exists($installedFile)) {
            // Allow access to setup routes
            if (!$request->is('setup*')) {
                return redirect()->route('setup.index');
            }
        } else {
            // If already installed, don't allow access to setup
            if ($request->is('setup*')) {
                return redirect('/');
            }
        }

        return $next($request);
    }
}
