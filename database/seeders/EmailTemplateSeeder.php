<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if email templates already exist
        $existingTemplatesCount = EmailTemplate::whereIn('name', [
            'welcome_email',
            'password_reset',
            'commission_earned'
        ])->count();

        if ($existingTemplatesCount > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh install mode - seed all templates
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: Seeding all email templates...');

        $templates = $this->getAllTemplates();

        foreach ($templates as $template) {
            EmailTemplate::create($template);
        }

        $this->command->info('✅ Email templates seeded successfully: ' . count($templates) . ' templates');
    }

    /**
     * Update mode - add only missing templates
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  Existing email templates detected!');
        $this->command->info('   Running in UPDATE mode (adding missing templates only)...');

        $templates = $this->getAllTemplates();
        $added = 0;
        $skipped = 0;

        foreach ($templates as $template) {
            if (!EmailTemplate::where('name', $template['name'])->exists()) {
                EmailTemplate::create($template);
                $this->command->info("   ➕ Added: {$template['name']}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ Added {$added} new email templates.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing templates (preserved).");
        }
    }

    /**
     * Get all default email templates
     */
    private function getAllTemplates(): array
    {
        return [
            // System Templates
            [
                'name' => 'welcome_email',
                'subject' => 'ยินดีต้อนรับสู่ {{app_name}}',
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #3B82F6;">ยินดีต้อนรับสู่ {{app_name}}!</h1>
    <p>สวัสดี <strong>{{user_name}}</strong>,</p>
    <p>ขอบคุณที่เข้าร่วมเป็นส่วนหนึ่งของ {{app_name}} เรายินดีที่ได้ต้อนรับคุณ!</p>
    <p>คุณสามารถเริ่มต้นใช้งานระบบ Affiliate ของเราได้ทันที:</p>
    <ul>
        <li>สร้างลิงก์ Affiliate ของคุณ</li>
        <li>แชร์ลิงก์ไปยังเพื่อนและครอบครัว</li>
        <li>รับคอมมิชชั่นจากการแนะนำ</li>
    </ul>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{dashboard_url}}" style="background-color: #3B82F6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            เข้าสู่แดชบอร์ด
        </a>
    </div>
    <p>หากมีคำถามใดๆ สามารถติดต่อเราได้ที่ {{support_email}}</p>
    <p style="color: #666; font-size: 14px; margin-top: 30px;">
        ขอแสดงความนับถือ,<br>
        ทีมงาน {{app_name}}
    </p>
</div>',
                'body_text' => 'ยินดีต้อนรับสู่ {{app_name}}!

สวัสดี {{user_name}},

ขอบคุณที่เข้าร่วมเป็นส่วนหนึ่งของ {{app_name}} เรายินดีที่ได้ต้อนรับคุณ!

คุณสามารถเริ่มต้นใช้งานระบบ Affiliate ของเราได้ทันที

เข้าสู่แดชบอร์ด: {{dashboard_url}}

หากมีคำถามใดๆ สามารถติดต่อเราได้ที่ {{support_email}}

ขอแสดงความนับถือ,
ทีมงาน {{app_name}}',
                'category' => 'system',
                'variables' => ['user_name', 'app_name', 'dashboard_url', 'support_email'],
                'is_active' => true,
                'language' => 'th',
                'description' => 'อีเมลต้อนรับสมาชิกใหม่',
            ],

            [
                'name' => 'password_reset',
                'subject' => 'รีเซ็ตรหัสผ่าน - {{app_name}}',
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #EF4444;">รีเซ็ตรหัสผ่าน</h1>
    <p>สวัสดี <strong>{{user_name}}</strong>,</p>
    <p>เราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับบัญชีของคุณ</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{reset_url}}" style="background-color: #EF4444; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            รีเซ็ตรหัสผ่าน
        </a>
    </div>
    <p style="color: #EF4444; font-weight: bold;">⚠️ ลิงก์นี้จะหมดอายุใน {{expiry_time}} นาที</p>
    <p>หากคุณไม่ได้ขอรีเซ็ตรหัสผ่าน กรุณาเพิกเฉยอีเมลนี้</p>
    <p style="color: #666; font-size: 14px; margin-top: 30px;">
        ขอแสดงความนับถือ,<br>
        ทีมงาน {{app_name}}
    </p>
</div>',
                'body_text' => 'รีเซ็ตรหัสผ่าน

สวัสดี {{user_name}},

เราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับบัญชีของคุณ

รีเซ็ตรหัสผ่าน: {{reset_url}}

ลิงก์นี้จะหมดอายุใน {{expiry_time}} นาที

หากคุณไม่ได้ขอรีเซ็ตรหัสผ่าน กรุณาเพิกเฉยอีเมลนี้

ขอแสดงความนับถือ,
ทีมงาน {{app_name}}',
                'category' => 'system',
                'variables' => ['user_name', 'app_name', 'reset_url', 'expiry_time'],
                'is_active' => true,
                'language' => 'th',
                'description' => 'อีเมลรีเซ็ตรหัสผ่าน',
            ],

            // Transactional Templates
            [
                'name' => 'commission_earned',
                'subject' => '🎉 คุณได้รับคอมมิชชั่น {{amount}} บาท',
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #10B981;">🎉 คุณได้รับคอมมิชชั่น!</h1>
    <p>สวัสดี <strong>{{user_name}}</strong>,</p>
    <p>ยินดีด้วย! คุณได้รับคอมมิชชั่นใหม่</p>
    <div style="background-color: #D1FAE5; border-left: 4px solid #10B981; padding: 20px; margin: 20px 0;">
        <h2 style="margin: 0; color: #059669;">฿{{amount}}</h2>
        <p style="margin: 5px 0 0 0; color: #065F46;">จาก {{source}}</p>
    </div>
    <p><strong>รายละเอียด:</strong></p>
    <ul>
        <li>ประเภท: {{commission_type}}</li>
        <li>วันที่: {{date}}</li>
        <li>อ้างอิง: {{reference}}</li>
    </ul>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{wallet_url}}" style="background-color: #10B981; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            ดูกระเป๋าเงิน
        </a>
    </div>
    <p style="color: #666; font-size: 14px; margin-top: 30px;">
        ขอแสดงความนับถือ,<br>
        ทีมงาน {{app_name}}
    </p>
