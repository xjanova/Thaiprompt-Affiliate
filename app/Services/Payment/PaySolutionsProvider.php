<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\PaymentGateway;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PaySolutions Payment Provider
 *
 * Integrates with PaySolutions.asia payment gateway API
 * Documentation: https://api-docs.paysolutions.asia/
 *
 * Supported Features:
 * - QR Code Payment (PromptPay, TrueMoney, etc.)
 * - Credit/Debit Card Payment
 * - Bank Transfer
 * - E-Wallet Payment
 * - Installment Payment
 */
class PaySolutionsProvider implements PaymentProviderInterface
{
    protected $gateway;
    protected $apiUrl;
    protected $merchantId;
    protected $apiKey;
    protected $secretKey;
    protected $testMode;

    public function __construct()
    {
        $this->gateway = PaymentGateway::findByCode('paysolutions');

        if ($this->gateway) {
            $this->testMode = $this->gateway->test_mode;
            $this->apiUrl = $this->testMode
                ? 'https://sandbox-api.paysolutions.asia'
                : 'https://api.paysolutions.asia';

            $this->merchantId = $this->gateway->getCredential('merchant_id');
            $this->apiKey = $this->gateway->getCredential('api_key');
            $this->secretKey = $this->gateway->getCredential('secret_key');
        }
    }

    /**
     * Validate payment request
     */
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        // Check if gateway is configured
        if (!$this->gateway || !$this->gateway->isConfigured()) {
            throw new Exception('PaySolutions gateway is not configured');
        }

        // Check if gateway is active
        if (!$this->gateway->is_active) {
            throw new Exception('PaySolutions gateway is not active');
        }

        // Validate amount
        if ($transaction->amount <= 0) {
            throw new Exception('Invalid payment amount');
        }

        // Validate amount limits
        $limits = $this->gateway->limits;
        if (isset($limits['min_amount']) && $transaction->amount < $limits['min_amount']) {
            throw new Exception("Minimum payment amount is {$limits['min_amount']} THB");
        }
        if (isset($limits['max_amount']) && $transaction->amount > $limits['max_amount']) {
            throw new Exception("Maximum payment amount is {$limits['max_amount']} THB");
        }

        // Validate payment method
        $paymentMethod = $data['paysolutions_method'] ?? 'qr';
        if (!in_array($paymentMethod, ['qr', 'card', 'bank_transfer', 'ewallet', 'installment'])) {
            throw new Exception('Invalid PaySolutions payment method');
        }

