<?php

namespace App\Http\Middleware;

use App\Services\Ekyc\EkycGate;
use App\Services\Ekyc\EkycService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ด่านยืนยันตัวตน AI eKYC สำหรับแอป (alias `ekyc.verified`) — 2026-10-04
 *
 * ผู้ใช้ที่ยังไม่ยืนยันตัวตน → 422 KYC_REQUIRED (รูปแบบเดียวกับ SellerAppResponses)
 * แอปเปิดหน้ายืนยันตัวตนแล้วพากลับมาทำรายการเดิม
 *
 * บังคับเฉพาะเมื่อ:
 *   1. config('ekyc.enforce') = true
 *   2. (ถ้าระบุพารามิเตอร์) ค่าใน request ตรงเงื่อนไข เช่น `ekyc.verified:availability=online`
 *      = บังคับเฉพาะตอนเปิดรับงาน — การปิดรับงาน (offline) ต้องทำได้เสมอ
 *
 * 🔒 (2026-10-13) ตัดสินฝั่งเซิร์ฟเวอร์ ไม่ขึ้นกับ header — เดิมบังคับเฉพาะ X-App-Build ≥ 44
 *    ทำให้เว็บ/LINE/แอปเก่า/curl ที่ไม่ส่ง header สั่งซื้อได้โดยไม่ยืนยันตัวตน (เจ้าของสั่ง: บังคับทุกช่องทาง)
 *    แอปรุ่นเก่ากว่า ekyc.min_build (ยังไม่มีหน้า eKYC) → ข้อความให้อัปเดตแอปก่อน (รูปแบบ JSON เดิม)
 *    ด่านเดียวกันในเว็บ/LINE ใช้ App\Services\Ekyc\EkycGate
 *
 * ⚠️ alias `kyc.verified` เป็นของหน้าเว็บผู้ขาย (EnsureKycVerified — redirect ไป onboarding) ห้ามสลับกัน
 *
 * @example Route::post('/cart/checkout', ...)->middleware('ekyc.verified');
 * @example Route::post('/rider/availability', ...)->middleware('ekyc.verified:availability=online');
 */
class EnsureEkycVerified
{
    public const CODE = 'KYC_REQUIRED';

    public const MESSAGE = 'กรุณายืนยันตัวตนก่อนใช้งานส่วนนี้ ใช้เวลาประมาณ 1 นาที';

    /**
     * @param  string|null  $condition  รูปแบบ "field=value" — บังคับเฉพาะเมื่อ input ตรงค่านี้
     */
    public function handle(Request $request, Closure $next, ?string $condition = null): Response
    {
        if (! self::enforcedFor($request)) {
            return $next($request);
        }

        if ($condition !== null && $condition !== '' && ! $this->conditionMatches($request, $condition)) {
            return $next($request);
        }

        $user = $request->user();

        // ยังไม่ล็อกอิน → ให้ auth middleware ตัดสินเอง
        if (! $user || $user->isKycVerified()) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'code' => self::CODE,
            'message' => self::messageFor($request),
            'data' => ['kyc_status' => EkycService::publicStatus($user)],
        ], 422);
    }

    /**
     * request นี้ต้องถูกบังคับไหม — ทุกช่องทาง (ไม่ดู header X-App-Build แล้ว)
     */
    public static function enforcedFor(Request $request): bool
    {
        return EkycGate::enforced();
    }

    /**
     * ข้อความตอบกลับ: แอปรุ่นเก่า (ส่ง X-App-Build แต่ต่ำกว่า ekyc.min_build) ยังไม่มีหน้ายืนยันตัวตน → ให้อัปเดตแอปก่อน
     */
    public static function messageFor(Request $request): string
    {
        $build = EnsureProfilePhoto::appBuild($request);

        if ($build > 0 && $build < (int) config('ekyc.min_build', 44)) {
            // เส้นสั่งซื้อใช้ข้อความที่เจ้าของกำหนด · เส้นอื่น (สมัครไรเดอร์/เปิดรับงาน/สมัครผู้ขาย) ใช้คำกลาง
            return $request->routeIs('*cart.checkout', '*orders.store')
                ? EkycGate::UPDATE_APP_MESSAGE
                : 'กรุณาอัปเดตแอปเป็นรุ่นล่าสุด แล้วยืนยันตัวตนก่อนใช้งานส่วนนี้';
        }

        return self::MESSAGE;
    }

    /**
     * เงื่อนไข "field=value" ตรงกับค่าใน request ไหม (เทียบแบบ string ตรงตัว)
     */
    private function conditionMatches(Request $request, string $condition): bool
    {
        [$field, $expected] = array_pad(explode('=', $condition, 2), 2, null);

        if ($field === null || $field === '' || $expected === null) {
            // เขียนพารามิเตอร์ผิดรูป → บังคับไว้ก่อน (ปลอดภัยกว่าเปิดทางให้ผ่าน)
            return true;
        }

        $value = $request->input($field);

        return is_scalar($value) && (string) $value === $expected;
    }
}
