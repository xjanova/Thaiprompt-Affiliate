<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม times_matched column ให้ line_bot_keywords table
     *
     * เพื่อบันทึกจำนวนครั้งที่ keyword ถูกใช้
     */
    public function up(): void
    {
        if (Schema::hasColumn('line_bot_keywords', 'times_matched')) {
            return;
        }

        Schema::table('line_bot_keywords', function (Blueprint $table) {
            $table->unsignedBigInteger('times_matched')->default(0)->after('is_active');
            $table->timestamp('last_matched_at')->nullable()->after('times_matched');
            $table->index('times_matched');
        });
    }

    /**
     * ลบ column
     */
    public function down(): void
    {
        Schema::table('line_bot_keywords', function (Blueprint $table) {
            if (Schema::hasColumn('line_bot_keywords', 'times_matched')) {
                $table->dropColumn('times_matched');
            }
            if (Schema::hasColumn('line_bot_keywords', 'last_matched_at')) {
                $table->dropColumn('last_matched_at');
            }
        });
    }
};
