<?php

namespace App\Http\Requests\TPIX;

use Illuminate\Foundation\Http\FormRequest;

class BuyTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'tpix_amount' => 'required|numeric|min:1',
            'slippage_tolerance' => 'nullable|numeric|min:0|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'tpix_amount.required' => 'กรุณาระบุจำนวน TPIX ที่ต้องการใช้ซื้อ',
            'tpix_amount.min' => 'จำนวน TPIX ต้องอย่างน้อย 1',
            'slippage_tolerance.max' => 'Slippage tolerance ต้องไม่เกิน 50%',
        ];
    }
}
