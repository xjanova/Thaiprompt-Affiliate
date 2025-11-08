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
        if (!Schema::hasTable('cookie_consents')) {
            Schema::create('cookie_consents', function (Blueprint $table) {
                $table->id();
                $table->string('session_id')->index();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->boolean('necessary')->default(true);
                $table->boolean('analytics')->default(false);
                $table->boolean('marketing')->default(false);
                $table->boolean('preferences')->default(false);
                $table->timestamp('consented_at')->useCurrent();
                $table->timestamps();

                $table->index(['ip_address', 'created_at']);
            });
        }

        if (!Schema::hasTable('cookie_tracking')) {
            Schema::create('cookie_tracking', function (Blueprint $table) {
                $table->id();
                $table->string('session_id')->index();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('ip_address', 45)->nullable();
                $table->string('country', 2)->nullable();
                $table->string('city')->nullable();

                // Referrer และ Source tracking
                $table->text('referrer_url')->nullable();
                $table->string('referrer_domain')->nullable();
                $table->string('utm_source')->nullable();
                $table->string('utm_medium')->nullable();
                $table->string('utm_campaign')->nullable();
                $table->string('utm_term')->nullable();
                $table->string('utm_content')->nullable();

                // Page tracking
                $table->text('landing_page')->nullable();
                $table->text('current_page')->nullable();
                $table->integer('page_views')->default(1);
                $table->integer('session_duration')->default(0); // in seconds

                // Device & Browser info
                $table->string('device_type')->nullable(); // mobile, tablet, desktop
                $table->string('browser')->nullable();
                $table->string('browser_version')->nullable();
                $table->string('os')->nullable();
                $table->string('os_version')->nullable();

                // Shopping & Interest tracking
                $table->json('viewed_products')->nullable();
                $table->json('searched_keywords')->nullable();
                $table->json('interests_detected')->nullable(); // AI-detected interests
                $table->json('behavior_tags')->nullable(); // shopping, browsing, etc.

                $table->timestamp('first_visit_at')->useCurrent();
                $table->timestamp('last_activity_at')->useCurrent();
                $table->timestamps();

                $table->index(['ip_address', 'created_at']);
                $table->index(['session_id', 'last_activity_at']);
                $table->index('referrer_domain');
            });
        }

        if (!Schema::hasTable('cookie_analytics_keywords')) {
            Schema::create('cookie_analytics_keywords', function (Blueprint $table) {
                $table->id();
                $table->string('keyword')->index();
                $table->integer('search_count')->default(1);
                $table->integer('unique_sessions')->default(1);
                $table->json('related_interests')->nullable();
                $table->date('date')->index();
                $table->timestamps();

                $table->unique(['keyword', 'date']);
            });
        }

        if (!Schema::hasTable('cookie_settings')) {
            Schema::create('cookie_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('text'); // text, boolean, json
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cookie_analytics_keywords');
        Schema::dropIfExists('cookie_tracking');
        Schema::dropIfExists('cookie_consents');
        Schema::dropIfExists('cookie_settings');
    }
};
