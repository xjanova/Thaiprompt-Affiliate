<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware บังคับให้ผู้ใช้เชื่อมต่อ LINE ก่อนเข้าใช้งาน
 *
 * ตรวจสอบว่าผู้ใช้มี line_user_id หรือไม่
 * ถ้าไม่มีจะ redirect ไปหน้าบังคับเชื่อมต่อ LINE
 *
 * ใช้งาน:
 * - ใน route: Route::middleware('require.line.uid')
 * - หรือใน controller: $this->middleware('require.line.uid');
 *
 * ตัวเลือก:
 * - 'soft' - แค่แสดง warning แต่ไม่บังคับ redirect
 * - ไม่ระบุ - บังคับ redirect ไปหน้าเชื่อมต่อ LINE
 *
 * @example
 * Route::middleware('require.line.uid')->group(function () {
 *     // Routes ที่ต้องการ LINE UID
 * });
 *
 * Route::middleware('require.line.uid:soft')->group(function () {
 *     // Routes ที่แนะนำให้มี LINE UID แต่ไม่บังคับ
 * });
 */
class RequireLineUid
{
    /**
     * Routes ที่ยกเว้นไม่ต้องตรวจสอบ LINE UID
     *
     * @var array<string>
     */
    protected array $exceptRoutes = [
        'user.line-required',
        'line.link',
        'line.link.callback',
        'line.login',
        'line.callback',
        'line.registration.*',
        'logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $mode  'soft' = แค่แสดง warning, null = บังคับ redirect
     */
    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        $user = $request->user();

        // ถ้าไม่ได้ login ให้ผ่านไป (ให้ auth middleware จัดการ)
        if (! $user) {
            return $next($request);
        }

        // ถ้ามี LINE UID แล้ว ให้ผ่านไป
        if ($user->line_user_id) {
            return $next($request);
        }

        // ตรวจสอบว่าอยู่ใน route ที่ยกเว้นหรือไม่
        $currentRoute = $request->route()?->getName();
        foreach ($this->exceptRoutes as $exceptRoute) {
            if ($currentRoute && fnmatch($exceptRoute, $currentRoute)) {
                return $next($request);
            }
        }

        // Soft mode: แค่เพิ่ม warning ให้ user รู้
        if ($mode === 'soft') {
            session()->flash('line_warning', 'แนะนำให้เชื่อมต่อ LINE เพื่อความสะดวกในการใช้งานและรับการแจ้งเตือน');

            return $next($request);
        }

        // ถ้า request เป็น AJAX/JSON ให้ return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเชื่อมต่อบัญชี LINE ก่อนใช้งาน',
                'redirect' => route('user.line-required'),
                'code' => 'LINE_REQUIRED',
            ], 403);
        }

        // เก็บ URL ปัจจุบันเพื่อ redirect กลับมาหลังเชื่อมต่อ LINE
        session()->put('line_redirect_after', $request->url());

        // Redirect ไปหน้าบังคับเชื่อมต่อ LINE
        return redirect()->route('user.line-required')
            ->with('warning', 'กรุณาเชื่อมต่อบัญชี LINE เพื่อยืนยันตัวตน');
    }
}
