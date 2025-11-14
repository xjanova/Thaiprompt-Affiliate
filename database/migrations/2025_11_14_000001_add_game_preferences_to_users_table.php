<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์ game_preferences สำหรับเก็บการตั้งค่าเกมของผู้ใช้
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ ตรวจสอบว่ามีฟิลด์อยู่แล้วหรือไม่
        if (!Schema::hasColumn('users', 'game_preferences')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('game_preferences')->nullable()->after('theme');
            });
        }
    }

    /**
     * ลบฟิลด์ game_preferences
     *
     * @return void
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'game_preferences')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('game_preferences');
            });
        }
    }
};
