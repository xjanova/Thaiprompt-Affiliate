<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OAuth2 user profile endpoint (/api/user) — สำหรับ SSO client (juntraweb)
 *
 * 🔐 (2026-07-17) auth ผ่าน guard 'api-oauth' (Passport) + scope read,profile,email
 *   คืน JSON แบบ FLAT (ไม่ห่อ {success,data}) เพราะ juntraweb ThaipromptClient::
 *   fetchUser() อ่านฟิลด์ตรงๆ จาก /api/user (จะ unwrap {data} เฉพาะตอน fallback
 *   ไป /api/v1/me) — ดู D:\Code\juntraweb\app\Services\ThaipromptClient.php
 *
 * derivation ของ facebook_user_id/signup_via ตรงกับ Api\V1\AuthController@me เป๊ะ
 * เพื่อให้พฤติกรรมเหมือนกันไม่ว่า juntra จะเข้าทาง /api/user หรือ /api/v1/me
 */
class OAuthProfileController extends Controller
{
    /**
     * คืนโปรไฟล์ผู้ใช้ที่ authorize ผ่าน OAuth token
     */
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\OAuthUser $user */
        $user = $request->user();

        // PSID ของลูกค้าคนนี้ = ตัวที่บอก juntra ว่า "ผูก Facebook แล้ว"
        //
        // 🎁 (2026-07-28) ยึดคอลัมน์ `users.facebook_psid` เป็นอันดับแรก
        //    เดิมดูจาก fortune_readings อย่างเดียว → ลูกค้าที่กด Magic Link เข้าเว็บ
        //    **โดยยังไม่เคยดูดวง** (ซึ่งคือกลุ่มหลักของเส้น FB→ห้องแชท) ไม่มีแถว reading
        //    → juntra ได้ facebook_user_id = null → ถือว่า "ยังไม่เชื่อม Facebook"
        //    → พอห้องแชทเข้าโหมดคิดเงิน ลูกค้ากลุ่มนี้โดน 403 ทั้งที่มาจาก FB แท้ ๆ
        //    (คอลัมน์นี้ถูกเติมตอนบัญชีถูกสร้าง + backfill 648 คนเมื่อ 2026-07-16)
        $fbPsid = $user->facebook_psid ?: null;

        if (! $fbPsid) {
            $fbPsid = FortuneReading::query()
                ->where('user_id', $user->id)
                ->whereNotNull('facebook_user_id')
                ->orderByDesc('created_at')
                ->value('facebook_user_id');
        }

        $signupVia = $user->line_user_id ? 'line'
                   : ($fbPsid ? 'facebook'
                   : ($user->email ? 'email' : null));

        // gate ข้อมูลตาม scope ที่ token ได้รับ (juntra ขอ read profile email ครบ)
        $canProfile = $user->tokenCan('profile');
        $canEmail = $user->tokenCan('email');

        return response()->json([
            'id' => $user->id,
            'user_id' => $user->id,
            'email' => $canEmail ? $user->email : null,
            'name' => $canProfile ? $user->name : null,
            'username' => $canProfile ? $user->name : null,
            'line_user_id' => $user->line_user_id,
            'facebook_user_id' => $fbPsid,
            'fb_psid' => $fbPsid,
            'signup_via' => $signupVia,
        ]);
    }
}
