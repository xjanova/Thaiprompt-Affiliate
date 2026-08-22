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
            // 0. Roles & Permissions (ต้องมาก่อนสุดเพื่อให้ระบบสิทธิ์พร้อมใช้งาน)
            AdminPermissionsSeeder::class,      // 🔐 Admin Permissions ครอบคลุมทุก modules

            // 1. Core Settings & Configuration (ต้องมาก่อนสุด)
            AppNameSettingSeeder::class,        // ตั้งค่าชื่อแอพ
            SiteSettingSeeder::class,           // 🆕 ตั้งค่าเว็บไซต์ (ชื่อ, โลโก้, SEO, Social)
            TwoFactorSettingsSeeder::class,     // ตั้งค่า 2FA และ OTP
            ArrowXThemeSeeder::class,           // ✅ Arrow X Theme System (V3) - Default theme data
            ThemePresetSeeder::class,           // ✅ Theme Presets (Classic, Modern Blue, Dark Professional, etc.)

            CookieSettingsSeeder::class,        // ตั้งค่า Cookie Consent & PDPA
            WindowsUiSeeder::class,             // Windows UI Settings (Colors, Themes, RGB - NO menu items)
            AppControlSectionSeeder::class,     // UI Control Sections (Navigation Bar, Tab Bar, Header, FAB)
            ComponentSettingSeeder::class,      // UI Component Settings (Buttons, Inputs, Cards, Text)
            ApiEndpointSeeder::class,           // API Endpoints Configuration (Users, Products, Orders, Analytics, etc.)

            // 2. User & Demo Data
            AdminUsersSeeder::class,            // 👑 สร้าง Super Admin, Admin, Manager
            // DemoUsersSeeder::class,             // ❌ ลบ - ใช้ ThaipromptMlmSeeder แทน
            TestUsersSeeder::class,             // สร้างผู้ใช้ทดสอบเพิ่มเติม (backward compatibility)
            KycVerificationSeeder::class,       // 🆕 KYC Demo Verification (pending, approved, rejected)

            // 3. Content & Pages
            DemoPagesSeeder::class,             // สร้างหน้าเพจต่างๆ
            SeoMetaSeeder::class,               // สร้าง SEO meta data
            SloganSeeder::class,                // 🆕 คำขวัญ/คำคม 200+ คำ สำหรับ Dashboard
            HomepageManagerSeeder::class,       // 🏠 Homepage Manager - Sections, Elements, Templates
            StoreBannerSeeder::class,           // 🎨 Store Banners - Slides สำหรับ Storefront Homepage
            // PageBuilderSeeder::class,           // Page Builder Templates (Homepage, Wiki, About builder) - SKIP: Already exists
            // HomepageImportSeeder::class,        // Import current homepage to Page Builder - SKIP: Already exists

            // 4. Communication Templates
            EmailTemplateSeeder::class,         // Email Templates สำหรับระบบส่งอีเมล
            EmailProviderSeeder::class,         // Email Providers สำหรับระบบส่งอีเมล (SMTP, Gmail)
            LineOaSettingSeeder::class,         // LINE OA Settings Configuration
            LineBotAiSeeder::class,             // 🆕 LINE Bot AI Profiles (Demo bots: Affiliate, Support, Sales)
            LineBotKeywordSeeder::class,        // 🆕 LINE Hybrid Bot Keywords (Keyword-based responses + AI fallback)
            LineRecruitmentSeeder::class,       // 🆕 LINE Recruitment System (AI-powered recruitment with topic filtering)
            FortuneTellingSettingSeeder::class,  // 🆕 ระบบดูดวง Facebook - การตั้งค่าเริ่มต้น
            FortuneCategorySeeder::class,        // 🆕 ระบบดูดวง - หมวดหมู่การทำนาย (ความรัก, การเงิน, สุขภาพ)
            FortuneResponseTemplateSeeder::class, // 🆕 ระบบดูดวง - เทมเพลตตอบกลับคำทำนาย (basic, deep, welcome, payment, error)
            FortuneKeywordSeeder::class,         // 🆕 ระบบดูดวง - Keywords บทสนทนาอัจฉริยะ (ทักทาย, ขอบคุณ, ราคา, อารมณ์, FAQ 48+ entries)
            FortuneHoroscopeCampaignSeeder::class, // 🆕 ระบบดูดวง - แคมเปญโพสดวงรายวันอัตโนมัติ (AI + FB/LINE auto-post)
            FortuneCommissionSeeder::class,      // 🔮 ค่าเริ่มต้น Level 1/Level 2 commission ดูดวง
            FortuneTakeoverSettingsSeeder::class, // 🎯 ระบบเทคโอเวอร์ดูดวง (แม่หมอ/แอดมินคุยแทน AI)
            FortuneBannerSeeder::class,          // 🖼️ แบนเนอร์ DM (4 รูปเริ่มต้นจาก banner1.zip)
            FortuneInviteMessageSeeder::class,   // 💬 ข้อความชวนดูดวงแบบสุ่ม 100 ข้อความ (ส่งแทนรูปเมื่อได้รูปสัปดาห์นี้แล้ว)
            FortuneInviteMessageBatch2Seeder::class, // 💬 ข้อความชวนดูดวง ชุดที่ 2 — สายมู 100 ข้อความ (มนต์ดำ/คุณไสย/เจ้ากรรมนายเวร ฯลฯ)
            FortuneInviteMessageBatch3Seeder::class, // 💬 ข้อความชวนดูดวง ชุดที่ 3 — จุดจบ/กรรม 100 ข้อความ (คนโกง/มือที่สาม/คนทรยศ/อยู่เหนือกว่า)
            FortuneInviteMessageBatch4Seeder::class, // 💬 ข้อความชวนดูดวง ชุดที่ 4 — โชคลาภ/ข่าวดี 30 ข้อความ (windfall/good_news)
            FortuneTransferInviteMessageSeeder::class, // 🔀 ข้อความชวนชุดโหมด transfer (พาไปเว็บ/LINE) — mode='transfer' ไม่ทับชุดเดิม
            FortuneDailyInviteMessageSeeder::class, // 🌙 ข้อความชวนบอกวันเกิดรับดวงรายวันฟรี 50 ข้อความ — mode='daily' ไม่ทับชุดเดิม
            FortuneDailyInviteMessageBatch2Seeder::class, // 🌙 ชุดที่ 2 อีก 50 ข้อความ (รวม 100) — แยกเช้า/เย็น/ดึกด้วย hour_from-hour_to
            FortuneMysticTopicSeeder::class,     // 🔮 หมวดคอนเทนต์สายมู (5 หมวด: สายมู/แก้เคล็ด/ปัญหาชีวิต/สิ่งลี้ลับ/รู้หรือไม่ทั่วโลก)
            FortuneContentCampaignSeeder::class, // 📣 แคมเปญคอนเทนต์อัตโนมัติ 5 แคมเปญ (สายมู bridge + กำลังใจ/โดพามีน/กฎแห่งกรรม/จิตวิทยา)
            FortuneKnowledgeSeeder::class,       // 🧠 คลังความรู้แม่หมอ (RAG) — สุขภาพ/ฮวงจุ้ย/เจ้าที่/องค์เทพ/มนต์ดำ (จาก config → DB)
            FortuneSystemVoiceClipSeeder::class,  // 🎧 คลังเสียงระบบ (ข้อความกลาง: กล่องกระตุ้น/กติกา/วันเกิด/เตือนจ่าย ฯลฯ)

            // 5. AI & Integrations
            AICoreFeatureSeeder::class,         // 🆕 AI Core Feature Registry (8 AI feature groups)
            AiProvidersSeeder::class,           // AI Providers และ Models (OpenAI, Claude, DeepSeek, Gemini, Meta Llama via Together AI)
            AiGenSeeder::class,                 // AI Generation System (Image & Video Generation)

            // 6. Payment Systems
            WalletSettingSeeder::class,         // 🆕 ตั้งค่า Wallet (ค่าธรรมเนียม, ภาษี, ยอดถอน)
            PaymentGatewaySeeder::class,        // Payment Gateways (PromptPay, Bank, TrueMoney, Omise, Stripe, PayPal, etc.)
            SmsCheckerSeeder::class,            // 📱 SMS Checker Device สำหรับระบบชำระเงินผ่าน SMS
            SmsGatewayPricingSeeder::class,     // 💰 แพ็กเกจราคา SMS Payment Gateway (Basic, Professional, Enterprise)
            PaySolutionsGatewaySeeder::class,   // PaySolutions Gateway Integration
            CryptoCurrencySeeder::class,        // Cryptocurrency Support (BTC, ETH, USDT, etc.)
            TPIXCurrencySeeder::class,          // TPIX Native Token (TPIX Network Blockchain)
            TPIXStakingPoolSeeder::class,       // TPIX Staking Pools (Flexible, 30d, 90d, 180d, 365d)
            NFCCardSeeder::class,               // 🆕 NFC Tap-to-Pay Cards (Demo cards with wallet integration, spending limits, TPIX support)

            // 7. MLM System
            MlmGlobalSettingsSeeder::class,     // การตั้งค่า MLM ทั่วไป (สร้างข้อมูลเริ่มต้น)
            MlmGlobalSettingSeeder::class,      // อัปเดต MLM Global Settings สำหรับระบบผู้มุ่งหวัง
            MlmPlanSeeder::class,               // แผนคอมมิชชัน MLM หลัก (แผนเดียวบังคับทั้งระบบ)
            MlmPackageSeeder::class,            // แพคเกจสมาชิก MLM (Bronze, Silver, Gold, Diamond, Premier)
            RankSeeder::class,                  // ระบบยศ/ระดับ (Bronze, Silver, Gold, Platinum, Diamond)
            RankSettingSeeder::class,           // 🆕 ตั้งค่าระบบ Rank (Auto Promotion, Points, Display)
            IdCardSettingSeeder::class,         // 🆕 การตั้งค่า Virtual ID Card ตาม Rank (8 ระดับ)
            SuperAdminMlmSeeder::class,         // 👑 SuperAdmin เป็น Root Leader (แม่ทีมใหญ่) - ต้องรันก่อน ThaipromptMlmSeeder
            // MlmHierarchySeeder::class,          // ❌ ลบ - ใช้ ThaipromptMlmSeeder แทน
            // ThaipromptMlmSeeder::class,         // ❌ ปิดไว้ - ทดสอบสมัครปกติ
            RecruitTemplateSeeder::class,       // 🆕 เทมเพลตหน้า Recruit สำหรับแม่ทีม
            PlatformRevenueSeeder::class,       // 🆕 ระบบรายได้ Platform (Wallets, Payout Settings)

            // 8. E-commerce & Products
            MarketplaceSettingSeeder::class,    // 🆕 ตั้งค่า Marketplace (ค่าธรรมเนียม, Commission, Shipping)
            ShippingProviderSeeder::class,      // 🚚 บริษัทขนส่งในประเทศไทย (ไปรษณีย์, Kerry, Flash, J&T, etc.)
            ShippingRateSeeder::class,          // 🚚 อัตราค่าจัดส่งตามน้ำหนัก (ในประเทศ + ต่างประเทศ)
            ProductCategorySeeder::class,       // หมวดหมู่สินค้า (ต้องมาก่อน ProductSeeder)
            MuCategorySeeder::class,            // 🔮 หมวดสายมู (ปี่เซี้ยะ/พีระมิด/แก้ปีชง/นักษัตร/เครื่องราง) — ใช้กับ lazada:mu-import
            ProductSeeder::class,               // สินค้าตัวอย่าง
            OfficialShopProductsSeeder::class,  // 🆕 สินค้าของระบบ (Official Shop) - seller_id = null, คอมมิชชั่นสูง 25-40%
            // WalletTopupPackagesSeeder::class,   // ❌ ยกเลิก - ระบบเติมเงินไม่ใช้สินค้าแล้ว ใช้ PaymentTransaction โดยตรง
            VendorPackageSeeder::class,         // แพคเกจสำหรับผู้ขาย/Vendor
            VendorPackageFeatureSeeder::class,  // ฟีเจอร์ของแพคเกจ Vendor
            StoreTrophySeeder::class,           // 🏆 Trophy สำหรับร้านค้า (Sales, Rating, Followers, Products)
            OfficialShopSettingSeeder::class,   // ⚙️ การตั้งค่า Official Shop (AI Selection, Best Sellers)
            StoreRatingSeeder::class,           // ⭐ ระบบคะแนนร้านค้า (Store Rating - แยกจาก Trophy)
            MarketplacePlatformSeeder::class,   // Marketplace Platforms (Shopee, Lazada, etc.)
            SoftwareProductSeeder::class,       // ระบบผลิตภัณฑ์ซอฟต์แวร์ (MLM, E-commerce, Affiliate systems)

            // 9. Academy & Learning Platform
            AcademySeeder::class,               // ตั้งค่าระบบ Academy
            LearningCategorySeeder::class,      // หมวดหมู่คอร์ส
            LearningArticleSeeder::class,       // คอร์สและบทความ
            ThaipromptCourseSeeder::class,      // 🎓 คอร์ส Thaiprompt Academy (เนื้อหาครบ + Quiz + Rewards)
            QuizSeeder::class,                  // Quiz และคำถาม

            // 10. Accounting System
            ChartOfAccountsSeeder::class,       // ผังบัญชี (Chart of Accounts)
            AccountingPermissionsSeeder::class, // สิทธิ์การใช้งานระบบบัญชี
            AccountingDemoSeeder::class,        // ข้อมูลทดสอบสำหรับระบบบัญชี

            // 11. HRM System
            HrmSeeder::class,                   // ระบบ HR Management

            // 12. Additional Systems
            TarotSystemSeeder::class,           // ระบบดูดวงไพ่ทาโรต์
            TarotCelticPositionMeaningsSeeder::class, // 🔮 คำทำนายไพ่ Celtic Cross 7,800 entries (78 ใบ × 10 ตำแหน่ง × 2 ทิศ × 5 หมวด)
            ProvinceSeeder::class,              // ข้อมูลจังหวัดไทย 77 จังหวัด พร้อมพิกัด GPS
            HotelSeeder::class,                 // ระบบจองโรงแรม
            InvestmentPlanSeeder::class,        // แพลนการลงทุน
            TradingBotSystemSeeder::class,      // ระบบเทรดดิ้งบอท (Packages, Exchanges, Strategies)
            VideoRewardSystemSeeder::class,     // ระบบรางวัลจากการดูวิดีโอ (Channels, Videos, Quests, Coins)
            VideoMissionSystemSeeder::class,    // 🆕 ระบบภารกิจดูคลิปรับรางวัล (Video Missions, Rank Limits, Anti-Cheat)
            VideoMissionYouTubeSeeder::class,   // 🎬 นำเข้าวิดีโอจากช่อง YouTube (@Metal-XProject)
            CoinShopSeeder::class,              // 🛒 ระบบร้านค้า Coins (Coin Shop Products)
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
            VideoAutomationSeeder::class,       // 🎬 Video Automation System (Templates, Settings สำหรับ Suno AI + Freepik + YouTube)
            AiContentWriterSeeder::class,       // 🖊️ AI Content Writer System (Settings, Templates สำหรับสร้าง Content)

            // 15. Service Booking System
            ServiceCategorySeeder::class,       // หมวดหมู่บริการ (นวด, สปา, ทำความสะอาด, จัดส่ง, ช่างไฟ, ซ่อมแอร์ ฯลฯ)
            ServicePricingRuleSeeder::class,    // กฎคำนวณราคาตามระยะทาง (0-5km, 5-10km, 10-20km, 20+km)
            ServiceAreaSeeder::class,           // พื้นที่ให้บริการ (กรุงเทพฯ, นนทบุรี, สมุทรปราการ, เชียงใหม่ ฯลฯ)
            ServiceSeeder::class,               // บริการตัวอย่าง 70+ บริการครบทุกหมวดหมู่
            ServiceProviderSeeder::class,       // ผู้ให้บริการตัวอย่าง 20 คนพร้อมข้อมูลจริง

            // 16. Google Maps Integration
            GoogleMapsSettingsSeeder::class,    // การตั้งค่า Google Maps API (Geocoding, Directions, Distance Matrix, Places)

            // 17. Label & Barcode System
            LabelPaperSizeSeeder::class,        // 🆕 ขนาดกระดาษมาตรฐานสำหรับฉลาก (A4, สติ๊กเกอร์, ใบเสร็จ, Zebra, DYMO, Brother)
            LabelTemplateSeeder::class,         // 🆕 เทมเพลตฉลากเริ่มต้น (ป้ายราคา, ฉลากจัดส่ง, Food Passport, ฉลากเปล่า)
            PosLabelTemplateSeeder::class,      // 🆕 POS Label Templates (Product Label, Shipping Label for POS)

            // 18. Community Forum System
            ForumCategorySeeder::class,         // 🆕 หมวดหมู่ฟอรั่ม (MLM, E-commerce, AI, Crypto, Help, etc.)
            ForumTrophySeeder::class,           // 🆕 โทรฟี่ฟอรั่ม (Positive/Negative badges, ติดชื่อตลอด)

            // 19. Menu Management System
            MenuItemSeeder::class,              // 🆕 ระบบจัดการเมนู (นำเข้าจาก config/menus.php)
            AcademyMenuSeeder::class,           // 🎓 เมนู Academy สำหรับ User Dashboard

            // 20. Mobile App System
            MobileBannerSeeder::class,          // 📱 Mobile App Banners (3 ตัวอย่าง: Welcome, Promo, News)

            // 21. Horoscope Public System (ระบบดูดวงสาธารณะ)
            HoroscopeZodiacSignSeeder::class,       // 🌟 12 ราศี (เมษ→มีน) พร้อมข้อมูลครบ
            HoroscopeDreamCategorySeeder::class,    // 🌙 หมวดหมู่ทำนายฝัน 12 หมวด
            HoroscopeDreamDictionarySeeder::class,  // 💭 พจนานุกรมฝัน 100+ สัญลักษณ์ + เลขเด็ด
            HoroscopeSettingsSeeder::class,         // ⚙️ ค่าเริ่มต้นระบบดูดวงสาธารณะ

            // 22. Fresh Market System (ตลาดสดไทยพร๊อม)
            FreshMarketSeeder::class,               // 🏪 ตลาดสดไทยพร๊อม (Settings + 8 หมวดหมู่สินค้า)

            // 23. Mobile App Config & Feature Flags (2026-08-10)
            //     ⚠️ 2 ตัวนี้ตกหล่นมานาน — SeederVerificationTest จับได้ แต่ CI ตั้ง
            //     continue-on-error ไว้เลยไม่มีใครเห็น (ฝ่าฝืน RULE #1 ใน CLAUDE.md)
            //     ทั้งคู่ idempotent (upsert) รันซ้ำได้ปลอดภัย
            AppConfigSeeder::class,                 // ⚙️ remote config ของแอป Flutter (กัน fallback cache-only)
            FeatureFlagSeeder::class,               // 🚩 feature flags — ทุกตัว default OFF
            FortuneCommentReplySeeder::class,       // 💬 คลังคำตอบคอมเมนต์ 100 ชุด (invite/blessing/thanks/emoji)
        ]);

        $this->command->info('');
        $this->command->info('✨ Database seeding completed successfully!');
        $this->command->info('');
    }
}
