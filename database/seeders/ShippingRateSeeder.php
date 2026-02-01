<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use Illuminate\Database\Seeder;

class ShippingRateSeeder extends Seeder
{
    /**
     * สร้างข้อมูลอัตราค่าจัดส่งเริ่มต้น
     *
     * อัตราค่าส่งตามน้ำหนักสำหรับ:
     * - ในประเทศ (domestic): 5 ระดับน้ำหนัก
     * - ต่างประเทศ (international): 4 ระดับน้ำหนัก
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🚚 กำลัง seed ข้อมูลอัตราค่าจัดส่ง...');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        if (ShippingRate::count() > 0) {
            $this->command->info('ข้อมูลอัตราค่าจัดส่งมีอยู่แล้ว ข้าม...');
            return;
        }

        $rates = [
            // ============ ในประเทศ (Domestic) ============
            [
                'name' => 'น้ำหนักเบา',
                'zone' => 'domestic',
                'min_weight_kg' => 0,
                'max_weight_kg' => 1,
                'base_fee' => 30,
                'per_kg_fee' => 0,
                'min_fee' => 30,
                'max_fee' => null,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'น้ำหนักปานกลาง',
                'zone' => 'domestic',
                'min_weight_kg' => 1,
                'max_weight_kg' => 5,
                'base_fee' => 30,
                'per_kg_fee' => 15,
                'min_fee' => 45,
                'max_fee' => null,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'น้ำหนักมาก',
                'zone' => 'domestic',
                'min_weight_kg' => 5,
                'max_weight_kg' => 10,
                'base_fee' => 50,
                'per_kg_fee' => 12,
                'min_fee' => 80,
                'max_fee' => null,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'น้ำหนักหนัก',
                'zone' => 'domestic',
                'min_weight_kg' => 10,
                'max_weight_kg' => 20,
                'base_fee' => 80,
                'per_kg_fee' => 10,
                'min_fee' => 120,
                'max_fee' => null,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'น้ำหนักหนักมาก',
                'zone' => 'domestic',
                'min_weight_kg' => 20,
                'max_weight_kg' => 0, // 0 = ไม่จำกัด
                'base_fee' => 150,
                'per_kg_fee' => 8,
                'min_fee' => 200,
                'max_fee' => null,
                'is_active' => true,
                'sort_order' => 5,
            ],

            // ============ ต่างประเทศ (International) ============
            [
                'name' => 'ต่างประเทศ - น้ำหนักเบา',
                'zone' => 'international',
                'min_weight_kg' => 0,
                'max_weight_kg' => 0.5,
                'base_fee' => 200,
                'per_kg_fee' => 0,
                'min_fee' => 200,
                'max_fee' => null,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'ต่างประเทศ - น้ำหนักปานกลาง',
                'zone' => 'international',
                'min_weight_kg' => 0.5,
                'max_weight_kg' => 5,
                'base_fee' => 200,
                'per_kg_fee' => 150,
                'min_fee' => 350,
                'max_fee' => null,
                'is_active' => true,
                'sort_order' => 11,
            ],
            [
                'name' => 'ต่างประเทศ - น้ำหนักมาก',
                'zone' => 'international',
                'min_weight_kg' => 5,
                'max_weight_kg' => 20,
                'base_fee' => 500,
                'per_kg_fee' => 120,
                'min_fee' => 800,
                'max_fee' => null,
                'is_active' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'ต่างประเทศ - น้ำหนักหนัก',
                'zone' => 'international',
                'min_weight_kg' => 20,
                'max_weight_kg' => 0, // 0 = ไม่จำกัด
                'base_fee' => 1500,
                'per_kg_fee' => 100,
                'min_fee' => 2000,
                'max_fee' => null,
                'is_active' => true,
                'sort_order' => 13,
            ],
        ];

        foreach ($rates as $rate) {
            ShippingRate::create($rate);
        }

        $this->command->info('✅ Seed อัตราค่าจัดส่ง ' . count($rates) . ' รายการสำเร็จ!');
    }
}
