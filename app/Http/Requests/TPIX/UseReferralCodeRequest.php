<?php

namespace App\Http\Requests\TPIX;

use Illuminate\Foundation\Http\FormRequest;

class UseReferralCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|exists:tpix_referral_codes,code',
            'token_id' => 'nullable|exists:tpix_tokens,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'กรุณาระบุรหัสแนะนำ',
            'code.exists' => 'รหัสแนะนำไม่ถูกต้อง',
            'token_id.exists' => 'ไม่พบ Token ที่ระบุ',
        ];
    }
}
