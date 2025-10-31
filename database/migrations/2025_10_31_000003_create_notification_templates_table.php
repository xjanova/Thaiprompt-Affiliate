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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Template name
            $table->string('slug')->unique(); // URL-friendly identifier
            $table->string('type'); // notification type
            $table->string('title');
            $table->text('message');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('priority')->default('normal');
            $table->boolean('is_important')->default(false);
            $table->boolean('show_immediately')->default(false);
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();
            $table->text('description')->nullable(); // Template description
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
