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
            ArrowXThemeSeeder::class,           // ✅ Arrow X Theme System (V3) - Default theme data
            ThemePresetSeeder::class,           // ✅ Theme Presets (Classic, Modern Blue, Dark Professional, etc.)
            AppManagementSeeder::class,         // ตั้งค่าแอพ, ธีม, ฟีเจอร์, แบนเนอร์, และ maintenance
            CookieSettingsSeeder::class,        // ตั้งค่า Cookie Consent & PDPA
            WindowsUiSeeder::class,             // Windows UI Settings (Colors, Themes, RGB - NO menu items)
            AppControlSectionSeeder::class,     // UI Control Sections (Navigation Bar, Tab Bar, Header, FAB)
            ComponentSettingSeeder::class,      // UI Component Settings (Buttons, Inputs, Cards, Text)
            ApiEndpointSeeder::class,           // API Endpoints Configuration (Users, Products, Orders, Analytics, etc.)

            // 2. User & Demo Data
            DemoUsersSeeder::class,             // สร้างผู้ใช้ทดสอบ
            TestUsersSeeder::class,             // สร้างผู้ใช้ทดสอบเพิ่มเติม (backward compatibility)
            KycVerificationSeeder::class,       // 🆕 KYC Demo Verification (pending, approved, rejected)
            LineSignupSessionSeeder::class,     // 🆕 LINE Demo Signup Sessions (new, in_progress, completed)

            // 3. Content & Pages
            DemoPagesSeeder::class,             // สร้างหน้าเพจต่างๆ
            SeoMetaSeeder::class,               // สร้าง SEO meta data
            // PageBuilderSeeder::class,           // Page Builder Templates (Homepage, Wiki, About builder) - SKIP: Already exists
            // HomepageImportSeeder::class,        // Import current homepage to Page Builder - SKIP: Already exists

            // 4. Communication Templates
            EmailTemplateSeeder::class,         // Email Templates สำหรับระบบส่งอีเมล
            LineOaSettingSeeder::class,         // LINE OA Settings Configuration
            LineFlexMessageTemplateSeeder::class, // LINE Flex Message Templates
            LineSignupTemplateSeeder::class,    // LINE Signup Templates (AI-Powered Membership Signup)
            LineSignupFlowSeeder::class,        // 🆕 LINE Signup Flow Steps (Complete signup conversation flow)
            LineBotAiSeeder::class,             // 🆕 LINE Bot AI Profiles (Demo bots: Affiliate, Support, Sales)
            LineBotKeywordSeeder::class,        // 🆕 LINE Hybrid Bot Keywords (Keyword-based responses + AI fallback)

            // 5. AI & Integrations
            AICoreFeatureSeeder::class,         // 🆕 AI Core Feature Registry (8 AI feature groups)
            AiProvidersSeeder::class,           // AI Providers และ Models (OpenAI, Claude, DeepSeek, Gemini)
            AiGenSeeder::class,                 // AI Generation System (Image & Video Generation)

            // 6. Payment Systems
            PaymentGatewaySeeder::class,        // Payment Gateways (PromptPay, Bank, TrueMoney, Omise, Stripe, PayPal, etc.)
            PaySolutionsGatewaySeeder::class,   // PaySolutions Gateway Integration
            CryptoCurrencySeeder::class,        // Cryptocurrency Support (BTC, ETH, USDT, etc.)
            TPIXCurrencySeeder::class,          // TPIX Native Token (TPIX Network Blockchain)
            TPIXStakingPoolSeeder::class,       // TPIX Staking Pools (Flexible, 30d, 90d, 180d, 365d)

            // 7. MLM System
            MlmGlobalSettingsSeeder::class,     // การตั้งค่า MLM ทั่วไป (สร้างข้อมูลเริ่มต้น)
            MlmGlobalSettingSeeder::class,      // อัปเดต MLM Global Settings สำหรับระบบผู้มุ่งหวัง
            MlmPlanSeeder::class,               // แผนคอมมิชชัน MLM หลัก (แผนเดียวบังคับทั้งระบบ)
            MlmPackageSeeder::class,            // แพคเกจสมาชิก MLM (Bronze, Silver, Gold, Diamond, Premier)
            RankSeeder::class,                  // ระบบยศ/ระดับ (Bronze, Silver, Gold, Platinum, Diamond)

            // 8. E-commerce & Products
            ProductCategorySeeder::class,       // หมวดหมู่สินค้า (ต้องมาก่อน ProductSeeder)
            ProductSeeder::class,               // สินค้าตัวอย่าง
            WalletTopupPackagesSeeder::class,   // แพ็คเกจเติมเงิน Wallet
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
            TradingBotSystemSeeder::class,      // ระบบเทรดดิ้งบอท (Packages, Exchanges, Strategies)
            VideoRewardSystemSeeder::class,     // ระบบรางวัลจากการดูวิดีโอ (Channels, Videos, Quests, Coins)
            GameSeeder::class,                  // ระบบเกม (Game System)
            GamesSeeder::class,                 // ข้อมูลเกมต่างๆ (Games Data)
            GameSettingsSeeder::class,          // การตั้งค่าเกม (IP, Port, Server Config)
            MissionsSeeder::class,              // ระบบภารกิจ (Missions System)

            // 13. Support & Ticket System
            TicketCannedResponseSeeder::class,  // Canned Responses สำหรับ Ticket Support (เทมเพลตตอบกลับอัตโนมัติ)
            TicketSlaSeeder::class,             // SLA Policies สำหรับ Ticket System (เป้าหมายเวลาตอบกลับและแก้ไข)

            // 14. Bot Automation System
            BotPlatformSeeder::class,           // Social Media Platforms (Facebook, LINE, Instagram, Twitter)
            BotMarketplaceCategorySeeder::class, // Bot Marketplace Categories (Sales, Support, Marketing, etc.)
        ]);

        $this->command->info('');
        $this->command->info('✨ Database seeding completed successfully!');
        $this->command->info('');
    }
}
