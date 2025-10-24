<?php

namespace Database\Seeders;

use App\Models\VendorFeature;
use App\Models\VendorFeatureVersion;
use App\Models\VendorFeatureChangelog;
use Illuminate\Database\Seeder;

class VendorFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sales & Marketing Features
        $advancedAnalytics = VendorFeature::create([
            'name' => 'Advanced Analytics',
            'slug' => 'advanced-analytics',
            'short_description' => 'ระบบวิเคราะห์ข้อมูลขั้นสูงพร้อม Dashboard แบบเรียลไทม์',
            'description' => 'ระบบวิเคราะห์ข้อมูลการขายแบบละเอียด พร้อมกราฟและรายงานที่ครบครัน ช่วยให้คุณเข้าใจพฤติกรรมลูกค้าและเพิ่มยอดขาย',
            'price' => 299.00,
            'price_type' => 'monthly',
            'current_version' => '1.0.0',
            'features_list' => [
                'Dashboard แบบเรียลไทม์',
                'รายงานยอดขายรายวัน/เดือน/ปี',
                'วิเคราะห์พฤติกรรมลูกค้า',
                'กราฟและแผนภูมิแบบ Interactive',
                'Export รายงานเป็น PDF/Excel',
                'ติดตามสินค้าขายดี',
            ],
            'icon' => 'chart-line',
            'category' => 'analytics',
            'sort_order' => 1,
            'is_active' => true,
            'is_featured' => true,
        ]);

        VendorFeatureVersion::create([
            'vendor_feature_id' => $advancedAnalytics->id,
            'version' => '1.0.0',
            'release_notes' => 'เปิดตัวครั้งแรก - ระบบวิเคราะห์ข้อมูลขั้นสูง',
            'changes' => [
                'Dashboard แบบเรียลไทม์',
                'รายงานยอดขาย',
                'วิเคราะห์พฤติกรรมลูกค้า',
            ],
            'change_type' => 'feature',
            'released_at' => now(),
            'is_major' => true,
        ]);

        $emailMarketing = VendorFeature::create([
            'name' => 'Email Marketing',
            'slug' => 'email-marketing',
            'short_description' => 'ระบบส่งอีเมลการตลาดอัตโนมัติ',
            'description' => 'ส่งอีเมลการตลาดถึงลูกค้าได้อย่างมีประสิทธิภาพ พร้อมระบบ Template สวยงามและ Automation ครบครัน',
            'price' => 199.00,
            'price_type' => 'monthly',
            'current_version' => '1.0.0',
            'features_list' => [
                'Email Template สวยงาม 50+ แบบ',
                'ส่งอีเมลอัตโนมัติ',
                'Segmentation ลูกค้า',
                'A/B Testing',
                'รายงานการเปิดและคลิก',
                'ตั้งเวลาส่งล่วงหน้า',
            ],
            'icon' => 'envelope',
            'category' => 'marketing',
            'sort_order' => 2,
            'is_active' => true,
            'is_featured' => true,
        ]);

        VendorFeatureVersion::create([
            'vendor_feature_id' => $emailMarketing->id,
            'version' => '1.0.0',
            'release_notes' => 'เปิดตัวระบบ Email Marketing',
            'changes' => [
                'Email Templates',
                'Auto-send campaigns',
                'Customer segmentation',
            ],
            'change_type' => 'feature',
            'released_at' => now(),
            'is_major' => true,
        ]);

        $inventoryPro = VendorFeature::create([
            'name' => 'Inventory Pro',
            'slug' => 'inventory-pro',
            'short_description' => 'ระบบจัดการสต็อกสินค้าขั้นสูง',
            'description' => 'จัดการสต็อกสินค้าอย่างมืออาชีพ แจ้งเตือนสินค้าใกล้หมด บาร์โค้ด และการนับสต็อกแบบอัตโนมัติ',
            'price' => 399.00,
            'price_type' => 'monthly',
            'current_version' => '1.0.0',
            'features_list' => [
                'ระบบแจ้งเตือนสินค้าใกล้หมด',
                'บาร์โค้ดสินค้า',
                'จัดการสต็อกหลายคลัง',
                'รายงานการเคลื่อนไหวสินค้า',
                'นับสต็อกอัตโนมัติ',
                'ประวัติการเข้า-ออกสินค้า',
            ],
            'icon' => 'boxes',
            'category' => 'inventory',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $multiChannel = VendorFeature::create([
            'name' => 'Multi-Channel Selling',
            'slug' => 'multi-channel-selling',
            'short_description' => 'ขายข้ามแพลตฟอร์ม Shopee, Lazada, Facebook',
            'description' => 'เชื่อมต่อและจัดการร้านค้าจากหลายแพลตฟอร์มในที่เดียว ซิงค์สินค้าและออเดอร์อัตโนมัติ',
            'price' => 599.00,
            'price_type' => 'monthly',
            'current_version' => '1.0.0',
            'features_list' => [
                'เชื่อมต่อ Shopee, Lazada',
                'เชื่อมต่อ Facebook Shop',
                'ซิงค์สินค้าอัตโนมัติ',
                'ซิงค์ออเดอร์ทั้งหมด',
                'จัดการสต็อกรวม',
                'Dashboard รวมทุกช่องทาง',
            ],
            'icon' => 'store-alt',
            'category' => 'sales',
            'sort_order' => 4,
            'is_active' => true,
            'is_featured' => true,
        ]);

        $loyaltyProgram = VendorFeature::create([
            'name' => 'Loyalty Program',
            'slug' => 'loyalty-program',
            'short_description' => 'ระบบสะสมแต้มและรางวัลสำหรับลูกค้า',
            'description' => 'สร้างความภักดีให้กับลูกค้าด้วยระบบสะสมแต้ม คะแนน และรางวัลพิเศษ',
            'price' => 249.00,
            'price_type' => 'monthly',
            'current_version' => '1.0.0',
            'features_list' => [
                'ระบบสะสมแต้ม',
                'Tier สมาชิก (Silver, Gold, Platinum)',
                'แลกของรางวัล',
                'คูปองส่วนลดพิเศษ',
                'Birthday Rewards',
                'รายงานความภักดี',
            ],
            'icon' => 'gift',
            'category' => 'marketing',
            'sort_order' => 5,
            'is_active' => true,
        ]);

        $customDomain = VendorFeature::create([
            'name' => 'Custom Domain',
            'slug' => 'custom-domain',
            'short_description' => 'ใช้โดเมนของคุณเอง',
            'description' => 'เชื่อมต่อโดเมนของคุณเองกับร้านค้า พร้อม SSL Certificate ฟรี',
            'price' => 499.00,
            'price_type' => 'yearly',
            'current_version' => '1.0.0',
            'features_list' => [
                'เชื่อมต่อ Custom Domain',
                'SSL Certificate ฟรี',
                'Email บนโดเมนของคุณ',
                'การตั้งค่า DNS อัตโนมัติ',
            ],
            'icon' => 'globe',
            'category' => 'branding',
            'sort_order' => 6,
            'is_active' => true,
        ]);

        $seoBoost = VendorFeature::create([
            'name' => 'SEO Boost',
            'slug' => 'seo-boost',
            'short_description' => 'เพิ่มการมองเห็นบน Google',
            'description' => 'ปรับแต่ง SEO ให้ร้านค้าของคุณติดอันดับบน Google และ Search Engines อื่นๆ',
            'price' => 349.00,
            'price_type' => 'monthly',
            'current_version' => '1.0.0',
            'features_list' => [
                'SEO Analysis และคำแนะนำ',
                'Meta Tags อัตโนมัติ',
                'Sitemap Generation',
                'Schema Markup',
                'Open Graph Tags',
                'ติดตามอันดับคีย์เวิร์ด',
            ],
            'icon' => 'search',
            'category' => 'marketing',
            'sort_order' => 7,
            'is_active' => true,
        ]);

        // Add changelogs
        VendorFeatureChangelog::create([
            'vendor_feature_id' => $advancedAnalytics->id,
            'type' => 'added',
            'title' => 'Dashboard แบบเรียลไทม์',
            'description' => 'เพิ่มหน้า Dashboard ที่แสดงข้อมูลแบบเรียลไทม์ อัพเดททุก 30 วินาที',
            'priority' => 'high',
            'published_at' => now(),
        ]);

        VendorFeatureChangelog::create([
            'vendor_feature_id' => $advancedAnalytics->id,
            'type' => 'added',
            'title' => 'รายงาน PDF Export',
            'description' => 'สามารถ Export รายงานเป็นไฟล์ PDF ได้แล้ว',
            'priority' => 'medium',
            'published_at' => now(),
        ]);

        $this->command->info('Vendor features seeded successfully!');
    }
}
