<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🚀 Starting database seeding...');
        $this->command->info('');

        // Seed in proper order to handle dependencies
        $this->call([
            // 1. Core Settings & Configuration (ต้องมาก่อนสุด)
            AppNameSettingSeeder::class,        // ตั้งค่าชื่อแอพ
            TwoFactorSettingsSeeder::class,     // ตั้งค่า 2FA และ OTP
            ThemeSeeder::class,                 // ธีมของระบบ
            AppManagementSeeder::class,         // ตั้งค่าแอพ, ธีม, ฟีเจอร์, แบนเนอร์, และ maintenance
            CookieSettingsSeeder::class,        // ตั้งค่า Cookie Consent & PDPA

            // 2. User & Demo Data
            DemoUsersSeeder::class,             // สร้างผู้ใช้ทดสอบ
            TestUsersSeeder::class,             // สร้างผู้ใช้ทดสอบเพิ่มเติม (backward compatibility)
            DemoAffiliatesSeeder::class,        // สร้าง affiliates
            DemoCommissionsSeeder::class,       // สร้าง commissions

            // 3. Content & Pages
            DemoPagesSeeder::class,             // สร้างหน้าเพจต่างๆ
            SeoMetaSeeder::class,               // สร้าง SEO meta data
            MenuItemSeeder::class,              // สร้างเมนูสำหรับ Header
            PageBuilderSeeder::class,           // Page Builder Templates (Homepage, Wiki, About builder)

            // 4. Communication Templates
            EmailTemplateSeeder::class,         // Email Templates สำหรับระบบส่งอีเมล
            LineOaSettingSeeder::class,         // LINE OA Settings Configuration
            LineFlexMessageTemplateSeeder::class, // LINE Flex Message Templates

            // 5. AI & Integrations
            AiProvidersSeeder::class,           // AI Providers และ Models (OpenAI, Claude, DeepSeek, Gemini)

            // 6. Payment Systems
            PaymentGatewaySeeder::class,        // Payment Gateways (PromptPay, Bank, TrueMoney, Omise, Stripe, PayPal, etc.)
            PaySolutionsGatewaySeeder::class,   // PaySolutions Gateway Integration
            CryptoCurrencySeeder::class,        // Cryptocurrency Support (BTC, ETH, USDT, etc.)

            // 7. MLM & Affiliate System
            MlmGlobalSettingsSeeder::class,     // การตั้งค่า MLM ทั่วไป (สร้างข้อมูลเริ่มต้น)
            MlmGlobalSettingSeeder::class,      // อัปเดต MLM Global Settings สำหรับระบบผู้มุ่งหวัง
            MlmPlanSeeder::class,               // แผนคอมมิชชัน MLM หลัก (แผนเดียวบังคับทั้งระบบ)
            MlmPackageSeeder::class,            // แพคเกจสมาชิก MLM (Bronze, Silver, Gold, Diamond, Premier)
            RankSeeder::class,                  // ระบบยศ/ระดับ (Bronze, Silver, Gold, Platinum, Diamond)
            CommissionDepthSeeder::class,       // ความลึกของการคำนวณคอมมิชชัน

            // 8. E-commerce & Products
            ProductCategorySeeder::class,       // หมวดหมู่สินค้า (ต้องมาก่อน ProductSeeder)
            ProductSeeder::class,               // สินค้าตัวอย่าง
            VendorPackageSeeder::class,         // แพคเกจสำหรับผู้ขาย/Vendor
            VendorPackageFeatureSeeder::class,  // ฟีเจอร์ของแพคเกจ Vendor
            MarketplacePlatformSeeder::class,   // Marketplace Platforms (Shopee, Lazada, etc.)
            SoftwareProductSeeder::class,       // ระบบผลิตภัณฑ์ซอฟต์แวร์ (MLM, E-commerce, Affiliate systems)

            // 9. Academy & Learning Platform
            AcademySeeder::class,               // ตั้งค่าระบบ Academy
            LearningCategorySeeder::class,      // หมวดหมู่คอร์ส
            LearningArticleSeeder::class,       // คอร์สและบทความ
            QuizSeeder::class,                  // Quiz และคำถาม

            // 10. Accounting System
            ChartOfAccountsSeeder::class,       // ผังบัญชี (Chart of Accounts)
            AccountingPermissionsSeeder::class, // สิทธิ์การใช้งานระบบบัญชี
            AccountingDemoSeeder::class,        // ข้อมูลทดสอบสำหรับระบบบัญชี

            // 11. HRM System
            HrmSeeder::class,                   // ระบบ HR Management

            // 12. Additional Systems
            TarotSystemSeeder::class,           // ระบบดูดวงไพ่ทาโรต์
            HotelSeeder::class,                 // ระบบจองโรงแรม
            InvestmentPlanSeeder::class,        // แพลนการลงทุน
        ]);

        $this->command->info('');
        $this->command->info('✨ Database seeding completed successfully!');
        $this->command->info('');
    }
}
