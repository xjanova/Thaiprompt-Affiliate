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
        Schema::create('cloudflare_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Default Configuration');
            $table->boolean('is_active')->default(true);

            // Cloudflare Tunnel Settings
            $table->boolean('tunnel_enabled')->default(false);
            $table->string('tunnel_id')->nullable();
            $table->string('tunnel_secret')->nullable();
            $table->boolean('verify_cf_headers')->default(true);

            // Spam Filter Settings
            $table->boolean('filter_comments')->default(true);
            $table->boolean('filter_orders')->default(true);
            $table->boolean('filter_contact_forms')->default(true);
            $table->boolean('filter_registrations')->default(true);

            // Challenge Settings
            $table->enum('challenge_mode', ['off', 'managed', 'high', 'under_attack'])->default('managed');
            $table->boolean('enable_bot_fight_mode')->default(true);
            $table->boolean('enable_ddos_protection')->default(true);

            // Rate Limiting Rules (stored as JSON)
            $table->json('rate_limit_rules')->nullable();

            // Custom Spam Keywords (stored as JSON)
            $table->json('custom_spam_keywords')->nullable();

            // Whitelist/Blacklist
            $table->json('ip_whitelist')->nullable();
            $table->json('ip_blacklist')->nullable();
            $table->json('country_whitelist')->nullable();
            $table->json('country_blacklist')->nullable();

            // WAF Rules
            $table->boolean('enable_waf')->default(true);
            $table->json('waf_rules')->nullable();

            // Security Level
            $table->enum('security_level', ['off', 'essentially_off', 'low', 'medium', 'high', 'under_attack'])->default('medium');

            // Browser Integrity Check
            $table->boolean('browser_integrity_check')->default(true);

            // Challenge Passage
            $table->integer('challenge_ttl')->default(1800); // 30 minutes

            $table->timestamps();
        });

        // Create default configuration
        DB::table('cloudflare_configs')->insert([
            'name' => 'Default Configuration',
            'is_active' => true,
            'tunnel_enabled' => false,
            'filter_comments' => true,
            'filter_orders' => true,
            'filter_contact_forms' => true,
            'filter_registrations' => true,
            'challenge_mode' => 'managed',
            'enable_bot_fight_mode' => true,
            'enable_ddos_protection' => true,
            'security_level' => 'medium',
            'browser_integrity_check' => true,
            'verify_cf_headers' => false, // Start disabled for local development
            'rate_limit_rules' => json_encode([
                'comment' => ['max_attempts' => 5, 'decay_minutes' => 60],
                'review' => ['max_attempts' => 5, 'decay_minutes' => 60],
                'order' => ['max_attempts' => 10, 'decay_minutes' => 60],
                'contact' => ['max_attempts' => 3, 'decay_minutes' => 60],
                'registration' => ['max_attempts' => 3, 'decay_minutes' => 60],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudflare_configs');
    }
};
