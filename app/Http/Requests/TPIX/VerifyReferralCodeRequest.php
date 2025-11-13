<?php

namespace App\Http\Requests\TPIX;

use Illuminate\Foundation\Http\FormRequest;

class VerifyReferralCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'referral_use_id' => 'required|exists:tpix_referral_uses,id',
            'verification_code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'referral_use_id.required' => 'ไม่พบข้อมูลการใช้รหัสแนะนำ',
            'referral_use_id.exists' => 'รหัสอ้างอิงไม่ถูกต้อง',
            'verification_code.required' => 'กรุณาระบุรหัสยืนยัน',
            'verification_code.size' => 'รหัสยืนยันต้องเป็นตัวเลข 6 หลัก',
            'verification_code.regex' => 'รหัสยืนยันต้องเป็นตัวเลขเท่านั้น',
        ];
    }
}
