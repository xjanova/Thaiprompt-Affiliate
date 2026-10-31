<?php

namespace App\Http\Middleware;

use App\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    protected TwoFactorService $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $action = 'general'): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Check if 2FA is required for this action
        $amount = $request->input('amount'); // For withdrawal/transfer
        if (! $this->twoFactorService->isRequired($action, $user, $amount)) {
            return $next($request);
        }

        // Check if already verified in current session
        if ($this->twoFactorService->isVerified($action)) {
            return $next($request);
        }

        // 🐛 (2026-10-29) แก้ 3 อย่าง:
        //    1) เดิมเรียก route('two-factor.verify') ซึ่งไม่มีจริง (ชื่อจริง user.two-factor.verify)
        //       → ทุกครั้งที่ต้องยืนยัน 2FA ก่อนโอน/ถอน ลูกค้าเจอ error 500
        //    2) หน้าที่เรียกด้วย fetch (สแกนจ่าย) ได้ redirect เป็น HTML → JS อ่าน JSON ไม่ได้ ค้างที่ "โอนไม่สำเร็จ"
        //    3) ยืนยันเสร็จแล้วพากลับ URL ของ POST → เปิดด้วย GET ไม่ได้ (405) ตอนนี้กลับหน้าฟอร์มเดิม
        $back = $request->isMethod('GET') ? $request->fullUrl() : url()->previous();
        $verifyUrl = route('user.two-factor.verify', ['action' => $action, 'redirect' => $back]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'code' => 'TWO_FACTOR_REQUIRED',
                'message' => 'กรุณายืนยันตัวตนด้วย 2FA ก่อนทำรายการนี้',
                'verify_url' => $verifyUrl,
            ], 403);
        }

        return redirect()->to($verifyUrl)->with('info', 'กรุณายืนยันตัวตนด้วย 2FA เพื่อดำเนินการต่อ');
    }
}
