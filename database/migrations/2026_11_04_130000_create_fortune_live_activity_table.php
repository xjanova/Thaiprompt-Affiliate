<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🔴 (2026-11-04) ตัวนับ "กำลังสนทนากับแม่หมอตอนนี้" — 1 แถวต่อลูกค้า 1 คนต่อช่องทาง
     *
     * เจ้าของสั่ง: ป้ายหน้าห้องของเว็บจันทรา ต้องเป็นเลขจริงของคนที่ทัก DM อยู่ทุกช่องทาง
     * (Facebook / LINE / Telegram / แชทเว็บ) — ไม่มีตารางไหนเก็บ "ทักล่าสุดเมื่อไร" ครบทุกช่องทาง
     * (fortune_contact_signals มีแค่ FB และหลุดบางข้อความ · chat log อยู่ใน Redis ที่อาจล่ม)
     * แถวถูกอัปเดตเวลาเท่านั้น (upsert) ตารางจึงโตตามจำนวนลูกค้า ไม่ใช่จำนวนข้อความ
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_live_activity')) {
            return;
        }

        Schema::create('fortune_live_activity', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 16);
            $table->string('user_key', 100);
            $table->timestamp('last_seen_at')->index();
            $table->unique(['platform', 'user_key'], 'fortune_live_activity_user_uq');
        });
    }

    /**
     * ลบตาราง fortune_live_activity
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_live_activity');
    }
};
