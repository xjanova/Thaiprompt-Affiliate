<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * DetectIframeMode Middleware
 *
 * ตรวจจับว่าเป็นการเรียกจาก iframe หรือไม่
 * และตั้งค่า view variable สำหรับเลือก layout
 *
 * การใช้งาน:
 * - ถ้ามี ?iframe=1 ใน query string = โหลดแบบ content-only (ไม่มี sidebar/navbar)
 * - ถ้าไม่มี = โหลดแบบปกติ (มี sidebar/navbar)
 *
 * @version 1.0.0
 * @since 2026-01-19
 */
class DetectIframeMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ตรวจสอบว่ามี ?iframe=1 หรือไม่
        $isIframeMode = $request->query('iframe') === '1';

        // ตั้งค่า view variable สำหรับทุก view
        View::share('isIframeMode', $isIframeMode);

        // เพิ่ม attribute ใน request เพื่อให้ controller เข้าถึงได้
        $request->attributes->set('iframe_mode', $isIframeMode);

        // เพิ่ม header เพื่อระบุว่าเป็น iframe mode
        $response = $next($request);

        if ($isIframeMode && $response instanceof Response) {
            // ป้องกัน clickjacking (แต่อนุญาตให้ same-origin)
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
        }

        return $response;
    }
}

