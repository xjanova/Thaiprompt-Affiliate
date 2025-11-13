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
        Schema::create('tpix_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');

            // Token Information
            $table->string('name', 100)->comment('Token name (e.g., "My Token")');
            $table->string('symbol', 20)->comment('Token symbol (e.g., "MTK")');
            $table->text('description')->nullable()->comment('Token description');
            $table->string('logo')->nullable()->comment('Token logo URL');
            $table->string('website')->nullable()->comment('Project website');
            $table->text('whitepaper_url')->nullable()->comment('Whitepaper URL');

            // Blockchain Information
            $table->string('contract_address', 42)->unique()->nullable()->comment('Deployed contract address');
            $table->string('deployment_tx_hash')->nullable()->comment('Deployment transaction hash');
            $table->integer('decimals')->default(18)->comment('Token decimals');
            $table->string('total_supply', 50)->comment('Total token supply (as string for big numbers)');
            $table->string('initial_supply', 50)->nullable()->comment('Initial minted supply');

            // Token Features
            $table->boolean('is_mintable')->default(false)->comment('Can mint new tokens');
            $table->boolean('is_burnable')->default(false)->comment('Can burn tokens');
            $table->boolean('is_pausable')->default(false)->comment('Can pause transfers');
            $table->boolean('is_freezable')->default(false)->comment('Can freeze accounts');
            $table->boolean('has_max_supply')->default(true)->comment('Has maximum supply limit');
            $table->string('max_supply', 50)->nullable()->comment('Maximum supply if limited');

            // Trading & Market
            $table->decimal('initial_price_tpix', 20, 8)->nullable()->comment('Initial price in TPIX');
            $table->decimal('current_price_tpix', 20, 8)->nullable()->comment('Current price in TPIX');
            $table->decimal('market_cap_tpix', 30, 8)->nullable()->comment('Market cap in TPIX');
            $table->decimal('volume_24h_tpix', 30, 8)->default(0)->comment('24h trading volume');
            $table->decimal('liquidity_tpix', 30, 8)->default(0)->comment('Total liquidity');

            // Distribution
            $table->json('distribution_plan')->nullable()->comment('Token distribution plan');
            $table->decimal('team_allocation_percent', 5, 2)->nullable();
            $table->decimal('public_sale_percent', 5, 2)->nullable();
            $table->decimal('liquidity_percent', 5, 2)->nullable();
            $table->decimal('marketing_percent', 5, 2)->nullable();

            // Vesting & Lock
            $table->boolean('has_vesting')->default(false);
            $table->json('vesting_schedule')->nullable()->comment('Vesting schedule details');
            $table->timestamp('trading_starts_at')->nullable()->comment('When trading is enabled');

            // Status
            $table->enum('status', ['draft', 'deploying', 'deployed', 'verified', 'active', 'paused', 'suspended', 'failed'])
                ->default('draft');
            $table->boolean('is_verified')->default(false)->comment('Contract verified on explorer');
            $table->boolean('is_listed')->default(false)->comment('Listed on DEX');
            $table->boolean('is_featured')->default(false)->comment('Featured token');

            // Statistics
            $table->bigInteger('holders_count')->default(0);
            $table->bigInteger('transfers_count')->default(0);
            $table->decimal('total_burned', 30, 8)->default(0);
            $table->decimal('total_minted', 30, 8)->default(0);

            // Social & Links
            $table->string('telegram')->nullable();
            $table->string('twitter')->nullable();
            $table->string('discord')->nullable();
            $table->string('github')->nullable();
            $table->string('medium')->nullable();

            // CoinMarketCap Integration
            $table->string('cmc_id')->nullable()->unique()->comment('CoinMarketCap ID');
            $table->timestamp('cmc_last_sync')->nullable();
            $table->json('cmc_data')->nullable()->comment('Latest CMC data');

            // Referral System
            $table->string('referral_code', 20)->unique()->nullable()->comment('Referral code for this token');
            $table->foreignId('referred_by')->nullable()->constrained('users')->comment('Who referred this token creator');
            $table->decimal('referral_rewards_paid', 20, 8)->default(0);

            // Security & Compliance
            $table->boolean('kyc_required')->default(false);
            $table->boolean('kyc_verified')->default(false);
            $table->boolean('audit_completed')->default(false);
            $table->string('audit_report_url')->nullable();
            $table->json('security_features')->nullable();

            // Metadata
            $table->json('metadata')->nullable()->comment('Additional token metadata');
            $table->text('notes')->nullable()->comment('Admin notes');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['creator_id', 'status']);
            $table->index(['symbol', 'status']);
            $table->index('contract_address');
            $table->index('is_verified');
            $table->index('is_listed');
            $table->index('is_featured');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpix_tokens');
    }
};
