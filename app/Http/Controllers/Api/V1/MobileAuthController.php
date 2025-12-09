<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MobileAuthToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Mobile Authentication Controller
 *
 * ระบบ Web-Based Authentication สำหรับ Mobile App
 * ใช้ PKCE (Proof Key for Code Exchange) เพื่อความปลอดภัย
 *
 * Flow:
 * 1. App เรียก /init → ได้ login_token
 * 2. App เปิด WebBrowser → /mobile-login?token={login_token}
 * 3. User login ที่เว็บ
 * 4. เว็บ redirect กลับ → thaiprompt://auth?code={auth_code}
 * 5. App เรียก /exchange → ได้ access_token
 */
class MobileAuthController extends Controller
{
    /**
     * Token expiry times (seconds)
     */
    private const LOGIN_TOKEN_EXPIRY = 300;     // 5 นาที
    private const AUTH_CODE_EXPIRY = 60;        // 1 นาที

    /**
     * Rate limiting
     */
    private const MAX_INIT_ATTEMPTS = 10;       // 10 ครั้งต่อนาที
    private const MAX_EXCHANGE_ATTEMPTS = 5;   // 5 ครั้งต่อนาที

    /**
     * Step 1: Initialize mobile login
     *
     * สร้าง login_token สำหรับเปิด WebBrowser
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function init(Request $request): JsonResponse
    {
        // Rate limiting
        $key = 'mobile-auth-init:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_INIT_ATTEMPTS)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many attempts. Please try again later.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:255',
            'code_verifier' => 'required|string|min:43|max:128',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // สร้าง code_challenge จาก code_verifier (PKCE S256)
            $codeChallenge = $this->generateCodeChallenge($request->code_verifier);

            // สร้าง login_token
            $loginToken = Str::random(64);
            $state = Str::random(32);

            // บันทึก token
            $mobileAuthToken = MobileAuthToken::create([
                'login_token' => hash('sha256', $loginToken),
                'code_challenge' => $codeChallenge,
                'device_id' => $request->device_id,
                'device_name' => $request->device_name ?? 'Unknown Device',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'state' => $state,
                'login_token_expires_at' => now()->addSeconds(self::LOGIN_TOKEN_EXPIRY),
            ]);

            // สร้าง login URL
            $loginUrl = url('/mobile-login') . '?' . http_build_query([
                'token' => $loginToken,
                'state' => $state,
            ]);

            Log::info('Mobile auth init', [
                'device_id' => $request->device_id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'login_url' => $loginUrl,
                    'login_token' => $loginToken,
                    'state' => $state,
                    'expires_in' => self::LOGIN_TOKEN_EXPIRY,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile auth init error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize login',
            ], 500);
        }
    }

    /**
     * Step 5: Exchange auth_code for access_token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exchange(Request $request): JsonResponse
    {
        // Rate limiting
        $key = 'mobile-auth-exchange:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_EXCHANGE_ATTEMPTS)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many attempts. Please try again later.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $validator = Validator::make($request->all(), [
            'auth_code' => 'required|string',
            'code_verifier' => 'required|string|min:43|max:128',
            'state' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // ค้นหา token
            $authCodeHash = hash('sha256', $request->auth_code);
            $mobileAuthToken = MobileAuthToken::where('auth_code', $authCodeHash)
                ->where('state', $request->state)
                ->whereNotNull('user_id')
                ->whereNull('used_at')
                ->first();

            if (!$mobileAuthToken) {
                Log::warning('Mobile auth exchange: invalid auth_code', [
                    'ip' => $request->ip(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired auth code',
                ], 401);
            }

            // ตรวจสอบ expiry
            if ($mobileAuthToken->auth_code_expires_at < now()) {
                $mobileAuthToken->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Auth code expired',
                ], 401);
            }

            // ตรวจสอบ PKCE code_verifier
            $expectedChallenge = $this->generateCodeChallenge($request->code_verifier);
            if (!hash_equals($mobileAuthToken->code_challenge, $expectedChallenge)) {
                Log::warning('Mobile auth exchange: PKCE verification failed', [
                    'ip' => $request->ip(),
                    'device_id' => $mobileAuthToken->device_id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid code verifier',
                ], 401);
            }

            // Mark as used
            $mobileAuthToken->update([
                'used_at' => now(),
            ]);

            // ดึง user
            $user = User::find($mobileAuthToken->user_id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // สร้าง API token
            $tokenName = 'mobile-app-' . substr($mobileAuthToken->device_id, 0, 8);
            $apiToken = $user->createToken($tokenName)->plainTextToken;

            Log::info('Mobile auth exchange success', [
                'user_id' => $user->id,
                'device_id' => $mobileAuthToken->device_id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $apiToken,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'avatar_url' => $user->avatar_url,
                        'referral_code' => $user->referral_code,
                        'rank_id' => $user->rank_id,
                        'rank_name' => $user->rank?->name,
                        'wallet_balance' => $user->wallet_balance ?? 0,
                        'line_user_id' => $user->line_user_id,
                        'line_display_name' => $user->line_display_name,
                        'is_verified' => $user->is_verified ?? false,
                        'kyc_status' => $user->kyc_status ?? 'pending',
                    ],
                ],
                'message' => 'เข้าสู่ระบบสำเร็จ',
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile auth exchange error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to exchange token',
            ], 500);
        }
    }

    /**
     * Check login status (for polling)
     *
     * แอพสามารถเรียกเพื่อเช็คว่า user login สำเร็จหรือยัง
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login_token' => 'required|string',
            'state' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
            ], 422);
        }

        $loginTokenHash = hash('sha256', $request->login_token);
        $mobileAuthToken = MobileAuthToken::where('login_token', $loginTokenHash)
            ->where('state', $request->state)
            ->first();

        if (!$mobileAuthToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
            ], 404);
        }

        // ตรวจสอบ expiry
        if ($mobileAuthToken->login_token_expires_at < now()) {
            $mobileAuthToken->delete();
            return response()->json([
                'success' => false,
                'status' => 'expired',
                'message' => 'Token expired',
            ]);
        }

        // ตรวจสอบว่า login แล้วหรือยัง
        if ($mobileAuthToken->user_id && $mobileAuthToken->auth_code) {
            return response()->json([
                'success' => true,
                'status' => 'authenticated',
                'message' => 'User authenticated',
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => 'pending',
            'message' => 'Waiting for user to login',
        ]);
    }

    /**
     * Cancel mobile login
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cancel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login_token' => 'required|string',
            'state' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
            ], 422);
        }

        $loginTokenHash = hash('sha256', $request->login_token);
        MobileAuthToken::where('login_token', $loginTokenHash)
            ->where('state', $request->state)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Login cancelled',
        ]);
    }

    /**
     * Generate PKCE code challenge (S256)
     *
     * @param string $codeVerifier
     * @return string
     */
    private function generateCodeChallenge(string $codeVerifier): string
    {
        $hash = hash('sha256', $codeVerifier, true);
        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }
}
