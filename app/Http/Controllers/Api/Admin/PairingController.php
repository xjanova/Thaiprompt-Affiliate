<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\MobileAuthToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Admin Mobile API: PairingController
 *
 * ระบบ QR Pairing สำหรับ Admin App ↔ Web
 *
 * Flow:
 * 1. Admin login web อยู่แล้ว → POST /api/admin/auth/pair/init (ผ่าน admin web AJAX)
 *    → ได้ pair_code (8 ตัว uppercase) + qr_payload + expires_in
 * 2. Web แสดง QR (encode pair_payload เช่น "thaipromptadmin://pair/<code>")
 * 3. Admin App สแกน QR → POST /api/admin/auth/pair/claim
 *    { pair_code, device_id, device_name?, code? (2FA) }
 * 4. Backend ตรวจ:
 *    - pair_code ยังไม่หมดอายุ + ยังไม่ถูกใช้
 *    - generator user ยังเป็น admin
 *    - ถ้า admin มี 2FA → ต้องใส่ code
 * 5. ออก Sanctum token (ability=['admin']) ผูกกับ device → return { token, admin }
 *
 * ทางเลือก: App polling /pair/status เพื่อรอ web confirm (รองรับ flow ที่ web ต้องกดยืนยันก่อน)
 */
class PairingController extends Controller
{
    /**
     * อายุ pair_code (วินาที)
     */
    private const PAIR_CODE_TTL = 300;          // 5 นาที

    /**
     * Rate limit
     */
    private const MAX_INIT_PER_HOUR = 30;       // ต่อ admin user
    private const MAX_CLAIM_PER_IP = 10;        // ต่อ IP ต่อนาที

    /**
     * URL scheme สำหรับ deep link
     */
    private const APP_SCHEME = 'thaipromptadmin';

    /**
     * Step 1: สร้าง pair_code (admin web → AJAX call)
     *
     * ต้อง auth:sanctum + admin.api middleware แล้ว (ออกที่ route group)
     */
    public function init(Request $request): JsonResponse
    {
        $admin = $request->user();

        // Rate limit ต่อ admin user (กันสร้าง pair_code รัวๆ)
        $rlKey = 'admin-pair-init:'.$admin->id;
        if (RateLimiter::tooManyAttempts($rlKey, self::MAX_INIT_PER_HOUR)) {
            $seconds = RateLimiter::availableIn($rlKey);

            return $this->error('สร้างรหัสจับคู่บ่อยเกินไป กรุณาลองใหม่ใน '.$seconds.' วินาที', 429);
        }
        RateLimiter::hit($rlKey, 3600);

        // สร้าง pair_code 8 ตัว uppercase A-Z 0-9 (อ่านง่าย ไม่มี O, I, 0, 1)
        $pairCode = $this->generateReadablePairCode(8);

        // ตรวจสอบว่ามี admin user เปิด 2FA ไหม
        $twoFactor = $admin->twoFactorSettings ?? null;
        $requires2fa = $twoFactor && $twoFactor->enabled;

        // สร้าง record
        $token = MobileAuthToken::create([
            // ── ฟิลด์เดิมของ PKCE flow (จำเป็นใส่ค่า dummy เพราะ NOT NULL) ──
            'login_token' => hash('sha256', Str::random(64)),
            'login_token_expires_at' => now()->addSeconds(self::PAIR_CODE_TTL),
            'code_challenge' => 'admin-pair-flow',
            'state' => Str::random(32),
            'device_id' => 'pending-'.Str::random(16),    // จะถูก override ตอน claim
            'device_name' => 'Pending Pair',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            // ── ฟิลด์ admin ใหม่ ──
            'is_admin' => true,
            'pair_code' => hash('sha256', $pairCode),
            'pair_code_expires_at' => now()->addSeconds(self::PAIR_CODE_TTL),
            'generator_user_id' => $admin->id,
            'requires_2fa' => $requires2fa,
            'two_factor_passed' => false,
        ]);

        // QR payload — deep link เพื่อให้ app เปิดได้โดยตรง (ทางเลือก)
        $qrPayload = self::APP_SCHEME.'://pair/'.$pairCode;

        Log::info('Admin pair code generated', [
            'admin_id' => $admin->id,
            'token_id' => $token->id,
            'requires_2fa' => $requires2fa,
            'ip' => $request->ip(),
        ]);

        return $this->success([
            'pair_code' => $pairCode,                       // โชว์ใต้ QR สำหรับกรอกมือ
            'qr_payload' => $qrPayload,                     // encode เป็น QR
            'expires_in' => self::PAIR_CODE_TTL,
            'requires_2fa' => $requires2fa,
            'admin_email' => $admin->email,                 // โชว์บนเว็บ confirm ถูกคน
        ], 'สร้างรหัสจับคู่สำเร็จ');
    }

