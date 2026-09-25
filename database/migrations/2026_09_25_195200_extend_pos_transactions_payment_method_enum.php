<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มวิธีชำระ 'bank_transfer' และ 'other' ให้ pos_transactions.payment_method
 *
 * หน้าขายหน้าร้านบนเว็บ (seller.pos.terminal) ให้เลือก "โอนเงิน" / "อื่น ๆ" ได้
 * และ SellerPosController::createTransaction ก็ validate ค่าเหล่านี้ผ่าน
 * แต่ enum เดิมมีแค่ cash, card, qr, e-wallet, credit, multiple → insert พังบน MySQL strict
 *
 * ⚠️ คงค่าเดิมทั้งหมดไว้ (แอป POS บนเครื่องยังส่ง e-wallet / credit / multiple ได้)
 */
return new class extends Migration
{
    /**
     * ขยาย enum ของวิธีชำระเงิน (เฉพาะ MySQL — SQLite ไม่มี enum จริง)
     */
    public function up(): void
    {
        if (! Schema::hasTable('pos_transactions') || ! Schema::hasColumn('pos_transactions', 'payment_method')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE pos_transactions MODIFY payment_method ENUM('cash','card','qr','e-wallet','credit','multiple','bank_transfer','other') NOT NULL DEFAULT 'cash'");
    }

    /**
     * ย้อนกลับ: แปลงค่าใหม่เป็น 'multiple' ก่อน แล้วคืน enum เดิม (กันข้อมูลหาย/insert พัง)
     */
    public function down(): void
    {
        if (! Schema::hasTable('pos_transactions') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('pos_transactions')
            ->whereIn('payment_method', ['bank_transfer', 'other'])
            ->update(['payment_method' => 'multiple']);

        DB::statement("ALTER TABLE pos_transactions MODIFY payment_method ENUM('cash','card','qr','e-wallet','credit','multiple') NOT NULL DEFAULT 'cash'");
    }
};
