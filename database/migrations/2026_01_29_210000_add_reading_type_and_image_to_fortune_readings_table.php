<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ reading_type และ image support ในตาราง fortune_readings
     *
     * reading_type: แยกประเภทคำทำนาย (basic/deep) เพื่อติดตาม freemium
     * reading_image_url: เก็บ URL รูปคำทำนายที่สร้างส่งให้ผู้ใช้
     * user_image_url: เก็บ URL รูปที่ผู้ใช้ส่งมาผ่าน Messenger
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            if (!Schema::hasColumn('fortune_readings', 'reading_type')) {
                $table->string('reading_type', 20)->default('basic')
                    ->after('response_type')
                    ->comment('ประเภทคำทำนาย: basic = พื้นฐาน, deep = เชิงลึก');
            }

            if (!Schema::hasColumn('fortune_readings', 'reading_image_url')) {
                $table->string('reading_image_url', 500)->nullable()
                    ->after('reading_type')
                    ->comment('URL รูปคำทำนายที่สร้างส่งให้ผู้ใช้');
            }

            if (!Schema::hasColumn('fortune_readings', 'user_image_url')) {
                $table->string('user_image_url', 500)->nullable()
                    ->after('reading_image_url')
                    ->comment('URL รูปที่ผู้ใช้ส่งมาผ่าน Messenger');
            }
        });

        // เพิ่ม index สำหรับ reading_type
        Schema::table('fortune_readings', function (Blueprint $table) {
            if (!Schema::hasColumn('fortune_readings', 'reading_type')) {
                return;
            }

            // ตรวจสอบว่ามี index อยู่แล้วหรือไม่ (ใช้ information_schema แทน deprecated Doctrine)
            if (!$this->indexExists('fortune_readings', 'fortune_read_type_idx')) {
                $table->index('reading_type', 'fortune_read_type_idx');
            }

            if (!$this->indexExists('fortune_readings', 'fortune_read_fb_type_date_idx')) {
                $table->index(['facebook_user_id', 'reading_type', 'created_at'], 'fortune_read_fb_type_date_idx');
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
        Schema::table('fortune_readings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_readings', 'user_image_url')) {
                $table->dropColumn('user_image_url');
            }
            if (Schema::hasColumn('fortune_readings', 'reading_image_url')) {
                $table->dropColumn('reading_image_url');
            }
            if (Schema::hasColumn('fortune_readings', 'reading_type')) {
                $table->dropColumn('reading_type');
            }
        });
    }

    /**
     * ตรวจสอบว่า index มีอยู่แล้วหรือไม่
     *
     * @param string $table
     * @param string $index
     * @return bool
     */
    protected function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = DB::select(
            "SELECT COUNT(*) as count
             FROM information_schema.statistics
             WHERE table_schema = ?
             AND table_name = ?
             AND index_name = ?",
            [$database, $table, $index]
        );

        return $result[0]->count > 0;
    }
};
