<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มที่เก็บ "กล่องคำถามแนะนำ + ปุ่มเลข 1️⃣2️⃣" ลงในแถวคำถาม Celtic
     *
     * ## ทำไมต้องมี
     * กล่องคำถามแนะนำถูกสร้างใน `finalizeCelticAnswer()` แล้วส่งต่อให้ ChannelManager
     * ผ่านตัวแปร `$result['suggestion_box']` / `$result['quick_replies']` เท่านั้น
     * **ไม่เคยถูกเขียนลงที่ไหนเลย** ⇒ มีชีวิตอยู่แค่ใน request เดียว
     *
     * พอ call ส่งล้ม (โควต้า push หมด / LINE ล่ม) ทั้งคำตอบและกล่องตายพร้อมกัน
     * แล้วเส้นกู้ `flushParkedCelticAnswers()` ส่งคืนได้แค่ `$q->response`
     * ⇒ **ลูกค้าไม่มีวันได้เห็นปุ่มถามต่อ ไม่ว่าจะรอนานแค่ไหน** (ไม่มีอะไรเหลือให้กู้)
     *
     * เคสจริง reading 11901 (2026-08-31): กล่องถูกสร้าง 4 ครั้ง (objects=2)
     * แต่ทุก call `success:false` ⇒ ลูกค้าเห็นแค่คำตอบเปล่า ไม่เคยเห็นปุ่มเลย
     *
     * 📖 หลักการ: ของที่ลูกค้าจ่ายเงินแล้ว ห้ามมีชีวิตอยู่แค่ในหน่วยความจำ
     *    — `.claude/LINE_MESSAGING_RULES.md` กฎข้อ 4
     *
     * ⚠️ นี่คือ ALTER TABLE (เพิ่มคอลัมน์) — ห้ามใช้ `Schema::hasTable()` + `return`
     *    ไม่งั้นคอลัมน์ใหม่จะไม่ถูกสร้างบนเครื่องที่มีตารางอยู่แล้ว
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_celtic_questions')) {
            return;
        }

        Schema::table('fortune_celtic_questions', function (Blueprint $table) {
            // ข้อความในกล่องคำถามแนะนำ (สร้างโดย buildCelticSuggestionBox)
            if (! Schema::hasColumn('fortune_celtic_questions', 'suggestion_box')) {
                $table->text('suggestion_box')->nullable()->after('response');
            }

            // ปุ่มเลข 1️⃣2️⃣ — [['label'=>..,'text'=>..], ...] (buildCelticSuggestionButtons)
            if (! Schema::hasColumn('fortune_celtic_questions', 'suggestion_quick_replies')) {
                $table->json('suggestion_quick_replies')->nullable()->after('suggestion_box');
            }
        });
    }

    /**
     * ถอนคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_celtic_questions')) {
            return;
        }

        Schema::table('fortune_celtic_questions', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_celtic_questions', 'suggestion_quick_replies')) {
                $table->dropColumn('suggestion_quick_replies');
            }

            if (Schema::hasColumn('fortune_celtic_questions', 'suggestion_box')) {
                $table->dropColumn('suggestion_box');
            }
        });
    }
};
