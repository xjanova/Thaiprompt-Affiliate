<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('membership_retention_status', function (Blueprint $table) {
            // วันที่เริ่มเป็นสมาชิก (วันที่ซื้อครั้งแรก - ไม่รวมการเติม wallet)
            $table->date('membership_start_date')->nullable()->after('user_id');

            // เพิ่ม index เพื่อความเร็ว
            $table->index('membership_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_retention_status', function (Blueprint $table) {
            $table->dropIndex(['membership_start_date']);
            $table->dropColumn('membership_start_date');
        });
    }
};
