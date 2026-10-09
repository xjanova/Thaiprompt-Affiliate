<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Models\User;
use App\Services\Ekyc\EkycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 🪪 (2026-10-09) ผล KYC ของลูกค้าสำหรับแอปพันธมิตร (TPIX TRADE) — GET /api/oauth/kyc
 *
 * เจ้าของสั่ง: "ให้ไปยืนยันใน thaiprompt app ถ้าผ่านก็บันทึกว่าผ่านแล้ว ถ้าเคยยืนยันแล้วก็ผ่านเลย
 *   ไม่ต้องยืนยันอีก" — แอปพันธมิตรจึงต้องถามได้ว่าลูกค้าคนนี้ผ่าน KYC ที่ Thaiprompt หรือยัง
 *
 * auth: Passport guard 'api-oauth' + scope `kyc` — ลูกค้าต้องกด "อนุญาต" บนหน้า consent ของเราเอง
 *   (client ของ TPIX ตั้ง skip_authorization=false ต่างจากจันทรา) จึงเป็นการยินยอมของเจ้าของข้อมูลจริง
 *
 * 🔒 PDPA — ส่งเท่าที่จำเป็นต่อการตัดสิน "ผ่านด่านไหม" เท่านั้น:
 *   ✗ เลขบัตร / hash เลขบัตร / ชื่อ / วันเกิด / รูป / คะแนน AI
 *   ✓ รหัสผู้ใช้ (ไว้ผูกบัญชีฝั่งโน้น และกันบัญชี Thaiprompt เดียวไปผูกหลายบัญชี) · สถานะ · วันที่ผ่าน · วิธีที่ใช้
 *
 * ตอบแบบ FLAT เหมือน /api/user (OAuthProfileController) — client อ่านฟิลด์ตรงๆ
 */
class OAuthKycStatusController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\OAuthUser $oauthUser */
        $oauthUser = $request->user();

        // OAuthUser ใช้ตาราง users เดียวกันแต่ไม่มีเมธอด KYC (ห้ามสืบทอด User — ดูหัวไฟล์ OAuthUser)
        // อ่านแถวเต็มผ่าน User เพื่อใช้กติกาเดียวกับแอป (EkycService::publicStatus)
        $user = User::query()->find($oauthUser->getKey());

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // แหล่งความจริงเดียว = users.kyc_status (eKYC อนุมัติ + แอดมินอนุมัติ ตั้งค่าที่นี่ทั้งคู่)
        $status = EkycService::publicStatus($user);
        $verified = $status === 'approved';

        $method = $verified
            ? KycVerification::query()
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->latest('id')
                ->value('method')
            : null;

        return response()->json([
            'sub' => (string) $user->id,
            'kyc_status' => $status,
            'verified' => $verified,
            'verified_at' => $verified ? $user->kyc_verified_at?->toIso8601String() : null,
            'method' => $verified ? ($method ?: 'manual') : null,
        ]);
    }
}