        return true;
    }

    /**
     * Process payment through PaySolutions
     */
    public function process(PaymentTransaction $transaction, array $data): array
    {
        try {
            $paymentMethod = $data['paysolutions_method'] ?? 'qr';

            // Prepare payment request
            $payload = $this->preparePaymentPayload($transaction, $paymentMethod, $data);

            // Call PaySolutions API
            $response = $this->callApi('/api/v1/payment/create', $payload);

            if (!$response['success']) {
                throw new Exception($response['message'] ?? 'Payment creation failed');
            }

            $paymentData = $response['data'];

            return [
                'status' => 'processing',
                'gateway' => 'paysolutions',
                'gateway_transaction_id' => $paymentData['transaction_id'] ?? null,
                'payment_url' => $paymentData['payment_url'] ?? null,
                'qr_code' => $paymentData['qr_code'] ?? null,
                'ref_no' => $paymentData['reference_no'] ?? null,
                'response' => [
                    'payment_method' => $paymentMethod,
                    'expires_at' => $paymentData['expires_at'] ?? null,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                ],
            ];
        } catch (Exception $e) {
            Log::error('PaySolutions payment processing error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify payment from webhook callback
     */
    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        try {
            // SECURITY: Verify webhook signature
            if (!$this->verifyWebhookSignature($data)) {
                Log::warning('PaySolutions webhook signature verification failed', [
                    'transaction_id' => $transaction->transaction_id,
                ]);
                return false;
            }

            // Verify transaction ID matches
            $gatewayTransactionId = $data['transaction_id'] ?? null;
            if ($gatewayTransactionId !== $transaction->gateway_transaction_id) {
                return false;
            }

            // Verify amount matches (anti-tampering)
            $paidAmount = (float) ($data['amount'] ?? 0);
            if (abs($paidAmount - $transaction->amount) > 0.01) {
                Log::warning('PaySolutions payment amount mismatch', [
                    'transaction_id' => $transaction->transaction_id,
                    'expected' => $transaction->amount,
                    'received' => $paidAmount,
                ]);
                return false;
            }

            // Check payment status
            $status = $data['status'] ?? '';
            if (in_array($status, ['success', 'completed', 'paid'])) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('PaySolutions verification error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Refund payment
     */
    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        try {
            $payload = [
                'merchant_id' => $this->merchantId,
                'transaction_id' => $transaction->gateway_transaction_id,
                'refund_amount' => $amount,
                'reason' => 'Customer requested refund',
                'timestamp' => now()->timestamp,
            ];

            // Add signature
            $payload['signature'] = $this->generateSignature($payload);

            $response = $this->callApi('/api/v1/payment/refund', $payload);

            if (!$response['success']) {
                throw new Exception($response['message'] ?? 'Refund failed');
            }

            return [
                'status' => 'completed',
                'refund_amount' => $amount,
                'refund_id' => $response['data']['refund_id'] ?? null,
                'refunded_at' => now()->toIso8601String(),
            ];
        } catch (Exception $e) {
            Log::error('PaySolutions refund error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check payment status
     */
    public function checkStatus(PaymentTransaction $transaction): array
    {
        try {
            $payload = [
                'merchant_id' => $this->merchantId,
                'transaction_id' => $transaction->gateway_transaction_id,
                'timestamp' => now()->timestamp,
            ];

            $payload['signature'] = $this->generateSignature($payload);

            $response = $this->callApi('/api/v1/payment/status', $payload, 'GET');

            if (!$response['success']) {
                throw new Exception($response['message'] ?? 'Status check failed');
            }

            return [
                'status' => $response['data']['status'] ?? 'pending',
                'paid_amount' => $response['data']['paid_amount'] ?? 0,
                'paid_at' => $response['data']['paid_at'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('PaySolutions status check error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Prepare payment payload for API request
     */
    protected function preparePaymentPayload(PaymentTransaction $transaction, string $method, array $data): array
    {
        $payload = [
            'merchant_id' => $this->merchantId,
            'order_id' => $transaction->transaction_id,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'payment_method' => $method,
            'description' => $this->getPaymentDescription($transaction),
            'customer' => [
                'name' => $transaction->user->name ?? '',
                'email' => $transaction->user->email ?? '',
                'phone' => $transaction->user->phone ?? '',
            ],
            'callback_url' => route('api.webhook.paysolutions'),
            'return_url' => route('payment.success', ['transaction' => $transaction->id]),
            'cancel_url' => route('payment.cancel', ['transaction' => $transaction->id]),
            'timestamp' => now()->timestamp,
            'metadata' => [
                'user_id' => $transaction->user_id,
                'transaction_type' => $transaction->type,
            ],
        ];

        // Add payment method specific data
        if ($method === 'card' && isset($data['card_token'])) {
            $payload['card_token'] = $data['card_token'];
        }

        // Add signature for security
        $payload['signature'] = $this->generateSignature($payload);

        return $payload;
    }

    /**
     * Generate HMAC signature for request
     */
    protected function generateSignature(array $data): string
    {
        // Remove signature field if exists
        unset($data['signature']);

        // Sort array by keys
        ksort($data);

        // Create string to sign
        $stringToSign = http_build_query($data);

        // Generate HMAC-SHA256 signature
        return hash_hmac('sha256', $stringToSign, $this->secretKey);
    }

    /**
     * Verify webhook signature
     */
    protected function verifyWebhookSignature(array $data): bool
    {
        $receivedSignature = $data['signature'] ?? '';

        if (empty($receivedSignature)) {
            return false;
        }

        $calculatedSignature = $this->generateSignature($data);

        return hash_equals($calculatedSignature, $receivedSignature);
    }

    /**
     * Call PaySolutions API
     */
    protected function callApi(string $endpoint, array $payload, string $method = 'POST'): array
    {
        try {
            $url = $this->apiUrl . $endpoint;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->apiKey,
                'X-Merchant-ID' => $this->merchantId,
            ])->timeout(30);

            if ($method === 'GET') {
                $response = $response->get($url, $payload);
            } else {
                $response = $response->post($url, $payload);
            }

            if ($response->failed()) {
                throw new Exception('PaySolutions API request failed: ' . $response->body());
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('PaySolutions API error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Payment gateway communication error: ' . $e->getMessage());
        }
    }

    /**
     * Get payment description
     */
    protected function getPaymentDescription(PaymentTransaction $transaction): string
    {
        if ($transaction->type === 'order_payment' && $transaction->order) {
            return 'Payment for Order #' . $transaction->order->order_number;
        }

        if ($transaction->type === 'wallet_topup') {
            return 'Wallet Top-up';
        }

        return 'Payment';
    }
}
