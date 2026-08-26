<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตารางค่าที่แอดมินแก้ได้ของ "การ์ดทางเข้า" (2026-08-26)
 *
 * เก็บเฉพาะ "ของที่ถูกทับ" — แถวไม่มี / ช่องว่าง = ใช้ค่าเดิมในโค้ด
 * ⇒ ไม่ต้อง seed และไม่พังถ้าโค้ดเพิ่มการ์ดใบใหม่ทีหลัง
 *
 * 🚨 image_path ชี้ไปที่ disk `public` (storage/app/public/...) **ห้ามชี้เข้า public/images/**
 *    เพราะ deploy.sh:814 รัน `git clean -fdx -e 'storage/app/public/*'`
 *    ⇒ ของใน public/images ที่ถูกอัปทับจะโดน git คืนค่าเดิมทุก deploy (รูปที่อัปหาย)
 *    ส่วน storage/app/public ถูก exclude ไว้ + มี backup ที่ deploy.sh:496
 */
return new class extends Migration
{
    /**
     * สร้างตาราง fortune_entry_cards
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_entry_cards')) {
            return;
        }

        Schema::create('fortune_entry_cards', function (Blueprint $table) {
            $table->id();

            // คีย์ของการ์ด: entry-free / entry-vip / day-0 … day-6
            $table->string('card_key', 32)->unique();

            // รูปที่แอดมินอัปทับ — null = ใช้รูปที่มากับโค้ด
            $table->string('image_path', 255)->nullable()
                ->comment('path บน disk public เช่น fortune/cards/entry-free.jpg — null = ใช้รูปเดิมในโค้ด');

            // ค่าที่แอดมินพิมพ์ทับ — null/ว่าง = ใช้ค่าเดิม
            $table->string('title', 120)->nullable()->comment('หัวข้อบนการ์ด (FB ตัดที่ 80)');
            $table->string('subtitle', 120)->nullable()->comment('คำบรรยายใต้หัวข้อ (FB ตัดที่ 80)');
            $table->string('button_label', 40)->nullable()->comment('ป้ายปุ่ม (FB ตัดที่ 20)');

            // 'invite' = ย่อคำจากข้อความชวนที่หมุนอยู่ · 'custom' = ใช้ title/subtitle ที่พิมพ์เอง
            // มีผลกับการ์ดที่ค่าเดิมมาจากคลังข้อความ (entry-free) เท่านั้น
            $table->string('text_mode', 16)->default('invite');

            $table->timestamps();
        });
    }

    /**
     * ลบตาราง fortune_entry_cards
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_entry_cards');
    }
};
