<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🔒 กันบัญชีที่ถูกระงับ (users.blocked_at) ใช้งานต่อ — ทั้งแอป (Sanctum token) และเว็บ (session)
 *
 * ลงทะเบียนต่อท้าย middleware group 'web' และ 'api' ใน bootstrap/app.php จึงครอบทุก route
 * รวมถึงทุกกลุ่ม auth:sanctum ในทุกไฟล์ route (api.php, admin_api.php ...) โดยไม่ต้องไล่ใส่ทีละกลุ่ม
 *
 * - API: เช็คเฉพาะ request ที่แนบ Bearer token มา → ถ้าเจ้าของ token ถูกระงับ
 *        เพิกถอน token ทั้งหมดของบัญชีนั้น แล้วตอบ 403 code ACCOUNT_SUSPENDED
 *        (กลุ่ม middleware ทำงานก่อน auth:sanctum ของ route จึง resolve ผู้ใช้เอง
 *         — RequestGuard cache ผู้ใช้ไว้ auth:sanctum ที่ตามมาไม่ query ซ้ำ)
 * - เว็บ: ถ้า session ล็อกอินอยู่เป็นบัญชีที่ถูกระงับ → ออกจากระบบ + ล้าง session แล้วพากลับหน้า login
 *
 * บัญชีที่ถูกลบ (soft delete) ไม่ต้องเช็คที่นี่ — guard หาไม่เจออยู่แล้วเพราะ SoftDeletes scope
 */
class EnsureAccountActive
{
    /** ข้อความเดียวกันทุกช่องทาง (เว็บ/แอป/ล็อกอิน) */
    public const SUSPENDED_MESSAGE = 'บัญชีของคุณถูกระงับการใช้งาน หากคิดว่าเป็นความผิดพลาด กรุณาติดต่อทีมงาน';

    public function handle(Request $request, Closure $next): Response
    {
        // ── แอป / API: มี Bearer token เท่านั้นถึงเช็ค (request สาธารณะไม่เสีย query เพิ่ม) ──
        if ($request->bearerToken()) {
            $user = $this->resolveSanctumUser();

            if ($user instanceof User && $user->isSuspended()) {
                $this->revokeApiTokens($user);

                return $this->suspendedJsonResponse();
            }

            return $next($request);
        }

        // ── เว็บ: session ที่ล็อกอินอยู่ ──
        if ($request->hasSession()) {
            $user = Auth::guard('web')->user();

            if ($user instanceof User && $user->isSuspended()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return $this->suspendedJsonResponse();
                }

                return redirect()->route('login')->with('error', self::SUSPENDED_MESSAGE);
            }
        }

        return $next($request);
    }

    /**
     * หาเจ้าของ Bearer token ผ่าน guard sanctum (token ที่ไม่ใช่ของ Sanctum เช่น Passport → null)
     */
    private function resolveSanctumUser(): mixed
    {
        try {
            return Auth::guard('sanctum')->user();
        } catch (\Throwable $e) {
            // guard พัง (เช่น DB สะดุด) ห้ามทำให้ทั้ง request ล้ม — ปล่อยให้ auth:sanctum ของ route ตัดสินต่อ
            Log::warning('EnsureAccountActive: resolve sanctum user failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * เพิกถอน token ของแอปทั้งหมดของบัญชีที่ถูกระงับ (บังคับออกจากระบบทุกเครื่อง)
     */
    private function revokeApiTokens(User $user): void
    {
        try {
            $user->tokens()->delete();
        } catch (\Throwable $e) {
            Log::warning('EnsureAccountActive: revoke tokens failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function suspendedJsonResponse(): Response
    {
        return response()->json([
            'success' => false,
            'code' => 'ACCOUNT_SUSPENDED',
            'message' => self::SUSPENDED_MESSAGE,
        ], 403);
    }
}
