<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * แคมเปญคอนเทนต์อัตโนมัติ — generalize จากระบบสายมูเดิม
     *
     * 1 row = 1 แคมเปญอิสระ มีตารางเวลา/แนวคอนเทนต์/pool ของตัวเอง
     * ทุกแคมเปญใช้ pipeline เดียวกัน (AI caption + AI image + FB Page เดิม)
     *
     * topic_source:
     * - 'inline'        = ใช้ pool ในแคมเปญเอง (แนวใหม่ เช่น กำลังใจ/กฎแห่งกรรม/จิตวิทยา)
     * - 'mystic_topics' = bridge ไปใช้คลังหัวข้อสายมูเดิม (fortune_mystic_topics, LRU rotation)
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง (ใช้ได้เฉพาะ CREATE TABLE)
        if (Schema::hasTable('fortune_content_campaigns')) {
            return;
        }

        Schema::create('fortune_content_campaigns', function (Blueprint $table) {
            $table->id();

            // ── ข้อมูลหลัก
            $table->string('slug', 100)->unique()
                ->comment('slug ประจำแคมเปญ เช่น saimoo, kamlangjai');
            $table->string('name_th', 200)
                ->comment('ชื่อแคมเปญภาษาไทย เช่น "กำลังใจ+ความเชื่อ"');
            $table->text('description_th')->nullable()
                ->comment('คำอธิบายแนวคอนเทนต์ — AI ใช้เป็นทิศทางการเขียนด้วย');
            $table->string('emoji', 10)->nullable();
            $table->boolean('is_enabled')->default(false)
                ->comment('เปิด/ปิดรายแคมเปญ — อิสระจากกัน');

            // ── ตารางเวลาโพส (อิสระต่อแคมเปญ)
            $table->json('schedule')->nullable()
                ->comment('array เวลาโพส เช่น ["07:30","19:00"] (Asia/Bangkok)');

            // ── แหล่งหัวข้อ
            $table->enum('topic_source', ['inline', 'mystic_topics'])->default('inline')
                ->comment('inline = pool ในแคมเปญ | mystic_topics = คลังสายมูเดิม (LRU)');

            // ── แนวคอนเทนต์ (สำหรับ topic_source=inline)
            $table->json('keywords')->nullable()
                ->comment('คีย์เวิร์ดแนวคอนเทนต์ — ใช้สร้าง prompt อัตโนมัติ + fallback image');
            $table->text('content_prompt')->nullable()
                ->comment('custom prompt เขียนเอง (override) — เว้นว่าง = ระบบสร้างจาก keywords/description');
            $table->string('persona', 200)->nullable()
                ->comment('บุคลิกผู้เขียน เช่น "แม่หมอจันทรา" — เว้นว่าง = ใช้ brand name จาก settings');
            $table->json('sub_topic_pool')->nullable()
                ->comment('pool หัวข้อย่อยสุ่ม — เว้นว่าง = AI เลือกแง่มุมเองจากแนว');
            $table->json('hashtag_pool')->nullable()
                ->comment('pool hashtag หมุนใช้');
            $table->json('search_queries')->nullable()
                ->comment('คำค้น web search — เว้นว่าง = สร้างจาก sub_topic + keywords');

            // ── ตัวเลือก pipeline
            $table->boolean('use_web_search')->default(true)
                ->comment('เปิด web search หาแหล่งอ้างอิงก่อนเขียน');
            $table->unsignedSmallInteger('caption_min')->nullable()
                ->comment('ความยาว caption ต่ำสุด — null = ใช้ค่า global');
            $table->unsignedSmallInteger('caption_max')->nullable()
                ->comment('ความยาว caption สูงสุด — null = ใช้ค่า global');
            $table->unsignedTinyInteger('hashtag_count')->nullable()
                ->comment('จำนวน hashtag — null = ใช้ค่า global');
            $table->string('image_style_hint', 500)->nullable()
                ->comment('สไตล์ภาพเสริม เช่น "warm cinematic" — ต่อท้าย image prompt ที่ AI สร้างจากเนื้อหา');

            // ── สถิติ
            $table->unsignedInteger('post_count')->default(0)
                ->comment('จำนวนโพสสะสมของแคมเปญ');
            $table->timestamp('last_posted_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            // ── Indexes
            $table->index('is_enabled', 'fcc_enabled_idx');
            $table->index('sort_order', 'fcc_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_content_campaigns');
    }
};
