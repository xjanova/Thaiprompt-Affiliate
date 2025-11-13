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
        Schema::create('qr_barcode_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // 'qrcode' or 'barcode'
            $table->string('format'); // Format type
            $table->json('settings'); // Template settings (colors, size, style)
            $table->string('thumbnail_path')->nullable();
            $table->boolean('is_public')->default(false); // Share as public template
            $table->boolean('is_premium')->default(false); // Premium template
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('type');
            $table->index('is_public');
            $table->index('usage_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_barcode_templates');
    }
};
