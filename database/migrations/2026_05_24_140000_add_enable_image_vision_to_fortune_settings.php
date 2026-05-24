<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🖼️ (2026-05-24) เพิ่ม toggle enable_image_vision
 *
 * User spec: "ปิด image_vision ให้ครอบคลุมทุก provider (OpenAI + Gemini + อนาคต)"
 *
 * ปัญหาเดิม:
 *   - ปิด OpenAI sensitive key (#23) → block แค่ chatWithImage() (OpenAI Celtic vision)
 *   - ImageIntentClassifier::classify() ใช้ chatWithImageGemini() ยังเรียก Gemini ต่อ
 *     → 36 calls/วัน เผา quota ฟรีๆ แม้ user คิดว่า "ปิดแล้ว"
 *
 * Toggle ใหม่:
 *   - Default: false (ปลอดภัย — ไม่เผา quota จนกว่า user flip ON)
 *   - เมื่อ false: gate ที่ chatWithImage() + chatWithImageGemini() entry point
 *                  → return null ทันที (ครอบทุก caller ทุก provider)
 *                  → ImageIntentClassifier คืน DEFAULT_INTENT_ON_FAIL (general_photo)
 *                  → fall through ไป legacy logic (ไม่มี vision call)
 *   - เมื่อ true: vision ทำงานปกติ (Celtic 99 read + slip auto-detect classifier)
 *
 * Side effect เมื่อ OFF:
 *   - Slip auto-detect ปิดด้วย → ลูกค้าต้องพิมพ์เลขบิล/จำนวนเงินเอง
 *   - SMS payment matching ยังทำงาน (ไม่ใช้ vision)
 *   - Celtic 99 customer ส่งรูป → บอทไม่วิเคราะห์รูป (ทำนายจากไพ่+คำถามแทน)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_image_vision')) {
                $table->boolean('enable_image_vision')
                    ->default(false)
                    ->after('enable_public_comment_reply')
                    ->comment('🖼️ (2026-05-24) Master toggle: ครอบทุก vision provider (OpenAI + Gemini) — default=false ประหยัด quota');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_image_vision')) {
                $table->dropColumn('enable_image_vision');
            }
        });
    }
};
