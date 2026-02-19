<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มโหมดจ่ายคอมมิชชั่น (PV หรือ Static) สำหรับระบบดูดวง
 *
 * - fortune_commission_mode: 'pv' (ใช้ PV คำนวณตาม MLM levels) หรือ 'static' (จ่ายตรงตามจำนวนที่ตั้ง)
 * - fortune_static_commission_amount: จำนวนเงินคอมมิชชั่นตายตัว (เช่น 5 บาท)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // โหมดจ่ายคอมมิชชั่น: 'pv' = ใช้ PV ตาม MLM levels, 'static' = จ่ายตรงตามจำนวน
            $this->safeAddColumn($table, 'fortune_telling_settings', 'fortune_commission_mode', function ($table) {
                $table->string('fortune_commission_mode', 20)->default('pv')->after('fortune_affiliate_invite_message');
            });

            // จำนวนคอมมิชชั่นตายตัว (ใช้เมื่อ mode = static)
            $this->safeAddColumn($table, 'fortune_telling_settings', 'fortune_static_commission_amount', function ($table) {
                $table->decimal('fortune_static_commission_amount', 10, 2)->default(0)->after('fortune_commission_mode');
            });
        });
    }

    public function down(): void
    {
        $this->safeDropColumn('fortune_telling_settings', [
            'fortune_commission_mode',
            'fortune_static_commission_amount',
        ]);
    }
};
