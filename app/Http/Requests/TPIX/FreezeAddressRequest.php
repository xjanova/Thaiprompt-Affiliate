<?php

namespace App\Http\Requests\TPIX;

use Illuminate\Foundation\Http\FormRequest;

class FreezeAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'address' => 'required|string|size:42|regex:/^0x[a-fA-F0-9]{40}$/',
            'reason' => 'required|string|min:20|max:1000',
            'duration_days' => 'nullable|integer|min:1|max:365',
        ];
    }

    public function messages(): array
    {
        return [
            'address.required' => 'กรุณาระบุที่อยู่ที่ต้องการระงับ',
            'address.regex' => 'ที่อยู่ไม่ถูกต้อง (ต้องเป็น 0x...)',
            'reason.required' => 'กรุณาระบุเหตุผลในการระงับที่อยู่',
            'reason.min' => 'เหตุผลต้องมีอย่างน้อย 20 ตัวอักษร',
            'duration_days.max' => 'ระยะเวลาระงับต้องไม่เกิน 365 วัน',
        ];
    }
}
