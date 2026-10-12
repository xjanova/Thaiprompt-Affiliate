<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🖼️ ชุดตัวอย่างตอนปิดการขาย — จดว่าลูกค้าคนนี้ "เคยได้แล้ว" (owner: ต้องยังไม่เคยส่งมาก่อน)
     *
     * เหตุผลที่ไม่ใช้ cache: deploy.sh รัน cache:clear (flush redis ทั้ง DB) ทุกรอบ deploy
     * → ธงใน cache หายวันละหลายรอบ ลูกค้าคนเดิมได้ภาพตัวอย่างซ้ำ
     * unique(platform_user_id) = จองสิทธิ์แบบ atomic ด้วย insertOrIgnore (กดรัว/สองโปรเซสพร้อมกันได้คนเดียว)
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_sales_showcase_sends')) {
            return;
        }

        Schema::create('fortune_sales_showcase_sends', function (Blueprint $table) {
            $table->id();
            $table->string('platform_user_id', 191)->unique()->comment('PSID / LINE userId ที่ได้ชุดตัวอย่างแล้ว');
            $table->string('trigger', 32)->comment('menu = การ์ดเลือกแพคเกจ/ถามราคา · idle = ลูกค้านิ่งนาน · bill = เปิดบิลไม่โอน · cancel = กำลังจะยกเลิก');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_sales_showcase_sends');
    }
};
