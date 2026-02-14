<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ avatar_frame สำหรับกรอบอวาต้าร์ตาม Rank
 *
 * @date 2025-11-26
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * เพิ่มคอลัมน์สำหรับจัดเก็บ URL กรอบอวาต้าร์
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง ranks มีอยู่แล้วหรือไม่
        if (! Schema::hasTable('ranks')) {
            return;
        }

        Schema::table('ranks', function (Blueprint $table) {
            // เพิ่มคอลัมน์ avatar_frame สำหรับเก็บ URL รูปกรอบ
            if (! Schema::hasColumn('ranks', 'avatar_frame')) {
                $table->string('avatar_frame')->nullable()->after('badge_icon')
                    ->comment('URL รูปกรอบอวาต้าร์สำหรับ Rank นี้');
            }

            // เพิ่มคอลัมน์ frame_animation สำหรับประเภท animation
            if (! Schema::hasColumn('ranks', 'frame_animation')) {
                $table->string('frame_animation')->nullable()->after('avatar_frame')
                    ->comment('ประเภท animation ของกรอบ: none, pulse, glow, sparkle, rotate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ranks', function (Blueprint $table) {
            if (Schema::hasColumn('ranks', 'avatar_frame')) {
                $table->dropColumn('avatar_frame');
            }
            if (Schema::hasColumn('ranks', 'frame_animation')) {
                $table->dropColumn('frame_animation');
            }
        });
    }
};
