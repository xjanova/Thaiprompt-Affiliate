<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🌙 (2026-09-21) บิลจันทราที่ถูกสั่งยกเลิก "ก่อน" บิลมาถึงที่นี่
     *
     * เกิดได้จริง: คำขอส่งบิลของจันทราหมดเวลารอ (ฝั่งเรายังทำงานอยู่) → ลูกค้าได้เงินคืน →
     *   จันทราสั่งยกเลิก ซึ่งถึงก่อนบิลจะถูกบันทึก → ตอบ "ไม่รู้จักบิลนี้" → แล้วบิลก็ถูกบันทึกตามมา
     *   = แจกค่าแนะนำให้บิลที่คืนเงินไปแล้ว โดยไม่มีใครดึงคืน
     * แถวนี้คือป้ายกันไว้: บิลเลขนี้ที่มาถึงทีหลังจะถูกบันทึกเป็นยกเลิกทันที ไม่แจกค่าแนะนำ
     */
    public function up(): void
    {
        if (Schema::hasTable('juntra_voided_bills')) {
            return;
        }

        Schema::create('juntra_voided_bills', function (Blueprint $table) {
            $table->id();
            // id รายการตัดเงินฝั่งจันทรา (ตัวเดียวกับเลขบิล JW-{id})
            $table->unsignedBigInteger('juntra_bill_id')->unique();
            $table->string('reason', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('juntra_voided_bills');
    }
};
