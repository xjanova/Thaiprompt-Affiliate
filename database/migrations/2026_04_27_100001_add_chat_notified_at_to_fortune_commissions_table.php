<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่ม chat_notified_at ใน fortune_commissions
     *
     * ทำไมต้องมี:
     * เมื่อ user ได้ค่าแนะนำเข้ากระเป๋า → เราต้องการแจ้งเขาตอนทักแชทมา
     * (FB/LINE policy: push ข้ามวันไม่ฟรี → ใช้ replyMessage ฟรีแทน)
     *
     * Flow:
     * 1. FortuneCommission สร้าง → chat_notified_at = NULL
     * 2. user ทักแชทมา → FCS::processMessage ตรวจ pending commission
     *    → ส่งสรุป "💰 มีรายได้ X บาทเข้ากระเป๋า" → set chat_notified_at = now()
     * 3. ครั้งหน้า user ทัก → query หาเฉพาะ chat_notified_at IS NULL → ไม่ส่งซ้ำ
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_commissions', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_commissions', 'chat_notified_at', function ($table) {
                $table->timestamp('chat_notified_at')
                    ->nullable()
                    ->after('paid_at')
                    ->comment('เวลาที่แจ้ง user ทาง chat (FB/LINE) ว่ามีรายได้แนะนำเข้ากระเป๋า');
            });
        });

        // Index — query หา pending notifications ของ user ทุกครั้งที่ทัก
        // (user_id, chat_notified_at) เพื่อ scan เฉพาะของ user นั้น
        $this->safeAddIndex('fortune_commissions', ['user_id', 'chat_notified_at'], 'fc_user_chat_notif_idx');
    }

    /**
     * ลบ column + index เมื่อ rollback
     */
    public function down(): void
    {
        $this->safeDropIndex('fortune_commissions', 'fc_user_chat_notif_idx');
        $this->safeDropColumn('fortune_commissions', ['chat_notified_at']);
    }
};
