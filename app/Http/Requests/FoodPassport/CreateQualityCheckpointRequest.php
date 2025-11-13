<?php

namespace App\Http\Requests\FoodPassport;

use Illuminate\Foundation\Http\FormRequest;

class CreateQualityCheckpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'food_product_id' => 'required|exists:food_products,id',
            'journey_stage_id' => 'nullable|exists:product_journey,id',
            'checkpoint_type' => 'required|in:visual,laboratory,sensor,certification,audit',
            'checkpoint_name' => 'required|string|max:255',
            'checked_at' => 'nullable|date',
            'inspector_id' => 'nullable|exists:users,id',
            'inspector_name' => 'nullable|string|max:255',
            'inspector_license' => 'nullable|string|max:100',
            'organization' => 'nullable|string|max:255',
            'test_parameters' => 'nullable|array',
            'test_parameters.*.name' => 'required|string',
            'test_parameters.*.value' => 'required',
            'test_parameters.*.unit' => 'nullable|string',
            'test_parameters.*.standard' => 'nullable',
            'test_parameters.*.status' => 'required|in:pass,fail',
            'overall_result' => 'required|in:pass,conditional_pass,fail',
            'pass_score' => 'nullable|numeric|min:0|max:100',
            'standards_applied' => 'nullable|array',
            'standards_applied.*' => 'string',
            'certification_issued' => 'boolean',
            'test_report_url' => 'nullable|url',
            'photos' => 'nullable|array',
            'photos.*' => 'url',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'food_product_id.required' => 'กรุณาระบุรหัสผลิตภัณฑ์',
            'checkpoint_type.required' => 'กรุณาระบุประเภทการตรวจสอบ',
            'checkpoint_name.required' => 'กรุณาระบุชื่อจุดตรวจสอบ',
            'overall_result.required' => 'กรุณาระบุผลการตรวจสอบ',
        ];
    }
}
