<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Mobile payment initiation for Juntra.
 *
 * Stub for first release — returns a method-specific payload so the app
 * can render the QR / open the wallet. Real PromptPay EMV string + Stripe
 * intent come in a follow-up patch when we wire to the existing
 * PaymentService that the Facebook bot already uses.
 */
class PaymentController extends Controller
{
    public function methods(): JsonResponse
    {
        return response()->json(['data' => [
            ['id' => 'promptpay', 'name' => 'PromptPay', 'subtitle' => 'สแกน QR · ทุกธนาคาร', 'enabled' => true],
            ['id' => 'truemoney', 'name' => 'TrueMoney Wallet', 'subtitle' => 'เปิดแอป TrueMoney', 'enabled' => true],
            ['id' => 'card', 'name' => 'บัตรเครดิต/เดบิต', 'subtitle' => 'Visa · Mastercard', 'enabled' => false],
            ['id' => 'wallet', 'name' => 'กระเป๋าจันทรา', 'subtitle' => 'ใช้แต้มสะสม', 'enabled' => true],
        ]]);
    }

    public function initiate(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'method' => 'required|in:promptpay,truemoney,card,wallet',
            'amount' => 'required|integer|min:1|max:100000',
            'reading_id' => 'nullable|integer',
        ]);
        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first()], 422);
        }

        $method = $request->input('method');
        $amount = (int) $request->input('amount');
        $paymentId = 'JUN-' . Str::upper(Str::random(12));

        $payload = match ($method) {
            'promptpay' => [
                'qr_string' => "00020101021129370016A000000677010111011300000000000000540" . $amount . "5802TH6304",
                'expires_at' => now()->addMinutes(15)->toIso8601String(),
            ],
            'truemoney' => [
                'deeplink' => "truemoney://transfer?amount={$amount}&ref={$paymentId}",
            ],
            'wallet' => [
                'wallet_balance_after' => max(0, 480 - $amount),
            ],
            default => [],
        };

        return response()->json(['data' => [
            'payment_id' => $paymentId,
            'method' => $method,
            'amount' => $amount,
            'status' => 'pending',
            'payload' => $payload,
        ]]);
    }

    public function status(Request $request, string $id): JsonResponse
    {
        // Stub — in production, look up the payment record + check
        // PromptPay/TrueMoney/Stripe webhook state.
        return response()->json(['data' => [
            'payment_id' => $id,
            'status' => 'pending',
        ]]);
    }
}
