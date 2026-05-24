<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🔍 (2026-05-25) เพิ่ม enable_celtic_enrichment toggle
     *
     * Feature: AI ถาม clarifying ก่อนทำนายลึก (เฉพาะคำถาม vague)
     * - คำถามชัด เช่น "เขาจะกลับมาไหม" → ทำนายตรง
     * - คำถาม vague เช่น "ดวงรัก" → AI ส่งคำถาม clarifying 1-2 ข้อก่อน
     * - Respect risk_flags: quiet_listener/mental_fragile/over_emotional → skip enrichment
     *
     * Default true — admin ปิดได้ที่ /admin/fortune/settings
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_celtic_enrichment')) {
                $table->boolean('enable_celtic_enrichment')->default(true)->after('celtic_cross_proactive_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_celtic_enrichment')) {
                $table->dropColumn('enable_celtic_enrichment');
            }
        });
    }
};
