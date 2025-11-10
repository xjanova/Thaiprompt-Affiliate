<?php

namespace Database\Seeders;

use App\Models\SoftwareProduct;
use App\Models\SoftwareProductCategory;
use App\Models\SoftwareProductOption;
use App\Models\SoftwareProductOptionValue;
use Illuminate\Database\Seeder;

class SoftwareProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 📌 Smart Seeding Strategy:
     * Adds missing software products, categories, options, and values only.
     * Preserves existing customizations at all levels.
     */
    public function run(): void
    {
        $this->command->info('🚀 Seeding Software Products...');

        // Seed categories (add missing only)
        $mlmCategory = $this->seedCategory('mlm-network-marketing', [
            'name' => 'ระบบ MLM / เครือข่าย',
            'description' => 'ระบบเครือข่ายการตลาดแบบหลายระดับ (MLM) ที่ครบครันและปรับแต่งได้',
            'icon' => 'fas fa-network-wired',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $ecommerceCategory = $this->seedCategory('ecommerce', [
            'name' => 'ระบบอีคอมเมิร์ซ',
            'description' => 'ระบบร้านค้าออนไลน์ที่ทันสมัยและปรับแต่งได้ง่าย',
            'icon' => 'fas fa-shopping-cart',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $affiliateCategory = $this->seedCategory('affiliate-system', [
            'name' => 'ระบบแอฟฟิลิเอท',
            'description' => 'ระบบจัดการแอฟฟิลิเอทและคอมมิชชันที่ทรงพลัง',
            'icon' => 'fas fa-users',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Seed MLM Product (only if doesn't exist)
        $mlmProduct = $this->seedMLMProduct($mlmCategory);

        // Seed Options and Values for MLM Product (add missing only)
        if ($mlmProduct) {
            $this->seedMLMProductOptions($mlmProduct);
        }

        $this->command->info('✅ Software Products seeding completed!');
    }

    /**
     * Seed a category (add if missing, preserve if exists)
     */
    private function seedCategory($slug, $data)
    {
        $category = SoftwareProductCategory::where('slug', $slug)->first();

        if ($category) {
            $this->command->info("   ⏭️  Category '{$data['name']}' already exists - preserved");
            return $category;
        }

        $category = SoftwareProductCategory::create(array_merge(['slug' => $slug], $data));
        $this->command->info("   ➕ Created category: {$data['name']}");

        return $category;
    }

    /**
     * Seed MLM Product (only if doesn't exist)
     */
    private function seedMLMProduct($category)
    {
        $slug = 'complete-mlm-system';
        $product = SoftwareProduct::where('slug', $slug)->first();

        if ($product) {
            $this->command->warn('⚠️  MLM Product already exists - skipping to preserve customizations');
            return $product;
        }

        $this->command->info('📦 Creating MLM Product...');

        $product = SoftwareProduct::create([
            'category_id' => $category->id,
            'slug' => $slug,
            'name' => 'ระบบ MLM / เครือข่ายแบบครบวงจร',
            'short_description' => 'ระบบ MLM ที่ครบครันที่สุด รองรับทุกแผนการตลาดเครือข่าย พร้อมฟีเจอร์ที่ปรับแต่งได้ตามต้องการ',
            'description' => '<h3>ระบบ MLM ที่สมบูรณ์แบบที่สุด</h3>
            <p>ระบบเครือข่ายการตลาดแบบหลายระดับ (MLM) ที่ออกแบบมาเพื่อธุรกิจของคุณโดยเฉพาะ</p>
            <ul>
                <li>รองรับแผนการตลาดครบทุกรูปแบบ: Binary, Unilevel, Matrix, Hybrid</li>
                <li>ระบบคำนวณคอมมิชชันอัตโนมัติแม่นยำ 100%</li>
                <li>ระบบจัดการสมาชิกและโครงสร้างเครือข่าย</li>
                <li>ระบบอีคอมเมิร์ซในตัว รองรับการขายสินค้า</li>
                <li>ระบบ E-Wallet และถอนเงิน</li>
                <li>รายงานและกราฟวิเคราะห์แบบเรียลไทม์</li>
                <li>ระบบสมาชิกหลายระดับ (Membership Tiers)</li>
                <li>ระบบโบนัสและรางวัลพิเศษ</li>
                <li>Mobile Responsive ใช้งานได้ทุกอุปกรณ์</li>
                <li>API สำหรับเชื่อมต่อระบบอื่น</li>
            </ul>',
            'features' => [
                'ระบบจัดการสมาชิกและเครือข่าย',
                'คำนวณคอมมิชชันอัตโนมัติ',
                'ระบบอีคอมเมิร์ซในตัว',
                'E-Wallet และการถอนเงิน',
                'รายงานและวิเคราะห์ข้อมูล',
                'ระบบแต้มสะสม (PV/BV)',
                'ระบบโบนัสและรางวัล',
                'ระบบสมัครสมาชิกแบบหลายชั้น',
                'Dashboard แบบเรียลไทม์',
                'Mobile Application (iOS & Android)',
            ],
            'base_price' => 150000.00,
            'price_label' => 'เริ่มต้น',
            'currency' => 'THB',
            'is_customizable' => true,
            'is_featured' => true,
            'is_active' => true,
            'requires_consultation' => false,
            'estimated_delivery_days' => 60,
            'tags' => ['MLM', 'Network Marketing', 'เครือข่าย', 'คอมมิชชัน'],
            'sort_order' => 1,
        ]);

        $this->command->info('  ✓ MLM Product created');

        return $product;
    }

    /**
     * Seed all MLM product options and values
     */
    private function seedMLMProductOptions($product)
    {
        $this->command->info('⚙️  Seeding Product Options...');

        // Option 1: MLM Plan
        $mlmPlanOption = $this->seedOption($product, 'mlm_plan', [
            'display_name' => 'แผนการตลาด MLM',
            'description' => 'เลือกรูปแบบแผนการตลาดเครือข่ายที่ต้องการ',
            'input_type' => 'checkbox',
            'is_required' => true,
            'allow_multiple' => true,
            'min_selections' => 1,
            'max_selections' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->seedOptionValue($mlmPlanOption, 'binary', [
            'display_label' => 'Binary Plan (แผนแบบทวิภาค)',
            'description' => 'แผนแบบทวิภาค มีสายซ้าย-ขวา เหมาะสำหรับการสร้างความสมดุล',
            'price_modifier' => 0.00,
            'price_type' => 'fixed',
            'setup_fee' => 0.00,
            'monthly_fee' => 0.00,
            'is_default' => true,
            'is_recommended' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->seedOptionValue($mlmPlanOption, 'unilevel', [
            'display_label' => 'Unilevel Plan (แผนแบบระดับเดียว)',
            'description' => 'แผนแบบระดับเดียว สามารถมีลูกทีมได้ไม่จำกัด',
            'price_modifier' => 25000.00,
            'price_type' => 'fixed',
            'setup_fee' => 0.00,
            'monthly_fee' => 0.00,
            'is_default' => false,
            'is_recommended' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->seedOptionValue($mlmPlanOption, 'matrix', [
            'display_label' => 'Matrix Plan (แผนแบบเมทริกซ์)',
            'description' => 'แผนแบบเมทริกซ์ เช่น 3x3, 4x5, จำกัดจำนวนสาขาในแต่ละระดับ',
            'price_modifier' => 35000.00,
            'price_type' => 'fixed',
            'setup_fee' => 0.00,
            'monthly_fee' => 0.00,
            'is_default' => false,
            'is_recommended' => false,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $this->seedOptionValue($mlmPlanOption, 'hybrid', [
            'display_label' => 'Hybrid Plan (แผนแบบผสม)',
            'description' => 'รวมแผนหลายแบบเข้าด้วยกัน ปรับแต่งได้ตามความต้องการ',
            'price_modifier' => 50000.00,
            'price_type' => 'fixed',
            'setup_fee' => 10000.00,
            'monthly_fee' => 0.00,
            'is_default' => false,
            'is_recommended' => false,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // Option 2: Member Limit
        $memberLimitOption = $this->seedOption($product, 'member_limit', [
            'display_name' => 'จำนวนสมาชิกสูงสุด',
            'description' => 'เลือกจำนวนสมาชิกสูงสุดที่ระบบรองรับ',
            'input_type' => 'radio',
            'is_required' => true,
            'allow_multiple' => false,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->seedOptionValue($memberLimitOption, '1000', [
            'display_label' => '1,000 สมาชิก',
            'description' => 'เหมาะสำหรับธุรกิจขนาดเล็ก-กลาง',
            'price_modifier' => 0.00,
            'price_type' => 'fixed',
            'is_default' => true,
            'is_recommended' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->seedOptionValue($memberLimitOption, '5000', [
            'display_label' => '5,000 สมาชิก',
            'description' => 'เหมาะสำหรับธุรกิจขนาดกลาง',
            'price_modifier' => 30000.00,
            'price_type' => 'fixed',
            'is_default' => false,
            'is_recommended' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->seedOptionValue($memberLimitOption, '10000', [
            'display_label' => '10,000 สมาชิก',
            'description' => 'เหมาะสำหรับธุรกิจขนาดใหญ่',
            'price_modifier' => 50000.00,
            'price_type' => 'fixed',
            'is_default' => false,
            'is_recommended' => false,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $this->seedOptionValue($memberLimitOption, 'unlimited', [
            'display_label' => 'ไม่จำกัดจำนวนสมาชิก',
            'description' => 'สำหรับธุรกิจที่ต้องการขยายไม่จำกัด',
            'price_modifier' => 100000.00,
            'price_type' => 'fixed',
            'is_default' => false,
            'is_recommended' => false,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // Option 3: Additional Features
        $additionalFeaturesOption = $this->seedOption($product, 'additional_features', [
            'display_name' => 'ฟีเจอร์เพิ่มเติม',
            'description' => 'เลือกฟีเจอร์เพิ่มเติมที่ต้องการ (เลือกได้หลายรายการ)',
            'input_type' => 'checkbox',
            'is_required' => false,
            'allow_multiple' => true,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $this->seedOptionValue($additionalFeaturesOption, 'mobile_app', [
            'display_label' => 'Mobile Application (iOS & Android)',
            'description' => 'แอปพลิเคชันมือถือสำหรับสมาชิก รองรับทั้ง iOS และ Android',
            'price_modifier' => 80000.00,
            'price_type' => 'fixed',
            'setup_fee' => 20000.00,
            'monthly_fee' => 5000.00,
            'is_default' => false,
            'is_recommended' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->seedOptionValue($additionalFeaturesOption, 'ewallet', [
            'display_label' => 'ระบบ E-Wallet และถอนเงิน',
            'description' => 'ระบบกระเป๋าเงินอิเล็กทรอนิกส์ พร้อมระบบถอนเงิน',
            'price_modifier' => 45000.00,
            'price_type' => 'fixed',
            'setup_fee' => 5000.00,
            'monthly_fee' => 2000.00,
            'is_default' => false,
            'is_recommended' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->seedOptionValue($additionalFeaturesOption, 'replicated_website', [
            'display_label' => 'Replicated Website (เว็บไซต์ส่วนตัวสำหรับสมาชิก)',
            'description' => 'เว็บไซต์ส่วนตัวให้สมาชิกทุกคน พร้อมระบบแนะนำสมาชิกใหม่',
            'price_modifier' => 35000.00,
            'price_type' => 'fixed',
            'setup_fee' => 0.00,
            'monthly_fee' => 1500.00,
            'is_default' => false,
            'is_recommended' => false,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $this->seedOptionValue($additionalFeaturesOption, 'training_center', [
            'display_label' => 'ศูนย์ฝึกอบรมออนไลน์ (Learning Management System)',
            'description' => 'ระบบจัดการหลักสูตรอบรมออนไลน์สำหรับสมาชิก',
            'price_modifier' => 40000.00,
            'price_type' => 'fixed',
            'setup_fee' => 5000.00,
            'monthly_fee' => 0.00,
            'is_default' => false,
            'is_recommended' => false,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        $this->seedOptionValue($additionalFeaturesOption, 'sms_notification', [
            'display_label' => 'ระบบแจ้งเตือน SMS',
            'description' => 'ส่ง SMS แจ้งเตือนให้สมาชิกอัตโนมัติ',
            'price_modifier' => 15000.00,
            'price_type' => 'fixed',
            'setup_fee' => 2000.00,
            'monthly_fee' => 1000.00,
            'is_default' => false,
            'is_recommended' => false,
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $this->seedOptionValue($additionalFeaturesOption, 'line_integration', [
            'display_label' => 'เชื่อมต่อ LINE Official Account',
            'description' => 'เชื่อมต่อกับ LINE OA พร้อมระบบแชทบอทและแจ้งเตือน',
            'price_modifier' => 25000.00,
            'price_type' => 'fixed',
            'setup_fee' => 5000.00,
            'monthly_fee' => 2000.00,
            'is_default' => false,
            'is_recommended' => true,
            'is_active' => true,
            'sort_order' => 6,
        ]);

        $this->seedOptionValue($additionalFeaturesOption, 'multi_currency', [
            'display_label' => 'รองรับหลายสกุลเงิน (Multi-Currency)',
            'description' => 'รองรับการทำธุรกรรมในหลายสกุลเงิน',
            'price_modifier' => 20000.00,
            'price_type' => 'fixed',
            'setup_fee' => 0.00,
            'monthly_fee' => 0.00,
            'is_default' => false,
            'is_recommended' => false,
            'is_active' => true,
            'sort_order' => 7,
        ]);

        $this->seedOptionValue($additionalFeaturesOption, 'multi_language', [
            'display_label' => 'รองรับหลายภาษา (Multi-Language)',
            'description' => 'รองรับการแสดงผลหลายภาษา (ไทย, อังกฤษ, จีน, ญี่ปุ่น)',
            'price_modifier' => 18000.00,
            'price_type' => 'fixed',
            'setup_fee' => 0.00,
            'monthly_fee' => 0.00,
            'is_default' => false,
            'is_recommended' => false,
            'is_active' => true,
            'sort_order' => 8,
        ]);

        // Option 4: Installation & Training
        $installationOption = $this->seedOption($product, 'installation_training', [
            'display_name' => 'การติดตั้งและอบรม',
            'description' => 'เลือกแพ็คเกจการติดตั้งและอบรมใช้งาน',
            'input_type' => 'radio',
            'is_required' => true,
            'allow_multiple' => false,
            'sort_order' => 4,
            'is_active' => true,
        ]);

        $this->seedOptionValue($installationOption, 'basic', [
            'display_label' => 'แพ็คเกจพื้นฐาน',
            'description' => 'ติดตั้งระบบและอบรมพื้นฐาน 1 วัน',
            'price_modifier' => 0.00,
            'price_type' => 'fixed',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->seedOptionValue($installationOption, 'standard', [
            'display_label' => 'แพ็คเกจมาตรฐาน',
            'description' => 'ติดตั้งระบบ + อบรมทีมงาน 3 วัน + Support 3 เดือน',
            'price_modifier' => 25000.00,
            'price_type' => 'fixed',
            'is_default' => false,
            'is_recommended' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->seedOptionValue($installationOption, 'premium', [
            'display_label' => 'แพ็คเกจพรีเมียม',
            'description' => 'ติดตั้งระบบ + อบรมเชิงลึก 5 วัน + Support 1 ปี + ปรับแต่งตามต้องการ',
            'price_modifier' => 50000.00,
            'price_type' => 'fixed',
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Option 5: Maintenance
        $maintenanceOption = $this->seedOption($product, 'maintenance', [
            'display_name' => 'แผนบำรุงรักษาและ Support',
            'description' => 'เลือกแผนบำรุงรักษาระบบรายเดือน',
            'input_type' => 'radio',
            'is_required' => true,
            'allow_multiple' => false,
            'sort_order' => 5,
            'is_active' => true,
        ]);

        $this->seedOptionValue($maintenanceOption, 'basic', [
            'display_label' => 'แผนพื้นฐาน',
            'description' => 'Support ผ่าน Email, อัพเดทระบบพื้นฐาน',
            'price_modifier' => 0.00,
            'price_type' => 'fixed',
            'monthly_fee' => 3000.00,
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->seedOptionValue($maintenanceOption, 'standard', [
            'display_label' => 'แผนมาตรฐาน',
            'description' => 'Support ผ่าน Email + LINE, อัพเดทระบบ, แก้ไข Bug ภายใน 24 ชม.',
            'price_modifier' => 0.00,
            'price_type' => 'fixed',
            'monthly_fee' => 5000.00,
            'is_default' => false,
            'is_recommended' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->seedOptionValue($maintenanceOption, 'premium', [
            'display_label' => 'แผนพรีเมียม',
            'description' => 'Support ผ่านทุกช่องทาง, อัพเดทระบบ, แก้ไขปัญหาภายใน 4 ชม., ปรับแต่งฟีเจอร์เล็กน้อย',
            'price_modifier' => 0.00,
            'price_type' => 'fixed',
            'monthly_fee' => 10000.00,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $this->seedOptionValue($maintenanceOption, 'enterprise', [
            'display_label' => 'แผนองค์กร',
            'description' => 'Support แบบ VIP 24/7, Dedicated Team, ปรับแต่งฟีเจอร์ได้ไม่จำกัด',
            'price_modifier' => 0.00,
            'price_type' => 'fixed',
            'monthly_fee' => 20000.00,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // Option 6: Hosting
        $hostingOption = $this->seedOption($product, 'hosting', [
            'display_name' => 'โฮสติ้งและโดเมน',
            'description' => 'เลือกแพ็คเกจโฮสติ้งสำหรับระบบ',
            'input_type' => 'radio',
            'is_required' => false,
            'allow_multiple' => false,
            'sort_order' => 6,
            'is_active' => true,
        ]);

        $this->seedOptionValue($hostingOption, 'self_hosted', [
            'display_label' => 'ติดตั้งบน Server ของลูกค้าเอง',
            'description' => 'ลูกค้าจัดเตรียม Server และโดเมนเอง',
            'price_modifier' => 0.00,
            'price_type' => 'fixed',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->seedOptionValue($hostingOption, 'cloud_basic', [
            'display_label' => 'Cloud Hosting พื้นฐาน',
            'description' => 'Cloud Server + Domain + SSL (รองรับ 1,000 สมาชิก)',
            'price_modifier' => 0.00,
            'price_type' => 'fixed',
            'setup_fee' => 5000.00,
            'monthly_fee' => 3000.00,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->seedOptionValue($hostingOption, 'cloud_premium', [
            'display_label' => 'Cloud Hosting พรีเมียม',
            'description' => 'High Performance Cloud + Domain + SSL + CDN (รองรับ 10,000 สมาชิก)',
            'price_modifier' => 0.00,
            'price_type' => 'fixed',
            'setup_fee' => 8000.00,
            'monthly_fee' => 8000.00,
            'is_default' => false,
            'is_recommended' => true,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $this->seedOptionValue($hostingOption, 'cloud_enterprise', [
            'display_label' => 'Cloud Hosting องค์กร',
            'description' => 'Dedicated Cloud Server + Load Balancer + Auto Scaling (ไม่จำกัดสมาชิก)',
            'price_modifier' => 0.00,
            'price_type' => 'fixed',
            'setup_fee' => 15000.00,
            'monthly_fee' => 15000.00,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        $this->command->info('  ✓ Product options and values seeded');
    }

    /**
     * Seed an option (add if missing, preserve if exists)
     */
    private function seedOption($product, $name, $data)
    {
        $option = SoftwareProductOption::where('software_product_id', $product->id)
            ->where('name', $name)
            ->first();

        if ($option) {
            return $option;
        }

        $option = SoftwareProductOption::create(array_merge(
            ['software_product_id' => $product->id, 'name' => $name],
            $data
        ));

        return $option;
    }

    /**
     * Seed an option value (add if missing, preserve if exists)
     */
    private function seedOptionValue($option, $value, $data)
    {
        $optionValue = SoftwareProductOptionValue::where('software_product_option_id', $option->id)
            ->where('value', $value)
            ->first();

        if ($optionValue) {
            return;
        }

        SoftwareProductOptionValue::create(array_merge(
            ['software_product_option_id' => $option->id, 'value' => $value],
            $data
        ));
    }
}
