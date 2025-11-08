<?php

namespace App\Http\Requests\Crypto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CreateCustodialWalletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user doesn't already have too many wallets
        return $this->user()->cryptoWallets()->count() < 5;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'pin' => [
                'required',
                'string',
                'digits:6',
                'confirmed',
            ],
            'two_factor_enabled' => ['nullable', 'boolean'],
            'currencies' => ['nullable', 'array'],
            'currencies.*' => ['exists:crypto_currencies,code'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'pin.required' => 'กรุณาตั้ง PIN 6 หลัก',
            'pin.digits' => 'PIN ต้องเป็นตัวเลข 6 หลัก',
            'pin.confirmed' => 'PIN ไม่ตรงกัน',
            'currencies.*.exists' => 'สกุลเหรียญที่เลือกไม่ถูกต้อง',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'pin' => 'รหัส PIN',
            'two_factor_enabled' => 'การยืนยันตัวตนสองขั้นตอน',
            'currencies' => 'สกุลเหรียญ',
        ];
    }
}
