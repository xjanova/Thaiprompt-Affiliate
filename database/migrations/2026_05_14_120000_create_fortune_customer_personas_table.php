<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 👤 (2026-05-14) Customer Persona Memory — บุคลิก/นิสัยลูกค้าระยะยาว
 *
 * แยกจาก LineBotConversation (24hr session memory):
 *  - LineBotConversation = ความจำสนทนาสั้นๆ (24 ชม) → reset ใหม่ทุกวัน
 *  - FortuneCustomerPersona = บุคลิก/นิสัย/ชอบ-ไม่ชอบ → เก็บถาวร update ทุกครั้งที่คุย
 *
 * Why: User feedback 2026-05-14 — "จำบุคลิกลูกค้าเพื่อนำมาคุยในครั้งต่อๆ ไปในแต่ละวัน"
 *
 * ObsidianX-ready (future export):
 *  - note_markdown — full persona เป็น markdown 1 file
 *  - topic_tags — comma-separated tags (เหมือน Obsidian frontmatter tags)
 *  - searchable via tags + title
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fortune_customer_personas')) {
            return;
        }

        Schema::create('fortune_customer_personas', function (Blueprint $table) {
            $table->id();

            // 🔑 Composite unique: platform + user_id
            $table->string('platform', 16)->index(); // 'facebook' / 'line'
            $table->string('platform_user_id', 64)->index(); // FB PSID / LINE userId
            $table->string('display_name', 128)->nullable();

            // 👥 Demographics — JSON เพื่อ flexible
            //   {age_range: "25-35"|"36-45"|...|"unknown",
            //    gender_hint: "male"|"female"|"non_binary"|"unknown",
            //    job_hint: "freelance"|"employee"|"student"|...,
            //    location_hint: "bangkok"|"north"|...}
            $table->json('demographics')->nullable();

            // 🎭 Personality traits — ["เปิดเผย", "ขี้กังวล", "ตรงๆ", "อ่อนไหว"]
            $table->json('traits')->nullable();

            // ❤️ Likes — ["ทำอาหาร", "หมา", "เพลงเก่า"]
            $table->json('likes')->nullable();

            // ❌ Dislikes — ["คนตรงเกินไป", "การรอ"]
            $table->json('dislikes')->nullable();

            // 💬 Conversation themes — ["ความรักไม่ลงตัว", "เรื่องงาน"]
            $table->json('conversation_themes')->nullable();

            // 🗣️ Communication style — {tone: "warm"|"casual"|"formal",
            //                            pace: "fast"|"slow",
            //                            formality: "informal"|"polite",
            //                            emoji_usage: "high"|"medium"|"low"}
            $table->json('communication_style')->nullable();

            // 🏷️ Topic tags — comma-separated (ObsidianX-style)
            //    "love, work-stress, family, anxiety, career-change"
            $table->text('topic_tags')->nullable();

            // 📝 Full markdown note — เตรียมไว้ export ไป ObsidianX
            $table->longText('note_markdown')->nullable();

            // 📊 Observability
            $table->unsignedInteger('observation_count')->default(0);
            $table->timestamp('last_observed_at')->nullable();
            $table->timestamp('last_persona_sync_at')->nullable(); // ครั้งสุดท้ายที่ AI วิเคราะห์ persona

            $table->timestamps();
            $table->softDeletes();

            // 🔍 Indexes
            $table->unique(['platform', 'platform_user_id'], 'ftn_persona_platform_user_unique');
            $table->index('last_observed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_customer_personas');
    }
};
