<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckDeveloperApproved Middleware
 *
 * ตรวจสอบว่า user เป็น developer ที่ได้รับอนุมัติแล้ว
 */
class CheckDeveloperApproved
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

        // ตรวจสอบว่ามี developer profile
        $developerProfile = $user->developerProfile;

        if (!$developerProfile) {
            return redirect()->route('developer.register')
                ->with('info', 'กรุณาลงทะเบียนเป็นนักพัฒนาก่อนเข้าถึงหน้านี้');
        }

        // ตรวจสอบสถานะการอนุมัติ
        if (!$developerProfile->isApproved()) {
            // ถ้าเป็น pending ให้ redirect ไปหน้า pending
            if ($developerProfile->status === 'pending') {
                return redirect()->route('developer.pending')
                    ->with('info', 'กรุณารอการอนุมัติจากแอดมิน');
            }

            // ถ้าเป็น rejected หรือ suspended
            $statusMessage = match($developerProfile->status) {
                'rejected' => 'บัญชีนักพัฒนาของคุณถูกปฏิเสธ กรุณาติดต่อแอดมิน',
                'suspended' => 'บัญชีนักพัฒนาของคุณถูกระงับชั่วคราว กรุณาติดต่อแอดมิน',
                default => 'บัญชีนักพัฒนาของคุณไม่สามารถใช้งานได้ กรุณาติดต่อแอดมิน',
            };

            return redirect()->route('home')
                ->with('error', $statusMessage);
        }

        // ผ่านการตรวจสอบทั้งหมด
        return $next($request);
    }
}
