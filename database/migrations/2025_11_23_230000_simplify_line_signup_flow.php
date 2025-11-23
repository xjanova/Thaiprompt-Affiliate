<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\LineSignupFlow;

return new class extends Migration
{
    /**
     * ปรับ LINE Signup Flow ให้ง่ายที่สุด - ใช้ข้อมูล LINE เป็นหลัก
     *
     * Flow ใหม่ (4 steps):
     * 1. welcome - แสดงข้อมูลจาก LINE + ยืนยันสมัคร
     * 2. sponsor_code (optional) - รหัสผู้แนะนำ
     * 3. consent - ยินยอม PDPA
     * 4. success - สมัครสำเร็จ + ส่งลิงก์ LINE Login
     *
     * ลบออก: phone, email, full_name, address, completion
     *
     * @return void
     */
    public function up(): void
    {
        // ลบขั้นตอนที่ไม่จำเป็น
        $stepsToDelete = ['phone', 'email', 'full_name', 'address', 'completion'];

        foreach ($stepsToDelete as $stepKey) {
            $step = LineSignupFlow::where('step_key', $stepKey)->first();
            if ($step) {
                $step->delete();
            }
        }

        // อัพเดท welcome step
        $welcomeStep = LineSignupFlow::where('step_key', 'welcome')->first();
        if ($welcomeStep) {
            $welcomeStep->update([
                'step_order' => 1,
                'message_text' => '👋 ยินดีต้อนรับ {{prospect_name}}!

📸 เราได้รับข้อมูลจาก LINE ของคุณแล้ว:
• ชื่อ: {{prospect_name}}
• รูปโปรไฟล์: มี ✅

ต้องการสมัครสมาชิกใช่ไหม?

💡 หลังสมัครเสร็จ คุณสามารถ:
✅ เข้าระบบด้วย LINE ได้ทันที
✅ เพิ่มข้อมูลเพิ่มเติมในระบบ
✅ เริ่มใช้งานได้ทันที',
                'next_step_key' => 'sponsor_code',
                'quick_reply_options' => [
                    ['label' => '✅ สมัครเลย', 'value' => 'สมัคร'],
                    ['label' => '❌ ยกเลิก', 'value' => 'ยกเลิก'],
                ],
            ]);
        }

        // อัพเดท sponsor_code step
        $sponsorStep = LineSignupFlow::where('step_key', 'sponsor_code')->first();
        if ($sponsorStep) {
            $sponsorStep->update([
                'step_order' => 2,
                'next_step_key' => 'consent',
            ]);
        }

        // อัพเดท consent step
        $consentStep = LineSignupFlow::where('step_key', 'consent')->first();
        if ($consentStep) {
            $consentStep->update([
                'step_order' => 3,
                'next_step_key' => 'success',
                'message_text' => '📋 ยินยอมการใช้ข้อมูลส่วนบุคคล

เราจะใช้ข้อมูลของคุณเพื่อ:
• สมัครสมาชิก
• ติดต่อและสนับสนุน
• ส่งข้อมูลข่าวสารและโปรโมชัน

✅ คุณยินยอมให้เราใช้ข้อมูลของคุณหรือไม่?',
                'conditional_next_steps' => [
                    [
                        'field' => 'consent',
                        'operator' => '==',
                        'value' => 'ไม่ยินยอม',
                        'next_step_key' => 'cancel',
                    ],
                ],
            ]);
        }

        // อัพเดท success step
        $successStep = LineSignupFlow::where('step_key', 'success')->first();
        if ($successStep) {
            $successStep->update([
                'step_order' => 4,
                'message_text' => '🎉 ยินดีด้วย! สมัครสมาชิกสำเร็จแล้ว!

📋 ข้อมูลของคุณ:
• ชื่อ: {{prospect_name}}
• รหัสสมาชิก: {{member_code}}
• รหัสแนะนำ: {{referral_code}}
• ผู้แนะนำ: {{sponsor_name}}

🚀 เข้าสู่ระบบได้เลยที่นี่:
{{dashboard_link}}

💡 ล็อกอินด้วย LINE ได้ทันที!
กดลิงก์ด้านบนเพื่อเริ่มใช้งาน

📝 อย่าลืม:
• เพิ่มเบอร์โทรศัพท์ในโปรไฟล์
• เพิ่มที่อยู่สำหรับจัดส่ง
• เพิ่มข้อมูลธนาคารรับเงิน',
            ]);
        }
    }

    /**
     * คืนค่า flow เดิม
     *
     * @return void
     */
    public function down(): void
    {
        // Manual restoration required - please run fresh seed
        // php artisan migrate:fresh --seed
    }
};
