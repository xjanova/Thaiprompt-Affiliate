<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🪪 eKYC หลายเอกสาร (2026-11-10) — บัตรประชาชน / ใบขับขี่ไทย / หนังสือเดินทางทุกประเทศ / เอกสารต่างประเทศอื่น
 *
 * document_type    = thai_id | thai_dl | passport | foreign_id | foreign_dl | other (null = แถวเก่า = thai_id)
 * document_country = รหัสประเทศผู้ออกเอกสาร ICAO 3 ตัว (THA, JPN, D, ...) — ไม่ใช่ข้อมูลส่วนบุคคล
 *
 * เพิ่มคอลัมน์อย่างเดียว · รันซ้ำได้ (SafeMigration)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('kyc_verifications')) {
            return;
        }

        Schema::table('kyc_verifications', function (Blueprint $table) {
            $this->safeAddColumn($table, 'kyc_verifications', 'document_type', fn ($c) => $c->string('document_type', 20)->nullable());
            $this->safeAddColumn($table, 'kyc_verifications', 'document_country', fn ($c) => $c->string('document_country', 3)->nullable());
        });
    }

    public function down(): void
    {
        $this->safeDropColumn('kyc_verifications', ['document_type', 'document_country']);
    }
};
