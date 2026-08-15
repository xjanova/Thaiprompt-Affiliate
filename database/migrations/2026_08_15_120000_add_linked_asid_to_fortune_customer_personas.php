<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🔗 (2026-08-15) กุญแจที่ใช้เย็บ "ลูกค้าคนเดียวกัน" ข้ามสาขา
 *
 * ปัญหา: Facebook ให้ PSID คนละตัวต่อเพจ → คนเดียวทัก 3 สาขา = persona 3 ใบ
 *        ความจำแม่หมอเลยเริ่มนับหนึ่งใหม่ทุกสาขา และข้อมูลบวมโดยไม่มีประโยชน์
 *
 * ทางแก้ที่ไม่ต้องพึ่ง Business Manager (ids_for_pages โดนล็อกอยู่):
 *   ASID (App-Scoped ID) จากการล็อกอินด้วย Facebook **ผูกกับแอป ไม่ใช่เพจ**
 *   → คนเดียวกันล็อกอินจากสาขาไหนก็ได้ ASID ตัวเดียวกันเสมอ
 *   เก็บไว้ที่นี่ = จุดนัดพบของ persona ทุกสาขา
 *
 * ⚠️ nullable เสมอ — ลูกค้าที่ไม่เคยล็อกอินก็ยังใช้งานได้ปกติแบบแยกรายสาขา
 *    ห้ามบังคับ ไม่งั้นคนที่คุยแชทอย่างเดียว (ส่วนใหญ่) จะพัง
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_customer_personas')) {
            return;
        }

        // ⚠️ ALTER TABLE — เช็คทีละคอลัมน์ ห้าม hasTable() + return
        if (! Schema::hasColumn('fortune_customer_personas', 'linked_asid')) {
            Schema::table('fortune_customer_personas', function (Blueprint $table) {
                $table->string('linked_asid', 64)
                    ->nullable()
                    ->after('platform_user_id');

                // ใช้หา "พี่น้องคนเดียวกันในสาขาอื่น" → ต้องมี index ไม่งั้นสแกนทั้งตาราง
                $table->index('linked_asid', 'fcp_linked_asid_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_customer_personas')
            || ! Schema::hasColumn('fortune_customer_personas', 'linked_asid')) {
            return;
        }

        Schema::table('fortune_customer_personas', function (Blueprint $table) {
            $table->dropIndex('fcp_linked_asid_idx');
            $table->dropColumn('linked_asid');
        });
    }
};
