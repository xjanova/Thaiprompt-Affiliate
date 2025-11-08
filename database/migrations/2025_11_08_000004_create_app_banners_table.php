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
        Schema::create('app_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->string('image_url')->nullable();
            $table->string('image_url_dark')->nullable();
            $table->string('type')->default('info'); // info, warning, success, promotion, announcement
            $table->string('position')->default('home'); // home, profile, wallet, products, etc.
            $table->string('action_type')->nullable(); // url, route, deeplink, none
            $table->string('action_value')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_dismissible')->default(true);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('target_audience')->default('all'); // all, new_users, members, vip, etc.
            $table->json('target_user_ids')->nullable(); // Specific user IDs
            $table->string('platform')->nullable(); // android, ios, all
            $table->integer('view_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->timestamps();

            $table->index('position');
            $table->index('is_active');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_banners');
    }
};
