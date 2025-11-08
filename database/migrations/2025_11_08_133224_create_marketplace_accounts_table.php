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
        Schema::create('marketplace_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('platform_id')->constrained('marketplace_platforms')->onDelete('cascade');

            // Account Info
            $table->string('account_name', 100);
            $table->string('shop_id', 100)->nullable();
            $table->string('shop_name', 200)->nullable();

            // API Credentials (Encrypted)
            $table->text('app_key');
            $table->text('app_secret');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            // Additional credentials (platform-specific)
            $table->json('additional_credentials')->nullable();

            // Configuration
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->boolean('auto_sync_products')->default(false);
            $table->boolean('auto_sync_orders')->default(true);
            $table->string('sync_frequency', 20)->default('hourly');

            // Status
            $table->enum('status', ['active', 'inactive', 'error', 'expired'])->default('active');
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();

            // Statistics
            $table->integer('total_products_synced')->default(0);
            $table->integer('total_orders_synced')->default(0);
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('total_commission', 15, 2)->default(0);

            $table->timestamps();

            $table->index(['user_id', 'platform_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_accounts');
    }
};
