<?php

namespace Database\Seeders;

use App\Models\AICoreFeature;
use Illuminate\Database\Seeder;

/**
 * AI Core Feature Seeder
 *
 * Register AI Features ทั้ง 8 กลุ่มเข้าสู่ระบบ AI Core
 * Features เหล่านี้จะถูกควบคุมโดย AI Core แบบรวมศูนย์
 */
class AICoreFeatureSeeder extends Seeder
{
    /**
     * สร้างข้อมูล AI Core Features
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล AI Core Features...');

        $features = $this->getFeatures();

        foreach ($features as $feature) {
            AICoreFeature::updateOrCreate(
                ['feature_key' => $feature['feature_key']],
                $feature
            );

            $this->command->info("  ✓ {$feature['feature_name']}");
        }

        $this->command->info('✅ Seed ข้อมูล AI Core Features สำเร็จ! (จำนวน ' . count($features) . ' features)');
    }

    /**
     * ดึงข้อมูล AI Features ทั้ง 8 กลุ่ม
     *
     * @return array
     */
    private function getFeatures(): array
    {
        return [
            // 1. AI Bot Marketplace & Rental System
            [
                'feature_key' => 'ai_bot_marketplace',
                'feature_name' => 'ตลาด AI Bots และระบบให้เช่า',
                'feature_name_en' => 'AI Bot Marketplace & Rental System',
                'description' => 'ตลาดกลางสำหรับซื้อขาย และให้เช่า AI bots พร้อมระบบจัดการการติดตั้งและใบอนุญาต',
                'category' => 'marketplace',
                'icon' => 'fas fa-store',
                'display_order' => 1,
                'is_enabled' => true,
                'is_visible' => true,
                'requires_approval' => false,
                'quota_type' => 'count',
                'default_quota_limit' => 10,
                'quota_reset_period' => 'monthly',
                'pricing_tier' => 'basic',
                'base_price' => 499.00,
                'usage_price' => 99.00,
                'allowed_roles' => ['admin', 'user', 'seller'],
                'required_permissions' => ['view_ai_bots'],
                'config' => [
                    'enable_rental' => true,
                    'enable_purchase' => true,
                    'max_installations' => 5,
                    'commission_rate' => 30,
                ],
                'provider_name' => 'Internal',
                'provider_version' => '1.0',
            ],

            // 2. AI Generation System (Image & Video)
            [
                'feature_key' => 'ai_generation',
                'feature_name' => 'ระบบสร้างภาพและวิดีโอด้วย AI',
                'feature_name_en' => 'AI Generation System (Image & Video)',
                'description' => 'สร้างภาพและวิดีโอด้วย AI รองรับหลายโมเดล (DALL-E, Stable Diffusion, Midjourney, etc.)',
                'category' => 'generation',
                'icon' => 'fas fa-image',
                'display_order' => 2,
                'is_enabled' => true,
                'is_visible' => true,
                'requires_approval' => false,
                'quota_type' => 'count',
                'default_quota_limit' => 100,
                'quota_reset_period' => 'monthly',
                'pricing_tier' => 'pro',
                'base_price' => 999.00,
                'usage_price' => 5.00,
                'allowed_roles' => ['admin', 'user'],
                'required_permissions' => ['use_ai_generation'],
                'config' => [
                    'default_model' => 'dall-e-3',
                    'max_image_size' => '1024x1024',
                    'max_video_duration' => 30,
                    'watermark_enabled' => false,
                ],
                'provider_name' => 'OpenAI',
                'provider_version' => '3.0',
            ],

            // 3. LINE Bot AI Integration
            [
                'feature_key' => 'line_bot_ai',
                'feature_name' => 'LINE Bot AI Integration',
                'feature_name_en' => 'LINE Bot AI Integration',
                'description' => 'ผสานระบบ AI เข้ากับ LINE Official Account สำหรับตอบคำถามอัตโนมัติและ personalization',
                'category' => 'line_bot',
                'icon' => 'fab fa-line',
                'display_order' => 3,
                'is_enabled' => true,
                'is_visible' => true,
                'requires_approval' => false,
                'quota_type' => 'usage',
                'default_quota_limit' => 10000,
                'quota_reset_period' => 'monthly',
                'pricing_tier' => 'pro',
                'base_price' => 1499.00,
                'usage_price' => 0.50,
                'allowed_roles' => ['admin', 'user'],
                'required_permissions' => ['manage_line_bots'],
                'config' => [
                    'max_bots' => 3,
                    'enable_rich_menu' => true,
                    'enable_flex_message' => true,
                    'auto_reply' => true,
                    'max_messages_per_day' => 1000,
                ],
                'provider_name' => 'LINE + OpenAI',
                'provider_version' => '2.0',
            ],

            // 4. Bot Automation Platform
            [
                'feature_key' => 'bot_automation',
                'feature_name' => 'แพลตฟอร์มระบบอัตโนมัติด้วย Bot',
                'feature_name_en' => 'Bot Automation Platform',
                'description' => 'สร้างและจัดการ automation workflows ด้วย bots (เช่น auto-reply, data processing, scheduling)',
                'category' => 'automation',
                'icon' => 'fas fa-robot',
                'display_order' => 4,
                'is_enabled' => true,
                'is_visible' => true,
                'requires_approval' => false,
                'quota_type' => 'count',
                'default_quota_limit' => 50,
                'quota_reset_period' => 'monthly',
                'pricing_tier' => 'basic',
                'base_price' => 799.00,
                'usage_price' => 10.00,
                'allowed_roles' => ['admin', 'user'],
                'required_permissions' => ['use_automation'],
                'config' => [
                    'max_workflows' => 10,
                    'max_steps_per_workflow' => 20,
                    'enable_scheduling' => true,
                    'enable_webhooks' => true,
                ],
                'provider_name' => 'Internal',
                'provider_version' => '1.0',
            ],

            // 5. AI Provider Management
            [
                'feature_key' => 'ai_provider',
                'feature_name' => 'จัดการผู้ให้บริการ AI',
                'feature_name_en' => 'AI Provider Management',
                'description' => 'จัดการ API keys และการเชื่อมต่อกับผู้ให้บริการ AI ต่างๆ (OpenAI, Google, Anthropic, etc.)',
                'category' => 'provider',
                'icon' => 'fas fa-plug',
                'display_order' => 5,
                'is_enabled' => true,
                'is_visible' => true,
                'requires_approval' => true,
                'quota_type' => 'none',
                'default_quota_limit' => null,
                'quota_reset_period' => null,
                'pricing_tier' => 'enterprise',
                'base_price' => 2999.00,
                'usage_price' => 0.00,
                'allowed_roles' => ['admin'],
                'required_permissions' => ['manage_ai_providers'],
                'config' => [
                    'supported_providers' => ['openai', 'google', 'anthropic', 'cohere'],
                    'enable_fallback' => true,
                    'enable_load_balancing' => true,
                ],
                'provider_name' => 'Multiple',
                'provider_version' => '1.0',
            ],

            // 6. AI Usage & Analytics
            [
                'feature_key' => 'ai_analytics',
                'feature_name' => 'วิเคราะห์การใช้งาน AI',
                'feature_name_en' => 'AI Usage & Analytics',
                'description' => 'วิเคราะห์การใช้งาน AI, ติดตาม quota, cost, และ performance metrics',
                'category' => 'analytics',
                'icon' => 'fas fa-chart-line',
                'display_order' => 6,
                'is_enabled' => true,
                'is_visible' => true,
                'requires_approval' => false,
                'quota_type' => 'none',
                'default_quota_limit' => null,
                'quota_reset_period' => null,
                'pricing_tier' => 'basic',
                'base_price' => 0.00,
                'usage_price' => 0.00,
                'allowed_roles' => ['admin', 'user'],
                'required_permissions' => ['view_analytics'],
                'config' => [
                    'retention_days' => 90,
                    'enable_export' => true,
                    'export_formats' => ['csv', 'xlsx', 'json'],
                    'enable_alerts' => true,
                ],
                'provider_name' => 'Internal',
                'provider_version' => '1.0',
            ],

            // 7. Trading Bot (AI-Powered)
            [
                'feature_key' => 'trading_bot',
                'feature_name' => 'Trading Bot ขับเคลื่อนด้วย AI',
                'feature_name_en' => 'AI-Powered Trading Bot',
                'description' => 'Bot สำหรับเทรด crypto/stocks โดยใช้ AI วิเคราะห์ตลาดและตัดสินใจอัตโนมัติ',
                'category' => 'trading',
                'icon' => 'fas fa-chart-candlestick',
                'display_order' => 7,
                'is_enabled' => true,
                'is_visible' => true,
                'requires_approval' => true,
                'quota_type' => 'usage',
                'default_quota_limit' => 1000,
                'quota_reset_period' => 'monthly',
                'pricing_tier' => 'enterprise',
                'base_price' => 4999.00,
                'usage_price' => 1.00,
                'allowed_roles' => ['admin', 'user'],
                'required_permissions' => ['use_trading_bot'],
                'config' => [
                    'max_concurrent_trades' => 5,
                    'max_trade_amount' => 10000,
                    'enable_paper_trading' => true,
                    'supported_exchanges' => ['binance', 'bitkub'],
                    'ai_model' => 'gpt-4',
                ],
                'provider_name' => 'OpenAI + Exchange APIs',
                'provider_version' => '1.0',
            ],

            // 8. Chatbot Platform Integration
            [
                'feature_key' => 'chatbot_platform',
                'feature_name' => 'แพลตฟอร์ม Chatbot Integration',
                'feature_name_en' => 'Chatbot Platform Integration',
                'description' => 'ผสานระบบ chatbot กับแพลตฟอร์มต่างๆ (Facebook, Instagram, Web, Mobile)',
                'category' => 'chatbot',
                'icon' => 'fas fa-comments',
                'display_order' => 8,
                'is_enabled' => true,
                'is_visible' => true,
                'requires_approval' => false,
                'quota_type' => 'usage',
                'default_quota_limit' => 5000,
                'quota_reset_period' => 'monthly',
                'pricing_tier' => 'pro',
                'base_price' => 1999.00,
                'usage_price' => 0.30,
                'allowed_roles' => ['admin', 'user'],
                'required_permissions' => ['manage_chatbots'],
                'config' => [
                    'max_bots' => 5,
                    'supported_platforms' => ['facebook', 'instagram', 'web', 'mobile'],
                    'enable_nlp' => true,
                    'enable_training' => true,
                    'max_intents' => 100,
                ],
                'provider_name' => 'Multiple',
                'provider_version' => '1.0',
            ],
        ];
    }
}
