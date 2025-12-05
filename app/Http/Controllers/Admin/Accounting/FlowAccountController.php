<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingFlowaccountConnection;
use App\Models\AccountingFlowaccountSyncLog;
use App\Models\AccountingInvoice;
use App\Models\AccountingExpense;
use App\Models\AccountingContact;
use App\Models\AccountingProduct;
use App\Services\FlowAccountService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FlowAccount Integration Controller
 *
 * จัดการการเชื่อมต่อและซิงค์ข้อมูลกับ FlowAccount API
 */
class FlowAccountController extends Controller
{
    /**
     * FlowAccount Service instance
     */
    protected FlowAccountService $flowAccountService;

    /**
     * Constructor - ตั้งค่า middleware และ inject service
     */
    public function __construct(FlowAccountService $flowAccountService)
    {
        $this->middleware(['auth', 'check.permission:accounting.manage_flowaccount']);
        $this->flowAccountService = $flowAccountService;
    }

    /**
     * แสดงหน้า Dashboard FlowAccount Integration
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        $stats = [];
        $syncLogs = collect();
        $companyInfo = null;

        if ($connection && $connection->isConnected()) {
            // ดึงสถิติการซิงค์
            $stats = $this->flowAccountService->getSyncStats($connection);

            // ดึง sync logs ล่าสุด
            $syncLogs = AccountingFlowaccountSyncLog::where('connection_id', $connection->id)
                ->latest('synced_at')
                ->limit(20)
                ->get();

            // ลองดึงข้อมูลบริษัท
            $companyInfo = $this->flowAccountService->getCompanyInfo($connection);
        }

        // ดึงการตั้งค่าจาก config
        $config = [
            'use_sandbox' => config('flowaccount.use_sandbox'),
            'features' => config('flowaccount.features'),
            'document_types' => config('flowaccount.document_types'),
        ];

        return view('admin.accounting.flowaccount.index', compact(
            'connection',
            'stats',
            'syncLogs',
            'companyInfo',
            'config'
        ));
    }

    /**
     * แสดงหน้าตั้งค่าการเชื่อมต่อ
     *
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        $config = config('flowaccount');

        return view('admin.accounting.flowaccount.settings', compact('connection', 'config'));
    }

    /**
     * แสดงหน้าฟอร์มเชื่อมต่อ FlowAccount
     *
     * @return \Illuminate\View\View
     */
    public function showConnectForm()
    {
        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        $config = [
            'use_sandbox' => config('flowaccount.use_sandbox'),
            'redirect_uri' => config('flowaccount.redirect_uri'),
        ];

        return view('admin.accounting.flowaccount.connect', compact('connection', 'config'));
    }

    /**
     * เชื่อมต่อ FlowAccount (บันทึก credentials และ redirect ไป OAuth)
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function connect(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|string|max:255',
            'client_secret' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        try {
            // บันทึก connection credentials
            $connection = $this->flowAccountService->saveConnection($user, [
                'client_id' => $validated['client_id'],
                'client_secret' => $validated['client_secret'],
                'is_active' => false,
            ]);

            // สร้าง state สำหรับ CSRF protection
            $state = bin2hex(random_bytes(16));
            session(['flowaccount_oauth_state' => $state]);

            // สร้าง OAuth authorization URL
            $authUrl = $this->flowAccountService->getAuthorizationUrl(
                $validated['client_id'],
                config('flowaccount.redirect_uri'),
                config('flowaccount.oauth_scope', 'read write'),
                $state
            );

            return redirect($authUrl);

        } catch (\Exception $e) {
            Log::error('FlowAccount connect error: ' . $e->getMessage());
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * รับ OAuth Callback จาก FlowAccount
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        // ตรวจสอบ error จาก OAuth
        if ($request->has('error')) {
            return redirect()->route('admin.accounting.flowaccount.index')
                ->with('error', 'การเชื่อมต่อถูกปฏิเสธ: ' . $request->get('error_description', $request->get('error')));
        }

        // ตรวจสอบ code
        if (!$request->has('code')) {
            return redirect()->route('admin.accounting.flowaccount.index')
                ->with('error', 'การเชื่อมต่อถูกยกเลิก');
        }

        // ตรวจสอบ state (CSRF protection)
        $expectedState = session('flowaccount_oauth_state');
        if ($expectedState && $request->get('state') !== $expectedState) {
            return redirect()->route('admin.accounting.flowaccount.index')
                ->with('error', 'การเชื่อมต่อไม่ถูกต้อง (state mismatch)');
        }
        session()->forget('flowaccount_oauth_state');

        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        if (!$connection) {
            return redirect()->route('admin.accounting.flowaccount.index')
                ->with('error', 'ไม่พบข้อมูลการเชื่อมต่อ กรุณาเริ่มใหม่อีกครั้ง');
        }

        try {
            // แลก code เป็น access token
            $tokenData = $this->flowAccountService->getAccessToken(
                $connection->client_id,
                $connection->client_secret,
                $request->code
            );

            if (!$tokenData) {
                throw new \Exception('ไม่สามารถรับ Access Token ได้');
            }

            // บันทึก tokens
            $connection->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
                'is_active' => true,
            ]);

            // ทดสอบการเชื่อมต่อ
            $testResult = $this->flowAccountService->testConnection($connection);

            if ($testResult['success']) {
                // ดึงและบันทึกข้อมูลบริษัท
                if (!empty($testResult['data'])) {
                    $connection->update([
                        'flowaccount_company_id' => $testResult['data']['id'] ?? null,
                    ]);
                }

                return redirect()->route('admin.accounting.flowaccount.index')
                    ->with('success', 'เชื่อมต่อ FlowAccount สำเร็จ!');
            } else {
                throw new \Exception($testResult['message'] ?? 'ไม่สามารถทดสอบการเชื่อมต่อได้');
            }

        } catch (\Exception $e) {
            Log::error('FlowAccount callback error: ' . $e->getMessage());
            return redirect()->route('admin.accounting.flowaccount.index')
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ยกเลิกการเชื่อมต่อ FlowAccount
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnect()
    {
        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        if ($connection) {
            $connection->update([
                'access_token' => null,
                'refresh_token' => null,
                'token_expires_at' => null,
                'is_active' => false,
            ]);

            Log::info('FlowAccount disconnected for user: ' . $user->id);
        }

        return back()->with('success', 'ยกเลิกการเชื่อมต่อ FlowAccount แล้ว');
    }

    /**
     * ทดสอบการเชื่อมต่อ (AJAX)
     *
     * @return JsonResponse
     */
    public function testConnection(): JsonResponse
    {
        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        if (!$connection || !$connection->isConnected()) {
            return response()->json([
                'success' => false,
                'message' => 'ยังไม่ได้เชื่อมต่อ FlowAccount',
            ], 400);
        }

        $result = $this->flowAccountService->testConnection($connection);

        return response()->json($result);
    }

