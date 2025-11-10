<?php

namespace App\Services\Crypto;

use App\Models\User;
use App\Models\CryptoWallet;
use App\Models\CryptoAddress;
use App\Models\CryptoCurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

/**
 * HD Wallet Service
 *
 * Implements Hierarchical Deterministic (HD) Wallet functionality based on BIP32/BIP44.
 * Allows creation of a single master wallet that can derive unlimited child wallets.
 * All child wallets are derived from the master seed using deterministic derivation paths.
 */
class HDWalletService
{
    /**
     * BIP39 word list for generating mnemonic seed phrases
     * This is a simplified version - in production, use full BIP39 wordlist
     */
    private const BIP39_WORDLIST = [
        'abandon', 'ability', 'able', 'about', 'above', 'absent', 'absorb', 'abstract',
        'absurd', 'abuse', 'access', 'accident', 'account', 'accuse', 'achieve', 'acid',
        'acoustic', 'acquire', 'across', 'act', 'action', 'actor', 'actress', 'actual',
        'adapt', 'add', 'addict', 'address', 'adjust', 'admit', 'adult', 'advance',
        'advice', 'aerobic', 'afford', 'afraid', 'again', 'age', 'agent', 'agree',
        'ahead', 'aim', 'air', 'airport', 'aisle', 'alarm', 'album', 'alcohol',
        'alert', 'alien', 'all', 'alley', 'allow', 'almost', 'alone', 'alpha',
        'already', 'also', 'alter', 'always', 'amateur', 'amazing', 'among', 'amount',
        'amused', 'analyst', 'anchor', 'ancient', 'anger', 'angle', 'angry', 'animal',
    ];

    /**
     * Create or get master wallet for user
     *
     * @param User $user
     * @param array $options
     * @return CryptoWallet
     */
    public function getOrCreateMasterWallet(User $user, array $options = []): CryptoWallet
    {
        // Check if user already has a master wallet
        $masterWallet = CryptoWallet::where('user_id', $user->id)
            ->where('is_master_wallet', true)
            ->where('wallet_type', 'custodial')
            ->first();

        if ($masterWallet) {
            return $masterWallet;
        }

        // Create new master wallet
        return $this->createMasterWallet($user, $options);
    }

