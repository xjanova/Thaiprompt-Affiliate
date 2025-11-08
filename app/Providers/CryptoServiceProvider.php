<?php

namespace App\Providers;

use App\Services\Crypto\CryptoExchangeService;
use App\Services\Crypto\CryptoPriceService;
use App\Services\Crypto\CryptoWalletService;
use Illuminate\Support\ServiceProvider;

class CryptoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
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
                $app->make(CryptoWalletService::class),
                $app->make(CryptoPriceService::class)
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
