<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง payout_settings
     * ตั้งค่าระบบจ่ายเงินอัจฉริยะ
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('payout_settings', function (Blueprint $table) {
            $table->id();

            // ประเภทการจ่าย
            $table->enum('payout_type', [
                'seller',       // จ่ายให้ผู้ขาย
                'mlm',          // จ่าย MLM Commission
                'service',      // จ่ายให้ผู้ให้บริการ
            ])->unique();

            // โหมดการจ่าย
            $table->enum('mode', [
                'manual',           // Admin อนุมัติทุกรายการ
                'auto_schedule',    // จ่ายอัตโนมัติตามกำหนด
                'realtime',         // จ่ายทันทีเมื่อมีรายได้
                'hybrid'            // ผสม (ยอดน้อย auto, ยอดมาก manual)
            ])->default('manual');

            // ตั้งค่าการจ่ายอัตโนมัติ
            $table->tinyInteger('schedule_day')->nullable();        // วันที่จ่าย (1-31)
            $table->time('schedule_time')->nullable();              // เวลาที่จ่าย
            $table->enum('schedule_frequency', [
                'daily',        // ทุกวัน
                'weekly',       // ทุกสัปดาห์
                'biweekly',     // ทุก 2 สัปดาห์
                'monthly',      // ทุกเดือน
            ])->default('monthly');
            $table->tinyInteger('schedule_day_of_week')->nullable(); // 0=อาทิตย์, 1=จันทร์...

            // เงื่อนไขการจ่าย
            $table->decimal('min_payout_amount', 15, 2)->default(100);    // ยอดขั้นต่ำที่จ่ายได้
            $table->decimal('max_payout_amount', 15, 2)->nullable();       // ยอดสูงสุดต่อครั้ง
            $table->integer('holding_days')->default(0);                   // พักเงินกี่วัน

            // การอนุมัติอัตโนมัติ (สำหรับ hybrid mode)
            $table->boolean('require_approval')->default(true);           // ต้องอนุมัติก่อนจ่าย
            $table->decimal('auto_approve_under', 15, 2)->nullable();     // อนุมัติอัตโนมัติถ้าต่ำกว่า X บาท
            $table->boolean('auto_approve_verified_users')->default(false); // อนุมัติอัตโนมัติสำหรับ user ที่ verified

            // ค่าธรรมเนียมการถอน
            $table->decimal('withdrawal_fee_fixed', 15, 2)->default(0);   // ค่าธรรมเนียมคงที่
            $table->decimal('withdrawal_fee_percent', 5, 2)->default(0);  // ค่าธรรมเนียม %

            // การแจ้งเตือน
            $table->boolean('notify_on_payout')->default(true);           // แจ้งเตือนเมื่อจ่ายเงิน
            $table->boolean('notify_on_pending')->default(true);          // แจ้งเตือนเมื่อมีเงินรอจ่าย
            $table->boolean('notify_admin_large_payout')->default(true);  // แจ้ง Admin เมื่อจ่ายยอดใหญ่
            $table->decimal('large_payout_threshold', 15, 2)->default(10000); // ยอดที่ถือว่าใหญ่

            // สถานะ
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();

            // การตั้งค่าเพิ่มเติม
            $table->json('extra_settings')->nullable();

            $table->timestamps();
        });
    }

    /**
     * ลบตาราง payout_settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_settings');
    }
};
