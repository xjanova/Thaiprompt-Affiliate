<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashbackSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->hasRole('admin') || $this->user()->hasRole('super_admin'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|in:global,product',
            'product_id' => 'required_if:type,product|nullable|exists:products,id',
            'value_type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0|max:' . ($this->value_type === 'percentage' ? '100' : '999999'),
            'is_active' => 'boolean',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_cashback_amount' => 'nullable|numeric|min:0|gt:0',
            'description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'กรุณาเลือกประเภท Cashback',
            'type.in' => 'ประเภท Cashback ไม่ถูกต้อง',
            'product_id.required_if' => 'กรุณาเลือกสินค้าสำหรับ Product Cashback',
            'product_id.exists' => 'ไม่พบสินค้าที่เลือก',
            'value_type.required' => 'กรุณาเลือกประเภทค่า Cashback',
            'value_type.in' => 'ประเภทค่า Cashback ไม่ถูกต้อง',
            'value.required' => 'กรุณากรอกค่า Cashback',
            'value.numeric' => 'ค่า Cashback ต้องเป็นตัวเลข',
            'value.min' => 'ค่า Cashback ต้องมากกว่าหรือเท่ากับ 0',
            'value.max' => 'ค่า Cashback เกินขีดจำกัดที่กำหนด',
            'min_order_amount.numeric' => 'ยอดขั้นต่ำต้องเป็นตัวเลข',
            'min_order_amount.min' => 'ยอดขั้นต่ำต้องมากกว่าหรือเท่ากับ 0',
            'max_cashback_amount.numeric' => 'Cashback สูงสุดต้องเป็นตัวเลข',
            'max_cashback_amount.min' => 'Cashback สูงสุดต้องมากกว่า 0',
            'max_cashback_amount.gt' => 'Cashback สูงสุดต้องมากกว่า 0',
            'description.max' => 'คำอธิบายต้องไม่เกิน 500 ตัวอักษร',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox to boolean
        $this->merge([
            'is_active' => $this->has('is_active'),
        ]);

        // Clean up product_id if type is global
        if ($this->type === 'global') {
            $this->merge([
                'product_id' => null,
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: percentage cannot exceed 100
            if ($this->value_type === 'percentage' && $this->value > 100) {
                $validator->errors()->add('value', 'เปอร์เซ็นต์ไม่สามารถเกิน 100%');
            }

            // Custom validation: if max_cashback is set, it should be reasonable
            if ($this->max_cashback_amount && $this->min_order_amount) {
                if ($this->max_cashback_amount > $this->min_order_amount) {
                    $validator->errors()->add('max_cashback_amount', 'Cashback สูงสุดไม่ควรมากกว่ายอดขั้นต่ำ');
                }
            }
        });
    }
}
