<?php

namespace Database\Seeders;

use App\Models\LearningCategory;
use Illuminate\Database\Seeder;

/**
 * Seeder สำหรับหมวดหมู่วิชา Academy Knowledge
 *
 * สร้างหมวดหมู่ตามโครงสร้าง Wiki ของระบบ TP-Affiliate
 * ทุกวิชาอ่านฟรีสำหรับสมาชิกที่ login
 */
class LearningCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 📌 Smart Seeding Strategy:
     * Adds missing learning categories only, preserves existing configurations.
     * User customizations (names, colors, icons, order) are preserved.
     */
    public function run(): void
    {
        $existingCategoriesCount = LearningCategory::count();

        if ($existingCategoriesCount > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh install mode - seed all learning categories
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: Seeding Academy Knowledge categories...');

        $categories = $this->getAllCategories();

        foreach ($categories as $category) {
            LearningCategory::create($category);
        }

        $this->command->info('✅ Academy Knowledge categories seeded: '.count($categories).' หมวดหมู่');
    }

    /**
     * Update mode - add only missing learning categories
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  มีหมวดหมู่อยู่แล้ว!');
        $this->command->info('   กำลังเพิ่มเฉพาะหมวดหมู่ที่ขาด...');

        $categories = $this->getAllCategories();
        $added = 0;
        $skipped = 0;

        foreach ($categories as $category) {
            if (! LearningCategory::where('slug', $category['slug'])->exists()) {
                LearningCategory::create($category);
                $this->command->info("   ➕ เพิ่ม: {$category['name']}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ เพิ่ม {$added} หมวดหมู่ใหม่");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  ข้าม {$skipped} หมวดหมู่ที่มีอยู่แล้ว");
        }
    }

    /**
     * Get all default learning categories
     *
     * โครงสร้างหมวดหมู่ตาม Wiki ของระบบ TP-Affiliate
     * ทุกวิชาอ่านฟรีสำหรับสมาชิก
     */
    private function getAllCategories(): array
    {
        return [
            // 1. ภาพรวมระบบ
            [
                'name' => 'ภาพรวม & สรุปฟีเจอร์',
                'slug' => 'overview',
                'description' => 'ทำความรู้จักระบบ TP-Affiliate ฟีเจอร์ทั้งหมด และภาพรวมของแพลตฟอร์ม',
                'icon' => '🚀',
                'color' => '#3B82F6',
                'order' => 1,
                'is_active' => true,
            ],
            // 2. เริ่มต้นใช้งาน
            [
                'name' => 'เริ่มต้นใช้งาน',
                'slug' => 'getting-started',
                'description' => 'คู่มือการเริ่มต้นใช้งานระบบสำหรับผู้ใช้ใหม่ ตั้งแต่สมัครสมาชิกจนถึงการใช้งานขั้นพื้นฐาน',
                'icon' => '🎯',
                'color' => '#10B981',
                'order' => 2,
                'is_active' => true,
            ],
            // 3. MLM & Affiliate
            [
                'name' => 'MLM & Affiliate',
                'slug' => 'mlm-affiliate',
                'description' => 'ระบบ Multi-Level Marketing และ Affiliate Marketing รวมถึง Binary, Unilevel, Commission และ Rank',
                'icon' => '💎',
                'color' => '#8B5CF6',
                'order' => 3,
                'is_active' => true,
            ],
            // 4. AI & Bot System
            [
                'name' => 'AI & Bot System',
                'slug' => 'ai-bot',
                'description' => 'ระบบ AI Chatbot, AI สร้างรูปภาพ/วิดีโอ, LINE AI Bot และเครื่องมือ AI อื่นๆ',
                'icon' => '🤖',
                'color' => '#7C3AED',
                'order' => 4,
                'is_active' => true,
            ],
            // 5. E-Commerce & POS
            [
                'name' => 'E-Commerce & POS',
                'slug' => 'ecommerce',
                'description' => 'ระบบร้านค้าออนไลน์ ตะกร้าสินค้า การจัดการคำสั่งซื้อ และ Point of Sale',
                'icon' => '🛒',
                'color' => '#F59E0B',
                'order' => 5,
                'is_active' => true,
            ],
            // 6. Hotel Management
            [
                'name' => 'Hotel Management',
                'slug' => 'hotel',
                'description' => 'ระบบจัดการโรงแรม การจองห้องพัก และการบริหารจัดการที่พัก',
                'icon' => '🏨',
                'color' => '#EF4444',
                'order' => 6,
                'is_active' => true,
            ],
            // 7. Crypto & Blockchain
            [
                'name' => 'Crypto & Blockchain',
                'slug' => 'crypto',
                'description' => 'ระบบ Cryptocurrency, TPIX Token, Staking, NFT และ Blockchain Integration',
                'icon' => '₿',
                'color' => '#F59E0B',
                'order' => 7,
                'is_active' => true,
            ],
            // 8. Wallet System
            [
                'name' => 'Wallet System',
                'slug' => 'wallet',
                'description' => 'ระบบกระเป๋าเงิน การเติมเงิน ถอนเงิน และการจัดการยอดคงเหลือ',
                'icon' => '💳',
                'color' => '#6366F1',
                'order' => 8,
                'is_active' => true,
            ],
            // 9. Payment Gateway
            [
                'name' => 'ช่องทางชำระเงิน',
                'slug' => 'payment',
                'description' => 'ระบบชำระเงิน PromptPay, บัตรเครดิต, TrueMoney และช่องทางอื่นๆ',
                'icon' => '💰',
                'color' => '#059669',
                'order' => 9,
                'is_active' => true,
            ],
            // 10. HRM
            [
                'name' => 'HRM (ระบบบุคคล)',
                'slug' => 'hrm',
                'description' => 'ระบบจัดการทรัพยากรบุคคล การลา การฝึกอบรม และการประเมินผล',
                'icon' => '👥',
                'color' => '#0EA5E9',
                'order' => 10,
                'is_active' => true,
            ],
            // 11. Accounting
            [
                'name' => 'ระบบบัญชี',
                'slug' => 'accounting',
                'description' => 'ระบบบัญชี ผังบัญชี การบันทึกรายการ และรายงานทางการเงิน',
                'icon' => '📊',
                'color' => '#14B8A6',
                'order' => 11,
                'is_active' => true,
            ],
            // 12. Software Sales
            [
                'name' => 'ซอฟต์แวร์และไลเซนส์',
                'slug' => 'software',
                'description' => 'ระบบขายซอฟต์แวร์ การจัดการไลเซนส์ และ White-label Solutions',
                'icon' => '💿',
                'color' => '#EC4899',
                'order' => 12,
                'is_active' => true,
            ],
            // 13. Vendor Management
            [
                'name' => 'ระบบผู้ขาย (Vendor)',
                'slug' => 'vendor',
                'description' => 'การจัดการผู้ขาย Marketplace, แพ็กเกจผู้ขาย และการตั้งค่าร้านค้า',
                'icon' => '🏪',
                'color' => '#F97316',
                'order' => 13,
                'is_active' => true,
            ],
            // 14. Technology & Architecture
            [
                'name' => 'เทคโนโลยีและสถาปัตยกรรม',
                'slug' => 'technology',
                'description' => 'เทคโนโลยีที่ใช้ในระบบ สถาปัตยกรรมซอฟต์แวร์ และ Stack ที่ใช้',
                'icon' => '⚙️',
                'color' => '#64748B',
                'order' => 14,
                'is_active' => true,
            ],
            // 15. Security
            [
                'name' => 'ความปลอดภัย',
                'slug' => 'security',
                'description' => 'ระบบรักษาความปลอดภัย การยืนยันตัวตน 2FA และการป้องกันข้อมูล',
                'icon' => '🔒',
                'color' => '#DC2626',
                'order' => 15,
                'is_active' => true,
            ],
            // 16. API & Integration
            [
                'name' => 'API & Integration',
                'slug' => 'api-integration',
                'description' => 'API Documentation, Webhooks และการเชื่อมต่อระบบภายนอก',
                'icon' => '🔌',
                'color' => '#475569',
                'order' => 16,
                'is_active' => true,
            ],
            // 17. FAQ & Support
            [
                'name' => 'FAQ & ศูนย์ช่วยเหลือ',
                'slug' => 'faq-support',
                'description' => 'คำถามที่พบบ่อย การแก้ไขปัญหา และวิธีติดต่อทีมสนับสนุน',
                'icon' => '❓',
                'color' => '#D946EF',
                'order' => 17,
                'is_active' => true,
            ],
        ];
    }
}
