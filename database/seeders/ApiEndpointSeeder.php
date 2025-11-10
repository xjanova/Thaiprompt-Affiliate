<?php

namespace Database\Seeders;

use App\Models\ApiEndpoint;
use Illuminate\Database\Seeder;

class ApiEndpointSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $endpoints = [
            // User Management
            [
                'name' => 'รายการผู้ใช้',
                'path' => 'api/v1/users',
                'method' => 'GET',
                'category' => 'Users',
                'description' => 'ดึงรายการผู้ใช้ทั้งหมดพร้อมการแบ่งหน้า รองรับการค้นหาและฟิลเตอร์',
                'is_active' => true,
                'requires_auth' => true,
                'rate_limit_per_minute' => 60,
                'rate_limit_per_hour' => 1000,
                'rate_limit_per_day' => 10000,
                'allowed_roles' => ['admin', 'user'],
                'priority' => 10,
            ],
            [
                'name' => 'สร้างผู้ใช้ใหม่',
                'path' => 'api/v1/users',
                'method' => 'POST',
                'category' => 'Users',
                'description' => 'สร้างบัญชีผู้ใช้ใหม่ ต้องการข้อมูล: name, email, password',
                'is_active' => true,
                'requires_auth' => true,
                'rate_limit_per_minute' => 10,
                'rate_limit_per_hour' => 100,
                'rate_limit_per_day' => 500,
                'allowed_roles' => ['admin'],
                'priority' => 11,
            ],
            [
                'name' => 'ข้อมูลผู้ใช้',
                'path' => 'api/v1/users/{id}',
                'method' => 'GET',
                'category' => 'Users',
                'description' => 'ดึงข้อมูลผู้ใช้ตาม ID พร้อมข้อมูลเพิ่มเติม',
                'is_active' => true,
                'requires_auth' => true,
                'rate_limit_per_minute' => 60,
                'rate_limit_per_hour' => 1000,
                'rate_limit_per_day' => 10000,
                'allowed_roles' => ['admin', 'user'],
                'priority' => 12,
            ],

            // Products
            [
                'name' => 'รายการสินค้า',
                'path' => 'api/v1/products',
                'method' => 'GET',
                'category' => 'Products',
                'description' => 'ดึงรายการสินค้าทั้งหมด รองรับการค้นหา, ฟิลเตอร์ และเรียงลำดับ',
                'is_active' => true,
                'requires_auth' => false,
                'rate_limit_per_minute' => 100,
                'rate_limit_per_hour' => 2000,
                'rate_limit_per_day' => 20000,
                'allowed_roles' => null,
                'priority' => 20,
            ],
            [
                'name' => 'ข้อมูลสินค้า',
                'path' => 'api/v1/products/{id}',
                'method' => 'GET',
                'category' => 'Products',
                'description' => 'ดึงข้อมูลสินค้าตาม ID พร้อมรายละเอียดทั้งหมด',
                'is_active' => true,
                'requires_auth' => false,
                'rate_limit_per_minute' => 100,
                'rate_limit_per_hour' => 2000,
                'rate_limit_per_day' => 20000,
                'allowed_roles' => null,
                'priority' => 21,
            ],
            [
                'name' => 'สร้างสินค้า',
                'path' => 'api/v1/products',
                'method' => 'POST',
                'category' => 'Products',
                'description' => 'สร้างสินค้าใหม่ ต้องการข้อมูล: name, description, price, stock',
                'is_active' => true,
                'requires_auth' => true,
                'rate_limit_per_minute' => 20,
                'rate_limit_per_hour' => 200,
                'rate_limit_per_day' => 1000,
                'allowed_roles' => ['admin', 'seller'],
                'priority' => 22,
            ],

            // Orders
            [
                'name' => 'รายการคำสั่งซื้อ',
                'path' => 'api/v1/orders',
                'method' => 'GET',
                'category' => 'Orders',
                'description' => 'ดึงรายการคำสั่งซื้อของผู้ใช้ รองรับฟิลเตอร์ตามสถานะ',
                'is_active' => true,
                'requires_auth' => true,
                'rate_limit_per_minute' => 60,
                'rate_limit_per_hour' => 1000,
                'rate_limit_per_day' => 10000,
                'allowed_roles' => ['admin', 'user', 'seller'],
                'priority' => 30,
            ],
            [
                'name' => 'สร้างคำสั่งซื้อ',
                'path' => 'api/v1/orders',
                'method' => 'POST',
                'category' => 'Orders',
                'description' => 'สร้างคำสั่งซื้อใหม่ ต้องการข้อมูล: items, shipping_address, payment_method',
                'is_active' => true,
                'requires_auth' => true,
                'rate_limit_per_minute' => 10,
                'rate_limit_per_hour' => 100,
                'rate_limit_per_day' => 500,
                'allowed_roles' => ['admin', 'user'],
                'priority' => 31,
            ],

            // Analytics
            [
                'name' => 'สถิติการขาย',
                'path' => 'api/v1/analytics/sales',
                'method' => 'GET',
                'category' => 'Analytics',
                'description' => 'ดึงสถิติการขายรายวัน, รายเดือน, รายปี พร้อมกราฟ',
                'is_active' => true,
                'requires_auth' => true,
                'rate_limit_per_minute' => 30,
                'rate_limit_per_hour' => 500,
                'rate_limit_per_day' => 5000,
                'allowed_roles' => ['admin', 'seller'],
                'priority' => 40,
            ],
            [
                'name' => 'สถิติผู้ใช้',
                'path' => 'api/v1/analytics/users',
                'method' => 'GET',
                'category' => 'Analytics',
                'description' => 'ดึงสถิติผู้ใช้ จำนวนสมาชิกใหม่ อัตราการใช้งาน',
                'is_active' => true,
                'requires_auth' => true,
                'rate_limit_per_minute' => 30,
                'rate_limit_per_hour' => 500,
                'rate_limit_per_day' => 5000,
                'allowed_roles' => ['admin'],
                'priority' => 41,
            ],

            // Affiliate
            [
                'name' => 'ข้อมูล Affiliate',
                'path' => 'api/v1/affiliate/stats',
                'method' => 'GET',
                'category' => 'Affiliate',
                'description' => 'ดึงข้อมูลสถิติ Affiliate ของผู้ใช้ คอมมิชชั่น ยอดขาย',
                'is_active' => true,
                'requires_auth' => true,
                'rate_limit_per_minute' => 60,
                'rate_limit_per_hour' => 1000,
                'rate_limit_per_day' => 10000,
                'allowed_roles' => ['admin', 'user'],
                'priority' => 50,
            ],
            [
                'name' => 'สร้าง Affiliate Link',
                'path' => 'api/v1/affiliate/links',
                'method' => 'POST',
                'category' => 'Affiliate',
                'description' => 'สร้าง Affiliate Link สำหรับสินค้าหรือหน้าเฉพาะ',
                'is_active' => true,
                'requires_auth' => true,
                'rate_limit_per_minute' => 30,
                'rate_limit_per_hour' => 500,
                'rate_limit_per_day' => 2000,
                'allowed_roles' => ['admin', 'user'],
                'priority' => 51,
            ],

            // Hotels
            [
                'name' => 'รายการโรงแรม',
                'path' => 'api/v1/hotels',
                'method' => 'GET',
                'category' => 'Hotels',
                'description' => 'ดึงรายการโรงแรมทั้งหมด รองรับการค้นหาและฟิลเตอร์ตามเมือง',
                'is_active' => true,
                'requires_auth' => false,
                'rate_limit_per_minute' => 100,
                'rate_limit_per_hour' => 2000,
                'rate_limit_per_day' => 20000,
                'allowed_roles' => null,
                'priority' => 60,
            ],
            [
                'name' => 'จองโรงแรม',
                'path' => 'api/v1/hotels/bookings',
                'method' => 'POST',
                'category' => 'Hotels',
                'description' => 'สร้างการจองโรงแรมใหม่ ต้องการข้อมูล: hotel_id, check_in, check_out, rooms',
                'is_active' => true,
                'requires_auth' => true,
                'rate_limit_per_minute' => 20,
                'rate_limit_per_hour' => 200,
                'rate_limit_per_day' => 1000,
                'allowed_roles' => ['admin', 'user'],
                'priority' => 61,
            ],

            // Wallet
            [
                'name' => 'ข้อมูลกระเป๋าเงิน',
                'path' => 'api/v1/wallet/balance',
                'method' => 'GET',
                'category' => 'Wallet',
                'description' => 'ดึงยอดเงินในกระเป๋าเงินของผู้ใช้',
                'is_active' => true,
                'requires_auth' => true,
                'rate_limit_per_minute' => 60,
                'rate_limit_per_hour' => 1000,
                'rate_limit_per_day' => 10000,
                'allowed_roles' => ['admin', 'user', 'seller'],
                'priority' => 70,
            ],
            [
                'name' => 'ประวัติธุรกรรม',
                'path' => 'api/v1/wallet/transactions',
                'method' => 'GET',
                'category' => 'Wallet',
                'description' => 'ดึงประวัติธุรกรรมการเงินทั้งหมด รองรับการแบ่งหน้า',
                'is_active' => true,
                'requires_auth' => true,
                'rate_limit_per_minute' => 60,
                'rate_limit_per_hour' => 1000,
                'rate_limit_per_day' => 10000,
                'allowed_roles' => ['admin', 'user', 'seller'],
                'priority' => 71,
            ],

            // AI/Chatbot
            [
                'name' => 'Chat กับ AI',
                'path' => 'api/v1/ai/chat',
                'method' => 'POST',
                'category' => 'AI',
                'description' => 'ส่งข้อความถึง AI chatbot และรับคำตอบ',
                'is_active' => true,
                'requires_auth' => true,
                'rate_limit_per_minute' => 20,
                'rate_limit_per_hour' => 200,
                'rate_limit_per_day' => 1000,
                'allowed_roles' => ['admin', 'user'],
                'priority' => 80,
            ],
        ];

        foreach ($endpoints as $endpoint) {
            ApiEndpoint::updateOrCreate(
                [
                    'path' => $endpoint['path'],
                    'method' => $endpoint['method'],
                ],
                $endpoint
            );
        }

        $this->command->info('✓ สร้างข้อมูล API endpoints เรียบร้อยแล้ว: ' . count($endpoints) . ' endpoints');
    }
}
