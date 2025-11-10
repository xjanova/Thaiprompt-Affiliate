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
        Schema::create('bot_marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('bot_automations')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('bot_marketplace_categories')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->json('features')->nullable(); // List of features
            $table->json('screenshots')->nullable(); // Preview images
            $table->string('demo_video_url')->nullable();

            $table->enum('pricing_model', ['free', 'one_time', 'monthly', 'yearly', 'per_use'])->default('monthly');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('monthly_price', 10, 2)->nullable();
            $table->decimal('yearly_price', 10, 2)->nullable();
            $table->decimal('per_use_price', 10, 4)->nullable();
            $table->decimal('commission_rate', 5, 2)->default(20.00); // Platform commission

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'suspended'])->default('draft');

            $table->integer('total_purchases')->default(0);
            $table->integer('total_rentals')->default(0);
            $table->integer('active_users')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->integer('view_count')->default(0);

            $table->json('tags')->nullable();
            $table->json('supported_platforms')->nullable();
            $table->json('requirements')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status']);
            $table->index(['is_featured', 'average_rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_marketplace_listings');
    }
};
