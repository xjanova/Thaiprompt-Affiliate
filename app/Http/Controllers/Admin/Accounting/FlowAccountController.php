<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingFlowaccountConnection;
use App\Models\AccountingInvoice;
use App\Models\AccountingExpense;
use App\Models\AccountingContact;
use App\Models\AccountingProduct;
use App\Services\FlowAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FlowAccountController extends Controller
{
    protected $flowAccountService;

    public function __construct(FlowAccountService $flowAccountService)
    {
        $this->middleware(['auth', 'check.permission:accounting.manage_flowaccount']);
        $this->flowAccountService = $flowAccountService;
    }

    /**
     * Display FlowAccount integration page
     */
    public function index()
    {
        $user = Auth::user();

        $connection = $this->flowAccountService->getConnection($user);

        $stats = [];

        if ($connection && $connection->isConnected()) {
            $stats = [
                'synced_invoices' => AccountingInvoice::where('user_id', $user->id)
                    ->whereNotNull('flowaccount_id')
                    ->count(),
                'synced_expenses' => AccountingExpense::where('user_id', $user->id)
                    ->whereNotNull('flowaccount_id')
                    ->count(),
                'synced_contacts' => AccountingContact::where('user_id', $user->id)
                    ->whereNotNull('flowaccount_id')
                    ->count(),
                'synced_products' => AccountingProduct::where('user_id', $user->id)
                    ->whereNotNull('flowaccount_id')
                    ->count(),
                'last_sync' => $connection->last_sync_at,
            ];
        }

        return view('admin.accounting.flowaccount.index', compact('connection', 'stats'));
    }

    /**
     * Connect to FlowAccount
     */
    public function connect(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        $user = Auth::user();

        try {
            // Save connection credentials
            $connection = $this->flowAccountService->saveConnection($user, [
                'client_id' => $validated['client_id'],
                'client_secret' => $validated['client_secret'],
                'is_active' => false,
            ]);

            // Redirect to FlowAccount OAuth
            $redirectUri = config('services.flowaccount.redirect_uri');
            $authUrl = $this->flowAccountService->baseUrl . '/oauth/authorize?' . http_build_query([
                'client_id' => $validated['client_id'],
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'read write',
            ]);

            return redirect($authUrl);

        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Handle OAuth callback
     */
    public function callback(Request $request)
    {
        if (!$request->has('code')) {
            return redirect()->route('admin.accounting.flowaccount.index')
                ->with('error', 'การเชื่อมต่อถูกยกเลิก');
        }

        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        if (!$connection) {
            return redirect()->route('admin.accounting.flowaccount.index')
                ->with('error', 'ไม่พบข้อมูลการเชื่อมต่อ');
        }

        try {
            // Exchange code for access token
            $tokenData = $this->flowAccountService->getAccessToken(
                $connection->client_id,
                $connection->client_secret,
                $request->code
            );

            if (!$tokenData) {
                throw new \Exception('ไม่สามารถรับ Access Token ได้');
            }

            // Update connection with tokens
            $connection->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                'is_active' => true,
            ]);

            // Test connection
            if ($this->flowAccountService->testConnection($connection)) {
                return redirect()->route('admin.accounting.flowaccount.index')
                    ->with('success', 'เชื่อมต่อ FlowAccount สำเร็จ');
            } else {
                throw new \Exception('ไม่สามารถทดสอบการเชื่อมต่อได้');
            }

        } catch (\Exception $e) {
            return redirect()->route('admin.accounting.flowaccount.index')
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect from FlowAccount
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
        }

        return back()->with('success', 'ยกเลิกการเชื่อมต่อ FlowAccount แล้ว');
    }

    /**
     * Sync all data with FlowAccount
     */
    public function sync(Request $request)
    {
        $this->middleware('check.permission:accounting.sync_flowaccount');

        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        if (!$connection || !$connection->isConnected()) {
            return back()->with('error', 'กรุณาเชื่อมต่อ FlowAccount ก่อน');
        }

        try {
            $results = [
                'contacts' => 0,
                'products' => 0,
                'invoices' => 0,
                'expenses' => 0,
            ];

            // Sync contacts
            if ($request->get('sync_contacts', true)) {
                $contacts = $this->flowAccountService->getContacts($connection);
                if ($contacts) {
                    // TODO: Process and save contacts
                    $results['contacts'] = count($contacts);
                }
            }

            // Sync products
            if ($request->get('sync_products', true)) {
                $products = $this->flowAccountService->getProducts($connection);
                if ($products) {
                    // TODO: Process and save products
                    $results['products'] = count($products);
                }
            }

            // Update last sync time
            $connection->update(['last_sync_at' => now()]);

            return back()->with('success',
                "ซิงค์ข้อมูลสำเร็จ: ผู้ติดต่อ {$results['contacts']} รายการ, สินค้า {$results['products']} รายการ"
            );

        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Sync specific type of data
     */
    public function syncType(Request $request, string $type)
    {
        $this->middleware('check.permission:accounting.sync_flowaccount');

        $user = Auth::user();
        $connection = $this->flowAccountService->getConnection($user);

        if (!$connection || !$connection->isConnected()) {
            return back()->with('error', 'กรุณาเชื่อมต่อ FlowAccount ก่อน');
        }

        try {
            $count = 0;

            switch ($type) {
                case 'invoices':
                    // Sync unpaid invoices
                    $invoices = AccountingInvoice::where('user_id', $user->id)
                        ->whereNull('flowaccount_id')
                        ->where('status', '!=', 'draft')
                        ->get();

                    foreach ($invoices as $invoice) {
                        $result = $this->flowAccountService->syncInvoice($invoice, $connection);
                        if ($result) {
                            $invoice->update([
                                'flowaccount_id' => $result['id'],
                                'synced_at' => now(),
                            ]);
                            $count++;
                        }
                    }
                    break;

                case 'expenses':
                    // Sync unpaid expenses
                    $expenses = AccountingExpense::where('user_id', $user->id)
                        ->whereNull('flowaccount_id')
                        ->where('status', '!=', 'draft')
                        ->get();

                    foreach ($expenses as $expense) {
                        $result = $this->flowAccountService->syncExpense($expense, $connection);
                        if ($result) {
                            $expense->update([
                                'flowaccount_id' => $result['id'],
                                'synced_at' => now(),
                            ]);
                            $count++;
                        }
                    }
                    break;

                case 'contacts':
                    $contacts = $this->flowAccountService->getContacts($connection);
                    if ($contacts) {
                        // TODO: Process contacts
                        $count = count($contacts);
                    }
                    break;

                case 'products':
                    $products = $this->flowAccountService->getProducts($connection);
                    if ($products) {
                        // TODO: Process products
                        $count = count($products);
                    }
                    break;

                default:
                    return back()->with('error', 'ประเภทข้อมูลไม่ถูกต้อง');
            }

            return back()->with('success', "ซิงค์ {$type} สำเร็จ {$count} รายการ");

        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
