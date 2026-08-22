<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_product_offers — ประวัติการเสนอสินค้าของบอทแม่หมอ
     *
     * ใช้ตอบ 3 คำถามที่ระบบต้องรู้ก่อนยิงทุกครั้ง:
     *   1. วันนี้ยิงให้คนนี้ไปแล้วหรือยัง (เพดาน 1 ครั้ง/วัน สำหรับการเสนอเอง)
     *   2. เคยส่งชิ้นนี้ให้คนนี้แล้วหรือยัง (กันส่งซ้ำชิ้นเดิม 30 วัน)
     *   3. ของชิ้นไหนคนกดเยอะ (ไว้จัดอันดับ + ดูว่าคุ้มค่าไหม)
     *
     * 🚨 ทำไมต้องอยู่ DB ไม่ใช่ Cache
     *   `deploy.sh` รัน `cache:clear` ทุกครั้ง และ cache:clear คือ **flushdb ทั้ง DB**
     *   ไม่ใช่ลบตาม prefix ⇒ ถ้าเก็บบน Cache ทุกครั้งที่เราพุชโค้ด
     *   ลูกค้าทุกคนจะถูกรีเซ็ตเพดานรายวัน แล้วโดนยิงซ้ำในวันเดียวกัน
     *   (บทเรียนเดียวกับ fortune_nav_flood_strikes และ fb_last_inbound)
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_product_offers')) {
            return;
        }

        Schema::create('fortune_product_offers', function (Blueprint $table) {
            $table->id();

            $table->string('platform', 20)->comment('facebook | line');
            $table->string('platform_user_id', 191)->comment('FB PSID / LINE userId');

            $table->unsignedBigInteger('marketplace_product_id')->comment('สินค้าที่เสนอ (marketplace_products.id)');
            $table->unsignedBigInteger('reading_id')->nullable()->comment('ผูกกับการดูดวงใบไหน (null = เสนอนอกรอบดูดวง)');

            $table->string('trigger', 32)->comment('celtic_end | deep_end | chat_end | pitch_declined | customer_ask');
            $table->string('mu_group', 32)->nullable()->comment('กลุ่มสายมูที่เลือกมาเสนอ');
            $table->string('slot', 8)->nullable()->comment('low = ตัวเลือกราคาต่ำ | high = ตัวเลือกราคาสูง');
            $table->string('context_reason', 100)->nullable()->comment('ทำไมถึงเลือกชิ้นนี้ — ปีชง/ปีเกิด/ไพ่/สุ่ม (ไว้ debug)');

            $table->decimal('price_at_send', 12, 2)->nullable()->comment('ราคา ณ ตอนส่ง (ราคาเปลี่ยนได้ทีหลัง)');
            $table->decimal('commission_rate_at_send', 5, 2)->nullable()->comment('ค่าคอม %% ณ ตอนส่ง');

            $table->timestamp('sent_at')->comment('ส่งถึงลูกค้าเมื่อไหร่');
            $table->timestamp('clicked_at')->nullable()->comment('ลูกค้ากดลิงก์เมื่อไหร่ (ถ้าติดตามได้)');

            $table->timestamps();

            // คำถาม 1 + 2: "คนนี้ได้อะไรไปแล้วบ้าง เมื่อไหร่"
            $table->index(['platform', 'platform_user_id', 'sent_at'], 'fpo_user_sent_idx');
            $table->index(['platform', 'platform_user_id', 'marketplace_product_id'], 'fpo_user_product_idx');
            // คำถาม 3: "ชิ้นไหนคนกดเยอะ"
            $table->index('marketplace_product_id', 'fpo_product_idx');
            $table->index('reading_id', 'fpo_reading_idx');
        });
    }

    /**
     * ลบตาราง fortune_product_offers
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_product_offers');
    }
};
