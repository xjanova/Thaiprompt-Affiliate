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
        // Add pricing to individual readings
        Schema::table('tarot_readings', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('interpretation');
            $table->enum('payment_status', ['free', 'pending', 'paid', 'failed'])->default('free')->after('price');
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->string('transaction_id')->nullable()->after('payment_method');
            $table->timestamp('paid_at')->nullable()->after('transaction_id');
        });

        // Cart items table
        Schema::create('tarot_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('category_id')->constrained('tarot_reading_categories')->onDelete('cascade');
            $table->foreignId('spread_type_id')->constrained('tarot_spread_types')->onDelete('cascade');
            $table->text('question');
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['session_id', 'user_id']);
            $table->index('ip_address');
        });

        // Add IP tracking to user limits
        Schema::table('tarot_user_limits', function (Blueprint $table) {
            if (!Schema::hasColumn('tarot_user_limits', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('session_id')->index();
            }
        });

        // Add quota settings to tarot_settings
        Schema::table('tarot_settings', function (Blueprint $table) {
            $table->integer('guest_daily_limit')->default(1)->after('value');
            $table->integer('member_daily_limit')->default(5)->after('guest_daily_limit');
            $table->boolean('require_login_for_save')->default(true)->after('member_daily_limit');
            $table->boolean('enable_ip_tracking')->default(true)->after('require_login_for_save');
            $table->boolean('enable_cart')->default(true)->after('enable_ip_tracking');
        });

        // Add per-reading pricing to spread types
        Schema::table('tarot_spread_types', function (Blueprint $table) {
            $table->decimal('base_price', 10, 2)->default(0)->after('card_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tarot_readings', function (Blueprint $table) {
            $table->dropColumn(['price', 'payment_status', 'payment_method', 'transaction_id', 'paid_at']);
        });

        Schema::dropIfExists('tarot_cart_items');

        Schema::table('tarot_user_limits', function (Blueprint $table) {
            if (Schema::hasColumn('tarot_user_limits', 'ip_address')) {
                $table->dropColumn('ip_address');
            }
        });

        Schema::table('tarot_settings', function (Blueprint $table) {
            $table->dropColumn([
                'guest_daily_limit',
                'member_daily_limit',
                'require_login_for_save',
                'enable_ip_tracking',
                'enable_cart'
            ]);
        });

        Schema::table('tarot_spread_types', function (Blueprint $table) {
            $table->dropColumn('base_price');
        });
    }
};
