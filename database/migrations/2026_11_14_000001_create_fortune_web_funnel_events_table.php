<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_web_funnel_events — ตัวนับเส้นทางลูกค้า FB → ห้องแม่หมอบนเว็บจันทรา
     *
     * แถวละ 1 เหตุการณ์ (ได้กล่องลิงก์ / แตะลิงก์ / ลิงก์ใช้ไม่ได้ / คุยกับแม่หมอ / ซื้อคำทำนาย)
     * เก็บแค่รหัสคน ไม่เก็บข้อความ — อยู่ใน PDPA deletion · ลบเองเมื่อเก่ากว่า 400 วัน
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_web_funnel_events')) {
            return;
        }

        Schema::create('fortune_web_funnel_events', function (Blueprint $table) {
            $table->id();
            $table->string('event', 20);
            // PSID ของลูกค้าในแชทเพจ (ขั้นที่ยังอยู่ฝั่ง FB)
            $table->string('platform_user_id', 64)->nullable();
            // users.id ฝั่ง Thaiprompt (ขั้นที่ล็อกอินแล้ว)
            $table->unsignedBigInteger('thaiprompt_user_id')->nullable();
            // users.id ฝั่งเว็บจันทรา (ขั้นที่เกิดบนเว็บ)
            $table->string('juntra_user_ref', 64)->nullable();
            $table->unsignedBigInteger('fortune_page_id')->nullable();
            $table->string('source', 60)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['event', 'created_at'], 'fwfe_event_time_idx');
            $table->index('platform_user_id', 'fwfe_psid_idx');
            $table->index('thaiprompt_user_id', 'fwfe_tp_user_idx');
        });
    }

    /**
     * ลบตาราง fortune_web_funnel_events
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_web_funnel_events');
    }
};
