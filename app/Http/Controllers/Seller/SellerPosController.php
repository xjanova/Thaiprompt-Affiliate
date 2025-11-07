<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use App\Models\PosTransaction;
use App\Models\PosSession;
use App\Models\PosCategory;
use App\Models\PosSetting;
use App\Models\PosAdvertisement;
use App\Models\VendorStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellerPosController extends Controller
{
    protected function getStore()
    {
        return VendorStore::where('user_id', auth()->id())->firstOrFail();
    }

    public function index()
    {
        $store = $this->getStore();

        $stats = [
            'total_devices' => PosDevice::where('store_id', $store->id)->count(),
            'active_devices' => PosDevice::where('store_id', $store->id)->active()->count(),
            'online_devices' => PosDevice::where('store_id', $store->id)->online()->count(),
            'active_sessions' => PosSession::whereHas('posDevice', fn($q) => $q->where('store_id', $store->id))->open()->count(),
            'today_transactions' => PosTransaction::where('store_id', $store->id)->whereDate('transaction_date', today())->count(),
            'today_sales' => PosTransaction::where('store_id', $store->id)->whereDate('transaction_date', today())->sum('total_amount'),
            'month_transactions' => PosTransaction::where('store_id', $store->id)->whereMonth('transaction_date', now()->month)->count(),
            'month_sales' => PosTransaction::where('store_id', $store->id)->whereMonth('transaction_date', now()->month)->sum('total_amount'),
        ];

        $recentTransactions = PosTransaction::where('store_id', $store->id)
            ->with(['posDevice', 'user'])
            ->latest('transaction_date')
            ->limit(10)
            ->get();

        $activeSessions = PosSession::whereHas('posDevice', fn($q) => $q->where('store_id', $store->id))
            ->with(['posDevice', 'user'])
            ->open()
            ->get();

        return view('seller.pos.index', compact('stats', 'recentTransactions', 'activeSessions'));
    }

    // Devices Management
    public function devices()
    {
        $store = $this->getStore();
        $devices = PosDevice::where('store_id', $store->id)
            ->with('sessions')
            ->latest()
            ->paginate(20);

        return view('seller.pos.devices.index', compact('devices'));
    }

    public function deviceShow(PosDevice $device)
    {
        $this->authorize('view', $device);

        $device->load(['sessions.user', 'transactions']);

        $stats = [
            'total_transactions' => $device->transactions()->count(),
            'today_transactions' => $device->transactions()->whereDate('transaction_date', today())->count(),
            'total_sales' => $device->transactions()->sum('total_amount'),
            'today_sales' => $device->transactions()->whereDate('transaction_date', today())->sum('total_amount'),
            'active_session' => $device->sessions()->open()->first(),
        ];

        return view('seller.pos.devices.show', compact('device', 'stats'));
    }

    // Transactions
    public function transactions(Request $request)
    {
        $store = $this->getStore();

        $query = PosTransaction::where('store_id', $store->id)
            ->with(['posDevice', 'user', 'items'])
            ->latest('transaction_date');

        if ($request->has('device_id')) {
            $query->where('pos_device_id', $request->device_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->paginate(20);

        $devices = PosDevice::where('store_id', $store->id)->get();

        return view('seller.pos.transactions.index', compact('transactions', 'devices'));
    }

    public function transactionShow(PosTransaction $transaction)
    {
        $this->authorize('view', $transaction);

        $transaction->load(['posDevice', 'posSession', 'user', 'items.product']);

        return view('seller.pos.transactions.show', compact('transaction'));
    }

    // Sessions
    public function sessions(Request $request)
    {
        $store = $this->getStore();

        $query = PosSession::whereHas('posDevice', fn($q) => $q->where('store_id', $store->id))
            ->with(['posDevice', 'user'])
            ->latest('opened_at');

        if ($request->has('device_id')) {
            $query->where('pos_device_id', $request->device_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->paginate(20);
        $devices = PosDevice::where('store_id', $store->id)->get();

        return view('seller.pos.sessions.index', compact('sessions', 'devices'));
    }

    public function sessionShow(PosSession $session)
    {
        $this->authorize('view', $session);

        $session->load(['posDevice', 'user', 'transactions']);

        return view('seller.pos.sessions.show', compact('session'));
    }

    // Settings
    public function settings()
    {
        $store = $this->getStore();
        $settings = PosSetting::firstOrCreate(
            ['store_id' => $store->id],
            PosSetting::make()->getDefaultSettings()
        );

        return view('seller.pos.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $store = $this->getStore();

        $validated = $request->validate([
            'store_display_name' => 'nullable|string|max:255',
            'receipt_header' => 'nullable|string|max:255',
            'receipt_footer' => 'nullable|string|max:255',
            'tax_enabled' => 'boolean',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_inclusive' => 'boolean',
            'tax_id_number' => 'nullable|string|max:50',
            'service_charge_enabled' => 'boolean',
            'service_charge_percentage' => 'nullable|numeric|min:0|max:100',
            'allow_discounts' => 'boolean',
            'max_discount_percentage' => 'nullable|numeric|min:0|max:100',
            'require_manager_approval' => 'boolean',
            'manager_approval_threshold' => 'nullable|numeric|min:0|max:100',
            'enabled_payment_methods' => 'nullable|array',
            'auto_print_receipt' => 'boolean',
            'receipt_size' => 'nullable|in:58mm,80mm',
            'receipt_copies' => 'nullable|integer|min:1|max:5',
            'dual_screen_enabled' => 'boolean',
            'show_product_images' => 'boolean',
            'show_stock_levels' => 'boolean',
            'theme_color' => 'nullable|string|max:7',
            'offline_mode_enabled' => 'boolean',
            'real_time_stock_sync' => 'boolean',
            'low_stock_warning' => 'boolean',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'prevent_negative_stock' => 'boolean',
            'require_cash_management' => 'boolean',
        ]);

        $settings = PosSetting::updateOrCreate(
            ['store_id' => $store->id],
            $validated
        );

        return back()->with('success', 'POS settings updated successfully!');
    }

    // Categories
    public function categories()
    {
        $store = $this->getStore();
        $categories = PosCategory::where('store_id', $store->id)
            ->with('products')
            ->rootCategories()
            ->ordered()
            ->paginate(20);

        return view('seller.pos.categories.index', compact('categories'));
    }

    public function categoryStore(Request $request)
    {
        $store = $this->getStore();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'show_in_pos' => 'boolean',
            'parent_id' => 'nullable|exists:pos_categories,id',
        ]);

        $validated['store_id'] = $store->id;

        $category = PosCategory::create($validated);

        return back()->with('success', 'Category created successfully!');
    }

    public function categoryUpdate(Request $request, PosCategory $category)
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'show_in_pos' => 'boolean',
            'parent_id' => 'nullable|exists:pos_categories,id',
        ]);

        $category->update($validated);

        return back()->with('success', 'Category updated successfully!');
    }

    public function categoryDestroy(PosCategory $category)
    {
        $this->authorize('delete', $category);

        $category->delete();

        return back()->with('success', 'Category deleted successfully!');
    }

    // Advertisements
    public function advertisements()
    {
        $store = $this->getStore();
        $advertisements = PosAdvertisement::where('store_id', $store->id)
            ->latest()
            ->paginate(20);

        return view('seller.pos.advertisements.index', compact('advertisements'));
    }

    public function advertisementStore(Request $request)
    {
        $store = $this->getStore();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:image,video,html,promotion',
            'image' => 'nullable|image|max:5120',
            'duration_seconds' => 'required|integer|min:1|max:300',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['store_id'] = $store->id;

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('pos/advertisements', 'public');
        }

        $advertisement = PosAdvertisement::create($validated);

        return back()->with('success', 'Advertisement created successfully!');
    }

    // Analytics
    public function analytics(Request $request)
    {
        $store = $this->getStore();
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $analytics = [
            'sales_by_device' => $this->getSalesByDevice($store->id, $dateFrom, $dateTo),
            'sales_by_payment' => $this->getSalesByPayment($store->id, $dateFrom, $dateTo),
            'top_products' => $this->getTopProducts($store->id, $dateFrom, $dateTo),
            'hourly_sales' => $this->getHourlySales($store->id, $dateFrom, $dateTo),
        ];

        return view('seller.pos.analytics', compact('analytics', 'dateFrom', 'dateTo'));
    }

    protected function getSalesByDevice($storeId, $dateFrom, $dateTo)
    {
        return PosDevice::where('store_id', $storeId)
            ->select('pos_devices.*')
            ->selectRaw('COUNT(pos_transactions.id) as transaction_count')
            ->selectRaw('SUM(pos_transactions.total_amount) as total_sales')
            ->leftJoin('pos_transactions', 'pos_devices.id', '=', 'pos_transactions.pos_device_id')
            ->whereBetween('pos_transactions.transaction_date', [$dateFrom, $dateTo])
            ->groupBy('pos_devices.id')
            ->get();
    }

    protected function getSalesByPayment($storeId, $dateFrom, $dateTo)
    {
        return PosTransaction::where('store_id', $storeId)
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->select('payment_method')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(total_amount) as total')
            ->groupBy('payment_method')
            ->get();
    }

    protected function getTopProducts($storeId, $dateFrom, $dateTo, $limit = 20)
    {
        return DB::table('pos_transaction_items')
            ->join('pos_transactions', 'pos_transaction_items.pos_transaction_id', '=', 'pos_transactions.id')
            ->where('pos_transactions.store_id', $storeId)
            ->whereBetween('pos_transactions.transaction_date', [$dateFrom, $dateTo])
            ->select(
                'pos_transaction_items.product_name',
                DB::raw('SUM(pos_transaction_items.quantity) as total_quantity'),
                DB::raw('SUM(pos_transaction_items.total) as total_sales')
            )
            ->groupBy('pos_transaction_items.product_id', 'pos_transaction_items.product_name')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();
    }

    protected function getHourlySales($storeId, $dateFrom, $dateTo)
    {
        return PosTransaction::where('store_id', $storeId)
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->selectRaw('HOUR(transaction_date) as hour')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(total_amount) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }
}
