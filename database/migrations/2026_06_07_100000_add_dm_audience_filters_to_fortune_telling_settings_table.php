<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มตัวกรอง "กลุ่มเป้าหมาย" ของ DM กลับ (คอมเมนต์/กดไลก์) + ปิดหมวดข้อความชวน
     *
     * 🌍 ตัวกรองสัญชาติ + อายุ:
     *   - dm_send_to_foreigners        ส่ง DM ให้คนต่างชาติด้วยไหม (default true = ส่งทุกคนเหมือนเดิม)
     *   - dm_foreigner_detect_basis    วิธีตรวจว่าใคร "ต่างชาติ" — 'script' | 'no_thai' | 'lao_only'
     *   - dm_filter_age_enabled        เปิดตัวกรองอายุไหม (default false)
     *   - dm_age_min / dm_age_max      ช่วงอายุที่ "ส่ง" (null = ไม่จำกัดด้านนั้น)
     *   - dm_age_unknown_action        ถ้าไม่รู้อายุ → 'send' (ส่งปกติ) | 'skip' (ไม่ส่ง)
     *
     * 🗂️ เปิด/ปิดหมวดข้อความชวน:
     *   - invite_disabled_categories   JSON array ของ "หมวดที่ปิด" (pickActive จะไม่สุ่มหมวดเหล่านี้)
     *
     * ⚠️ ALTER TABLE — ใช้ Schema::table() + hasColumn guard (ห้าม hasTable+return)
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // 🌍 ตัวกรองสัญชาติ (ต่างชาติ)
            if (! Schema::hasColumn('fortune_telling_settings', 'dm_send_to_foreigners')) {
                $table->boolean('dm_send_to_foreigners')->default(true)->after('enable_invite_rotation');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'dm_foreigner_detect_basis')) {
                $table->string('dm_foreigner_detect_basis', 20)->default('script')->after('dm_send_to_foreigners');
            }

            // 🎂 ตัวกรองอายุ
            if (! Schema::hasColumn('fortune_telling_settings', 'dm_filter_age_enabled')) {
                $table->boolean('dm_filter_age_enabled')->default(false)->after('dm_foreigner_detect_basis');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'dm_age_min')) {
                $table->unsignedTinyInteger('dm_age_min')->nullable()->after('dm_filter_age_enabled');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'dm_age_max')) {
                $table->unsignedTinyInteger('dm_age_max')->nullable()->after('dm_age_min');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'dm_age_unknown_action')) {
                $table->string('dm_age_unknown_action', 10)->default('send')->after('dm_age_max');
            }

            // 🗂️ หมวดข้อความชวนที่ปิดอยู่ (pickActive จะไม่สุ่มมา)
            if (! Schema::hasColumn('fortune_telling_settings', 'invite_disabled_categories')) {
                $table->json('invite_disabled_categories')->nullable()->after('dm_age_unknown_action');
            }
        });
    }

    /**
     * ย้อนกลับ — ลบคอลัมน์ที่เพิ่ม (เช็คก่อนลบ)
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach ([
                'dm_send_to_foreigners',
                'dm_foreigner_detect_basis',
                'dm_filter_age_enabled',
                'dm_age_min',
                'dm_age_max',
                'dm_age_unknown_action',
                'invite_disabled_categories',
            ] as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
