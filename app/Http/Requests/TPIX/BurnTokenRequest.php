<?php

namespace App\Http\Requests\TPIX;

use Illuminate\Foundation\Http\FormRequest;

class BurnTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'from_address' => 'required|string|size:42|regex:/^0x[a-fA-F0-9]{40}$/',
            'amount' => 'required|numeric|min:0.00000001',
            'reason' => 'required|string|min:10|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'from_address.required' => 'กรุณาระบุที่อยู่ที่ต้องการเบิร์น Token',
            'from_address.regex' => 'ที่อยู่ไม่ถูกต้อง (ต้องเป็น 0x...)',
            'amount.required' => 'กรุณาระบุจำนวน Token ที่ต้องการเบิร์น',
            'amount.min' => 'จำนวนต้องมากกว่า 0',
            'reason.required' => 'กรุณาระบุเหตุผลในการเบิร์น Token',
            'reason.min' => 'เหตุผลต้องมีอย่างน้อย 10 ตัวอักษร',
        ];
    }
}
