<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ที่ขาดหายไปในตาราง payout_settings
 *
 * คอลัมน์ที่เพิ่ม:
 * - slug: ชื่อย่อสำหรับอ้างอิง
 * - name: ชื่อแสดงผล
 * - earning_type: ประเภทรายได้
 * - payout_mode: โหมดการจ่าย (ใช้แทน mode เดิม)
 * - min_payout: ยอดขั้นต่ำ
 * - max_payout: ยอดสูงสุด
 * - fee_fixed: ค่าธรรมเนียมคงที่
 * - fee_percentage: ค่าธรรมเนียมเป็น %
 * - requires_approval: ต้องอนุมัติหรือไม่
 * - schedule: ตารางเวลาการจ่าย (JSON)
 *
 * ใช้สำหรับ PlatformRevenueSeeder
 */
return new class extends Migration
{
    /**
     * รัน migrations
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('payout_settings', function (Blueprint $table) {
            // เพิ่มคอลัมน์ใหม่ที่ seeder ต้องการ
            if (!Schema::hasColumn('payout_settings', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('id');
            }

            if (!Schema::hasColumn('payout_settings', 'name')) {
                $table->string('name')->nullable()->after('slug');
            }

            if (!Schema::hasColumn('payout_settings', 'earning_type')) {
                $table->string('earning_type')->nullable()->after('name');
            }

            if (!Schema::hasColumn('payout_settings', 'payout_mode')) {
                $table->string('payout_mode')->default('manual')->after('earning_type');
            }

            if (!Schema::hasColumn('payout_settings', 'min_payout')) {
                $table->decimal('min_payout', 15, 2)->default(100)->after('payout_mode');
            }

            if (!Schema::hasColumn('payout_settings', 'max_payout')) {
                $table->decimal('max_payout', 15, 2)->nullable()->after('min_payout');
            }

            if (!Schema::hasColumn('payout_settings', 'fee_fixed')) {
                $table->decimal('fee_fixed', 15, 2)->default(0)->after('max_payout');
            }

            if (!Schema::hasColumn('payout_settings', 'fee_percentage')) {
                $table->decimal('fee_percentage', 5, 2)->default(0)->after('fee_fixed');
            }

            if (!Schema::hasColumn('payout_settings', 'requires_approval')) {
                $table->boolean('requires_approval')->default(true)->after('fee_percentage');
            }

            if (!Schema::hasColumn('payout_settings', 'schedule')) {
                $table->json('schedule')->nullable()->after('requires_approval');
            }
        });
    }

    /**
     * ย้อนกลับ migrations
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('payout_settings', function (Blueprint $table) {
            $columns = ['slug', 'name', 'earning_type', 'payout_mode', 'min_payout', 'max_payout', 'fee_fixed', 'fee_percentage', 'requires_approval', 'schedule'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('payout_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
