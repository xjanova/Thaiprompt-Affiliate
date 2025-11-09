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
        Schema::create('trading_exchanges', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Binance, Bybit, MT4, MT5, etc.
            $table->string('slug')->unique();
            $table->enum('type', ['crypto', 'forex', 'stock'])->default('crypto');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('website_url')->nullable();

            // API Configuration
            $table->string('api_base_url')->nullable();
            $table->json('supported_markets')->nullable(); // ['spot', 'futures', 'margin']
            $table->json('supported_pairs')->nullable(); // Cache of available trading pairs
            $table->boolean('requires_kyc')->default(false);

            // Features
            $table->boolean('supports_websocket')->default(true);
            $table->boolean('supports_paper_trading')->default(false);
            $table->boolean('supports_margin')->default(false);
            $table->boolean('supports_futures')->default(false);

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_maintenance')->default(false);
            $table->integer('display_order')->default(0);

            // Rate limiting
            $table->integer('rate_limit_per_minute')->default(60);
            $table->json('additional_config')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_exchanges');
    }
};
