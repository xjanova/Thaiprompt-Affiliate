<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayPal Payment Provider
 *
 * รองรับการชำระเงินผ่าน PayPal
 *
 * @see https://developer.paypal.com/docs/
 */
class PayPalProvider implements PaymentProviderInterface
{
    protected $gateway;

    protected $clientId;

    protected $clientSecret;

    protected $mode;

    protected $accessToken;

    protected const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';

    protected const LIVE_URL = 'https://api-m.paypal.com';

    public function __construct()
    {
        try {
            $this->gateway = PaymentGateway::findByCode('paypal');

            if ($this->gateway) {
                $this->mode = $this->gateway->test_mode ? 'sandbox' : 'live';
                $this->clientId = $this->gateway->getCredential('client_id');
                $this->clientSecret = $this->gateway->getCredential('client_secret');
            }
        } catch (\Exception $e) {
            // ⚠️ ถ้า database ไม่พร้อมใช้งาน ให้ข้ามการโหลด config
            Log::debug('PayPalProvider: Cannot load gateway config - '.$e->getMessage());
            $this->gateway = null;
        }
    }

    /**
     * Get API base URL
     */
    protected function getApiUrl(): string
    {
        return $this->mode === 'sandbox' ? self::SANDBOX_URL : self::LIVE_URL;
    }

    /**
     * Validate PayPal payment
     *
     * @throws Exception
     */
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        // ตรวจสอบว่า gateway พร้อมใช้งาน
        if (! $this->gateway || ! $this->gateway->isConfigured()) {
            throw new Exception('PayPal gateway is not configured');
        }

        // ตรวจสอบว่า gateway เปิดใช้งาน
        if (! $this->gateway->is_active) {
            throw new Exception('PayPal gateway is not active');
        }

        // ตรวจสอบจำนวนเงิน
        if ($transaction->amount <= 0) {
            throw new Exception('Invalid payment amount');
        }

        // PayPal minimum amount
        $minAmount = 1;
        if ($transaction->amount < $minAmount) {
            throw new Exception("Minimum payment amount for PayPal is {$minAmount} USD");
        }

        return true;
    }

    /**
     * Process PayPal payment
     *
     * @throws Exception
     */
    public function process(PaymentTransaction $transaction, array $data): array
    {
        try {
            // สร้าง PayPal Order
            $order = $this->createOrder($transaction, $data);

            if (! $order || ! isset($order['id'])) {
                throw new Exception('Failed to create PayPal order');
            }

            // หา approval URL
            $approvalUrl = null;
            foreach ($order['links'] ?? [] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }

            Log::info('PayPal payment initiated', [
                'transaction_id' => $transaction->transaction_id,
                'order_id' => $order['id'],
                'amount' => $transaction->amount,
            ]);

            return [
                'status' => 'processing',
                'gateway' => 'paypal',
                'gateway_transaction_id' => $order['id'],
                'approval_url' => $approvalUrl,
                'response' => [
                    'order_id' => $order['id'],
                    'status' => $order['status'],
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'redirect_required' => true,
                ],
            ];
        } catch (Exception $e) {
            Log::error('PayPal payment processing error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify PayPal payment (webhook callback)
     */
    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        try {
            // ตรวจสอบ order ID
            $orderId = $data['resource']['id'] ?? $data['order_id'] ?? null;

            if ($orderId !== $transaction->gateway_transaction_id) {
                return false;
            }

            // ตรวจสอบสถานะ
            $status = $data['resource']['status'] ?? $data['status'] ?? '';

            if ($status === 'COMPLETED') {
                // ตรวจสอบจำนวนเงิน
                $amount = $data['resource']['purchase_units'][0]['amount']['value'] ?? 0;
                if (abs((float) $amount - $transaction->amount) > 0.01) {
                    Log::warning('PayPal payment amount mismatch', [
                        'transaction_id' => $transaction->transaction_id,
                        'expected' => $transaction->amount,
                        'received' => $amount,
                    ]);

                    return false;
                }

                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('PayPal verification error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Refund PayPal payment
     *
     * @throws Exception
     */
    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        try {
            // ต้องมี capture_id สำหรับการ refund
            $captureId = $transaction->metadata['capture_id'] ?? null;

            if (! $captureId) {
                throw new Exception('Capture ID not found for refund');
            }

            $refund = $this->createRefund($captureId, $amount, $transaction->currency);

            Log::info('PayPal refund processed', [
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
            Log::error('PayPal refund error', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get access token from PayPal
     *
     * @throws Exception
     */
    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post($this->getApiUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->failed()) {
            throw new Exception('Failed to get PayPal access token');
        }

        $this->accessToken = $response->json()['access_token'];

        return $this->accessToken;
    }

    /**
     * Create PayPal Order
     *
     * @throws Exception
     */
    protected function createOrder(PaymentTransaction $transaction, array $data): array
    {
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $transaction->transaction_id,
                    'description' => $this->getDescription($transaction),
                    'amount' => [
                        'currency_code' => strtoupper($transaction->currency ?? 'THB'),
                        'value' => number_format($transaction->amount, 2, '.', ''),
                    ],
                ],
            ],
            'application_context' => [
                'return_url' => route('payment.paypal.success', ['transaction' => $transaction->id]),
                'cancel_url' => route('payment.paypal.cancel', ['transaction' => $transaction->id]),
                'brand_name' => config('app.name'),
                'landing_page' => 'LOGIN',
                'user_action' => 'PAY_NOW',
            ],
        ];

        return $this->callPayPalApi('v2/checkout/orders', $payload);
    }

    /**
     * Capture PayPal Order
     *
     * @throws Exception
     */
    public function captureOrder(string $orderId): array
    {
        return $this->callPayPalApi("v2/checkout/orders/{$orderId}/capture", [], 'POST');
    }

    /**
     * Create PayPal Refund
     *
     * @throws Exception
     */
    protected function createRefund(string $captureId, float $amount, string $currency): array
    {
        $payload = [
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency_code' => strtoupper($currency ?? 'THB'),
            ],
        ];

        return $this->callPayPalApi("v2/payments/captures/{$captureId}/refund", $payload);
    }

    /**
     * Call PayPal API
     *
     * @throws Exception
     */
    protected function callPayPalApi(string $endpoint, array $payload = [], string $method = 'POST'): array
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = $this->getApiUrl().'/'.$endpoint;

            $response = Http::withToken($accessToken)
                ->timeout(30);

            if ($method === 'GET') {
                $response = $response->get($url, $payload);
            } else {
                $response = $response->post($url, $payload);
            }

            if ($response->failed()) {
                $error = $response->json();
                throw new Exception($error['message'] ?? 'PayPal API request failed');
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('PayPal API error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Payment gateway communication error: '.$e->getMessage());
        }
    }

    /**
     * Get payment description
     */
    protected function getDescription(PaymentTransaction $transaction): string
    {
        if ($transaction->type === 'order_payment' && $transaction->order) {
            return 'Payment for Order #'.$transaction->order->order_number;
        }

        if ($transaction->type === 'wallet_topup') {
            return 'Wallet Top-up';
        }

        return 'Payment';
    }

    /**
     * Check payment status
     */
    public function checkStatus(PaymentTransaction $transaction): array
    {
        try {
            $result = $this->callPayPalApi(
                "v2/checkout/orders/{$transaction->gateway_transaction_id}",
                [],
                'GET'
            );

            return [
                'status' => $result['status'] ?? 'unknown',
                'amount' => $result['purchase_units'][0]['amount']['value'] ?? 0,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }
}
