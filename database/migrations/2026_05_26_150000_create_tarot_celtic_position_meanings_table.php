<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง tarot_celtic_position_meanings
 *
 * ตารางนี้เก็บคำทำนายไพ่ยิปซีในรูปแบบ Celtic Cross แบบละเอียดที่สุด
 * ครอบคลุมทุกมิติ:
 * - ไพ่ 78 ใบ
 * - 10 ตำแหน่ง Celtic Cross
 * - 2 ทิศทาง (หัวตั้ง/กลับหัว)
 * - 5 หมวดหมู่การทำนาย (ความรัก, การงาน/เงิน, พัฒนาตนเอง, สุขภาพ, ทั่วไป)
 *
 * รวม: 78 × 10 × 2 × 5 = 7,800 entries
 *
 * แต่ละ entry มี:
 * - core_meaning_th: ความหมายหลัก
 * - detailed_interpretation_th: คำทำนายแบบละเอียด
 * - adjacent_influences: อิทธิพลของไพ่ข้างเคียง (JSON)
 * - secret_keys_th: ความลับ/เคล็ดลับการอ่าน
 * - advice_th: คำแนะนำ
 */
return new class extends Migration
{
    /**
     * สร้างตาราง tarot_celtic_position_meanings
     */
    public function up(): void
    {
        // ✅ เช็คตารางก่อนสร้าง (CREATE TABLE pattern)
        if (Schema::hasTable('tarot_celtic_position_meanings')) {
            return;
        }

        Schema::create('tarot_celtic_position_meanings', function (Blueprint $table) {
            $table->id();

            // Foreign key ไปยังตาราง tarot_cards
            $table->foreignId('card_id')
                ->constrained('tarot_cards')
                ->onDelete('cascade')
                ->comment('รหัสไพ่ - อ้างถึง tarot_cards.id');

            // ตำแหน่งใน Celtic Cross (1-10)
            $table->unsignedTinyInteger('position_number')
                ->comment('หมายเลขตำแหน่งใน Celtic Cross (1-10)');

            $table->string('position_slug', 50)
                ->comment('slug ของตำแหน่ง: present, challenge, past, future, above, below, advice, external, hopes, outcome');

            $table->string('position_name_th', 100)
                ->comment('ชื่อตำแหน่งภาษาไทย');

            // ทิศทางของไพ่
            $table->boolean('is_reversed')
                ->default(false)
                ->comment('false = หัวตั้ง (upright), true = กลับหัว (reversed)');

            // หมวดหมู่การทำนาย (nullable สำหรับ general/ทั่วไป)
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('tarot_reading_categories')
                ->onDelete('cascade')
                ->comment('หมวดหมู่การทำนาย - null = ทั่วไป (general)');

            $table->string('category_slug', 50)
                ->default('general')
                ->comment('slug ของหมวดหมู่: love-relationships, career-finance, personal-growth, health-wellness, general');

            // ความหมายหลัก (1-2 ประโยค - สำหรับแสดงผลย่อ)
            $table->text('core_meaning_th')
                ->comment('ความหมายหลักของไพ่ในตำแหน่งนี้ (1-2 ประโยค)');

            // คำทำนายแบบละเอียด (เนื้อหายาว - สำหรับให้บอทใช้ทำนาย)
            $table->text('detailed_interpretation_th')
                ->comment('คำทำนายแบบละเอียด - บอทจะใช้ข้อมูลนี้ในการทำนาย');

            // อิทธิพลของไพ่ข้างเคียง (JSON)
            // โครงสร้าง: {"major_arcana": "...", "wands": "...", "cups": "...", "swords": "...", "pentacles": "...", "court_cards": "..."}
            $table->json('adjacent_influences')
                ->nullable()
                ->comment('อิทธิพลของไพ่ข้างเคียงในตำแหน่งอื่น แบ่งตามประเภทไพ่');

            // ความลับ/เคล็ดลับการอ่าน
            $table->text('secret_keys_th')
                ->nullable()
                ->comment('ความลับ/เคล็ดลับการอ่านไพ่ในตำแหน่งและทิศทางนี้');

            // คำแนะนำ
            $table->text('advice_th')
                ->comment('คำแนะนำสำหรับผู้ถามในสถานการณ์นี้');

            // คำสำคัญ (keywords) - JSON array สำหรับช่วยบอท
            $table->json('keywords_th')
                ->nullable()
                ->comment('คำสำคัญในรูปแบบ JSON array');

            // ระดับความรุนแรง/ความสำคัญ (1-5) - ใช้สำหรับ AI weighting
            $table->unsignedTinyInteger('intensity_level')
                ->default(3)
                ->comment('ระดับความรุนแรงของพลัง 1-5 (1=อ่อน, 5=รุนแรง)');

            // โทนอารมณ์: positive, neutral, negative, mixed
            $table->enum('emotional_tone', ['positive', 'neutral', 'negative', 'mixed'])
                ->default('neutral')
                ->comment('โทนอารมณ์ของคำทำนาย');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Unique constraint - ไพ่ใบเดียวกัน, ตำแหน่งเดียวกัน, ทิศทางเดียวกัน, หมวดเดียวกัน → 1 entry
            $table->unique(
                ['card_id', 'position_number', 'is_reversed', 'category_slug'],
                'celtic_meaning_unique'
            );

            // Indexes สำหรับ query performance
            $table->index(['card_id', 'position_number'], 'celtic_card_position_idx');
            $table->index(['position_number', 'is_reversed'], 'celtic_pos_reversed_idx');
            $table->index('category_slug', 'celtic_category_idx');
            $table->index('is_active', 'celtic_active_idx');
        });
    }

    /**
     * ลบตาราง tarot_celtic_position_meanings
     */
    public function down(): void
    {
        Schema::dropIfExists('tarot_celtic_position_meanings');
    }
};
