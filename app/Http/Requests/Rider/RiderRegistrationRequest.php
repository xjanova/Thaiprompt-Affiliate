<?php

namespace App\Http\Requests\Rider;

use App\Models\Rider;
use App\Rules\ThaiNationalId;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * ใบสมัครไรเดอร์ — ใช้ร่วมกันทั้งแอป (POST /api/v1/rider/register) และเว็บ (POST /user/rider/register)
 *
 * กติกาเดียวกันทุกช่องทาง (audit RIDER-27):
 *   - เลขบัตรประชาชน 13 หลัก + checksum ถูกต้อง + ไม่ซ้ำกับไรเดอร์คนอื่น
 *   - อายุ ≥ 18 ปีบริบูรณ์
 *   - เบอร์มือถือไทย 9-10 หลักขึ้นต้นด้วย 0
 *   - มอเตอร์ไซค์/รถยนต์ ต้องมีทะเบียนรถ
 *
 * API: ข้อมูลไม่ผ่าน → 422 {success:false, code:VALIDATION_ERROR, message, errors}
 * เว็บ: redirect กลับพร้อม error ตามปกติของ Laravel
 */
class RiderRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * ล้างรูปแบบก่อนตรวจ (เบอร์/เลขบัตรที่พิมพ์มีขีดหรือเว้นวรรค)
     */
    protected function prepareForValidation(): void
    {
        $clean = [];

        if ($this->has('id_card_number')) {
            $clean['id_card_number'] = ThaiNationalId::normalize($this->input('id_card_number'));
        }

        if ($this->has('phone')) {
            $clean['phone'] = preg_replace('/[\s\-().]+/', '', (string) $this->input('phone'));
        }

        foreach (['full_name', 'address', 'province', 'district', 'vehicle_brand', 'vehicle_color'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $clean[$field] = trim((string) $this->input($field));
            }
        }

        if ($this->has('vehicle_plate') && is_string($this->input('vehicle_plate'))) {
            $clean['vehicle_plate'] = trim(preg_replace('/\s+/', ' ', (string) $this->input('vehicle_plate')));
        }

        $this->merge($clean);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // ไรเดอร์เดิมของผู้ใช้คนนี้ (สมัครใหม่หลังถูกปฏิเสธ / แก้ใบสมัคร) → ไม่นับว่าเลขบัตรซ้ำกับตัวเอง
        $ownRiderId = Rider::withTrashed()->where('user_id', (int) $this->user()?->id)->value('id');

        return [
            'full_name' => ['required', 'string', 'min:4', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^0\d{8,9}$/'],
            'id_card_number' => [
                'required',
                'string',
                new ThaiNationalId,
                Rule::unique('riders', 'id_card_number')->ignore($ownRiderId)->whereNull('deleted_at'),
            ],
            'birth_date' => [
                'required',
                'date',
                'before_or_equal:'.now()->subYears(18)->toDateString(),
                'after:'.now()->subYears(100)->toDateString(),
            ],
            'address' => ['required', 'string', 'min:10', 'max:500'],
            'province' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'vehicle_type' => ['required', Rule::in(['motorcycle', 'car', 'bicycle', 'walk'])],
            'vehicle_plate' => ['nullable', 'required_if:vehicle_type,motorcycle,car', 'string', 'max:20'],
            'vehicle_brand' => ['nullable', 'string', 'max:100'],
            'vehicle_color' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * ข้อความภาษาไทย (โปรเจกต์ไม่มี lang/th/validation.php)
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'กรุณากรอกชื่อ-นามสกุล',
            'full_name.min' => 'ชื่อ-นามสกุลสั้นเกินไป',
            'full_name.max' => 'ชื่อ-นามสกุลยาวเกินไป',
            'phone.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            'phone.regex' => 'เบอร์โทรศัพท์ไม่ถูกต้อง (ตัวเลข 9-10 หลัก ขึ้นต้นด้วย 0)',
            'id_card_number.required' => 'กรุณากรอกเลขบัตรประชาชน',
            'id_card_number.unique' => 'เลขบัตรประชาชนนี้ถูกใช้สมัครไรเดอร์แล้ว',
            'birth_date.required' => 'กรุณาระบุวันเกิด',
            'birth_date.date' => 'วันเกิดไม่ถูกต้อง',
            'birth_date.before_or_equal' => 'ผู้สมัครไรเดอร์ต้องมีอายุอย่างน้อย 18 ปีบริบูรณ์',
            'birth_date.after' => 'วันเกิดไม่ถูกต้อง',
            'address.required' => 'กรุณากรอกที่อยู่',
            'address.min' => 'กรุณากรอกที่อยู่ให้ครบถ้วน',
            'address.max' => 'ที่อยู่ยาวเกินไป',
            'province.max' => 'ชื่อจังหวัดยาวเกินไป',
            'district.max' => 'ชื่ออำเภอ/เขตยาวเกินไป',
            'vehicle_type.required' => 'กรุณาเลือกประเภทยานพาหนะ',
            'vehicle_type.in' => 'ประเภทยานพาหนะไม่ถูกต้อง',
            'vehicle_plate.required_if' => 'กรุณากรอกทะเบียนรถ',
            'vehicle_plate.max' => 'ทะเบียนรถยาวเกินไป',
            'vehicle_brand.max' => 'ยี่ห้อรถยาวเกินไป',
            'vehicle_color.max' => 'สีรถยาวเกินไป',
            'string' => 'ข้อมูลไม่ถูกต้อง',
        ];
    }

    /**
     * API ได้ JSON ตามมาตรฐานโปรเจกต์ / เว็บ redirect กลับตามปกติ
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson() || $this->is('api/*')) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first() ?: 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors()->toArray(),
                'data' => null,
            ], 422));
        }

        parent::failedValidation($validator);
    }

    /**
     * ข้อมูลที่พร้อมบันทึกลงตาราง riders
     *
     * @return array<string, mixed>
     */
    public function riderData(): array
    {
        $data = $this->validated();

        return [
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'id_card_number' => $data['id_card_number'],
            'birth_date' => $data['birth_date'],
            'address' => $data['address'],
            'province' => $data['province'] ?? null,
            'district' => $data['district'] ?? null,
            'vehicle_type' => $data['vehicle_type'],
            'vehicle_plate' => ($data['vehicle_plate'] ?? '') !== '' ? $data['vehicle_plate'] : null,
            'vehicle_brand' => ($data['vehicle_brand'] ?? '') !== '' ? $data['vehicle_brand'] : null,
            'vehicle_color' => ($data['vehicle_color'] ?? '') !== '' ? $data['vehicle_color'] : null,
        ];
    }
}
