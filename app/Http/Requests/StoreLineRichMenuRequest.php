<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Form Request สำหรับสร้าง LINE Rich Menu
 *
 * ตรวจสอบ:
 * - ข้อมูลพื้นฐาน (name, description, chat_bar_text, etc.)
 * - ขนาดภาพตามมาตรฐาน LINE (2500x1686px หรือ 2500x843px)
 * - File size (max 1MB)
 * - Format (JPG, PNG, WEBP)
 */
class StoreLineRichMenuRequest extends FormRequest
{
    /**
     * ตรวจสอบว่า user มีสิทธิ์ทำ request นี้หรือไม่
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    /**
     * Validation rules
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'size' => 'required|in:full,half',
            'selected' => 'sometimes|boolean',
            'chat_bar_text' => 'required|string|max:14',
            'areas' => 'required|json',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:1024', // max 1MB
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Custom validation messages
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'กรุณาระบุชื่อ Rich Menu',
            'name.max' => 'ชื่อ Rich Menu ต้องไม่เกิน 255 ตัวอักษร',
            'size.required' => 'กรุณาเลือกขนาด Rich Menu',
            'size.in' => 'ขนาด Rich Menu ต้องเป็น full หรือ half เท่านั้น',
            'chat_bar_text.required' => 'กรุณาระบุข้อความบน Chat Bar',
            'chat_bar_text.max' => 'ข้อความบน Chat Bar ต้องไม่เกิน 14 ตัวอักษร',
            'areas.required' => 'กรุณากำหนดพื้นที่คลิก (Areas)',
            'areas.json' => 'ข้อมูล Areas ต้องอยู่ในรูปแบบ JSON',
            'image.required' => 'กรุณาอัปโหลดภาพ Rich Menu',
            'image.image' => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น',
            'image.mimes' => 'รองรับเฉพาะไฟล์ JPG, PNG, WEBP',
            'image.max' => 'ขนาดไฟล์ต้องไม่เกิน 1 MB',
        ];
    }

    /**
     * Custom attributes สำหรับ error messages
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'ชื่อ Rich Menu',
            'description' => 'คำอธิบาย',
            'size' => 'ขนาด',
            'chat_bar_text' => 'ข้อความบน Chat Bar',
            'areas' => 'พื้นที่คลิก',
            'image' => 'รูปภาพ',
        ];
    }

    /**
     * Custom validator ตรวจสอบขนาดภาพตามมาตรฐาน LINE
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->hasFile('image') && $this->file('image')->isValid()) {
                $this->validateImageDimensions($validator);
            }
        });
    }

    /**
     * ตรวจสอบขนาดภาพตามมาตรฐาน LINE Rich Menu
     *
     * LINE Rich Menu Image Specifications:
     * - Full size: 2500 x 1686 px
     * - Half size: 2500 x 843 px
     * - File size: max 1 MB
     * - Format: JPG, PNG
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateImageDimensions(Validator $validator): void
    {
        $image = $this->file('image');
        $size = $this->input('size');

        // อ่านขนาดภาพ
        $imageInfo = getimagesize($image->getPathname());

        if ($imageInfo === false) {
            $validator->errors()->add('image', 'ไม่สามารถอ่านข้อมูลรูปภาพได้');
            return;
        }

        [$width, $height] = $imageInfo;

        // กำหนดขนาดที่ถูกต้องตาม LINE spec
        $requiredDimensions = [
            'full' => ['width' => 2500, 'height' => 1686],
            'half' => ['width' => 2500, 'height' => 843],
        ];

        $required = $requiredDimensions[$size] ?? null;

        if (!$required) {
            $validator->errors()->add('size', 'ขนาด Rich Menu ไม่ถูกต้อง');
            return;
        }

        // ✅ ตรวจสอบว่าภาพมีขนาดตรงตาม spec หรือไม่
        // เรายอมรับได้ 2 กรณี:
        // 1. ขนาดตรงพอดี (2500x1686 หรือ 2500x843)
        // 2. Aspect ratio ถูกต้อง และระบบจะ resize ให้อัตโนมัติ

        $requiredWidth = $required['width'];
        $requiredHeight = $required['height'];
        $requiredRatio = $requiredWidth / $requiredHeight;
        $actualRatio = $width / $height;

        // ตรวจสอบ aspect ratio (ยอมรับผิดพลาด 1%)
        $ratioTolerance = 0.01;
        $ratioDifference = abs($requiredRatio - $actualRatio) / $requiredRatio;

        if ($ratioDifference > $ratioTolerance) {
            $validator->errors()->add(
                'image',
                sprintf(
                    '❌ Aspect Ratio ไม่ถูกต้อง! ต้องการ %d:%d (%.2f:1) แต่ได้ %d:%d (%.2f:1)',
                    $requiredWidth,
                    $requiredHeight,
                    $requiredRatio,
                    $width,
                    $height,
                    $actualRatio
                )
            );
            return;
        }

        // ⚠️ ถ้าขนาดไม่ตรง แต่ aspect ratio ถูกต้อง → แจ้งเตือนว่าจะ resize
        if ($width !== $requiredWidth || $height !== $requiredHeight) {
            // เก็บ flag ไว้เพื่อบอกให้ controller resize ภาพ
            $this->merge([
                'needs_resize' => true,
                'original_width' => $width,
                'original_height' => $height,
            ]);

            // แจ้งเตือน (ไม่ block การ upload)
            session()->flash('warning', sprintf(
                '⚠️ ภาพมีขนาด %dx%d px จะถูก resize เป็น %dx%d px อัตโนมัติ',
                $width,
                $height,
                $requiredWidth,
                $requiredHeight
            ));
        }

        // ✅ ตรวจสอบว่าภาพมีขนาดพอสำหรับ upscale หรือไม่
        if ($width < $requiredWidth * 0.5 || $height < $requiredHeight * 0.5) {
            $validator->errors()->add(
                'image',
                sprintf(
                    '❌ ภาพมีขนาดเล็กเกินไป! (%dx%d px) ต้องการอย่างน้อย %dx%d px',
                    $width,
                    $height,
                    (int)($requiredWidth * 0.5),
                    (int)($requiredHeight * 0.5)
                )
            );
            return;
        }

        // ✅ ทุกอย่างผ่าน!
        session()->flash('info', sprintf(
            '✅ ภาพผ่านการตรวจสอบ (%dx%d px)',
            $width,
            $height
        ));
    }
}
