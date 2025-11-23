<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\LineSignupFlow;

return new class extends Migration
{
    /**
     * เพิ่มขั้นตอน sponsor_code ใน LINE Signup Flow
     *
     * ขั้นตอนนี้เป็น optional (ข้ามได้) เพื่อให้ user กรอกรหัสผู้แนะนำได้
     * ถ้าไม่กรอก → ใช้ Super Admin auto-placement
     * ถ้ากรอก → ใช้ sponsor ที่ระบุ
     *
     * @return void
     */
    public function up(): void
    {
        // เช็คว่ามี step sponsor_code อยู่แล้วหรือยัง
        $existingSponsorStep = LineSignupFlow::where('step_key', 'sponsor_code')->first();

        if ($existingSponsorStep) {
            return;
        }

        // 1. อัพเดท address step ให้ชี้ไปที่ sponsor_code
        $addressStep = LineSignupFlow::where('step_key', 'address')->first();
        if ($addressStep) {
            $addressStep->update([
                'next_step_key' => 'sponsor_code',
            ]);
        }

        // 2. อัพเดท step_order ของขั้นตอนถัดไป +1
        LineSignupFlow::where('step_order', '>=', 6)
            ->increment('step_order', 1);

        // 3. สร้าง sponsor_code step ใหม่
        LineSignupFlow::create([
            'name' => 'ขั้นตอนที่ 6: รหัสผู้แนะนำ (Optional)',
            'step_key' => 'sponsor_code',
            'step_order' => 6,
            'message_text' => '👥 มีรหัสผู้แนะนำหรือไม่?

หากคุณมีรหัสผู้แนะนำจากทีมของคุณ กรุณากรอกที่นี่
เช่น: ABC12345 หรือ REF-001

💡 ถ้าไม่มีรหัสผู้แนะนำ สามารถกด "ข้าม" ได้เลย
ระบบจะจัดทีมให้อัตโนมัติตามการตั้งค่า',
            'input_type' => 'text',
            'validation_rules' => [
                'required' => false,
                'min_length' => 3,
                'max_length' => 50,
            ],
            'validation_error_message' => '❌ รหัสผู้แนะนำไม่ถูกต้อง กรุณากรอกใหม่ (3-50 ตัวอักษร)',
            'next_step_key' => 'consent',
            'quick_reply_options' => [
                ['label' => '⏭️ ข้าม (ไม่มี)', 'value' => 'ข้าม'],
                ['label' => '👥 กรอกรหัส', 'value' => 'กรอก'],
            ],
            'is_active' => true,
            'is_skippable' => true, // ← สำคัญ: ข้ามได้!
            'require_ai' => false,
        ]);
    }

    /**
     * ลบขั้นตอน sponsor_code และคืนค่า step_order เดิม
     *
     * @return void
     */
    public function down(): void
    {
        // 1. ลบ sponsor_code step
        $sponsorStep = LineSignupFlow::where('step_key', 'sponsor_code')->first();
        if ($sponsorStep) {
            $sponsorStep->delete();
        }

        // 2. คืนค่า address step ให้ชี้ไปที่ consent
        $addressStep = LineSignupFlow::where('step_key', 'address')->first();
        if ($addressStep) {
            $addressStep->update([
                'next_step_key' => 'consent',
            ]);
        }

        // 3. คืนค่า step_order ของขั้นตอนถัดไป -1
        LineSignupFlow::where('step_order', '>=', 7)
            ->decrement('step_order', 1);
    }
};
