<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * อัปเดตข้อมูล keyword "commission" จากระบบ MLM เดิม
     * เป็นระบบแนะนำเพื่อนดูดวง (Fortune Referral)
     */
    public function up(): void
    {
        $updated = DB::table('line_bot_keywords')
            ->where('keyword', 'commission')
            ->update([
                'description' => 'ค่าแนะนำจากการแชร์ลิงก์ดูดวง',
                'trigger_words' => json_encode(
                    ['commission', 'คอมมิชชั่น', 'earning', 'รายได้', 'ได้เงิน', 'ค่าแนะนำ', 'แชร์รายได้'],
                    JSON_UNESCAPED_UNICODE
                ),
                'response_text' => <<<'RESPONSE'
🎁 ระบบแนะนำเพื่อนดูดวง

แชร์ลิงก์ให้เพื่อนมาดูดวง ทุกครั้งที่เพื่อนดูดวง คุณจะได้รับค่าแนะนำเข้า Wallet ตลอดไปค่ะ ✨

📌 วิธีแชร์:
พิมพ์ "แชร์" เพื่อรับลิงก์เชิญเพื่อน

💰 ค่าแนะนำ:
ทุกครั้งที่เพื่อนดูดวง คุณจะได้รับค่าแนะนำอัตโนมัติเข้า Wallet

📲 การถอนเงิน:
ถอนได้ที่เว็บไซต์ เข้าบัญชีภายใน 1-3 วันทำการ

ลองพิมพ์ "แชร์" เพื่อรับลิงก์ได้เลยค่ะ 🔮
RESPONSE,
                'category' => 'faq',
                'priority' => 72,
            ]);

        if ($updated) {
            // Migration สำเร็จ
        }
    }

    /**
     * ไม่ต้อง rollback เพราะเป็น data migration
     */
    public function down(): void
    {
        // ไม่ rollback ข้อมูล
    }
};
