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
            $this->command->warn('⚠️  sponsor_code step already exists, skipping...');
            return;
        }

        $this->command->info('🔧 Adding sponsor_code step to LINE Signup Flow...');

        // 1. อัพเดท address step ให้ชี้ไปที่ sponsor_code
        $addressStep = LineSignupFlow::where('step_key', 'address')->first();
        if ($addressStep) {
            $addressStep->update([
                'next_step_key' => 'sponsor_code',
            ]);
            $this->command->info('✅ Updated address step next_step_key → sponsor_code');
        }

        // 2. อัพเดท step_order ของขั้นตอนถัดไป +1
        LineSignupFlow::where('step_order', '>=', 6)
            ->increment('step_order', 1);
        $this->command->info('✅ Updated step_order for existing steps (+1)');

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

        $this->command->info('✅ Created sponsor_code step (order: 6, skippable: yes)');
        $this->command->line('');
        $this->command->info('📋 Updated LINE Signup Flow:');
        $this->command->line('  1. welcome');
        $this->command->line('  2. phone');
        $this->command->line('  3. email');
        $this->command->line('  4. full_name');
        $this->command->line('  5. address');
        $this->command->line('  6. sponsor_code (NEW - Optional) ⭐');
        $this->command->line('  7. consent');
        $this->command->line('  8. completion');
        $this->command->line('  9. success');
        $this->command->line('');
        $this->command->info('💡 User can skip sponsor_code by clicking "ข้าม"');
    }

    /**
     * ลบขั้นตอน sponsor_code และคืนค่า step_order เดิม
     *
     * @return void
     */
    public function down(): void
    {
        $this->command->info('🔧 Removing sponsor_code step...');

        // 1. ลบ sponsor_code step
        $sponsorStep = LineSignupFlow::where('step_key', 'sponsor_code')->first();
        if ($sponsorStep) {
            $sponsorStep->delete();
            $this->command->info('✅ Deleted sponsor_code step');
        }

        // 2. คืนค่า address step ให้ชี้ไปที่ consent
        $addressStep = LineSignupFlow::where('step_key', 'address')->first();
        if ($addressStep) {
            $addressStep->update([
                'next_step_key' => 'consent',
            ]);
            $this->command->info('✅ Restored address step next_step_key → consent');
        }

        // 3. คืนค่า step_order ของขั้นตอนถัดไป -1
        LineSignupFlow::where('step_order', '>=', 7)
            ->decrement('step_order', 1);
        $this->command->info('✅ Restored step_order for existing steps (-1)');
    }
};
