<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\SellerAppResponses;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureProfilePhoto;
use App\Services\AppDownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 📲 เช็คอัปเดตแอป Thai Prompt APP (2026-10-05) — แอปโหลด APK จากเซิร์ฟเวอร์เราแล้วเปิดตัวติดตั้งเอง
 *
 * GET /api/v1/app/update?platform=android&build=44 (public — ยังไม่ล็อกอินก็เช็คได้)
 *   build = versionCode ที่ติดตั้งในเครื่อง (ไม่ส่ง = ใช้ header X-App-Build)
 *   ไฟล์ล่าสุดมาจาก app:publish-apk (latest.json) · เคารพสวิตช์หลังบ้านเดียวกับปุ่มโหลดบนเว็บ
 *
 * @example GET /api/v1/app/update?platform=android&build=44
 * // {"success":true,"data":{"update_available":true,"required":false,"latest":{"version":"3.388.0",...}}}
 */
class AppUpdateController extends Controller
{
    use SellerAppResponses;

    /**
     * มีเวอร์ชันใหม่กว่าที่ติดตั้งอยู่ไหม + ข้อมูลไฟล์สำหรับโหลด/ตรวจ
     *
     * รับ service ต่อ request (ไม่ผ่าน constructor) — Laravel เก็บ instance ของ controller ไว้กับ route
     * ส่วน AppDownloadService จำ latest.json ไว้ในตัว → ใช้ซ้ำข้าม request จะเห็นไฟล์เก่า
     */
    public function check(Request $request, AppDownloadService $downloads): JsonResponse
    {
        $platform = strtolower(trim((string) $request->query('platform', 'android')));
        if (! in_array($platform, ['android', 'ios'], true)) {
            $platform = 'android';
        }

        $raw = trim((string) $request->query('build', ''));
        $build = $raw !== '' && preg_match('/^\d{1,9}$/', $raw) ? (int) $raw : EnsureProfilePhoto::appBuild($request);

        return $this->ok($downloads->updateInfo($build, $platform))
            // ห้ามแคชระหว่างทาง (Cloudflare/พร็อกซี) — ปล่อยตัวใหม่แล้วต้องเห็นทันที
            ->header('Cache-Control', 'no-store');
    }
}
