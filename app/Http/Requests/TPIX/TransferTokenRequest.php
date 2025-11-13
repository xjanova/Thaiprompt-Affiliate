<?php

namespace App\Http\Requests\TPIX;

use Illuminate\Foundation\Http\FormRequest;

class TransferTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'to_address_or_email' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.00000001',
            'note' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'to_address_or_email.required' => 'กรุณาระบุที่อยู่ผู้รับหรืออีเมล',
            'amount.required' => 'กรุณาระบุจำนวน Token',
            'amount.min' => 'จำนวน Token ต้องมากกว่า 0',
            'note.max' => 'หมายเหตุต้องไม่เกิน 500 ตัวอักษร',
        ];
    }
}
