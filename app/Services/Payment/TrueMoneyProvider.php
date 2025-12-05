<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\PaymentGateway;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TrueMoney Wallet Payment Provider
 *
 * รองรับการชำระเงินผ่าน TrueMoney Wallet (e-wallet ยอดนิยมในประเทศไทย)
 *
 * @see https://developer.truemoney.com/
 */
class TrueMoneyProvider implements PaymentProviderInterface
{
    protected $gateway;
    protected $appId;
    protected $appSecret;
    protected $merchantId;
    protected $testMode;

    protected const SANDBOX_URL = 'https://api-sandbox.truemoney.com';
    protected const LIVE_URL = 'https://api.truemoney.com';

    public function __construct()
    {
        try {
            $this->gateway = PaymentGateway::findByCode('truemoney');

            if ($this->gateway) {
                $this->testMode = $this->gateway->test_mode;
                $this->appId = $this->gateway->getCredential('app_id');
                $this->appSecret = $this->gateway->getCredential('app_secret');
                $this->merchantId = $this->gateway->getCredential('merchant_id');
            }
        } catch (\Exception $e) {
            // ⚠️ ถ้า database ไม่พร้อมใช้งาน ให้ข้ามการโหลด config
            Log::debug('TrueMoneyProvider: Cannot load gateway config - ' . $e->getMessage());
            $this->gateway = null;
        }
    }

    /**
     * Get API base URL
     *
     * @return string
     */
    protected function getApiUrl(): string
    {
        return $this->testMode ? self::SANDBOX_URL : self::LIVE_URL;
    }

