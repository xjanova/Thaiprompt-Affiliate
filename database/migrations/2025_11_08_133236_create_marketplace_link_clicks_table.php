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
        if (Schema::hasTable('marketplace_link_clicks')) {
            return;
        }

        Schema::create('marketplace_link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_link_id')->constrained('marketplace_affiliate_links')->onDelete('cascade');

            // Visitor Info
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referer_url')->nullable();

            // Location (if available)
            $table->string('country_code', 2)->nullable();
            $table->string('city', 100)->nullable();

            // Tracking
            $table->string('session_id', 100)->nullable();
            $table->boolean('is_unique_click')->default(false);
            $table->boolean('converted')->default(false);
            $table->foreignId('order_id')->nullable()->constrained('marketplace_orders')->onDelete('set null');

            $table->timestamp('clicked_at')->useCurrent();

            $table->index('affiliate_link_id');
            $table->index('clicked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_link_clicks');
    }
};
