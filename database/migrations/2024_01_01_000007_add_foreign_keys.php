<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: เพิ่ม foreign keys ในตาราง users
 *
 * ⚠️ NOTE: Migration นี้จะรันเฉพาะเมื่อ:
 *    - ตาราง users มีอยู่แล้ว
 *    - ตาราง affiliates มีอยู่แล้ว
 *    - คอลัมน์ affiliate_id มีอยู่ใน users
 *    - ยังไม่มี foreign key constraint
 */
return new class extends Migration
{
    /**
     * เพิ่ม foreign key constraint
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง users และ affiliates มีอยู่หรือไม่
        if (! Schema::hasTable('users')) {
            // ตาราง users ยังไม่มี - ข้าม migration นี้
            // foreign key จะถูกสร้างใน users_comprehensive migration แทน
            return;
        }

        if (! Schema::hasTable('affiliates')) {
            // ตาราง affiliates ยังไม่มี - ข้าม migration นี้
            return;
        }

        // ตรวจสอบว่ามีคอลัมน์ affiliate_id หรือไม่
        if (! Schema::hasColumn('users', 'affiliate_id')) {
            // ยังไม่มีคอลัมน์ affiliate_id - ข้าม
            return;
        }

        // ตรวจสอบว่ามี foreign key อยู่แล้วหรือไม่
        $foreignKeys = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableForeignKeys('users');

        $hasForeignKey = collect($foreignKeys)->contains(function ($fk) {
            return in_array('affiliate_id', $fk->getLocalColumns());
        });

        if ($hasForeignKey) {
            // มี foreign key อยู่แล้ว - ข้าม
            return;
        }

        // เพิ่ม foreign key
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('affiliate_id')
                ->references('id')
                ->on('affiliates')
                ->onDelete('set null');
        });
    }

    /**
     * ลบ foreign key constraint
     */
    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        // ตรวจสอบว่ามี foreign key หรือไม่ก่อนลบ
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['affiliate_id']);
            });
        } catch (\Exception $e) {
            // Foreign key อาจไม่มีอยู่ - ไม่ต้องทำอะไร
        }
    }
};
