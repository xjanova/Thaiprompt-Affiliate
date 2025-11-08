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
        Schema::create('tarot_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('tarot_reading_categories')->onDelete('cascade');
            $table->foreignId('spread_type_id')->constrained('tarot_spread_types')->onDelete('cascade');
            $table->string('question')->nullable(); // User's question
            $table->text('interpretation')->nullable(); // AI-generated interpretation
            $table->decimal('amount_paid', 10, 2)->default(0); // Amount paid for this reading
            $table->boolean('is_free')->default(false); // Was this a free reading
            $table->boolean('is_saved')->default(false); // User saved this reading
            $table->string('session_id')->nullable(); // Session ID for guest users
            $table->string('ip_address', 45)->nullable(); // IP address
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['category_id', 'created_at']);
            $table->index(['session_id', 'created_at']);
            $table->index('is_saved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarot_readings');
    }
};
