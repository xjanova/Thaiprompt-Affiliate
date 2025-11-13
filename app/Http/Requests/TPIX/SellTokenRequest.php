<?php

namespace App\Http\Requests\TPIX;

use Illuminate\Foundation\Http\FormRequest;

class SellTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'token_amount' => 'required|numeric|min:0.00000001',
            'slippage_tolerance' => 'nullable|numeric|min:0|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'token_amount.required' => 'กรุณาระบุจำนวน Token ที่ต้องการขาย',
            'token_amount.min' => 'จำนวน Token ต้องมากกว่า 0',
            'slippage_tolerance.max' => 'Slippage tolerance ต้องไม่เกิน 50%',
        ];
    }
}
