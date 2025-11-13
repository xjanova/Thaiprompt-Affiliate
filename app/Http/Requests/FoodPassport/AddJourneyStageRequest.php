<?php

namespace App\Http\Requests\FoodPassport;

use Illuminate\Foundation\Http\FormRequest;

class AddJourneyStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'food_product_id' => 'required|exists:food_products,id',
            'stage' => 'required|in:farm,processing,storage,distribution,retail,transport',
            'stage_name' => 'nullable|string|max:100',
            'location_name' => 'required|string|max:255',
            'location_coordinates' => 'nullable|array',
            'location_coordinates.lat' => 'required_with:location_coordinates|numeric|between:-90,90',
            'location_coordinates.lng' => 'required_with:location_coordinates|numeric|between:-180,180',
            'arrived_at' => 'nullable|date',
            'handler_id' => 'nullable|exists:users,id',
            'handler_name' => 'nullable|string|max:255',
            'handler_type' => 'nullable|in:farmer,processor,distributor,retailer,transporter',
            'organization' => 'nullable|string|max:255',
            'temperature_range' => 'nullable|array',
            'humidity_range' => 'nullable|array',
            'storage_conditions' => 'nullable|string',
            'transport_method' => 'nullable|string|max:50',
            'distance_km' => 'nullable|numeric|min:0',
            'fuel_type' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'url',
            'documents' => 'nullable|array',
            'documents.*' => 'url',
        ];
    }

    public function messages(): array
    {
        return [
            'food_product_id.required' => 'กรุณาระบุรหัสผลิตภัณฑ์',
            'food_product_id.exists' => 'ไม่พบผลิตภัณฑ์ในระบบ',
            'stage.required' => 'กรุณาระบุขั้นตอน',
            'stage.in' => 'ขั้นตอนไม่ถูกต้อง',
            'location_name.required' => 'กรุณาระบุสถานที่',
        ];
    }
}
