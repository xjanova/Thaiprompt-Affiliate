<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileAuthToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Admin Web: Mobile Pair Page
 *
 * หน้าให้ admin ที่ login web อยู่แล้ว → กด "เชื่อมต่อมือถือ"
 * เพื่อสร้าง QR code สำหรับให้ Admin Mobile App สแกน
 *
 * ใช้ session auth (admin middleware) แทน Sanctum bearer
 * — ไม่ต้องออก self-token ก่อน
 */
class MobilePairController extends Controller
{
    /**
     * อายุ pair_code (วินาที) — ตรงกับ PairingController API
     */
    private const PAIR_CODE_TTL = 300;

    /**
     * URL scheme
     */
    private const APP_SCHEME = 'thaipromptadmin';

    /**
     * แสดงหน้า "เชื่อมต่อ Admin Mobile App"
     */
    public function index(Request $request)
    {
        return view('admin.mobile-pair', [
            'pageTitle' => 'เชื่อมต่อ Admin Mobile App',
        ]);
    }

    /**
     * POST /admin/mobile-pair/init — สร้าง pair_code (session auth)
     */
    public function init(Request $request): JsonResponse
    {
        $admin = $request->user();

        // Rate limit ต่อ admin user
        $rlKey = 'admin-web-pair-init:'.$admin->id;
        if (RateLimiter::tooManyAttempts($rlKey, 30)) {
            $seconds = RateLimiter::availableIn($rlKey);

            return $this->error('สร้างรหัสจับคู่บ่อยเกินไป กรุณาลองใหม่ใน '.$seconds.' วินาที', 429);
        }
        RateLimiter::hit($rlKey, 3600);

        // ตรวจสอบ admin role อีกที (กันกรณี middleware miss)
        $isAdmin = ($admin->is_super_admin ?? false) || ($admin->role ?? null) === 'admin';
        if (! $isAdmin) {
            return $this->error('บัญชีนี้ไม่มีสิทธิ์ admin', 403);
        }

        // สร้าง readable pair code
        $pairCode = $this->generateReadablePairCode(8);

        // ตรวจสอบ 2FA
        $twoFactor = $admin->twoFactorSettings;
        $requires2fa = $twoFactor && ($twoFactor->enabled ?? false);

        $token = MobileAuthToken::create([
            'login_token' => hash('sha256', Str::random(64)),
            'login_token_expires_at' => now()->addSeconds(self::PAIR_CODE_TTL),
            'code_challenge' => 'admin-pair-flow',
            'state' => Str::random(32),
            'device_id' => 'pending-'.Str::random(16),
            'device_name' => 'Pending Pair',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_admin' => true,
            'pair_code' => hash('sha256', $pairCode),
            'pair_code_expires_at' => now()->addSeconds(self::PAIR_CODE_TTL),
            'generator_user_id' => $admin->id,
            'requires_2fa' => $requires2fa,
            'two_factor_passed' => false,
        ]);

        Log::info('Admin web pair code generated', [
            'admin_id' => $admin->id,
            'token_id' => $token->id,
            'requires_2fa' => $requires2fa,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'pair_code' => $pairCode,
                'qr_payload' => self::APP_SCHEME.'://pair/'.$pairCode,
                'expires_in' => self::PAIR_CODE_TTL,
                'requires_2fa' => $requires2fa,
                'admin_email' => $admin->email,
            ],
        ]);
    }

    /**
     * GET /admin/mobile-pair/status?pair_code=XXX
     */
    public function status(Request $request): JsonResponse
    {
        $pairCode = $request->query('pair_code');
        if (! $pairCode || strlen($pairCode) !== 8) {
            return response()->json(['success' => false, 'message' => 'pair_code ไม่ถูกต้อง'], 422);
        }

        $token = MobileAuthToken::where('pair_code', hash('sha256', strtoupper($pairCode)))
            ->where('is_admin', true)
            ->first();

        if (! $token) {
            return response()->json(['success' => true, 'data' => ['status' => 'not_found']]);
        }

        if ($token->isPairCodeExpired() && ! $token->claimed_at) {
            return response()->json(['success' => true, 'data' => ['status' => 'expired']]);
        }

        if ($token->claimed_at) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'claimed',
                    'device_name' => $token->device_name,
                    'claimed_at' => $token->claimed_at->toIso8601String(),
                ],
            ]);
        }

        return response()->json(['success' => true, 'data' => ['status' => 'pending']]);
    }

    /**
     * POST /admin/mobile-pair/cancel
     */
    public function cancel(Request $request): JsonResponse
    {
        $admin = $request->user();
        $pairCode = $request->input('pair_code');

        if (! $pairCode || strlen($pairCode) !== 8) {
            return response()->json(['success' => false, 'message' => 'pair_code ไม่ถูกต้อง'], 422);
        }

        $deleted = MobileAuthToken::where('pair_code', hash('sha256', strtoupper($pairCode)))
            ->where('is_admin', true)
            ->where('generator_user_id', $admin->id)
            ->whereNull('claimed_at')
            ->delete();

        return response()->json(['success' => true, 'data' => ['cancelled' => $deleted > 0]]);
    }

    private function generateReadablePairCode(int $length = 8): string
    {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $charsLen = strlen($chars);
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $charsLen - 1)];
        }

        return $code;
    }

    private function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
