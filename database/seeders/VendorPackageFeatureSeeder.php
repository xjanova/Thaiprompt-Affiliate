<?php

namespace Database\Seeders;

use App\Models\VendorPackageFeature;
use Illuminate\Database\Seeder;

class VendorPackageFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            // Marketing Features
            [
                'feature_name' => 'AI ChatBot',
                'feature_slug' => 'ai-chatbot',
                'display_name' => 'AI ChatBot',
                'description' => 'บอท AI ตอบลูกค้าอัตโนมัติ 24/7 รองรับหลายภาษา สามารถเรียนรู้และปรับปรุงคำตอบ',
                'icon' => 'robot',
                'feature_type' => 'recurring',
                'price' => 499,
                'billing_cycle' => 'monthly',
                'category' => 'ai',
                'required_package_level' => 1,
                'settings_schema' => [
                    'max_conversations' => 1000,
                    'languages' => ['th', 'en'],
                    'integrations' => ['line', 'facebook', 'website'],
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'feature_name' => 'LINE Broadcast',
                'feature_slug' => 'line-broadcast',
                'display_name' => 'LINE Broadcast',
                'description' => 'ส่งข่าวสารการตลาดผ่าน LINE OA ถึงลูกค้าทุกคน แบ่งกลุ่มได้',
                'icon' => 'message-circle',
                'feature_type' => 'recurring',
                'price' => 299,
                'billing_cycle' => 'monthly',
                'category' => 'marketing',
                'required_package_level' => 1,
                'settings_schema' => [
                    'max_messages_per_month' => 5000,
                    'segmentation' => true,
                    'scheduling' => true,
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'feature_name' => 'Email Marketing',
                'feature_slug' => 'email-marketing',
                'display_name' => 'Email Marketing',
                'description' => 'ส่งอีเมลการตลาดถึงลูกค้า สร้างแคมเปญ ออกแบบเทมเพลต',
                'icon' => 'mail',
                'feature_type' => 'recurring',
                'price' => 199,
                'billing_cycle' => 'monthly',
                'category' => 'marketing',
                'required_package_level' => 1,
                'settings_schema' => [
                    'max_emails_per_month' => 10000,
                    'templates' => 50,
                    'automation' => true,
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'feature_name' => 'MLM System',
                'feature_slug' => 'mlm-system',
                'display_name' => 'ระบบ MLM',
                'description' => 'ระบบ Multi-level Marketing ส่วนตัว สามารถกำหนดแผนและคอมมิชชั่นเอง',
                'icon' => 'users',
                'feature_type' => 'recurring',
                'price' => 1999,
                'billing_cycle' => 'monthly',
                'category' => 'marketing',
                'required_package_level' => 2,
                'settings_schema' => [
                    'max_levels' => 10,
                    'commission_plans' => 'unlimited',
                    'genealogy_tree' => true,
                ],
                'is_active' => true,
                'sort_order' => 4,
            ],

            // Analytics Features
            [
                'feature_name' => 'Advanced Analytics',
                'feature_slug' => 'advanced-analytics',
                'display_name' => 'รายงานขั้นสูง',
                'description' => 'รายงานขั้นสูงและ Business Intelligence วิเคราะห์ข้อมูลเชิงลึก',
                'icon' => 'bar-chart',
                'feature_type' => 'recurring',
                'price' => 399,
                'billing_cycle' => 'monthly',
                'category' => 'analytics',
                'required_package_level' => 1,
                'settings_schema' => [
                    'custom_reports' => true,
                    'data_export' => true,
                    'api_access' => true,
                ],
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'feature_name' => 'Heatmap & Behavior',
                'feature_slug' => 'heatmap-behavior',
                'display_name' => 'Heatmap & พฤติกรรม',
                'description' => 'วิเคราะห์พฤติกรรมลูกค้าแบบ Real-time ดู Heatmap และ Session Recording',
                'icon' => 'activity',
                'feature_type' => 'recurring',
                'price' => 299,
                'billing_cycle' => 'monthly',
                'category' => 'analytics',
                'required_package_level' => 1,
                'settings_schema' => [
                    'recordings_per_month' => 10000,
                    'heatmaps' => 'unlimited',
                ],
                'is_active' => true,
                'sort_order' => 6,
            ],

            // Storage Features
            [
                'feature_name' => 'Extra Storage 5GB',
                'feature_slug' => 'extra-storage-5gb',
                'display_name' => 'พื้นที่เพิ่ม 5GB',
                'description' => 'พื้นที่จัดเก็บเพิ่มเติม 5GB สำหรับรูปภาพและไฟล์',
                'icon' => 'hard-drive',
                'feature_type' => 'recurring',
                'price' => 99,
                'billing_cycle' => 'monthly',
                'category' => 'storage',
                'required_package_level' => 0,
                'settings_schema' => [
                    'storage_mb' => 5000,
                ],
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'feature_name' => 'Extra Storage 10GB',
                'feature_slug' => 'extra-storage-10gb',
                'display_name' => 'พื้นที่เพิ่ม 10GB',
                'description' => 'พื้นที่จัดเก็บเพิ่มเติม 10GB สำหรับรูปภาพและไฟล์',
                'icon' => 'hard-drive',
                'feature_type' => 'recurring',
                'price' => 179,
                'billing_cycle' => 'monthly',
                'category' => 'storage',
                'required_package_level' => 0,
                'settings_schema' => [
                    'storage_mb' => 10000,
                ],
                'is_active' => true,
                'sort_order' => 8,
            ],

            // Integration Features
            [
                'feature_name' => 'Facebook Shop Integration',
                'feature_slug' => 'facebook-shop',
                'display_name' => 'Facebook Shop',
                'description' => 'เชื่อมต่อร้านค้ากับ Facebook Shop ซิงค์สินค้าอัตโนมัติ',
                'icon' => 'facebook',
                'feature_type' => 'recurring',
                'price' => 249,
                'billing_cycle' => 'monthly',
                'category' => 'integration',
                'required_package_level' => 1,
                'settings_schema' => [
                    'auto_sync' => true,
                    'inventory_sync' => true,
                ],
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'feature_name' => 'TikTok Shop Integration',
                'feature_slug' => 'tiktok-shop',
                'display_name' => 'TikTok Shop',
                'description' => 'เชื่อมต่อร้านค้ากับ TikTok Shop ซิงค์สินค้าอัตโนมัติ',
                'icon' => 'music',
                'feature_type' => 'recurring',
                'price' => 249,
                'billing_cycle' => 'monthly',
                'category' => 'integration',
                'required_package_level' => 1,
                'settings_schema' => [
                    'auto_sync' => true,
                    'inventory_sync' => true,
                ],
                'is_active' => true,
                'sort_order' => 10,
            ],

            // Special Features
            [
                'feature_name' => 'White Label',
                'feature_slug' => 'white-label',
                'display_name' => 'White Label',
                'description' => 'ลบแบรนด์แพลตฟอร์ม ใช้แบรนด์ของคุณเอง',
                'icon' => 'tag',
                'feature_type' => 'recurring',
                'price' => 2999,
                'billing_cycle' => 'monthly',
                'category' => 'special',
                'required_package_level' => 3,
                'settings_schema' => [
                    'custom_branding' => true,
                    'remove_credits' => true,
                ],
                'is_active' => true,
                'sort_order' => 11,
            ],
            [
                'feature_name' => 'Custom Domain SSL',
                'feature_slug' => 'custom-domain-ssl',
                'display_name' => 'Custom Domain + SSL',
                'description' => 'ใช้โดเมนของคุณเอง พร้อม SSL Certificate ฟรี',
                'icon' => 'globe',
                'feature_type' => 'one_time',
                'price' => 299,
                'billing_cycle' => 'yearly',
                'category' => 'special',
                'required_package_level' => 2,
                'settings_schema' => [
                    'ssl' => true,
                    'dns_management' => true,
                ],
                'is_active' => true,
                'sort_order' => 12,
            ],
        ];

        foreach ($features as $feature) {
            VendorPackageFeature::updateOrCreate(
                ['feature_slug' => $feature['feature_slug']],
                $feature
            );
        }

        $this->command->info('✅ Vendor package features seeded successfully!');
    }
}
