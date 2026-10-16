<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AppDownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * เส้นเช็คอัปเดตของแอป Flutter รุ่นเก่า (app.thaiprompt.thaipromptapp ≤ 1.0.24)
 *
 * GET /api/v1/app/latest-version?platform=android
 *
 * 🚫 กฎ owner: ลูกค้าห้ามเห็น GitHub — อัปเดตต้องผ่านระบบของเว็บเท่านั้น (2026-10-16)
 * เดิมอ่านตาราง app_releases ซึ่งเก็บลิงก์ GitHub Releases → แอปเก่าโหลด APK จาก GitHub ตรงๆ
 * ตอนนี้ตอบจาก APK ที่ปล่อยบนเว็บ (app:publish-apk · ตัวเดียวกับ /api/v1/app/update และปุ่ม /app/download)
 * แอปเก่ากดอัปเดต = ได้แอป Thai Prompt APP ตัวปัจจุบันจากเซิร์ฟเวอร์เรา
 *
 * ยังไม่มีไฟล์บนเว็บ / ปิดสวิตช์หลังบ้าน / iOS = 404 (แอปเก่าถือว่าไม่มีอัปเดต) — ไม่ถอยไปใช้ลิงก์ในตาราง
 *
 * @example GET /api/v1/app/latest-version?platform=android
 * // {"data":{"latest_version":"3.391.0","latest_build":48,"apk_url":"https://main.thaiprompt.online/storage/app-downloads/ThaiPrompt-APP-3.391.0.apk?h=…",...}}
 */
class AppReleaseApiController extends Controller
{
    /**
     * เวอร์ชันล่าสุดสำหรับแอปเก่า — ไม่บังคับอัปเดต (min_supported_version ต่ำกว่าแอปเก่าทุกตัว)
     */
    public function latest(Request $request, AppDownloadService $downloads): JsonResponse
    {
        $platform = strtolower(trim((string) $request->query('platform', $request->header('X-Device-Platform', 'android'))));
        $apk = $platform === 'android' ? $downloads->available() : null;

        if ($apk === null || $apk['version'] === '') {
            return response()->json([
                'data' => null,
                'message' => 'ยังไม่มีการเผยแพร่สำหรับแพลตฟอร์มนี้',
            ], 404)->header('Cache-Control', 'no-store');
        }

        $notes = array_map(fn (string $line) => '- '.$line, $apk['notes']);

        return response()->json([
            'data' => [
                'latest_version' => $apk['version'],
                'latest_build' => $apk['version_code'],
                'min_supported_version' => '1.0.0',
                'release_notes_md' => trim("## Thai Prompt APP {$apk['version']}\n\n".implode("\n", $notes)),
                'apk_url' => $downloads->fileUrl($apk),
                'apk_size_bytes' => $apk['size'] > 0 ? $apk['size'] : null,
                'apk_sha256' => $apk['sha256'] !== '' ? $apk['sha256'] : null,
                'play_store_url' => $downloads->playStoreUrl(),
                'published_at' => $apk['published_at'] !== '' ? $apk['published_at'] : null,
            ],
        ])->header('Cache-Control', 'no-store');
    }
}
