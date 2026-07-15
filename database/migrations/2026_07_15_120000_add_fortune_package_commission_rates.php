<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ค่าแนะนำ "แยกตามแพคเกจ" ในตาราง fortune_telling_settings
     *
     * เดิม: ค่าแนะนำเป็นจำนวนเดียวทั้งระบบ (L1=10, L2=1) ไม่ว่าลูกค้าจะซื้อ
     *       ดูพื้นดวง 39฿ หรือ Celtic Cross 99฿ ก็ได้เท่ากัน
     *
     * ใหม่: แยกอัตราตาม reading_type ของบิล
     *       - deep (39฿)          → สายตรง 5฿, ไม่มีชั้นหลาน
     *       - celtic_cross (99฿)  → สายตรง 10฿, ชั้นหลาน 2฿
     *
     * ⚠️ default OFF (fortune_pkg_rates_enabled = false)
     *    → พฤติกรรมเดิม 100% จนกว่าแอดมินจะเปิดเอง
     *
     * ⚠️ IMPORTANT: ห้ามใช้ Schema::hasTable() + return
     * เพราะจะทำให้คอลัมน์ใหม่ไม่ถูกสร้าง!
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // สวิตช์หลัก — ปิดไว้ก่อน ใช้อัตราเดิมทั้งระบบ
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_pkg_rates_enabled')) {
                $table->boolean('fortune_pkg_rates_enabled')
                    ->default(false)
                    ->after('fortune_level2_commission_amount');
            }

            // ===== ดูพื้นดวง (reading_type = 'deep', ราคา 39฿) =====
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_deep_l1_amount')) {
                $table->decimal('fortune_deep_l1_amount', 10, 2)
                    ->default(5)
                    ->after('fortune_pkg_rates_enabled');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_deep_l2_enabled')) {
                $table->boolean('fortune_deep_l2_enabled')
                    ->default(false)
                    ->after('fortune_deep_l1_amount');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_deep_l2_amount')) {
                $table->decimal('fortune_deep_l2_amount', 10, 2)
                    ->default(0)
                    ->after('fortune_deep_l2_enabled');
            }

            // ===== Celtic Cross (reading_type = 'celtic_cross', ราคา 99฿) =====
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_celtic_l1_amount')) {
                $table->decimal('fortune_celtic_l1_amount', 10, 2)
                    ->default(10)
                    ->after('fortune_deep_l2_amount');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_celtic_l2_enabled')) {
                $table->boolean('fortune_celtic_l2_enabled')
                    ->default(true)
                    ->after('fortune_celtic_l1_amount');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_celtic_l2_amount')) {
                $table->decimal('fortune_celtic_l2_amount', 10, 2)
                    ->default(2)
                    ->after('fortune_celtic_l2_enabled');
            }

            // ===== คลิปบรรยายแผน (โฮสต์ที่ จันทรา.online) =====
            // ปิดไว้ก่อน — เปิดเมื่ออัปคลิปขึ้นเว็บแล้ว
            if (! Schema::hasColumn('fortune_telling_settings', 'plan_video_enabled')) {
                $table->boolean('plan_video_enabled')
                    ->default(false)
                    ->after('fortune_celtic_l2_amount');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'plan_video_url')) {
                $table->string('plan_video_url', 500)
                    ->nullable()
                    ->after('plan_video_enabled');
            }

            // ส่งคลิปให้คนที่แอดไลน์ใหม่อัตโนมัติหรือไม่ (ปิดไว้ก่อน)
            if (! Schema::hasColumn('fortune_telling_settings', 'plan_video_send_on_welcome')) {
                $table->boolean('plan_video_send_on_welcome')
                    ->default(false)
                    ->after('plan_video_url');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $columns = [
                'fortune_pkg_rates_enabled',
                'fortune_deep_l1_amount',
                'fortune_deep_l2_enabled',
                'fortune_deep_l2_amount',
                'fortune_celtic_l1_amount',
                'fortune_celtic_l2_enabled',
                'fortune_celtic_l2_amount',
                'plan_video_enabled',
                'plan_video_url',
                'plan_video_send_on_welcome',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
