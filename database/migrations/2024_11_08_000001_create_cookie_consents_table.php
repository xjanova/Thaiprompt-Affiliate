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
        // Cookie Tracking Table
        if (!Schema::hasTable('cookie_tracking')) {
            Schema::create('cookie_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');

            // Visitor Information
            $table->string('ip_address', 45)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();

            // Referrer Information
            $table->text('referrer_url')->nullable();
            $table->string('referrer_domain')->nullable();

            // UTM Parameters
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();

            // Page Tracking
            $table->text('landing_page')->nullable();
            $table->text('current_page')->nullable();
            $table->integer('page_views')->default(1);
            $table->integer('session_duration')->default(0)->comment('Duration in seconds');

            // Device Information
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('os')->nullable();
            $table->string('os_version')->nullable();

            // Behavior Tracking
            $table->json('viewed_products')->nullable();
            $table->json('searched_keywords')->nullable();
            $table->json('interests_detected')->nullable();
            $table->json('behavior_tags')->nullable();

            // Timestamps - ใช้ useCurrent() เพื่อแก้ปัญหา invalid default value
            $table->timestamp('first_visit_at')->useCurrent();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'session_id']);
            $table->index(['country', 'city']);
            $table->index('last_activity_at');
            });
        }

        // Cookie Consents Table
        if (!Schema::hasTable('cookie_consents')) {
            Schema::create('cookie_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->index();
            $table->string('ip_address', 45)->nullable();

            // Consent Types
            $table->boolean('essential')->default(true)->comment('Required cookies');
            $table->boolean('analytics')->default(false)->comment('Analytics cookies');
            $table->boolean('marketing')->default(false)->comment('Marketing cookies');
            $table->boolean('preferences')->default(false)->comment('Preference cookies');

            // Metadata
            $table->string('user_agent')->nullable();
            $table->text('consent_details')->nullable();
            $table->timestamp('consented_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['session_id', 'ip_address']);
            $table->index('consented_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cookie_consents');
        Schema::dropIfExists('cookie_tracking');
    }
};
