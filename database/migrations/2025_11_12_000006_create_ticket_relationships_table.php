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
        Schema::create('ticket_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('related_ticket_id')->constrained('tickets')->onDelete('cascade');

            $table->enum('relationship_type', [
                'duplicate',
                'related',
                'blocks',
                'blocked_by',
                'parent',
                'child'
            ])->default('related');

            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            $table->timestamps();

            $table->index(['ticket_id', 'relationship_type']);
            $table->unique(['ticket_id', 'related_ticket_id', 'relationship_type'], 'unique_relationship');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_relationships');
    }
};
