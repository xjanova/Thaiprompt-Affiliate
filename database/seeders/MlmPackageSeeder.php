<?php

namespace Database\Seeders;

use App\Models\MlmPackage;
use Illuminate\Database\Seeder;

class MlmPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * สำคัญ: นี่คือ "แพคเกจสมาชิก" ไม่ใช่ "แผนคอมมิชชัน"
     * - MlmPackage = แพคเกจที่สมาชิกซื้อเพื่อเข้าร่วม MLM (Bronze, Silver, Gold, etc.)
     * - MlmPlan = แผนการคำนวณค่าคอมมิชชัน (ใช้ร่วมกันโดยสมาชิกทุกคน)
     *   ดู MlmPlanSeeder.php
     */
    public function run(): void
    {
        // ลบข้อมูลเก่าทั้งหมดเพื่อป้องกันความซ้ำซ้อน (ใช้ forceDelete เพื่อลบถาวร)
        $this->command->info('🗑️  Cleaning old MLM Packages...');
        MlmPackage::query()->forceDelete();

        $this->command->info('📦 Creating MLM Packages...');

        $packages = [
            [
                'name' => 'Bronze Package',
                'name_th' => 'แพคเกจ Bronze',
                'slug' => 'bronze-package',
                'description' => 'Perfect starter package for new members. Get started with MLM business today!',
                'description_th' => 'แพคเกจเริ่มต้นที่เหมาะสำหรับสมาชิกใหม่ เริ่มต้นธุรกิจ MLM ได้ง่ายๆ',
                'price' => 990.00,
                'pv_value' => 990.00,
                'color' => '#CD7F32',
                'icon' => 'shield',
                'sort_order' => 1,
                'badge' => null,
                'features' => [
                    'เริ่มต้นธุรกิจ MLM',
                    'รับค่าคอมมิชชันตามแผนหลัก',
                    'เข้าถึงระบบ Back Office',
                    'สนับสนุนทีมงาน 24/7',
                ],
                'products' => [], // จะตั้งค่าภายหลังจากแอดมิน
                'benefits' => [
                    'ถอนเงินขั้นต่ำ 500 บาท',
                    'ถอนได้ 2 ครั้งต่อเดือน',
                ],
                'max_downline' => null, // unlimited
                'max_withdrawal_per_month' => 2,
                'min_withdrawal_amount' => 500.00,
                'is_active' => true,
                'is_popular' => false,
                'is_available_for_new_members' => true,
                'can_upgrade' => true,
                'upgrade_options' => [2, 3, 4, 5], // สามารถอัพเกรดเป็น Silver, Gold, Diamond, Premier
            ],
            [
                'name' => 'Silver Package',
                'name_th' => 'แพคเกจ Silver',
                'slug' => 'silver-package',
                'description' => 'Popular choice for committed members. Enhanced benefits and better rewards!',
                'description_th' => 'ตัวเลือกยอดนิยมสำหรับสมาชิกที่จริงจัง ผลตอบแทนและสิทธิพิเศษที่ดีกว่า',
                'price' => 2990.00,
                'pv_value' => 2990.00,
                'color' => '#C0C0C0',
                'icon' => 'award',
                'sort_order' => 2,
                'badge' => 'Popular',
                'features' => [
                    'ทุกสิทธิ์ของ Bronze',
                    'สินค้าพรีเมี่ยม',
                    'เข้าร่วมอบรมพิเศษ',
                    'รายงานขั้นสูง',
                    'โบนัสพิเศษ',
                ],
                'products' => [], // จะตั้งค่าภายหลังจากแอดมิน
                'benefits' => [
                    'ถอนเงินขั้นต่ำ 300 บาท',
                    'ถอนได้ 4 ครั้งต่อเดือน',
                    'รับโบนัสพิเศษ 5%',
                ],
                'max_downline' => null,
                'max_withdrawal_per_month' => 4,
                'min_withdrawal_amount' => 300.00,
                'is_active' => true,
                'is_popular' => true,
                'is_available_for_new_members' => true,
                'can_upgrade' => true,
                'upgrade_options' => [3, 4, 5], // สามารถอัพเกรดเป็น Gold, Diamond, Premier
            ],
            [
                'name' => 'Gold Package',
                'name_th' => 'แพคเกจ Gold',
                'slug' => 'gold-package',
                'description' => 'Premium package for serious business builders. Maximum earning potential!',
                'description_th' => 'แพคเกจพรีเมี่ยมสำหรับผู้สร้างธุรกิจอย่างจริงจัง ศักยภาพรายได้สูงสุด',
                'price' => 5990.00,
                'pv_value' => 5990.00,
                'color' => '#FFD700',
                'icon' => 'star',
                'sort_order' => 3,
                'badge' => 'Best Value',
                'features' => [
                    'ทุกสิทธิ์ของ Silver',
                    'สินค้าพรีเมี่ยมคุณภาพสูง',
                    'เข้าร่วมงานสัมมนาพิเศษ',
                    'ที่ปรึกษาส่วนตัว',
                    'โบนัสผู้นำทีม',
                    'เครื่องมือการตลาดพิเศษ',
                ],
                'products' => [], // จะตั้งค่าภายหลังจากแอดมิน
                'benefits' => [
                    'ถอนเงินขั้นต่ำ 200 บาท',
                    'ถอนได้ 8 ครั้งต่อเดือน',
                    'รับโบนัสพิเศษ 10%',
                    'เครดิตโฆษณาฟรี 1,000 บาท',
                ],
                'max_downline' => null,
                'max_withdrawal_per_month' => 8,
                'min_withdrawal_amount' => 200.00,
                'is_active' => true,
                'is_popular' => false,
                'is_available_for_new_members' => true,
                'can_upgrade' => true,
                'upgrade_options' => [4, 5], // สามารถอัพเกรดเป็น Diamond, Premier
            ],
            [
                'name' => 'Diamond Package',
                'name_th' => 'แพคเกจ Diamond',
                'slug' => 'diamond-package',
                'description' => 'Elite package for top performers. Exclusive benefits and highest commissions!',
                'description_th' => 'แพคเกจระดับสูงสำหรับผู้ทำผลงานยอดเยี่ยม สิทธิพิเศษและค่าคอมมิชชันสูงสุด',
                'price' => 9990.00,
                'pv_value' => 9990.00,
                'color' => '#B9F2FF',
                'icon' => 'gem',
                'sort_order' => 4,
                'badge' => 'Elite',
                'features' => [
                    'ทุกสิทธิ์ของ Gold',
                    'สินค้าพรีเมี่ยมระดับ VIP',
                    'เข้าร่วมอบรมต่างประเทศ',
                    'ทีมสนับสนุนเฉพาะบุคคล',
                    'โบนัสผู้นำระดับสูง',
                    'ระบบอัตโนมัติพิเศษ',
                    'สิทธิ์นำเสนอแพคเกจ',
                ],
                'products' => [], // จะตั้งค่าภายหลังจากแอดมิน
                'benefits' => [
                    'ถอนเงินขั้นต่ำ 100 บาท',
                    'ถอนได้ไม่จำกัดครั้ง',
                    'รับโบนัสพิเศษ 15%',
                    'เครดิตโฆษณาฟรี 3,000 บาท',
                    'ของขวัญพิเศษรายไตรมาส',
                ],
                'max_downline' => null,
                'max_withdrawal_per_month' => null, // unlimited
                'min_withdrawal_amount' => 100.00,
                'is_active' => true,
                'is_popular' => false,
                'is_available_for_new_members' => true,
                'can_upgrade' => true,
                'upgrade_options' => [5], // สามารถอัพเกรดเป็น Premier
            ],
            [
                'name' => 'Premier Package',
                'name_th' => 'แพคเกจ Premier',
                'slug' => 'premier-package',
                'description' => 'Ultimate VIP package for business leaders. All-inclusive benefits and unlimited potential!',
                'description_th' => 'แพคเกจ VIP สูงสุดสำหรับผู้นำธุรกิจ สิทธิพิเศษครบครันและศักยภาพไร้ขีดจำกัด',
                'price' => 19990.00,
                'pv_value' => 19990.00,
                'color' => '#9333EA',
                'icon' => 'crown',
                'sort_order' => 5,
                'badge' => 'VIP',
                'features' => [
                    'ทุกสิทธิ์ของ Diamond',
                    'สินค้าพรีเมี่ยม VIP Exclusive',
                    'เข้าร่วมงานสุดพิเศษทั่วโลก',
                    'ทีมผู้เชี่ยวชาญเฉพาะบุคคล',
                    'โบนัสผู้นำระดับ Premier',
                    'ระบบ AI อัตโนมัติ',
                    'สิทธิ์เป็นพันธมิตรธุรกิจ',
                    'รายได้พาสซีฟเพิ่มเติม',
                ],
                'products' => [], // จะตั้งค่าภายหลังจากแอดมิน
                'benefits' => [
                    'ถอนเงินไม่มีขั้นต่ำ',
                    'ถอนได้ไม่จำกัดครั้ง',
                    'รับโบนัสพิเศษ 20%',
                    'เครดิตโฆษณาฟรี 10,000 บาท',
                    'ของขวัญพิเศษรายเดือน',
                    'ประกันสุขภาพพิเศษ',
                    'บัตรเครดิต Premier Card',
                ],
                'max_downline' => null,
                'max_withdrawal_per_month' => null,
                'min_withdrawal_amount' => null,
                'is_active' => true,
                'is_popular' => false,
                'is_available_for_new_members' => true,
                'can_upgrade' => false, // ไม่สามารถอัพเกรดต่อได้เพราะเป็นแพคเกจสูงสุดแล้ว
                'upgrade_options' => [],
            ],
        ];

        foreach ($packages as $package) {
            MlmPackage::create($package);
        }

        $this->command->info('✅ Created 5 MLM Packages: Bronze, Silver, Gold, Diamond, Premier');
        $this->command->info('ℹ️  หากต้องการดูแผนคอมมิชชัน ให้รัน: php artisan db:seed --class=MlmPlanSeeder');
    }
}
