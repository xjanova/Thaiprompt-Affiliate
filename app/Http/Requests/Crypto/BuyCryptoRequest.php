<?php

namespace App\Http\Requests\Crypto;

use App\Models\CryptoCurrency;
use Illuminate\Foundation\Http\FormRequest;

class BuyCryptoRequest extends FormRequest
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
            'thb_amount' => [
                'required',
                'numeric',
                'min:100',
                'max:1000000',
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
            'currency_code.required' => 'กรุณาเลือกสกุลเหรียญที่ต้องการซื้อ',
            'currency_code.exists' => 'สกุลเหรียญที่เลือกไม่ถูกต้อง',
            'thb_amount.required' => 'กรุณาระบุจำนวนเงิน THB',
            'thb_amount.numeric' => 'จำนวนเงินต้องเป็นตัวเลข',
            'thb_amount.min' => 'จำนวนเงินขั้นต่ำคือ 100 บาท',
            'thb_amount.max' => 'จำนวนเงินสูงสุดคือ 1,000,000 บาท',
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
            'thb_amount' => 'จำนวนเงิน THB',
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
