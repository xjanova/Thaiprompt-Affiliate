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
        // กระเป๋าต้องเป็นของสมาชิกคนนี้และยังใช้งานได้
        // route จริงคือ /wallet/{id}/set-default — เดิมอ่าน route('wallet') ที่ไม่มีอยู่ จึงได้ 403 ทุกครั้ง
        $walletId = $this->route('id') ?? $this->route('wallet') ?? $this->wallet_id;

        if (! $walletId) {
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
        // รหัสกระเป๋ามากับ URL และตรวจความเป็นเจ้าของแล้วใน authorize() — ไม่ต้องส่ง wallet_id ในฟอร์มซ้ำ
        // (เดิมบังคับ wallet_id ที่ฟอร์มไม่เคยส่ง ปุ่ม "ตั้งเป็นกระเป๋าหลัก" จึงใช้ไม่ได้)
        return [];
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
