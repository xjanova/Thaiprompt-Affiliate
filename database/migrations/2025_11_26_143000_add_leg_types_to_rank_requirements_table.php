<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มประเภท requirement สำหรับ leg (ลูกทีมระดับต่างๆ)
 *
 * ประเภทที่เพิ่ม:
 * - diamond_legs: จำนวนลูกทีมระดับเพชร
 * - crown_legs: จำนวนลูกทีมระดับมงกุฎ
 * - royal_legs: จำนวนลูกทีมระดับรอยัล
 *
 * ใช้สำหรับ rank requirements ของระดับ Crown, Royal และ Legend
 */
return new class extends Migration
{
    /**
     * รัน migrations
     *
     * @return void
     */
    public function up(): void
    {
        // เพิ่ม enum values ใหม่สำหรับ requirement_type
        // ใช้ ALTER TABLE เพื่อเพิ่มค่า enum ใหม่
        DB::statement("ALTER TABLE `rank_requirements` MODIFY COLUMN `requirement_type` ENUM(
            'points',
            'referrals',
            'sales',
            'active_referrals',
            'team_sales',
            'consecutive_months',
            'custom',
            'diamond_legs',
            'crown_legs',
            'royal_legs'
        ) DEFAULT 'points'");
    }

    /**
     * ย้อนกลับ migrations
     *
     * @return void
     */
    public function down(): void
    {
        // ลบค่า enum ที่เพิ่มเข้าไป (ต้องลบข้อมูลที่ใช้ค่าเหล่านี้ก่อน)
        DB::statement("DELETE FROM `rank_requirements` WHERE `requirement_type` IN ('diamond_legs', 'crown_legs', 'royal_legs')");

        // คืนค่า enum กลับไปเป็นค่าเดิม
        DB::statement("ALTER TABLE `rank_requirements` MODIFY COLUMN `requirement_type` ENUM(
            'points',
            'referrals',
            'sales',
            'active_referrals',
            'team_sales',
            'consecutive_months',
            'custom'
        ) DEFAULT 'points'");
    }
};
