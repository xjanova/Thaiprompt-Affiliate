<?php

namespace App\Http\Requests\Crypto;

use App\Models\CryptoCurrency;
use Illuminate\Foundation\Http\FormRequest;

class SellCryptoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has a crypto wallet
        $wallet = $this->user()->defaultCryptoWallet;

        if (!$wallet) {
            return false;
        }

        // Check if wallet is active
        return $wallet->status === 'active';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $currency = CryptoCurrency::where('code', $this->currency_code)->first();

        return [
            'currency_code' => [
                'required',
                'exists:crypto_currencies,code',
            ],
            'crypto_amount' => [
                'required',
                'numeric',
                'min:' . ($currency?->min_withdrawal ?? 0.00001),
                'max:' . ($currency?->max_withdrawal ?? 1000000),
            ],
            'expected_rate' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'slippage_tolerance' => [
                'nullable',
                'numeric',
                'min:0',
                'max:10',
            ],
            'pin' => [
                'required',
                'string',
                'digits:6',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'currency_code.required' => 'กรุณาเลือกสกุลเหรียญที่ต้องการขาย',
            'currency_code.exists' => 'สกุลเหรียญที่เลือกไม่ถูกต้อง',
            'crypto_amount.required' => 'กรุณาระบุจำนวนเหรียญ',
            'crypto_amount.numeric' => 'จำนวนเหรียญต้องเป็นตัวเลข',
            'crypto_amount.min' => 'จำนวนเหรียญขั้นต่ำคือ :min',
            'crypto_amount.max' => 'จำนวนเหรียญสูงสุดคือ :max',
            'expected_rate.numeric' => 'อัตราแลกเปลี่ยนต้องเป็นตัวเลข',
            'slippage_tolerance.numeric' => 'ความคลาดเคลื่อนต้องเป็นตัวเลข',
            'slippage_tolerance.max' => 'ความคลาดเคลื่อนสูงสุดคือ 10%',
            'pin.required' => 'กรุณาระบุรหัส PIN',
            'pin.digits' => 'PIN ต้องเป็นตัวเลข 6 หลัก',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'currency_code' => 'สกุลเหรียญ',
            'crypto_amount' => 'จำนวนเหรียญ',
            'expected_rate' => 'อัตราแลกเปลี่ยนที่คาดหวัง',
            'slippage_tolerance' => 'ความคลาดเคลื่อน',
            'pin' => 'รหัส PIN',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default slippage if not provided
        if (!$this->slippage_tolerance) {
            $this->merge([
                'slippage_tolerance' => 1.0, // 1% default
            ]);
        }
    }
}
