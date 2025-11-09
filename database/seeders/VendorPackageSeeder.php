<?php

namespace Database\Seeders;

use App\Models\VendorPackage;
use Illuminate\Database\Seeder;

class VendorPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 📌 Smart Seeding Strategy:
     * Adds missing vendor packages only, preserves existing configurations.
     * User customizations (prices, limits, features, commission rates) are preserved.
     */
    public function run(): void
    {
        $existingPackagesCount = VendorPackage::count();

        if ($existingPackagesCount > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh install mode - seed all vendor packages
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: Seeding all vendor packages...');

        $packages = $this->getAllPackages();

        foreach ($packages as $package) {
            VendorPackage::create($package);
        }

        $this->command->info('✅ Vendor packages seeded successfully: ' . count($packages) . ' packages');
    }

    /**
     * Update mode - add only missing vendor packages
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  Existing vendor packages detected!');
        $this->command->info('   Running in UPDATE mode (adding missing packages only)...');

        $packages = $this->getAllPackages();
        $added = 0;
        $skipped = 0;

        foreach ($packages as $package) {
            if (!VendorPackage::where('package_slug', $package['package_slug'])->exists()) {
                VendorPackage::create($package);
                $this->command->info("   ➕ Added: {$package['package_name']}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ Added {$added} new vendor packages.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing packages (preserved).");
        }
    }

    /**
     * Get all default vendor packages
     */
    private function getAllPackages(): array
    {
        return [
            [
                'package_name' => 'Free',
                'package_slug' => 'free',
                'display_name' => 'แพ็คเกจฟรี',
                'description' => 'เหมาะสำหรับร้านค้าเริ่มต้น ทดลองใช้งานฟรี',
                'features' => [
                    'สินค้าได้สูงสุด 10 รายการ',
                    'รูปภาพสินค้าได้ 5 รูปต่อสินค้า',
                    'พื้นที่จัดเก็บ 100 MB',
                    'คำสั่งซื้อ 50 รายการต่อเดือน',
                    'รองรับการชำระเงินพื้นฐาน',
                    'รายงานพื้นฐาน',
                ],
                'price' => 0,
                'yearly_price' => null,
                'setup_fee' => 0,
                'currency' => 'THB',
                'max_products' => 10,
                'max_images_per_product' => 5,
                'max_categories' => 5,
                'max_storage_mb' => 100,
                'max_monthly_orders' => 50,
                'commission_rate' => 15.00,
                'allow_custom_domain' => false,
                'allow_custom_theme' => false,
                'allow_api_access' => false,
                'allow_export_data' => false,
                'allow_advanced_analytics' => false,
                'allow_bulk_operations' => false,
                'allow_ai_bot' => false,
                'allow_marketing_tools' => false,
                'priority_support' => false,
                'trial_days' => 0,
                'badge' => null,
                'badge_color' => null,
                'sort_order' => 1,
                'is_featured' => false,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'package_name' => 'Basic',
                'package_slug' => 'basic',
                'display_name' => 'แพ็คเกจพื้นฐาน',
                'description' => 'เหมาะสำหรับร้านค้าขนาดกลาง มีฟีเจอร์ครบ',
                'features' => [
                    'สินค้าได้สูงสุด 100 รายการ',
                    'รูปภาพสินค้าได้ 10 รูปต่อสินค้า',
                    'พื้นที่จัดเก็บ 1 GB',
                    'คำสั่งซื้อ 500 รายการต่อเดือน',
                    'ปรับแต่ง Theme ได้',
                    'เครื่องมือการตลาด',
                    'ส่งออกข้อมูล',
                    'รายงานขั้นสูง',
                    'ทดลองใช้ฟรี 14 วัน',
                ],
                'price' => 999,
                'yearly_price' => 9999,
                'setup_fee' => 0,
                'currency' => 'THB',
                'max_products' => 100,
                'max_images_per_product' => 10,
                'max_categories' => 20,
                'max_storage_mb' => 1000,
                'max_monthly_orders' => 500,
                'commission_rate' => 10.00,
                'allow_custom_domain' => false,
                'allow_custom_theme' => true,
                'allow_api_access' => false,
                'allow_export_data' => true,
                'allow_advanced_analytics' => false,
                'allow_bulk_operations' => true,
                'allow_ai_bot' => false,
                'allow_marketing_tools' => true,
                'priority_support' => false,
                'trial_days' => 14,
                'badge' => 'ยอดนิยม',
                'badge_color' => '#3B82F6',
                'sort_order' => 2,
                'is_featured' => true,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'package_name' => 'Premium',
                'package_slug' => 'premium',
                'display_name' => 'แพ็คเกจพรีเมียม',
                'description' => 'เหมาะสำหรับร้านค้าขนาดใหญ่ ไม่จำกัดสินค้า',
                'features' => [
                    'สินค้าไม่จำกัด',
                    'รูปภาพสินค้าได้ 20 รูปต่อสินค้า',
                    'พื้นที่จัดเก็บ 5 GB',
                    'คำสั่งซื้อไม่จำกัด',
                    'Custom Domain',
                    'ปรับแต่ง Theme ได้',
                    'API Access',
                    'ส่งออกข้อมูล',
                    'รายงานขั้นสูง',
                    'จัดการแบบกลุ่ม',
                    'AI Bot',
                    'เครื่องมือการตลาด',
                    'ลำดับความสำคัญในการสนับสนุน',
                    'ทดลองใช้ฟรี 30 วัน',
                ],
                'price' => 2999,
                'yearly_price' => 29999,
                'setup_fee' => 0,
                'currency' => 'THB',
                'max_products' => null,
                'max_images_per_product' => 20,
                'max_categories' => null,
                'max_storage_mb' => 5000,
                'max_monthly_orders' => null,
                'commission_rate' => 7.00,
                'allow_custom_domain' => true,
                'allow_custom_theme' => true,
                'allow_api_access' => true,
                'allow_export_data' => true,
                'allow_advanced_analytics' => true,
                'allow_bulk_operations' => true,
                'allow_ai_bot' => true,
                'allow_marketing_tools' => true,
                'priority_support' => true,
                'trial_days' => 30,
                'badge' => 'คุ้มที่สุด',
                'badge_color' => '#8B5CF6',
                'sort_order' => 3,
                'is_featured' => true,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'package_name' => 'Enterprise',
                'package_slug' => 'enterprise',
                'display_name' => 'แพ็คเกจองค์กร',
                'description' => 'เหมาะสำหรับองค์กร ปรับแต่งได้ตามต้องการ',
                'features' => [
                    'ทุกอย่างใน Premium',
                    'ไม่จำกัดทุกอย่าง',
                    'พื้นที่จัดเก็บไม่จำกัด',
                    'Account Manager เฉพาะ',
                    'Custom Development',
                    'ความปลอดภัยขั้นสูง',
                    'SLA 99.9%',
                    'Training & Onboarding',
                    'White Label',
                ],
                'price' => 0, // Custom pricing
                'yearly_price' => null,
                'setup_fee' => 0,
                'currency' => 'THB',
                'max_products' => null,
                'max_images_per_product' => null,
                'max_categories' => null,
                'max_storage_mb' => null,
                'max_monthly_orders' => null,
                'commission_rate' => 5.00,
                'allow_custom_domain' => true,
                'allow_custom_theme' => true,
                'allow_api_access' => true,
                'allow_export_data' => true,
                'allow_advanced_analytics' => true,
                'allow_bulk_operations' => true,
                'allow_ai_bot' => true,
                'allow_marketing_tools' => true,
                'priority_support' => true,
                'trial_days' => 30,
                'badge' => 'องค์กร',
                'badge_color' => '#EAB308',
                'sort_order' => 4,
                'is_featured' => false,
                'is_active' => true,
                'is_default' => false,
            ],
        ];
    }
}