</div>',
                'body_text' => 'คุณได้รับคอมมิชชั่น!

สวัสดี {{user_name}},

ยินดีด้วย! คุณได้รับคอมมิชชั่นใหม่

จำนวน: ฿{{amount}}
จาก: {{source}}

รายละเอียด:
- ประเภท: {{commission_type}}
- วันที่: {{date}}
- อ้างอิง: {{reference}}

ดูกระเป๋าเงิน: {{wallet_url}}

ขอแสดงความนับถือ,
ทีมงาน {{app_name}}',
                'category' => 'transactional',
                'variables' => ['user_name', 'app_name', 'amount', 'source', 'commission_type', 'date', 'reference', 'wallet_url'],
                'is_active' => true,
                'language' => 'th',
                'description' => 'แจ้งเตือนการได้รับคอมมิชชั่น',
            ],

            [
                'name' => 'withdrawal_approved',
                'subject' => '✅ คำขอถอนเงินของคุณได้รับการอนุมัติ',
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #10B981;">✅ คำขอถอนเงินได้รับการอนุมัติ</h1>
    <p>สวัสดี <strong>{{user_name}}</strong>,</p>
    <p>คำขอถอนเงินของคุณได้รับการอนุมัติแล้ว</p>
    <div style="background-color: #F3F4F6; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <p><strong>จำนวนเงิน:</strong> ฿{{amount}}</p>
        <p><strong>วิธีการโอน:</strong> {{payment_method}}</p>
        <p><strong>บัญชีปลายทาง:</strong> {{account_number}}</p>
        <p><strong>วันที่ประมวลผล:</strong> {{processed_date}}</p>
    </div>
    <p>เงินจะถูกโอนเข้าบัญชีของคุณภายใน 1-3 วันทำการ</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{withdrawal_url}}" style="background-color: #3B82F6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            ดูรายละเอียด
        </a>
    </div>
    <p style="color: #666; font-size: 14px; margin-top: 30px;">
        ขอแสดงความนับถือ,<br>
        ทีมงาน {{app_name}}
    </p>
</div>',
                'body_text' => 'คำขอถอนเงินได้รับการอนุมัติ

สวัสดี {{user_name}},

คำขอถอนเงินของคุณได้รับการอนุมัติแล้ว

จำนวนเงิน: ฿{{amount}}
วิธีการโอน: {{payment_method}}
บัญชีปลายทาง: {{account_number}}
วันที่ประมวลผล: {{processed_date}}

เงินจะถูกโอนเข้าบัญชีของคุณภายใน 1-3 วันทำการ

ดูรายละเอียด: {{withdrawal_url}}

