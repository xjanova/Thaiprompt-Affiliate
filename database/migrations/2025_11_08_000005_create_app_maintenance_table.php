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
        Schema::create('app_maintenance', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_maintenance_mode')->default(false);
            $table->string('title')->default('ระบบกำลังปรับปรุง');
            $table->string('title_en')->default('Under Maintenance');
            $table->text('message')->nullable();
            $table->text('message_en')->nullable();
            $table->string('image_url')->nullable();
            $table->dateTime('scheduled_start')->nullable();
            $table->dateTime('scheduled_end')->nullable();
            $table->dateTime('actual_start')->nullable();
            $table->dateTime('actual_end')->nullable();
            $table->boolean('show_countdown')->default(true);
            $table->boolean('allow_admin_access')->default(true);
            $table->json('allowed_user_ids')->nullable(); // Users who can access during maintenance
            $table->string('bypass_key')->nullable(); // Secret key to bypass maintenance
            $table->string('platform')->nullable(); // android, ios, web, all
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_maintenance');
    }
};
