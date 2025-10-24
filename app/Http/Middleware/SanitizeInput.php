<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize all input data
        $input = $request->all();

        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                // Remove any HTML tags except allowed ones
                $value = strip_tags($value, config('security.allowed_tags', '<b><i><u><strong><em>'));

                // Remove any null bytes
                $value = str_replace(chr(0), '', $value);

                // Trim whitespace
                $value = trim($value);
            }
        });

        $request->merge($input);

        return $next($request);
    }
}