    /**
     * Step 3: App ส่ง pair_code เพื่อขอ token
     */
    public function claim(Request $request): JsonResponse
    {
        // Rate limit per IP
        $rlKey = 'admin-pair-claim:'.$request->ip();
        if (RateLimiter::tooManyAttempts($rlKey, self::MAX_CLAIM_PER_IP)) {
            $seconds = RateLimiter::availableIn($rlKey);

            return $this->error('พยายามจับคู่บ่อยเกินไป กรุณาลองใหม่ใน '.$seconds.' วินาที', 429);
        }
        RateLimiter::hit($rlKey, 60);

        $validator = Validator::make($request->all(), [
            'pair_code' => ['required', 'string', 'size:8'],
            'device_id' => ['required', 'string', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'min:6', 'max:8'],
        ]);

        if ($validator->fails()) {
            return $this->error('ข้อมูลไม่ถูกต้อง', 422, $validator->errors()->toArray());
        }

        $pairCode = strtoupper($request->pair_code);
        $pairCodeHash = hash('sha256', $pairCode);

        // ค้นหา token
        $token = MobileAuthToken::where('pair_code', $pairCodeHash)
            ->where('is_admin', true)
            ->whereNull('claimed_at')
            ->first();

        if (! $token) {
            Log::warning('Admin pair claim: invalid pair_code', [
                'ip' => $request->ip(),
                'device_id' => $request->device_id,
            ]);

            return $this->error('รหัสจับคู่ไม่ถูกต้องหรือถูกใช้ไปแล้ว', 401, null, 'INVALID_PAIR_CODE');
        }

        if ($token->isPairCodeExpired()) {
            $token->delete();

            return $this->error('รหัสจับคู่หมดอายุ กรุณาสร้างใหม่', 401, null, 'PAIR_CODE_EXPIRED');
        }

        // ดึง admin user ที่สร้าง pair_code
        $admin = User::find($token->generator_user_id);
        if (! $admin) {
            $token->delete();

            return $this->error('ไม่พบ admin ผู้สร้างรหัสนี้', 404);
        }

        // ตรวจสอบ admin role อีกที (กรณีถูกลด role หลังสร้าง pair_code)
        $isAdmin = ($admin->is_super_admin ?? false) || ($admin->role ?? null) === 'admin';
        if (! $isAdmin) {
            $token->delete();

            return $this->error('บัญชี admin นี้ถูกระงับสิทธิ์', 403, null, 'GENERATOR_NOT_ADMIN');
        }

        // ถ้าต้อง 2FA และยังไม่ผ่าน → require code
        if ($token->requires_2fa && ! $token->two_factor_passed) {
            if (! $request->filled('code')) {
                return $this->success([
                    'requires_2fa' => true,
                ], 'กรุณายืนยันรหัส 2FA ก่อน', 200);
            }

            // ตรวจสอบรหัส 2FA
            if (! $this->verifyAdminTwoFactor($admin, $request->code)) {
                Log::warning('Admin pair claim: 2FA failed', [
                    'admin_id' => $admin->id,
                    'ip' => $request->ip(),
                ]);

                return $this->error('รหัส 2FA ไม่ถูกต้อง', 401, null, '2FA_INVALID');
            }

            $token->two_factor_passed = true;
        }

        // ทุกอย่างผ่าน → claim
        $token->update([
            'user_id' => $admin->id,
            'device_id' => $request->device_id,
            'device_name' => $request->device_name ?? 'Admin Mobile',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'claimed_at' => now(),
            'used_at' => now(),
        ]);

        // ออก admin token (ability='admin')
        $tokenName = 'admin-pair-'.substr($request->device_id, 0, 12);
        $apiToken = $admin->createToken($tokenName, ['admin'])->plainTextToken;

        Log::info('Admin pair claim success', [
            'admin_id' => $admin->id,
            'device_id' => $request->device_id,
            'ip' => $request->ip(),
        ]);

        return $this->success([
            'token' => $apiToken,
            'admin' => new AdminUserResource($admin),
        ], 'จับคู่สำเร็จ ยินดีต้อนรับ '.$admin->name);
    }

