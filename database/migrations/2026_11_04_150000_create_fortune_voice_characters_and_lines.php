<?php

use App\Support\FortuneVoiceDefaults;
use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * 🎭 (2026-11-04) ตัวละครเสียงพากย์ + คลังบทพูด ย้ายจากค่าคงที่ในโค้ดมาอยู่ในฐานข้อมูล
 * เจ้าของสั่ง "เพิ่มลด เสียงต้นแบบได้ … สร้างเพิ่มได้อีก เพื่อกำหนดใช้กับห้องพยากรณ์ใหม่ได้"
 *
 * 1. fortune_voice_characters: ตัวละคร (แม่หมอ/บุษบา/คุณไสย + ที่แอดมินเพิ่ม) — เสียง/โมเดลของแต่ละตัว
 * 2. fortune_voice_lines: คลังบทพูดสั้นที่อัดไว้ใช้ซ้ำ (คำรับ/คำเติมของโหมดคุยด้วยเสียง) — ผูกกับไฟล์ใน fortune_character_voice_clips
 *
 * ใส่ค่าเริ่มต้นในตัว (prod ไม่รัน seeder) — FortuneVoiceDefaults ใส่เฉพาะที่ยังไม่มี
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        $this->safeCreateTable('fortune_voice_characters', function (Blueprint $table) {
            $table->id();
            $table->string('key', 20);              // ตรงกับ persona ของคลิป (คอลัมน์ persona ยาว 20)
            $table->string('label', 100);
            $table->string('brief', 500)->nullable();
            $table->text('sample_text');
            $table->string('voice_id', 64)->nullable(); // null = เสียงสำรองของระบบ
            $table->string('model', 64)->nullable();    // null = โมเดลกลางในหน้าตั้งค่า
            $table->boolean('is_builtin')->default(false); // ตัวที่ห้องเดิมใช้อยู่ ลบไม่ได้ (ปิดได้)
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('key', 'fvc_key_unique');
        });

        $this->safeCreateTable('fortune_voice_lines', function (Blueprint $table) {
            $table->id();
            $table->string('persona', 20);
            $table->string('line_key', 40);         // "{หมวด}_{เลข}" เช่น ack_1
            $table->string('category', 20);
            $table->text('script');                 // บทพร้อมแท็กอารมณ์ v3
            $table->string('voice_id', 64)->nullable(); // null = เสียงของตัวละคร
            $table->string('model', 64)->nullable();    // null = โมเดลของตัวละคร
            $table->unsignedBigInteger('clip_id')->nullable(); // ไฟล์ล่าสุดที่สร้างให้บรรทัดนี้
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['persona', 'line_key'], 'fvl_persona_key_unique');
            $table->index('clip_id', 'fvl_clip_idx');
        });

        FortuneVoiceDefaults::seed();
    }

    public function down(): void
    {
        $this->safeDropTable('fortune_voice_lines');
        $this->safeDropTable('fortune_voice_characters');
    }
};