ขอแสดงความนับถือ,
ทีมงาน {{app_name}}',
                'category' => 'transactional',
                'variables' => ['user_name', 'app_name', 'amount', 'payment_method', 'account_number', 'processed_date', 'withdrawal_url'],
                'is_active' => true,
                'language' => 'th',
                'description' => 'แจ้งเตือนการอนุมัติถอนเงิน',
            ],

            [
                'name' => 'withdrawal_rejected',
                'subject' => '❌ คำขอถอนเงินถูกปฏิเสธ',
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #EF4444;">❌ คำขอถอนเงินถูกปฏิเสธ</h1>
    <p>สวัสดี <strong>{{user_name}}</strong>,</p>
    <p>เราเสียใจที่ต้องแจ้งให้ทราบว่าคำขอถอนเงินของคุณถูกปฏิเสธ</p>
    <div style="background-color: #FEE2E2; border-left: 4px solid #EF4444; padding: 20px; margin: 20px 0;">
        <p><strong>จำนวนเงิน:</strong> ฿{{amount}}</p>
        <p><strong>เหตุผล:</strong> {{rejection_reason}}</p>
    </div>
    <p>ยอดเงินจะถูกคืนเข้ากระเป๋าเงินของคุณ และคุณสามารถยื่นคำขอถอนใหม่ได้</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{wallet_url}}" style="background-color: #3B82F6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            ดูกระเป๋าเงิน
        </a>
    </div>
    <p style="color: #666; font-size: 14px; margin-top: 30px;">
        ขอแสดงความนับถือ,<br>
        ทีมงาน {{app_name}}
    </p>
</div>',
                'body_text' => 'คำขอถอนเงินถูกปฏิเสธ

สวัสดี {{user_name}},

เราเสียใจที่ต้องแจ้งให้ทราบว่าคำขอถอนเงินของคุณถูกปฏิเสธ

จำนวนเงิน: ฿{{amount}}
เหตุผล: {{rejection_reason}}

ยอดเงินจะถูกคืนเข้ากระเป๋าเงินของคุณ

ดูกระเป๋าเงิน: {{wallet_url}}

ขอแสดงความนับถือ,
ทีมงาน {{app_name}}',
                'category' => 'transactional',
                'variables' => ['user_name', 'app_name', 'amount', 'rejection_reason', 'wallet_url'],
                'is_active' => true,
                'language' => 'th',
                'description' => 'แจ้งเตือนการปฏิเสธถอนเงิน',
            ],

            // Security Templates
            [
                'name' => 'security_alert_login',
                'subject' => '🔐 แจ้งเตือนการเข้าสู่ระบบใหม่',
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #F59E0B;">🔐 การเข้าสู่ระบบใหม่</h1>
    <p>สวัสดี <strong>{{user_name}}</strong>,</p>
    <p>เราตรวจพบการเข้าสู่ระบบใหม่ในบัญชีของคุณ</p>
    <div style="background-color: #FEF3C7; border-left: 4px solid #F59E0B; padding: 20px; margin: 20px 0;">
        <p><strong>IP Address:</strong> {{ip_address}}</p>
        <p><strong>Location:</strong> {{location}}</p>
        <p><strong>Device:</strong> {{device}}</p>
        <p><strong>เวลา:</strong> {{login_time}}</p>
    </div>
    <p>หากนี่ไม่ใช่คุณ กรุณาเปลี่ยนรหัสผ่านทันที</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{security_url}}" style="background-color: #EF4444; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            ตรวจสอบความปลอดภัย
        </a>
    </div>
    <p style="color: #666; font-size: 14px; margin-top: 30px;">
        ขอแสดงความนับถือ,<br>
        ทีมงาน {{app_name}}
    </p>
</div>',
                'body_text' => 'การเข้าสู่ระบบใหม่

สวัสดี {{user_name}},

เราตรวจพบการเข้าสู่ระบบใหม่ในบัญชีของคุณ

IP Address: {{ip_address}}
Location: {{location}}
Device: {{device}}
เวลา: {{login_time}}

หากนี่ไม่ใช่คุณ กรุณาเปลี่ยนรหัสผ่านทันที

ตรวจสอบความปลอดภัย: {{security_url}}

ขอแสดงความนับถือ,
ทีมงาน {{app_name}}',
                'category' => 'system',
                'variables' => ['user_name', 'app_name', 'ip_address', 'location', 'device', 'login_time', 'security_url'],
                'is_active' => true,
                'language' => 'th',
                'description' => 'แจ้งเตือนการเข้าสู่ระบบใหม่',
            ],

            [
                'name' => 'ip_auto_banned',
                'subject' => '⚠️ แจ้งเตือน: IP ถูก Ban อัตโนมัติ',
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #EF4444;">⚠️ IP ถูก Ban อัตโนมัติ</h1>
    <p>เรียน Admin,</p>
    <p>ระบบตรวจพบกิจกรรมที่น่าสงสัยและได้ทำการ Ban IP อัตโนมัติ</p>
    <div style="background-color: #FEE2E2; border-left: 4px solid #EF4444; padding: 20px; margin: 20px 0;">
        <p><strong>IP Address:</strong> {{ip_address}}</p>
        <p><strong>เหตุผล:</strong> {{ban_reason}}</p>
        <p><strong>จำนวนครั้งที่ผิดปกติ:</strong> {{violation_count}}</p>
        <p><strong>ระยะเวลา Ban:</strong> {{ban_duration}} นาที</p>
    </div>
    <p><strong>กิจกรรมที่ตรวจพบ:</strong></p>
    <ul>
        <li>{{activity_details}}</li>
    </ul>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{security_logs_url}}" style="background-color: #EF4444; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            ดู Security Logs
        </a>
    </div>
    <p style="color: #666; font-size: 14px; margin-top: 30px;">
        ระบบรักษาความปลอดภัย<br>
        {{app_name}}
    </p>
