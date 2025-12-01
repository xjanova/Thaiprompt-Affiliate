<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware บังคับให้ผู้ใช้เชื่อมต่อ LINE ก่อนเข้าใช้งาน Provider Dashboard
 *
 * ตรวจสอบว่าผู้ใช้มี line_user_id หรือไม่
 * ถ้าไม่มีจะ redirect ไปหน้าบังคับเชื่อมต่อ LINE
 */
class EnsureProviderLineConnected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // ตรวจสอบว่า user มี line_user_id หรือไม่
        if (!$user || !$user->line_user_id) {
            // ถ้า request เป็น AJAX ให้ return JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาเชื่อมต่อบัญชี LINE ก่อนใช้งาน',
                    'redirect' => route('provider.line-required'),
                ], 403);
            }

            // Redirect ไปหน้าบังคับเชื่อมต่อ LINE
            return redirect()->route('provider.line-required')
                ->with('warning', 'กรุณาเชื่อมต่อบัญชี LINE ก่อนสมัครเป็นผู้ให้บริการ');
        }

        return $next($request);
    }
}
