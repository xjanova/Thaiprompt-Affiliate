<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เปลี่ยนเกณฑ์ "คอมเมนต์รัว" จากตัวแบน → เรดาร์เตือน (notify-only)
     *
     * เหตุผลจากข้อมูลจริงบน prod (2026-08-09):
     * - สแปมเมอร์จริง 22 จาก 25 คน (88%) **โพสต์ครั้งเดียวจบ** → เกณฑ์คอมรัวจับไม่ได้เลย
     * - แฟนเพจสายมูคอม 4 ครั้งใน 63 วินาที (พิธีกรรม "รับแสง/น้อมรับ") → รัวกว่าสแปมอีก
     * - ของจริงที่แยกได้แม่นคือ "มีลิงก์ไหม" (จับสแปม 30/30) ซึ่งมีด่านแยกอยู่แล้ว
     * ⇒ ปล่อยให้ flood แบนคน = พลาดสแปม 88% แล้วไปโดนลูกค้าแทน (เกิดจริงมาแล้ว 3 ราย)
     *
     * default = notify: บันทึกเข้าหน้าจัดการ + เตือนแอดมิน แต่ **ไม่บล็อกใคร**
     * เจ้าของเปลี่ยนเป็น block เองได้ถ้าเจอเคสรุมจริง
     *
     * threshold ขยับ 5 → 15 ด้วย เพราะ 5 ต่ำกว่าพฤติกรรมแฟนปกติ
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'comment_flood_action')) {
                $table->string('comment_flood_action', 20)
                    ->default('notify')
                    ->comment('notify = แค่เตือน ไม่บล็อก (default) / block = บล็อกทันที');
            }
        });

        // ยกเกณฑ์ให้พ้นพฤติกรรมแฟนปกติ — แก้เฉพาะที่ยังเป็นค่า default เดิม
        // (ถ้าเจ้าของตั้งเองไว้แล้วห้ามไปทับ)
        DB::table('fortune_telling_settings')
            ->where('comment_flood_threshold', 5)
            ->update(['comment_flood_threshold' => 15]);
    }

    /**
     * ย้อนกลับ
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'comment_flood_action')) {
                $table->dropColumn('comment_flood_action');
            }
        });
    }
};
