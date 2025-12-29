<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: เพิ่มฟิลด์ permissions และ preferred_language ให้ตาราง users
 *
 * ⚠️ NOTE: ฟิลด์เหล่านี้ถูกรวมใน create_users_comprehensive_v3 แล้ว
 * Migration นี้จะข้ามถ้าตาราง users ยังไม่มี หรือมีคอลัมน์อยู่แล้ว
 */
return new class extends Migration
{
    /**
     * เพิ่มฟิลด์ permissions และ preferred_language
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง users มีอยู่หรือไม่
        if (! Schema::hasTable('users')) {
            // ตาราง users ยังไม่มี - ข้าม migration นี้
            // ฟิลด์เหล่านี้จะถูกสร้างใน users_comprehensive migration แทน
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // เพิ่ม permissions field (JSON) เฉพาะเมื่อยังไม่มี
            if (! Schema::hasColumn('users', 'permissions')) {
                $table->json('permissions')->nullable()->after('is_super_admin');
            }

            // เพิ่ม preferred language เฉพาะเมื่อยังไม่มี
            if (! Schema::hasColumn('users', 'preferred_language')) {
                $table->string('preferred_language', 5)->default('th')->after('permissions');
            }
        });
    }

    /**
     * ลบฟิลด์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('users', 'permissions')) {
                $columns[] = 'permissions';
            }
            if (Schema::hasColumn('users', 'preferred_language')) {
                $columns[] = 'preferred_language';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
