<?php

namespace App\Http\Controllers;

use App\Services\NFC\NfcService;
use Illuminate\Http\Request;

class NfcPaymentController extends Controller
{
    protected NfcService $nfcService;

    public function __construct(NfcService $nfcService)
    {
        $this->nfcService = $nfcService;
    }

    /**
     * Show NFC payment page
     */
    public function index()
    {
        return view('nfc.payment');
    }

    /**
     * Process NFC payment
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'card_uid' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'terminal_id' => 'nullable|string',
        ]);

        $result = $this->nfcService->processPayment(
            $request->card_uid,
            $request->amount,
            [
                'terminal_id' => $request->terminal_id,
                'payment_method' => 'nfc',
                'ip_address' => $request->ip(),
            ]
        );

        return response()->json($result);
    }

    /**
     * Check NFC card balance
     */
    public function checkBalance(Request $request)
    {
        $request->validate([
            'card_uid' => 'required|string',
        ]);

        $result = $this->nfcService->checkBalance($request->card_uid);

        return response()->json($result);
    }

    /**
     * Get card information
     */
    public function getCardInfo(Request $request)
    {
        $request->validate([
            'card_uid' => 'required|string',
        ]);

        $card = $this->nfcService->getCardInfo($request->card_uid);

        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'card' => [
                'uid' => $card->card_uid,
                'type' => $card->card_type,
                'name' => $card->card_name,
                'is_active' => $card->is_active,
                'is_linked' => $card->isLinked(),
                'user' => $card->user ? [
                    'name' => $card->user->name,
                    'email' => $card->user->email,
                ] : null,
            ],
        ]);
    }

    /**
     * Verify NFC card
     */
    public function verifyCard(Request $request)
    {
        $request->validate([
            'card_uid' => 'required|string',
        ]);

        $card = $this->nfcService->getCardInfo($request->card_uid);

        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found',
            ], 404);
        }

        if (!$card->canBeUsed()) {
            return response()->json([
                'success' => false,
                'message' => 'Card is not active or not linked',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Card verified successfully',
            'card' => [
                'uid' => $card->card_uid,
                'name' => $card->card_name,
                'user' => [
                    'name' => $card->user->name,
                ],
            ],
        ]);
    }
}
