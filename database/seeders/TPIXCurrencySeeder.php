<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TPIXCurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Check if TPIX already exists
        $exists = DB::table('crypto_currencies')->where('code', 'TPIX')->exists();

        if ($exists) {
            $this->command->info('TPIX currency already exists. Updating...');

            DB::table('crypto_currencies')
                ->where('code', 'TPIX')
                ->update([
                    'name' => 'TPIX',
                    'symbol' => 'TPIX',
                    'network' => 'tpix',
                    'contract_address' => null, // Native coin, no contract
                    'token_standard' => 'native',
                    'decimals' => 18,
                    'chain_id' => '7000',
                    'icon' => '/images/crypto/tpix.svg',
                    'color' => '#4F46E5', // Indigo color
                    'price_source' => 'manual', // Manual price for now
                    'coingecko_id' => null, // Not on CoinGecko yet
                    'binance_symbol' => null, // Not on Binance yet
                    'manual_price_thb' => 20.00, // Default price: 20 THB per TPIX
                    'exchange_fee_percentage' => 0.5, // 0.5% exchange fee
                    'min_exchange_amount' => 1.00000000, // Min 1 TPIX
                    'min_deposit' => 0.01000000, // Min 0.01 TPIX deposit
                    'min_withdrawal' => 0.10000000, // Min 0.1 TPIX withdrawal
                    'max_withdrawal' => 100000.00000000, // Max 100,000 TPIX per transaction
                    'withdrawal_fee' => 0.01000000, // 0.01 TPIX withdrawal fee
                    'withdrawal_fee_type' => 'fixed',
                    'confirmations_required' => 5, // 5 blocks (~10 seconds)
                    'rpc_endpoints' => json_encode([
                        'http://localhost:8545',
                        'ws://localhost:8546'
                    ]),
                    'explorer_url' => 'http://localhost:4000',
                    'explorer_tx_path' => '/tx/',
                    'explorer_address_path' => '/address/',
                    'is_active' => true,
                    'deposit_enabled' => true,
                    'withdrawal_enabled' => true,
                    'exchange_enabled' => true,
                    'payment_gateway_enabled' => true, // Enable as payment gateway
                    'sort_order' => 1, // Show first in lists
                    'description' => 'TPIX is the native cryptocurrency of the TPIX Network blockchain. It is used for transaction fees and as a medium of exchange within the Thaiprompt Affiliate ecosystem.',
                    'metadata' => json_encode([
                        'total_supply' => '7000000000',
                        'circulating_supply' => '7000000000',
                        'max_supply' => '7000000000',
                        'blockchain_type' => 'native',
                        'consensus' => 'IBFT',
                        'block_time' => 2,
                        'website' => 'https://tpix.network',
                        'whitepaper' => 'https://tpix.network/whitepaper',
                        'features' => [
                            'native_coin' => true,
                            'evm_compatible' => true,
                            'smart_contracts' => true,
                            'erc20_support' => true,
                            'fixed_supply' => true,
                            'fast_transactions' => true,
                            'low_fees' => true
                        ]
                    ]),
                    'updated_at' => $now
                ]);
        } else {
            $this->command->info('Creating TPIX currency...');

            DB::table('crypto_currencies')->insert([
                'code' => 'TPIX',
                'name' => 'TPIX',
                'symbol' => 'TPIX',
                'network' => 'tpix',
                'contract_address' => null, // Native coin, no contract
                'token_standard' => 'native',
                'decimals' => 18,
                'chain_id' => '7000',
                'icon' => '/images/crypto/tpix.svg',
                'color' => '#4F46E5', // Indigo color
                'price_source' => 'manual', // Manual price for now
                'coingecko_id' => null, // Not on CoinGecko yet
                'binance_symbol' => null, // Not on Binance yet
                'manual_price_thb' => 20.00, // Default price: 20 THB per TPIX
                'exchange_fee_percentage' => 0.5, // 0.5% exchange fee
                'min_exchange_amount' => 1.00000000, // Min 1 TPIX
                'min_deposit' => 0.01000000, // Min 0.01 TPIX deposit
                'min_withdrawal' => 0.10000000, // Min 0.1 TPIX withdrawal
                'max_withdrawal' => 100000.00000000, // Max 100,000 TPIX per transaction
                'withdrawal_fee' => 0.01000000, // 0.01 TPIX withdrawal fee
                'withdrawal_fee_type' => 'fixed',
                'confirmations_required' => 5, // 5 blocks (~10 seconds)
                'rpc_endpoints' => json_encode([
                    'http://localhost:8545',
                    'ws://localhost:8546'
                ]),
                'explorer_url' => 'http://localhost:4000',
                'explorer_tx_path' => '/tx/',
                'explorer_address_path' => '/address/',
                'is_active' => true,
                'deposit_enabled' => true,
                'withdrawal_enabled' => true,
                'exchange_enabled' => true,
                'payment_gateway_enabled' => true, // Enable as payment gateway
                'sort_order' => 1, // Show first in lists
                'description' => 'TPIX is the native cryptocurrency of the TPIX Network blockchain. It is used for transaction fees and as a medium of exchange within the Thaiprompt Affiliate ecosystem.',
                'metadata' => json_encode([
                    'total_supply' => '7000000000',
                    'circulating_supply' => '7000000000',
                    'max_supply' => '7000000000',
                    'blockchain_type' => 'native',
                    'consensus' => 'IBFT',
                    'block_time' => 2,
                    'website' => 'https://tpix.network',
                    'whitepaper' => 'https://tpix.network/whitepaper',
                    'features' => [
                        'native_coin' => true,
                        'evm_compatible' => true,
                        'smart_contracts' => true,
                        'erc20_support' => true,
                        'fixed_supply' => true,
                        'fast_transactions' => true,
                        'low_fees' => true
                    ]
                ]),
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        $this->command->info('TPIX currency seeded successfully!');
    }
}
