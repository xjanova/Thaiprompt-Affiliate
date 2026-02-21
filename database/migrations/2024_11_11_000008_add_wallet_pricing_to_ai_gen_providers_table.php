<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ wallet pricing ให้ตาราง ai_gen_providers
     *
     * คอลัมน์ใหม่:
     * - wallet_cost_per_image: ราคาต่อภาพ (หักจาก wallet)
     * - wallet_cost_per_video: ราคาต่อวิดีโอ (หักจาก wallet)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('ai_gen_providers', function (Blueprint $table) {
            $this->safeAddColumn($table, 'ai_gen_providers', 'wallet_cost_per_image', function ($table) {
                $table->decimal('wallet_cost_per_image', 10, 2)->nullable()->after('priority')
                    ->comment('ราคาต่อภาพ (THB) หักจาก wallet');
            });

            $this->safeAddColumn($table, 'ai_gen_providers', 'wallet_cost_per_video', function ($table) {
                $table->decimal('wallet_cost_per_video', 10, 2)->nullable()->after('wallet_cost_per_image')
                    ->comment('ราคาต่อวิดีโอ (THB) หักจาก wallet');
            });
        });
    }

    /**
     * ลบคอลัมน์ wallet pricing
     *
     * @return void
     */
    public function down(): void
    {
        $this->safeDropColumn('ai_gen_providers', ['wallet_cost_per_image', 'wallet_cost_per_video']);
    }
};
