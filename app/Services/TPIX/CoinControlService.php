<?php

namespace App\Services\TPIX;

use App\Models\TPIXToken;
use App\Models\User;
use App\Models\CoinControlAction;
use App\Services\Crypto\TPIXBlockchainService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Coin Control Service
 *
 * Advanced coin control features: minting, burning, freezing, etc.
 */
class CoinControlService
{
    protected TPIXBlockchainService $blockchain;

    public function __construct(TPIXBlockchainService $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    /**
     * Mint new tokens
     */
    public function mint(TPIXToken $token, User $admin, string $toAddress, string $amount, string $reason): array
    {
        if (!$token->canMint()) {
            throw new Exception('Token is not mintable or not active');
        }

        // Check max supply limit
        if ($token->has_max_supply) {
            $newTotalSupply = bcadd($token->total_supply, $amount, 8);
            if (bccomp($newTotalSupply, $token->max_supply, 8) > 0) {
                throw new Exception('Minting would exceed max supply');
            }
        }

        DB::beginTransaction();
        try {
            // Record control action
            $action = CoinControlAction::create([
                'token_id' => $token->id,
                'admin_id' => $admin->id,
                'action_type' => 'mint',
                'target_address' => $toAddress,
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'pending',
            ]);

            // In production: Call smart contract mint function
            // For now: Simulate blockchain transaction
            $txHash = $this->simulateMintTransaction($token, $toAddress, $amount);

            // Update action
            $action->update([
                'tx_hash' => $txHash,
                'status' => 'success',
            ]);

            // Update token supply
            $token->increment('total_supply', $amount);
            $token->increment('total_minted', $amount);

            // Update user balance
            $this->updateBalance($token, $toAddress, $amount, 'add');

            DB::commit();

            Log::info('Tokens minted', [
                'token_id' => $token->id,
                'amount' => $amount,
                'to_address' => $toAddress,
                'admin_id' => $admin->id
            ]);

            return [
                'success' => true,
                'tx_hash' => $txHash,
                'action_id' => $action->id,
                'new_total_supply' => $token->fresh()->total_supply,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            if (isset($action)) {
                $action->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }

            Log::error('Minting failed', [
                'token_id' => $token->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Burn tokens
     */
    public function burn(TPIXToken $token, User $admin, string $fromAddress, string $amount, string $reason): array
    {
        if (!$token->canBurn()) {
            throw new Exception('Token is not burnable or not active');
        }

        DB::beginTransaction();
        try {
            // Check balance
            $balance = $this->getBalance($token, $fromAddress);
            if (bccomp($balance, $amount, 8) < 0) {
                throw new Exception('Insufficient balance to burn');
            }

            // Record control action
            $action = CoinControlAction::create([
                'token_id' => $token->id,
                'admin_id' => $admin->id,
                'action_type' => 'burn',
                'target_address' => $fromAddress,
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'pending',
            ]);

            // Simulate blockchain transaction
            $txHash = $this->simulateBurnTransaction($token, $fromAddress, $amount);

            // Update action
            $action->update([
                'tx_hash' => $txHash,
                'status' => 'success',
            ]);

            // Update token supply
            $token->decrement('total_supply', $amount);
            $token->increment('total_burned', $amount);

            // Update user balance
            $this->updateBalance($token, $fromAddress, $amount, 'subtract');

            DB::commit();

            Log::info('Tokens burned', [
                'token_id' => $token->id,
                'amount' => $amount,
                'from_address' => $fromAddress,
                'admin_id' => $admin->id
            ]);

            return [
                'success' => true,
                'tx_hash' => $txHash,
                'action_id' => $action->id,
                'new_total_supply' => $token->fresh()->total_supply,
                'total_burned' => $token->fresh()->total_burned,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            if (isset($action)) {
                $action->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }

            Log::error('Burning failed', [
                'token_id' => $token->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Freeze an address
     */
    public function freezeAddress(TPIXToken $token, User $admin, string $address, string $reason): array
    {
        if (!$token->is_freezable) {
            throw new Exception('Token does not support freezing');
        }

        DB::beginTransaction();
        try {
            // Record control action
            $action = CoinControlAction::create([
                'token_id' => $token->id,
                'admin_id' => $admin->id,
                'action_type' => 'freeze_address',
                'target_address' => $address,
                'reason' => $reason,
                'status' => 'pending',
            ]);

            // Simulate blockchain transaction
            $txHash = $this->simulateFreezeTransaction($token, $address);

            // Update action
            $action->update([
                'tx_hash' => $txHash,
                'status' => 'success',
            ]);

            // Freeze balance
            $balance = $token->balances()->where('wallet_address', $address)->first();
            if ($balance) {
                $balance->update([
                    'is_frozen' => true,
                    'freeze_reason' => $reason
                ]);
            }

            DB::commit();

            Log::info('Address frozen', [
                'token_id' => $token->id,
                'address' => $address,
                'admin_id' => $admin->id
            ]);

            return [
                'success' => true,
                'tx_hash' => $txHash,
                'action_id' => $action->id,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Address freeze failed', [
                'token_id' => $token->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Unfreeze an address
     */
    public function unfreezeAddress(TPIXToken $token, User $admin, string $address, string $reason): array
    {
        DB::beginTransaction();
        try {
            // Record control action
            $action = CoinControlAction::create([
                'token_id' => $token->id,
                'admin_id' => $admin->id,
                'action_type' => 'unfreeze_address',
                'target_address' => $address,
                'reason' => $reason,
                'status' => 'pending',
            ]);

            // Simulate blockchain transaction
            $txHash = $this->simulateUnfreezeTransaction($token, $address);

            // Update action
            $action->update([
                'tx_hash' => $txHash,
                'status' => 'success',
            ]);

            // Unfreeze balance
            $balance = $token->balances()->where('wallet_address', $address)->first();
            if ($balance) {
                $balance->update([
                    'is_frozen' => false,
                    'freeze_reason' => null
                ]);
            }

            DB::commit();

            Log::info('Address unfrozen', [
                'token_id' => $token->id,
                'address' => $address,
                'admin_id' => $admin->id
            ]);

            return [
                'success' => true,
                'tx_hash' => $txHash,
                'action_id' => $action->id,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Address unfreeze failed', [
                'token_id' => $token->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Pause all token transfers
     */
    public function pauseContract(TPIXToken $token, User $admin, string $reason): array
    {
        if (!$token->is_pausable) {
            throw new Exception('Token does not support pausing');
        }

        DB::beginTransaction();
        try {
            $action = CoinControlAction::create([
                'token_id' => $token->id,
                'admin_id' => $admin->id,
                'action_type' => 'pause_contract',
                'reason' => $reason,
                'status' => 'success',
            ]);

            $token->update(['status' => 'paused']);

            DB::commit();

            Log::info('Contract paused', [
                'token_id' => $token->id,
                'admin_id' => $admin->id
            ]);

            return [
                'success' => true,
                'action_id' => $action->id,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Unpause token transfers
     */
    public function unpauseContract(TPIXToken $token, User $admin, string $reason): array
    {
        DB::beginTransaction();
        try {
            $action = CoinControlAction::create([
                'token_id' => $token->id,
                'admin_id' => $admin->id,
                'action_type' => 'unpause_contract',
                'reason' => $reason,
                'status' => 'success',
            ]);

            $token->update(['status' => 'active']);

            DB::commit();

            Log::info('Contract unpaused', [
                'token_id' => $token->id,
                'admin_id' => $admin->id
            ]);

            return [
                'success' => true,
                'action_id' => $action->id,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Simulate blockchain transactions (replace with real calls in production)
     */
    protected function simulateMintTransaction($token, $toAddress, $amount): string
    {
        return '0x' . bin2hex(random_bytes(32));
    }

    protected function simulateBurnTransaction($token, $fromAddress, $amount): string
    {
        return '0x' . bin2hex(random_bytes(32));
    }

    protected function simulateFreezeTransaction($token, $address): string
    {
        return '0x' . bin2hex(random_bytes(32));
    }

    protected function simulateUnfreezeTransaction($token, $address): string
    {
        return '0x' . bin2hex(random_bytes(32));
    }

    /**
     * Update balance
     */
    protected function updateBalance(TPIXToken $token, string $address, string $amount, string $operation)
    {
        $balance = $token->balances()->where('wallet_address', $address)->first();

        if (!$balance) {
            if ($operation === 'add') {
                $token->balances()->create([
                    'user_id' => $this->getUserIdFromAddress($address),
                    'wallet_address' => $address,
                    'balance' => $amount,
                    'available_balance' => $amount,
                ]);
            }
            return;
        }

        if ($operation === 'add') {
            $balance->increment('balance', $amount);
            $balance->increment('available_balance', $amount);
            $balance->increment('total_received', $amount);
        } else {
            $balance->decrement('balance', $amount);
            $balance->decrement('available_balance', $amount);
            $balance->increment('total_sent', $amount);
        }

        $balance->touch('last_activity_at');
    }

    /**
     * Get balance
     */
    protected function getBalance(TPIXToken $token, string $address): string
    {
        $balance = $token->balances()->where('wallet_address', $address)->first();
        return $balance ? $balance->balance : '0';
    }

    /**
     * Get user ID from address (helper)
     */
    protected function getUserIdFromAddress(string $address): ?int
    {
        $wallet = \App\Models\CryptoWallet::where('address', $address)->first();
        return $wallet ? $wallet->user_id : null;
    }
}
