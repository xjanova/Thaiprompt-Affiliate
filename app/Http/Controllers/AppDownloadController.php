<?php

namespace App\Http\Controllers;

use App\Services\AppDownloadService;
use Illuminate\Http\RedirectResponse;

/**
 * ปุ่ม "ดาวน์โหลดแอป" บนเว็บ — ส่งต่อไปไฟล์ APK บนเซิร์ฟเวอร์เรา (ไฟล์นิ่ง ไม่สตรีมผ่าน PHP)
 *
 * ลำดับ: APK บนเซิร์ฟเวอร์ → ลิงก์ APK ที่หลังบ้านตั้งไว้ (https) → กลับหน้าแรกพร้อมแจ้งว่าใกล้เปิดแล้ว
 */
class AppDownloadController extends Controller
{
    /**
     * GET /app/download
     */
    public function android(AppDownloadService $downloads): RedirectResponse
    {
        $apk = $downloads->available();
        if ($apk) {
            return redirect()->away($downloads->fileUrl($apk));
        }

        $external = $downloads->externalUrl();
        if ($external) {
            return redirect()->away($external);
        }

        return redirect()->to(url('/').'#nv-app')
            ->with('info', 'แอป Thai Prompt APP กำลังจะเปิดให้ดาวน์โหลดเร็วๆ นี้');
    }
}
