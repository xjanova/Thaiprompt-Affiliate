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
        Schema::create('system_info', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20);
            $table->string('build_number', 50)->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->json('php_version')->nullable();
            $table->json('database_version')->nullable();
            $table->json('extensions')->nullable();
            $table->boolean('is_setup_completed')->default(false);
            $table->text('setup_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_info');
    }
};
