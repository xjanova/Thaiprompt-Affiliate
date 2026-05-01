<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Admin Mobile API: AuthController
 *
 * จัดการ login/logout/2FA/profile สำหรับ Thaiprompt Admin Mobile App
 *
 * Flow login (กรณี 2FA เปิด):
 * 1. POST /api/admin/auth/login { email, password, device_id, device_name? }
 *    → { requires_2fa: true, challenge_token, expires_in }
 * 2. POST /api/admin/auth/verify-2fa { challenge_token, code }
 *    → { token, admin: {...} }
 *
 * Flow login (กรณีไม่เปิด 2FA):
 * 1. POST /api/admin/auth/login → { token, admin: {...} } เลย (ไม่แนะนำสำหรับ admin)
 */
class AuthController extends Controller
{
    /**
     * Rate limiting
     */
    private const MAX_LOGIN_ATTEMPTS = 5;       // ต่อ IP ต่อนาที

    private const MAX_2FA_ATTEMPTS = 5;         // ต่อ challenge_token

    /**
     * อายุ challenge token (วินาที)
     */
    private const CHALLENGE_TOKEN_TTL = 300;    // 5 นาที

    /**
     * Step 1: Login ด้วย email + password
     */
    public function login(Request $request): JsonResponse
    {
        // Rate limit ต่อ IP
        $rlKey = 'admin-api-login:'.$request->ip();
        if (RateLimiter::tooManyAttempts($rlKey, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($rlKey);

            return $this->error('พยายามเข้าสู่ระบบบ่อยเกินไป กรุณาลองใหม่ใน '.$seconds.' วินาที', 429);
        }
        RateLimiter::hit($rlKey, 60);

        // ตรวจสอบ input
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_id' => ['required', 'string', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->error('ข้อมูลไม่ถูกต้อง', 422, $validator->errors()->toArray());
        }

        // ดึง user
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            Log::info('Admin API login failed', [
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);

            return $this->error('อีเมลหรือรหัสผ่านไม่ถูกต้อง', 401, [
                'email' => ['credentials_invalid'],
            ]);
        }

        // ตรวจสอบ admin role (กันไม่ให้ user ทั่วไป login ผ่าน admin endpoint)
        $isAdmin = ($user->is_super_admin ?? false) || ($user->role ?? null) === 'admin';
        if (! $isAdmin) {
            Log::warning('Admin API login: non-admin user attempted login', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return $this->error('บัญชีนี้ไม่มีสิทธิ์ Admin', 403, null, 'NOT_ADMIN');
        }

        // ตรวจสอบ 2FA
        $twoFactor = $user->twoFactorSettings ?? null;
        $twoFaEnabled = $twoFactor && $twoFactor->enabled;

        if ($twoFaEnabled) {
            // สร้าง challenge token รอ verify-2fa step
            $challengeToken = Str::random(64);
            Cache::put(
                $this->challengeKey($challengeToken),
                [
                    'user_id' => $user->id,
                    'device_id' => $request->device_id,
                    'device_name' => $request->device_name ?? 'Unknown Device',
                    'attempts' => 0,
                    'ip' => $request->ip(),
                ],
                now()->addSeconds(self::CHALLENGE_TOKEN_TTL)
            );

            return $this->success([
                'requires_2fa' => true,
                'challenge_token' => $challengeToken,
                'expires_in' => self::CHALLENGE_TOKEN_TTL,
                'method' => $twoFactor->preferred_method ?? 'google2fa',
            ], 'กรุณายืนยันรหัส 2FA');
        }

        // ไม่เปิด 2FA → ออก token ทันที (ปลอดภัยน้อยกว่า)
        $token = $this->issueAdminToken($user, $request->device_id, $request->device_name);

        return $this->success([
            'requires_2fa' => false,
            'token' => $token,
            'admin' => new AdminUserResource($user),
        ], 'เข้าสู่ระบบสำเร็จ');
    }

    /**
     * Step 2: ยืนยัน 2FA code → ได้ admin token จริง
     */
    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'string', 'min:6', 'max:8'],
        ]);

        if ($validator->fails()) {
            return $this->error('ข้อมูลไม่ถูกต้อง', 422, $validator->errors()->toArray());
        }

        $cacheKey = $this->challengeKey($request->challenge_token);
        $challenge = Cache::get($cacheKey);

        if (! $challenge) {
            return $this->error('Challenge token หมดอายุหรือไม่ถูกต้อง', 401, null, 'CHALLENGE_INVALID');
        }

        // นับจำนวนครั้งที่พยายาม
        if (($challenge['attempts'] ?? 0) >= self::MAX_2FA_ATTEMPTS) {
            Cache::forget($cacheKey);

            return $this->error('พยายามใส่รหัส 2FA เกินจำนวน กรุณา login ใหม่', 429, null, 'TOO_MANY_2FA_ATTEMPTS');
        }

        $user = User::find($challenge['user_id']);
        if (! $user) {
            Cache::forget($cacheKey);

            return $this->error('ไม่พบบัญชีผู้ใช้', 404);
        }

        // ตรวจสอบรหัส 2FA (Google2FA)
        $twoFactor = $user->twoFactorSettings;
        if (! $twoFactor || ! $twoFactor->enabled || empty($twoFactor->google2fa_secret)) {
            Cache::forget($cacheKey);

            return $this->error('บัญชีนี้ไม่ได้เปิด 2FA', 400, null, '2FA_NOT_ENABLED');
        }

        $google2fa = new Google2FA;

        try {
            // decrypt secret (TwoFactorUserSetting cast google2fa_secret as encrypted ส่วนใหญ่)
            $secret = $twoFactor->google2fa_secret;
            // ลอง decrypt ถ้า encrypt อยู่ ถ้าไม่ใช่ Crypt:: throw exception
            try {
                $secret = decrypt($secret);
            } catch (\Throwable $e) {
                // raw secret (ไม่ได้ encrypt)
            }

            $valid = $google2fa->verifyKey($secret, $request->code, 2);
        } catch (\Throwable $e) {
            Log::error('2FA verification error', ['error' => $e->getMessage(), 'user_id' => $user->id]);

            return $this->error('ไม่สามารถยืนยันรหัส 2FA ได้', 500);
        }

        if (! $valid) {
            // เพิ่ม attempt count
            $challenge['attempts'] = ($challenge['attempts'] ?? 0) + 1;
            Cache::put($cacheKey, $challenge, now()->addSeconds(self::CHALLENGE_TOKEN_TTL));

            return $this->error('รหัส 2FA ไม่ถูกต้อง', 401, null, '2FA_INVALID');
        }

        // ลบ challenge หลังใช้
        Cache::forget($cacheKey);

        // อัพเดท last_verified
        $twoFactor->update([
            'last_verified_at' => now(),
            'last_verified_ip' => $challenge['ip'] ?? request()->ip(),
            'total_verifications' => ($twoFactor->total_verifications ?? 0) + 1,
        ]);

        // ออก admin token
        $token = $this->issueAdminToken($user, $challenge['device_id'], $challenge['device_name']);

        Log::info('Admin API login success (with 2FA)', [
            'user_id' => $user->id,
            'device_id' => $challenge['device_id'],
            'ip' => request()->ip(),
        ]);

        return $this->success([
            'token' => $token,
            'admin' => new AdminUserResource($user),
        ], 'เข้าสู่ระบบสำเร็จ');
    }

    /**
     * GET /me — ดึง profile ของ admin ปัจจุบัน
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success([
            'admin' => new AdminUserResource($request->user()),
        ]);
    }

    /**
     * Logout — revoke current token
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'ออกจากระบบสำเร็จ');
    }

    /**
     * Logout ทุก device (revoke all tokens of this user)
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $count = $request->user()->tokens()->delete();

        return $this->success(['revoked_count' => $count], 'ออกจากระบบทุกอุปกรณ์สำเร็จ');
    }

    // ────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────

    /**
     * ออก Sanctum token ที่มี ability 'admin'
     */
    private function issueAdminToken(User $user, string $deviceId, ?string $deviceName = null): string
    {
        $tokenName = 'admin-mobile-'.substr($deviceId, 0, 12);
        if ($deviceName) {
            $tokenName .= ' ('.\Illuminate\Support\Str::limit($deviceName, 24, '').')';
        }

        // ออก token พร้อม ability 'admin'
        return $user->createToken($tokenName, ['admin'])->plainTextToken;
    }

    private function challengeKey(string $token): string
    {
        return 'admin-api-2fa-challenge:'.hash('sha256', $token);
    }

    private function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json(array_filter([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], fn ($v) => $v !== null), $status);
    }

    private function error(string $message, int $status = 400, ?array $errors = null, ?string $code = null): JsonResponse
    {
        return response()->json(array_filter([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'error_code' => $code,
        ], fn ($v) => $v !== null), $status);
    }
}
