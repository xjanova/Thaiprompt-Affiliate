<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payment\PaymentService;
use App\Services\Shop\ShopPresenter;
use App\Support\Shop\PaymentMethod;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * PaymentApiController
 *
 * API endpoints สำหรับ Payment ใน Mobile App
 * รองรับ: Payment methods, Initialize payment, Check status
 */
class PaymentApiController extends Controller
{
    /** วิธีชำระที่แอปใช้กับออเดอร์ร้านค้าที่ค้างจ่ายได้ */
    private const APP_PAYMENT_METHODS = [PaymentMethod::WALLET, PaymentMethod::PROMPTPAY, PaymentMethod::BANK_TRANSFER];

    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    // =====================================================
    // Payment Methods
    // =====================================================

    /**
     * ดึงรายการวิธีการชำระเงินที่ใช้ได้
     */
    public function getMethods(Request $request): JsonResponse
    {
        try {
            // ดึง payment methods ที่พร้อมใช้งาน
            $methods = $this->paymentService->getAvailablePaymentMethods();

            // Filter เฉพาะที่เปิดใช้งาน
            $enabledMethods = array_filter($methods, function ($method) {
                return $method['enabled'] ?? false;
            });

            // จัดกลุ่มตาม category
            $grouped = [];
            foreach ($enabledMethods as $method) {
                $category = $method['category'] ?? 'other';
                if (! isset($grouped[$category])) {
                    $grouped[$category] = [];
                }
                $grouped[$category][] = [
                    'id' => $method['id'],
                    'name' => $method['name'],
                    'description' => $method['description'] ?? '',
                    'icon' => $method['icon'] ?? null,
                    'color' => $method['color'] ?? null,
                    'fees' => $method['fees'] ?? null,
                    'limits' => $method['limits'] ?? null,
                    'test_mode' => $method['test_mode'] ?? false,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'methods' => array_values($enabledMethods),
                    'grouped' => $grouped,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get payment methods', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงรายการวิธีการชำระเงินได้',
            ], 500);
        }
    }

    /**
     * ดึงรายการวิธีการเติมเงิน (Deposit)
     */
    public function getDepositMethods(): JsonResponse
    {
        try {
            $methods = $this->paymentService->getDepositMethods();

            return response()->json([
                'success' => true,
                'data' => array_values($methods),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงรายการวิธีการเติมเงินได้',
            ], 500);
        }
    }

    // =====================================================
    // Initialize Payment
    // =====================================================

