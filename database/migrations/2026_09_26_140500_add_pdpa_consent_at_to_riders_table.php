<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * riders.pdpa_consent_at — เวลาที่ผู้สมัครไรเดอร์ติ๊กยินยอมให้เก็บและใช้ข้อมูลส่วนบุคคล (PDPA)
 *
 * บันทึกจากหน้าเว็บ /user/rider/register ทุกครั้งที่ส่งใบสมัคร (ส่งใหม่ = เวลาใหม่)
 * เก็บเป็นหลักฐานความยินยอมตาม พ.ร.บ.คุ้มครองข้อมูลส่วนบุคคล (เลขบัตร รูปเอกสาร ตำแหน่ง GPS)
 *
 * prod ตอนนี้ riders = 0 แถว → เพิ่มคอลัมน์ได้ปลอดภัย / แถวเก่า (ถ้ามี) = null = ยังไม่มีหลักฐานจากเว็บ
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('riders')) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) {
            $this->safeAddColumn($table, 'riders', 'pdpa_consent_at', function ($table) {
                $table->timestamp('pdpa_consent_at')->nullable()
                    ->comment('เวลาที่ยินยอม PDPA ตอนส่งใบสมัครไรเดอร์');
            });
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('riders')) {
            $this->safeDropColumn('riders', 'pdpa_consent_at');
        }
    }
};
