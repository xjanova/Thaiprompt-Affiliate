<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตารางระบบ Membership Retention ทั้งหมดที่หายไป
 *
 * Migration นี้แก้ไขปัญหาที่ตารางหายไปในบาง environment
 * โดยจะสร้างตารางใหม่เฉพาะกรณีที่ยังไม่มีตารางอยู่
 *
 * ตารางที่ครอบคลุม:
 * - membership_retention_status
 * - membership_retention_history
 * - membership_retention_transactions
 * - membership_retention_repairs
 * - membership_retention_advance_renewals
 * - membership_retention_settings
 *
 * @see App\Services\MembershipRetentionService
 */
return new class extends Migration
{
    /**
     * สร้างตารางระบบ Membership Retention ที่หายไปทั้งหมด
     *
     * @return void
     */
    public function up(): void
    {
        // 1. ตาราง membership_retention_status - เก็บสถานะการรักษายอดของสมาชิก
        if (!Schema::hasTable('membership_retention_status')) {
            Schema::create('membership_retention_status', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->onDelete('cascade');
                $table->enum('status', ['active', 'inactive', 'expired'])->default('active');
                $table->decimal('current_points', 10, 2)->default(0);
                $table->decimal('required_points', 10, 2)->default(0);
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->date('next_renewal_date')->nullable();
                $table->integer('consecutive_months')->default(0);
                $table->date('last_active_date')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status'], 'mrs_user_status_idx');
                $table->index('next_renewal_date', 'mrs_renewal_idx');
            });
        }

        // 2. ตาราง membership_retention_history - ประวัติการรักษายอดแต่ละเดือน
        if (!Schema::hasTable('membership_retention_history')) {
            Schema::create('membership_retention_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->onDelete('cascade');
                $table->string('period_month', 7);
                $table->decimal('required_points', 10, 2);
                $table->decimal('earned_points', 10, 2)->default(0);
                $table->enum('status', ['pending', 'achieved', 'failed'])->default('pending');
                $table->date('period_start');
                $table->date('period_end');
                $table->date('achieved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'period_month'], 'mrh_user_period_idx');
                $table->index('status', 'mrh_status_idx');
            });
        }

        // 3. ตาราง membership_retention_transactions - รายการที่นับเข้าระบบรักษายอด
        if (!Schema::hasTable('membership_retention_transactions')) {
            Schema::create('membership_retention_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->onDelete('cascade');
                $table->unsignedBigInteger('commission_id')->nullable();
                $table->foreign('commission_id')
                    ->references('id')
                    ->on('mlm_commissions')
                    ->onDelete('set null');
                $table->string('transaction_type');
                $table->decimal('points', 10, 2);
                $table->string('period_month', 7);
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'period_month'], 'mrt_user_period_idx');
                $table->index('transaction_type', 'mrt_type_idx');
            });
        }

        // 4. ตาราง membership_retention_repairs - การซื้อซ่อมสิทธิ์
        if (!Schema::hasTable('membership_retention_repairs')) {
            Schema::create('membership_retention_repairs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->onDelete('cascade');
                $table->string('repair_period_month', 7);
                $table->decimal('points_needed', 10, 2);
                $table->decimal('repair_cost', 10, 2);
                $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
                $table->date('repaired_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'repair_period_month'], 'mrr_user_period_idx');
                $table->index('payment_status', 'mrr_payment_idx');
            });
        }

        // 5. ตาราง membership_retention_advance_renewals - การเติมวันล่วงหน้า
        if (!Schema::hasTable('membership_retention_advance_renewals')) {
            Schema::create('membership_retention_advance_renewals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->onDelete('cascade');
                $table->integer('months_advance')->default(1);
                $table->decimal('total_cost', 10, 2);
                $table->date('valid_from');
                $table->date('valid_until');
                $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
                $table->date('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'valid_from', 'valid_until'], 'mrar_user_validity_idx');
                $table->index('payment_status', 'mrar_payment_idx');
            });
        }

        // 6. ตาราง membership_retention_settings - การตั้งค่าระบบ
        if (!Schema::hasTable('membership_retention_settings')) {
            Schema::create('membership_retention_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string');
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
     * ลบตารางระบบ Membership Retention
     *
     * @return void
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
