<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม columns สำหรับ conversational fortune telling flow
 *
 * รองรับ flow:
 * 1. ดูดวงพื้นฐานฟรี (ดึงโปรไฟล์ทำนายเบื้องต้น)
 * 2. เสนอดูดวงละเอียด 49 บาท (ถามวันเกิด + 3 คำถาม)
 * 3. สร้างบิลรอชำระ + unique amount
 * 4. SMS match → ส่งคำทำนายผ่าน Messenger
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            // สถานะ conversation: new, basic_done, collecting_birthdate, collecting_questions, pending_payment, paid, completed
            if (! Schema::hasColumn('fortune_readings', 'conversation_status')) {
                $table->string('conversation_status', 30)->default('new')->after('reading_type');
            }

            // เก็บ state ของ conversation (คำถามที่เก็บไว้, ข้อมูลอื่นๆ)
            if (! Schema::hasColumn('fortune_readings', 'conversation_state')) {
                $table->json('conversation_state')->nullable()->after('conversation_status');
            }

            // เชื่อมกับ unique_payment_amounts table
            if (! Schema::hasColumn('fortune_readings', 'unique_payment_amount_id')) {
                $table->unsignedBigInteger('unique_payment_amount_id')->nullable()->after('sms_notification_id');
            }

            // คำทำนายพื้นฐาน (ฟรี) แยกจากคำทำนายละเอียด
            if (! Schema::hasColumn('fortune_readings', 'basic_response')) {
                $table->text('basic_response')->nullable()->after('ai_response');
            }

            // คำทำนายละเอียด (เสียเงิน)
            if (! Schema::hasColumn('fortune_readings', 'deep_response')) {
                $table->text('deep_response')->nullable()->after('basic_response');
            }

            // Index สำหรับค้นหา pending payments
            $indexName = 'fr_conv_status_fb_user_idx';
            $indexNames = collect(Schema::getIndexes('fortune_readings'))->pluck('name')->toArray();
            if (! in_array($indexName, $indexNames)) {
                $table->index(['conversation_status', 'facebook_user_id'], $indexName);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            $table->dropIndex('fr_conv_status_fb_user_idx');

            if (Schema::hasColumn('fortune_readings', 'conversation_status')) {
                $table->dropColumn('conversation_status');
            }
            if (Schema::hasColumn('fortune_readings', 'conversation_state')) {
                $table->dropColumn('conversation_state');
            }
            if (Schema::hasColumn('fortune_readings', 'unique_payment_amount_id')) {
                $table->dropColumn('unique_payment_amount_id');
            }
            if (Schema::hasColumn('fortune_readings', 'basic_response')) {
                $table->dropColumn('basic_response');
            }
            if (Schema::hasColumn('fortune_readings', 'deep_response')) {
                $table->dropColumn('deep_response');
            }
        });
    }
};
