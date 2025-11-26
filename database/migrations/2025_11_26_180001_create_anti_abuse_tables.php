<?php

/**
 * Migration สร้างตารางสำหรับระบบป้องกันการกลั่นแกล้ง (Anti-Abuse Protection)
 *
 * ตารางที่สร้าง:
 * 1. service_booking_location_logs - บันทึกประวัติตำแหน่ง GPS
 * 2. service_booking_penalties - บันทึกค่าปรับ/บทลงโทษ
 * 3. user_trust_scores - คะแนนความน่าเชื่อถือ
 * 4. user_blocks - ระบบ Block/Blacklist
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // 1. ตาราง Location Logs - บันทึกประวัติตำแหน่ง GPS
        if (!Schema::hasTable('service_booking_location_logs')) {
            Schema::create('service_booking_location_logs', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('service_booking_id');
                $table->foreign('service_booking_id')
                    ->references('id')
                    ->on('service_bookings')
                    ->onDelete('cascade');

                $table->unsignedBigInteger('user_id')->nullable();
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');

                $table->unsignedBigInteger('provider_id')->nullable();
                $table->foreign('provider_id')
                    ->references('id')
                    ->on('service_providers')
                    ->onDelete('set null');

                // ประเภท: user หรือ provider
                $table->enum('actor_type', ['user', 'provider']);

                // พิกัด GPS
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->decimal('accuracy', 8, 2)->nullable()->comment('ความแม่นยำเป็นเมตร');
                $table->decimal('speed', 8, 2)->nullable()->comment('ความเร็ว km/h');
                $table->decimal('heading', 5, 2)->nullable()->comment('ทิศทาง 0-360');
                $table->decimal('altitude', 10, 2)->nullable()->comment('ความสูงจากระดับน้ำทะเล');

                // แหล่งที่มาของตำแหน่ง
                $table->enum('source', ['gps', 'network', 'passive', 'fused', 'manual'])->default('gps');

                // สถานะแบตเตอรี่
                $table->tinyInteger('battery_level')->nullable()->comment('0-100%');
                $table->boolean('is_charging')->nullable();

                // App state
                $table->enum('app_state', ['foreground', 'background', 'terminated'])->default('foreground');

                $table->timestamp('recorded_at')->useCurrent();
                $table->timestamps();

                // Indexes
                $table->index(['service_booking_id', 'actor_type', 'recorded_at'], 'sb_loc_logs_booking_actor_time_idx');
                $table->index(['recorded_at'], 'sb_loc_logs_recorded_at_idx');
            });
        }

        // 2. ตาราง User Trust Scores - คะแนนความน่าเชื่อถือ
        if (!Schema::hasTable('user_trust_scores')) {
            Schema::create('user_trust_scores', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('user_id')->nullable();
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->unsignedBigInteger('provider_id')->nullable();
                $table->foreign('provider_id')
                    ->references('id')
                    ->on('service_providers')
                    ->onDelete('cascade');

                $table->enum('entity_type', ['user', 'provider']);

                // คะแนนหลัก (0-100)
                $table->decimal('trust_score', 5, 2)->default(80.00);

                // คะแนนย่อย
                $table->decimal('payment_score', 5, 2)->default(100.00)->comment('ความน่าเชื่อถือด้านการชำระเงิน');
                $table->decimal('cancellation_score', 5, 2)->default(100.00)->comment('คะแนนการไม่ยกเลิก');
                $table->decimal('punctuality_score', 5, 2)->default(100.00)->comment('ความตรงต่อเวลา');
                $table->decimal('behavior_score', 5, 2)->default(100.00)->comment('พฤติกรรม');
                $table->decimal('verification_score', 5, 2)->default(50.00)->comment('การยืนยันตัวตน');

                // สถิติ
                $table->unsignedInteger('total_bookings')->default(0);
                $table->unsignedInteger('completed_bookings')->default(0);
                $table->unsignedInteger('cancelled_bookings')->default(0);
                $table->unsignedInteger('no_show_count')->default(0);
                $table->unsignedInteger('late_cancellation_count')->default(0);
                $table->unsignedInteger('dispute_count')->default(0);
                $table->unsignedInteger('dispute_lost_count')->default(0);
                $table->unsignedInteger('warnings_count')->default(0);

                // ระดับความน่าเชื่อถือ
                $table->enum('trust_level', [
                    'new',
                    'standard',
                    'trusted',
                    'verified',
                    'warning',
                    'restricted',
                    'suspended',
                    'banned',
                ])->default('new');

                // การจำกัด
                $table->boolean('requires_prepayment')->default(false);
                $table->boolean('requires_deposit')->default(false);
                $table->decimal('max_booking_value', 12, 2)->nullable();
                $table->unsignedInteger('max_active_bookings')->nullable();

                // การระงับ
                $table->timestamp('suspended_until')->nullable();
                $table->string('suspension_reason')->nullable();
                $table->timestamp('banned_at')->nullable();
                $table->string('ban_reason')->nullable();

                $table->timestamps();

                $table->unique(['user_id', 'entity_type'], 'user_trust_user_entity_unique');
                $table->unique(['provider_id', 'entity_type'], 'user_trust_provider_entity_unique');
                $table->index(['trust_level', 'trust_score'], 'user_trust_level_score_idx');
            });
        }

        // 3. ตาราง Penalties - บันทึกค่าปรับ/บทลงโทษ
        if (!Schema::hasTable('service_booking_penalties')) {
            Schema::create('service_booking_penalties', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('service_booking_id');
                $table->foreign('service_booking_id')
                    ->references('id')
                    ->on('service_bookings')
                    ->onDelete('cascade');

                $table->unsignedBigInteger('user_id')->nullable();
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');

                $table->unsignedBigInteger('provider_id')->nullable();
                $table->foreign('provider_id')
                    ->references('id')
                    ->on('service_providers')
                    ->onDelete('set null');

                $table->enum('penalized_type', ['user', 'provider']);

                // ประเภทค่าปรับ
                $table->enum('penalty_type', [
                    'late_cancellation',
                    'no_show',
                    'payment_default',
                    'service_incomplete',
                    'dispute_lost',
                    'policy_violation',
                    'fraud',
                    'abuse',
                ]);

                // จำนวนเงิน
                $table->decimal('penalty_amount', 12, 2);
                $table->string('currency', 3)->default('THB');

                // คะแนนที่หัก
                $table->decimal('trust_score_deduction', 5, 2)->default(0);

                // สถานะ
                $table->enum('status', [
                    'pending',
                    'charged',
                    'paid',
                    'waived',
                    'disputed',
                ])->default('pending');

                $table->text('reason');
                $table->text('notes')->nullable();

                // การชำระ
                $table->timestamp('charged_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('transaction_id')->nullable();

                $table->timestamps();

                $table->index(['penalized_type', 'status'], 'penalties_type_status_idx');
            });
        }

        // 4. ตาราง User Blocks - Block/Blacklist
        if (!Schema::hasTable('user_blocks')) {
            Schema::create('user_blocks', function (Blueprint $table) {
                $table->id();

                // ผู้ block
                $table->unsignedBigInteger('blocker_user_id')->nullable();
                $table->foreign('blocker_user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->unsignedBigInteger('blocker_provider_id')->nullable();
                $table->foreign('blocker_provider_id')
                    ->references('id')
                    ->on('service_providers')
                    ->onDelete('cascade');

                $table->enum('blocker_type', ['user', 'provider', 'system', 'admin']);

                // ผู้ถูก block
                $table->unsignedBigInteger('blocked_user_id')->nullable();
                $table->foreign('blocked_user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->unsignedBigInteger('blocked_provider_id')->nullable();
                $table->foreign('blocked_provider_id')
                    ->references('id')
                    ->on('service_providers')
                    ->onDelete('cascade');

                $table->enum('blocked_type', ['user', 'provider']);

                // ประเภทการ block
                $table->enum('block_type', [
                    'personal',
                    'safety',
                    'system_auto',
                    'admin',
                ])->default('personal');

                $table->string('reason')->nullable();
                $table->timestamp('expires_at')->nullable()->comment('null = ถาวร');

                $table->timestamps();

                $table->index(['blocker_user_id', 'blocked_user_id'], 'blocks_user_to_user_idx');
                $table->index(['blocker_provider_id', 'blocked_provider_id'], 'blocks_provider_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('user_blocks');
        Schema::dropIfExists('service_booking_penalties');
        Schema::dropIfExists('user_trust_scores');
        Schema::dropIfExists('service_booking_location_logs');
    }
};
