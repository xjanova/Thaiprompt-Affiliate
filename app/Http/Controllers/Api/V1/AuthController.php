<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAccountActive;
use App\Models\User;
use App\Services\PushTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login via API (for mobile app)
     *
     * 🔒 (2026-09-25) route หุ้ม throttle.login + throttle:6,1 แล้ว (routes/api.php)
     *    - บัญชีที่ถูกระงับ → 403 ACCOUNT_SUSPENDED (ไม่ออก token)
     *    - บัญชีที่ถูกลบ → หาไม่เจอเอง (SoftDeletes) ตอบเหมือนรหัสผิด ไม่บอกว่าเคยมีบัญชี
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['อีเมลหรือรหัสผ่านไม่ถูกต้อง'],
            ]);
        }

        // เช็คหลังรหัสผ่านถูกเท่านั้น — ไม่บอกคนเดารหัสว่าบัญชีนี้ถูกระงับ
        if ($user->isSuspended()) {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_SUSPENDED',
                'message' => EnsureAccountActive::SUSPENDED_MESSAGE,
            ], 403);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        // ดึง wallet_address จาก wallet relationship
        $walletAddress = $user->wallet?->wallet_address;

        return response()->json([
            'success' => true,
            'message' => 'เข้าสู่ระบบสำเร็จ',
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
     *
     * 🔔 (2026-09-25) CC-21: แอปส่ง push_token (และ/หรือ device_id) มาด้วยได้
     *    → ถอด token ของเครื่องนี้ออกจากบัญชี (เครื่องที่ออกจากระบบแล้วจะไม่ได้แจ้งเตือนของบัญชีเดิม)
     *    body (ไม่บังคับ): { push_token?: string, device_id?: string }
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        $pushToken = $request->input('push_token');
        $deviceId = $request->input('device_id');

        $detached = 0;
        if (is_string($pushToken) || is_string($deviceId)) {
            try {
                $detached = PushTokenService::detachForUser(
                    (int) $user->id,
                    is_string($pushToken) ? mb_substr($pushToken, 0, 500) : null,
                    is_string($deviceId) ? mb_substr($deviceId, 0, 100) : null
                );
            } catch (\Throwable $e) {
                // ถอด push token ไม่สำเร็จ ห้ามทำให้ logout ล้ม
                Log::warning('API logout: detach push token failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // token ของแอปคือ PersonalAccessToken — TransientToken (session) ไม่มีอะไรให้ลบ
        $accessToken = $user->currentAccessToken();
        if ($accessToken instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $accessToken->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'ออกจากระบบสำเร็จ',
            'data' => [
                'push_tokens_removed' => $detached,
            ],
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
                'referralCode' => $user->referral_code,
                'referralLink' => url('/register?ref='.$user->referral_code),
                'is_super_admin' => $user->is_super_admin ?? false,

                // SSO-link state for juntra (and any other federated client)
                'line_user_id' => $user->line_user_id,
                'facebook_user_id' => $fbPsid,
                'signup_via' => $signupVia,
            ]),
        ]);
    }
}
