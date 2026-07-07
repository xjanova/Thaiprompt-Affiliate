<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ PDPA ลงตาราง users
     *
     * ⚠️ IMPORTANT: เป็น ALTER TABLE (เพิ่มคอลัมน์) — ห้ามใช้ Schema::hasTable() + return
     * ต้องเช็คทีละคอลัมน์ด้วย Schema::hasColumn()
     *
     * ใช้ทำเครื่องหมายบัญชีที่ถูก "ปกปิด PII + ยุติสิทธิ์" ตามคำขอลบข้อมูล
     * (บัญชีไม่ถูกลบทิ้ง เพื่อรักษาโครงสร้างผังสายงาน/บัญชีการเงินของสมาชิกคนอื่น)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // วันเวลาที่ปกปิดข้อมูลตาม PDPA (null = ยังปกติ)
            if (! Schema::hasColumn('users', 'pdpa_deleted_at')) {
                $table->timestamp('pdpa_deleted_at')->nullable()->after('updated_at');
            }

            // รหัสอ้างอิงคำขอลบ (โยงกับ fortune_data_deletion_requests.confirmation_code)
            if (! Schema::hasColumn('users', 'pdpa_deletion_ref')) {
                $table->string('pdpa_deletion_ref', 64)->nullable()->after('pdpa_deleted_at');
            }
        });
    }

    /**
     * ลบคอลัมน์ PDPA
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'pdpa_deletion_ref')) {
                $table->dropColumn('pdpa_deletion_ref');
            }
            if (Schema::hasColumn('users', 'pdpa_deleted_at')) {
                $table->dropColumn('pdpa_deleted_at');
            }
        });
    }
};