    /**
     * Polling สถานะ pair_code (สำหรับ web ที่รอดูว่า app claim หรือยัง)
     */
    public function status(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pair_code' => ['required', 'string', 'size:8'],
        ]);

        if ($validator->fails()) {
            return $this->error('ข้อมูลไม่ถูกต้อง', 422);
        }

        $token = MobileAuthToken::where('pair_code', hash('sha256', strtoupper($request->pair_code)))
            ->where('is_admin', true)
            ->first();

        if (! $token) {
            return $this->success(['status' => 'not_found']);
        }

        if ($token->isPairCodeExpired() && ! $token->claimed_at) {
            return $this->success(['status' => 'expired']);
        }

        if ($token->claimed_at) {
            return $this->success([
                'status' => 'claimed',
                'device_name' => $token->device_name,
                'claimed_at' => $token->claimed_at->toIso8601String(),
            ]);
        }

        return $this->success(['status' => 'pending']);
    }

    /**
     * ยกเลิก pair_code (admin web กดยกเลิกก่อน app claim)
     */
    public function cancel(Request $request): JsonResponse
    {
        $admin = $request->user();

        $validator = Validator::make($request->all(), [
            'pair_code' => ['required', 'string', 'size:8'],
        ]);

        if ($validator->fails()) {
            return $this->error('ข้อมูลไม่ถูกต้อง', 422);
        }

        $deleted = MobileAuthToken::where('pair_code', hash('sha256', strtoupper($request->pair_code)))
            ->where('is_admin', true)
            ->where('generator_user_id', $admin->id)
            ->whereNull('claimed_at')
            ->delete();

        return $this->success(['cancelled' => $deleted > 0], 'ยกเลิกการจับคู่สำเร็จ');
    }

    // ────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────

    /**
     * สร้าง pair_code อ่านง่าย (ตัด O, 0, I, 1, L ออก)
     */
    private function generateReadablePairCode(int $length = 8): string
    {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';   // ไม่มี O, I, 0, 1, L
        $charsLen = strlen($chars);
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $charsLen - 1)];
        }

        return $code;
    }

    /**
     * ตรวจสอบรหัส 2FA ของ admin
     */
    private function verifyAdminTwoFactor(User $admin, string $code): bool
    {
        $twoFactor = $admin->twoFactorSettings;
        if (! $twoFactor || ! ($twoFactor->enabled ?? false) || empty($twoFactor->google2fa_secret)) {
            return false;
        }

        try {
            $secret = $twoFactor->google2fa_secret;
            try {
                $secret = decrypt($secret);
            } catch (\Throwable $e) {
                // raw secret
            }

            $google2fa = new \PragmaRX\Google2FA\Google2FA;

            return $google2fa->verifyKey($secret, $code, 2);
        } catch (\Throwable $e) {
            Log::error('Pair 2FA verify error', ['error' => $e->getMessage()]);

            return false;
        }
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
