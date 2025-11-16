<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์ rank ให้กับตาราง affiliates
     *
     * หมายเหตุ: ส่วนของ users ถูกย้ายไปที่ create_users_comprehensive_v3 แล้ว
     */
    public function up(): void
    {
        // ตรวจสอบว่ามี column อยู่แล้วหรือไม่ (เพื่อป้องกัน error)
        if (!Schema::hasColumn('affiliates', 'rank_id')) {
            Schema::table('affiliates', function (Blueprint $table) {
                $table->foreignId('rank_id')->nullable()->after('status')
                    ->constrained('ranks')->onDelete('set null');
                $table->integer('rank_points')->default(0)->after('rank_id');
                $table->decimal('monthly_sales', 12, 2)->default(0)->after('rank_points');
                $table->decimal('team_sales', 12, 2)->default(0)->after('monthly_sales');
            });
        }
    }

    /**
     * ย้อนกลับการ migration
     */
    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            if (Schema::hasColumn('affiliates', 'rank_id')) {
                $table->dropForeign(['rank_id']);
                $table->dropColumn(['rank_id', 'rank_points', 'monthly_sales', 'team_sales']);
            }
        });
    }
};
