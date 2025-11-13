<?php

namespace App\Http\Requests\FoodPassport;

use Illuminate\Foundation\Http\FormRequest;

class CreateFoodProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Will be handled by policy
    }

    public function rules(): array
    {
        return [
            'product_id' => 'nullable|exists:products,id',
            'farm_name' => 'nullable|string|max:255',
            'farm_location' => 'nullable|array',
            'farm_location.lat' => 'required_with:farm_location|numeric|between:-90,90',
            'farm_location.lng' => 'required_with:farm_location|numeric|between:-180,180',
            'farm_location.address' => 'nullable|string',
            'farmer_id' => 'nullable|exists:users,id',
            'product_type' => 'required|string|max:100',
            'variety' => 'required|string|max:100',
            'harvest_date' => 'required|date|before_or_equal:today',
            'batch_number' => 'nullable|string|max:50',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
            'certifications' => 'nullable|array',
            'certifications.*' => 'string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'product_type.required' => 'กรุณาระบุประเภทผลิตภัณฑ์',
            'variety.required' => 'กรุณาระบุชนิดพันธุ์',
            'harvest_date.required' => 'กรุณาระบุวันที่เก็บเกี่ยว',
            'harvest_date.before_or_equal' => 'วันที่เก็บเกี่ยวต้องไม่เกินวันนี้',
            'quantity.required' => 'กรุณาระบุปริมาณ',
            'quantity.min' => 'ปริมาณต้องมากกว่า 0',
            'unit.required' => 'กรุณาระบุหน่วย',
        ];
    }
}
