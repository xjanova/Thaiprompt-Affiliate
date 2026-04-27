<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่ม Central Wallet fallback fields ใน fortune_telling_settings
     *
     * ทำไมต้องมี:
     * เดิม FortuneCommissionService::payLevel1/payLevel2 จะ return เงียบๆ ถ้า:
     *   - ลูกค้าไม่มีผู้แนะนำ
     *   - sponsor ไม่ active (no roll-up policy)
     *   - ไม่มี grandparent
     * → เงินค่าแนะนำตกหายโดยไม่มี audit trail
     *
     * ใหม่: ถ้าหาผู้รับไม่ได้ → จ่ายเข้า "กระเป๋ากลาง" (system user)
     *   - มี FortuneCommission record + WalletTransaction → audit trail สมบูรณ์
     *   - notes บันทึกเหตุผล fallback (no_referrer / sponsor_inactive / etc.)
     *
     * fields:
     * - fortune_central_user_id: User ID ของกระเป๋ากลาง (nullable — ถ้า null = ปิด)
     * - fortune_central_fallback_enabled: เปิด/ปิดระบบ (default true)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_telling_settings', 'fortune_central_user_id', function ($table) {
                $table->unsignedBigInteger('fortune_central_user_id')
                    ->nullable()
                    ->after('fortune_level2_commission_amount')
                    ->comment('User ID ของกระเป๋ากลางที่รับค่าแนะนำเมื่อหา sponsor ไม่ได้');
            });

            $this->safeAddColumn($table, 'fortune_telling_settings', 'fortune_central_fallback_enabled', function ($table) {
                $table->boolean('fortune_central_fallback_enabled')
                    ->default(true)
                    ->after('fortune_central_user_id')
                    ->comment('เปิด/ปิดระบบ fallback ค่าแนะนำเข้ากระเป๋ากลาง');
            });
        });
    }

    /**
     * ลบ fields เมื่อ rollback
     */
    public function down(): void
    {
        $this->safeDropColumn('fortune_telling_settings', [
            'fortune_central_user_id',
            'fortune_central_fallback_enabled',
        ]);
    }
};
