<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม delivery tracking ในตาราง fortune_celtic_questions
     *
     * 🐛 (2026-05-28) Fix: ลูกค้าจ่าย 99฿ — AI สร้างคำตอบสำเร็จ + markCelticAnswered
     *   แต่ FB/LINE push ล้มเหลว → ลูกค้าไม่ได้รับ (DB ว่า "ตอบแล้ว" ≠ delivered)
     *   เคสจริง: บิล FTU-260528-E8815 (reading 4009) — คำตอบ 908 ตัวอักษรอยู่ใน DB แต่ลูกค้าเห็นแค่ "ติดขัด"
     *
     *   - delivered_at: timestamp ที่ push สำเร็จจริง (null = ยังไม่ถึงลูกค้า → cron re-deliver)
     *   - delivery_attempts: นับจำนวนครั้งที่พยายามส่ง (cap กัน loop)
     */
    public function up(): void
    {
        Schema::table('fortune_celtic_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_celtic_questions', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('answered_at');
            }
            if (! Schema::hasColumn('fortune_celtic_questions', 'delivery_attempts')) {
                $table->unsignedTinyInteger('delivery_attempts')->default(0)->after('delivered_at');
            }
        });

        // index ช่วย cron query หา answered-but-undelivered เร็วขึ้น
        Schema::table('fortune_celtic_questions', function (Blueprint $table) {
            if (! $this->indexExists('fortune_celtic_questions', 'fcq_undelivered_idx')) {
                $table->index(['delivered_at', 'answered_at'], 'fcq_undelivered_idx');
            }
        });

        // ⚠️ Backfill — ถือว่าคำตอบเก่าทั้งหมด "ส่งถึงลูกค้าแล้ว" (delivered_at = answered_at)
        //   ป้องกัน cron re-deliver ส่งคำตอบเก่าที่ส่งสำเร็จไปแล้วซ้ำ (spam) ตอน deploy ครั้งแรก
        //   delivery tracking จริงเริ่มนับเฉพาะคำตอบใหม่หลัง deploy เป็นต้นไป
        //   (user spec 2026-05-28: ไม่กู้เคสเก่า "Kt ผ่านไปแล้ว" — สอดคล้องกับ backfill นี้)
        DB::table('fortune_celtic_questions')
            ->whereNotNull('answered_at')
            ->whereNull('delivered_at')
            ->update(['delivered_at' => DB::raw('answered_at')]);
    }

    /**
     * ลบ delivery tracking columns
     */
    public function down(): void
    {
        Schema::table('fortune_celtic_questions', function (Blueprint $table) {
            if ($this->indexExists('fortune_celtic_questions', 'fcq_undelivered_idx')) {
                $table->dropIndex('fcq_undelivered_idx');
            }
            if (Schema::hasColumn('fortune_celtic_questions', 'delivery_attempts')) {
                $table->dropColumn('delivery_attempts');
            }
            if (Schema::hasColumn('fortune_celtic_questions', 'delivered_at')) {
                $table->dropColumn('delivered_at');
            }
        });
    }

    /**
     * เช็คว่ามี index นี้อยู่แล้วหรือยัง (กัน error รันซ้ำ)
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $conn = Schema::getConnection();
            $dbName = $conn->getDatabaseName();

            return (int) $conn->table('information_schema.statistics')
                ->where('table_schema', $dbName)
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->count() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
