<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\PaymentGateway;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Razorpay Payment Provider
 *
 * รองรับการชำระเงินผ่าน Razorpay (รองรับ India และต่างประเทศ)
 *
 * @see https://razorpay.com/docs/api/
 */
class RazorpayProvider implements PaymentProviderInterface
{
    protected $gateway;
    protected $keyId;
    protected $keySecret;
    protected $testMode;

    protected const API_URL = 'https://api.razorpay.com/v1';

    public function __construct()
    {
        try {
            $this->gateway = PaymentGateway::findByCode('razorpay');

            if ($this->gateway) {
                $this->testMode = $this->gateway->test_mode;
                $this->keyId = $this->gateway->getCredential('key_id');
                $this->keySecret = $this->gateway->getCredential('key_secret');
            }
        } catch (\Exception $e) {
            // ⚠️ ถ้า database ไม่พร้อมใช้งาน ให้ข้ามการโหลด config
            Log::debug('RazorpayProvider: Cannot load gateway config - ' . $e->getMessage());
            $this->gateway = null;
        }
    }

    /**
     * Validate Razorpay payment
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
            throw new Exception('Razorpay gateway is not configured');
        }

        // ตรวจสอบว่า gateway เปิดใช้งาน
        if (!$this->gateway->is_active) {
            throw new Exception('Razorpay gateway is not active');
        }

        // ตรวจสอบจำนวนเงิน
        if ($transaction->amount <= 0) {
            throw new Exception('Invalid payment amount');
        }

        // Razorpay minimum amount is 1 INR (100 paise)
        // สำหรับ THB จะใช้ค่าเริ่มต้นที่ 1
        $minAmount = 1;
        if ($transaction->amount < $minAmount) {
            throw new Exception("Minimum payment amount for Razorpay is {$minAmount}");
        }

        return true;
    }

    /**
     * Process Razorpay payment
     *
     * @param PaymentTransaction $transaction
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function process(PaymentTransaction $transaction, array $data): array
    {
        try {
            // สร้าง Razorpay Order
            $order = $this->createOrder($transaction, $data);

            if (!$order || !isset($order['id'])) {
                throw new Exception('Failed to create Razorpay order');
            }

            Log::info('Razorpay payment initiated', [
                'transaction_id' => $transaction->transaction_id,
                'order_id' => $order['id'],
                'amount' => $transaction->amount,
            ]);

            return [
                'status' => 'processing',
                'gateway' => 'razorpay',
                'gateway_transaction_id' => $order['id'],
                'response' => [
                    'order_id' => $order['id'],
                    'status' => $order['status'],
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'key_id' => $this->keyId,
                    'redirect_required' => true,
                ],
            ];
        } catch (Exception $e) {
            Log::error('Razorpay payment processing error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify Razorpay payment (webhook callback)
     *
     * @param PaymentTransaction $transaction
     * @param array $data
     * @return bool
     */
    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        try {
            // ตรวจสอบ razorpay_signature
            $razorpayOrderId = $data['razorpay_order_id'] ?? '';
            $razorpayPaymentId = $data['razorpay_payment_id'] ?? '';
            $razorpaySignature = $data['razorpay_signature'] ?? '';

            // ตรวจสอบว่า order ID ตรงกัน
            if ($razorpayOrderId !== $transaction->gateway_transaction_id) {
                return false;
            }

            // Verify signature
            $generatedSignature = hash_hmac(
                'sha256',
                $razorpayOrderId . '|' . $razorpayPaymentId,
                $this->keySecret
            );

            if (!hash_equals($generatedSignature, $razorpaySignature)) {
                Log::warning('Razorpay signature verification failed', [
                    'transaction_id' => $transaction->transaction_id,
                ]);
                return false;
            }

            // ดึงข้อมูล payment เพื่อยืนยันจำนวนเงิน
            $payment = $this->getPayment($razorpayPaymentId);

            if (!$payment) {
                return false;
            }

            // ตรวจสอบจำนวนเงิน (Razorpay ใช้ paise/satang)
            $paidAmount = ($payment['amount'] ?? 0) / 100;
            if (abs($paidAmount - $transaction->amount) > 0.01) {
                Log::warning('Razorpay payment amount mismatch', [
                    'transaction_id' => $transaction->transaction_id,
                    'expected' => $transaction->amount,
                    'received' => $paidAmount,
                ]);
                return false;
            }

            // ตรวจสอบสถานะ
            return ($payment['status'] ?? '') === 'captured';
        } catch (Exception $e) {
            Log::error('Razorpay verification error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Refund Razorpay payment
     *
     * @param PaymentTransaction $transaction
     * @param float $amount
     * @return array
     * @throws Exception
     */
    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        try {
            // ต้องมี payment_id สำหรับการ refund
            $paymentId = $transaction->metadata['razorpay_payment_id'] ?? null;

            if (!$paymentId) {
                throw new Exception('Payment ID not found for refund');
            }

            $refund = $this->createRefund($paymentId, $amount);

            Log::info('Razorpay refund processed', [
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
            Log::error('Razorpay refund error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create Razorpay Order
     *
     * @param PaymentTransaction $transaction
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function createOrder(PaymentTransaction $transaction, array $data): array
    {
        $currency = strtoupper($transaction->currency ?? 'THB');

        // Razorpay ใช้ paise (100 paise = 1 INR) หรือ satang สำหรับ THB
        $payload = [
            'amount' => (int) ($transaction->amount * 100),
            'currency' => $currency,
            'receipt' => $transaction->transaction_id,
            'notes' => [
                'transaction_id' => $transaction->transaction_id,
                'user_id' => $transaction->user_id,
                'type' => $transaction->type,
            ],
        ];

        return $this->callRazorpayApi('orders', $payload);
    }

    /**
     * Get payment details
     *
     * @param string $paymentId
     * @return array|null
     */
    protected function getPayment(string $paymentId): ?array
    {
        try {
            return $this->callRazorpayApi("payments/{$paymentId}", [], 'GET');
        } catch (Exception $e) {
            Log::error('Failed to get Razorpay payment', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create Razorpay Refund
     *
     * @param string $paymentId
     * @param float $amount
     * @return array
     * @throws Exception
     */
    protected function createRefund(string $paymentId, float $amount): array
    {
        $payload = [
            'amount' => (int) ($amount * 100), // แปลงเป็น paise/satang
        ];

        return $this->callRazorpayApi("payments/{$paymentId}/refund", $payload);
    }

    /**
     * Call Razorpay API
     *
     * @param string $endpoint
     * @param array $payload
     * @param string $method
     * @return array
     * @throws Exception
     */
    protected function callRazorpayApi(string $endpoint, array $payload = [], string $method = 'POST'): array
    {
        try {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->timeout(30);

            $url = self::API_URL . '/' . $endpoint;

            if ($method === 'GET') {
                $response = $response->get($url, $payload);
            } else {
                $response = $response->post($url, $payload);
            }

            if ($response->failed()) {
                $error = $response->json();
                throw new Exception($error['error']['description'] ?? 'Razorpay API request failed');
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('Razorpay API error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Payment gateway communication error: ' . $e->getMessage());
        }
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
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->timeout(30)
                ->get(self::API_URL . "/orders/{$transaction->gateway_transaction_id}");

            if ($response->failed()) {
                return [
                    'status' => 'unknown',
                    'error' => 'Failed to fetch payment status',
                ];
            }

            $order = $response->json();

            return [
                'status' => $order['status'] ?? 'unknown',
                'amount' => ($order['amount'] ?? 0) / 100,
                'amount_paid' => ($order['amount_paid'] ?? 0) / 100,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }
}
