<?php

namespace App\Http\Requests\TPIX;

use Illuminate\Foundation\Http\FormRequest;

class StakeTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'pool_id' => 'required|exists:tpix_staking_pools,id',
            'amount' => 'required|numeric|min:0.00000001',
        ];
    }

    public function messages(): array
    {
        return [
            'pool_id.required' => 'กรุณาเลือก Staking Pool',
            'pool_id.exists' => 'ไม่พบ Staking Pool ที่ระบุ',
            'amount.required' => 'กรุณาระบุจำนวน Token ที่ต้องการ Stake',
            'amount.min' => 'จำนวนต้องมากกว่า 0',
        ];
    }
}
