<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🧠 (2026-06-01) คลังองค์ความรู้แม่หมอ (RAG) — admin-managed, card-driven
     *
     * เก็บ "องค์ความรู้ทั้งหมด" ที่แม่หมอ (AI) ดึงไปทำนายตามหน้าไพ่:
     *   สุขภาพ / ฮวงจุ้ย / เจ้าที่ / องค์เทพ / ไสยศาสตร์ — แอดมินแก้ไข/เพิ่มเองได้
     *
     * แยกจาก fortune_admin_qa (อันนั้น = Q&A จับจาก inbox อัตโนมัติ คนละรูปแบบ)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_knowledge')) {
            Schema::create('fortune_knowledge', function (Blueprint $table) {
                $table->id();
                $table->string('category', 32)->index(); // health/feng_shui/guardian_spirits/deities/black_magic/general
                $table->string('card_name', 64)->nullable()->index(); // name_en ของไพ่ (เฉพาะความรู้รายไพ่)
                $table->string('subject', 120)->nullable(); // ชื่อย่อยจัดกลุ่ม (เทพ/ทิศ/ไพ่)
                $table->string('title', 200); // หัวข้อสั้น
                $table->text('keywords')->nullable(); // คำค้น trigger (คั่นด้วย ,)
                $table->longText('content'); // เนื้อหาความรู้
                $table->string('severity', 16)->nullable(); // low/medium/high
                $table->integer('priority')->default(0); // ลำดับการแสดง/inject
                $table->boolean('is_active')->default(true);
                $table->json('embedding')->nullable(); // สำรอง semantic mode (v1 = null)
                $table->string('source', 255)->nullable(); // อ้างอิง
                $table->timestamps();

                $table->index(['is_active', 'category'], 'fortune_knowledge_active_cat_idx');
            });
        }

        // 🌱 Auto-seed ตอน deploy (deploy.sh รัน migrate --force ไม่รัน db:seed)
        //   idempotent (create-if-missing) — รันซ้ำปลอดภัย, ไม่ทับของที่แอดมินแก้
        //   non-blocking: ถ้า fail → directive มี config fallback อยู่แล้ว
        try {
            Artisan::call('db:seed', [
                '--class' => \Database\Seeders\FortuneKnowledgeSeeder::class,
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('FortuneKnowledge auto-seed ใน migration ล้มเหลว (non-blocking)', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_knowledge');
    }
};
