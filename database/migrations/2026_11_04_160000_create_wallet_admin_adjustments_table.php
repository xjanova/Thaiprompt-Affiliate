<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง wallet_admin_adjustments — สมุดบัญชีตรวจสอบ (audit trail) ของทุกรายการ
     * ที่แอดมินแตะยอดเงินในกระเป๋าผู้ใช้โดยตรง (เติม / หัก / คืนเงิน / ย้อนรายการ / โอนโดยแอดมิน)
     *
     * แต่ละแถวบอก: ใครทำ (admin_id) · ทำกับกระเป๋าใคร · ยอดก่อน/หลัง · เหตุผล · IP · เวลา
     * idempotency_key + wallet_id เป็น unique — กดยืนยันซ้ำ/ส่งฟอร์มเดิมซ้ำจะไม่ตัด/เติมเงินซ้ำ
     */
    public function up(): void
    {
        if (Schema::hasTable('wallet_admin_adjustments')) {
            return;
        }

        Schema::create('wallet_admin_adjustments', function (Blueprint $table) {
            $table->id();
            // หลักฐานต้องอยู่ต่อแม้กระเป๋าถูกลบ → nullOnDelete (ไม่ใช่ cascade) · user_id/ยอด/เหตุผลยังอยู่ครบ
            $table->foreignId('wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            // เจ้าของกระเป๋า (เก็บซ้ำไว้เพื่อค้นหาเร็ว)
            $table->unsignedBigInteger('user_id')->index();
            // แอดมินที่ทำรายการ — ถ้าบัญชีแอดมินถูกลบ ประวัติยังอยู่ (ชื่อเก็บใน admin_name)
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('admin_name')->nullable();
            // credit | debit | refund | rollback | transfer_out | transfer_in
            $table->string('action', 20)->index();
            $table->decimal('amount', 20, 2);
            $table->decimal('balance_before', 20, 2);
            $table->decimal('balance_after', 20, 2);
            $table->text('reason');
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            // ธุรกรรมต้นฉบับ (ใช้กับ rollback — กันย้อนรายการเดิมซ้ำ)
            $table->unsignedBigInteger('original_transaction_id')->nullable()->index();
            $table->string('idempotency_key', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['idempotency_key', 'wallet_id'], 'waa_idem_wallet_unique');
            $table->index(['wallet_id', 'created_at'], 'waa_wallet_created_idx');
            $table->index(['admin_id', 'created_at'], 'waa_admin_created_idx');
        });
    }

    /**
     * ลบตาราง wallet_admin_adjustments
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_admin_adjustments');
    }
};
