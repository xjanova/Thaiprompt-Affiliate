<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🌙 (2026-09-21) ค่าแนะนำของบิลจากเว็บ/แอพจันทรา — เป็น "เปอร์เซ็นต์ของยอดบิล"
     *
     * เจ้าของเลือกเอง: ราคาจันทรามีตั้งแต่ 9฿ ถึง 129฿ ถ้าใช้อัตราคงที่ต่อบิลของบอท
     *   (สายตรง 10฿ + หลาน 5฿) ไพ่ 9฿ จะจ่ายค่าแนะนำ 15฿ เกินราคาขาย
     *
     * ⚠️ ห้ามใช้ Schema::hasTable() + return — คอลัมน์ใหม่จะไม่ถูกสร้าง
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_juntra_l1_percent')) {
                $table->decimal('fortune_juntra_l1_percent', 5, 2)->default(10);
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_juntra_l2_enabled')) {
                $table->boolean('fortune_juntra_l2_enabled')->default(true);
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_juntra_l2_percent')) {
                $table->decimal('fortune_juntra_l2_percent', 5, 2)->default(5);
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['fortune_juntra_l1_percent', 'fortune_juntra_l2_enabled', 'fortune_juntra_l2_percent'] as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
