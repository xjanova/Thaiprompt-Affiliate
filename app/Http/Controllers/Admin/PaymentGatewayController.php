<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class PaymentGatewayController extends Controller
{
    /**
     * Display all payment gateways
     */
    public function index()
    {
        if (!auth()->user()->hasPermission('manage_payment_settings')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        try {
            $gateways = PaymentGateway::orderBy('category')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->groupBy('category');

            // Count configured gateways with error handling
            $configuredCount = 0;
            try {
                $configuredCount = PaymentGateway::get()->filter(function($g) {
                    try {
                        return $g->isConfigured();
                    } catch (\Throwable $e) {
                        \Log::error('Error checking isConfigured for gateway', [
                            'gateway_id' => $g->id ?? null,
                            'gateway_code' => $g->code ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                        return false;
                    }
                })->count();
            } catch (\Throwable $e) {
                \Log::error('Error counting configured gateways', [
                    'error' => $e->getMessage(),
                ]);
            }

            $stats = [
                'total' => PaymentGateway::count(),
                'active' => PaymentGateway::where('is_active', true)->count(),
                'configured' => $configuredCount,
                'coming_soon' => PaymentGateway::where('is_coming_soon', true)->count(),
            ];

            return view('admin.payment-gateways.index', compact('gateways', 'stats'));
        } catch (\Throwable $e) {
            \Log::error('Critical error in PaymentGatewayController::index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล Payment Gateway: ' . $e->getMessage());
        }
    }

    /**
     * Show configuration form for a specific gateway
     */
    public function edit(PaymentGateway $paymentGateway)
    {
        if (!auth()->user()->hasPermission('manage_payment_settings')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        return view('admin.payment-gateways.edit', compact('paymentGateway'));
    }

    /**
     * Update gateway configuration
     */
    public function update(Request $request, PaymentGateway $paymentGateway)
    {
        if (!auth()->user()->hasPermission('manage_payment_settings')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ในการดำเนินการนี้');
        }

        try {
            $data = [
                'is_active' => $request->boolean('is_active'),
                'test_mode' => $request->boolean('test_mode'),
                'supports_deposit' => $request->boolean('supports_deposit'),
                'supports_withdrawal' => $request->boolean('supports_withdrawal'),
            ];

            // Update basic info
            if ($request->filled('name')) {
                $data['name'] = $request->name;
            }

            if ($request->filled('description')) {
                $data['description'] = $request->description;
            }

            if ($request->filled('instructions')) {
                $data['instructions'] = $request->instructions;
            }

            if ($request->filled('help_url')) {
                $data['help_url'] = $request->help_url;
            }

            // Update credentials based on gateway type
            $credentials = $this->buildCredentials($request, $paymentGateway->code);
            if (!empty($credentials)) {
                $data['credentials'] = $credentials;
            }

            // Update test credentials if in test mode
            if ($request->boolean('test_mode')) {
                $testCredentials = $this->buildTestCredentials($request, $paymentGateway->code);
                if (!empty($testCredentials)) {
                    $data['test_credentials'] = $testCredentials;
                }
            }

            // Update fees
            if ($request->has('fees')) {
                $data['fees'] = [
                    'deposit_fee_type' => $request->input('fees.deposit_fee_type', 'percentage'),
                    'deposit_fee_amount' => $request->input('fees.deposit_fee_amount', 0),
                    'withdrawal_fee_type' => $request->input('fees.withdrawal_fee_type', 'percentage'),
                    'withdrawal_fee_amount' => $request->input('fees.withdrawal_fee_amount', 0),
                ];
            }

            // Update limits
            if ($request->has('limits')) {
                $data['limits'] = [
                    'min_deposit' => $request->input('limits.min_deposit', 1),
                    'max_deposit' => $request->input('limits.max_deposit', 1000000),
                    'min_withdrawal' => $request->input('limits.min_withdrawal', 100),
                    'max_withdrawal' => $request->input('limits.max_withdrawal', 100000),
                ];
            }

            $paymentGateway->update($data);

            Cache::forget('payment_gateways');
            Cache::forget('active_payment_gateways');

            return redirect()->back()->with('success', 'อัพเดทการตั้งค่าเรียบร้อยแล้ว');
        } catch (\Throwable $e) {
            \Log::error('Error updating payment gateway', [
                'gateway_id' => $paymentGateway->id ?? null,
                'gateway_code' => $paymentGateway->code ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Toggle gateway active status
     */
    public function toggle(PaymentGateway $paymentGateway)
    {
        if (!auth()->user()->hasPermission('manage_payment_settings')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $paymentGateway->is_active = !$paymentGateway->is_active;
            $paymentGateway->save();

            Cache::forget('payment_gateways');
            Cache::forget('active_payment_gateways');

            return response()->json([
                'success' => true,
                'is_active' => $paymentGateway->is_active,
                'message' => $paymentGateway->is_active ? 'เปิดใช้งานแล้ว' : 'ปิดใช้งานแล้ว',
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Test gateway connection
     */
    public function testConnection(PaymentGateway $paymentGateway)
    {
        if (!auth()->user()->hasPermission('manage_payment_settings')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $result = $paymentGateway->testConnection();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error testing payment gateway connection', [
                'gateway_id' => $paymentGateway->id ?? null,
                'gateway_code' => $paymentGateway->code ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update gateway sort order
     */
    public function updateOrder(Request $request)
    {
        if (!auth()->user()->hasPermission('manage_payment_settings')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $orders = $request->input('orders', []);

            foreach ($orders as $id => $order) {
                PaymentGateway::where('id', $id)->update(['sort_order' => $order]);
            }

            Cache::forget('payment_gateways');

            return response()->json(['success' => true, 'message' => 'อัพเดทลำดับเรียบร้อยแล้ว']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Build credentials array based on gateway code
     */
    protected function buildCredentials(Request $request, string $code): array
    {
        return match($code) {
            'promptpay' => [
                'promptpay_id' => $request->input('credentials.promptpay_id'),
                'promptpay_name' => $request->input('credentials.promptpay_name'),
                'promptpay_type' => $request->input('credentials.promptpay_type', 'phone'),
            ],
            'bank_transfer' => [
                'bank_name' => $request->input('credentials.bank_name'),
                'bank_code' => $request->input('credentials.bank_code'),
                'account_number' => $request->input('credentials.account_number'),
                'account_name' => $request->input('credentials.account_name'),
                'branch' => $request->input('credentials.branch'),
            ],
            'stripe' => [
                'api_key' => $request->input('credentials.api_key'),
                'secret_key' => $request->input('credentials.secret_key'),
                'webhook_secret' => $request->input('credentials.webhook_secret'),
            ],
            'paypal' => [
                'client_id' => $request->input('credentials.client_id'),
                'client_secret' => $request->input('credentials.client_secret'),
                'mode' => $request->input('credentials.mode', 'sandbox'),
            ],
            'razorpay' => [
                'key_id' => $request->input('credentials.key_id'),
                'key_secret' => $request->input('credentials.key_secret'),
                'webhook_secret' => $request->input('credentials.webhook_secret'),
            ],
            'truemoney' => [
                'app_id' => $request->input('credentials.app_id'),
                'app_secret' => $request->input('credentials.app_secret'),
                'app_token' => $request->input('credentials.app_token'),
            ],
            'thaiepay' => [
                'merchant_id' => $request->input('credentials.merchant_id'),
                'secret_key' => $request->input('credentials.secret_key'),
            ],
            'omise' => [
                'public_key' => $request->input('credentials.public_key'),
                'secret_key' => $request->input('credentials.secret_key'),
            ],
            default => [],
        };
    }

    /**
     * Build test credentials array
     */
    protected function buildTestCredentials(Request $request, string $code): array
    {
        return match($code) {
            'stripe' => [
                'api_key' => $request->input('test_credentials.api_key'),
                'secret_key' => $request->input('test_credentials.secret_key'),
            ],
            'paypal' => [
                'client_id' => $request->input('test_credentials.client_id'),
                'client_secret' => $request->input('test_credentials.client_secret'),
                'mode' => 'sandbox',
            ],
            default => [],
        };
    }
}
