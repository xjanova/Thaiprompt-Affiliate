<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;

class VerifyCsrfToken extends Middleware
{
    /**
     * URIs ที่ไม่ต้องตรวจสอบ CSRF token
     *
     * @var array<int, string>
     */
    protected $except = [
        'webhook/line',
        'webhook/paysolutions',
        'webhook/promptpay',
        'webhook/stripe',
        'webhook/omise',
        'api/webhook/line',
        'api/webhook/paysolutions',
        'api/webhook/promptpay',
        'api/webhook/stripe',
        'api/webhook/omise',
        'api/webhook/github/*',
        'api/games/snake-io/*',
    ];

    /**
     * จัดการ CSRF token ไม่ตรง
     *
     * แทนที่จะแสดงหน้า 419 ทื่อๆ → redirect กลับหน้าเดิมพร้อม error message
     * ช่วยแก้ปัญหาบนมือถือที่ session หมดอายุขณะกรอกฟอร์ม
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, \Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $e) {
            // สำหรับ API requests → return JSON error
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Session หมดอายุ กรุณาลองใหม่อีกครั้ง',
                    'error' => 'csrf_token_mismatch',
                ], 419);
            }

            // สำหรับ web requests → regenerate session + redirect กลับพร้อม error
            // สร้าง CSRF token ใหม่ให้โดยอัตโนมัติ
            $request->session()->regenerateToken();

            return redirect()->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation', 'cf-turnstile-response'))
                ->withErrors([
                    'csrf' => 'Session หมดอายุ กรุณากดส่งข้อมูลอีกครั้ง',
                ]);
        }
    }
}
