<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * 📊 ดัชนีสำหรับหน้า "รายงาน TPIX"
 *
 * - (status, paid_at)  → ยอดรับรายวัน/7/30 วัน + ผู้จ่ายสูงสุด (WHERE status IN (...) AND paid_at >= ?)
 * - (created_at)       → อัตราแปลง (ใบที่ออกในช่วงเวลา) + ส่งออก CSV ตามช่วงวันที่
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('tpix_payments')) {
            return;
        }

        $this->safeAddIndex('tpix_payments', ['status', 'paid_at'], 'tpix_pay_status_paid_idx');
        $this->safeAddIndex('tpix_payments', ['created_at'], 'tpix_pay_created_idx');
    }

    public function down(): void
    {
        if (! Schema::hasTable('tpix_payments')) {
            return;
        }

        $this->safeDropIndex('tpix_payments', 'tpix_pay_status_paid_idx');
        $this->safeDropIndex('tpix_payments', 'tpix_pay_created_idx');
    }
};
