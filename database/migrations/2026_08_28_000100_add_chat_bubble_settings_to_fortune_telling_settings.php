<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มสวิตช์ "ตอบเป็นหลายกล่องบับเบิ้ล" (2026-08-28)
 *
 * เจ้าของสั่ง: "อยากให้แยกกล่องบับเบิ้ลตอบ อย่าตอบยาว ๆ ทำเป็นหลายกล่องเพื่อให้เหมือนคนตอบ
 * ค่อย ๆ ส่งห่างกันอย่างน้อย 5-10 วินาที แต่ละกล่องบับเบิ้ล"
 *
 * - fortune_chat_bubbles_fb      : เปิด/ปิดฝั่ง Facebook  (default **เปิด**)
 * - fortune_chat_bubbles_line    : เปิด/ปิดฝั่ง LINE       (default **ปิด**)
 * - fortune_chat_bubble_gap_min  : ระยะห่างต่ำสุดระหว่างกล่อง (วินาที)
 * - fortune_chat_bubble_gap_max  : ระยะห่างสูงสุด (สุ่มระหว่าง min-max ไม่ให้เท่ากันเป๊ะ)
 * - fortune_chat_bubble_max      : จำนวนกล่องมากสุดต่อ 1 คำตอบ
 *
 * 🚨 ทำไม LINE ต้องแยกสวิตช์และ default ปิด
 *    LINE นับโควตา **ต่อ message object** — แพลนปัจจุบัน 300 push/เดือน
 *    และเคยหมดเกลี้ยงจนบอทเงียบทั้งช่องทางมาแล้ว (2026-08-26, 429 ปิดปาก webhook)
 *    ผ่า 1 คำตอบเป็น 3 กล่อง = โควตาหมดเร็วขึ้น ~3 เท่า
 *    ⇒ โค้ดรองรับไว้ครบ รอเจ้าของอัปแพลนแล้วค่อยเปิดเอง (เจ้าของสั่งเองแบบนี้ 2026-08-28)
 *    หมายเหตุ: กล่องแรกฝั่ง LINE ใช้ reply token (ฟรี ไม่กินโควตา) เฉพาะกล่อง 2..N ที่ต้อง push
 *
 * ⚠️ เป็น ALTER TABLE (เพิ่มคอลัมน์) — ห้ามใช้ Schema::hasTable() + return
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ตั้งค่าบับเบิ้ล
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_chat_bubbles_fb')) {
                $table->boolean('fortune_chat_bubbles_fb')
                    ->default(true)
                    ->comment('FB: ตอบคำทำนายเป็นหลายกล่อง เว้นระยะเหมือนคนพิมพ์');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_chat_bubbles_line')) {
                $table->boolean('fortune_chat_bubbles_line')
                    ->default(false)
                    ->comment('LINE: แยกกล่อง (default ปิด — กล่อง 2..N กินโควตา push)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_chat_bubble_gap_min')) {
                $table->unsignedSmallInteger('fortune_chat_bubble_gap_min')
                    ->default(5)
                    ->comment('ระยะห่างต่ำสุดระหว่างกล่อง (วินาที)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_chat_bubble_gap_max')) {
                $table->unsignedSmallInteger('fortune_chat_bubble_gap_max')
                    ->default(10)
                    ->comment('ระยะห่างสูงสุดระหว่างกล่อง (วินาที)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_chat_bubble_max')) {
                $table->unsignedTinyInteger('fortune_chat_bubble_max')
                    ->default(4)
                    ->comment('จำนวนกล่องมากสุดต่อ 1 คำตอบ');
            }
        });
    }

    /**
     * ถอนคอลัมน์ตั้งค่าบับเบิ้ล
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach ([
                'fortune_chat_bubbles_fb',
                'fortune_chat_bubbles_line',
                'fortune_chat_bubble_gap_min',
                'fortune_chat_bubble_gap_max',
                'fortune_chat_bubble_max',
            ] as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
