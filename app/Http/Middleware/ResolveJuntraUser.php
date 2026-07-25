<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * ResolveJuntraUser
 *
 * 🔧 (2026-07-26) ทำให้ผู้ใช้ที่ล็อกอินมาด้วย guard 'api-oauth' (Passport)
 *    กลายเป็น App\Models\User ตัวจริงก่อนเข้าคอนโทรลเลอร์ของกลุ่ม /api/v1/juntra/*
 *
 * ทำไมต้องมี:
 *   - SSO ของเว็บจันทรา (juntraweb) แลก token ผ่าน Passport → เก็บเป็น
 *     `users.thaiprompt_token` แล้วส่งเป็น Bearer มาที่ /api/v1/juntra/*
 *   - แต่กลุ่มนั้นเดิมยืนบน `auth:sanctum` อย่างเดียว ซึ่งหา token ในตาราง
 *     personal_access_tokens เท่านั้น → Passport JWT ไม่มีทางผ่าน
 *     (ยืนยันบน prod 2026-07-26: GET /api/v1/juntra/fortune/credits = 401
 *      แต่ GET /api/user = 200 ด้วย token ตัวเดียวกัน)
 *   - ผลคือเว็บจันทราถูกตัดขาดจากคลังความรู้ + คีย์พูลของบอทมาตลอด
 *     แล้วตกไปใช้ Gemini ในเครื่องที่ไม่มีคีย์บน prod = คำทำนายไม่ใช่ของจริง
 *
 * วิธีแก้: เปิดกลุ่มให้รับทั้ง `auth:sanctum,api-oauth` แล้วใช้ middleware นี้
 *   normalize ผู้ใช้ให้เป็น App\Models\User เสมอ — คอนโทรลเลอร์เดิมทุกตัวเรียก
 *   `$request->user()` แล้วใช้ relation/เมธอดของ User (wallet, fortuneReadings ฯลฯ)
 *   ซึ่ง App\Models\OAuthUser (extends Authenticatable ตรงๆ) ไม่มีให้
 *
 * ⚠️ ห้ามแก้ให้ OAuthUser extends User — จะ compile-fatal เพราะ createToken()
 *    ของ Sanctum กับ Passport signature ไม่ตรงกัน (ดูคอมเมนต์ใน OAuthUser)
 */
class ResolveJuntraUser
{
    /**
     * client ของ Passport ที่ยอมให้เข้ากลุ่ม /api/v1/juntra/* ได้
     *
     * 🔐 ต้องผูกกับ provider 'oauth_users' เท่านั้น (เว็บจันทราถูก provision
     *    ด้วย ProvisionJuntraOAuthClient ซึ่งตั้งค่านี้ไว้ชัด)
     */
    private const REQUIRED_CLIENT_PROVIDER = 'oauth_users';

    /**
     * แปลงผู้ใช้จาก guard ใดก็ตามให้เป็น App\Models\User ตัวจริง
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authenticated = $request->user();

        // ไม่ได้ล็อกอิน (route สาธารณะ) หรือเป็น User อยู่แล้ว → ปล่อยผ่าน
        if ($authenticated === null || $authenticated instanceof User) {
            return $next($request);
        }

        // 🔐 (2026-07-26 จับผี CRITICAL) มาจาก Passport → ต้องเป็น token ของ
        //    client ที่ผูก provider 'oauth_users' เท่านั้น
        //
        //    ทำไมต้องเช็ค: Passport เปิดเส้น POST /oauth/clients ให้ผู้ใช้เว็บ
        //    ที่ล็อกอินคนไหนก็สร้าง client ของตัวเองได้ และ client ที่สร้างทางนั้น
        //    มี provider = null ซึ่ง TokenGuard ของ Passport "ข้ามการเช็ค provider"
        //    ให้เลย (เงื่อนไข $client->provider && ... → null = falsy)
        //    → ถ้าไม่กันตรงนี้ ใครก็ล่อให้ลูกค้าที่ล็อกอินค้างอยู่กดหน้า consent
        //    ที่เขียนว่า "อ่านโปรไฟล์" แล้วได้ token ที่ยิง /mlm/claim-referral
        //    (ผูกสายงานถาวร แก้กลับไม่ได้) + ดึงประวัติดูดวงทั้งหมดของเหยื่อได้
        //
        //    scope ช่วยไม่ได้: ระบบประกาศไว้แค่ read/profile/email แล้วตั้งเป็น
        //    default scope ทั้งหมด — token ทุกใบจึงมีครบอยู่แล้ว
        $clientProvider = null;
        try {
            if (method_exists($authenticated, 'token')) {
                $clientProvider = $authenticated->token()?->client?->provider;
            }
        } catch (\Throwable $e) {
            $clientProvider = null;
        }

        if ($clientProvider !== self::REQUIRED_CLIENT_PROVIDER) {
            Log::warning('ResolveJuntraUser: ปฏิเสธ token ที่ไม่ได้มาจาก client ของเว็บจันทรา', [
                'auth_id' => $authenticated->getAuthIdentifier(),
                'client_provider' => $clientProvider,
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'token นี้ไม่มีสิทธิ์เข้าถึงบริการนี้'], 403);
        }

        $realUser = User::find($authenticated->getAuthIdentifier());

        if ($realUser === null) {
            // token ใช้ได้แต่แถวผู้ใช้หายไปแล้ว (ถูกลบ/ถูก anonymize ตาม PDPA)
            Log::warning('ResolveJuntraUser: token ใช้ได้แต่ไม่พบผู้ใช้ในตาราง users', [
                'auth_id' => $authenticated->getAuthIdentifier(),
                'path' => $request->path(),
            ]);

            return response()->json(['message' => 'ไม่พบบัญชีผู้ใช้'], 401);
        }

        $request->setUserResolver(fn () => $realUser);

        return $next($request);
    }
}
