<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\PosAdvertisement;
use App\Models\PosApiKey;
use App\Models\PosCategory;
use App\Models\PosDevice;
use App\Models\PosSession;
use App\Models\PosSetting;
use App\Models\PosTerminal;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\Product;
use App\Models\VendorStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SellerPosController extends Controller
{
    protected function getStore()
    {
        return VendorStore::where('user_id', auth()->id())->firstOrFail();
    }

    /**
     * ตรวจว่าข้อมูล POS เป็นของร้านผู้ใช้ปัจจุบัน
     *
     * (2026-09-25) เดิมใช้ $this->authorize() แต่ไม่มี Policy ของโมเดล POS เลย
     * → Gate ปฏิเสธทุกครั้ง หน้าอุปกรณ์/รายการขาย/เซสชัน/ใบเสร็จ/แก้หมวดหมู่ ของผู้ขายเจอ 403 หมด
     * จึงเปลี่ยนมาเทียบ store_id ตรง ๆ (ซูเปอร์แอดมินยังผ่านได้เหมือนเดิมผ่าน Gate::before)
     */
    protected function ensureStoreOwns($storeId): void
    {
        if (auth()->user()?->is_super_admin) {
            return;
        }

        abort_if((int) $storeId !== (int) $this->getStore()->id, 403, 'ไม่มีสิทธิ์เข้าถึงข้อมูลนี้');
    }

    public function index()
    {
        $store = $this->getStore();

        $stats = [
            'total_devices' => PosDevice::where('store_id', $store->id)->count(),
            'active_devices' => PosDevice::where('store_id', $store->id)->active()->count(),
            'online_devices' => PosDevice::where('store_id', $store->id)->online()->count(),
            'active_sessions' => PosSession::whereHas('posDevice', fn ($q) => $q->where('store_id', $store->id))->open()->count(),
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

        $activeSessions = PosSession::whereHas('posDevice', fn ($q) => $q->where('store_id', $store->id))
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
        $this->ensureStoreOwns($device->store_id);

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

        // ใช้ filled() — ตัวเลือก "ทั้งหมด" ส่งค่าว่างมา (has() จะกรองเป็น IS NULL จนไม่เจออะไรเลย)
        if ($request->filled('device_id')) {
            $query->where('pos_device_id', $request->device_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        // สรุปยอดตามตัวกรองปัจจุบัน (ทุกหน้า ไม่ใช่เฉพาะหน้าที่แสดง)
        $summaryRows = (clone $query)->setEagerLoads([])->reorder()
            ->select('payment_method')
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total')
            ->groupBy('payment_method')
            ->get();
        $summary = [
            'count' => (int) $summaryRows->sum('cnt'),
            'total' => round((float) $summaryRows->sum('total'), 2),
            'by_method' => $summaryRows->mapWithKeys(fn ($r) => [$r->payment_method => ['count' => (int) $r->cnt, 'total' => round((float) $r->total, 2)]])->all(),
        ];

        $transactions = $query->paginate(20)->withQueryString();

        $devices = PosDevice::where('store_id', $store->id)->get();

        return view('seller.pos.transactions.index', compact('transactions', 'devices', 'summary'));
    }

    public function transactionShow(PosTransaction $transaction)
    {
        $this->ensureStoreOwns($transaction->store_id);

        $transaction->load(['posDevice', 'posSession', 'user', 'items.product']);

        return view('seller.pos.transactions.show', compact('transaction'));
    }

    // Sessions
    public function sessions(Request $request)
    {
        $store = $this->getStore();

        $query = PosSession::whereHas('posDevice', fn ($q) => $q->where('store_id', $store->id))
            ->with(['posDevice', 'user'])
            ->latest('opened_at');

        if ($request->filled('device_id')) {
            $query->where('pos_device_id', $request->device_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->paginate(20)->withQueryString();
        $devices = PosDevice::where('store_id', $store->id)->get();

        return view('seller.pos.sessions.index', compact('sessions', 'devices'));
    }

    public function sessionShow(PosSession $session)
    {
        $this->ensureStoreOwns($session->posDevice?->store_id);

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
            'enabled_payment_methods.*' => 'in:cash,card,qr,bank_transfer,other',
            'auto_print_receipt' => 'boolean',
            'receipt_size' => 'nullable|in:58mm,80mm',
            'receipt_copies' => 'nullable|integer|min:1|max:5',
            'dual_screen_enabled' => 'boolean',
            'show_product_images' => 'boolean',
            'show_stock_levels' => 'boolean',
            'theme_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
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

        return back()->with('success', 'บันทึกการตั้งค่า POS เรียบร้อยแล้ว');
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
            // สีต้องเป็นรหัส hex จริง (ค่านี้ถูกใส่ลง style ของหน้า)
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'show_in_pos' => 'boolean',
            // หมวดแม่ต้องเป็นของร้านนี้เท่านั้น
            'parent_id' => ['nullable', \Illuminate\Validation\Rule::exists('pos_categories', 'id')->where('store_id', $store->id)],
        ]);

        $validated['store_id'] = $store->id;

        $category = PosCategory::create($validated);

        return back()->with('success', 'เพิ่มหมวดหมู่เรียบร้อยแล้ว');
    }

    public function categoryUpdate(Request $request, PosCategory $category)
    {
        $this->ensureStoreOwns($category->store_id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'show_in_pos' => 'boolean',
            'parent_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('pos_categories', 'id')->where('store_id', $category->store_id),
                \Illuminate\Validation\Rule::notIn([$category->id]),
            ],
        ]);

        $category->update($validated);

        return back()->with('success', 'แก้ไขหมวดหมู่เรียบร้อยแล้ว');
    }

    public function categoryDestroy(PosCategory $category)
    {
        $this->ensureStoreOwns($category->store_id);

        $category->delete();

        return back()->with('success', 'ลบหมวดหมู่เรียบร้อยแล้ว');
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

        return back()->with('success', 'เพิ่มโฆษณาเรียบร้อยแล้ว');
    }

    /**
     * แก้ไขโฆษณาจอลูกค้า (GAP-10) — แก้ได้เฉพาะโฆษณาของร้านตัวเอง
     */
    public function advertisementUpdate(Request $request, PosAdvertisement $advertisement)
    {
        $store = $this->getStore();
        abort_if((int) $advertisement->store_id !== (int) $store->id, 403, 'ไม่มีสิทธิ์แก้ไขโฆษณานี้');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:image,video,html,promotion',
            'image' => 'nullable|image|max:5120',
            'duration_seconds' => 'required|integer|min:1|max:300',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $oldPath = $advertisement->image_url;
            $validated['image_url'] = $request->file('image')->store('pos/advertisements', 'public');

            // ลบไฟล์รูปเดิม (เฉพาะไฟล์ในดิสก์ของเรา ไม่ใช่ URL ภายนอก)
            if ($oldPath && ! str_starts_with($oldPath, 'http')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
        }
        unset($validated['image']);

        $advertisement->update($validated);

        return back()->with('success', 'แก้ไขโฆษณาเรียบร้อยแล้ว');
    }

    /**
     * ลบโฆษณาจอลูกค้า (GAP-10) — soft delete เฉพาะโฆษณาของร้านตัวเอง
     */
    public function advertisementDestroy(PosAdvertisement $advertisement)
    {
        $store = $this->getStore();
        abort_if((int) $advertisement->store_id !== (int) $store->id, 403, 'ไม่มีสิทธิ์ลบโฆษณานี้');

        $advertisement->delete();

        return back()->with('success', 'ลบโฆษณาเรียบร้อยแล้ว');
    }

    // Analytics
    public function analytics(Request $request)
    {
        $store = $this->getStore();

        // แปลงช่วงวันที่ให้ครอบทั้งวัน (เดิมส่งสตริง Y-m-d ตรง ๆ → ยอดของ "วันสุดท้าย" หายทั้งวัน)
        try {
            $dateFrom = $request->filled('date_from')
                ? \Carbon\Carbon::parse($request->get('date_from'))->startOfDay()
                : now()->subDays(29)->startOfDay();
            $dateTo = $request->filled('date_to')
                ? \Carbon\Carbon::parse($request->get('date_to'))->endOfDay()
                : now()->endOfDay();
        } catch (\Throwable $e) {
            $dateFrom = now()->subDays(29)->startOfDay();
            $dateTo = now()->endOfDay();
        }
        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

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
        // ระบุชื่อตารางให้ store_id (ทั้ง pos_devices และ pos_transactions มีคอลัมน์นี้ → เดิม SQL ambiguous 500)
        return PosDevice::where('pos_devices.store_id', $storeId)
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

    // POS Terminal (Cashier Interface)
    public function terminal()
    {
        $store = $this->getStore();

        // Get or create default POS device for web
        $device = PosDevice::firstOrCreate(
            [
                'store_id' => $store->id,
                'device_type' => 'web',
                'device_name' => 'Web POS - '.auth()->user()->name,
            ],
            [
                'device_key' => Str::random(32),
                'is_active' => true,
                'is_online' => true,
                'subscription_status' => 'active',
            ]
        );

        // Get or create active session
        $session = PosSession::firstOrCreate(
            [
                'pos_device_id' => $device->id,
                'user_id' => auth()->id(),
                'status' => 'open',
            ],
            [
                'opened_at' => now(),
                'opening_cash' => 0,
            ]
        );

        // Get products for the store
        $products = Product::where('store_id', $store->id)
            ->where('is_active', true)
            ->where('stock_status', '!=', 'out_of_stock')
            ->with('category')
            ->get();

        // Get categories
        $categories = $products->pluck('category')->unique('id')->filter();

        // Get POS settings
        $settings = PosSetting::firstOrCreate(
            ['store_id' => $store->id],
            PosSetting::make()->getDefaultSettings()
        );

        return view('seller.pos.terminal', compact('device', 'session', 'products', 'categories', 'settings', 'store'));
    }

    // Create transaction via POS Terminal
    public function createTransaction(Request $request)
    {
        $store = $this->getStore();

        $validated = $request->validate([
            'device_id' => 'required|exists:pos_devices,id',
            'session_id' => 'required|exists:pos_sessions,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            // คอลัมน์ pos_transaction_items.quantity เป็น int → รับเฉพาะจำนวนเต็ม
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,qr,bank_transfer,other',
            'payment_amount' => 'required|numeric|min:0',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        // 🔒 (2026-09-25) กัน IDOR: อุปกรณ์ / เซสชัน / สินค้า ต้องเป็นของร้านนี้เท่านั้น
        //    (เดิมตรวจแค่ว่า id มีอยู่ในระบบ → ขายสินค้า/ตัดสต็อกของร้านอื่นได้)
        $deviceOk = PosDevice::where('id', $validated['device_id'])->where('store_id', $store->id)->exists();
        $sessionOk = PosSession::where('id', $validated['session_id'])
            ->where('pos_device_id', $validated['device_id'])
            ->exists();
        $productIds = collect($validated['items'])->pluck('product_id')->unique();
        $ownProducts = Product::whereIn('id', $productIds)->where('store_id', $store->id)->count();

        if (! $deviceOk || ! $sessionOk || $ownProducts !== $productIds->count()) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_YOUR_STORE',
                'message' => 'มีอุปกรณ์หรือสินค้าที่ไม่ใช่ของร้านคุณในรายการขาย',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Create transaction
            $transaction = PosTransaction::create([
                'store_id' => $store->id,
                'pos_device_id' => $validated['device_id'],
                'pos_session_id' => $validated['session_id'],
                'user_id' => auth()->id(),
                'transaction_code' => 'POS-'.strtoupper(Str::random(8)),
                'receipt_number' => $this->generateReceiptNumber($store->id),
                'transaction_date' => now(),
                'subtotal' => $validated['subtotal'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'total_amount' => $validated['total_amount'],
                'payment_method' => $validated['payment_method'],
                // คอลัมน์จริงชื่อ amount_paid (payment_amount ไม่อยู่ใน fillable → เดิมถูกทิ้งเงียบ ๆ)
                'amount_paid' => $validated['payment_amount'],
                'change_amount' => max(0, round($validated['payment_amount'] - $validated['total_amount'], 2)),
                // enum ของ payment_status คือ completed|refunded|partial_refund|void ('paid' ทำให้ insert พังบน MySQL strict)
                'payment_status' => 'completed',
                'status' => 'completed',
                'total_items' => count($validated['items']),
                'total_quantity' => (int) collect($validated['items'])->sum('quantity'),
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create transaction items
            foreach ($validated['items'] as $item) {
                // ล็อกแถวสินค้าระหว่างตัดสต็อก (กันขายพร้อมกันหลายเครื่องแล้วสต็อกเพี้ยน)
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->first();
                $lineSubtotal = round($item['quantity'] * $item['unit_price'], 2);
                $lineDiscount = round((float) ($item['discount'] ?? 0), 2);

                PosTransactionItem::create([
                    'pos_transaction_id' => $transaction->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'product_barcode' => $product->barcode,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    // original_price เป็น NOT NULL — เดิมไม่ได้ส่งมา ทำให้บันทึกการขายพังทุกครั้ง
                    'original_price' => $product->price ?? $item['unit_price'],
                    // คอลัมน์จริงคือ discount_amount / tax_amount (เดิมใช้ discount / tax ที่ไม่อยู่ใน fillable)
                    'discount_amount' => $lineDiscount,
                    'subtotal' => $lineSubtotal,
                    'tax_amount' => 0,
                    'total' => round($lineSubtotal - $lineDiscount, 2),
                ]);

                // Update stock
                if ($product->track_inventory) {
                    $product->decrement('stock_quantity', $item['quantity']);

                    // Update stock status — enum มีแค่ in_stock|out_of_stock|on_backorder ('low_stock' ทำให้ insert พัง)
                    if ($product->stock_quantity <= 0) {
                        $product->update(['stock_status' => 'out_of_stock']);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'บันทึกการขายสำเร็จ',
                'transaction' => $transaction->load('items'),
                'receipt_url' => route('seller.pos.receipt', $transaction),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // ไม่ส่งข้อความ exception ดิบกลับไปหน้าเว็บ — เก็บลง log แทน
            \Illuminate\Support\Facades\Log::error('Seller POS createTransaction failed', [
                'store_id' => $store->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'code' => 'POS_TRANSACTION_FAILED',
                'message' => 'บันทึกการขายไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
            ], 500);
        }
    }

    protected function generateReceiptNumber($storeId)
    {
        $lastTransaction = PosTransaction::where('store_id', $storeId)
            ->whereDate('created_at', today())
            ->latest()
            ->first();

        if ($lastTransaction && $lastTransaction->receipt_number) {
            $lastNumber = (int) substr($lastTransaction->receipt_number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        // receipt_number เป็น unique ทั้งตาราง → ต้องใส่รหัสร้านด้วย
        // (เดิมร้านที่สองของวันได้เลขซ้ำกับร้านแรก แล้วบันทึกการขายพัง)
        return 'RCP-'.$storeId.'-'.date('Ymd').'-'.str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    // Receipt view
    public function receipt(PosTransaction $transaction)
    {
        $this->ensureStoreOwns($transaction->store_id);
        $transaction->load(['items.product', 'store', 'user']);

        return view('seller.pos.receipt', compact('transaction'));
    }

    // Search products for POS
    public function searchProducts(Request $request)
    {
        $store = $this->getStore();
        $search = $request->get('q', '');

        $products = Product::where('store_id', $store->id)
            ->where('is_active', true)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get();

        return response()->json($products);
    }

    // =========================================
    // POS Terminal Registration (สำหรับ Desktop App)
    // =========================================

    /**
     * หน้าจัดการ POS Terminals
     * แสดง API Keys และ Terminals ที่ลงทะเบียนแล้ว
     */
    public function terminals()
    {
        $store = $this->getStore();

        // ดึง API Keys ทั้งหมดของร้าน
        $apiKeys = PosApiKey::where('shop_id', $store->id)
            ->with('terminals')
            ->latest()
            ->get();

        // ดึง Terminals ที่ลงทะเบียนแล้ว
        $terminals = PosTerminal::where('shop_id', $store->id)
            ->with('apiKey')
            ->latest()
            ->get();

        // สถิติ — ตาราง pos_terminals บน prod มีคอลัมน์ is_active (ไม่มี status/is_online)
        //    จึงคำนวณ "ใช้งาน" และ "ออนไลน์" ผ่าน helper ที่รองรับทั้งสองแบบ
        $stats = [
            'total_api_keys' => $apiKeys->count(),
            'active_api_keys' => $apiKeys->where('is_active', true)->where('is_blocked', false)->count(),
            'total_terminals' => $terminals->count(),
            'active_terminals' => $terminals->filter(fn ($t) => self::terminalIsActive($t))->count(),
            'online_terminals' => $terminals->filter(fn ($t) => self::terminalIsOnline($t))->count(),
        ];

        return view('seller.pos.terminals.index', compact('apiKeys', 'terminals', 'stats', 'store'));
    }

    /**
     * สร้าง API Key ใหม่สำหรับ POS Terminal
     */
    public function createApiKey(Request $request)
    {
        $store = $this->getStore();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        // สร้าง API Key
        $apiKey = PosApiKey::create([
            'shop_id' => $store->id,
            // VendorStore ไม่มี relation apiKeys() → นับจากตารางตรง ๆ (เดิม 500 เมื่อไม่กรอกชื่อ)
            'name' => $validated['name'] ?? 'POS Terminal '.(PosApiKey::where('shop_id', $store->id)->count() + 1),
            'description' => $validated['description'] ?? null,
            'is_active' => true,
            'is_blocked' => false,
        ]);

        return back()->with('success', 'สร้าง API Key สำเร็จ! คัดลอก Key นี้ไปใส่ในโปรแกรม POS')
            ->with('new_api_key', $apiKey->key);
    }

    /**
     * แสดงรายละเอียด API Key
     */
    public function showApiKey(PosApiKey $apiKey)
    {
        $store = $this->getStore();

        // ตรวจสอบว่า API Key เป็นของร้านนี้
        if ($apiKey->shop_id !== $store->id) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึง API Key นี้');
        }

        $apiKey->load('terminals');

        return view('seller.pos.terminals.api-key-detail', compact('apiKey', 'store'));
    }

    /**
     * บล็อก/ปลดบล็อก API Key
     */
    public function toggleApiKeyBlock(Request $request, PosApiKey $apiKey)
    {
        $store = $this->getStore();

        if ($apiKey->shop_id !== $store->id) {
            abort(403, 'ไม่มีสิทธิ์');
        }

        if ($apiKey->is_blocked) {
            // ปลดบล็อก
            $apiKey->unblock();
            $message = 'ปลดบล็อก API Key สำเร็จ';
        } else {
            // บล็อก
            $reason = $request->input('reason', 'บล็อกโดยเจ้าของร้าน');
            $apiKey->block($reason, auth()->id());
            $message = 'บล็อก API Key สำเร็จ';
        }

        return back()->with('success', $message);
    }

    /**
     * ลบ API Key
     */
    public function deleteApiKey(PosApiKey $apiKey)
    {
        $store = $this->getStore();

        if ($apiKey->shop_id !== $store->id) {
            abort(403, 'ไม่มีสิทธิ์');
        }

        // ลบ terminals ที่ใช้ API Key นี้ด้วย
        $apiKey->terminals()->delete();
        $apiKey->delete();

        return back()->with('success', 'ลบ API Key สำเร็จ');
    }

    /**
     * แสดงรายละเอียด Terminal
     */
    public function showTerminal(PosTerminal $terminal)
    {
        $store = $this->getStore();

        if ($terminal->shop_id !== $store->id) {
            abort(403, 'ไม่มีสิทธิ์');
        }

        $terminal->load('apiKey');

        return view('seller.pos.terminals.terminal-detail', compact('terminal', 'store'));
    }

    /**
     * บล็อก/ปลดบล็อก Terminal
     */
    public function toggleTerminalStatus(PosTerminal $terminal)
    {
        $store = $this->getStore();

        if ($terminal->shop_id !== $store->id) {
            abort(403, 'ไม่มีสิทธิ์');
        }

        $turnOn = ! self::terminalIsActive($terminal);

        // รองรับทั้งสองโครงสร้างตาราง: status (pending|active|suspended|blocked) หรือ is_active
        // (เดิมเขียน status='inactive' ซึ่งไม่อยู่ใน enum และบน prod ไม่มีคอลัมน์ status → 500)
        if (\Illuminate\Support\Facades\Schema::hasColumn('pos_terminals', 'status')) {
            $terminal->forceFill(['status' => $turnOn ? 'active' : 'suspended'])->save();
        } else {
            $terminal->forceFill(['is_active' => $turnOn])->save();
        }

        $message = $turnOn ? 'เปิดใช้งาน Terminal สำเร็จ' : 'ปิดใช้งาน Terminal สำเร็จ';

        return back()->with('success', $message);
    }

    /**
     * Terminal เปิดใช้งานอยู่หรือไม่ (รองรับตารางที่มี status หรือ is_active)
     */
    public static function terminalIsActive(PosTerminal $terminal): bool
    {
        $status = $terminal->getAttribute('status');
        if ($status !== null) {
            return $status === 'active';
        }

        return (bool) ($terminal->getAttribute('is_active') ?? false);
    }

    /**
     * Terminal ออนไลน์หรือไม่ — ถือว่าออนไลน์ถ้าเห็นเครื่องหรือซิงก์ภายใน 10 นาที
     */
    public static function terminalIsOnline(PosTerminal $terminal): bool
    {
        $seen = $terminal->getAttribute('last_seen_at') ?? $terminal->getAttribute('last_sync_at');
        if (! $seen) {
            return false;
        }

        return \Illuminate\Support\Carbon::parse($seen)->gt(now()->subMinutes(10));
    }

    /**
     * ลบ Terminal
     */
    public function deleteTerminal(PosTerminal $terminal)
    {
        $store = $this->getStore();

        if ($terminal->shop_id !== $store->id) {
            abort(403, 'ไม่มีสิทธิ์');
        }

        $terminal->delete();

        return back()->with('success', 'ลบ Terminal สำเร็จ');
    }
}
