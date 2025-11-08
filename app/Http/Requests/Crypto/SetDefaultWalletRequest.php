<?php

namespace App\Http\Requests\Crypto;

use Illuminate\Foundation\Http\FormRequest;

class SetDefaultWalletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if the wallet belongs to the user
        $walletId = $this->route('wallet') ?? $this->wallet_id;

        if (!$walletId) {
            return false;
        }

        $wallet = $this->user()->cryptoWallets()->find($walletId);

        return $wallet !== null && $wallet->status === 'active';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'wallet_id' => [
                'required',
                'exists:crypto_wallets,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'wallet_id.required' => 'กรุณาระบุกระเป๋าเงิน',
            'wallet_id.exists' => 'ไม่พบกระเป๋าเงินที่เลือก',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'wallet_id' => 'กระเป๋าเงิน',
        ];
    }
}
