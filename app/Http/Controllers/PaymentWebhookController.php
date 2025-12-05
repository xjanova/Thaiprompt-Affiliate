<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Payment Webhook Controller
 *
 * Handles webhook callbacks from various payment gateways
 */
class PaymentWebhookController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle PaySolutions webhook
     */
    public function handlePaySolutions(Request $request)
    {
        try {
            Log::info('PaySolutions webhook received', [
                'ip' => $request->ip(),
                'data' => $request->all(),
            ]);

            // Get transaction ID from webhook data
            $transactionId = $request->input('order_id');

            if (!$transactionId) {
                Log::warning('PaySolutions webhook missing order_id');
                return response()->json(['error' => 'Missing order_id'], 400);
            }

            // Find transaction
            $transaction = PaymentTransaction::where('transaction_id', $transactionId)
                ->whereIn('status', ['pending', 'processing'])
                ->first();

            if (!$transaction) {
                Log::warning('PaySolutions webhook: transaction not found', [
                    'transaction_id' => $transactionId,
                ]);
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // Verify and complete payment
            $verified = $this->paymentService->verifyPayment($transactionId, $request->all());

            if ($verified) {
                Log::info('PaySolutions payment verified and completed', [
                    'transaction_id' => $transactionId,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment completed',
                ]);
            } else {
                Log::warning('PaySolutions payment verification failed', [
                    'transaction_id' => $transactionId,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Verification failed',
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('PaySolutions webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Handle PromptPay webhook
     */
    public function handlePromptPay(Request $request)
    {
        try {
            Log::info('PromptPay webhook received', [
                'ip' => $request->ip(),
                'data' => $request->all(),
            ]);

            $refNo = $request->input('ref_no');

            if (!$refNo) {
                return response()->json(['error' => 'Missing ref_no'], 400);
            }

            $transaction = PaymentTransaction::where('promptpay_ref_no', $refNo)
                ->whereIn('status', ['pending', 'processing'])
                ->first();

            if (!$transaction) {
                Log::warning('PromptPay webhook: transaction not found', [
                    'ref_no' => $refNo,
                ]);
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            $verified = $this->paymentService->verifyPayment($transaction->transaction_id, $request->all());

            if ($verified) {
                return response()->json(['status' => 'success']);
            }

            return response()->json(['status' => 'error'], 400);
        } catch (\Exception $e) {
            Log::error('PromptPay webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Handle Stripe webhook
     */
    public function handleStripe(Request $request)
    {
        try {
            Log::info('Stripe webhook received', [
                'ip' => $request->ip(),
                'event_type' => $request->input('type'),
            ]);

            // Stripe uses event-based webhooks
            $event = $request->all();
            $eventType = $event['type'] ?? '';

            // Handle different event types
            switch ($eventType) {
                case 'payment_intent.succeeded':
                    return $this->handleStripePaymentSuccess($event);

                case 'payment_intent.payment_failed':
                    return $this->handleStripePaymentFailed($event);

                case 'charge.refunded':
                    return $this->handleStripeRefund($event);

                default:
                    Log::info('Unhandled Stripe event type', ['type' => $eventType]);
                    return response()->json(['status' => 'ok']);
            }
        } catch (\Exception $e) {
            Log::error('Stripe webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Handle Stripe payment success
     */
    protected function handleStripePaymentSuccess(array $event): \Illuminate\Http\JsonResponse
    {
        $paymentIntent = $event['data']['object'] ?? [];
        $metadata = $paymentIntent['metadata'] ?? [];
        $transactionId = $metadata['transaction_id'] ?? null;

        if (!$transactionId) {
            return response()->json(['error' => 'Missing transaction_id'], 400);
        }

        $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();

        if ($transaction && !$transaction->isCompleted()) {
            $this->paymentService->completePayment($transaction);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Stripe payment failed
     */
    protected function handleStripePaymentFailed(array $event): \Illuminate\Http\JsonResponse
    {
        $paymentIntent = $event['data']['object'] ?? [];
        $metadata = $paymentIntent['metadata'] ?? [];
        $transactionId = $metadata['transaction_id'] ?? null;

        if ($transactionId) {
            $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();

            if ($transaction && !$transaction->isCompleted()) {
                $transaction->markAsFailed('Payment failed via Stripe');
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Stripe refund
     */
    protected function handleStripeRefund(array $event): \Illuminate\Http\JsonResponse
    {
        // Handle refund notification
        Log::info('Stripe refund webhook received', ['event' => $event]);

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Omise webhook
     */
    public function handleOmise(Request $request)
    {
        try {
            Log::info('Omise webhook received', [
                'ip' => $request->ip(),
                'data' => $request->all(),
            ]);

            $event = $request->all();
            $eventKey = $event['key'] ?? '';

            if ($eventKey === 'charge.complete') {
                $chargeData = $event['data'] ?? [];
                $metadata = $chargeData['metadata'] ?? [];
                $transactionId = $metadata['transaction_id'] ?? null;

                if ($transactionId) {
                    $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();

                    if ($transaction && !$transaction->isCompleted()) {
                        $verified = $this->paymentService->verifyPayment($transactionId, $event);
                        if ($verified) {
                            Log::info('Omise payment completed', ['transaction_id' => $transactionId]);
                        }
                    }
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Omise webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Handle PayPal webhook
     */
    public function handlePayPal(Request $request)
    {
        try {
            Log::info('PayPal webhook received', [
                'ip' => $request->ip(),
                'event_type' => $request->input('event_type'),
            ]);

            $event = $request->all();
            $eventType = $event['event_type'] ?? '';

            switch ($eventType) {
                case 'CHECKOUT.ORDER.APPROVED':
                case 'PAYMENT.CAPTURE.COMPLETED':
                    return $this->handlePayPalPaymentSuccess($event);

                case 'PAYMENT.CAPTURE.DENIED':
                case 'PAYMENT.CAPTURE.DECLINED':
                    return $this->handlePayPalPaymentFailed($event);

                default:
                    Log::info('Unhandled PayPal event type', ['type' => $eventType]);
                    return response()->json(['status' => 'ok']);
            }
        } catch (\Exception $e) {
            Log::error('PayPal webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Handle PayPal payment success
     */
    protected function handlePayPalPaymentSuccess(array $event): \Illuminate\Http\JsonResponse
    {
        $resource = $event['resource'] ?? [];
        $orderId = $resource['id'] ?? null;

        if ($orderId) {
            $transaction = PaymentTransaction::where('gateway_transaction_id', $orderId)->first();

            if ($transaction && !$transaction->isCompleted()) {
                $verified = $this->paymentService->verifyPayment($transaction->transaction_id, $event);
                if ($verified) {
                    Log::info('PayPal payment completed', ['order_id' => $orderId]);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle PayPal payment failed
     */
    protected function handlePayPalPaymentFailed(array $event): \Illuminate\Http\JsonResponse
    {
        $resource = $event['resource'] ?? [];
        $orderId = $resource['id'] ?? null;

        if ($orderId) {
            $transaction = PaymentTransaction::where('gateway_transaction_id', $orderId)->first();

            if ($transaction && !$transaction->isCompleted()) {
                $transaction->markAsFailed('Payment failed via PayPal');
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Razorpay webhook
     */
    public function handleRazorpay(Request $request)
    {
        try {
            Log::info('Razorpay webhook received', [
                'ip' => $request->ip(),
                'event' => $request->input('event'),
            ]);

            $event = $request->all();
            $eventType = $event['event'] ?? '';

            switch ($eventType) {
                case 'payment.captured':
                    return $this->handleRazorpayPaymentSuccess($event);

                case 'payment.failed':
                    return $this->handleRazorpayPaymentFailed($event);

                default:
                    Log::info('Unhandled Razorpay event type', ['type' => $eventType]);
                    return response()->json(['status' => 'ok']);
            }
        } catch (\Exception $e) {
            Log::error('Razorpay webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Handle Razorpay payment success
     */
    protected function handleRazorpayPaymentSuccess(array $event): \Illuminate\Http\JsonResponse
    {
        $payload = $event['payload']['payment']['entity'] ?? [];
        $notes = $payload['notes'] ?? [];
        $transactionId = $notes['transaction_id'] ?? null;

        if ($transactionId) {
            $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();

            if ($transaction && !$transaction->isCompleted()) {
                // Save razorpay_payment_id for refunds
                $transaction->update([
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'razorpay_payment_id' => $payload['id'] ?? null,
                    ]),
                ]);

                $this->paymentService->completePayment($transaction);
                Log::info('Razorpay payment completed', ['transaction_id' => $transactionId]);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Razorpay payment failed
     */
    protected function handleRazorpayPaymentFailed(array $event): \Illuminate\Http\JsonResponse
    {
        $payload = $event['payload']['payment']['entity'] ?? [];
        $notes = $payload['notes'] ?? [];
        $transactionId = $notes['transaction_id'] ?? null;

        if ($transactionId) {
            $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();

            if ($transaction && !$transaction->isCompleted()) {
                $errorDesc = $payload['error_description'] ?? 'Payment failed';
                $transaction->markAsFailed($errorDesc);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle TrueMoney webhook
     */
    public function handleTrueMoney(Request $request)
    {
        try {
            Log::info('TrueMoney webhook received', [
                'ip' => $request->ip(),
                'data' => $request->all(),
            ]);

            $data = $request->all();
            $status = $data['status'] ?? '';
            $transactionId = $data['reference_id'] ?? $data['transaction_id'] ?? null;

            if (!$transactionId) {
                return response()->json(['error' => 'Missing transaction reference'], 400);
            }

            $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();

            if (!$transaction) {
                Log::warning('TrueMoney webhook: transaction not found', [
                    'transaction_id' => $transactionId,
                ]);
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            if (in_array(strtoupper($status), ['SUCCESS', 'COMPLETED'])) {
                if (!$transaction->isCompleted()) {
                    $verified = $this->paymentService->verifyPayment($transactionId, $data);
                    if ($verified) {
                        Log::info('TrueMoney payment completed', ['transaction_id' => $transactionId]);
                    }
                }
            } elseif (in_array(strtoupper($status), ['FAILED', 'CANCELLED'])) {
                if (!$transaction->isCompleted()) {
                    $transaction->markAsFailed('Payment ' . strtolower($status));
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('TrueMoney webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }
}
