<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\AppThemeSetting;
use App\Models\AppFeature;
use App\Models\AppBanner;
use App\Models\AppMaintenance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AppManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 📌 Smart Seeding Strategy:
     * Preserves existing app management settings, adds missing features/banners only.
     */
    public function run(): void
    {
        $this->command->info('🚀 Seeding App Management data...');

        // 1. Seed App Settings (singleton)
        $this->seedAppSettings();

        // 2. Seed App Theme Settings (singleton)
        $this->seedAppThemeSettings();

        // 3. Seed App Features (add missing only)
        $this->seedAppFeatures();

        // 4. Seed App Banners (add missing only)
        $this->seedAppBanners();

        // 5. Seed App Maintenance (singleton)
        $this->seedAppMaintenance();

        $this->command->info('✅ App Management data seeded successfully!');
    }

    private function seedAppSettings()
    {
        // Check if app settings exist
        if (AppSetting::find(1)) {
            $this->command->warn('⚠️  App Settings already exist - skipping to preserve customizations');
            return;
        }

        $this->command->info('📱 Creating App Settings...');

        AppSetting::create([
            'id' => 1,
                'app_name' => 'TP Affiliate',
                'app_short_name' => 'TP',
                'app_description' => 'ระบบแอฟฟิลิเอทและ MLM ครบวงจร',
                'app_version' => '1.0.0',
                'app_build_number' => '1',
                'force_update' => false,
                'min_supported_version' => '1.0.0',
                'latest_version' => '1.0.0',
                'update_message_th' => 'มีเวอร์ชันใหม่พร้อมให้อัพเดท กรุณาอัพเดทเพื่อประสบการณ์ที่ดีกว่า',
                'update_message_en' => 'A new version is available. Please update for a better experience.',
                'app_store_url' => null,
                'play_store_url' => null,
                'support_email' => 'support@tpaffiliate.com',
                'support_phone' => '02-xxx-xxxx',
                'support_line_id' => '@tpaffiliate',
                'privacy_policy_url' => '/privacy-policy',
                'terms_url' => '/terms-of-service',
                'default_language' => 'th',
                'supported_languages' => ['th', 'en'],
                'currency' => 'THB',
                'timezone' => 'Asia/Bangkok',
                'is_active' => true,
        ]);

        $this->command->info('  ✓ App Settings created');
    }

    private function seedAppThemeSettings()
    {
        // ✅ ตรวจสอบว่าตารางมีอยู่หรือไม่ก่อน (ป้องกัน error กรณีตารางยังไม่ถูกสร้าง)
        if (!Schema::hasTable('app_theme_settings')) {
            $this->command->warn('⚠️  Table app_theme_settings does not exist - skipping (Arrow X theme is used by default)');
            return;
        }

        // Check if theme settings exist
        if (AppThemeSetting::find(1)) {
            $this->command->warn('⚠️  App Theme Settings already exist - skipping to preserve customizations');
            return;
        }

        $this->command->info('🎨 Creating App Theme Settings...');

        AppThemeSetting::create([
            'id' => 1,
                'theme_name' => 'default',
                'logo_url' => '/images/logo.png',
                'logo_dark_url' => '/images/logo-dark.png',
                'favicon_url' => '/favicon.ico',
                'app_icon_url' => '/images/app-icon.png',
                'splash_screen_url' => '/images/splash.png',
                'splash_screen_dark_url' => '/images/splash-dark.png',

                // Primary Colors
                'primary_color' => '#3B82F6',
                'primary_dark_color' => '#2563EB',
                'primary_light_color' => '#60A5FA',

                // Secondary Colors
                'secondary_color' => '#8B5CF6',
                'secondary_dark_color' => '#7C3AED',
                'secondary_light_color' => '#A78BFA',

                // Background Colors
                'background_color' => '#FFFFFF',
                'background_dark_color' => '#1F2937',
                'surface_color' => '#F9FAFB',
                'surface_dark_color' => '#374151',

                // Text Colors
                'text_primary_color' => '#111827',
                'text_primary_dark_color' => '#F9FAFB',
                'text_secondary_color' => '#6B7280',
                'text_secondary_dark_color' => '#D1D5DB',

                // Status Colors
                'success_color' => '#10B981',
                'warning_color' => '#F59E0B',
                'error_color' => '#EF4444',
                'info_color' => '#3B82F6',

                // Border & Divider
                'border_color' => '#E5E7EB',
                'border_dark_color' => '#4B5563',
                'divider_color' => '#E5E7EB',
                'divider_dark_color' => '#4B5563',

                // Fonts
                'font_family' => 'Noto Sans Thai',
                'font_family_en' => 'Inter',
                'font_size_base' => 14,
                'font_size_small' => 12,
                'font_size_large' => 16,
                'font_size_xlarge' => 20,

                // Border Radius
                'border_radius_small' => 4,
                'border_radius_medium' => 8,
                'border_radius_large' => 12,
                'border_radius_xlarge' => 16,

                // Spacing
                'spacing_small' => 8,
                'spacing_medium' => 16,
                'spacing_large' => 24,
                'spacing_xlarge' => 32,

                // Animation
                'animation_duration' => 300,
                'animation_curve' => 'ease-in-out',

                // Dark Mode
                'dark_mode_enabled' => true,
                'dark_mode_default' => false,
                'is_active' => true,
        ]);

        $this->command->info('  ✓ App Theme Settings created');
    }

    private function seedAppFeatures()
    {
        $this->command->info('⚡ Seeding App Features...');

        $features = $this->getAllFeatures();
        $added = 0;
        $skipped = 0;

        foreach ($features as $feature) {
            if (!AppFeature::where('feature_key', $feature['feature_key'])->exists()) {
                AppFeature::create($feature);
                $added++;
            } else {
                $skipped++;
            }
        }

        $this->command->info("  ✓ {$added} features added, {$skipped} existing preserved");
    }

    private function getAllFeatures(): array
    {
        return [
            // General Features
            ['feature_key' => 'dark_mode', 'feature_name' => 'Dark Mode', 'category' => 'general', 'is_enabled' => true, 'sort_order' => 1],
            ['feature_key' => 'push_notifications', 'feature_name' => 'Push Notifications', 'category' => 'general', 'is_enabled' => true, 'sort_order' => 2],
            ['feature_key' => 'biometric_login', 'feature_name' => 'Biometric Login', 'category' => 'general', 'is_enabled' => true, 'sort_order' => 3],
            ['feature_key' => 'multi_language', 'feature_name' => 'Multi Language Support', 'category' => 'general', 'is_enabled' => true, 'sort_order' => 4],

            // Social Features
            ['feature_key' => 'social_sharing', 'feature_name' => 'Social Media Sharing', 'category' => 'social', 'is_enabled' => true, 'sort_order' => 1],
            ['feature_key' => 'line_integration', 'feature_name' => 'LINE Integration', 'category' => 'social', 'is_enabled' => true, 'sort_order' => 2],
            ['feature_key' => 'referral_system', 'feature_name' => 'Referral System', 'category' => 'social', 'is_enabled' => true, 'sort_order' => 3],

            // Payment Features
            ['feature_key' => 'crypto_payment', 'feature_name' => 'Cryptocurrency Payment', 'category' => 'payment', 'is_enabled' => true, 'sort_order' => 1],
            ['feature_key' => 'wallet_system', 'feature_name' => 'Digital Wallet', 'category' => 'payment', 'is_enabled' => true, 'sort_order' => 2],
            ['feature_key' => 'qr_payment', 'feature_name' => 'QR Code Payment', 'category' => 'payment', 'is_enabled' => true, 'sort_order' => 3],
            ['feature_key' => 'withdrawal', 'feature_name' => 'Withdrawal System', 'category' => 'payment', 'is_enabled' => true, 'sort_order' => 4],

            // Affiliate Features
            ['feature_key' => 'commission_tracking', 'feature_name' => 'Commission Tracking', 'category' => 'affiliate', 'is_enabled' => true, 'sort_order' => 1],
            ['feature_key' => 'genealogy_tree', 'feature_name' => 'Genealogy Tree', 'category' => 'affiliate', 'is_enabled' => true, 'sort_order' => 2],
            ['feature_key' => 'rank_system', 'feature_name' => 'Rank & Achievement', 'category' => 'affiliate', 'is_enabled' => true, 'sort_order' => 3],
            ['feature_key' => 'team_performance', 'feature_name' => 'Team Performance', 'category' => 'affiliate', 'is_enabled' => true, 'sort_order' => 4],

            // E-Commerce Features
            ['feature_key' => 'product_catalog', 'feature_name' => 'Product Catalog', 'category' => 'ecommerce', 'is_enabled' => true, 'sort_order' => 1],
            ['feature_key' => 'shopping_cart', 'feature_name' => 'Shopping Cart', 'category' => 'ecommerce', 'is_enabled' => true, 'sort_order' => 2],
            ['feature_key' => 'order_tracking', 'feature_name' => 'Order Tracking', 'category' => 'ecommerce', 'is_enabled' => true, 'sort_order' => 3],
            ['feature_key' => 'product_review', 'feature_name' => 'Product Reviews', 'category' => 'ecommerce', 'is_enabled' => true, 'sort_order' => 4],

            // Learning Features
            ['feature_key' => 'learning_center', 'feature_name' => 'Learning Center', 'category' => 'learning', 'is_enabled' => true, 'sort_order' => 1],
            ['feature_key' => 'video_courses', 'feature_name' => 'Video Courses', 'category' => 'learning', 'is_enabled' => true, 'sort_order' => 2],
            ['feature_key' => 'certificates', 'feature_name' => 'Certificates', 'category' => 'learning', 'is_enabled' => true, 'sort_order' => 3],
        ];
    }

    private function seedAppBanners()
    {
        $this->command->info('🎯 Seeding App Banners...');

        $banners = $this->getAllBanners();
        $added = 0;
        $skipped = 0;

        foreach ($banners as $banner) {
            $exists = AppBanner::where('title', $banner['title'])
                ->where('position', $banner['position'])
                ->exists();

            if (!$exists) {
                AppBanner::create($banner);
                $added++;
            } else {
                $skipped++;
            }
        }

        $this->command->info("  ✓ {$added} banners added, {$skipped} existing preserved");
    }

    private function getAllBanners(): array
    {
        return [
            [
                'title' => 'ยินดีต้อนรับสู่ TP Affiliate',
                'title_en' => 'Welcome to TP Affiliate',
                'description' => 'เริ่มต้นสร้างรายได้ง่ายๆ กับระบบแอฟฟิลิเอทที่ดีที่สุด',
                'description_en' => 'Start earning easily with the best affiliate system',
                'type' => 'info',
                'position' => 'home',
                'is_active' => true,
                'is_dismissible' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'โปรโมชั่นพิเศษ!',
                'title_en' => 'Special Promotion!',
                'description' => 'รับโบนัส 10% สำหรับการแนะนำเพื่อน 5 คนแรก',
                'description_en' => 'Get 10% bonus for referring your first 5 friends',
                'type' => 'promotion',
                'position' => 'home',
                'is_active' => false,
                'is_dismissible' => true,
                'sort_order' => 2,
            ],
        ];
    }

    private function seedAppMaintenance()
    {
        // Check if maintenance settings exist
        if (AppMaintenance::find(1)) {
            $this->command->warn('⚠️  App Maintenance already exists - skipping to preserve customizations');
            return;
        }

        $this->command->info('🔧 Creating App Maintenance settings...');

        AppMaintenance::create([
            'id' => 1,
            'is_maintenance_mode' => false,
            'title' => 'ระบบกำลังปรับปรุง',
            'title_en' => 'Under Maintenance',
            'message' => 'ขออภัยในความไม่สะดวก ขณะนี้ระบบกำลังอยู่ระหว่างการปรับปรุงเพื่อพัฒนาประสิทธิภาพให้ดียิ่งขึ้น กรุณากลับมาใช้งานอีกครั้งในภายหลัง',
            'message_en' => 'Sorry for the inconvenience. The system is currently under maintenance to improve performance. Please come back later.',
            'image_url' => null,
            'scheduled_start' => null,
            'scheduled_end' => null,
            'show_countdown' => true,
            'allow_admin_access' => true,
            'allowed_user_ids' => null,
            'bypass_key' => null,
            'platform' => 'all',
        ]);

        $this->command->info('  ✓ App Maintenance settings created');
    }
}
