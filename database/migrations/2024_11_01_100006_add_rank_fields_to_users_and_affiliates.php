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
        // ตรวจสอบว่าตาราง affiliates มีอยู่แล้วหรือไม่
        if (! Schema::hasTable('affiliates')) {
            // ตาราง affiliates ยังไม่มี - ข้าม migration นี้
            // columns จะถูกเพิ่มตอนสร้างตาราง affiliates
            return;
        }

        // ตรวจสอบว่ามี column อยู่แล้วหรือไม่ (เพื่อป้องกัน error)
        if (! Schema::hasColumn('affiliates', 'rank_id')) {
            Schema::table('affiliates', function (Blueprint $table) {
                // ใช้ unsignedBigInteger แทน foreignId()->constrained() เพื่อความปลอดภัย
                $table->unsignedBigInteger('rank_id')->nullable()->after('status');
                $table->integer('rank_points')->default(0)->after('rank_id');
                $table->decimal('monthly_sales', 12, 2)->default(0)->after('rank_points');
                $table->decimal('team_sales', 12, 2)->default(0)->after('monthly_sales');
            });

            // เพิ่ม foreign key ถ้าตาราง ranks มีอยู่
            if (Schema::hasTable('ranks')) {
                Schema::table('affiliates', function (Blueprint $table) {
                    $table->foreign('rank_id')->references('id')->on('ranks')->onDelete('set null');
                });
            }
        }
    }

    /**
     * ย้อนกลับการ migration
     */
    public function down(): void
    {
        if (Schema::hasTable('affiliates') && Schema::hasColumn('affiliates', 'rank_id')) {
            Schema::table('affiliates', function (Blueprint $table) {
                $table->dropForeign(['rank_id']);
                $table->dropColumn(['rank_id', 'rank_points', 'monthly_sales', 'team_sales']);
            });
        }
    }
};
