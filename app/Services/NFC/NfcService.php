<?php

namespace App\Services\NFC;

use App\Models\NfcCard;
use App\Models\NfcCardTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NfcService
{
    /**
     * Register a new NFC card
     */
    public function registerCard(string $cardUid, array $data = []): NfcCard
    {
        return NfcCard::create([
            'card_uid' => $cardUid,
            'card_type' => $data['card_type'] ?? 'standard',
            'card_name' => $data['card_name'] ?? null,
            'is_active' => true,
            'notes' => $data['notes'] ?? null,
            'meta_data' => $data['meta_data'] ?? [],
        ]);
    }

    /**
     * Link NFC card to user and wallet
     */
    public function linkCardToUser(string $cardUid, int $userId, User $admin): array
    {
        try {
            DB::beginTransaction();

            $card = NfcCard::where('card_uid', $cardUid)->firstOrFail();

            if ($card->isLinked()) {
                throw new \Exception('Card is already linked to another user');
            }

            $user = User::with('wallet')->findOrFail($userId);

            if (!$user->wallet) {
                throw new \Exception('User does not have a wallet');
            }

            $card->linkToUser($user, $admin);

            // Log the linking transaction
            $this->logTransaction($card, $user, 'link', 0, 'success', [
                'linked_by' => $admin->id,
                'linked_by_name' => $admin->name,
            ]);

            DB::commit();

            return [
                'success' => true,
                'card' => $card->load(['user', 'wallet']),
                'message' => 'Card successfully linked to user',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('NFC Card Link Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Unlink NFC card from user
     */
    public function unlinkCard(string $cardUid, User $admin): array
    {
        try {
            DB::beginTransaction();

            $card = NfcCard::where('card_uid', $cardUid)->firstOrFail();

            if (!$card->isLinked()) {
                throw new \Exception('Card is not linked to any user');
            }

            $user = $card->user;

            // Log the unlinking transaction
            $this->logTransaction($card, $user, 'unlink', 0, 'success', [
                'unlinked_by' => $admin->id,
                'unlinked_by_name' => $admin->name,
            ]);

            $card->unlink();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Card successfully unlinked',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('NFC Card Unlink Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process NFC payment
     */
    public function processPayment(string $cardUid, float $amount, array $metadata = []): array
    {
        try {
            DB::beginTransaction();

            $card = NfcCard::where('card_uid', $cardUid)
                ->with(['user', 'wallet'])
                ->firstOrFail();

            if (!$card->canBeUsed()) {
                throw new \Exception('Card is not active or not linked to a user');
            }

            $wallet = $card->wallet;

            if (!$wallet->hasSufficientBalance($amount)) {
                throw new \Exception('Insufficient balance');
            }

            // Debit from wallet
            $transaction = $wallet->debit(
                $amount,
                'nfc_payment',
                'Payment via NFC card',
                null
            );

            // Update card last used
            $card->updateLastUsed();

            // Log NFC transaction
            $nfcTransaction = $this->logTransaction(
                $card,
                $card->user,
                'payment',
                $amount,
                'success',
                array_merge($metadata, [
                    'wallet_transaction_id' => $transaction->id,
                ])
            );

            DB::commit();

            return [
                'success' => true,
                'transaction' => $nfcTransaction,
                'wallet_transaction' => $transaction,
                'remaining_balance' => $wallet->fresh()->balance,
                'message' => 'Payment successful',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('NFC Payment Error: ' . $e->getMessage());

            // Log failed transaction
            if (isset($card)) {
                $this->logTransaction(
                    $card,
                    $card->user ?? null,
                    'payment',
                    $amount,
                    'failed',
                    $metadata,
                    $e->getMessage()
                );
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check card balance
     */
    public function checkBalance(string $cardUid): array
    {
        try {
            $card = NfcCard::where('card_uid', $cardUid)
                ->with(['user', 'wallet'])
                ->firstOrFail();

            if (!$card->canBeUsed()) {
                throw new \Exception('Card is not active or not linked to a user');
            }

            // Log balance check
            $this->logTransaction(
                $card,
                $card->user,
                'balance_check',
                0,
                'success'
            );

            return [
                'success' => true,
                'balance' => $card->wallet->balance,
                'user' => [
                    'name' => $card->user->name,
                    'email' => $card->user->email,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('NFC Balance Check Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get card information
     */
    public function getCardInfo(string $cardUid): ?NfcCard
    {
        return NfcCard::where('card_uid', $cardUid)
            ->with(['user', 'wallet', 'linkedBy'])
            ->first();
    }

    /**
     * Deactivate card
     */
    public function deactivateCard(string $cardUid): bool
    {
        $card = NfcCard::where('card_uid', $cardUid)->firstOrFail();
        $card->is_active = false;

        return $card->save();
    }

    /**
     * Activate card
     */
    public function activateCard(string $cardUid): bool
    {
        $card = NfcCard::where('card_uid', $cardUid)->firstOrFail();
        $card->is_active = true;

        return $card->save();
    }

    /**
     * Log NFC transaction
     */
    private function logTransaction(
        NfcCard $card,
        ?User $user,
        string $type,
        float $amount,
        string $status,
        array $metadata = [],
        ?string $error = null
    ): NfcCardTransaction {
        return NfcCardTransaction::create([
            'nfc_card_id' => $card->id,
            'user_id' => $user?->id,
            'transaction_type' => $type,
            'amount' => $amount,
            'status' => $status,
            'terminal_id' => $metadata['terminal_id'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'error_message' => $error,
            'meta_data' => $metadata,
        ]);
    }

    /**
     * Get card transaction history
     */
    public function getCardTransactions(string $cardUid, int $limit = 50): array
    {
        $card = NfcCard::where('card_uid', $cardUid)->firstOrFail();

        return $card->transactions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
