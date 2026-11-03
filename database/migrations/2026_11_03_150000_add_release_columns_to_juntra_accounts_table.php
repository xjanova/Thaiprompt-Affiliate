<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * 🌙 (2026-11-03) FB web-only — ปลดบัญชีจันทราที่ถูกลบ (invariant M4)
     *
     * เดิม: ลูกค้าลบบัญชีบนจันทรา → juntra_accounts ยังผูก user_ref เก่ากับผู้ใช้ Thaiprompt ไว้
     *   บัญชีจันทราใหม่ที่ผูก Thaiprompt คนเดิมจะชน "ถูกจองแล้ว" แล้วได้ผู้ใช้เงาใต้ผู้แนะนำเริ่มต้นแบบเงียบ ๆ
     *
     * - released_at              : จันทราแจ้งว่าบัญชี user_ref นี้ถูกลบแล้ว (POST affiliate/accounts/{ref}/release)
     *                              แถวยังชี้ผู้ใช้เดิม (บิลค้างส่งของบัญชีเก่ายังลงคนเดิม) จนมีบัญชีใหม่มาขอผูก
     * - released_juntra_user_id  : user_ref เดิมของแถวนี้ หลังถูกชี้ไปบัญชีจันทราใหม่แล้ว
     *                              บิลค้างส่งของบัญชีเก่าที่มาถึงทีหลังยังหาคนเดิมเจอ (ไม่สร้างเงา)
     *
     * ⚠️ ALTER TABLE — ใช้ SafeMigration ห้าม hasTable + return
     */
    public function up(): void
    {
        Schema::table('juntra_accounts', function (Blueprint $table) {
            $this->safeAddColumn($table, 'juntra_accounts', 'released_at', function ($table) {
                $table->timestamp('released_at')->nullable()
                    ->comment('จันทราแจ้งลบบัญชี user_ref นี้แล้ว — บัญชีใหม่ผูก Thaiprompt คนเดิมได้');
            });

            $this->safeAddColumn($table, 'juntra_accounts', 'released_juntra_user_id', function ($table) {
                $table->unsignedBigInteger('released_juntra_user_id')->nullable()
                    ->comment('user_ref เดิมก่อนถูกชี้ไปบัญชีจันทราใหม่');
            });
        });

        $this->safeAddIndex('juntra_accounts', 'released_juntra_user_id', 'juntra_acc_released_ref_idx');
    }

    /**
     * ลบคอลัมน์ + index เมื่อ rollback
     */
    public function down(): void
    {
        $this->safeDropIndex('juntra_accounts', 'juntra_acc_released_ref_idx');
        $this->safeDropColumn('juntra_accounts', ['released_at', 'released_juntra_user_id']);
    }
};
