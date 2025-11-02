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
        Schema::create('line_oa_settings', function (Blueprint $table) {
            $table->id();
            $table->string('channel_id')->nullable();
            $table->string('channel_secret')->nullable();
            $table->text('channel_access_token')->nullable();
            $table->string('liff_id')->nullable();
            $table->boolean('require_line_registration')->default(false);
            $table->boolean('enable_line_messaging')->default(true);
            $table->text('welcome_message')->nullable();
            $table->text('registration_success_message')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_oa_settings');
    }
};
