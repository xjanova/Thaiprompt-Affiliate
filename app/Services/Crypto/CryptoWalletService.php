<?php

namespace App\Services\Crypto;

use App\Models\CryptoAddress;
use App\Models\CryptoCurrency;
use App\Models\CryptoDepositAddress;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
                'is_default' => ! $user->cryptoWallets()->exists(),
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
     */
    public function getOrCreateDefaultWallet(User $user): ?CryptoWallet
    {
        // First try to get default wallet
        $wallet = $user->cryptoWallets()->where('is_default', true)->first();

        if (! $wallet) {
            // Auto-create custodial wallet (HD Wallet system)
            $wallet = $this->createCustodialWallet($user);
        }

        return $wallet;
    }

    /**
     * Create additional wallet for user (derives from existing master wallet)
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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllChildWallets(User $user)
    {
        $masterWallet = $this->getMasterWallet($user);

        if (! $masterWallet) {
            return collect();
        }

        return $this->hdWalletService->getChildWallets($masterWallet);
    }

    /**
     * Get wallet balance for specific currency
     */
    public function getBalance(CryptoWallet $wallet, CryptoCurrency $currency): float
    {
        return $wallet->cryptoAddresses()
            ->where('crypto_currency_id', $currency->id)
            ->sum('balance');
    }

    /**
     * Get all balances for wallet
     */
    public function getAllBalances(CryptoWallet $wallet): array
    {
        $balances = [];

        foreach ($wallet->cryptoAddresses as $address) {
            $code = $address->currency->code;
            if (! isset($balances[$code])) {
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
     * โอนเหรียญระหว่างสมาชิกภายในระบบ (ย้ายยอดในฐานข้อมูล ไม่ได้ส่งขึ้นเชน)
     *
     * @param  string  $amount  จำนวนเหรียญเป็นสตริงทศนิยม — ห้ามส่ง float ยอด 18 ตำแหน่งจะปัดเศษผิด
     * @return array{success: bool, message: string}
     */
    public function transfer(User $sender, User $recipient, CryptoCurrency $currency, string $amount): array
    {
        // ผู้รับที่ยังไม่เคยเปิดกระเป๋าต้องมีแถวให้ล็อกก่อนเข้า transaction
        CryptoWallet::firstOrCreate(
            ['user_id' => $recipient->id, 'currency_id' => $currency->id],
            ['balance' => 0, 'status' => 'active', 'wallet_type' => 'custodial']
        );

        return DB::transaction(function () use ($sender, $recipient, $currency, $amount) {
            // ล็อกสองแถวในคำสั่งเดียวเรียงตาม id — A โอนหา B พร้อม B โอนหา A จะได้ไม่ deadlock
            $wallets = CryptoWallet::whereIn('user_id', [$sender->id, $recipient->id])
                ->where('currency_id', $currency->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->unique('user_id')
                ->keyBy('user_id');

            $fromWallet = $wallets->get($sender->id);
            $toWallet = $wallets->get($recipient->id);

            if (! $fromWallet || ! $fromWallet->isActive()) {
                return ['success' => false, 'message' => 'กระเป๋าของคุณถูกระงับการใช้งาน ทำรายการไม่ได้'];
            }

            if (! $toWallet || ! $toWallet->isActive()) {
                return ['success' => false, 'message' => 'กระเป๋าของผู้รับยังรับเหรียญไม่ได้'];
            }

            if (bccomp((string) $fromWallet->balance, $amount, 18) < 0) {
                return ['success' => false, 'message' => 'ยอดคงเหลือไม่พอ'];
            }

            $fromWallet->decrement('balance', $amount);
            $toWallet->increment('balance', $amount);

            // สองขาอ้างรหัสเดียวกัน ตามคู่รายการได้เมื่อต้องตรวจสอบย้อนหลัง
            $transferRef = (string) Str::uuid();
            $legs = [
                [$sender, $fromWallet, 'transfer_out', 'ส่งให้ '.$recipient->name, $recipient],
                [$recipient, $toWallet, 'transfer_in', 'รับจาก '.$sender->name, $sender],
            ];

            foreach ($legs as [$owner, $wallet, $type, $description, $counterparty]) {
                CryptoTransaction::create([
                    'user_id' => $owner->id,
                    'crypto_currency_id' => $currency->id,
                    'crypto_wallet_id' => $wallet->id,
                    'type' => $type,
                    'amount' => $amount,
                    'fee' => 0,
                    'from_address' => $fromWallet->address,
                    'to_address' => $toWallet->address,
                    'network' => $currency->network,
                    'status' => 'completed',
                    'confirmations_required' => 0,
                    'completed_at' => now(),
                    'description' => $description,
                    'metadata' => [
                        'internal_transfer' => true,
                        'transfer_ref' => $transferRef,
                        'counterparty_user_id' => $counterparty->id,
                    ],
                ]);
            }

            return ['success' => true, 'message' => 'ส่ง '.$amount.' '.$currency->code.' ให้ '.$recipient->name.' สำเร็จ'];
        });
    }

    /**
     * Generate addresses for all active currencies
     */
    public function generateAddressesForWallet(CryptoWallet $wallet): void
    {
        if (! $wallet->isCustodial()) {
            return;
        }

        $currencies = CryptoCurrency::active()->get();

        foreach ($currencies as $currency) {
            $this->generateAddressForCurrency($wallet, $currency);
        }
    }

    /**
     * Generate address for specific currency
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
     */
    private function generatePlaceholderAddress(string $network): string
    {
        switch ($network) {
            case 'ethereum':
            case 'bsc':
            case 'polygon':
                // EVM address format (0x + 40 hex chars)
                return '0x'.bin2hex(random_bytes(20));

            case 'bitcoin':
                // Bitcoin address format (simplified)
                return '1'.bin2hex(random_bytes(20));

            case 'tron':
                // TRON address format
                return 'T'.bin2hex(random_bytes(20));

            default:
                return bin2hex(random_bytes(32));
        }
    }

    /**
     * Get wallet statistics
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
