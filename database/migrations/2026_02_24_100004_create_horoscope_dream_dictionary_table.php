<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง horoscope_dream_dictionary — พจนานุกรมทำนายฝัน 100+ สัญลักษณ์
     *
     * เก็บสัญลักษณ์ฝัน + ความหมาย + เลขเด็ด + ระดับลางบอกเหตุ
     * ใช้ FULLTEXT index สำหรับค้นหาคำไทย
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('horoscope_dream_dictionary')) {
            return;
        }

        Schema::create('horoscope_dream_dictionary', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('horoscope_dream_categories')
                ->onDelete('cascade');

            $table->string('keyword_th', 200)->comment('คำค้นหาฝันภาษาไทย เช่น เสือ, งู, น้ำท่วม');
            $table->text('meaning_th')->comment('ความหมายของฝัน');
            $table->json('lucky_numbers')->nullable()->comment('เลขเด็ด เช่น [3,7,21,37]');
            $table->boolean('is_good_omen')->default(true)->comment('ลางดี (true) หรือ ลางร้าย (false)');
            $table->unsignedTinyInteger('intensity')->default(3)->comment('ระดับความรุนแรง 1-5');
            $table->unsignedInteger('search_count')->default(0)->comment('จำนวนครั้งที่ถูกค้นหา');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['category_id', 'is_active'], 'dream_dict_cat_idx');
            $table->index('search_count', 'dream_dict_popular_idx');
        });

        // เพิ่ม FULLTEXT index สำหรับค้นหาภาษาไทย (ต้องใช้ raw SQL)
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE horoscope_dream_dictionary ADD FULLTEXT INDEX dream_dict_keyword_ft (keyword_th)'
        );
    }

    /**
     * ลบตาราง horoscope_dream_dictionary
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('horoscope_dream_dictionary');
    }
};
