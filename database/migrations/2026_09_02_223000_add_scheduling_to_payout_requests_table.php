<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ที่ระบบจ่ายเงินแบบตั้งเวลา (auto_schedule) ต้องใช้
     *
     * ที่มาของบั๊ก:
     * PayoutService + ProcessScheduledPayouts เขียนโดยอ้างอิงคอลัมน์ชุดหนึ่ง
     * แต่ migration เดิม (2025_11_26_000005_create_payout_requests_table)
     * สร้างคอลัมน์อีกชุดหนึ่ง ทำให้ cron `payouts:process-scheduled`
     * fatal ทุกครั้งที่วิ่ง (Undefined constant PayoutRequest::STATUS_SCHEDULED)
     *
     * แนวทางแก้:
     * เพิ่มลง DB เฉพาะคอลัมน์ที่ "ไม่มีของเดิมใช้แทนได้" เท่านั้น
     * ส่วนที่ความหมายซ้ำกับคอลัมน์เดิม (gross_amount→amount, fee_amount→fee,
     * admin_note→approval_note, reject_reason→rejection_reason,
     * processed_at→paid_at, transaction_ref→payment_reference)
     * ไปแก้ที่โค้ดให้กลับมาใช้คอลัมน์เดิม เพื่อไม่ให้ตารางเดียวมีคอลัมน์
     * ความหมายซ้ำกันสองชุด
     */
    public function up(): void
    {
        // เพิ่มค่า 'scheduled' เข้า enum ของคอลัมน์ status
        // (Laravel แก้ enum ตรงๆ ไม่ได้ ต้องใช้ raw SQL)
        $this->addScheduledToStatusEnum();

        Schema::table('payout_requests', function (Blueprint $table) {
            // setting ที่ใช้ตอนสร้างคำขอ เก็บไว้ย้อนดูว่าคำนวณด้วยกฎชุดไหน
            $this->safeAddColumn($table, 'payout_requests', 'payout_setting_id', function (Blueprint $table) {
                $table->unsignedBigInteger('payout_setting_id')->nullable()->after('user_id');
            });

            // ประเภทรายได้ต้นทาง เช่น seller_sale, mlm_commission, affiliate_commission
            // ละเอียดกว่า payout_type และเป็นตัวเลือก Platform Wallet ต้นทางใน getSourceWallet()
            $this->safeAddColumn($table, 'payout_requests', 'earning_type', function (Blueprint $table) {
                $table->string('earning_type')->nullable()->after('payout_type');
            });

            // ยอดหนี้ที่หักไปตอนสร้างคำขอ ต้องเก็บไว้เพื่อคืนหนี้ให้ถูกยอดตอนคำขอถูกปฏิเสธ
            $this->safeAddColumn($table, 'payout_requests', 'debt_deduction', function (Blueprint $table) {
                $table->decimal('debt_deduction', 20, 4)->default(0)->after('fee');
            });

            // เวลาที่ตั้งไว้ให้จ่ายอัตโนมัติ (ใช้เฉพาะโหมด auto_schedule)
            $this->safeAddColumn($table, 'payout_requests', 'scheduled_at', function (Blueprint $table) {
                $table->timestamp('scheduled_at')->nullable()->after('status');
            });

            // ข้อความ error ตอนจ่ายไม่สำเร็จ ไว้ให้แอดมินดูสาเหตุ
            $this->safeAddColumn($table, 'payout_requests', 'error_message', function (Blueprint $table) {
                $table->text('error_message')->nullable()->after('payment_note');
            });
        });

        // index สำหรับ cron: WHERE status = 'scheduled' AND scheduled_at <= now()
        $this->safeAddIndex(
            'payout_requests',
            ['status', 'scheduled_at'],
            'payout_requests_status_scheduled_at_index'
        );

        // FK ไป payout_settings แบบ set null
        // (ห้าม cascade เพราะประวัติการจ่ายเงินต้องไม่หายตามการลบ setting)
        $this->addPayoutSettingForeignKey();
    }

    /**
     * ย้อนกลับการเปลี่ยนแปลง
     */
    public function down(): void
    {
        $this->safeDropForeign('payout_requests', 'payout_requests_payout_setting_id_foreign');
        $this->safeDropIndex('payout_requests', 'payout_requests_status_scheduled_at_index');

        // ดึงแถวที่ยังเป็น 'scheduled' กลับเป็น 'pending' ก่อนหด enum
        // ไม่งั้น MySQL จะเปลี่ยนค่าที่ไม่อยู่ใน enum เป็นค่าว่างเงียบๆ
        if ($this->isMySql()) {
            DB::table('payout_requests')
                ->where('status', 'scheduled')
                ->update(['status' => 'pending']);
        }

        $this->safeDropColumn('payout_requests', [
            'payout_setting_id',
            'earning_type',
            'debt_deduction',
            'scheduled_at',
            'error_message',
        ]);

        $this->removeScheduledFromStatusEnum();
    }

    /**
     * เพิ่มค่า 'scheduled' ใน enum ของคอลัมน์ status
     *
     * เช็คก่อนว่ามีอยู่แล้วหรือยัง เพื่อให้รันซ้ำได้ปลอดภัย
     */
    private function addScheduledToStatusEnum(): void
    {
        if (! $this->isMySql() || $this->statusEnumHasScheduled()) {
            return;
        }

        DB::statement(
            'ALTER TABLE `payout_requests` MODIFY COLUMN `status` '.
            "ENUM('pending','scheduled','approved','processing','paid','rejected','cancelled','failed') ".
            "NOT NULL DEFAULT 'pending'"
        );
    }

    /**
     * เอาค่า 'scheduled' ออกจาก enum ของคอลัมน์ status
     */
    private function removeScheduledFromStatusEnum(): void
    {
        if (! $this->isMySql() || ! $this->statusEnumHasScheduled()) {
            return;
        }

        DB::statement(
            'ALTER TABLE `payout_requests` MODIFY COLUMN `status` '.
            "ENUM('pending','approved','processing','paid','rejected','cancelled','failed') ".
            "NOT NULL DEFAULT 'pending'"
        );
    }

    /**
     * เช็คว่า enum ของ status มีค่า 'scheduled' อยู่แล้วหรือยัง
     */
    private function statusEnumHasScheduled(): bool
    {
        try {
            $column = DB::selectOne("SHOW COLUMNS FROM `payout_requests` LIKE 'status'");

            return $column !== null && str_contains($column->Type, "'scheduled'");
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * เพิ่ม foreign key payout_setting_id แบบ set null
     *
     * เขียนเองแทน safeAddForeign() เพราะ trait บังคับ onDelete('cascade')
     * ซึ่งอันตรายกับตารางประวัติการจ่ายเงิน
     */
    private function addPayoutSettingForeignKey(): void
    {
        $constraintName = 'payout_requests_payout_setting_id_foreign';

        if (! Schema::hasTable('payout_settings')) {
            return;
        }

        if (! Schema::hasColumn('payout_requests', 'payout_setting_id')) {
            return;
        }

        if ($this->foreignKeyExists('payout_requests', $constraintName)) {
            return;
        }

        Schema::table('payout_requests', function (Blueprint $table) use ($constraintName) {
            $table->foreign('payout_setting_id', $constraintName)
                ->references('id')
                ->on('payout_settings')
                ->nullOnDelete();
        });
    }

    /**
     * เช็คว่า connection ปัจจุบันเป็น MySQL/MariaDB หรือไม่
     *
     * ต้องรับ 'mariadb' ด้วย เพราะ Laravel 11 แยก driver ตัวนี้ออกมาต่างหาก
     * prod ตอนนี้เป็น MariaDB 10.6 แต่ config เป็น 'mysql' อยู่
     * ถ้าวันหนึ่งเปลี่ยน config เป็น 'mariadb' แล้วเช็คแค่ 'mysql'
     * การขยาย enum จะถูกข้ามเงียบๆ และ cron จะพังเหมือนเดิม
     */
    private function isMySql(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
