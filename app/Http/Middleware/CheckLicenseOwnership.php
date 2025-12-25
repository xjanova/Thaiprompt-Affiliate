<?php

namespace App\Http\Middleware;

use App\Models\SoftwareLicense;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckLicenseOwnership Middleware
 *
 * ตรวจสอบว่า user เป็นเจ้าของ license
 */
class CheckLicenseOwnership
{
    /**
     * จัดการ incoming request
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // ตรวจสอบว่า user login แล้ว
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'กรุณาเข้าสู่ระบบเพื่อเข้าถึงหน้านี้');
        }

        // ดึง license จาก route parameter
        $license = $request->route('license');

        // ถ้าเป็น ID ให้ query จาก database
        if (is_numeric($license)) {
            $license = SoftwareLicense::find($license);
        }

        // ตรวจสอบว่า license มีอยู่จริง
        if (!$license) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ License ที่ต้องการ',
            ], 404);
        }

        // ตรวจสอบว่า user เป็นเจ้าของ license
        if ($license->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์เข้าถึง License นี้',
            ], 403);
        }

        // ผ่านการตรวจสอบทั้งหมด
        return $next($request);
    }
}
