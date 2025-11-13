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
        Schema::create('qr_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type'); // 'qrcode' or 'barcode'
            $table->string('format'); // QR: text, url, email, etc. / Barcode: CODE128, EAN13, etc.
            $table->text('content'); // The actual content
            $table->json('settings')->nullable(); // Colors, size, customization settings
            $table->string('title')->nullable(); // User-defined title
            $table->text('description')->nullable(); // User-defined description
            $table->string('category')->nullable(); // User categorization
            $table->json('tags')->nullable(); // Tags for organization
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_public')->default(false); // Share in public gallery
            $table->string('file_path')->nullable(); // Saved file path
            $table->string('thumbnail_path')->nullable(); // Thumbnail for quick preview
            $table->integer('scan_count')->default(0); // Track number of scans
            $table->timestamp('last_scanned_at')->nullable();
            $table->json('scan_analytics')->nullable(); // Store scan data
            $table->string('short_url')->nullable()->unique(); // Short URL for sharing
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('type');
            $table->index('format');
            $table->index('is_favorite');
            $table->index('is_public');
            $table->index('short_url');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_barcodes');
    }
};
