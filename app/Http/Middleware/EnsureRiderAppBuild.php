<?php

namespace App\Http\Middleware;

use App\Support\Rider\ClientAppBuild;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ด่าน "รับงาน/ทำงานส่งต้องใช้แอปรุ่นล่าสุด" ของ API ไรเดอร์ (2026-10-13)
 *
 * owner ตัดสิน: ไรเดอร์รับงานผ่านแอปเท่านั้น — เส้นรับงาน / คืนงาน / อัปเดตสถานะ / ส่งของ / ส่งไม่สำเร็จ /
 * แจ้ง GPS / สแกนส่งมอบ / เปิดรับงาน ต้องมาจากแอปที่ส่ง header X-App-Build ≥ config('rider.min_app_build') (44)
 * ไม่ส่ง header / แอปรุ่นเก่า / ค่าแปลก → 426 APP_UPDATE_REQUIRED (ให้แอปพาไปหน้าอัปเดต)
 *
 * ทำไมต้องมี: เดิมงานที่รับจากหน้าเว็บ/LINE/แอปเก่าไม่ต้องสแกนส่งมอบ → ปุ่มส่งของแบบเดิม (รูปอะไรก็ได้ ไม่เช็คพิกัด
 * ไรเดอร์ได้เงินทันที ผู้ซื้อร้องเรียนไม่ได้) — ตอนนี้งานทุกงานต้องส่งมอบ จึงต้องบังคับแอปที่มีหน้าส่งมอบ
 *
 * พารามิเตอร์ "field=value" (ไม่บังคับ) = บังคับเฉพาะเมื่อ input ตรงค่านี้
 *   เช่น `EnsureRiderAppBuild::class.':availability=online'` → ปิดรับงาน (offline) ทำได้เสมอ แม้แอปเก่า
 *
 * @example Route::post('/rider/jobs/{id}/accept', ...)->middleware(EnsureRiderAppBuild::class);
 * @example Route::post('/rider/availability', ...)->middleware(EnsureRiderAppBuild::class.':availability=online');
 */
class EnsureRiderAppBuild
{
    public const CODE = 'APP_UPDATE_REQUIRED';

    public const MESSAGE = 'กรุณาอัปเดตแอป Thai Prompt APP เป็นรุ่นล่าสุดก่อนรับงาน';

    /**
     * @param  string|null  $condition  รูปแบบ "field=value" — บังคับเฉพาะเมื่อ input ตรงค่านี้
     */
    public function handle(Request $request, Closure $next, ?string $condition = null): Response
    {
        if ($condition !== null && $condition !== '' && ! $this->conditionMatches($request, $condition)) {
            return $next($request);
        }

        if (self::supported($request)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'code' => self::CODE,
            'message' => self::MESSAGE,
            'data' => [
                'min_build' => self::minBuild(),
                'download_url' => url((string) config('rider.app_download_path', '/app/download')),
            ],
        ], 426);
    }

    /**
     * build ต่ำสุดที่รับงานได้ (ตั้งผิดเป็น 0/ติดลบ = ใช้ 1 — ไม่ส่ง header ก็ยังไม่ผ่าน)
     */
    public static function minBuild(): int
    {
        return max(1, (int) config('rider.min_app_build', 44));
    }

    /**
     * คำขอนี้มาจากแอปรุ่นที่รับงานได้หรือไม่ (อ่าน header ผ่าน ClientAppBuild — ไม่ส่ง/ค่าแปลก = null = ไม่ผ่าน)
     */
    public static function supported(Request $request): bool
    {
        $build = ClientAppBuild::fromRequest($request);

        return $build !== null && $build >= self::minBuild();
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
