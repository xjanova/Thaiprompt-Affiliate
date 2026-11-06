<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🔐 (2026-11-03) สร้างตาราง fortune_web_handoffs — FB web-only
     *
     * หนึ่งแถว = การส่งต่อลูกค้าหนึ่งครั้งจากแชท FB ไปห้องแม่หมอบนเว็บจันทรา
     * เก็บ "เรื่องที่ลูกค้าอยากถาม" (intent) ไว้ให้แม่หมอบนเว็บคุยต่อได้ทันที
     * nonce (uuid) ฝังใน token v2 (h) และใน path /chat/continue/{nonce}
     * ใช้ครั้งเดียว (consumed_at) · prune แถวที่เกิน 7 วัน · อยู่ใน PDPA deletion
     */
    public function up(): void
    {
        // ✅ CREATE TABLE — เช็คตารางก่อนสร้าง
        if (Schema::hasTable('fortune_web_handoffs')) {
            return;
        }

        Schema::create('fortune_web_handoffs', function (Blueprint $table) {
            $table->id();
            // nonce สาธารณะ (อยู่ในลิงก์) — ห้ามใช้ id ตัวเลขในลิงก์
            $table->uuid('nonce')->unique();
            // แพลตฟอร์มต้นทาง (ตอนนี้ใช้แค่ facebook)
            $table->string('platform', 20)->default('facebook');
            // PSID / platform user id ของลูกค้า
            $table->string('platform_user_id', 100)->index();
            // สาขา (fortune_pages.id) ที่ลูกค้าคุยอยู่ — ไม่ผูก FK เพื่อไม่ให้ลบเพจแล้วพัง
            $table->unsignedBigInteger('fortune_page_id')->nullable()->index();
            // users.id ฝั่ง Thaiprompt — gateway เติมให้ตอนลูกค้ากดลิงก์
            $table->unsignedBigInteger('thaiprompt_user_id')->nullable()->index();
            // ข้อความที่ทำให้เกิดการย้าย (ผ่าน sanitize แล้ว ≤300 ตัวอักษร)
            $table->string('intent_text', 300)->nullable();
            // หัวข้อ: love|career|money|health|kunsai|general
            $table->string('intent_topic', 20)->nullable();
            // จุดที่สร้าง เช่น web_only_gate, web_room_button
            $table->string('source', 50)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            // juntra user ที่ใช้ handoff นี้
            $table->string('consumed_juntra_user_id', 100)->nullable();
            $table->timestamps();

            // ค้น "แถวล่าสุดที่ยังไม่ consume" ต่อลูกค้า
            $table->index(['thaiprompt_user_id', 'consumed_at'], 'fwh_tp_user_consumed_idx');
            $table->index('created_at', 'fwh_created_at_idx');
        });
    }

    /**
     * ลบตาราง fortune_web_handoffs
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_web_handoffs');
    }
};
