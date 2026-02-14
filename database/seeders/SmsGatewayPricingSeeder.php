<?php

namespace Database\Seeders;

use App\Models\SmsGatewayPricing;
use Illuminate\Database\Seeder;

/**
 * SMS Gateway Pricing Seeder
 *
 * สร้างแพ็กเกจราคาเริ่มต้นสำหรับ SMS Payment Gateway
 * ร้านค้าพรีเมียมและ Official Shop ใช้ฟรี — ไม่ต้องสมัคร
 */
class SmsGatewayPricingSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // Basic Monthly
            [
                'name' => 'แพ็กเกจ Basic',
                'description' => 'เหมาะสำหรับร้านค้าขนาดเล็ก เชื่อมต่อ 1 อุปกรณ์',
                'plan_type' => 'monthly',
                'price' => 299.00,
                'currency' => 'THB',
                'max_devices' => 1,
                'trial_days' => 7,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'features_json' => [
                    'อุปกรณ์ 1 เครื่อง',
                    'รับ Push Notification แบบ Real-time',
                    'อนุมัติ/ปฏิเสธคำสั่งซื้อ',
                    'ดูสถิติรายวัน',
                    'ทดลองใช้ฟรี 7 วัน',
                ],
            ],
            // Basic Yearly
            [
                'name' => 'แพ็กเกจ Basic (รายปี)',
                'description' => 'เหมาะสำหรับร้านค้าขนาดเล็ก เชื่อมต่อ 1 อุปกรณ์ — ประหยัด 17%',
                'plan_type' => 'yearly',
                'price' => 2990.00,
                'currency' => 'THB',
                'max_devices' => 1,
                'trial_days' => 7,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 2,
                'features_json' => [
                    'อุปกรณ์ 1 เครื่อง',
                    'รับ Push Notification แบบ Real-time',
                    'อนุมัติ/ปฏิเสธคำสั่งซื้อ',
                    'ดูสถิติรายวัน',
                    'ทดลองใช้ฟรี 7 วัน',
                    'ประหยัด 17% เทียบกับรายเดือน',
                ],
            ],
            // Professional Monthly
            [
                'name' => 'แพ็กเกจ Professional',
                'description' => 'เหมาะสำหรับร้านค้าขนาดกลาง เชื่อมต่อได้ 3 อุปกรณ์',
                'plan_type' => 'monthly',
                'price' => 599.00,
                'currency' => 'THB',
                'max_devices' => 3,
                'trial_days' => 7,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
                'features_json' => [
                    'อุปกรณ์ 3 เครื่อง',
                    'รับ Push Notification แบบ Real-time',
                    'อนุมัติ/ปฏิเสธคำสั่งซื้อ',
                    'ดูสถิติรายวัน',
                    'จัดการบัญชีธนาคารหลายบัญชี',
                    'ทดลองใช้ฟรี 7 วัน',
                ],
            ],
            // Professional Yearly
            [
                'name' => 'แพ็กเกจ Professional (รายปี)',
                'description' => 'เหมาะสำหรับร้านค้าขนาดกลาง เชื่อมต่อได้ 3 อุปกรณ์ — ประหยัด 17%',
                'plan_type' => 'yearly',
                'price' => 5990.00,
                'currency' => 'THB',
                'max_devices' => 3,
                'trial_days' => 7,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 4,
                'features_json' => [
                    'อุปกรณ์ 3 เครื่อง',
                    'รับ Push Notification แบบ Real-time',
                    'อนุมัติ/ปฏิเสธคำสั่งซื้อ',
                    'ดูสถิติรายวัน',
                    'จัดการบัญชีธนาคารหลายบัญชี',
                    'ทดลองใช้ฟรี 7 วัน',
                    'ประหยัด 17% เทียบกับรายเดือน',
                ],
            ],
            // Enterprise Monthly
            [
                'name' => 'แพ็กเกจ Enterprise',
                'description' => 'เหมาะสำหรับร้านค้าขนาดใหญ่ เชื่อมต่อได้ 10 อุปกรณ์',
                'plan_type' => 'monthly',
                'price' => 999.00,
                'currency' => 'THB',
                'max_devices' => 10,
                'trial_days' => 14,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 5,
                'features_json' => [
                    'อุปกรณ์ 10 เครื่อง',
                    'รับ Push Notification แบบ Real-time',
                    'อนุมัติ/ปฏิเสธคำสั่งซื้อ',
                    'ดูสถิติรายวัน',
                    'จัดการบัญชีธนาคารหลายบัญชี',
                    'รองรับหลายสาขา',
                    'ทดลองใช้ฟรี 14 วัน',
                ],
            ],
            // Enterprise Yearly
            [
                'name' => 'แพ็กเกจ Enterprise (รายปี)',
                'description' => 'เหมาะสำหรับร้านค้าขนาดใหญ่ เชื่อมต่อได้ 10 อุปกรณ์ — ประหยัด 17%',
                'plan_type' => 'yearly',
                'price' => 9990.00,
                'currency' => 'THB',
                'max_devices' => 10,
                'trial_days' => 14,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 6,
                'features_json' => [
                    'อุปกรณ์ 10 เครื่อง',
                    'รับ Push Notification แบบ Real-time',
                    'อนุมัติ/ปฏิเสธคำสั่งซื้อ',
                    'ดูสถิติรายวัน',
                    'จัดการบัญชีธนาคารหลายบัญชี',
                    'รองรับหลายสาขา',
                    'ทดลองใช้ฟรี 14 วัน',
                    'ประหยัด 17% เทียบกับรายเดือน',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            SmsGatewayPricing::updateOrCreate(
                [
                    'name' => $plan['name'],
                    'plan_type' => $plan['plan_type'],
                ],
                $plan
            );
        }

        $this->command->info('✅ SMS Gateway Pricing seeder completed! Created '.count($plans).' plans.');
    }
}
