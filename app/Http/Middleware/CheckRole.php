<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ตรวจสอบสิทธิ์ตาม role ก่อนเข้าถึง route
 *
 * ใช้งาน: Route::middleware('role:user,affiliate') หรือ 'role:admin,super_admin'
 *
 * 🚨 (2026-07-27) แก้ redirect loop ที่ทำให้ 55 บัญชีบน prod ใช้งานเว็บไม่ได้เลย:
 *    เดิม redirectToDashboard() เด้งคนที่ไม่ผ่านไป route('user.home')
 *    แต่ /user/* ถูกหุ้มด้วย middleware 'role:user' อยู่ → คนที่ไม่ใช่ role 'user'
 *    (affiliate 35 / provider 19 / manager 1) โดนบล็อกซ้ำแล้วเด้งกลับที่เดิมไม่รู้จบ
 *    = ERR_TOO_MANY_REDIRECTS ตั้งแต่ล็อกอินเสร็จ (LoginController เด้งไป user.home)
 *
 * กันไม่ให้เกิดซ้ำ 2 ชั้น:
 *    1. MEMBER_ROLES = แหล่งความจริงเดียวว่า "ใครเข้าพื้นที่สมาชิก /user/* ได้"
 *       (routes/web.php ใช้ค่าเดียวกันนี้หุ้ม group → กติกาตรงกันเสมอ)
 *    2. loop guard ใน redirectToDashboard() — ถ้าปลายทางที่จะเด้งคือหน้าเดิมที่เพิ่ง
 *       ถูกบล็อก ให้ตกไปหน้าแรกสาธารณะแทน และถ้ายังชนอีกให้ตอบ 403 ไปเลย
 *       (ต่อให้อนาคตมีคนเพิ่ม role ใหม่แล้วลืมแก้ MEMBER_ROLES ก็จะไม่วนลูป)
 */
class CheckRole
{
    /**
     * Role ที่ถือเป็น "สมาชิก" — เข้าพื้นที่ส่วนตัว /user/* ได้
     *
     * เจตนาของระบบ MLM/affiliate: /user/* คือพื้นที่ข้อมูลของตัวเอง
     * (กระเป๋าเงิน, ค่าคอมมิชชั่น, ผู้มุ่งหวัง, KYC, โปรไฟล์) ทุก route
     * ผูกกับ auth()->id() ของเจ้าตัวอยู่แล้ว ไม่ใช่พื้นที่หลังบ้าน
     * → นักขาย (affiliate), ผู้ให้บริการ (provider), ผู้จัดการ (manager),
     *   ผู้สอน (instructor) ต้องเข้าดูข้อมูลตัวเองได้ ไม่งั้นสมัครมาแล้วใช้อะไรไม่ได้เลย
     *
     * ⚠️ เพิ่ม role ใหม่ในระบบ → เพิ่มที่นี่ที่เดียว (routes/web.php อ่านค่าจากตรงนี้)
     *
     * หมายเหตุ: admin / super_admin ผ่านทุก route อยู่แล้ว (เช็คแยกใน handle())
     *          ส่วน seller มีพื้นที่ของตัวเองที่ /seller/* จึงไม่อยู่ในลิสต์นี้
     *
     * @var array<string>
     */
    public const MEMBER_ROLES = [
        'user',
        'affiliate',
        'provider',
        'manager',
        'instructor',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Check if user is super admin (has access to everything)
        if ($user->is_super_admin) {
            return $next($request);
        }

        // Check if user is admin (can access user/seller routes for management)
        // Admin สามารถเข้าถึง user routes เพื่อทดสอบและจัดการระบบ
        if ($user->role === 'admin' || $user->role === 'super_admin') {
            return $next($request);
        }

        // Check if user's role matches any of the allowed roles
        foreach ($roles as $role) {
            if ($user->role === $role) {
                return $next($request);
            }
        }

        // User doesn't have permission, redirect to their appropriate dashboard
        return $this->redirectToDashboard($request, $user);
    }

    /**
     * เด้งผู้ใช้ไปหน้าที่ "ตัวเองเข้าได้จริง"
     *
     * ⚠️ ห้ามเด้งไปหน้าที่ middleware ตัวนี้หุ้มอยู่แล้วผู้ใช้คนนั้นเข้าไม่ได้
     *    เพราะจะโดนบล็อกซ้ำ → วนลูปไม่รู้จบ (ERR_TOO_MANY_REDIRECTS)
     *
     * @param  \App\Models\User  $user
     */
    private function redirectToDashboard(Request $request, $user): Response
    {
        $target = $this->dashboardUrlFor($user);

        // 🔒 ชั้นกันลูปที่ 1: ปลายทาง = หน้าเดิมที่เพิ่งถูกบล็อก → ไปหน้าแรกสาธารณะแทน
        if ($this->isSamePage($target, $request->url())) {
            $target = route('home');
        }

        // 🔒 ชั้นกันลูปที่ 2: หน้าแรกสาธารณะยังชนกับหน้าเดิมอีก (ไม่ควรเกิด แต่กันไว้)
        //    → ตอบ 403 ไปเลย ห้าม redirect ต่อเด็ดขาด
        if ($this->isSamePage($target, $request->url())) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        return redirect()->to($target)->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
    }

    /**
     * หา URL หน้าแรกที่เหมาะกับ role นั้นๆ
     *
     * role ที่ไม่รู้จัก (แอดมินสร้างเพิ่มเองในตาราง roles) → หน้าแรกสาธารณะ
     * ซึ่งเปิดให้ทุกคนเข้าได้เสมอ จึงปลอดภัยที่จะใช้เป็นค่า fallback
     *
     * @param  \App\Models\User  $user
     */
    private function dashboardUrlFor($user): string
    {
        if ($user->is_super_admin || $user->role === 'admin' || $user->role === 'super_admin') {
            return route('admin.dashboard');
        }

        if ($user->role === 'seller') {
            return route('seller.dashboard');
        }

        if (in_array($user->role, self::MEMBER_ROLES, true)) {
            return route('user.home');
        }

        return route('home');
    }

    /**
     * URL สองตัวนี้คือ "หน้าเดียวกัน" หรือไม่
     *
     * เทียบเฉพาะ path เพราะ scheme/host อาจต่างกันได้เมื่ออยู่หลัง proxy
     * (APP_URL เป็น https แต่ request ที่เข้ามาเป็น http) — ถ้าเทียบทั้ง URL
     * จะจับลูปไม่เจอในเคสนั้น
     */
    private function isSamePage(string $a, string $b): bool
    {
        $pathOf = static fn (string $url): string => rtrim(parse_url($url, PHP_URL_PATH) ?: '/', '/');

        return $pathOf($a) === $pathOf($b);
    }
}
