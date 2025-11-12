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
        Schema::create('ticket_canned_responses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('shortcode')->unique()->comment('Quick access code like /greeting');
            $table->text('content');

            // Categorization
            $table->foreignId('category_id')->nullable()->constrained('ticket_categories')->onDelete('set null');
            $table->json('tags')->nullable();

            // Access control
            $table->boolean('is_public')->default(false)->comment('Available to all staff or specific users');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            // Usage tracking
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['is_active', 'is_public']);
            $table->index('usage_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_canned_responses');
    }
};
