<?php

namespace App\Http\Middleware;

use App\Models\TPIXToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTokenDeployment
{
    /**
     * Handle an incoming request.
     *
     * Verifies that the token is properly deployed before allowing certain operations
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

        // Check if token is deployed
        if (!in_array($token->status, ['deployed', 'active'])) {
            return response()->json([
                'error' => 'Token not deployed',
                'message' => 'This token has not been deployed yet. Current status: ' . $token->status,
                'token_status' => $token->status,
            ], 422);
        }

        // Verify contract address exists
        if (!$token->contract_address) {
            return response()->json([
                'error' => 'Invalid token state',
                'message' => 'Token is marked as deployed but has no contract address',
            ], 422);
        }

        // Add token to request
        $request->merge(['token' => $token]);

        return $next($request);
    }
}
