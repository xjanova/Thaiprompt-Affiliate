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
        Schema::create('mlm_binary_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mlm_member_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('mlm_members')->onDelete('cascade');
            $table->foreignId('left_child_id')->nullable()->constrained('mlm_members')->onDelete('set null');
            $table->foreignId('right_child_id')->nullable()->constrained('mlm_members')->onDelete('set null');

            // Position Info
            $table->enum('position', ['left', 'right'])->nullable(); // Position under parent
            $table->integer('depth')->default(0); // Depth in binary tree
            $table->string('binary_code')->unique(); // Unique code like "LRR" = Left-Right-Right

            // Spillover tracking
            $table->boolean('is_spillover')->default(false);
            $table->foreignId('spillover_from_id')->nullable()->constrained('mlm_members')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('mlm_member_id');
            $table->index(['parent_id', 'position']);
            $table->index('binary_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_binary_positions');
    }
};