    /**
     * เริ่มต้น/ชำระใหม่สำหรับ Order ที่ยังค้างจ่าย
     *
     * 🛒 (2026-09-25) SHOP-07: แอปเรียกเส้นนี้เมื่อต้องการ QR พร้อมเพย์ใหม่ (หมดอายุ/สร้างไม่สำเร็จตอน checkout)
     *    หรือเปลี่ยนไปจ่ายด้วยกระเป๋าเงิน — รับเฉพาะ wallet | promptpay | bank_transfer
     *    ออเดอร์ที่ยกเลิก/จ่ายแล้ว/เก็บเงินปลายทาง ชำระผ่านเส้นนี้ไม่ได้
     */
    public function initializeOrderPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|min:1',
            'payment_method' => 'required|string|max:30',
        ], [
            'order_id.required' => 'กรุณาระบุคำสั่งซื้อ',
            'payment_method.required' => 'กรุณาเลือกวิธีการชำระเงิน',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first() ?: 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $method = PaymentMethod::normalize((string) $request->input('payment_method'));

        try {
            $user = Auth::user();

            // ไม่พบ/ไม่ใช่ของผู้ใช้ → 404 เหมือนกัน (ไม่บอกว่ามีออเดอร์ของคนอื่น)
            $order = Order::where('user_id', $user->id)->find((int) $request->input('order_id'));
            if (! $order) {
                return response()->json([
                    'success' => false,
                    'code' => 'ORDER_NOT_FOUND',
                    'message' => 'ไม่พบคำสั่งซื้อ',
                ], 404);
            }

            if ($order->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'code' => 'ALREADY_PAID',
                    'message' => 'คำสั่งซื้อนี้ชำระเงินแล้ว',
                ], 409);
            }

            if ($order->isCod()) {
                return response()->json([
                    'success' => false,
                    'code' => 'COD_ORDER',
                    'message' => 'คำสั่งซื้อนี้เก็บเงินปลายทาง ชำระกับไรเดอร์เมื่อได้รับสินค้า',
                ], 409);
            }

            if (! $order->canRetryPayment()) {
                return response()->json([
                    'success' => false,
                    'code' => 'ORDER_NOT_PAYABLE',
                    'message' => 'คำสั่งซื้อนี้ชำระเงินไม่ได้แล้ว',
                ], 409);
            }

            if (! in_array($method, self::APP_PAYMENT_METHODS, true) || ! $this->paymentService->hasProvider(PaymentMethod::providerKey($method))) {
                return response()->json([
                    'success' => false,
                    'code' => 'PAYMENT_METHOD_UNAVAILABLE',
                    'message' => 'วิธีการชำระเงินนี้ไม่พร้อมใช้งานในแอป',
                ], 422);
            }

            $providerKey = PaymentMethod::providerKey($method);

            // Idempotency: มีรายการรอชำระที่ยังไม่หมดอายุ → คืนรายการเดิม (พร้อม QR)
            $existingTransaction = PaymentTransaction::where('type', 'order_payment')
                ->where('user_id', $user->id)
                ->where('order_id', $order->id)
                ->whereIn('status', ['pending', 'processing'])
                ->where('payment_method', $providerKey)
                ->latest('id')
                ->first();

            if ($existingTransaction && ! $existingTransaction->isExpired() && $method !== PaymentMethod::WALLET) {
                return response()->json([
                    'success' => true,
                    'message' => 'มีรายการรอชำระอยู่แล้ว',
                    'data' => ShopPresenter::payment($existingTransaction),
                ]);
            }

            // เปลี่ยนวิธีชำระของออเดอร์ให้ตรงกับที่ผู้ซื้อเลือกจริง
            if (PaymentMethod::normalize($order->payment_method) !== $method) {
                $order->suppressStatusNotification = true;
                $order->update(['payment_method' => $method]);
            }

            // สร้าง payment transaction ใหม่
            $transaction = $this->paymentService->createOrderPayment(
                $order,
                $providerKey,
                [
                    'metadata' => [
                        'source' => 'mobile_app',
                    ],
                ]
            );

            // ประมวลผล payment (ส่งเฉพาะข้อมูลที่ provider ต้องใช้ — ไม่ส่ง request ทั้งก้อน)
            $result = $this->paymentService->processPayment($transaction, []);

            if (! $result['success']) {
                $failure = (string) ($result['message'] ?? '');

                Log::warning('Order payment init failed', [
                    'order_id' => $order->id,
                    'method' => $method,
                    'message' => $failure,
                ]);

                return response()->json([
                    'success' => false,
                    'code' => str_contains($failure, 'Insufficient') ? 'INSUFFICIENT_BALANCE' : 'PAYMENT_FAILED',
                    'message' => str_contains($failure, 'Insufficient')
                        ? 'ยอดเงินในกระเป๋าไม่เพียงพอ'
                        : 'เริ่มการชำระเงินไม่สำเร็จ กรุณาลองใหม่',
                ], 422);
            }

            // สร้าง response สำหรับ mobile app
            $responseData = $this->buildPaymentResponse($result['transaction'], $result['data'] ?? []);

            $order->refresh();
            $responseData['order'] = [
                'id' => (int) $order->id,
                'status' => (string) $order->status,
                'payment_status' => (string) $order->payment_status,
            ];

            return response()->json([
                'success' => true,
                'message' => $order->payment_status === 'paid' ? 'ชำระเงินสำเร็จ' : 'เริ่มต้นการชำระเงินสำเร็จ',
                'data' => $responseData,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to initialize order payment', [
                'error' => $e->getMessage(),
                'order_id' => $request->input('order_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเริ่มต้นการชำระเงิน',
            ], 500);
        }
    }

    /**
     * เริ่มต้นการเติมเงิน Wallet
     */
    public function initializeWalletTopup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
        ], [
            'amount.required' => 'กรุณาระบุจำนวนเงิน',
            'amount.min' => 'จำนวนเงินต้องมากกว่า 0',
            'payment_method.required' => 'กรุณาเลือกวิธีการชำระเงิน',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();

            // ตรวจสอบว่า payment method พร้อมใช้งาน
            if (! $this->paymentService->hasProvider($request->payment_method)) {
                return response()->json([
                    'success' => false,
                    'message' => 'วิธีการชำระเงินไม่พร้อมใช้งาน',
                ], 400);
            }

            // สร้าง payment transaction
            $transaction = $this->paymentService->createWalletTopup(
                $user,
                $request->amount,
                $request->payment_method,
                [
                    'metadata' => [
                        'source' => 'mobile_app',
                        'device' => $request->header('User-Agent'),
                    ],
                ]
            );

            // ประมวลผล payment
            $result = $this->paymentService->processPayment($transaction, $request->all());

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'การเติมเงินล้มเหลว',
                ], 400);
            }

            // สร้าง response สำหรับ mobile app
            $responseData = $this->buildPaymentResponse($result['transaction'], $result['data'] ?? []);

            return response()->json([
                'success' => true,
                'message' => 'เริ่มต้นการเติมเงินสำเร็จ',
                'data' => $responseData,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to initialize wallet topup', [
                'error' => $e->getMessage(),
                'amount' => $request->amount,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเริ่มต้นการเติมเงิน',
            ], 500);
        }
    }

    // =====================================================
    // Payment Status
    // =====================================================

    /**
     * ตรวจสอบสถานะการชำระเงิน
     */
    public function checkStatus(string $transactionId): JsonResponse
    {
        try {
            $user = Auth::user();

            $transaction = PaymentTransaction::where('transaction_id', $transactionId)
                ->where('user_id', $user->id)
                ->first();

            if (! $transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบรายการชำระเงิน',
                ], 404);
            }

            // ถ้ายังอยู่ในสถานะ processing ให้ลองเช็คกับ gateway
            if ($transaction->status === 'processing' && $transaction->gateway_transaction_id) {
                try {
                    $provider = $this->paymentService->getProvider($transaction->payment_method);
                    if (method_exists($provider, 'checkStatus')) {
                        $gatewayStatus = $provider->checkStatus($transaction);
                        // อัพเดท metadata
                        $transaction->update([
                            'gateway_response' => array_merge(
                                $transaction->gateway_response ?? [],
                                ['last_check' => $gatewayStatus]
                            ),
                        ]);
                    }
                } catch (Exception $e) {
                    // ถ้าเช็คไม่ได้ก็ใช้สถานะเดิม
                    Log::debug('Failed to check gateway status', ['error' => $e->getMessage()]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'status' => $transaction->status,
                    'status_label' => $this->getStatusLabel($transaction->status),
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'payment_method' => $transaction->payment_method,
                    'type' => $transaction->type,
                    'order_id' => $transaction->order_id,
                    // สถานะออเดอร์ ให้แอป poll แล้วรู้ทันทีว่าร้านได้รับออเดอร์ที่จ่ายแล้ว
                    'order' => $transaction->order_id && ($order = Order::where('user_id', $user->id)->find($transaction->order_id)) ? [
                        'id' => (int) $order->id,
                        'order_number' => $order->order_number,
                        'status' => (string) $order->status,
                        'payment_status' => (string) $order->payment_status,
                    ] : null,
                    'created_at' => $transaction->created_at->toISOString(),
                    'completed_at' => $transaction->completed_at?->toISOString(),
                    'expired_at' => $transaction->expired_at?->toISOString(),
                    'is_expired' => $transaction->isExpired(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to check payment status', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถตรวจสอบสถานะการชำระเงินได้',
            ], 500);
        }
    }

    /**
     * ดึงประวัติการชำระเงิน
     */
    public function getHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = $request->input('per_page', 15);
            $type = $request->input('type'); // order_payment, wallet_topup

            $query = PaymentTransaction::where('user_id', $user->id)
                ->orderBy('created_at', 'desc');

            if ($type) {
                $query->where('type', $type);
            }

            $transactions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactions->map(function ($t) {
                        return [
                            'id' => $t->id,
                            'transaction_id' => $t->transaction_id,
                            'type' => $t->type,
                            'type_label' => $this->getTypeLabel($t->type),
                            'status' => $t->status,
                            'status_label' => $this->getStatusLabel($t->status),
                            'amount' => $t->amount,
                            'currency' => $t->currency,
                            'payment_method' => $t->payment_method,
                            'order_id' => $t->order_id,
                            'created_at' => $t->created_at->toISOString(),
                            'completed_at' => $t->completed_at?->toISOString(),
                        ];
                    }),
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'last_page' => $transactions->lastPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                    ],
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงประวัติการชำระเงินได้',
            ], 500);
        }
    }

    // =====================================================
    // Helper Methods
    // =====================================================

    /**
     * สร้าง response สำหรับ payment
     */
    protected function buildPaymentResponse(PaymentTransaction $transaction, array $paymentData): array
    {
        // ออเดอร์ร้านค้าใช้รูปแบบกลางเดียวกับ checkout (App\Services\Shop\ShopPresenter::payment)
        if ($transaction->type === 'order_payment') {
            return ShopPresenter::payment($transaction, $paymentData);
        }

        $response = [
            'transaction_id' => $transaction->transaction_id,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'payment_method' => $transaction->payment_method,
            'expired_at' => $transaction->expired_at?->toISOString(),
        ];

        // เพิ่มข้อมูลเฉพาะตาม payment method
        $paymentResponse = $paymentData['response'] ?? [];

        // QR Code สำหรับ PromptPay
        if (isset($paymentData['qr_code'])) {
            $response['qr_code'] = $paymentData['qr_code'];
            $response['qr_code_url'] = $paymentData['qr_code_url'] ?? null;
        }

        // Redirect URL สำหรับ gateway ที่ต้อง redirect
        if (isset($paymentData['approval_url'])) {
            $response['redirect_url'] = $paymentData['approval_url'];
            $response['redirect_required'] = true;
        } elseif (isset($paymentData['authorize_uri'])) {
            $response['redirect_url'] = $paymentData['authorize_uri'];
            $response['redirect_required'] = true;
        } elseif (isset($paymentResponse['redirect_required']) && $paymentResponse['redirect_required']) {
            $response['redirect_required'] = true;
        }

        // Client secret สำหรับ Stripe
        if (isset($paymentData['client_secret'])) {
            $response['client_secret'] = $paymentData['client_secret'];
        }

        // Deep link สำหรับ TrueMoney หรืออื่นๆ
        if (isset($paymentResponse['deep_link'])) {
            $response['deep_link'] = $paymentResponse['deep_link'];
        }

        // Bank info สำหรับ Bank Transfer
        if ($transaction->payment_method === 'bank_transfer' && isset($paymentResponse['bank_name'])) {
            $response['bank_info'] = [
                'bank_name' => $paymentResponse['bank_name'],
                'bank_code' => $paymentResponse['bank_code'] ?? null,
                'account_number' => $paymentResponse['account_number'],
                'account_name' => $paymentResponse['account_name'],
                'branch' => $paymentResponse['branch'] ?? null,
                'ref_no' => $paymentResponse['ref_no'] ?? null,
                'instructions' => $paymentResponse['instructions'] ?? null,
            ];
        }

        // Gateway transaction ID
        if ($transaction->gateway_transaction_id) {
            $response['gateway_transaction_id'] = $transaction->gateway_transaction_id;
        }

        return $response;
    }

    /**
     * แปลง status เป็น label ภาษาไทย
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'รอดำเนินการ',
            'processing' => 'กำลังดำเนินการ',
            'completed' => 'สำเร็จ',
            'failed' => 'ล้มเหลว',
            'cancelled' => 'ยกเลิก',
            'refunded' => 'คืนเงินแล้ว',
            'expired' => 'หมดอายุ',
            default => $status,
        };
    }

    /**
     * แปลง type เป็น label ภาษาไทย
     */
    protected function getTypeLabel(string $type): string
    {
        return match ($type) {
            'order_payment' => 'ชำระเงินคำสั่งซื้อ',
            'wallet_topup' => 'เติมเงิน Wallet',
            'withdrawal' => 'ถอนเงิน',
            'refund' => 'คืนเงิน',
            default => $type,
        };
    }
}
