<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง slip_verification_logs — ประวัติการตรวจสลิป "ทุกครั้ง" (audit)
     *
     * ต่างจาก slip_verifications (เก็บเฉพาะสลิปที่ตัดบิลสำเร็จ + trans_ref unique กันซ้ำ)
     * ตารางนี้บันทึก "ทุกการตรวจ" รวมที่ล้มเหลว/ซ้ำ/ไม่มีบิล/ถูก pre-check ตัด
     * เพื่อให้แอดมินเห็น: ส่งไป SlipOK จริงไหม / สลิปซ้ำไหม / บิลไหน
     */
    public function up(): void
    {
        // ✅ CREATE TABLE — เช็คก่อนสร้างได้ (ปลอดภัย)
        if (Schema::hasTable('slip_verification_logs')) {
            return;
        }

        Schema::create('slip_verification_logs', function (Blueprint $table) {
            $table->id();

            // บิลที่ตรวจ (null = paid-claim ไม่มีบิล)
            $table->unsignedBigInteger('fortune_reading_id')->nullable()->index()
                ->comment('บิลดูดวงที่ตรวจ (null = ไม่มีบิล/paid-claim)');

            $table->string('platform', 20)->nullable()->comment('facebook / line');
            $table->string('chat_user_id', 64)->nullable()->index()->comment('chat user id ของลูกค้า');

            // บริบทที่ตรวจ
            $table->string('context', 40)->nullable()
                ->comment('returning / no_bill / active_fallback / active_onping ฯลฯ');

            // ส่งไป SlipOK API จริงไหม (false = pre-check Gemini ตัด / ไม่มีสลิป)
            $table->boolean('sent_to_slipok')->default(false)
                ->comment('ยิง SlipOK API จริงไหม');

            // ผลการตัดสิน
            $table->string('decision', 40)->nullable()->index()
                ->comment('approve/duplicate/reject_receiver/reject_amount/no_qr/stale/error/not_slip/no_bill_*');

            $table->boolean('is_duplicate')->default(false)->comment('สลิปซ้ำ (เคยใช้ตัดบิลแล้ว)');
            $table->boolean('to_our_account')->nullable()->comment('โอนเข้าบัญชีร้านไหม');

            // รายละเอียดสลิป (เพื่อ audit — ไม่ unique เพราะเก็บทุกครั้ง)
            $table->string('trans_ref', 64)->nullable()->index()->comment('เลขอ้างอิง (ไม่ unique — audit)');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('sender_name', 191)->nullable();
            $table->string('receiver_account', 64)->nullable();

            $table->string('note', 255)->nullable()->comment('เหตุผล/สรุปผล');

            $table->timestamps();

            $table->index(['decision', 'created_at']);
        });
    }

    /**
     * ลบตาราง slip_verification_logs
     */
    public function down(): void
    {
        Schema::dropIfExists('slip_verification_logs');
    }
};
