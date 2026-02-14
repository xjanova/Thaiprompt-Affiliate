<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์วันเดือนปีเกิดในตาราง fortune_readings
     * เพื่อให้ AI วิเคราะห์ดวงชะตาได้แม่นยำยิ่งขึ้น
     */
    public function up(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_readings', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('user_posts_context')
                    ->comment('วันเดือนปีเกิดผู้ถาม เพื่อทำนายตามราศี/ลัคนา');
            }
        });
    }

    /**
     * ลบคอลัมน์ birth_date
     */
    public function down(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_readings', 'birth_date')) {
                $table->dropColumn('birth_date');
            }
        });
    }
};
