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
        Schema::create('ai_gen_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Freepik, Vidu, Pixverse, etc.
            $table->string('slug')->unique(); // freepik, vidu, pixverse
            $table->string('type'); // image, video, both
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->json('supported_features')->nullable(); // array of features
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // for sorting
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_gen_providers');
    }
};
