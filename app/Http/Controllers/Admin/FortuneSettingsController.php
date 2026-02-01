<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Fortune Settings Controller
 *
 * จัดการการตั้งค่าระบบดูดวง
 */
class FortuneSettingsController extends Controller
{
    /**
     * แสดงหน้าตั้งค่า
     */
    public function index()
    {
        $settings = FortuneTellingSetting::getSettings();

        return view('admin.fortune.settings.index', [
            'settings' => $settings,
            'pageTitle' => 'ตั้งค่าระบบดูดวง',
        ]);
    }

    /**
     * บันทึกการตั้งค่า
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // การตั้งค่า Facebook
            'facebook_app_id' => 'nullable|string|max:100',
            'facebook_app_secret' => 'nullable|string|max:255',
            'facebook_page_id' => 'nullable|string|max:100',
            'facebook_page_token' => 'nullable|string',
            'facebook_verify_token' => 'nullable|string|max:100',
            // การตั้งค่า AI
            'ai_provider' => 'required|in:gemini,groq,qwen,openrouter',
            'ai_api_key' => 'nullable|string',
            'ai_model' => 'required|string|max:100',
            'basic_prompt_template' => 'nullable|string',
            'deep_prompt_template' => 'nullable|string',
            'use_global_ai_settings' => 'boolean',
            // การตั้งค่าพื้นฐาน
            'max_free_readings' => 'required|integer|min:0|max:100',
            'reading_price' => 'required|numeric|min:0',
            'is_enabled' => 'boolean',
            'respond_in_comment' => 'boolean',
            'require_registration' => 'boolean',
            // ระบบ Freemium: คำทำนายเชิงลึก
            'enable_deep_reading' => 'boolean',
            'deep_reading_price' => 'nullable|numeric|min:0',
            'allow_try_before_buy' => 'boolean',
            'free_deep_per_day' => 'nullable|integer|min:0|max:10',
            // ระบบสมัครสมาชิก
            'subscription_enabled' => 'boolean',
            'subscription_monthly_price' => 'nullable|numeric|min:0',
            'subscription_yearly_price' => 'nullable|numeric|min:0',
            'subscription_benefits' => 'nullable|string',
            // ข้อความอัตโนมัติ
            'welcome_message' => 'nullable|string',
            'limit_exceeded_message' => 'nullable|string',
            'payment_message' => 'nullable|string',
            'subscription_message' => 'nullable|string',
            'try_before_buy_message' => 'nullable|string',
            // QR Code
            'payment_qr_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            // Comment Engagement
            'comment_engagement_enabled' => 'boolean',
            'comment_engagement_mode' => 'nullable|in:ai,template',
            'comment_reply_template' => 'nullable|string|max:500',
            'comment_dm_template' => 'nullable|string|max:2000',
            'comment_engagement_prompt' => 'nullable|string|max:3000',
        ]);

        $settings = FortuneTellingSetting::getSettings();

        // จัดการ checkbox fields (ถ้าไม่ส่งมา = false)
        $checkboxFields = [
            'is_enabled', 'respond_in_comment', 'require_registration',
            'enable_deep_reading', 'allow_try_before_buy', 'subscription_enabled',
            'use_global_ai_settings', 'comment_engagement_enabled',
        ];
        foreach ($checkboxFields as $field) {
            if (!$request->has($field)) {
                $validated[$field] = false;
            }
        }

        // อัพโหลด QR Code ถ้ามี
        if ($request->hasFile('payment_qr_image')) {
            // ลบรูปเก่า
            if ($settings->payment_qr_image) {
                Storage::disk('public')->delete($settings->payment_qr_image);
            }

            // บันทึกรูปใหม่
            $path = $request->file('payment_qr_image')->store('fortune/qr-codes', 'public');
            $validated['payment_qr_image'] = $path;
        }

        $settings->update($validated);

        return redirect()
            ->route('admin.fortune.settings.index')
            ->with('success', 'บันทึกการตั้งค่าสำเร็จ');
    }

    /**
     * ทดสอบการเชื่อมต่อ AI
     */
    public function testAI(Request $request)
    {
        $settings = FortuneTellingSetting::getSettings();
        $aiService = new FortuneAIService($settings);

        $result = $aiService->testConnection();

        return response()->json($result);
    }
}
