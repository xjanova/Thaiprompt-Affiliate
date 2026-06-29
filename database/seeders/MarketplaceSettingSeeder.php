<?php

namespace Database\Seeders;

use App\Models\MarketplaceSetting;
use Illuminate\Database\Seeder;

/**
 * MarketplaceSettingSeeder
 *
 * สร้างข้อมูลเริ่มต้นสำหรับการตั้งค่า Marketplace
 * ป้องกัน settings หายหลัง deploy
 */
class MarketplaceSettingSeeder extends Seeder
{
    /**
     * สร้างข้อมูลเริ่มต้น Marketplace Settings
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล Marketplace Settings...');

        $settings = $this->getDefaultSettings();
        $created = 0;
        $skipped = 0;

        foreach ($settings as $setting) {
            // ตรวจสอบว่ามีอยู่แล้วหรือไม่
            if (MarketplaceSetting::where('setting_key', $setting['setting_key'])->exists()) {
                $skipped++;

                continue;
            }

            MarketplaceSetting::create($setting);
            $created++;
        }

        if ($created > 0) {
            $this->command->info("  ✓ สร้าง Marketplace Settings ใหม่ {$created} รายการ");
        }
        if ($skipped > 0) {
            $this->command->info("  - ข้าม {$skipped} รายการ (มีอยู่แล้ว)");
        }

        $this->command->info('✅ Seed ข้อมูล Marketplace Settings สำเร็จ!');
    }

    /**
     * ดึงการตั้งค่าเริ่มต้น
     */
    protected function getDefaultSettings(): array
    {
        return [
            // Platform Settings
            [
                'setting_key' => 'platform_fee_percentage',
                'setting_value' => '10',
                'setting_type' => 'decimal',
                'description' => 'ค่าธรรมเนียม Platform จากการขาย (%)',
                'is_public' => false,
            ],
            [
                'setting_key' => 'vat_percentage',
                'setting_value' => '7',
                'setting_type' => 'decimal',
                'description' => 'ภาษีมูลค่าเพิ่ม VAT (%)',
                'is_public' => true,
            ],
            [
                'setting_key' => 'min_product_price',
                'setting_value' => '1',
                'setting_type' => 'decimal',
                'description' => 'ราคาสินค้าขั้นต่ำ (บาท)',
                'is_public' => true,
            ],
            [
                'setting_key' => 'max_product_price',
                'setting_value' => '9999999',
                'setting_type' => 'decimal',
                'description' => 'ราคาสินค้าสูงสุด (บาท)',
                'is_public' => true,
            ],
            // Seller Settings
            [
                'setting_key' => 'seller_verification_required',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'description' => 'ต้องยืนยันตัวตนก่อนขายสินค้า',
                'is_public' => true,
            ],
            [
                'setting_key' => 'auto_approve_products',
                'setting_value' => 'false',
                'setting_type' => 'boolean',
                'description' => 'อนุมัติสินค้าอัตโนมัติ',
                'is_public' => false,
            ],
            [
                'setting_key' => 'max_products_per_seller',
                'setting_value' => '1000',
                'setting_type' => 'integer',
                'description' => 'จำนวนสินค้าสูงสุดต่อร้านค้า',
                'is_public' => true,
            ],
            [
                'setting_key' => 'max_images_per_product',
                'setting_value' => '10',
                'setting_type' => 'integer',
                'description' => 'จำนวนรูปภาพสูงสุดต่อสินค้า',
                'is_public' => true,
            ],
            // Order Settings
            [
                'setting_key' => 'order_auto_complete_days',
                'setting_value' => '7',
                'setting_type' => 'integer',
                'description' => 'วันที่ออเดอร์เสร็จสมบูรณ์อัตโนมัติหลังจัดส่ง',
                'is_public' => true,
            ],
            [
                'setting_key' => 'order_cancel_days',
                'setting_value' => '1',
                'setting_type' => 'integer',
                'description' => 'วันที่สามารถยกเลิกออเดอร์ได้',
                'is_public' => true,
            ],
            [
                'setting_key' => 'return_window_days',
                'setting_value' => '7',
                'setting_type' => 'integer',
                'description' => 'วันที่สามารถขอคืนสินค้าได้',
                'is_public' => true,
            ],
            // Commission Settings
            [
                'setting_key' => 'affiliate_commission_default',
                'setting_value' => '5',
                'setting_type' => 'decimal',
                'description' => 'คอมมิชชั่น Affiliate เริ่มต้น (%)',
                'is_public' => false,
            ],
            [
                'setting_key' => 'seller_can_set_commission',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'description' => 'ผู้ขายสามารถกำหนดคอมมิชชั่นได้',
                'is_public' => true,
            ],
            [
                'setting_key' => 'max_commission_percentage',
                'setting_value' => '50',
                'setting_type' => 'decimal',
                'description' => 'คอมมิชชั่นสูงสุดที่กำหนดได้ (%)',
                'is_public' => true,
            ],
            // Shipping Settings
            [
                'setting_key' => 'free_shipping_threshold',
                'setting_value' => '500',
                'setting_type' => 'decimal',
                'description' => 'ยอดซื้อขั้นต่ำสำหรับส่งฟรี (บาท)',
                'is_public' => true,
            ],
            [
                'setting_key' => 'default_shipping_cost',
                'setting_value' => '50',
                'setting_type' => 'decimal',
                'description' => 'ค่าจัดส่งเริ่มต้น (บาท)',
                'is_public' => true,
            ],
            // Display Settings
            [
                'setting_key' => 'products_per_page',
                'setting_value' => '24',
                'setting_type' => 'integer',
                'description' => 'จำนวนสินค้าต่อหน้า',
                'is_public' => true,
            ],
            [
                'setting_key' => 'show_sold_count',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'description' => 'แสดงจำนวนขายแล้ว',
                'is_public' => true,
            ],
            [
                'setting_key' => 'show_stock_count',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'description' => 'แสดงจำนวนสต็อก',
                'is_public' => true,
            ],
            [
                'setting_key' => 'enable_reviews',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'description' => 'เปิดใช้งานระบบรีวิว',
                'is_public' => true,
            ],
            [
                'setting_key' => 'review_requires_purchase',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'description' => 'ต้องซื้อก่อนจึงรีวิวได้',
                'is_public' => true,
            ],

            // ── Lazada Hub Settings ──
            [
                'setting_key' => 'lazada_default_markup_percent',
                'setting_value' => '30',
                'setting_type' => 'decimal',
                'description' => 'เปอร์เซ็นต์บวกกำไรเริ่มต้น (โหมดขายเอง) (%)',
                'is_public' => false,
            ],
            [
                'setting_key' => 'lazada_price_rounding',
                'setting_value' => 'baht',
                'setting_type' => 'string',
                'description' => 'กฎปัดเศษราคา: none / baht (จำนวนเต็ม) / ten (หลักสิบ) / nine (ลงท้าย 9)',
                'is_public' => false,
            ],
            [
                'setting_key' => 'lazada_default_fulfillment_mode',
                'setting_value' => 'affiliate',
                'setting_type' => 'string',
                'description' => 'โหมดเริ่มต้นของสินค้านำเข้า: affiliate / resell',
                'is_public' => false,
            ],
            [
                'setting_key' => 'lazada_auto_sync_enabled',
                'setting_value' => 'false',
                'setting_type' => 'boolean',
                'description' => 'เปิด auto-sync ราคา/สต็อก/ออเดอร์ ตามเวลา (ต้องมี API)',
                'is_public' => false,
            ],
            [
                'setting_key' => 'lazada_commission_to_mlm',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'description' => 'กระจายค่าคอม affiliate เข้าระบบ MLM',
                'is_public' => false,
            ],
        ];
    }
}
