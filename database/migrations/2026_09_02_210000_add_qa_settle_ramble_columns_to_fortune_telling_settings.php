<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มค่าตั้ง "หน้าต่างรอลูกค้าพิมพ์จบ" (settle window) ลง fortune_telling_settings
     *
     * ต้นตอ FTU-260902-V9628: ลูกค้าเล่าเรื่องยาวเป็นชิ้นๆ ห่างกัน 15-47 วินาที
     * แต่ settle window ตั้งไว้ 10 วินาที → ทุกชิ้นถูกนับเป็นคำถามใหม่
     * บอทตอบคำทำนายเต็ม 500-1,300 ตัวอักษรทุกชิ้น (9 ครั้งใน 25 นาที) = ไม่มืออาชีพ
     *
     * ⚠️ `celtic_qa_settle_seconds` / `pro_session_settle_seconds` ถูก "อ่าน" ในโค้ดมาตั้งแต่
     *    2026-06-22 / 2026-08-17 แต่ **ไม่เคยมีคอลัมน์จริง** → ตกไปใช้ `?? 10` ตลอด
     *    ปรับจากหน้าแอดมินไม่ได้เลย migration นี้ทำให้ปรับได้จริง
     *
     * ⚠️ นี่คือ ALTER TABLE (เพิ่มคอลัมน์) — ห้ามใช้ Schema::hasTable() + return
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // หน้าต่างพื้นฐาน — ลูกค้าถามข้อเดียวแล้วหยุด ต้องได้คำตอบไว (ค่าเดิมที่ hardcode ไว้)
            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_qa_settle_seconds')) {
                $table->unsignedSmallInteger('celtic_qa_settle_seconds')->default(10)
                    ->comment('Celtic Q&A: รอกี่วินาทีหลังลูกค้าหยุดพิมพ์ ก่อนรวบตอบ (0 = ปิด)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'pro_session_settle_seconds')) {
                $table->unsignedSmallInteger('pro_session_settle_seconds')->default(10)
                    ->comment('Pro Session / Deep 39: รอกี่วินาทีหลังลูกค้าหยุดพิมพ์ ก่อนรวบตอบ (0 = ปิด)');
            }

            // หน้าต่างยาว — ใช้เมื่อจับได้ว่าลูกค้า "กำลังเล่ายาว" (พิมพ์ต่อโดยยังไม่ได้อ่านคำตอบ)
            if (! Schema::hasColumn('fortune_telling_settings', 'qa_settle_ramble_seconds')) {
                $table->unsignedSmallInteger('qa_settle_ramble_seconds')->default(50)
                    ->comment('คนเล่ายาว: ขยายหน้าต่างรอเป็นกี่วินาที (0 = ไม่ขยาย ใช้ค่าพื้นฐาน)');
            }

            // เพดานแข็ง — พิมพ์ไม่หยุดจริงๆ ก็ต้องตอบ ห้ามเงียบไม่มีที่สิ้นสุด
            if (! Schema::hasColumn('fortune_telling_settings', 'qa_settle_max_seconds')) {
                $table->unsignedSmallInteger('qa_settle_max_seconds')->default(180)
                    ->comment('เพดานรวมนับจากข้อความแรกในชุด — ครบแล้วตอบทันทีแม้ยังพิมพ์อยู่');
            }

            // ตอบสั้นแบบรับฟังระหว่างที่ลูกค้ายังเล่าไม่จบ (คำวิเคราะห์เต็มรอตอนเธอหยุด)
            if (! Schema::hasColumn('fortune_telling_settings', 'qa_ramble_brief_reply')) {
                $table->boolean('qa_ramble_brief_reply')->default(true)
                    ->comment('คนเล่ายาว: ตอบสั้นรับฟัง 2-3 บรรทัด แทนคำทำนายเต็ม');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach ([
                'celtic_qa_settle_seconds',
                'pro_session_settle_seconds',
                'qa_settle_ramble_seconds',
                'qa_settle_max_seconds',
                'qa_ramble_brief_reply',
            ] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
