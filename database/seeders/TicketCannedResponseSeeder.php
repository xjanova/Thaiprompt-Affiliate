<?php

namespace Database\Seeders;

use App\Models\TicketCannedResponse;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketCannedResponseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first admin user
        $admin = User::where('is_super_admin', true)->orWhere('role', 'admin')->first();

        if (!$admin) {
            $this->command->warn('⚠ No admin user found. Skipping canned responses seeding.');
            return;
        }

        $responses = [
            [
                'title' => 'ทักทายและขอข้อมูลเพิ่มเติม',
                'shortcode' => '/greeting',
                'content' => 'สวัสดีครับคุณ {user_name}

ขอบคุณที่ติดต่อเรา เราได้รับ Ticket #{ticket_number} ของคุณแล้ว

เพื่อให้เราสามารถช่วยเหลือคุณได้อย่างมีประสิทธิภาพ กรุณาให้ข้อมูลเพิ่มเติมดังนี้:
1. รายละเอียดของปัญหาที่พบ
2. ขั้นตอนที่ทำให้เกิดปัญหา
3. ภาพหน้าจอ (ถ้ามี)

ขอบคุณครับ
{agent_name}',
                'category_id' => null,
                'tags' => ['greeting', 'request_info'],
                'is_public' => true,
                'created_by' => $admin->id,
                'is_active' => true,
                'sort_order' => 100,
            ],
            [
                'title' => 'ได้รับข้อมูลแล้ว - กำลังตรวจสอบ',
                'shortcode' => '/investigating',
                'content' => 'สวัสดีครับคุณ {user_name}

ขอบคุณสำหรับข้อมูลเพิ่มเติม ทีมงานของเรากำลังตรวจสอบปัญหาที่คุณพบ และจะดำเนินการแก้ไขโดยเร็วที่สุด

เราจะแจ้งความคืบหน้าให้คุณทราบอีกครั้งภายใน 24 ชั่วโมง

ขอบคุณที่รอครับ
{agent_name}',
                'category_id' => null,
                'tags' => ['investigating', 'acknowledge'],
                'is_public' => true,
                'created_by' => $admin->id,
                'is_active' => true,
                'sort_order' => 90,
            ],
            [
                'title' => 'แก้ไขปัญหาเรียบร้อย',
                'shortcode' => '/resolved',
                'content' => 'สวัสดีครับคุณ {user_name}

เรายินดีที่จะแจ้งให้ทราบว่า ปัญหาของคุณได้รับการแก้ไขเรียบร้อยแล้ว

กรุณาตรวจสอบและยืนยันว่าทุกอย่างทำงานได้ปกติ หากยังพบปัญหา กรุณาแจ้งให้เราทราบได้ทันที

ขอบคุณที่ใช้บริการครับ
{agent_name}',
                'category_id' => null,
                'tags' => ['resolved', 'closing'],
                'is_public' => true,
                'created_by' => $admin->id,
                'is_active' => true,
                'sort_order' => 80,
            ],
            [
                'title' => 'รอการตอบกลับจากลูกค้า',
                'shortcode' => '/waiting',
                'content' => 'สวัสดีครับคุณ {user_name}

เราได้ส่งข้อมูลและวิธีแก้ไขให้คุณไปแล้ว กรุณาตรวจสอบและแจ้งผลกลับมาให้เราทราบ

หากไม่ได้รับการตอบกลับภายใน 3 วัน เราจะถือว่าปัญหาได้รับการแก้ไขแล้วและปิด Ticket นี้

ขอบคุณครับ
{agent_name}',
                'category_id' => null,
                'tags' => ['waiting', 'follow_up'],
                'is_public' => true,
                'created_by' => $admin->id,
                'is_active' => true,
                'sort_order' => 70,
            ],
            [
                'title' => 'ขอข้อมูลการเข้าสู่ระบบ',
                'shortcode' => '/login_issue',
                'content' => 'สวัสดีครับคุณ {user_name}

เพื่อช่วยแก้ไขปัญหาการเข้าสู่ระบบ กรุณาให้ข้อมูลดังนี้:
1. Username หรือ Email ที่ใช้เข้าสู่ระบบ
2. ข้อความ Error ที่แสดง (ถ้ามี)
3. เบราว์เซอร์ที่ใช้งาน
4. วันและเวลาที่พบปัญหา

หากลืมรหัสผ่าน คุณสามารถใช้ฟังก์ชัน "Forgot Password" ที่หน้าเข้าสู่ระบบได้เลยครับ

ขอบคุณครับ
{agent_name}',
                'category_id' => null,
                'tags' => ['login', 'account'],
                'is_public' => true,
                'created_by' => $admin->id,
                'is_active' => true,
                'sort_order' => 60,
            ],
            [
                'title' => 'ปัญหาการชำระเงิน',
                'shortcode' => '/payment_issue',
                'content' => 'สวัสดีครับคุณ {user_name}

เราเสียใจที่คุณพบปัญหาในการชำระเงิน กรุณาให้ข้อมูลดังนี้:
1. หมายเลขคำสั่งซื้อ (Order ID)
2. วิธีการชำระเงินที่ใช้
3. วันและเวลาที่ทำรายการ
4. จำนวนเงินที่โอน
5. สลิปโอนเงิน หรือหลักฐานการชำระเงิน

เราจะตรวจสอบและดำเนินการให้โดยเร็วที่สุดครับ

ขอบคุณครับ
{agent_name}',
                'category_id' => null,
                'tags' => ['payment', 'finance'],
                'is_public' => true,
                'created_by' => $admin->id,
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'title' => 'ส่งต่อให้ทีมเทคนิค',
                'shortcode' => '/escalate',
                'content' => 'สวัสดีครับคุณ {user_name}

ปัญหาของคุณต้องการการตรวจสอบเชิงเทคนิคเพิ่มเติม เราได้ส่งต่อให้ทีมเทคนิคของเราแล้ว

ทีมเทคนิคจะติดต่อกลับภายใน 24-48 ชั่วโมง

ขอบคุณที่อดทนรอครับ
{agent_name}',
                'category_id' => null,
                'tags' => ['escalate', 'technical'],
                'is_public' => true,
                'created_by' => $admin->id,
                'is_active' => true,
                'sort_order' => 40,
            ],
        ];

        foreach ($responses as $response) {
            TicketCannedResponse::updateOrCreate(
                ['shortcode' => $response['shortcode']],
                $response
            );
        }

        $this->command->info('✓ Canned responses seeded successfully!');
    }
}
