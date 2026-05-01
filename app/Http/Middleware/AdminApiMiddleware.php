<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminApiMiddleware
 *
 * Middleware สำหรับ Admin Mobile API
 * คืน JSON 401/403 (ไม่ redirect แบบ AdminMiddleware ที่ใช้กับ web)
 *
 * เงื่อนไข:
 * 1. ผู้ใช้ต้อง login ผ่าน Sanctum
 * 2. Token ต้องมี ability 'admin' (ออกจาก admin login flow)
 * 3. User record ต้องเป็น super_admin หรือ role='admin'
 */
class AdminApiMiddleware
{
    /**
     * ตรวจสอบสิทธิ์ Admin สำหรับ API request
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. ต้อง authenticated
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ต้องเข้าสู่ระบบก่อน',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }

        // 2. Token ต้องมี ability 'admin' (กันคนใช้ user token มาเรียก admin API)
        $token = $user->currentAccessToken();
        if ($token && method_exists($token, 'can') && ! $token->can('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Token นี้ไม่มีสิทธิ์ Admin',
                'error_code' => 'INSUFFICIENT_TOKEN_SCOPE',
            ], 403);
        }

        // 3. User record ต้องเป็น admin จริง (กรณีถูกลด role หลังออก token)
        $isAdmin = ($user->is_super_admin ?? false) || ($user->role ?? null) === 'admin';

        if (! $isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'บัญชีนี้ไม่มีสิทธิ์ Admin',
                'error_code' => 'NOT_ADMIN',
            ], 403);
        }

        return $next($request);
    }
}
