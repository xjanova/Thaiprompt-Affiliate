<?php

namespace Database\Seeders;

use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * ServiceAreaSeeder - สร้างพื้นที่ให้บริการตัวอย่าง
 *
 * พื้นที่ให้บริการในกรุงเทพและปริมณฑล
 */
class ServiceAreaSeeder extends Seeder
{
    /**
     * รายการพื้นที่ให้บริการ
     */
    protected array $areas = [
        // ============ กรุงเทพมหานคร - กลางเมือง ============
        [
            'name' => 'สาทร-สีลม',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'สาทร',
            'subdistrict' => null,
            'center_latitude' => 13.7217,
            'center_longitude' => 100.5286,
            'is_featured' => true,
        ],
        [
            'name' => 'สุขุมวิท',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'วัฒนา',
            'subdistrict' => null,
            'center_latitude' => 13.7315,
            'center_longitude' => 100.5661,
            'is_featured' => true,
        ],
        [
            'name' => 'สยาม-ราชประสงค์',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'ปทุมวัน',
            'subdistrict' => null,
            'center_latitude' => 13.7465,
            'center_longitude' => 100.5345,
            'is_featured' => true,
        ],
        [
            'name' => 'อารีย์-พหลโยธิน',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'พญาไท',
            'subdistrict' => null,
            'center_latitude' => 13.7790,
            'center_longitude' => 100.5445,
            'is_featured' => true,
        ],
        [
            'name' => 'ลาดพร้าว-รัชดา',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'จตุจักร',
            'subdistrict' => null,
            'center_latitude' => 13.8169,
            'center_longitude' => 100.5644,
            'is_featured' => false,
        ],

        // ============ กรุงเทพมหานคร - ฝั่งธนบุรี ============
        [
            'name' => 'ธนบุรี-วงเวียนใหญ่',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'ธนบุรี',
            'subdistrict' => null,
            'center_latitude' => 13.7242,
            'center_longitude' => 100.4892,
            'is_featured' => false,
        ],
        [
            'name' => 'บางแค-ภาษีเจริญ',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'บางแค',
            'subdistrict' => null,
            'center_latitude' => 13.6981,
            'center_longitude' => 100.4052,
            'is_featured' => false,
        ],
        [
            'name' => 'ตลิ่งชัน',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'ตลิ่งชัน',
            'subdistrict' => null,
            'center_latitude' => 13.7676,
            'center_longitude' => 100.4340,
            'is_featured' => false,
        ],

        // ============ กรุงเทพมหานคร - รอบนอก ============
        [
            'name' => 'บางนา-ศรีนครินทร์',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'บางนา',
            'subdistrict' => null,
            'center_latitude' => 13.6671,
            'center_longitude' => 100.6067,
            'is_featured' => false,
        ],
        [
            'name' => 'พระราม 9-รามคำแหง',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'บางกะปิ',
            'subdistrict' => null,
            'center_latitude' => 13.7590,
            'center_longitude' => 100.5933,
            'is_featured' => false,
        ],
        [
            'name' => 'มีนบุรี-หนองจอก',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'มีนบุรี',
            'subdistrict' => null,
            'center_latitude' => 13.8095,
            'center_longitude' => 100.7305,
            'is_featured' => false,
        ],
        [
            'name' => 'ดอนเมือง-หลักสี่',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'ดอนเมือง',
            'subdistrict' => null,
            'center_latitude' => 13.9175,
            'center_longitude' => 100.5969,
            'is_featured' => false,
        ],
        [
            'name' => 'บางเขน-สายไหม',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'บางเขน',
            'subdistrict' => null,
            'center_latitude' => 13.8746,
            'center_longitude' => 100.5891,
            'is_featured' => false,
        ],

        // ============ ปริมณฑล ============
        [
            'name' => 'นนทบุรี',
            'province' => 'นนทบุรี',
            'district' => 'เมืองนนทบุรี',
            'subdistrict' => null,
            'center_latitude' => 13.8621,
            'center_longitude' => 100.5144,
            'is_featured' => true,
        ],
        [
            'name' => 'ปากเกร็ด',
            'province' => 'นนทบุรี',
            'district' => 'ปากเกร็ด',
            'subdistrict' => null,
            'center_latitude' => 13.9133,
            'center_longitude' => 100.4968,
            'is_featured' => false,
        ],
        [
            'name' => 'สมุทรปราการ',
            'province' => 'สมุทรปราการ',
            'district' => 'เมืองสมุทรปราการ',
            'subdistrict' => null,
            'center_latitude' => 13.5990,
            'center_longitude' => 100.5998,
            'is_featured' => true,
        ],
        [
            'name' => 'บางพลี',
            'province' => 'สมุทรปราการ',
            'district' => 'บางพลี',
            'subdistrict' => null,
            'center_latitude' => 13.6172,
            'center_longitude' => 100.7094,
            'is_featured' => false,
        ],
        [
            'name' => 'ปทุมธานี',
            'province' => 'ปทุมธานี',
            'district' => 'เมืองปทุมธานี',
            'subdistrict' => null,
            'center_latitude' => 14.0208,
            'center_longitude' => 100.5253,
            'is_featured' => false,
        ],
        [
            'name' => 'รังสิต-คลองหลวง',
            'province' => 'ปทุมธานี',
            'district' => 'คลองหลวง',
            'subdistrict' => null,
            'center_latitude' => 14.0579,
            'center_longitude' => 100.6127,
            'is_featured' => false,
        ],
        [
            'name' => 'นครปฐม',
            'province' => 'นครปฐม',
            'district' => 'เมืองนครปฐม',
            'subdistrict' => null,
            'center_latitude' => 13.8196,
            'center_longitude' => 100.0644,
            'is_featured' => false,
        ],

        // ============ หัวเมืองใหญ่ ============
        [
            'name' => 'เชียงใหม่',
            'province' => 'เชียงใหม่',
            'district' => 'เมืองเชียงใหม่',
            'subdistrict' => null,
            'center_latitude' => 18.7883,
            'center_longitude' => 98.9853,
            'is_featured' => true,
        ],
        [
            'name' => 'พัทยา',
            'province' => 'ชลบุรี',
            'district' => 'บางละมุง',
            'subdistrict' => null,
            'center_latitude' => 12.9236,
            'center_longitude' => 100.8825,
            'is_featured' => true,
        ],
        [
            'name' => 'ภูเก็ต',
            'province' => 'ภูเก็ต',
            'district' => 'เมืองภูเก็ต',
            'subdistrict' => null,
            'center_latitude' => 7.8804,
            'center_longitude' => 98.3923,
            'is_featured' => true,
        ],
        [
            'name' => 'ขอนแก่น',
            'province' => 'ขอนแก่น',
            'district' => 'เมืองขอนแก่น',
            'subdistrict' => null,
            'center_latitude' => 16.4419,
            'center_longitude' => 102.8360,
            'is_featured' => false,
        ],
        [
            'name' => 'หาดใหญ่',
            'province' => 'สงขลา',
            'district' => 'หาดใหญ่',
            'subdistrict' => null,
            'center_latitude' => 7.0086,
            'center_longitude' => 100.4747,
            'is_featured' => false,
        ],
    ];

