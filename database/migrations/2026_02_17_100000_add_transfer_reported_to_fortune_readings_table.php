<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ transfer_reported สำหรับระบบแจ้งปัญหาโอนเงิน
     *
     * ผู้ใช้สามารถแจ้งว่าโอนเงินแล้วผ่าน Rich Menu
     * admin จะเห็นรายการที่ถูกแจ้งและตรวจสอบได้
     */
    public function up(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_readings', 'transfer_reported', function ($table) {
                $table->boolean('transfer_reported')->default(false)->after('amount_paid');
            });

            $this->safeAddColumn($table, 'fortune_readings', 'transfer_reported_at', function ($table) {
                $table->timestamp('transfer_reported_at')->nullable()->after('transfer_reported');
            });
        });
    }

    /**
     * ลบคอลัมน์ transfer_reported
     */
    public function down(): void
    {
        $this->safeDropColumn('fortune_readings', ['transfer_reported_at', 'transfer_reported']);
    }
};
