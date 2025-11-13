<?php

namespace App\Services\TPIX;

use App\Models\User;
use App\Models\TPIXToken;
use App\Models\TPIXTokenBalance;
use App\Models\TPIXTokenTransfer;
use App\Models\CryptoWallet;
use App\Models\CryptoCurrency;
use App\Services\Crypto\TPIXBlockchainService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Token Wallet Integration Service
 *
 * Integrates TPIX tokens with the existing CryptoWallet system
 */
class TokenWalletIntegrationService
{
    protected TPIXBlockchainService $blockchain;

    public function __construct(TPIXBlockchainService $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    /**
     * Get or create TPIX wallet for user
     */
    public function getOrCreateTPIXWallet(User $user): CryptoWallet
    {
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

        if (!$tpixCurrency) {
            throw new Exception('TPIX currency not found in database');
        }

        // Get or create wallet
        $wallet = CryptoWallet::firstOrCreate(
            [
                'user_id' => $user->id,
                'currency_id' => $tpixCurrency->id,
            ],
            [
                'balance' => 0,
                'status' => 'active',
                'wallet_type' => 'custodial',
            ]
        );

        // Generate address if not exists
        if (!$wallet->address) {
            $wallet->address = $this->generateTPIXAddress($user);
            $wallet->save();
        }

        return $wallet;
    }

    /**
     * Get user's token balance
     */
    public function getTokenBalance(User $user, TPIXToken $token): TPIXTokenBalance
    {
        $wallet = $this->getOrCreateTPIXWallet($user);

        return TPIXTokenBalance::firstOrCreate(
            [
                'token_id' => $token->id,
                'user_id' => $user->id,
            ],
            [
                'wallet_address' => $wallet->address,
                'balance' => 0,
                'available_balance' => 0,
            ]
        );
    }

    /**
     * Transfer token to another user
     */
    public function transferToken(
        TPIXToken $token,
        User $fromUser,
        string $toAddressOrEmail,
        string $amount
    ): array {
        DB::beginTransaction();
        try {
            // Get sender's balance
            $fromBalance = $this->getTokenBalance($fromUser, $token);

            // Check if sufficient balance
            if (bccomp($fromBalance->available_balance, $amount, 8) < 0) {
                throw new Exception('Insufficient token balance');
            }

            // Check if frozen
            if ($fromBalance->is_frozen) {
                throw new Exception('Your address is frozen for this token');
            }

            // Find recipient
            $toUser = $this->findRecipient($toAddressOrEmail);
            $toAddress = null;

            if ($toUser) {
                $toWallet = $this->getOrCreateTPIXWallet($toUser);
                $toAddress = $toWallet->address;
                $toBalance = $this->getTokenBalance($toUser, $token);

                // Check if recipient is frozen
                if ($toBalance->is_frozen) {
                    throw new Exception('Recipient address is frozen for this token');
                }
            } else {
                // External address
                $toAddress = $toAddressOrEmail;
                if (!$this->blockchain->isValidAddress($toAddress)) {
                    throw new Exception('Invalid recipient address or email');
                }
            }

            // Simulate blockchain transaction
            $txHash = $this->simulateTokenTransfer($token, $fromBalance->wallet_address, $toAddress, $amount);

            // Update sender balance
            $fromBalance->decrement('balance', $amount);
            $fromBalance->decrement('available_balance', $amount);
            $fromBalance->increment('total_sent', $amount);
            $fromBalance->increment('transfers_count');
            $fromBalance->touch('last_activity_at');

            // Update recipient balance (if internal)
            if ($toUser) {
                $toBalance->increment('balance', $amount);
                $toBalance->increment('available_balance', $amount);
                $toBalance->increment('total_received', $amount);

                if (!$toBalance->first_received_at) {
                    $toBalance->update(['first_received_at' => now()]);
                }

                $toBalance->touch('last_activity_at');
            }

            // Create transfer record
            $transfer = TPIXTokenTransfer::create([
                'token_id' => $token->id,
                'tx_hash' => $txHash,
                'from_address' => $fromBalance->wallet_address,
                'to_address' => $toAddress,
                'amount' => $amount,
                'from_user_id' => $fromUser->id,
                'to_user_id' => $toUser?->id,
                'transfer_type' => 'transfer',
                'status' => 'confirmed',
                'confirmations' => 5,
            ]);

            // Update token statistics
            $token->increment('transfers_count');
            $token->updateStatistics();

            DB::commit();

            Log::info('Token transferred', [
                'token_id' => $token->id,
                'from_user_id' => $fromUser->id,
                'to_user_id' => $toUser?->id,
                'amount' => $amount,
                'tx_hash' => $txHash
            ]);

            return [
                'success' => true,
                'tx_hash' => $txHash,
                'transfer_id' => $transfer->id,
                'explorer_url' => config('crypto.networks.tpix.explorer') . '/tx/' . $txHash
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Token transfer failed', [
                'token_id' => $token->id,
                'from_user_id' => $fromUser->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Buy token with TPIX
     */
    public function buyToken(User $user, TPIXToken $token, string $tpixAmount): array
    {
        DB::beginTransaction();
        try {
            // Get user's TPIX wallet
            $tpixWallet = $this->getOrCreateTPIXWallet($user);

            // Check TPIX balance
            if (bccomp($tpixWallet->balance, $tpixAmount, 8) < 0) {
                throw new Exception('Insufficient TPIX balance');
            }

            // Calculate token amount based on price
            if (!$token->current_price_tpix || $token->current_price_tpix <= 0) {
                throw new Exception('Token price not set');
            }

            $tokenAmount = bcdiv($tpixAmount, $token->current_price_tpix, 8);

            // Deduct TPIX
            $tpixWallet->decrement('balance', $tpixAmount);

            // Add tokens
            $tokenBalance = $this->getTokenBalance($user, $token);
            $tokenBalance->increment('balance', $tokenAmount);
            $tokenBalance->increment('available_balance', $tokenAmount);
            $tokenBalance->increment('total_received', $tokenAmount);

            if (!$tokenBalance->first_received_at) {
                $tokenBalance->update(['first_received_at' => now()]);
            }

            // Create transfer record
            $txHash = $this->simulateTokenPurchase($token, $user, $tokenAmount, $tpixAmount);

            TPIXTokenTransfer::create([
                'token_id' => $token->id,
                'tx_hash' => $txHash,
                'from_address' => '0x0000000000000000000000000000000000000000', // Mint address
                'to_address' => $tpixWallet->address,
                'amount' => $tokenAmount,
                'to_user_id' => $user->id,
                'transfer_type' => 'swap',
                'status' => 'confirmed',
            ]);

            // Update token volume
            $token->increment('volume_24h_tpix', $tpixAmount);

            DB::commit();

            Log::info('Token purchased', [
                'token_id' => $token->id,
                'user_id' => $user->id,
                'tpix_amount' => $tpixAmount,
                'token_amount' => $tokenAmount
            ]);

            return [
                'success' => true,
                'token_amount' => $tokenAmount,
                'tpix_spent' => $tpixAmount,
                'tx_hash' => $txHash,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Token purchase failed', [
                'token_id' => $token->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Sell token for TPIX
     */
    public function sellToken(User $user, TPIXToken $token, string $tokenAmount): array
    {
        DB::beginTransaction();
        try {
            // Get token balance
            $tokenBalance = $this->getTokenBalance($user, $token);

            // Check token balance
            if (bccomp($tokenBalance->available_balance, $tokenAmount, 8) < 0) {
                throw new Exception('Insufficient token balance');
            }

            // Calculate TPIX amount
            if (!$token->current_price_tpix || $token->current_price_tpix <= 0) {
                throw new Exception('Token price not set');
            }

            $tpixAmount = bcmul($tokenAmount, $token->current_price_tpix, 8);

            // Deduct tokens
            $tokenBalance->decrement('balance', $tokenAmount);
            $tokenBalance->decrement('available_balance', $tokenAmount);
            $tokenBalance->increment('total_sent', $tokenAmount);

            // Add TPIX
            $tpixWallet = $this->getOrCreateTPIXWallet($user);
            $tpixWallet->increment('balance', $tpixAmount);

            // Create transfer record
            $txHash = $this->simulateTokenSale($token, $user, $tokenAmount, $tpixAmount);

            TPIXTokenTransfer::create([
                'token_id' => $token->id,
                'tx_hash' => $txHash,
                'from_address' => $tpixWallet->address,
                'to_address' => '0x0000000000000000000000000000000000000000', // Burn address
                'amount' => $tokenAmount,
                'from_user_id' => $user->id,
                'transfer_type' => 'swap',
                'status' => 'confirmed',
            ]);

            // Update token volume
            $token->increment('volume_24h_tpix', $tpixAmount);

            DB::commit();

            Log::info('Token sold', [
                'token_id' => $token->id,
                'user_id' => $user->id,
                'token_amount' => $tokenAmount,
                'tpix_received' => $tpixAmount
            ]);

            return [
                'success' => true,
                'token_amount' => $tokenAmount,
                'tpix_received' => $tpixAmount,
                'tx_hash' => $txHash,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Token sale failed', [
                'token_id' => $token->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Find recipient by email or address
     */
    protected function findRecipient(string $emailOrAddress): ?User
    {
        // Try email first
        if (filter_var($emailOrAddress, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $emailOrAddress)->first();
        }

        // Try by wallet address
        $wallet = CryptoWallet::where('address', $emailOrAddress)->first();
        return $wallet ? $wallet->user : null;
    }

    /**
     * Generate TPIX address for user
     */
    protected function generateTPIXAddress(User $user): string
    {
        // In production: Derive from HD wallet or generate unique address
        // For now: Generate random address
        return '0x' . bin2hex(random_bytes(20));
    }

    /**
     * Simulate blockchain transactions
     */
    protected function simulateTokenTransfer($token, $from, $to, $amount): string
    {
        return '0x' . bin2hex(random_bytes(32));
    }

    protected function simulateTokenPurchase($token, $user, $tokenAmount, $tpixAmount): string
    {
        return '0x' . bin2hex(random_bytes(32));
    }

    protected function simulateTokenSale($token, $user, $tokenAmount, $tpixAmount): string
    {
        return '0x' . bin2hex(random_bytes(32));
    }

    /**
     * Get user's portfolio value
     */
    public function getPortfolioValue(User $user): array
    {
        $balances = TPIXTokenBalance::with('token')
            ->where('user_id', $user->id)
            ->where('balance', '>', 0)
            ->get();

        $totalValueTpix = '0';
        $tokens = [];

        foreach ($balances as $balance) {
            $tokenValueTpix = bcmul(
                $balance->balance,
                $balance->token->current_price_tpix ?? '0',
                8
            );

            $totalValueTpix = bcadd($totalValueTpix, $tokenValueTpix, 8);

            $tokens[] = [
                'token' => $balance->token,
                'balance' => $balance->balance,
                'value_tpix' => $tokenValueTpix,
            ];
        }

        return [
            'total_value_tpix' => $totalValueTpix,
            'tokens' => $tokens,
            'token_count' => count($tokens),
        ];
    }
}
