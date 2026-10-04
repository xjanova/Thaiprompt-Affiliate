<?php

namespace App\Http\Middleware;

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
 *   2. แอปส่ง header X-App-Build ≥ config('ekyc.min_build') (44) — แอปรุ่นเก่า/เว็บ = ปล่อยผ่าน (ใช้ KYC แบบเดิม)
 *   3. (ถ้าระบุพารามิเตอร์) ค่าใน request ตรงเงื่อนไข เช่น `ekyc.verified:availability=online`
 *      = บังคับเฉพาะตอนเปิดรับงาน — การปิดรับงาน (offline) ต้องทำได้เสมอ
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
            'message' => self::MESSAGE,
            'data' => ['kyc_status' => EkycService::publicStatus($user)],
        ], 422);
    }

    /**
     * request นี้ต้องถูกบังคับไหม (เปิดฟีเจอร์ + แอปรุ่นที่มีหน้า eKYC)
     */
    public static function enforcedFor(Request $request): bool
    {
        if (! filter_var(config('ekyc.enforce', true), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        return EnsureProfilePhoto::appBuild($request) >= (int) config('ekyc.min_build', 44);
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