    /**
     * บันทึกการตั้งค่า
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'auto_sync' => 'boolean',
            'sync_contacts' => 'boolean',
            'sync_products' => 'boolean',
            'sync_invoices' => 'boolean',
            'sync_expenses' => 'boolean',
            'sync_quotations' => 'boolean',
            'sync_receipts' => 'boolean',
        ]);

        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        if (!$connection) {
            return back()->with('error', 'กรุณาเชื่อมต่อ FlowAccount ก่อน');
        }

        try {
            $connection->update([
                'auto_sync' => $validated['auto_sync'] ?? false,
                'sync_settings' => [
                    'sync_contacts' => $validated['sync_contacts'] ?? true,
                    'sync_products' => $validated['sync_products'] ?? true,
                    'sync_invoices' => $validated['sync_invoices'] ?? true,
                    'sync_expenses' => $validated['sync_expenses'] ?? true,
                    'sync_quotations' => $validated['sync_quotations'] ?? true,
                    'sync_receipts' => $validated['sync_receipts'] ?? true,
                ],
            ]);

            return back()->with('success', 'บันทึกการตั้งค่าสำเร็จ');

        } catch (\Exception $e) {
            Log::error('FlowAccount save settings error: ' . $e->getMessage());
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ซิงค์ข้อมูลทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function sync(Request $request)
    {
        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        if (!$connection || !$connection->isConnected()) {
            $message = 'กรุณาเชื่อมต่อ FlowAccount ก่อน';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 400)
                : back()->with('error', $message);
        }

        try {
            $results = [
                'contacts' => ['created' => 0, 'updated' => 0, 'failed' => 0],
                'products' => ['created' => 0, 'updated' => 0, 'failed' => 0],
                'invoices' => ['synced' => 0, 'failed' => 0],
                'expenses' => ['synced' => 0, 'failed' => 0],
            ];

            $syncSettings = $connection->sync_settings ?? [];

            // ซิงค์ผู้ติดต่อ
            if ($request->get('sync_contacts', $syncSettings['sync_contacts'] ?? true)) {
                $results['contacts'] = $this->flowAccountService->syncContactsFromFlowAccount(
                    $connection,
                    $user->id
                );
            }

            // ซิงค์สินค้า
            if ($request->get('sync_products', $syncSettings['sync_products'] ?? true)) {
                $results['products'] = $this->flowAccountService->syncProductsFromFlowAccount(
                    $connection,
                    $user->id
                );
            }

            // ซิงค์ใบแจ้งหนี้ไปยัง FlowAccount
            if ($request->get('sync_invoices', $syncSettings['sync_invoices'] ?? true)) {
                $invoices = AccountingInvoice::where('user_id', $user->id)
                    ->whereNull('flowaccount_id')
                    ->where('status', '!=', 'draft')
                    ->get();

                foreach ($invoices as $invoice) {
                    $result = $this->flowAccountService->syncInvoice($invoice, $connection);
                    if ($result) {
                        $results['invoices']['synced']++;
                    } else {
                        $results['invoices']['failed']++;
                    }
                }
            }

            // ซิงค์ค่าใช้จ่ายไปยัง FlowAccount
            if ($request->get('sync_expenses', $syncSettings['sync_expenses'] ?? true)) {
                $expenses = AccountingExpense::where('user_id', $user->id)
                    ->whereNull('flowaccount_id')
                    ->where('status', '!=', 'draft')
                    ->get();

                foreach ($expenses as $expense) {
                    $result = $this->flowAccountService->syncExpense($expense, $connection);
                    if ($result) {
                        $results['expenses']['synced']++;
                    } else {
                        $results['expenses']['failed']++;
                    }
                }
            }

            // อัปเดตเวลาซิงค์
            $connection->update(['last_sync_at' => now()]);

            // สร้างข้อความสรุป
            $summary = sprintf(
                "ซิงค์สำเร็จ: ผู้ติดต่อ %d/%d, สินค้า %d/%d, ใบแจ้งหนี้ %d, ค่าใช้จ่าย %d",
                $results['contacts']['created'] + $results['contacts']['updated'],
                $results['contacts']['created'] + $results['contacts']['updated'] + $results['contacts']['failed'],
                $results['products']['created'] + $results['products']['updated'],
                $results['products']['created'] + $results['products']['updated'] + $results['products']['failed'],
                $results['invoices']['synced'],
                $results['expenses']['synced']
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $summary,
                    'results' => $results,
                ]);
            }

            return back()->with('success', $summary);

        } catch (\Exception $e) {
            Log::error('FlowAccount sync error: ' . $e->getMessage());
            $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->with('error', $message);
        }
    }

    /**
     * ซิงค์ข้อมูลตามประเภท
     *
     * @param Request $request
     * @param string $type ประเภทข้อมูล (contacts, products, invoices, expenses)
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function syncType(Request $request, string $type)
    {
        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        if (!$connection || !$connection->isConnected()) {
            $message = 'กรุณาเชื่อมต่อ FlowAccount ก่อน';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 400)
                : back()->with('error', $message);
        }

        try {
            $result = [];
            $message = '';

            switch ($type) {
                case 'contacts':
                    $result = $this->flowAccountService->syncContactsFromFlowAccount($connection, $user->id);
                    $message = sprintf(
                        'ซิงค์ผู้ติดต่อสำเร็จ: สร้าง %d, อัปเดต %d, ล้มเหลว %d',
                        $result['created'],
                        $result['updated'],
                        $result['failed']
                    );
                    break;

                case 'products':
                    $result = $this->flowAccountService->syncProductsFromFlowAccount($connection, $user->id);
                    $message = sprintf(
                        'ซิงค์สินค้าสำเร็จ: สร้าง %d, อัปเดต %d, ล้มเหลว %d',
                        $result['created'],
                        $result['updated'],
                        $result['failed']
                    );
                    break;

                case 'invoices':
                    $invoices = AccountingInvoice::where('user_id', $user->id)
                        ->whereNull('flowaccount_id')
                        ->where('status', '!=', 'draft')
                        ->get();

                    $synced = 0;
                    $failed = 0;

                    foreach ($invoices as $invoice) {
                        $syncResult = $this->flowAccountService->syncInvoice($invoice, $connection);
                        if ($syncResult) {
                            $synced++;
                        } else {
                            $failed++;
                        }
                    }

                    $result = ['synced' => $synced, 'failed' => $failed];
                    $message = sprintf('ซิงค์ใบแจ้งหนี้สำเร็จ: %d รายการ, ล้มเหลว %d รายการ', $synced, $failed);
                    break;

                case 'expenses':
                    $expenses = AccountingExpense::where('user_id', $user->id)
                        ->whereNull('flowaccount_id')
                        ->where('status', '!=', 'draft')
                        ->get();

                    $synced = 0;
                    $failed = 0;

                    foreach ($expenses as $expense) {
                        $syncResult = $this->flowAccountService->syncExpense($expense, $connection);
                        if ($syncResult) {
                            $synced++;
                        } else {
                            $failed++;
                        }
                    }

                    $result = ['synced' => $synced, 'failed' => $failed];
                    $message = sprintf('ซิงค์ค่าใช้จ่ายสำเร็จ: %d รายการ, ล้มเหลว %d รายการ', $synced, $failed);
                    break;

                default:
                    $message = 'ประเภทข้อมูลไม่ถูกต้อง';
                    return $request->wantsJson()
                        ? response()->json(['success' => false, 'message' => $message], 400)
                        : back()->with('error', $message);
            }

            // อัปเดตเวลาซิงค์
            $connection->update(['last_sync_at' => now()]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'result' => $result,
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error("FlowAccount sync {$type} error: " . $e->getMessage());
            $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->with('error', $message);
        }
    }

    /**
     * ดึงประวัติการซิงค์
     *
     * @param Request $request
     * @return \Illuminate\View\View|JsonResponse
     */
    public function syncLogs(Request $request)
    {
        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        if (!$connection) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'ไม่พบการเชื่อมต่อ'], 404);
            }
            return redirect()->route('admin.accounting.flowaccount.index')
                ->with('error', 'กรุณาเชื่อมต่อ FlowAccount ก่อน');
        }

        $query = AccountingFlowaccountSyncLog::where('connection_id', $connection->id);

        // กรองตาม type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // กรองตาม status
        if ($request->filled('status')) {
            $query->where('success', $request->status === 'success');
        }

        // กรองตามวันที่
        if ($request->filled('start_date')) {
            $query->whereDate('synced_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('synced_at', '<=', $request->end_date);
        }

        $logs = $query->latest('synced_at')->paginate(50);

        // สถิติ
        $stats = [
            'total' => AccountingFlowaccountSyncLog::where('connection_id', $connection->id)->count(),
            'success' => AccountingFlowaccountSyncLog::where('connection_id', $connection->id)->where('success', true)->count(),
            'failed' => AccountingFlowaccountSyncLog::where('connection_id', $connection->id)->where('success', false)->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $logs,
                'stats' => $stats,
            ]);
        }

        return view('admin.accounting.flowaccount.logs', compact('logs', 'stats', 'connection'));
    }

    /**
     * ดึงสถิติการซิงค์ (AJAX)
     *
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        if (!$connection || !$connection->isConnected()) {
            return response()->json([
                'success' => false,
                'message' => 'ยังไม่ได้เชื่อมต่อ FlowAccount',
            ], 400);
        }

        $stats = $this->flowAccountService->getSyncStats($connection);
        $status = $this->flowAccountService->getConnectionStatus($connection);

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'status' => $status,
        ]);
    }

    /**
     * ดึงข้อมูลจาก FlowAccount API โดยตรง (สำหรับทดสอบ)
     *
     * @param Request $request
     * @param string $endpoint
     * @return JsonResponse
     */
    public function fetchFromFlowAccount(Request $request, string $endpoint): JsonResponse
    {
        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        if (!$connection || !$connection->isConnected()) {
            return response()->json([
                'success' => false,
                'message' => 'ยังไม่ได้เชื่อมต่อ FlowAccount',
            ], 400);
        }

        try {
            $data = null;
            $params = $request->only(['CurrentPage', 'PageSize', 'Filter', 'SearchString', 'SortBy']);

            switch ($endpoint) {
                case 'contacts':
                    $data = $this->flowAccountService->getContacts($connection, $params);
                    break;
                case 'products':
                    $data = $this->flowAccountService->getProducts($connection, $params);
                    break;
                case 'tax-invoices':
                    $data = $this->flowAccountService->getTaxInvoices($connection, $params);
                    break;
                case 'cash-invoices':
                    $data = $this->flowAccountService->getCashInvoices($connection, $params);
                    break;
                case 'receivable-invoices':
                    $data = $this->flowAccountService->getReceivableInvoices($connection, $params);
                    break;
                case 'quotations':
                    $data = $this->flowAccountService->getQuotations($connection, $params);
                    break;
                case 'billing-notes':
                    $data = $this->flowAccountService->getBillingNotes($connection, $params);
                    break;
                case 'receipts':
                    $data = $this->flowAccountService->getReceipts($connection, $params);
                    break;
                case 'expenses':
                    $data = $this->flowAccountService->getExpenses($connection, $params);
                    break;
                case 'company':
                    $data = $this->flowAccountService->getCompanyInfo($connection);
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Endpoint ไม่ถูกต้อง',
                    ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบประวัติการซิงค์เก่า
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function clearLogs(Request $request)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        if (!$connection) {
            $message = 'ไม่พบการเชื่อมต่อ';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 404)
                : back()->with('error', $message);
        }

        try {
            $cutoffDate = now()->subDays($validated['days']);

            $deleted = AccountingFlowaccountSyncLog::where('connection_id', $connection->id)
                ->where('synced_at', '<', $cutoffDate)
                ->delete();

            $message = "ลบประวัติการซิงค์สำเร็จ: {$deleted} รายการ";

            return $request->wantsJson()
                ? response()->json(['success' => true, 'message' => $message, 'deleted' => $deleted])
                : back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('FlowAccount clear logs error: ' . $e->getMessage());
            $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->with('error', $message);
        }
    }
}
