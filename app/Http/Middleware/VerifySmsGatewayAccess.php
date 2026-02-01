<?php

namespace App\Http\Middleware;

use App\Models\SmsGatewaySubscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ตรวจสอบสิทธิ์การเข้าถึง SMS Payment Gateway
 *
 * ใช้หลัง VerifySmsCheckerDevice middleware
 * - Admin devices (store_id = null) → ผ่านเสมอ
 * - Seller devices → เช็ค subscription → 403 ถ้าไม่มีสิทธิ์
 */
class VerifySmsGatewayAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $device = $request->attributes->get('sms_checker_device');

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device authentication required',
            ], 401);
        }

        // Admin devices (store_id = null) → ผ่านเสมอ
        if ($device->isAdminDevice()) {
            return $next($request);
        }

        // Seller devices → เช็ค subscription
        if (!SmsGatewaySubscription::storeHasAccess($device->store_id)) {
            return response()->json([
                'success' => false,
                'message' => 'SMS Gateway subscription required. Please subscribe from your store dashboard.',
                'error_code' => 'subscription_required',
            ], 403);
        }

        return $next($request);
    }
}
