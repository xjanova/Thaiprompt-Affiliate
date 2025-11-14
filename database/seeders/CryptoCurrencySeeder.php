<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CryptoCurrency;

class CryptoCurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 📌 Smart Seeding Strategy:
     * Adds missing cryptocurrencies only, preserves existing configurations.
     * User customizations (fees, prices, RPC endpoints) are preserved.
     */
    public function run(): void
    {
        $existingCurrenciesCount = CryptoCurrency::count();

        if ($existingCurrenciesCount > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh install mode - seed all cryptocurrencies
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: Seeding all cryptocurrencies...');

        $currencies = $this->getAllCurrencies();

        foreach ($currencies as $currency) {
            CryptoCurrency::create($currency);
        }

        $this->command->info('✅ Cryptocurrencies seeded successfully: ' . count($currencies) . ' currencies');
    }

    /**
     * Update mode - add only missing cryptocurrencies
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  Existing cryptocurrencies detected!');
        $this->command->info('   Running in UPDATE mode (adding missing currencies only)...');

        $currencies = $this->getAllCurrencies();
        $added = 0;
        $skipped = 0;

        foreach ($currencies as $currency) {
            if (!CryptoCurrency::where('code', $currency['code'])->exists()) {
                CryptoCurrency::create($currency);
                $this->command->info("   ➕ Added: {$currency['name']} ({$currency['code']})");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ Added {$added} new cryptocurrencies.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing currencies (preserved).");
        }
    }

    /**
     * Get all default cryptocurrencies
     */
    private function getAllCurrencies(): array
    {
        return [
            // Bitcoin
            [
                'code' => 'BTC',
                'name' => 'Bitcoin',
                'symbol' => '₿',
                'network' => 'bitcoin',
                'contract_address' => null,
                'token_standard' => 'native',
                'decimals' => 8,
                'chain_id' => null,
                'icon' => '/assets/crypto/btc.svg',
                'color' => '#F7931A',
                'price_source' => 'api',
                'coingecko_id' => 'bitcoin',
                'binance_symbol' => 'BTCUSDT',
                'manual_price_thb' => null,
                'exchange_fee_percentage' => 1.0,
                'min_exchange_amount' => 100,
                'min_deposit' => 0.00001,
                'min_withdrawal' => 0.0001,
                'max_withdrawal' => 10,
                'withdrawal_fee' => 0.0005,
                'withdrawal_fee_type' => 'fixed',
                'confirmations_required' => 6,
                'rpc_endpoints' => json_encode([
                    'https://blockchain.info',
                    'https://blockstream.info/api',
                ]),
                'explorer_url' => 'https://www.blockchain.com/explorer',
                'explorer_tx_path' => '/btc/tx/',
                'explorer_address_path' => '/btc/address/',
                'is_active' => true,
                'deposit_enabled' => true,
                'withdrawal_enabled' => true,
                'exchange_enabled' => true,
                'payment_gateway_enabled' => true,
                'sort_order' => 1,
                'description' => 'Bitcoin is the first decentralized cryptocurrency.',
            ],

            // Ethereum
            [
                'code' => 'ETH',
                'name' => 'Ethereum',
                'symbol' => 'Ξ',
                'network' => 'ethereum',
                'contract_address' => null,
                'token_standard' => 'native',
                'decimals' => 18,
                'chain_id' => '1',
                'icon' => '/assets/crypto/eth.svg',
                'color' => '#627EEA',
                'price_source' => 'api',
                'coingecko_id' => 'ethereum',
                'binance_symbol' => 'ETHUSDT',
                'manual_price_thb' => null,
                'exchange_fee_percentage' => 1.0,
                'min_exchange_amount' => 100,
                'min_deposit' => 0.001,
                'min_withdrawal' => 0.01,
                'max_withdrawal' => 100,
                'withdrawal_fee' => 0.005,
                'withdrawal_fee_type' => 'fixed',
                'confirmations_required' => 12,
                'rpc_endpoints' => json_encode([
                    'https://eth-mainnet.g.alchemy.com/v2/YOUR_API_KEY',
                    'https://mainnet.infura.io/v3/YOUR_API_KEY',
                ]),
                'explorer_url' => 'https://etherscan.io',
                'explorer_tx_path' => '/tx/',
                'explorer_address_path' => '/address/',
                'is_active' => true,
                'deposit_enabled' => true,
                'withdrawal_enabled' => true,
                'exchange_enabled' => true,
                'payment_gateway_enabled' => true,
                'sort_order' => 2,
                'description' => 'Ethereum is a decentralized platform for smart contracts.',
            ],

            // USDT on Ethereum (ERC-20)
            [
                'code' => 'USDT',
                'name' => 'Tether USD (ERC-20)',
                'symbol' => '₮',
                'network' => 'ethereum',
                'contract_address' => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
                'token_standard' => 'ERC-20',
                'decimals' => 6,
                'chain_id' => '1',
                'icon' => '/assets/crypto/usdt.svg',
                'color' => '#26A17B',
                'price_source' => 'both',
                'coingecko_id' => 'tether',
                'binance_symbol' => 'USDTUSDT',
                'manual_price_thb' => 33.00,
                'exchange_fee_percentage' => 0.5,
                'min_exchange_amount' => 50,
                'min_deposit' => 1,
                'min_withdrawal' => 10,
                'max_withdrawal' => 100000,
                'withdrawal_fee' => 1,
                'withdrawal_fee_type' => 'fixed',
                'confirmations_required' => 12,
                'rpc_endpoints' => json_encode([
                    'https://eth-mainnet.g.alchemy.com/v2/YOUR_API_KEY',
                    'https://mainnet.infura.io/v3/YOUR_API_KEY',
                ]),
                'explorer_url' => 'https://etherscan.io',
                'explorer_tx_path' => '/tx/',
                'explorer_address_path' => '/token/0xdAC17F958D2ee523a2206206994597C13D831ec7?a=',
                'is_active' => true,
                'deposit_enabled' => true,
                'withdrawal_enabled' => true,
                'exchange_enabled' => true,
                'payment_gateway_enabled' => true,
                'sort_order' => 3,
                'description' => 'USDT is a stablecoin pegged to the US Dollar.',
            ],

            // USDC on Ethereum (ERC-20)
            [
                'code' => 'USDC',
                'name' => 'USD Coin (ERC-20)',
                'symbol' => 'USDC',
                'network' => 'ethereum',
                'contract_address' => '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48',
                'token_standard' => 'ERC-20',
                'decimals' => 6,
                'chain_id' => '1',
                'icon' => '/assets/crypto/usdc.svg',
                'color' => '#2775CA',
                'price_source' => 'both',
                'coingecko_id' => 'usd-coin',
                'binance_symbol' => 'USDCUSDT',
                'manual_price_thb' => 33.00,
                'exchange_fee_percentage' => 0.5,
                'min_exchange_amount' => 50,
                'min_deposit' => 1,
                'min_withdrawal' => 10,
                'max_withdrawal' => 100000,
                'withdrawal_fee' => 1,
                'withdrawal_fee_type' => 'fixed',
                'confirmations_required' => 12,
                'rpc_endpoints' => json_encode([
                    'https://eth-mainnet.g.alchemy.com/v2/YOUR_API_KEY',
                    'https://mainnet.infura.io/v3/YOUR_API_KEY',
                ]),
                'explorer_url' => 'https://etherscan.io',
                'explorer_tx_path' => '/tx/',
                'explorer_address_path' => '/token/0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48?a=',
                'is_active' => true,
                'deposit_enabled' => true,
                'withdrawal_enabled' => true,
                'exchange_enabled' => true,
                'payment_gateway_enabled' => true,
                'sort_order' => 4,
                'description' => 'USDC is a fully-backed stablecoin pegged to the US Dollar.',
            ],

            // BNB on BSC
            [
                'code' => 'BNB',
                'name' => 'BNB (Binance Smart Chain)',
                'symbol' => 'BNB',
                'network' => 'bsc',
                'contract_address' => null,
                'token_standard' => 'native',
                'decimals' => 18,
                'chain_id' => '56',
                'icon' => '/assets/crypto/bnb.svg',
                'color' => '#F3BA2F',
                'price_source' => 'api',
                'coingecko_id' => 'binancecoin',
                'binance_symbol' => 'BNBUSDT',
                'manual_price_thb' => null,
                'exchange_fee_percentage' => 1.0,
                'min_exchange_amount' => 100,
                'min_deposit' => 0.001,
                'min_withdrawal' => 0.01,
                'max_withdrawal' => 100,
                'withdrawal_fee' => 0.0005,
                'withdrawal_fee_type' => 'fixed',
                'confirmations_required' => 15,
                'rpc_endpoints' => json_encode([
                    'https://bsc-dataseed.binance.org',
                    'https://bsc-dataseed1.defibit.io',
                    'https://bsc-dataseed1.ninicoin.io',
                ]),
                'explorer_url' => 'https://bscscan.com',
                'explorer_tx_path' => '/tx/',
                'explorer_address_path' => '/address/',
                'is_active' => true,
                'deposit_enabled' => true,
                'withdrawal_enabled' => true,
                'exchange_enabled' => true,
                'payment_gateway_enabled' => true,
                'sort_order' => 5,
                'description' => 'BNB is the native token of Binance Smart Chain.',
            ],

            // USDT on BSC (BEP-20)
            [
                'code' => 'USDT_BSC',
                'name' => 'Tether USD (BEP-20)',
                'symbol' => '₮',
                'network' => 'bsc',
                'contract_address' => '0x55d398326f99059fF775485246999027B3197955',
                'token_standard' => 'BEP-20',
                'decimals' => 18,
                'chain_id' => '56',
                'icon' => '/assets/crypto/usdt.svg',
                'color' => '#26A17B',
                'price_source' => 'both',
                'coingecko_id' => 'tether',
                'binance_symbol' => 'USDTUSDT',
                'manual_price_thb' => 33.00,
                'exchange_fee_percentage' => 0.5,
                'min_exchange_amount' => 50,
                'min_deposit' => 1,
                'min_withdrawal' => 10,
                'max_withdrawal' => 100000,
                'withdrawal_fee' => 0.5,
                'withdrawal_fee_type' => 'fixed',
                'confirmations_required' => 15,
                'rpc_endpoints' => json_encode([
                    'https://bsc-dataseed.binance.org',
                    'https://bsc-dataseed1.defibit.io',
                ]),
                'explorer_url' => 'https://bscscan.com',
                'explorer_tx_path' => '/tx/',
                'explorer_address_path' => '/token/0x55d398326f99059fF775485246999027B3197955?a=',
                'is_active' => true,
                'deposit_enabled' => true,
                'withdrawal_enabled' => true,
                'exchange_enabled' => true,
                'payment_gateway_enabled' => true,
                'sort_order' => 6,
                'description' => 'USDT on Binance Smart Chain (BEP-20).',
            ],

            // TPIX - Thai Prompt Native Token
            [
                'code' => 'TPIX',
                'name' => 'Thai Prompt Token',
                'symbol' => 'TPIX',
                'network' => 'tpix-chain',
                'contract_address' => null, // Native token
                'token_standard' => 'native',
                'decimals' => 18,
                'chain_id' => '88888', // Custom chain ID for TPIX Chain
                'icon' => '/assets/crypto/tpix.svg',
                'color' => '#FF6B35', // Thai Prompt brand color
                'price_source' => 'manual', // Internal pricing
                'coingecko_id' => null, // Not listed on CoinGecko yet
                'binance_symbol' => null,
                'manual_price_thb' => 3.50, // 1 TPIX = 3.50 THB
                'exchange_fee_percentage' => 0.25, // Lower fees for native token
                'min_exchange_amount' => 10,
                'min_deposit' => 1,
                'min_withdrawal' => 10,
                'max_withdrawal' => 1000000,
                'withdrawal_fee' => 0.1, // Very low fee
                'withdrawal_fee_type' => 'fixed',
                'confirmations_required' => 6,
                'rpc_endpoints' => json_encode([
                    'https://rpc.tpix.network',
                    'https://rpc-backup.tpix.network',
                ]),
                'explorer_url' => 'https://explorer.tpix.network',
                'explorer_tx_path' => '/tx/',
                'explorer_address_path' => '/address/',
                'is_active' => true,
                'deposit_enabled' => true,
                'withdrawal_enabled' => true,
                'exchange_enabled' => true,
                'payment_gateway_enabled' => true,
                'sort_order' => 1, // Featured at top
                'description' => 'TPIX is the native utility token of Thai Prompt ecosystem, used for Food Passport NFTs, Carbon Credits, and platform fees.',
            ],

            // Polygon (MATIC)
            [
                'code' => 'MATIC',
                'name' => 'Polygon',
                'symbol' => 'MATIC',
                'network' => 'polygon',
                'contract_address' => null,
                'token_standard' => 'native',
                'decimals' => 18,
                'chain_id' => '137',
                'icon' => '/assets/crypto/matic.svg',
                'color' => '#8247E5',
                'price_source' => 'api',
                'coingecko_id' => 'matic-network',
                'binance_symbol' => 'MATICUSDT',
                'manual_price_thb' => null,
                'exchange_fee_percentage' => 1.0,
                'min_exchange_amount' => 50,
                'min_deposit' => 1,
                'min_withdrawal' => 10,
                'max_withdrawal' => 10000,
                'withdrawal_fee' => 0.1,
                'withdrawal_fee_type' => 'fixed',
                'confirmations_required' => 128,
                'rpc_endpoints' => json_encode([
                    'https://polygon-rpc.com',
                    'https://rpc-mainnet.matic.network',
                    'https://rpc-mainnet.maticvigil.com',
                ]),
                'explorer_url' => 'https://polygonscan.com',
                'explorer_tx_path' => '/tx/',
                'explorer_address_path' => '/address/',
                'is_active' => true,
                'deposit_enabled' => true,
                'withdrawal_enabled' => true,
                'exchange_enabled' => true,
                'payment_gateway_enabled' => true,
                'sort_order' => 7,
                'description' => 'Polygon is a Layer 2 scaling solution for Ethereum.',
            ],

            // USDT on Polygon
            [
                'code' => 'USDT_POLYGON',
                'name' => 'Tether USD (Polygon)',
                'symbol' => '₮',
                'network' => 'polygon',
                'contract_address' => '0xc2132D05D31c914a87C6611C10748AEb04B58e8F',
                'token_standard' => 'ERC-20',
                'decimals' => 6,
                'chain_id' => '137',
                'icon' => '/assets/crypto/usdt.svg',
                'color' => '#26A17B',
                'price_source' => 'both',
                'coingecko_id' => 'tether',
                'binance_symbol' => 'USDTUSDT',
                'manual_price_thb' => 33.00,
                'exchange_fee_percentage' => 0.5,
                'min_exchange_amount' => 50,
                'min_deposit' => 1,
                'min_withdrawal' => 10,
                'max_withdrawal' => 100000,
                'withdrawal_fee' => 0.5,
                'withdrawal_fee_type' => 'fixed',
                'confirmations_required' => 128,
                'rpc_endpoints' => json_encode([
                    'https://polygon-rpc.com',
                    'https://rpc-mainnet.matic.network',
                ]),
                'explorer_url' => 'https://polygonscan.com',
                'explorer_tx_path' => '/tx/',
                'explorer_address_path' => '/token/0xc2132D05D31c914a87C6611C10748AEb04B58e8F?a=',
                'is_active' => true,
                'deposit_enabled' => true,
                'withdrawal_enabled' => true,
                'exchange_enabled' => true,
                'payment_gateway_enabled' => true,
                'sort_order' => 8,
                'description' => 'USDT on Polygon Network.',
            ],
        ];
    }
}
