<?php

/**
 * Migration สำหรับระบบป้องกันการกลั่นแกล้ง (Anti-Abuse Protection)
 *
 * ประกอบด้วย:
 * 1. service_booking_location_logs - บันทึกประวัติตำแหน่ง GPS (แม้ปิดแอพ)
 * 2. service_booking_disputes - ระบบร้องเรียน/ข้อพิพาท
 * 3. user_trust_scores - คะแนนความน่าเชื่อถือ
 * 4. service_booking_penalties - บันทึกค่าปรับ/บทลงโทษ
 * 5. user_blocks - ระบบ Block/Blacklist
 */

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
        // 1. ตาราง Location Logs - บันทึกประวัติตำแหน่ง GPS ทุก X วินาที
        if (!Schema::hasTable('service_booking_location_logs')) {
            Schema::create('service_booking_location_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_booking_id')->constrained('service_bookings')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('provider_id')->nullable()->constrained('service_providers')->onDelete('set null');

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

                // สถานะแบตเตอรี่ (สำคัญสำหรับ background tracking)
                $table->tinyInteger('battery_level')->nullable()->comment('0-100%');
                $table->boolean('is_charging')->nullable();

                // App state
                $table->enum('app_state', ['foreground', 'background', 'terminated'])->default('foreground');

                $table->timestamp('recorded_at')->useCurrent();
                $table->timestamps();

                // Indexes สำหรับ query ที่รวดเร็ว
                $table->index(['service_booking_id', 'actor_type', 'recorded_at']);
                $table->index(['recorded_at']);
            });
        }

        // 2. ตาราง Disputes - ข้อพิพาท/ร้องเรียน
        if (!Schema::hasTable('service_booking_disputes')) {
            Schema::create('service_booking_disputes', function (Blueprint $table) {
                $table->id();
                $table->string('dispute_number', 20)->unique();
                $table->foreignId('service_booking_id')->constrained('service_bookings')->onDelete('cascade');

                // ผู้ร้องเรียน
                $table->foreignId('reporter_user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('reporter_provider_id')->nullable()->constrained('service_providers')->onDelete('set null');
                $table->enum('reporter_type', ['user', 'provider', 'system']);

                // ผู้ถูกร้องเรียน
                $table->foreignId('accused_user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('accused_provider_id')->nullable()->constrained('service_providers')->onDelete('set null');
                $table->enum('accused_type', ['user', 'provider']);

                // ประเภทการร้องเรียน
                $table->enum('dispute_type', [
                    'no_show',              // ไม่มาตามนัด
                    'late_cancellation',    // ยกเลิกกระทันหัน
                    'payment_issue',        // ปัญหาการชำระเงิน
                    'service_quality',      // คุณภาพบริการไม่ดี
                    'fraud',                // การฉ้อโกง
                    'harassment',           // คุกคาม/ล่วงละเมิด
                    'safety_concern',       // ปัญหาความปลอดภัย
                    'property_damage',      // ทำทรัพย์สินเสียหาย
                    'overcharge',           // เก็บเงินเกิน
                    'fake_location',        // ปลอมตำแหน่ง GPS
                    'other'                 // อื่นๆ
                ]);

                // รายละเอียด
                $table->string('title');
                $table->text('description');
                $table->json('evidence_files')->nullable()->comment('รูปภาพ, วิดีโอ หลักฐาน');
                $table->json('location_evidence')->nullable()->comment('หลักฐาน GPS จาก location_logs');

                // สถานะ
                $table->enum('status', [
                    'pending',              // รอตรวจสอบ
                    'investigating',        // กำลังตรวจสอบ
                    'awaiting_response',    // รอคำชี้แจง
                    'resolved_favor_reporter',   // ตัดสินให้ผู้ร้อง
                    'resolved_favor_accused',    // ตัดสินให้ผู้ถูกร้อง
                    'resolved_mutual',      // ยอมความ
                    'dismissed',            // ยกฟ้อง
                    'escalated'             // ส่งต่อ
                ])->default('pending');

                // ผลการตัดสิน
                $table->text('resolution_notes')->nullable();
                $table->decimal('refund_amount', 12, 2)->default(0);
                $table->decimal('penalty_amount', 12, 2)->default(0);
                $table->boolean('resulted_in_ban')->default(false);

                // Admin ที่จัดการ
                $table->foreignId('handled_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('resolved_at')->nullable();

                // Priority
                $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');

                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'priority', 'created_at']);
            });
        }

        // 3. ตาราง User Trust Scores - คะแนนความน่าเชื่อถือ
        if (!Schema::hasTable('user_trust_scores')) {
            Schema::create('user_trust_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->foreignId('provider_id')->nullable()->constrained('service_providers')->onDelete('cascade');
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
                    'new',          // ใหม่ (< 5 bookings)
                    'standard',     // ปกติ (score >= 60)
                    'trusted',      // น่าเชื่อถือ (score >= 80)
                    'verified',     // ยืนยันแล้ว (score >= 90 + verified)
                    'warning',      // เตือน (score 40-59)
                    'restricted',   // จำกัด (score 20-39)
                    'suspended',    // ระงับชั่วคราว
                    'banned'        // แบน
                ])->default('new');

                // การจำกัด
                $table->boolean('requires_prepayment')->default(false)->comment('ต้องจ่ายล่วงหน้า');
                $table->boolean('requires_deposit')->default(false)->comment('ต้องวางมัดจำ');
                $table->decimal('max_booking_value', 12, 2)->nullable()->comment('วงเงินสูงสุดต่อครั้ง');
                $table->unsignedInteger('max_active_bookings')->nullable()->comment('จำนวน booking พร้อมกันสูงสุด');

                // การระงับ
                $table->timestamp('suspended_until')->nullable();
                $table->string('suspension_reason')->nullable();
                $table->timestamp('banned_at')->nullable();
                $table->string('ban_reason')->nullable();

                $table->timestamps();

                $table->unique(['user_id', 'entity_type']);
                $table->unique(['provider_id', 'entity_type']);
                $table->index(['trust_level', 'trust_score']);
            });
        }

        // 4. ตาราง Penalties - บันทึกค่าปรับ/บทลงโทษ
        if (!Schema::hasTable('service_booking_penalties')) {
            Schema::create('service_booking_penalties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_booking_id')->constrained('service_bookings')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('provider_id')->nullable()->constrained('service_providers')->onDelete('set null');
                $table->enum('penalized_type', ['user', 'provider']);

                // ประเภทค่าปรับ
                $table->enum('penalty_type', [
                    'late_cancellation',        // ยกเลิกสาย
                    'no_show',                  // ไม่มา
                    'payment_default',          // ไม่จ่ายเงิน
                    'service_incomplete',       // บริการไม่ครบ
                    'dispute_lost',             // แพ้ข้อพิพาท
                    'policy_violation',         // ละเมิดนโยบาย
                    'fraud',                    // ฉ้อโกง
                    'abuse'                     // กลั่นแกล้ง
                ]);

                // จำนวนเงิน
                $table->decimal('penalty_amount', 12, 2);
                $table->string('currency', 3)->default('THB');

                // คะแนนที่หัก
                $table->decimal('trust_score_deduction', 5, 2)->default(0);

                // สถานะ
                $table->enum('status', [
                    'pending',      // รอดำเนินการ
                    'charged',      // หักแล้ว
                    'paid',         // จ่ายแล้ว
                    'waived',       // ยกเว้น
                    'disputed'      // โต้แย้ง
                ])->default('pending');

                $table->text('reason');
                $table->text('notes')->nullable();

                // การชำระ
                $table->timestamp('charged_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('transaction_id')->nullable();

                $table->timestamps();

                $table->index(['penalized_type', 'status']);
            });
        }

        // 5. ตาราง User Blocks - Block/Blacklist ระหว่างผู้ใช้
        if (!Schema::hasTable('user_blocks')) {
            Schema::create('user_blocks', function (Blueprint $table) {
                $table->id();

                // ผู้ block
                $table->foreignId('blocker_user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->foreignId('blocker_provider_id')->nullable()->constrained('service_providers')->onDelete('cascade');
                $table->enum('blocker_type', ['user', 'provider', 'system']);

                // ผู้ถูก block
                $table->foreignId('blocked_user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->foreignId('blocked_provider_id')->nullable()->constrained('service_providers')->onDelete('cascade');
                $table->enum('blocked_type', ['user', 'provider']);

                // ประเภทการ block
                $table->enum('block_type', [
                    'personal',     // Block ส่วนตัว (ไม่อยากเจอกันอีก)
                    'safety',       // ปัญหาความปลอดภัย
                    'system_auto',  // ระบบ block อัตโนมัติ
                    'admin'         // Admin block
                ])->default('personal');

                $table->string('reason')->nullable();
                $table->timestamp('expires_at')->nullable()->comment('null = ถาวร');

                $table->timestamps();

                $table->index(['blocker_user_id', 'blocked_user_id']);
                $table->index(['blocker_provider_id', 'blocked_provider_id']);
            });
        }

        // 6. เพิ่ม columns ใน service_bookings สำหรับ anti-abuse
        Schema::table('service_bookings', function (Blueprint $table) {
            // Cancellation protection
            if (!Schema::hasColumn('service_bookings', 'cancellation_reason')) {
                $table->string('cancellation_reason')->nullable()->after('cancelled_at');
            }
            if (!Schema::hasColumn('service_bookings', 'cancelled_by_type')) {
                $table->enum('cancelled_by_type', ['user', 'provider', 'system', 'admin'])->nullable()->after('cancellation_reason');
            }
            if (!Schema::hasColumn('service_bookings', 'cancellation_fee')) {
                $table->decimal('cancellation_fee', 12, 2)->default(0)->after('cancelled_by_type');
            }
            if (!Schema::hasColumn('service_bookings', 'cancellation_fee_charged')) {
                $table->boolean('cancellation_fee_charged')->default(false)->after('cancellation_fee');
            }

            // Payment protection
            if (!Schema::hasColumn('service_bookings', 'payment_hold_amount')) {
                $table->decimal('payment_hold_amount', 12, 2)->default(0)->after('payment_status');
            }
            if (!Schema::hasColumn('service_bookings', 'payment_hold_released_at')) {
                $table->timestamp('payment_hold_released_at')->nullable()->after('payment_hold_amount');
            }

            // Evidence
            if (!Schema::hasColumn('service_bookings', 'evidence_photos')) {
                $table->json('evidence_photos')->nullable()->comment('รูปหลักฐานก่อน/หลังบริการ');
            }
            if (!Schema::hasColumn('service_bookings', 'service_started_photo')) {
                $table->string('service_started_photo')->nullable()->comment('รูปเมื่อเริ่มบริการ');
            }
            if (!Schema::hasColumn('service_bookings', 'service_completed_photo')) {
                $table->string('service_completed_photo')->nullable()->comment('รูปเมื่อเสร็จบริการ');
            }

            // Flags
            if (!Schema::hasColumn('service_bookings', 'is_suspicious')) {
                $table->boolean('is_suspicious')->default(false)->comment('มีพฤติกรรมน่าสงสัย');
            }
            if (!Schema::hasColumn('service_bookings', 'suspicious_flags')) {
                $table->json('suspicious_flags')->nullable()->comment('รายละเอียดพฤติกรรมน่าสงสัย');
            }
            if (!Schema::hasColumn('service_bookings', 'requires_photo_evidence')) {
                $table->boolean('requires_photo_evidence')->default(false)->comment('ต้องถ่ายรูปหลักฐาน');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ลบ columns ที่เพิ่มใน service_bookings
        Schema::table('service_bookings', function (Blueprint $table) {
            $columns = [
                'cancellation_reason',
                'cancelled_by_type',
                'cancellation_fee',
                'cancellation_fee_charged',
                'payment_hold_amount',
                'payment_hold_released_at',
                'evidence_photos',
                'service_started_photo',
                'service_completed_photo',
                'is_suspicious',
                'suspicious_flags',
                'requires_photo_evidence',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('service_bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('user_blocks');
        Schema::dropIfExists('service_booking_penalties');
        Schema::dropIfExists('user_trust_scores');
        Schema::dropIfExists('service_booking_disputes');
        Schema::dropIfExists('service_booking_location_logs');
    }
};
