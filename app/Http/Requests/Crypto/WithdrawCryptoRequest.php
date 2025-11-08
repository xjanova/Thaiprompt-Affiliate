<?php

namespace App\Http\Requests\Crypto;

use App\Models\CryptoCurrency;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawCryptoRequest extends FormRequest
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

        // Check if wallet is not locked
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
            'amount' => [
                'required',
                'numeric',
                'min:' . ($currency?->min_withdrawal ?? 0.00001),
                'max:' . ($currency?->max_withdrawal ?? 1000000),
            ],
            'to_address' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Basic validation - will be enhanced with proper address validation
                    if (strlen($value) < 26) {
                        $fail('ที่อยู่ปลายทางไม่ถูกต้อง');
                    }
                },
            ],
            'network' => [
                'required',
                'string',
                'in:bitcoin,ethereum,bsc,polygon,tron',
            ],
            'pin' => [
                'required',
                'string',
                'digits:6',
            ],
            'two_factor_code' => [
                'nullable',
                'string',
                'digits:6',
            ],
            'accept_terms' => [
                'required',
                'accepted',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'currency_code.required' => 'กรุณาเลือกสกุลเหรียญ',
            'currency_code.exists' => 'สกุลเหรียญที่เลือกไม่ถูกต้อง',
            'amount.required' => 'กรุณาระบุจำนวนที่ต้องการถอน',
            'amount.numeric' => 'จำนวนต้องเป็นตัวเลข',
            'amount.min' => 'จำนวนถอนต่ำสุดคือ :min',
            'amount.max' => 'จำนวนถอนสูงสุดคือ :max',
            'to_address.required' => 'กรุณาระบุที่อยู่ปลายทาง',
            'to_address.max' => 'ที่อยู่ปลายทางยาวเกินไป',
            'network.required' => 'กรุณาเลือกเครือข่าย',
            'network.in' => 'เครือข่ายที่เลือกไม่ถูกต้อง',
            'pin.required' => 'กรุณาระบุรหัส PIN',
            'pin.digits' => 'PIN ต้องเป็นตัวเลข 6 หลัก',
            'two_factor_code.digits' => 'รหัส 2FA ต้องเป็นตัวเลข 6 หลัก',
            'accept_terms.required' => 'กรุณายอมรับข้อกำหนดและเงื่อนไข',
            'accept_terms.accepted' => 'กรุณายอมรับข้อกำหนดและเงื่อนไข',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'currency_code' => 'สกุลเหรียญ',
            'amount' => 'จำนวน',
            'to_address' => 'ที่อยู่ปลายทาง',
            'network' => 'เครือข่าย',
            'pin' => 'รหัส PIN',
            'two_factor_code' => 'รหัส 2FA',
            'accept_terms' => 'ข้อกำหนดและเงื่อนไข',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from address
        if ($this->to_address) {
            $this->merge([
                'to_address' => trim($this->to_address),
            ]);
        }
    }
}
