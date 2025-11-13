<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NFCCard;
use App\Models\NFCReader;
use App\Services\NFC\NFCCardService;
use App\Services\NFC\NFCCardEncryptionService;
use Illuminate\Http\Request;
use Exception;

/**
 * NFC Card API Controller
 *
 * API endpoints สำหรับการทำงานกับบัตร NFC
 */
class NFCCardApiController extends Controller
{
    protected NFCCardService $nfcCardService;
    protected NFCCardEncryptionService $encryptionService;

    public function __construct(
        NFCCardService $nfcCardService,
        NFCCardEncryptionService $encryptionService
    ) {
        $this->nfcCardService = $nfcCardService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * Get user's NFC cards
     */
    public function index(Request $request)
    {
        try {
            $cards = $this->nfcCardService->getCardsByUser($request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $cards->map(function ($card) {
                    return [
                        'id' => $card->id,
                        'card_number_masked' => $card->masked_card_number,
                        'card_name' => $card->card_name,
                        'card_type' => $card->card_type,
                        'card_type_label' => $card->card_type_label,
                        'balance' => $card->balance,
                        'status' => $card->status,
                        'status_label' => $card->status_label,
                        'is_active' => $card->isActive(),
                        'expires_at' => $card->expires_at,
                        'last_used_at' => $card->last_used_at,
                    ];
                }),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get specific card details
     */
    public function show(Request $request, $cardId)
    {
        try {
            $card = NFCCard::where('id', $cardId)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $statistics = $this->nfcCardService->getCardStatistics($card);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $card->id,
                    'card_number_masked' => $card->masked_card_number,
                    'card_name' => $card->card_name,
                    'card_type' => $card->card_type,
                    'card_type_label' => $card->card_type_label,
                    'balance' => $card->balance,
                    'credit_limit' => $card->credit_limit,
                    'status' => $card->status,
                    'status_label' => $card->status_label,
                    'is_active' => $card->isActive(),
                    'is_paired' => $card->is_paired,
                    'paired_at' => $card->paired_at,
                    'expires_at' => $card->expires_at,
                    'last_used_at' => $card->last_used_at,
                    'statistics' => $statistics,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Verify card (read and verify NFC card data)
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'card_number' => 'required|string',
            'encrypted_data' => 'required|string',
        ]);

        try {
            $result = $this->nfcCardService->readAndVerifyCard(
                $validated['card_number'],
                $validated['encrypted_data']
            );

            if (!$result['verified']) {
                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'error' => $result['error'],
                ], 400);
            }

            $card = $result['card'];

            // ตรวจสอบว่าบัตรเป็นของ user ที่ request หรือไม่
            if ($card->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'error' => 'Card does not belong to this user',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'verified' => true,
                'data' => [
                    'card_id' => $card->id,
                    'card_number_masked' => $card->masked_card_number,
                    'balance' => $card->balance,
                    'card_type' => $card->card_type,
                    'status' => $card->status,
                    'is_active' => $card->isActive(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Process payment with NFC card
     */
    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'card_id' => 'required|exists:nfc_cards,id',
            'amount' => 'required|numeric|min:0.01',
            'reader_id' => 'nullable|exists:nfc_readers,id',
            'encrypted_data' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            $card = NFCCard::where('id', $validated['card_id'])
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            // ตรวจสอบความถูกต้องของบัตร
            $verification = $this->nfcCardService->readAndVerifyCard(
                $card->card_number,
                $validated['encrypted_data']
            );

            if (!$verification['verified']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Card verification failed: ' . $verification['error'],
                ], 400);
            }

            // ดึง reader ถ้ามี
            $reader = null;
            if (isset($validated['reader_id'])) {
                $reader = NFCReader::find($validated['reader_id']);
            }

            // ประมวลผลการชำระเงิน
            $transaction = $this->nfcCardService->processPayment(
                $card,
                $validated['amount'],
                $reader,
                array_merge($validated['metadata'] ?? [], [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'receipt_number' => $transaction->receipt_number,
                    'amount' => $transaction->amount,
                    'balance_before' => $transaction->balance_before,
                    'balance_after' => $transaction->balance_after,
                    'status' => $transaction->status,
                    'completed_at' => $transaction->completed_at,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get card transactions
     */
    public function transactions(Request $request, $cardId)
    {
        try {
            $card = NFCCard::where('id', $cardId)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $limit = $request->input('limit', 20);
            $transactions = $card->transactions()
                ->with('nfcReader')
                ->latest()
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $transactions->map(function ($transaction) {
                    return [
                        'transaction_id' => $transaction->transaction_id,
                        'receipt_number' => $transaction->receipt_number,
                        'type' => $transaction->type,
                        'type_label' => $transaction->type_label,
                        'amount' => $transaction->amount,
                        'balance_before' => $transaction->balance_before,
                        'balance_after' => $transaction->balance_after,
                        'status' => $transaction->status,
                        'status_label' => $transaction->status_label,
                        'location' => $transaction->location,
                        'reader_name' => $transaction->nfcReader?->name,
                        'created_at' => $transaction->created_at,
                        'completed_at' => $transaction->completed_at,
                    ];
                }),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get card balance
     */
    public function balance(Request $request, $cardId)
    {
        try {
            $card = NFCCard::where('id', $cardId)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'balance' => $card->balance,
                    'credit_limit' => $card->credit_limit,
                    'currency' => 'THB',
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get nearby readers (for mobile app)
     */
    public function nearbyReaders(Request $request)
    {
        $readers = NFCReader::active()
            ->online()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $readers->map(function ($reader) {
                return [
                    'id' => $reader->id,
                    'reader_id' => $reader->reader_id,
                    'name' => $reader->name,
                    'location' => $reader->location,
                    'status' => $reader->status,
                    'is_online' => $reader->isOnline(),
                ];
            }),
        ]);
    }
}
