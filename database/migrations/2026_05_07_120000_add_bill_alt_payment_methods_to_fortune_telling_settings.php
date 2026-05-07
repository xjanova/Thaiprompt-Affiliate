<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🩹 (2026-05-07 review U1) เพิ่ม bill_alternative_payment_methods
 *
 * Admin custom text — ใช้ใน Bill Psychology prompt แทน hard-code TrueMoney/Wise
 * ลูกค้าถามเรื่องโอนต่างประเทศ → AI ใช้ข้อความนี้ตอบ
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'bill_alternative_payment_methods')) {
                $table->text('bill_alternative_payment_methods')
                    ->nullable()
                    ->comment('ทางเลือกชำระเงินอื่น (admin custom — ใช้ตอบลูกค้าต่างประเทศ ฯลฯ)');
            }
        });
    }

    public function down(): void
    {
        $this->safeDropColumn('fortune_telling_settings', ['bill_alternative_payment_methods']);
    }
};
