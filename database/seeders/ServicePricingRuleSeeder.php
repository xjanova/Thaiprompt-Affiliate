<?php

namespace Database\Seeders;

use App\Models\ServicePricingRule;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * ServicePricingRuleSeeder
 *
 * สร้างกฎคำนวณราคาเริ่มต้น
 */
class ServicePricingRuleSeeder extends Seeder
{
    /**
     * รันการ seed
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed กฎคำนวณราคา...');

        // หา owner_id (ใช้ admin หรือ user แรก)
        $owner = User::where('role', 'admin')->first() ?? User::first();

        if (!$owner) {
            $this->command->error('❌ ไม่พบ User สำหรับเป็น owner กฎคำนวณราคา');
            $this->command->warn('ℹ️  กรุณารัน UserSeeder ก่อน');
            return;
        }

        $rules = [
            [
                'service_id' => null, // ใช้กับทุกบริการ
                'owner_id' => $owner->id,
                'name' => 'ค่าเดินทางพื้นฐาน 0-5 km',
                'description' => 'สำหรับระยะทางใกล้ ๆ ไม่เกิน 5 กิโลเมตร',
                'min_distance_km' => 0,
                'max_distance_km' => 5,
                'flat_fee' => 30.00,
                'price_per_km' => 0, // ฟรี
                'is_active' => true,
                'priority' => 1,
            ],
            [
                'service_id' => null,
                'owner_id' => $owner->id,
                'name' => 'ค่าเดินทางปานกลาง 5-10 km',
                'description' => 'สำหรับระยะทางปานกลาง 5-10 กิโลเมตร',
                'min_distance_km' => 5,
                'max_distance_km' => 10,
                'flat_fee' => 30.00,
                'price_per_km' => 10.00, // 10 บาท/กม.
                'is_active' => true,
                'priority' => 2,
            ],
            [
                'service_id' => null,
                'owner_id' => $owner->id,
                'name' => 'ค่าเดินทางไกล 10-20 km',
                'description' => 'สำหรับระยะทางไกล 10-20 กิโลเมตร',
                'min_distance_km' => 10,
                'max_distance_km' => 20,
                'flat_fee' => 80.00,
                'price_per_km' => 15.00, // 15 บาท/กม.
                'is_active' => true,
                'priority' => 3,
            ],
            [
                'service_id' => null,
                'owner_id' => $owner->id,
                'name' => 'ค่าเดินทางไกลมาก 20+ km',
                'description' => 'สำหรับระยะทางไกลมากกว่า 20 กิโลเมตร',
                'min_distance_km' => 20,
                'max_distance_km' => null, // ไม่จำกัด
                'flat_fee' => 200.00,
                'price_per_km' => 20.00, // 20 บาท/กม.
                'is_active' => true,
                'priority' => 4,
            ],
        ];

        foreach ($rules as $rule) {
            // ตรวจสอบว่ามีกฎนี้อยู่แล้วหรือไม่
            $existing = ServicePricingRule::where('name', $rule['name'])
                ->where('owner_id', $rule['owner_id'])
                ->first();

            if ($existing) {
                $this->command->warn("  ⚠️  กฎ '{$rule['name']}' มีอยู่แล้ว ข้าม...");
                continue;
            }

            ServicePricingRule::create($rule);
            $this->command->info("  ✅ สร้างกฎ '{$rule['name']}' สำเร็จ");
        }

        $total = ServicePricingRule::count();
        $this->command->info("✅ Seed กฎคำนวณราคาสำเร็จ! รวม {$total} กฎ");
    }
}
