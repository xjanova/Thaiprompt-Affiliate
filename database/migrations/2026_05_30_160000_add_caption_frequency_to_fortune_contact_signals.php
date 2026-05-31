<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์สำหรับตรวจจับ 2 รูปแบบเพิ่มเติม:
     *  - link_caption_count : ลิงก์ภายนอก + คำประกบสั้นๆ (เช่น "ดูเลย🔥 + ลิงก์")
     *  - active_days        : จำนวนวันที่ส่งสแปมต่างกัน (ความถี่ — ส่งหลายวันติด)
     *  - last_spam_day      : วันล่าสุดที่ส่งสแปม (ใช้คำนวณ active_days)
     *
     * ⚠️ ALTER TABLE — ใช้ hasColumn เช็คทีละคอลัมน์ (ห้าม hasTable + return)
     */
    public function up(): void
    {
        Schema::table('fortune_contact_signals', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_contact_signals', 'link_caption_count')) {
                $table->unsignedInteger('link_caption_count')->default(0)->after('link_image_count');
            }
            if (! Schema::hasColumn('fortune_contact_signals', 'active_days')) {
                $table->unsignedSmallInteger('active_days')->default(0)->after('interaction_count');
            }
            if (! Schema::hasColumn('fortune_contact_signals', 'last_spam_day')) {
                $table->date('last_spam_day')->nullable()->after('last_seen_at');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fortune_contact_signals', function (Blueprint $table) {
            foreach (['link_caption_count', 'active_days', 'last_spam_day'] as $col) {
                if (Schema::hasColumn('fortune_contact_signals', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
