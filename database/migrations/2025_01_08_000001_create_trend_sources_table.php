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
        Schema::create('trend_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('web'); // web, api, rss, social_media
            $table->string('url');
            $table->json('api_config')->nullable(); // API credentials, headers, params
            $table->string('scraping_method')->default('html'); // html, json, xml, rss
            $table->json('selectors')->nullable(); // CSS selectors for scraping
            $table->integer('check_interval')->default(60); // minutes
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(1); // 1-10
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'last_checked_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trend_sources');
    }
};
