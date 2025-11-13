<?php

namespace App\Http\Requests\TPIX;

use Illuminate\Foundation\Http\FormRequest;

class CreateStakingPoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'token_id' => 'required|exists:tpix_tokens,id',
            'name' => 'required|string|min:5|max:100',
            'description' => 'required|string|min:20|max:2000',

            // Staking Parameters
            'apy' => 'required|numeric|min:0.01|max:10000',
            'lock_period_days' => 'required|integer|min:1|max:3650',
            'min_stake_amount' => 'required|numeric|min:0.00000001',
            'max_stake_amount' => 'nullable|numeric|gt:min_stake_amount',

            // Pool Limits
            'pool_cap' => 'nullable|numeric|min:1',
            'max_stakers' => 'nullable|integer|min:1',

            // Rewards
            'reward_token_type' => 'required|in:same,tpix,other',
            'reward_token_id' => 'required_if:reward_token_type,other|nullable|exists:tpix_tokens,id',

            // Timing
            'starts_at' => 'nullable|date|after:now',
            'ends_at' => 'nullable|date|after:starts_at',
        ];
    }

    public function messages(): array
    {
        return [
            'token_id.required' => 'กรุณาเลือก Token',
            'token_id.exists' => 'ไม่พบ Token ที่ระบุ',
            'name.required' => 'กรุณาระบุชื่อ Pool',
            'name.min' => 'ชื่อ Pool ต้องมีอย่างน้อย 5 ตัวอักษร',
            'description.min' => 'คำอธิบายต้องมีอย่างน้อย 20 ตัวอักษร',
            'apy.required' => 'กรุณาระบุ APY',
            'apy.min' => 'APY ต้องมากกว่า 0',
            'lock_period_days.required' => 'กรุณาระบุระยะเวลาล็อค',
            'lock_period_days.max' => 'ระยะเวลาล็อคต้องไม่เกิน 10 ปี',
            'min_stake_amount.required' => 'กรุณาระบุจำนวน Stake ขั้นต่ำ',
            'max_stake_amount.gt' => 'จำนวนสูงสุดต้องมากกว่าจำนวนขั้นต่ำ',
            'reward_token_type.required' => 'กรุณาเลือกประเภทรางวัล',
            'starts_at.after' => 'วันที่เริ่มต้องเป็นวันในอนาคต',
            'ends_at.after' => 'วันที่สิ้นสุดต้องหลังวันที่เริ่ม',
        ];
    }
}
