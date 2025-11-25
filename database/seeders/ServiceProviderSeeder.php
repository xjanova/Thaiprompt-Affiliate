<?php

namespace Database\Seeders;

use App\Models\ServiceProvider;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * ServiceProviderSeeder - สร้างผู้ให้บริการตัวอย่าง
 *
 * ผู้ให้บริการหลากหลายประเภท พร้อมข้อมูลที่สมจริง
 */
class ServiceProviderSeeder extends Seeder
{
    /**
     * รายการผู้ให้บริการตัวอย่าง
     */
    protected array $providers = [
        // ============ นวดและสปา ============
        [
            'name' => 'คุณสมศรี นวดแผนไทย',
            'email' => 'somsri@example.com',
            'phone' => '081-234-5001',
            'bio' => 'ช่างนวดแผนไทยมืออาชีพ ประสบการณ์กว่า 10 ปี ผ่านการอบรมจากวัดโพธิ์',
            'skills' => ['นวดแผนไทย', 'นวดน้ำมัน', 'นวดฝ่าเท้า'],
            'rating' => 4.8,
            'total_reviews' => 256,
            'total_bookings' => 1250,
            'status' => 'available',
        ],
        [
            'name' => 'คุณมาลี สปามือถึง',
            'email' => 'malee@example.com',
            'phone' => '081-234-5002',
            'bio' => 'ผู้เชี่ยวชาญด้านสปาและอโรมาเทอราพี บริการถึงบ้านทั่วกรุงเทพ',
            'skills' => ['นวดน้ำมันอโรมา', 'สปาหน้า', 'ขัดผิว'],
            'rating' => 4.9,
            'total_reviews' => 189,
            'total_bookings' => 890,
            'status' => 'available',
        ],

        // ============ ตัดผมและทำผม ============
        [
            'name' => 'คุณต้น ช่างผมมืออาชีพ',
            'email' => 'ton@example.com',
            'phone' => '081-234-5003',
            'bio' => 'ช่างตัดผมชาย ผ่านการอบรมจากโรงเรียนช่างผม บริการถึงบ้าน',
            'skills' => ['ตัดผมชาย', 'โกนหนวด', 'ทำสีผม'],
            'rating' => 4.7,
            'total_reviews' => 324,
            'total_bookings' => 1567,
            'status' => 'available',
        ],
        [
            'name' => 'คุณแนน Hair Artist',
            'email' => 'nan@example.com',
            'phone' => '081-234-5004',
            'bio' => 'ช่างทำผมผู้หญิง เชี่ยวชาญด้านการทำสี ดัด ยืด และเกล้าผมงานแต่ง',
            'skills' => ['ตัดผมหญิง', 'ทำสีผม', 'ดัดผม', 'เกล้าผม'],
            'rating' => 4.9,
            'total_reviews' => 456,
            'total_bookings' => 2100,
            'status' => 'busy',
        ],

        // ============ ซ่อมแอร์ ============
        [
            'name' => 'ช่างเอก แอร์เซอร์วิส',
            'email' => 'ek@example.com',
            'phone' => '081-234-5005',
            'bio' => 'ช่างซ่อมแอร์มืออาชีพ ประสบการณ์ 15 ปี ซ่อมได้ทุกยี่ห้อ',
            'skills' => ['ซ่อมแอร์', 'ล้างแอร์', 'ติดตั้งแอร์', 'เติมน้ำยา'],
            'rating' => 4.8,
            'total_reviews' => 567,
            'total_bookings' => 2890,
            'status' => 'available',
        ],
        [
            'name' => 'ทีมช่างแอร์ CoolFix',
            'email' => 'coolfix@example.com',
            'phone' => '081-234-5006',
            'bio' => 'ทีมช่างแอร์มืออาชีพ พร้อมรับงานด่วน ซ่อมแอร์บ้าน คอนโด สำนักงาน',
            'skills' => ['ซ่อมแอร์', 'ล้างแอร์', 'ติดตั้งแอร์'],
            'rating' => 4.6,
            'total_reviews' => 890,
            'total_bookings' => 4500,
            'status' => 'available',
        ],

        // ============ ช่างไฟฟ้า ============
        [
            'name' => 'ช่างโจ ไฟฟ้า 24 ชม.',
            'email' => 'jo@example.com',
            'phone' => '081-234-5007',
            'bio' => 'ช่างไฟฟ้ามีใบอนุญาต พร้อมบริการ 24 ชั่วโมง งานคุณภาพ ปลอดภัย',
            'skills' => ['ซ่อมไฟฟ้า', 'เดินสายไฟ', 'ติดตั้งหลอดไฟ'],
            'rating' => 4.7,
            'total_reviews' => 234,
            'total_bookings' => 1200,
            'status' => 'available',
        ],

        // ============ ช่างประปา ============
        [
            'name' => 'ช่างนิด ประปา',
            'email' => 'nid@example.com',
            'phone' => '081-234-5008',
            'bio' => 'ช่างประปาประสบการณ์ 12 ปี รับซ่อมท่อตัน ติดตั้งสุขภัณฑ์',
            'skills' => ['ซ่อมประปา', 'แก้ท่อตัน', 'ติดตั้งสุขภัณฑ์'],
            'rating' => 4.5,
            'total_reviews' => 156,
            'total_bookings' => 780,
            'status' => 'available',
        ],

        // ============ ทำความสะอาด ============
        [
            'name' => 'ทีมแม่บ้าน CleanPro',
            'email' => 'cleanpro@example.com',
            'phone' => '081-234-5009',
            'bio' => 'บริการทำความสะอาดมืออาชีพ บ้าน คอนโด สำนักงาน Big Cleaning',
            'skills' => ['ทำความสะอาดบ้าน', 'ทำความสะอาดคอนโด', 'Big Cleaning'],
            'rating' => 4.8,
            'total_reviews' => 678,
            'total_bookings' => 3400,
            'status' => 'available',
        ],
        [
            'name' => 'คุณจันทร์ บริการทำความสะอาด',
            'email' => 'jan@example.com',
            'phone' => '081-234-5010',
            'bio' => 'แม่บ้านประจำ รับทำความสะอาดรายวัน รายสัปดาห์',
            'skills' => ['ทำความสะอาดบ้าน', 'ซักรีด', 'ดูแลผู้สูงอายุ'],
            'rating' => 4.6,
            'total_reviews' => 234,
            'total_bookings' => 1100,
            'status' => 'available',
        ],

        // ============ ล้างรถ ============
        [
            'name' => 'ทีมล้างรถ ShinyCar',
            'email' => 'shinycar@example.com',
            'phone' => '081-234-5011',
            'bio' => 'บริการล้างรถถึงบ้าน ขัดเคลือบ เคลือบแก้ว ดูดฝุ่นภายใน',
            'skills' => ['ล้างรถ', 'ขัดเคลือบ', 'เคลือบแก้ว', 'ดูดฝุ่น'],
            'rating' => 4.7,
            'total_reviews' => 345,
            'total_bookings' => 1800,
            'status' => 'available',
        ],

        // ============ ส่งอาหาร/พัสดุ ============
        [
            'name' => 'คุณเบิร์ด Rider',
            'email' => 'bird@example.com',
            'phone' => '081-234-5012',
            'bio' => 'ไรเดอร์มืออาชีพ รับส่งอาหาร พัสดุ ของด่วน ทั่วกรุงเทพ',
            'skills' => ['ส่งอาหาร', 'ส่งพัสดุ', 'ส่งเอกสาร'],
            'rating' => 4.9,
            'total_reviews' => 1234,
            'total_bookings' => 8900,
            'status' => 'available',
        ],
        [
            'name' => 'คุณเก่ง Express Delivery',
            'email' => 'keng@example.com',
            'phone' => '081-234-5013',
            'bio' => 'รับส่งของด่วน รถกระบะ ขนย้ายของ ส่งสินค้า',
            'skills' => ['ส่งพัสดุ', 'ขนย้าย', 'ส่งสินค้า'],
            'rating' => 4.7,
            'total_reviews' => 567,
            'total_bookings' => 3200,
            'status' => 'busy',
        ],

        // ============ สอนพิเศษ ============
        [
            'name' => 'ครูนุ่น ติวเตอร์',
            'email' => 'noon@example.com',
            'phone' => '081-234-5014',
            'bio' => 'ครูสอนพิเศษคณิตศาสตร์ วิทยาศาสตร์ จบปริญญาโท ประสบการณ์ 8 ปี',
            'skills' => ['สอนคณิตศาสตร์', 'สอนวิทยาศาสตร์', 'สอนฟิสิกส์'],
            'rating' => 4.9,
            'total_reviews' => 189,
            'total_bookings' => 670,
            'status' => 'available',
        ],
        [
            'name' => 'ครูจอห์น English Tutor',
            'email' => 'john@example.com',
            'phone' => '081-234-5015',
            'bio' => 'Native English Speaker สอนภาษาอังกฤษ TOEIC IELTS Conversation',
            'skills' => ['สอนภาษาอังกฤษ', 'TOEIC', 'IELTS', 'Conversation'],
            'rating' => 4.8,
            'total_reviews' => 456,
            'total_bookings' => 1890,
            'status' => 'available',
        ],

        // ============ สอนฟิตเนส ============
        [
            'name' => 'โค้ชบอส Personal Trainer',
            'email' => 'boss@example.com',
            'phone' => '081-234-5016',
            'bio' => 'Personal Trainer มีใบรับรอง ACE ประสบการณ์ 7 ปี',
            'skills' => ['Weight Training', 'Cardio', 'Diet Plan'],
            'rating' => 4.9,
            'total_reviews' => 234,
            'total_bookings' => 890,
            'status' => 'available',
        ],
        [
            'name' => 'ครูโยคะ มายด์',
            'email' => 'mind@example.com',
            'phone' => '081-234-5017',
            'bio' => 'ครูสอนโยคะ ผ่านการรับรอง 200HR YTT สอนโยคะถึงบ้าน',
            'skills' => ['โยคะ', 'พิลาทิส', 'Stretching'],
            'rating' => 4.8,
            'total_reviews' => 167,
            'total_bookings' => 560,
            'status' => 'available',
        ],

        // ============ สัตว์เลี้ยง ============
        [
            'name' => 'คุณเหมียว Pet Grooming',
            'email' => 'meow@example.com',
            'phone' => '081-234-5018',
            'bio' => 'บริการอาบน้ำตัดขนสัตว์เลี้ยงถึงบ้าน หมา แมว กระต่าย',
            'skills' => ['อาบน้ำสัตว์เลี้ยง', 'ตัดขน', 'ตัดเล็บ'],
            'rating' => 4.9,
            'total_reviews' => 345,
            'total_bookings' => 1200,
            'status' => 'available',
        ],

        // ============ ช่างภาพ ============
        [
            'name' => 'ช่างภาพ บอม',
            'email' => 'bom@example.com',
            'phone' => '081-234-5019',
            'bio' => 'ช่างภาพมืออาชีพ รับถ่ายงานแต่ง งานอีเวนท์ ภาพบุคคล',
            'skills' => ['ถ่ายภาพ', 'ถ่ายวิดีโอ', 'ตัดต่อวิดีโอ'],
            'rating' => 4.8,
            'total_reviews' => 123,
            'total_bookings' => 450,
            'status' => 'available',
        ],

        // ============ ดูแลสวน ============
        [
            'name' => 'ทีมจัดสวน GreenGarden',
            'email' => 'green@example.com',
            'phone' => '081-234-5020',
            'bio' => 'บริการตัดหญ้า ดูแลสวน จัดสวน ปลูกต้นไม้',
            'skills' => ['ตัดหญ้า', 'ดูแลสวน', 'จัดสวน'],
            'rating' => 4.6,
            'total_reviews' => 189,
            'total_bookings' => 780,
            'status' => 'available',
        ],
    ];

