<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม category 'fortune' ในตาราง line_bot_keywords
     *
     * เพื่อรองรับ keywords สำหรับระบบดูดวง Fortune Bot
     * แยกจาก Hybrid Bot keywords ที่มีอยู่เดิม
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('line_bot_keywords')) {
            return;
        }

        // เปลี่ยน enum เพิ่ม 'fortune' category
        DB::statement("ALTER TABLE line_bot_keywords MODIFY COLUMN category ENUM('faq','support','product','custom','fortune') DEFAULT 'custom'");
    }

    /**
     * คืนค่า category enum เดิม
     *
     * @return void
     */
    public function down(): void
    {
        if (! Schema::hasTable('line_bot_keywords')) {
            return;
        }

        // ลบ fortune keywords ก่อน แล้วคืนค่า enum เดิม
        DB::table('line_bot_keywords')->where('category', 'fortune')->update(['category' => 'custom']);
        DB::statement("ALTER TABLE line_bot_keywords MODIFY COLUMN category ENUM('faq','support','product','custom') DEFAULT 'custom'");
    }
};
