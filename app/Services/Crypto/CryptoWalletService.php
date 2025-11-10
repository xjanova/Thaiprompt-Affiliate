<?php

namespace App\Services\Crypto;

use App\Models\User;
use App\Models\CryptoWallet;
use App\Models\CryptoAddress;
use App\Models\CryptoCurrency;
use App\Models\CryptoDepositAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class CryptoWalletService
{
    protected HDWalletService $hdWalletService;

    public function __construct(HDWalletService $hdWalletService)
    {
        $this->hdWalletService = $hdWalletService;
    }
    /**
     * Create a new custodial wallet for user
     * Now uses HD Wallet system - creates master wallet and first child wallet
     *
     * @param User $user
     * @param string|null $pin
     * @param array $options
     * @return CryptoWallet
     */
    public function createCustodialWallet(User $user, ?string $pin = null, array $options = []): CryptoWallet
    {
        DB::beginTransaction();
        try {
            // Get or create master wallet for user
            $masterWallet = $this->hdWalletService->getOrCreateMasterWallet($user, [
                'pin' => $pin,
                'device_info' => $options['device_info'] ?? null,
            ]);

            // Derive first child wallet from master
            $wallet = $this->hdWalletService->deriveChildWallet($masterWallet, [
                'name' => $options['name'] ?? 'My Crypto Wallet',
                'device_info' => $options['device_info'] ?? null,
            ]);

            DB::commit();

            Log::info('Custodial crypto wallet created (HD Wallet)', [
                'user_id' => $user->id,
                'master_wallet_id' => $masterWallet->id,
                'wallet_id' => $wallet->id,
            ]);

            return $wallet;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create custodial wallet', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Connect external wallet (MetaMask, WalletConnect)
     *
     * @param User $user
     * @param string $address
     * @param string $network
     * @param array $options
     * @return CryptoWallet
     */
    public function connectExternalWallet(User $user, string $address, string $network, array $options = []): CryptoWallet
    {
        DB::beginTransaction();
        try {
            // Create crypto wallet
            $wallet = CryptoWallet::create([
                'user_id' => $user->id,
                'wallet_type' => 'external',
                'name' => $options['name'] ?? 'MetaMask Wallet',
                'status' => 'active',
                'is_default' => !$user->cryptoWallets()->exists(),
                'is_verified' => false,
                'device_info' => $options['device_info'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Add the main address
            $currency = CryptoCurrency::where('network', $network)->first();

            if ($currency) {
                CryptoAddress::create([
                    'crypto_wallet_id' => $wallet->id,
                    'crypto_currency_id' => $currency->id,
                    'address' => $address,
                    'network' => $network,
                    'is_imported' => true,
                    'is_active' => true,
                    'is_watched' => true,
                ]);
            }

            DB::commit();

            Log::info('External crypto wallet connected', [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'address' => $address,
                'network' => $network,
            ]);

            return $wallet;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to connect external wallet', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify external wallet ownership with signature
     *
     * @param CryptoWallet $wallet
     * @param string $signature
     * @param string $message
     * @return bool
     */
    public function verifyWalletOwnership(CryptoWallet $wallet, string $signature, string $message): bool
    {
        // This will be implemented in Web3Service
        // For now, just mark as verified
        $wallet->update([
            'is_verified' => true,
            'verified_at' => now(),
            'signature_proof' => $signature,
        ]);

        return true;
    }

    /**
     * Get or create default wallet for user
     *
     * @param User $user
     * @return CryptoWallet|null
     */
    public function getOrCreateDefaultWallet(User $user): ?CryptoWallet
    {
        // First try to get default wallet
        $wallet = $user->cryptoWallets()->where('is_default', true)->first();

        if (!$wallet) {
            // Auto-create custodial wallet (HD Wallet system)
            $wallet = $this->createCustodialWallet($user);
        }

        return $wallet;
    }

    /**
     * Create additional wallet for user (derives from existing master wallet)
     *
     * @param User $user
     * @param array $options
     * @return CryptoWallet
     */
    public function createAdditionalWallet(User $user, array $options = []): CryptoWallet
    {
        // Get existing master wallet
        $masterWallet = $this->hdWalletService->getOrCreateMasterWallet($user);

        // Derive new child wallet
        return $this->hdWalletService->deriveChildWallet($masterWallet, $options);
    }

    /**
     * Get master wallet for user
     *
     * @param User $user
     * @return CryptoWallet|null
     */
    public function getMasterWallet(User $user): ?CryptoWallet
    {
        return CryptoWallet::where('user_id', $user->id)
            ->where('is_master_wallet', true)
            ->first();
    }

    /**
     * Get all child wallets for user
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllChildWallets(User $user)
    {
        $masterWallet = $this->getMasterWallet($user);

        if (!$masterWallet) {
            return collect();
        }

        return $this->hdWalletService->getChildWallets($masterWallet);
    }

    /**
     * Get wallet balance for specific currency
     *
     * @param CryptoWallet $wallet
     * @param CryptoCurrency $currency
     * @return float
     */
    public function getBalance(CryptoWallet $wallet, CryptoCurrency $currency): float
    {
        return $wallet->cryptoAddresses()
            ->where('crypto_currency_id', $currency->id)
            ->sum('balance');
    }

    /**
     * Get all balances for wallet
     *
     * @param CryptoWallet $wallet
     * @return array
     */
    public function getAllBalances(CryptoWallet $wallet): array
    {
        $balances = [];

        foreach ($wallet->cryptoAddresses as $address) {
            $code = $address->currency->code;
            if (!isset($balances[$code])) {
                $balances[$code] = [
                    'currency' => $address->currency,
                    'balance' => 0,
                    'balance_thb' => 0,
                ];
            }

            $balances[$code]['balance'] += $address->balance;
            $balances[$code]['balance_thb'] += $address->balance_in_thb;
        }

        return $balances;
    }

    /**
     * Generate addresses for all active currencies
     *
     * @param CryptoWallet $wallet
     * @return void
     */
    public function generateAddressesForWallet(CryptoWallet $wallet): void
    {
        if (!$wallet->isCustodial()) {
            return;
        }

        $currencies = CryptoCurrency::active()->get();

        foreach ($currencies as $currency) {
            $this->generateAddressForCurrency($wallet, $currency);
        }
    }

    /**
     * Generate address for specific currency
     *
     * @param CryptoWallet $wallet
     * @param CryptoCurrency $currency
     * @param int $addressIndex
     * @return CryptoAddress
     */
    public function generateAddressForCurrency(
        CryptoWallet $wallet,
        CryptoCurrency $currency,
        int $addressIndex = 0
    ): CryptoAddress {
        // This is a placeholder - actual implementation will use Web3Service
        // For now, generate a random address format
        $address = $this->generatePlaceholderAddress($currency->network);

        $cryptoAddress = CryptoAddress::create([
            'crypto_wallet_id' => $wallet->id,
            'crypto_currency_id' => $currency->id,
            'address' => $address,
            'network' => $currency->network,
            'is_imported' => false,
            'address_index' => $addressIndex,
            'is_active' => true,
            'is_watched' => true,
        ]);

        // Create deposit address entry
        CryptoDepositAddress::create([
            'user_id' => $wallet->user_id,
            'crypto_wallet_id' => $wallet->id,
            'crypto_currency_id' => $currency->id,
            'address' => $address,
            'network' => $currency->network,
            'is_monitored' => true,
            'is_active' => true,
        ]);

        return $cryptoAddress;
    }

    /**
     * Generate seed phrase (12 words BIP39)
     *
     * @return string
     */
    private function generateSeedPhrase(): string
    {
        // This is a placeholder - actual implementation will use BIP39 library
        // For now, return a placeholder seed phrase
        $words = ['abandon', 'ability', 'able', 'about', 'above', 'absent', 'absorb', 'abstract', 'absurd', 'abuse', 'access', 'accident'];
        return implode(' ', $words);
    }

    /**
     * Generate placeholder address (will be replaced with real implementation)
     *
     * @param string $network
     * @return string
     */
    private function generatePlaceholderAddress(string $network): string
    {
        switch ($network) {
            case 'ethereum':
            case 'bsc':
            case 'polygon':
                // EVM address format (0x + 40 hex chars)
                return '0x' . bin2hex(random_bytes(20));

            case 'bitcoin':
                // Bitcoin address format (simplified)
                return '1' . bin2hex(random_bytes(20));

            case 'tron':
                // TRON address format
                return 'T' . bin2hex(random_bytes(20));

            default:
                return bin2hex(random_bytes(32));
        }
    }

    /**
     * Get wallet statistics
     *
     * @param CryptoWallet $wallet
     * @return array
     */
    public function getWalletStatistics(CryptoWallet $wallet): array
    {
        return [
            'total_balance_thb' => $wallet->getTotalBalanceInTHB(),
            'total_transactions' => $wallet->total_transactions,
            'last_activity' => $wallet->last_activity_at,
            'addresses_count' => $wallet->cryptoAddresses()->count(),
            'active_addresses' => $wallet->cryptoAddresses()->active()->count(),
            'deposit_count' => $wallet->cryptoTransactions()->type('deposit')->count(),
            'withdrawal_count' => $wallet->cryptoTransactions()->type('withdrawal')->count(),
            'exchange_count' => $wallet->exchangeTransactionsFrom()->count() +
                              $wallet->exchangeTransactionsTo()->count(),
        ];
    }

    /**
     * Lock wallet
     *
     * @param CryptoWallet $wallet
     * @param int $minutes
     * @return void
     */
    public function lockWallet(CryptoWallet $wallet, int $minutes = 30): void
    {
        $wallet->lock($minutes);

        Log::warning('Crypto wallet locked', [
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'locked_until' => $wallet->locked_until,
        ]);
    }

    /**
     * Unlock wallet
     *
     * @param CryptoWallet $wallet
     * @return void
     */
    public function unlockWallet(CryptoWallet $wallet): void
    {
        $wallet->unlock();

        Log::info('Crypto wallet unlocked', [
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
        ]);
    }

    /**
     * Delete wallet (soft delete)
     *
     * @param CryptoWallet $wallet
     * @return bool
     */
    public function deleteWallet(CryptoWallet $wallet): bool
    {
        DB::beginTransaction();
        try {
            // Check if wallet has balance
            $totalBalance = $wallet->getTotalBalanceInTHB();
            if ($totalBalance > 0) {
                throw new \Exception('Cannot delete wallet with balance. Please withdraw all funds first.');
            }

            // Soft delete all related records
            $wallet->cryptoAddresses()->delete();
            $wallet->depositAddresses()->delete();
            $wallet->delete();

            DB::commit();

            Log::info('Crypto wallet deleted', [
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete crypto wallet', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
