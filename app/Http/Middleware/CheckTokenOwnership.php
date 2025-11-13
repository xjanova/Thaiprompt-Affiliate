<?php

namespace App\Http\Middleware;

use App\Models\TPIXToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenOwnership
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tokenId = $request->route('id') ?? $request->route('token');

        if (!$tokenId) {
            return response()->json(['error' => 'Token ID not found'], 400);
        }

        $token = TPIXToken::find($tokenId);

        if (!$token) {
            return response()->json(['error' => 'Token not found'], 404);
        }

        $user = $request->user();

        // Check if user is owner or admin
        if ($token->creator_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You do not own this token'
            ], 403);
        }

        // Add token to request for easy access
        $request->merge(['token' => $token]);

        return $next($request);
    }
}
