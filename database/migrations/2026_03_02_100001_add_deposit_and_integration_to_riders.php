<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มระบบค่าประกัน, ประเภทไรเดอร์, และเชื่อมต่อ Fresh Market + Service Provider
     *
     * คอลัมน์ใหม่:
     * - deposit_amount, deposit_paid_at, deposit_status, deposit_transaction_id — ค่าประกันงานแรกเข้า
     * - rider_type — delivery | service | both
     * - service_provider_id — เชื่อมกับ service_providers (ไรเดอร์เซอร์วิส)
     * - line_user_id — LINE integration
     * - fresh_market_linked — เชื่อมกับตลาดสด
     * - service_categories — JSON หมวดหมู่งานช่าง
     */
    public function up(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            // ค่าประกันงานแรกเข้า
            if (!Schema::hasColumn('riders', 'deposit_amount')) {
                $table->decimal('deposit_amount', 10, 2)->default(0)->after('total_earnings');
            }
            if (!Schema::hasColumn('riders', 'deposit_paid_at')) {
                $table->timestamp('deposit_paid_at')->nullable()->after('deposit_amount');
            }
            if (!Schema::hasColumn('riders', 'deposit_status')) {
                $table->string('deposit_status', 20)->default('pending')->after('deposit_paid_at');
            }
            if (!Schema::hasColumn('riders', 'deposit_transaction_id')) {
                $table->string('deposit_transaction_id')->nullable()->after('deposit_status');
            }

            // ประเภทไรเดอร์
            if (!Schema::hasColumn('riders', 'rider_type')) {
                $table->string('rider_type', 20)->default('delivery')->after('status');
            }

            // เชื่อมกับ Service Provider (ไรเดอร์เซอร์วิส)
            if (!Schema::hasColumn('riders', 'service_provider_id')) {
                $table->unsignedBigInteger('service_provider_id')->nullable()->after('rider_type');
            }

            // LINE integration
            if (!Schema::hasColumn('riders', 'line_user_id')) {
                $table->string('line_user_id')->nullable()->after('user_id');
            }

            // เชื่อมกับตลาดสด
            if (!Schema::hasColumn('riders', 'fresh_market_linked')) {
                $table->boolean('fresh_market_linked')->default(false)->after('line_user_id');
            }

            // หมวดหมู่งานช่าง (สำหรับไรเดอร์เซอร์วิส)
            if (!Schema::hasColumn('riders', 'service_categories')) {
                $table->json('service_categories')->nullable()->after('rider_type');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $columns = [
                'deposit_amount', 'deposit_paid_at', 'deposit_status', 'deposit_transaction_id',
                'rider_type', 'service_provider_id', 'line_user_id', 'fresh_market_linked',
                'service_categories',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('riders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
