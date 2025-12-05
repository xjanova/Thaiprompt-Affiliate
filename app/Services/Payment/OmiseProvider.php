<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\PaymentGateway;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Omise Payment Provider
 *
 * รองรับการชำระเงินผ่าน Omise (บัตรเครดิต/เดบิต, PromptPay, TrueMoney)
 *
 * @see https://docs.opn.ooo/
 */
class OmiseProvider implements PaymentProviderInterface
{
    protected $gateway;
    protected $publicKey;
    protected $secretKey;
    protected $testMode;

    protected const API_URL = 'https://api.omise.co';
    protected const VAULT_URL = 'https://vault.omise.co';

    public function __construct()
    {
        try {
            $this->gateway = PaymentGateway::findByCode('omise');

            if ($this->gateway) {
                $this->testMode = $this->gateway->test_mode;
                $this->publicKey = $this->gateway->getCredential('public_key');
                $this->secretKey = $this->gateway->getCredential('secret_key');
            }
        } catch (\Exception $e) {
            // ⚠️ ถ้า database ไม่พร้อมใช้งาน ให้ข้ามการโหลด config
            Log::debug('OmiseProvider: Cannot load gateway config - ' . $e->getMessage());
            $this->gateway = null;
        }
    }

    /**
     * Validate Omise payment
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
            throw new Exception('Omise gateway is not configured');
        }

        // ตรวจสอบว่า gateway เปิดใช้งาน
        if (!$this->gateway->is_active) {
            throw new Exception('Omise gateway is not active');
        }

        // ตรวจสอบจำนวนเงิน
        if ($transaction->amount <= 0) {
            throw new Exception('Invalid payment amount');
        }

        // Omise minimum amount is 20 THB (2000 satang)
        $minAmount = 20;
        if ($transaction->amount < $minAmount) {
            throw new Exception("Minimum payment amount for Omise is {$minAmount} THB");
        }

        // ตรวจสอบ payment token หรือ source
        if (empty($data['token']) && empty($data['source'])) {
            throw new Exception('Payment token or source is required');
        }

        return true;
    }

    /**
     * Process Omise payment
     *
     * @param PaymentTransaction $transaction
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function process(PaymentTransaction $transaction, array $data): array
    {
        try {
            // สร้าง Charge
            $charge = $this->createCharge($transaction, $data);

            if (!$charge || !isset($charge['id'])) {
                throw new Exception('Failed to create Omise charge');
            }

            Log::info('Omise payment initiated', [
                'transaction_id' => $transaction->transaction_id,
                'charge_id' => $charge['id'],
                'amount' => $transaction->amount,
            ]);

            // ตรวจสอบสถานะ charge
            $status = $charge['status'] ?? 'unknown';

            if ($status === 'successful') {
                return [
                    'status' => 'completed',
                    'gateway' => 'omise',
                    'gateway_transaction_id' => $charge['id'],
                    'response' => [
                        'charge_id' => $charge['id'],
                        'status' => $status,
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                    ],
                ];
            }

            // ถ้าต้องรอ redirect (เช่น 3D Secure, Internet Banking)
            if ($status === 'pending' && isset($charge['authorize_uri'])) {
                return [
                    'status' => 'processing',
                    'gateway' => 'omise',
                    'gateway_transaction_id' => $charge['id'],
                    'authorize_uri' => $charge['authorize_uri'],
                    'response' => [
                        'charge_id' => $charge['id'],
                        'status' => $status,
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                        'redirect_required' => true,
                    ],
                ];
            }

            // ถ้า failed
            if ($status === 'failed') {
                throw new Exception($charge['failure_message'] ?? 'Payment failed');
            }

            return [
                'status' => 'processing',
                'gateway' => 'omise',
                'gateway_transaction_id' => $charge['id'],
                'response' => [
                    'charge_id' => $charge['id'],
                    'status' => $status,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                ],
            ];
        } catch (Exception $e) {
            Log::error('Omise payment processing error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify Omise payment (webhook callback)
     *
     * @param PaymentTransaction $transaction
     * @param array $data
     * @return bool
     */
    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        try {
            // ตรวจสอบ event type
            $eventType = $data['key'] ?? '';

            if (!in_array($eventType, ['charge.complete', 'charge.create'])) {
                return false;
            }

            // ตรวจสอบ charge data
            $chargeData = $data['data'] ?? [];
            $chargeId = $chargeData['id'] ?? null;

            if ($chargeId !== $transaction->gateway_transaction_id) {
                return false;
            }

            // ตรวจสอบจำนวนเงิน
            $paidAmount = ($chargeData['amount'] ?? 0) / 100; // Omise ใช้ satang
            if (abs($paidAmount - $transaction->amount) > 0.01) {
                Log::warning('Omise payment amount mismatch', [
                    'transaction_id' => $transaction->transaction_id,
                    'expected' => $transaction->amount,
                    'received' => $paidAmount,
                ]);
                return false;
            }

            // ตรวจสอบสถานะ
            $status = $chargeData['status'] ?? '';
            return $status === 'successful';
        } catch (Exception $e) {
            Log::error('Omise verification error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Refund Omise payment
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

            Log::info('Omise refund processed', [
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
            Log::error('Omise refund error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create Omise Charge
     *
     * @param PaymentTransaction $transaction
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function createCharge(PaymentTransaction $transaction, array $data): array
    {
        $payload = [
            'amount' => (int) ($transaction->amount * 100), // แปลงเป็น satang
            'currency' => strtolower($transaction->currency ?? 'thb'),
            'metadata' => [
                'transaction_id' => $transaction->transaction_id,
                'user_id' => $transaction->user_id,
                'type' => $transaction->type,
            ],
            'return_uri' => route('payment.omise.callback', ['transaction' => $transaction->id]),
        ];

        // ใช้ token หรือ source
        if (!empty($data['token'])) {
            $payload['card'] = $data['token'];
        } elseif (!empty($data['source'])) {
            $payload['source'] = $data['source'];
        }

        // ถ้ามี customer ID
        if (!empty($data['customer_id'])) {
            $payload['customer'] = $data['customer_id'];
        }

        return $this->callOmiseApi('charges', $payload);
    }

    /**
     * Create Omise Refund
     *
     * @param PaymentTransaction $transaction
     * @param float $amount
     * @return array
     * @throws Exception
     */
    protected function createRefund(PaymentTransaction $transaction, float $amount): array
    {
        $payload = [
            'amount' => (int) ($amount * 100), // แปลงเป็น satang
        ];

        return $this->callOmiseApi("charges/{$transaction->gateway_transaction_id}/refunds", $payload);
    }

    /**
     * Call Omise API
     *
     * @param string $endpoint
     * @param array $payload
     * @param string $method
     * @return array
     * @throws Exception
     */
    protected function callOmiseApi(string $endpoint, array $payload, string $method = 'POST'): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->timeout(30);

            $url = self::API_URL . '/' . $endpoint;

            if ($method === 'GET') {
                $response = $response->get($url, $payload);
            } else {
                $response = $response->post($url, $payload);
            }

            if ($response->failed()) {
                $error = $response->json();
                throw new Exception($error['message'] ?? 'Omise API request failed');
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('Omise API error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Payment gateway communication error: ' . $e->getMessage());
        }
    }

    /**
     * Create Omise Source (for PromptPay, TrueMoney, etc.)
     *
     * @param string $type Source type (promptpay, truemoney_wallet, etc.)
     * @param int $amount Amount in satang
     * @return array
     * @throws Exception
     */
    public function createSource(string $type, int $amount): array
    {
        $payload = [
            'type' => $type,
            'amount' => $amount,
            'currency' => 'thb',
        ];

        return $this->callOmiseApi('sources', $payload);
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
            $response = Http::withBasicAuth($this->secretKey, '')
                ->timeout(30)
                ->get(self::API_URL . "/charges/{$transaction->gateway_transaction_id}");

            if ($response->failed()) {
                return [
                    'status' => 'unknown',
                    'error' => 'Failed to fetch payment status',
                ];
            }

            $charge = $response->json();

            return [
                'status' => $charge['status'] ?? 'unknown',
                'amount' => ($charge['amount'] ?? 0) / 100,
                'paid' => $charge['paid'] ?? false,
                'failure_message' => $charge['failure_message'] ?? null,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get public key for frontend
     *
     * @return string|null
     */
    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }
}
