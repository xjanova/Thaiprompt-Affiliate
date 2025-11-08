<?php

namespace App\Providers;

use App\Services\Crypto\BlockchainIndexerService;
use App\Services\Crypto\BlockchainTransactionService;
use App\Services\Crypto\ClientSideTransactionService;
use App\Services\Crypto\CryptoExchangeService;
use App\Services\Crypto\CryptoPriceService;
use App\Services\Crypto\CryptoWalletService;
use App\Services\Crypto\DepositDetectionService;
use App\Services\Crypto\Web3Service;
use App\Services\Crypto\WithdrawalProcessingService;
use App\Services\WalletService;
use Illuminate\Support\ServiceProvider;

class CryptoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Web3Service as singleton
        $this->app->singleton(Web3Service::class, function ($app) {
            return new Web3Service();
        });

        // Register BlockchainIndexerService as singleton
        $this->app->singleton(BlockchainIndexerService::class, function ($app) {
            return new BlockchainIndexerService();
        });

        // Register CryptoWalletService as singleton
        $this->app->singleton(CryptoWalletService::class, function ($app) {
            return new CryptoWalletService();
        });

        // Register CryptoPriceService as singleton
        $this->app->singleton(CryptoPriceService::class, function ($app) {
            return new CryptoPriceService();
        });

        // Register CryptoExchangeService as singleton
        $this->app->singleton(CryptoExchangeService::class, function ($app) {
            return new CryptoExchangeService(
                $app->make(CryptoPriceService::class),
                $app->make(WalletService::class),
                $app->make(CryptoWalletService::class)
            );
        });

        // Register BlockchainTransactionService as singleton
        $this->app->singleton(BlockchainTransactionService::class, function ($app) {
            return new BlockchainTransactionService(
                $app->make(Web3Service::class),
                $app->make(CryptoPriceService::class)
            );
        });

        // Register ClientSideTransactionService as singleton
        $this->app->singleton(ClientSideTransactionService::class, function ($app) {
            return new ClientSideTransactionService(
                $app->make(Web3Service::class),
                $app->make(CryptoPriceService::class)
            );
        });

        // Register DepositDetectionService as singleton
        $this->app->singleton(DepositDetectionService::class, function ($app) {
            return new DepositDetectionService(
                $app->make(Web3Service::class),
                $app->make(CryptoPriceService::class),
                $app->make(CryptoWalletService::class)
            );
        });

        // Register WithdrawalProcessingService as singleton
        $this->app->singleton(WithdrawalProcessingService::class, function ($app) {
            return new WithdrawalProcessingService(
                $app->make(BlockchainTransactionService::class),
                $app->make(CryptoWalletService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
