<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ที่ขาดหายไปใน games table
     *
     * ⚠️ แก้ปัญหา: มี 2 migrations ที่สร้างตาราง games ด้วย schema ต่างกัน
     * - 2025_11_13_000001: สร้างด้วย slug, name (ไม่มี deleted_at)
     * - 2025_11_13_121744: สร้างด้วย title, title_en (มี deleted_at) แต่มี hasTable check
     *
     * Migration นี้จะเพิ่มคอลัมน์ที่ขาดหายไปจากทั้งสอง migrations
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // คอลัมน์จาก migration 121744 ที่อาจขาดหายไป
            if (!Schema::hasColumn('games', 'title')) {
                $table->string('title')->nullable()->after('name');
            }
            if (!Schema::hasColumn('games', 'title_en')) {
                $table->string('title_en')->nullable()->after('title');
            }
            if (!Schema::hasColumn('games', 'description_en')) {
                $table->text('description_en')->nullable()->after('description');
            }
            if (!Schema::hasColumn('games', 'icon')) {
                $table->string('icon')->default('🎮')->after('description_en');
            }
            if (!Schema::hasColumn('games', 'image')) {
                $table->string('image')->nullable()->after('icon');
            }
            if (!Schema::hasColumn('games', 'url')) {
                $table->string('url')->nullable()->after('image');
            }
            if (!Schema::hasColumn('games', 'primary_color')) {
                $table->string('primary_color')->default('#00ffff')->after('url');
            }
            if (!Schema::hasColumn('games', 'secondary_color')) {
                $table->string('secondary_color')->default('#0080ff')->after('primary_color');
            }
            if (!Schema::hasColumn('games', 'glow_color')) {
                $table->string('glow_color')->default('rgba(0, 255, 255, 0.8)')->after('secondary_color');
            }
            if (!Schema::hasColumn('games', 'order')) {
                $table->integer('order')->default(0)->after('glow_color');
            }
            if (!Schema::hasColumn('games', 'card_style')) {
                $table->string('card_style')->default('default')->after('is_active');
            }
            if (!Schema::hasColumn('games', 'meta_data')) {
                $table->json('meta_data')->nullable()->after('settings');
            }

            // ⚠️ SoftDeletes - คอลัมน์ที่ Model ต้องการ
            if (!Schema::hasColumn('games', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $columnsToCheck = [
                'title', 'title_en', 'description_en', 'icon', 'image',
                'url', 'primary_color', 'secondary_color', 'glow_color',
                'order', 'card_style', 'meta_data', 'deleted_at'
            ];

            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('games', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
