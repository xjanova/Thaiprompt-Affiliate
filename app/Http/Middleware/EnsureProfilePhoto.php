<?php

namespace App\Http\Middleware;

use App\Services\Media\ProfilePhotoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ด่านรูปโปรไฟล์ถ่ายสด (alias `profile.photo`) — ไรเดอร์รอบ 2, 2026-10-04
 *
 * ผู้ใช้ที่ยังไม่มีรูปถ่ายสด → 422 PROFILE_PHOTO_REQUIRED (รูปแบบคำตอบเดียวกับ SellerAppResponses)
 *
 * บังคับเฉพาะเมื่อ:
 *   1. config('profile_photo.required') = true
 *   2. แอปส่ง header X-App-Build ≥ config('profile_photo.min_build') (43)
 *      แอปรุ่นเก่า/ไม่ส่ง header/หน้าเว็บ = ปล่อยผ่าน (ยังไม่มีหน้าถ่ายรูป ห้ามล็อกผู้ใช้ออกจากระบบ)
 *   3. (ถ้าระบุพารามิเตอร์) ค่าใน request ตรงเงื่อนไข เช่น `profile.photo:availability=online`
 *      = บังคับเฉพาะตอนเปิดรับงาน — การปิดรับงาน (offline) ต้องทำได้เสมอ
 *
 * @example Route::post('/cart/checkout', ...)->middleware('profile.photo');
 * @example Route::post('/rider/availability', ...)->middleware('profile.photo:availability=online');
 */
class EnsureProfilePhoto
{
    public const CODE = 'PROFILE_PHOTO_REQUIRED';

    public const MESSAGE = 'กรุณาถ่ายรูปโปรไฟล์ก่อนใช้งานส่วนนี้';

    public function __construct(private readonly ProfilePhotoService $photos) {}

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

        // ยังไม่ล็อกอิน → ปล่อยให้ auth middleware ตัดสินเอง (ไม่ใช่หน้าที่ของด่านนี้)
        if (! $user || $this->photos->hasPhoto($user)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'code' => self::CODE,
            'message' => self::MESSAGE,
            'data' => ['required' => true, 'has_photo' => false],
        ], 422);
    }

    /**
     * request นี้ต้องถูกบังคับไหม (เปิดฟีเจอร์ + แอปรุ่นที่รองรับ)
     *
     * ใช้ร่วมกับตัวควบคุมที่ต้องเช็คเองได้ (เช่นเส้นที่เงื่อนไขซับซ้อนกว่า field=value)
     */
    public static function enforcedFor(Request $request): bool
    {
        if (! filter_var(config('profile_photo.required', false), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        $build = self::appBuild($request);

        // แอปตั้งแต่ build ของ eKYC (44) ไม่มีหน้าถ่ายรูปสดแล้ว (อวาตาร์ = รูปอะไรก็ได้ + ยืนยันตัวตนด้วย eKYC แทน)
        // บังคับต่อ = ผู้ใช้ติดด่านที่ผ่านไม่ได้ ⇒ ด่านนี้ใช้กับ build 43 เท่านั้น
        if ($build >= (int) config('ekyc.min_build', 44)) {
            return false;
        }

        return $build >= (int) config('profile_photo.min_build', 43);
    }

    /**
     * เลข build ของแอปจาก header X-App-Build — ไม่ส่ง/ไม่ใช่ตัวเลข = 0 (ถือเป็นรุ่นเก่า)
     */
    public static function appBuild(Request $request): int
    {
        $raw = trim((string) $request->header('X-App-Build', ''));

        // รับเฉพาะตัวเลขล้วน ยาวไม่เกิน 9 หลัก (กันค่าแปลกๆ เช่น "43abc" / เลขล้น int)
        if ($raw === '' || ! preg_match('/^\d{1,9}$/', $raw)) {
            return 0;
        }

        return (int) $raw;
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
