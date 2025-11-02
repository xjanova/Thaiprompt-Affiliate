<?php

namespace App\Http\Controllers;

use App\Models\OtpSetting;
use App\Models\OtpVerification;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OtpController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Send OTP to phone number
     */
    public function send(Request $request)
    {
        // Check if OTP is enabled
        if (!OtpSetting::isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'ระบบ OTP ไม่ได้เปิดใช้งาน',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'regex:/^(\+66|66|0)[0-9]{9}$/'],
            'purpose' => ['nullable', 'string', 'in:phone_verification,password_reset,login'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'เบอร์โทรศัพท์ไม่ถูกต้อง กรุณากรอกเบอร์โทรศัพท์ไทย 10 หลัก',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = $request->input('phone');
        $purpose = $request->input('purpose', 'phone_verification');

        // Check rate limit
        if (!OtpVerification::checkRateLimit($phone)) {
            $settings = OtpSetting::getActive();
            $window = $settings->rate_limit_window ?? 60;

            return response()->json([
                'success' => false,
                'message' => "คุณส่ง OTP บ่อยเกินไป กรุณารอ {$window} นาทีก่อนลองอีกครั้ง",
            ], 429);
        }

        // Send OTP
        $result = $this->otpService->sendOTP($phone, $purpose);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'ส่งรหัส OTP เรียบร้อยแล้ว กรุณาตรวจสอบข้อความในโทรศัพท์',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'ไม่สามารถส่ง OTP ได้ กรุณาลองใหม่อีกครั้ง',
        ], 500);
    }

    /**
     * Verify OTP code
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string'],
            'code' => ['required', 'string', 'min:4', 'max:8'],
            'purpose' => ['nullable', 'string', 'in:phone_verification,password_reset,login'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = $request->input('phone');
        $code = $request->input('code');
        $purpose = $request->input('purpose', 'phone_verification');

        $verified = OtpVerification::verifyOTP($phone, $code, $purpose);

        if ($verified) {
            // If user is authenticated and purpose is phone verification, update their phone
            if (Auth::check() && $purpose === 'phone_verification') {
                $user = Auth::user();
                $user->update([
                    'phone' => $phone,
                    'phone_verified' => true,
                    'phone_verified_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'ยืนยันเบอร์โทรศัพท์สำเร็จ',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'รหัส OTP ไม่ถูกต้องหรือหมดอายุแล้ว',
        ], 400);
    }

    /**
     * Check verification status
     */
    public function status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string'],
            'purpose' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = $request->input('phone');
        $purpose = $request->input('purpose', 'phone_verification');

        $hasVerification = OtpVerification::hasRecentVerification($phone, $purpose);

        return response()->json([
            'success' => true,
            'verified' => $hasVerification,
        ]);
    }

    /**
     * Resend OTP
     */
    public function resend(Request $request)
    {
        return $this->send($request);
    }
}
