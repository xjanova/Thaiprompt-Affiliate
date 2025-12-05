<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\PaymentGateway;
use App\Models\Order;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * PaymentApiController
 *
 * API endpoints สำหรับ Payment ใน Mobile App
 * รองรับ: Payment methods, Initialize payment, Check status
 */
class PaymentApiController extends Controller
{
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
     *
     * @param Request $request
     * @return JsonResponse
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
                if (!isset($grouped[$category])) {
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
     *
     * @return JsonResponse
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
     * เริ่มต้นการชำระเงินสำหรับ Order
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initializeOrderPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'payment_method' => 'required|string',
        ], [
            'order_id.required' => 'กรุณาระบุ order',
            'order_id.exists' => 'ไม่พบ order',
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
            $order = Order::findOrFail($request->order_id);

            // ตรวจสอบว่า order เป็นของ user นี้
            if ($order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์ชำระเงินสำหรับ order นี้',
                ], 403);
            }

            // ตรวจสอบสถานะ order
            if ($order->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order นี้ชำระเงินแล้ว',
                ], 400);
            }

            // ตรวจสอบว่า payment method พร้อมใช้งาน
            if (!$this->paymentService->hasProvider($request->payment_method)) {
                return response()->json([
                    'success' => false,
                    'message' => 'วิธีการชำระเงินไม่พร้อมใช้งาน',
                ], 400);
            }

            // สร้าง payment transaction
            $transaction = $this->paymentService->createOrderPayment(
                $order,
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

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'การชำระเงินล้มเหลว',
                ], 400);
            }

            // สร้าง response สำหรับ mobile app
            $responseData = $this->buildPaymentResponse($result['transaction'], $result['data'] ?? []);

            return response()->json([
                'success' => true,
                'message' => 'เริ่มต้นการชำระเงินสำเร็จ',
                'data' => $responseData,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to initialize order payment', [
                'error' => $e->getMessage(),
                'order_id' => $request->order_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเริ่มต้นการชำระเงิน',
            ], 500);
        }
    }

    /**
     * เริ่มต้นการเติมเงิน Wallet
     *
     * @param Request $request
     * @return JsonResponse
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
            if (!$this->paymentService->hasProvider($request->payment_method)) {
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

            if (!$result['success']) {
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
     *
     * @param string $transactionId
     * @return JsonResponse
     */
    public function checkStatus(string $transactionId): JsonResponse
    {
        try {
            $user = Auth::user();

            $transaction = PaymentTransaction::where('transaction_id', $transactionId)
                ->where('user_id', $user->id)
                ->first();

            if (!$transaction) {
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
     *
     * @param Request $request
     * @return JsonResponse
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
     *
     * @param PaymentTransaction $transaction
     * @param array $paymentData
     * @return array
     */
    protected function buildPaymentResponse(PaymentTransaction $transaction, array $paymentData): array
    {
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
     *
     * @param string $status
     * @return string
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
     *
     * @param string $type
     * @return string
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
