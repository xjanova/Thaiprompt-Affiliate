<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('vendor_features_usage');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Schema::create('vendor_features_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');
            $table->foreignId('feature_id')->constrained('vendor_package_features')->onDelete('cascade');

            // Activation
            $table->timestamp('activated_at');
            $table->timestamp('expires_at')->nullable();

            // Settings
            $table->json('feature_settings')->nullable();

            // Usage Stats
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['store_id', 'feature_id']);
            $table->index(['store_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_features_usage');
    }
};
