<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleOAuthSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * หลังบ้าน: ตั้งค่า "เข้าสู่ระบบด้วย Google"
 *
 * แอดมินสร้าง OAuth client (ชนิด Web application) ใน Google Cloud
 * แล้วเอา Client ID / Client secret มาวางที่นี่ — ใช้ได้ทั้งเว็บและแอป (แอปวิ่งผ่านเว็บ)
 *
 * URL: /admin/auth/google-oauth
 */
class GoogleOAuthController extends Controller
{
    /**
     * แสดงหน้าตั้งค่า
     */
    public function index(): View
    {
        $settings = GoogleOAuthSetting::first() ?? new GoogleOAuthSetting;

        return view('admin.auth.google-oauth.index', [
            'settings' => $settings,
            'redirectUri' => GoogleOAuthSetting::redirectUri(),
            'hasSecret' => $this->hasSecret($settings),
            'isReady' => $settings->exists && $settings->isReady(),
        ]);
    }

    /**
     * บันทึกการตั้งค่า
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9._\-]+$/'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'client_id.regex' => 'Client ID มีอักขระที่ไม่ถูกต้อง — คัดลอกจาก Google Cloud มาทั้งบรรทัด',
            'client_id.max' => 'Client ID ยาวเกินไป',
            'client_secret.max' => 'Client secret ยาวเกินไป',
        ]);

        $data = [
            'client_id' => isset($validated['client_id']) ? trim($validated['client_id']) : null,
            'is_active' => $request->boolean('is_active'),
        ];

        // ช่อง secret ว่าง = คงค่าเดิม (หน้าเว็บไม่เคยส่ง secret เดิมกลับมาให้เห็น)
        $secret = trim((string) ($validated['client_secret'] ?? ''));
        if ($secret !== '') {
            $data['client_secret'] = $secret;
        }

        $settings = GoogleOAuthSetting::first() ?? new GoogleOAuthSetting;
        $settings->fill($data);

        // เปิดใช้โดยที่ข้อมูลยังไม่ครบ → ไม่เปิดให้ (ปุ่มจะพังถ้าเปิด)
        if ($settings->is_active && ! $settings->isReady()) {
            return back()
                ->withInput($request->except('client_secret'))
                ->withErrors(['is_active' => 'ต้องกรอก Client ID และ Client secret ให้ครบก่อนเปิดใช้งาน']);
        }

        $settings->save();
        GoogleOAuthSetting::clearCache();

        return redirect()->route('admin.auth.google-oauth.index')
            ->with('success', $settings->is_active
                ? 'บันทึกแล้ว — ปุ่ม "เข้าสู่ระบบด้วย Google" แสดงบนเว็บและในแอปแล้ว'
                : 'บันทึกแล้ว — ยังปิดอยู่ ปุ่ม Google จะยังไม่แสดง');
    }

    /**
     * ตรวจว่าตั้งค่าครบพร้อมใช้หรือยัง (ไม่ยิงไป Google — แค่เช็คข้อมูลในระบบ)
     */
    public function test(): JsonResponse
    {
        $settings = GoogleOAuthSetting::getActive();

        if (! $settings || trim((string) $settings->client_id) === '' || ! $this->hasSecret($settings)) {
            return response()->json([
                'success' => false,
                'message' => 'ยังกรอกไม่ครบ — ต้องมี Client ID และ Client secret',
            ], 400);
        }

        if (! $settings->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลครบแล้ว แต่ยังไม่ได้เปิดใช้งาน',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'พร้อมใช้งาน — ลองกดปุ่ม "เข้าสู่ระบบด้วย Google" ที่หน้า /login ในหน้าต่างไม่ระบุตัวตน',
            'redirect_uri' => GoogleOAuthSetting::redirectUri(),
        ]);
    }

    /**
     * มี secret บันทึกไว้แล้วหรือยัง (ถอดรหัสไม่ได้ = ถือว่าไม่มี ต้องกรอกใหม่)
     */
    private function hasSecret(GoogleOAuthSetting $settings): bool
    {
        try {
            return trim((string) $settings->client_secret) !== '';
        } catch (\Throwable) {
            return false;
        }
    }
}
