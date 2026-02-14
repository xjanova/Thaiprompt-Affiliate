<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ system_prompt สำหรับกำหนดขอบเขตการตอบของ AI
     */
    public function up(): void
    {
        Schema::table('central_ai_settings', function (Blueprint $table) {
            $this->safeAddColumn($table, 'central_ai_settings', 'system_prompt', function ($table) {
                $table->text('system_prompt')->nullable()->after('default_temperature')
                    ->comment('System prompt สำหรับกำหนดขอบเขตการตอบของ AI');
            });
        });
    }

    /**
     * ลบคอลัมน์ system_prompt
     */
    public function down(): void
    {
        $this->safeDropColumn('central_ai_settings', ['system_prompt']);
    }
};
