<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ส่วนดูดวง/ไพ่ทาโรต์บนเว็บ (/tarot/* และ /horoscope/*) ย้ายไปเว็บแม่หมอจันทรา (จันทรา.online)
 *
 * เจ้าของสั่ง 2026-09-27: "ส่วนของดูดวงไพ่ทาโร่ทั้งหมด ลิงก์ไปเว็บจันทรา.online"
 * - ใส่เป็น middleware ของกลุ่ม route แทนการลบ route → ชื่อ route เดิม (route('tarot.index') ฯลฯ)
 *   ที่ถูกอ้างในเมนู/หลังบ้านยังสร้างลิงก์ได้ ไม่เกิด RouteNotFoundException
 * - ปิดได้ด้วย JUNTRA_REDIRECT_FORTUNE=false (config services.juntra.redirect_fortune) → หน้าเดิมกลับมาทำงาน
 * - API ของจันทรา (/api/...) ไม่ได้อยู่ในกลุ่มนี้ ไม่โดน
 */
class RedirectFortuneToJuntra
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('services.juntra.redirect_fortune', true)) {
            return $next($request);
        }

        $juntra = rtrim((string) config('services.juntra.url', 'https://xn--82c4af5bzdj.online'), '/');

        return redirect()->away($juntra, 302);
    }
}
