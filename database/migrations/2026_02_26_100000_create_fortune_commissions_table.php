<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_commissions
     *
     * บันทึกคอมมิชชั่นจากบิลดูดวงโดยเฉพาะ (แยกจาก mlm_commissions)
     * รองรับ Level 1 (สายตรง) และ Level 2 (หลาน)
     * ทั้ง fixed amount และ percent ของราคาดูดวง
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_commissions')) {
            return;
        }

        Schema::create('fortune_commissions', function (Blueprint $table) {
            $table->id();

            // ผู้รับคอมมิชชั่น
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // ผู้จ่ายค่าดูดวง (คนดูดวง)
            $table->foreignId('from_user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // บิลดูดวงที่เกี่ยวข้อง
            $table->unsignedBigInteger('fortune_reading_id');
            $table->index('fortune_reading_id', 'fc_reading_idx');

            // MLM member ผู้รับคอมมิชชั่น
            $table->unsignedBigInteger('mlm_member_id')->nullable();
            $table->index('mlm_member_id', 'fc_mlm_member_idx');

            // MLM member ผู้จ่ายค่าดูดวง
            $table->unsignedBigInteger('from_mlm_member_id')->nullable();
            $table->index('from_mlm_member_id', 'fc_from_mlm_idx');

            // ชั้น: 1 = สายตรง (sponsor), 2 = หลาน (sponsor ของ sponsor)
            $table->tinyInteger('level')->default(1);

            // ประเภทคอมมิชชั่น: fixed = จำนวนเงินคงที่, percent = เปอร์เซ็นต์จากราคา
            $table->enum('commission_type', ['fixed', 'percent'])->default('fixed');

            // อัตราที่ใช้คำนวณ ณ ตอนนั้น (เช่น 10 บาท หรือ 10%)
            $table->decimal('commission_rate', 10, 2)->default(0);

            // จำนวนเงินจริงที่ได้รับ
            $table->decimal('amount', 12, 2)->default(0);

            // ราคาดูดวง ณ ตอนนั้น (snapshot)
            $table->decimal('reading_price', 12, 2)->default(0);

            // สถานะ
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');

            // วันที่เปลี่ยนสถานะ
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            // ลิงก์กับ wallet transaction (ถ้าจ่ายแล้ว)
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();

            // หมายเหตุ
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ป้องกันจ่ายซ้ำ: 1 บิล + 1 ผู้รับ + 1 ชั้น = 1 record
            $table->unique(
                ['fortune_reading_id', 'user_id', 'level'],
                'fc_reading_user_level_unique'
            );

            // Index สำหรับ query ที่ใช้บ่อย
            $table->index(['user_id', 'status'], 'fc_user_status_idx');
            $table->index(['user_id', 'level'], 'fc_user_level_idx');
            $table->index(['status', 'created_at'], 'fc_status_date_idx');
        });
    }

    /**
     * ลบตาราง fortune_commissions
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_commissions');
    }
};
