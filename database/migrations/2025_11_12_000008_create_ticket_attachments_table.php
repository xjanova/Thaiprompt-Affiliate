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
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship (can attach to ticket or reply)
            $table->morphs('attachable'); // attachable_id, attachable_type

            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');

            // File information
            $table->string('filename');
            $table->string('original_filename');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size'); // in bytes

            // Optional: Generate thumbnails for images
            $table->string('thumbnail_path')->nullable();

            // Security
            $table->string('hash')->nullable()->comment('File hash for duplicate detection');
            $table->boolean('is_scanned')->default(false)->comment('Virus scan status');

            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
            $table->index('hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
    }
};
