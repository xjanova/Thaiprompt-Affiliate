<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มฟิลด์สำหรับ PostXAgent AI Manager
     *
     * PostXAgent เป็น AI Manager ที่รองรับหลาย provider ในตัว
     * โดยจะเรียก API ไปยัง PostXAgent server แทนการเรียก AI provider โดยตรง
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('line_bot_ai_settings', function (Blueprint $table) {
            // PostXAgent Configuration
            if (!Schema::hasColumn('line_bot_ai_settings', 'postxagent_host')) {
                $table->string('postxagent_host')->nullable()->after('api_endpoint')
                    ->comment('PostXAgent server host (e.g., localhost, postx.example.com)');
            }

            if (!Schema::hasColumn('line_bot_ai_settings', 'postxagent_api_port')) {
                $table->integer('postxagent_api_port')->nullable()->default(5000)->after('postxagent_host')
                    ->comment('PostXAgent API port');
            }

            if (!Schema::hasColumn('line_bot_ai_settings', 'postxagent_signalr_port')) {
                $table->integer('postxagent_signalr_port')->nullable()->default(5002)->after('postxagent_api_port')
                    ->comment('PostXAgent SignalR port for real-time updates');
            }

            if (!Schema::hasColumn('line_bot_ai_settings', 'postxagent_api_key')) {
                $table->string('postxagent_api_key')->nullable()->after('postxagent_signalr_port')
                    ->comment('PostXAgent API key for authentication');
            }

            if (!Schema::hasColumn('line_bot_ai_settings', 'postxagent_use_ssl')) {
                $table->boolean('postxagent_use_ssl')->default(false)->after('postxagent_api_key')
                    ->comment('Use SSL/HTTPS for PostXAgent connection');
            }

            if (!Schema::hasColumn('line_bot_ai_settings', 'postxagent_timeout')) {
                $table->integer('postxagent_timeout')->default(30)->after('postxagent_use_ssl')
                    ->comment('Request timeout in seconds');
            }

            if (!Schema::hasColumn('line_bot_ai_settings', 'postxagent_preferred_provider')) {
                $table->string('postxagent_preferred_provider')->nullable()->after('postxagent_timeout')
                    ->comment('Preferred AI provider in PostXAgent (auto, ollama, openai, anthropic, google)');
            }
        });
    }

    /**
     * ลบฟิลด์ PostXAgent
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('line_bot_ai_settings', function (Blueprint $table) {
            $columns = [
                'postxagent_host',
                'postxagent_api_port',
                'postxagent_signalr_port',
                'postxagent_api_key',
                'postxagent_use_ssl',
                'postxagent_timeout',
                'postxagent_preferred_provider',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('line_bot_ai_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
