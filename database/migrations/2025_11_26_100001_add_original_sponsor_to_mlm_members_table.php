<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม original_sponsor_id สำหรับเก็บผู้แนะนำตรงจริงๆ
 *
 * ความแตกต่างระหว่าง sponsor ต่างๆ:
 * - original_sponsor_id: คนที่แนะนำตรงจริงๆ (ใช้รหัสของใคร) → ใช้สำหรับค่าแนะนำตรง
 * - unilevel_sponsor_id: parent ใน Unilevel tree (อาจ spillover ไปคนอื่น)
 * - binary_sponsor_id: parent ใน Binary tree (ตามกลยุทธ์ที่ตั้งค่า)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง mlm_members มีอยู่แล้วหรือไม่
        if (! Schema::hasTable('mlm_members')) {
            return;
        }

        Schema::table('mlm_members', function (Blueprint $table) {
            // เพิ่ม original_sponsor_id - คนที่แนะนำตรงจริงๆ
            if (! Schema::hasColumn('mlm_members', 'original_sponsor_id')) {
                $table->foreignId('original_sponsor_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('mlm_members')
                    ->nullOnDelete();
            }

            // Index สำหรับค้นหาลูกทีมตรง (direct referrals)
            // จะสร้าง index เฉพาะถ้ายังไม่มี
        });

        // สร้าง index แยก (เพื่อป้องกัน error ถ้า index มีอยู่แล้ว)
        $indexExists = collect(Schema::getIndexes('mlm_members'))
            ->pluck('name')
            ->contains('mlm_members_original_sponsor_id_index');

        if (! $indexExists && Schema::hasColumn('mlm_members', 'original_sponsor_id')) {
            Schema::table('mlm_members', function (Blueprint $table) {
                $table->index('original_sponsor_id', 'mlm_members_original_sponsor_id_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mlm_members', function (Blueprint $table) {
            if (Schema::hasColumn('mlm_members', 'original_sponsor_id')) {
                $table->dropForeign(['original_sponsor_id']);
                $table->dropColumn('original_sponsor_id');
            }
        });
    }
};
