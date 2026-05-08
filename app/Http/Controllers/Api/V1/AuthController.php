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
     *
     * The juntraweb / จันทรา.online site reads `line_user_id` and
     * `facebook_user_id` from this payload to gate its AI chat — see
     * App\Http\Controllers\ChatController in juntraweb. We expose them
     * explicitly so juntra never has to scan FortuneReadings to figure
     * out the FB-link state.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $walletAddress = $user->wallet?->wallet_address;

        // Surface the most-recent fortune_reading PSID as the canonical
        // facebook_user_id for SSO purposes — this is the FB id that the
        // user has actually used to talk to the bot. No PSID = never came
        // via FB → juntra will treat them as "not FB-linked".
        $fbPsid = \App\Models\FortuneReading::query()
            ->where('user_id', $user->id)
            ->whereNotNull('facebook_user_id')
            ->orderByDesc('created_at')
            ->value('facebook_user_id');

        $signupVia = $user->line_user_id ? 'line'
                   : ($fbPsid ? 'facebook'
                   : ($user->email ? 'email' : null));

        return response()->json([
            'success' => true,
            'data' => array_merge($user->toArray(), [
                'wallet_address' => $walletAddress,
                'referralCode'   => $user->referral_code,
                'referralLink'   => url('/register?ref='.$user->referral_code),
                'is_super_admin' => $user->is_super_admin ?? false,

                // SSO-link state for juntra (and any other federated client)
                'line_user_id'     => $user->line_user_id,
                'facebook_user_id' => $fbPsid,
                'signup_via'       => $signupVia,
            ]),
        ]);
    }
}
