<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Payment Provider
 *
 * รองรับการชำระเงินผ่าน Stripe (บัตรเครดิต/เดบิต)
 *
 * @see https://stripe.com/docs
 */
class StripeProvider implements PaymentProviderInterface
{
    protected $gateway;

    protected $apiKey;

    protected $secretKey;

    protected $webhookSecret;

    protected $testMode;

    public function __construct()
    {
        try {
            $this->gateway = PaymentGateway::findByCode('stripe');

            if ($this->gateway) {
                $this->testMode = $this->gateway->test_mode;
                $this->apiKey = $this->gateway->getCredential('api_key');
                $this->secretKey = $this->gateway->getCredential('secret_key');
                $this->webhookSecret = $this->gateway->getCredential('webhook_secret');
            }
        } catch (\Exception $e) {
            // ⚠️ ถ้า database ไม่พร้อมใช้งาน ให้ข้ามการโหลด config
            Log::debug('StripeProvider: Cannot load gateway config - '.$e->getMessage());
            $this->gateway = null;
        }
    }

    /**
     * Validate Stripe payment
     *
     * @throws Exception
     */
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        // ตรวจสอบว่า gateway พร้อมใช้งาน
        if (! $this->gateway || ! $this->gateway->isConfigured()) {
            throw new Exception('Stripe gateway is not configured');
        }

        // ตรวจสอบว่า gateway เปิดใช้งาน
        if (! $this->gateway->is_active) {
            throw new Exception('Stripe gateway is not active');
        }

        // ตรวจสอบจำนวนเงิน
        if ($transaction->amount <= 0) {
            throw new Exception('Invalid payment amount');
        }

        // Stripe minimum amount is 20 THB (2000 satang)
        $minAmount = 20;
        if ($transaction->amount < $minAmount) {
            throw new Exception("Minimum payment amount for Stripe is {$minAmount} THB");
        }

        // ตรวจสอบ payment token หรือ payment method
        if (empty($data['payment_method_id']) && empty($data['payment_token'])) {
            throw new Exception('Payment method or token is required');
        }