    /**
     * สร้างพื้นที่ให้บริการ
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลังสร้างพื้นที่ให้บริการ...');

        // หา owner (admin user)
        $owner = User::where('role', 'admin')->first() ?? User::first();

        if (! $owner) {
            $this->command->error('❌ ไม่พบ User สำหรับเป็น owner');

            return;
        }

        foreach ($this->areas as $index => $areaData) {
            // ตรวจสอบว่ามีอยู่แล้วหรือไม่
            $existing = ServiceArea::where('name', $areaData['name'])
                ->where('province', $areaData['province'])
                ->first();

            if ($existing) {
                $this->command->warn("  ⚠️  พื้นที่ '{$areaData['name']}' มีอยู่แล้ว ข้าม...");

                continue;
            }

            ServiceArea::create([
                'owner_id' => $owner->id,
                'name' => $areaData['name'],
                'province' => $areaData['province'],
                'district' => $areaData['district'],
                'subdistrict' => $areaData['subdistrict'],
                'center_latitude' => $areaData['center_latitude'],
                'center_longitude' => $areaData['center_longitude'],
                'is_active' => true,
                'sort_order' => $index + 1,
                'provider_count' => rand(5, 50),
                'booking_count' => rand(100, 5000),
            ]);

            $this->command->info("  ✅ สร้างพื้นที่ '{$areaData['name']}' สำเร็จ");
        }

        $total = ServiceArea::count();
        $this->command->info("✅ สร้างพื้นที่ให้บริการสำเร็จ! รวม {$total} พื้นที่");
    }
}
