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
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // promptpay, stripe, paypal, etc.
            $table->string('name'); // Display name
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('instructions')->nullable(); // Setup instructions

            // Status
            $table->boolean('is_active')->default(false);
            $table->boolean('is_available')->default(true); // System availability
            $table->boolean('is_coming_soon')->default(false);
            $table->boolean('supports_deposit')->default(true);
            $table->boolean('supports_withdrawal')->default(true);

            // Configuration
            $table->json('config')->nullable(); // Store gateway-specific configuration
            $table->json('credentials')->nullable(); // Encrypted sensitive data
            $table->json('fees')->nullable(); // Fee structure
            $table->json('limits')->nullable(); // Min/max amounts

            // Visual & UX
            $table->string('icon')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('color')->default('#3B82F6'); // Brand color
            $table->text('help_url')->nullable(); // Link to documentation

            // Testing
            $table->boolean('test_mode')->default(false);
            $table->json('test_credentials')->nullable();

            // Priority & Sorting
            $table->integer('sort_order')->default(0);
            $table->string('category')->default('general'); // general, crypto, bank, e-wallet

            // Metadata
            $table->timestamp('last_tested_at')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index('category');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
