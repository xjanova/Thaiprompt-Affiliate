<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('TP Affiliate');
            $table->string('app_short_name')->nullable();
            $table->text('app_description')->nullable();
            $table->string('app_version')->default('1.0.0');
            $table->string('app_build_number')->default('1');
            $table->boolean('force_update')->default(false);
            $table->string('min_supported_version')->nullable();
            $table->string('latest_version')->nullable();
            $table->text('update_message_th')->nullable();
            $table->text('update_message_en')->nullable();
            $table->string('app_store_url')->nullable();
            $table->string('play_store_url')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->string('support_line_id')->nullable();
            $table->string('privacy_policy_url')->nullable();
            $table->string('terms_url')->nullable();
            $table->string('default_language')->default('th');
            $table->json('supported_languages')->nullable();
            $table->string('currency')->default('THB');
            $table->string('timezone')->default('Asia/Bangkok');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
