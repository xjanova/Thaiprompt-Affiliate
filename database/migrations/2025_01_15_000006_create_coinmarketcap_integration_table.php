<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmc_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->nullable()->constrained('tpix_tokens')->onDelete('cascade');

            // Sync Information
            $table->enum('sync_type', ['auto', 'manual'])->default('manual');
            $table->string('cmc_id')->comment('CoinMarketCap ID');
            $table->string('cmc_slug')->nullable();

            // Data Synced
            $table->json('price_data')->nullable()->comment('Price information');
            $table->json('market_data')->nullable()->comment('Market cap, volume, etc.');
            $table->json('metadata')->nullable()->comment('Token info, links, etc.');

            // Previous vs Current
            $table->decimal('old_price_usd', 20, 8)->nullable();
            $table->decimal('new_price_usd', 20, 8)->nullable();
            $table->decimal('price_change_percent', 10, 4)->nullable();

            // Status
            $table->enum('status', ['success', 'failed', 'partial'])->default('success');
            $table->text('error_message')->nullable();
            $table->foreignId('synced_by')->nullable()->constrained('users')->comment('Admin who triggered manual sync');

            $table->timestamps();

            // Indexes
            $table->index(['token_id', 'created_at']);
            $table->index('cmc_id');
            $table->index('status');
        });

        Schema::create('cmc_token_listings', function (Blueprint $table) {
            $table->id();
            $table->string('cmc_id')->unique()->comment('CoinMarketCap ID');

            // Basic Information
            $table->string('name');
            $table->string('symbol', 20);
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();

            // Market Data
            $table->decimal('price_usd', 20, 8)->nullable();
            $table->decimal('price_btc', 20, 12)->nullable();
            $table->decimal('market_cap_usd', 30, 2)->nullable();
            $table->decimal('volume_24h_usd', 30, 2)->nullable();
            $table->decimal('circulating_supply', 30, 8)->nullable();
            $table->decimal('total_supply', 30, 8)->nullable();
            $table->decimal('max_supply', 30, 8)->nullable();

            // Price Changes
            $table->decimal('percent_change_1h', 10, 4)->nullable();
            $table->decimal('percent_change_24h', 10, 4)->nullable();
            $table->decimal('percent_change_7d', 10, 4)->nullable();
            $table->decimal('percent_change_30d', 10, 4)->nullable();

            // Rankings
            $table->integer('cmc_rank')->nullable();
            $table->integer('num_market_pairs')->nullable();

            // Platform Information
            $table->string('platform_name')->nullable()->comment('e.g., Ethereum, BSC');
            $table->string('token_address')->nullable();

            // Metadata
            $table->json('tags')->nullable();
            $table->json('urls')->nullable()->comment('Website, explorer, social links');
            $table->timestamp('date_added')->nullable();
            $table->timestamp('last_updated')->nullable();

            // Import Information
            $table->foreignId('imported_by')->nullable()->constrained('users');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('symbol');
            $table->index('cmc_rank');
            $table->index('is_active');
        });

        Schema::create('coin_control_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->constrained('tpix_tokens')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');

            // Rule Type
            $table->enum('rule_type', [
                'mint_limit',           // Limit minting per period
                'burn_required',        // Require burn before mint
                'transfer_limit',       // Limit transfers per period
                'whitelist_only',       // Only whitelisted can transfer
                'blacklist_restrict',   // Blacklisted cannot transfer
                'max_wallet',           // Maximum tokens per wallet
                'cooldown_period',      // Cooldown between transfers
                'tax_on_transfer'       // Transaction tax
            ]);

            // Rule Parameters (flexible JSON)
            $table->json('parameters')->comment('Rule-specific parameters');

            // Example parameters for different types:
            // mint_limit: {"max_amount": "1000000", "period_hours": 24}
            // max_wallet: {"max_balance": "10000000", "exclude_addresses": ["0x..."]}
            // tax_on_transfer: {"tax_percent": 2, "recipient_address": "0x..."}

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();

            // Metadata
            $table->text('description')->nullable();
            $table->text('reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['token_id', 'rule_type', 'is_active']);
        });

        Schema::create('coin_control_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->constrained('tpix_tokens')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->comment('Admin who performed action');

            // Action Type
            $table->enum('action_type', [
                'mint',              // Mint new tokens
                'burn',              // Burn tokens
                'freeze_address',    // Freeze an address
                'unfreeze_address',  // Unfreeze an address
                'pause_contract',    // Pause all transfers
                'unpause_contract',  // Resume transfers
                'whitelist_add',     // Add to whitelist
                'whitelist_remove',  // Remove from whitelist
                'blacklist_add',     // Add to blacklist
                'blacklist_remove',  // Remove from blacklist
                'update_supply',     // Update total supply info
            ]);

            // Target
            $table->string('target_address', 42)->nullable()->comment('Target address for action');
            $table->decimal('amount', 30, 8)->nullable()->comment('Amount for mint/burn');

            // Blockchain Transaction
            $table->string('tx_hash')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->text('error_message')->nullable();

            // Metadata
            $table->text('reason')->comment('Reason for this action');
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['token_id', 'action_type']);
            $table->index('admin_id');
            $table->index('target_address');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_control_actions');
        Schema::dropIfExists('coin_control_rules');
        Schema::dropIfExists('cmc_token_listings');
        Schema::dropIfExists('cmc_sync_logs');
    }
};
