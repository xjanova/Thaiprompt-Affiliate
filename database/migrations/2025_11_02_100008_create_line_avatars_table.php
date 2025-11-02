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
        // Skip if table already exists (from SQL import)
        if (Schema::hasTable('line_avatars')) {
            $this->command->warn('Table line_avatars already exists, skipping...');
            return;
        }

        Schema::create('line_avatars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['image', 'gif', 'lottie', 'video'])->default('image');
            $table->string('file_path')->comment('path ของไฟล์');
            $table->string('file_url')->nullable()->comment('URL สาธารณะ');
            $table->integer('file_size')->nullable()->comment('ขนาดไฟล์ bytes');
            $table->string('source_type')->nullable()->comment('upload, url, line, default');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_avatars');
    }
};
