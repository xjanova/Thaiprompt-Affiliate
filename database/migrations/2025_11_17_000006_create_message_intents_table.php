<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง message_intents สำหรับเก็บข้อมูล intent ที่ตรวจพบจากข้อความ
     *
     * Intent ประเภท:
     * - COMPLAINT: ข้อร้องเรียน
     * - INQUIRY: คำถาม/สอบถาม
     * - PURCHASE: ต้องการซื้อ
     * - FEEDBACK: ข้อเสนอแนะ
     * - SUPPORT: ขอความช่วยเหลือ
     * - INFORMATION: ต้องการข้อมูล
     * - PAYMENT: เรื่องการชำระเงิน
     * - DELIVERY: เรื่องการจัดส่ง
     * - RETURN: ต้องการคืนสินค้า
     * - WARRANTY: เรื่องการรับประกัน
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('message_intents')) {
            return;
        }

        Schema::create('message_intents', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('message_sentiment_id')
                ->constrained('message_sentiments')
                ->onDelete('cascade');

            $table->foreignId('line_bot_keyword_id')
                ->nullable()
                ->constrained('line_bot_keywords')
                ->onDelete('set null');

            // Primary intent
            $table->enum('primary_intent', [
                'COMPLAINT',
                'INQUIRY',
                'PURCHASE',
                'FEEDBACK',
                'SUPPORT',
                'INFORMATION',
                'PAYMENT',
                'DELIVERY',
                'RETURN',
                'WARRANTY',
                'SUGGESTION',
                'GREETING',
                'GOODBYE',
                'THANKS',
                'APOLOGY',
                'OTHER'
            ])->index();

            // Additional intents (JSON array for multiple intents)
            $table->json('secondary_intents')->nullable(); // เช่น ['PAYMENT', 'DELIVERY']
            $table->float('primary_confidence')->default(0.90); // ความมั่นใจของ primary intent
            $table->json('intent_scores')->nullable(); // คะแนนแต่ละ intent

            // Intent source
            $table->enum('intent_source', ['KEYWORDS', 'PATTERNS', 'ML', 'MANUAL'])->default('KEYWORDS');

            // Context & Related Info
            $table->text('intent_explanation')->nullable(); // คำอธิบาย intent
            $table->json('trigger_keywords')->nullable(); // คำหลักที่ทริกเกอร์ intent นี้
            $table->json('suggested_actions')->nullable(); // แนะนำการดำเนินการ

            // Routing & Priority
            $table->enum('suggested_department', [
                'SALES',
                'SUPPORT',
                'BILLING',
                'DELIVERY',
                'QUALITY',
                'ACCOUNT',
                'OTHER'
            ])->nullable();

            $table->enum('priority_level', [
                'LOW',
                'NORMAL',
                'HIGH',
                'URGENT'
            ])->default('NORMAL');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['message_sentiment_id', 'primary_intent']);
            $table->index(['primary_intent', 'priority_level']);
        });
    }

    /**
     * ลบตาราง message_intents
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('message_intents');
    }
};
