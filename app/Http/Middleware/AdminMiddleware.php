<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * ตรวจสอบสิทธิ์การเข้าถึงสำหรับ Admin
     *
     * เฉพาะ Super Admin หรือ ผู้ใช้ที่มี role = 'admin' เท่านั้น
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ตรวจสอบว่า user ล็อกอินหรือยัง
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // ตรวจสอบว่าเป็น super admin หรือ admin role
        // ✅ แก้ไข: ใช้ role field แทน is_admin ที่ไม่มีอยู่จริง
        if (!$user->is_super_admin && $user->role !== 'admin') {
            abort(403, 'ไม่มีสิทธิ์เข้าถึง ต้องเป็น Admin เท่านั้น');
        }

        return $next($request);
    }
}
