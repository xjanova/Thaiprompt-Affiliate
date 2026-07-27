<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ mode ให้คลังข้อความชวนดูดวง
     *
     * 🔀 (2026-07-28) โหมด transfer ย้ายบริการไปเว็บ/LINE — ข้อความชวนชุดเดิม
     * ที่เจ้าของคัดไว้เอง 100 ข้อความยังชวน "ทักมาดูดวงในแชท" ซึ่งสวนทางกับกล่องใหม่
     *
     * ⚠️ ห้ามเขียนทับข้อความเดิมเด็ดขาด (เจ้าของคัดมาเอง + สลับกลับ classic ต้องได้ของเดิม)
     * → แยกด้วยคอลัมน์ mode แทน:
     *   - 'all'      = ใช้ได้ทุกโหมด (ค่าเริ่มต้นของแถวเดิมทั้งหมด → พฤติกรรมเดิม 100%)
     *   - 'classic'  = เฉพาะโหมดเดิม
     *   - 'transfer' = เฉพาะโหมดพาไปเว็บ/LINE
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_invite_messages')) {
            return;
        }

        Schema::table('fortune_invite_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_invite_messages', 'mode')) {
                $table->string('mode', 20)
                    ->default('all')
                    ->after('category')
                    ->comment('โหมดที่ใช้ข้อความนี้: all | classic | transfer');
            }
        });

        // index ช่วยตอน pickActive() กรองโหมด (ตารางนี้ถูกสุ่มทุก DM)
        if (Schema::hasColumn('fortune_invite_messages', 'mode')
            && ! $this->indexExists('fortune_invite_messages', 'fim_mode_active_idx')) {
            Schema::table('fortune_invite_messages', function (Blueprint $table) {
                $table->index(['mode', 'is_active'], 'fim_mode_active_idx');
            });
        }
    }

    /**
     * ลบคอลัมน์ mode
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_invite_messages')) {
            return;
        }

        if ($this->indexExists('fortune_invite_messages', 'fim_mode_active_idx')) {
            Schema::table('fortune_invite_messages', function (Blueprint $table) {
                $table->dropIndex('fim_mode_active_idx');
            });
        }

        Schema::table('fortune_invite_messages', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_invite_messages', 'mode')) {
                $table->dropColumn('mode');
            }
        });
    }

    /**
     * เช็คว่ามี index นี้อยู่แล้วหรือยัง (กันรันซ้ำแล้วพัง)
     */
    protected function indexExists(string $table, string $index): bool
    {
        try {
            return collect(Schema::getIndexes($table))
                ->contains(fn ($i) => ($i['name'] ?? '') === $index);
        } catch (\Throwable $e) {
            return false;
        }
    }
};
