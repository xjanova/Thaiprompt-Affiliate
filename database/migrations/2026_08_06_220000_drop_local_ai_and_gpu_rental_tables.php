<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ถอนระบบ AI แบบ local ออกจากฐานข้อมูลทั้งหมด
     *
     * ครอบคลุม:
     * - Central AI / Ollama management (central_ai_settings)
     * - ระบบติดตั้งโมเดลเอง (ai_installation_logs)
     * - ระบบเช่า GPU cloud (ai_rental_* ชุด 2025_11_24)
     * - ข้อมูลประกอบของหน้าเช่า GPU (huggingface_*)
     * - provider แบบ local ใน ai_providers + ai_models ที่ผูกอยู่
     * - คอลัมน์ postxagent_* ใน line_bot_ai_settings
     *
     * ⚠️ ห้ามแตะ `ai_rental_transactions` และ `ai_owner_earnings`
     *    สองตารางนี้เป็นของ "ระบบเช่าบอทแชท" ซึ่งเป็นธุรกิจหลักที่ยังใช้งานอยู่
     *    ชื่อคล้ายกันแต่คนละระบบกับการเช่า GPU
     *
     * ตรวจสอบก่อนเขียน migration นี้แล้วว่า (prod 2026-08-06):
     * - ไม่มี ai_bot_profiles / ai_usage_logs / ai_conversations แถวไหนอ้าง provider local เลย
     * - ตาราง ai_rental_* ที่จะลบมีแต่ seed data ไม่มีข้อมูลผู้ใช้จริง
     */
    public function up(): void
    {
        // 1) ลบ provider แบบ local ออกจาก ai_providers พร้อม model ที่ผูกอยู่
        if (Schema::hasTable('ai_providers') && Schema::hasTable('ai_models')) {
            $localProviderIds = DB::table('ai_providers')
                ->whereIn('name', ['deepseek-local', 'meta-local', 'postxagent'])
                ->pluck('id');

            if ($localProviderIds->isNotEmpty()) {
                DB::table('ai_models')->whereIn('provider_id', $localProviderIds)->delete();
                DB::table('ai_providers')->whereIn('id', $localProviderIds)->delete();
            }
        }

        // 2) ลบคอลัมน์ PostXAgent ที่ค้างอยู่ในตารางตั้งค่าบอท LINE
        if (Schema::hasTable('line_bot_ai_settings')) {
            $postXAgentColumns = array_values(array_filter([
                'postxagent_host',
                'postxagent_api_port',
                'postxagent_signalr_port',
                'postxagent_api_key',
                'postxagent_use_ssl',
                'postxagent_timeout',
                'postxagent_preferred_provider',
            ], fn ($column) => Schema::hasColumn('line_bot_ai_settings', $column)));

            if (! empty($postXAgentColumns)) {
                Schema::table('line_bot_ai_settings', function (Blueprint $table) use ($postXAgentColumns) {
                    $table->dropColumn($postXAgentColumns);
                });
            }
        }

        // 3) ลบตารางของระบบ local AI และระบบเช่า GPU cloud
        //    ปิด foreign key check ชั่วคราวเพราะตารางในชุดนี้อ้างอิงกันเอง
        Schema::disableForeignKeyConstraints();

        $tablesToDrop = [
            // ระบบเช่า GPU cloud (ลบลูกก่อนแม่)
            'ai_rental_audit_logs',
            'ai_rental_health_checks',
            'ai_rental_alerts',
            'ai_rental_budget_limits',
            'ai_rental_deployments',
            'ai_rental_cloud_configs',
            'ai_rental_models',
            'ai_rental_cloud_providers',

            // ข้อมูลประกอบของหน้าเช่า GPU
            'huggingface_model_news',
            'huggingface_trending_models',

            // ระบบติดตั้งโมเดลเอง + Central AI (Ollama)
            'ai_installation_logs',
            'central_ai_settings',
        ];

        foreach ($tablesToDrop as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * ไม่รองรับการย้อนกลับ
     *
     * ระบบ AI แบบ local ถูกถอดออกถาวรตามคำสั่งเจ้าของระบบ (2026-08-06)
     * ทั้งโค้ด เมนู และเซิร์ฟเวอร์ถูกลบไปพร้อมกันแล้ว
     * ถ้าต้องการนำกลับมา ต้องกู้ migration เดิมจาก git history
     */
    public function down(): void
    {
        // ตั้งใจปล่อยว่าง — ดูคำอธิบายด้านบน
    }
};
