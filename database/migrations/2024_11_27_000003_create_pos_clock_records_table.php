<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง pos_clock_records
     * ตารางบันทึกการลงเวลาเข้า-ออกของพนักงาน POS
     */
    public function up(): void
    {
        if (Schema::hasTable('pos_clock_records')) {
            return;
        }

        Schema::create('pos_clock_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_staff_assignment_id')
                ->constrained('pos_staff_assignments')
                ->onDelete('cascade');
            $table->foreignId('pos_session_id')
                ->nullable()
                ->constrained('pos_sessions')
                ->onDelete('set null');
            $table->foreignId('work_shift_id')
                ->nullable()
                ->constrained('work_shifts')
                ->onDelete('set null');

            // การลงเวลา
            $table->date('work_date'); // วันที่ทำงาน
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();
            $table->timestamp('break_start_at')->nullable();
            $table->timestamp('break_end_at')->nullable();

            // ระบบ Clock-in
            $table->string('clock_in_method')->nullable(); // pin, rfid, fingerprint, manual
            $table->string('clock_out_method')->nullable();

            // สถานะ
            $table->enum('status', ['clocked_in', 'on_break', 'clocked_out', 'absent', 'late', 'early_out'])
                ->default('clocked_in');

            // การคำนวณเวลา
            $table->integer('scheduled_minutes')->default(0); // เวลาตามกะ (นาที)
            $table->integer('worked_minutes')->default(0); // เวลาทำงานจริง
            $table->integer('overtime_minutes')->default(0); // OT
            $table->integer('late_minutes')->default(0); // มาสาย
            $table->integer('early_out_minutes')->default(0); // ออกก่อน
            $table->integer('break_minutes')->default(0); // พักเกิน

            // สถิติยอดขาย
            $table->integer('transactions_count')->default(0);
            $table->decimal('sales_amount', 15, 2)->default(0);
            $table->decimal('cash_handled', 15, 2)->default(0);
            $table->decimal('tips_received', 10, 2)->default(0);

            // หมายเหตุ
            $table->text('notes')->nullable();
            $table->text('clock_in_notes')->nullable();
            $table->text('clock_out_notes')->nullable();

            // ตำแหน่ง GPS (ถ้ามี)
            $table->decimal('clock_in_latitude', 10, 7)->nullable();
            $table->decimal('clock_in_longitude', 10, 7)->nullable();
            $table->decimal('clock_out_latitude', 10, 7)->nullable();
            $table->decimal('clock_out_longitude', 10, 7)->nullable();

            // การอนุมัติ (กรณีแก้ไข)
            $table->boolean('is_modified')->default(false);
            $table->foreignId('modified_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('modified_at')->nullable();
            $table->text('modification_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['pos_staff_assignment_id', 'work_date']);
            $table->index(['work_date', 'status']);
            $table->unique(['pos_staff_assignment_id', 'work_date', 'work_shift_id'], 'unique_staff_date_shift');
        });
    }

    /**
     * ลบตาราง pos_clock_records
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_clock_records');
    }
};
