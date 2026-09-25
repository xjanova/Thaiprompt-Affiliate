<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มข้อมูลประกอบการระงับบัญชีผู้ใช้ (users.blocked_at มีอยู่แล้ว)
 *
 * - blocked_reason : เหตุผลที่แอดมินระงับ (เก็บไว้ให้ทีมงานดู ไม่แสดงให้ผู้ใช้เห็นตรงๆ)
 * - blocked_by     : แอดมินที่กดระงับ (ไว้ตรวจสอบย้อนหลัง)
 *
 * ⚠️ ALTER TABLE → ห้ามใช้ Schema::hasTable() + return (คอลัมน์จะไม่ถูกสร้าง)
 *    ใช้ SafeMigration เช็คทีละคอลัมน์ รันซ้ำได้ปลอดภัย
 */
return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ blocked_reason + blocked_by ในตาราง users
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $this->safeAddColumn($table, 'users', 'blocked_reason', function (Blueprint $table) {
                $table->string('blocked_reason', 500)->nullable()->after('blocked_at')
                    ->comment('เหตุผลที่ระงับบัญชี (สำหรับทีมงาน)');
            });

            $this->safeAddColumn($table, 'users', 'blocked_by', function (Blueprint $table) {
                $table->unsignedBigInteger('blocked_by')->nullable()->after('blocked_at')
                    ->comment('แอดมินที่ระงับบัญชี');
            });
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        $this->safeDropColumn('users', ['blocked_reason', 'blocked_by']);
    }
};
