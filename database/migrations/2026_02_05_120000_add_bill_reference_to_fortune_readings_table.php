<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม bill_reference สำหรับอ้างอิงบิลดูดวง
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            if (!Schema::hasColumn('fortune_readings', 'bill_reference')) {
                $table->string('bill_reference', 20)->nullable()->unique()->after('id');
                $table->index('bill_reference', 'fortune_bill_ref_idx');
            }
        });
    }

    /**
     * ลบคอลัมน์ bill_reference
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_readings', 'bill_reference')) {
                $table->dropIndex('fortune_bill_ref_idx');
                $table->dropColumn('bill_reference');
            }
        });
    }
};
