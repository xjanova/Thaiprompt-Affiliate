<?php

namespace Database\Seeders;

use App\Models\CentralAiSetting;
use Illuminate\Database\Seeder;

/**
 * Central AI Setting Seeder
 *
 * สร้างข้อมูลเริ่มต้นสำหรับ Central AI Settings
 */
class CentralAiSettingSeeder extends Seeder
{
    /**
     * รัน seeder
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล Central AI Settings...');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่ (idempotent)
        if (CentralAiSetting::where('is_active', true)->exists()) {
            $this->command->info('⚠️  ข้อมูล Central AI Settings มีอยู่แล้ว ข้าม...');
            return;
        }

        // สร้างข้อมูลเริ่มต้น
        CentralAiSetting::create([
            'name' => 'Central AI',
            'setup_completed' => false,
            'setup_step' => 0,
            'is_active' => true,

            // Ollama Configuration
            'is_ollama_installed' => false,
            'ollama_status' => 'not_installed',
            'ollama_host' => 'localhost',
            'ollama_port' => 11434,
            'default_ollama_model' => 'llama3:8b',
            'installed_models' => null,

            // PostXAgent Configuration
            'postxagent_enabled' => false,
            'postxagent_host' => 'localhost',
            'postxagent_api_port' => 5000,
            'postxagent_signalr_port' => 5000,
            'postxagent_api_key' => null,
            'postxagent_status' => 'not_configured',
            'postxagent_preferred_provider' => 'auto',

            // RAG Settings
            'enable_rag' => true,
            'embedding_provider' => 'ollama',
            'embedding_model' => 'nomic-embed-text',
            'similarity_threshold' => 0.70,
            'max_context_chunks' => 5,

            // LINE Bot Settings
            'auto_reply_enabled' => true,
            'response_max_tokens' => 2048,
            'default_temperature' => 0.70,
            'fallback_message' => 'กรุณาติดต่อเจ้าหน้าที่',

            // Statistics
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
        ]);

        $this->command->info('✅ Seed ข้อมูล Central AI Settings สำเร็จ!');
    }
}