        return true;
    }

    /**
     * Process Stripe payment
     *
     * @throws Exception
     */
    public function process(PaymentTransaction $transaction, array $data): array
    {
        try {
            // สร้าง Payment Intent
            $paymentIntent = $this->createPaymentIntent($transaction, $data);

            if (! $paymentIntent || ! isset($paymentIntent['id'])) {
                throw new Exception('Failed to create Stripe payment intent');
            }

            Log::info('Stripe payment initiated', [
                'transaction_id' => $transaction->transaction_id,
                'payment_intent_id' => $paymentIntent['id'],
                'amount' => $transaction->amount,
            ]);

            // ถ้า payment สำเร็จทันที
            if (isset($paymentIntent['status']) && $paymentIntent['status'] === 'succeeded') {
                return [
                    'status' => 'completed',
                    'gateway' => 'stripe',
                    'gateway_transaction_id' => $paymentIntent['id'],
                    'response' => [
                        'payment_intent_id' => $paymentIntent['id'],
                        'status' => $paymentIntent['status'],
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                    ],
                ];
            }

            // ถ้าต้องรอ confirm หรือ 3D Secure
            return [
                'status' => 'processing',
                'gateway' => 'stripe',
                'gateway_transaction_id' => $paymentIntent['id'],
                'client_secret' => $paymentIntent['client_secret'] ?? null,
                'requires_action' => $paymentIntent['status'] === 'requires_action',
                'response' => [
                    'payment_intent_id' => $paymentIntent['id'],
                    'status' => $paymentIntent['status'],
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                ],
            ];
        } catch (Exception $e) {
            Log::error('Stripe payment processing error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify Stripe payment (webhook callback)
     */
    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        try {
            // ตรวจสอบ webhook signature
            if (! $this->verifyWebhookSignature($data)) {
                Log::warning('Stripe webhook signature verification failed', [
                    'transaction_id' => $transaction->transaction_id,
                ]);

                return false;
            }

            // ตรวจสอบ payment intent ID
            $paymentIntentId = $data['data']['object']['id'] ?? null;
            if ($paymentIntentId !== $transaction->gateway_transaction_id) {
                return false;
            }

            // ตรวจสอบจำนวนเงิน
            $paidAmount = ($data['data']['object']['amount_received'] ?? 0) / 100; // Stripe ใช้ satang
            if (abs($paidAmount - $transaction->amount) > 0.01) {
                Log::warning('Stripe payment amount mismatch', [
                    'transaction_id' => $transaction->transaction_id,
                    'expected' => $transaction->amount,
                    'received' => $paidAmount,
                ]);

                return false;
            }

            // ตรวจสอบสถานะ
            $status = $data['data']['object']['status'] ?? '';

            return $status === 'succeeded';
        } catch (Exception $e) {
            Log::error('Stripe verification error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Refund Stripe payment
     *
     * @throws Exception
     */
    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        try {
            $refund = $this->createRefund($transaction, $amount);

            Log::info('Stripe refund processed', [
                'transaction_id' => $transaction->transaction_id,
                'refund_id' => $refund['id'] ?? null,
                'amount' => $amount,
            ]);

            return [
                'status' => 'completed',
                'refund_id' => $refund['id'] ?? null,
                'refund_amount' => $amount,
                'refunded_at' => now()->toIso8601String(),
            ];
        } catch (Exception $e) {
            Log::error('Stripe refund error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create Stripe Payment Intent
     *
     * @throws Exception
     */
    protected function createPaymentIntent(PaymentTransaction $transaction, array $data): array
    {
        $payload = [
            'amount' => (int) ($transaction->amount * 100), // แปลงเป็น satang
            'currency' => strtolower($transaction->currency ?? 'thb'),
            'payment_method' => $data['payment_method_id'] ?? $data['payment_token'],
            'confirm' => true,
            'metadata' => [
                'transaction_id' => $transaction->transaction_id,
                'user_id' => $transaction->user_id,
                'type' => $transaction->type,
            ],
        ];

        // ถ้ามี customer ID
        if (! empty($data['customer_id'])) {
            $payload['customer'] = $data['customer_id'];
        }

        // Return URL สำหรับ 3D Secure
        $payload['return_url'] = route('payment.stripe.callback', ['transaction' => $transaction->id]);

        return $this->callStripeApi('payment_intents', $payload);
    }

    /**
     * Create Stripe Refund
     *
     * @throws Exception
     */
    protected function createRefund(PaymentTransaction $transaction, float $amount): array
    {
        $payload = [
            'payment_intent' => $transaction->gateway_transaction_id,
            'amount' => (int) ($amount * 100), // แปลงเป็น satang
        ];

        return $this->callStripeApi('refunds', $payload);
    }

    /**
     * Call Stripe API
     *
     * @throws Exception
     */
    protected function callStripeApi(string $endpoint, array $payload): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->timeout(30)
                ->post("https://api.stripe.com/v1/{$endpoint}", $payload);

            if ($response->failed()) {
                $error = $response->json();
                throw new Exception($error['error']['message'] ?? 'Stripe API request failed');
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('Stripe API error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Payment gateway communication error: '.$e->getMessage());
        }
    }

    /**
     * Verify Stripe webhook signature
     */
    protected function verifyWebhookSignature(array $data): bool
    {
        // ในการใช้งานจริงต้องตรวจสอบ signature จาก header
        // Stripe-Signature header
        if (empty($this->webhookSecret)) {
            // ถ้าไม่มี webhook secret ให้ข้ามการตรวจสอบ (ไม่แนะนำ)
            return true;
        }

        // TODO: Implement proper webhook signature verification
        // $signature = request()->header('Stripe-Signature');
        // return Webhook::constructEvent($payload, $signature, $this->webhookSecret);

        return true;
    }

    /**
     * Check payment status
     */
    public function checkStatus(PaymentTransaction $transaction): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->timeout(30)
                ->get("https://api.stripe.com/v1/payment_intents/{$transaction->gateway_transaction_id}");

            if ($response->failed()) {
                return [
                    'status' => 'unknown',
                    'error' => 'Failed to fetch payment status',
                ];
            }

            $paymentIntent = $response->json();

            return [
                'status' => $paymentIntent['status'] ?? 'unknown',
                'amount' => ($paymentIntent['amount'] ?? 0) / 100,
                'paid_at' => isset($paymentIntent['created']) ? date('c', $paymentIntent['created']) : null,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }
}
