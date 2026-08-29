<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🤝 (2026-08-29 FTU-260829-M9469) ช่วง "หลังบทสรุป" ของ Celtic 99 — คุยต่อได้ ไม่วางสายทันที
     *
     * ⚠️ ห้ามใช้ Schema::hasTable() + return — เป็นการเพิ่มคอลัมน์ (ALTER TABLE)
     *
     * ต้นตอ (บิล FTU-260829-M9469 / reading 11760):
     *   ลูกค้าจ่าย 99 บาท ถามรัว 11 ข้อใน 15 นาที พอครบเวลาบอทยิงบทสรุปแล้ววางสายทันที
     *   ลูกค้าพิมพ์คำถามข้อ 12 มาช้ากว่านาฬิกา 17 วินาที = โดนตัดบทกลางวง
     *   สแกน 30 วัน: 43 จาก 77 บิล (56%) ยังถามอยู่ตอนนาทีที่ 14+ → โดนแบบเดียวกันทั้งหมด
     *
     * สเปกเจ้าของ 2026-08-29:
     *   "บทสรุปยัง 15 นาทีหรือตามค่าที่ตั้งเหมือนเดิม เพียงแต่ยังคุยต่อได้ ในเรื่องการทำนายรอบเดียวกัน
     *    ไม่เกิน 30 นาที จากคำถามแรก หรือลูกค้ามีสัญญาณวางสายเอง เช่นขอบคุณ บอทก็กล่าวลาและอวยพร
     *    เพื่อความประทับใจที่สุด แต่ถ้าเปิดบิลก่อนก็ทำได้เป็นรอบใหม่"
     *
     * ⚠️ คอลัมน์นี้ **ไม่ได้เลื่อนเวลาบทสรุป** — บทสรุปยังยิงที่ celtic_cross_qa_window_minutes เท่าเดิม
     *    ที่เปลี่ยนคือ "หลังบทสรุปแล้วไม่ clear pro session ทิ้ง" เท่านั้น
     *
     * default = เปิด เพราะเจ้าของสั่งให้แก้พฤติกรรมนี้โดยตรง (ไม่ใช่ฟีเจอร์ทดลอง)
     *   ถ้าพังให้ปิดที่ celtic_aftercare_enabled = 0 → กลับพฤติกรรมเดิม 100%
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_aftercare_enabled')) {
                $table->boolean('celtic_aftercare_enabled')
                    ->default(true)
                    ->after('celtic_cross_qa_window_minutes')
                    ->comment('เปิดช่วงคุยต่อหลังบทสรุป Celtic 99 (ปิด = ส่งบทสรุปแล้ววางสายทันทีแบบเดิม)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_aftercare_total_minutes')) {
                $table->unsignedSmallInteger('celtic_aftercare_total_minutes')
                    ->default(30)
                    ->after('celtic_aftercare_enabled')
                    ->comment('เพดานรวมของรอบทำนาย (นาที) นับจากคำถามแรก — ต้องมากกว่า qa_window ถึงจะมีช่วงคุยต่อ');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_aftercare_idle_minutes')) {
                $table->unsignedSmallInteger('celtic_aftercare_idle_minutes')
                    ->default(10)
                    ->after('celtic_aftercare_total_minutes')
                    ->comment('เงียบกี่นาทีระหว่างช่วงคุยต่อ ถึงให้แม่หมอกล่าวลา+อวยพร');
            }
        });
    }

    /**
     * ลบคอลัมน์ช่วงคุยต่อหลังบทสรุป
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach ([
                'celtic_aftercare_idle_minutes',
                'celtic_aftercare_total_minutes',
                'celtic_aftercare_enabled',
            ] as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
