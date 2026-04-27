<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookOAuthSetting;
use Illuminate\Http\Request;

/**
 * Admin: Facebook OAuth Settings
 *
 * จัดการ config สำหรับ "Login with Facebook"
 * - App ID + App Secret + Redirect URI
 * - เปิด/ปิด feature + auto-create options
 * - แสดงสถิติ login (last_login_at, total_logins)
 *
 * URL: /admin/auth/facebook-oauth
 */
class FacebookOAuthController extends Controller
{
    /**
     * แสดงหน้า settings
     */
    public function index()
    {
        $settings = FacebookOAuthSetting::first() ?? new FacebookOAuthSetting;

        // ค่า callback URL ที่แนะนำ (auto-build จาก app.url) — admin copy ไปใส่ FB Console
        $suggestedRedirectUri = rtrim(config('app.url'), '/') . '/auth/facebook/callback';

        return view('admin.auth.facebook-oauth.index', [
            'settings' => $settings,
            'suggestedRedirectUri' => $suggestedRedirectUri,
            'callbackRouteUrl' => route('facebook.callback'),
        ]);
    }

    /**
     * บันทึกการตั้งค่า
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_id' => ['nullable', 'string', 'max:100'],
            'app_secret' => ['nullable', 'string', 'max:191'],
            'redirect_uri' => ['nullable', 'string', 'max:500'],
            'graph_version' => ['nullable', 'string', 'max:10'],
            'scopes' => ['nullable', 'string'], // textarea, comma-separated
            'is_enabled' => ['boolean'],
            'auto_create_user' => ['boolean'],
            'auto_create_wallet' => ['boolean'],
            'auto_create_mlm_member' => ['boolean'],
        ]);

        // Boolean fields ต้อง default false เมื่อไม่ได้ส่งมา
        foreach (['is_enabled', 'auto_create_user', 'auto_create_wallet', 'auto_create_mlm_member'] as $field) {
            $validated[$field] = $request->boolean($field);
        }

        // แปลง scopes string → array (รองรับทั้ง comma และ newline-separated)
        if (! empty($validated['scopes'])) {
            $scopes = preg_split('/[\r\n,]+/', $validated['scopes']);
            $scopes = array_values(array_filter(array_map('trim', $scopes)));
            $validated['scopes'] = ! empty($scopes) ? $scopes : null;
        } else {
            $validated['scopes'] = null;
        }

        // ห้ามทับ app_secret ด้วยค่าว่าง (admin ไม่ได้กรอกใหม่ → คงค่าเดิม)
        if (empty($validated['app_secret'])) {
            unset($validated['app_secret']);
        }

        $settings = FacebookOAuthSetting::first();

        if ($settings) {
            $settings->update($validated);
        } else {
            $settings = FacebookOAuthSetting::create($validated);
        }

        FacebookOAuthSetting::clearCache();

        return redirect()->route('admin.auth.facebook-oauth.index')
            ->with('success', 'บันทึกการตั้งค่า Facebook Login เรียบร้อยแล้ว');
    }

    /**
     * ทดสอบ config — ลองสร้าง Socialite redirect URL ดูว่าทำงานไหม
     *
     * Returns JSON เพื่อแสดงใน UI
     */
    public function test(Request $request)
    {
        $settings = FacebookOAuthSetting::getActive();

        if (! $settings) {
            return response()->json([
                'success' => false,
                'message' => 'ยังไม่มี config ในระบบ — กรุณากรอก App ID + App Secret',
            ], 400);
        }

        if (! $settings->isReady()) {
            return response()->json([
                'success' => false,
                'message' => 'Config ยังไม่ครบ — ตรวจ App ID, App Secret และ "เปิดใช้งาน"',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Config พร้อมใช้งาน — ทดสอบ login ได้ที่หน้า /login',
            'redirect_uri' => $settings->getRedirectUri(),
            'scopes' => $settings->getScopes(),
            'graph_version' => $settings->graph_version,
        ]);
    }
}
