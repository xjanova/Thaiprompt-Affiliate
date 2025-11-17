<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง message_entities สำหรับเก็บข้อมูล entities ที่ดึงออกมาจากข้อความ
     *
     * Entities ประเภท:
     * - PRODUCT: ชื่อสินค้า
     * - ISSUE: ปัญหาหรือค่า complaint
     * - LOCATION: สถานที่ (จังหวัด, เมือง)
     * - QUANTITY: จำนวน (เลข, ตัวเลข)
     * - TIME: เวลา (วัน, เดือน, ปี)
     * - PERSON: ชื่อบุคคล
     * - ORGANIZATION: ชื่อบริษัท/องค์กร
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('message_entities')) {
            return;
        }

        Schema::create('message_entities', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('message_sentiment_id')
                ->constrained('message_sentiments')
                ->onDelete('cascade');

            $table->foreignId('line_bot_keyword_id')
                ->nullable()
                ->constrained('line_bot_keywords')
                ->onDelete('set null');

            // Entity information
            $table->enum('entity_type', [
                'PRODUCT',
                'ISSUE',
                'LOCATION',
                'QUANTITY',
                'TIME',
                'PERSON',
                'ORGANIZATION',
                'PRICE',
                'EMAIL',
                'PHONE',
                'PAYMENT_METHOD',
                'DELIVERY_STATUS',
                'ORDER_STATUS',
                'CUSTOM'
            ])->index();

            $table->string('entity_value')->index(); // ค่าที่ดึงออกมา (เช่น "Bangkok", "500 บาท")
            $table->string('display_name')->nullable(); // ชื่อที่แสดง (เช่น "กรุงเทพ" สำหรับ "Bangkok")
            $table->text('entity_text')->nullable(); // ข้อความต้นฉบับในอักขระที่ดึง
            $table->integer('start_position')->nullable(); // ตำแหน่งเริ่มต้นในข้อความ
            $table->integer('end_position')->nullable(); // ตำแหน่งสิ้นสุดในข้อความ

            // Confidence & Metadata
            $table->float('confidence')->default(0.95); // ความมั่นใจ 0-1
            $table->enum('entity_source', ['LEXICON', 'PATTERN', 'ML', 'MANUAL'])->default('LEXICON');
            $table->json('metadata')->nullable(); // ข้อมูลเพิ่มเติม (เช่น category, tags)
            $table->boolean('is_primary')->default(false); // Entity หลักในข้อความ

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['message_sentiment_id', 'entity_type']);
            $table->index(['entity_type', 'entity_value']);
        });
    }

    /**
     * ลบตาราง message_entities
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('message_entities');
    }
};
