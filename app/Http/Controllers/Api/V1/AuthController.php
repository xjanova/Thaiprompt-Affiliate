<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login via API (for mobile app)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        // ดึง wallet_address จาก wallet relationship
        $walletAddress = $user->wallet?->wallet_address;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => array_merge($user->toArray(), [
                    'wallet_address' => $walletAddress,
                    'referralCode' => $user->referral_code,
                    'referralLink' => url('/register?ref='.$user->referral_code),
                    'is_super_admin' => $user->is_super_admin ?? false,
                ]),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout via API
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $walletAddress = $user->wallet?->wallet_address;

        return response()->json([
            'success' => true,
            'data' => array_merge($user->toArray(), [
                'wallet_address' => $walletAddress,
                'referralCode' => $user->referral_code,
                'referralLink' => url('/register?ref='.$user->referral_code),
                'is_super_admin' => $user->is_super_admin ?? false,
            ]),
        ]);
    }
}
