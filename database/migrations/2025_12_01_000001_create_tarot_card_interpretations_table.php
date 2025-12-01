<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง tarot_card_interpretations
 *
 * ตารางนี้เก็บคำทำนายที่ปรับแต่งได้สำหรับแต่ละไพ่ในแต่ละหมวดหมู่
 * ทำให้แอดมินสามารถแก้ไขคำทำนายสำหรับแต่ละไพ่ตามหมวดหมู่ที่ต่างกัน
 */
return new class extends Migration
{
    /**
     * สร้างตาราง tarot_card_interpretations
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่ามีตารางอยู่แล้วหรือไม่
        if (Schema::hasTable('tarot_card_interpretations')) {
            return;
        }

        Schema::create('tarot_card_interpretations', function (Blueprint $table) {
            $table->id();

            // Foreign key ไปยังตาราง tarot_cards
            $table->foreignId('card_id')
                ->constrained('tarot_cards')
                ->onDelete('cascade');

            // Foreign key ไปยังตาราง tarot_reading_categories
            $table->foreignId('category_id')
                ->constrained('tarot_reading_categories')
                ->onDelete('cascade');

            // คำทำนายหัวตั้ง
            $table->text('upright_interpretation_th')->nullable()
                ->comment('คำทำนายหัวตั้ง ภาษาไทย');
            $table->text('upright_interpretation_en')->nullable()
                ->comment('คำทำนายหัวตั้ง ภาษาอังกฤษ');

            // คำทำนายกลับหัว
            $table->text('reversed_interpretation_th')->nullable()
                ->comment('คำทำนายกลับหัว ภาษาไทย');
            $table->text('reversed_interpretation_en')->nullable()
                ->comment('คำทำนายกลับหัว ภาษาอังกฤษ');

            // คำแนะนำพิเศษสำหรับหมวดนี้
            $table->text('advice_th')->nullable()
                ->comment('คำแนะนำ ภาษาไทย');
            $table->text('advice_en')->nullable()
                ->comment('คำแนะนำ ภาษาอังกฤษ');

            $table->timestamps();

            // Unique constraint - แต่ละไพ่มีได้เพียง 1 คำทำนายต่อหมวด
            $table->unique(['card_id', 'category_id'], 'card_category_unique');
        });
    }

    /**
     * ลบตาราง tarot_card_interpretations
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tarot_card_interpretations');
    }
};
