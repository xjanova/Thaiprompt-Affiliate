<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OtpSetting;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OtpSettingsController extends Controller
{
    /**
     * Display OTP settings page
     */
    public function index()
    {
        $settings = OtpSetting::first();

        // Create default settings if none exist
        if (!$settings) {
            $settings = OtpSetting::create([
                'provider' => 'twilio',
                'enabled' => false,
                'enable_line_otp' => false,
                'line_otp_message_template' => 'รหัสยืนยันของคุณคือ: {code}\nใช้งานได้ใน {expiry} นาที',
                'otp_length' => 6,
                'otp_expiry_minutes' => 5,
                'max_attempts' => 3,
                'alphanumeric' => false,
                'rate_limit_per_phone' => 3,
                'rate_limit_window' => 60,
                'message_template' => 'Your verification code is: {code}. Valid for {expiry} minutes.',
            ]);
        }

        return view('admin.otp.settings', compact('settings'));
    }

    /**
     * Update OTP settings
     */
    public function update(Request $request)
    {
        $rules = [
            'provider' => ['required', 'in:twilio,nexmo,custom,line'],
            'enabled' => ['boolean'],
            'enable_line_otp' => ['boolean'],
            'line_otp_message_template' => ['nullable', 'string'],
            'otp_length' => ['required', 'integer', 'min:4', 'max:8'],
            'otp_expiry_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'alphanumeric' => ['boolean'],
            'rate_limit_per_phone' => ['required', 'integer', 'min:1', 'max:20'],
            'rate_limit_window' => ['required', 'integer', 'min:1', 'max:1440'],
            'message_template' => ['required', 'string'],
        ];

        // Provider-specific validation
        $provider = $request->input('provider');

        if ($provider === 'twilio') {
            $rules['twilio_sid'] = ['required_if:enabled,true', 'nullable', 'string'];
            $rules['twilio_token'] = ['required_if:enabled,true', 'nullable', 'string'];
            $rules['twilio_from'] = ['required_if:enabled,true', 'nullable', 'string'];
        } elseif ($provider === 'nexmo') {
            $rules['nexmo_key'] = ['required_if:enabled,true', 'nullable', 'string'];
            $rules['nexmo_secret'] = ['required_if:enabled,true', 'nullable', 'string'];
            $rules['nexmo_from'] = ['required_if:enabled,true', 'nullable', 'string'];
        } elseif ($provider === 'custom') {
            $rules['custom_api_url'] = ['required_if:enabled,true', 'nullable', 'url'];
            $rules['custom_api_key'] = ['nullable', 'string'];
            $rules['custom_api_headers'] = ['nullable', 'string'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'กรุณากรอกข้อมูลให้ครบถ้วน');
        }

        $settings = OtpSetting::firstOrNew();

        $data = [
            'provider' => $request->input('provider'),
            'enabled' => $request->boolean('enabled'),
            'enable_line_otp' => $request->boolean('enable_line_otp'),
            'line_otp_message_template' => $request->input('line_otp_message_template'),
            'otp_length' => $request->input('otp_length'),
            'otp_expiry_minutes' => $request->input('otp_expiry_minutes'),
            'max_attempts' => $request->input('max_attempts'),
            'alphanumeric' => $request->boolean('alphanumeric'),
            'rate_limit_per_phone' => $request->input('rate_limit_per_phone'),
            'rate_limit_window' => $request->input('rate_limit_window'),
            'message_template' => $request->input('message_template'),
        ];

        // Provider-specific fields
        if ($provider === 'twilio') {
            $data['twilio_sid'] = $request->input('twilio_sid');
            $data['twilio_token'] = $request->input('twilio_token');
            $data['twilio_from'] = $request->input('twilio_from');
        } elseif ($provider === 'nexmo') {
            $data['nexmo_key'] = $request->input('nexmo_key');
            $data['nexmo_secret'] = $request->input('nexmo_secret');
            $data['nexmo_from'] = $request->input('nexmo_from');
        } elseif ($provider === 'custom') {
            $data['custom_api_url'] = $request->input('custom_api_url');
            $data['custom_api_key'] = $request->input('custom_api_key');
            $data['custom_api_headers'] = $request->input('custom_api_headers');
        }

        $settings->fill($data);
        $settings->save();

        // Clear cache
        OtpSetting::clearCache();

        return redirect()->back()->with('success', 'บันทึกการตั้งค่า OTP เรียบร้อยแล้ว');
    }

    /**
     * Test OTP sending
     */
    public function test(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_phone' => ['required', 'string', 'regex:/^(\+66|66|0)[0-9]{9}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'เบอร์โทรศัพท์ไม่ถูกต้อง',
            ], 422);
        }

        if (!OtpSetting::isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเปิดใช้งานระบบ OTP ก่อนทดสอบ',
            ], 400);
        }

        $otpService = app(OtpService::class);
        $result = $otpService->sendOTP($request->input('test_phone'), 'test');

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'ส่งรหัส OTP ทดสอบเรียบร้อยแล้ว กรุณาตรวจสอบโทรศัพท์',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'ไม่สามารถส่ง OTP ได้',
        ], 500);
    }
}
