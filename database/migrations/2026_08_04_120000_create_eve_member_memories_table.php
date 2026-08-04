<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ความจำบทสนทนาของน้อง Eve — เก็บให้ "สมาชิกที่ล็อกอิน" เท่านั้น 7 วัน
 *
 * ทำไมต้องลงตาราง ไม่เก็บในแคช:
 *   - CACHE_DRIVER=file และ deploy.sh สั่ง `php artisan cache:clear` หลายจุด
 *     ทุกครั้งที่ push ขึ้น claude/Main = auto-deploy → ความจำในแคชโดนล้างสัปดาห์ละหลายรอบ
 *   - แคชไฟล์ไล่ดูรายคนไม่ได้ → คำขอ PDPA "ลบข้อมูลของฉัน" ทำไม่ได้เลย
 *
 * แถวละ 1 สมาชิก (unique user_id) — ไม่ใช่แถวละข้อความ เพื่อให้ลบ/อ่านจบในคิวรีเดียว
 *
 * ⚠️ คนละตารางกับ `eve_memories` (บทเรียนรวมของ Eve ฝั่งแอดมิน ไม่ผูกกับผู้ใช้)
 */
return new class extends Migration
{
    public function up(): void
    {
        // CREATE TABLE ใช้ hasTable + return ได้ (กฎโปรเจกต์: ห้ามใช้แบบนี้กับการเพิ่มคอลัมน์)
        if (Schema::hasTable('eve_member_memories')) {
            return;
        }

        Schema::create('eve_member_memories', function (Blueprint $table) {
            $table->id();

            // ผูกกับสมาชิก — ลบผู้ใช้แล้วความจำต้องหายตาม
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // เทิร์นสนทนาแบบ [{role, content, ts}] — เก็บบนดิสก์ได้มากกว่าที่ replay เข้า prompt
            $table->json('turns')->nullable();

            // บทสรุปเทิร์นเก่าที่ถูกย่อทิ้งไปแล้ว (คุมความยาว prompt ไม่ให้บวมตามอายุ 7 วัน)
            $table->text('summary')->nullable();

            // นับจำนวนเทิร์นสะสม (ใช้ดูสถิติ ไม่ได้ใช้ตัดสินอะไร)
            $table->unsignedInteger('turn_count')->default(0);

            // เวลาคุยล่าสุด — ตัวชี้ขาดอายุ 7 วัน และเป็นคีย์ให้คำสั่ง prune
            $table->timestamp('last_message_at')->nullable();

            $table->timestamps();

            // 1 สมาชิก = 1 แถว (ชื่อ index สั้นกว่า 50 ตัวอักษรตามกฎโปรเจกต์)
            $table->unique('user_id', 'eve_mem_user_unique');
            $table->index('last_message_at', 'eve_mem_last_msg_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eve_member_memories');
    }
};