    /**
     * สร้างข้อมูลผู้ให้บริการตัวอย่าง
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลังสร้างผู้ให้บริการตัวอย่าง...');

        // หา owner (admin user)
        $owner = User::where('role', 'admin')->first() ?? User::first();

        if (!$owner) {
            $this->command->error('❌ ไม่พบ User สำหรับเป็น owner');
            return;
        }

        // หา areas (ถ้ามี)
        $areas = ServiceArea::where('is_active', true)->get();

        foreach ($this->providers as $index => $providerData) {
            // ตรวจสอบว่ามีอยู่แล้วหรือไม่
            $existing = ServiceProvider::where('email', $providerData['email'])->first();
            if ($existing) {
                $this->command->warn("  ⚠️  ผู้ให้บริการ '{$providerData['name']}' มีอยู่แล้ว ข้าม...");
                continue;
            }

            // สร้าง User สำหรับ Provider (ถ้าไม่มี)
            $user = User::firstOrCreate(
                ['email' => $providerData['email']],
                [
                    'name' => $providerData['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'provider',
                    'phone' => $providerData['phone'],
                ]
            );

            // สร้าง Provider
            $provider = ServiceProvider::create([
                'user_id' => $user->id,
                'owner_id' => $owner->id,
                'name' => $providerData['name'],
                'email' => $providerData['email'],
                'phone' => $providerData['phone'],
                'bio' => $providerData['bio'],
                'rating' => $providerData['rating'],
                'total_reviews' => $providerData['total_reviews'],
                'total_bookings' => $providerData['total_bookings'],
                'total_accepted' => intval($providerData['total_bookings'] * 0.95),
                'total_rejected' => intval($providerData['total_bookings'] * 0.05),
                'acceptance_rate' => 95.0,
                'average_response_minutes' => rand(3, 15),
                'status' => $providerData['status'],
                'is_verified' => true,
                'is_active' => true,
                'auto_accept_bookings' => rand(0, 1) == 1,
                'auto_accept_max_distance_km' => rand(10, 30),
                'auto_accept_max_daily_bookings' => rand(5, 15),
                // พิกัดสุ่มในกรุงเทพ
                'current_latitude' => 13.7 + (rand(-50, 50) / 1000),
                'current_longitude' => 100.5 + (rand(-50, 50) / 1000),
                'last_location_update' => now()->subMinutes(rand(5, 120)),
            ]);

            // เชื่อมกับ Service Areas (ถ้ามี)
            if ($areas->count() > 0) {
                $randomAreas = $areas->random(min(3, $areas->count()));
                $provider->areas()->attach($randomAreas->pluck('id'));
            }

            $this->command->info("  ✅ สร้างผู้ให้บริการ '{$providerData['name']}' สำเร็จ");
        }

        $total = ServiceProvider::count();
        $this->command->info("✅ สร้างผู้ให้บริการตัวอย่างสำเร็จ! รวม {$total} คน");
    }
}
