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
        // ตาราง membership_retention_status - เก็บสถานะการรักษายอดของสมาชิก
        if (!Schema::hasTable('membership_retention_status')) {
            Schema::create('membership_retention_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active');
            $table->decimal('current_points', 10, 2)->default(0); // แต้มปัจจุบันในเดือนนี้
            $table->decimal('required_points', 10, 2)->default(0); // แต้มที่ต้องรักษา
            $table->date('period_start')->nullable(); // วันเริ่มต้นรอบ
            $table->date('period_end')->nullable(); // วันสิ้นสุดรอบ
            $table->date('next_renewal_date')->nullable(); // วันที่ต้องต่ออายุครั้งต่อไป
            $table->integer('consecutive_months')->default(0); // จำนวนเดือนติดต่อกันที่รักษายอดได้
            $table->date('last_active_date')->nullable(); // วันที่แอคทีฟล่าสุด
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('next_renewal_date');
            });
        }

        // ตาราง membership_retention_history - ประวัติการรักษายอดแต่ละเดือน
        if (!Schema::hasTable('membership_retention_history')) {
            Schema::create('membership_retention_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('period_month', 7); // YYYY-MM
            $table->decimal('required_points', 10, 2);
            $table->decimal('earned_points', 10, 2)->default(0);
            $table->enum('status', ['pending', 'achieved', 'failed'])->default('pending');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('achieved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'period_month']);
            $table->index('status');
            });
        }

        // ตาราง membership_retention_transactions - รายการซื้อขายที่นับเข้าระบบรักษายอด
        if (!Schema::hasTable('membership_retention_transactions')) {
            Schema::create('membership_retention_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('commission_id')->nullable()->constrained()->onDelete('set null');
            $table->string('transaction_type'); // 'purchase', 'commission', 'repair', 'advance_renewal'
            $table->decimal('points', 10, 2);
            $table->string('period_month', 7); // YYYY-MM
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'period_month']);
            $table->index('transaction_type');
            });
        }

        // ตาราง membership_retention_repairs - การซื้อซ่อมสิทธิ์
        if (!Schema::hasTable('membership_retention_repairs')) {
            Schema::create('membership_retention_repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('repair_period_month', 7); // เดือนที่ซ่อม YYYY-MM
            $table->decimal('points_needed', 10, 2); // แต้มที่ต้องซ่อม
            $table->decimal('repair_cost', 10, 2); // ค่าใช้จ่ายในการซ่อม
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->date('repaired_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'repair_period_month']);
            $table->index('payment_status');
            });
        }

        // ตาราง membership_retention_advance_renewals - การเติมวันล่วงหน้า
        if (!Schema::hasTable('membership_retention_advance_renewals')) {
            Schema::create('membership_retention_advance_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('months_advance')->default(1); // จำนวนเดือนที่เติมล่วงหน้า
            $table->decimal('total_cost', 10, 2); // ค่าใช้จ่ายทั้งหมด
            $table->date('valid_from'); // เริ่มต้นที่
            $table->date('valid_until'); // สิ้นสุดที่
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'valid_from', 'valid_until']);
            $table->index('payment_status');
            });
        }

        // ตาราง membership_retention_settings - การตั้งค่าระบบ
        if (!Schema::hasTable('membership_retention_settings')) {
            Schema::create('membership_retention_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, number, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
            });

            // Insert default settings
            DB::table('membership_retention_settings')->insert([
            [
                'key' => 'minimum_points_per_month',
                'value' => '1000',
                'type' => 'number',
                'description' => 'จำนวนแต้มขั้นต่ำที่ต้องรักษาต่อเดือน',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'repair_cost_per_point',
                'value' => '1.5',
                'type' => 'number',
                'description' => 'ค่าใช้จ่ายในการซ่อมต่อแต้ม (เท่าของราคาปกติ)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'advance_renewal_discount',
                'value' => '0.9',
                'type' => 'number',
                'description' => 'ส่วนลดสำหรับการเติมล่วงหน้า (0.9 = ลด 10%)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'grace_period_days',
                'value' => '3',
                'type' => 'number',
                'description' => 'ระยะเวลาผ่อนผัน (วัน) หลังหมดเขตก่อนจะถูก expire',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'warning_days_before_expiry',
                'value' => '7',
                'type' => 'number',
                'description' => 'จำนวนวันที่จะแจ้งเตือนก่อนหมดอายุ',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_retention_system',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'เปิด/ปิด ระบบรักษายอด',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'block_commission_if_expired',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'บล็อกการคำนวณคอมมิชชั่นหากไม่รักษายอด',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_retention_advance_renewals');
        Schema::dropIfExists('membership_retention_repairs');
        Schema::dropIfExists('membership_retention_transactions');
        Schema::dropIfExists('membership_retention_history');
        Schema::dropIfExists('membership_retention_status');
        Schema::dropIfExists('membership_retention_settings');
    }
};