    /**
     * Validate TrueMoney payment
     *
     * @param PaymentTransaction $transaction
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        // ตรวจสอบว่า gateway พร้อมใช้งาน
        if (!$this->gateway || !$this->gateway->isConfigured()) {
            throw new Exception('TrueMoney gateway is not configured');
        }

        // ตรวจสอบว่า gateway เปิดใช้งาน
        if (!$this->gateway->is_active) {
            throw new Exception('TrueMoney gateway is not active');
        }

        // ตรวจสอบจำนวนเงิน
        if ($transaction->amount <= 0) {
            throw new Exception('Invalid payment amount');
        }

        // TrueMoney minimum amount is 1 THB
        $minAmount = 1;
        if ($transaction->amount < $minAmount) {
            throw new Exception("Minimum payment amount for TrueMoney is {$minAmount} THB");
        }

        // TrueMoney maximum amount is 50,000 THB per transaction
        $maxAmount = 50000;
        if ($transaction->amount > $maxAmount) {
            throw new Exception("Maximum payment amount for TrueMoney is {$maxAmount} THB");
        }

        return true;
    }

    /**
     * Process TrueMoney payment
     *
     * @param PaymentTransaction $transaction
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function process(PaymentTransaction $transaction, array $data): array
    {
        try {
            // สร้าง TrueMoney Payment Request
            $payment = $this->createPaymentRequest($transaction, $data);

            if (!$payment || !isset($payment['transaction_id'])) {
                throw new Exception('Failed to create TrueMoney payment request');
            }

            Log::info('TrueMoney payment initiated', [
                'transaction_id' => $transaction->transaction_id,
                'truemoney_transaction_id' => $payment['transaction_id'],
                'amount' => $transaction->amount,
            ]);

            // ถ้ามี redirect URL (สำหรับ web payment)
            $redirectUrl = $payment['redirect_url'] ?? null;

            return [
                'status' => 'processing',
                'gateway' => 'truemoney',
                'gateway_transaction_id' => $payment['transaction_id'],
                'redirect_url' => $redirectUrl,
                'response' => [
                    'transaction_id' => $payment['transaction_id'],
                    'status' => $payment['status'] ?? 'pending',
                    'amount' => $transaction->amount,
                    'currency' => 'THB',
                    'redirect_required' => !empty($redirectUrl),
                    'deep_link' => $payment['deep_link'] ?? null,
                ],
            ];
        } catch (Exception $e) {
            Log::error('TrueMoney payment processing error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify TrueMoney payment (webhook callback)
     *
     * @param PaymentTransaction $transaction
     * @param array $data
     * @return bool
     */
    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        try {
            // ตรวจสอบ transaction ID
            $transactionId = $data['transaction_id'] ?? null;

            if ($transactionId !== $transaction->gateway_transaction_id) {
                return false;
            }

            // ตรวจสอบ signature (ถ้ามี)
            if (isset($data['signature'])) {
                if (!$this->verifySignature($data)) {
                    Log::warning('TrueMoney signature verification failed', [
                        'transaction_id' => $transaction->transaction_id,
                    ]);
                    return false;
                }
            }

            // ตรวจสอบจำนวนเงิน
            $amount = $data['amount'] ?? 0;
            if (abs((float) $amount - $transaction->amount) > 0.01) {
                Log::warning('TrueMoney payment amount mismatch', [
                    'transaction_id' => $transaction->transaction_id,
                    'expected' => $transaction->amount,
                    'received' => $amount,
                ]);
                return false;
            }

            // ตรวจสอบสถานะ
            $status = $data['status'] ?? '';
            return in_array($status, ['SUCCESS', 'COMPLETED', 'success', 'completed']);
        } catch (Exception $e) {
            Log::error('TrueMoney verification error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Refund TrueMoney payment
     *
     * @param PaymentTransaction $transaction
     * @param float $amount
     * @return array
     * @throws Exception
     */
    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        try {
            $refund = $this->createRefund($transaction, $amount);

            Log::info('TrueMoney refund processed', [
                'transaction_id' => $transaction->transaction_id,
                'refund_id' => $refund['refund_id'] ?? null,
                'amount' => $amount,
            ]);

            return [
                'status' => 'completed',
                'refund_id' => $refund['refund_id'] ?? null,
                'refund_amount' => $amount,
                'refunded_at' => now()->toIso8601String(),
            ];
        } catch (Exception $e) {
            Log::error('TrueMoney refund error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create TrueMoney Payment Request
     *
     * @param PaymentTransaction $transaction
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function createPaymentRequest(PaymentTransaction $transaction, array $data): array
    {
        $payload = [
            'merchant_id' => $this->merchantId,
            'reference_id' => $transaction->transaction_id,
            'amount' => number_format($transaction->amount, 2, '.', ''),
            'currency' => 'THB',
            'description' => $this->getDescription($transaction),
            'return_url' => route('payment.truemoney.success', ['transaction' => $transaction->id]),
            'cancel_url' => route('payment.truemoney.cancel', ['transaction' => $transaction->id]),
            'callback_url' => route('payment.truemoney.callback'),
            'metadata' => [
                'transaction_id' => $transaction->transaction_id,
                'user_id' => $transaction->user_id,
                'type' => $transaction->type,
            ],
        ];

        // ถ้ามีเบอร์โทรศัพท์ (สำหรับ direct payment)
        if (!empty($data['phone_number'])) {
            $payload['payer_phone'] = $data['phone_number'];
        }

        return $this->callTrueMoneyApi('payments', $payload);
    }

    /**
     * Create TrueMoney Refund
     *
     * @param PaymentTransaction $transaction
     * @param float $amount
     * @return array
     * @throws Exception
     */
    protected function createRefund(PaymentTransaction $transaction, float $amount): array
    {
        $payload = [
            'merchant_id' => $this->merchantId,
            'transaction_id' => $transaction->gateway_transaction_id,
            'refund_amount' => number_format($amount, 2, '.', ''),
            'reason' => 'Customer refund request',
        ];

        return $this->callTrueMoneyApi('refunds', $payload);
    }

    /**
     * Verify webhook signature
     *
     * @param array $data
     * @return bool
     */
    protected function verifySignature(array $data): bool
    {
        $signature = $data['signature'] ?? '';
        unset($data['signature']);

        // สร้าง signature จาก payload
        ksort($data);
        $payload = http_build_query($data);
        $expectedSignature = hash_hmac('sha256', $payload, $this->appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Call TrueMoney API
     *
     * @param string $endpoint
     * @param array $payload
     * @param string $method
     * @return array
     * @throws Exception
     */
    protected function callTrueMoneyApi(string $endpoint, array $payload = [], string $method = 'POST'): array
    {
        try {
            $timestamp = now()->format('Y-m-d\TH:i:s\Z');

            // สร้าง signature สำหรับ request
            $signatureData = $method . '/' . $endpoint . $timestamp . json_encode($payload);
            $signature = hash_hmac('sha256', $signatureData, $this->appSecret);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-App-Id' => $this->appId,
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
            ])->timeout(30);

            $url = $this->getApiUrl() . '/' . $endpoint;

            if ($method === 'GET') {
                $response = $response->get($url, $payload);
            } else {
                $response = $response->post($url, $payload);
            }

            if ($response->failed()) {
                $error = $response->json();
                throw new Exception($error['message'] ?? $error['error'] ?? 'TrueMoney API request failed');
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('TrueMoney API error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Payment gateway communication error: ' . $e->getMessage());
        }
    }

    /**
     * Get payment description
     *
     * @param PaymentTransaction $transaction
     * @return string
     */
    protected function getDescription(PaymentTransaction $transaction): string
    {
        if ($transaction->type === 'order_payment' && $transaction->order) {
            return 'ชำระเงินคำสั่งซื้อ #' . $transaction->order->order_number;
        }

        if ($transaction->type === 'wallet_topup') {
            return 'เติมเงิน Wallet';
        }

        return 'ชำระเงิน';
    }

    /**
     * Check payment status
     *
     * @param PaymentTransaction $transaction
     * @return array
     */
    public function checkStatus(PaymentTransaction $transaction): array
    {
        try {
            $result = $this->callTrueMoneyApi(
                "payments/{$transaction->gateway_transaction_id}",
                [],
                'GET'
            );

            return [
                'status' => $result['status'] ?? 'unknown',
                'amount' => $result['amount'] ?? 0,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }
}
