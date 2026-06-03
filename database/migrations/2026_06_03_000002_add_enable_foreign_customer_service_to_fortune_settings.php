<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม toggle เปิด/ปิดบริการลูกค้าต่างประเทศ ในตาราง fortune_telling_settings
     *
     * enable_foreign_customer_service:
     *   - true (default)  = เปิดบริการทุกประเทศ (พฤติกรรมเดิม — ไม่ block ใคร)
     *   - false           = ตรวจพบลูกค้าต่างชาติ (สคริปต์ลาวในข้อความ/ชื่อ) → ไม่สร้างบิล
     *                       ขึ้นข้อความ "ยังไม่เปิดบริการในประเทศของคุณ"
     *
     * เหตุผล: QR/PromptPay เป็นของไทย — ลูกค้าลาวจ่ายไม่ได้ → สร้างบิลค้างรก
     *         admin เลือกได้ว่าจะรับลูกค้าต่างประเทศหรือไม่
     *
     * ⚠️ ALTER TABLE — ใช้ Schema::hasColumn() เช็ค (ห้าม hasTable + return)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_foreign_customer_service')) {
                $table->boolean('enable_foreign_customer_service')->default(true)
                    ->after('enable_celtic_cross')
                    ->comment('เปิดบริการลูกค้าต่างประเทศ (false = ตรวจพบต่างชาติ → ไม่สร้างบิล)');
            }
        });
    }

    /**
     * ลบคอลัมน์ enable_foreign_customer_service
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_foreign_customer_service')) {
                $table->dropColumn('enable_foreign_customer_service');
            }
        });
    }
};
