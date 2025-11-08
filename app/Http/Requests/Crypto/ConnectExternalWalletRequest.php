<?php

namespace App\Http\Requests\Crypto;

use Illuminate\Foundation\Http\FormRequest;

class ConnectExternalWalletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->cryptoWallets()->count() < 10;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'address' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Basic validation - will be enhanced with proper address validation
                    if (strlen($value) < 26) {
                        $fail('ที่อยู่กระเป๋าเงินไม่ถูกต้อง');
                    }
                },
            ],
            'network' => [
                'required',
                'string',
                'in:bitcoin,ethereum,bsc,polygon,tron',
            ],
            'currency_code' => [
                'required',
                'exists:crypto_currencies,code',
            ],
            'signature' => ['nullable', 'string'], // For Web3 verification
            'message' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'address.required' => 'กรุณาระบุที่อยู่กระเป๋าเงิน',
            'address.max' => 'ที่อยู่กระเป๋าเงินยาวเกินไป',
            'network.required' => 'กรุณาเลือกเครือข่าย',
            'network.in' => 'เครือข่ายที่เลือกไม่ถูกต้อง',
            'currency_code.required' => 'กรุณาเลือกสกุลเหรียญ',
            'currency_code.exists' => 'สกุลเหรียญที่เลือกไม่ถูกต้อง',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'address' => 'ที่อยู่กระเป๋าเงิน',
            'network' => 'เครือข่าย',
            'currency_code' => 'สกุลเหรียญ',
            'signature' => 'ลายเซ็น',
        ];
    }
}
