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
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('pending'); // pending, running, completed, failed, cancelled
            $table->string('branch', 100)->nullable();
            $table->string('commit_hash', 40)->nullable();
            $table->text('commit_message')->nullable();
            $table->unsignedBigInteger('started_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration')->nullable()->comment('Duration in seconds');
            $table->longText('output')->nullable()->comment('Deployment logs');
            $table->text('error')->nullable()->comment('Error message if failed');
            $table->boolean('rollback_available')->default(false);
            $table->string('rollback_commit', 40)->nullable()->comment('Previous commit for rollback');
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('started_by');
            $table->index('started_at');
            $table->index('created_at');

            // Foreign key
            $table->foreign('started_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