</div>',
                'body_text' => 'IP ถูก Ban อัตโนมัติ

เรียน Admin,

ระบบตรวจพบกิจกรรมที่น่าสงสัยและได้ทำการ Ban IP อัตโนมัติ

IP Address: {{ip_address}}
เหตุผล: {{ban_reason}}
จำนวนครั้งที่ผิดปกติ: {{violation_count}}
ระยะเวลา Ban: {{ban_duration}} นาที

กิจกรรมที่ตรวจพบ:
{{activity_details}}

ดู Security Logs: {{security_logs_url}}

ระบบรักษาความปลอดภัย
{{app_name}}',
                'category' => 'system',
                'variables' => ['app_name', 'ip_address', 'ban_reason', 'violation_count', 'ban_duration', 'activity_details', 'security_logs_url'],
                'is_active' => true,
                'language' => 'th',
                'description' => 'แจ้งเตือน Admin เมื่อมี IP ถูก Ban',
            ],

            // Marketing Templates
            [
                'name' => 'weekly_report',
                'subject' => '📊 รายงานสรุปประจำสัปดาห์ - {{week_period}}',
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #3B82F6;">📊 รายงานสรุปประจำสัปดาห์</h1>
    <p>สวัสดี <strong>{{user_name}}</strong>,</p>
    <p>นี่คือผลงานของคุณในสัปดาห์ที่ผ่านมา ({{week_period}})</p>

    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
        <h2 style="margin: 0 0 15px 0;">💰 รายได้รวม</h2>
        <h1 style="margin: 0; font-size: 36px;">฿{{total_earnings}}</h1>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0;">
        <div style="background-color: #DBEAFE; padding: 15px; border-radius: 8px;">
            <p style="margin: 0; color: #1E40AF; font-size: 14px;">คอมมิชชั่น</p>
            <h3 style="margin: 5px 0 0 0; color: #1E3A8A;">฿{{commissions}}</h3>
        </div>
        <div style="background-color: #D1FAE5; padding: 15px; border-radius: 8px;">
            <p style="margin: 0; color: #065F46; font-size: 14px;">การแนะนำ</p>
            <h3 style="margin: 5px 0 0 0; color: #064E3B;">{{referrals}} คน</h3>
        </div>
    </div>

    <p><strong>🎯 เป้าหมายต่อไป:</strong></p>
    <ul>
        <li>เพิ่มการแนะนำอีก {{remaining_referrals}} คนเพื่อปลดล็อคโบนัส</li>
        <li>สร้างรายได้ให้ถึง ฿{{next_milestone}} เพื่อเลื่อนระดับ</li>
    </ul>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{dashboard_url}}" style="background-color: #3B82F6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            ดูรายงานเต็ม
        </a>
    </div>

    <p style="color: #666; font-size: 14px; margin-top: 30px;">
        ขอแสดงความนับถือ,<br>
        ทีมงาน {{app_name}}
    </p>
</div>',
                'body_text' => 'รายงานสรุปประจำสัปดาห์

สวัสดี {{user_name}},

นี่คือผลงานของคุณในสัปดาห์ที่ผ่านมา ({{week_period}})

รายได้รวม: ฿{{total_earnings}}
คอมมิชชั่น: ฿{{commissions}}
การแนะนำ: {{referrals}} คน

เป้าหมายต่อไป:
- เพิ่มการแนะนำอีก {{remaining_referrals}} คนเพื่อปลดล็อคโบนัส
- สร้างรายได้ให้ถึง ฿{{next_milestone}} เพื่อเลื่อนระดับ

ดูรายงานเต็ม: {{dashboard_url}}

ขอแสดงความนับถือ,
ทีมงาน {{app_name}}',
                'category' => 'marketing',
                'variables' => ['user_name', 'app_name', 'week_period', 'total_earnings', 'commissions', 'referrals', 'remaining_referrals', 'next_milestone', 'dashboard_url'],
                'is_active' => true,
                'language' => 'th',
                'description' => 'รายงานสรุปประจำสัปดาห์',
            ],
        ];
    }
}
