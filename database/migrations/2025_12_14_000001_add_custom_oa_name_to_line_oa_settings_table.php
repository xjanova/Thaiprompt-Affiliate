<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ custom_oa_name สำหรับกำหนดชื่อ LINE OA เอง
     *
     * ใช้สำหรับแสดงชื่อ LINE OA ในหน้าต่างๆ แทนชื่อจาก API
     * ถ้าไม่ได้กำหนดจะใช้ชื่อจาก Channel Access Token แทน
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('line_oa_settings', function (Blueprint $table) {
            // เช็คว่าคอลัมน์มีอยู่แล้วหรือยัง
            if (!Schema::hasColumn('line_oa_settings', 'custom_oa_name')) {
                $table->string('custom_oa_name')->nullable()->after('liff_id')
                    ->comment('ชื่อ LINE OA ที่กำหนดเอง (ถ้าไม่ใส่จะใช้ชื่อจากโทเค็น)');
            }
        });
    }

    /**
     * ลบคอลัมน์ custom_oa_name
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('line_oa_settings', function (Blueprint $table) {
            if (Schema::hasColumn('line_oa_settings', 'custom_oa_name')) {
                $table->dropColumn('custom_oa_name');
            }
        });
    }
};
