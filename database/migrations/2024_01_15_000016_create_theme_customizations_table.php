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
        Schema::create('theme_customizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('theme_name')->default('default');
            $table->string('primary_color')->default('#4F46E5');
            $table->string('secondary_color')->default('#EC4899');
            $table->string('accent_color')->default('#F59E0B');
            $table->json('gradient_colors')->nullable(); // สำหรับไล่เฉดสี
            $table->string('font_family')->default('Inter');
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('banner_url')->nullable();
            $table->json('custom_css')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->timestamp('premium_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('premium_features', function (Blueprint $table) {
            $table->id();
            $table->string('feature_name');
            $table->string('feature_key')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly, lifetime
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vendor_premium_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('premium_feature_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('active'); // active, expired, cancelled
            $table->decimal('amount_paid', 10, 2);
            $table->timestamps();
        });

        // Insert default premium features
        DB::table('premium_features')->insert([
            [
                'feature_name' => 'Theme Customization',
                'feature_key' => 'theme_customization',
                'description' => 'ปรับแต่งสีธีม โลโก้ และการแสดงผลของร้านค้า',
                'price' => 299.00,
                'billing_cycle' => 'monthly',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'feature_name' => 'Advanced Analytics',
                'feature_key' => 'advanced_analytics',
                'description' => 'กราฟและรายงานขั้นสูงพร้อม Real-time Dashboard',
                'price' => 199.00,
                'billing_cycle' => 'monthly',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'feature_name' => 'Premium Support',
                'feature_key' => 'premium_support',
                'description' => 'การสนับสนุนลูกค้าแบบพิเศษตลอด 24/7',
                'price' => 499.00,
                'billing_cycle' => 'monthly',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_premium_subscriptions');
        Schema::dropIfExists('premium_features');
        Schema::dropIfExists('theme_customizations');
    }
};
