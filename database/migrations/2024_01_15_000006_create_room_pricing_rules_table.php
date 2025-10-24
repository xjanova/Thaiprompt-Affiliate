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
        Schema::create('room_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types')->onDelete('cascade');

            $table->string('name'); // "High Season", "New Year", "Weekends"
            $table->enum('rule_type', ['seasonal', 'date_range', 'day_of_week', 'special_event']);

            // Date Range (for seasonal & special events)
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Days of Week (for weekends, etc.)
            $table->json('applicable_days')->nullable(); // [5,6] for Friday-Saturday

            // Pricing
            $table->decimal('price_adjustment', 10, 2); // Positive or negative
            $table->enum('adjustment_type', ['fixed', 'percentage'])->default('fixed');

            // Priority (higher priority rules override lower ones)
            $table->integer('priority')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_pricing_rules');
    }
};
