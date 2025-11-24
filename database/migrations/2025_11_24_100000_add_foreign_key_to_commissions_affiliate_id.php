<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม foreign key constraint สำหรับ affiliate_id ใน commissions table
     *
     * Migration นี้รันหลังจาก affiliates table ถูกสร้างแล้ว
     * เพื่อแก้ปัญหา migration order dependency
     */
    public function up(): void
    {
        // ตรวจสอบว่าทั้ง 2 table มีอยู่แล้ว
        if (!Schema::hasTable('commissions') || !Schema::hasTable('affiliates')) {
            return;
        }

        // ตรวจสอบว่า foreign key ถูกสร้างแล้วหรือยัง
        $foreignKeys = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableForeignKeys('commissions');

        $hasForeignKey = false;
        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey->getForeignTableName() === 'affiliates') {
                $hasForeignKey = true;
                break;
            }
        }

        // ถ้ายังไม่มี foreign key → เพิ่ม
        if (!$hasForeignKey) {
            Schema::table('commissions', function (Blueprint $table) {
                // ตรวจสอบว่า column เป็น unsigned bigint หรือยัง
                // ถ้าเป็น foreign key อยู่แล้ว จะ skip
                try {
                    $table->foreign('affiliate_id')
                        ->references('id')
                        ->on('affiliates')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key อาจมีอยู่แล้ว ข้าม
                    \Log::info('Foreign key commissions_affiliate_id_foreign already exists or error: ' . $e->getMessage());
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('commissions')) {
            return;
        }

        Schema::table('commissions', function (Blueprint $table) {
            try {
                $table->dropForeign(['affiliate_id']);
            } catch (\Exception $e) {
                // Foreign key ไม่มีอยู่ ข้าม
            }
        });
    }
};
