<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ province_id ในตาราง hotels
     *
     * ⚠️ IMPORTANT: ใช้ Schema::hasColumn() เพื่อเช็คก่อนเพิ่ม
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง hotels มีอยู่หรือไม่
        if (! Schema::hasTable('hotels')) {
            return;
        }

        Schema::table('hotels', function (Blueprint $table) {
            // เพิ่ม province_id สำหรับเชื่อมโยงกับจังหวัด
            if (! Schema::hasColumn('hotels', 'province_id')) {
                $table->unsignedBigInteger('province_id')->nullable()->after('country');

                // เพิ่ม foreign key constraint
                $table->foreign('province_id')
                    ->references('id')
                    ->on('provinces')
                    ->onDelete('set null');

                // เพิ่ม index
                $table->index('province_id', 'hotels_province_idx');
            }
        });
    }

    /**
     * ลบคอลัมน์ province_id
     */
    public function down(): void
    {
        if (! Schema::hasTable('hotels')) {
            return;
        }

        Schema::table('hotels', function (Blueprint $table) {
            if (Schema::hasColumn('hotels', 'province_id')) {
                // ลบ foreign key ก่อน
                $table->dropForeign(['province_id']);
                // ลบ index
                $table->dropIndex('hotels_province_idx');
                // ลบคอลัมน์
                $table->dropColumn('province_id');
            }
        });
    }
};
