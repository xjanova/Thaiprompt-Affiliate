<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categories = [
            [
                'name' => 'การสนับสนุนทางเทคนิค',
                'icon' => 'fa-solid fa-screwdriver-wrench',
                'description' => 'ปัญหาทางเทคนิค, บั๊ก, หรือข้อผิดพลาดของระบบ',
                'color' => '#3B82F6', // Blue
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'การเงินและการชำระเงิน',
                'icon' => 'fa-solid fa-wallet',
                'description' => 'คำถามเกี่ยวกับการชำระเงิน, ค่าคอมมิชชั่น, การถอนเงิน',
                'color' => '#10B981', // Green
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'บัญชีผู้ใช้',
                'icon' => 'fa-solid fa-id-card',
                'description' => 'ปัญหาเกี่ยวกับบัญชี, การเข้าสู่ระบบ, หรือข้อมูลส่วนตัว',
                'color' => '#8B5CF6', // Purple
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'ระบบแอฟฟิลิเอท',
                'icon' => 'fa-solid fa-sitemap',
                'description' => 'คำถามเกี่ยวกับโครงสร้างแอฟฟิลิเอท, การเชิญชวน, หรือดาวน์ไลน์',
                'color' => '#F59E0B', // Orange
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'ผลิตภัณฑ์และบริการ',
                'icon' => 'fa-solid fa-gift',
                'description' => 'คำถามเกี่ยวกับผลิตภัณฑ์, บริการ, หรือคุณสมบัติ',
                'color' => '#EC4899', // Pink
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'ข้อเสนอแนะ',
                'icon' => 'fa-solid fa-comment-dots',
                'description' => 'ข้อเสนอแนะ, คำขอคุณสมบัติใหม่, หรือการปรับปรุง',
                'color' => '#14B8A6', // Teal
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'อื่นๆ',
                'icon' => 'fa-solid fa-circle-question',
                'description' => 'คำถามทั่วไปหรือเรื่องอื่นๆ',
                'color' => '#6B7280', // Gray
                'sort_order' => 7,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('ticket_categories')->insert(array_merge($category, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('ticket_categories')->truncate();
    }
};
