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
        Schema::create('mlm_genealogy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mlm_member_id')->constrained()->onDelete('cascade');
            $table->foreignId('ancestor_id')->constrained('mlm_members')->onDelete('cascade');
            $table->foreignId('mlm_plan_id')->constrained()->onDelete('cascade');

            // Hierarchy Details
            $table->enum('tree_type', ['unilevel', 'binary']);
            $table->integer('depth'); // How many levels between ancestor and member
            $table->string('path')->nullable(); // Full path for quick traversal

            // Binary specific
            $table->enum('leg', ['left', 'right'])->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['mlm_member_id', 'tree_type']);
            $table->index(['ancestor_id', 'tree_type', 'depth']);
            $table->index(['mlm_plan_id', 'tree_type']);
            $table->unique(['mlm_member_id', 'ancestor_id', 'tree_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_genealogy');
    }
};
