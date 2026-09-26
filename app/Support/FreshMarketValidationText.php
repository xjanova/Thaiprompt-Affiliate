<?php

namespace App\Support;

/**
 * ข้อความ validation ภาษาไทยของฟอร์มตลาดสด (สมัครร้าน / ตั้งค่าร้าน / ลงขาย / แก้สินค้า)
 *
 * ใช้ร่วมกันระหว่างหน้าเว็บ (FreshMarket\HomeController) และ API แอป (Api\V1\FreshMarketApiController)
 * → ผู้ใช้เห็นข้อความเดียวกันทั้งสองช่องทาง (โปรเจกต์ไม่มี lang/th/validation.php จึงต้องส่งข้อความเอง)
 */
class FreshMarketValidationText
{
    /**
     * ข้อความตามชนิดกฎ (ใช้ต่อท้ายข้อความเฉพาะช่อง — ข้อความเฉพาะช่องมาก่อนเสมอ)
     *
     * @return array<string, string|array<string, string>>
     */
    public static function messages(): array
    {
        return [
            'required' => 'กรุณากรอก:attribute',
            'required_if' => 'กรุณากรอก:attribute',
            'required_with' => 'กรุณากรอก:attribute',
            'accepted' => 'กรุณายอมรับ:attribute',
            'string' => ':attributeไม่ถูกต้อง',
            'numeric' => ':attributeต้องเป็นตัวเลข',
            'integer' => ':attributeต้องเป็นจำนวนเต็ม',
            'boolean' => ':attributeไม่ถูกต้อง',
            'array' => ':attributeไม่ถูกต้อง',
            'exists' => 'กรุณาเลือก:attributeที่มีอยู่ในระบบ',
            'in' => ':attributeไม่ถูกต้อง',
            'image' => ':attributeต้องเป็นไฟล์รูปภาพ',
            'regex' => 'รูปแบบ:attributeไม่ถูกต้อง',
            'between' => [
                'numeric' => ':attributeไม่ถูกต้อง',
                'string' => ':attributeไม่ถูกต้อง',
                'array' => ':attributeไม่ถูกต้อง',
                'file' => ':attributeไม่ถูกต้อง',
            ],
            'gt' => [
                'numeric' => ':attributeต้องมากกว่า :value',
                'string' => ':attributeไม่ถูกต้อง',
                'array' => ':attributeไม่ถูกต้อง',
                'file' => ':attributeไม่ถูกต้อง',
            ],
            'min' => [
                'numeric' => ':attributeต้องไม่น้อยกว่า :min',
                'string' => ':attributeต้องยาวอย่างน้อย :min ตัวอักษร',
                'array' => ':attributeต้องมีอย่างน้อย :min รายการ',
                'file' => ':attributeมีขนาดเล็กเกินไป',
            ],
            'max' => [
                'numeric' => ':attributeต้องไม่เกิน :max',
                'string' => ':attributeยาวได้ไม่เกิน :max ตัวอักษร',
                'array' => ':attributeมีได้ไม่เกิน :max รายการ',
                'file' => ':attributeต้องมีขนาดไม่เกิน :max KB',
            ],
        ];
    }

    /**
     * ชื่อช่องภาษาไทย
     *
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'title' => 'ชื่อสินค้า',
            'description' => 'รายละเอียดสินค้า',
            'category_id' => 'หมวดหมู่',
            'price' => 'ราคาขาย',
            'compare_at_price' => 'ราคาก่อนลด',
            'unit' => 'หน่วยขาย',
            'track_stock' => 'การตัดสต็อก',
            'quantity_available' => 'จำนวนที่มีขาย',
            'is_organic' => 'สินค้าอินทรีย์',
            'is_available' => 'สถานะเปิดขาย',
            'freshness_level' => 'ระดับความสด',
            'cashback_percentage' => 'เงินคืน',
            'images' => 'รูปสินค้า',
            'images.*' => 'รูปสินค้า',
            'shop_name' => 'ชื่อร้าน',
            'shop_description' => 'คำอธิบายร้าน',
            'phone' => 'เบอร์โทร',
            'address' => 'ที่อยู่ร้าน',
            'province' => 'จังหวัด',
            'district' => 'อำเภอ/เขต',
            'sub_district' => 'ตำบล/แขวง',
            'latitude' => 'พิกัดร้าน',
            'longitude' => 'พิกัดร้าน',
            'agree_terms' => 'เงื่อนไขการขาย',
            'option_groups' => 'กลุ่มตัวเลือก',
            'option_groups.*.name' => 'ชื่อกลุ่มตัวเลือก',
            'option_groups.*.min_select' => 'จำนวนขั้นต่ำที่ต้องเลือก',
            'option_groups.*.max_select' => 'จำนวนสูงสุดที่เลือกได้',
            'option_groups.*.options' => 'ตัวเลือก',
            'option_groups.*.options.*.name' => 'ชื่อตัวเลือก',
            'option_groups.*.options.*.price_delta' => 'ราคาเพิ่มของตัวเลือก',
            'option_groups.*.options.*.image' => 'รูปตัวเลือก',
        ];
    }
}
