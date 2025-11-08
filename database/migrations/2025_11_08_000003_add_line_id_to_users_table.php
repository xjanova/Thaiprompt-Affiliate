<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * เพิ่มฟิลด์ LINE ID สำหรับแม่ทีม (เพื่อให้สมาชิกใหม่เพิ่มเพื่อนอัตโนมัติ)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('line_id')->nullable()->after('line_user_id'); // LINE ID สำหรับเพิ่มเพื่อน (@line_id)
            $table->string('line_official_url')->nullable()->after('line_id'); // URL ของ LINE Official Account
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['line_id', 'line_official_url']);
        });
    }
};
