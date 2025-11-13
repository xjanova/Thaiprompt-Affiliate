<?php

namespace App\Http\Requests\TPIX;

use Illuminate\Foundation\Http\FormRequest;

class DeployTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'gas_price' => 'nullable|numeric|min:1',
            'confirm' => 'required|accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'gas_price.min' => 'Gas price ต้องมากกว่า 0',
            'confirm.required' => 'กรุณายืนยันการ Deploy Token',
            'confirm.accepted' => 'กรุณายืนยันว่าคุณเข้าใจและยอมรับความเสี่ยง',
        ];
    }
}
