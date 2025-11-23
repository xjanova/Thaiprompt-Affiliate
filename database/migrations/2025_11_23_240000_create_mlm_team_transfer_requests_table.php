<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง mlm_team_transfer_requests
     *
     * ตารางนี้เก็บคำขอย้ายทีมของสมาชิก MLM
     *
     * Business Rules:
     * - สมาชิกสามารถขอย้ายไปหาแม่ทีมใหม่ได้
     * - ต้องได้รับอนุมัติจากแม่ทีมเก่า
     * - ตำแหน่งใหม่ (binary position) ต้องว่าง
     * - ค่าธรรมเนียม: 100 บาท
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง
        if (Schema::hasTable('mlm_team_transfer_requests')) {
            return;
        }

        Schema::create('mlm_team_transfer_requests', function (Blueprint $table) {
            $table->id();

            // ข้อมูลผู้ขอย้าย
            $table->foreignId('mlm_member_id')
                ->constrained('mlm_members')
                ->onDelete('cascade')
                ->comment('สมาชิกที่ขอย้ายทีม');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('User ที่ขอย้าย');

            // ข้อมูลแม่ทีมเก่า (Unilevel)
            $table->foreignId('old_unilevel_sponsor_id')
                ->constrained('mlm_members')
                ->onDelete('cascade')
                ->comment('แม่ทีมเก่า (Unilevel)');

            // ข้อมูลแม่ทีมใหม่ (Unilevel)
            $table->foreignId('new_unilevel_sponsor_id')
                ->constrained('mlm_members')
                ->onDelete('cascade')
                ->comment('แม่ทีมใหม่ที่ต้องการย้ายไป (Unilevel)');

            // ข้อมูล Binary Tree
            $table->foreignId('old_binary_parent_id')
                ->nullable()
                ->constrained('mlm_members')
                ->onDelete('cascade')
                ->comment('Binary parent เก่า');

            $table->enum('old_binary_position', ['left', 'right'])
                ->nullable()
                ->comment('Binary position เก่า');

            $table->foreignId('new_binary_parent_id')
                ->nullable()
                ->constrained('mlm_members')
                ->onDelete('cascade')
                ->comment('Binary parent ใหม่ที่ต้องการย้ายไป');

            $table->enum('new_binary_position', ['left', 'right'])
                ->nullable()
                ->comment('Binary position ใหม่ (left/right)');

            // สถานะคำขอ
            $table->enum('status', [
                'pending',      // รออนุมัติจากแม่ทีมเก่า
                'approved',     // แม่ทีมเก่าอนุมัติแล้ว
                'rejected',     // แม่ทีมเก่าปฏิเสธ
                'paid',         // ชำระเงินแล้ว รอ admin ดำเนินการ
                'processing',   // Admin กำลังดำเนินการ
                'completed',    // ย้ายสำเร็จแล้ว
                'cancelled',    // ยกเลิกโดยผู้ขอ
            ])->default('pending')
                ->comment('สถานะคำขอ');

            // ข้อมูลการอนุมัติ
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('แม่ทีมเก่าที่อนุมัติ (user_id)');

            $table->timestamp('approved_at')
                ->nullable()
                ->comment('วันที่อนุมัติ');

            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('แม่ทีมเก่าที่ปฏิเสธ (user_id)');

            $table->timestamp('rejected_at')
                ->nullable()
                ->comment('วันที่ปฏิเสธ');

            $table->text('rejection_reason')
                ->nullable()
                ->comment('เหตุผลที่ปฏิเสธ');

            // ข้อมูลการชำระเงิน
            $table->decimal('transfer_fee', 10, 2)
                ->default(100.00)
                ->comment('ค่าธรรมเนียมการย้าย (บาท)');

            $table->foreignId('wallet_transaction_id')
                ->nullable()
                ->constrained('wallet_transactions')
                ->onDelete('set null')
                ->comment('Transaction ID ของการชำระเงิน');

            $table->timestamp('paid_at')
                ->nullable()
                ->comment('วันที่ชำระเงิน');

            // ข้อมูลการดำเนินการ
            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Admin ที่ดำเนินการ');

            $table->timestamp('processed_at')
                ->nullable()
                ->comment('วันที่ดำเนินการเสร็จสิ้น');

            $table->text('admin_notes')
                ->nullable()
                ->comment('หมายเหตุจาก Admin');

            // เหตุผลและข้อมูลเพิ่มเติม
            $table->text('reason')
                ->nullable()
                ->comment('เหตุผลที่ขอย้าย');

            $table->text('notes')
                ->nullable()
                ->comment('หมายเหตุเพิ่มเติม');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('mlm_member_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('old_unilevel_sponsor_id');
            $table->index('new_unilevel_sponsor_id');
            $table->index(['status', 'created_at'], 'status_date_idx');
        });
    }

    /**
     * ลบตาราง mlm_team_transfer_requests
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_team_transfer_requests');
    }
};
