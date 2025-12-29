<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * ServiceSeeder - สร้างบริการตัวอย่างครบทุกหมวดหมู่
 *
 * รองรับบริการหลากหลายประเภท:
 * - นวดสปา, ร้านทำผม, ความงาม
 * - ซ่อมแอร์, ช่างไฟ, ช่างประปา
 * - ทำความสะอาด, ขนย้าย, จัดส่ง
 * - สอนพิเศษ, ฟิตเนส, ถ่ายภาพ
 * - และอื่นๆ
 */
class ServiceSeeder extends Seeder
{
    /**
     * รายการบริการตามหมวดหมู่
     */
    protected array $servicesByCategory = [
        // ============ นวดและสปา ============
        'massage-spa' => [
            [
                'name' => 'นวดแผนไทย 1 ชั่วโมง',
                'slug' => 'thai-massage-1hr',
                'description' => 'นวดแผนไทยแท้ 1 ชั่วโมง ผ่อนคลายกล้ามเนื้อ บรรเทาอาการปวดเมื่อย',
                'base_price' => 350.00,
                'duration_minutes' => 60,
                'options' => [
                    ['name' => 'น้ำมันหอมระเหย', 'price' => 50],
                    ['name' => 'ขัดผิว', 'price' => 100],
                ],
                'is_featured' => true,
                'commission_rate' => 10.00,
            ],
            [
                'name' => 'นวดแผนไทย 2 ชั่วโมง',
                'slug' => 'thai-massage-2hr',
                'description' => 'นวดแผนไทยเต็มตัว 2 ชั่วโมง ผ่อนคลายอย่างล้ำลึก',
                'base_price' => 600.00,
                'duration_minutes' => 120,
                'options' => [
                    ['name' => 'ประคบสมุนไพร', 'price' => 150],
                ],
                'is_featured' => true,
                'commission_rate' => 10.00,
            ],
            [
                'name' => 'นวดน้ำมันอโรมา 90 นาที',
                'slug' => 'aroma-oil-massage-90min',
                'description' => 'นวดน้ำมันหอมระเหย ผ่อนคลายสุดๆ พร้อมน้ำมันหอมกลิ่นลาเวนเดอร์',
                'base_price' => 550.00,
                'duration_minutes' => 90,
                'options' => [
                    ['name' => 'น้ำมันกลิ่นโรสแมรี่', 'price' => 30],
                ],
                'is_featured' => true,
                'commission_rate' => 10.00,
            ],
            [
                'name' => 'นวดฝ่าเท้า 45 นาที',
                'slug' => 'foot-massage-45min',
                'description' => 'นวดกดจุดฝ่าเท้า ช่วยกระตุ้นระบบไหลเวียนเลือด',
                'base_price' => 250.00,
                'duration_minutes' => 45,
                'options' => [
                    ['name' => 'นวดน่อง', 'price' => 100],
                ],
                'is_featured' => false,
                'commission_rate' => 10.00,
            ],
            [
                'name' => 'นวดศีรษะ ไหล่ คอ',
                'slug' => 'head-shoulder-massage',
                'description' => 'นวดบริเวณศีรษะ ไหล่ คอ ช่วยลดอาการปวดหัว เครียด',
                'base_price' => 300.00,
                'duration_minutes' => 45,
                'options' => [],
                'is_featured' => false,
                'commission_rate' => 10.00,
            ],
        ],

        // ============ ร้านทำผม ============
        'hair-salon' => [
            [
                'name' => 'ตัดผมชาย',
                'slug' => 'mens-haircut',
                'description' => 'ตัดผมชายสไตล์โมเดิร์น โดยช่างผู้ชำนาญ',
                'base_price' => 200.00,
                'duration_minutes' => 30,
                'options' => [
                    ['name' => 'โกนหนวด', 'price' => 50],
                    ['name' => 'สระผม', 'price' => 50],
                ],
                'is_featured' => true,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'ตัดผมหญิง',
                'slug' => 'womens-haircut',
                'description' => 'ตัดผมหญิง ออกแบบทรงให้เหมาะกับใบหน้า',
                'base_price' => 350.00,
                'duration_minutes' => 60,
                'options' => [
                    ['name' => 'ไดร์ผม', 'price' => 100],
                    ['name' => 'ทรีทเมนท์', 'price' => 200],
                ],
                'is_featured' => true,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'ทำสีผม',
                'slug' => 'hair-coloring',
                'description' => 'ทำสีผมคุณภาพสูง ไม่เจ็บหนังศีรษะ',
                'base_price' => 800.00,
                'duration_minutes' => 120,
                'options' => [
                    ['name' => 'ไฮไลท์', 'price' => 500],
                    ['name' => 'ออมเบร', 'price' => 700],
                ],
                'is_featured' => true,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'ดัดผม',
                'slug' => 'hair-perm',
                'description' => 'ดัดผมลอนสวย ให้ผมมีวอลุ่ม',
                'base_price' => 1200.00,
                'duration_minutes' => 180,
                'options' => [
                    ['name' => 'ทรีทเมนท์หลังดัด', 'price' => 300],
                ],
                'is_featured' => false,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'ยืดผม',
                'slug' => 'hair-straightening',
                'description' => 'ยืดผมตรงเรียบ ใช้สารไม่ทำลายผม',
                'base_price' => 1500.00,
                'duration_minutes' => 180,
                'options' => [
                    ['name' => 'ทรีทเมนท์หลังยืด', 'price' => 300],
                ],
                'is_featured' => false,
                'commission_rate' => 12.00,
            ],
        ],

        // ============ ความงามและสกินแคร์ ============
        'beauty' => [
            [
                'name' => 'ทำเล็บเจล',
                'slug' => 'gel-nails',
                'description' => 'ทำเล็บเจลสวย ทนทาน อยู่ได้นาน 2-3 สัปดาห์',
                'base_price' => 450.00,
                'duration_minutes' => 60,
                'options' => [
                    ['name' => 'ต่อเล็บ', 'price' => 200],
                    ['name' => 'วาดลาย', 'price' => 150],
                ],
                'is_featured' => true,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'แต่งหน้าออกงาน',
                'slug' => 'event-makeup',
                'description' => 'แต่งหน้าสวยพร้อมออกงาน โดยช่างแต่งหน้ามืออาชีพ',
                'base_price' => 1500.00,
                'duration_minutes' => 90,
                'options' => [
                    ['name' => 'ทำผม', 'price' => 800],
                    ['name' => 'ติดขนตาปลอม', 'price' => 200],
                ],
                'is_featured' => true,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'ทรีทเมนท์หน้าใส',
                'slug' => 'facial-treatment',
                'description' => 'ทรีทเมนท์บำรุงผิวหน้า ลดสิว ผิวกระจ่างใส',
                'base_price' => 800.00,
                'duration_minutes' => 60,
                'options' => [
                    ['name' => 'มาส์กคอลลาเจน', 'price' => 300],
                    ['name' => 'วิตามินซี', 'price' => 200],
                ],
                'is_featured' => true,
                'commission_rate' => 12.00,
            ],
        ],

        // ============ ซ่อมแอร์ ============
        'air-conditioner' => [
            [
                'name' => 'ล้างแอร์บ้าน',
                'slug' => 'home-ac-cleaning',
                'description' => 'ล้างแอร์บ้าน ทำความสะอาดคอยล์ เติมน้ำยา',
                'base_price' => 400.00,
                'duration_minutes' => 60,
                'options' => [
                    ['name' => 'เติมน้ำยา R32', 'price' => 300],
                    ['name' => 'ฆ่าเชื้อโควิด', 'price' => 100],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'ซ่อมแอร์ไม่เย็น',
                'slug' => 'ac-not-cooling-repair',
                'description' => 'ตรวจซ่อมแอร์ไม่เย็น รับประกันผลงาน',
                'base_price' => 500.00,
                'duration_minutes' => 90,
                'options' => [
                    ['name' => 'เปลี่ยนคอมเพรสเซอร์', 'price' => 3500],
                    ['name' => 'เปลี่ยนบอร์ด', 'price' => 2000],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'ติดตั้งแอร์ใหม่',
                'slug' => 'ac-installation',
                'description' => 'บริการติดตั้งแอร์ใหม่ พร้อมเดินท่อ',
                'base_price' => 1500.00,
                'duration_minutes' => 180,
                'options' => [
                    ['name' => 'ท่อทองแดงเพิ่ม (ต่อเมตร)', 'price' => 350],
                ],
                'is_featured' => false,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'ย้ายแอร์',
                'slug' => 'ac-relocation',
                'description' => 'บริการย้ายแอร์พร้อมติดตั้งใหม่',
                'base_price' => 2000.00,
                'duration_minutes' => 240,
                'options' => [],
                'is_featured' => false,
                'commission_rate' => 15.00,
            ],
        ],

        // ============ ช่างไฟฟ้า ============
        'electrician' => [
            [
                'name' => 'ซ่อมไฟไม่ติด',
                'slug' => 'light-repair',
                'description' => 'ตรวจซ่อมไฟไม่ติด เปลี่ยนหลอด สวิตช์',
                'base_price' => 300.00,
                'duration_minutes' => 60,
                'options' => [
                    ['name' => 'เปลี่ยนหลอด LED', 'price' => 150],
                    ['name' => 'เปลี่ยนสวิตช์', 'price' => 100],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'เดินสายไฟใหม่',
                'slug' => 'electrical-wiring',
                'description' => 'เดินสายไฟใหม่ปลอดภัยตามมาตรฐาน',
                'base_price' => 800.00,
                'duration_minutes' => 180,
                'options' => [
                    ['name' => 'ติดตู้เบรกเกอร์', 'price' => 1500],
                ],
                'is_featured' => false,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'ตรวจระบบไฟฟ้า',
                'slug' => 'electrical-inspection',
                'description' => 'ตรวจสอบระบบไฟฟ้าทั้งบ้าน หาจุดรั่ว',
                'base_price' => 500.00,
                'duration_minutes' => 90,
                'options' => [],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
        ],

        // ============ ช่างประปา ============
        'plumber' => [
            [
                'name' => 'แก้ท่อตัน',
                'slug' => 'unclog-drain',
                'description' => 'แก้ท่อตัน อ่างล้างจาน โถส้วม ท่อน้ำทิ้ง',
                'base_price' => 350.00,
                'duration_minutes' => 60,
                'options' => [
                    ['name' => 'ใช้เครื่องดูด', 'price' => 200],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'ซ่อมก๊อกน้ำรั่ว',
                'slug' => 'faucet-leak-repair',
                'description' => 'ซ่อมก๊อกน้ำรั่ว เปลี่ยนอะไหล่',
                'base_price' => 250.00,
                'duration_minutes' => 45,
                'options' => [
                    ['name' => 'เปลี่ยนก๊อกใหม่', 'price' => 500],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'ติดตั้งเครื่องทำน้ำอุ่น',
                'slug' => 'water-heater-install',
                'description' => 'ติดตั้งเครื่องทำน้ำอุ่นพร้อมเดินท่อ',
                'base_price' => 800.00,
                'duration_minutes' => 120,
                'options' => [],
                'is_featured' => false,
                'commission_rate' => 15.00,
            ],
        ],

        // ============ ซ่อมเครื่องใช้ไฟฟ้า ============
        'appliance-repair' => [
            [
                'name' => 'ซ่อมเครื่องซักผ้า',
                'slug' => 'washing-machine-repair',
                'description' => 'ตรวจซ่อมเครื่องซักผ้า ทุกยี่ห้อ',
                'base_price' => 500.00,
                'duration_minutes' => 90,
                'options' => [
                    ['name' => 'เปลี่ยนมอเตอร์', 'price' => 2500],
                    ['name' => 'เปลี่ยนสายพาน', 'price' => 500],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'ซ่อมตู้เย็น',
                'slug' => 'refrigerator-repair',
                'description' => 'ตรวจซ่อมตู้เย็น ไม่เย็น น้ำแข็งเกาะ',
                'base_price' => 500.00,
                'duration_minutes' => 90,
                'options' => [
                    ['name' => 'เติมน้ำยา', 'price' => 800],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'ซ่อมไมโครเวฟ',
                'slug' => 'microwave-repair',
                'description' => 'ตรวจซ่อมไมโครเวฟ ไม่ร้อน ไม่หมุน',
                'base_price' => 400.00,
                'duration_minutes' => 60,
                'options' => [],
                'is_featured' => false,
                'commission_rate' => 15.00,
            ],
        ],

        // ============ งานช่างทั่วไป ============
        'handyman' => [
            [
                'name' => 'ประกอบเฟอร์นิเจอร์ IKEA',
                'slug' => 'ikea-furniture-assembly',
                'description' => 'ประกอบเฟอร์นิเจอร์ IKEA ทุกชนิด',
                'base_price' => 300.00,
                'duration_minutes' => 120,
                'options' => [],
                'is_featured' => true,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'ติดทีวีแขวนผนัง',
                'slug' => 'tv-wall-mount',
                'description' => 'ติดตั้งทีวีแขวนผนัง พร้อมซ่อนสายไฟ',
                'base_price' => 500.00,
                'duration_minutes' => 60,
                'options' => [
                    ['name' => 'ขาแขวนทีวี', 'price' => 500],
                ],
                'is_featured' => true,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'ซ่อมประตู หน้าต่าง',
                'slug' => 'door-window-repair',
                'description' => 'ซ่อมประตู หน้าต่าง บานพับ ลูกบิด',
                'base_price' => 350.00,
                'duration_minutes' => 60,
                'options' => [],
                'is_featured' => false,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'ทาสีห้อง',
                'slug' => 'room-painting',
                'description' => 'ทาสีห้อง ราคาต่อห้อง (ไม่เกิน 20 ตร.ม.)',
                'base_price' => 2500.00,
                'duration_minutes' => 480,
                'options' => [
                    ['name' => 'รวมสี', 'price' => 1000],
                ],
                'is_featured' => false,
                'commission_rate' => 12.00,
            ],
        ],

        // ============ ทำความสะอาด ============
        'house-cleaning' => [
            [
                'name' => 'ทำความสะอาดบ้าน ขนาดเล็ก',
                'slug' => 'house-cleaning-small',
                'description' => 'ทำความสะอาดบ้านขนาดเล็ก 1-2 ห้องนอน พื้นที่ไม่เกิน 50 ตร.ม.',
                'base_price' => 500.00,
                'duration_minutes' => 120,
                'options' => [
                    ['name' => 'ทำความสะอาดห้องครัว', 'price' => 200],
                    ['name' => 'ล้างห้องน้ำ', 'price' => 150],
                    ['name' => 'เช็ดกระจก', 'price' => 100],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'ทำความสะอาดบ้าน ขนาดกลาง',
                'slug' => 'house-cleaning-medium',
                'description' => 'ทำความสะอาดบ้านขนาดกลาง 3-4 ห้องนอน พื้นที่ 50-100 ตร.ม.',
                'base_price' => 800.00,
                'duration_minutes' => 180,
                'options' => [
                    ['name' => 'ดูดฝุ่นพรม', 'price' => 200],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'ทำความสะอาดคอนโด',
                'slug' => 'condo-cleaning',
                'description' => 'ทำความสะอาดคอนโด สตูดิโอ - 1 ห้องนอน พื้นที่ไม่เกิน 40 ตร.ม.',
                'base_price' => 400.00,
                'duration_minutes' => 90,
                'options' => [
                    ['name' => 'ซักผ้าม่าน', 'price' => 250],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'ทำความสะอาดหลังก่อสร้าง',
                'slug' => 'post-construction-cleaning',
                'description' => 'ทำความสะอาดหลังก่อสร้าง รีโนเวท ขจัดฝุ่น',
                'base_price' => 1500.00,
                'duration_minutes' => 300,
                'options' => [],
                'is_featured' => false,
                'commission_rate' => 15.00,
            ],
        ],

        // ============ ล้างรถ ============
        'car-wash' => [
            [
                'name' => 'ล้างรถนอกสถานที่',
                'slug' => 'mobile-car-wash',
                'description' => 'บริการล้างรถถึงบ้าน ที่ทำงาน หรือคอนโด',
                'base_price' => 199.00,
                'duration_minutes' => 45,
                'options' => [
                    ['name' => 'ขัดเคลือบสี', 'price' => 500],
                    ['name' => 'ดูดฝุ่นภายใน', 'price' => 150],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'ล้างรถ + เคลือบแก้ว',
                'slug' => 'car-wash-ceramic',
                'description' => 'ล้างรถพร้อมเคลือบแก้ว ป้องกันรอยขีดข่วน',
                'base_price' => 2500.00,
                'duration_minutes' => 180,
                'options' => [],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
        ],

        // ============ ซักรีด ============
        'laundry' => [
            [
                'name' => 'รับซักส่ง (ราคาต่อ กก.)',
                'slug' => 'laundry-pickup-kg',
                'description' => 'รับผ้าซัก-ส่งถึงบ้าน คิดราคาต่อกิโลกรัม',
                'base_price' => 35.00,
                'duration_minutes' => 1440, // 24 ชม.
                'options' => [
                    ['name' => 'รีดผ้า', 'price' => 15],
                    ['name' => 'ซักด่วน 6 ชม.', 'price' => 20],
                ],
                'is_featured' => true,
                'commission_rate' => 20.00,
            ],
            [
                'name' => 'ซักผ้าปูที่นอน/ผ้าห่ม',
                'slug' => 'bedding-laundry',
                'description' => 'ซักผ้าปูที่นอน ปลอกหมอน ผ้าห่ม',
                'base_price' => 150.00,
                'duration_minutes' => 2880, // 48 ชม.
                'options' => [],
                'is_featured' => false,
                'commission_rate' => 20.00,
            ],
        ],

        // ============ จัดส่งอาหาร ============
        'food-delivery' => [
            [
                'name' => 'รับส่งอาหาร',
                'slug' => 'food-delivery',
                'description' => 'บริการรับส่งอาหารจากร้านอาหาร',
                'base_price' => 25.00,
                'duration_minutes' => 45,
                'options' => [
                    ['name' => 'ประกันอาหาร', 'price' => 10],
                ],
                'is_featured' => true,
                'commission_rate' => 25.00,
            ],
        ],

        // ============ จัดส่งพัสดุ ============
        'parcel-delivery' => [
            [
                'name' => 'จัดส่งด่วนภายในเมือง',
                'slug' => 'express-delivery',
                'description' => 'บริการจัดส่งด่วน รับ-ส่ง ภายใน 1-2 ชั่วโมง',
                'base_price' => 80.00,
                'duration_minutes' => 60,
                'options' => [
                    ['name' => 'ประกันสินค้า', 'price' => 30],
                    ['name' => 'บริการ COD', 'price' => 20],
                ],
                'is_featured' => true,
                'commission_rate' => 20.00,
            ],
            [
                'name' => 'รับส่งเอกสาร',
                'slug' => 'document-delivery',
                'description' => 'รับส่งเอกสารด่วน ปลอดภัย',
                'base_price' => 60.00,
                'duration_minutes' => 45,
                'options' => [],
                'is_featured' => true,
                'commission_rate' => 20.00,
            ],
        ],

        // ============ ขนย้าย ============
        'moving' => [
            [
                'name' => 'ขนย้ายห้องเล็ก',
                'slug' => 'small-room-moving',
                'description' => 'ขนย้ายของจากห้องเล็ก สตูดิโอ ห้องพัก',
                'base_price' => 1500.00,
                'duration_minutes' => 180,
                'options' => [
                    ['name' => 'แพ็คของ', 'price' => 500],
                    ['name' => 'ยกของขึ้นลิฟต์', 'price' => 300],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'ขนย้ายคอนโด',
                'slug' => 'condo-moving',
                'description' => 'ขนย้ายคอนโด 1-2 ห้องนอน',
                'base_price' => 3000.00,
                'duration_minutes' => 360,
                'options' => [
                    ['name' => 'แพ็คของ', 'price' => 1000],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'ขนย้ายบ้าน',
                'slug' => 'house-moving',
                'description' => 'ขนย้ายบ้านทั้งหลัง ราคาตามปริมาณของ',
                'base_price' => 5000.00,
                'duration_minutes' => 480,
                'options' => [
                    ['name' => 'รถใหญ่ 6 ล้อ', 'price' => 2000],
                ],
                'is_featured' => false,
                'commission_rate' => 15.00,
            ],
        ],

        // ============ เรียกรถ ============
        'ride-hailing' => [
            [
                'name' => 'เรียกรถยนต์ส่วนตัว',
                'slug' => 'private-car',
                'description' => 'เรียกรถยนต์ส่วนตัวพร้อมคนขับ',
                'base_price' => 45.00,
                'duration_minutes' => 30,
                'options' => [
                    ['name' => 'รถ SUV', 'price' => 30],
                ],
                'is_featured' => true,
                'commission_rate' => 25.00,
            ],
            [
                'name' => 'เรียกมอเตอร์ไซค์',
                'slug' => 'motorcycle-taxi',
                'description' => 'เรียกมอเตอร์ไซค์รับจ้าง รวดเร็ว ประหยัด',
                'base_price' => 25.00,
                'duration_minutes' => 20,
                'options' => [],
                'is_featured' => true,
                'commission_rate' => 25.00,
            ],
        ],

        // ============ สอนพิเศษ ============
        'tutoring' => [
            [
                'name' => 'สอนคณิตศาสตร์ ม.ต้น',
                'slug' => 'math-tutoring-junior',
                'description' => 'สอนพิเศษคณิตศาสตร์ ม.1-3 ปูพื้นฐานแน่น',
                'base_price' => 400.00,
                'duration_minutes' => 90,
                'options' => [
                    ['name' => 'สอน 2 ชม.', 'price' => 150],
                ],
                'is_featured' => true,
                'commission_rate' => 10.00,
            ],
            [
                'name' => 'สอนภาษาอังกฤษ',
                'slug' => 'english-tutoring',
                'description' => 'สอนภาษาอังกฤษ สนทนา ไวยากรณ์ ทุกระดับ',
                'base_price' => 500.00,
                'duration_minutes' => 60,
                'options' => [
                    ['name' => 'Native Speaker', 'price' => 300],
                ],
                'is_featured' => true,
                'commission_rate' => 10.00,
            ],
            [
                'name' => 'สอนภาษาจีน',
                'slug' => 'chinese-tutoring',
                'description' => 'สอนภาษาจีน ตั้งแต่เริ่มต้น สนทนาได้จริง',
                'base_price' => 600.00,
                'duration_minutes' => 60,
                'options' => [],
                'is_featured' => true,
                'commission_rate' => 10.00,
            ],
            [
                'name' => 'สอนวาดรูป',
                'slug' => 'drawing-tutoring',
                'description' => 'สอนวาดรูป สีไม้ สีน้ำ สำหรับเด็กและผู้ใหญ่',
                'base_price' => 450.00,
                'duration_minutes' => 90,
                'options' => [
                    ['name' => 'รวมอุปกรณ์', 'price' => 200],
                ],
                'is_featured' => false,
                'commission_rate' => 10.00,
            ],
            [
                'name' => 'สอนเปียโน',
                'slug' => 'piano-tutoring',
                'description' => 'สอนเปียโนทุกระดับ ตั้งแต่เริ่มต้นจนถึงขั้นสูง',
                'base_price' => 700.00,
                'duration_minutes' => 60,
                'options' => [],
                'is_featured' => false,
                'commission_rate' => 10.00,
            ],
        ],

        // ============ ฟิตเนสและกายภาพบำบัด ============
        'fitness-training' => [
            [
                'name' => 'Personal Training',
                'slug' => 'personal-training',
                'description' => 'เทรนเนอร์ส่วนตัว ออกแบบโปรแกรมเฉพาะคุณ',
                'base_price' => 800.00,
                'duration_minutes' => 60,
                'options' => [
                    ['name' => 'แผนอาหาร', 'price' => 500],
                ],
                'is_featured' => true,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'สอนโยคะที่บ้าน',
                'slug' => 'home-yoga',
                'description' => 'สอนโยคะถึงบ้าน ผ่อนคลาย ยืดเหยียด',
                'base_price' => 600.00,
                'duration_minutes' => 60,
                'options' => [
                    ['name' => 'กลุ่ม 2-3 คน', 'price' => 200],
                ],
                'is_featured' => true,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'กายภาพบำบัด',
                'slug' => 'physical-therapy',
                'description' => 'กายภาพบำบัดที่บ้าน ฟื้นฟูหลังผ่าตัด',
                'base_price' => 1200.00,
                'duration_minutes' => 60,
                'options' => [],
                'is_featured' => true,
                'commission_rate' => 10.00,
            ],
        ],

        // ============ บริการสัตว์เลี้ยง ============
        'pet-services' => [
            [
                'name' => 'อาบน้ำตัดขนสุนัข (ไซส์เล็ก)',
                'slug' => 'dog-grooming-small',
                'description' => 'อาบน้ำ ตัดขน ตัดเล็บ สุนัขตัวเล็ก (ไม่เกิน 5 กก.)',
                'base_price' => 350.00,
                'duration_minutes' => 60,
                'options' => [
                    ['name' => 'ทำสีขน', 'price' => 300],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'อาบน้ำตัดขนสุนัข (ไซส์กลาง)',
                'slug' => 'dog-grooming-medium',
                'description' => 'อาบน้ำ ตัดขน ตัดเล็บ สุนัขตัวกลาง (5-15 กก.)',
                'base_price' => 500.00,
                'duration_minutes' => 90,
                'options' => [],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'Pet Sitting',
                'slug' => 'pet-sitting',
                'description' => 'รับฝากเลี้ยงสัตว์ที่บ้านคุณ ราคาต่อวัน',
                'base_price' => 500.00,
                'duration_minutes' => 1440,
                'options' => [
                    ['name' => 'พาเดินเล่น 2 รอบ', 'price' => 200],
                ],
                'is_featured' => false,
                'commission_rate' => 15.00,
            ],
        ],

        // ============ ดูแลผู้ป่วย/ผู้สูงอายุ ============
        'nursing-care' => [
            [
                'name' => 'พี่เลี้ยงเด็ก (รายวัน)',
                'slug' => 'babysitter-daily',
                'description' => 'บริการพี่เลี้ยงเด็กประจำวัน 8 ชม.',
                'base_price' => 800.00,
                'duration_minutes' => 480,
                'options' => [
                    ['name' => 'ค้างคืน', 'price' => 500],
                ],
                'is_featured' => true,
                'commission_rate' => 10.00,
            ],
            [
                'name' => 'ดูแลผู้สูงอายุ (รายวัน)',
                'slug' => 'elderly-care-daily',
                'description' => 'บริการดูแลผู้สูงอายุที่บ้าน 8 ชม.',
                'base_price' => 1000.00,
                'duration_minutes' => 480,
                'options' => [
                    ['name' => 'ค้างคืน', 'price' => 600],
                ],
                'is_featured' => true,
                'commission_rate' => 10.00,
            ],
            [
                'name' => 'ดูแลผู้ป่วย',
                'slug' => 'patient-care',
                'description' => 'บริการดูแลผู้ป่วยที่บ้าน พร้อมช่วยเหลือกิจวัตร',
                'base_price' => 1200.00,
                'duration_minutes' => 480,
                'options' => [],
                'is_featured' => true,
                'commission_rate' => 10.00,
            ],
        ],

        // ============ ถ่ายภาพ ============
        'photography' => [
            [
                'name' => 'ถ่ายภาพบุคคล',
                'slug' => 'portrait-photography',
                'description' => 'ถ่ายภาพบุคคล/โปรไฟล์ รวมแต่งรูป 10 ภาพ',
                'base_price' => 2500.00,
                'duration_minutes' => 120,
                'options' => [
                    ['name' => 'รูปเพิ่ม 10 ภาพ', 'price' => 1000],
                ],
                'is_featured' => true,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'ถ่ายภาพสินค้า',
                'slug' => 'product-photography',
                'description' => 'ถ่ายภาพสินค้าสำหรับขายออนไลน์ 20 ชิ้น',
                'base_price' => 3000.00,
                'duration_minutes' => 180,
                'options' => [
                    ['name' => 'รีทัช Pro', 'price' => 1500],
                ],
                'is_featured' => true,
                'commission_rate' => 12.00,
            ],
            [
                'name' => 'ถ่ายวิดีโอโปรโมท',
                'slug' => 'promo-video',
                'description' => 'ถ่ายและตัดต่อวิดีโอโปรโมทธุรกิจ 1-3 นาที',
                'base_price' => 5000.00,
                'duration_minutes' => 240,
                'options' => [],
                'is_featured' => false,
                'commission_rate' => 12.00,
            ],
        ],

        // ============ จัดสวน ============
        'gardening' => [
            [
                'name' => 'ตัดหญ้า ตัดแต่งสวน',
                'slug' => 'lawn-trimming',
                'description' => 'ตัดหญ้า ตัดแต่งสวน พื้นที่ไม่เกิน 50 ตร.ม.',
                'base_price' => 500.00,
                'duration_minutes' => 120,
                'options' => [
                    ['name' => 'พื้นที่เพิ่ม 50 ตร.ม.', 'price' => 300],
                ],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'จัดสวนใหม่',
                'slug' => 'garden-design',
                'description' => 'ออกแบบและจัดสวนใหม่ รวมต้นไม้',
                'base_price' => 5000.00,
                'duration_minutes' => 480,
                'options' => [],
                'is_featured' => true,
                'commission_rate' => 15.00,
            ],
            [
                'name' => 'รดน้ำดูแลต้นไม้',
                'slug' => 'plant-watering',
                'description' => 'บริการรดน้ำดูแลต้นไม้ ราคาต่อครั้ง',
                'base_price' => 200.00,
                'duration_minutes' => 30,
                'options' => [],
                'is_featured' => false,
                'commission_rate' => 15.00,
            ],
        ],

        // ============ อื่นๆ ============
        'others' => [
            [
                'name' => 'รับจ้างต่อคิว',
                'slug' => 'queue-service',
                'description' => 'รับจ้างต่อคิว สำนักงานราชการ ธนาคาร',
                'base_price' => 200.00,
                'duration_minutes' => 120,
                'options' => [],
                'is_featured' => true,
                'commission_rate' => 20.00,
            ],
            [
                'name' => 'รับจ้างซื้อของ',
                'slug' => 'shopping-service',
                'description' => 'รับจ้างซื้อของจากห้างสรรพสินค้า ตลาด',
                'base_price' => 100.00,
                'duration_minutes' => 60,
                'options' => [],
                'is_featured' => true,
                'commission_rate' => 20.00,
            ],
        ],
    ];

    /**
     * สร้างบริการตัวอย่าง
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed บริการตัวอย่าง...');

        // หา owner (admin user)
        $owner = User::where('role', 'admin')->first() ?? User::first();

        if (! $owner) {
            $this->command->error('❌ ไม่พบ User สำหรับเป็น owner บริการ');

            return;
        }

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($this->servicesByCategory as $categorySlug => $services) {
            // หา category
            $category = ServiceCategory::where('slug', $categorySlug)->first();

            if (! $category) {
                $this->command->warn("  ⚠️  ไม่พบหมวดหมู่ '{$categorySlug}' ข้าม...");

                continue;
            }

            $this->command->info("📁 หมวดหมู่: {$category->name}");

            foreach ($services as $serviceData) {
                // ตรวจสอบว่ามีบริการนี้อยู่แล้วหรือไม่
                $existing = Service::where('slug', $serviceData['slug'])->first();

                if ($existing) {
                    $this->command->warn("    ⏭️  บริการ '{$serviceData['name']}' มีอยู่แล้ว ข้าม...");
                    $totalSkipped++;

                    continue;
                }

                Service::create([
                    'category_id' => $category->id,
                    'owner_id' => $owner->id,
                    'name' => $serviceData['name'],
                    'slug' => $serviceData['slug'],
                    'description' => $serviceData['description'],
                    'base_price' => $serviceData['base_price'],
                    'duration_minutes' => $serviceData['duration_minutes'],
                    'images' => ["/images/services/{$serviceData['slug']}.jpg"],
                    'options' => $serviceData['options'],
                    'requires_location' => true,
                    'is_active' => true,
                    'is_featured' => $serviceData['is_featured'] ?? false,
                    'min_booking_hours' => $serviceData['min_booking_hours'] ?? 2,
                    'cancellation_hours' => $serviceData['cancellation_hours'] ?? 4,
                    'commission_rate' => $serviceData['commission_rate'],
                ]);

                $this->command->info("    ✅ สร้างบริการ '{$serviceData['name']}' สำเร็จ");
                $totalCreated++;
            }
        }

        $total = Service::count();
        $this->command->info('');
        $this->command->info('✅ Seed บริการตัวอย่างสำเร็จ!');
        $this->command->info("   📊 สร้างใหม่: {$totalCreated} บริการ");
        $this->command->info("   ⏭️  ข้าม: {$totalSkipped} บริการ");
        $this->command->info("   📈 รวมทั้งหมด: {$total} บริการ");
    }
}
