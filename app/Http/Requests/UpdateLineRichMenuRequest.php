<?php

namespace App\Http\Requests;

/**
 * Form Request สำหรับอัปเดต LINE Rich Menu
 *
 * เหมือน StoreLineRichMenuRequest แต่ image เป็น optional
 */
class UpdateLineRichMenuRequest extends StoreLineRichMenuRequest
{
    /**
     * Validation rules (override เพื่อให้ image เป็น nullable)
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // เปลี่ยน image จาก required เป็น nullable
        $rules['image'] = 'nullable|image|mimes:jpg,jpeg,png,webp|max:1024';

        return $rules;
    }

    /**
     * Custom messages (override)
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = parent::messages();

        // ลบ message สำหรับ image.required เพราะเป็น nullable แล้ว
        unset($messages['image.required']);

        return $messages;
    }
}
