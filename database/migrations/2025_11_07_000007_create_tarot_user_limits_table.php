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
        Schema::create('tarot_user_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('tarot_reading_categories')->onDelete('cascade');
            $table->date('limit_date'); // Date of the limit
            $table->integer('free_count')->default(0); // Number of free readings used today
            $table->integer('paid_count')->default(0); // Number of paid readings today
            $table->string('session_id')->nullable(); // For guest users
            $table->string('ip_address', 45)->nullable(); // IP address for guest tracking
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'category_id', 'limit_date']);
            $table->index(['session_id', 'category_id', 'limit_date']);
            $table->index(['ip_address', 'category_id', 'limit_date']);

            // Unique constraint to prevent duplicate entries
            $table->unique(['user_id', 'category_id', 'limit_date'], 'unique_user_category_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarot_user_limits');
    }
};
