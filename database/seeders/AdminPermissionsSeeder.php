<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\Concerns\SafeSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder สำหรับสร้าง Permissions ครอบคลุมทุก modules ในระบบ Admin
 *
 * รองรับ 30+ categories ตามโครงสร้างเมนูแอดมินปัจจุบัน
 *
 * @version 3.197.0
 */
class AdminPermissionsSeeder extends Seeder
{
    use SafeSeeder;

    /**
     * Run the database seeds.
     *
     * สร้าง permissions ใหม่สำหรับทุก modules
     */
    public function run(): void
    {
        // ตรวจสอบว่าตาราง permissions มีอยู่หรือไม่
        if (! $this->requireTable('permissions', 'AdminPermissionsSeeder')) {
            return;
        }

        $this->command->info('🔐 กำลังสร้าง Admin Permissions...');

        $permissions = $this->getPermissions();
        $created = 0;
        $skipped = 0;

        foreach ($permissions as $permission) {
            // ตรวจสอบว่า permission มีอยู่แล้วหรือไม่
            $exists = Permission::where('name', $permission['name'])->exists();

            if (! $exists) {
                Permission::create([
                    'name' => $permission['name'],
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description'],
                    'category' => $permission['category'],
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }

        $this->command->info("   ✅ สร้าง permissions ใหม่: {$created} รายการ");
        $this->command->info("   ⏭️ ข้าม permissions ที่มีอยู่แล้ว: {$skipped} รายการ");

        // กำหนด permissions ให้กับ roles
        $this->assignPermissionsToRoles();

        $this->command->info('✅ สร้าง Admin Permissions สำเร็จ!');
    }

    /**
     * รายการ permissions ทั้งหมด
     */
    private function getPermissions(): array
    {
        return [
            // ============================================
            // TICKET SUPPORT (tickets)
            // ============================================
            ['name' => 'view_tickets', 'display_name' => 'ดู Ticket', 'category' => 'tickets', 'description' => 'ดูรายการ Ticket Support'],
            ['name' => 'manage_tickets', 'display_name' => 'จัดการ Ticket', 'category' => 'tickets', 'description' => 'ตอบและจัดการ Ticket Support'],
            ['name' => 'delete_tickets', 'display_name' => 'ลบ Ticket', 'category' => 'tickets', 'description' => 'ลบ Ticket ออกจากระบบ'],

            // ============================================
            // AI CORE (ai_core)
            // ============================================
            ['name' => 'view_ai_core', 'display_name' => 'ดู AI Core', 'category' => 'ai_core', 'description' => 'เข้าถึง AI Core Dashboard'],
            ['name' => 'manage_ai_features', 'display_name' => 'จัดการ AI Features', 'category' => 'ai_core', 'description' => 'จัดการ AI Features ทั้งหมด'],
            ['name' => 'manage_ai_tenants', 'display_name' => 'จัดการ AI Tenants', 'category' => 'ai_core', 'description' => 'จัดการ Multi-tenancy ของ AI'],
            ['name' => 'manage_ai_quotas', 'display_name' => 'จัดการ AI Quotas', 'category' => 'ai_core', 'description' => 'จัดการโควต้าการใช้งาน AI'],
            ['name' => 'manage_ai_schedules', 'display_name' => 'จัดการ AI Schedules', 'category' => 'ai_core', 'description' => 'จัดการตารางเวลา AI'],
            ['name' => 'view_ai_analytics', 'display_name' => 'ดูสถิติ AI', 'category' => 'ai_core', 'description' => 'ดูสถิติการใช้งาน AI'],
            ['name' => 'manage_ai_settings', 'display_name' => 'ตั้งค่า AI Core', 'category' => 'ai_core', 'description' => 'จัดการตั้งค่า AI Core ทั้งหมด'],

            // ============================================
            // AI BOTS (ai_bots)
            // ============================================
            ['name' => 'view_ai_bots', 'display_name' => 'ดู AI Bots', 'category' => 'ai_bots', 'description' => 'ดูรายการ AI Bots'],
            ['name' => 'manage_ai_bots', 'display_name' => 'จัดการ AI Bots', 'category' => 'ai_bots', 'description' => 'สร้าง แก้ไข และลบ AI Bots'],
            ['name' => 'manage_ai_providers', 'display_name' => 'จัดการ AI Providers', 'category' => 'ai_bots', 'description' => 'จัดการผู้ให้บริการ AI'],
            ['name' => 'manage_ai_installation', 'display_name' => 'จัดการการติดตั้ง AI', 'category' => 'ai_bots', 'description' => 'จัดการการติดตั้ง AI Bots'],
            ['name' => 'manage_knowledge_bases', 'display_name' => 'จัดการ Knowledge Bases', 'category' => 'ai_bots', 'description' => 'จัดการฐานความรู้ AI'],
            ['name' => 'view_ai_monitoring', 'display_name' => 'ดู AI Monitoring', 'category' => 'ai_bots', 'description' => 'ดูการทำงานของ AI Bots'],

            // ============================================
            // CHATBOT SYSTEM (chatbot)
            // ============================================
            ['name' => 'view_chatbots', 'display_name' => 'ดู Chatbots', 'category' => 'chatbot', 'description' => 'ดูรายการ Chatbots'],
            ['name' => 'manage_chatbots', 'display_name' => 'จัดการ Chatbots', 'category' => 'chatbot', 'description' => 'สร้างและจัดการ Chatbots'],
            ['name' => 'manage_chatbot_marketplace', 'display_name' => 'จัดการ Bot Marketplace', 'category' => 'chatbot', 'description' => 'จัดการ Bot Marketplace'],

            // ============================================
            // SMART SLIDERS (smart_sliders)
            // ============================================
            ['name' => 'view_smart_sliders', 'display_name' => 'ดู Smart Sliders', 'category' => 'smart_sliders', 'description' => 'ดูรายการ Smart Sliders'],
            ['name' => 'manage_smart_sliders', 'display_name' => 'จัดการ Smart Sliders', 'category' => 'smart_sliders', 'description' => 'สร้างและจัดการ Smart Sliders'],

            // ============================================
            // HOTEL MANAGEMENT (hotels)
            // ============================================
            ['name' => 'view_hotels', 'display_name' => 'ดูโรงแรม', 'category' => 'hotels', 'description' => 'ดูรายการโรงแรม'],
            ['name' => 'manage_hotels', 'display_name' => 'จัดการโรงแรม', 'category' => 'hotels', 'description' => 'จัดการโรงแรมทั้งหมด'],
            ['name' => 'manage_hotel_owners', 'display_name' => 'จัดการเจ้าของโรงแรม', 'category' => 'hotels', 'description' => 'จัดการเจ้าของโรงแรม'],
            ['name' => 'manage_hotel_bookings', 'display_name' => 'จัดการการจอง', 'category' => 'hotels', 'description' => 'จัดการการจองโรงแรม'],
            ['name' => 'manage_hotel_reviews', 'display_name' => 'จัดการรีวิว', 'category' => 'hotels', 'description' => 'จัดการรีวิวโรงแรม'],
            ['name' => 'manage_hotel_facilities', 'display_name' => 'จัดการสิ่งอำนวยความสะดวก', 'category' => 'hotels', 'description' => 'จัดการสิ่งอำนวยความสะดวก'],
            ['name' => 'manage_hotel_offers', 'display_name' => 'จัดการโปรโมชั่นโรงแรม', 'category' => 'hotels', 'description' => 'จัดการโปรโมชั่นพิเศษ'],

            // ============================================
            // SERVICE BOOKING (service_booking)
            // ============================================
            ['name' => 'view_service_bookings', 'display_name' => 'ดูการจองบริการ', 'category' => 'service_booking', 'description' => 'ดูรายการจองบริการ'],
            ['name' => 'manage_service_bookings', 'display_name' => 'จัดการการจองบริการ', 'category' => 'service_booking', 'description' => 'จัดการการจองบริการ'],
            ['name' => 'manage_service_categories', 'display_name' => 'จัดการหมวดหมู่บริการ', 'category' => 'service_booking', 'description' => 'จัดการหมวดหมู่บริการ'],
            ['name' => 'manage_services', 'display_name' => 'จัดการบริการ', 'category' => 'service_booking', 'description' => 'จัดการบริการทั้งหมด'],
            ['name' => 'manage_service_providers', 'display_name' => 'จัดการผู้ให้บริการ', 'category' => 'service_booking', 'description' => 'จัดการผู้ให้บริการ'],
            ['name' => 'manage_service_pricing', 'display_name' => 'จัดการราคาบริการ', 'category' => 'service_booking', 'description' => 'จัดการกฎราคาบริการ'],

            // ============================================
            // E-COMMERCE (ecommerce)
            // ============================================
            ['name' => 'view_ecommerce', 'display_name' => 'ดูอีคอมเมิร์ซ', 'category' => 'ecommerce', 'description' => 'เข้าถึง E-commerce Dashboard'],
            ['name' => 'manage_products', 'display_name' => 'จัดการสินค้า', 'category' => 'ecommerce', 'description' => 'จัดการสินค้าทั้งหมด'],
            ['name' => 'manage_orders', 'display_name' => 'จัดการออเดอร์', 'category' => 'ecommerce', 'description' => 'จัดการออเดอร์ทั้งหมด'],
            ['name' => 'manage_ecommerce_categories', 'display_name' => 'จัดการหมวดหมู่สินค้า', 'category' => 'ecommerce', 'description' => 'จัดการหมวดหมู่สินค้า'],
            ['name' => 'manage_product_reviews', 'display_name' => 'จัดการรีวิวสินค้า', 'category' => 'ecommerce', 'description' => 'จัดการรีวิวสินค้า'],
            ['name' => 'manage_ecommerce_settings', 'display_name' => 'ตั้งค่าอีคอมเมิร์ซ', 'category' => 'ecommerce', 'description' => 'จัดการตั้งค่าอีคอมเมิร์ซ'],

            // ============================================
            // POINT OF SALE (pos)
            // ============================================
            ['name' => 'view_pos', 'display_name' => 'ดู POS', 'category' => 'pos', 'description' => 'เข้าถึง POS Dashboard'],
            ['name' => 'manage_pos', 'display_name' => 'จัดการ POS', 'category' => 'pos', 'description' => 'จัดการระบบ POS'],
            ['name' => 'manage_pos_devices', 'display_name' => 'จัดการอุปกรณ์ POS', 'category' => 'pos', 'description' => 'จัดการอุปกรณ์ POS'],
            ['name' => 'view_pos_transactions', 'display_name' => 'ดูรายการ POS', 'category' => 'pos', 'description' => 'ดูรายการธุรกรรม POS'],
            ['name' => 'view_pos_analytics', 'display_name' => 'ดูสถิติ POS', 'category' => 'pos', 'description' => 'ดูสถิติ POS'],

            // ============================================
            // NFC CARDS (nfc)
            // ============================================
            ['name' => 'view_nfc', 'display_name' => 'ดู NFC', 'category' => 'nfc', 'description' => 'เข้าถึงระบบ NFC'],
            ['name' => 'manage_nfc_cards', 'display_name' => 'จัดการบัตร NFC', 'category' => 'nfc', 'description' => 'จัดการบัตร NFC'],
            ['name' => 'view_nfc_transactions', 'display_name' => 'ดูรายการ NFC', 'category' => 'nfc', 'description' => 'ดูรายการธุรกรรม NFC'],

            // ============================================
            // CRYPTOCURRENCY (crypto)
            // ============================================
            ['name' => 'view_crypto', 'display_name' => 'ดูคริปโต', 'category' => 'crypto', 'description' => 'เข้าถึง Crypto Dashboard'],
            ['name' => 'manage_crypto_wallets', 'display_name' => 'จัดการกระเป๋าคริปโต', 'category' => 'crypto', 'description' => 'จัดการกระเป๋าคริปโต'],
            ['name' => 'view_crypto_transactions', 'display_name' => 'ดูรายการคริปโต', 'category' => 'crypto', 'description' => 'ดูรายการธุรกรรมคริปโต'],
            ['name' => 'approve_crypto_withdrawals', 'display_name' => 'อนุมัติถอนคริปโต', 'category' => 'crypto', 'description' => 'อนุมัติการถอนคริปโต'],
            ['name' => 'manage_crypto_currencies', 'display_name' => 'จัดการสกุลเงินคริปโต', 'category' => 'crypto', 'description' => 'จัดการสกุลเงินคริปโต'],
            ['name' => 'manage_crypto_settings', 'display_name' => 'ตั้งค่าคริปโต', 'category' => 'crypto', 'description' => 'จัดการตั้งค่าคริปโต'],

            // ============================================
            // MLM SYSTEM (mlm)
            // ============================================
            ['name' => 'view_mlm', 'display_name' => 'ดู MLM', 'category' => 'mlm', 'description' => 'เข้าถึง MLM Dashboard'],
            ['name' => 'manage_mlm_members', 'display_name' => 'จัดการสมาชิก MLM', 'category' => 'mlm', 'description' => 'จัดการสมาชิก MLM'],
            ['name' => 'manage_mlm_plans', 'display_name' => 'จัดการแผน MLM', 'category' => 'mlm', 'description' => 'จัดการแผนการตลาด MLM'],
            ['name' => 'view_mlm_genealogy', 'display_name' => 'ดูสายงาน MLM', 'category' => 'mlm', 'description' => 'ดูสายงานและโครงสร้าง MLM'],
            ['name' => 'manage_mlm_prospects', 'display_name' => 'จัดการผู้สนใจ MLM', 'category' => 'mlm', 'description' => 'จัดการผู้สนใจสมัคร MLM'],
            ['name' => 'manage_mlm_product_pv', 'display_name' => 'จัดการ PV สินค้า', 'category' => 'mlm', 'description' => 'จัดการค่า PV ของสินค้า'],
            ['name' => 'view_mlm_reports', 'display_name' => 'ดูรายงาน MLM', 'category' => 'mlm', 'description' => 'ดูรายงาน MLM'],
            ['name' => 'manage_mlm_settings', 'display_name' => 'ตั้งค่า MLM', 'category' => 'mlm', 'description' => 'จัดการตั้งค่า MLM'],

            // ============================================
            // EMAIL MANAGEMENT (email)
            // ============================================
            ['name' => 'view_email', 'display_name' => 'ดูอีเมล', 'category' => 'email', 'description' => 'เข้าถึง Email Dashboard'],
            ['name' => 'manage_email_campaigns', 'display_name' => 'จัดการแคมเปญอีเมล', 'category' => 'email', 'description' => 'จัดการแคมเปญอีเมล'],
            ['name' => 'manage_email_queue', 'display_name' => 'จัดการคิวอีเมล', 'category' => 'email', 'description' => 'จัดการคิวการส่งอีเมล'],
            ['name' => 'manage_email_templates', 'display_name' => 'จัดการเทมเพลตอีเมล', 'category' => 'email', 'description' => 'จัดการเทมเพลตอีเมล'],
            ['name' => 'manage_email_providers', 'display_name' => 'จัดการผู้ให้บริการอีเมล', 'category' => 'email', 'description' => 'จัดการผู้ให้บริการอีเมล'],
            ['name' => 'view_email_logs', 'display_name' => 'ดูประวัติอีเมล', 'category' => 'email', 'description' => 'ดูประวัติการส่งอีเมล'],
            ['name' => 'view_email_analytics', 'display_name' => 'ดูสถิติอีเมล', 'category' => 'email', 'description' => 'ดูสถิติการส่งอีเมล'],

            // ============================================
            // LINE OA (line_oa)
            // ============================================
            ['name' => 'view_line_oa', 'display_name' => 'ดู LINE OA', 'category' => 'line_oa', 'description' => 'เข้าถึง LINE OA Dashboard'],
            ['name' => 'manage_line_oa', 'display_name' => 'จัดการ LINE OA', 'category' => 'line_oa', 'description' => 'จัดการตั้งค่า LINE OA'],
            ['name' => 'manage_line_bot_ai', 'display_name' => 'จัดการ LINE AI Bot', 'category' => 'line_oa', 'description' => 'จัดการ AI ChatBot สำหรับ LINE'],
            ['name' => 'manage_line_rich_menu', 'display_name' => 'จัดการ Rich Menu', 'category' => 'line_oa', 'description' => 'จัดการ Rich Menu ของ LINE'],
            ['name' => 'manage_line_membership', 'display_name' => 'จัดการสมาชิก LINE', 'category' => 'line_oa', 'description' => 'จัดการระบบสมาชิก LINE'],
            ['name' => 'manage_line_keywords', 'display_name' => 'จัดการ Keywords LINE', 'category' => 'line_oa', 'description' => 'จัดการ Keyword Automation'],
            ['name' => 'manage_line_broadcast', 'display_name' => 'จัดการ Broadcast LINE', 'category' => 'line_oa', 'description' => 'จัดการ Broadcast Messages'],
            ['name' => 'view_line_analytics', 'display_name' => 'ดูสถิติ LINE', 'category' => 'line_oa', 'description' => 'ดูสถิติ LINE OA'],

            // ============================================
            // ACADEMY SYSTEM (academy)
            // ============================================
            ['name' => 'view_academy', 'display_name' => 'ดู Academy', 'category' => 'academy', 'description' => 'เข้าถึง Academy Dashboard'],
            ['name' => 'manage_courses', 'display_name' => 'จัดการคอร์ส', 'category' => 'academy', 'description' => 'จัดการคอร์สเรียน'],
            ['name' => 'manage_quizzes', 'display_name' => 'จัดการแบบทดสอบ', 'category' => 'academy', 'description' => 'จัดการแบบทดสอบ'],
            ['name' => 'manage_certificates', 'display_name' => 'จัดการใบประกาศ', 'category' => 'academy', 'description' => 'จัดการใบประกาศนียบัตร'],
            ['name' => 'view_instructor_dashboard', 'display_name' => 'ดู Instructor Dashboard', 'category' => 'academy', 'description' => 'เข้าถึง Instructor Dashboard'],
            ['name' => 'manage_academy_settings', 'display_name' => 'ตั้งค่า Academy', 'category' => 'academy', 'description' => 'จัดการตั้งค่า Academy'],

            // ============================================
            // LEARNING CENTER (learning)
            // ============================================
            ['name' => 'view_learning', 'display_name' => 'ดู Learning Center', 'category' => 'learning', 'description' => 'เข้าถึง Learning Center'],
            ['name' => 'manage_articles', 'display_name' => 'จัดการบทความ', 'category' => 'learning', 'description' => 'จัดการบทความ'],
            ['name' => 'manage_article_categories', 'display_name' => 'จัดการหมวดหมู่บทความ', 'category' => 'learning', 'description' => 'จัดการหมวดหมู่บทความ'],

            // ============================================
            // MARKETING (marketing)
            // ============================================
            ['name' => 'view_marketing', 'display_name' => 'ดูการตลาด', 'category' => 'marketing', 'description' => 'เข้าถึง Marketing Dashboard'],
            ['name' => 'manage_retention', 'display_name' => 'จัดการ Retention', 'category' => 'marketing', 'description' => 'จัดการระบบ Retention'],
            ['name' => 'manage_cashback', 'display_name' => 'จัดการ Cashback', 'category' => 'marketing', 'description' => 'จัดการระบบ Cashback'],
            ['name' => 'manage_promotions', 'display_name' => 'จัดการโปรโมชั่น', 'category' => 'marketing', 'description' => 'จัดการโปรโมชั่น'],

            // ============================================
            // HUMAN RESOURCES (hrm)
            // ============================================
            ['name' => 'view_hrm', 'display_name' => 'ดู HRM', 'category' => 'hrm', 'description' => 'เข้าถึง HRM Dashboard'],
            ['name' => 'manage_employees', 'display_name' => 'จัดการพนักงาน', 'category' => 'hrm', 'description' => 'จัดการข้อมูลพนักงาน'],
            ['name' => 'manage_departments', 'display_name' => 'จัดการแผนก', 'category' => 'hrm', 'description' => 'จัดการแผนก'],
            ['name' => 'manage_positions', 'display_name' => 'จัดการตำแหน่ง', 'category' => 'hrm', 'description' => 'จัดการตำแหน่งงาน'],
            ['name' => 'manage_leave', 'display_name' => 'จัดการลางาน', 'category' => 'hrm', 'description' => 'จัดการการลางาน'],
            ['name' => 'manage_payroll', 'display_name' => 'จัดการเงินเดือน', 'category' => 'hrm', 'description' => 'จัดการเงินเดือน'],

            // ============================================
            // ACCOUNTING (accounting)
            // ============================================
            ['name' => 'view_accounting', 'display_name' => 'ดูบัญชี', 'category' => 'accounting', 'description' => 'เข้าถึง Accounting Dashboard'],
            ['name' => 'manage_invoices', 'display_name' => 'จัดการใบแจ้งหนี้', 'category' => 'accounting', 'description' => 'จัดการใบแจ้งหนี้'],
            ['name' => 'manage_expenses', 'display_name' => 'จัดการค่าใช้จ่าย', 'category' => 'accounting', 'description' => 'จัดการค่าใช้จ่าย'],
            ['name' => 'manage_accounting_contacts', 'display_name' => 'จัดการผู้ติดต่อ', 'category' => 'accounting', 'description' => 'จัดการผู้ติดต่อบัญชี'],
            ['name' => 'view_accounting_reports', 'display_name' => 'ดูรายงานบัญชี', 'category' => 'accounting', 'description' => 'ดูรายงานบัญชี'],
            ['name' => 'manage_flowaccount', 'display_name' => 'จัดการ FlowAccount', 'category' => 'accounting', 'description' => 'จัดการการเชื่อมต่อ FlowAccount'],

            // ============================================
            // NOTIFICATIONS (notifications)
            // ============================================
            ['name' => 'view_notifications', 'display_name' => 'ดูการแจ้งเตือน', 'category' => 'notifications', 'description' => 'ดูการแจ้งเตือนทั้งหมด'],
            ['name' => 'manage_notifications', 'display_name' => 'จัดการการแจ้งเตือน', 'category' => 'notifications', 'description' => 'จัดการการแจ้งเตือน'],
            ['name' => 'manage_notification_templates', 'display_name' => 'จัดการเทมเพลตแจ้งเตือน', 'category' => 'notifications', 'description' => 'จัดการเทมเพลตการแจ้งเตือน'],

            // ============================================
            // SECURITY (security)
            // ============================================
            ['name' => 'view_security', 'display_name' => 'ดูความปลอดภัย', 'category' => 'security', 'description' => 'เข้าถึง Security Dashboard'],
            ['name' => 'view_threat_intelligence', 'display_name' => 'ดู Threat Intelligence', 'category' => 'security', 'description' => 'ดูการวิเคราะห์ภัยคุกคาม'],
            ['name' => 'view_security_analytics', 'display_name' => 'ดูสถิติความปลอดภัย', 'category' => 'security', 'description' => 'ดูสถิติความปลอดภัย'],
            ['name' => 'manage_otp', 'display_name' => 'จัดการ OTP', 'category' => 'security', 'description' => 'จัดการตั้งค่า OTP'],
            ['name' => 'manage_two_factor', 'display_name' => 'จัดการ 2FA', 'category' => 'security', 'description' => 'จัดการตั้งค่า 2FA'],

            // ============================================
            // PAGES & SEO (pages_seo)
            // ============================================
            ['name' => 'view_pages', 'display_name' => 'ดูหน้าเพจ', 'category' => 'pages_seo', 'description' => 'ดูรายการหน้าเพจ'],
            ['name' => 'manage_pages', 'display_name' => 'จัดการหน้าเพจ', 'category' => 'pages_seo', 'description' => 'จัดการหน้าเพจ'],
            ['name' => 'manage_seo', 'display_name' => 'จัดการ SEO', 'category' => 'pages_seo', 'description' => 'จัดการตั้งค่า SEO'],

            // ============================================
            // ANALYTICS (analytics)
            // ============================================
            ['name' => 'view_system_analytics', 'display_name' => 'ดูสถิติระบบ', 'category' => 'analytics', 'description' => 'ดูสถิติระบบทั้งหมด'],
            ['name' => 'export_analytics', 'display_name' => 'ส่งออกสถิติ', 'category' => 'analytics', 'description' => 'ส่งออกข้อมูลสถิติ'],

            // ============================================
            // THEMES (themes)
            // ============================================
            ['name' => 'view_themes', 'display_name' => 'ดูธีม', 'category' => 'themes', 'description' => 'ดูการตั้งค่าธีม'],
            ['name' => 'manage_themes', 'display_name' => 'จัดการธีม', 'category' => 'themes', 'description' => 'จัดการธีมและการแสดงผล'],
            ['name' => 'manage_app_control', 'display_name' => 'จัดการ App Control', 'category' => 'themes', 'description' => 'จัดการ App Control Sections'],

            // ============================================
            // LANGUAGES (languages)
            // ============================================
            ['name' => 'view_translations', 'display_name' => 'ดูการแปล', 'category' => 'languages', 'description' => 'ดูการแปลภาษา'],
            ['name' => 'manage_translations', 'display_name' => 'จัดการการแปล', 'category' => 'languages', 'description' => 'จัดการการแปลภาษา'],
            ['name' => 'manage_languages', 'display_name' => 'จัดการภาษา', 'category' => 'languages', 'description' => 'จัดการภาษาในระบบ'],

            // ============================================
            // CONTENT & MEDIA (content_media)
            // ============================================
            ['name' => 'view_content', 'display_name' => 'ดูคอนเทนต์', 'category' => 'content_media', 'description' => 'ดูคอนเทนต์และมีเดีย'],
            ['name' => 'manage_webp', 'display_name' => 'จัดการ WebP', 'category' => 'content_media', 'description' => 'จัดการการแปลง WebP'],
            ['name' => 'manage_page_builder', 'display_name' => 'จัดการ Page Builder', 'category' => 'content_media', 'description' => 'จัดการ Page Builder'],
            ['name' => 'manage_tarot', 'display_name' => 'จัดการไพ่ทาโรต์', 'category' => 'content_media', 'description' => 'จัดการระบบไพ่ทาโรต์'],
            ['name' => 'manage_video_rewards', 'display_name' => 'จัดการ Video Rewards', 'category' => 'content_media', 'description' => 'จัดการระบบ Video Rewards'],

            // ============================================
            // AI GENERATION (ai_gen)
            // ============================================
            ['name' => 'view_ai_gen', 'display_name' => 'ดู AI Gen', 'category' => 'ai_gen', 'description' => 'เข้าถึง AI Gen Dashboard'],
            ['name' => 'manage_ai_gen_providers', 'display_name' => 'จัดการ AI Gen Providers', 'category' => 'ai_gen', 'description' => 'จัดการผู้ให้บริการ AI Gen'],
            ['name' => 'manage_ai_gen_packages', 'display_name' => 'จัดการ AI Gen Packages', 'category' => 'ai_gen', 'description' => 'จัดการแพ็คเกจ AI Gen'],
            ['name' => 'manage_ai_gen_quotas', 'display_name' => 'จัดการ AI Gen Quotas', 'category' => 'ai_gen', 'description' => 'จัดการโควต้า AI Gen'],
            ['name' => 'manage_ai_gen_subscriptions', 'display_name' => 'จัดการ AI Gen Subscriptions', 'category' => 'ai_gen', 'description' => 'จัดการการสมัครสมาชิก AI Gen'],
            ['name' => 'view_ai_gen_usage', 'display_name' => 'ดูการใช้งาน AI Gen', 'category' => 'ai_gen', 'description' => 'ดูสถิติการใช้งาน AI Gen'],
            ['name' => 'manage_ai_gen_settings', 'display_name' => 'ตั้งค่า AI Gen', 'category' => 'ai_gen', 'description' => 'จัดการตั้งค่า AI Gen'],

            // ============================================
            // SYSTEM SETTINGS (settings)
            // ============================================
            ['name' => 'manage_app_management', 'display_name' => 'จัดการแอพมือถือ', 'category' => 'settings', 'description' => 'จัดการแอพมือถือ'],
            ['name' => 'manage_app_features', 'display_name' => 'จัดการฟีเจอร์แอพ', 'category' => 'settings', 'description' => 'จัดการฟีเจอร์แอพมือถือ'],
            ['name' => 'manage_app_banners', 'display_name' => 'จัดการแบนเนอร์แอพ', 'category' => 'settings', 'description' => 'จัดการแบนเนอร์แอพมือถือ'],
            ['name' => 'manage_app_maintenance', 'display_name' => 'จัดการ Maintenance', 'category' => 'settings', 'description' => 'จัดการโหมด Maintenance'],
            ['name' => 'manage_ocr', 'display_name' => 'จัดการ OCR', 'category' => 'settings', 'description' => 'จัดการตั้งค่า OCR'],
            ['name' => 'manage_api_keys', 'display_name' => 'จัดการ API Keys', 'category' => 'settings', 'description' => 'จัดการ API Keys'],
            ['name' => 'manage_demo_data', 'display_name' => 'จัดการข้อมูลตัวอย่าง', 'category' => 'settings', 'description' => 'จัดการข้อมูลตัวอย่าง'],
            ['name' => 'manage_system_reset', 'display_name' => 'รีเซ็ตระบบ', 'category' => 'settings', 'description' => 'เข้าถึงเครื่องมือรีเซ็ตระบบ'],

            // ============================================
            // PAYMENT GATEWAYS (payment)
            // ============================================
            ['name' => 'view_payment_gateways', 'display_name' => 'ดู Payment Gateways', 'category' => 'payment', 'description' => 'ดู Payment Gateways'],
            ['name' => 'manage_payment_gateways', 'display_name' => 'จัดการ Payment Gateways', 'category' => 'payment', 'description' => 'จัดการ Payment Gateways'],

            // ============================================
            // ROLES MANAGEMENT (roles)
            // ============================================
            ['name' => 'view_roles', 'display_name' => 'ดูบทบาท', 'category' => 'roles', 'description' => 'ดูรายการบทบาท'],
            ['name' => 'manage_roles', 'display_name' => 'จัดการบทบาท', 'category' => 'roles', 'description' => 'สร้างและจัดการบทบาท'],
            ['name' => 'assign_roles', 'display_name' => 'กำหนดบทบาท', 'category' => 'roles', 'description' => 'กำหนดบทบาทให้ผู้ใช้'],
        ];
    }

    /**
     * กำหนด permissions ให้กับ roles
     */
    private function assignPermissionsToRoles(): void
    {
        $this->command->info('   📋 กำลังกำหนด permissions ให้กับ roles...');

        // Super Admin ได้ทุก permissions
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $allPermissions = Permission::all();
            $existingPermissionIds = DB::table('role_permissions')
                ->where('role_id', $superAdminRole->id)
                ->pluck('permission_id')
                ->toArray();

            $newPermissions = $allPermissions->filter(function ($permission) use ($existingPermissionIds) {
                return ! in_array($permission->id, $existingPermissionIds);
            });

            if ($newPermissions->isNotEmpty()) {
                $superAdminRole->permissions()->attach($newPermissions->pluck('id'));
                $this->command->info("      ✅ Super Admin: เพิ่ม {$newPermissions->count()} permissions ใหม่");
            }
        }

        // Admin ได้ permissions ตาม default
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $basicAdminPermissions = [
                'view_tickets', 'manage_tickets',
                'view_ai_bots', 'view_chatbots',
                'view_hotels', 'view_service_bookings',
                'view_ecommerce', 'manage_products', 'manage_orders',
                'view_pos', 'view_pos_transactions',
                'view_nfc', 'view_nfc_transactions',
                'view_crypto', 'view_crypto_transactions',
                'view_mlm', 'view_mlm_genealogy', 'view_mlm_reports',
                'view_email', 'view_email_logs',
                'view_line_oa', 'view_line_analytics',
                'view_academy', 'view_learning',
                'view_marketing',
                'view_hrm',
                'view_accounting', 'view_accounting_reports',
                'view_notifications',
                'view_security',
                'view_pages',
                'view_system_analytics',
                'view_themes',
                'view_translations',
                'view_content',
                'view_ai_gen', 'view_ai_gen_usage',
                'view_payment_gateways',
                'view_roles',
            ];

            $permissions = Permission::whereIn('name', $basicAdminPermissions)->get();
            $existingPermissionIds = DB::table('role_permissions')
                ->where('role_id', $adminRole->id)
                ->pluck('permission_id')
                ->toArray();

            $newPermissions = $permissions->filter(function ($permission) use ($existingPermissionIds) {
                return ! in_array($permission->id, $existingPermissionIds);
            });

            if ($newPermissions->isNotEmpty()) {
                $adminRole->permissions()->attach($newPermissions->pluck('id'));
                $this->command->info("      ✅ Admin: เพิ่ม {$newPermissions->count()} permissions ใหม่");
            }
        }
    }
}
