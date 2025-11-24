<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ extracted_data ในตาราง kyc_verifications
     * สำหรับเก็บข้อมูลที่ OCR อ่านได้จากบัตรประชาชน
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            // เช็คว่าคอลัมน์มีอยู่แล้วหรือยัง
            if (!Schema::hasColumn('kyc_verifications', 'extracted_data')) {
                $table->json('extracted_data')->nullable()->after('submitted_at');
            }
        });
    }

    /**
     * ลบคอลัมน์ extracted_data
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            if (Schema::hasColumn('kyc_verifications', 'extracted_data')) {
                $table->dropColumn('extracted_data');
            }
        });
    }
};