    /**
     * Create a new master wallet
     *
     * @param User $user
     * @param array $options
     * @return CryptoWallet
     */
    public function createMasterWallet(User $user, array $options = []): CryptoWallet
    {
        DB::beginTransaction();
        try {
            // Generate master seed phrase (24 words for better security)
            $masterSeed = $this->generateSeedPhrase(24);

            // Create master wallet
            $masterWallet = CryptoWallet::create([
                'user_id' => $user->id,
                'wallet_type' => 'custodial',
                'is_master_wallet' => true,
                'name' => $options['name'] ?? 'Master Wallet',
                'master_seed_encrypted' => Crypt::encryptString($masterSeed),
                'derivation_path' => "m/44'/60'/0'", // BIP44 base path
                'derivation_index' => 0,
                'total_derived_wallets' => 0,
                'status' => 'active',
                'is_default' => true,
                'device_info' => $options['device_info'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Set PIN if provided
            if (isset($options['pin'])) {
                $masterWallet->setPin($options['pin']);
            }

            DB::commit();

            Log::info('Master HD wallet created', [
                'user_id' => $user->id,
                'wallet_id' => $masterWallet->id,
            ]);

            return $masterWallet;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create master wallet', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Derive a new child wallet from master wallet
     *
     * @param CryptoWallet $masterWallet
     * @param array $options
     * @return CryptoWallet
     */
    public function deriveChildWallet(CryptoWallet $masterWallet, array $options = []): CryptoWallet
    {
        if (!$masterWallet->is_master_wallet) {
            throw new \InvalidArgumentException('Wallet must be a master wallet to derive child wallets');
        }

        DB::beginTransaction();
        try {
            // Get next derivation index
            $derivationIndex = $masterWallet->total_derived_wallets;

            // Create child wallet
            $childWallet = CryptoWallet::create([
                'user_id' => $masterWallet->user_id,
                'wallet_type' => 'custodial',
                'is_master_wallet' => false,
                'master_wallet_id' => $masterWallet->id,
                'name' => $options['name'] ?? "Wallet #{$derivationIndex}",
                'derivation_path' => $this->buildDerivationPath($masterWallet, $derivationIndex),
                'derivation_index' => $derivationIndex,
                'status' => 'active',
                'is_default' => false,
                'device_info' => $options['device_info'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Increment master wallet's derived wallet count
            $masterWallet->increment('total_derived_wallets');

            // Generate addresses for all active currencies
            $this->generateAddressesForChildWallet($childWallet, $masterWallet);

            DB::commit();

            Log::info('Child wallet derived from master', [
                'user_id' => $masterWallet->user_id,
                'master_wallet_id' => $masterWallet->id,
                'child_wallet_id' => $childWallet->id,
                'derivation_index' => $derivationIndex,
            ]);

            return $childWallet;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to derive child wallet', [
                'master_wallet_id' => $masterWallet->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate addresses for child wallet based on master seed
     *
     * @param CryptoWallet $childWallet
     * @param CryptoWallet $masterWallet
     * @return void
     */
    private function generateAddressesForChildWallet(CryptoWallet $childWallet, CryptoWallet $masterWallet): void
    {
        $currencies = CryptoCurrency::active()->get();

        foreach ($currencies as $currency) {
            // Derive address from master seed
            $address = $this->deriveAddressForCurrency(
                $masterWallet,
                $currency,
                $childWallet->derivation_index
            );

            CryptoAddress::create([
                'crypto_wallet_id' => $childWallet->id,
                'crypto_currency_id' => $currency->id,
                'address' => $address,
                'network' => $currency->network,
                'is_imported' => false,
                'address_index' => $childWallet->derivation_index,
                'is_active' => true,
                'is_watched' => true,
            ]);
        }
    }

    /**
     * Derive address for specific currency from master seed
     *
     * @param CryptoWallet $masterWallet
     * @param CryptoCurrency $currency
     * @param int $derivationIndex
     * @return string
     */
    private function deriveAddressForCurrency(
        CryptoWallet $masterWallet,
        CryptoCurrency $currency,
        int $derivationIndex
    ): string {
        // This is a simplified implementation
        // In production, use proper BIP32 derivation with the master seed

        $masterSeed = $masterWallet->getDecryptedSeed() ?? $this->getDecryptedMasterSeed($masterWallet);

        // Create deterministic address using hash of master seed + derivation index + currency
        $hash = hash('sha256', $masterSeed . $derivationIndex . $currency->code);

        return $this->formatAddressForNetwork($hash, $currency->network);
    }

    /**
     * Get decrypted master seed from master wallet
     *
     * @param CryptoWallet $masterWallet
     * @return string
     */
    private function getDecryptedMasterSeed(CryptoWallet $masterWallet): string
    {
        if (!$masterWallet->is_master_wallet || !$masterWallet->master_seed_encrypted) {
            throw new \InvalidArgumentException('Not a valid master wallet');
        }

        try {
            return Crypt::decryptString($masterWallet->master_seed_encrypted);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt master seed', [
                'wallet_id' => $masterWallet->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Format address based on network type
     *
     * @param string $hash
     * @param string $network
     * @return string
     */
    private function formatAddressForNetwork(string $hash, string $network): string
    {
        switch ($network) {
            case 'ethereum':
            case 'bsc':
            case 'polygon':
                // EVM address format (0x + 40 hex chars)
                return '0x' . substr($hash, 0, 40);

            case 'bitcoin':
                // Bitcoin address format (simplified)
                return '1' . substr($hash, 0, 33);

            case 'tron':
                // TRON address format
                return 'T' . substr($hash, 0, 33);

            default:
                return '0x' . substr($hash, 0, 40);
        }
    }

    /**
     * Build BIP44 derivation path
     *
     * @param CryptoWallet $masterWallet
     * @param int $accountIndex
     * @return string
     */
    private function buildDerivationPath(CryptoWallet $masterWallet, int $accountIndex): string
    {
        // BIP44 format: m/purpose'/coin_type'/account'/change/address_index
        // We use account index as the differentiator for each wallet
        return "m/44'/60'/{$accountIndex}'/0/0";
    }

    /**
     * Generate BIP39 mnemonic seed phrase
     *
     * @param int $wordCount (12, 15, 18, 21, or 24)
     * @return string
     */
    private function generateSeedPhrase(int $wordCount = 12): string
    {
        if (!in_array($wordCount, [12, 15, 18, 21, 24])) {
            throw new \InvalidArgumentException('Word count must be 12, 15, 18, 21, or 24');
        }

        $words = [];
        $wordlistSize = count(self::BIP39_WORDLIST);

        for ($i = 0; $i < $wordCount; $i++) {
            $words[] = self::BIP39_WORDLIST[random_int(0, $wordlistSize - 1)];
        }

        return implode(' ', $words);
    }

    /**
     * Get all child wallets for a master wallet
     *
     * @param CryptoWallet $masterWallet
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getChildWallets(CryptoWallet $masterWallet)
    {
        if (!$masterWallet->is_master_wallet) {
            throw new \InvalidArgumentException('Wallet must be a master wallet');
        }

        return CryptoWallet::where('master_wallet_id', $masterWallet->id)
            ->orderBy('derivation_index')
            ->get();
    }

    /**
     * Get master wallet for a child wallet
     *
     * @param CryptoWallet $childWallet
     * @return CryptoWallet|null
     */
    public function getMasterWallet(CryptoWallet $childWallet): ?CryptoWallet
    {
        if ($childWallet->is_master_wallet) {
            return $childWallet;
        }

        return CryptoWallet::find($childWallet->master_wallet_id);
    }

    /**
     * Get wallet statistics including child wallets
     *
     * @param CryptoWallet $masterWallet
     * @return array
     */
    public function getMasterWalletStatistics(CryptoWallet $masterWallet): array
    {
        if (!$masterWallet->is_master_wallet) {
            throw new \InvalidArgumentException('Wallet must be a master wallet');
        }

        $childWallets = $this->getChildWallets($masterWallet);

        $totalBalanceThb = 0;
        $totalTransactions = 0;
        $totalAddresses = 0;

        foreach ($childWallets as $wallet) {
            $totalBalanceThb += $wallet->getTotalBalanceInTHB();
            $totalTransactions += $wallet->total_transactions;
            $totalAddresses += $wallet->cryptoAddresses()->count();
        }

        return [
            'total_child_wallets' => $childWallets->count(),
            'total_balance_thb' => $totalBalanceThb,
            'total_transactions' => $totalTransactions,
            'total_addresses' => $totalAddresses,
            'child_wallets' => $childWallets,
        ];
    }

    /**
     * Regenerate address for existing child wallet (in case of address collision)
     *
     * @param CryptoWallet $childWallet
     * @param CryptoCurrency $currency
     * @return CryptoAddress
     */
    public function regenerateAddress(CryptoWallet $childWallet, CryptoCurrency $currency): CryptoAddress
    {
        if ($childWallet->is_master_wallet) {
            throw new \InvalidArgumentException('Cannot regenerate address for master wallet');
        }

        $masterWallet = $this->getMasterWallet($childWallet);
        if (!$masterWallet) {
            throw new \Exception('Master wallet not found');
        }

        DB::beginTransaction();
        try {
            // Find existing address
            $existingAddress = $childWallet->cryptoAddresses()
                ->where('crypto_currency_id', $currency->id)
                ->first();

            if ($existingAddress) {
                // Deactivate old address
                $existingAddress->update(['is_active' => false]);
            }

            // Generate new address
            $newAddress = $this->deriveAddressForCurrency(
                $masterWallet,
                $currency,
                $childWallet->derivation_index + 1000 // Use offset to ensure uniqueness
            );

            $cryptoAddress = CryptoAddress::create([
                'crypto_wallet_id' => $childWallet->id,
                'crypto_currency_id' => $currency->id,
                'address' => $newAddress,
                'network' => $currency->network,
                'is_imported' => false,
                'address_index' => $childWallet->derivation_index + 1000,
                'is_active' => true,
                'is_watched' => true,
            ]);

            DB::commit();

            Log::info('Address regenerated for child wallet', [
                'child_wallet_id' => $childWallet->id,
                'currency' => $currency->code,
                'old_address' => $existingAddress?->address,
                'new_address' => $newAddress,
            ]);

            return $cryptoAddress;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
